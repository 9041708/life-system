<?php
namespace App\Model;

use App\Service\Database;

class ForumRepliedThread
{
    public static function markReplied(int $accountId, int $tid, string $title = ''): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO forum_replied_threads (account_id, tid, title, replied_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE replied_at = NOW(), title = VALUES(title)"
        );
        $stmt->execute([$accountId, $tid, $title]);
        return (int)$pdo->lastInsertId();
    }

    public static function isReplied(int $accountId, int $tid): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT 1 FROM forum_replied_threads WHERE account_id = ? AND tid = ? LIMIT 1"
        );
        $stmt->execute([$accountId, $tid]);
        return $stmt->fetch() !== false;
    }

    public static function hasRepliedSince(int $accountId, int $tid, int $seconds = 3600): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT 1 FROM forum_replied_threads WHERE account_id = ? AND tid = ? AND replied_at > DATE_SUB(NOW(), INTERVAL ? SECOND) LIMIT 1"
        );
        $stmt->execute([$accountId, $tid, $seconds]);
        return $stmt->fetch() !== false;
    }

    public static function getRepliedTids(int $accountId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT tid FROM forum_replied_threads WHERE account_id = ? ORDER BY replied_at DESC"
        );
        $stmt->execute([$accountId]);
        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'tid');
    }

    public static function listByAccount(int $accountId, int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $limit = max(1, min(200, $limit));
        $stmt = $pdo->prepare(
            "SELECT * FROM forum_replied_threads 
             WHERE account_id = ? 
             ORDER BY replied_at DESC 
             LIMIT $limit"
        );
        $stmt->execute([$accountId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function cleanOldRecords(int $keepDays = 30): int
    {
        $pdo = Database::getConnection();
        $keepDays = max(1, min(365, $keepDays));
        $stmt = $pdo->prepare(
            "DELETE FROM forum_replied_threads WHERE replied_at < DATE_SUB(NOW(), INTERVAL $keepDays DAY)"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    public static function deleteByAccount(int $accountId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM forum_replied_threads WHERE account_id = ?");
        $stmt->execute([$accountId]);
        return $stmt->rowCount();
    }
}
