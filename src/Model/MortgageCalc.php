<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class MortgageCalc
{
    public static function save(int $userId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO mortgage_calcs (user_id, principal, rate, months, method, monthly_payment, total_interest) VALUES (:uid, :p, :r, :m, :mt, :mp, :ti)');
        $stmt->execute([
            ':uid' => $userId, ':p' => $data['principal'], ':r' => $data['rate'],
            ':m' => $data['months'], ':mt' => $data['method'],
            ':mp' => $data['monthly_payment'], ':ti' => $data['total_interest'],
        ]);
        $id = (int)$pdo->lastInsertId();
        self::cleanOld($userId, 10);
        return $id;
    }

    public static function listByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM mortgage_calcs WHERE user_id = :uid ORDER BY created_at DESC LIMIT 10');
        $stmt->execute([':uid' => $userId]);
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $rows[] = $row;
        return $rows;
    }

    public static function delete(int $id, int $userId): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('DELETE FROM mortgage_calcs WHERE id = :id AND user_id = :uid')->execute([':id' => $id, ':uid' => $userId]);
    }

    public static function clearAll(int $userId): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('DELETE FROM mortgage_calcs WHERE user_id = :uid')->execute([':uid' => $userId]);
    }

    private static function cleanOld(int $userId, int $keep): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM mortgage_calcs WHERE user_id = :uid ORDER BY created_at DESC');
        $stmt->execute([':uid' => $userId]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        if (count($ids) <= $keep) return;
        foreach (array_slice($ids, $keep) as $id) {
            $pdo->prepare('DELETE FROM mortgage_calcs WHERE id = :id')->execute([':id' => $id]);
        }
    }

    public static function calcEqualPayment(float $principalWan, float $annualRate, int $months): array
    {
        $P = $principalWan * 10000;
        $r = $annualRate / 100 / 12;
        if ($r == 0) {
            $monthly = $P / $months;
            return ['monthly' => round($monthly, 2), 'total_interest' => 0, 'total' => round($P, 2)];
        }
        $monthly = $P * $r * pow(1 + $r, $months) / (pow(1 + $r, $months) - 1);
        $total = $monthly * $months;
        return ['monthly' => round($monthly, 2), 'total_interest' => round($total - $P, 2), 'total' => round($total, 2)];
    }

    public static function calcEqualPrincipal(float $principalWan, float $annualRate, int $months): array
    {
        $P = $principalWan * 10000;
        $r = $annualRate / 100 / 12;
        $monthlyPrincipal = $P / $months;
        $firstMonthly = $monthlyPrincipal + $P * $r;
        $totalInterest = 0;
        for ($i = 0; $i < $months; $i++) {
            $remaining = $P - $monthlyPrincipal * $i;
            $totalInterest += $remaining * $r;
        }
        return ['monthly' => round($firstMonthly, 2), 'total_interest' => round($totalInterest, 2), 'total' => round($P + $totalInterest, 2)];
    }
}
