<?php
namespace App\Model;

use App\Service\Database;

class EasyTodoMemo
{
    public static function create(int $userId, string $content): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO easytodo_memo (user_id, content) VALUES (?, ?)");
        $stmt->execute([$userId, $content]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByUser(int $userId, int $limit = 100): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM easytodo_memo WHERE user_id = ? ORDER BY updated_at DESC LIMIT " . (int)$limit);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM easytodo_memo WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function update(int $id, int $userId, string $content): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE easytodo_memo SET content = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$content, $id, $userId]);
    }

    public static function delete(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM easytodo_memo WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}