<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class StockAccount
{
    public static function getOrCreate(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM stock_accounts WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
        $pdo->prepare('INSERT INTO stock_accounts (user_id) VALUES (:uid)')->execute([':uid' => $userId]);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function updateBalance(int $userId, float $amount): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE stock_accounts SET balance = :bal WHERE user_id = :uid')->execute([':bal' => $amount, ':uid' => $userId]);
    }

    public static function resetAccount(int $userId, float $initialBalance): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('DELETE FROM stock_positions WHERE user_id = :uid')->execute([':uid' => $userId]);
        $pdo->prepare('DELETE FROM stock_trades WHERE user_id = :uid')->execute([':uid' => $userId]);
        $pdo->prepare('UPDATE stock_accounts SET balance = :bal, initial_balance = :bal WHERE user_id = :uid')->execute([':bal' => $initialBalance, ':uid' => $userId]);
    }
}
