<?php namespace App\Model;
use App\Service\Database; use PDO;
class EntPosition {
    public static function get(int $uid, int $sid): ?array {
        $s=Database::getConnection()->prepare('SELECT * FROM ent_positions WHERE user_id=:u AND stock_id=:s');
        $s->execute([':u'=>$uid,':s'=>$sid]);return $s->fetch(PDO::FETCH_ASSOC)?:null;
    }
    public static function allByUser(int $uid): array {
        $s=Database::getConnection()->prepare('SELECT p.*,s.name,s.symbol,s.current_price FROM ent_positions p JOIN ent_stocks s ON s.id=p.stock_id WHERE p.user_id=:u AND p.quantity>0');
        $s->execute([':u'=>$uid]);return $s->fetchAll(PDO::FETCH_ASSOC)?:[];
    }
    public static function buy(int $uid, int $sid, int $qty, float $price): void {
        $pdo=Database::getConnection();$ex=self::get($uid,$sid);
        if($ex){$nq=(int)$ex['quantity']+$qty;$nc=((float)$ex['avg_cost']*(int)$ex['quantity']+$price*$qty)/$nq;
            $pdo->prepare('UPDATE ent_positions SET quantity=:q,avg_cost=:c WHERE user_id=:u AND stock_id=:s')->execute([':q'=>$nq,':c'=>round($nc,4),':u'=>$uid,':s'=>$sid]);}
        else{$pdo->prepare('INSERT INTO ent_positions (user_id,stock_id,quantity,avg_cost) VALUES (:u,:s,:q,:c)')->execute([':u'=>$uid,':s'=>$sid,':q'=>$qty,':c'=>$price]);}
    }
    public static function sell(int $uid, int $sid, int $qty): void {
        $pdo=Database::getConnection();$ex=self::get($uid,$sid);
        if(!$ex||(int)$ex['quantity']<$qty)return;$nq=(int)$ex['quantity']-$qty;
        if($nq<=0)$pdo->prepare('DELETE FROM ent_positions WHERE user_id=:u AND stock_id=:s')->execute([':u'=>$uid,':s'=>$sid]);
        else $pdo->prepare('UPDATE ent_positions SET quantity=:q WHERE user_id=:u AND stock_id=:s')->execute([':q'=>$nq,':u'=>$uid,':s'=>$sid]);
    }
}