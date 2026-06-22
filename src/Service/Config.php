<?php
namespace App\Service;

class Config
{
    private static $config = [];
    private static $configFile = '';

    public static function init(string $file): void
    {
        self::$configFile = $file;
        if (empty(self::$config)) {
            self::$config = require $file;
            // 加载本地覆盖配置（不入GitHub）
            $localFile = dirname($file) . '/config.local.php';
            if (file_exists($localFile)) {
                $local = require $localFile;
                if (is_array($local)) {
                    self::$config = array_replace_recursive(self::$config, $local);
                }
            }
        }
    }

    public static function get($key, $default = null)
    {
        $parts = explode('.', $key);
        $value = self::$config;
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $parts = explode('.', $key);
        $target = &self::$config;
        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $target[$part] = $value;
            } else {
                if (!isset($target[$part]) || !is_array($target[$part])) {
                    $target[$part] = [];
                }
                $target = &$target[$part];
            }
        }

        self::save();
    }

    private static function save(): void
    {
        if (self::$configFile === '') {
            return;
        }

        $content = '<?php' . "\n";
        $content .= 'declare(strict_types=1);' . "\n\n";
        $content .= 'return ' . var_export(self::$config, true) . ';' . "\n";

        // Atomic write: tmp file then rename
        $tmp = self::$configFile . '.tmp.' . uniqid('', true);

        $written = file_put_contents($tmp, $content, LOCK_EX);
        if ($written === false) {
            error_log('[Config] Failed to write tmp file: ' . $tmp);
            return;
        }

        if (!rename($tmp, self::$configFile)) {
            error_log('[Config] Failed to rename tmp to: ' . self::$configFile);
            @unlink($tmp);
            return;
        }

        // Invalidate opcache so next require() reads the new file
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(self::$configFile, true);
        }

        // Touch the file to update mtime (helps some opcache setups)
        touch(self::$configFile);
    }
}


