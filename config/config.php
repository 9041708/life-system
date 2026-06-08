<?php
declare(strict_types=1);

return array (
  'db' => 
  array (
    'host' => 'localhost',
    'dbname' => 'ssjizhang_cn',
    'user' => 'root',
    'pass' => 'QQcao110..',
    'charset' => 'utf8mb4',
  ),
  'mail' => 
  array (
    'driver' => 'mail',
    'host' => 'smtp.exmail.qq.com',
    'port' => 465,
    'encryption' => 'ssl',
    'username' => 'sanshi@9041708.cn',
    'password' => 'TeNCTefZzmQ3ihXe',
    'from_email' => 'sanshi@9041708.cn',
    'from_name' => '个人生活管理平台',
  ),
  'app' => 
  array (
    'name' => '个人生活管理平台',
    'base_url' => '/',
    'site_url' => 'https://9041708.cn:555',
    'allow_register' => true,
    'upload_dir' => '/volume1/web/ssjizhang.cn_ceshi/config/../uploads',
    'version' => 'v2.1.5',
    'mini_version' => 'v2.1.5',
    'landing_enabled' => true,
    'license_admin_enabled' => true,
    'screenshotmachine_api_key' => '531677',
  ),
  'license' => 
  array (
    'client_enabled' => false,
    'server_url' => 'https://9041708.cn:555',
    'check_interval_hours' => 24,
    'offline_max_days' => 7,
  ),
  'ai' => 
  array (
    'enabled' => true,
    'provider' => 'qclaw',
    'qclaw_api_url' => 'http://127.0.0.1:5000/parse',
    'qclaw_use_cli' => false,
    'qclaw_cli_path' => '',
    'timeout' => 30,
    'forum_reply' => 
    array (
      'enabled' => true,
      'api_url' => 'https://api.deepseek.com/v1/chat/completions',
      'api_key' => 'sk-8e4423c0d6fd4865893e0b396270dd00',
      'model' => 'deepseek-v4-pro',
      'max_tokens' => 300,
      'temperature' => 0.8,
      'filter_words' => 
      array (
        0 => '卧槽',
        1 => '我操',
        2 => '我草',
        3 => '我靠',
        4 => '操你',
        5 => '草泥马',
        6 => '尼玛',
        7 => '特么',
        8 => 'tmd',
        9 => 'TMD',
        10 => 'cnm',
        11 => 'CNM',
        12 => 'nmsl',
        13 => 'NMSL',
        14 => 'sb',
        15 => 'SB',
        16 => '傻逼',
        17 => '傻b',
        18 => '妈蛋',
        19 => '妈的',
        20 => '靠',
        21 => 'shit',
        22 => 'fuck',
      ),
    ),
  ),
  'wechat' => 
  array (
    'miniapp_appid' => 'wx1807d58e8e5f8909',
    'miniapp_secret' => '33560a46b8e1d838f64950e43d4075fe',
    'share_secret' => 'ssjizhang_share_secret_20250108',
    'enable_miniapp' => true,
  ),
  'security' => 
  array (
    'login_max_attempts' => 5,
    'login_lockout_minutes' => 15,
    'login_attempt_window' => 300,
    'enable_ip_blocking' => true,
    'ip_blacklist_duration' => 3600,
  ),
  'backup' => 
  array (
    'enabled' => true,
    'retention_days' => 10,
    'encrypt_backup' => true,
    'encryption_key' => 'sanshi-backup-key-2025',
    'backup_paths' => 
    array (
      'database' => true,
      'uploads' => false,
    ),
    'frequency' => 'weekly',
    'execution_day' => 1,
    'execution_time' => '02:00',
    'keep_versions' => 5,
    'email_notify' => true,
    'notify_email' => '9041708@qq.com',
    'backup_target' => 'database',
    'last_run_time' => 1780405544,
  ),
  'scheduler' => 
  array (
    'enabled' => true,
    'check_interval' => 60,
    'log_retention_days' => 30,
    'max_execution_time' => 3600,
  ),
  'cron_token' => 'ss_cron_a8f3e2b1c9d7',
);
