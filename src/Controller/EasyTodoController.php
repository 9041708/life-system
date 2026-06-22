<?php
namespace App\Controller;

use App\Service\Config;
use App\Model\EasyTodoTask;
use App\Model\EasyTodoCommand;
use App\Model\EasyTodoCountdown;
use App\Model\EasyTodoPomodoro;
use App\Model\EasyTodoMemo;
use App\Model\EasyTodoReport;
use App\Model\EasyTodoClipboard;
use App\Model\Ledger;
use App\Service\LedgerContext;
use App\Model\User;

class EasyTodoController
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
        $_SESSION['current_page_title'] = $params['pageTitle'] ?? 'EasyTodo';
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ================================
    // 待办管理
    // ================================
    private function getHolidays(int $year): array
    {
        // 中国法定节假日（硬编码）
        $holidays = [
            $year . '-01-01' => '元旦',
            $year . '-02-10' => '春节', $year . '-02-11' => '春节', $year . '-02-12' => '春节',
            $year . '-02-13' => '春节', $year . '-02-14' => '春节', $year . '-02-15' => '春节', $year . '-02-16' => '春节',
            $year . '-04-04' => '清明', $year . '-04-05' => '清明', $year . '-04-06' => '清明',
            $year . '-05-01' => '劳动节', $year . '-05-02' => '劳动节', $year . '-05-03' => '劳动节',
            $year . '-05-04' => '劳动节', $year . '-05-05' => '劳动节',
            $year . '-06-10' => '端午节', $year . '-06-11' => '端午节', $year . '-06-12' => '端午节',
            $year . '-09-15' => '中秋节', $year . '-09-16' => '中秋节', $year . '-09-17' => '中秋节',
            $year . '-10-01' => '国庆节', $year . '-10-02' => '国庆节', $year . '-10-03' => '国庆节',
            $year . '-10-04' => '国庆节', $year . '-10-05' => '国庆节', $year . '-10-06' => '国庆节', $year . '-10-07' => '国庆节',
        ];
        return $holidays;
    }

    private function getLunarDates(int $year): array
    {
        // 调用农历接口获取年度农历数据（使用中国法定节假日 API）
        $cacheKey = "easytodo_lunar_{$year}";
        $cacheFile = sys_get_temp_dir() . '/' . $cacheKey . '.json';
        $lunar = [];

        if (file_exists($cacheFile) && filemtime($cacheFile) > time() - 86400 * 30) {
            $lunar = json_decode(file_get_contents($cacheFile), true) ?? [];
        }

        if (empty($lunar)) {
            $context = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
            $json = @file_get_contents("https://api.apihubs.cn/holiday/get?year={$year}&size=366", false, $context);
            if ($json) {
                $data = json_decode($json, true);
                if (!empty($data['data']['list'])) {
                    foreach ($data['data']['list'] as $item) {
                        $gd = $item['date'] ?? null;
                        $ld = $item['lunar'] ?? null;
                        $cn = $item['cn'] ?? '';
                        $isHoliday = !empty($item['holiday']);
                        if ($gd && $ld) {
                            $lunar[$gd] = $ld;
                        }
                        if ($gd && $isHoliday && $cn) {
                            // 节假日直接覆盖
                            if (!isset($lunar[$gd]) || $lunar[$gd] !== $cn) {
                                $lunar[$gd] = ($lunar[$gd] ?? '') . ' ' . $cn;
                            }
                        }
                    }
                    @file_put_contents($cacheFile, json_encode($lunar));
                }
            }
        }
        return $lunar;
    }

    public function tasks(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        try { EasyTodoTask::ensureAdvancedColumns(); } catch (\Throwable $e) {}

        $date = $_GET['date'] ?? date('Y-m-d');
        $tasks = EasyTodoTask::listByUser($userId, $ledgerId, $date);

        // 今日统计
        $stats = EasyTodoTask::countByDate($userId, $date);

        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));
        if ($month < 1) { $month = 12; $year--; }
        if ($month > 12) { $month = 1; $year++; }

        // 月度任务统计（供日历使用）
        $monthStats = EasyTodoTask::listByMonth($userId, $year, $month);

        // 整月任务列表（按日期分组，供日历格子显示）
        $allTasks = EasyTodoTask::listByUser($userId, $ledgerId, null, 1000);
        $tasksByDate = [];
        foreach ($allTasks as $t) {
            $d = $t['task_date'] ?? null;
            if (!$d) continue;
            if (!isset($tasksByDate[$d])) $tasksByDate[$d] = [];
            $tasksByDate[$d][] = $t;
        }

        $this->render('easytodo/tasks', [
            'pageTitle' => '待办管理',
            'tasks' => $tasks,
            'date' => $date,
            'stats' => $stats,
            'year' => $year,
            'month' => $month,
            'monthStats' => $monthStats,
            'tasksByDate' => $tasksByDate,
            'selectedDate' => $date,
            'holidays' => $this->getHolidays($year),
            'lunarInfo' => $this->getLunarDates($year),
        ]);
    }

    public function apiTasks(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            // 快捷指令展开
            if ($action === 'expand_command') {
                $text = trim($_POST['text'] ?? '');
                $tasks = [];
                // 匹配 /cmd 或 /cmd 参数
                if (preg_match('/^(\/\S+)/', $text, $m)) {
                    $trigger = $m[1];
                    $cmd = EasyTodoCommand::findByTrigger($trigger, $userId);
                    if ($cmd) {
                        $lines = explode("\n", trim($cmd['content']));
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if ($line === '') continue;
                            // 替换 {{date}} 为当前日期
                            $line = str_replace('{{date}}', date('Y-m-d'), $line);
                            $tasks[] = EasyTodoTask::create($userId, [
                                'ledger_id' => $ledgerId > 0 ? $ledgerId : null,
                                'title' => $line,
                                'task_date' => $date = $_POST['date'] ?? date('Y-m-d'),
                            ]);
                        }
                    }
                }
                $this->json(['ok' => true, 'created' => count($tasks)]);
                return;
            }

            // 创建任务
            if ($action === 'create') {
                $title = trim($_POST['title'] ?? '');
                if ($title === '') {
                    $this->json(['ok' => false, 'error' => '标题不能为空']);
                }
                $id = EasyTodoTask::create($userId, [
                    'ledger_id' => $ledgerId > 0 ? $ledgerId : null,
                    'title' => $title,
                    'description' => $_POST['description'] ?? null,
                    'task_date' => $_POST['task_date'] ?? date('Y-m-d'),
                    'tags' => $_POST['tags'] ?? null,
                    'color' => $_POST['color'] ?? 'blue',
                    'recurrence' => $_POST['recurrence'] ?? 'none',
                    'reminder_at' => !empty($_POST['reminder_at']) ? $_POST['reminder_at'] : null,
                    'reminder_advance' => (int)($_POST['reminder_advance'] ?? 0),
                ]);
                $this->json(['ok' => true, 'id' => $id]);
                return;
            }

            // 切换完成状态
            if ($action === 'toggle') {
                $id = (int)($_POST['id'] ?? 0);
                $task = EasyTodoTask::findById($id);
                if (!$task || (int)$task['user_id'] !== $userId) {
                    $this->json(['ok' => false, 'error' => '无权操作']);
                }
                $newCompleted = $task['completed'] ? 0 : 1;
                EasyTodoTask::update($id, $userId, ['completed' => $newCompleted]);
                $this->json(['ok' => true, 'completed' => $newCompleted]);
                return;
            }

            // 置顶
            if ($action === 'pin') {
                $id = (int)($_POST['id'] ?? 0);
                $task = EasyTodoTask::findById($id);
                if (!$task || (int)$task['user_id'] !== $userId) {
                    $this->json(['ok' => false, 'error' => '无权操作']);
                }
                $newPinned = $task['pinned'] ? 0 : 1;
                EasyTodoTask::update($id, $userId, ['pinned' => $newPinned]);
                $this->json(['ok' => true, 'pinned' => $newPinned]);
                return;
            }

            // 删除
            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                EasyTodoTask::delete($id, $userId);
                $this->json(['ok' => true]);
                return;
            }

            // 更新
            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $data = [];
                if (isset($_POST['title'])) $data['title'] = $_POST['title'];
                if (isset($_POST['description'])) $data['description'] = $_POST['description'];
                if (isset($_POST['task_date'])) $data['task_date'] = $_POST['task_date'];
                if (isset($_POST['tags'])) $data['tags'] = $_POST['tags'];
                if (isset($_POST['color'])) $data['color'] = $_POST['color'];
                if (isset($_POST['recurrence'])) $data['recurrence'] = $_POST['recurrence'];
                if (array_key_exists('reminder_at', $_POST)) $data['reminder_at'] = !empty($_POST['reminder_at']) ? $_POST['reminder_at'] : null;
                if (isset($_POST['reminder_advance'])) $data['reminder_advance'] = (int)$_POST['reminder_advance'];
                EasyTodoTask::update($id, $userId, $data);
                $this->json(['ok' => true]);
                return;
            }
        }

        $this->json(['ok' => false, 'error' => '未知操作']);
    }

    // ================================
    // 倒计时
    // ================================
    public function countdowns(): void
    {
        $userId = $this->requireLogin();
        $countdowns = EasyTodoCountdown::listByUser($userId);
        $this->render('easytodo/countdowns', [
            'pageTitle' => '倒计时',
            'countdowns' => $countdowns,
        ]);
    }

    public function apiCountdowns(): void
    {
        $userId = $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $title = trim($_POST['title'] ?? '');
                if ($title === '') {
                    $this->json(['ok' => false, 'error' => '标题不能为空']);
                }
                $id = EasyTodoCountdown::create($userId, [
                    'title' => $title,
                    'target_time' => $_POST['target_time'] ?? '',
                    'target_date' => $_POST['target_date'] ?? null,
                    'repeat_type' => $_POST['repeat_type'] ?? 'none',
                    'repeat_weekday' => isset($_POST['repeat_weekday']) ? (int)$_POST['repeat_weekday'] : null,
                    'repeat_month_day' => isset($_POST['repeat_month_day']) ? (int)$_POST['repeat_month_day'] : null,
                    'display_mode' => (int)($_POST['display_mode'] ?? 2),
                ]);
                $this->json(['ok' => true, 'id' => $id]);
                return;
            }

            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $data = [];
                $allowed = ['title','target_time','target_date','repeat_type','repeat_weekday','repeat_month_day','display_mode','enabled'];
                foreach ($allowed as $f) {
                    if (isset($_POST[$f])) {
                        $data[$f] = $_POST[$f];
                    }
                }
                EasyTodoCountdown::update($id, $userId, $data);
                $this->json(['ok' => true]);
                return;
            }

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                EasyTodoCountdown::delete($id, $userId);
                $this->json(['ok' => true]);
                return;
            }

            if ($action === 'toggle_enabled') {
                $id = (int)($_POST['id'] ?? 0);
                $cd = EasyTodoCountdown::findById($id);
                if (!$cd) { $this->json(['ok' => false]); }
                EasyTodoCountdown::update($id, $userId, ['enabled' => $cd['enabled'] ? 0 : 1]);
                $this->json(['ok' => true]);
                return;
            }
        }

        $this->json(['ok' => false, 'error' => '未知操作']);
    }

    // ================================
    // 番茄钟
    // ================================
    public function pomodoro(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);
        $settings = EasyTodoPomodoro::getSettings($userId);
        $recentSessions = EasyTodoPomodoro::listSessions($userId, 20);
        $todayWorkSessions = EasyTodoPomodoro::countTodayWorkSessions($userId);
        $todayWorkMinutes = EasyTodoPomodoro::sumTodayWorkMinutes($userId);

        $this->render('easytodo/pomodoro', [
            'pageTitle' => '番茄钟',
            'settings' => $settings,
            'recentSessions' => $recentSessions,
            'todayWorkSessions' => $todayWorkSessions,
            'todayWorkMinutes' => $todayWorkMinutes,
        ]);
    }

    public function apiPomodoro(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'save_settings') {
                $data = [];
                $fields = ['work_duration','short_break','long_break','long_break_interval','auto_start_break','auto_start_work'];
                foreach ($fields as $f) {
                    if (isset($_POST[$f])) $data[$f] = (int)$_POST[$f];
                }
                EasyTodoPomodoro::saveSettings($userId, $data);
                $this->json(['ok' => true]);
                return;
            }

            if ($action === 'start_session') {
                $type = $_POST['type'] ?? 'work';
                $startedAt = $_POST['started_at'] ?? date('Y-m-d H:i:s');
                $id = EasyTodoPomodoro::createSession(
                    $userId,
                    $ledgerId > 0 ? $ledgerId : null,
                    $type,
                    $startedAt,
                    null,
                    0,
                    null
                );
                $this->json(['ok' => true, 'id' => $id]);
                return;
            }

            if ($action === 'end_session') {
                $id = (int)($_POST['id'] ?? 0);
                $endedAt = $_POST['ended_at'] ?? date('Y-m-d H:i:s');
                $startedAt = $_POST['started_at'] ?? $endedAt;
                $diff = abs(strtotime($endedAt) - strtotime($startedAt));
                $minutes = (int)floor($diff / 60);
                $pdo = \App\Service\Database::getConnection();
                $stmt = $pdo->prepare("UPDATE easytodo_pomodoro_session SET ended_at = ?, duration_minutes = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$endedAt, $minutes, $id, $userId]);
                $this->json(['ok' => true, 'minutes' => $minutes]);
                return;
            }
        }
    }

    // ================================
    // 备忘录
    // ================================
    public function memos(): void
    {
        $userId = $this->requireLogin();
        $memos = EasyTodoMemo::listByUser($userId);
        $this->render('easytodo/memos', [
            'pageTitle' => '备忘录',
            'memos' => $memos,
        ]);
    }

    public function apiMemos(): void
    {
        $userId = $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $content = trim($_POST['content'] ?? '');
                if ($content === '') $this->json(['ok' => false, 'error' => '内容不能为空']);
                $id = EasyTodoMemo::create($userId, $content);
                $this->json(['ok' => true, 'id' => $id]);
                return;
            }

            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $content = trim($_POST['content'] ?? '');
                EasyTodoMemo::update($id, $userId, $content);
                $this->json(['ok' => true]);
                return;
            }

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                EasyTodoMemo::delete($id, $userId);
                $this->json(['ok' => true]);
                return;
            }
        }
    }

    // ================================
    // 统计看板
    // ================================
    public function statistics(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);

        $view = $_GET['view'] ?? 'week';
        $today = date('Y-m-d');

        if ($view === 'day') {
            $stats = [EasyTodoTask::countByDate($userId, $today)];
            $labels = [$today];
        } elseif ($view === 'month') {
            $year = (int)date('Y');
            $month = (int)date('m');
            $rows = EasyTodoTask::listByMonth($userId, $year, $month);
            $map = [];
            foreach ($rows as $r) {
                $dt = $r['task_date'];
                if (!isset($map[$dt])) $map[$dt] = ['total' => 0, 'done' => 0];
                $map[$dt]['total'] += (int)$r['cnt'];
                if ((int)$r['completed'] === 1) $map[$dt]['done'] += (int)$r['cnt'];
            }
            $stats = $map;
            $labels = array_keys($map);
        } else {
            // week
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $rows = EasyTodoTask::countByWeek($userId, $weekStart);
            $map = [];
            foreach ($rows as $r) {
                $dt = $r['dt'];
                if (!isset($map[$dt])) $map[$dt] = ['total' => 0, 'done' => 0];
                $map[$dt]['total'] += (int)$r['cnt'];
                if ((int)$r['completed'] === 1) $map[$dt]['done'] += (int)$r['cnt'];
            }
            $stats = $map;
            $labels = array_keys($map);
        }

        $this->render('easytodo/statistics', [
            'pageTitle' => '统计看板',
            'view' => $view,
            'stats' => $stats,
            'labels' => $labels,
            'todayWorkSessions' => EasyTodoPomodoro::countTodayWorkSessions($userId),
            'todayWorkMinutes' => EasyTodoPomodoro::sumTodayWorkMinutes($userId),
        ]);
    }

    // ================================
    // AI日报/周报
    // ================================
    public function reports(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);

        $type = $_GET['type'] ?? 'daily';
        $aiEnabled = (bool)Config::get('ai.enabled', false);

        // 获取历史报告
        $reports = EasyTodoReport::listByUser($userId);

        $this->render('easytodo/reports', [
            'pageTitle' => 'AI ' . ($type === 'daily' ? '日报' : '周报'),
            'type' => $type,
            'reports' => $reports,
            'aiEnabled' => $aiEnabled,
        ]);
    }

    public function apiReports(): void
    {
        $userId = $this->requireLogin();
        $ledgerId = LedgerContext::requireActiveLedgerId($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'generate') {
                $type = $_POST['type'] ?? 'daily';
                $aiEnabled = (bool)Config::get('ai.enabled', false);

                if (!$aiEnabled) {
                    $this->json(['ok' => false, 'error' => 'AI功能未启用，请联系管理员']);
                }

                // 收集任务数据
                $targetDate = $type === 'daily'
                    ? date('Y-m-d')
                    : date('Y-m-d', strtotime('monday this week'));

                if ($type === 'daily') {
                    $tasks = EasyTodoTask::listByUser($userId, $ledgerId, $targetDate);
                } else {
                    $weekStart = date('Y-m-d', strtotime('monday this week'));
                    $tasks = EasyTodoTask::listByUser($userId, $ledgerId, null);
                    $tasks = array_filter($tasks, function($t) use ($weekStart) {
                        if (!$t['task_date']) return false;
                        return $t['task_date'] >= $weekStart && $t['task_date'] < date('Y-m-d', strtotime($weekStart . ' +7 day'));
                    });
                }

                $taskSummary = json_encode(['type' => $type, 'date' => $targetDate, 'count' => count($tasks), 'tasks' => $tasks], JSON_UNESCAPED_UNICODE);

                // 调用AI生成报告
                $prompt = $this->buildReportPrompt($type, $tasks);
                $reportContent = $this->callAi($prompt);

                if ($reportContent) {
                    $id = EasyTodoReport::create($userId, $ledgerId > 0 ? $ledgerId : null, $type, $reportContent, $taskSummary);
                    $this->json(['ok' => true, 'id' => $id, 'content' => $reportContent]);
                } else {
                    $this->json(['ok' => false, 'error' => 'AI生成失败']);
                }
                return;
            }

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                EasyTodoReport::delete($id, $userId);
                $this->json(['ok' => true]);
                return;
            }
        }
    }

    private function buildReportPrompt(string $type, array $tasks): string
    {
        $date = $type === 'daily' ? date('Y年m月d日') : '本周';
        $done = array_filter($tasks, fn($t) => !empty($t['completed']));
        $undone = array_filter($tasks, fn($t) => empty($t['completed']));

        $taskList = '';
        foreach ($tasks as $t) {
            $status = !empty($t['completed']) ? '[x]' : '[ ]';
            $date = $t['task_date'] ?? '';
            $taskList .= "{$status} {$t['title']} ({$date})\n";
        }

        return "请为 {$date} 生成待办任务总结报告。

今日/本周任务清单：
{$taskList}

已完成: " . count($done) . " / " . count($tasks) . "

请生成一份简洁的总结报告，包括：
1. 完成情况概览
2. 主要成果
3. 未完成任务及原因（如果有）
4. 明日/下周建议";
    }

    private function callAi(string $prompt): ?string
    {
        $apiBase = Config::get('ai.qclaw_api_url', 'http://127.0.0.1:5000/parse');
        $timeout = (int)Config::get('ai.timeout', 30);

        $payload = json_encode([
            'prompt' => $prompt,
            'max_tokens' => 1000,
        ], JSON_UNESCAPED_UNICODE);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($apiBase, false, $context);
        if ($response === false) return null;

        $data = json_decode($response, true);
        if (!$data) return null;

        return $data['text'] ?? $data['content'] ?? $data['result'] ?? null;
    }
}