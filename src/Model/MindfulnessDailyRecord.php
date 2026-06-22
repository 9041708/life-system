<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class MindfulnessDailyRecord
{
    public static function add(int $userId, string $date, string $type, string $itemName, float $scoreChange): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO mindfulness_daily_records (user_id, record_date, type, item_name, score_change) VALUES (:uid, :date, :type, :name, :score)');
        $stmt->execute([':uid' => $userId, ':date' => $date, ':type' => $type, ':name' => $itemName, ':score' => $scoreChange]);
        return (int)$pdo->lastInsertId();
    }

    public static function getByDate(int $userId, string $date): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM mindfulness_daily_records WHERE user_id = :uid AND record_date = :date ORDER BY created_at');
        $stmt->execute([':uid' => $userId, ':date' => $date]);
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function getMonthSummary(int $userId, int $year, int $month): array
    {
        $pdo = Database::getConnection();
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $stmt = $pdo->prepare('SELECT record_date, type, SUM(score_change) as total FROM mindfulness_daily_records WHERE user_id = :uid AND record_date BETWEEN :start AND :end GROUP BY record_date, type');
        $stmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $date = $row['record_date'];
            if (!isset($result[$date])) {
                $result[$date] = ['positive' => 0, 'negative' => 0];
            }
            $result[$date][$row['type']] = (float)$row['total'];
        }
        return $result;
    }

    public static function getRecentNegativeCount(int $userId, int $days = 7): int
    {
        $pdo = Database::getConnection();
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $stmt = $pdo->prepare('SELECT COUNT(DISTINCT record_date) FROM mindfulness_daily_records WHERE user_id = :uid AND type = \'negative\' AND record_date >= :start');
        $stmt->execute([':uid' => $userId, ':start' => $startDate]);
        return (int)$stmt->fetchColumn();
    }

    public static function delete(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM mindfulness_daily_records WHERE id = :id AND user_id = :uid');
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }
}
