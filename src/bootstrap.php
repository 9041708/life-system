<?php

declare(strict_types=1);

// Composer autoload（如果已安装依赖）
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
	require $autoload;
}

// 简单 PSR-4 自动加载，用于在未执行 composer install 时加载 App 命名空间下的类
spl_autoload_register(function (string $class): void {
	$prefix = 'App\\';
	$len = strlen($prefix);
	if (strncmp($prefix, $class, $len) !== 0) {
		return; // 非 App 命名空间，忽略
	}
	$relative = substr($class, $len);
	$file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
	if (file_exists($file)) {
		require $file;
	}
});

use App\Service\Config;
use App\Service\LicenseClient;
use App\Service\TaskScheduler;

// 加载配置
Config::init(__DIR__ . '/../config/config.php');

// 自动数据库迁移
try {
	$pdo = \App\Service\Database::getConnection();
	$col1 = $pdo->query("SHOW COLUMNS FROM forum_accounts LIKE 'last_reply'")->fetch();
	if (!$col1) {
		$pdo->exec("ALTER TABLE forum_accounts ADD COLUMN last_reply DATETIME DEFAULT NULL COMMENT '上次自动回帖时间' AFTER last_notice_check");
	}
	$col2 = $pdo->query("SHOW COLUMNS FROM forum_accounts LIKE 'auto_reply_interval'")->fetch();
	if (!$col2) {
		$pdo->exec("ALTER TABLE forum_accounts ADD COLUMN auto_reply_interval INT DEFAULT 30 COMMENT '自动回帖间隔(分钟)' AFTER reply_interval");
	}
	$col3 = $pdo->query("SHOW COLUMNS FROM forum_accounts LIKE 'notice_interval'")->fetch();
	if (!$col3) {
		$pdo->exec("ALTER TABLE forum_accounts ADD COLUMN notice_interval INT DEFAULT 15 COMMENT '通知检查间隔(分钟)' AFTER enable_notice");
	}
	$col4 = $pdo->query("SHOW COLUMNS FROM nav_bookmarks LIKE 'show_on_home'")->fetch();
	if (!$col4) {
		$pdo->exec("ALTER TABLE nav_bookmarks ADD COLUMN show_on_home TINYINT(1) DEFAULT 0 COMMENT '是否在首页显示' AFTER sort_order");
	}
	$col5 = $pdo->query("SHOW TABLES LIKE 'miniapps'")->fetch();
	if (!$col5) {
		$pdo->exec("CREATE TABLE IF NOT EXISTS miniapps (
			id INT AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(100) NOT NULL DEFAULT '' COMMENT '小程序名称',
			qrcode_path VARCHAR(500) NOT NULL DEFAULT '' COMMENT '小程序码图片路径',
			sort_order INT NOT NULL DEFAULT 0 COMMENT '排序',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小程序配置'");
	}
	$col6 = $pdo->query("SHOW COLUMNS FROM forum_accounts LIKE 'enable_mention_reply'")->fetch();
	if (!$col6) {
		$pdo->exec("ALTER TABLE forum_accounts ADD COLUMN enable_mention_reply TINYINT(1) DEFAULT 0 COMMENT '@提及自动回复' AFTER enable_notice");
		$pdo->exec("ALTER TABLE forum_accounts ADD COLUMN mention_reply_mode VARCHAR(20) DEFAULT 'ai' COMMENT '@提及回复模式: ai/random/custom' AFTER enable_mention_reply");
	}
	$col7 = $pdo->query("SHOW COLUMNS FROM forum_accounts LIKE 'signin_url'")->fetch();
	if (!$col7) {
		$pdo->exec("ALTER TABLE forum_accounts ADD COLUMN signin_url VARCHAR(500) DEFAULT '' COMMENT '签到页面链接' AFTER signin_time");
	}
	$col8 = $pdo->query("SHOW COLUMNS FROM forum_accounts LIKE 'signin_params'")->fetch();
	if (!$col8) {
		$pdo->exec("ALTER TABLE forum_accounts ADD COLUMN signin_params TEXT COMMENT '签到参数' AFTER signin_url");
	}
	$col9 = $pdo->query("SHOW COLUMNS FROM forum_accounts LIKE 'reply_interval'")->fetch();
	if (!$col9) {
		$pdo->exec("ALTER TABLE forum_accounts ADD COLUMN reply_interval INT DEFAULT 10 COMMENT '手动回帖间隔(秒)' AFTER reply_time");
	}
	$col10 = $pdo->query("SHOW COLUMNS FROM forum_accounts LIKE 'last_mention_reply'")->fetch();
	if (!$col10) {
		$pdo->exec("ALTER TABLE forum_accounts ADD COLUMN last_mention_reply DATETIME DEFAULT NULL COMMENT '上次@提及回复时间' AFTER last_notice_check");
	}
	$col11 = $pdo->query("SHOW COLUMNS FROM forum_accounts LIKE 'enable_follow_up'")->fetch();
	if (!$col11) {
		$pdo->exec("ALTER TABLE forum_accounts ADD COLUMN enable_follow_up TINYINT(1) DEFAULT 0 COMMENT '跟进回复开关' AFTER enable_mention_reply");
	}
	$col12 = $pdo->query("SHOW COLUMNS FROM forum_accounts LIKE 'enable_bonus'")->fetch();
	if (!$col12) {
		$pdo->exec("ALTER TABLE forum_accounts ADD COLUMN enable_bonus TINYINT(1) DEFAULT 0 COMMENT '自动领取彩蛋' AFTER enable_follow_up");
	}

	// Resume multi-support migration
	$resumeNameCol = $pdo->query("SHOW COLUMNS FROM resume_data LIKE 'name'")->fetch();
	if (!$resumeNameCol) {
		$pdo->exec("ALTER TABLE resume_data ADD COLUMN name VARCHAR(100) DEFAULT '未命名简历' COMMENT '简历名称' AFTER user_id");
	}
	$resumeCreatedCol = $pdo->query("SHOW COLUMNS FROM resume_data LIKE 'created_at'")->fetch();
	if (!$resumeCreatedCol) {
		$pdo->exec("ALTER TABLE resume_data ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间' AFTER template");
	}
	// Drop unique key on user_id to allow multiple resumes per user
	try {
		$ukCheck = $pdo->query("SHOW INDEX FROM resume_data WHERE Key_name = 'uk_user'")->fetch();
		if ($ukCheck) {
			$pdo->exec("ALTER TABLE resume_data DROP INDEX uk_user");
		}
	} catch (\Throwable $eIgnored) {}
	// Add index on user_id for query performance
	try {
		$idxCheck = $pdo->query("SHOW INDEX FROM resume_data WHERE Key_name = 'idx_user'")->fetch();
		if (!$idxCheck) {
			$pdo->exec("ALTER TABLE resume_data ADD INDEX idx_user (user_id)");
		}
	} catch (\Throwable $eIgnored) {}
} catch (\Throwable $e) {
	// 静默失败，不影响正常请求
}

// 如启用授权客户端，则在每次请求入口执行授权自检与联机调度
if (Config::get('license.client_enabled', false)) {
	LicenseClient::enforce();
}

// 启动内置定时任务调度器
if (Config::get('scheduler.enabled', false) && !defined('SCHEDULER_STARTED')) {
	define('SCHEDULER_STARTED', true);

	$scheduler = TaskScheduler::getInstance();

	if (function_exists('pcntl_fork')) {
		// Unix/Linux 系统：通过心跳检查是否需要重启调度器
		$needStart = true;
		$statusFile = __DIR__ . '/../logs/task_scheduler.status';
		if (file_exists($statusFile)) {
			$status = json_decode(@file_get_contents($statusFile) ?: '', true);
			if (is_array($status) && !empty($status['is_running'])) {
				$pid = $status['pid'] ?? null;
				$lastHeartbeat = $status['last_heartbeat'] ?? null;
				$checkInterval = $status['check_interval'] ?? 60;
				$alive = false;
				if ($pid !== null && function_exists('posix_kill')) {
					$alive = @posix_kill($pid, 0);
				}
				if (!$alive && $lastHeartbeat !== null) {
					$alive = (time() - $lastHeartbeat) <= max(120, $checkInterval * 2);
				}
				if ($alive) {
					$needStart = false;
				}
			}
		}
		if ($needStart) {
			$pid = pcntl_fork();
			if ($pid === -1) {
				error_log('无法创建调度器进程');
			} elseif ($pid === 0) {
				$scheduler->start();
				exit(0);
			}
		}
	} else {
		// 无 pcntl_fork 环境（群晖 NAS / Windows 等）
		// 检查后台进程是否存活，若不存活则自动拉起
		$runnerFile = __DIR__ . '/../scheduler_runner.php';
		$pidFile = __DIR__ . '/../logs/scheduler.pid';
		$statusFile = __DIR__ . '/../logs/task_scheduler.status';
		$needStart = true;

		// 方式1: 通过 PID 文件检测
		if (file_exists($pidFile)) {
			$pid = (int)@file_get_contents($pidFile);
			if ($pid > 0) {
				if (PHP_OS_FAMILY === 'Windows') {
					// Windows: 通过 tasklist 检测进程
					$output = [];
					@exec("tasklist /FI \"PID eq {$pid}\" /NH 2>nul", $output);
					$alive = !empty($output) && stripos(implode('', $output), 'php') !== false;
				} else {
					// Linux: 通过 /proc 或 posix_kill 检测
					$alive = file_exists("/proc/{$pid}");
				}
				if ($alive) {
					$needStart = false;
				}
			}
		}

		// 方式2: 通过心跳文件兜底
		if ($needStart && file_exists($statusFile)) {
			$status = json_decode(@file_get_contents($statusFile) ?: '', true);
			if (is_array($status) && !empty($status['last_heartbeat'])) {
				if ((time() - (int)$status['last_heartbeat']) <= 180) {
					$needStart = false;
				}
			}
		}

		if ($needStart && file_exists($runnerFile)) {
			$phpBin = PHP_BINARY;
			$logFile = __DIR__ . '/../logs/scheduler_spawn.log';
			$spawned = false;

			// exec() 可用时，拉起独立后台进程
			if (function_exists('exec')) {
				if (PHP_OS_FAMILY === 'Windows') {
					$cmd = 'start "" /B "' . $phpBin . '" "' . $runnerFile . '" >nul 2>&1';
					@exec($cmd, $output, $ret);
					$spawned = true;
				} else {
					$cmd = 'nohup "' . $phpBin . '" "' . $runnerFile . '" >> "' . $logFile . '" 2>&1 &';
					@exec($cmd, $output, $ret);
					$spawned = true;
				}
				@file_put_contents($logFile,
					'[' . date('Y-m-d H:i:s') . '] 调度器进程已拉起' . "\n",
					FILE_APPEND
				);
			}

			// exec() 不可用或拉起失败，记录日志但不同步执行（避免超时）
			if (!$spawned) {
				@file_put_contents($logFile,
					'[' . date('Y-m-d H:i:s') . '] exec() 不可用，跳过后台进程拉起，请手动配置群晖计划任务' . "\n",
					FILE_APPEND
				);
			}
	}
}

// 论坛助手自动任务：每次页面访问都进行非阻塞到期检查（不依赖外部cron）
if (!defined('FORUM_AUTO_SHUTDOWN')) {
	define('FORUM_AUTO_SHUTDOWN', true);
	register_shutdown_function(function () {
		try {
			$lockFile = __DIR__ . '/../runtime/forum_auto.lock';
			$lockDir = dirname($lockFile);
			if (!is_dir($lockDir)) {
				@mkdir($lockDir, 0755, true);
			}
			$fp = @fopen($lockFile, 'w');
			if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
				if ($fp) fclose($fp);
				return;
			}
			// 确保请求完成后才执行（避免阻塞用户）
			if (function_exists('fastcgi_finish_request')) {
				fastcgi_finish_request();
			}
			// 忽略用户中止
			ignore_user_abort(true);
			set_time_limit(120);

			TaskScheduler::runForumCron();

			flock($fp, LOCK_UN);
			fclose($fp);
		} catch (\Throwable $e) {
			// 静默失败
		}
	});
}
}


