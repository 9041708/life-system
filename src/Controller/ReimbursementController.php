<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\LedgerContext;
use App\Service\Database;
use App\Service\Logger;
use App\Model\Reimbursement;
use App\Model\ReimbursementConfig;
use App\Model\Category;

/**
 * 报销管理控制器
 */
class ReimbursementController
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
        $view = 'reimbursement/' . $view;
        include __DIR__ . '/../../templates/layout_main.php';
    }

    /**
     * 待报销列表
     */
    public function pending(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        $items = Reimbursement::getPending($ledgerId);
        $overview = Reimbursement::getOverview($ledgerId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $title = trim($_POST['title'] ?? '');
                $amount = (float)($_POST['amount'] ?? 0);
                if ($title !== '' && $amount > 0) {
                    Reimbursement::create($userId, $ledgerId, [
                        'title'          => $title,
                        'amount'         => $amount,
                        'category_id'    => (int)($_POST['category_id'] ?? 0) ?: null,
                        'transaction_id' => (int)($_POST['transaction_id'] ?? 0) ?: null,
                        'description'    => trim($_POST['description'] ?? ''),
                    ]);
                    Logger::log('创建报销记录', "创建报销记录：{$title}，金额：{$amount}", $userId, $_SESSION['user_nickname'] ?? null);
                }
                header('Location: /public/index.php?route=reimbursement-pending');
                exit;
            }

            if ($action === 'mark-reimbursed') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    Reimbursement::updateStatus($id, $ledgerId, Reimbursement::STATUS_REIMBURSED, $userId);
                    Logger::log('标记已报销', "报销记录 #{$id} 已标记为已报销", $userId, $_SESSION['user_nickname'] ?? null);
                }
                header('Location: /public/index.php?route=reimbursement-pending');
                exit;
            }

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    Reimbursement::delete($id, $ledgerId);
                    Logger::log('删除报销记录', "删除报销记录 #{$id}", $userId, $_SESSION['user_nickname'] ?? null);
                }
                header('Location: /public/index.php?route=reimbursement-pending');
                exit;
            }
        }

        $this->render('pending', [
            'items'    => $items,
            'overview' => $overview,
        ]);
    }

    /**
     * 已报销列表
     */
    public function completed(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        $items = Reimbursement::getReimbursed($ledgerId);

        $this->render('completed', [
            'items' => $items,
        ]);
    }

    /**
     * 报销统计
     */
    public function statistics(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        $overview = Reimbursement::getOverview($ledgerId);
        $monthly  = Reimbursement::getMonthlyStats($ledgerId);
        $category = Reimbursement::getCategoryStats($ledgerId);

        $this->render('statistics', [
            'overview' => $overview,
            'monthly'  => $monthly,
            'category' => $category,
        ]);
    }

    /**
     * 报销情况（合并页面）
     */
    public function index(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        // 获取筛选参数
        $currentMonth = trim($_GET['month'] ?? '');
        $currentStatus = trim($_GET['status'] ?? '');
        $search = trim($_GET['search'] ?? '');

        // 获取所有月份（用于筛选下拉）
        $months = Reimbursement::getMonths($ledgerId);

        // 获取所有记录
        $allItems = Reimbursement::getAll($ledgerId);

        // 应用筛选
        $items = array_filter($allItems, function ($item) use ($currentMonth, $currentStatus, $search) {
            // 月份筛选
            if ($currentMonth !== '') {
                $createdAt = substr($item['created_at'], 0, 7); // YYYY-MM
                if ($createdAt !== $currentMonth) {
                    return false;
                }
            }
            // 状态筛选
            if ($currentStatus !== '' && $item['status'] !== $currentStatus) {
                return false;
            }
            // 搜索
            if ($search !== '') {
                $title = strtolower($item['title'] ?? '');
                $desc = strtolower($item['description'] ?? '');
                $keyword = strtolower($search);
                if (strpos($title, $keyword) === false && strpos($desc, $keyword) === false) {
                    return false;
                }
            }
            return true;
        });

        // 排序：未报销优先，然后按创建时间倒序
        usort($items, function ($a, $b) {
            $statusOrder = [
                'pending'   => 0,
                'approved'  => 1,
                'rejected'  => 2,
                'reimbursed'=> 3,
            ];
            $orderA = $statusOrder[$a['status']] ?? 4;
            $orderB = $statusOrder[$b['status']] ?? 4;
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });

        // 计算总金额
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += (float)$item['amount'];
        }

        // 获取概览数据
        $overview = Reimbursement::getOverview($ledgerId);

        // 处理 POST 操作
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $title = trim($_POST['title'] ?? '');
                $amount = (float)($_POST['amount'] ?? 0);
                if ($title !== '' && $amount > 0) {
                    Reimbursement::create($userId, $ledgerId, [
                        'title'          => $title,
                        'amount'         => $amount,
                        'category_id'    => (int)($_POST['category_id'] ?? 0) ?: null,
                        'transaction_id' => (int)($_POST['transaction_id'] ?? 0) ?: null,
                        'description'    => trim($_POST['description'] ?? ''),
                    ]);
                    Logger::log('创建报销记录', "创建报销记录：{$title}，金额：{$amount}", $userId, $_SESSION['user_nickname'] ?? null);
                }
                header('Location: /public/index.php?route=reimbursement');
                exit;
            }

            if ($action === 'mark-reimbursed') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    Reimbursement::updateStatus($id, $ledgerId, Reimbursement::STATUS_REIMBURSED, $userId);
                    Logger::log('标记已报销', "报销记录 #{$id} 已标记为已报销", $userId, $_SESSION['user_nickname'] ?? null);
                }
                header('Location: /public/index.php?route=reimbursement');
                exit;
            }

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    Reimbursement::delete($id, $ledgerId);
                    Logger::log('删除报销记录', "删除报销记录 #{$id}", $userId, $_SESSION['user_nickname'] ?? null);
                }
                header('Location: /public/index.php?route=reimbursement');
                exit;
            }
        }

        $this->render('reimbursement', [
            'items'         => $items,
            'overview'      => $overview,
            'months'        => $months,
            'currentMonth'  => $currentMonth,
            'currentStatus' => $currentStatus,
            'search'        => $search,
            'totalAmount'   => $totalAmount,
        ]);
    }

    /**
     * 报销配置
     */
    public function config(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ReimbursementConfig::update($ledgerId, [
                'enabled' => (int)($_POST['enabled'] ?? 1),
            ]);
            Logger::log('更新报销配置', "更新报销配置 enabled=".(int)($_POST['enabled'] ?? 1), $userId, $_SESSION['user_nickname'] ?? null);
            header('Location: /public/index.php?route=reimbursement-config');
            exit;
        }

        $config = ReimbursementConfig::getOrCreate($ledgerId);

        $this->render('config', [
            'config' => $config,
        ]);
    }
}
