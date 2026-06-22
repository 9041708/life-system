<?php
namespace App\Model;
use App\Service\Database; use PDO;
class SalaryConfig {
    public static function get(int $uid): ?array {
        $s=Database::getConnection()->prepare('SELECT * FROM salary_configs WHERE user_id=:u AND effective_from<=CURDATE() ORDER BY effective_from DESC LIMIT 1');
        $s->execute([':u'=>$uid]);return $s->fetch(PDO::FETCH_ASSOC)?:null;
    }
    public static function save(int $uid, array $d): void {
        Database::getConnection()->prepare('INSERT INTO salary_configs (user_id,base_salary,subsidy,effective_from) VALUES (:u,:b,:s,:e)')
            ->execute([':u'=>$uid,':b'=>$d['base'],':s'=>$d['sub'],':e'=>$d['date']]);
    }
}
