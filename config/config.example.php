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
    'from_name' => 'SanS记账系统',
  ),
  'app' =>
  array (
    'name' => 'SanS记账系统',
    'base_url' => '/',
    'site_url' => 'https://your-domain.com',
    'allow_register' => true,
    'upload_dir' => __DIR__ . '/../../uploads',
    'version' => 'v2.0.1',
    'mini_version' => 'v2.0.1',
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
      'api_key' => '',
      'model' => 'deepseek-chat',
      'max_tokens' => 100,
      'temperature' => 0.8,
    ),
  ),
  'wechat' =>
  array (
    'miniapp_appid' => '',
    'miniapp_secret' => '',
    'share_secret' => '',
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
    'enabled' => true,
    'retention_days' => 10,
    'encrypt_backup' => true,
    'encryption_key' => '',
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
    'enabled' => false,
    'check_interval' => 60,
    'log_retention_days' => 30,
    'max_execution_time' => 3600,
  ),
  'cron_token' => '',
);
