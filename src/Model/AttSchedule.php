<?php
namespace App\Model;
use App\Service\Database; use PDO;
class AttSchedule {
    public static function get(int $uid, string $ym): array {
        $s=Database::getConnection()->prepare("SELECT s.*,sh.name,sh.start_time,sh.end_time FROM att_schedule s LEFT JOIN att_shifts sh ON sh.id=s.shift_id WHERE s.user_id=:u AND DATE_FORMAT(schedule_date,'%Y-%m')=:ym");
        $s->execute([':u'=>$uid,':ym'=>$ym]);$r=[];while($row=$s->fetch(PDO::FETCH_ASSOC))$r[$row['schedule_date']]=$row;return $r;
    }
    public static function set(int $uid, string $date, int $shiftId): void {
        $pdo=Database::getConnection();
        $pdo->prepare('INSERT INTO att_schedule (user_id,schedule_date,shift_id) VALUES (:u,:d,:s) ON DUPLICATE KEY UPDATE shift_id=:s')
            ->execute([':u'=>$uid,':d'=>$date,':s'=>$shiftId]);
    }
    public static function getShiftForDate(int $uid, string $date): ?int {
        $s=Database::getConnection()->prepare('SELECT shift_id FROM att_schedule WHERE user_id=:u AND schedule_date=:d');
        $s->execute([':u'=>$uid,':d'=>$date]);$r=$s->fetch(PDO::FETCH_ASSOC);
        return $r? (int)$r['shift_id']:null;
    }
}
