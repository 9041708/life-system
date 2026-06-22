-- 论坛助手数据库表
-- 执行方式: mysql -u root -p ssjizhang_cn < forum_tables.sql

CREATE TABLE IF NOT EXISTS `forum_accounts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `forum_name` VARCHAR(100) NOT NULL COMMENT '论坛名称',
    `forum_url` VARCHAR(500) NOT NULL COMMENT '论坛地址',
    `username` VARCHAR(100) NOT NULL,
    `password` VARCHAR(500) NOT NULL COMMENT 'AES加密存储',
    `enable_notice` TINYINT(1) DEFAULT 0 COMMENT '接收通知',
    `enable_signin` TINYINT(1) DEFAULT 0 COMMENT '自动签到',
    `enable_autoreply` TINYINT(1) DEFAULT 0 COMMENT '自动回帖',
    `reply_mode` ENUM('custom','random','ai') DEFAULT 'random' COMMENT '回帖模式',
    `custom_reply` TEXT COMMENT '自定义回帖内容（多条换行）',
    `ai_reply_flag` VARCHAR(50) DEFAULT '[AI回帖]' COMMENT 'AI回帖标识',
    `signin_time` TIME DEFAULT '08:00:00' COMMENT '签到时间',
    `reply_time` TIME DEFAULT '09:00:00' COMMENT '回帖时间',
    `last_signin` DATE DEFAULT NULL,
    `last_reply` DATETIME DEFAULT NULL COMMENT '上次自动回帖时间',
    `last_notice_check` DATETIME DEFAULT NULL,
    `last_mention_reply` DATETIME DEFAULT NULL COMMENT '上次@提及回复时间',
    `cookie_data` TEXT COMMENT '登录Cookie缓存',
    `cookie_expire` DATETIME DEFAULT NULL COMMENT 'Cookie过期时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_signin_time` (`signin_time`),
    INDEX `idx_reply_time` (`reply_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='论坛账号配置';

CREATE TABLE IF NOT EXISTS `forum_action_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `account_id` INT NOT NULL,
    `action_type` ENUM('signin','reply','notice','login','error') NOT NULL,
    `target_info` VARCHAR(500) DEFAULT NULL COMMENT '目标帖子标题等',
    `result` VARCHAR(200) NOT NULL COMMENT '操作结果',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_created` (`user_id`, `created_at`),
    INDEX `idx_account` (`account_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='论坛操作日志';
