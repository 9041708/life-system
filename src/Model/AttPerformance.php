<?php
namespace App\Model;
use App\Service\Database; use PDO;
class AttPerformance {
    public static function get(int $uid, string $ym): ?array {
        $s=Database::getConnection()->prepare('SELECT * FROM att_performance WHERE user_id=:u AND month=:ym');
        $s->execute([':u'=>$uid,':ym'=>$ym]); return $s->fetch(PDO::FETCH_ASSOC)?:null;
    }
    public static function save(int $uid, string $month, float $sales, float $rate, float $bonus, string $metrics=''): void {
        $perf=$sales*$rate+$bonus;
        Database::getConnection()->prepare('INSERT INTO att_performance (user_id,month,sales_amount,commission_rate,bonus,performance,other_metrics) VALUES (:u,:m,:s,:r,:b,:p,:mt) ON DUPLICATE KEY UPDATE sales_amount=:s,commission_rate=:r,bonus=:b,performance=:p,other_metrics=:mt')->execute([':u'=>$uid,':m'=>$month,':s'=>$sales,':r'=>$rate,':b'=>$bonus,':p'=>$perf,':mt'=>$metrics]);
    }
}
