<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class StockPosition
{
    public static function get(int $userId, string $symbol): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM stock_positions WHERE user_id = :uid AND symbol = :sym');
        $stmt->execute([':uid' => $userId, ':sym' => $symbol]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function listByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM stock_positions WHERE user_id = :uid AND quantity > 0 ORDER BY updated_at DESC');
        $stmt->execute([':uid' => $userId]);
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $rows[] = $row;
        return $rows;
    }

    public static function buy(int $userId, string $symbol, string $name, int $quantity, float $price): void
    {
        $pdo = Database::getConnection();
        $existing = self::get($userId, $symbol);
        if ($existing) {
            $newQty = (int)$existing['quantity'] + $quantity;
            $newCost = ((float)$existing['avg_cost'] * (int)$existing['quantity'] + $price * $quantity) / $newQty;
            $pdo->prepare('UPDATE stock_positions SET quantity = :q, avg_cost = :c WHERE user_id = :uid AND symbol = :sym')
                ->execute([':q' => $newQty, ':c' => round($newCost, 4), ':uid' => $userId, ':sym' => $symbol]);
        } else {
            $pdo->prepare('INSERT INTO stock_positions (user_id, symbol, name, quantity, avg_cost) VALUES (:uid, :sym, :name, :q, :c)')
                ->execute([':uid' => $userId, ':sym' => $symbol, ':name' => $name, ':q' => $quantity, ':c' => $price]);
        }
    }

    public static function sell(int $userId, string $symbol, int $quantity): void
    {
        $pdo = Database::getConnection();
        $existing = self::get($userId, $symbol);
        if (!$existing || (int)$existing['quantity'] < $quantity) return;
        $newQty = (int)$existing['quantity'] - $quantity;
        if ($newQty <= 0) {
            $pdo->prepare('DELETE FROM stock_positions WHERE user_id = :uid AND symbol = :sym')->execute([':uid' => $userId, ':sym' => $symbol]);
        } else {
            $pdo->prepare('UPDATE stock_positions SET quantity = :q WHERE user_id = :uid AND symbol = :sym')->execute([':q' => $newQty, ':uid' => $userId, ':sym' => $symbol]);
        }
    }
}
