<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\Logger;
use App\Model\FinanceDeposit;

class FinanceController
{
    private function requireLogin(): int
    {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: /public/index.php?route=login');
            exit;
        }
        return $uid;
    }

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        $_SESSION['current_page_title'] = $params['pageTitle'] ?? '理财管理';
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function index(): void
    {
        $userId = $this->requireLogin();
        $deposits = FinanceDeposit::listByUser($userId);
        $summary = FinanceDeposit::summary($userId);
        $this->render('finance/index', [
            'pageTitle' => '理财管理',
            'deposits' => $deposits,
            'summary' => $summary,
        ]);
    }

    public function api(): void
    {
        $userId = $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $amount = (float)($_POST['amount'] ?? 0);
                if ($amount <= 0) {
                    $this->json(['ok' => false, 'error' => '请输入有效的存款金额']);
                }
                $annualRate = $_POST['annual_rate'] !== '' ? (float)$_POST['annual_rate'] : null;
                $maturityDate = $_POST['maturity_date'] ?? null;
                $depositDate = $_POST['deposit_date'] ?? date('Y-m-d');

                // calculate estimated interest
                $estimatedInterest = null;
                if ($amount > 0 && $annualRate !== null && $maturityDate) {
                    $days = max(0, (strtotime($maturityDate) - strtotime($depositDate)) / 86400);
                    $years = $days / 365;
                    $estimatedInterest = round($amount * $annualRate * $years, 2);
                }

                $id = FinanceDeposit::create($userId, [
                    'deposit_date' => $depositDate,
                    'amount' => $amount,
                    'method' => $_POST['method'] ?? '存单',
                    'maturity_date' => $maturityDate,
                    'annual_rate' => $annualRate,
                    'estimated_interest' => $estimatedInterest,
                    'auto_renew' => (int)($_POST['auto_renew'] ?? 0),
                    'notes' => $_POST['notes'] ?? null,
                ]);
                $summary = FinanceDeposit::summary($userId);
                Logger::log('新增理财', "新增理财：金额 {$amount}，方式 " . ($_POST['method'] ?? '存单'), $userId, $_SESSION['user_nickname'] ?? null);
                $this->json(['ok' => true, 'id' => $id, 'summary' => $summary]);
                return;
            }

            if ($action === 'withdraw') {
                $id = (int)($_POST['id'] ?? 0);
                $deposit = FinanceDeposit::findById($id, $userId);
                if (!$deposit) {
                    $this->json(['ok' => false, 'error' => '记录不存在']);
                }
                $ok = FinanceDeposit::withdraw($id, $userId, [
                    'withdraw_date' => $_POST['withdraw_date'] ?? date('Y-m-d'),
                    'withdraw_principal' => $_POST['withdraw_principal'] ?? null,
                    'withdraw_interest' => $_POST['withdraw_interest'] ?? null,
                    'withdraw_notes' => $_POST['withdraw_notes'] ?? null,
                ]);
                $summary = FinanceDeposit::summary($userId);
                Logger::log('取出理财', "取出理财 #{$id}，本金 " . ($_POST['withdraw_principal'] ?? '-') . "，利息 " . ($_POST['withdraw_interest'] ?? '-'), $userId, $_SESSION['user_nickname'] ?? null);
                $this->json(['ok' => $ok, 'summary' => $summary]);
                return;
            }

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $deposit = FinanceDeposit::findById($id, $userId);
                $ok = FinanceDeposit::delete($id, $userId);
                Logger::log('删除理财', "删除理财 #{$id}", $userId, $_SESSION['user_nickname'] ?? null);
                $summary = FinanceDeposit::summary($userId);
                $this->json(['ok' => $ok, 'summary' => $summary]);
                return;
            }

            if ($action === 'load') {
                $deposits = FinanceDeposit::listByUser($userId);
                $summary = FinanceDeposit::summary($userId);
                $this->json(['ok' => true, 'deposits' => $deposits, 'summary' => $summary]);
                return;
            }
        }

        $this->json(['ok' => false, 'error' => '未知操作']);
    }
}
