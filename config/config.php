<?php
declare(strict_types=1);

return array (
  'db' => 
  array (
    'host' => 'localhost',
    'dbname' => 'your_database_name',
    'user' => 'your_db_user',
    'pass' => 'your_db_password',
    'charset' => 'utf8mb4',
  ),
  'mail' => 
  array (
    'driver' => 'mail',
    'host' => 'smtp.example.com',
    'port' => 465,
    'encryption' => 'ssl',
    'username' => 'your_email@example.com',
    'password' => 'your_email_password',
    'from_email' => 'your_email@example.com',
    'from_name' => '个人生活管理平台',
  ),
  'app' => 
  array (
    'name' => '个人生活管理平台',
    'base_url' => '/',
    'site_url' => 'https://your-domain.com',
    'allow_register' => true,
    'upload_dir' => __DIR__ . '/../uploads',
    'version' => 'v2.1.0',
    'mini_version' => 'v2.1.0',
    'landing_enabled' => true,
    'license_admin_enabled' => false,
    'screenshotmachine_api_key' => '',
  ),
  'license' => 
  array (
    'client_enabled' => false,
    'server_url' => 'https://your-domain.com',
    'check_interval_hours' => 24,
    'offline_max_days' => 7,
  ),
  'ai' => 
  array (
    'enabled' => false,
    'provider' => 'qclaw',
    'qclaw_api_url' => 'http://127.0.0.1:5000/parse',
    'qclaw_use_cli' => false,
    'qclaw_cli_path' => '',
    'timeout' => 30,
    'forum_reply' => 
    array (
      'enabled' => false,
      'api_url' => 'https://api.deepseek.com/v1/chat/completions',
      'api_key' => 'your_deepseek_api_key',
      'model' => 'deepseek-chat',
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
    'miniapp_appid' => 'your_miniapp_appid',
    'miniapp_secret' => 'your_miniapp_secret',
    'share_secret' => 'your_share_secret_key',
    'enable_miniapp' => false,
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
    'enabled' => false,
    'retention_days' => 10,
    'encrypt_backup' => true,
    'encryption_key' => 'your_backup_encryption_key',
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
    'notify_email' => 'your_email@example.com',
    'backup_target' => 'database',
  ),
  'scheduler' => 
  array (
    'enabled' => true,
    'check_interval' => 60,
    'log_retention_days' => 30,
    'max_execution_time' => 3600,
  ),
  'cron_token' => 'your_cron_token_here',
);
