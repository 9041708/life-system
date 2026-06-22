<?php namespace App\Model;
use App\Service\Database; use PDO;
class EntTrade {
    public static function log(int $uid, int $sid, string $type, float $price, int $qty, float $fee, float $total): void {
        Database::getConnection()->prepare('INSERT INTO ent_trades (user_id,stock_id,type,price,quantity,fee,total_amount) VALUES (:u,:s,:t,:p,:q,:f,:a)')
            ->execute([':u'=>$uid,':s'=>$sid,':t'=>$type,':p'=>$price,':q'=>$qty,':f'=>$fee,':a'=>$total]);
    }
    public static function byUser(int $uid, int $limit=20, int $offset=0): array {
        $s=Database::getConnection()->prepare('SELECT t.*,s.name,s.symbol FROM ent_trades t JOIN ent_stocks s ON s.id=t.stock_id WHERE t.user_id=:u ORDER BY t.created_at DESC LIMIT :l OFFSET :o');
        $s->bindValue(':u',$uid,PDO::PARAM_INT);$s->bindValue(':l',$limit,PDO::PARAM_INT);$s->bindValue(':o',$offset,PDO::PARAM_INT);$s->execute();
        return $s->fetchAll(PDO::FETCH_ASSOC)?:[];
    }
    public static function countByUser(int $uid): array {
        $s=Database::getConnection()->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN type='sell' THEN 1 ELSE 0 END) as win FROM ent_trades WHERE user_id=:u");
        $s->execute([':u'=>$uid]);return $s->fetch(PDO::FETCH_ASSOC)?:['total'=>0,'win'=>0];
    }
}