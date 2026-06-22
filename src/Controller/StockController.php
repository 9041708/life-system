<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\StockApi;
use App\Model\StockWatchlist;
use App\Model\StockAccount;
use App\Model\StockPosition;
use App\Model\StockTrade;

class StockController
{
    private static function ensureTables(): void
    {
        try {
            $pdo = \App\Service\Database::getConnection();
            $pdo->exec("CREATE TABLE IF NOT EXISTS stock_watchlist (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                symbol VARCHAR(20) NOT NULL,
                name VARCHAR(50) NOT NULL,
                market VARCHAR(10) DEFAULT 'A',
                sort_order INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_user_symbol (user_id, symbol),
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS stock_accounts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL UNIQUE,
                initial_balance DECIMAL(14,2) DEFAULT 1000000.00,
                balance DECIMAL(14,2) DEFAULT 1000000.00,
                commission_rate DECIMAL(6,4) DEFAULT 0.0003,
                stamp_tax_rate DECIMAL(6,4) DEFAULT 0.001,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS stock_positions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                symbol VARCHAR(20) NOT NULL,
                name VARCHAR(50) NOT NULL,
                quantity INT NOT NULL,
                avg_cost DECIMAL(10,4) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_user_symbol (user_id, symbol),
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS stock_trades (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                symbol VARCHAR(20) NOT NULL,
                name VARCHAR(50) NOT NULL,
                type ENUM('buy','sell') NOT NULL,
                price DECIMAL(10,4) NOT NULL,
                quantity INT NOT NULL,
                commission DECIMAL(10,2) DEFAULT 0,
                stamp_tax DECIMAL(10,2) DEFAULT 0,
                total_amount DECIMAL(14,2) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {}
    }

    private function requireLogin(): int
    {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) { header('Location: /public/index.php?route=login'); exit; }
        return $uid;
    }

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        $_SESSION['current_page_title'] = $params['pageTitle'] ?? '股票';
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function json(array $data): void
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function viewer(): void
    {
        $userId = $this->requireLogin();
        self::ensureTables();
        $watchlist = StockWatchlist::listByUser($userId);
        $symbols = array_map(function($w) { return $w['symbol']; }, $watchlist);
        $quotes = [];
        if (!empty($symbols)) {
            $quotes = StockApi::getQuotes($symbols);
        }
        $quoteMap = [];
        foreach ($quotes as $q) {
            $quoteMap[$q['symbol']] = $q;
            // 如果数据库里没有名称，补齐
            if (!empty($q['name'])) {
                foreach ($watchlist as &$w) {
                    if ($w['symbol'] === $q['symbol'] && (empty($w['name']) || $w['name'] === $w['symbol'])) {
                        $w['name'] = $q['name'];
                    }
                }
                unset($w);
            }
        }
        $this->render('stock/viewer', [
            'pageTitle' => '查看股票',
            'watchlist' => $watchlist,
            'quoteMap' => $quoteMap,
        ]);
    }

    public function simulator(): void
    {
        $userId = $this->requireLogin();
        self::ensureTables();
        $account = StockAccount::getOrCreate($userId);
        $positions = StockPosition::listByUser($userId);
        $symbols = array_map(function($p) { return $p['symbol']; }, $positions);
        $quotes = [];
        if (!empty($symbols)) {
            $quotes = StockApi::getQuotes($symbols);
        }
        $quoteMap = [];
        foreach ($quotes as $q) $quoteMap[$q['symbol']] = $q;
        $trades = StockTrade::listByUser($userId, 30);
        $this->render('stock/simulator', [
            'pageTitle' => '模拟股票',
            'account' => $account,
            'positions' => $positions,
            'quoteMap' => $quoteMap,
            'trades' => $trades,
        ]);
    }

    public function api(): void
    {
        $userId = $this->requireLogin();
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        try {
            ob_start();
            switch ($action) {
                case 'search': $this->apiSearch(); break;
                case 'quote': $this->apiQuote(); break;
                case 'kline': $this->apiKline(); break;
                case 'add_watch': $this->addWatch($userId); break;
                case 'remove_watch': $this->removeWatch($userId); break;
                case 'buy': $this->buyStock($userId); break;
                case 'sell': $this->sellStock($userId); break;
                case 'reset_account': $this->resetAccount($userId); break;
                default: $this->json(['ok' => false, 'error' => '未知操作']);
            }
            ob_get_clean();
        } catch (\Throwable $e) {
            if (ob_get_level()) ob_end_clean();
            $this->json(['ok' => false, 'error' => '操作异常: ' . $e->getMessage()]);
        }
    }

    private function apiSearch(): void
    {
        $q = trim($_GET['q'] ?? $_POST['q'] ?? '');
        if ($q === '') $this->json(['ok' => true, 'results' => []]);
        $results = StockApi::search($q);
        $this->json(['ok' => true, 'results' => $results]);
    }

    private function apiQuote(): void
    {
        $symbol = trim($_GET['symbol'] ?? $_POST['symbol'] ?? '');
        if ($symbol === '') $this->json(['ok' => false, 'error' => '请输入股票代码']);
        $quote = StockApi::getQuote($symbol);
        if (!$quote) $this->json(['ok' => false, 'error' => '获取行情失败']);
        $this->json(['ok' => true, 'quote' => $quote]);
    }

    private function apiKline(): void
    {
        $symbol = trim($_GET['symbol'] ?? $_POST['symbol'] ?? '');
        $scale = trim($_GET['scale'] ?? '240');
        $count = min(500, max(20, (int)($_GET['count'] ?? 120)));
        $data = StockApi::getKline($symbol, $scale, $count);
        $this->json(['ok' => true, 'data' => $data]);
    }

    private function addWatch(int $userId): void
    {
        $symbol = trim($_POST['symbol'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $market = trim($_POST['market'] ?? 'A');
        if ($symbol === '' && $name === '') $this->json(['ok' => false, 'error' => '股票代码不能为空']);
        StockWatchlist::add($userId, $symbol, $name, $market);
        $this->json(['ok' => true, 'message' => '已添加自选']);
    }

    private function removeWatch(int $userId): void
    {
        $symbol = trim($_POST['symbol'] ?? '');
        StockWatchlist::remove($userId, $symbol);
        $this->json(['ok' => true, 'message' => '已移除自选']);
    }

    private function buyStock(int $userId): void
    {
        $symbol = trim($_POST['symbol'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        if ($symbol === '' || $price <= 0 || $quantity <= 0) $this->json(['ok' => false, 'error' => '请输入有效信息']);
        if ($quantity % 100 !== 0) $this->json(['ok' => false, 'error' => '买入数量必须为100的整数倍']);

        $account = StockAccount::getOrCreate($userId);
        $amount = $price * $quantity;
        $commission = max(5, $amount * (float)$account['commission_rate']);
        $total = $amount + $commission;

        if ($total > (float)$account['balance']) {
            $this->json(['ok' => false, 'error' => '可用资金不足，需要 ¥' . number_format($total, 2)]);
        }

        StockPosition::buy($userId, $symbol, $name, $quantity, $price);
        StockAccount::updateBalance($userId, round((float)$account['balance'] - $total, 2));
        StockTrade::create($userId, [
            'symbol' => $symbol, 'name' => $name, 'type' => 'buy',
            'price' => $price, 'quantity' => $quantity,
            'commission' => round($commission, 2), 'stamp_tax' => 0, 'total_amount' => round($total, 2),
        ]);
        $this->json(['ok' => true, 'message' => '买入成功 ' . $name . ' ' . $quantity . '股 @ ¥' . $price]);
    }

    private function sellStock(int $userId): void
    {
        $symbol = trim($_POST['symbol'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        if ($symbol === '' || $price <= 0 || $quantity <= 0) $this->json(['ok' => false, 'error' => '请输入有效信息']);
        if ($quantity % 100 !== 0) $this->json(['ok' => false, 'error' => '卖出数量必须为100的整数倍']);

        $position = StockPosition::get($userId, $symbol);
        if (!$position || (int)$position['quantity'] < $quantity) {
            $this->json(['ok' => false, 'error' => '持仓不足']);
        }

        $account = StockAccount::getOrCreate($userId);
        $amount = $price * $quantity;
        $commission = max(5, $amount * (float)$account['commission_rate']);
        $stampTax = $amount * (float)$account['stamp_tax_rate'];
        $total = $amount - $commission - $stampTax;

        StockPosition::sell($userId, $symbol, $quantity);
        StockAccount::updateBalance($userId, round((float)$account['balance'] + $total, 2));
        StockTrade::create($userId, [
            'symbol' => $symbol, 'name' => $name, 'type' => 'sell',
            'price' => $price, 'quantity' => $quantity,
            'commission' => round($commission, 2), 'stamp_tax' => round($stampTax, 2), 'total_amount' => round($total, 2),
        ]);
        $profit = ($price - (float)$position['avg_cost']) * $quantity - $commission - $stampTax;
        $this->json(['ok' => true, 'message' => '卖出成功 ' . $name . ' ' . $quantity . '股 @ ¥' . $price . '，盈亏 ¥' . number_format($profit, 2)]);
    }

    private function resetAccount(int $userId): void
    {
        $balance = (float)($_POST['balance'] ?? 1000000);
        StockAccount::resetAccount($userId, $balance);
        $this->json(['ok' => true, 'message' => '账户已重置，初始资金 ¥' . number_format($balance, 2)]);
    }
}
