<?php
namespace App\Model;

use App\Service\Database;
use PDO;

/**
 * 攻击检测记录模型
 */
class AttackLog
{
    private static function ensureTableExists(): void
    {
        $pdo = Database::getConnection();
        try {
            $pdo->query('SELECT 1 FROM attack_logs LIMIT 1');
        } catch (\Throwable $e) {
            $sql = 'CREATE TABLE IF NOT EXISTS attack_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                attack_type VARCHAR(50) NOT NULL COMMENT "sql_injection|xss|brute_force|crawler|高频异常|other",
                ip_address VARCHAR(45) NOT NULL,
                target_url VARCHAR(500) DEFAULT NULL,
                user_agent TEXT,
                attack_payload TEXT,
                threat_level VARCHAR(10) DEFAULT "low" COMMENT "low|medium|high|critical",
                blocked TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_type_time (attack_type, created_at),
                INDEX idx_ip (ip_address, created_at),
                INDEX idx_level (threat_level, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
            $pdo->exec($sql);
        }
    }

    /**
     * 记录攻击事件
     */
    public static function record(string $type, string $ip, string $userAgent = '', ?string $payload = null, ?string $url = null, string $level = 'low', bool $blocked = false): int
    {
        try {
            self::ensureTableExists();
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('INSERT INTO attack_logs (attack_type, ip_address, target_url, user_agent, attack_payload, threat_level, blocked) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $type,
                $ip,
                substr($url ?? '', 0, 500),
                substr($userAgent, 0, 1000),
                substr($payload ?? '', 0, 2000),
                $level,
                $blocked ? 1 : 0,
            ]);
            return (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            error_log('记录攻击事件失败: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * SQL 注入检测
     */
    public static function detectSqlInjection(string $payload): bool
    {
        $patterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bexec\b|\bexecute\b)/i',
            '/(--|\#|\/\*)/',
            '/(\bor\b.*=.*\bor\b)/i',
            '/(\band\b.*=.*\band\b)/i',
            '/\'\s*(or|and)\s+\'/i',
            '/\bunion\s+all\s+select\b/i',
            '/\binformation_schema\b/i',
            '/\bsleep\s*\(\s*\d+\s*\)/i',
            '/\bbenchmark\s*\(/i',
            '/\bwaitfor\s+delay\b/i',
            '/\bascii\s*\(/i',
            '/\bsubstring\s*\(/i',
            '/\bconvert\s*\(/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $payload)) {
                return true;
            }
        }
        return false;
    }

    /**
     * XSS 检测
     */
    public static function detectXss(string $payload): bool
    {
        $patterns = [
            '/<script[^>]*>/i',
            '/<img[^>]+onerror/i',
            '/<svg[^>]+onload/i',
            '/<iframe/i',
            '/javascript:/i',
            '/onerror\s*=/i',
            '/onload\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i',
            '/<body[^>]*onload/i',
            '/<input[^>]+autofocus/i',
            '/<embed[^>]+src/i',
            '/<object[^>]+data/i',
            '/document\.cookie/i',
            '/document\.write/i',
            '/window\.location/i',
            '/eval\s*\(/i',
            '/alert\s*\(/i',
            '/prompt\s*\(/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $payload)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 爬虫检测
     */
    public static function detectCrawler(string $userAgent): bool
    {
        $crawlers = [
            'googlebot', 'bingbot', 'yandex', 'baiduspider',
            '360spider', 'bytespider', 'bytespider', 'sogou',
            'twitterbot', 'facebookexternalhit', 'applebot',
            'semrushbot', 'ahrefsbot', 'mj12bot', 'dotbot',
            'rogerbot', 'ia_archiver', 'exabot', 'duckduckbot',
            'slurp', 'crawl', 'spider', 'archiver', 'scraper',
        ];

        $ua = strtolower($userAgent);
        foreach ($crawlers as $crawler) {
            if (strpos($ua, $crawler) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 检测并记录攻击（入口方法）
     */
    public static function checkAndRecord(string $ip, string $path, string $userAgent = '', ?string $payload = null): bool
    {
        $isAttack = false;
        $attackType = null;
        $level = 'low';

        // SQL 注入检测
        if ($payload && self::detectSqlInjection($payload)) {
            $isAttack = true;
            $attackType = 'sql_injection';
            $level = 'high';
            self::record('sql_injection', $ip, $userAgent, $payload, $path, 'high', true);
        }

        // XSS 检测
        if ($payload && self::detectXss($payload)) {
            $isAttack = true;
            $attackType = 'xss';
            $level = 'medium';
            self::record('xss', $ip, $userAgent, $payload, $path, 'medium', true);
        }

        // 爬虫检测
        if (self::detectCrawler($userAgent)) {
            $isAttack = true;
            $attackType = 'crawler';
            $level = 'low';
            self::record('crawler', $ip, $userAgent, null, $path, 'low', false);
        }

        return $isAttack;
    }

    /**
     * 获取统计数据
     */
    public static function getStats(int $days = 7): array
    {
        try {
            self::ensureTableExists();
            $pdo = Database::getConnection();
            $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

            $stmt = $pdo->prepare("
                SELECT attack_type, COUNT(*) as count, threat_level
                FROM attack_logs 
                WHERE created_at > ?
                GROUP BY attack_type, threat_level
            ");
            $stmt->execute([$since]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $stats = [
                'sql_injection' => 0,
                'xss' => 0,
                'brute_force' => 0,
                'crawler' => 0,
                'high_freq' => 0,
                'other' => 0,
                'total' => 0,
                'blocked' => 0,
            ];

            foreach ($rows as $row) {
                $type = $row['attack_type'];
                $count = (int)$row['count'];
                if (isset($stats[$type])) {
                    $stats[$type] = $count;
                }
                $stats['total'] += $count;
                if ((int)$row['threat_level'] >= 3) {
                    $stats['blocked'] += $count;
                }
            }

            return $stats;
        } catch (\Throwable $e) {
            return [
                'sql_injection' => 0, 'xss' => 0, 'brute_force' => 0,
                'crawler' => 0, 'high_freq' => 0, 'other' => 0,
                'total' => 0, 'blocked' => 0,
            ];
        }
    }

    /**
     * 获取最近的攻击记录
     */
    public static function getRecent(int $limit = 50): array
    {
        try {
            self::ensureTableExists();
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT * FROM attack_logs ORDER BY created_at DESC LIMIT ?');
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 获取高危 IP 列表（短时间内多次攻击）
     */
    public static function getDangerousIps(int $hoursBack = 24, int $threshold = 5): array
    {
        try {
            self::ensureTableExists();
            $pdo = Database::getConnection();
            $since = date('Y-m-d H:i:s', strtotime("-{$hoursBack} hours"));
            $stmt = $pdo->prepare("
                SELECT ip_address, COUNT(*) as attack_count, MAX(threat_level) as max_level
                FROM attack_logs 
                WHERE created_at > ?
                GROUP BY ip_address 
                HAVING attack_count >= ?
                ORDER BY attack_count DESC
            ");
            $stmt->execute([$since, $threshold]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 获取最近 N 天的攻击趋势（按天统计）
     */
    public static function getTrend(int $days = 7): array
    {
        try {
            self::ensureTableExists();
            $pdo = Database::getConnection();
            $since = date('Y-m-d', strtotime("-{$days} days"));
            $stmt = $pdo->prepare("
                SELECT DATE(created_at) as stat_date, COUNT(*) as count
                FROM attack_logs 
                WHERE created_at >= ?
                GROUP BY DATE(created_at)
                ORDER BY stat_date ASC
            ");
            $stmt->execute([$since]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}