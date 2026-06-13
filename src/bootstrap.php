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

} catch (\Throwable $e) {
	// 静默失败，不影响正常请求
}



