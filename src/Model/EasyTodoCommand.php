<?php
namespace App\Model;

use App\Service\Database;

class EasyTodoCommand
{
    public static function create(int $userId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO easytodo_command (user_id, trigger, name, content, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $data['trigger'],
            $data['name'],
            $data['content'],
            (int)($data['sort_order'] ?? 0),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM easytodo_command WHERE user_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function findByTrigger(string $trigger, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM easytodo_command WHERE `trigger` = ? AND user_id = ?");
        $stmt->execute([$trigger, $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $pdo = Database::getConnection();
        $fields = [];
        $params = [];
        foreach (['trigger','name','content','sort_order'] as $f) {
            if (isset($data[$f])) {
                $fields[] = "`{$f}` = ?";
                $params[] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $params[] = $id;
        $params[] = $userId;
        $stmt = $pdo->prepare("UPDATE easytodo_command SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?");
        return $stmt->execute($params);
    }

    public static function delete(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM easytodo_command WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}