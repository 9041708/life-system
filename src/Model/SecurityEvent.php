<?php

namespace App\Model;

/**
 * 安全事件记录表
 * 用于记录系统级安全事件：登录日志、攻击记录、异常告警
 */
class SecurityEvent
{
    public $id;
    public $event_type;    // login / attack / anomaly / malware
    public $source_ip;
    public $source_port;
    public $target;        // 目标路由/接口
    public $user_id;       // 关联用户（可为null）
    public $severity;      // low / medium / high / critical
    public $status;        // pending / blocked / cleared
    public $attack_type;   // sqlmap / xss / brute_force / scan /爆破/...
    public $location;      // 来源地区
    public $user_agent;
    public $note;
    public $created_at;

    public static function listRecent(int $limit = 20): array
    {
        $pdo = self::pdo();
        $stmt = $pdo->query("SELECT * FROM security_events ORDER BY id DESC LIMIT " . (int)$limit);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function countByType(): array
    {
        $pdo = self::pdo();
        $stmt = $pdo->query("
            SELECT attack_type, COUNT(*) as cnt
            FROM security_events
            WHERE attack_type IS NOT NULL AND attack_type != ''
            GROUP BY attack_type
            ORDER BY cnt DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function countByLocation(): array
    {
        $pdo = self::pdo();
        $stmt = $pdo->query("
            SELECT location, COUNT(*) as cnt
            FROM security_events
            WHERE location IS NOT NULL AND location != ''
            GROUP BY location
            ORDER BY cnt DESC
            LIMIT 10
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function todayLoginCount(): int
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM security_events
            WHERE event_type = 'login'
            AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public static function activeSessionCount(): int
    {
        $pdo = self::pdo();
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT user_id)
            FROM security_events
            WHERE event_type = 'login'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
            AND user_id IS NOT NULL
        ");
        return (int)$stmt->fetchColumn();
    }

    public static function uniqueLocationCount(): int
    {
        $pdo = self::pdo();
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT location)
            FROM security_events
            WHERE event_type = 'login'
            AND DATE(created_at) = CURDATE()
            AND location IS NOT NULL AND location != ''
        ");
        return (int)$stmt->fetchColumn();
    }

    public static function create(array $data): bool
    {
        $cols = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $pdo = self::pdo();
        $stmt = $pdo->prepare("INSERT INTO security_events ({$cols}) VALUES ({$placeholders})");
        return $stmt->execute(array_values($data));
    }

    public static function findById(int $id): ?array
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("SELECT * FROM security_events WHERE id = ?");
        $stmt->execute([$id]);
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public static function updateStatus(int $id, string $status): bool
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("UPDATE security_events SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public static function createTable(): string
    {
        return "
        CREATE TABLE IF NOT EXISTS security_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL DEFAULT 'login',
            source_ip VARCHAR(45),
            source_port INT UNSIGNED,
            target VARCHAR(255),
            user_id INT UNSIGNED,
            severity VARCHAR(20) NOT NULL DEFAULT 'low',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            attack_type VARCHAR(100),
            location VARCHAR(100),
            user_agent TEXT,
            note TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_type (event_type),
            INDEX idx_severity (severity),
            INDEX idx_created_at (created_at),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
    }

    private static function pdo(): \PDO
    {
        return \App\Service\Database::getConnection();
    }
}