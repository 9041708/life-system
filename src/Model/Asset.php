<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class Asset
{
    public static function allByUser(int $userId, ?string $keyword = null): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT * FROM assets WHERE user_id = :uid';
        $params = [':uid' => $userId];
        if ($keyword !== null && $keyword !== '') {
            $sql .= ' AND name LIKE :kw';
            $params[':kw'] = '%' . $keyword . '%';
        }
        $sql .= ' ORDER BY created_at DESC, id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findByUser(int $userId, int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM assets WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(int $userId, string $name, string $acquiredDate, float $valueAmount, ?string $iconType, ?string $iconValue, ?string $remark = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO assets (user_id, name, acquired_date, value_amount, icon_type, icon_value, status, remark, created_at, updated_at) VALUES (:uid,:name,:adate,:value,:icon_type,:icon_value,\'active\',:remark,NOW(),NOW())');
        $stmt->execute([
            ':uid' => $userId,
            ':name' => $name,
            ':adate' => $acquiredDate,
            ':value' => $valueAmount,
            ':icon_type' => $iconType,
            ':icon_value' => $iconValue,
            ':remark' => $remark,
        ]);
    }

    public static function update(int $userId, int $id, string $name, string $acquiredDate, float $valueAmount, ?string $iconType, ?string $iconValue, ?string $remark = null): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE assets SET name = :name, acquired_date = :adate, value_amount = :value, icon_type = :icon_type, icon_value = :icon_value, remark = :remark, updated_at = NOW() WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':name' => $name,
            ':adate' => $acquiredDate,
            ':value' => $valueAmount,
            ':icon_type' => $iconType,
            ':icon_value' => $iconValue,
            ':remark' => $remark,
            ':id' => $id,
            ':uid' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function transfer(int $userId, int $id, string $transferDate, float $transferPrice): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE assets SET status = \'transferred\', transfer_date = :tdate, transfer_price = :tprice, updated_at = NOW() WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':tdate' => $transferDate,
            ':tprice' => $transferPrice,
            ':id' => $id,
            ':uid' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $userId, int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM assets WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
