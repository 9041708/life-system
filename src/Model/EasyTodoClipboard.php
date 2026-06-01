<?php
namespace App\Model;

use App\Service\Database;

class EasyTodoClipboard
{
    public static function create(int $userId, string $content): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO easytodo_clipboard_history (user_id, content) VALUES (?, ?)");
        $stmt->execute([$userId, $content]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByUser(int $userId, int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM easytodo_clipboard_history WHERE user_id = ? ORDER BY created_at DESC LIMIT " . (int)$limit);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function delete(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM easytodo_clipboard_history WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public static function clearAll(int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM easytodo_clipboard_history WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
}