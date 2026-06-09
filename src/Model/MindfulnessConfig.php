<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class MindfulnessConfig
{
    private static array $defaults = [
        'initial_score' => 80.0,
        'checkin_score' => 0.3,
        'positive_items' => '{"运动":3,"学习":2,"家务":2,"正常作息":3,"正能量树洞":1}',
        'negative_items' => '{"自慰":-5,"意淫":-3,"看黄":-3,"参赌":-3,"喝酒":-3,"熬夜":-5,"夜宵":-3,"负能量树洞":-3}',
        'bonus_rules' => '[{"days":7,"bonus":0.5},{"days":15,"bonus":1.0},{"days":30,"bonus":3.0}]',
        'ai_mode' => 'system',
    ];

    public static function get(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM mindfulness_configs WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return self::getDefaults($userId);
        }
        $row['positive_items'] = json_decode($row['positive_items'] ?? '{}', true) ?: [];
        $row['negative_items'] = json_decode($row['negative_items'] ?? '{}', true) ?: [];
        $row['bonus_rules'] = json_decode($row['bonus_rules'] ?? '[]', true) ?: [];
        return $row;
    }

    public static function getDefaults(int $userId): array
    {
        return [
            'user_id' => $userId,
            'initial_score' => self::$defaults['initial_score'],
            'checkin_score' => self::$defaults['checkin_score'],
            'positive_items' => json_decode(self::$defaults['positive_items'], true),
            'negative_items' => json_decode(self::$defaults['negative_items'], true),
            'bonus_rules' => json_decode(self::$defaults['bonus_rules'], true),
            'ai_mode' => 'system',
            'custom_api_url' => '',
            'custom_api_key' => '',
            'custom_model' => '',
        ];
    }

    public static function save(int $userId, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM mindfulness_configs WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $exists = $stmt->fetch();

        $positiveItems = is_array($data['positive_items'] ?? null) ? json_encode($data['positive_items'], JSON_UNESCAPED_UNICODE) : ($data['positive_items'] ?? self::$defaults['positive_items']);
        $negativeItems = is_array($data['negative_items'] ?? null) ? json_encode($data['negative_items'], JSON_UNESCAPED_UNICODE) : ($data['negative_items'] ?? self::$defaults['negative_items']);
        $bonusRules = is_array($data['bonus_rules'] ?? null) ? json_encode($data['bonus_rules'], JSON_UNESCAPED_UNICODE) : ($data['bonus_rules'] ?? self::$defaults['bonus_rules']);

        $fields = [
            'initial_score' => (float)($data['initial_score'] ?? self::$defaults['initial_score']),
            'checkin_score' => (float)($data['checkin_score'] ?? self::$defaults['checkin_score']),
            'positive_items' => $positiveItems,
            'negative_items' => $negativeItems,
            'bonus_rules' => $bonusRules,
            'ai_mode' => in_array($data['ai_mode'] ?? '', ['system', 'custom']) ? $data['ai_mode'] : 'system',
            'custom_api_url' => trim($data['custom_api_url'] ?? ''),
            'custom_api_key' => trim($data['custom_api_key'] ?? ''),
            'custom_model' => trim($data['custom_model'] ?? ''),
        ];

        if ($exists) {
            $sets = [];
            $vals = [];
            foreach ($fields as $k => $v) {
                $sets[] = "{$k} = :{$k}";
                $vals[":{$k}"] = $v;
            }
            $vals[':uid'] = $userId;
            $stmt = $pdo->prepare('UPDATE mindfulness_configs SET ' . implode(', ', $sets) . ' WHERE user_id = :uid');
            $stmt->execute($vals);
        } else {
            $cols = array_keys($fields);
            $cols[] = 'user_id';
            $placeholders = array_map(fn($c) => ':' . $c, $cols);
            $vals = [];
            foreach ($fields as $k => $v) {
                $vals[":{$k}"] = $v;
            }
            $vals[':user_id'] = $userId;
            $stmt = $pdo->prepare('INSERT INTO mindfulness_configs (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $placeholders) . ')');
            $stmt->execute($vals);
        }
    }

    public static function calculateCurrentScore(int $userId): float
    {
        $config = self::get($userId);
        $score = (float)$config['initial_score'];

        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(score_change), 0) FROM mindfulness_checkins WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $score += (float)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(score_change), 0) FROM mindfulness_daily_records WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $score += (float)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(score_change), 0) FROM mindfulness_treasures WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $score += (float)$stmt->fetchColumn();

        return max(0, min(100, round($score, 1)));
    }
}
