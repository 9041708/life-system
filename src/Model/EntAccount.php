<?php namespace App\Model;
use App\Service\Database; use PDO;
class EntAccount {
    public static function get(int $uid): array {
        $pdo=Database::getConnection();$s=$pdo->prepare('SELECT * FROM ent_accounts WHERE user_id=:u');
        $s->execute([':u'=>$uid]);$r=$s->fetch(PDO::FETCH_ASSOC);
        if($r)return $r;
        $pdo->prepare('INSERT INTO ent_accounts (user_id) VALUES (:u)')->execute([':u'=>$uid]);
        $s->execute([':u'=>$uid]);return $s->fetch(PDO::FETCH_ASSOC);
    }
    public static function updateBalance(int $uid, float $bal): void {
        Database::getConnection()->prepare('UPDATE ent_accounts SET balance=:b WHERE user_id=:u')->execute([':b'=>$bal,':u'=>$uid]);
    }
    public static function loan(int $uid, float $amount): void {
        Database::getConnection()->prepare('UPDATE ent_accounts SET balance=balance+:a,loan_amount=loan_amount+:a,loan_count=loan_count+1 WHERE user_id=:u')->execute([':a'=>$amount,':u'=>$uid]);
    }
}
