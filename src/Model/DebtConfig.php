<?php
namespace App\Model;

use App\Service\Database;
use PDO;

/**
 * 负债配置 Model
 * 负责管理用户的负债项目配置（如信用卡、车贷等）
 */
class DebtConfig
{
    /**
     * 根据ID查找负债配置（按账本）
     */
    public static function findByLedger(int $ledgerId, int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM debt_config WHERE id = :id AND ledger_id = :lid LIMIT 1');
        $stmt->execute([':id' => $id, ':lid' => $ledgerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * 根据ID查找负债配置（按用户）
     */
    public static function findByUser(int $userId, int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM debt_config WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * 获取用户所有负债配置（按账本）
     */
    public static function allByLedger(int $ledgerId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM debt_config WHERE ledger_id = :lid AND status != "cancelled" ORDER BY created_at DESC');
        $stmt->execute([':lid' => $ledgerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 获取用户所有负债配置（按用户）
     */
    public static function allByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM debt_config WHERE user_id = :uid AND status != "cancelled" ORDER BY created_at DESC');
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 创建负债配置
     * @return int 新创建的负债配置ID
     */
    public static function create(int $userId, int $ledgerId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            INSERT INTO debt_config 
            (user_id, ledger_id, name, total_principal, total_interest, installment_count, 
             per_period_principal, per_period_interest, per_period_total, 
             first_payment_date, repayment_method, note, status) 
            VALUES 
            (:uid, :lid, :name, :total_principal, :total_interest, :installment_count,
             :per_period_principal, :per_period_interest, :per_period_total,
             :first_payment_date, :repayment_method, :note, :status)
        ');
        
        $stmt->execute([
            ':uid' => $userId,
            ':lid' => $ledgerId,
            ':name' => $data['name'],
            ':total_principal' => $data['total_principal'],
            ':total_interest' => $data['total_interest'],
            ':installment_count' => $data['installment_count'],
            ':per_period_principal' => $data['per_period_principal'],
            ':per_period_interest' => $data['per_period_interest'],
            ':per_period_total' => $data['per_period_total'],
            ':first_payment_date' => $data['first_payment_date'],
            ':repayment_method' => $data['repayment_method'] ?? 'equal',
            ':note' => $data['note'] ?? null,
            ':status' => 'active',
        ]);
        
        return (int)$pdo->lastInsertId();
    }

    /**
     * 更新负债配置
     */
    public static function update(int $userId, int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            UPDATE debt_config SET 
                name = :name,
                total_principal = :total_principal,
                total_interest = :total_interest,
                installment_count = :installment_count,
                per_period_principal = :per_period_principal,
                per_period_interest = :per_period_interest,
                per_period_total = :per_period_total,
                first_payment_date = :first_payment_date,
                repayment_method = :repayment_method,
                note = :note
            WHERE id = :id AND user_id = :uid
        ');
        
        $stmt->execute([
            ':name' => $data['name'],
            ':total_principal' => $data['total_principal'],
            ':total_interest' => $data['total_interest'],
            ':installment_count' => $data['installment_count'],
            ':per_period_principal' => $data['per_period_principal'],
            ':per_period_interest' => $data['per_period_interest'],
            ':per_period_total' => $data['per_period_total'],
            ':first_payment_date' => $data['first_payment_date'],
            ':repayment_method' => $data['repayment_method'] ?? 'equal',
            ':note' => $data['note'] ?? null,
            ':id' => $id,
            ':uid' => $userId,
        ]);
    }

    /**
     * 取消负债配置（软删除，标记为cancelled）
     */
    public static function cancel(int $userId, int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE debt_config SET status = "cancelled" WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * 完成负债配置（所有期数已还完）
     */
    public static function markCompleted(int $userId, int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE debt_config SET status = "completed" WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    /**
     * 生成还款计划
     * 根据负债配置，自动生成所有期的还款记录
     */
    public static function generateRepaymentPlan(int $debtConfigId, int $userId, int $ledgerId): void
    {
        $pdo = Database::getConnection();
        
        // 获取负债配置信息
        $stmt = $pdo->prepare('SELECT * FROM debt_config WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $debtConfigId, ':uid' => $userId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            return;
        }
        
        $firstDate = new \DateTime($config['first_payment_date']);
        $installmentCount = (int)$config['installment_count'];
        $perPeriodPrincipal = (float)$config['per_period_principal'];
        $perPeriodInterest = (float)$config['per_period_interest'];
        $perPeriodTotal = (float)$config['per_period_total'];
        
        // 为每一期生成还款记录
        $firstDay = (int)$firstDate->format('d');
        for ($i = 1; $i <= $installmentCount; $i++) {
            $dueDate = clone $firstDate;
            $dueDate->modify('first day of +' . ($i - 1) . ' months');
            // 取目标月的同一天，如果超出则取最后一天
            $lastDay = (int)$dueDate->format('t');
            $targetDay = min($firstDay, $lastDay);
            $dueDate->setDate((int)$dueDate->format('Y'), (int)$dueDate->format('m'), $targetDay);
            
            $stmt = $pdo->prepare('
                INSERT IGNORE INTO debt_payment 
                (debt_config_id, user_id, ledger_id, period_number, due_date, 
                 principal_amount, interest_amount, total_amount, status) 
                VALUES 
                (:debt_id, :uid, :lid, :period, :due_date, 
                 :principal, :interest, :total, "pending")
            ');
            
            $stmt->execute([
                ':debt_id' => $debtConfigId,
                ':uid' => $userId,
                ':lid' => $ledgerId,
                ':period' => $i,
                ':due_date' => $dueDate->format('Y-m-d'),
                ':principal' => $perPeriodPrincipal,
                ':interest' => $perPeriodInterest,
                ':total' => $perPeriodTotal,
            ]);
        }
    }
}
