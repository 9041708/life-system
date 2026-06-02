<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\LedgerContext;
use App\Model\Goal;
use App\Model\Account;

class GoalController
{
    private function requireLogin(): int
    {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: /public/index.php?route=login');
            exit;
        }
        return $uid;
    }

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/layout_main.php';
    }

    public function index(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }
        if ($ledgerId < 0) {
            $ledgerId = 0;
        }

        // 账户列表：当前账本优先，如账本下暂无账户则回退到用户级账户
        if ($ledgerId > 0) {
            $accounts = Account::allByLedger($ledgerId);
            if (empty($accounts)) {
                $accounts = Account::allByUser($userId);
            }
        } else {
            $accounts = Account::allByUser($userId);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $title = trim((string)($_POST['title'] ?? ''));
                $accountId = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
                $targetAmount = isset($_POST['target_amount']) ? (float)$_POST['target_amount'] : 0.0;
                $savedAmount = isset($_POST['saved_amount']) ? (float)$_POST['saved_amount'] : 0.0;
                $deadline = trim((string)($_POST['deadline'] ?? ''));

                if ($title !== '' && $targetAmount > 0) {
                    if ($savedAmount < 0) {
                        $savedAmount = 0.0;
                    }
                    if ($savedAmount > $targetAmount) {
                        $savedAmount = $targetAmount;
                    }
                    if ($accountId > 0) {
                        // 简单校验账户是否属于当前用户/账本
                        try {
                            $acc = $ledgerId > 0
                                ? Account::findByLedger($ledgerId, $accountId)
                                : Account::findByUser($userId, $accountId);
                            if (!$acc) {
                                $accountId = 0;
                            }
                        } catch (\Throwable $e) {
                            $accountId = 0;
                        }
                    }
                    Goal::create($userId, $ledgerId > 0 ? $ledgerId : 0, $accountId, $title, $targetAmount, $savedAmount, $deadline !== '' ? $deadline : null);
                }
            } elseif ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $title = trim((string)($_POST['title'] ?? ''));
                $accountId = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
                $targetAmount = isset($_POST['target_amount']) ? (float)$_POST['target_amount'] : 0.0;
                $savedAmount = isset($_POST['saved_amount']) ? (float)$_POST['saved_amount'] : 0.0;
                $deadline = trim((string)($_POST['deadline'] ?? ''));
                $status = trim((string)($_POST['status'] ?? 'active'));

                if ($id > 0 && $title !== '' && $targetAmount > 0) {
                    if ($savedAmount < 0) {
                        $savedAmount = 0.0;
                    }
                    if ($savedAmount > $targetAmount) {
                        $savedAmount = $targetAmount;
                    }
                    if (!in_array($status, ['active', 'done', 'archived'], true)) {
                        $status = 'active';
                    }
                    if ($accountId > 0) {
                        try {
                            $acc = $ledgerId > 0
                                ? Account::findByLedger($ledgerId, $accountId)
                                : Account::findByUser($userId, $accountId);
                            if (!$acc) {
                                $accountId = 0;
                            }
                        } catch (\Throwable $e) {
                            $accountId = 0;
                        }
                    }
                    Goal::update($userId, $id, $accountId, $title, $targetAmount, $savedAmount, $deadline !== '' ? $deadline : null, $status);
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    Goal::delete($userId, $id);
                }
            }

            header('Location: /public/index.php?route=goals');
            exit;
        }

        $rows = Goal::listByUserAndLedger($userId, $ledgerId > 0 ? $ledgerId : 0);
        // 账户映射，方便展示
        $accountLabels = [];
        foreach ($accounts as $a) {
            $label = (string)($a['name'] ?? '');
            $group = (string)($a['group_name'] ?? '');
            if ($group !== '') {
                $label = '[' . $group . '] ' . $label;
            }
            $accountLabels[(int)$a['id']] = $label;
        }
        $goals = [];
        $totalTarget = 0.0;
        $totalSaved = 0.0;
        foreach ($rows as $g) {
            $target = (float)$g['target_amount'];
            $saved = (float)$g['saved_amount'];
            $percent = $target > 0 ? min(999, round($saved / $target * 100)) : 0;
            $bar = min(100, $percent);
            $status = (string)($g['status'] ?? 'active');

            $accId = isset($g['account_id']) ? (int)$g['account_id'] : 0;
            $goals[] = [
                'id' => (int)$g['id'],
                'title' => (string)$g['title'],
                'account_id' => $accId,
                'account_label' => $accId > 0 && isset($accountLabels[$accId]) ? $accountLabels[$accId] : '',
                'target_amount' => $target,
                'saved_amount' => $saved,
                'deadline' => $g['deadline'],
                'status' => $status,
                'percent' => $percent,
                'barPercent' => $bar,
            ];

            $totalTarget += $target;
            $totalSaved += $saved;
        }

        $this->render('goals/index', [
            'goals' => $goals,
            'totalTarget' => $totalTarget,
            'totalSaved' => $totalSaved,
            'accounts' => $accounts,
        ]);
    }
}
