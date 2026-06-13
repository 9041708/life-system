<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\RealEstateScraper;
use App\Model\Property;
use App\Model\PropertyPrice;
use App\Model\MortgageCalc;

class PropertyController
{
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
        $_SESSION['current_page_title'] = $params['pageTitle'] ?? '房产';
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function json(array $data): void
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function watch(): void
    {
        $userId = $this->requireLogin();
        $status = $_GET['status'] ?? 'all';
        $properties = Property::listByUser($userId, $status);
        foreach ($properties as &$p) {
            $p['price_history'] = PropertyPrice::getHistory((int)$p['id'], $userId, 30);
        }
        unset($p);
        $this->render('property/watch', ['pageTitle' => '房产关注', 'properties' => $properties, 'currentStatus' => $status]);
    }

    public function search(): void
    {
        $userId = $this->requireLogin();
        $city = trim($_GET['city'] ?? '北京');
        $keyword = trim($_GET['q'] ?? '');
        $type = $_GET['type'] ?? 'sale';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $results = ['ok' => false, 'items' => [], 'total' => 0];
        if ($keyword !== '') {
            if ($type === 'rent') {
                $results = RealEstateScraper::searchRent($city, $keyword, $page);
            } else {
                $results = RealEstateScraper::searchSale($city, $keyword, $page);
            }
        }
        $this->render('property/search', ['pageTitle' => '房产查询', 'city' => $city, 'keyword' => $keyword, 'type' => $type, 'page' => $page, 'results' => $results]);
    }

    public function mortgage(): void
    {
        $userId = $this->requireLogin();
        $history = MortgageCalc::listByUser($userId);
        $this->render('property/mortgage', ['pageTitle' => '贷款计算', 'history' => $history]);
    }

    public function api(): void
    {
        $userId = $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->json(['ok' => false, 'error' => '无效请求']); }
        $action = $_POST['action'] ?? '';
        try {
            ob_start();
            switch ($action) {
                case 'add_property': $this->addProperty($userId); break;
                case 'update_property': $this->updateProperty($userId); break;
                case 'delete_property': $this->deleteProperty($userId); break;
                case 'update_price': $this->updatePrice($userId); break;
                case 'calc_mortgage': $this->calcMortgage($userId); break;
                case 'delete_calc': $this->deleteCalc($userId); break;
                case 'clear_calcs': $this->clearCalcs($userId); break;
                default: $this->json(['ok' => false, 'error' => '未知操作']);
            }
            ob_get_clean();
        } catch (\Throwable $e) {
            if (ob_get_level()) ob_end_clean();
            $this->json(['ok' => false, 'error' => '操作异常: ' . $e->getMessage()]);
        }
    }

    private function addProperty(int $userId): void
    {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') $this->json(['ok' => false, 'error' => '请输入小区名称']);
        $id = Property::create($userId, [
            'name' => $name, 'city' => trim($_POST['city'] ?? ''), 'address' => trim($_POST['address'] ?? ''),
            'area' => (float)($_POST['area'] ?? 0), 'layout' => trim($_POST['layout'] ?? ''),
            'current_price' => (float)($_POST['current_price'] ?? 0), 'unit_price' => (float)($_POST['unit_price'] ?? 0),
            'source_url' => trim($_POST['source_url'] ?? ''), 'source_id' => trim($_POST['source_id'] ?? ''),
            'note' => trim($_POST['note'] ?? ''),
        ]);
        $this->json(['ok' => true, 'id' => $id, 'message' => '已添加']);
    }

    private function updateProperty(int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $data = [];
        foreach (['name', 'city', 'address', 'layout', 'source_url', 'source_id', 'note', 'status'] as $k) {
            if (isset($_POST[$k])) $data[$k] = trim($_POST[$k]);
        }
        foreach (['area', 'current_price', 'unit_price'] as $k) {
            if (isset($_POST[$k])) $data[$k] = (float)$_POST[$k];
        }
        Property::update($id, $userId, $data);
        $this->json(['ok' => true, 'message' => '已更新']);
    }

    private function deleteProperty(int $userId): void
    {
        Property::delete((int)($_POST['id'] ?? 0), $userId);
        $this->json(['ok' => true, 'message' => '已删除']);
    }

    private function updatePrice(int $userId): void
    {
        $id = (int)($_POST['property_id'] ?? 0);
        $prop = Property::findById($id, $userId);
        if (!$prop) $this->json(['ok' => false, 'error' => '房产不存在']);
        $price = (float)($_POST['price'] ?? 0);
        $unitPrice = (float)($_POST['unit_price'] ?? 0);
        if ($price <= 0) $this->json(['ok' => false, 'error' => '请输入有效价格']);
        PropertyPrice::add($id, $userId, $price, $unitPrice, 'manual');
        Property::update($id, $userId, ['current_price' => $price, 'unit_price' => $unitPrice]);
        $this->json(['ok' => true, 'message' => '价格已更新']);
    }

    private function calcMortgage(int $userId): void
    {
        $principal = (float)($_POST['principal'] ?? 0);
        $rate = (float)($_POST['rate'] ?? 0);
        $months = (int)($_POST['months'] ?? 0);
        $method = in_array($_POST['method'] ?? '', ['equal', 'principal']) ? $_POST['method'] : 'equal';
        if ($principal <= 0 || $months <= 0) $this->json(['ok' => false, 'error' => '请输入有效的贷款信息']);
        $result = $method === 'equal' ? MortgageCalc::calcEqualPayment($principal, $rate, $months) : MortgageCalc::calcEqualPrincipal($principal, $rate, $months);
        $id = MortgageCalc::save($userId, [
            'principal' => $principal, 'rate' => $rate, 'months' => $months, 'method' => $method,
            'monthly_payment' => $result['monthly'], 'total_interest' => $result['total_interest'],
        ]);
        $this->json(['ok' => true, 'id' => $id, 'result' => $result, 'message' => '计算完成']);
    }

    private function deleteCalc(int $userId): void
    {
        MortgageCalc::delete((int)($_POST['id'] ?? 0), $userId);
        $this->json(['ok' => true, 'message' => '已删除']);
    }

    private function clearCalcs(int $userId): void
    {
        MortgageCalc::clearAll($userId);
        $this->json(['ok' => true, 'message' => '已清空']);
    }
}
