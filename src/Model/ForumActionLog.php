<?php
namespace App\Model;

use App\Service\Database;

class ForumActionLog
{
    public static function create(int $userId, int $accountId, string $actionType, string $result, ?string $targetInfo = null): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO forum_action_logs (user_id, account_id, action_type, target_info, result)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId, $accountId, $actionType,
            $targetInfo !== null ? mb_substr($targetInfo, 0, 490) : null,
            mb_substr($result, 0, 190),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByUser(int $userId, int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $limit = max(1, min(500, $limit));
        $stmt = $pdo->prepare(
            "SELECT l.*, a.forum_name, a.forum_url 
             FROM forum_action_logs l
             LEFT JOIN forum_accounts a ON l.account_id = a.id
             WHERE l.user_id = ? 
             ORDER BY l.created_at DESC 
             LIMIT $limit"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function listByAccount(int $accountId, int $limit = 20): array
    {
        $pdo = Database::getConnection();
        $limit = max(1, min(500, $limit));
        $stmt = $pdo->prepare(
            "SELECT * FROM forum_action_logs 
             WHERE account_id = ? 
             ORDER BY created_at DESC 
             LIMIT $limit"
        );
        $stmt->execute([$accountId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function cleanOldLogs(int $keepDays = 3): int
    {
        $pdo = Database::getConnection();
        $keepDays = max(1, min(365, $keepDays));
        $stmt = $pdo->prepare(
            "DELETE FROM forum_action_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL $keepDays DAY)"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    public static function cleanByUser(int $userId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM forum_action_logs WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->rowCount();
    }

    public static function countByUser(int $userId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM forum_action_logs WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
}
