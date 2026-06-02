<?php
namespace App\Model;

use App\Service\Database;
use PDO;

/**
 * 报销配置 Model - 简化版（只有启用开关）
 */
class ReimbursementConfig
{
    /**
     * 获取或创建配置
     */
    public static function getOrCreate(int $ledgerId): array
    {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare('SELECT * FROM reimbursement_config WHERE ledger_id = :lid LIMIT 1');
        $stmt->execute([':lid' => $ledgerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            // 创建默认配置（默认启用）
            $stmt = $pdo->prepare('
                INSERT INTO reimbursement_config (ledger_id, enabled, created_at, updated_at)
                VALUES (:lid, 1, NOW(), NOW())
            ');
            $stmt->execute([':lid' => $ledgerId]);
            
            return [
                'ledger_id' => $ledgerId,
                'enabled' => 1,
            ];
        }
        
        return $row;
    }
    
    /**
     * 更新配置（只更新 enabled 字段）
     */
    public static function update(int $ledgerId, array $data): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            UPDATE reimbursement_config 
            SET enabled = :enabled, updated_at = NOW()
            WHERE ledger_id = :lid
        ');
        $stmt->execute([
            ':lid' => $ledgerId,
            ':enabled' => (int)($data['enabled'] ?? 1),
        ]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * 检查是否启用报销功能
     */
    public static function isEnabled(int $ledgerId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT enabled FROM reimbursement_config WHERE ledger_id = :lid LIMIT 1');
        $stmt->execute([':lid' => $ledgerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (bool)$row['enabled'] : true; // 默认启用
    }
}
