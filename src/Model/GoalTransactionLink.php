<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class GoalTransactionLink
{
    public static function ensureTable(): void
    {
        $pdo = Database::getConnection();
        $sql = "CREATE TABLE IF NOT EXISTS `goal_transaction_links` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `goal_id` INT UNSIGNED NOT NULL,
            `transaction_id` INT UNSIGNED NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_goal_tx` (`goal_id`, `transaction_id`),
            KEY `idx_goal` (`goal_id`),
            KEY `idx_tx` (`transaction_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        try {
            $pdo->exec($sql);
        } catch (\Throwable $e) {
            // 若无建表权限，忽略；相关功能会自然退化为不统计关联进度。
        }
    }

    public static function listGoalIdsByTransactionId(int $transactionId): array
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare('SELECT goal_id FROM goal_transaction_links WHERE transaction_id = :tid');
            $stmt->execute([':tid' => $transactionId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $ids = [];
            foreach ($rows as $r) {
                $ids[] = (int)($r['goal_id'] ?? 0);
            }
            return array_values(array_filter($ids));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function replaceLinksForTransaction(int $transactionId, array $goalIds): void
    {
        self::ensureTable();
        $pdo = Database::getConnection();

        $goalIds = array_values(array_unique(array_map('intval', $goalIds)));
        $goalIds = array_values(array_filter($goalIds, static fn($v) => $v > 0));

        try {
            $pdo->beginTransaction();
            $stmtDel = $pdo->prepare('DELETE FROM goal_transaction_links WHERE transaction_id = :tid');
            $stmtDel->execute([':tid' => $transactionId]);

            if ($goalIds) {
                $stmtIns = $pdo->prepare('INSERT IGNORE INTO goal_transaction_links (goal_id, transaction_id) VALUES (:gid, :tid)');
                foreach ($goalIds as $gid) {
                    $stmtIns->execute([':gid' => $gid, ':tid' => $transactionId]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            try {
                $pdo->rollBack();
            } catch (\Throwable $ignored) {
            }
        }
    }

    public static function deleteByTransactionIds(array $transactionIds): void
    {
        self::ensureTable();
        $ids = array_values(array_unique(array_map('intval', $transactionIds)));
        $ids = array_values(array_filter($ids, static fn($v) => $v > 0));
        if (!$ids) {
            return;
        }

        $pdo = Database::getConnection();
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM goal_transaction_links WHERE transaction_id IN ($placeholders)");
            $stmt->execute($ids);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
