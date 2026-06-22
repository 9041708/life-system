<?php
/**
 * 独立调度器进程入口
 * 
 * 由 bootstrap.php 在页面访问时自动拉起（后台常驻），不依赖外部 cron。
 * 通过心跳文件 + PID 检测保证唯一性，页面访问时若检测不到活进程会自动重启。
 * 
 * 一般不需要手动运行，系统会在首次页面访问时自动启动。
 */

declare(strict_types=1);

// 忽略客户端断开，保持后台运行
ignore_user_abort(true);
set_time_limit(0);

$baseDir = __DIR__;

// Composer autoload
$autoload = $baseDir . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
}

// PSR-4 自动加载
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative = substr($class, $len);
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Service\Config;
use App\Service\TaskScheduler;

Config::init($baseDir . '/config/config.php');

// 写入 PID 文件，供 bootstrap.php 检测存活
$pidFile = $baseDir . '/logs/scheduler.pid';
$pidDir = dirname($pidFile);
if (!is_dir($pidDir)) {
    @mkdir($pidDir, 0755, true);
}
@file_put_contents($pidFile, (string)getmypid());

// 注册退出清理
register_shutdown_function(function () use ($pidFile) {
    @unlink($pidFile);
});

// 启动调度器（内部是 while(true) 循环，永不退出）
$scheduler = TaskScheduler::getInstance();
$scheduler->start();
