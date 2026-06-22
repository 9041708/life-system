<?php
namespace App\Model;
use App\Service\Database; use PDO;
class SalaryActual {
    public static function get(int $uid, string $month): ?array {
        $s=Database::getConnection()->prepare('SELECT * FROM salary_actual WHERE user_id=:u AND salary_month=:m');
        $s->execute([':u'=>$uid,':m'=>$month]);return $s->fetch(PDO::FETCH_ASSOC)?:null;
    }
    public static function save(int $uid, string $month, float $amt, string $note=''): void {
        Database::getConnection()->prepare('INSERT INTO salary_actual (user_id,salary_month,actual_amount,note) VALUES (:u,:m,:a,:n) ON DUPLICATE KEY UPDATE actual_amount=:a,note=:n')
            ->execute([':u'=>$uid,':m'=>$month,':a'=>$amt,':n'=>$note]);
    }
}
