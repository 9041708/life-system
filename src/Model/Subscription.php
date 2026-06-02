<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class Subscription
{
    public static function cleanupExpired(int $userId): void
    {
        $pdo = Database::getConnection();
        // 关闭超过 30 天的逻辑删除记录物理清理
        $pdo->prepare("DELETE FROM subscriptions WHERE user_id = :uid AND status = 'closed' AND expire_date IS NOT NULL AND expire_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)")
            ->execute([':uid' => $userId]);
    }

    public static function allActiveByUser(int $userId, ?string $keyword = null): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT * FROM subscriptions WHERE user_id = :uid AND status = \'active\'';
        $params = [':uid' => $userId];
        if ($keyword !== null && $keyword !== '') {
            $sql .= ' AND platform LIKE :kw';
            $params[':kw'] = '%' . $keyword . '%';
        }
        // 排序规则：
        // 1. 未到期的订阅优先（到期日越近越靠前）；
        // 2. 无到期日（买断）其次；
        // 3. 已过期的订阅排在最后。
        $sql .= ' ORDER BY CASE WHEN expire_date IS NULL THEN 1 WHEN expire_date < CURDATE() THEN 2 ELSE 0 END, expire_date ASC, id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findByUser(int $userId, int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM subscriptions WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(
        int $userId,
        string $platform,
        string $type,
        float $price,
        ?string $expireDate,
        bool $autoRenew,
        ?string $period,
        ?string $iconType,
        ?string $iconValue,
        ?string $remark = null
    ): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO subscriptions (user_id, platform, type, price, expire_date, auto_renew, period, icon_type, icon_value, status, remark, created_at, updated_at) VALUES (:uid,:platform,:type,:price,:expire_date,:auto_renew,:period,:icon_type,:icon_value,\'active\',:remark,NOW(),NOW())');
        $stmt->execute([
            ':uid' => $userId,
            ':platform' => $platform,
            ':type' => $type,
            ':price' => $price,
            ':expire_date' => $expireDate,
            ':auto_renew' => $autoRenew ? 1 : 0,
            ':period' => $period,
            ':icon_type' => $iconType,
            ':icon_value' => $iconValue,
            ':remark' => $remark,
        ]);
    }

    public static function update(
        int $userId,
        int $id,
        string $platform,
        string $type,
        float $price,
        ?string $expireDate,
        bool $autoRenew,
        ?string $period,
        ?string $iconType,
        ?string $iconValue,
        ?string $remark = null
    ): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE subscriptions SET platform = :platform, type = :type, price = :price, expire_date = :expire_date, auto_renew = :auto_renew, period = :period, icon_type = :icon_type, icon_value = :icon_value, remark = :remark, updated_at = NOW() WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':platform' => $platform,
            ':type' => $type,
            ':price' => $price,
            ':expire_date' => $expireDate,
            ':auto_renew' => $autoRenew ? 1 : 0,
            ':period' => $period,
            ':icon_type' => $iconType,
            ':icon_value' => $iconValue,
            ':remark' => $remark,
            ':id' => $id,
            ':uid' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function renew(
        int $userId,
        int $id,
        string $type,
        float $price,
        ?string $expireDate,
        bool $autoRenew,
        ?string $period
    ): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE subscriptions SET type = :type, price = :price, expire_date = :expire_date, auto_renew = :auto_renew, period = :period, status = \'active\', updated_at = NOW() WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':type' => $type,
            ':price' => $price,
            ':expire_date' => $expireDate,
            ':auto_renew' => $autoRenew ? 1 : 0,
            ':period' => $period,
            ':id' => $id,
            ':uid' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function logicalDelete(int $userId, int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'closed', updated_at = NOW() WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
