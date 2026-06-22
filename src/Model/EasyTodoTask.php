<?php
namespace App\Model;

use App\Service\Database;

class EasyTodoTask
{
    public static function ensureAdvancedColumns(): void
    {
        $pdo = Database::getConnection();
        $cols = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM easytodo_task");
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) { $cols[] = $r['Field']; }
        if (!in_array('color', $cols)) $pdo->exec("ALTER TABLE easytodo_task ADD COLUMN color VARCHAR(20) DEFAULT 'blue' AFTER tags");
        if (!in_array('recurrence', $cols)) $pdo->exec("ALTER TABLE easytodo_task ADD COLUMN recurrence VARCHAR(20) DEFAULT 'none' AFTER color");
        if (!in_array('reminder_at', $cols)) $pdo->exec("ALTER TABLE easytodo_task ADD COLUMN reminder_at DATETIME NULL AFTER recurrence");
        if (!in_array('reminder_advance', $cols)) $pdo->exec("ALTER TABLE easytodo_task ADD COLUMN reminder_advance INT DEFAULT 0 AFTER reminder_at");
    }

    public static function create(int $userId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO easytodo_task (user_id, ledger_id, title, description, completed, pinned, task_date, tags, color, recurrence, reminder_at, reminder_advance, trans_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userId,
            $data['ledger_id'] ?? null,
            $data['title'] ?? '',
            $data['description'] ?? null,
            (int)($data['completed'] ?? 0),
            (int)($data['pinned'] ?? 0),
            $data['task_date'] ?? null,
            $data['tags'] ?? null,
            $data['color'] ?? 'blue',
            $data['recurrence'] ?? 'none',
            $data['reminder_at'] ?? null,
            (int)($data['reminder_advance'] ?? 0),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByUser(int $userId, ?int $ledgerId = null, ?string $date = null, int $limit = 100): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM easytodo_task WHERE user_id = ?";
        $params = [$userId];
        if ($ledgerId !== null) {
            $sql .= " AND (ledger_id = ? OR ledger_id IS NULL)";
            $params[] = $ledgerId;
        }
        if ($date) {
            $sql .= " AND task_date = ?";
            $params[] = $date;
        }
        $sql .= " ORDER BY pinned DESC, sort_order ASC, trans_time DESC LIMIT " . (int)$limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM easytodo_task WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $pdo = Database::getConnection();
        $fields = [];
        $params = [];
        $allowed = ['title','description','completed','pinned','task_date','tags','sort_order','color','recurrence','reminder_at','reminder_advance'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "`{$f}` = ?";
                $params[] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        if (isset($data['completed']) && $data['completed']) {
            $fields[] = "completed_at = NOW()";
        } else {
            $fields[] = "completed_at = NULL";
        }
        $params[] = $id;
        $params[] = $userId;
        $stmt = $pdo->prepare("UPDATE easytodo_task SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?");
        return $stmt->execute($params);
    }

    public static function delete(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM easytodo_task WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public static function countByDate(int $userId, string $date): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT completed, COUNT(*) as cnt FROM easytodo_task WHERE user_id = ? AND task_date = ? GROUP BY completed");
        $stmt->execute([$userId, $date]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $total = $done = 0;
        foreach ($rows as $r) {
            $total += (int)$r['cnt'];
            if ((int)$r['completed'] === 1) $done += (int)$r['cnt'];
        }
        return ['total' => $total, 'done' => $done];
    }

    public static function countByWeek(int $userId, string $weekStart): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT DATE(task_date) as dt, completed, COUNT(*) as cnt FROM easytodo_task WHERE user_id = ? AND task_date >= ? AND task_date < DATE_ADD(?, INTERVAL 7 DAY) GROUP BY DATE(task_date), completed ORDER BY dt");
        $stmt->execute([$userId, $weekStart, $weekStart]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function listByMonth(int $userId, int $year, int $month): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT task_date, completed, COUNT(*) as cnt FROM easytodo_task WHERE user_id = ? AND YEAR(task_date) = ? AND MONTH(task_date) = ? GROUP BY task_date, completed");
        $stmt->execute([$userId, $year, $month]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getPendingReminders(int $userId): array
    {
        $pdo = Database::getConnection();
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT * FROM easytodo_task WHERE user_id = ? AND completed = 0 AND task_date >= ? AND (reminder_at IS NULL OR reminder_at <= ?) ORDER BY task_date ASC LIMIT 20");
        $stmt->execute([$userId, $today, $now]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}