<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\LedgerContext;
use App\Service\DiscuzService;
use App\Model\PasswordVault;
use App\Model\EasyTodoTask;
use App\Model\ForumAccount;
use App\Model\ForumActionLog;
use App\Model\AiQuota;
use App\Service\TodayDoService;

class ToolboxController
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
        $_SESSION['current_page_title'] = $params['pageTitle'] ?? '工具箱';
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function json(array $data): void
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function passwordVault(): void
    {
        $userId = $this->requireLogin();
        $search = $_GET['search'] ?? '';
        $entries = PasswordVault::listByUser($userId, $search);
        $this->render('toolbox/password_vault', [
            'pageTitle' => '密码箱',
            'entries' => $entries,
            'search' => $search,
        ]);
    }

    public function passwordVaultApi(): void
    {
        $userId = $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $name = trim($_POST['name'] ?? '');
                $password = $_POST['password'] ?? '';
                if ($name === '' || $password === '') {
                    $this->json(['ok' => false, 'error' => '名称和密码不能为空']);
                }
                $id = PasswordVault::create($userId, [
                    'name' => $name,
                    'url' => $_POST['url'] ?? null,
                    'username' => $_POST['username'] ?? '',
                    'password' => $password,
                    'notes' => $_POST['notes'] ?? null,
                ]);
                $this->json(['ok' => true, 'id' => $id]);
                return;
            }

            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $entry = PasswordVault::findById($id);
                if (!$entry || (int)$entry['user_id'] !== $userId) {
                    $this->json(['ok' => false, 'error' => '无权操作']);
                }
                $name = trim($_POST['name'] ?? '');
                if ($name === '') {
                    $this->json(['ok' => false, 'error' => '名称不能为空']);
                }
                $data = [
                    'name' => $name,
                    'url' => $_POST['url'] ?? null,
                    'username' => $_POST['username'] ?? '',
                    'notes' => $_POST['notes'] ?? null,
                ];
                if (!empty($_POST['password'])) {
                    $data['password'] = $_POST['password'];
                }
                PasswordVault::update($id, $userId, $data);
                $this->json(['ok' => true]);
                return;
            }

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                PasswordVault::delete($id, $userId);
                $this->json(['ok' => true]);
                return;
            }

            if ($action === 'get') {
                $id = (int)($_POST['id'] ?? 0);
                $entry = PasswordVault::findById($id);
                if (!$entry || (int)$entry['user_id'] !== $userId) {
                    $this->json(['ok' => false, 'error' => '无权操作']);
                }
                $this->json(['ok' => true, 'entry' => $entry]);
                return;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $action = $_GET['action'] ?? '';
            if ($action === 'search') {
                $search = $_GET['search'] ?? '';
                $entries = PasswordVault::listByUser($userId, $search);
                $this->json(['ok' => true, 'entries' => $entries]);
                return;
            }
        }

        $this->json(['ok' => false, 'error' => '未知操作']);
    }

    public function cnyConverter(): void
    {
        $this->requireLogin();
        $this->render('toolbox/cny_converter', ['pageTitle' => '人民币大写转换器']);
    }

    public function shelfLife(): void
    {
        $this->requireLogin();
        $this->render('toolbox/shelf_life', ['pageTitle' => '保质期计算器']);
    }

    public function qrcode(): void
    {
        $this->requireLogin();
        $this->render('toolbox/qrcode', ['pageTitle' => '二维码生成器']);
    }

    public function morse(): void
    {
        $this->requireLogin();
        $this->render('toolbox/morse', ['pageTitle' => '摩斯电码编码解码']);
    }

    public function calendar(): void
    {
        $userId = $this->requireLogin();
        $dateParam = isset($_GET['date']) ? trim((string)$_GET['date']) : '';
        $ym = isset($_GET['ym']) ? trim((string)$_GET['ym']) : '';
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateParam, $m)) {
            $dY = (int)$m[1]; $dM = (int)$m[2]; $dD = (int)$m[3];
        } elseif (preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) {
            $dY = (int)$m[1]; $dM = (int)$m[2]; $dD = 1;
        } else {
            $dY = (int)date('Y'); $dM = (int)date('n'); $dD = (int)date('j');
        }
        if ($dM < 1 || $dM > 12) { $dY = (int)date('Y'); $dM = (int)date('n'); }
        if ($dD < 1 || $dD > 31) $dD = 1;
        $taskMonthStats = [];
        try {
            $taskMonthStats = EasyTodoTask::listByMonth($userId, $dY, $dM);
        } catch (\Throwable $e) { $taskMonthStats = []; }
        $this->render('toolbox/calendar', [
            'pageTitle' => '万年历',
            'selYear' => $dY,
            'selMonth' => $dM,
            'selDay' => $dD,
            'taskMonthStats' => $taskMonthStats,
            'holidays' => [],
            'holidayRestWork' => self::getHolidayRestWork($dY),
        ]);
    }

    private static function getHolidayRestWork(int $year): array
    {
        $schedule = [
            2025 => [
                'rest' => ['2025-01-01','2025-01-28','2025-01-29','2025-01-30','2025-01-31','2025-02-01','2025-02-02','2025-02-03','2025-02-04','2025-04-04','2025-04-05','2025-04-06','2025-05-01','2025-05-02','2025-05-03','2025-05-04','2025-05-05','2025-05-31','2025-06-01','2025-06-02','2025-10-01','2025-10-02','2025-10-03','2025-10-04','2025-10-05','2025-10-06','2025-10-07','2025-10-08'],
                'work' => ['2025-01-26','2025-02-08','2025-04-27','2025-09-28','2025-10-11'],
            ],
            2026 => [
                'rest' => ['2026-01-01','2026-01-02','2026-01-03','2026-02-17','2026-02-18','2026-02-19','2026-02-20','2026-02-21','2026-02-22','2026-02-23','2026-04-05','2026-04-06','2026-04-07','2026-05-01','2026-05-02','2026-05-03','2026-05-04','2026-05-05','2026-06-19','2026-06-20','2026-06-21','2026-10-01','2026-10-02','2026-10-03','2026-10-04','2026-10-05','2026-10-06','2026-10-07','2026-10-08'],
                'work' => ['2026-02-14','2026-02-28','2026-04-26','2026-05-09','2026-09-27','2026-10-10'],
            ],
        ];
        return $schedule[$year] ?? ['rest' => [], 'work' => []];
    }

    public function forumAssistant(): void
    {
        $userId = $this->requireLogin();
        $accounts = ForumAccount::listByUser($userId);
        $logs = ForumActionLog::listByUser($userId, 30);
        $this->render('toolbox/forum_assistant', [
            'pageTitle' => '论坛助手',
            'accounts' => $accounts,
            'logs' => $logs,
        ]);
    }

    public function forumAssistantApi(): void
    {
        $userId = $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => '无效请求']);
        }

        $action = $_POST['action'] ?? '';

        try {
            ob_start();
            switch ($action) {
                case 'create':
                case 'update':
                    $this->forumSaveAccount($userId, $action);
                    break;
                case 'delete':
                    $this->forumDeleteAccount($userId);
                    break;
                case 'get':
                    $this->forumGetAccount($userId);
                    break;
                case 'test':
                    $this->forumTestConnection($userId);
                    break;
                case 'signin':
                    $this->forumSignin($userId);
                    break;
                case 'notice':
                    $this->forumGetNotices($userId);
                    break;
            case 'reply':
                $this->forumReply($userId);
                break;
            case 'get_threads':
                $this->forumGetThreads($userId);
                break;
            case 'clear_logs':
                $this->forumClearLogs($userId);
                break;
            case 'clean_old_logs':
                $count = ForumActionLog::cleanOldLogs(3);
                $this->json(['ok' => true, 'message' => "清理了 {$count} 条旧日志"]);
                break;
            case 'check_notices':
                $this->forumCheckNotices($userId);
                break;
            case 'get_logs':
                $this->forumGetLogs($userId);
                break;
            default:
                $this->json(['ok' => false, 'error' => '未知操作']);
            }
            $junk = ob_get_clean();
        } catch (\Throwable $e) {
            if (ob_get_level()) ob_end_clean();
            $this->json(['ok' => false, 'error' => '操作异常: ' . $e->getMessage()]);
        }
    }

    private function forumSaveAccount(int $userId, string $action): void
    {
        $forumName = trim($_POST['forum_name'] ?? '');
        $forumUrl = trim($_POST['forum_url'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($forumName === '' || $forumUrl === '' || $username === '') {
            $this->json(['ok' => false, 'error' => '论坛名称、地址和用户名不能为空']);
        }

        $data = [
            'forum_name' => $forumName,
            'forum_url' => $forumUrl,
            'username' => $username,
            'enable_notice' => isset($_POST['enable_notice']) ? 1 : 0,
            'notice_interval' => max(5, min(1440, (int)($_POST['notice_interval'] ?? 15))),
            'enable_mention_reply' => isset($_POST['enable_mention_reply']) ? 1 : 0,
            'enable_follow_up' => isset($_POST['enable_follow_up']) ? 1 : 0,
            'enable_signin' => isset($_POST['enable_signin']) ? 1 : 0,
            'enable_autoreply' => isset($_POST['enable_autoreply']) ? 1 : 0,
            'reply_mode' => 'ai',
            'custom_reply' => '',
            'ai_reply_flag' => trim($_POST['ai_reply_flag'] ?? '[AI回帖]'),
            'signin_time' => $_POST['signin_time'] ?? '08:00',
            'signin_url' => trim($_POST['signin_url'] ?? ''),
            'reply_time' => $_POST['reply_time'] ?? '09:00',
            'reply_interval' => max(5, (int)($_POST['reply_interval'] ?? 10)),
            'auto_reply_interval' => max(5, (int)($_POST['auto_reply_interval'] ?? 30)),
        ];

        if ($password !== '') {
            $data['password'] = $password;
        }

        if ($action === 'create') {
            if ($password === '') {
                $this->json(['ok' => false, 'error' => '密码不能为空']);
            }
            $id = ForumAccount::create($userId, $data);
            $this->json(['ok' => true, 'id' => $id, 'message' => '添加成功']);
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $account = ForumAccount::findByIdAndUser($id, $userId);
            if (!$account) {
                $this->json(['ok' => false, 'error' => '账号不存在']);
            }
            $result = ForumAccount::update($id, $userId, $data);
            $this->json(['ok' => $result, 'message' => $result ? '更新成功' : '更新失败，请检查数据']);
        }
    }

    private function forumDeleteAccount(int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        ForumAccount::delete($id, $userId);
        $this->json(['ok' => true, 'message' => '删除成功']);
    }

    private function forumGetAccount(int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $account = ForumAccount::findByIdAndUser($id, $userId);
        if (!$account) {
            $this->json(['ok' => false, 'error' => '账号不存在']);
        }
        unset($account['cookie_data']);
        $this->json(['ok' => true, 'account' => $account]);
    }

    private function forumTestConnection(int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $account = ForumAccount::findByIdAndUser($id, $userId);
        if (!$account) {
            $this->json(['ok' => false, 'error' => '账号不存在']);
        }

        $service = new DiscuzService($userId, $account);
        $result = $service->testConnection();
        if (!$result['ok']) {
            $this->json($result);
        }

        $loginResult = $service->login($account['username'], $account['password']);
        if ($loginResult['ok']) {
            $this->json(['ok' => true, 'message' => '连接成功，登录正常']);
        } else {
            $this->json(['ok' => false, 'error' => '连接成功，但登录失败：' . ($loginResult['error'] ?? '未知错误')]);
        }
    }

    private function forumSignin(int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $account = ForumAccount::findByIdAndUser($id, $userId);
        if (!$account) {
            $this->json(['ok' => false, 'error' => '账号不存在']);
        }

        $service = new DiscuzService($userId, $account);

        // 先登录
        $loginResult = $service->login($account['username'], $account['password']);
        if (!$loginResult['ok']) {
            ForumActionLog::create($userId, $id, 'error', $loginResult['error']);
            $this->json($loginResult);
        }

        // 签到
        $result = $service->signin($account);
        $logType = $result['ok'] ? 'signin' : 'error';
        ForumActionLog::create($userId, $id, $logType, $result['message'] ?? $result['error']);
        $this->json($result);
    }

    private function forumGetNotices(int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $account = ForumAccount::findByIdAndUser($id, $userId);
        if (!$account) {
            $this->json(['ok' => false, 'error' => '账号不存在']);
        }

        $service = new DiscuzService($userId, $account);

        // 先登录
        $loginResult = $service->login($account['username'], $account['password']);
        if (!$loginResult['ok']) {
            ForumActionLog::create($userId, $id, 'error', $loginResult['error']);
            $this->json($loginResult);
        }

        // 获取通知
        $result = $service->getNotices();
        ForumActionLog::create($userId, $id, 'notice', $result['message'] ?? $result['error'] ?? '获取通知');
        $this->json($result);
    }

    private function forumGetThreads(int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $account = ForumAccount::findByIdAndUser($id, $userId);
        if (!$account) {
            $this->json(['ok' => false, 'error' => '账号不存在']);
        }

        $service = new DiscuzService($userId, $account);

        $loginResult = $service->login($account['username'], $account['password']);
        if (!$loginResult['ok']) {
            $this->json(['ok' => false, 'error' => '登录失败：' . ($loginResult['error'] ?? '未知错误')]);
        }

        $fid = (int)($_POST['fid'] ?? 0);
        $result = $service->getThreadList($fid, true);
        $this->json($result);
    }

    private function forumReply(int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $tid = (int)($_POST['tid'] ?? 0);
        $account = ForumAccount::findByIdAndUser($id, $userId);
        if (!$account) {
            $this->json(['ok' => false, 'error' => '账号不存在']);
        }

        try {
            $service = new DiscuzService($userId, $account);

            // 先登录
            $loginResult = $service->login($account['username'], $account['password']);
            if (!$loginResult['ok']) {
                ForumActionLog::create($userId, $id, 'error', $loginResult['error']);
                $this->json($loginResult);
            }

            // 如果没有指定tid，自动选择一个未回复的帖子
            if ($tid <= 0) {
                $thread = $service->getUnrepliedThread();
                if (!$thread) {
                    ForumActionLog::create($userId, $id, 'error', '没有可回复的帖子（可能都已回复过）');
                    $this->json(['ok' => false, 'error' => '没有可回复的帖子（可能都已回复过）']);
                }
                $tid = $thread['tid'];
            }

            // AI 生成回帖内容
            $aiFlag = $account['ai_reply_flag'] ?? '[AI回帖]';

            if (!AiQuota::hasQuota($userId)) {
                $this->json(['ok' => false, 'error' => 'AI次数已用完，请在"正念配置"页面购买套餐或联系管理员']);
            }

            $message = '';
            $threadContent = $service->getThreadContent($tid);
            if ($threadContent['ok']) {
                $aiReply = DiscuzService::generateAiReply($threadContent['title'], $threadContent['content']);
                if (!$aiReply) {
                    $aiReply = DiscuzService::generateAiReply($threadContent['title'], $threadContent['content'], true);
                }
                if ($aiReply) {
                    $message = $aiReply;
                }
            }
            if (empty($message)) {
                $this->json(['ok' => false, 'error' => 'AI回复生成失败，请稍后重试']);
            }
            $message .= "\n" . $aiFlag;

            AiQuota::consume($userId, 'forum_reply', '论坛回帖：' . mb_substr($account['forum_name'] ?? '', 0, 30));

            // 回帖
            $result = $service->reply($tid, $message);
            $logType = $result['ok'] ? 'reply' : 'error';
            $target = ($result['title'] ?? "帖子") . " [tid:$tid]";
            ForumActionLog::create($userId, $id, $logType, $result['message'] ?? $result['error'], $target);
            $this->json($result);
        } catch (\Throwable $e) {
            ForumActionLog::create($userId, $id, 'error', $e->getMessage());
            $this->json(['ok' => false, 'error' => '回帖异常: ' . $e->getMessage()]);
        }
    }

    private function forumClearLogs(int $userId): void
    {
        $count = ForumActionLog::cleanByUser($userId);
        $this->json(['ok' => true, 'message' => "清除 {$count} 条日志"]);
    }

    private function forumCheckNotices(int $userId): void
    {
        $accounts = ForumAccount::listByUser($userId);
        $results = [];
        
        foreach ($accounts as $account) {
            if (empty($account['enable_notice'])) {
                continue;
            }
            
            $item = [
                'id' => (int)$account['id'],
                'forum_name' => $account['forum_name'],
                'unread' => 0,
                'error' => null,
            ];
            
            try {
                $service = new DiscuzService($userId, $account);
                $loginResult = $service->login($account['username'], $account['password']);
                if (!$loginResult['ok']) {
                    $item['error'] = $loginResult['error'];
                } else {
                    $noticeResult = $service->getNotices();
                    if ($noticeResult['ok']) {
                        $item['unread'] = $noticeResult['unread'] ?? 0;
                    } else {
                        $item['error'] = $noticeResult['error'] ?? '获取失败';
                    }
                }
            } catch (\Throwable $e) {
                $item['error'] = $e->getMessage();
            }
            
            $results[] = $item;
        }
        
        $this->json(['ok' => true, 'results' => $results]);
    }

    private function forumGetLogs(int $userId): void
    {
        $logs = ForumActionLog::listByUser($userId, 50);
        $this->json(['ok' => true, 'logs' => $logs]);
    }

    public function todayDo(): void
    {
        $this->requireLogin();
        $this->render('toolbox/today_do', ['pageTitle' => '今天干嘛']);
    }

    public function todayDoApi(): void
    {
        $this->requireLogin();
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        switch ($action) {
            case 'get_food':
                $category = $_POST['category'] ?? '';
                $result = TodayDoService::getRandomFood($category);
                $this->json(['ok' => true, 'data' => $result]);
                break;
            case 'get_food_list':
                $foods = TodayDoService::getRandomFoodList($_POST['category'] ?? '');
                $this->json(['ok' => true, 'data' => $foods]);
                break;
            case 'food_categories':
                $this->json(['ok' => true, 'data' => TodayDoService::getFoodCategories()]);
                break;
            case 'get_place':
                $city = $_POST['city'] ?? '';
                $isFree = isset($_POST['is_free']) ? (int)$_POST['is_free'] : null;
                $result = TodayDoService::getRandomPlace($city, $isFree);
                $this->json(['ok' => true, 'data' => $result]);
                break;
            case 'get_place_list':
                $city = $_POST['city'] ?? '';
                $isFree = isset($_POST['is_free']) ? (int)$_POST['is_free'] : null;
                $places = TodayDoService::getRandomPlaceList($city, $isFree);
                $this->json(['ok' => true, 'data' => $places]);
                break;
            case 'places_cities':
                $this->json(['ok' => true, 'data' => TodayDoService::getPlacesCities()]);
                break;
            case 'get_show':
                $type = $_POST['type'] ?? 'tv';
                $platform = $_POST['platform'] ?? '';
                $result = TodayDoService::getRandomShow($type, $platform);
                $this->json(['ok' => true, 'data' => $result]);
                break;
            case 'get_show_list':
                $type = $_POST['type'] ?? 'tv';
                $shows = TodayDoService::getRandomShowList($type);
                $this->json(['ok' => true, 'data' => $shows]);
                break;
            case 'show_types':
                $this->json(['ok' => true, 'data' => TodayDoService::getShowTypes()]);
                break;
            case 'show_platforms':
                $this->json(['ok' => true, 'data' => TodayDoService::getShowPlatforms()]);
                break;
            default:
                $this->json(['ok' => false, 'error' => '未知操作']);
        }
    }
}
