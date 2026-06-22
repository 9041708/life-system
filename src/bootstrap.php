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

// 授权码模块表（主站使用）
try {
	$pdoLC = \App\Service\Database::getConnection();
	$pdoLC->exec("CREATE TABLE IF NOT EXISTS licenses (
		id INT AUTO_INCREMENT PRIMARY KEY,
		license_key VARCHAR(64) NOT NULL UNIQUE,
		email VARCHAR(200) NOT NULL DEFAULT '',
		domain VARCHAR(200) NOT NULL DEFAULT '',
		expire_date DATE NOT NULL,
		is_active TINYINT DEFAULT 1,
		last_checkin DATETIME,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$pdoLC->exec("CREATE TABLE IF NOT EXISTS license_config (
		id INT AUTO_INCREMENT PRIMARY KEY,
		config_key VARCHAR(50) NOT NULL UNIQUE,
		config_value TEXT,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$pdoLC->exec("CREATE TABLE IF NOT EXISTS license_applications (
		id INT AUTO_INCREMENT PRIMARY KEY,
		email VARCHAR(200) NOT NULL,
		domain VARCHAR(200) NOT NULL,
		payment_screenshot VARCHAR(500) DEFAULT '',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$pdoLC->exec("CREATE TABLE IF NOT EXISTS license_campaigns (
		id INT AUTO_INCREMENT PRIMARY KEY,
		name VARCHAR(100) NOT NULL,
		campaign_price DECIMAL(10,2) NOT NULL,
		start_date DATE NOT NULL,
		end_date DATE NOT NULL,
		is_active TINYINT DEFAULT 1,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (\Throwable $e) {}

// 考勤模块表
try {
	$pdoAT = \App\Service\Database::getConnection();
	$pdoAT->exec("CREATE TABLE IF NOT EXISTS att_shifts (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		name VARCHAR(50) NOT NULL,
		start_time TIME NOT NULL,
		end_time TIME NOT NULL,
		is_rest TINYINT DEFAULT 0,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_user (user_id)
	)");
	$pdoAT->exec("CREATE TABLE IF NOT EXISTS att_records (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		record_date DATE NOT NULL,
		shift_id INT DEFAULT NULL,
		status ENUM('present','absent','late','leave','rest') DEFAULT 'present',
		note VARCHAR(200) DEFAULT '',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY uk_user_date (user_id, record_date),
		INDEX idx_user_month (user_id, record_date)
	)");
	$pdoAT->exec("CREATE TABLE IF NOT EXISTS salary_configs (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		base_salary DECIMAL(10,2) DEFAULT 0,
		performance DECIMAL(10,2) DEFAULT 0,
		subsidy DECIMAL(10,2) DEFAULT 0,
		effective_from DATE NOT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_user (user_id)
	)");
	$pdoAT->exec("CREATE TABLE IF NOT EXISTS salary_deductions (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		deduction_month VARCHAR(7) NOT NULL,
		amount DECIMAL(10,2) NOT NULL,
		detail VARCHAR(200) DEFAULT '',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_user (user_id)
	)");
	$pdoAT->exec("CREATE TABLE IF NOT EXISTS salary_social (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		social_amount DECIMAL(10,2) DEFAULT 0,
		fund_amount DECIMAL(10,2) DEFAULT 0,
		start_date DATE NOT NULL,
		end_date DATE DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_user (user_id)
	)");
	$pdoAT->exec("CREATE TABLE IF NOT EXISTS salary_actual (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		salary_month VARCHAR(7) NOT NULL,
		actual_amount DECIMAL(10,2) NOT NULL,
		note VARCHAR(200) DEFAULT '',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY uk_user_month (user_id, salary_month)
	)");
	$pdoAT->exec("CREATE TABLE IF NOT EXISTS att_company (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		company_name VARCHAR(100) NOT NULL,
		join_date DATE NOT NULL,
		left_date DATE DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_user (user_id)
	)");
	$pdoAT->exec("CREATE TABLE IF NOT EXISTS att_schedule (
		user_id INT NOT NULL,
		schedule_date DATE NOT NULL COMMENT '排班日期',
		shift_id INT NOT NULL,
		PRIMARY KEY (user_id, schedule_date)
	)");
	$pdoAT->exec("ALTER TABLE att_schedule DROP COLUMN IF EXISTS weekday");
	// 种子班次
	$sc2 = $pdoAT->query("SELECT COUNT(*) FROM att_shifts WHERE user_id=0")->fetchColumn();
	if ($sc2 == 0) {
		$pdoAT->exec("INSERT INTO att_shifts (user_id, name, start_time, end_time) VALUES (0, '早班', '08:00:00', '16:00:00'), (0, '晚班', '16:00:00', '24:00:00'), (0, '休息', '00:00:00', '00:00:00')");
	}
} catch (\Throwable $e) {}

// 娱乐·炒股模块
try {
	$pdoET = \App\Service\Database::getConnection();
	$pdoET->exec("CREATE TABLE IF NOT EXISTS ent_stocks (
		id INT AUTO_INCREMENT PRIMARY KEY,
		symbol VARCHAR(10) NOT NULL UNIQUE,
		name VARCHAR(50) NOT NULL,
		sector VARCHAR(20) DEFAULT '',
		base_price DECIMAL(10,2) NOT NULL,
		current_price DECIMAL(10,2) NOT NULL,
		is_active TINYINT DEFAULT 1,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$pdoET->exec("CREATE TABLE IF NOT EXISTS ent_accounts (
		user_id INT NOT NULL PRIMARY KEY,
		balance DECIMAL(14,2) DEFAULT 1000000.00,
		loan_amount DECIMAL(14,2) DEFAULT 0,
		loan_count INT DEFAULT 0,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$pdoET->exec("CREATE TABLE IF NOT EXISTS ent_positions (
		user_id INT NOT NULL, stock_id INT NOT NULL,
		quantity INT NOT NULL DEFAULT 0,
		avg_cost DECIMAL(10,4) NOT NULL,
		PRIMARY KEY (user_id, stock_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$pdoET->exec("CREATE TABLE IF NOT EXISTS ent_trades (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL, stock_id INT NOT NULL,
		type ENUM('buy','sell') NOT NULL,
		price DECIMAL(10,4) NOT NULL, quantity INT NOT NULL,
		fee DECIMAL(10,2) DEFAULT 0,
		total_amount DECIMAL(14,2) NOT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_user (user_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$pdoET->exec("CREATE TABLE IF NOT EXISTS ent_news (
		id INT AUTO_INCREMENT PRIMARY KEY,
		stock_id INT NOT NULL,
		title VARCHAR(200) NOT NULL,
		content TEXT,
		effect ENUM('positive','negative') NOT NULL,
		strength INT DEFAULT 5,
		expire_hours INT DEFAULT 4,
		is_active TINYINT DEFAULT 1,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_stock (stock_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$pdoET->exec("CREATE TABLE IF NOT EXISTS ent_config (
		id INT AUTO_INCREMENT PRIMARY KEY,
		config_key VARCHAR(50) NOT NULL UNIQUE,
		config_value TEXT,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$pdoET->exec("CREATE TABLE IF NOT EXISTS ent_loans (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		amount DECIMAL(14,2) NOT NULL,
		interest_rate DECIMAL(5,2) DEFAULT 5.00,
		repay_method ENUM('equal','interest_first','equal_principal') DEFAULT 'equal',
		total_repayable DECIMAL(14,2) NOT NULL,
		repaid DECIMAL(14,2) DEFAULT 0,
		due_date DATE NOT NULL,
		status ENUM('active','repaid','overdue') DEFAULT 'active',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_user (user_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$pdoET->exec("ALTER TABLE ent_accounts ADD COLUMN IF NOT EXISTS bankruptcy_count INT DEFAULT 0");
	// 新字段
	$pdoET->exec("ALTER TABLE ent_stocks ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL AFTER sector");
	$pdoET->exec("ALTER TABLE ent_stocks ADD COLUMN IF NOT EXISTS listed_date DATE DEFAULT NULL AFTER description");
	$pdoET->exec("ALTER TABLE ent_stocks ADD COLUMN IF NOT EXISTS ipo_price DECIMAL(10,2) DEFAULT NULL AFTER listed_date");
	$pdoET->exec("ALTER TABLE ent_stocks ADD COLUMN IF NOT EXISTS total_shares BIGINT DEFAULT 1000000000 AFTER ipo_price");
	$pdoET->exec("ALTER TABLE ent_stocks ADD COLUMN IF NOT EXISTS limit_per_user INT DEFAULT 100000 AFTER total_shares");
	// 新闻定时发布
	$pdoET->exec("ALTER TABLE ent_news ADD COLUMN IF NOT EXISTS scheduled_at DATETIME DEFAULT NULL AFTER expire_hours");
	// 重建委托表
	$pdoET->exec("CREATE TABLE IF NOT EXISTS ent_orders (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL, stock_id INT NOT NULL,
		type ENUM('buy','sell') NOT NULL,
		price DECIMAL(10,4) NOT NULL, quantity INT NOT NULL,
		status ENUM('pending','done','cancelled') DEFAULT 'pending',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_stock_status (stock_id, status)
	)");
	// 种子数据
	$sc = $pdoET->query("SELECT COUNT(*) FROM ent_stocks")->fetchColumn();
	if ($sc == 0) {
		$stocks = [
			['801027','龙腾地产','🏠地产',8.50],['803015','星辰银行','🏦金融',15.80],['800591','量子芯片','🔬半导体',68.00],
			['807220','深海矿业','⛏️资源',12.30],['805109','智能家居','🏡家居',16.20],['808333','宇宙科技','💻科技',42.00],
			['802768','星际通信','📡通信',18.90],['800147','云端数据','☁️云计算',55.00],['804556','绿色能源','⚡能源',23.60],
			['806889','数字支付','💳支付',27.80],['809001','极速物流','🚚物流',22.40],['803342','智慧医疗','🏥医疗',35.20],
			['807895','航天动力','🚀航天',52.00],['800725','网络安全','🛡️安全',48.00],['801456','生物基因','🧬生物',45.50],
			['802234','虚拟现实','🕶️VR',38.00],['804890','未来汽车','🚗汽车',31.00],['808567','教育互联','📚教育',20.00],
			['805678','海洋开发','🌊海洋',14.60],['803901','宇宙能源','⚡能源',25.40],['806234','智能机器','🤖机器人',58.00],
			['800389','区块链链','🔗区块链',33.00],['802456','新能源车','🚗汽车',28.50],['807123','飞行汽车','🚀飞行',65.00],
			['804678','精准医疗','🏥医疗',41.00],['808901','量子通信','📡通信',72.00],['801789','太空旅游','🚀航天',88.00],
			['805345','元宇宙地产','🏠地产',15.50],['803567','气候科技','🌿环保',19.80],['809234','脑机接口','🧠科技',95.00],
			['800456','核聚变能','⚡能源',75.00],['806789','数字孪生','💻科技',36.00],['802890','基因编辑','🧬生物',55.50],
			['807456','深海探测','🌊海洋',22.00],['804123','智能城市','🏙️城市',18.00],['808234','激光武器','🛡️军工',62.00],
			['801567','纳米材料','🔬材料',45.00],['803890','光子芯片','🔬芯片',80.00],['805012','星际采矿','⛏️太空采矿',55.00],
			['809678','反重力技术','🚀飞行',120.00],
		];
		$si = $pdoET->prepare('INSERT INTO ent_stocks (symbol,name,sector,base_price,current_price) VALUES (?,?,?,?,?)');
		foreach ($stocks as $s) $si->execute($s);
	}
} catch (\Throwable $e) {}




// 娱乐·我的人生模块
try {
	$pdoLife = \App\Service\Database::getConnection();
	$pdoLife->exec("CREATE TABLE IF NOT EXISTS life_events (
		id INT AUTO_INCREMENT PRIMARY KEY,
		age_min INT NOT NULL DEFAULT 0 COMMENT '触发年龄下限',
		age_max INT NOT NULL DEFAULT 100 COMMENT '触发年龄上限',
		gender ENUM('all','male','female') DEFAULT 'all' COMMENT '适用性别',
		iq_min INT DEFAULT 0, iq_max INT DEFAULT 100,
		eq_min INT DEFAULT 0, eq_max INT DEFAULT 100,
		health_min INT DEFAULT 0, health_max INT DEFAULT 100,
		wealth_min INT DEFAULT 0, wealth_max INT DEFAULT 100,
		looks_min INT DEFAULT 0, looks_max INT DEFAULT 100,
		luck_min INT DEFAULT 0, luck_max INT DEFAULT 100,
		family_background VARCHAR(50) DEFAULT '' COMMENT '原生家庭类型过滤，空为不限',
		condition_json TEXT DEFAULT NULL COMMENT '特殊触发条件JSON',
		title VARCHAR(200) NOT NULL COMMENT '事件标题',
		description TEXT COMMENT '事件描述',
		choices JSON NOT NULL COMMENT '选项JSON数组',
		is_active TINYINT DEFAULT 1,
		sort_order INT DEFAULT 0,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_age (age_min, age_max),
		INDEX idx_iq (iq_min, iq_max)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='我的人生事件库'");

	$pdoLife->exec("CREATE TABLE IF NOT EXISTS life_records (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		family_background VARCHAR(50) NOT NULL DEFAULT '' COMMENT '原生家庭类型',
		gender VARCHAR(10) NOT NULL DEFAULT 'male' COMMENT '性别',
		start_time DATETIME NOT NULL,
		end_time DATETIME DEFAULT NULL,
		initial_iq TINYINT DEFAULT 50, initial_eq TINYINT DEFAULT 50,
		initial_health TINYINT DEFAULT 50, initial_wealth TINYINT DEFAULT 50,
		initial_looks TINYINT DEFAULT 50, initial_luck TINYINT DEFAULT 50,
		final_age TINYINT DEFAULT 0,
		final_iq TINYINT DEFAULT 0, final_eq TINYINT DEFAULT 0,
		final_health TINYINT DEFAULT 0, final_wealth TINYINT DEFAULT 0,
		final_looks TINYINT DEFAULT 0, final_luck TINYINT DEFAULT 0,
		life_log LONGTEXT DEFAULT NULL COMMENT '完整人生日志JSON',
		is_completed TINYINT DEFAULT 0 COMMENT '是否已完成',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_user (user_id),
		INDEX idx_completed (is_completed)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='我的人生游戏记录'");

	$pdoLife->exec("CREATE TABLE IF NOT EXISTS life_achievements (
		id INT AUTO_INCREMENT PRIMARY KEY,
		name VARCHAR(100) NOT NULL COMMENT '成就名称（含emoji）',
		description VARCHAR(255) NOT NULL COMMENT '成就描述',
		condition_json TEXT NOT NULL COMMENT '解锁条件JSON',
		icon VARCHAR(50) DEFAULT '' COMMENT '图标',
		sort_order INT DEFAULT 0,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='我的人生成就定义'");

	$pdoLife->exec("CREATE TABLE IF NOT EXISTS life_user_achievements (
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NOT NULL,
		record_id INT NOT NULL COMMENT '关联的人生记录ID',
		achievement_id INT NOT NULL,
		unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY uk_user_ach (user_id, record_id, achievement_id),
		INDEX idx_user (user_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户成就解锁记录'");

	$pdoLife->exec("CREATE TABLE IF NOT EXISTS life_config (
		id INT AUTO_INCREMENT PRIMARY KEY,
		config_key VARCHAR(50) NOT NULL UNIQUE,
		config_value TEXT,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='我的人生配置表'");

	// life_config 种子数据
	$cc = $pdoLife->query("SELECT COUNT(*) FROM life_config")->fetchColumn();
	if ($cc == 0) {
		$cfg = [
			['initial_iq_range','40,60'],['initial_eq_range','40,60'],['initial_health_range','40,100'],
			['initial_wealth_range','30,70'],['initial_looks_range','30,70'],['initial_luck_range','30,70'],
			['max_age','100'],['events_per_age','1'],['auto_speed_ms','800']
		];
		$ci = $pdoLife->prepare('INSERT INTO life_config (config_key,config_value) VALUES (?,?)');
		foreach ($cfg as $c) $ci->execute($c);
	}

	// life_achievements 种子数据
	$ac = $pdoLife->query("SELECT COUNT(*) FROM life_achievements")->fetchColumn();
	if ($ac == 0) {
		$achs = [
			['💰 百万富翁','财富达到90+','{"wealth":{"min":90}}','💰',1],
			['🎓 学霸','智商达到90+且完成高等教育','{"iq":{"min":90},"has_education":"university"}','🎓',2],
			['❤️ 情场高手','情商达到80+且恋爱次数≥3','{"eq":{"min":80},"romance_count":{"min":3}}','❤️',3],
			['🏃 百岁老人','活到100岁','{"age":{"min":100}}','🏃',4],
			['💑 模范夫妻','结婚后未离婚且婚姻持续40年+','{"marriage_years":{"min":40},"divorced":false}','💑',5],
			['👶 多子多福','子女数量≥3','{"children_count":{"min":3}}','👶',6],
			['🚀 逆袭人生','初始财富<30且最终财富>80','{"initial_wealth":{"max":30},"final_wealth":{"min":80}}','🚀',7],
			['😎 颜值担当','颜值达到90+','{"looks":{"min":90}}','😎',8],
			['🍀 欧皇','运气达到90+','{"luck":{"min":90}}','🍀',9],
			['🧘 平淡是真','活到80岁且各项属性均衡','{"age":{"min":80},"balanced":true}','🧘',10],
			['💀 英年早逝','30岁前死亡','{"age":{"max":30},"dead":true}','💀',11],
			['🎭 传奇人生','同时解锁3个以上其他成就','{"unlock_count":{"min":3}}','🎭',12]
		];
		$ai = $pdoLife->prepare('INSERT INTO life_achievements (name,description,condition_json,icon,sort_order) VALUES (?,?,?,?,?)');
		foreach ($achs as $a) $ai->execute($a);
	}

	// life_events 种子数据（先插入少量，后续通过管理后台添加）
	$ec = $pdoLife->query("SELECT COUNT(*) FROM life_events")->fetchColumn();
	if ($ec == 0) {
		$events = [
			[0,1,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','出生了','你来到了这个世界，一声啼哭宣告你的降临。',
			 '[{"text":"好好吃奶","effects":{"health":5,"iq":1}},{"text":"大哭大闹","effects":{"health":-2,"eq":-1}}]',1,0],
			[0,1,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','起名字','父母在讨论给你起什么名字。',
			 '[{"text":"叫个响亮的名字","effects":{"luck":2}},{"text":"随便起一个","effects":{}}]',1,1],
			[1,2,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','学会走路','你跌跌撞撞地尝试走路，虽然经常摔倒，但很开心。',
			 '[{"text":"坚持练习","effects":{"health":3,"luck":1}},{"text":"依赖大人抱着","effects":{"health":-2}}]',1,2],
			[2,3,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','上幼儿园','你到了上幼儿园的年纪，第一次离开父母。',
			 '[{"text":"勇敢地去","effects":{"eq":3,"iq":1}},{"text":"哭闹不想去","effects":{"eq":-2,"health":-1}}]',1,3],
			[6,7,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','小学第一天','你背着新书包，走进小学教室，心里既紧张又兴奋。',
			 '[{"text":"认真听讲","effects":{"iq":3}},{"text":"和同学聊天","effects":{"eq":2}}]',1,10],
			[7,8,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','第一次考试','你迎来了人生中第一次考试，有点紧张。',
			 '[{"text":"努力学习考满分","effects":{"iq":5,"luck":-1}},{"text":"考多少算多少","effects":{"iq":1,"luck":2}}]',1,11],
			[8,9,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','培养兴趣','父母问你想学什么兴趣班。',
			 '[{"text":"学钢琴","effects":{"iq":2,"looks":1}},{"text":"学画画","effects":{"iq":2,"eq":1}},{"text":"学体育","effects":{"health":5}}]',1,12],
			[9,10,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','交朋友','你在学校里认识了新的朋友。',
			 '[{"text":"主动交朋友","effects":{"eq":4}},{"text":"等别人来找你","effects":{"eq":0,"iq":1}}]',1,13],
			[10,11,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','第一次上网','你第一次接触互联网，打开了新世界的大门。',
			 '[{"text":"查学习资料","effects":{"iq":3}},{"text":"玩游戏","effects":{"eq":1,"health":-2}}]',1,14],
			[11,12,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','小学毕业','你小学毕业了，即将进入初中。',
			 '[{"text":"继续努力学习","effects":{"iq":3,"eq":1}},{"text":"放松一下玩游戏","effects":{"health":2,"iq":-1}}]',1,15],
			[13,14,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','初中生活','你进入了初中，课程变难了，还遇到了青春期烦恼。',
			 '[{"text":"努力学习","effects":{"iq":5}},{"text":"谈恋爱","effects":{"eq":5,"iq":-2}},{"text":"沉迷游戏","effects":{"iq":-5,"eq":2}}]',1,20],
			[15,16,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','中考','你面临中考，这将决定你能上什么样的高中。',
			 '[{"text":"拼命学习考重点","effects":{"iq":8,"health":-3}},{"text":"发挥正常就行","effects":{"iq":3,"health":1}},{"text":"弃考去职高","effects":{"iq":-3,"wealth":2}}]',1,21],
			[16,17,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','高中生活','你进入了高中，学习压力更大了。',
			 '[{"text":"刻苦学习","effects":{"iq":6,"health":-2}},{"text":"参加社团活动","effects":{"eq":4,"looks":1}},{"text":"谈恋爱","effects":{"eq":5,"iq":-3}}]',1,22],
			[17,18,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','高考','人生的重要转折点，高考来临。',
			 '[{"text":"十年寒窗终一搏","effects":{"iq":10,"health":-5,"luck":5}},{"text":"正常发挥","effects":{"iq":5}},{"text":"弃考去打工","effects":{"wealth":5,"iq":-8}}]',1,23],
			[18,18,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','高考出分','成绩出来了，你考得怎么样？',
			 '[{"text":"考上理想大学","effects":{"iq":5,"wealth":-3,"luck":5}},{"text":"考上普通大学","effects":{"iq":3}},{"text":"没考上","effects":{"iq":-2,"wealth":2}}]',1,24],
			[19,20,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','大学生活','你开始了大学生活，这是人生中最自由的时光。',
			 '[{"text":"努力学习拿奖学金","effects":{"iq":8,"wealth":5}},{"text":"参加社团锻炼能力","effects":{"eq":6,"looks":2}},{"text":"谈一场恋爱","effects":{"eq":8,"luck":2}},{"text":"混日子玩游戏","effects":{"iq":-5,"health":-3}}]',1,30],
			[21,22,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','第一次兼职','你想利用课余时间做兼职。',
			 '[{"text":"做家教","effects":{"iq":3,"wealth":3}},{"text":"做服务员","effects":{"eq":3,"wealth":2}},{"text":"做实习生","effects":{"iq":2,"wealth":2,"eq":2}}]',1,31],
			[22,23,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','大学毕业','你大学毕业了，面临人生选择。',
			 '[{"text":"继续读研","effects":{"iq":10,"wealth":-5}},{"text":"找工作","effects":{"wealth":5,"eq":3}},{"text":"创业","effects":{"wealth":10,"luck":-5,"iq":3}}]',1,32],
			[23,25,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','第一份工作','你找到了第一份工作，开始了职业生涯。',
			 '[{"text":"努力工作争取晋升","effects":{"wealth":8,"eq":3}},{"text":"摸鱼划水","effects":{"health":3,"iq":-2}},{"text":"频繁跳槽","effects":{"wealth":-3,"eq":2}}]',1,33],
			[25,26,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','遇到喜欢的人','你在工作中/生活中遇到了心动的人。',
			 '[{"text":"主动表白","effects":{"eq":5,"luck":3}},{"text":"暗恋不说","effects":{"eq":-2,"iq":1}},{"text":"等别人追你","effects":{"eq":0,"luck":2}}]',1,34],
			[26,28,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','考虑结婚','你的感情稳定了，双方在讨论结婚。',
			 '[{"text":"结婚","effects":{"eq":5,"wealth":-10}},{"text":"再谈恋爱","effects":{"eq":3}},{"text":"恐婚不结婚","effects":{"wealth":5,"eq":-3}}]',1,35],
			[28,30,'male',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','买房压力','你面临买房的压力，房价很高。',
			 '[{"text":"借钱买房","effects":{"wealth":-20,"eq":-2}},{"text":"租房住","effects":{"wealth":-5,"eq":1}},{"text":"和父母住","effects":{"wealth":2,"eq":-3}}]',1,36],
			[28,30,'female',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','买房压力','你面临买房的压力，男方是否在意？',
			 '[{"text":"要求男方买房","effects":{"eq":-2,"wealth":5}},{"text":"一起奋斗买房","effects":{"eq":5,"wealth":-10}},{"text":"租房也行","effects":{"eq":3}}]',1,37],
		];
		$ei = $pdoLife->prepare('INSERT INTO life_events (age_min,age_max,gender,iq_min,iq_max,eq_min,eq_max,health_min,health_max,wealth_min,wealth_max,looks_min,looks_max,luck_min,luck_max,family_background,condition_json,title,description,choices,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
		foreach ($events as $e) $ei->execute($e);
	}

	// 补充缺失年龄段的事件（INSERT IGNORE，幂等安全）
	try {
		$gapEvents = [
			// 4~6岁
			[4,5,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','语言爆发期','你的语言能力突飞猛进，开始问十万个为什么。','[{"text":"好奇多问","effects":{"iq":3,"eq":2}},{"text":"安静观察","effects":{"iq":1}},{"text":"缠着大人讲故事","effects":{"iq":2,"eq":3}}]',1,4],
			[5,6,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','幼儿园毕业','你要从幼儿园毕业了，即将成为小学生。','[{"text":"期待上小学","effects":{"eq":3,"iq":1}},{"text":"舍不得幼儿园","effects":{"eq":-1}}]',1,5],
			// 12~14岁
			[12,13,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','小升初','小学即将结束，面临小升初的选择。','[{"text":"努力考好初中","effects":{"iq":4,"health":-1}},{"text":"就近入学","effects":{"health":2}},{"text":"特长生招生","effects":{"iq":2,"looks":1}}]',1,16],
			[13,14,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','青春期的烦恼','你开始注意到自己的身体变化，心情也变得不稳定。','[{"text":"和父母沟通","effects":{"eq":3}},{"text":"闷在心里","effects":{"eq":-2,"health":-1}},{"text":"和朋友倾诉","effects":{"eq":2}}]',1,17],
			// 15~17岁
			[14,15,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','初二分水岭','初二是成绩的分水岭，课程难度明显提升。','[{"text":"迎难而上","effects":{"iq":5,"health":-2}},{"text":"保持现状","effects":{"iq":1}},{"text":"开始厌学","effects":{"iq":-3,"health":1}}]',1,18],
			[15,17,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','叛逆期高峰','你和父母的矛盾达到了顶峰，经常吵架。','[{"text":"冷战对抗","effects":{"eq":-3,"health":-1}},{"text":"尝试理解父母","effects":{"eq":5,"iq":2}},{"text":"找朋友发泄","effects":{"eq":2,"luck":1}}]',1,19],
			// 20~24岁
			[20,21,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','社团/学生会','你在考虑要不要参加社团或学生会。','[{"text":"加入学生会","effects":{"eq":5,"iq":2}},{"text":"加兴趣社团","effects":{"eq":3,"looks":1}},{"text":"专注学习不参加","effects":{"iq":4}}]',1,25],
			[23,24,'all',0,100,0,100,0,100,0,100,0,100,0,100,'','{}','实习机会','你获得了一个实习机会。','[{"text":"去大厂实习","effects":{"wealth":5,"iq":3,"eq":2}},{"text":"去创业公司","effects":{"wealth":2,"iq":4,"luck":-2}},{"text":"不实习继续学习","effects":{"iq":3}}]',1,33],
		];
		$gei = $pdoLife->prepare('INSERT IGNORE INTO life_events (age_min,age_max,gender,iq_min,iq_max,eq_min,eq_max,health_min,health_max,wealth_min,wealth_max,looks_min,looks_max,luck_min,luck_max,family_background,condition_json,title,description,choices,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
		foreach ($gapEvents as $e) $gei->execute($e);
	} catch (\Throwable ) {}

	// 确保 life_records 有 gender 字段
	try { $pdoLife->exec("ALTER TABLE life_records ADD COLUMN gender VARCHAR(10) NOT NULL DEFAULT 'male' COMMENT '性别'"); } catch (\Throwable ) {}

	// 修复老年事件：安详离世选项加 die 标记
	try {
		$pdoLife->exec("UPDATE life_events SET choices = REPLACE(choices, '{\"text\":\"安详离世\",\"effects\":{}}', '{\"text\":\"安详离世\",\"effects\":{},\"die\":true}') WHERE title LIKE '%晚年%' AND choices LIKE '%安详离世%'");
		$pdoLife->exec("UPDATE life_events SET choices = REPLACE(choices, '{\"text\":\"安详离世\",\"effects\":{\"health\":-10}}', '{\"text\":\"安详离世\",\"effects\":{\"health\":-10},\"die\":true}') WHERE title LIKE '%晚年%' AND choices LIKE '%安详离世%'");
		// 通用修复：所有含"安详离世"或"告别世界"的选项加 die
		$stmt = $pdoLife->query("SELECT id, choices FROM life_events WHERE choices LIKE '%安详离世%' OR choices LIKE '%告别世界%' OR choices LIKE '%选择离开%'");
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$c = json_decode($row['choices'], true);
			$changed = false;
			foreach ($c as &$ch) {
				if (isset($ch['text']) && (mb_strpos($ch['text'], '安详离世') !== false || mb_strpos($ch['text'], '告别世界') !== false || mb_strpos($ch['text'], '选择离开') !== false || mb_strpos($ch['text'], '选择死亡') !== false)) {
					$ch['die'] = true;
					$changed = true;
				}
			}
			unset($ch);
			if ($changed) {
				$upd = $pdoLife->prepare("UPDATE life_events SET choices = ? WHERE id = ?");
				$upd->execute([json_encode($c, JSON_UNESCAPED_UNICODE), $row['id']]);
			}
		}
	} catch (\Throwable ) {}
} catch (\Throwable ) {}
