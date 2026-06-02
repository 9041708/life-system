<?php
namespace App\Model;

use App\Service\Database;
use PDO;

/**
 * 访问统计模型 - 记录每日 PV/UV/IP 数据
 */
class AccessLog
{
    /**
     * 确保表存在
     */
    private static function ensureTableExists(): void
    {
        $pdo = Database::getConnection();
        try {
            $pdo->query('SELECT 1 FROM access_stats LIMIT 1');
        } catch (\Throwable $e) {
            $sql = 'CREATE TABLE IF NOT EXISTS access_stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                stat_date DATE NOT NULL,
                pv INT DEFAULT 0,
                uv INT DEFAULT 0,
                ip_count INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_date (stat_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
            $pdo->exec($sql);
        }
    }

    /**
     * 确保访问明细表存在（用于更详细的攻击检测）
     */
    private static function ensureDetailTableExists(): void
    {
        $pdo = Database::getConnection();
        try {
            $pdo->query('SELECT 1 FROM access_log_detail LIMIT 1');
        } catch (\Throwable $e) {
            $sql = 'CREATE TABLE IF NOT EXISTS access_log_detail (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                path VARCHAR(500) NOT NULL,
                user_agent TEXT,
                is_attack TINYINT(1) DEFAULT 0,
                attack_type VARCHAR(50) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip (ip_address),
                INDEX idx_path (path),
                INDEX idx_created (created_at),
                INDEX idx_attack (is_attack, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
            $pdo->exec($sql);
        }
    }

    /**
     * 记录一次访问
     */
    public static function recordAccess(string $ip, string $path, string $userAgent = '', bool $isAttack = false, ?string $attackType = null): void
    {
        try {
            self::ensureTableExists();
            self::ensureDetailTableExists();

            $pdo = Database::getConnection();
            $today = date('Y-m-d');

            // 记录明细
            $stmt = $pdo->prepare('INSERT INTO access_log_detail (ip_address, path, user_agent, is_attack, attack_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $ip,
                substr($path, 0, 500),
                substr($userAgent, 0, 1000),
                $isAttack ? 1 : 0,
                $attackType,
            ]);

            // 更新每日统计
            self::updateDailyStats($today);
        } catch (\Throwable $e) {
            error_log('记录访问失败: ' . $e->getMessage());
        }
    }

    /**
     * 更新每日统计数据
     */
    private static function updateDailyStats(string $date): void
    {
        try {
            $pdo = Database::getConnection();

            // 统计该日的 PV、UV、IP 数
            $stmt = $pdo->prepare('
                SELECT 
                    COUNT(*) as pv,
                    COUNT(DISTINCT ip_address) as ip_count
                FROM access_log_detail 
                WHERE DATE(created_at) = ?
            ');
            $stmt->execute([$date]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $uv = 0;
            // UV 用 IP + 简单指纹（user_agent 前 50 字符）组合
            $stmt2 = $pdo->prepare('
                SELECT COUNT(DISTINCT CONCAT(ip_address, "_", LEFT(user_agent, 50))) as uv
                FROM access_log_detail 
                WHERE DATE(created_at) = ?
            ');
            $stmt2->execute([$date]);
            $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);

            $pv = (int)($result['pv'] ?? 0);
            $uv = (int)($result2['uv'] ?? 0);
            $ipCount = (int)($result['ip_count'] ?? 0);

            // UPSERT
            $stmt3 = $pdo->prepare('
                INSERT INTO access_stats (stat_date, pv, uv, ip_count) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE pv = VALUES(pv), uv = VALUES(uv), ip_count = VALUES(ip_count)
            ');
            $stmt3->execute([$date, $pv, $uv, $ipCount]);
        } catch (\Throwable $e) {
            error_log('更新每日统计失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取指定日期范围的统计数据
     */
    public static function getStats(string $dateFrom, string $dateTo): array
    {
        try {
            self::ensureTableExists();
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('
                SELECT stat_date, pv, uv, ip_count 
                FROM access_stats 
                WHERE stat_date BETWEEN ? AND ?
                ORDER BY stat_date ASC
            ');
            $stmt->execute([$dateFrom, $dateTo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 获取今日统计
     */
    public static function getTodayStats(): array
    {
        $today = date('Y-m-d');
        $stats = self::getStats($today, $today);
        return $stats[0] ?? ['pv' => 0, 'uv' => 0, 'ip_count' => 0];
    }

    /**
     * 获取昨日统计
     */
    public static function getYesterdayStats(): array
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $stats = self::getStats($yesterday, $yesterday);
        return $stats[0] ?? ['pv' => 0, 'uv' => 0, 'ip_count' => 0];
    }

    /**
     * 获取最近 N 天统计
     */
    public static function getRecentStats(int $days = 7): array
    {
        $dateTo = date('Y-m-d');
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        return self::getStats($dateFrom, $dateTo);
    }

    /**
     * 清理旧数据
     */
    public static function cleanup(int $retentionDays = 90): void
    {
        try {
            $pdo = Database::getConnection();
            $threshold = date('Y-m-d', strtotime("-{$retentionDays} days"));
            
            $stmt = $pdo->prepare('DELETE FROM access_log_detail WHERE DATE(created_at) < ?');
            $stmt->execute([$threshold]);

            // stats 表保留更久（汇总数据）
            $stmt2 = $pdo->prepare('DELETE FROM access_stats WHERE stat_date < DATE_SUB(?, INTERVAL 365 DAY)');
            $stmt2->execute([date('Y-m-d')]);
        } catch (\Throwable $e) {
            error_log('清理访问日志失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取访问最频繁的 IP
     */
    public static function getTopIps(int $limit = 20, int $hoursBack = 24): array
    {
        try {
            $pdo = Database::getConnection();
            $threshold = date('Y-m-d H:i:s', strtotime("-{$hoursBack} hours"));
            $stmt = $pdo->prepare("
                SELECT ip_address, COUNT(*) as access_count,
                       MAX(created_at) as last_access
                FROM access_log_detail 
                WHERE created_at > ?
                GROUP BY ip_address 
                ORDER BY access_count DESC 
                LIMIT ?
            ");
            $stmt->execute([$threshold, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 获取访问最频繁的路径
     */
    public static function getTopPaths(int $limit = 20, int $hoursBack = 24): array
    {
        try {
            $pdo = Database::getConnection();
            $threshold = date('Y-m-d H:i:s', strtotime("-{$hoursBack} hours"));
            $stmt = $pdo->prepare("
                SELECT path, COUNT(*) as access_count
                FROM access_log_detail 
                WHERE created_at > ?
                GROUP BY path 
                ORDER BY access_count DESC 
                LIMIT ?
            ");
            $stmt->execute([$threshold, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}