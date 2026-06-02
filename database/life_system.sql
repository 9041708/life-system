-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2026-06-02 20:58:47
-- 服务器版本： 10.11.11-MariaDB
-- PHP 版本： 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `ssjizhang_cn`
--

-- --------------------------------------------------------

--
-- 表的结构 `accounts`
--

CREATE TABLE `accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `group_id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `account_no` varchar(100) DEFAULT NULL,
  `initial_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `is_default` tinyint(4) NOT NULL DEFAULT 0,
  `icon_type` varchar(20) DEFAULT NULL COMMENT '图标类型：file/svg',
  `icon_value` text DEFAULT NULL COMMENT '图标值：文件相对路径或 SVG 代码',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `account_groups`
--

CREATE TABLE `account_groups` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `announcements`
--

CREATE TABLE `announcements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `scheduled_at` datetime NOT NULL COMMENT '计划推送时间（开始对用户可见的时间）',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `announcement_reads`
--

CREATE TABLE `announcement_reads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `announcement_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `client` enum('pc','miniapp') NOT NULL DEFAULT 'pc' COMMENT '查看来源：PC 端或小程序',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `api_tokens`
--

CREATE TABLE `api_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(100) NOT NULL,
  `client_type` varchar(32) NOT NULL COMMENT 'miniapp/web 等客户端类型',
  `description` varchar(255) DEFAULT '',
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `assets`
--

CREATE TABLE `assets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `icon_type` varchar(20) DEFAULT NULL,
  `icon_value` varchar(255) DEFAULT NULL,
  `acquired_date` date NOT NULL,
  `value_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','transferred') NOT NULL DEFAULT 'active',
  `transfer_date` date DEFAULT NULL,
  `transfer_price` decimal(10,2) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `bg_images`
--

CREATE TABLE `bg_images` (
  `id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT '',
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(10) NOT NULL COMMENT 'pdf/txt',
  `file_size` bigint(20) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `cover` varchar(500) DEFAULT '',
  `scope` varchar(10) DEFAULT 'personal',
  `pushed_by` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `push_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`push_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `book_progress`
--

CREATE TABLE `book_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `page_num` int(11) DEFAULT 1 COMMENT 'PDF当前页码',
  `scroll_offset` int(11) DEFAULT 0 COMMENT 'TXT滚动位置',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `budgets`
--

CREATE TABLE `budgets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `icon_type` varchar(20) DEFAULT NULL COMMENT '图标类型：file/svg',
  `icon_value` text DEFAULT NULL COMMENT '图标值：文件相对路径或 SVG 代码',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `debt_config`
--

CREATE TABLE `debt_config` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ledger_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL COMMENT '负债项目名称',
  `total_principal` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '总本金',
  `total_interest` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '总利息',
  `installment_count` int(11) NOT NULL DEFAULT 1 COMMENT '总期数',
  `per_period_principal` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '每期本金',
  `per_period_interest` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '每期利息',
  `per_period_total` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '每期总额',
  `first_payment_date` date NOT NULL COMMENT '首次还款日期',
  `repayment_method` varchar(20) NOT NULL DEFAULT 'equal' COMMENT '还款方式：equal(等额本息)、principal(等额本金)',
  `note` text DEFAULT NULL COMMENT '备注',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT '状态：active(进行中)、completed(已完成)、cancelled(已取消)',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='负债配置表';

-- --------------------------------------------------------

--
-- 表的结构 `debt_payment`
--

CREATE TABLE `debt_payment` (
  `id` int(11) NOT NULL,
  `debt_config_id` int(11) NOT NULL COMMENT '关联的负债配置ID',
  `user_id` int(11) NOT NULL,
  `ledger_id` int(11) NOT NULL DEFAULT 0,
  `period_number` int(11) NOT NULL COMMENT '第几期',
  `due_date` date NOT NULL COMMENT '应还款日期',
  `principal_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '本金金额',
  `interest_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '利息金额',
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '总额',
  `paid_amount` decimal(15,2) DEFAULT NULL COMMENT '实际还款金额',
  `paid_date` date DEFAULT NULL COMMENT '实际还款日期',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '状态：pending(待还)、paid(已还)、overdue(逾期)',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='还款记录表';

-- --------------------------------------------------------

--
-- 表的结构 `easytodo_clipboard_history`
--

CREATE TABLE `easytodo_clipboard_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL COMMENT '剪贴板文本内容',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `easytodo_command`
--

CREATE TABLE `easytodo_command` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `trigger` varchar(100) NOT NULL COMMENT '触发词，如 /bb',
  `name` varchar(200) NOT NULL COMMENT '模板名称',
  `content` text NOT NULL COMMENT '展开内容，每行一条任务',
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `easytodo_countdown`
--

CREATE TABLE `easytodo_countdown` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(500) NOT NULL COMMENT '倒计时标题',
  `target_time` datetime NOT NULL COMMENT '目标时间',
  `target_date` date DEFAULT NULL COMMENT '目标日期（用于每周/每月重复）',
  `repeat_type` enum('none','weekly','monthly','yearly') DEFAULT 'none' COMMENT '重复类型',
  `repeat_weekday` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '每周重复的星期几，0=周日',
  `repeat_month_day` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '每月重复的日期',
  `display_mode` tinyint(3) UNSIGNED DEFAULT 2 COMMENT '显示粒度：1=天，2=天时分，3=天时分分，4=天时分秒分',
  `enabled` tinyint(3) UNSIGNED DEFAULT 1 COMMENT '是否启用',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `easytodo_memo`
--

CREATE TABLE `easytodo_memo` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL COMMENT '备忘录内容',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `easytodo_pomodoro_session`
--

CREATE TABLE `easytodo_pomodoro_session` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `ledger_id` int(10) UNSIGNED DEFAULT NULL COMMENT '所属账本',
  `type` enum('work','short_break','long_break') NOT NULL COMMENT '会话类型',
  `started_at` datetime NOT NULL COMMENT '开始时间',
  `ended_at` datetime DEFAULT NULL COMMENT '结束时间',
  `duration_minutes` int(10) UNSIGNED DEFAULT 0 COMMENT '实际时长（分钟）',
  `note` varchar(500) DEFAULT NULL COMMENT '备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `easytodo_pomodoro_setting`
--

CREATE TABLE `easytodo_pomodoro_setting` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `work_duration` int(10) UNSIGNED DEFAULT 25 COMMENT '工作时长（分钟）',
  `short_break` int(10) UNSIGNED DEFAULT 5 COMMENT '短休息时长（分钟）',
  `long_break` int(10) UNSIGNED DEFAULT 15 COMMENT '长休息时长（分钟）',
  `long_break_interval` int(10) UNSIGNED DEFAULT 4 COMMENT '长休息间隔周期数',
  `auto_start_break` tinyint(3) UNSIGNED DEFAULT 0 COMMENT '是否自动开始休息',
  `auto_start_work` tinyint(3) UNSIGNED DEFAULT 0 COMMENT '是否自动开始工作',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `easytodo_report`
--

CREATE TABLE `easytodo_report` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `ledger_id` int(10) UNSIGNED DEFAULT NULL COMMENT '所属账本',
  `type` enum('daily','weekly') NOT NULL COMMENT '报告类型',
  `content` text NOT NULL COMMENT '报告内容',
  `task_summary` text DEFAULT NULL COMMENT '任务汇总数据（JSON）',
  `generated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `easytodo_task`
--

CREATE TABLE `easytodo_task` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `ledger_id` int(10) UNSIGNED DEFAULT NULL COMMENT '所属账本，为NULL表示全局',
  `title` varchar(500) NOT NULL COMMENT '任务标题',
  `description` text DEFAULT NULL COMMENT '任务描述',
  `completed` tinyint(3) UNSIGNED DEFAULT 0 COMMENT '是否完成：0=未完成，1=已完成',
  `pinned` tinyint(3) UNSIGNED DEFAULT 0 COMMENT '是否置顶：0=否，1=是',
  `task_date` date DEFAULT NULL COMMENT '任务日期',
  `tags` varchar(500) DEFAULT NULL COMMENT '标签，JSON数组格式：[{"color":"#fff","text":"工作"}]',
  `color` varchar(20) DEFAULT 'blue',
  `recurrence` varchar(20) DEFAULT 'none',
  `reminder_at` datetime DEFAULT NULL,
  `reminder_advance` int(11) DEFAULT 0,
  `trans_time` datetime DEFAULT current_timestamp() COMMENT '创建时间',
  `completed_at` datetime DEFAULT NULL COMMENT '完成时间',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序权重'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `email_pushes`
--

CREATE TABLE `email_pushes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` mediumtext NOT NULL,
  `scope` enum('all','selected') NOT NULL DEFAULT 'all' COMMENT 'all=全量推送，selected=指定用户',
  `scheduled_at` datetime NOT NULL COMMENT '计划发送时间',
  `sent_at` datetime DEFAULT NULL COMMENT '最近一次实际发送时间',
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `email_push_recipients`
--

CREATE TABLE `email_push_recipients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `push_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(100) NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `error_message` varchar(255) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `email_tokens`
--

CREATE TABLE `email_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(100) NOT NULL,
  `type` enum('register','reset_password','token_view') NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `category` varchar(20) NOT NULL COMMENT 'suggest / bug / other',
  `content` text NOT NULL,
  `images` text DEFAULT NULL COMMENT 'JSON 数组，存储相对路径',
  `status` enum('pending','resolved','closed') NOT NULL DEFAULT 'pending',
  `admin_reply` text DEFAULT NULL,
  `admin_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_reply_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `feedback_messages`
--

CREATE TABLE `feedback_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `feedback_id` bigint(20) UNSIGNED NOT NULL,
  `sender_type` enum('user','admin') NOT NULL,
  `sender_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `content` text NOT NULL,
  `images` text DEFAULT NULL COMMENT 'JSON array of relative paths',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `finance_deposit`
--

CREATE TABLE `finance_deposit` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `deposit_date` date NOT NULL COMMENT '存款日期',
  `amount` decimal(12,2) NOT NULL COMMENT '存款金额',
  `method` varchar(20) DEFAULT '存单' COMMENT '存款方式(存单/存折/硬卡/其它)',
  `maturity_date` date DEFAULT NULL COMMENT '到期时间',
  `annual_rate` decimal(6,4) DEFAULT NULL COMMENT '年化利率(如0.025表示2.5%)',
  `estimated_interest` decimal(12,2) DEFAULT NULL COMMENT '预估利息',
  `auto_renew` tinyint(1) DEFAULT 0 COMMENT '自动续期',
  `notes` text DEFAULT NULL COMMENT '备注',
  `status` varchar(10) DEFAULT 'active' COMMENT '状态(active存续/withdrawn已取出)',
  `withdraw_date` date DEFAULT NULL COMMENT '取出日期',
  `withdraw_principal` decimal(12,2) DEFAULT NULL COMMENT '取出本金',
  `withdraw_interest` decimal(12,2) DEFAULT NULL COMMENT '获得利息',
  `withdraw_notes` text DEFAULT NULL COMMENT '取出备注',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `forum_accounts`
--

CREATE TABLE `forum_accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `forum_name` varchar(100) NOT NULL COMMENT '论坛名称',
  `forum_url` varchar(500) NOT NULL COMMENT '论坛地址',
  `username` varchar(100) NOT NULL,
  `password` varchar(500) NOT NULL COMMENT 'AES加密存储',
  `enable_notice` tinyint(1) DEFAULT 0 COMMENT '接收通知',
  `enable_mention_reply` tinyint(1) DEFAULT 0 COMMENT '@提及自动回复',
  `mention_reply_mode` varchar(20) DEFAULT 'ai' COMMENT '@提及回复模式: ai/random/custom',
  `notice_interval` int(11) DEFAULT 15 COMMENT '通知检查间隔(分钟)',
  `enable_signin` tinyint(1) DEFAULT 0 COMMENT '自动签到',
  `enable_autoreply` tinyint(1) DEFAULT 0 COMMENT '自动回帖',
  `reply_mode` enum('custom','random','ai','smart') DEFAULT 'random',
  `custom_reply` text DEFAULT NULL COMMENT '自定义回帖内容（多条换行）',
  `ai_reply_flag` varchar(50) DEFAULT '[AI回帖]' COMMENT 'AI回帖标识',
  `signin_time` time DEFAULT '08:00:00' COMMENT '签到时间',
  `signin_url` varchar(500) DEFAULT '' COMMENT '自定义签到URL',
  `signin_params` varchar(500) DEFAULT '' COMMENT '自定义签到参数',
  `reply_time` time DEFAULT '09:00:00' COMMENT '回帖时间',
  `reply_interval` int(11) NOT NULL DEFAULT 10 COMMENT '回帖间隔秒数',
  `auto_reply_interval` int(11) DEFAULT 30 COMMENT '自动回帖间隔(分钟)',
  `last_signin` date DEFAULT NULL,
  `last_notice_check` datetime DEFAULT NULL,
  `last_reply` datetime DEFAULT NULL COMMENT '上次自动回帖时间',
  `cookie_data` text DEFAULT NULL COMMENT '登录Cookie缓存',
  `cookie_expire` datetime DEFAULT NULL COMMENT 'Cookie过期时间',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='论坛账号配置';

-- --------------------------------------------------------

--
-- 表的结构 `forum_action_logs`
--

CREATE TABLE `forum_action_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `action_type` enum('signin','reply','notice','login','error') NOT NULL,
  `target_info` varchar(500) DEFAULT NULL COMMENT '目标帖子标题等',
  `result` varchar(200) NOT NULL COMMENT '操作结果',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `forum_replied_threads`
--

CREATE TABLE `forum_replied_threads` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `tid` int(11) NOT NULL COMMENT '帖子TID',
  `title` varchar(500) DEFAULT '' COMMENT '帖子标题',
  `replied_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='论坛已回复帖子记录';

-- --------------------------------------------------------

--
-- 表的结构 `goals`
--

CREATE TABLE `goals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `target_amount` decimal(12,2) NOT NULL,
  `saved_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deadline` date DEFAULT NULL,
  `status` enum('active','done','archived') NOT NULL DEFAULT 'active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `goal_transaction_links`
--

CREATE TABLE `goal_transaction_links` (
  `id` int(10) UNSIGNED NOT NULL,
  `goal_id` int(10) UNSIGNED NOT NULL,
  `transaction_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `icon_library`
--

CREATE TABLE `icon_library` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `system_icon_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `ip_blacklist`
--

CREATE TABLE `ip_blacklist` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(500) DEFAULT '',
  `added_by` varchar(100) DEFAULT 'manual',
  `created_at` int(11) NOT NULL,
  `expires_at` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `ip_geo_cache`
--

CREATE TABLE `ip_geo_cache` (
  `ip_address` varchar(45) NOT NULL,
  `country_code` varchar(2) DEFAULT NULL,
  `country_name` varchar(100) DEFAULT NULL,
  `cached_at` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `ip_whitelist`
--

CREATE TABLE `ip_whitelist` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `remark` varchar(500) DEFAULT '',
  `added_by` varchar(100) DEFAULT 'manual',
  `created_at` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `items`
--

CREATE TABLE `items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `icon_type` varchar(20) DEFAULT NULL COMMENT '图标类型：file/svg',
  `icon_value` text DEFAULT NULL COMMENT '图标值：文件相对路径或 SVG 代码',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `ledgers`
--

CREATE TABLE `ledgers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('personal','shared') NOT NULL DEFAULT 'personal',
  `name` varchar(100) NOT NULL,
  `owner_user_id` bigint(20) UNSIGNED NOT NULL,
  `invite_code` varchar(32) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `ledger_members`
--

CREATE TABLE `ledger_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ledger_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('admin','member') NOT NULL DEFAULT 'member',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `license_pricing`
--

CREATE TABLE `license_pricing` (
  `id` int(11) NOT NULL,
  `first_month_price` decimal(10,2) DEFAULT NULL,
  `first_month_price_promo` decimal(10,2) DEFAULT NULL,
  `first_year_price` decimal(10,2) DEFAULT NULL,
  `first_year_price_promo` decimal(10,2) DEFAULT NULL,
  `first_lifetime_price` decimal(10,2) DEFAULT NULL,
  `first_lifetime_price_promo` decimal(10,2) DEFAULT NULL,
  `change_price` decimal(10,2) DEFAULT NULL,
  `change_price_promo` decimal(10,2) DEFAULT NULL,
  `is_promo_active` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `license_requests`
--

CREATE TABLE `license_requests` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `request_type` varchar(50) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `pay_proof_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `license_users`
--

CREATE TABLE `license_users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `license_code` varchar(255) DEFAULT NULL,
  `license_status` varchar(50) DEFAULT NULL,
  `license_type` varchar(50) DEFAULT NULL,
  `license_period` varchar(50) DEFAULT NULL,
  `domain_change_quota` int(11) DEFAULT NULL,
  `domain_change_used` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `account` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` int(11) NOT NULL,
  `success` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `login_history`
--

CREATE TABLE `login_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `ip` varchar(45) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `device_type` varchar(20) DEFAULT 'unknown',
  `device_fingerprint` varchar(64) DEFAULT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_anomalous` tinyint(1) NOT NULL DEFAULT 0,
  `anomalous_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `login_tokens`
--

CREATE TABLE `login_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `status` enum('pending','confirmed','expired') NOT NULL DEFAULT 'pending',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `miniapps`
--

CREATE TABLE `miniapps` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '小程序名称',
  `qrcode_path` varchar(500) NOT NULL DEFAULT '' COMMENT '小程序码图片路径',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='小程序配置';

-- --------------------------------------------------------

--
-- 表的结构 `nav_bookmarks`
--

CREATE TABLE `nav_bookmarks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `url` varchar(1000) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_type` varchar(20) DEFAULT NULL,
  `icon_value` text DEFAULT NULL,
  `screenshot` varchar(500) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `show_on_home` tinyint(1) DEFAULT 0 COMMENT '是否在首页显示',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `nav_bookmark_urls`
--

CREATE TABLE `nav_bookmark_urls` (
  `id` int(11) NOT NULL,
  `bookmark_id` int(11) NOT NULL,
  `url` varchar(1000) NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `nav_groups`
--

CREATE TABLE `nav_groups` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon_type` varchar(20) DEFAULT NULL,
  `icon_value` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `nav_pushes`
--

CREATE TABLE `nav_pushes` (
  `id` int(11) NOT NULL,
  `bookmark_id` int(11) NOT NULL,
  `pushed_by` int(11) NOT NULL,
  `target_user_id` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `password_vault`
--

CREATE TABLE `password_vault` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `username` varchar(255) NOT NULL DEFAULT '',
  `password` text NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `reimbursement`
--

CREATE TABLE `reimbursement` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL COMMENT '创建用户ID',
  `ledger_id` int(11) UNSIGNED NOT NULL COMMENT '账本ID',
  `title` varchar(255) NOT NULL COMMENT '报销标题',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '报销金额',
  `category_id` int(11) UNSIGNED DEFAULT NULL COMMENT '关联分类ID',
  `transaction_id` int(11) UNSIGNED DEFAULT NULL COMMENT '关联记账记录ID',
  `description` text DEFAULT NULL COMMENT '报销说明',
  `receipt_path` varchar(500) DEFAULT NULL COMMENT '凭证文件路径',
  `status` enum('pending','approved','reimbursed','rejected') NOT NULL DEFAULT 'pending' COMMENT '状态',
  `reimbursed_at` datetime DEFAULT NULL COMMENT '报销完成时间',
  `reimbursed_by` int(11) UNSIGNED DEFAULT NULL COMMENT '报销操作人ID',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='报销记录表';

-- --------------------------------------------------------

--
-- 表的结构 `reimbursement_config`
--

CREATE TABLE `reimbursement_config` (
  `id` int(10) UNSIGNED NOT NULL,
  `ledger_id` int(10) UNSIGNED NOT NULL,
  `enabled` tinyint(3) UNSIGNED DEFAULT 1 COMMENT '是否启用报销功能（1=启用，0=禁用）',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `repayment_plan`
--

CREATE TABLE `repayment_plan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `debt_name` varchar(255) NOT NULL COMMENT '债务名称（如信用卡、网贷等）',
  `total_amount` decimal(15,2) NOT NULL COMMENT '总欠款金额',
  `total_periods` int(11) NOT NULL COMMENT '总期数',
  `current_period` int(11) DEFAULT 1 COMMENT '当前期数',
  `remaining_periods` int(11) GENERATED ALWAYS AS (`total_periods` - `current_period` + 1) STORED COMMENT '剩余期数',
  `period_amount` decimal(15,2) NOT NULL COMMENT '每期还款金额',
  `remaining_amount` decimal(15,2) GENERATED ALWAYS AS (`period_amount` * `remaining_periods`) STORED COMMENT '剩余金额',
  `next_repayment_date` date DEFAULT NULL COMMENT '下次还款日期',
  `status` enum('active','completed','overdue') DEFAULT 'active' COMMENT '状态',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='还款计划表';

-- --------------------------------------------------------

--
-- 表的结构 `repayment_record`
--

CREATE TABLE `repayment_record` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL COMMENT '关联的还款计划ID',
  `user_id` int(11) NOT NULL,
  `period_number` int(11) NOT NULL COMMENT '第几期',
  `amount` decimal(15,2) NOT NULL COMMENT '还款金额',
  `repayment_date` date NOT NULL COMMENT '还款日期',
  `repayment_method` varchar(50) DEFAULT NULL COMMENT '还款方式（支付宝/微信/银行转账等）',
  `status` enum('success','failed','pending') DEFAULT 'success' COMMENT '还款状态',
  `remark` text DEFAULT NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='还款记录表';

-- --------------------------------------------------------

--
-- 表的结构 `resume_data`
--

CREATE TABLE `resume_data` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT '未命名简历' COMMENT '简历名称',
  `template` varchar(50) DEFAULT 'simple',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '创建时间',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `security_events`
--

CREATE TABLE `security_events` (
  `id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL DEFAULT 'login',
  `source_ip` varchar(45) DEFAULT NULL,
  `source_port` int(10) UNSIGNED DEFAULT NULL,
  `target` varchar(255) DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `severity` varchar(20) NOT NULL DEFAULT 'low',
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `attack_type` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `security_policy`
--

CREATE TABLE `security_policy` (
  `id` int(11) NOT NULL,
  `key` varchar(80) NOT NULL,
  `value` text NOT NULL,
  `updated_at` int(11) NOT NULL,
  `updated_by` varchar(100) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `platform` varchar(255) NOT NULL,
  `icon_type` varchar(20) DEFAULT NULL,
  `icon_value` varchar(255) DEFAULT NULL,
  `type` enum('subscription','lifetime') NOT NULL DEFAULT 'subscription',
  `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
  `period` enum('day','week','month','quarter','year') DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expire_date` date DEFAULT NULL,
  `status` enum('active','closed') NOT NULL DEFAULT 'active',
  `remark` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `system_icon_changes`
--

CREATE TABLE `system_icon_changes` (
  `id` int(11) NOT NULL,
  `action` varchar(16) NOT NULL,
  `system_icon_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `system_icon_cleanup_logs`
--

CREATE TABLE `system_icon_cleanup_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `system_icon_library`
--

CREATE TABLE `system_icon_library` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `source_type` varchar(16) DEFAULT NULL,
  `source_mode` varchar(16) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `system_icon_submissions`
--

CREATE TABLE `system_icon_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_action` enum('publish','replace') DEFAULT NULL,
  `review_note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `system_settings`
--

CREATE TABLE `system_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `site_name` varchar(100) NOT NULL,
  `site_url` varchar(255) DEFAULT NULL,
  `site_icon_svg` mediumtext DEFAULT NULL,
  `allow_register` tinyint(4) NOT NULL DEFAULT 1,
  `session_timeout_hours` smallint(5) UNSIGNED NOT NULL DEFAULT 24,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `bind_qr_text` text DEFAULT NULL,
  `icp_number` varchar(128) DEFAULT NULL,
  `ai_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'AI 功能启用状态',
  `ai_provider` varchar(50) NOT NULL DEFAULT 'baidu' COMMENT 'AI 提供商（baidu）',
  `ai_model` varchar(100) NOT NULL DEFAULT 'ernie-3.5-8k' COMMENT 'AI 模型名称',
  `ai_api_key` varchar(255) DEFAULT NULL COMMENT 'AI API Key',
  `ai_secret_key` varchar(255) DEFAULT NULL COMMENT 'AI Secret Key',
  `ai_system_prompt` longtext DEFAULT NULL COMMENT 'AI 系统提示词',
  `ai_model_name` varchar(255) DEFAULT NULL COMMENT '模型展示名称',
  `ai_api_url` varchar(1024) DEFAULT NULL COMMENT 'AI API 网关地址',
  `ai_default_model` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认模型',
  `ai_image_model` varchar(255) DEFAULT NULL,
  `bg_image_path` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(20) NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `from_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `to_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `trans_time` datetime NOT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `source` varchar(20) DEFAULT 'manual',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `transaction_attachments`
--

CREATE TABLE `transaction_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` bigint(20) UNSIGNED NOT NULL,
  `relative_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `personal_ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `active_ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `avatar_path` varchar(255) DEFAULT NULL COMMENT '用户头像文件相对路径（uploads 下）',
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `email_verified` tinyint(4) NOT NULL DEFAULT 0,
  `failed_login_count` int(11) NOT NULL DEFAULT 0,
  `login_lock_until` datetime DEFAULT NULL,
  `theme_mode` enum('light','dark') NOT NULL DEFAULT 'light',
  `budget_reminder_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否开启预算接近上限/超支提醒',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `enable_transfer` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否启用账户间转账功能',
  `allow_negative_balance` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否允许账户为负数',
  `system_icon_last_sync_change_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump data for table `users`
--

INSERT INTO `users` (`id`, `personal_ledger_id`, `active_ledger_id`, `username`, `nickname`, `avatar_path`, `email`, `password_hash`, `role`, `status`, `email_verified`, `failed_login_count`, `login_lock_until`, `theme_mode`, `budget_reminder_enabled`, `created_at`, `updated_at`, `enable_transfer`, `allow_negative_balance`, `system_icon_last_sync_change_id`) VALUES
(1, NULL, NULL, 'admin', 'Admin', NULL, 'admin@example.com', '$2y$10$f0MpMzeILHXuxLwgnAlOgevQHOr.xeMJ9tg/wHgatn.NsgyxAsCpO', 'admin', 1, 1, 0, NULL, 'light', 1, NOW(), NOW(), 1, 1, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `user_wechat_bindings`
--

CREATE TABLE `user_wechat_bindings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `openid` varchar(64) NOT NULL,
  `unionid` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_accounts_user` (`user_id`),
  ADD KEY `fk_accounts_group` (`group_id`);

--
-- 表的索引 `account_groups`
--
ALTER TABLE `account_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- 表的索引 `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_announcements_scheduled_at` (`scheduled_at`);

--
-- 表的索引 `announcement_reads`
--
ALTER TABLE `announcement_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_announcement_user` (`announcement_id`,`user_id`),
  ADD KEY `idx_announcement_reads_announcement` (`announcement_id`),
  ADD KEY `idx_announcement_reads_user` (`user_id`);

--
-- 表的索引 `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_token` (`token`),
  ADD KEY `fk_token_user` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- 表的索引 `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assets_user` (`user_id`),
  ADD KEY `idx_assets_status` (`status`);

--
-- 表的索引 `bg_images`
--
ALTER TABLE `bg_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created` (`created_at`);

--
-- 表的索引 `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- 表的索引 `book_progress`
--
ALTER TABLE `book_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_book` (`user_id`,`book_id`);

--
-- 表的索引 `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_budget` (`user_id`,`year`,`month`,`type`,`category_id`,`item_id`),
  ADD KEY `fk_budget_category` (`category_id`),
  ADD KEY `fk_budget_item` (`item_id`);

--
-- 表的索引 `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_type_name` (`user_id`,`type`,`name`);

--
-- 表的索引 `debt_config`
--
ALTER TABLE `debt_config`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_ledger` (`user_id`,`ledger_id`),
  ADD KEY `idx_status` (`status`);

--
-- 表的索引 `debt_payment`
--
ALTER TABLE `debt_payment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_debt_config` (`debt_config_id`),
  ADD KEY `idx_user_ledger` (`user_id`,`ledger_id`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_status` (`status`);

--
-- 表的索引 `easytodo_clipboard_history`
--
ALTER TABLE `easytodo_clipboard_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- 表的索引 `easytodo_command`
--
ALTER TABLE `easytodo_command`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_trigger_user` (`trigger`,`user_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- 表的索引 `easytodo_countdown`
--
ALTER TABLE `easytodo_countdown`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_target_time` (`target_time`),
  ADD KEY `idx_enabled` (`enabled`);

--
-- 表的索引 `easytodo_memo`
--
ALTER TABLE `easytodo_memo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- 表的索引 `easytodo_pomodoro_session`
--
ALTER TABLE `easytodo_pomodoro_session`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_started` (`started_at`);

--
-- 表的索引 `easytodo_pomodoro_setting`
--
ALTER TABLE `easytodo_pomodoro_setting`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- 表的索引 `easytodo_report`
--
ALTER TABLE `easytodo_report`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_generated` (`generated_at`);

--
-- 表的索引 `easytodo_task`
--
ALTER TABLE `easytodo_task`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_ledger` (`ledger_id`),
  ADD KEY `idx_task_date` (`task_date`),
  ADD KEY `idx_completed` (`completed`);

--
-- 表的索引 `email_pushes`
--
ALTER TABLE `email_pushes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_pushes_status_scheduled` (`status`,`scheduled_at`);

--
-- 表的索引 `email_push_recipients`
--
ALTER TABLE `email_push_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_push_recipients_push` (`push_id`),
  ADD KEY `idx_email_push_recipients_user` (`user_id`);

--
-- 表的索引 `email_tokens`
--
ALTER TABLE `email_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_email_tokens_user` (`user_id`);

--
-- 表的索引 `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_feedbacks_user` (`user_id`),
  ADD KEY `idx_feedbacks_status` (`status`);

--
-- 表的索引 `feedback_messages`
--
ALTER TABLE `feedback_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_feedback` (`feedback_id`);

--
-- 表的索引 `finance_deposit`
--
ALTER TABLE `finance_deposit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- 表的索引 `forum_accounts`
--
ALTER TABLE `forum_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_signin_time` (`signin_time`),
  ADD KEY `idx_reply_time` (`reply_time`);

--
-- 表的索引 `forum_action_logs`
--
ALTER TABLE `forum_action_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_account` (`account_id`);

--
-- 表的索引 `forum_replied_threads`
--
ALTER TABLE `forum_replied_threads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_account_tid` (`account_id`,`tid`),
  ADD KEY `idx_account` (`account_id`),
  ADD KEY `idx_replied_at` (`replied_at`);

--
-- 表的索引 `goals`
--
ALTER TABLE `goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_goals_user` (`user_id`),
  ADD KEY `idx_goals_ledger` (`ledger_id`),
  ADD KEY `idx_goals_status` (`status`);

--
-- 表的索引 `goal_transaction_links`
--
ALTER TABLE `goal_transaction_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_goal_tx` (`goal_id`,`transaction_id`),
  ADD KEY `idx_goal` (`goal_id`),
  ADD KEY `idx_tx` (`transaction_id`);

--
-- 表的索引 `icon_library`
--
ALTER TABLE `icon_library`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_icon_library_user` (`user_id`);

--
-- 表的索引 `ip_blacklist`
--
ALTER TABLE `ip_blacklist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- 表的索引 `ip_geo_cache`
--
ALTER TABLE `ip_geo_cache`
  ADD PRIMARY KEY (`ip_address`),
  ADD KEY `idx_cached_at` (`cached_at`);

--
-- 表的索引 `ip_whitelist`
--
ALTER TABLE `ip_whitelist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`),
  ADD KEY `idx_ip` (`ip_address`);

--
-- 表的索引 `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_cat_name` (`user_id`,`category_id`,`name`),
  ADD KEY `fk_items_category` (`category_id`);

--
-- 表的索引 `ledgers`
--
ALTER TABLE `ledgers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_owner_type` (`owner_user_id`,`type`),
  ADD UNIQUE KEY `uniq_invite_code` (`invite_code`);

--
-- 表的索引 `ledger_members`
--
ALTER TABLE `ledger_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_ledger_user` (`ledger_id`,`user_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- 表的索引 `license_pricing`
--
ALTER TABLE `license_pricing`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `license_requests`
--
ALTER TABLE `license_requests`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `license_users`
--
ALTER TABLE `license_users`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_account_time` (`account`,`attempt_time`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_time`),
  ADD KEY `idx_success_time` (`success`,`attempt_time`);

--
-- 表的索引 `login_history`
--
ALTER TABLE `login_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_login_at` (`login_at`),
  ADD KEY `idx_ip` (`ip`);

--
-- 表的索引 `login_tokens`
--
ALTER TABLE `login_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_token` (`token`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- 表的索引 `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- 表的索引 `miniapps`
--
ALTER TABLE `miniapps`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `nav_bookmarks`
--
ALTER TABLE `nav_bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_group` (`group_id`);

--
-- 表的索引 `nav_bookmark_urls`
--
ALTER TABLE `nav_bookmark_urls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bookmark` (`bookmark_id`);

--
-- 表的索引 `nav_groups`
--
ALTER TABLE `nav_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- 表的索引 `nav_pushes`
--
ALTER TABLE `nav_pushes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bookmark` (`bookmark_id`),
  ADD KEY `idx_target` (`target_user_id`);

--
-- 表的索引 `password_vault`
--
ALTER TABLE `password_vault`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- 表的索引 `reimbursement`
--
ALTER TABLE `reimbursement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ledger_status` (`ledger_id`,`status`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_reimbursed_at` (`reimbursed_at`);

--
-- 表的索引 `reimbursement_config`
--
ALTER TABLE `reimbursement_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ledger_id` (`ledger_id`);

--
-- 表的索引 `repayment_plan`
--
ALTER TABLE `repayment_plan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- 表的索引 `repayment_record`
--
ALTER TABLE `repayment_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_plan_id` (`plan_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- 表的索引 `resume_data`
--
ALTER TABLE `resume_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- 表的索引 `security_events`
--
ALTER TABLE `security_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- 表的索引 `security_policy`
--
ALTER TABLE `security_policy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- 表的索引 `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subscriptions_user` (`user_id`),
  ADD KEY `idx_subscriptions_status` (`status`),
  ADD KEY `idx_subscriptions_expire` (`expire_date`);

--
-- 表的索引 `system_icon_changes`
--
ALTER TABLE `system_icon_changes`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `system_icon_cleanup_logs`
--
ALTER TABLE `system_icon_cleanup_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`read_at`);

--
-- 表的索引 `system_icon_library`
--
ALTER TABLE `system_icon_library`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_system_icon_path` (`file_path`);

--
-- 表的索引 `system_icon_submissions`
--
ALTER TABLE `system_icon_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_system_icon_submissions_user` (`user_id`),
  ADD KEY `idx_system_icon_submissions_status` (`status`),
  ADD KEY `idx_system_icon_submissions_name` (`name`),
  ADD KEY `idx_sis_user_path_id` (`user_id`,`file_path`,`id`),
  ADD KEY `idx_sis_user_status_id` (`user_id`,`status`,`id`),
  ADD KEY `idx_sis_status_id` (`status`,`id`);

--
-- 表的索引 `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tx_user` (`user_id`),
  ADD KEY `fk_tx_category` (`category_id`),
  ADD KEY `fk_tx_item` (`item_id`),
  ADD KEY `fk_tx_from_account` (`from_account_id`),
  ADD KEY `fk_tx_to_account` (`to_account_id`);

--
-- 表的索引 `transaction_attachments`
--
ALTER TABLE `transaction_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaction` (`transaction_id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 表的索引 `user_wechat_bindings`
--
ALTER TABLE `user_wechat_bindings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_wechat_openid` (`openid`),
  ADD KEY `fk_wechat_user` (`user_id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- 使用表AUTO_INCREMENT `account_groups`
--
ALTER TABLE `account_groups`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- 使用表AUTO_INCREMENT `announcement_reads`
--
ALTER TABLE `announcement_reads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=246;

--
-- 使用表AUTO_INCREMENT `api_tokens`
--
ALTER TABLE `api_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=584;

--
-- 使用表AUTO_INCREMENT `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- 使用表AUTO_INCREMENT `bg_images`
--
ALTER TABLE `bg_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `book_progress`
--
ALTER TABLE `book_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=528;

--
-- 使用表AUTO_INCREMENT `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- 使用表AUTO_INCREMENT `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=634;

--
-- 使用表AUTO_INCREMENT `debt_config`
--
ALTER TABLE `debt_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- 使用表AUTO_INCREMENT `debt_payment`
--
ALTER TABLE `debt_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- 使用表AUTO_INCREMENT `easytodo_clipboard_history`
--
ALTER TABLE `easytodo_clipboard_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `easytodo_command`
--
ALTER TABLE `easytodo_command`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `easytodo_countdown`
--
ALTER TABLE `easytodo_countdown`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `easytodo_memo`
--
ALTER TABLE `easytodo_memo`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `easytodo_pomodoro_session`
--
ALTER TABLE `easytodo_pomodoro_session`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `easytodo_pomodoro_setting`
--
ALTER TABLE `easytodo_pomodoro_setting`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `easytodo_report`
--
ALTER TABLE `easytodo_report`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `easytodo_task`
--
ALTER TABLE `easytodo_task`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `email_pushes`
--
ALTER TABLE `email_pushes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `email_push_recipients`
--
ALTER TABLE `email_push_recipients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `email_tokens`
--
ALTER TABLE `email_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- 使用表AUTO_INCREMENT `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `feedback_messages`
--
ALTER TABLE `feedback_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `finance_deposit`
--
ALTER TABLE `finance_deposit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `forum_accounts`
--
ALTER TABLE `forum_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `forum_action_logs`
--
ALTER TABLE `forum_action_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6687;

--
-- 使用表AUTO_INCREMENT `forum_replied_threads`
--
ALTER TABLE `forum_replied_threads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- 使用表AUTO_INCREMENT `goals`
--
ALTER TABLE `goals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `goal_transaction_links`
--
ALTER TABLE `goal_transaction_links`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `icon_library`
--
ALTER TABLE `icon_library`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=138;

--
-- 使用表AUTO_INCREMENT `ip_blacklist`
--
ALTER TABLE `ip_blacklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ip_whitelist`
--
ALTER TABLE `ip_whitelist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `items`
--
ALTER TABLE `items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1483;

--
-- 使用表AUTO_INCREMENT `ledgers`
--
ALTER TABLE `ledgers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- 使用表AUTO_INCREMENT `ledger_members`
--
ALTER TABLE `ledger_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- 使用表AUTO_INCREMENT `license_pricing`
--
ALTER TABLE `license_pricing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `license_requests`
--
ALTER TABLE `license_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `license_users`
--
ALTER TABLE `license_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- 使用表AUTO_INCREMENT `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `login_tokens`
--
ALTER TABLE `login_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- 使用表AUTO_INCREMENT `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- 使用表AUTO_INCREMENT `miniapps`
--
ALTER TABLE `miniapps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `nav_bookmarks`
--
ALTER TABLE `nav_bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- 使用表AUTO_INCREMENT `nav_bookmark_urls`
--
ALTER TABLE `nav_bookmark_urls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- 使用表AUTO_INCREMENT `nav_groups`
--
ALTER TABLE `nav_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `nav_pushes`
--
ALTER TABLE `nav_pushes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `password_vault`
--
ALTER TABLE `password_vault`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `reimbursement`
--
ALTER TABLE `reimbursement`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `reimbursement_config`
--
ALTER TABLE `reimbursement_config`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `repayment_plan`
--
ALTER TABLE `repayment_plan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `repayment_record`
--
ALTER TABLE `repayment_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `resume_data`
--
ALTER TABLE `resume_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- 使用表AUTO_INCREMENT `security_events`
--
ALTER TABLE `security_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `security_policy`
--
ALTER TABLE `security_policy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- 使用表AUTO_INCREMENT `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- 使用表AUTO_INCREMENT `system_icon_changes`
--
ALTER TABLE `system_icon_changes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- 使用表AUTO_INCREMENT `system_icon_cleanup_logs`
--
ALTER TABLE `system_icon_cleanup_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `system_icon_library`
--
ALTER TABLE `system_icon_library`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- 使用表AUTO_INCREMENT `system_icon_submissions`
--
ALTER TABLE `system_icon_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- 使用表AUTO_INCREMENT `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1006;

--
-- 使用表AUTO_INCREMENT `transaction_attachments`
--
ALTER TABLE `transaction_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=498;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- 使用表AUTO_INCREMENT `user_wechat_bindings`
--
ALTER TABLE `user_wechat_bindings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- 限制导出的表
--

--
-- 限制表 `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `fk_accounts_group` FOREIGN KEY (`group_id`) REFERENCES `account_groups` (`id`),
  ADD CONSTRAINT `fk_accounts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `announcement_reads`
--
ALTER TABLE `announcement_reads`
  ADD CONSTRAINT `fk_announcement_reads_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_announcement_reads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD CONSTRAINT `fk_token_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `fk_budget_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_budget_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_budget_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `email_push_recipients`
--
ALTER TABLE `email_push_recipients`
  ADD CONSTRAINT `fk_email_push_recipients_push` FOREIGN KEY (`push_id`) REFERENCES `email_pushes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_email_push_recipients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `email_tokens`
--
ALTER TABLE `email_tokens`
  ADD CONSTRAINT `fk_email_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `icon_library`
--
ALTER TABLE `icon_library`
  ADD CONSTRAINT `fk_icon_library_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_items_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `nav_bookmark_urls`
--
ALTER TABLE `nav_bookmark_urls`
  ADD CONSTRAINT `nav_bookmark_urls_ibfk_1` FOREIGN KEY (`bookmark_id`) REFERENCES `nav_bookmarks` (`id`) ON DELETE CASCADE;

--
-- 限制表 `nav_pushes`
--
ALTER TABLE `nav_pushes`
  ADD CONSTRAINT `nav_pushes_ibfk_1` FOREIGN KEY (`bookmark_id`) REFERENCES `nav_bookmarks` (`id`) ON DELETE CASCADE;

--
-- 限制表 `repayment_record`
--
ALTER TABLE `repayment_record`
  ADD CONSTRAINT `repayment_record_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `repayment_plan` (`id`) ON DELETE CASCADE;

--
-- 限制表 `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_tx_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_tx_from_account` FOREIGN KEY (`from_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_to_account` FOREIGN KEY (`to_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `user_wechat_bindings`
--
ALTER TABLE `user_wechat_bindings`
  ADD CONSTRAINT `fk_wechat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
