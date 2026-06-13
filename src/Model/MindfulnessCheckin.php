<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class MindfulnessCheckin
{
    public static function checkin(int $userId, string $date, float $scoreChange): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT IGNORE INTO mindfulness_checkins (user_id, checkin_date, score_change) VALUES (:uid, :date, :score)');
        return $stmt->execute([':uid' => $userId, ':date' => $date, ':score' => $scoreChange]);
    }

    public static function isCheckedIn(int $userId, string $date): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM mindfulness_checkins WHERE user_id = :uid AND checkin_date = :date');
        $stmt->execute([':uid' => $userId, ':date' => $date]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function getMonthRecords(int $userId, int $year, int $month): array
    {
        $pdo = Database::getConnection();
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $stmt = $pdo->prepare('SELECT checkin_date, score_change FROM mindfulness_checkins WHERE user_id = :uid AND checkin_date BETWEEN :start AND :end');
        $stmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['checkin_date']] = (float)$row['score_change'];
        }
        return $result;
    }

    public static function getAllCheckinDates(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT checkin_date FROM mindfulness_checkins WHERE user_id = :uid ORDER BY checkin_date');
        $stmt->execute([':uid' => $userId]);
        $dates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dates[] = $row['checkin_date'];
        }
        return $dates;
    }

    public static function getStreakStats(int $userId): array
    {
        $dates = self::getAllCheckinDates($userId);
        if (empty($dates)) {
            return ['max_streak' => 0, 'current_streak' => 0];
        }

        // 查询所有有负念记录的日期
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT DISTINCT record_date FROM mindfulness_daily_records WHERE user_id = :uid AND type = "negative"');
        $stmt->execute([':uid' => $userId]);
        $negativeDates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $negativeDates[] = $row['record_date'];
        }

        $maxStreak = 1;
        $currentStreak = 1;
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // 第一天如果有负念记录则从0开始
        if (in_array($dates[0], $negativeDates)) {
            $currentStreak = 0;
            $maxStreak = 0;
        }

        for ($i = 1; $i < count($dates); $i++) {
            $prev = date('Y-m-d', strtotime($dates[$i - 1]));
            $curr = $dates[$i];

            // 当天有负念记录，连续签到归零
            if (in_array($curr, $negativeDates)) {
                $currentStreak = 0;
                continue;
            }

            if (date('Y-m-d', strtotime($prev . ' +1 day')) === $curr && $currentStreak > 0) {
                $currentStreak++;
            } else {
                $currentStreak = 1;
            }
            $maxStreak = max($maxStreak, $currentStreak);
        }

        $lastDate = end($dates);
        if ($lastDate !== $today && $lastDate !== $yesterday) {
            $currentStreak = 0;
        }

        return ['max_streak' => $maxStreak, 'current_streak' => $currentStreak];
    }
}
