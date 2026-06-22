<?php
namespace App\Model;
use App\Service\Database; use PDO;
class SalaryDeduction {
    public static function byUser(int $uid): array {
        $s=Database::getConnection()->prepare('SELECT * FROM salary_deductions WHERE user_id=:u ORDER BY deduction_month DESC, created_at DESC');
        $s->execute([':u'=>$uid]);return $s->fetchAll(PDO::FETCH_ASSOC)?:[];
    }
    public static function add(int $uid, string $month, float $amt, string $detail): void {
        Database::getConnection()->prepare('INSERT INTO salary_deductions (user_id,deduction_month,amount,detail) VALUES (:u,:m,:a,:d)')->execute([':u'=>$uid,':m'=>$month,':a'=>$amt,':d'=>$detail]);
    }
    public static function delete(int $id, int $uid): void {
        Database::getConnection()->prepare('DELETE FROM salary_deductions WHERE id=:i AND user_id=:u')->execute([':i'=>$id,':u'=>$uid]);
    }
}
