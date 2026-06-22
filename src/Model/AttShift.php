<?php
namespace App\Model;
use App\Service\Database; use PDO;
class AttShift {
    public static function byUser(int $uid): array {
        $s=Database::getConnection()->prepare('SELECT * FROM att_shifts WHERE user_id=:u OR user_id=0 ORDER BY is_rest, id');
        $s->execute([':u'=>$uid]); return $s->fetchAll(PDO::FETCH_ASSOC)?:[];
    }
    public static function create(int $uid, array $d): void {
        Database::getConnection()->prepare('INSERT INTO att_shifts (user_id,name,start_time,end_time) VALUES (:u,:n,:s,:e)')->execute([':u'=>$uid,':n'=>$d['name'],':s'=>$d['start'],':e'=>$d['end']]);
    }
    public static function delete(int $id, int $uid): void {
        Database::getConnection()->prepare('DELETE FROM att_shifts WHERE id=:i AND user_id=:u')->execute([':i'=>$id,':u'=>$uid]);
    }
}
