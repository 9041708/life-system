<?php
namespace App\Model;

use App\Service\Database;

class Book
{
    private static function ensureTable(): void
    {
        $pdo = Database::getConnection();
        try {
            $pdo->query('SELECT 1 FROM books LIMIT 1');
        } catch (\Throwable $e) {
            $pdo->exec('CREATE TABLE IF NOT EXISTS books (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                author VARCHAR(255) DEFAULT \'\',
                description TEXT NULL COMMENT \'简介\',
                file_path VARCHAR(500) NOT NULL,
                file_type VARCHAR(10) NOT NULL COMMENT \'pdf/txt\',
                file_size BIGINT DEFAULT 0,
                cover VARCHAR(500) DEFAULT \'\',
                scope VARCHAR(10) DEFAULT \'personal\' COMMENT \'personal/system/shared\',
                pushed_by INT NULL,
                push_data JSON NULL COMMENT \'推送记录\',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }
        // migration: add missing columns
        $migrations = [
            'cover' => "VARCHAR(500) DEFAULT ''",
            'scope' => "VARCHAR(10) DEFAULT 'personal'",
            'pushed_by' => 'INT NULL',
            'description' => 'TEXT NULL',
            'push_data' => 'JSON NULL',
        ];
        foreach ($migrations as $col => $def) {
            try {
                $pdo->query("SELECT $col FROM books LIMIT 1");
            } catch (\Throwable $e) {
                $pdo->exec("ALTER TABLE books ADD COLUMN $col $def");
            }
        }
    }

    public static function create(int $userId, array $data): int
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO books (user_id, title, author, description, file_path, file_type, file_size, cover)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $userId,
            $data['title'] ?? '未命名',
            $data['author'] ?? '',
            $data['description'] ?? '',
            $data['file_path'] ?? '',
            $data['file_type'] ?? 'pdf',
            (int)($data['file_size'] ?? 0),
            $data['cover'] ?? '',
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByUser(int $userId, ?string $scope = null): array
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $sql = 'SELECT b.*, p.page_num, p.scroll_offset FROM books b
            LEFT JOIN book_progress p ON b.id = p.book_id AND p.user_id = ?
            WHERE b.user_id = ?';
        $params = [$userId, $userId];
        if ($scope) {
            $sql .= ' AND b.scope = ?';
            $params[] = $scope;
        }
        $sql .= ' ORDER BY b.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function listSystem(): array
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM books WHERE scope = \'system\' ORDER BY created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function pushToAll(int $bookId, int $userId): bool
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $book = self::findById($bookId, $userId);
        if (!$book) return false;
        $stmt = $pdo->prepare('UPDATE books SET scope = \'system\', pushed_by = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$userId, $bookId, $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function pushToUser(int $bookId, int $fromUserId, int $toUserId): ?int
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $book = self::findById($bookId, $fromUserId);
        if (!$book) return null;

        // update push_data on original book to track who received it
        $pushData = $book['push_data'] ? json_decode($book['push_data'], true) : [];
        if (!is_array($pushData)) $pushData = [];
        $pushData[] = ['uid' => $toUserId, 'time' => date('Y-m-d H:i:s')];
        $stmt = $pdo->prepare('UPDATE books SET push_data = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([json_encode($pushData, JSON_UNESCAPED_UNICODE), $bookId, $fromUserId]);

        // create copy for target user
        $stmt = $pdo->prepare('INSERT INTO books (user_id, title, author, description, file_path, file_type, file_size, cover, scope, pushed_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'personal\', ?)');
        $stmt->execute([
            $toUserId,
            $book['title'],
            $book['author'],
            $book['description'] ?? '',
            $book['file_path'],
            $book['file_type'],
            $book['file_size'],
            $book['cover'] ?? '',
            $fromUserId,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findById(int $id, int $userId): ?array
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM books WHERE id = ? AND (user_id = ? OR scope = \'system\')');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function syncFromDisk(int $userId): int
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $uploadDir = __DIR__ . '/../../uploads/books';
        if (!is_dir($uploadDir)) return 0;

        $existingStmt = $pdo->query('SELECT file_path FROM books');
        $existingPaths = $existingStmt->fetchAll(\PDO::FETCH_COLUMN);

        $files = scandir($uploadDir);
        $synced = 0;

        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $matches = [];
            if (!preg_match('/^book_(\d+)_(\d+)\.(pdf|txt)$/i', $f, $matches)) continue;

            $fileUserId = (int)$matches[1];
            $ts = (int)$matches[2];
            $ext = strtolower($matches[3]);
            $filePath = '/uploads/books/' . $f;
            $absPath = $uploadDir . '/' . $f;

            if (in_array($filePath, $existingPaths, true)) continue;

            $cover = '';
            $coverPattern = '/^cover_' . $fileUserId . '_' . $ts . '\.(png|jpg|jpeg|gif|webp)$/i';
            foreach ($files as $cf) {
                if (preg_match($coverPattern, $cf)) {
                    $cover = '/uploads/books/' . $cf;
                    break;
                }
            }

            $title = pathinfo($f, PATHINFO_FILENAME);
            $stmt = $pdo->prepare('INSERT INTO books (user_id, title, author, description, file_path, file_type, file_size, cover, scope, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'personal\', ?)');
            $stmt->execute([
                $fileUserId,
                $title,
                '',
                '',
                $filePath,
                $ext,
                filesize($absPath),
                $cover,
                date('Y-m-d H:i:s', $ts),
            ]);
            $synced++;
        }
        return $synced;
    }

    public static function delete(int $id, int $userId): bool
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM books WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function removePushTarget(int $bookId, int $ownerId, int $targetUid): bool
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $book = self::findById($bookId, $ownerId);
        if (!$book) return false;

        // remove from push_data
        $pushData = $book['push_data'] ? json_decode($book['push_data'], true) : [];
        if (!is_array($pushData)) $pushData = [];
        $pushData = array_values(array_filter($pushData, function($item) use ($targetUid) {
            return (int)($item['uid'] ?? 0) !== $targetUid;
        }));
        $stmt = $pdo->prepare('UPDATE books SET push_data = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([json_encode($pushData, JSON_UNESCAPED_UNICODE), $bookId, $ownerId]);

        // delete target user's copy
        $stmt = $pdo->prepare('DELETE FROM books WHERE user_id = ? AND pushed_by = ? AND file_path = ? AND id != ?');
        $stmt->execute([$targetUid, $ownerId, $book['file_path'], $bookId]);
        return true;
    }
}
