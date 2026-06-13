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

    public static function consume(int $userId, string $source = '', string $detail = ''): bool
    {
        $q = self::get($userId);
        $pdo = Database::getConnection();

        $existing = $pdo->prepare('SELECT id FROM user_ai_quotas WHERE user_id = :uid');
        $existing->execute([':uid' => $userId]);
        if (!$existing->fetch()) {
            $stmt = $pdo->prepare('INSERT INTO user_ai_quotas (user_id, system_used) VALUES (:uid, 1)');
            $result = $stmt->execute([':uid' => $userId]);
        } else {
            $systemRemaining = max(0, (int)$q['system_quota'] - (int)$q['system_used']);
            if ($systemRemaining > 0) {
                $stmt = $pdo->prepare('UPDATE user_ai_quotas SET system_used = system_used + 1 WHERE user_id = :uid');
                $result = $stmt->execute([':uid' => $userId]);
            } else {
                $purchasedRemaining = max(0, (int)$q['purchased_quota'] - (int)$q['purchased_used']);
                if ($purchasedRemaining > 0) {
                    $stmt = $pdo->prepare('UPDATE user_ai_quotas SET purchased_used = purchased_used + 1 WHERE user_id = :uid');
                    $result = $stmt->execute([':uid' => $userId]);
                } else {
                    return false;
                }
            }
        }

        // 记录使用日志
        if ($source !== '') {
            try {
                $logStmt = $pdo->prepare('INSERT INTO ai_usage_logs (user_id, source, detail) VALUES (:uid, :src, :detail)');
                $logStmt->execute([':uid' => $userId, ':src' => $source, ':detail' => mb_substr($detail, 0, 200)]);
            } catch (\Throwable $e) {}
        }

        return true;
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

    public static function getUsageLogs(int $userId, int $limit = 20, int $offset = 0): array
    {
        $pdo = Database::getConnection();
        // 自动清理10天前的日志
        $pdo->prepare('DELETE FROM ai_usage_logs WHERE user_id = :uid AND created_at < DATE_SUB(NOW(), INTERVAL 10 DAY)')->execute([':uid' => $userId]);
        $stmt = $pdo->prepare('SELECT * FROM ai_usage_logs WHERE user_id = :uid ORDER BY created_at DESC LIMIT :lim OFFSET :off');
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function countUsageLogs(int $userId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM ai_usage_logs WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function cleanupLogs(int $days): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM ai_usage_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)');
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->rowCount();
    }

    public static function getAllUsageLogs(int $limit = 100): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT l.*, u.username, u.nickname FROM ai_usage_logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.created_at DESC LIMIT :lim');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
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

    public static function listPaged(int $limit, int $offset): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT q.*, u.username, u.nickname FROM user_ai_quotas q LEFT JOIN users u ON q.user_id = u.id ORDER BY q.updated_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
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
