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
    public static function find(int $id) {
        $s = Database::getConnection()->prepare('SELECT * FROM ent_stocks WHERE id=:i');
        $s->execute([':i'=>$id]); return $s->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public static function refreshPrices(): void {
        $pdo = Database::getConnection();

        // 保障新字段存在（一次性的迁移逻辑）
        $cols = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ent_news' AND COLUMN_NAME IN ('biased','bias_strength')")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $hasBias = array_column($cols, 'COLUMN_NAME');
        if (!in_array('biased', $hasBias)) {
            $pdo->exec("ALTER TABLE ent_news ADD COLUMN biased TINYINT(1) DEFAULT 0 COMMENT '是否已施加过一次初始bias'");
        }
        if (!in_array('bias_strength', $hasBias)) {
            $pdo->exec("ALTER TABLE ent_news ADD COLUMN bias_strength DECIMAL(5,2) DEFAULT 0 COMMENT '剩余bias强度，随刷新衰减'");
        }
        $cols2 = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ent_stocks' AND COLUMN_NAME='day_open_price'")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (empty($cols2)) {
            $pdo->exec("ALTER TABLE ent_stocks ADD COLUMN day_open_price DECIMAL(10,2) DEFAULT NULL COMMENT '今日开盘价（涨跌停基准）'");
            $pdo->exec("UPDATE ent_stocks SET day_open_price = current_price");
        }

        $stocks = self::all();
        $isTradeTime = self::isTradingTime();

        // 一次性修复：所有超过base_price×1.3的股价拉回涨停价
        $pdo->exec("UPDATE ent_stocks SET current_price = ROUND(base_price * 1.3, 2) WHERE current_price > base_price * 1.3 AND base_price > 0");
        $pdo->exec("UPDATE ent_stocks SET current_price = ROUND(base_price * 0.7, 2) WHERE current_price < base_price * 0.7 AND current_price > 0 AND base_price > 0");

        foreach ($stocks as $s) {
            $curr = (float)$s['current_price'];
            $base = (float)$s['base_price'];
            if ($curr <= 0) {
                $curr = $base > 0 ? $base : 100.00;
                $pdo->prepare('UPDATE ent_stocks SET current_price=:p WHERE id=:i')->execute([':p'=>$curr, ':i'=>$s['id']]);
            }
            if ($base <= 0) $base = 100.00;

            // 涨跌停：基于 base_price ±30%
            $limitPct = 0.30;
            $ceil = round($base * (1 + $limitPct), 2);
            $floor = round($base * (1 - $limitPct), 2);

            // 如果当前价已严重偏离base_price，强制拉回涨停价
            if ($curr > $base * 1.3 || ($curr > 0 && $curr < $floor * 0.5)) {
                $curr = $ceil;
                $pdo->prepare('UPDATE ent_stocks SET current_price=:p WHERE id=:i')->execute([':p'=>$curr, ':i'=>$s['id']]);
            }

            // 获取生效中的新闻
            // 获取生效中的新闻（biased/bias_strength字段可能不存在旧数据中，用@抑制undefined警告）
            $ns = $pdo->prepare("SELECT id, effect, strength, biased, bias_strength FROM ent_news WHERE stock_id=:sid AND is_active=1 AND (scheduled_at IS NULL OR scheduled_at <= NOW()) AND DATE_ADD(created_at, INTERVAL expire_hours HOUR) > NOW()");
            $ns->execute([':sid'=>$s['id']]);
            $totalBias = 0;
            $toUpdate = [];
            while ($n = $ns->fetch(PDO::FETCH_ASSOC)) {
                // 每天首次施加初始bias（strength × 50），后续每次刷新 ×0.95（衰减更慢）
                $remains = ((float)($n['bias_strength'] ?? 0)) * 0.95;
                if ((int)($n['biased'] ?? 0) === 0) {
                    $remains += ((int)$n['strength']) * 50.0;
                    $toUpdate[] = ['id'=>(int)$n['id'], 'biased'=>1, 'bias_strength'=>round($remains, 2)];
                } else {
                    $toUpdate[] = ['id'=>(int)$n['id'], 'bias_strength'=>round(max(0, $remains), 2)];
                }
                $totalBias += ($n['effect']==='positive' ? 1 : -1) * $remains;
            }
            // 写入新闻衰减结果
            foreach ($toUpdate as $u) {
                $pdo->prepare("UPDATE ent_news SET biased=:b, bias_strength=:bs WHERE id=:i")->execute([':b'=>$u['biased'], ':bs'=>$u['bias_strength'], ':i'=>$u['id']]);
            }

            // 随机波动：交易时段 ±0.3%，休市 ±0.1%
            $randRange = $isTradeTime ? 30 : 10;
            $randomBase = rand(-$randRange, $randRange) / 10000;

            // 交易供需压力：最近5分钟内净买入量影响价格
            $tradePressure = 0;
            if ($isTradeTime) {
                try {
                    $t5 = $pdo->query("SELECT COALESCE(SUM(CASE WHEN type='buy' THEN quantity ELSE -quantity END), 0) AS net FROM ent_trades WHERE stock_id={$s['id']} AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetchColumn();
                    $t5 = (int)$t5;
                    $tradePressure = $t5 / 100 * 0.0005;
                } catch (\Throwable $e) {}
            }

            $pct = $randomBase + $totalBias / 50 + $tradePressure;
            $newPrice = round(max($floor, min($ceil, $curr * (1 + $pct))), 2);

            if ($isTradeTime) self::matchOrders($s['id'], $newPrice);
            $upd = $pdo->prepare('UPDATE ent_stocks SET current_price=:p WHERE id=:i');
            $upd->execute([':p'=>$newPrice, ':i'=>$s['id']]);
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
        return $t>=480;
    }
    public static function update(int $id, array $data): void {
        $pdo = Database::getConnection();
        $fields = ['name','sector','description','listed_date','ipo_price','base_price','current_price','is_active'];
        $sets = []; $vals = [':i'=>$id];
        foreach ($fields as $f) { if (isset($data[$f])) { $sets[] = "$f=:$f"; $vals[":$f"] = $data[$f]; } }
        if (!empty($sets)) $pdo->prepare('UPDATE ent_stocks SET '.implode(',',$sets).' WHERE id=:i')->execute($vals);
    }
}
