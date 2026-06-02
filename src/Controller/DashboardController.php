<?php
namespace App\Controller;

use App\Service\Database;
use App\Service\Config;
use App\Service\LedgerContext;
use App\Model\Account;
use App\Model\Budget;
use App\Model\User;
use App\Model\Announcement;
use App\Model\Goal;
use App\Model\DebtPayment;
use App\Model\EasyTodoTask;
use PDO;

class DashboardController
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
        $pdo = Database::getConnection();

        $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        $isLedgerMode = $ledgerId > 0;

        $currentUser = User::findById($userId);
        $budgetReminderEnabled = isset($currentUser['budget_reminder_enabled'])
            ? (int)$currentUser['budget_reminder_enabled'] === 1
            : true;

        // 各大类账户余额
        $stmt = $pdo->prepare('SELECT ag.code, ag.name, SUM(a.current_balance) AS total
            FROM accounts a
            JOIN account_groups ag ON a.group_id = ag.id
            WHERE ' . ($isLedgerMode ? 'a.ledger_id = :lid' : 'a.user_id = :uid') . '
            GROUP BY ag.id, ag.code, ag.name');
        $stmt->execute($isLedgerMode ? [':lid' => $ledgerId] : [':uid' => $userId]);
        $balances = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $map = [
            'financial' => 0.0,
            'saving' => 0.0,
            'receivable' => 0.0,
            'debt' => 0.0,
            'other' => 0.0,
        ];
        foreach ($balances as $row) {
            $code = $row['code'] ?? null;
            if ($code !== null && array_key_exists($code, $map)) {
                $map[$code] = (float)$row['total'];
            }
        }

        // 各分类账户明细（用于弹窗）
        $accounts = $isLedgerMode ? Account::allByLedger($ledgerId) : Account::allByUser($userId);
        $accountsByGroup = [];
        foreach ($accounts as $acc) {
            $code = $acc['group_code'] ?? '';
            if ($code === '') {
                continue;
            }
            if (!isset($accountsByGroup[$code])) {
                $accountsByGroup[$code] = [];
            }
            $accountsByGroup[$code][] = $acc;
        }

        // 当月预算总额（支出）及“已用预算”（仅对已设置预算的支出分类/项目统计）
        $year = (int)date('Y');
        $month = (int)date('n');

        $budgets = $isLedgerMode ? Budget::listByLedgerMonth($ledgerId, $year, $month) : Budget::listByUserMonth($userId, $year, $month);
        $totalBudgetExpense = 0.0;
        $totalUsedExpense = 0.0;

        foreach ($budgets as $b) {
            if (($b['type'] ?? '') !== 'expense') {
                continue;
            }

            $totalBudgetExpense += (float)($b['amount'] ?? 0);

            $sql = 'SELECT COALESCE(SUM(amount),0) AS used_amount
                    FROM transactions
                    WHERE ' . ($isLedgerMode ? 'ledger_id = :lid' : 'user_id = :uid') . '
                      AND type = :type
                      AND YEAR(trans_time) = :y
                      AND MONTH(trans_time) = :m';
            $params = [
                ($isLedgerMode ? ':lid' : ':uid') => ($isLedgerMode ? $ledgerId : $userId),
                ':type' => $b['type'],
                ':y' => $year,
                ':m' => $month,
            ];
            if (!empty($b['category_id'])) {
                $sql .= ' AND category_id = :cid';
                $params[':cid'] = $b['category_id'];
            }
            if (!empty($b['item_id'])) {
                $sql .= ' AND item_id = :iid';
                $params[':iid'] = $b['item_id'];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['used_amount' => 0];
            $totalUsedExpense += (float)$row['used_amount'];
        }

        $monthBudget = $totalBudgetExpense;
        $monthBudgetUsed = $totalUsedExpense;

        // 当月实际收入 / 支出（全部支出，不仅限于预算）
                $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS total
                        FROM transactions
                        WHERE ' . ($isLedgerMode ? 'ledger_id = :lid' : 'user_id = :uid') . '
                            AND type = "expense"
                            AND YEAR(trans_time) = :y
                            AND MONTH(trans_time) = :m');
                $stmt->execute($isLedgerMode ? [':lid' => $ledgerId, ':y' => $year, ':m' => $month] : [':uid' => $userId, ':y' => $year, ':m' => $month]);
        $expenseRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0];
        $monthExpense = (float)$expenseRow['total'];

                $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS total
                        FROM transactions
                        WHERE ' . ($isLedgerMode ? 'ledger_id = :lid' : 'user_id = :uid') . '
                            AND type = "income"
                            AND YEAR(trans_time) = :y
                            AND MONTH(trans_time) = :m');
                $stmt->execute($isLedgerMode ? [':lid' => $ledgerId, ':y' => $year, ':m' => $month] : [':uid' => $userId, ':y' => $year, ':m' => $month]);
        $incomeRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0];
        $monthIncome = (float)$incomeRow['total'];
        $monthNet = $monthIncome - $monthExpense;

        // 预算使用情况（支出）
        $monthBudgetRemain = $monthBudget - $monthBudgetUsed;
        if ($monthBudgetRemain < 0) {
            $monthBudgetRemain = 0.0;
        }

        $monthBudgetRate = $monthBudget > 0 ? ($monthBudgetUsed / $monthBudget) : 0.0;
        $monthBudgetRatePercent = (int)round(min(999, $monthBudgetRate * 100));
        $monthBudgetOver = $monthBudget > 0 && $monthBudgetUsed > $monthBudget;
        $monthBudgetWarn = !$monthBudgetOver && $monthBudgetRate >= 0.8;

        // 目标进度汇总（当前用户 + 当前账本）
        $goalTotalTarget = 0.0;
        $goalTotalSaved = 0.0;
        $goalOverallPercent = 0;
        $goalActiveCount = 0;
        try {
            $goalRows = Goal::listByUserAndLedger($userId, $isLedgerMode ? $ledgerId : 0);
            foreach ($goalRows as $g) {
                $target = (float)($g['target_amount'] ?? 0);
                $saved = (float)($g['saved_amount'] ?? 0);
                if ($target <= 0) {
                    continue;
                }
                if (($g['status'] ?? 'active') === 'archived') {
                    continue;
                }
                $goalTotalTarget += $target;
                $goalTotalSaved += min($saved, $target);
                $goalActiveCount++;
            }
            if ($goalTotalTarget > 0) {
                $goalOverallPercent = (int)round(min(100, ($goalTotalSaved / $goalTotalTarget) * 100));
            }
        } catch (\Throwable $e) {
            $goalTotalTarget = 0.0;
            $goalTotalSaved = 0.0;
            $goalOverallPercent = 0;
            $goalActiveCount = 0;
        }

        // 最近 7 天收入 / 支出趋势（含今天，共 7 天）
        $labels7 = [];
        $income7 = [];
        $expense7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} day"));
            $labels7[] = date('m-d', strtotime($date));

            $stmt = $pdo->prepare('SELECT type, COALESCE(SUM(amount),0) AS total
                FROM transactions
                                WHERE ' . ($isLedgerMode ? 'ledger_id = :lid' : 'user_id = :uid') . '
                  AND DATE(trans_time) = :d
                  AND type IN ("income","expense")
                GROUP BY type');
                        $stmt->execute($isLedgerMode ? [':lid' => $ledgerId, ':d' => $date] : [':uid' => $userId, ':d' => $date]);

            $income = 0.0;
            $expense = 0.0;
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($row['type'] === 'income') {
                    $income = (float)$row['total'];
                } elseif ($row['type'] === 'expense') {
                    $expense = (float)$row['total'];
                }
            }

            $income7[] = $income;
            $expense7[] = $expense;
        }

        // PC 端首页不再展示公告，统一由小程序端处理公告弹窗
        $latestAnnouncement = null;

        // 当月应还负债（从 debt_payment 表获取）
        $debtCurrentMonthTotal = 0.0;
        $debtCurrentMonthCount = 0;
        $debtCurrentMonthList = [];
        try {
            $stmt = $pdo->prepare('
                SELECT dp.id, dp.period_number, dp.due_date,
                       dp.principal_amount, dp.interest_amount, dp.total_amount, dp.status,
                       dc.name AS debt_name, dc.installment_count
                FROM debt_payment dp
                INNER JOIN debt_config dc ON dp.debt_config_id = dc.id
                WHERE ' . ($isLedgerMode ? 'dp.ledger_id = :lid' : 'dp.user_id = :uid') . '
                  AND dp.status IN ("pending", "overdue")
                  AND DATE_FORMAT(dp.due_date, "%Y-%m") = DATE_FORMAT(NOW(), "%Y-%m")
                ORDER BY dp.due_date ASC
            ');
            $stmt->execute($isLedgerMode ? [':lid' => $ledgerId] : [':uid' => $userId]);
            $debtCurrentMonthList = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $debtCurrentMonthCount = count($debtCurrentMonthList);
            foreach ($debtCurrentMonthList as $item) {
                $debtCurrentMonthTotal += (float)$item['total_amount'];
            }
        } catch (\Throwable $e) {
            $debtCurrentMonthTotal = 0.0;
            $debtCurrentMonthCount = 0;
            $debtCurrentMonthList = [];
        }

        // 待办任务月度统计（用于首页日历标记）
        $taskMonthStats = [];
        $tasksByDate = [];
        $pendingReminders = [];
        try {
            EasyTodoTask::ensureAdvancedColumns();
            $taskMonthStats = EasyTodoTask::listByMonth($userId, $year, $month);
            $allTasks = EasyTodoTask::listByUser($userId, $ledgerId, null, 500);
            $monthPrefix = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
            foreach ($allTasks as $t) {
                $d = $t['task_date'] ?? '';
                if ($d && strpos($d, $monthPrefix) === 0) {
                    $tasksByDate[$d][] = $t;
                }
            }
            $pendingReminders = EasyTodoTask::getPendingReminders($userId);
        } catch (\Throwable $e) {
            $taskMonthStats = [];
        }

        // 节假日数据（基础固定节假日，农历节日在模板中计算）
        $holidays = [
            $year.'-01-01'=>'元旦',$year.'-03-08'=>'妇女节',$year.'-05-01'=>'劳动节',
            $year.'-06-01'=>'儿童节',$year.'-07-01'=>'建党节',$year.'-08-01'=>'建军节',
            $year.'-10-01'=>'国庆节',$year.'-10-02'=>'国庆节',$year.'-10-03'=>'国庆节',
            $year.'-12-25'=>'圣诞节',
        ];

        $this->render('dashboard/index', [
            'balances' => $map,
            'accountsByGroup' => $accountsByGroup,
            'monthBudget' => $monthBudget,
            'monthBudgetUsed' => $monthBudgetUsed,
            'monthExpense' => $monthExpense,
            'monthIncome' => $monthIncome,
            'monthNet' => $monthNet,
            'monthBudgetRemain' => $monthBudgetRemain,
            'monthBudgetRatePercent' => $monthBudgetRatePercent,
            'monthBudgetOver' => $monthBudgetOver,
            'monthBudgetWarn' => $monthBudgetWarn,
            'goalTotalTarget' => $goalTotalTarget,
            'goalTotalSaved' => $goalTotalSaved,
            'goalOverallPercent' => $goalOverallPercent,
            'goalActiveCount' => $goalActiveCount,
            'budgetReminderEnabled' => $budgetReminderEnabled,
            'trendLabels7' => $labels7,
            'trendIncome7' => $income7,
            'trendExpense7' => $expense7,
            'latestAnnouncement' => $latestAnnouncement,
            'debtCurrentMonthTotal' => $debtCurrentMonthTotal,
            'debtCurrentMonthCount' => $debtCurrentMonthCount,
            'debtCurrentMonthList' => $debtCurrentMonthList,
            'reimbPendingCount' => $isLedgerMode ? \App\Model\Reimbursement::getPendingCount($ledgerId) : 0,
            'reimbPendingTotal' => $isLedgerMode ? \App\Model\Reimbursement::getPendingTotal($ledgerId) : 0.0,
            'reimbPendingList'  => $isLedgerMode ? \App\Model\Reimbursement::getPending($ledgerId) : [],
            'reimbEnabled'      => $isLedgerMode ? \App\Model\ReimbursementConfig::isEnabled($ledgerId) : false,
            'taskMonthStats'    => $taskMonthStats,
            'tasksByDate'       => $tasksByDate,
            'holidays'          => $holidays,
            'pendingReminders'  => $pendingReminders,
            'homeBookmarks'    => \App\Model\NavBookmark::listHomeBookmarks($userId),
        ]);
    }
}

