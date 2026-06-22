<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class StockTrade
{
    public static function create(int $userId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO stock_trades (user_id, symbol, name, type, price, quantity, commission, stamp_tax, total_amount) VALUES (:uid, :sym, :name, :type, :price, :qty, :comm, :tax, :total)');
        $stmt->execute([
            ':uid' => $userId, ':sym' => $data['symbol'], ':name' => $data['name'],
            ':type' => $data['type'], ':price' => $data['price'], ':qty' => $data['quantity'],
            ':comm' => $data['commission'], ':tax' => $data['stamp_tax'], ':total' => $data['total_amount'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByUser(int $userId, int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM stock_trades WHERE user_id = :uid ORDER BY created_at DESC LIMIT :lim');
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $rows[] = $row;
        return $rows;
    }
}
