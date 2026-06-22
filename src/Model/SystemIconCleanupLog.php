<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class SystemIconCleanupLog
{
    private static function ensureTable(): void
    {
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'system_icon_cleanup_logs'");
            $exists = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $exists = true;
        }

        if ($exists) {
            return;
        }

        try {
            $pdo->exec(
                "CREATE TABLE system_icon_cleanup_logs (\n" .
                "  id INT AUTO_INCREMENT PRIMARY KEY,\n" .
                "  user_id INT NOT NULL,\n" .
                "  name VARCHAR(255) NULL,\n" .
                "  file_path VARCHAR(255) NULL,\n" .
                "  created_at DATETIME NOT NULL,\n" .
                "  read_at DATETIME NULL,\n" .
                "  KEY idx_user_read (user_id, read_at)\n" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function add(int $userId, ?string $name, ?string $filePath): void
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $now = date('Y-m-d H:i:s');
        try {
            $stmt = $pdo->prepare('INSERT INTO system_icon_cleanup_logs (user_id, name, file_path, created_at) VALUES (:uid,:n,:p,:ca)');
            $stmt->execute([
                ':uid' => $userId,
                ':n' => $name,
                ':p' => $filePath,
                ':ca' => $now,
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function listUnread(int $userId, int $limit = 50): array
    {
        self::ensureTable();
        $limit = max(1, min(200, (int)$limit));
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare('SELECT * FROM system_icon_cleanup_logs WHERE user_id = :uid AND read_at IS NULL ORDER BY id DESC LIMIT ' . $limit);
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function markAllRead(int $userId): void
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $now = date('Y-m-d H:i:s');
        try {
            $stmt = $pdo->prepare('UPDATE system_icon_cleanup_logs SET read_at = :ra WHERE user_id = :uid AND read_at IS NULL');
            $stmt->execute([':ra' => $now, ':uid' => $userId]);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
