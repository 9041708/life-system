<?php
namespace App\Model;

use App\Service\Database;

class EasyTodoCountdown
{
    public static function create(int $userId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO easytodo_countdown (user_id, title, target_time, target_date, repeat_type, repeat_weekday, repeat_month_day, display_mode, enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $data['title'],
            $data['target_time'],
            $data['target_date'] ?? null,
            $data['repeat_type'] ?? 'none',
            $data['repeat_weekday'] ?? null,
            $data['repeat_month_day'] ?? null,
            (int)($data['display_mode'] ?? 2),
            (int)($data['enabled'] ?? 1),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM easytodo_countdown WHERE user_id = ? ORDER BY enabled DESC, target_time ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM easytodo_countdown WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $pdo = Database::getConnection();
        $fields = [];
        $params = [];
        $allowed = ['title','target_time','target_date','repeat_type','repeat_weekday','repeat_month_day','display_mode','enabled'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "`{$f}` = ?";
                $params[] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $params[] = $id;
        $params[] = $userId;
        $stmt = $pdo->prepare("UPDATE easytodo_countdown SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?");
        return $stmt->execute($params);
    }

    public static function delete(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM easytodo_countdown WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}