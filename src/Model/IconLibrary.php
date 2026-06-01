<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class IconLibrary
{
    private static function ensureSystemIconIdColumn(): void
    {
        $pdo = Database::getConnection();
        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM icon_library LIKE 'system_icon_id'");
            $hasColumn = (bool)$colStmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasColumn = false;
        }

        if ($hasColumn) {
            return;
        }

        try {
            $pdo->exec('ALTER TABLE icon_library ADD COLUMN system_icon_id INT NULL DEFAULT NULL');
        } catch (\Throwable $e) {
            // ignore if no permission
        }
    }

    public static function allByUser(int $userId): array
    {
        self::ensureSystemIconIdColumn();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM icon_library WHERE user_id = :uid ORDER BY id');
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findByUser(int $userId, int $id): ?array
    {
        self::ensureSystemIconIdColumn();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM icon_library WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByUserAndSystemIconId(int $userId, int $systemIconId): ?array
    {
        self::ensureSystemIconIdColumn();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM icon_library WHERE user_id = :uid AND system_icon_id = :sid LIMIT 1');
        $stmt->execute([':uid' => $userId, ':sid' => $systemIconId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByUserAndFilePath(int $userId, string $filePath): ?array
    {
        self::ensureSystemIconIdColumn();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM icon_library WHERE user_id = :uid AND file_path = :path LIMIT 1');
        $stmt->execute([':uid' => $userId, ':path' => $filePath]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(int $userId, string $name, string $filePath): int
    {
        self::ensureSystemIconIdColumn();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO icon_library (user_id, name, file_path) VALUES (:uid,:name,:path)');
        $stmt->execute([
            ':uid' => $userId,
            ':name' => $name,
            ':path' => $filePath,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function createSystem(int $userId, int $systemIconId, string $name, string $filePath): int
    {
        self::ensureSystemIconIdColumn();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO icon_library (user_id, name, file_path, system_icon_id) VALUES (:uid,:name,:path,:sid)');
        $stmt->execute([
            ':uid' => $userId,
            ':name' => $name,
            ':path' => $filePath,
            ':sid' => $systemIconId,
        ]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * 如果当前用户下不存在同一路径的图标，则以给定名称创建一条记录。
     */
    public static function ensureExists(int $userId, string $filePath, string $defaultName): void
    {
        self::ensureSystemIconIdColumn();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM icon_library WHERE user_id = :uid AND file_path = :path LIMIT 1');
        $stmt->execute([
            ':uid' => $userId,
            ':path' => $filePath,
        ]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return;
        }

        // 使用默认名称创建记录
        self::create($userId, $defaultName, $filePath);
    }

    public static function updateName(int $userId, int $id, string $name): bool
    {
        self::ensureSystemIconIdColumn();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE icon_library SET name = :name WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':name' => $name,
            ':id' => $id,
            ':uid' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function updateFile(int $userId, int $id, string $filePath, ?string $name = null): bool
    {
        self::ensureSystemIconIdColumn();
        $pdo = Database::getConnection();
        if ($name !== null && $name !== '') {
            $stmt = $pdo->prepare('UPDATE icon_library SET file_path = :path, name = :name WHERE id = :id AND user_id = :uid');
            $stmt->execute([
                ':path' => $filePath,
                ':name' => $name,
                ':id' => $id,
                ':uid' => $userId,
            ]);
        } else {
            $stmt = $pdo->prepare('UPDATE icon_library SET file_path = :path WHERE id = :id AND user_id = :uid');
            $stmt->execute([
                ':path' => $filePath,
                ':id' => $id,
                ':uid' => $userId,
            ]);
        }
        return $stmt->rowCount() > 0;
    }

    public static function assignSystemIconId(int $userId, int $id, int $systemIconId): bool
    {
        self::ensureSystemIconIdColumn();
        $systemIconId = (int)$systemIconId;
        if ($systemIconId <= 0) {
            return false;
        }
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare('UPDATE icon_library SET system_icon_id = :sid WHERE id = :id AND user_id = :uid');
            $stmt->execute([':sid' => $systemIconId, ':id' => $id, ':uid' => $userId]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function updateNameAndPathAndSystemId(int $userId, int $id, string $name, string $filePath, int $systemIconId): bool
    {
        self::ensureSystemIconIdColumn();
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare('UPDATE icon_library SET name = :name, file_path = :path, system_icon_id = :sid WHERE id = :id AND user_id = :uid');
            $stmt->execute([
                ':name' => $name,
                ':path' => $filePath,
                ':sid' => $systemIconId,
                ':id' => $id,
                ':uid' => $userId,
            ]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function delete(int $userId, int $id): bool
    {
        self::ensureSystemIconIdColumn();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM icon_library WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':id' => $id,
            ':uid' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function deleteAllBySystemIconId(int $systemIconId): array
    {
        self::ensureSystemIconIdColumn();
        $pdo = Database::getConnection();
        $systemIconId = (int)$systemIconId;
        if ($systemIconId <= 0) {
            return [];
        }

        // 先查出受影响的用户与图标信息，用于提示
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, name, file_path FROM icon_library WHERE system_icon_id = :sid');
            $stmt->execute([':sid' => $systemIconId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        try {
            $del = $pdo->prepare('DELETE FROM icon_library WHERE system_icon_id = :sid');
            $del->execute([':sid' => $systemIconId]);
        } catch (\Throwable $e) {
            // ignore
        }

        return $rows;
    }

    public static function deleteAllByFilePath(string $filePath, ?int $onlyUserId = null): array
    {
        self::ensureSystemIconIdColumn();
        $filePath = trim($filePath);
        if ($filePath === '') {
            return [];
        }

        $pdo = Database::getConnection();
        $params = [':p' => $filePath];
        $where = 'file_path = :p';
        if ($onlyUserId !== null) {
            $where .= ' AND user_id = :uid';
            $params[':uid'] = (int)$onlyUserId;
        }

        try {
            $stmt = $pdo->prepare('SELECT id, user_id, name, file_path FROM icon_library WHERE ' . $where);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        try {
            $del = $pdo->prepare('DELETE FROM icon_library WHERE ' . $where);
            $del->execute($params);
        } catch (\Throwable $e) {
            // ignore
        }

        return $rows;
    }
}
