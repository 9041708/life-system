<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\MindfulnessCheckin;
use App\Model\MindfulnessDailyRecord;
use App\Model\MindfulnessTreasure;
use App\Model\MindfulnessConfig;
use App\Model\AiQuota;
use App\Model\User;

class MindfulnessController
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
        $_SESSION['current_page_title'] = $params['pageTitle'] ?? '正念';
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function json(array $data): void
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function isAdmin(): bool
    {
        return ($_SESSION['user_role'] ?? 'user') === 'admin';
    }

    public function checkin(): void
    {
        $userId = $this->requireLogin();
        $config = MindfulnessConfig::get($userId);
        $currentScore = MindfulnessConfig::calculateCurrentScore($userId);
        $streakStats = MindfulnessCheckin::getStreakStats($userId);
        $isCheckedIn = MindfulnessCheckin::isCheckedIn($userId, date('Y-m-d'));
        $aiQuota = AiQuota::get($userId);

        $this->render('mindfulness/checkin', [
            'pageTitle' => '正念签到',
            'config' => $config,
            'currentScore' => $currentScore,
            'streakStats' => $streakStats,
            'isCheckedIn' => $isCheckedIn,
            'aiQuota' => $aiQuota,
            'isAdmin' => $this->isAdmin(),
        ]);
    }

    public function treasure(): void
    {
        $userId = $this->requireLogin();
        $config = MindfulnessConfig::get($userId);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = 10;
        $total = MindfulnessTreasure::countByUser($userId);
        $totalPages = max(1, (int)ceil($total / $pageSize));
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $pageSize;
        $treasures = MindfulnessTreasure::listByUser($userId, $pageSize, $offset);
        $aiQuota = AiQuota::get($userId);
        $currentScore = MindfulnessConfig::calculateCurrentScore($userId);

        $this->render('mindfulness/treasure', [
            'pageTitle' => '正念树洞',
            'config' => $config,
            'treasures' => $treasures,
            'aiQuota' => $aiQuota,
            'currentScore' => $currentScore,
            'isAdmin' => $this->isAdmin(),
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    public function config(): void
    {
        $userId = $this->requireLogin();
        $config = MindfulnessConfig::get($userId);
        $aiQuota = AiQuota::get($userId);
        $pricingPlans = AiQuota::getPricingPlans();

        $this->render('mindfulness/config', [
            'pageTitle' => '正念配置',
            'config' => $config,
            'aiQuota' => $aiQuota,
            'pricingPlans' => $pricingPlans,
            'isAdmin' => $this->isAdmin(),
        ]);
    }

    public function api(): void
    {
        $userId = $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => '无效请求']);
        }

        $action = $_POST['action'] ?? '';
        try {
            ob_start();
            switch ($action) {
                case 'do_checkin':
                    $this->doCheckin($userId);
                    break;
                case 'backfill_record':
                    $this->backfillRecord($userId);
                    break;
                case 'get_calendar':
                    $this->getCalendar($userId);
                    break;
                case 'get_day_records':
                    $this->getDayRecords($userId);
                    break;
                case 'delete_record':
                    $this->deleteRecord($userId);
                    break;
                case 'create_treasure':
                    $this->createTreasure($userId);
                    break;
                case 'list_treasures':
                    $this->listTreasures($userId);
                    break;
                case 'delete_treasure':
                    $this->deleteTreasure($userId);
                    break;
                case 'save_config':
                    $this->saveConfig($userId);
                    break;
                case 'get_ai_quota':
                    $this->getAiQuota($userId);
                    break;
                default:
                    $this->json(['ok' => false, 'error' => '未知操作']);
            }
            ob_get_clean();
        } catch (\Throwable $e) {
            if (ob_get_level()) ob_end_clean();
            $this->json(['ok' => false, 'error' => '操作异常: ' . $e->getMessage()]);
        }
    }

    private function doCheckin(int $userId): void
    {
        $today = date('Y-m-d');
        if (MindfulnessCheckin::isCheckedIn($userId, $today)) {
            $this->json(['ok' => false, 'error' => '今日已签到']);
        }

        $config = MindfulnessConfig::get($userId);
        $scoreChange = (float)$config['checkin_score'];

        $streakStats = MindfulnessCheckin::getStreakStats($userId);
        $bonusRules = $config['bonus_rules'] ?? [];
        $bonus = 0;
        $newStreak = $streakStats['current_streak'] + 1;
        foreach ($bonusRules as $rule) {
            if ($newStreak >= (int)$rule['days']) {
                $bonus = (float)$rule['bonus'];
            }
        }
        $totalScoreChange = $scoreChange + $bonus;

        MindfulnessCheckin::checkin($userId, $today, $totalScoreChange);

        $newScore = MindfulnessConfig::calculateCurrentScore($userId);
        $streakStats = MindfulnessCheckin::getStreakStats($userId);

        $this->json([
            'ok' => true,
            'score_change' => $totalScoreChange,
            'bonus' => $bonus,
            'new_score' => $newScore,
            'streak' => $streakStats,
            'message' => $bonus > 0
                ? "签到成功 +{$totalScoreChange}分（含连续签到奖励 +{$bonus}）"
                : "签到成功 +{$totalScoreChange}分",
        ]);
    }

    private function backfillRecord(int $userId): void
    {
        $date = $_POST['date'] ?? '';
        $type = $_POST['type'] ?? '';
        $itemName = $_POST['item_name'] ?? '';

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->json(['ok' => false, 'error' => '日期格式不正确']);
        }
        if (!in_array($type, ['positive', 'negative'])) {
            $this->json(['ok' => false, 'error' => '类型不正确']);
        }

        $config = MindfulnessConfig::get($userId);
        $items = $type === 'positive' ? ($config['positive_items'] ?? []) : ($config['negative_items'] ?? []);

        if (!isset($items[$itemName])) {
            $this->json(['ok' => false, 'error' => '无效的项目']);
        }

        $scoreChange = (float)$items[$itemName];

        MindfulnessDailyRecord::add($userId, $date, $type, $itemName, $scoreChange);

        $newScore = MindfulnessConfig::calculateCurrentScore($userId);

        $this->json([
            'ok' => true,
            'score_change' => $scoreChange,
            'new_score' => $newScore,
            'message' => ($type === 'positive' ? '正念' : '负念') . "记录成功 " . ($scoreChange > 0 ? '+' : '') . $scoreChange . '分',
        ]);
    }

    private function getCalendar(int $userId): void
    {
        $year = (int)($_POST['year'] ?? date('Y'));
        $month = (int)($_POST['month'] ?? date('n'));

        $checkins = MindfulnessCheckin::getMonthRecords($userId, $year, $month);
        $dailySummary = MindfulnessDailyRecord::getMonthSummary($userId, $year, $month);

        $calendar = [];
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $calendar[$date] = [
                'checked' => isset($checkins[$date]),
                'positive' => ($dailySummary[$date]['positive'] ?? 0),
                'negative' => ($dailySummary[$date]['negative'] ?? 0),
            ];
        }

        $this->json(['ok' => true, 'calendar' => $calendar]);
    }

    private function getDayRecords(int $userId): void
    {
        $date = $_POST['date'] ?? date('Y-m-d');
        $records = MindfulnessDailyRecord::getByDate($userId, $date);
        $config = MindfulnessConfig::get($userId);

        $this->json([
            'ok' => true,
            'records' => $records,
            'positive_items' => $config['positive_items'] ?? [],
            'negative_items' => $config['negative_items'] ?? [],
        ]);
    }

    private function deleteRecord(int $userId): void
    {
        $id = (int)($_POST['record_id'] ?? 0);
        if ($id <= 0) {
            $this->json(['ok' => false, 'error' => '无效的记录ID']);
        }
        MindfulnessDailyRecord::delete($id, $userId);
        $newScore = MindfulnessConfig::calculateCurrentScore($userId);
        $this->json(['ok' => true, 'new_score' => $newScore, 'message' => '已删除']);
    }

    private function createTreasure(int $userId): void
    {
        $content = trim($_POST['content'] ?? '');
        if ($content === '') {
            $this->json(['ok' => false, 'error' => '请输入心事内容']);
        }

        if (MindfulnessTreasure::countToday($userId) >= 3) {
            $this->json(['ok' => false, 'error' => '今天已经写了3条心事了，明天再来倾诉吧~']);
        }

        $config = MindfulnessConfig::get($userId);
        $aiMode = $config['ai_mode'] ?? 'system';

        if ($aiMode === 'system') {
            if (!AiQuota::hasQuota($userId)) {
                $this->json(['ok' => false, 'error' => 'AI次数已用完，请购买套餐或切换为自定义AI配置', 'quota_exhausted' => true]);
            }
        }

        $aiReply = '';
        $sentiment = 'neutral';
        $scoreChange = 0;

        $recentNegative = MindfulnessTreasure::getRecentNegativeCount($userId, 5);
        $warning = '';
        if ($recentNegative >= 3) {
            $warning = '最近负面心事有点多了，要注意休息哦，保持积极心态~';
        }

        $aiResult = $this->callTreasureAi($userId, $content, $config);
        if ($aiResult) {
            $aiReply = $aiResult['reply'] ?? '';
            $sentiment = $aiResult['sentiment'] ?? 'neutral';
        }

        $items = $sentiment === 'positive' ? ($config['positive_items'] ?? []) : ($config['negative_items'] ?? []);
        if ($sentiment === 'positive' && isset($items['正能量树洞'])) {
            $scoreChange = (float)$items['正能量树洞'];
        } elseif ($sentiment === 'negative' && isset($items['负能量树洞'])) {
            $scoreChange = (float)$items['负能量树洞'];
        }

        if ($aiMode === 'system') {
            AiQuota::consume($userId);
        }

        $id = MindfulnessTreasure::create($userId, $content, $aiReply, $sentiment, $scoreChange);
        $newScore = MindfulnessConfig::calculateCurrentScore($userId);
        $aiQuota = AiQuota::get($userId);

        $this->json([
            'ok' => true,
            'id' => $id,
            'ai_reply' => $aiReply,
            'sentiment' => $sentiment,
            'score_change' => $scoreChange,
            'new_score' => $newScore,
            'warning' => $warning,
            'ai_quota' => $aiQuota,
            'message' => '心事已记录' . ($scoreChange != 0 ? ' ' . ($scoreChange > 0 ? '+' : '') . $scoreChange . '分' : ''),
        ]);
    }

    private function callTreasureAi(int $userId, string $content, array $config): ?array
    {
        $aiMode = $config['ai_mode'] ?? 'system';

        if ($aiMode === 'custom') {
            $apiUrl = $config['custom_api_url'] ?? '';
            $apiKey = $config['custom_api_key'] ?? '';
            $model = $config['custom_model'] ?? 'gpt-3.5-turbo';
        } else {
            $apiUrl = Config::get('ai.forum_reply.api_url', '');
            $apiKey = Config::get('ai.forum_reply.api_key', '');
            $model = Config::get('ai.forum_reply.model', 'gpt-3.5-turbo');
        }

        if (empty($apiUrl) || empty($apiKey)) {
            return null;
        }

        $systemPrompt = '你是一个温暖、善解人意的心灵倾听者。用户会向你倾诉心事，你需要：' . "\n";
        $systemPrompt .= '1. 用暖心、温柔的语气回复，让对方感到被理解和支持' . "\n";
        $systemPrompt .= '2. 不要过于鸡汤，不要空洞的说教，要真诚实用' . "\n";
        $systemPrompt .= '3. 如果内容偏负面，温柔地引导对方换个角度思考' . "\n";
        $systemPrompt .= '4. 回复控制在50-120字' . "\n";
        $systemPrompt .= '5. 同时判断用户的心事是"positive"(正能量)还是"negative"(负能量)或"neutral"(中性)' . "\n";
        $systemPrompt .= '6. 必须以JSON格式返回：{"reply":"你的回复内容","sentiment":"positive/negative/neutral"}' . "\n";
        $systemPrompt .= '7. 只返回JSON，不要加其他文字' . "\n";

        $prompt = "用户的心事：\n{$content}";

        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 300,
            'temperature' => 0.8,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        if (!empty($data['choices'][0]['message']['content'])) {
            $raw = trim($data['choices'][0]['message']['content']);
            $raw = preg_replace('/^```json\s*/i', '', $raw);
            $raw = preg_replace('/```\s*$/', '', $raw);
            $parsed = json_decode($raw, true);
            if (is_array($parsed) && isset($parsed['reply'])) {
                return [
                    'reply' => $parsed['reply'],
                    'sentiment' => in_array($parsed['sentiment'] ?? '', ['positive', 'negative', 'neutral']) ? $parsed['sentiment'] : 'neutral',
                ];
            }
            return ['reply' => $raw, 'sentiment' => 'neutral'];
        }

        return null;
    }

    private function listTreasures(int $userId): void
    {
        $page = max(1, (int)($_POST['page'] ?? 1));
        $pageSize = 10;
        $total = MindfulnessTreasure::countByUser($userId);
        $totalPages = max(1, (int)ceil($total / $pageSize));
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $pageSize;
        $treasures = MindfulnessTreasure::listByUser($userId, $pageSize, $offset);
        $this->json(['ok' => true, 'treasures' => $treasures, 'page' => $page, 'total_pages' => $totalPages, 'total' => $total]);
    }

    private function deleteTreasure(int $userId): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['ok' => false, 'error' => '无效的ID']);
        }
        MindfulnessTreasure::delete($id, $userId);
        $this->json(['ok' => true, 'message' => '已删除']);
    }

    private function saveConfig(int $userId): void
    {
        $data = [
            'initial_score' => (float)($_POST['initial_score'] ?? 80),
            'checkin_score' => (float)($_POST['checkin_score'] ?? 0.3),
            'ai_mode' => in_array($_POST['ai_mode'] ?? '', ['system', 'custom']) ? $_POST['ai_mode'] : 'system',
            'custom_api_url' => trim($_POST['custom_api_url'] ?? ''),
            'custom_api_key' => trim($_POST['custom_api_key'] ?? ''),
            'custom_model' => trim($_POST['custom_model'] ?? ''),
        ];

        $positiveItems = [];
        $piNames = $_POST['pi_name'] ?? [];
        $piScores = $_POST['pi_score'] ?? [];
        for ($i = 0; $i < count($piNames); $i++) {
            $name = trim($piNames[$i]);
            if ($name !== '') {
                $positiveItems[$name] = (float)($piScores[$i] ?? 0);
            }
        }
        $data['positive_items'] = $positiveItems;

        $negativeItems = [];
        $niNames = $_POST['ni_name'] ?? [];
        $niScores = $_POST['ni_score'] ?? [];
        for ($i = 0; $i < count($niNames); $i++) {
            $name = trim($niNames[$i]);
            if ($name !== '') {
                $negativeItems[$name] = (float)($niScores[$i] ?? 0);
            }
        }
        $data['negative_items'] = $negativeItems;

        $bonusRules = [];
        $brDays = $_POST['br_days'] ?? [];
        $brBonus = $_POST['br_bonus'] ?? [];
        for ($i = 0; $i < count($brDays); $i++) {
            $days = (int)($brDays[$i] ?? 0);
            $bonus = (float)($brBonus[$i] ?? 0);
            if ($days > 0) {
                $bonusRules[] = ['days' => $days, 'bonus' => $bonus];
            }
        }
        $data['bonus_rules'] = $bonusRules;

        MindfulnessConfig::save($userId, $data);
        $this->json(['ok' => true, 'message' => '配置已保存']);
    }

    private function getAiQuota(int $userId): void
    {
        $quota = AiQuota::get($userId);
        $remaining = AiQuota::getRemaining($userId);
        $this->json(['ok' => true, 'quota' => $quota, 'remaining' => $remaining]);
    }
}
