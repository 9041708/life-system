<?php namespace App\Model;
use App\Service\Database; use PDO;
class EntConfig {
    public static function get(string $key, string $default=''): string {
        $s=Database::getConnection()->prepare('SELECT config_value FROM ent_config WHERE config_key=:k');
        $s->execute([':k'=>$key]);return $s->fetchColumn()?:$default;
    }
    public static function set(string $key, string $val): void {
        Database::getConnection()->prepare('REPLACE INTO ent_config (config_key,config_value) VALUES (:k,:v)')->execute([':k'=>$key,':v'=>$val]);
    }
}
