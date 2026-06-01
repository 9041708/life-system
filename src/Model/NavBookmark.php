<?php
namespace App\Model;

use App\Service\Database;

class NavBookmark
{
    public static function listGroups(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM nav_groups WHERE user_id = ? ORDER BY sort_order, id");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function createGroup(int $userId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO nav_groups (user_id, name, icon_type, icon_value, sort_order) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId,
            $data['name'],
            $data['icon_type'] ?? null,
            $data['icon_value'] ?? null,
            (int)($data['sort_order'] ?? 0),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateGroup(int $id, int $userId, array $data): bool
    {
        $pdo = Database::getConnection();
        $fields = [];
        $values = [];
        foreach (['name', 'icon_type', 'icon_value', 'sort_order'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $values[] = $userId;
        $stmt = $pdo->prepare(
            "UPDATE nav_groups SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute($values);
    }

    public static function deleteGroup(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM nav_bookmarks WHERE group_id = ? AND user_id = ?")->execute([$id, $userId]);
            $pdo->prepare("DELETE FROM nav_groups WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function listBookmarks(int $userId, int $groupId = 0): array
    {
        $pdo = Database::getConnection();
        if ($groupId > 0) {
            $stmt = $pdo->prepare(
                "SELECT b.*, g.name AS group_name FROM nav_bookmarks b
                 JOIN nav_groups g ON g.id = b.group_id
                 WHERE b.user_id = ? AND b.group_id = ?
                 ORDER BY b.sort_order, b.id"
            );
            $stmt->execute([$userId, $groupId]);
        } else {
            $stmt = $pdo->prepare(
                "SELECT b.*, g.name AS group_name FROM nav_bookmarks b
                 JOIN nav_groups g ON g.id = b.group_id
                 WHERE b.user_id = ?
                 ORDER BY g.sort_order, g.id, b.sort_order, b.id"
            );
            $stmt->execute([$userId]);
        }
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['urls'] = self::listUrls((int)$row['id']);
        }
        return $rows;
    }

    public static function createBookmark(int $userId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO nav_bookmarks (user_id, group_id, name, url, description, icon_type, icon_value, screenshot, sort_order, show_on_home)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId,
            (int)$data['group_id'],
            $data['name'],
            $data['url'],
            $data['description'] ?? null,
            $data['icon_type'] ?? null,
            $data['icon_value'] ?? null,
            $data['screenshot'] ?? null,
            (int)($data['sort_order'] ?? 0),
            (int)($data['show_on_home'] ?? 0),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateBookmark(int $id, int $userId, array $data): bool
    {
        $pdo = Database::getConnection();
        $fields = [];
        $values = [];
        foreach (['group_id', 'name', 'url', 'description', 'icon_type', 'icon_value', 'screenshot', 'sort_order', 'show_on_home'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $values[] = $userId;
        $stmt = $pdo->prepare(
            "UPDATE nav_bookmarks SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute($values);
    }

    public static function deleteBookmark(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM nav_bookmarks WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public static function getBookmark(int $id, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT b.*, g.name AS group_name FROM nav_bookmarks b LEFT JOIN nav_groups g ON g.id = b.group_id WHERE b.id = ? AND b.user_id = ?");
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return null;
        $row['urls'] = self::listUrls($id);
        return $row;
    }

    public static function listUrls(int $bookmarkId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM nav_bookmark_urls WHERE bookmark_id = ? ORDER BY sort_order, id");
        $stmt->execute([$bookmarkId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function saveUrls(int $bookmarkId, array $urls): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare("DELETE FROM nav_bookmark_urls WHERE bookmark_id = ?")->execute([$bookmarkId]);
        $stmt = $pdo->prepare("INSERT INTO nav_bookmark_urls (bookmark_id, url, label, sort_order) VALUES (?, ?, ?, ?)");
        foreach ($urls as $i => $u) {
            $url = trim((string)($u['url'] ?? ''));
            if ($url === '') continue;
            $stmt->execute([
                $bookmarkId,
                $url,
                trim((string)($u['label'] ?? '')),
                $i,
            ]);
        }
    }

    public static function push(int $bookmarkId, int $pushedBy, int $targetUserId): bool
    {
        $pdo = Database::getConnection();
        $exists = $pdo->prepare("SELECT 1 FROM nav_pushes WHERE bookmark_id = ? AND target_user_id = ?");
        $exists->execute([$bookmarkId, $targetUserId]);
        if ($exists->fetchColumn()) return true;
        $stmt = $pdo->prepare("INSERT INTO nav_pushes (bookmark_id, pushed_by, target_user_id) VALUES (?, ?, ?)");
        return $stmt->execute([$bookmarkId, $pushedBy, $targetUserId]);
    }

    public static function unpush(int $bookmarkId, int $targetUserId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM nav_pushes WHERE bookmark_id = ? AND target_user_id = ?");
        return $stmt->execute([$bookmarkId, $targetUserId]);
    }

    public static function listPushedBookmarks(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT b.*, g.name AS group_name, p.pushed_by, p.created_at AS pushed_at
             FROM nav_pushes p
             JOIN nav_bookmarks b ON b.id = p.bookmark_id
             JOIN nav_groups g ON g.id = b.group_id
             WHERE (p.target_user_id = ? OR p.target_user_id = 0)
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['urls'] = self::listUrls((int)$row['id']);
        }
        return $rows;
    }

    public static function getPushedTargets(int $bookmarkId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT target_user_id FROM nav_pushes WHERE bookmark_id = ?");
        $stmt->execute([$bookmarkId]);
        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'target_user_id');
    }

    public static function listHomeBookmarks(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT b.*, g.name AS group_name FROM nav_bookmarks b
             JOIN nav_groups g ON g.id = b.group_id
             WHERE b.user_id = ? AND b.show_on_home = 1
             ORDER BY g.sort_order, g.id, b.sort_order, b.id"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['urls'] = self::listUrls((int)$row['id']);
        }
        return $rows;
    }
}
