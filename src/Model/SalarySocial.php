<?php
namespace App\Model;
use App\Service\Database; use PDO;
class SalarySocial {
    public static function byUser(int $uid): array {
        $s=Database::getConnection()->prepare('SELECT * FROM salary_social WHERE user_id=:u ORDER BY start_date DESC');
        $s->execute([':u'=>$uid]);return $s->fetchAll(PDO::FETCH_ASSOC)?:[];
    }
    public static function add(int $uid, float $social, float $fund, string $start, ?string $end): void {
        Database::getConnection()->prepare('INSERT INTO salary_social (user_id,social_amount,fund_amount,start_date,end_date) VALUES (:u,:s,:f,:sd,:ed)')
            ->execute([':u'=>$uid,':s'=>$social,':f'=>$fund,':sd'=>$start,':ed'=>$end]);
    }
    public static function delete(int $id, int $uid): void {
        Database::getConnection()->prepare('DELETE FROM salary_social WHERE id=:i AND user_id=:u')->execute([':i'=>$id,':u'=>$uid]);
    }
}
