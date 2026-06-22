<?php
namespace App\Model;

use App\Service\Database;

class BookProgress
{
    private static function ensureTable(): void
    {
        $pdo = Database::getConnection();
        try {
            $pdo->query('SELECT 1 FROM book_progress LIMIT 1');
        } catch (\Throwable $e) {
            $pdo->exec('CREATE TABLE IF NOT EXISTS book_progress (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                book_id INT NOT NULL,
                page_num INT DEFAULT 1 COMMENT \'PDF当前页码\',
                scroll_offset INT DEFAULT 0 COMMENT \'TXT滚动位置\',
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_user_book (user_id, book_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }
    }

    public static function get(int $userId, int $bookId): ?array
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM book_progress WHERE user_id = ? AND book_id = ?');
        $stmt->execute([$userId, $bookId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function save(int $userId, int $bookId, int $pageNum, int $scrollOffset): void
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $pdo->prepare('INSERT INTO book_progress (user_id, book_id, page_num, scroll_offset, updated_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE page_num = VALUES(page_num), scroll_offset = VALUES(scroll_offset), updated_at = NOW()')
            ->execute([$userId, $bookId, $pageNum, $scrollOffset]);
    }

    public static function progressText(int $userId, int $bookId, int $totalPages = 0): string
    {
        $p = self::get($userId, $bookId);
        if (!$p) return '';
        $page = (int)($p['page_num'] ?? 0);
        if ($page <= 0) return '';
        if ($totalPages > 0) {
            $pct = round($page / $totalPages * 100);
            return "已读 {$pct}%";
        }
        return "第 {$page} 页";
    }
}
