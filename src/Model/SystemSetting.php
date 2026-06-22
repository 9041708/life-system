<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class SystemSetting
{
    public static ?array $cache = null;

    public static function get(): array
    {
        if (self::$cache !== null) return self::$cache;
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM system_settings WHERE id = 1 LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $row = [
                'site_name' => 'SanS三石记账系统',
                'site_url' => null,
                'site_icon_svg' => null,
                'allow_register' => 1,
                // 默认空表情况下的会话超时时间（小时）
                'session_timeout_hours' => 24,
                // 绑定二维码默认有效期（分钟）
                'bind_qr_expires_minutes' => 10,
                // 绑定二维码下方提示文案
                'bind_qr_text' => '打开微信小程序“SanS三石记账”，进入绑定页面扫码完成绑定。',
                // 授权相关字段默认值（老库可能不存在这些字段）
                'license_email' => null,
                'license_code' => null,
                'license_status' => null,
                'license_last_check_at' => null,
                // 授权后台配置：通知邮箱与部署包下载地址
                'license_admin_email' => null,
                'license_source_path' => null,
                // AI 配置相关字段
                'ai_enabled' => 0,
                'ai_provider' => 'baidu',
                'ai_model_name' => null,
                'ai_model' => 'ernie-3.5-8k',
                'ai_image_model' => null,
                'ai_api_url' => null,
                'ai_api_key' => null,
                'ai_secret_key' => null,
                'ai_system_prompt' => '你是一个智能记账助手，可以帮助用户记录收支流水。请根据用户的描述，智能识别交易类型、金额、分类等信息，并提供友好的记账建议。',
                'ai_default_model' => 0,
            ];
        } else {
            // 兼容旧数据表中可能不存在的字段
            if (!array_key_exists('site_icon_svg', $row)) {
                $row['site_icon_svg'] = null;
            }
            if (!array_key_exists('session_timeout_hours', $row)) {
                $row['session_timeout_hours'] = 24;
            }
            if (!array_key_exists('bind_qr_expires_minutes', $row)) {
                $row['bind_qr_expires_minutes'] = 10;
            }
            if (!array_key_exists('bind_qr_text', $row)) {
                $row['bind_qr_text'] = '打开微信小程序"SanS三石记账"，进入绑定页面扫码完成绑定。';
            }
            if (!array_key_exists('admin_contact', $row)) {
                $row['admin_contact'] = null;
            }
            if (!array_key_exists('admin_qrcode_image', $row)) {
                $row['admin_qrcode_image'] = null;
            }
            if (!array_key_exists('license_email', $row)) {
                $row['license_email'] = null;
            }
            if (!array_key_exists('license_code', $row)) {
                $row['license_code'] = null;
            }
            if (!array_key_exists('license_status', $row)) {
                $row['license_status'] = null;
            }
            if (!array_key_exists('license_last_check_at', $row)) {
                $row['license_last_check_at'] = null;
            }
            if (!array_key_exists('license_admin_email', $row)) {
                $row['license_admin_email'] = null;
            }
            if (!array_key_exists('license_source_path', $row)) {
                $row['license_source_path'] = null;
            }
            if (!array_key_exists('ai_enabled', $row)) {
                $row['ai_enabled'] = 0;
            }
            if (!array_key_exists('ai_provider', $row)) {
                $row['ai_provider'] = 'baidu';
            }
            if (!array_key_exists('ai_model', $row)) {
                $row['ai_model'] = 'ernie-3.5-8k';
            }
            if (!array_key_exists('ai_image_model', $row)) {
                $row['ai_image_model'] = null;
            }
            if (!array_key_exists('ai_api_key', $row)) {
                $row['ai_api_key'] = null;
            }
            if (!array_key_exists('ai_secret_key', $row)) {
                $row['ai_secret_key'] = null;
            }
            if (!array_key_exists('ai_system_prompt', $row)) {
                $row['ai_system_prompt'] = '你是一个智能记账助手，可以帮助用户记录收支流水。请根据用户的描述，智能识别交易类型、金额、分类等信息，并提供友好的记账建议。';
            }
            if (!array_key_exists('ai_model_name', $row)) {
                $row['ai_model_name'] = null;
            }
            if (!array_key_exists('ai_api_url', $row)) {
                $row['ai_api_url'] = null;
            }
            if (!array_key_exists('ai_default_model', $row)) {
                $row['ai_default_model'] = 0;
            }
        }
        self::$cache = $row;
        return $row;
    }

    public static function update(string $siteName, ?string $siteUrl, bool $allowRegister, ?string $siteIconSvg, ?int $sessionTimeoutHours = null, ?int $bindQrExpiresMinutes = null, ?string $bindQrText = null, ?bool $aiEnabled = null, ?string $aiProvider = null, ?string $aiModel = null, ?string $aiImageModel = null, ?string $aiApiKey = null, ?string $aiSecretKey = null, ?string $aiSystemPrompt = null, ?string $aiModelName = null, ?string $aiApiUrl = null, ?bool $aiDefaultModel = null, ?string $bgImagePath = null): void
    {
        $pdo = Database::getConnection();
        // 规范和限制会话超时时间（小时），范围 1~168 小时，默认 24 小时
        $timeout = $sessionTimeoutHours ?? 24;
        if ($timeout <= 0) {
            $timeout = 24;
        } elseif ($timeout > 168) {
            $timeout = 168;
        }

        // 绑定二维码有效期（分钟），1~1440 之间
        $bindMinutes = $bindQrExpiresMinutes ?? 10;
        if ($bindMinutes <= 0) {
            $bindMinutes = 10;
        } elseif ($bindMinutes > 1440) {
            $bindMinutes = 1440;
        }

        // 确保 AI 辅助字段存在，便于旧库自动扩展
        self::ensureColumnExists($pdo, 'ai_image_model', 'VARCHAR(255) DEFAULT NULL');
        self::ensureColumnExists($pdo, 'bg_image_path', 'VARCHAR(500) DEFAULT NULL');

        // 检查当前数据库中是否已存在相关字段，避免旧库报错
        $hasTimeoutColumn = false;
        $hasBindMinutesColumn = false;
        $hasBindTextColumn = false;
        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'session_timeout_hours'");
            $hasTimeoutColumn = (bool)$colStmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasTimeoutColumn = false;
        }

        try {
            $colStmt2 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'bind_qr_expires_minutes'");
            $hasBindMinutesColumn = (bool)$colStmt2->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasBindMinutesColumn = false;
        }

        try {
            $colStmt3 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'bind_qr_text'");
            $hasBindTextColumn = (bool)$colStmt3->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasBindTextColumn = false;
        }

        // 检查 AI 配置字段是否存在
        $hasAiEnabled = false;
        $hasAiProvider = false;
        $hasAiModel = false;
        $hasAiModelName = false;
        $hasAiApiUrl = false;
        $hasAiApiKey = false;
        $hasAiSecretKey = false;
        $hasAiSystemPrompt = false;
        $hasAiDefaultModel = false;
        try {
            $colStmt4 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'ai_enabled'");
            $hasAiEnabled = (bool)$colStmt4->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasAiEnabled = false;
        }
        try {
            $colStmt5 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'ai_provider'");
            $hasAiProvider = (bool)$colStmt5->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasAiProvider = false;
        }
        try {
            $colStmt6 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'ai_model'");
            $hasAiModel = (bool)$colStmt6->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasAiModel = false;
        }
        try {
            $colStmt7 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'ai_model_name'");
            $hasAiModelName = (bool)$colStmt7->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasAiModelName = false;
        }
        try {
            $colStmt8 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'ai_api_url'");
            $hasAiApiUrl = (bool)$colStmt8->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasAiApiUrl = false;
        }
        try {
            $colStmt9 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'ai_api_key'");
            $hasAiApiKey = (bool)$colStmt9->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasAiApiKey = false;
        }
        try {
            $colStmt10 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'ai_secret_key'");
            $hasAiSecretKey = (bool)$colStmt10->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasAiSecretKey = false;
        }
        try {
            $colStmt11 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'ai_system_prompt'");
            $hasAiSystemPrompt = (bool)$colStmt11->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasAiSystemPrompt = false;
        }
        try {
            $colStmt12 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'ai_image_model'");
            $hasAiImageModel = (bool)$colStmt12->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasAiImageModel = false;
        }
        try {
            $colStmt13 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'ai_default_model'");
            $hasAiDefaultModel = (bool)$colStmt13->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasAiDefaultModel = false;
        }

        $hasBgImagePath = false;
        try {
            $colStmtBg = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'bg_image_path'");
            $hasBgImagePath = (bool)$colStmtBg->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasBgImagePath = false;
        }

        // 构造动态 SQL，仅在对应字段存在时才更新，避免旧库报错
        $setParts = ['site_name = :name', 'site_url = :url', 'site_icon_svg = :icon', 'allow_register = :ar'];
        $params = [
            ':name' => $siteName,
            ':url' => $siteUrl,
            ':icon' => $siteIconSvg,
            ':ar' => $allowRegister ? 1 : 0,
        ];

        if ($hasTimeoutColumn) {
            $setParts[] = 'session_timeout_hours = :timeout';
            $params[':timeout'] = $timeout;
        }
        if ($hasBindMinutesColumn) {
            $setParts[] = 'bind_qr_expires_minutes = :bind_minutes';
            $params[':bind_minutes'] = $bindMinutes;
        }
        if ($hasBindTextColumn) {
            $setParts[] = 'bind_qr_text = :bind_text';
            $params[':bind_text'] = $bindQrText;
        }
        if ($hasAiEnabled && $aiEnabled !== null) {
            $setParts[] = 'ai_enabled = :ai_enabled';
            $params[':ai_enabled'] = $aiEnabled ? 1 : 0;
        }
        if ($hasAiProvider && $aiProvider !== null) {
            $setParts[] = 'ai_provider = :ai_provider';
            $params[':ai_provider'] = $aiProvider;
        }
        if ($hasAiModelName && $aiModelName !== null) {
            $setParts[] = 'ai_model_name = :ai_model_name';
            $params[':ai_model_name'] = $aiModelName;
        }
        if ($hasAiModel && $aiModel !== null) {
            $setParts[] = 'ai_model = :ai_model';
            $params[':ai_model'] = $aiModel;
        }
        if ($hasAiImageModel && $aiImageModel !== null) {
            $setParts[] = 'ai_image_model = :ai_image_model';
            $params[':ai_image_model'] = $aiImageModel;
        }
        if ($hasAiApiUrl && $aiApiUrl !== null) {
            $setParts[] = 'ai_api_url = :ai_api_url';
            $params[':ai_api_url'] = $aiApiUrl;
        }
        if ($hasAiApiKey && $aiApiKey !== null) {
            $setParts[] = 'ai_api_key = :ai_api_key';
            $params[':ai_api_key'] = $aiApiKey;
        }
        if ($hasAiSecretKey && $aiSecretKey !== null) {
            $setParts[] = 'ai_secret_key = :ai_secret_key';
            $params[':ai_secret_key'] = $aiSecretKey;
        }
        if ($hasAiSystemPrompt && $aiSystemPrompt !== null) {
            $setParts[] = 'ai_system_prompt = :ai_system_prompt';
            $params[':ai_system_prompt'] = $aiSystemPrompt;
        }
        if ($hasAiDefaultModel && $aiDefaultModel !== null) {
            $setParts[] = 'ai_default_model = :ai_default_model';
            $params[':ai_default_model'] = $aiDefaultModel ? 1 : 0;
        }

        // bg_image_path：始终尝试写入（null 时清空字段）
        try {
            $setParts[] = 'bg_image_path = :bg_image_path';
            $params[':bg_image_path'] = $bgImagePath;
        } catch (\Throwable $eBg) {
            // 列不存在且无法自动创建时跳过，不影响其他配置保存
        }

        $sql = 'UPDATE system_settings SET ' . implode(', ', $setParts) . ' WHERE id = 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    private static function ensureColumnExists(PDO $pdo, string $column, string $definition): void
    {
        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM system_settings LIKE '$column'");
            if (!$colStmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec("ALTER TABLE system_settings ADD COLUMN `$column` $definition");
            }
        } catch (\Throwable $e) {
            // 忽略无法自动创建字段的情况下的错误，避免影响其他配置保存
        }
        self::$cache = null;
    }

    /**
     * 更新本地授权相关配置与最近一次联机校验结果。
     * 为兼容旧库，所有字段均在确认存在后才更新。
     */
    public static function updateLicense(?string $email, ?string $code, ?string $status, ?string $lastCheckAt): void
    {
        $pdo = Database::getConnection();

        $hasEmail = false;
        $hasCode = false;
        $hasStatus = false;
        $hasLastCheck = false;
        try {
            $c1 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'license_email'");
            $hasEmail = (bool)$c1->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasEmail = false;
        }
        try {
            $c2 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'license_code'");
            $hasCode = (bool)$c2->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasCode = false;
        }
        try {
            $c3 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'license_status'");
            $hasStatus = (bool)$c3->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasStatus = false;
        }
        try {
            $c4 = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'license_last_check_at'");
            $hasLastCheck = (bool)$c4->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasLastCheck = false;
        }

        $setParts = [];
        $params = [];

        if ($hasEmail) {
            $setParts[] = 'license_email = :license_email';
            $params[':license_email'] = $email;
        }
        if ($hasCode) {
            $setParts[] = 'license_code = :license_code';
            $params[':license_code'] = $code;
        }
        if ($hasStatus) {
            $setParts[] = 'license_status = :license_status';
            $params[':license_status'] = $status;
        }
        if ($hasLastCheck) {
            $setParts[] = 'license_last_check_at = :license_last_check_at';
            $params[':license_last_check_at'] = $lastCheckAt;
        }

        if (empty($setParts)) {
            return;
        }

        $sql = 'UPDATE system_settings SET ' . implode(', ', $setParts) . ' WHERE id = 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * 更新部署授权页所使用的 PC 客户端部署包下载地址。
     * 仅在当前库存在 license_source_path 字段时才会执行更新，以兼容旧库。
     */
    public static function updateLicenseSourcePath(?string $path): void
    {
        $pdo = Database::getConnection();

        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM system_settings LIKE 'license_source_path'");
            $hasColumn = (bool)$colStmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasColumn = false;
        }

        if (!$hasColumn) {
            return;
        }

        $stmt = $pdo->prepare('UPDATE system_settings SET license_source_path = :path WHERE id = 1');
        $stmt->execute([':path' => $path]);
    }
}
