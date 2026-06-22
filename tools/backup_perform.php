<?php
/**
 * 备份执行脚本
 * 用于定时任务执行备份
 * 
 * 使用方法：
 * php tools/backup_perform.php
 * 
 * Cron 配置示例：
 * 0 2 * * * /usr/bin/php /path/to/backup_perform.php >> /var/log/backup.log 2>&1
 */

require_once __DIR__ . '/../src/bootstrap.php';

use App\Service\Backup;
use App\Service\Mailer;

// 执行备份
$result = Backup::performBackup();

// 记录日志
$logFile = dirname(__DIR__) . '/backup_log.txt';
$logEntry = sprintf(
    "[%s] %s - Size: %s\n",
    date('Y-m-d H:i:s'),
    $result['success'] ? '✅ 成功' : '❌ 失败',
    $result['formatted_size'] ?? 'N/A'
);

if (!$result['success']) {
    $logEntry .= "  错误: " . json_encode($result['errors'] ?? []) . "\n";
}

@file_put_contents($logFile, $logEntry, FILE_APPEND);

// 输出结果
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n";

// 如果失败，发送告警邮件
if (!$result['success']) {
    try {
        $config = \App\Service\Config::get('mail', []);
        if ($config && !empty($config['admin_email'])) {
            Mailer::send(
                $config['admin_email'],
                '【告警】备份失败',
                '备份执行失败，错误信息：' . json_encode($result['errors'] ?? [], JSON_UNESCAPED_UNICODE)
            );
        }
    } catch (\Throwable $e) {
        error_log('发送告警邮件失败: ' . $e->getMessage());
    }
}

exit($result['success'] ? 0 : 1);
