<?php
namespace App\Model;

use App\Service\Database;

class EasyTodoReport
{
    public static function create(int $userId, ?int $ledgerId, string $type, string $content, ?string $taskSummary): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO easytodo_report (user_id, ledger_id, type, content, task_summary) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $ledgerId, $type, $content, $taskSummary]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByUser(int $userId, int $limit = 30): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM easytodo_report WHERE user_id = ? ORDER BY generated_at DESC LIMIT " . (int)$limit);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM easytodo_report WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function delete(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM easytodo_report WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}