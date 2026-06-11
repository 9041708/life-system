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
	$existingMigrationFlag = __DIR__ . '/../runtime/existing_migrated.flag';
	if (!file_exists($existingMigrationFlag)) {
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
	@file_put_contents($existingMigrationFlag, date('Y-m-d H:i:s'));
	}

	// TodayDoService 初始化（用独立标记，只跑一次）
	$todayDoFlag = __DIR__ . '/../runtime/todaydo_inited.flag';
	if (!file_exists($todayDoFlag)) {
		try {
			\App\Service\TodayDoService::initTables();
		} catch (\Throwable $e) {}
		@file_put_contents($todayDoFlag, date('Y-m-d H:i:s'));
	}

	// 正念+项目+知识库模块表（用文件缓存标记避免每次请求都检查）
	$migrationFlag = __DIR__ . '/../runtime/mindfulness_migrated.flag';
	if (!file_exists($migrationFlag)) {
		$tblMF1 = $pdo->query("SHOW TABLES LIKE 'mindfulness_checkins'")->fetch();
		if (!$tblMF1) {
			$pdo->exec("CREATE TABLE IF NOT EXISTS mindfulness_checkins (
			id INT AUTO_INCREMENT PRIMARY KEY,
			user_id INT NOT NULL,
			checkin_date DATE NOT NULL,
			score_change DECIMAL(5,1) DEFAULT 0.3,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY uk_user_date (user_id, checkin_date),
			INDEX idx_user_id (user_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='正念签到记录'");
	}
	$tblMF2 = $pdo->query("SHOW TABLES LIKE 'mindfulness_daily_records'")->fetch();
	if (!$tblMF2) {
		$pdo->exec("CREATE TABLE IF NOT EXISTS mindfulness_daily_records (
			id INT AUTO_INCREMENT PRIMARY KEY,
			user_id INT NOT NULL,
			record_date DATE NOT NULL,
			type ENUM('positive','negative') NOT NULL,
			item_name VARCHAR(100) NOT NULL,
			score_change DECIMAL(5,1) NOT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_user_date (user_id, record_date)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='正念每日正负念记录'");
	}
	$tblMF3 = $pdo->query("SHOW TABLES LIKE 'mindfulness_treasures'")->fetch();
	if (!$tblMF3) {
		$pdo->exec("CREATE TABLE IF NOT EXISTS mindfulness_treasures (
			id INT AUTO_INCREMENT PRIMARY KEY,
			user_id INT NOT NULL,
			content TEXT NOT NULL,
			ai_reply TEXT,
			sentiment ENUM('positive','negative','neutral') DEFAULT 'neutral',
			score_change DECIMAL(5,1) DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_user_id (user_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='正念树洞心事'");
	}
	$tblMF4 = $pdo->query("SHOW TABLES LIKE 'mindfulness_configs'")->fetch();
	if (!$tblMF4) {
		$pdo->exec("CREATE TABLE IF NOT EXISTS mindfulness_configs (
			id INT AUTO_INCREMENT PRIMARY KEY,
			user_id INT NOT NULL UNIQUE,
			initial_score DECIMAL(5,1) DEFAULT 80.0,
			checkin_score DECIMAL(5,1) DEFAULT 0.3,
			positive_items JSON,
			negative_items JSON,
			bonus_rules JSON,
			ai_mode ENUM('system','custom') DEFAULT 'system',
			custom_api_url VARCHAR(500) DEFAULT '',
			custom_api_key VARCHAR(200) DEFAULT '',
			custom_model VARCHAR(100) DEFAULT '',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='正念用户配置'");
	}
	$tblMF5 = $pdo->query("SHOW TABLES LIKE 'user_ai_quotas'")->fetch();
	if (!$tblMF5) {
		$pdo->exec("CREATE TABLE IF NOT EXISTS user_ai_quotas (
			id INT AUTO_INCREMENT PRIMARY KEY,
			user_id INT NOT NULL UNIQUE,
			system_quota INT DEFAULT 10,
			system_used INT DEFAULT 0,
			purchased_quota INT DEFAULT 0,
			purchased_used INT DEFAULT 0,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户AI使用次数'");
	}
	$tblMF6 = $pdo->query("SHOW TABLES LIKE 'ai_pricing_plans'")->fetch();
	if (!$tblMF6) {
		$pdo->exec("CREATE TABLE IF NOT EXISTS ai_pricing_plans (
			id INT AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(50) NOT NULL,
			quota INT NOT NULL,
			original_price DECIMAL(10,2) NOT NULL,
			price DECIMAL(10,2) NOT NULL,
			sort_order INT DEFAULT 0,
			enabled TINYINT DEFAULT 1,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI套餐定价'");
		$pdo->exec("INSERT INTO ai_pricing_plans (name, quota, original_price, price, sort_order) VALUES
			('体验包', 10, 10.00, 5.00, 1),
			('标准包', 50, 40.00, 25.00, 2),
			('高级包', 200, 120.00, 68.00, 3)");
	}

	$tblPJ1 = $pdo->query("SHOW TABLES LIKE 'projects'")->fetch();
	if (!$tblPJ1) {
		$pdo->exec("CREATE TABLE IF NOT EXISTS projects (
			id INT AUTO_INCREMENT PRIMARY KEY,
			user_id INT NOT NULL,
			name VARCHAR(200) NOT NULL,
			description TEXT,
			status ENUM('planning','active','completed','archived') DEFAULT 'planning',
			progress TINYINT DEFAULT 0,
			start_date DATE DEFAULT NULL,
			end_date DATE DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_user_id (user_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='项目管理'");
	}
	$tblPJ2 = $pdo->query("SHOW TABLES LIKE 'project_updates'")->fetch();
	if (!$tblPJ2) {
		$pdo->exec("CREATE TABLE IF NOT EXISTS project_updates (
			id INT AUTO_INCREMENT PRIMARY KEY,
			project_id INT NOT NULL,
			user_id INT NOT NULL,
			title VARCHAR(200) NOT NULL,
			content TEXT,
			progress TINYINT DEFAULT 0,
			update_date DATE NOT NULL,
			attachments TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_project_id (project_id),
			INDEX idx_user_id (user_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='项目进度更新'");
	}
	$tblPJ3 = $pdo->query("SHOW TABLES LIKE 'project_members'")->fetch();
	if (!$tblPJ3) {
		$pdo->exec("CREATE TABLE IF NOT EXISTS project_members (
			id INT AUTO_INCREMENT PRIMARY KEY,
			project_id INT NOT NULL,
			user_id INT NOT NULL,
			role ENUM('owner','member') DEFAULT 'member',
			joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY uk_project_user (project_id, user_id),
			INDEX idx_user_id (user_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='项目成员'");
	}
	$colPJ1 = $pdo->query("SHOW COLUMNS FROM projects LIKE 'tasks'")->fetch();
	if (!$colPJ1) {
		$pdo->exec("ALTER TABLE projects ADD COLUMN tasks TEXT DEFAULT NULL AFTER description");
	}

	// 知识库模块表
	$tblKB1 = $pdo->query("SHOW TABLES LIKE 'kb_spaces'")->fetch();
	if (!$tblKB1) {
		$pdo->exec("CREATE TABLE IF NOT EXISTS kb_spaces (
			id INT AUTO_INCREMENT PRIMARY KEY,
			user_id INT NOT NULL,
			name VARCHAR(100) NOT NULL DEFAULT '我的知识库',
			description TEXT DEFAULT NULL,
			version_enabled TINYINT DEFAULT 0 COMMENT '是否开启版本历史',
			version_max INT DEFAULT 10 COMMENT '最大版本数',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_user (user_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='知识库空间'");
	}
	$tblKB2 = $pdo->query("SHOW TABLES LIKE 'kb_documents'")->fetch();
	if (!$tblKB2) {
		$pdo->exec("CREATE TABLE IF NOT EXISTS kb_documents (
			id INT AUTO_INCREMENT PRIMARY KEY,
			space_id INT NOT NULL,
			user_id INT NOT NULL,
			parent_id INT DEFAULT 0 COMMENT '父文档ID，0=顶级',
			title VARCHAR(255) NOT NULL DEFAULT '无标题',
			content LONGTEXT COMMENT 'Markdown原文',
			content_html LONGTEXT COMMENT '渲染后HTML缓存',
			sort_order INT DEFAULT 0,
			is_folder TINYINT DEFAULT 0 COMMENT '是否为文件夹',
			is_public TINYINT DEFAULT 0 COMMENT '是否允许外部分享',
			share_token VARCHAR(64) DEFAULT NULL COMMENT '分享token',
			status ENUM('draft','published') DEFAULT 'published',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_space (space_id),
			INDEX idx_user (user_id),
			INDEX idx_parent (parent_id),
			INDEX idx_share_token (share_token)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='知识库文档'");
	}
	$tblKB3 = $pdo->query("SHOW TABLES LIKE 'kb_doc_versions'")->fetch();
	if (!$tblKB3) {
		$pdo->exec("CREATE TABLE IF NOT EXISTS kb_doc_versions (
			id INT AUTO_INCREMENT PRIMARY KEY,
			doc_id INT NOT NULL,
			user_id INT NOT NULL,
			title VARCHAR(255) NOT NULL,
			content LONGTEXT,
			version_num INT NOT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_doc (doc_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='知识库文档版本'");
	}

		@file_put_contents($migrationFlag, date('Y-m-d H:i:s'));
	}

} catch (\Throwable $e) {
	// 静默失败，不影响正常请求
}

// 如启用授权客户端，则在每次请求入口执行授权自检与联机调度
if (Config::get('license.client_enabled', false)) {
	LicenseClient::enforce();
}

// 启动内置定时任务调度器（用标记避免每次请求都检查文件）
$schedulerFlag = __DIR__ . '/../runtime/scheduler_checked.flag';
$schedulerNeedsCheck = !file_exists($schedulerFlag) || (time() - filemtime($schedulerFlag)) > 60;
if ($schedulerNeedsCheck && Config::get('scheduler.enabled', false) && !defined('SCHEDULER_STARTED')) {
	@touch($schedulerFlag);
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


