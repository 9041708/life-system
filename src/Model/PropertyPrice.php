<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class PropertyPrice
{
    public static function add(int $propertyId, int $userId, float $price, float $unitPrice, string $source = 'manual'): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO property_prices (property_id, user_id, price, unit_price, source, recorded_at) VALUES (:pid, :uid, :price, :up, :src, CURDATE())');
        $stmt->execute([':pid' => $propertyId, ':uid' => $userId, ':price' => $price, ':up' => $unitPrice, ':src' => $source]);
    }

    public static function getHistory(int $propertyId, int $userId, int $days = 90): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM property_prices WHERE property_id = :pid AND user_id = :uid AND recorded_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY) ORDER BY recorded_at ASC');
        $stmt->bindValue(':pid', $propertyId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $rows[] = $row;
        return $rows;
    }

    public static function getLatest(int $propertyId, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM property_prices WHERE property_id = :pid AND user_id = :uid ORDER BY recorded_at DESC LIMIT 1');
        $stmt->execute([':pid' => $propertyId, ':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
