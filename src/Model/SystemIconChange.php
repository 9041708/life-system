<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class SystemIconChange
{
    private static function ensureTable(): void
    {
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'system_icon_changes'");
            $exists = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $exists = true;
        }

        if ($exists) {
            return;
        }

        try {
            $pdo->exec(
                "CREATE TABLE system_icon_changes (\n" .
                "  id INT AUTO_INCREMENT PRIMARY KEY,\n" .
                "  action VARCHAR(16) NOT NULL,\n" .
                "  system_icon_id INT NULL,\n" .
                "  name VARCHAR(255) NULL,\n" .
                "  file_path VARCHAR(255) NULL,\n" .
                "  created_at DATETIME NOT NULL\n" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (\Throwable $e) {
            // ignore if no permission
        }
    }

    public static function record(string $action, ?int $systemIconId, ?string $name, ?string $filePath): void
    {
        $action = trim($action);
        if (!in_array($action, ['create', 'update', 'delete'], true)) {
            return;
        }

        self::ensureTable();

        $pdo = Database::getConnection();
        $now = date('Y-m-d H:i:s');
        try {
            $stmt = $pdo->prepare('INSERT INTO system_icon_changes (action, system_icon_id, name, file_path, created_at) VALUES (:a,:sid,:n,:p,:ca)');
            $stmt->execute([
                ':a' => $action,
                ':sid' => $systemIconId,
                ':n' => $name,
                ':p' => $filePath,
                ':ca' => $now,
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function latestId(): int
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->query('SELECT MAX(id) FROM system_icon_changes');
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function listSinceId(int $afterId, int $limit = 200): array
    {
        self::ensureTable();
        $afterId = max(0, (int)$afterId);
        $limit = max(1, min(500, (int)$limit));

        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare('SELECT * FROM system_icon_changes WHERE id > :id ORDER BY id ASC LIMIT ' . $limit);
            $stmt->execute([':id' => $afterId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
