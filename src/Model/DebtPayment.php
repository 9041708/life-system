<?php
namespace App\Model;

use App\Service\Database;
use PDO;

/**
 * 还款记录 Model
 * 负责管理每期还款记录
 */
class DebtPayment
{
    /**
     * 获取当月应还列表
     * @param int $userId 用户ID
     * @param int $ledgerId 账本ID
     * @return array 当月应还的还款记录
     */
    public static function getCurrentMonthPayments(int $userId, int $ledgerId): array
    {
        $pdo = Database::getConnection();
        
        // 查询当月所有还款记录（包含已还款，方便回退操作）
        $stmt = $pdo->prepare('
            SELECT 
                dp.*,
                dc.name AS debt_name,
                dc.installment_count,
                dc.total_principal,
                dc.total_interest
            FROM debt_payment dp
            INNER JOIN debt_config dc ON dp.debt_config_id = dc.id
            WHERE dp.user_id = :uid 
              AND dp.ledger_id = :lid
              AND DATE_FORMAT(dp.due_date, "%Y-%m") = DATE_FORMAT(NOW(), "%Y-%m")
            ORDER BY 
                CASE dp.status WHEN "paid" THEN 1 ELSE 0 END,
                dp.due_date ASC
        ');
        
        $stmt->execute([':uid' => $userId, ':lid' => $ledgerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 获取指定月份的还款记录
     */
    public static function getMonthPayments(int $userId, int $ledgerId, string $month): array
    {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare('
            SELECT 
                dp.*,
                dc.name AS debt_name,
                dc.installment_count,
                dc.total_principal,
                dc.total_interest
            FROM debt_payment dp
            INNER JOIN debt_config dc ON dp.debt_config_id = dc.id
            WHERE dp.user_id = :uid 
              AND dp.ledger_id = :lid
              AND DATE_FORMAT(dp.due_date, "%Y-%m") = :month
            ORDER BY 
                CASE dp.status WHEN "paid" THEN 1 ELSE 0 END,
                dp.due_date ASC
        ');
        
        $stmt->execute([':uid' => $userId, ':lid' => $ledgerId, ':month' => $month]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 获取某负债配置的还款记录
     */
    public static function getByDebtConfigId(int $debtConfigId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT * FROM debt_payment 
            WHERE debt_config_id = :debt_id 
            ORDER BY period_number ASC
        ');
        $stmt->execute([':debt_id' => $debtConfigId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 标记还款
     * @param int $paymentId 还款记录ID
     * @param int $userId 用户ID
     * @param float $paidAmount 实际还款金额
     * @return bool 是否成功，以及是否是所有期中最后一期
     */
    public static function markAsPaid(int $paymentId, int $userId, float $paidAmount): array
    {
        $pdo = Database::getConnection();
        
        // 获取还款记录信息
        $stmt = $pdo->prepare('
            SELECT dp.*, dc.installment_count 
            FROM debt_payment dp
            INNER JOIN debt_config dc ON dp.debt_config_id = dc.id
            WHERE dp.id = :id AND dp.user_id = :uid
            LIMIT 1
        ');
        $stmt->execute([':id' => $paymentId, ':uid' => $userId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            return [false, false];
        }
        
        // 标记还款：全额→paid，部分→保留pending但记录已还金额
        $today = date('Y-m-d');
        $totalAmount = (float)$payment['total_amount'];
        $newStatus = ($paidAmount >= $totalAmount) ? 'paid' : 'pending';
        $stmt = $pdo->prepare('
            UPDATE debt_payment 
            SET paid_amount = :paid_amount,
                paid_date = :paid_date,
                status = :status
            WHERE id = :id AND user_id = :uid
        ');
        $stmt->execute([
            ':paid_amount' => $paidAmount,
            ':paid_date' => $today,
            ':status' => $newStatus,
            ':id' => $paymentId,
            ':uid' => $userId,
        ]);
        
        $success = $stmt->rowCount() > 0;
        
        // 检查是否是最后一期
        $isLastPeriod = ((int)$payment['period_number'] === (int)$payment['installment_count']);
        
        // 如果最后一期已还，更新负债配置状态为completed
        if ($success && $isLastPeriod) {
            $stmt = $pdo->prepare('
                UPDATE debt_config 
                SET status = "completed" 
                WHERE id = :debt_id AND user_id = :uid
            ');
            $stmt->execute([
                ':debt_id' => $payment['debt_config_id'],
                ':uid' => $userId,
            ]);
        }
        
        return [$success, $isLastPeriod];
    }

    /**
     * 获取汇总统计
     * @return array 包含各负债的汇总信息
     */
    public static function getSummary(int $userId, int $ledgerId): array
    {
        $pdo = Database::getConnection();
        
        // 按负债配置分组统计
        $stmt = $pdo->prepare('
            SELECT 
                dc.id AS debt_id,
                dc.name AS debt_name,
                dc.total_principal,
                dc.total_interest,
                dc.installment_count,
                dc.per_period_total,
                COUNT(dp.id) AS total_periods,
                SUM(CASE WHEN dp.status = "paid" THEN 1 ELSE 0 END) AS paid_periods,
                SUM(CASE WHEN dp.status = "pending" THEN 1 ELSE 0 END) AS pending_periods,
                SUM(CASE WHEN dp.status = "overdue" THEN 1 ELSE 0 END) AS overdue_periods,
                SUM(CASE WHEN dp.status = "paid" THEN dp.paid_amount ELSE 0 END) AS total_paid,
                SUM(CASE WHEN dp.status != "paid" THEN dp.total_amount ELSE 0 END) AS total_remaining
            FROM debt_config dc
            LEFT JOIN debt_payment dp ON dc.id = dp.debt_config_id
            WHERE dc.user_id = :uid AND dc.ledger_id = :lid AND dc.status != "cancelled"
            GROUP BY dc.id
            ORDER BY dc.created_at DESC
        ');
        
        $stmt->execute([':uid' => $userId, ':lid' => $ledgerId]);
        $summary = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // 计算每笔负债的剩余期数和金额
        foreach ($summary as &$item) {
            $item['remaining_periods'] = (int)$item['pending_periods'] + (int)$item['overdue_periods'];
            $item['remaining_amount'] = (float)$item['total_remaining'];
            $item['progress_percent'] = ((int)$item['installment_count'] > 0) 
                ? round(((int)$item['paid_periods'] / (int)$item['installment_count']) * 100) 
                : 0;
        }
        
        return $summary;
    }

    /**
     * 回退还款（将已还款记录恢复为待还状态）
     * @param int $paymentId 还款记录ID
     * @param int $userId 用户ID
     * @return bool 是否成功
     */
    public static function undoPaid(int $paymentId, int $userId): bool
    {
        $pdo = Database::getConnection();
        
        // 获取还款记录信息
        $stmt = $pdo->prepare('
            SELECT dp.*, dc.installment_count 
            FROM debt_payment dp
            INNER JOIN debt_config dc ON dp.debt_config_id = dc.id
            WHERE dp.id = :id AND dp.user_id = :uid AND dp.status = "paid"
            LIMIT 1
        ');
        $stmt->execute([':id' => $paymentId, ':uid' => $userId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            return false;
        }
        
        // 恢复为待还状态
        $stmt = $pdo->prepare('
            UPDATE debt_payment 
            SET paid_amount = NULL,
                paid_date = NULL,
                status = "pending"
            WHERE id = :id AND user_id = :uid
        ');
        $stmt->execute([':id' => $paymentId, ':uid' => $userId]);
        
        $success = $stmt->rowCount() > 0;
        
        // 如果负债配置已被标记为completed，恢复为active
        if ($success) {
            $stmt = $pdo->prepare('
                UPDATE debt_config 
                SET status = "active" 
                WHERE id = :debt_id AND user_id = :uid AND status = "completed"
            ');
            $stmt->execute([
                ':debt_id' => $payment['debt_config_id'],
                ':uid' => $userId,
            ]);
        }
        
        return $success;
    }

    /**
     * 获取某条还款记录
     */
    public static function findById(int $paymentId, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT * FROM debt_payment 
            WHERE id = :id AND user_id = :uid 
            LIMIT 1
        ');
        $stmt->execute([':id' => $paymentId, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function deleteByConfigId(int $configId): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('DELETE FROM debt_payment WHERE debt_config_id = :id')->execute([':id' => $configId]);
    }
}
