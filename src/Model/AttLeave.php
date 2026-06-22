<?php
namespace App\Model;
use App\Service\Database; use PDO;
class AttLeave {
    public static function getMonth(int $uid, string $ym): array {
        $s=Database::getConnection()->prepare('SELECT * FROM att_leaves WHERE user_id=:u AND DATE_FORMAT(leave_date,"%Y-%m")=:ym ORDER BY leave_date');
        $s->execute([':u'=>$uid,':ym'=>$ym]); return $s->fetchAll(PDO::FETCH_ASSOC)?:[];
    }
    public static function add(int $uid, string $date, float $hours, string $note=''): void {
        Database::getConnection()->prepare('INSERT INTO att_leaves (user_id,leave_date,hours,note) VALUES (:u,:d,:h,:n) ON DUPLICATE KEY UPDATE hours=:h,note=:n')->execute([':u'=>$uid,':d'=>$date,':h'=>$hours,':n'=>$note]);
    }
    public static function delete(int $uid, string $date): void {
        Database::getConnection()->prepare('DELETE FROM att_leaves WHERE user_id=:u AND leave_date=:d')->execute([':u'=>$uid,':d'=>$date]);
    }
}
