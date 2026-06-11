<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class ApiToken
{
    /**
     * 创建 API Token
     * @param int $userId 用户ID
     * @param string $description 描述
     * @param int $expiresIn 过期秒数（默认365天）
     * @param string $clientType 客户端类型（miniapp/mobile-web/空=手动创建）
     */
    public static function createToken(int $userId, string $description, int $expiresIn = 31536000, string $clientType = ''): string
    {
        $token = 'ssj_' . bin2hex(random_bytes(32));
        $pdo = Database::getConnection();
        self::ensureTable($pdo);
        $stmt = $pdo->prepare('INSERT INTO api_tokens (user_id, token, client_type, description, expires_at, created_at) VALUES (:u, :t, :ct, :d, DATE_ADD(NOW(), INTERVAL :exp SECOND), NOW())');
        $stmt->execute([
            ':u' => $userId,
            ':t' => $token,
            ':ct' => $clientType,
            ':d' => $description,
            ':exp' => $expiresIn,
        ]);
        return $token;
    }

    /**
     * 列出某用户的所有 Token
     */
    public static function listByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        self::ensureTable($pdo);
        // 自动删除过期 Token
        $pdo->prepare('DELETE FROM api_tokens WHERE user_id = :u AND expires_at < NOW()')->execute([':u' => $userId]);
        // 过滤掉小程序和移动端登录 Token（只显示手动创建的）
        $stmt = $pdo->prepare('SELECT id, user_id, LEFT(token, 8) AS token_prefix, description, expires_at, last_used_at, created_at FROM api_tokens WHERE user_id = :u AND (client_type IS NULL OR client_type = "") ORDER BY id DESC');
        $stmt->execute([':u' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 统计用户可见 API Token 数量
     */
    public static function countByUser(int $userId): int
    {
        $pdo = Database::getConnection();
        self::ensureTable($pdo);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM api_tokens WHERE user_id = :u AND (client_type IS NULL OR client_type = "") AND (expires_at IS NULL OR expires_at > NOW())');
        $stmt->execute([':u' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * 获取完整 Token（用于查看）
     */
    public static function findRawToken(int $id, int $userId): ?string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT token FROM api_tokens WHERE id = :id AND user_id = :u');
        $stmt->execute([':id' => $id, ':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['token'] : null;
    }

    /**
     * 根据 token 字符串查找有效 Token（用于 API 认证）
     */
    public static function findValidToken(string $token): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM api_tokens WHERE token = :t LIMIT 1');
        $stmt->execute([':t' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $update = $pdo->prepare('UPDATE api_tokens SET last_used_at = NOW() WHERE id = :id');
            $update->execute([':id' => $row['id']]);
        }
        return $row ?: null;
    }

    /**
     * 根据 id 撤销某用户的 Token
     */
    public static function revokeById(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM api_tokens WHERE id = :id AND user_id = :u');
        $stmt->execute([':id' => $id, ':u' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * 批量撤销 Token
     */
    public static function revokeByIds(array $ids, int $userId): int
    {
        $pdo = Database::getConnection();
        $count = 0;
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $stmt = $pdo->prepare('DELETE FROM api_tokens WHERE id = :id AND user_id = :u');
                $stmt->execute([':id' => $id, ':u' => $userId]);
                $count += $stmt->rowCount();
            }
        }
        return $count;
    }

    /**
     * 确保 api_tokens 表存在
     */
    private static function ensureTable(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS api_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(128) NOT NULL UNIQUE,
            client_type VARCHAR(50) DEFAULT "",
            description VARCHAR(255) DEFAULT "",
            expires_at DATETIME DEFAULT NULL,
            last_used_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        // 添加 description 列（如果不存在）
        try {
            $pdo->exec('ALTER TABLE api_tokens ADD COLUMN description VARCHAR(255) DEFAULT "" AFTER client_type');
        } catch (\Throwable $e) {
            // 列已存在，忽略
        }
    }
}
