<?php
/**
 * 系统定时任务处理器
 * 
 * 功能：
 * 1. 检查备份配置，到达执行时间时自动触发备份
 * 2. 清理过期备份文件
 * 3. 发送邮件通知
 * 
 * 触发方式：每次页面加载时自动检查（无外部依赖）
 */

require_once __DIR__ . '/src/Service/Config.php';
require_once __DIR__ . '/src/Service/Backup.php';
require_once __DIR__ . '/src/Service/Database.php';
require_once __DIR__ . '/src/Service/Mailer.php';

use App\Service\Config;
use App\Service\Backup;
use App\Service\Mailer;

// 防止重复执行（使用文件锁）
$lockFile = __DIR__ . '/runtime/task_runner.lock';
if (!is_dir(dirname($lockFile))) {
    @mkdir(dirname($lockFile), 0755, true);
}

$fp = @fopen($lockFile, 'w');
if ($fp && flock($fp, LOCK_EX | LOCK_NB)) {
    try {
        processBackupTask();
        flock($fp, LOCK_UN);
    } catch (\Throwable $e) {
        error_log('系统任务执行失败: ' . $e->getMessage());
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

/**
 * 处理备份任务
 */
function processBackupTask(): void
{
    $config = Config::get('backup', []);
    
    // 检查是否启用自动备份
    if (!($config['enabled'] ?? false)) {
        return;
    }
    
    $frequency = $config['frequency'] ?? 'daily';
    $executionDay = (int)($config['execution_day'] ?? 1);
    $executionTime = $config['execution_time'] ?? '02:00';
    
    // 检查是否到达执行时间
    if (!shouldRunBackup($frequency, $executionDay, $executionTime)) {
        return;
    }
    
    // 检查距离上次备份是否已超过周期
    $lastRunTime = $config['last_run_time'] ?? 0;
    $now = time();
    
    if (hasRunRecently($lastRunTime, $frequency, $now)) {
        return; // 最近已执行过，跳过
    }
    
    // 执行备份
    $result = Backup::performBackup();
    
    // 更新最后执行时间
    $config['last_run_time'] = $now;
    Config::set('backup', $config);
    
    // 发送邮件通知
    if ($result['success'] && ($config['email_notify'] ?? false)) {
        sendBackupNotificationEmail($result, $config);
    }
    
    // 记录日志
    $logFile = __DIR__ . '/runtime/backup_tasks.log';
    $logMsg = sprintf(
        "[%s] 备份任务执行: %s (大小: %s)\n",
        date('Y-m-d H:i:s'),
        $result['success'] ? '成功' : '失败',
        $result['formatted_size'] ?? '0B'
    );
    @file_put_contents($logFile, $logMsg, FILE_APPEND | LOCK_EX);
}

/**
 * 判断是否应该执行备份
 */
function shouldRunBackup(string $frequency, int $executionDay, string $executionTime): bool
{
    $now = new \DateTime();
    $currentTime = $now->format('H:i');
    $currentWeekday = (int)$now->format('N'); // 1-7 (周一-周日)
    $currentDay = (int)$now->format('j'); // 1-31
    
    // 检查时间是否匹配（允许前后15分钟误差）
    [$execHour, $execMin] = explode(':', $executionTime);
    [$currentHour, $currentMin] = explode(':', $currentTime);
    
    $execMinutes = (int)$execHour * 60 + (int)$execMin;
    $currentMinutes = (int)$currentHour * 60 + (int)$currentMin;
    $diffMinutes = abs($execMinutes - $currentMinutes);
    
    if ($diffMinutes > 15) {
        return false; // 不在执行时间窗口内
    }
    
    // 根据频率检查日期
    switch ($frequency) {
        case 'daily':
            return true; // 每天执行，时间匹配即可
            
        case 'weekly':
            return $currentWeekday === $executionDay; // 周几匹配
            
        case 'monthly':
            return $currentDay === $executionDay; // 几号匹配
            
        default:
            return false;
    }
}

/**
 * 检查最近是否已执行过备份
 */
function hasRunRecently(int $lastRunTime, string $frequency, int $now): bool
{
    if ($lastRunTime <= 0) {
        return false; // 从未执行过
    }
    
    $intervalSeconds = $now - $lastRunTime;
    
    switch ($frequency) {
        case 'daily':
            return $intervalSeconds < 86400; // 24小时内
            
        case 'weekly':
            return $intervalSeconds < 604800; // 7天内
            
        case 'monthly':
            return $intervalSeconds < 2592000; // 30天内
            
        default:
            return false;
    }
}

/**
 * 发送备份通知邮件（使用系统 Mailer 类）
 */
function sendBackupNotificationEmail(array $result, array $config): void
{
    $notifyEmail = $config['notify_email'] ?? '';
    if (empty($notifyEmail) || !filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) {
        error_log('邮件发送失败: 邮箱地址无效 - ' . $notifyEmail);
        return;
    }
    
    $subject = '【' . ($config['app_name'] ?? '三石记账') . '】数据备份完成通知';
    
    $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        .container { max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; }
        .header { background: #0d6efd; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .info-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .info-table td { padding: 8px; border-bottom: 1px solid #dee2e6; }
        .info-table td:first-child { font-weight: bold; color: #6c757d; width: 30%; }
        .footer { padding: 15px; text-align: center; color: #6c757d; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>数据备份完成</h2>
        </div>
        <div class="content">
            <p>您好，</p>
            <p>您的记账系统数据备份已成功完成，以下是备份详情：</p>
            
            <table class="info-table">
                <tr>
                    <td>备份名称</td>
                    <td>{$result['backup_name']}</td>
                </tr>
                <tr>
                    <td>备份时间</td>
                    <td>{$result['timestamp']}</td>
                </tr>
                <tr>
                    <td>备份大小</td>
                    <td>{$result['formatted_size']}</td>
                </tr>
                <tr>
                    <td>备份状态</td>
                    <td style="color: #198754; font-weight: bold;">成功</td>
                </tr>
            </table>
            
            <p>您可以登录系统查看详细的备份记录。</p>
        </div>
        <div class="footer">
            <p>此邮件由系统自动发送，请勿回复</p>
            <p>© 三石记账</p>
        </div>
    </div>
</body>
</html>
HTML;
    
    // 使用系统 Mailer 类发送邮件
    $sent = \App\Service\Mailer::send($notifyEmail, '三石记账用户', $subject, $body);
    
    // 记录发送结果
    $logFile = __DIR__ . '/runtime/mail.log';
    $logMsg = sprintf(
        "[%s] 邮件发送%s: to=%s, subject=%s\n",
        date('Y-m-d H:i:s'),
        $sent ? '成功' : '失败',
        $notifyEmail,
        $subject
    );
    @file_put_contents($logFile, $logMsg, FILE_APPEND | LOCK_EX);
    
    if (!$sent) {
        error_log('邮件发送失败: to=' . $notifyEmail . ', subject=' . $subject);
    }
}

// 如果在命令行运行，直接执行
if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
    echo "开始执行系统任务...\n";
    processBackupTask();
    echo "任务执行完成.\n";
}
