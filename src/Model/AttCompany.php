<?php
namespace App\Model;
use App\Service\Database; use PDO;
class AttCompany {
    public static function getActive(int $uid): ?array {
        $s=Database::getConnection()->prepare('SELECT * FROM att_company WHERE user_id=:u AND left_date IS NULL ORDER BY join_date DESC LIMIT 1');
        $s->execute([':u'=>$uid]);return $s->fetch(PDO::FETCH_ASSOC)?:null;
    }
    public static function allByUser(int $uid): array {
        $s=Database::getConnection()->prepare('SELECT * FROM att_company WHERE user_id=:u ORDER BY join_date DESC');
        $s->execute([':u'=>$uid]);return $s->fetchAll(PDO::FETCH_ASSOC)?:[];
    }
    public static function join(int $uid, string $name, string $date): void {
        Database::getConnection()->prepare('INSERT INTO att_company (user_id,company_name,join_date) VALUES (:u,:n,:d)')->execute([':u'=>$uid,':n'=>$name,':d'=>$date]);
    }
    public static function leave(int $uid, string $date): void {
        Database::getConnection()->prepare('UPDATE att_company SET left_date=:d WHERE user_id=:u AND left_date IS NULL')->execute([':d'=>$date,':u'=>$uid]);
    }
}
