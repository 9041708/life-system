<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class AiQuota
{
    public static function get(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM user_ai_quotas WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return [
                'user_id' => $userId,
                'system_quota' => 10,
                'system_used' => 0,
                'purchased_quota' => 0,
                'purchased_used' => 0,
            ];
        }
        return $row;
    }

    public static function getRemaining(int $userId): int
    {
        $q = self::get($userId);
        $systemRemaining = max(0, (int)$q['system_quota'] - (int)$q['system_used']);
        $purchasedRemaining = max(0, (int)$q['purchased_quota'] - (int)$q['purchased_used']);
        return $systemRemaining + $purchasedRemaining;
    }

    public static function consume(int $userId): bool
    {
        $q = self::get($userId);
        $pdo = Database::getConnection();

        $existing = $pdo->prepare('SELECT id FROM user_ai_quotas WHERE user_id = :uid');
        $existing->execute([':uid' => $userId]);
        if (!$existing->fetch()) {
            $stmt = $pdo->prepare('INSERT INTO user_ai_quotas (user_id, system_used) VALUES (:uid, 1)');
            return $stmt->execute([':uid' => $userId]);
        }

        $systemRemaining = max(0, (int)$q['system_quota'] - (int)$q['system_used']);
        if ($systemRemaining > 0) {
            $stmt = $pdo->prepare('UPDATE user_ai_quotas SET system_used = system_used + 1 WHERE user_id = :uid');
            return $stmt->execute([':uid' => $userId]);
        }

        $purchasedRemaining = max(0, (int)$q['purchased_quota'] - (int)$q['purchased_used']);
        if ($purchasedRemaining > 0) {
            $stmt = $pdo->prepare('UPDATE user_ai_quotas SET purchased_used = purchased_used + 1 WHERE user_id = :uid');
            return $stmt->execute([':uid' => $userId]);
        }

        return false;
    }

    public static function hasQuota(int $userId): bool
    {
        return self::getRemaining($userId) > 0;
    }

    public static function adminGrant(int $userId, int $amount): void
    {
        $pdo = Database::getConnection();
        $existing = $pdo->prepare('SELECT id FROM user_ai_quotas WHERE user_id = :uid');
        $existing->execute([':uid' => $userId]);
        if (!$existing->fetch()) {
            $stmt = $pdo->prepare('INSERT INTO user_ai_quotas (user_id, purchased_quota) VALUES (:uid, :amt)');
            $stmt->execute([':uid' => $userId, ':amt' => $amount]);
        } else {
            $stmt = $pdo->prepare('UPDATE user_ai_quotas SET purchased_quota = purchased_quota + :amt WHERE user_id = :uid');
            $stmt->execute([':amt' => $amount, ':uid' => $userId]);
        }
    }

    public static function adminSetQuota(int $userId, int $systemQuota, int $purchasedQuota): void
    {
        $pdo = Database::getConnection();
        $existing = $pdo->prepare('SELECT id FROM user_ai_quotas WHERE user_id = :uid');
        $existing->execute([':uid' => $userId]);
        if (!$existing->fetch()) {
            $stmt = $pdo->prepare('INSERT INTO user_ai_quotas (user_id, system_quota, purchased_quota) VALUES (:uid, :sq, :pq)');
            $stmt->execute([':uid' => $userId, ':sq' => $systemQuota, ':pq' => $purchasedQuota]);
        } else {
            $stmt = $pdo->prepare('UPDATE user_ai_quotas SET system_quota = :sq, purchased_quota = :pq WHERE user_id = :uid');
            $stmt->execute([':sq' => $systemQuota, ':pq' => $purchasedQuota, ':uid' => $userId]);
        }
    }

    public static function listAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT q.*, u.username, u.nickname FROM user_ai_quotas q LEFT JOIN users u ON q.user_id = u.id ORDER BY q.updated_at DESC');
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function getPricingPlans(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM ai_pricing_plans WHERE enabled = 1 ORDER BY sort_order, id');
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function getAllPricingPlans(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM ai_pricing_plans ORDER BY sort_order, id');
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function savePricingPlan(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE ai_pricing_plans SET name = :name, quota = :quota, original_price = :op, price = :price, sort_order = :sort, enabled = :enabled WHERE id = :id');
            $stmt->execute([':name' => $data['name'], ':quota' => $data['quota'], ':op' => $data['original_price'], ':price' => $data['price'], ':sort' => $data['sort_order'], ':enabled' => $data['enabled'] ?? 1, ':id' => $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO ai_pricing_plans (name, quota, original_price, price, sort_order, enabled) VALUES (:name, :quota, :op, :price, :sort, :enabled)');
            $stmt->execute([':name' => $data['name'], ':quota' => $data['quota'], ':op' => $data['original_price'], ':price' => $data['price'], ':sort' => $data['sort_order'], ':enabled' => $data['enabled'] ?? 1]);
        }
    }

    public static function deletePricingPlan(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM ai_pricing_plans WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
