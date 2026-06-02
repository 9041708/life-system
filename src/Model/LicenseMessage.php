<?php
namespace App\Model;

use App\Service\Database;

class LicenseMessage
{
    public static function create(string $email, string $nickname, string $content): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('INSERT INTO license_messages (email, nickname, content, created_at) VALUES (:email, :nickname, :content, NOW())');
            $stmt->execute([
                ':email' => $email,
                ':nickname' => $nickname,
                ':content' => $content,
            ]);
        } catch (\Throwable $e) {
            // 忽略留言表异常（例如尚未建表），避免影响主流程
        }
    }

    public static function listLatest(int $limit = 50): array
    {
        try {
            $pdo = Database::getConnection();
            $limit = max(1, min($limit, 200));
            $stmt = $pdo->query('SELECT * FROM license_messages ORDER BY id DESC LIMIT ' . $limit);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            // 若留言表尚未创建或查询异常，则返回空列表
            return [];
        }
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT * FROM license_messages WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function updateReply(int $id, string $reply): void
    {
        if ($id <= 0) {
            return;
        }

        try {
            $pdo = Database::getConnection();
            $reply = trim($reply);
            if ($reply === '') {
                $stmt = $pdo->prepare('UPDATE license_messages SET admin_reply = NULL, admin_reply_at = NULL WHERE id = :id');
                $stmt->execute([':id' => $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE license_messages SET admin_reply = :reply, admin_reply_at = NOW() WHERE id = :id');
                $stmt->execute([
                    ':reply' => $reply,
                    ':id' => $id,
                ]);
            }
        } catch (\Throwable $e) {
            // 忽略异常，避免影响授权后台其他功能
        }
    }
}
