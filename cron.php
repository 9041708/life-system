<?php
/**
 * 论坛助手定时任务入口（群晖计划任务 / Linux crontab 专用）
 * 
 * 群晖配置方法：
 *   控制面板 → 任务计划 → 新增 → 计划的任务 → 用户自定义的脚本
 *   计划：重复执行，每 5 分钟
 *   任务设置 → 运行命令：
 *     php /volume1/web/ssjizhang.cn_ceshi/cron.php
 * 
 * 确保后台调度器常驻（不需要页面访问触发）：
 *   触发任务 → 开机触发 → 运行命令：
 *     bash /volume1/web/ssjizhang.cn_ceshi/start_scheduler.sh
 */

declare(strict_types=1);
ignore_user_abort(true);

$baseDir = __DIR__;

// 自动加载
$autoload = $baseDir . '/vendor/autoload.php';
if (file_exists($autoload)) { require $autoload; }
spl_autoload_register(function (string $class): void {
    if (strncmp('App\\', $class, 4) !== 0) return;
    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (file_exists($file)) require $file;
});

use App\Service\Config;
use App\Model\ForumAccount;
use App\Model\ForumActionLog;
use App\Service\DiscuzService;

Config::init($baseDir . '/config/config.php');

$isCli = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
$results = ['signin' => 0, 'reply' => 0, 'notice' => 0, 'mention' => 0, 'errors' => []];
$cronStartTime = time();

// ===== 1. 签到 =====
try {
    $accounts = ForumAccount::getNeedExecute('signin');
    foreach ($accounts as $account) {
        try {
            $svc = new DiscuzService((int)$account['user_id'], $account);
            $login = $svc->login($account['username'], $account['password']);
            if (!$login['ok']) {
                ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'error', $login['error']);
                $results['errors'][] = "签到登录失败[{$account['forum_name']}]: {$login['error']}";
                continue;
            }
            $r = $svc->signin($account);
            ForumActionLog::create((int)$account['user_id'], (int)$account['id'], $r['ok'] ? 'signin' : 'error', $r['message'] ?? $r['error']);
            if ($r['ok']) ForumAccount::updateLastSignin((int)$account['id']);
            $results['signin']++;
        } catch (\Throwable $e) {
            ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'error', $e->getMessage());
            $results['errors'][] = "签到异常[{$account['forum_name']}]: " . $e->getMessage();
        }
    }
} catch (\Throwable $e) { $results['errors'][] = '签到任务异常: ' . $e->getMessage(); }

// ===== 2. 自动回帖 =====
try {
    $accounts = ForumAccount::getNeedExecute('autoreply');
    foreach ($accounts as $account) {
        try {
            $svc = new DiscuzService((int)$account['user_id'], $account);
            $login = $svc->login($account['username'], $account['password']);
            if (!$login['ok']) {
                ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'error', $login['error']);
                continue;
            }
            $thread = $svc->getUnrepliedThread();
            if (!$thread) { ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'error', '没有可回复的帖子'); continue; }
            $tid = (int)$thread['tid'];
            $msg = '';
            $threadContent = $svc->getThreadContent($tid);
            if ($threadContent['ok']) {
                $msg = DiscuzService::generateAiReply($threadContent['title'], $threadContent['content']);
                if (empty($msg)) {
                    $msg = DiscuzService::generateAiReply($threadContent['title'], $threadContent['content'], true);
                }
            }
            if (empty($msg)) continue;
            $r = $svc->reply($tid, $msg);
            ForumActionLog::create((int)$account['user_id'], (int)$account['id'], $r['ok'] ? 'reply' : 'error', $r['message'] ?? $r['error'], $r['title'] ?? "帖子#$tid");
            if ($r['ok']) ForumAccount::updateLastReply((int)$account['id']);
            $results['reply']++;
        } catch (\Throwable $e) {
            ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'error', $e->getMessage());
            $results['errors'][] = "回帖异常[{$account['forum_name']}]: " . $e->getMessage();
        }
    }
} catch (\Throwable $e) { $results['errors'][] = '回帖任务异常: ' . $e->getMessage(); }

// ===== 3. 通知检查 =====
try {
    $accounts = ForumAccount::getNeedCheckNotice();
    foreach ($accounts as $account) {
        try {
            $svc = new DiscuzService((int)$account['user_id'], $account);
            $login = $svc->login($account['username'], $account['password']);
            if (!$login['ok']) { ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'error', $login['error']); continue; }
            $r = $svc->getNotices();
            ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'notice', $r['message'] ?? '检查通知');
            $results['notice']++;
        } catch (\Throwable $e) {
            ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'error', $e->getMessage());
            $results['errors'][] = "通知异常[{$account['forum_name']}]: " . $e->getMessage();
        }
    }
} catch (\Throwable $e) { $results['errors'][] = '通知任务异常: ' . $e->getMessage(); }

// ===== 3.5 @提及自动回复 =====
try {
    $mentionAccounts = ForumAccount::getNeedMentionReply();
    foreach ($mentionAccounts as $account) {
        try {
            $svc = new DiscuzService((int)$account['user_id'], $account);
            $login = $svc->login($account['username'], $account['password']);
            if (!$login['ok']) continue;
            $r = $svc->handleMentionReplies();
            if (($r['replied'] ?? 0) > 0) {
                ForumActionLog::create((int)$account['user_id'], (int)$account['id'], 'reply', $r['message']);
            }
        } catch (\Throwable $e) {
            $results['errors'][] = "@提及回复异常[{$account['forum_name']}]: " . $e->getMessage();
        }
    }
} catch (\Throwable $e) { $results['errors'][] = '@提及回复任务异常: ' . $e->getMessage(); }

// ===== 4. 清理旧日志 =====
try { ForumActionLog::cleanOldLogs(3); } catch (\Throwable $e) {}

// ===== 5. 系统备份 =====
try {
    require_once $baseDir . '/process_system_tasks.php';
    processBackupTask();
} catch (\Throwable $e) {}

// 输出
$time = date('Y-m-d H:i:s');
$errCount = count($results['errors']);

// 写入调度器日志，让页面定时任务列表能显示执行记录
$logFile = $baseDir . '/logs/task_scheduler.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
$cronEndTime = time();
$logEntry = json_encode([
    'type' => 'execution',
    'task_name' => 'forum_cron',
    'start_time' => $cronStartTime,
    'end_time' => $cronEndTime,
    'duration' => $cronEndTime - $cronStartTime,
    'formatted_start_time' => date('Y-m-d H:i:s', $cronStartTime),
    'success' => $errCount === 0,
    'result' => json_encode($results, JSON_UNESCAPED_UNICODE),
], JSON_UNESCAPED_UNICODE);
@file_put_contents($logFile, $logEntry . "\n", FILE_APPEND | LOCK_EX);

if ($isCli) {
    echo "[{$time}] 签到:{$results['signin']} 回帖:{$results['reply']} 通知:{$results['notice']} 错误:{$errCount}\n";
    foreach ($results['errors'] as $e) echo "  ! {$e}\n";
} else {
    header('Content-Type: application/json');
    echo json_encode(['time' => $time, 'success' => $errCount === 0] + $results, JSON_UNESCAPED_UNICODE);
}
