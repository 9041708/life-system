<?php
namespace App\Model;
use App\Service\Database; use PDO;
class AttRecord {
    public static function set(int $uid, string $date, int $sid, string $status, string $note=''): void {
        $pdo=Database::getConnection();
        $pdo->prepare('INSERT INTO att_records (user_id,record_date,shift_id,status,note) VALUES (:u,:d,:s,:st,:n) ON DUPLICATE KEY UPDATE shift_id=:s,status=:st,note=:n')
            ->execute([':u'=>$uid,':d'=>$date,':s'=>$sid,':st'=>$status,':n'=>$note]);
    }
    public static function getMonth(int $uid, string $ym): array {
        $s=Database::getConnection()->prepare("SELECT * FROM att_records WHERE user_id=:u AND DATE_FORMAT(record_date,'%Y-%m')=:ym ORDER BY record_date");
        $s->execute([':u'=>$uid,':ym'=>$ym]);$r=[];while($row=$s->fetch(PDO::FETCH_ASSOC))$r[$row['record_date']]=$row;return $r;
    }
    public static function stats(int $uid, string $ym): array {
        $s=Database::getConnection()->prepare("SELECT status,COUNT(*) as cnt FROM att_records WHERE user_id=:u AND DATE_FORMAT(record_date,'%Y-%m')=:ym GROUP BY status");
        $s->execute([':u'=>$uid,':ym'=>$ym]);$r=['present'=>0,'absent'=>0,'late'=>0,'leave'=>0,'rest'=>0];while($row=$s->fetch())$r[$row['status']]=(int)$row['cnt'];
        $days=cal_days_in_month(CAL_GREGORIAN,(int)substr($ym,5,2),(int)substr($ym,0,4));$r['total']=$days;$r['worked']=$r['present']+$r['late'];$r['should']=$r['total']-4*(int)ceil($days/7);
        return $r;
    }
}
