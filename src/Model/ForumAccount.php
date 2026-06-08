<?php
namespace App\Model;

use App\Service\Database;

class ForumAccount
{
    private static function getCipherKey(): string
    {
        return hash('sha256', 'sanshi-forum-account-key-2026', true);
    }

    public static function encrypt(string $plain): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($plain, 'aes-256-cbc', self::getCipherKey(), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt(string $cipher): string
    {
        $data = @base64_decode($cipher, true);
        if ($data === false || strlen($data) < 17) {
            return '';
        }
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        $result = @openssl_decrypt($encrypted, 'aes-256-cbc', self::getCipherKey(), OPENSSL_RAW_DATA, $iv);
        return $result !== false ? $result : '';
    }

    public static function create(int $userId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO forum_accounts (user_id, forum_name, forum_url, username, password, 
             enable_notice, enable_mention_reply, mention_reply_mode,
             enable_signin, enable_autoreply, reply_mode, custom_reply, 
             ai_reply_flag, signin_time, signin_url, signin_params, reply_time, reply_interval, auto_reply_interval)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId,
            $data['forum_name'],
            rtrim($data['forum_url'], '/'),
            $data['username'],
            self::encrypt($data['password']),
            $data['enable_notice'] ?? 0,
            $data['enable_mention_reply'] ?? 0,
            $data['mention_reply_mode'] ?? 'ai',
            $data['enable_signin'] ?? 0,
            $data['enable_autoreply'] ?? 0,
            $data['reply_mode'] ?? 'random',
            $data['custom_reply'] ?? null,
            $data['ai_reply_flag'] ?? '[AI回帖]',
            $data['signin_time'] ?? '08:00:00',
            $data['signin_url'] ?? '',
            $data['signin_params'] ?? '',
            $data['reply_time'] ?? '09:00:00',
            $data['reply_interval'] ?? 10,
            $data['auto_reply_interval'] ?? 30,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT *
             FROM forum_accounts WHERE user_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            if (!empty($row['password'])) {
                $row['password'] = self::decrypt($row['password']);
            }
        }
        return $rows;
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM forum_accounts WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row && !empty($row['password'])) {
            $row['password'] = self::decrypt($row['password']);
        }
        if ($row && !empty($row['cookie_data'])) {
            $row['cookie_data'] = self::decrypt($row['cookie_data']);
        }
        return $row ?: null;
    }

    public static function findByIdAndUser(int $id, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM forum_accounts WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row && !empty($row['password'])) {
            $row['password'] = self::decrypt($row['password']);
        }
        if ($row && !empty($row['cookie_data'])) {
            $row['cookie_data'] = self::decrypt($row['cookie_data']);
        }
        return $row ?: null;
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $pdo = Database::getConnection();
        $fields = [];
        $values = [];

        $simpleFields = [
            'forum_name', 'forum_url', 'username', 'enable_notice',
            'enable_mention_reply', 'enable_follow_up', 'enable_bonus',
            'notice_interval', 'mention_reply_mode',
            'enable_signin', 'enable_autoreply', 'reply_mode',
            'custom_reply', 'ai_reply_flag', 'signin_time', 'signin_url',
            'reply_time', 'reply_interval', 'auto_reply_interval'
        ];

        foreach ($simpleFields as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $f === 'forum_url' ? rtrim($data[$f], '/') : $data[$f];
            }
        }

        if (array_key_exists('password', $data) && $data['password'] !== '') {
            $fields[] = "password = ?";
            $values[] = self::encrypt($data['password']);
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $values[] = $userId;
        $sql = "UPDATE forum_accounts SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public static function updateCookie(int $id, string $cookieData, string $expireTime): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "UPDATE forum_accounts SET cookie_data = ?, cookie_expire = ? WHERE id = ?"
        );
        return $stmt->execute([self::encrypt($cookieData), $expireTime, $id]);
    }

    public static function updateLastSignin(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "UPDATE forum_accounts SET last_signin = CURDATE() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    public static function updateLastNoticeCheck(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "UPDATE forum_accounts SET last_notice_check = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    public static function updateLastMentionReply(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "UPDATE forum_accounts SET last_mention_reply = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    public static function updateLastReply(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "UPDATE forum_accounts SET last_reply = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    public static function delete(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM forum_accounts WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public static function getNeedExecute(string $actionType): array
    {
        $pdo = Database::getConnection();

        if ($actionType === 'signin') {
            $sql = "SELECT * FROM forum_accounts WHERE enable_signin = 1 
                    AND signin_time <= CURTIME()
                    AND (last_signin IS NULL OR last_signin < CURDATE())";
        } else {
            // 检查 last_reply 字段是否存在（兼容未跑迁移的情况）
            $hasLastReply = false;
            try {
                $colCheck = $pdo->query("SHOW COLUMNS FROM forum_accounts LIKE 'last_reply'")->fetch();
                $hasLastReply = (bool)$colCheck;
            } catch (\Throwable $e) {}

            if ($hasLastReply) {
                $sql = "SELECT * FROM forum_accounts WHERE enable_autoreply = 1 
                        AND CONCAT(CURDATE(), ' ', reply_time) <= NOW()
                        AND (last_reply IS NULL OR last_reply < CURDATE() OR TIMESTAMPDIFF(MINUTE, last_reply, NOW()) >= COALESCE(auto_reply_interval, 30))";
            } else {
                // 降级：旧逻辑（精确时间匹配，无间隔控制）
                $sql = "SELECT * FROM forum_accounts WHERE enable_autoreply = 1 
                        AND HOUR(reply_time) = HOUR(NOW()) 
                        AND MINUTE(reply_time) = MINUTE(NOW())";
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            if (!empty($row['password'])) {
                $row['password'] = self::decrypt($row['password']);
            }
            if (!empty($row['cookie_data'])) {
                $row['cookie_data'] = self::decrypt($row['cookie_data']);
            }
        }
        return $rows;
    }

    public static function getNeedCheckNotice(): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM forum_accounts WHERE enable_notice = 1 
                AND (last_notice_check IS NULL OR last_notice_check < DATE_SUB(NOW(), INTERVAL COALESCE(notice_interval, 15) MINUTE))";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getNeedMentionReply(): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM forum_accounts WHERE enable_mention_reply = 1
                AND (last_mention_reply IS NULL OR last_mention_reply < DATE_SUB(NOW(), INTERVAL 10 MINUTE))";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            if (!empty($row['password'])) {
                $row['password'] = self::decrypt($row['password']);
            }
            if (!empty($row['cookie_data'])) {
                $row['cookie_data'] = self::decrypt($row['cookie_data']);
            }
        }
        return $rows;
    }
}
