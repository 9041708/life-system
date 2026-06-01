<?php
/**
 * 论坛助手定时任务脚本
 * 
 * 使用方式：
 * 1. 系统Cron: 每分钟执行一次
 *    * * * * * php /path/to/tools/cron_forum.php
 * 
 * 2. HTTP触发:
 *    GET /public/index.php?route=toolbox-forum-cron&token=YOUR_TOKEN
 */

declare(strict_types=1);

// 如果是通过HTTP访问，走web入口
if (isset($_SERVER['REQUEST_METHOD'])) {
    // 由 public/index.php 路由处理
    return;
}

// CLI模式
require __DIR__ . '/../src/bootstrap.php';

use App\Service\DiscuzService;
use App\Model\ForumAccount;
use App\Model\ForumActionLog;

echo "[" . date('Y-m-d H:i:s') . "] 论坛助手定时任务开始执行\n";

// 清理3天前的日志
$cleaned = ForumActionLog::cleanOldLogs(3);
if ($cleaned > 0) {
    echo "清理了 {$cleaned} 条旧日志\n";
}

// 执行签到任务
$signinAccounts = ForumAccount::getNeedExecute('signin');
echo "找到 " . count($signinAccounts) . " 个需要签到的账号\n";

foreach ($signinAccounts as $account) {
    echo "执行签到: [{$account['forum_name']}] {$account['username']}\n";
    try {
        $service = new DiscuzService((int)$account['user_id'], $account);
        
        // 登录
        $loginResult = $service->login($account['username'], $account['password']);
        if (!$loginResult['ok']) {
            ForumActionLog::create(
                (int)$account['user_id'],
                (int)$account['id'],
                'error',
                $loginResult['error']
            );
            echo "  登录失败: {$loginResult['error']}\n";
            continue;
        }

        // 签到
        $result = $service->signin();
        $logType = $result['ok'] ? 'signin' : 'error';
        ForumActionLog::create(
            (int)$account['user_id'],
            (int)$account['id'],
            $logType,
            $result['message'] ?? $result['error']
        );
        echo "  签到结果: " . ($result['message'] ?? $result['error']) . "\n";
    } catch (\Throwable $e) {
        ForumActionLog::create(
            (int)$account['user_id'],
            (int)$account['id'],
            'error',
            $e->getMessage()
        );
        echo "  异常: {$e->getMessage()}\n";
    }
}

// 执行回帖任务
$replyAccounts = ForumAccount::getNeedExecute('autoreply');
echo "找到 " . count($replyAccounts) . " 个需要回帖的账号\n";

foreach ($replyAccounts as $account) {
    echo "执行回帖: [{$account['forum_name']}] {$account['username']}\n";
    try {
        $service = new DiscuzService((int)$account['user_id'], $account);
        
        // 登录
        $loginResult = $service->login($account['username'], $account['password']);
        if (!$loginResult['ok']) {
            ForumActionLog::create(
                (int)$account['user_id'],
                (int)$account['id'],
                'error',
                $loginResult['error']
            );
            echo "  登录失败: {$loginResult['error']}\n";
            continue;
        }

        // 获取未回复的帖子
        $thread = $service->getUnrepliedThread();
        if (!$thread) {
            ForumActionLog::create(
                (int)$account['user_id'],
                (int)$account['id'],
                'error',
                '没有可回复的帖子（可能都已回复过）'
            );
            echo "  没有可回复的帖子\n";
            continue;
        }

        $tid = (int)$thread['tid'];

        // 获取回帖内容
        $replyMode = $account['reply_mode'] ?? 'random';
        $customReply = $account['custom_reply'] ?? '';
        $message = '';
        if ($replyMode === 'ai' || $replyMode === 'smart') {
            $threadContent = $service->getThreadContent($tid);
            if ($threadContent['ok']) {
                $message = DiscuzService::generateAiReply($threadContent['title'], $threadContent['content']);
                if (empty($message)) {
                    $message = DiscuzService::generateAiReply($threadContent['title'], $threadContent['content'], true);
                }
            }
        }
        if (empty($message)) {
            $message = DiscuzService::getReplyContent($replyMode, $customReply);
        }
        $message .= "\n" . ($account['ai_reply_flag'] ?? '[AI回帖]');

        // 回帖
        $result = $service->reply($tid, $message);
        $logType = $result['ok'] ? 'reply' : 'error';
        $target = $result['title'] ?? "帖子#$tid";
        ForumActionLog::create(
            (int)$account['user_id'],
            (int)$account['id'],
            $logType,
            $result['message'] ?? $result['error'],
            $target
        );
        echo "  回帖结果: " . ($result['message'] ?? $result['error']) . " (帖子: $target)\n";
    } catch (\Throwable $e) {
        ForumActionLog::create(
            (int)$account['user_id'],
            (int)$account['id'],
            'error',
            $e->getMessage()
        );
        echo "  异常: {$e->getMessage()}\n";
    }
}

// 检查通知
$noticeAccounts = ForumAccount::getNeedCheckNotice();
echo "找到 " . count($noticeAccounts) . " 个需要检查通知的账号\n";

foreach ($noticeAccounts as $account) {
    echo "检查通知: [{$account['forum_name']}] {$account['username']}\n";
    try {
        $service = new DiscuzService((int)$account['user_id'], $account);
        
        // 登录
        $loginResult = $service->login($account['username'], $account['password']);
        if (!$loginResult['ok']) {
            ForumActionLog::create(
                (int)$account['user_id'],
                (int)$account['id'],
                'error',
                $loginResult['error']
            );
            echo "  登录失败: {$loginResult['error']}\n";
            continue;
        }

        // 获取通知
        $result = $service->getNotices();
        ForumActionLog::create(
            (int)$account['user_id'],
            (int)$account['id'],
            'notice',
            $result['message'] ?? $result['error'] ?? '检查通知'
        );
        echo "  通知结果: " . ($result['message'] ?? $result['error'] ?? '完成') . "\n";
    } catch (\Throwable $e) {
        ForumActionLog::create(
            (int)$account['user_id'],
            (int)$account['id'],
            'error',
            $e->getMessage()
        );
        echo "  异常: {$e->getMessage()}\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] 论坛助手定时任务执行完毕\n";
