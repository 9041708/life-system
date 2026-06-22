<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class StockWatchlist
{
    public static function add(int $userId, string $symbol, string $name, string $market = 'A'): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT IGNORE INTO stock_watchlist (user_id, symbol, name, market) VALUES (:uid, :sym, :name, :mkt)');
        $stmt->execute([':uid' => $userId, ':sym' => $symbol, ':name' => $name, ':mkt' => $market]);
    }

    public static function remove(int $userId, string $symbol): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('DELETE FROM stock_watchlist WHERE user_id = :uid AND symbol = :sym')->execute([':uid' => $userId, ':sym' => $symbol]);
    }

    public static function listByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM stock_watchlist WHERE user_id = :uid ORDER BY sort_order, id');
        $stmt->execute([':uid' => $userId]);
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $rows[] = $row;
        return $rows;
    }

    public static function isWatched(int $userId, string $symbol): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM stock_watchlist WHERE user_id = :uid AND symbol = :sym');
        $stmt->execute([':uid' => $userId, ':sym' => $symbol]);
        return $stmt->fetch() !== false;
    }
}
