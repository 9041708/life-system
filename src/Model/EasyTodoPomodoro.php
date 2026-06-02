<?php
namespace App\Model;

use App\Service\Database;

class EasyTodoPomodoro
{
    public static function getSettings(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM easytodo_pomodoro_setting WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return [
                'work_duration' => 25,
                'short_break' => 5,
                'long_break' => 15,
                'long_break_interval' => 4,
                'auto_start_break' => 0,
                'auto_start_work' => 0,
            ];
        }
        return $row;
    }

    public static function saveSettings(int $userId, array $data): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO easytodo_pomodoro_setting (user_id, work_duration, short_break, long_break, long_break_interval, auto_start_break, auto_start_work) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE work_duration = VALUES(work_duration), short_break = VALUES(short_break), long_break = VALUES(long_break), long_break_interval = VALUES(long_break_interval), auto_start_break = VALUES(auto_start_break), auto_start_work = VALUES(auto_start_work)");
        return $stmt->execute([
            $userId,
            (int)($data['work_duration'] ?? 25),
            (int)($data['short_break'] ?? 5),
            (int)($data['long_break'] ?? 15),
            (int)($data['long_break_interval'] ?? 4),
            (int)($data['auto_start_break'] ?? 0),
            (int)($data['auto_start_work'] ?? 0),
        ]);
    }

    public static function createSession(int $userId, ?int $ledgerId, string $type, string $startedAt, ?string $endedAt, int $durationMinutes, ?string $note): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO easytodo_pomodoro_session (user_id, ledger_id, type, started_at, ended_at, duration_minutes, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $ledgerId, $type, $startedAt, $endedAt, $durationMinutes, $note]);
        return (int)$pdo->lastInsertId();
    }

    public static function listSessions(int $userId, int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM easytodo_pomodoro_session WHERE user_id = ? ORDER BY started_at DESC LIMIT " . (int)$limit);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function countTodayWorkSessions(int $userId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM easytodo_pomodoro_session WHERE user_id = ? AND type = 'work' AND DATE(started_at) = CURDATE()");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }

    public static function sumTodayWorkMinutes(int $userId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(duration_minutes), 0) as total FROM easytodo_pomodoro_session WHERE user_id = ? AND type = 'work' AND DATE(started_at) = CURDATE()");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }
}