<?php namespace App\Model;
use App\Service\Database; use PDO;
class EntOrder {
    public static function create(int $uid, int $sid, string $type, float $price, int $qty): int {
        $s=Database::getConnection()->prepare('INSERT INTO ent_orders (user_id,stock_id,type,price,quantity) VALUES (:u,:s,:t,:p,:q)');
        $s->execute([':u'=>$uid,':s'=>$sid,':t'=>$type,':p'=>$price,':q'=>$qty]);
        return (int)Database::getConnection()->lastInsertId();
    }
    public static function byUser(int $uid): array {
        $s=Database::getConnection()->prepare("SELECT o.*,s.name,s.symbol,s.current_price FROM ent_orders o JOIN ent_stocks s ON s.id=o.stock_id WHERE o.user_id=:u ORDER BY o.created_at DESC LIMIT 50");
        $s->execute([':u'=>$uid]); return $s->fetchAll(PDO::FETCH_ASSOC)?:[];
    }
    public static function cancel(int $oid, int $uid): void {
        Database::getConnection()->prepare("UPDATE ent_orders SET status='cancelled' WHERE id=:i AND user_id=:u AND status='pending'")->execute([':i'=>$oid,':u'=>$uid]);
    }
    public static function countPending(int $uid): int {
        $s=Database::getConnection()->prepare("SELECT COUNT(*) FROM ent_orders WHERE user_id=:u AND status='pending'");
        $s->execute([':u'=>$uid]); return (int)$s->fetchColumn();
    }
}
