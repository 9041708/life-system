<?php
namespace App\Service;

use App\Model\Log;

class Logger
{
    public static function log(string $action, ?string $details = null, ?int $userId = null, ?string $username = null): void
    {
        try {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

            Log::create([
                'user_id' => $userId,
                'username' => $username,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'action' => $action,
                'details' => $details,
            ]);
        } catch (\Throwable $e) {
            // 忽略日志记录异常，避免影响主业务
        }
    }
}