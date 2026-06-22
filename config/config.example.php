<?php
declare(strict_types=1);

return array (
  'db' => 
  array (
    'host' => 'localhost',
    'dbname' => 'YOUR_DB_NAME',
    'user' => 'root',
    'pass' => 'YOUR_DB_PASSWORD',
    'charset' => 'utf8mb4',
  ),
  'mail' => 
  array (
    'driver' => 'mail',
    'host' => 'smtp.example.com',
    'port' => 465,
    'encryption' => 'ssl',
    'username' => 'you@example.com',
    'password' => 'YOUR_SMTP_PASSWORD',
  ),
  'ai' => 
  array (
    'forum_reply' => 
    array (
      'api_url' => 'https://api.openai.com/v1/chat/completions',
      'api_key' => 'YOUR_AI_API_KEY',
      'model' => 'gpt-3.5-turbo',
      'max_tokens' => 300,
      'temperature' => 0.8,
      'filter_words' => '卧槽,草泥马,TMD,SB,傻逼',
    ),
  ),
  'version' => 'v2.1.3',
  'allow_register' => true,
  'upload_dir' => __DIR__ . '/../uploads',
  'mini_version' => 'v2.1.3',
  'landing_enabled' => true,
  'license_admin_enabled' => true,
  'screenshotmachine_api_key' => '',
  'amap_key' => '',
  'license' => 
  array (
    'client_enabled' => false,
    'server_url' => 'https://your-domain.com',
    'check_interval_hours' => 24,
    'offline_max_days' => 7,
    'trial_days' => 14,
    'grace_days' => 3,
  ),
  'sms' => 
  array (
    'provider' => '',
    'access_key' => '',
    'access_secret' => '',
    'sign_name' => '',
    'template_code' => '',
  ),
  'backup' => 
  array (
    'enabled' => true,
    'keep_versions' => 5,
  ),
  'wechat' => 
  array (
    'miniapp_id' => '',
    'miniapp_secret' => '',
    'share_secret' => 'CHANGE_THIS_SECRET',
  ),
  'cron_token' => 'CHANGE_THIS_TOKEN',
  'encryption_key' => 'CHANGE_THIS_KEY',
);
