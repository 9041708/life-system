<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\LedgerContext;
use App\Service\Database;
use App\Service\Logger;
use App\Model\DebtConfig;
use App\Model\DebtPayment;

/**
 * 负债管理控制器
 * 处理负债相关的所有请求
 */
class DebtController
{
    /**
     * 检查用户是否登录
     * @return int 用户ID
     */
    private function requireLogin(): int
    {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: /public/index.php?route=login');
            exit;
        }
        return $uid;
    }

    /**
     * 渲染视图
     * @param string $view 视图名称
     * @param array $params 传递给视图的参数
     */
    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        $view = 'debt/' . $view;
        include __DIR__ . '/../../templates/layout_main.php';
    }

    /**
     * 每月应还页面
     */
    public function currentMonth(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        // 月份筛选：默认当前月
        $month = trim((string)($_GET['month'] ?? ''));
        if ($month === '' || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        // 设置页面标题
        $_SESSION['current_page_title'] = '每月应还';

        // 获取应还列表
        $payments = DebtPayment::getMonthPayments($userId, $ledgerId, $month);
        
        // 计算统计信息
        $totalAmount = 0.0;
        $totalCount = count($payments);
        
        foreach ($payments as &$payment) {
            $totalAmount += (float)$payment['total_amount'];
            
            // 计算剩余期数
            $stmt = Database::getConnection()->prepare('
                SELECT COUNT(*) FROM debt_payment 
                WHERE debt_config_id = :debt_id AND status != "paid"
            ');
            $stmt->execute([':debt_id' => $payment['debt_config_id']]);
            $payment['remaining_periods'] = (int)$stmt->fetchColumn();
            
            // 计算剩余金额
            $stmt = Database::getConnection()->prepare('
                SELECT SUM(total_amount) FROM debt_payment 
                WHERE debt_config_id = :debt_id AND status != "paid"
            ');
            $stmt->execute([':debt_id' => $payment['debt_config_id']]);
            $payment['remaining_amount'] = (float)$stmt->fetchColumn();
        }

        // 查询当月已还款金额（按应还日期统计，与列表一致）
        $paidAmount = 0.0;
        $paidCount = 0;
        try {
            $stmt = Database::getConnection()->prepare('
                SELECT COALESCE(SUM(paid_amount), 0) AS total, COUNT(*) AS cnt
                FROM debt_payment
                WHERE ' . ($ledgerId > 0 ? 'ledger_id = :lid' : 'user_id = :uid') . '
                  AND status = "paid"
                  AND DATE_FORMAT(due_date, "%Y-%m") = :month
            ');
            $params = $ledgerId > 0 ? [':lid' => $ledgerId, ':month' => $month] : [':uid' => $userId, ':month' => $month];
            $stmt->execute($params);
            $paidRow = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($paidRow) {
                $paidAmount = (float)$paidRow['total'];
                $paidCount = (int)$paidRow['cnt'];
            }
        } catch (\Throwable $e) {
            $paidAmount = 0.0;
            $paidCount = 0;
        }

        $this->render('current_month', [
            'payments' => $payments,
            'totalAmount' => $totalAmount,
            'totalCount' => $totalCount,
            'paidAmount' => $paidAmount,
            'paidCount' => $paidCount,
            'month' => $month,
        ]);
    }

    /**
     * 汇总统计页面
     */
    public function summary(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        // 获取汇总统计
        $summary = DebtPayment::getSummary($userId, $ledgerId);
        
        // 计算总计
        $grandTotalPrincipal = 0.0;
        $grandTotalInterest = 0.0;
        $grandTotalPaid = 0.0;
        $grandTotalRemaining = 0.0;
        $grandTotalPeriods = 0;
        $grandPaidPeriods = 0;
        
        foreach ($summary as $item) {
            $grandTotalPrincipal += (float)$item['total_principal'];
            $grandTotalInterest += (float)$item['total_interest'];
            $grandTotalPaid += (float)$item['total_paid'];
            $grandTotalRemaining += (float)$item['remaining_amount'];
            $grandTotalPeriods += (int)$item['installment_count'];
            $grandPaidPeriods += (int)$item['paid_periods'];
        }

        $this->render('summary', [
            'summary' => $summary,
            'grandTotalPrincipal' => $grandTotalPrincipal,
            'grandTotalInterest' => $grandTotalInterest,
            'grandTotalPaid' => $grandTotalPaid,
            'grandTotalRemaining' => $grandTotalRemaining,
            'grandTotalPeriods' => $grandTotalPeriods,
            'grandPaidPeriods' => $grandPaidPeriods,
            'grandProgressPercent' => $grandTotalPeriods > 0 ? round(($grandPaidPeriods / $grandTotalPeriods) * 100) : 0,
        ]);
    }

    /**
     * 负债配置列表页面
     */
    public function configIndex(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        // 获取所有负债配置
        if ($ledgerId > 0) {
            $configs = DebtConfig::allByLedger($ledgerId);
        } else {
            $configs = DebtConfig::allByUser($userId);
        }
        
        // 为每个配置获取进度信息
        foreach ($configs as &$config) {
            $payments = DebtPayment::getByDebtConfigId($config['id']);
            $paidCount = 0;
            $remainingAmount = 0.0;
            
            foreach ($payments as $p) {
                if ($p['status'] === 'paid') {
                    $paidCount++;
                } else {
                    $remainingAmount += (float)$p['total_amount'];
                }
            }
            
            $config['paid_periods'] = $paidCount;
            $config['remaining_periods'] = (int)$config['installment_count'] - $paidCount;
            $config['remaining_amount'] = $remainingAmount;
            $config['progress_percent'] = ((int)$config['installment_count'] > 0) 
                ? round(($paidCount / (int)$config['installment_count']) * 100) 
                : 0;
        }

        $this->render('config_index', [
            'configs' => $configs,
        ]);
    }

    /**
     * 创建/编辑负债配置表单页面
     */
    public function configCreate(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $config = null;
        
            if ($id > 0) {
                // 按用户ID查找，不限制ledger_id，避免账本切换后找不到
                $config = DebtConfig::findByUser($userId, $id);
            }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => trim((string)($_POST['name'] ?? '')),
                'total_principal' => isset($_POST['total_principal']) ? (float)$_POST['total_principal'] : 0.0,
                'total_interest' => isset($_POST['total_interest']) ? (float)$_POST['total_interest'] : 0.0,
                'installment_count' => isset($_POST['installment_count']) ? (int)$_POST['installment_count'] : 0,
                'per_period_principal' => isset($_POST['per_period_principal']) ? (float)$_POST['per_period_principal'] : 0.0,
                'per_period_interest' => isset($_POST['per_period_interest']) ? (float)$_POST['per_period_interest'] : 0.0,
                'per_period_total' => isset($_POST['per_period_total']) ? (float)$_POST['per_period_total'] : 0.0,
                'first_payment_date' => trim((string)($_POST['first_payment_date'] ?? '')),
                'repayment_method' => trim((string)($_POST['repayment_method'] ?? 'equal')),
                'note' => trim((string)($_POST['note'] ?? '')),
            ];

            if ($id > 0 && $config) {
                // 更新前删除旧的还款计划，然后重新生成
                DebtPayment::deleteByConfigId($id);
                DebtConfig::update($userId, $id, $data);
                DebtConfig::generateRepaymentPlan($id, $userId, $ledgerId);
                Logger::log('更新负债配置', "更新负债配置：{$data['name']}，本金：{$data['total_principal']}，利息：{$data['total_interest']}，期数：{$data['installment_count']}", $userId, $_SESSION['user_nickname'] ?? null);
            } else {
                // 创建
                $newId = DebtConfig::create($userId, $ledgerId, $data);
                Logger::log('创建负债配置', "创建负债配置：{$data['name']}，本金：{$data['total_principal']}，利息：{$data['total_interest']}，期数：{$data['installment_count']}，首次还款：{$data['first_payment_date']}", $userId, $_SESSION['user_nickname'] ?? null);

                // 自动生成还款计划
                DebtConfig::generateRepaymentPlan($newId, $userId, $ledgerId);
            }

            header('Location: /public/index.php?route=debt-config');
            exit;
        }

        $this->render('config_form', [
            'config' => $config,
            'isEdit' => $id > 0 && $config !== null,
        ]);
    }

    /**
     * 标记还款
     */
    public function markPaid(): void
    {
        $userId = $this->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paymentId = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
            $paidAmount = isset($_POST['paid_amount']) ? (float)$_POST['paid_amount'] : 0.0;
            
            if ($paymentId > 0 && $paidAmount > 0) {
                // 先查一下负债名称，用于日志
                $paymentInfo = DebtPayment::findById($paymentId, $userId);
                $debtName = $paymentInfo['debt_name'] ?? '';
                $periodNumber = $paymentInfo['period_number'] ?? 0;

                list($success, $isLastPeriod) = DebtPayment::markAsPaid($paymentId, $userId, $paidAmount);
                
                if ($success) {
                    Logger::log('标记还款', "负债【{$debtName}】第{$periodNumber}期，实际还款金额：{$paidAmount}", $userId, $_SESSION['user_nickname'] ?? null);
                    if ($isLastPeriod) {
                        // 最后一期还款完成，设置session标志，用于显示恭喜弹窗
                        $_SESSION['debt_congratulations'] = true;
                    }
                }
            }
        }

        header('Location: /public/index.php?route=debt-current');
        exit;
    }

    /**
     * 回退还款
     */
    public function undoPaid(): void
    {
        $userId = $this->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paymentId = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
            
            if ($paymentId > 0) {
                // 先查一下负债名称，用于日志
                $paymentInfo = DebtPayment::findById($paymentId, $userId);
                $debtName = $paymentInfo['debt_name'] ?? '';
                $periodNumber = $paymentInfo['period_number'] ?? 0;

                $success = DebtPayment::undoPaid($paymentId, $userId);
                if ($success) {
                    Logger::log('回退还款', "回退负债【{$debtName}】第{$periodNumber}期的还款记录", $userId, $_SESSION['user_nickname'] ?? null);
                }
            }
        }

        header('Location: /public/index.php?route=debt-current');
        exit;
    }

    /**
     * 取消负债配置（软删除）
     */
    public function configCancel(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = 0;
        try {
            $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        } catch (\Throwable $e) {
            $ledgerId = 0;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id > 0) {
                // 先查一下负债名称，用于日志
                $config = DebtConfig::findByLedger($ledgerId, $id);
                $configName = $config['name'] ?? '';

                DebtConfig::cancel($userId, $id);
                Logger::log('取消负债配置', "取消负债配置：{$configName}", $userId, $_SESSION['user_nickname'] ?? null);
            }
        }

        header('Location: /public/index.php?route=debt-config');
        exit;
    }
}
