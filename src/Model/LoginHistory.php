<?php

namespace App\Model;

/**
 * 登录日志表
 * 记录每个用户的登录历史：IP、地区、设备类型、是否异常
 */
class LoginHistory
{
    public $id;
    public $user_id;
    public $ip;
    public $location;
    public $device_type;   // pc / mobile / tablet / unknown
    public $device_fingerprint;
    public $device_name;
    public $user_agent;
    public $login_at;
    public $is_anomalous;
    public $anomalous_reason;

    public static function listByUser(int $userId, int $limit = 20): array
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("
            SELECT * FROM login_history
            WHERE user_id = ?
            ORDER BY login_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function todayLoginCount(int $userId): int
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM login_history
            WHERE user_id = ? AND DATE(login_at) = CURDATE()
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function uniqueLocationsToday(int $userId): int
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT location)
            FROM login_history
            WHERE user_id = ? AND DATE(login_at) = CURDATE()
            AND location IS NOT NULL AND location != ''
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function create(array $data): bool
    {
        $cols = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $pdo = self::pdo();
        $stmt = $pdo->prepare("INSERT INTO login_history ({$cols}) VALUES ({$placeholders})");
        return $stmt->execute(array_values($data));
    }

    public static function createTable(): string
    {
        return "
        CREATE TABLE IF NOT EXISTS login_history (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            ip VARCHAR(45) NOT NULL,
            location VARCHAR(100) DEFAULT NULL,
            device_type VARCHAR(20) DEFAULT 'unknown',
            device_fingerprint VARCHAR(64) DEFAULT NULL,
            device_name VARCHAR(100) DEFAULT NULL,
            user_agent TEXT,
            login_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_anomalous TINYINT(1) NOT NULL DEFAULT 0,
            anomalous_reason VARCHAR(255) DEFAULT NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_login_at (login_at),
            INDEX idx_ip (ip)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
    }

    private static function pdo(): \PDO
    {
        return \App\Service\Database::getConnection();
    }
}