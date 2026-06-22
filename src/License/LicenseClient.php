<?php
namespace App\License;

class LicenseClient
{
    private static string $keyFile = '';
    private static string $publicKey = '';

    public static function init(): void
    {
        self::$keyFile = __DIR__ . '/../../data/key.php';
        $pubKeyFile = __DIR__ . '/../../config/license_public.key';
        if (!file_exists($pubKeyFile)) {
            // 首次使用：将私钥登录主站后台 /license/admin.php 生成，公钥放入此文件
            trigger_error('license_public.key not found. Please generate from admin panel.', E_USER_WARNING);
            self::$publicKey = '';
            return;
        }
        self::$publicKey = file_get_contents($pubKeyFile);
    }

    public static function isActivated(): bool
    {
        $d = self::read();
        if (!$d) return false;
        $expire = $d['expire_date'] ?? '2000-01-01';
        // 2099年为永久授权
        if ($expire >= '2099-01-01') return true;
        return $expire >= date('Y-m-d');
    }

    public static function getInfo(): ?array
    {
        return self::read() ?: null;
    }

    public static function getTrialDays(): int
    {
        $installedFile = __DIR__ . '/../../data/installed_at';
        if (!file_exists($installedFile)) {
            $dir = dirname($installedFile);
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            @file_put_contents($installedFile, date('Y-m-d'));
        }
        $content = @file_get_contents($installedFile);
        $installed = $content ? strtotime($content) : time();
        $days = (int)((time() - $installed) / 86400);
        return max(0, 30 - $days);
    }

    public static function isExpired(): bool
    {
        return self::getTrialDays() <= 0 && !self::isActivated();
    }

    public static function check(): array
    {
        self::init();
        return [
            'trial_days' => self::getTrialDays(),
            'activated' => self::isActivated(),
            'expired' => self::isExpired(),
            'info' => self::getInfo(),
        ];
    }

    private static function read(): ?array
    {
        if (!self::$keyFile) self::init();
        if (!file_exists(self::$keyFile)) return null;
        $data = @include self::$keyFile;
        if (!is_array($data) || empty($data['signature'])) return null;
        if (self::$publicKey === '' || str_starts_with(self::$publicKey, '#')) return null; // 公钥未配置

        $sig = @base64_decode($data['signature']);
        unset($data['signature'], $data['sig_date']);
        $payload = json_encode($data);

        $valid = @openssl_verify($payload, $sig, self::$publicKey, OPENSSL_ALGO_SHA256);
        if ($valid !== 1) return null;

        return $data;
    }
}
