<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\NamingService;

class NamingController
{
    private function requireLogin(): int
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /public/index.php?route=login');
            exit;
        }
        return (int)$_SESSION['user_id'];
    }

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function json(array $data): void
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function index(): void
    {
        $this->requireLogin();
        $_SESSION['current_page_title'] = '取名助手';
        ob_start();
        $tags = NamingService::getAvailableTags();
        ob_end_clean();
        $this->render('naming/index', ['tags' => $tags]);
    }

    public function api(): void
    {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => '无效请求']);
        }

        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'generate':
                $surname = trim($_POST['surname'] ?? '');
                if ($surname === '' || mb_strlen($surname) > 2) {
                    $this->json(['ok' => false, 'error' => '请输入1-2个字的姓氏']);
                }
                $gender = $_POST['gender'] ?? 'n';
                $nameLen = max(1, min(2, (int)($_POST['name_len'] ?? 2)));
                $generationChar = trim($_POST['generation_char'] ?? '');
                if ($generationChar !== '' && mb_strlen($generationChar) === 1) {
                    $nameLen = 1;
                }
                $rawTags = $_POST['prefer_tags'] ?? [];
                $preferTags = array_filter(array_map('trim', is_array($rawTags) ? $rawTags : explode(',', $rawTags)));
                $preferWuxing = $_POST['prefer_wuxing'] ?? '';
                if ($preferWuxing && !in_array($preferWuxing, ['金', '木', '水', '火', '土'])) $preferWuxing = '';
                $count = max(10, min(100, (int)($_POST['count'] ?? 20)));

                $results = NamingService::generateNames($surname, $gender, $nameLen, $preferTags, $preferWuxing ?: null, $count, $generationChar ?: null);
                $this->json(['ok' => true, 'results' => $results, 'count' => count($results)]);
                break;

            case 'analyze':
                $fullName = trim($_POST['name'] ?? '');
                if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 4) {
                    $this->json(['ok' => false, 'error' => '请输入2-4个字的姓名']);
                }
                $result = NamingService::analyzeName($fullName);
                $this->json($result);
                break;

            case 'char_info':
                $char = mb_substr(trim($_POST['char'] ?? ''), 0, 1);
                if ($char === '') {
                    $this->json(['ok' => false, 'error' => '请输入一个汉字']);
                }
                $info = NamingService::getCharInfo($char);
                $this->json(['ok' => true, 'info' => $info]);
                break;

            default:
                $this->json(['ok' => false, 'error' => '未知操作']);
        }
    }
}
