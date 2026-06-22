<?php
namespace App\Model;

use App\Service\Database;

class ResumeData
{
    private static function ensureTable(): void
    {
        $pdo = Database::getConnection();
        try {
            $pdo->query('SELECT 1 FROM resume_data LIMIT 1');
        } catch (\Throwable $e) {
            $pdo->exec('CREATE TABLE IF NOT EXISTS resume_data (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(100) DEFAULT \'未命名简历\',
                template VARCHAR(50) DEFAULT \'simple\',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                data JSON NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }
    }

    public static function getAll(int $userId): array
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, name, template, updated_at FROM resume_data WHERE user_id = ? ORDER BY updated_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function get(int $userId): ?array
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM resume_data WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row && isset($row['data'])) {
            $row['data'] = json_decode($row['data'], true) ?? [];
        }
        return $row ?: null;
    }

    public static function getById(int $id, int $userId): ?array
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM resume_data WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row && isset($row['data'])) {
            $row['data'] = json_decode($row['data'], true) ?? [];
        }
        return $row ?: null;
    }

    public static function save(?int $id, int $userId, array $data, string $template = 'simple', string $name = ''): int
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($name === '') {
            $name = $data['basic']['name'] ?? '未命名简历';
        }

        if ($id !== null) {
            $pdo->prepare('UPDATE resume_data SET name = ?, template = ?, data = ?, updated_at = NOW() WHERE id = ? AND user_id = ?')
                ->execute([$name, $template, $json, $id, $userId]);
            return $id;
        }

        $pdo->prepare('INSERT INTO resume_data (user_id, name, template, data, updated_at) VALUES (?, ?, ?, ?, NOW())')
            ->execute([$userId, $name, $template, $json]);
        return (int)$pdo->lastInsertId();
    }

    public static function copy(int $id, int $userId): ?int
    {
        self::ensureTable();
        $source = self::getById($id, $userId);
        if (!$source) {
            return null;
        }
        $data = $source['data'] ?? [];
        $template = $source['template'] ?? 'simple';
        $name = ($source['name'] ?? '未命名简历') . ' (副本)';
        return self::save(null, $userId, $data, $template, $name);
    }

    public static function delete(int $id, int $userId): bool
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM resume_data WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }
}
