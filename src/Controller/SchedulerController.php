<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\TaskScheduler;
use App\Service\Database;

class SchedulerController
{
    private function requireLogin(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /public/index.php?route=login');
            exit;
        }
        if (($_SESSION['user_role'] ?? 'user') !== 'admin') {
            header('Location: /public/index.php?route=landing');
            exit;
        }
    }

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/layout_main.php';
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function index(): void
    {
        $this->requireLogin();
        $_SESSION['current_page_title'] = '定时任务';

        $scheduler = TaskScheduler::getInstance();
        $status = $scheduler->getStatus();
        $history = $scheduler->getExecutionHistory(30);

        $tasks = $this->collectAllTasks($status);

        $this->render('scheduler/index', [
            'status' => $status,
            'history' => $history,
            'tasks' => $tasks,
        ]);
    }

    public function api(): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => '无效请求']);
        }

        $action = $_POST['action'] ?? '';
        $scheduler = TaskScheduler::getInstance();

        switch ($action) {
            case 'run_task':
                $taskName = trim($_POST['task_name'] ?? '');
                if ($taskName === '') {
                    $this->json(['ok' => false, 'error' => '任务名称不能为空']);
                }
                // 论坛子任务统一映射到 forum_cron
                if (in_array($taskName, ['forum_signin', 'forum_reply', 'forum_notice'], true)) {
                    $taskName = 'forum_cron';
                }
                // 论坛任务可能涉及HTTP请求，增加超时
                if ($taskName === 'forum_cron') {
                    @set_time_limit(120);
                }
                // 数据备份任务：直接执行
                if ($taskName === 'data_backup') {
                    try {
                        require_once dirname(__DIR__, 2) . '/process_system_tasks.php';
                        processBackupTask();
                        // 写入调度器日志
                        $logFile = dirname(__DIR__, 2) . '/logs/task_scheduler.log';
                        $logDir = dirname($logFile);
                        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
                        $logEntry = json_encode([
                            'type' => 'execution', 'task_name' => 'data_backup',
                            'start_time' => time(), 'end_time' => time(), 'duration' => 0,
                            'formatted_start_time' => date('Y-m-d H:i:s'),
                            'success' => true, 'result' => '备份任务已执行', 'error' => null,
                        ], JSON_UNESCAPED_UNICODE);
                        @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $logEntry . "\n", FILE_APPEND | LOCK_EX);
                        $this->json(['success' => true, 'result' => ['message' => '备份任务已执行']]);
                    } catch (\Throwable $e) {
                        $logFile = dirname(__DIR__, 2) . '/logs/task_scheduler.log';
                        $logEntry = json_encode([
                            'type' => 'execution', 'task_name' => 'data_backup',
                            'start_time' => time(), 'end_time' => time(), 'duration' => 0,
                            'formatted_start_time' => date('Y-m-d H:i:s'),
                            'success' => false, 'result' => '', 'error' => $e->getMessage(),
                        ], JSON_UNESCAPED_UNICODE);
                        @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $logEntry . "\n", FILE_APPEND | LOCK_EX);
                        $this->json(['success' => false, 'error' => $e->getMessage()]);
                    }
                }
                $result = $scheduler->runTaskNow($taskName);
                $this->json($result);
                break;

            case 'refresh':
                $status = $scheduler->getStatus();
                $history = $scheduler->getExecutionHistory(30);
                $tasks = $this->collectAllTasks($status);
                $this->json(['ok' => true, 'status' => $status, 'history' => $history, 'tasks' => $tasks]);
                break;

            default:
                $this->json(['ok' => false, 'error' => '未知操作']);
        }
    }

    private function collectAllTasks(array $schedulerStatus): array
    {
        $tasks = [];
        $now = time();

        // 1. 数据备份任务
        $backupConfig = Config::get('backup', []);
        $backupEnabled = !empty($backupConfig['enabled']);
        $frequency = $backupConfig['frequency'] ?? 'daily';
        $execTime = $backupConfig['execution_time'] ?? '02:00';
        $execDay = (int)($backupConfig['execution_day'] ?? 1);
        $freqLabels = ['daily' => '每天', 'weekly' => '每周', 'monthly' => '每月'];
        $weekLabels = [1 => '一', 2 => '二', 3 => '三', 4 => '四', 5 => '五', 6 => '六', 7 => '日'];
        $scheduleDesc = ($freqLabels[$frequency] ?? $frequency);
        if ($frequency === 'weekly') {
            $scheduleDesc .= '周' . ($weekLabels[$execDay] ?? $execDay);
        } elseif ($frequency === 'monthly') {
            $scheduleDesc .= $execDay . '号';
        }
        $scheduleDesc .= ' ' . substr($execTime, 0, 5);

        $tasks[] = [
            'name' => 'data_backup',
            'description' => '数据自动备份',
            'source' => '系统配置（数据备份页面）',
            'enabled' => $backupEnabled,
            'schedule' => $scheduleDesc,
            'next_run' => $this->calcNextBackupTime($frequency, $execDay, $execTime),
            'type' => 'backup',
            'runnable' => true,
        ];

        // 2. 论坛助手 — 从数据库读取实际账号配置
        try {
            $pdo = Database::getConnection();
            $colCheck = $pdo->query("SHOW COLUMNS FROM forum_accounts LIKE 'last_reply'")->fetch();
            $hasLastReply = (bool)$colCheck;
            $lrExpr = $hasLastReply ? '`last_reply`' : 'NULL';

            $stmt = $pdo->query("SELECT id, forum_name, username,
                enable_signin, signin_time, last_signin,
                enable_autoreply, reply_time, reply_interval, auto_reply_interval, {$lrExpr} AS last_reply,
                enable_notice, notice_interval, last_notice_check
                FROM forum_accounts ORDER BY id DESC");
            $accounts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $accounts = [];
        }

        $signinAccounts = [];
        $replyAccounts = [];
        $noticeAccounts = [];
        foreach ($accounts as $acc) {
            if (!empty($acc['enable_signin'])) $signinAccounts[] = $acc;
            if (!empty($acc['enable_autoreply'])) $replyAccounts[] = $acc;
            if (!empty($acc['enable_notice'])) $noticeAccounts[] = $acc;
        }

        // 签到任务
        $signinDetails = [];
        $signinNextRun = null;
        foreach ($signinAccounts as $acc) {
            $t = substr($acc['signin_time'], 0, 5);
            $signinDetails[] = "{$acc['forum_name']}({$acc['username']}) {$t}";
            $nextSignin = $this->calcNextSigninTime($t, $acc['last_signin']);
            if ($signinNextRun === null || $nextSignin < $signinNextRun) {
                $signinNextRun = $nextSignin;
            }
        }
        $tasks[] = [
            'name' => 'forum_signin',
            'description' => '论坛自动签到',
            'source' => '工具箱 → 论坛助手',
            'enabled' => !empty($signinAccounts),
            'schedule' => count($signinAccounts) . ' 个账号',
            'detail' => !empty($signinDetails) ? implode('；', $signinDetails) : '暂无启用签到的账号',
            'next_run' => $signinNextRun ? date('Y-m-d H:i', $signinNextRun) : '-',
            'type' => 'forum',
            'runnable' => true,
        ];

        // 回帖任务
        $replyDetails = [];
        $replyNextRun = null;
        foreach ($replyAccounts as $acc) {
            $startT = substr($acc['reply_time'], 0, 5);
            $interval = (int)($acc['auto_reply_interval'] ?? 30);
            $replyDetails[] = "{$acc['forum_name']}({$acc['username']}) 起始{$startT} 每{$interval}分钟";
            $nextReply = $this->calcNextReplyTime($startT, $interval, $acc['last_reply'] ?? null);
            if ($replyNextRun === null || $nextReply < $replyNextRun) {
                $replyNextRun = $nextReply;
            }
        }
        $tasks[] = [
            'name' => 'forum_reply',
            'description' => '论坛自动回帖',
            'source' => '工具箱 → 论坛助手',
            'enabled' => !empty($replyAccounts),
            'schedule' => count($replyAccounts) . ' 个账号',
            'detail' => !empty($replyDetails) ? implode('；', $replyDetails) : '暂无启用回帖的账号',
            'next_run' => $replyNextRun ? date('Y-m-d H:i', $replyNextRun) : '-',
            'type' => 'forum',
            'runnable' => true,
        ];

        // 通知检查任务
        $noticeDetails = [];
        $noticeNextRun = null;
        foreach ($noticeAccounts as $acc) {
            $interval = (int)($acc['notice_interval'] ?? 15);
            $noticeDetails[] = "{$acc['forum_name']}({$acc['username']}) 每{$interval}分钟";
            $lastCheck = $acc['last_notice_check'] ?? null;
            if ($lastCheck) {
                $nextNotice = strtotime($lastCheck) + ($interval * 60);
            } else {
                $nextNotice = $now;
            }
            if ($nextNotice < $now) $nextNotice = $now;
            if ($noticeNextRun === null || $nextNotice < $noticeNextRun) {
                $noticeNextRun = $nextNotice;
            }
        }
        $tasks[] = [
            'name' => 'forum_notice',
            'description' => '论坛通知检查',
            'source' => '工具箱 → 论坛助手',
            'enabled' => !empty($noticeAccounts),
            'schedule' => '按账号配置间隔',
            'detail' => !empty($noticeDetails) ? implode('；', $noticeDetails) : '暂无启用通知的账号',
            'next_run' => $noticeNextRun ? date('Y-m-d H:i', $noticeNextRun) : '-',
            'type' => 'forum',
            'runnable' => true,
        ];

        // 从日志文件读取最近执行记录，按任务名分组取最后一次
        $logFile = dirname(__DIR__, 2) . '/logs/task_scheduler.log';
        $lastByTask = [];
        if (file_exists($logFile)) {
            $lines = $this->tailFile($logFile, 100);
            foreach ($lines as $line) {
                $line = trim($line);
                // 去掉 writeLog 添加的时间戳前缀: [2026-05-30 03:07:47]
                if (preg_match('/^\[.+?\]\s*(.*)$/s', $line, $m)) {
                    $line = $m[1];
                }
                $data = json_decode($line, true);
                if ($data && isset($data['type']) && $data['type'] === 'execution' && isset($data['task_name'])) {
                    $name = $data['task_name'];
                    if (!isset($lastByTask[$name]) || ($data['start_time'] ?? 0) > ($lastByTask[$name]['start_time'] ?? 0)) {
                        $lastByTask[$name] = $data;
                    }
                }
            }
        }

        // cron.php 写入的记录也读取（task_name=forum_cron，视为所有论坛子任务的执行）
        $forumCronLast = $lastByTask['forum_cron'] ?? null;

        foreach ($tasks as &$t) {
            // 优先匹配精确任务名
            if (isset($lastByTask[$t['name']])) {
                $rec = $lastByTask[$t['name']];
                $t['last_run'] = date('Y-m-d H:i:s', $rec['start_time'] ?? 0);
                $t['last_result'] = ($rec['success'] ?? false) ? 'success' : 'error';
            }
            // 论坛子任务降级使用 forum_cron 的记录
            elseif ($forumCronLast && in_array($t['name'], ['forum_signin', 'forum_reply', 'forum_notice'])) {
                $t['last_run'] = date('Y-m-d H:i:s', $forumCronLast['start_time'] ?? 0);
                $t['last_result'] = ($forumCronLast['success'] ?? false) ? 'success' : 'error';
            }
        }
        unset($t);

        return $tasks;
    }

    private function calcNextBackupTime(string $frequency, int $execDay, string $execTime): string
    {
        [$h, $m] = explode(':', $execTime);
        $now = new \DateTime();
        $next = new \DateTime();
        $next->setTime((int)$h, (int)$m, 0);

        if ($next <= $now) {
            $next->modify('+1 day');
        }

        if ($frequency === 'weekly') {
            $targetDow = $execDay;
            while ((int)$next->format('N') !== $targetDow) {
                $next->modify('+1 day');
            }
        } elseif ($frequency === 'monthly') {
            $next->setDate((int)$next->format('Y'), (int)$next->format('n'), $execDay);
            if ($next <= $now) {
                $next->modify('+1 month');
                $next->setDate((int)$next->format('Y'), (int)$next->format('n'), $execDay);
            }
        }

        return $next->format('Y-m-d H:i');
    }

    private function calcNextSigninTime(string $timeHHMM, ?string $lastSignin): int
    {
        $now = new \DateTime();
        $today = $now->format('Y-m-d');
        $target = new \DateTime($today . ' ' . $timeHHMM . ':00');

        $lastDate = $lastSignin ? substr($lastSignin, 0, 10) : '';
        if ($lastDate === $today) {
            $target->modify('+1 day');
        } elseif ($target <= $now) {
            $target->modify('+1 day');
        }

        return $target->getTimestamp();
    }

    private function calcNextReplyTime(string $startHHMM, int $intervalMinutes, ?string $lastReply): int
    {
        $now = new \DateTime();
        $today = $now->format('Y-m-d');
        $startToday = new \DateTime($today . ' ' . $startHHMM . ':00');

        if ($now < $startToday) {
            return $startToday->getTimestamp();
        }

        if ($lastReply) {
            $lastTs = new \DateTime($lastReply);
            $next = clone $lastTs;
            $next->modify('+' . $intervalMinutes . ' minutes');
            if ($next > $now) {
                return $next->getTimestamp();
            }
            return $now->getTimestamp();
        }

        return $now->getTimestamp();
    }

    private function tailFile(string $filePath, int $lines = 100): array
    {
        $result = [];
        $handle = @fopen($filePath, 'r');
        if (!$handle) return $result;
        $buffer = [];
        while (($line = fgets($handle)) !== false) {
            $buffer[] = $line;
            if (count($buffer) > $lines) array_shift($buffer);
        }
        fclose($handle);
        return $buffer;
    }
}
