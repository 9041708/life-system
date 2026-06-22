<?php namespace App\Model;
use App\Service\Database; use PDO;
class EntNews {
    public static function create(int $sid, string $title, string $content, string $effect, int $strength, int $hours, ?string $scheduledAt=null): void {
        Database::getConnection()->prepare('INSERT INTO ent_news (stock_id,title,content,effect,strength,expire_hours,scheduled_at) VALUES (:s,:t,:c,:e,:st,:h,:sa)')
            ->execute([':s'=>$sid,':t'=>$title,':c'=>$content,':e'=>$effect,':st'=>$strength,':h'=>$hours,':sa'=>$scheduledAt]);
    }
    public static function all(): array {
        return Database::getConnection()->query('SELECT n.*,s.name,s.symbol FROM ent_news n JOIN ent_stocks s ON s.id=n.stock_id ORDER BY n.created_at DESC LIMIT 30')->fetchAll(PDO::FETCH_ASSOC)?:[];
    }
    public static function delete(int $id): void { Database::getConnection()->prepare('DELETE FROM ent_news WHERE id=:i')->execute([':i'=>$id]); }
}
