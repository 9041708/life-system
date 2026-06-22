<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\Database;
use App\Service\Upload;
use App\Model\Transaction;
use App\Model\Category;
use App\Model\Item;
use App\Model\Account;
use App\Model\Goal;
use App\Model\GoalTransactionLink;
use App\Model\TransactionAttachment;
use App\Model\Ledger;
use App\Model\LedgerMember;
use App\Service\LedgerContext;
use PDO;
use App\Model\User;
use App\Service\Logger;

class TransactionController
{
    private function requireLogin(): int
    {
        $uid = (int)($_SESSION['user_id']);
        if ($uid <= 0) {
			header('Location: /public/index.php?route=login');
            exit;
        }
        return $uid;
    }

    /**
     * ?????????????????????? "Y-m-d H:i:s" ???
     * ??????? datetime-local ???????????? 2025-12-30T06:43 ?? 2025-12-30T06:43:13??
     * ?????��? "Y-m-d H:i:s" ???????????
     */
    private function normalizeTransTime(?string $input): string
    {
        $input = trim((string)$input);
        if ($input === '') {
            return date('Y-m-d H:i:s');
        }

        // ???? datetime-local ????��??? "T"
        if (strpos($input, 'T') !== false) {
            $input = str_replace('T', ' ', $input);
        }

        // ??????????? :00
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $input)) {
            $input .= ':00';
        }

        $dt = date_create($input);
        if ($dt === false) {
            return date('Y-m-d H:i:s');
        }
        return $dt->format('Y-m-d H:i:s');
    }

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function buildGoalsByAccount(int $userId, int $ledgerId): array
    {
        $rows = Goal::listByUserAndLedger($userId, $ledgerId > 0 ? $ledgerId : 0);
        $map = [];
        foreach ($rows as $g) {
            $status = (string)($g['status'] ?? 'active');
            if ($status !== 'active') {
                continue;
            }
            $accId = (int)($g['account_id']);
            if ($accId <= 0) {
                continue;
            }
            if (!isset($map[$accId])) {
                $map[$accId] = [];
            }
            $map[$accId][] = [
                'id' => (int)$g['id'],
                'title' => (string)$g['title'],
            ];
        }
        return $map;
    }

    private function validateGoalForAccount(int $userId, int $ledgerId, int $goalId, int $expectedAccountId): int
    {
        if ($goalId <= 0 || $expectedAccountId <= 0) {
            return 0;
        }
        $g = Goal::findOne($userId, $goalId);
        if (!$g) {
            return 0;
        }
        $gAcc = (int)($g['account_id']);
        if ($gAcc !== $expectedAccountId) {
            return 0;
        }
        $gLedgerId = (int)($g['ledger_id']);
        if ($ledgerId > 0) {
            if ($gLedgerId !== $ledgerId) {
                return 0;
            }
        } else {
            // �ɿ���ݣ�Ŀ??ledger_id Ϊ��
            if (!empty($g['ledger_id'])) {
                return 0;
            }
        }
        $status = (string)($g['status'] ?? 'active');
        if ($status !== 'active') {
            return 0;
        }
        return $goalId;
    }

    public function index(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        $currentUser = User::findById($userId);
        $transferEnabled = !empty($currentUser['enable_transfer']);

        $pageIndex = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = (int)($_GET['page_size'] ?? 10);
        $allowedPageSizes = [10, 30, 50, 100];
        $pageSize = in_array($pageSize, $allowedPageSizes, true) ? $pageSize : 10;

        $filters = [
            'type' => $_GET['type'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'item_id' => $_GET['item_id'] ?? '',
            'account_id' => $_GET['account_id'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'amount_min' => $_GET['amount_min'] ?? '',
            'amount_max' => $_GET['amount_max'] ?? '',
            'remark' => $_GET['remark'] ?? '',
        ];

        $totalCount = 0;
        $totalPages = 1;
        $offset = 0;

        if ($ledgerId > 0) {
            $totalCount = Transaction::countByLedger($ledgerId, $filters);
            $totalPages = max(1, (int)ceil($totalCount / $pageSize));
            if ($pageIndex > $totalPages) {
                $pageIndex = $totalPages;
            }
            $offset = ($pageIndex - 1) * $pageSize;

            $transactions = Transaction::searchByLedger($ledgerId, $filters, $pageSize, $offset);
            $summary = Transaction::summarizeByLedger($ledgerId, $filters);

            // ���� / ��Ŀ���ڹ����˱���ֻʹ�õ�ǰ�˱������ã����ٻ��˵����˼�
            $categories = Category::allByLedger($ledgerId);
            $items = Item::allByLedger($ledgerId);
            $accounts = Account::allByLedger($ledgerId);
        } else {
            // �ɿ���ݣ��� ledger �ṹ??
            $totalCount = Transaction::count($userId, $filters);
            $totalPages = max(1, (int)ceil($totalCount / $pageSize));
            if ($pageIndex > $totalPages) {
                $pageIndex = $totalPages;
            }
            $offset = ($pageIndex - 1) * $pageSize;

            $transactions = Transaction::search($userId, $filters, $pageSize, $offset);
            $summary = Transaction::summarize($userId, $filters);
            $categories = Category::allByUser($userId);
            $items = Item::allByUser($userId);
            $accounts = Account::allByUser($userId);
        }

        // 附件信息映射，供 PC 端列表查看缩略图使用
        try {
            $txIds = array_map(static fn($r) => (int)($r['id'] ?? 0), $transactions);
            $attachmentMap = TransactionAttachment::listByTransactionIds($txIds);
            foreach ($transactions as &$tx) {
                $id = (int)($tx['id'] ?? 0);
                $rows = $attachmentMap[$id] ?? [];
                $paths = [];
                foreach ($rows as $r) {
                    $p = trim((string)($r['relative_path'] ?? ''));
                    if ($p !== '') {
                        $paths[] = $p;
                    }
                }
                $tx['attachments'] = $paths;
                // 如果没有主附件路径，则取第一张附件作为缩略图
                if (empty($tx['attachment_path']) && !empty($paths)) {
                    $tx['attachment_path'] = $paths[0];
                }
            }
            unset($tx);
        } catch (\Throwable $e) {
            // 忽略附件加载异常，确保列表继续显示
        }

        $this->render('transactions/index', [
            'transactions' => $transactions,
            'summary' => $summary,
            'filters' => $filters,
            'categories' => $categories,
            'items' => $items,
            'accounts' => $accounts,
            'totalCount' => $totalCount,
            'pageIndex' => $pageIndex,
            'pageSize' => $pageSize,
            'totalPages' => $totalPages,
            'transferEnabled' => $transferEnabled,
        ]);
    }

    public function create(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        $currentUser = User::findById($userId);
        $transferEnabled = !empty($currentUser['enable_transfer']);
        $allowNegative = !empty($currentUser['allow_negative_balance']);

        if ($ledgerId > 0) {
            $categories = Category::allByLedger($ledgerId);
            $items = Item::allByLedger($ledgerId);
            $accounts = Account::allByLedger($ledgerId);
        } else {
            $categories = Category::allByUser($userId);
            $items = Item::allByUser($userId);
            $accounts = Account::allByUser($userId);
        }

        $today = date('Y-m-d');
        $todayFilters = [
            'type' => '',
            'category_id' => '',
            'item_id' => '',
            'account_id' => '',
            'date_from' => $today,
            'date_to' => $today,
            'amount_min' => '',
            'amount_max' => '',
            'remark' => '',
        ];
        $todayTransactions = $ledgerId > 0
            ? Transaction::searchByLedger($ledgerId, $todayFilters)
            : Transaction::search($userId, $todayFilters);

        $goalsByAccount = $this->buildGoalsByAccount($userId, $ledgerId);
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['saved'] ?? '') === '1') {
            $success = '保存成功。';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type'] ?? 'expense';
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $itemId = (int)($_POST['item_id'] ?? 0);
            $fromAccountId = (int)($_POST['from_account_id'] ?? 0);
            $toAccountId = (int)($_POST['to_account_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $remark = trim($_POST['remark'] ?? '');
            $transTime = $this->normalizeTransTime($_POST['trans_time'] ?? '');
            $source = strtolower(trim((string)($_POST['source'] ?? 'manual')));
            if (!in_array($source, ['manual', 'ai', 'qclaw'], true)) {
                $source = 'manual';
            }

            if ($amount <= 0) {
                $error = '金额必须大于 0。';
            } elseif (!$categoryId) {
                $error = '请选择分类。';
            } else {
                if ($type === 'expense' && !$fromAccountId) {
                    $error = '支出需要选择支出账户。';
                } elseif ($type === 'income' && !$toAccountId) {
                    $error = '收入需要选择收入账户。';
                } elseif ($type === 'transfer') {
                    if (!$transferEnabled) {
                        $error = '当前账户未开启转账功能，请先启用转账。';
                    } elseif (!$fromAccountId || !$toAccountId) {
                        $error = '转账时请选择出账账户和收入账户。';
                    } elseif ($fromAccountId === $toAccountId) {
                        $error = '转出账户和转入账户不能相同。';
                    }
                }

                if (!$error && $type === 'expense' && !$allowNegative && $fromAccountId) {
                    $account = $ledgerId > 0
                        ? Account::findByLedger($ledgerId, $fromAccountId)
                        : Account::findByUser($userId, $fromAccountId);
                    if ($account) {
                        $currentBalance = (float)($account['current_balance'] ?? 0);
                        if ($currentBalance - $amount < 0) {
                            $error = '账户余额不足，无法保存该支出。';
                        }
                    }
                }
            }

            $attachmentPath = null;
            $attachmentPaths = [];
            if (!$error) {
                if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'] ?? null)) {
                    $fileCount = count($_FILES['attachments']['name']);
                    $max = min(5, $fileCount);
                    for ($i = 0; $i < $max; $i++) {
                        $file = [
                            'name' => $_FILES['attachments']['name'][$i] ?? null,
                            'type' => $_FILES['attachments']['type'][$i] ?? null,
                            'tmp_name' => $_FILES['attachments']['tmp_name'][$i] ?? null,
                            'error' => $_FILES['attachments']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                            'size' => $_FILES['attachments']['size'][$i] ?? 0,
                        ];
                        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                            continue;
                        }
                        $saved = Upload::saveAttachment($userId, $file);
                        if ($saved === null) {
                            $error = '图片上传失败或超过 10MB 限制。';
                            break;
                        }
                        $attachmentPaths[] = $saved;
                    }
                } elseif (isset($_FILES['attachment']) && ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $saved = Upload::saveAttachment($userId, $_FILES['attachment']);
                    if ($saved === null) {
                        $error = '图片上传失败或超过 10MB 限制。';
                    } else {
                        $attachmentPath = $saved;
                        $attachmentPaths = [$saved];
                    }
                }
            }

            if (!$error) {
                $inSync = (int)($_POST['goal_in_sync'] ?? 0) === 1;
                $outSync = (int)($_POST['goal_out_sync'] ?? 0) === 1;
                $goalInId = $inSync ? (int)($_POST['goal_in_id'] ?? 0) : 0;
                $goalOutId = $outSync ? (int)($_POST['goal_out_id'] ?? 0) : 0;

                if ($type === 'income' && $inSync) {
                    if ($this->validateGoalForAccount($userId, $ledgerId, $goalInId, $toAccountId) <= 0) {
                        $error = '未找到对应目标，请选择与入账账户匹配的有效目标。';
                    }
                }
                if ($type === 'expense' && $outSync) {
                    if ($this->validateGoalForAccount($userId, $ledgerId, $goalOutId, $fromAccountId) <= 0) {
                        $error = '未找到对应目标，请选择与出账账户匹配的有效目标。';
                    }
                }
                if ($type === 'transfer') {
                    if ($inSync && $this->validateGoalForAccount($userId, $ledgerId, $goalInId, $toAccountId) <= 0) {
                        $error = '未找到对应入账目标，请选择与入账账户匹配的有效目标。';
                    }
                    if (!$error && $outSync && $this->validateGoalForAccount($userId, $ledgerId, $goalOutId, $fromAccountId) <= 0) {
                        $error = '未找到对应出账目标，请选择与出账账户匹配的有效目标。';
                    }
                }
            }

            if (!$error) {
                $data = [
                    'user_id' => $userId,
                    'ledger_id' => $ledgerId > 0 ? $ledgerId : null,
                    'type' => $type,
                    'category_id' => $categoryId,
                    'item_id' => $itemId ?: null,
                    'from_account_id' => $fromAccountId ?: null,
                    'to_account_id' => $toAccountId ?: null,
                    'amount' => $amount,
                    'trans_time' => $transTime,
                    'remark' => $remark,
                    'source' => $source,
                    'attachment_path' => $attachmentPath,
                ];

                $this->applyBalanceChange($type, $fromAccountId, $toAccountId, $amount, 1);
                $txId = Transaction::create($data);
                if ($txId > 0 && $attachmentPaths) {
                    TransactionAttachment::replaceForTransaction($txId, $attachmentPaths);
                }

                if ($txId > 0) {
                    // 记录创建交易日志
                    $typeLabel = $type === 'income' ? '收入' : ($type === 'expense' ? '支出' : '转账');
                    Logger::log('创建交易', "创建{$typeLabel}交易，金额：{$amount}，备注：{$remark}", $userId, $_SESSION['user_nickname'] ?? null);

                    // 处理报销
                    $needsReimb = !empty($_POST['needs_reimbursement']);
                    $type = $_POST['type'] ?? '';
                    if ($needsReimb && $type === 'expense') {
                        // 支出：创建待报销记录
                        $reimbId = \App\Model\Reimbursement::create($userId, $ledgerId, [
                            'transaction_id' => $txId,
                            'category_id' => $categoryId,
                            'amount' => $amount,
                            'description' => $remark,
                        ]);
                        if ($reimbId > 0) {
                            Logger::log('创建报销记录', "从交易 #$txId 创建报销记录 #$reimbId", $userId, $_SESSION['user_nickname'] ?? null);
                        }
                    } elseif ($type === 'income' && !empty($_POST['reimb_id'])) {
                        // 收入：抵消用户指定的待报销记录
                        $reimbId = (int)$_POST['reimb_id'];
                        $updated = \App\Model\Reimbursement::updateStatus($reimbId, $ledgerId, \App\Model\Reimbursement::STATUS_REIMBURSED, $userId);
                        if ($updated) {
                            Logger::log('标记已报销', "收入交易 #$txId 抵消待报销记录 #$reimbId，标记为已报销", $userId, $_SESSION['user_nickname'] ?? null);
                        } else {
                            Logger::log('报销抵消失败', "收入交易 #$txId 抵消待报销记录 #$reimbId 失败", $userId, $_SESSION['user_nickname'] ?? null);
                        }
                    }

                    $goalIds = [];
                    if ($type === 'income' && $inSync) {
                        $gid = $this->validateGoalForAccount($userId, $ledgerId, $goalInId, $toAccountId);
                        if ($gid > 0) {
                            $goalIds[] = $gid;
                        }
                    } elseif ($type === 'expense' && $outSync) {
                        $gid = $this->validateGoalForAccount($userId, $ledgerId, $goalOutId, $fromAccountId);
                        if ($gid > 0) {
                            $goalIds[] = $gid;
                        }
                    } elseif ($type === 'transfer') {
                        $gidIn = $this->validateGoalForAccount($userId, $ledgerId, $goalInId, $toAccountId);
                        $gidOut = $this->validateGoalForAccount($userId, $ledgerId, $goalOutId, $fromAccountId);
                        if ($gidIn > 0) {
                            $goalIds[] = $gidIn;
                        }
                        if ($gidOut > 0) {
                            $goalIds[] = $gidOut;
                        }
                    }

                    if ($goalIds) {
                        GoalTransactionLink::replaceLinksForTransaction($txId, $goalIds);
                    }
                }

                header('Location: /public/index.php?route=transaction-create&saved=1');
                exit;
            }
        }

        $this->render('transactions/form', [
            'mode' => 'create',
            'error' => $error,
            'success' => $success,
            'categories' => $categories,
            'items' => $items,
            'accounts' => $accounts,
            'todayTransactions' => $todayTransactions,
            'transferEnabled' => $transferEnabled,
            'goalsByAccount' => $goalsByAccount,
            'reimbursementEnabled' => \App\Model\ReimbursementConfig::isEnabled($ledgerId),
            'pendingReimbursements' => \App\Model\ReimbursementConfig::isEnabled($ledgerId) ? \App\Model\Reimbursement::getPending($ledgerId) : [],
        ]);
    }

    public function edit(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        $currentUser = User::findById($userId);
        $transferEnabled = !empty($currentUser['enable_transfer']);
        $allowNegative = !empty($currentUser['allow_negative_balance']);
        $id = (int)($_GET['id'] ?? 0);

        if ($ledgerId > 0 && !LedgerContext::assertCanAccessLedger($userId, $ledgerId)) {
            http_response_code(403);
            echo '没有权限访问该账本。';
            return;
        }

        $transaction = $ledgerId > 0
            ? Transaction::findByIdInLedger($id, $ledgerId)
            : Transaction::findById($id, $userId);
        if (!$transaction) {
            http_response_code(404);
            echo '交易记录不存在。';
            return;
        }

        // 检查是否已创建报销记录
        $reimb = \App\Model\Reimbursement::findByTransactionId($id);
        $transaction['needs_reimbursement'] = $reimb ? 1 : 0;
        $canEditOthers = false;
        if ($ledgerId > 0) {
            $ledger = Ledger::findById($ledgerId);
            if ($ledger && (int)($ledger['owner_user_id'] ?? 0) === $userId) {
                $canEditOthers = true;
            } else {
                try {
                    $role = LedgerMember::getRole($ledgerId, $userId);
                    $canEditOthers = ($role === 'admin');
                } catch (\Throwable $e) {
                    $canEditOthers = false;
                }
            }

            if (!$canEditOthers && (int)($transaction['user_id'] ?? 0) !== $userId) {
                http_response_code(403);
                echo '没有权限编辑此记录。';
                return;
            }
        }

        try {
            $attMap = TransactionAttachment::listByTransactionIds([$id]);
            $transaction['attachments'] = array_values(array_map(static function ($r) {
                return (string)($r['relative_path'] ?? '');
            }, $attMap[$id] ?? []));
            $transaction['attachments'] = array_values(array_filter($transaction['attachments'], static fn($p) => $p !== ''));
        } catch (\Throwable $e) {
            // ignore attachment loading errors
        }

        $categories = $ledgerId > 0 ? Category::allByLedger($ledgerId) : Category::allByUser($userId);
        $items = $ledgerId > 0 ? Item::allByLedger($ledgerId) : Item::allByUser($userId);
        $accounts = $ledgerId > 0 ? Account::allByLedger($ledgerId) : Account::allByUser($userId);

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type'] ?? ($transaction['type'] ?? 'expense');
            $categoryId = (int)($_POST['category_id'] ?? ($transaction['category_id'] ?? 0));
            $itemId = (int)($_POST['item_id'] ?? ($transaction['item_id'] ?? 0));
            $fromAccountId = (int)($_POST['from_account_id'] ?? ($transaction['from_account_id'] ?? 0));
            $toAccountId = (int)($_POST['to_account_id'] ?? ($transaction['to_account_id'] ?? 0));
            $amount = (float)($_POST['amount'] ?? ($transaction['amount'] ?? 0));
            $remark = trim($_POST['remark'] ?? ($transaction['remark'] ?? ''));
            $transTime = $this->normalizeTransTime($_POST['trans_time'] ?? ($transaction['trans_time'] ?? ''));
            $source = isset($_POST['source'])
                ? strtolower(trim((string)$_POST['source']))
                : (string)($transaction['source'] ?? 'manual');
            if (!in_array($source, ['manual', 'ai', 'qclaw'], true)) {
                $source = (string)($transaction['source'] ?? 'manual');
            }
            $removeAttachment = !empty($_POST['remove_attachment']);

            if ($amount <= 0) {
                $error = '金额必须大于0。';
            } elseif (!$categoryId) {
                $error = '请选择分类。';
            } else {
                if ($type === 'expense' && !$fromAccountId) {
                    $error = '支出需要选择支出账户。';
                } elseif ($type === 'income' && !$toAccountId) {
                    $error = '收入需要选择收入账户。';
                } elseif ($type === 'transfer') {
                    if (!$transferEnabled && !$isEditingTransfer) {
                        $error = '当前账户未启用转账功能，无法编辑转账记录。';
                    } elseif (!$fromAccountId || !$toAccountId) {
                        $error = '转账时请选择出账账户和收入账户。';
                    } elseif ($fromAccountId === $toAccountId) {
                        $error = '转出账户和转入账户不能相同。';
                    }
                }

                if (!$error && $type === 'expense' && !$allowNegative && $fromAccountId) {
                    $account = $ledgerId > 0
                        ? Account::findByLedger($ledgerId, $fromAccountId)
                        : Account::findByUser($userId, $fromAccountId);
                    if ($account) {
                        $currentBalance = (float)($account['current_balance'] ?? 0);
                        if ($currentBalance - $amount < 0) {
                            $error = '账户余额不足，无法保存此笔支出。';
                        }
                    }
                }
            }

            $attachmentPath = $transaction['attachment_path'] ?? null;
            $attachmentPaths = [];
            if (!empty($transaction['attachments']) && is_array($transaction['attachments'])) {
                $attachmentPaths = $transaction['attachments'];
            } elseif (!empty($attachmentPath)) {
                $attachmentPaths = [$attachmentPath];
            }

            if (!$error) {
                $baseDir = Config::get('app.upload_dir');

                if ($removeAttachment && $baseDir) {
                    foreach ($attachmentPaths as $p) {
                        $fullOld = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $p;
                        if (is_file($fullOld)) {
                            @unlink($fullOld);
                        }
                    }
                    $attachmentPath = null;
                    $attachmentPaths = [];
                }

                $newAttachmentPaths = [];
                if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'] ?? null)) {
                    $fileCount = count($_FILES['attachments']['name']);
                    $max = min(5, $fileCount);
                    for ($i = 0; $i < $max; $i++) {
                        $file = [
                            'name' => $_FILES['attachments']['name'][$i] ?? null,
                            'type' => $_FILES['attachments']['type'][$i] ?? null,
                            'tmp_name' => $_FILES['attachments']['tmp_name'][$i] ?? null,
                            'error' => $_FILES['attachments']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                            'size' => $_FILES['attachments']['size'][$i] ?? 0,
                        ];
                        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                            continue;
                        }
                        $saved = Upload::saveAttachment($userId, $file);
                        if ($saved === null) {
                            $error = '图片上传失败或超过 10MB 限制。';
                            break;
                        }
                        $newAttachmentPaths[] = $saved;
                    }
                } elseif (isset($_FILES['attachment']) && ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $saved = Upload::saveAttachment($userId, $_FILES['attachment']);
                    if ($saved === null) {
                        $error = '图片上传失败或超过 10MB 限制。';
                    } else {
                        $newAttachmentPaths = [$saved];
                    }
                }

                if (!$error && $newAttachmentPaths) {
                    if ($baseDir) {
                        foreach ($attachmentPaths as $p) {
                            $fullOld = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $p;
                            if (is_file($fullOld)) {
                                @unlink($fullOld);
                            }
                        }
                    }
                    $attachmentPaths = $newAttachmentPaths;
                    $attachmentPath = $attachmentPaths[0] ?? null;
                }
            }

            if (!$error) {
                $this->applyBalanceChange((string)($transaction['type'] ?? ''), (int)($transaction['from_account_id'] ?? 0), (int)($transaction['to_account_id'] ?? 0), (float)($transaction['amount'] ?? 0), -1);
                $this->applyBalanceChange($type, $fromAccountId, $toAccountId, $amount, 1);

                $data = [
                    'type' => $type,
                    'category_id' => $categoryId,
                    'item_id' => $itemId ?: null,
                    'from_account_id' => $fromAccountId ?: null,
                    'to_account_id' => $toAccountId ?: null,
                    'amount' => $amount,
                    'trans_time' => $transTime,
                    'remark' => $remark,
                    'source' => $source,
                    'attachment_path' => $attachmentPath,
                ];
                if ($ledgerId > 0) {
                    Transaction::updateInLedger($id, $ledgerId, $data);
                } else {
                    Transaction::update($id, $userId, $data);
                }
                TransactionAttachment::replaceForTransaction($id, $attachmentPaths);

                // 记录更新交易日志
                $typeLabel = $type === 'income' ? '收入' : ($type === 'expense' ? '支出' : '转账');
                Logger::log('更新交易', "更新{$typeLabel}交易 ID:{$id}，金额：{$amount}，备注：{$remark}", $userId, $_SESSION['user_nickname'] ?? null);

                // 处理报销
                $type = $_POST['type'] ?? '';
                $needsReimb = !empty($_POST['needs_reimbursement']);
                
                if ($type === 'expense') {
                    $reimb = \App\Model\Reimbursement::findByTransactionId($id);
                    if ($needsReimb && !$reimb) {
                        // 勾选了需要报销，创建一个待报销记录
                        $reimbId = \App\Model\Reimbursement::create($userId, $ledgerId, [
                            'transaction_id' => $id,
                            'category_id' => $categoryId,
                            'amount' => $amount,
                            'description' => $remark,
                        ]);
                        if ($reimbId > 0) {
                            Logger::log('创建报销记录', "从交易 #$id 创建报销记录 #$reimbId", $userId, $_SESSION['user_nickname'] ?? null);
                        }
                    } elseif (!$needsReimb && $reimb) {
                        // 取消了报销勾选，删除报销记录
                        $pdo = \App\Service\Database::getConnection();
                        $stmt = $pdo->prepare('DELETE FROM reimbursement WHERE id = :id');
                        $stmt->execute([':id' => $reimb['id']]);
                        if ($stmt->rowCount() > 0) {
                            Logger::log('删除报销记录', "删除交易 #$id 的报销记录 #{$reimb['id']}", $userId, $_SESSION['user_nickname'] ?? null);
                        }
                    }
                } elseif ($type === 'income' && !empty($_POST['reimb_id'])) {
                    // 收入：抵消用户指定的待报销记录
                    $reimbId = (int)$_POST['reimb_id'];
                    $updated = \App\Model\Reimbursement::updateStatus($reimbId, $ledgerId, \App\Model\Reimbursement::STATUS_REIMBURSED, $userId);
                    if ($updated) {
                        Logger::log('标记已报销', "收入交易 #$id 抵消待报销记录 #$reimbId，标记为已报销", $userId, $_SESSION['user_nickname'] ?? null);
                    } else {
                        Logger::log('报销抵消失败', "收入交易 #$id 抵消待报销记录 #$reimbId 失败", $userId, $_SESSION['user_nickname'] ?? null);
                    }
                }

                $goalIds = [];
                $inSync = (int)($_POST['goal_in_sync'] ?? 0) === 1;
                $outSync = (int)($_POST['goal_out_sync'] ?? 0) === 1;
                $goalInId = $inSync ? (int)($_POST['goal_in_id'] ?? 0) : 0;
                $goalOutId = $outSync ? (int)($_POST['goal_out_id'] ?? 0) : 0;

                if ($type === 'income' && $inSync) {
                    $gid = $this->validateGoalForAccount($userId, $ledgerId, $goalInId, $toAccountId);
                    if ($gid > 0) {
                        $goalIds[] = $gid;
                    }
                } elseif ($type === 'expense' && $outSync) {
                    $gid = $this->validateGoalForAccount($userId, $ledgerId, $goalOutId, $fromAccountId);
                    if ($gid > 0) {
                        $goalIds[] = $gid;
                    }
                } elseif ($type === 'transfer') {
                    $gidIn = $inSync ? $this->validateGoalForAccount($userId, $ledgerId, $goalInId, $toAccountId) : 0;
                    $gidOut = $outSync ? $this->validateGoalForAccount($userId, $ledgerId, $goalOutId, $fromAccountId) : 0;
                    if ($gidIn > 0) {
                        $goalIds[] = $gidIn;
                    }
                    if ($gidOut > 0) {
                        $goalIds[] = $gidOut;
                    }
                }

                GoalTransactionLink::replaceLinksForTransaction($id, $goalIds);

                $from = $_REQUEST['from'] ?? '';
                if ($from === 'create') {
                    header('Location: /public/index.php?route=transaction-create');
                } else {
                    header('Location: /public/index.php?route=transactions');
                }
                exit;
            }
        }

        $goalsByAccount = $this->buildGoalsByAccount($userId, $ledgerId);
        $linkedGoalIds = GoalTransactionLink::listGoalIdsByTransactionId($id);
        $goalInId = 0;
        $goalOutId = 0;
        $fromAccountId0 = (int)($transaction['from_account_id'] ?? 0);
        $toAccountId0 = (int)($transaction['to_account_id'] ?? 0);
        foreach ($linkedGoalIds as $gid) {
            $g = Goal::findOne($userId, (int)$gid);
            if (!$g) {
                continue;
            }
            $acc = (int)($g['account_id'] ?? 0);
            if ($acc > 0 && $acc === $toAccountId0 && $goalInId === 0) {
                $goalInId = (int)$gid;
            }
            if ($acc > 0 && $acc === $fromAccountId0 && $goalOutId === 0) {
                $goalOutId = (int)$gid;
            }
        }

        $this->render('transactions/form', [
            'mode' => 'edit',
            'error' => $error,
            'transaction' => $transaction,
            'categories' => $categories,
            'items' => $items,
            'accounts' => $accounts,
            'todayTransactions' => [],
            'transferEnabled' => ($transferEnabled || $isEditingTransfer),
            'goalsByAccount' => $goalsByAccount,
            'goalInId' => $goalInId,
            'goalOutId' => $goalOutId,
            'reimbursementEnabled' => \App\Model\ReimbursementConfig::isEnabled($ledgerId),
            'pendingReimbursements' => \App\Model\ReimbursementConfig::isEnabled($ledgerId) ? \App\Model\Reimbursement::getPending($ledgerId) : [],
        ]);
    }

    public function delete(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        if ($ledgerId > 0 && !LedgerContext::assertCanAccessLedger($userId, $ledgerId)) {
            http_response_code(403);
            echo '没有权限访问该账本。';
            return;
        }

        $canEditOthers = false;
        if ($ledgerId > 0) {
            $ledger = Ledger::findById($ledgerId);
            if ($ledger && (int)($ledger['owner_user_id'] ?? 0) === $userId) {
                $canEditOthers = true;
            } else {
                try {
                    $role = LedgerMember::getRole($ledgerId, $userId);
                    $canEditOthers = ($role === 'admin');
                } catch (\Throwable $e) {
                    $canEditOthers = false;
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ids = isset($_POST['ids']) ? (array)$_POST['ids'] : [];
            foreach ($ids as $id) {
                $id = (int)$id;
                $tx = $ledgerId > 0
                    ? Transaction::findByIdInLedger($id, $ledgerId)
                    : Transaction::findById($id, $userId);
                if ($tx) {
                    if ($ledgerId > 0 && !$canEditOthers && (int)($tx['user_id'] ?? 0) !== $userId) {
                        http_response_code(403);
                        echo '没有权限删除此记录。';
                        return;
                    }
                    $this->applyBalanceChange((string)($tx['type'] ?? ''), (int)($tx['from_account_id'] ?? 0), (int)($tx['to_account_id'] ?? 0), (float)($tx['amount'] ?? 0), -1);
                }
            }

            if ($ledgerId > 0) {
                Transaction::deleteManyInLedger($ledgerId, array_map('intval', $ids));
            } else {
                Transaction::deleteMany($userId, array_map('intval', $ids));
            }

            GoalTransactionLink::deleteByTransactionIds($ids);

            // 删除关联的待报销记录
            $intIds = array_map('intval', $ids);
            $pdo = \App\Service\Database::getConnection();
            $stmt = $pdo->prepare('DELETE FROM reimbursement WHERE transaction_id IN (' . implode(',', array_fill(0, count($intIds), '?')) . ') AND status IN ("pending", "approved")');
            $stmt->execute($intIds);
            if ($stmt->rowCount() > 0) {
                Logger::log('删除报销记录', "删除交易 #{$idsStr} 关联的 {$stmt->rowCount()} 条待报销记录", $userId, $_SESSION['user_nickname'] ?? null);
            }

            // 记录删除交易日志
            $idsStr = implode(',', array_map('intval', $ids));
            Logger::log('删除交易', "删除交易 ID:{$idsStr}", $userId, $_SESSION['user_nickname'] ?? null);
        }

        $from = $_POST['from'] ?? ($_GET['from'] ?? '');
        if ($from === 'create') {
            header('Location: /public/index.php?route=transaction-create');
        } else {
            header('Location: /public/index.php?route=transactions');
        }
        exit;
    }

    public function export(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        $filters = [
            'type' => $_GET['type'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'item_id' => $_GET['item_id'] ?? '',
            'account_id' => $_GET['account_id'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'amount_min' => $_GET['amount_min'] ?? '',
            'amount_max' => $_GET['amount_max'] ?? '',
            'remark' => $_GET['remark'] ?? '',
        ];

        $rows = $ledgerId > 0 ? Transaction::searchByLedger($ledgerId, $filters) : Transaction::search($userId, $filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transactions_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['类型', '分类', '项目', '账户', '金额', '时间', '备注']);
        foreach ($rows as $r) {
            $typeLabel = $r['type'] === 'expense' ? '支出' : ($r['type'] === 'income' ? '收入' : '转账');
            if ($r['type'] === 'transfer') {
                $accountLabel = trim((string)($r['from_account_name'] ?? '')) . ' -> ' . trim((string)($r['to_account_name'] ?? ''));
            } elseif ($r['type'] === 'expense') {
                $accountLabel = $r['from_account_name'] ?? '';
            } else {
                $accountLabel = $r['to_account_name'] ?? '';
            }
            fputcsv($out, [
                $typeLabel,
                $r['category_name'] ?? '',
                $r['item_name'] ?? '',
                $accountLabel,
                $r['amount'],
                $r['trans_time'],
                $r['remark'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    private function applyBalanceChange(string $type, int $fromAccountId, int $toAccountId, float $amount, int $direction): void
    {
        if ($amount <= 0) {
            return;
        }

        $delta = $amount * $direction;
        if ($type === 'expense') {
            if ($fromAccountId) {
                Account::adjustBalance($fromAccountId, -$delta);
            }
        } elseif ($type === 'income') {
            if ($toAccountId) {
                Account::adjustBalance($toAccountId, $delta);
            }
        } elseif ($type === 'transfer') {
            if ($fromAccountId) {
                Account::adjustBalance($fromAccountId, -$delta);
            }
            if ($toAccountId) {
                Account::adjustBalance($toAccountId, $delta);
            }
        }
    }
}

