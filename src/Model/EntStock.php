<?php
namespace App\Model;
use App\Service\Database; use PDO;

class EntStock
{
    public static function all(): array {
        return Database::getConnection()->query('SELECT * FROM ent_stocks WHERE is_active=1 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    public static function allAdmin(): array {
        return Database::getConnection()->query('SELECT * FROM ent_stocks ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    public static function find(int $id): ?array {
        $s = Database::getConnection()->prepare('SELECT * FROM ent_stocks WHERE id=:i');
        $s->execute([':i'=>$id]); return $s->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public static function refreshPrices(): void {
        $pdo = Database::getConnection(); $stocks = self::all();
        $isTradeTime = self::isTradingTime();
        foreach ($stocks as $s) {
            $base = (float)$s['base_price']; $curr = (float)$s['current_price'];
            $ns = $pdo->prepare("SELECT effect, strength FROM ent_news WHERE stock_id=:sid AND is_active=1 AND (scheduled_at IS NULL OR scheduled_at <= NOW()) AND DATE_ADD(created_at, INTERVAL expire_hours HOUR) > NOW()");
            $ns->execute([':sid'=>$s['id']]); $newsBias = 0;
            while ($n = $ns->fetch(PDO::FETCH_ASSOC)) $newsBias += ($n['effect']==='positive'?1:-1) * (int)$n['strength'] * 0.3;
            $randomBase = $isTradeTime ? rand(-500,500)/100 : rand(-100,100)/100;
            $pct = $randomBase + $newsBias;
            $newPrice = round(max($base*0.7, min($base*1.3, $curr*(1+$pct/100))), 2);
            if ($isTradeTime) self::matchOrders($s['id'], $newPrice);
            $pdo->prepare('UPDATE ent_stocks SET current_price=:p WHERE id=:i')->execute([':p'=>$newPrice, ':i'=>$s['id']]);
        }
    }
    private static function matchOrders(int $sid, float $price): void {
        $pdo = Database::getConnection();
        // 买卖撮合：买价>=卖价即可成交，以卖单价为准
        $buys = $pdo->prepare("SELECT * FROM ent_orders WHERE stock_id=:s AND type='buy' AND status='pending' AND price>=:p ORDER BY price DESC, created_at ASC");
        $buys->execute([':s'=>$sid,':p'=>$price]);
        $sells = $pdo->prepare("SELECT * FROM ent_orders WHERE stock_id=:s AND type='sell' AND status='pending' AND price<=:p ORDER BY price ASC, created_at ASC");
        $sells->execute([':s'=>$sid,':p'=>$price]);
        $sellList = $sells->fetchAll(PDO::FETCH_ASSOC)?:[];
        while ($bo = $buys->fetch(PDO::FETCH_ASSOC)) {
            foreach ($sellList as $so) {
                if ($so['status'] !== 'pending' || $so['user_id'] == $bo['user_id']) continue;
                $qty = min((int)$bo['quantity'], (int)$so['quantity']);
                if ($qty <= 0) continue;
                $dealPrice = (float)$so['price']; $amt = $dealPrice * $qty;
                $buyFee = round($amt * 0.001, 2); $buyTotal = $amt + $buyFee;
                $sellFee = round($amt * 0.002, 2); $sellTotal = $amt - $sellFee;
                $bAcc = EntAccount::get((int)$bo['user_id']);
                if ((float)$bAcc['balance'] < $buyTotal) continue;
                $sPos = EntPosition::get((int)$so['user_id'], $sid);
                if (!$sPos || (int)$sPos['quantity'] < $qty) continue;
                EntAccount::updateBalance((int)$bo['user_id'], round((float)$bAcc['balance'] - $buyTotal, 2));
                EntPosition::buy((int)$bo['user_id'], $sid, $qty, $dealPrice);
                EntAccount::updateBalance((int)$so['user_id'], round((float)EntAccount::get((int)$so['user_id'])['balance'] + $sellTotal, 2));
                EntPosition::sell((int)$so['user_id'], $sid, $qty);
                EntTrade::log((int)$bo['user_id'], $sid, 'buy', $dealPrice, $qty, $buyFee, $buyTotal);
                EntTrade::log((int)$so['user_id'], $sid, 'sell', $dealPrice, $qty, $sellFee, $sellTotal);
                $pdo->prepare("UPDATE ent_orders SET status='done' WHERE id=:i")->execute([':i'=>$bo['id']]);
                $pdo->prepare("UPDATE ent_orders SET status='done' WHERE id=:i")->execute([':i'=>$so['id']]);
                $so['status'] = 'done';
                if ((int)$bo['quantity'] <= $qty) break;
            }
        }
    }
    public static function isTradingTime(): bool {
        $h=(int)date('H');$m=(int)date('i');$t=$h*60+$m;
        return ($t>=480&&$t<720)||($t>=780&&$t<1020)||($t>=1140&&$t<1320);
    }
    public static function update(int $id, array $data): void {
        $pdo = Database::getConnection();
        $fields = ['name','sector','description','listed_date','ipo_price','base_price','current_price','is_active'];
        $sets = []; $vals = [':i'=>$id];
        foreach ($fields as $f) { if (isset($data[$f])) { $sets[] = "$f=:$f"; $vals[":$f"] = $data[$f]; } }
        if (!empty($sets)) $pdo->prepare('UPDATE ent_stocks SET '.implode(',',$sets).' WHERE id=:i')->execute($vals);
    }
}
