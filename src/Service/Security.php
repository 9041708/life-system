<?php
namespace App\Service;

use App\Service\Database;

/**
 * 安全服务：防爆破攻击、IP 限制、登录追踪、黑白名单、地区策略
 */
class Security
{
    // ==================== 登录尝试记录 ====================

    /**
     * 记录登录尝试
     */
    public static function recordLoginAttempt(string $account, string $ip = '', bool $success = false): void
    {
        if (empty($ip)) {
            $ip = self::getClientIp();
        }

        try {
            $pdo = Database::getConnection();
            // 自动建表（首次调用时）
            if (!self::tableExists('login_attempts')) {
                $pdo->exec(
                    'CREATE TABLE IF NOT EXISTS login_attempts (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        account VARCHAR(255) NOT NULL,
                        ip_address VARCHAR(45) NOT NULL,
                        attempt_time INT NOT NULL,
                        success TINYINT(1) DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_account_time (account, attempt_time),
                        INDEX idx_ip_time (ip_address, attempt_time),
                        INDEX idx_success_time (success, attempt_time)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
                );
            }
            $stmt = $pdo->prepare(
                'INSERT INTO login_attempts (account, ip_address, attempt_time, success) 
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $account,
                $ip,
                time(),
                $success ? 1 : 0,
            ]);
        } catch (\Throwable $e) {
            error_log('记录登录尝试失败: ' . $e->getMessage());
        }
    }

    // ==================== 账户锁定 ====================

    /**
     * 检查账户是否被锁定
     */
    public static function isAccountLocked(string $account): ?array
    {
        try {
            $pdo = Database::getConnection();
            $config = Config::get('security', []);

            $maxAttempts = (int)($config['login_max_attempts'] ?? 5);
            $lockoutMinutes = (int)($config['login_lockout_minutes'] ?? 15);
            $window = (int)($config['login_attempt_window'] ?? 300);

            $timeThreshold = time() - $window;

            if (!self::tableExists('login_attempts')) {
                return null;
            }

            $stmt = $pdo->prepare(
                'SELECT COUNT(*) as fail_count, MAX(attempt_time) as last_attempt 
                 FROM login_attempts 
                 WHERE account = ? AND success = 0 AND attempt_time > ? 
                 GROUP BY account'
            );
            $stmt->execute([$account, $timeThreshold]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result && $result['fail_count'] >= $maxAttempts) {
                $lockUntil = $result['last_attempt'] + ($lockoutMinutes * 60);
                $now = time();

                if ($now < $lockUntil) {
                    return [
                        'locked' => true,
                        'until' => date('Y-m-d H:i:s', $lockUntil),
                        'seconds_remaining' => $lockUntil - $now,
                    ];
                }
            }

            return null;
        } catch (\Throwable $e) {
            error_log('检查账户锁定失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 解锁账户（清除该账户的失败记录）
     */
    public static function unlockAccount(string $account): array
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE account = ? AND success = 0');
            $stmt->execute([$account]);
            return ['success' => true, 'deleted' => $stmt->rowCount()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 获取所有被锁定的账户
     */
    public static function getLockedAccounts(): array
    {
        try {
            $pdo = Database::getConnection();
            $config = Config::get('security', []);
            $maxAttempts = (int)($config['login_max_attempts'] ?? 5);
            $lockoutMinutes = (int)($config['login_lockout_minutes'] ?? 15);
            $window = (int)($config['login_attempt_window'] ?? 300);
            $timeThreshold = time() - $window;

            $stmt = $pdo->prepare(
                'SELECT account, MAX(attempt_time) as last_attempt, COUNT(*) as fail_count
                 FROM login_attempts
                 WHERE success = 0 AND attempt_time > ?
                 GROUP BY account
                 HAVING fail_count >= ?'
            );
            $stmt->execute([$timeThreshold, $maxAttempts]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $locked = [];
            foreach ($rows as $row) {
                $lockUntil = (int)$row['last_attempt'] + ($lockoutMinutes * 60);
                if (time() < $lockUntil) {
                    $locked[] = [
                        'account' => $row['account'],
                        'until' => date('Y-m-d H:i:s', $lockUntil),
                        'seconds_remaining' => $lockUntil - time(),
                    ];
                }
            }
            return $locked;
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ==================== IP 黑白名单 ====================

    /**
     * 初始化黑白名单表（如果不存在）
     */
    private static function ensureIpListTables(): void
    {
        $pdo = Database::getConnection();

        // 黑名单表
        $pdo->exec('CREATE TABLE IF NOT EXISTS ip_blacklist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL UNIQUE,
            reason VARCHAR(500) DEFAULT "",
            added_by VARCHAR(100) DEFAULT "manual",
            created_at INT NOT NULL,
            expires_at INT DEFAULT 0,
            INDEX idx_ip (ip_address),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        // 白名单表
        $pdo->exec('CREATE TABLE IF NOT EXISTS ip_whitelist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL UNIQUE,
            remark VARCHAR(500) DEFAULT "",
            added_by VARCHAR(100) DEFAULT "manual",
            created_at INT NOT NULL,
            INDEX idx_ip (ip_address)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        // IP 地理缓存表（GeoIP 加速，有效期7天）
        $pdo->exec('CREATE TABLE IF NOT EXISTS ip_geo_cache (
            ip_address VARCHAR(45) NOT NULL PRIMARY KEY,
            country_code VARCHAR(2),
            country_name VARCHAR(100),
            cached_at INT NOT NULL,
            INDEX idx_cached_at (cached_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        // 地区策略表
        $pdo->exec('CREATE TABLE IF NOT EXISTS security_policy (
            id INT AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(80) NOT NULL UNIQUE,
            `value` TEXT NOT NULL,
            updated_at INT NOT NULL,
            updated_by VARCHAR(100) DEFAULT ""
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    /**
     * 获取黑名单列表
     */
    public static function getBlacklist(int $limit = 100, int $offset = 0): array
    {
        self::ensureIpListTables();
        try {
            $pdo = Database::getConnection();
            $now = time();

            // 同时返回已过期但未清理的记录，由调用方决定是否过滤
            $stmt = $pdo->prepare("
                SELECT id, ip_address, reason, added_by, created_at, expires_at,
                       CASE WHEN expires_at > 0 AND expires_at < :now THEN 1 ELSE 0 END as expired
                FROM ip_blacklist
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':now', $now, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('获取黑名单失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取黑名单总数
     */
    public static function getBlacklistCount(): int
    {
        self::ensureIpListTables();
        try {
            $pdo = Database::getConnection();
            return (int)$pdo->query("SELECT COUNT(*) FROM ip_blacklist")->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 添加 IP 到黑名单
     */
    public static function addToBlacklist(string $ip, string $reason = '', int $durationSeconds = 0, string $addedBy = 'manual'): array
    {
        self::ensureIpListTables();
        
        $ip = trim($ip);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['success' => false, 'error' => '无效的 IP 地址'];
        }

        try {
            $pdo = Database::getConnection();
            $expiresAt = $durationSeconds > 0 ? time() + $durationSeconds : 0;

            $stmt = $pdo->prepare(
                'INSERT INTO ip_blacklist (ip_address, reason, added_by, created_at, expires_at) 
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE reason = ?, expires_at = ?, added_by = ?'
            );
            $stmt->execute([$ip, $reason, $addedBy, time(), $expiresAt, $reason, $expiresAt, $addedBy]);

            return ['success' => true, 'message' => "IP {$ip} 已加入黑名单"];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 从黑名单移除 IP
     */
    public static function removeFromBlacklist(string $ip): array
    {
        self::ensureIpListTables();
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('DELETE FROM ip_blacklist WHERE ip_address = ?');
            $stmt->execute([trim($ip)]);
            $count = $stmt->rowCount();
            return [
                'success' => true,
                'deleted' => $count,
                'message' => $count > 0 ? "已从黑名单移除" : "该 IP 不在黑名单中",
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 批量从黑名单移除
     */
    public static function batchRemoveFromBlacklist(array $ips): array
    {
        self::ensureIpListTables();
        try {
            $pdo = Database::getConnection();
            $placeholders = implode(',', array_fill(0, count($ips), '?'));
            $stmt = $pdo->prepare("DELETE FROM ip_blacklist WHERE ip_address IN ($placeholders)");
            $stmt->execute($ips);
            return ['success' => true, 'deleted' => $stmt->rowCount()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 检查 IP 是否在黑名单中
     */
    public static function isIpInBlacklist(string $ip = ''): bool
    {
        if (empty($ip)) $ip = self::getClientIp();
        self::ensureIpListTables();
        try {
            $pdo = Database::getConnection();
            $now = time();
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM ip_blacklist 
                 WHERE ip_address = ? AND (expires_at = 0 OR expires_at > ?)'
            );
            $stmt->execute([$ip, $now]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取白名单列表
     */
    public static function getWhitelist(int $limit = 100, int $offset = 0): array
    {
        self::ensureIpListTables();
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT id, ip_address, remark, added_by, created_at
                FROM ip_whitelist
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('获取白名单失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取白名单总数
     */
    public static function getWhitelistCount(): int
    {
        self::ensureIpListTables();
        try {
            $pdo = Database::getConnection();
            return (int)$pdo->query("SELECT COUNT(*) FROM ip_whitelist")->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 添加 IP 到白名单
     */
    public static function addToWhitelist(string $ip, string $remark = '', string $addedBy = 'manual'): array
    {
        self::ensureIpListTables();
        
        $ip = trim($ip);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['success' => false, 'error' => '无效的 IP 地址'];
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO ip_whitelist (ip_address, remark, added_by, created_at) 
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE remark = ?, added_by = ?'
            );
            $stmt->execute([$ip, $remark, $addedBy, time(), $remark, $addedBy]);

            return ['success' => true, 'message' => "IP {$ip} 已加入白名单"];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 从白名单移除 IP
     */
    public static function removeFromWhitelist(string $ip): array
    {
        self::ensureIpListTables();
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('DELETE FROM ip_whitelist WHERE ip_address = ?');
            $stmt->execute([trim($ip)]);
            $count = $stmt->rowCount();
            return [
                'success' => true,
                'deleted' => $count,
                'message' => $count > 0 ? "已从白名单移除" : "该 IP 不在白名单中",
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 批量从白名单移除
     */
    public static function batchRemoveFromWhitelist(array $ips): array
    {
        self::ensureIpListTables();
        try {
            $pdo = Database::getConnection();
            $placeholders = implode(',', array_fill(0, count($ips), '?'));
            $stmt = $pdo->prepare("DELETE FROM ip_whitelist WHERE ip_address IN ($placeholders)");
            $stmt->execute($ips);
            return ['success' => true, 'deleted' => $stmt->rowCount()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 检查 IP 是否在白名单中
     */
    public static function isIpInWhitelist(string $ip = ''): bool
    {
        if (empty($ip)) $ip = self::getClientIp();
        self::ensureIpListTables();
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM ip_whitelist WHERE ip_address = ?');
            $stmt->execute([$ip]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ==================== 地区访问策略 ====================

    /**
     * 获取安全策略配置
     */
    public static function getPolicy(string $key = '', $default = null)
    {
        self::ensureIpListTables();
        try {
            $pdo = Database::getConnection();
            if ($key !== '') {
                $stmt = $pdo->prepare('SELECT `value` FROM security_policy WHERE `key` = ?');
                $stmt->execute([$key]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                return $row ? $row['value'] : $default;
            }
            $rows = $pdo->query('SELECT `key`, `value` FROM security_policy')->fetchAll(\PDO::FETCH_ASSOC);
            $result = [];
            foreach ($rows as $r) {
                $result[$r['key']] = $r['value'];
            }
            return $result;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * 设置安全策略配置
     */
    public static function setPolicy(string $key, string $value, string $updatedBy = ''): array
    {
        self::ensureIpListTables();
        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare(
                'INSERT INTO security_policy (`key`, `value`, updated_at, updated_by) 
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE `value` = ?, updated_at = ?, updated_by = ?'
            );
            $now = time();
            $stmt->execute([$key, $value, $now, $updatedBy, $value, $now, $updatedBy]);
            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 批量设置策略
     */
    public static function setPolicies(array $policies, string $updatedBy = ''): array
    {
        self::ensureIpListTables();
        try {
            $pdo = Database::getConnection();
            $now = time();
            foreach ($policies as $key => $value) {
                $stmt = $pdo->prepare(
                    'INSERT INTO security_policy (`key`, `value`, updated_at, updated_by) 
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE `value` = ?, updated_at = ?, updated_by = ?'
                );
                $stmt->execute([$key, $value, $now, $updatedBy, $value, $now, $updatedBy]);
            }
            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 获取所有策略配置（用于页面渲染）
     */
    public static function getAllPolicies(): array
    {
        $defaults = [
            'geo_mode'             => 'off',       // off | monitor | auto_block
            'geo_allowed_regions'  => 'CN,HK,MO,TW', // 允许的地区代码
            'auto_block_duration'  => 'permanent',   // temporary | permanent
            'auto_block_minutes'   => '1440',        // 临时拉黑的分钟数
            'login_lock_threshold' => '5',            // 连续失败锁定阈值
            'login_lock_duration'  => '3',            // 锁定时长（分钟）
            'ip_ban_threshold'     => '10',           // IP 封禁阈值
            'ip_ban_duration'      => '60',           // IP 封禁时长（分钟）
        ];

        $stored = self::getPolicy('', []);
        
        // 合并默认值
        foreach ($defaults as $k => $v) {
            if (!isset($stored[$k])) {
                $stored[$k] = $v;
            }
        }

        return $stored;
    }

    // ==================== 综合检查入口 ====================

    /**
     * 检查 IP 是否应该被阻止（综合：黑名单 + 地区策略）
     */
    public static function shouldBlockIp(string $ip = ''): ?string
    {
        if (empty($ip)) $ip = self::getClientIp();

        // 白名单优先放行
        if (self::isIpInWhitelist($ip)) {
            return null; // 放行
        }

        // 黑名单拦截
        if (self::isIpInBlacklist($ip)) {
            return 'blacklisted';
        }

        // 地区策略检查
        $policy = self::getAllPolicies();
        $mode = $policy['geo_mode'] ?? 'off';
        if ($mode === 'auto_block') {
            $region = self::getIpRegion($ip);
            $allowed = explode(',', $policy['geo_allowed_regions'] ?? '');
            $allowed = array_map('trim', $allowed);
            if ($region && !in_array($region, $allowed)) {
                return 'geo_blocked';
            }
        }

        return null;
    }

    /**
     * 获取 IP 归属地区代码（简化版，实际可接入 GeoIP 库）
     */
    public static function getIpRegion(string $ip): ?string
    {
        if (empty($ip)) {
            return null;
        }
        // 跳过私有/保留 IP
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return null;
        }

        self::ensureIpListTables();
        $pdo = \App\Database::getConnection();

        // 先查缓存（有效期7天）
        $stmt = $pdo->prepare('SELECT country_code, cached_at FROM ip_geo_cache WHERE ip_address = ? AND cached_at > ?');
        $sevenDaysAgo = time() - 7 * 86400;
        $stmt->execute([$ip, $sevenDaysAgo]);
        $cached = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($cached) {
            return $cached['country_code'] ?: null;
        }

        // 调用 API
        $result = self::queryGeoApi($ip);
        if ($result === null) {
            return null;
        }

        // 写入缓存
        $stmt = $pdo->prepare('REPLACE INTO ip_geo_cache (ip_address, country_code, country_name, cached_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([$ip, $result['countryCode'], $result['countryName'], time()]);

        return $result['countryCode'] ?: null;
    }

    // ==================== 工具方法 ====================

    /**
     * 获取客户端 IP
     */

    /**
     * 调用 GeoIP API（ip-api.com 免费接口，无需 Key）
     * 超时 2 秒，失败返回 null
     */
    private static function queryGeoApi(string $ip): ?array
    {
        $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=status,message,countryCode,country&lang=zh-CN';
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 2,
                'ignore_errors' => true,
            ]
        ]);
        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            error_log('GeoIP API request failed for IP: ' . $ip);
            return null;
        }
        $data = json_decode($response, true);
        if (!$data || ($data['status'] ?? '') !== 'success') {
            return null;
        }
        return [
            'countryCode' => strtoupper($data['countryCode'] ?? ''),
            'countryName' => $data['country'] ?? '',
        ];
    }

    public static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return self::sanitizeIp($_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return self::sanitizeIp(trim($ips[0]));
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return self::sanitizeIp($_SERVER['HTTP_X_REAL_IP']);
        }
        return self::sanitizeIp($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /**
     * 清理 IP 地址
     */
    private static function sanitizeIp(string $ip): string
    {
        $ip = trim($ip);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        return '0.0.0.0';
    }

    /**
     * 检查是否存在表
     */
    private static function tableExists(string $tableName): bool
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return (bool)$stmt->fetch();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取登录统计
     */
    public static function getLoginStats(string $account = '', int $hoursBack = 24): array
    {
        try {
            if (!self::tableExists('login_attempts')) {
                return [];
            }

            $pdo = Database::getConnection();
            $timeThreshold = time() - ($hoursBack * 3600);

            $sql = 'SELECT 
                      success,
                      COUNT(*) as count,
                      COUNT(DISTINCT ip_address) as unique_ips
                    FROM login_attempts 
                    WHERE attempt_time > ?';

            $params = [$timeThreshold];

            if (!empty($account)) {
                $sql .= ' AND account = ?';
                $params[] = $account;
            }

            $sql .= ' GROUP BY success';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $stats = [
                'success_count' => 0,
                'failed_count' => 0,
                'total_attempts' => 0,
                'unique_ips' => 0,
            ];

            foreach ($results as $row) {
                $count = (int)$row['count'];
                $stats['total_attempts'] += $count;

                if ($row['success']) {
                    $stats['success_count'] = $count;
                } else {
                    $stats['failed_count'] = $count;
                }

                if ((int)$row['success'] === 0) {
                    $stats['unique_ips'] = (int)$row['unique_ips'];
                }
            }

            return $stats;
        } catch (\Throwable $e) {
            error_log('获取登录统计失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 清理过期的登录尝试记录
     */
    public static function cleanupOldAttempts(int $daysBefore = 30): void
    {
        try {
            if (!self::tableExists('login_attempts')) {
                return;
            }

            $pdo = Database::getConnection();
            $timeThreshold = time() - ($daysBefore * 24 * 3600);

            $stmt = $pdo->prepare(
                'DELETE FROM login_attempts WHERE attempt_time < ?'
            );
            $stmt->execute([$timeThreshold]);
        } catch (\Throwable $e) {
            error_log('清理登录记录失败: ' . $e->getMessage());
        }
    }

    /**
     * 清理过期黑名单记录
     */
    public static function cleanupExpiredBlacklist(): int
    {
        self::ensureIpListTables();
        try {
            $pdo = Database::getConnection();
            $now = time();
            $stmt = $pdo->prepare('DELETE FROM ip_blacklist WHERE expires_at > 0 AND expires_at < ?');
            $stmt->execute([$now]);
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 初始化所有安全相关表（一键建表）
     */
    public static function initializeTables(): array
    {
        try {
            $pdo = Database::getConnection();
            $errors = [];

            // login_attempts 表
            try {
                $pdo->exec(
                    'CREATE TABLE IF NOT EXISTS login_attempts (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        account VARCHAR(255) NOT NULL,
                        ip_address VARCHAR(45) NOT NULL,
                        attempt_time INT NOT NULL,
                        success TINYINT(1) DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_account_time (account, attempt_time),
                        INDEX idx_ip_time (ip_address, attempt_time),
                        INDEX idx_success_time (success, attempt_time)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
                );
            } catch (\Throwable $e) {
                $errors[] = 'login_attempts: ' . $e->getMessage();
            }

            // 黑白名单和策略表
            self::ensureIpListTables();

            return [
                'success' => empty($errors),
                'errors' => $errors,
                'message' => empty($errors) ? '安全相关表初始化成功' : '部分表初始化失败',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()],
                'message' => '初始化失败',
            ];
        }
    }

    /**
     * 检查 IP 是否被自动规则封锁（基于失败次数）
     */
    public static function isIpBlocked(string $ip = ''): bool
    {
        try {
            if (empty($ip)) {
                $ip = self::getClientIp();
            }

            $config = Config::get('security', []);
            if (!($config['enable_ip_blocking'] ?? false)) {
                return false;
            }

            if (!self::tableExists('login_attempts')) {
                return false;
            }

            $pdo = Database::getConnection();
            $blockDuration = (int)($config['ip_blacklist_duration'] ?? 3600);
            $timeThreshold = time() - $blockDuration;

            $stmt = $pdo->prepare(
                'SELECT COUNT(*) as count FROM login_attempts 
                 WHERE ip_address = ? AND success = 0 
                 AND attempt_time > ? 
                 GROUP BY ip_address
                 LIMIT 1'
            );
            $stmt->execute([$ip, $timeThreshold]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result && ($result['count'] >= 10);
        } catch (\Throwable $e) {
            error_log('检查 IP 黑名单失败: ' . $e->getMessage());
            return false;
        }
    }
}
