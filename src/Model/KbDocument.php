<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class KbDocument
{
    public static function create(int $spaceId, int $userId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO kb_documents (space_id, user_id, parent_id, title, content, content_html, sort_order, is_folder, status) VALUES (:sid, :uid, :pid, :title, :content, :html, :sort, :folder, :status)');
        $stmt->execute([
            ':sid' => $spaceId,
            ':uid' => $userId,
            ':pid' => (int)($data['parent_id'] ?? 0),
            ':title' => $data['title'] ?? '无标题',
            ':content' => $data['content'] ?? '',
            ':html' => $data['content_html'] ?? '',
            ':sort' => (int)($data['sort_order'] ?? 0),
            ':folder' => (int)($data['is_folder'] ?? 0),
            ':status' => $data['status'] ?? 'published',
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, int $userId, array $data): void
    {
        $pdo = Database::getConnection();
        $sets = [];
        $vals = [':id' => $id, ':uid' => $userId];
        foreach ($data as $k => $v) {
            $sets[] = "{$k} = :{$k}";
            $vals[":{$k}"] = $v;
        }
        if (empty($sets)) return;
        $pdo->prepare('UPDATE kb_documents SET ' . implode(', ', $sets) . ' WHERE id = :id AND user_id = :uid')->execute($vals);
    }

    public static function findById(int $id, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kb_documents WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByIdAny(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kb_documents WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByToken(string $token): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT d.*, s.name as space_name, s.user_id as space_user_id FROM kb_documents d LEFT JOIN kb_spaces s ON s.id = d.space_id WHERE d.share_token = :t AND d.is_public = 1');
        $stmt->execute([':t' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getTree(int $spaceId, int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, parent_id, title, is_folder, sort_order, status FROM kb_documents WHERE space_id = :sid AND user_id = :uid ORDER BY is_folder DESC, sort_order ASC, id ASC');
        $stmt->execute([':sid' => $spaceId, ':uid' => $userId]);
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function getChildren(int $spaceId, int $parentId, int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, parent_id, title, is_folder, sort_order, status FROM kb_documents WHERE space_id = :sid AND parent_id = :pid AND user_id = :uid ORDER BY is_folder DESC, sort_order ASC, id ASC');
        $stmt->execute([':sid' => $spaceId, ':pid' => $parentId, ':uid' => $userId]);
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function delete(int $id, int $userId): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('DELETE FROM kb_doc_versions WHERE doc_id = :id AND user_id = :uid')->execute([':id' => $id, ':uid' => $userId]);
        $pdo->prepare('DELETE FROM kb_documents WHERE id = :id AND user_id = :uid')->execute([':id' => $id, ':uid' => $userId]);
    }

    public static function deleteRecursive(int $id, int $userId): void
    {
        $children = self::getChildren(self::findById($id, $userId)['space_id'] ?? 0, $id, $userId);
        foreach ($children as $child) {
            self::deleteRecursive((int)$child['id'], $userId);
        }
        self::delete($id, $userId);
    }

    public static function getMaxSortOrder(int $spaceId, int $parentId, int $userId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM kb_documents WHERE space_id = :sid AND parent_id = :pid AND user_id = :uid');
        $stmt->execute([':sid' => $spaceId, ':pid' => $parentId, ':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function search(int $spaceId, int $userId, string $keyword): array
    {
        $pdo = Database::getConnection();
        $kw = '%' . $keyword . '%';
        $stmt = $pdo->prepare('SELECT id, parent_id, title, is_folder FROM kb_documents WHERE space_id = :sid AND user_id = :uid AND (title LIKE :kw OR content LIKE :kw) ORDER BY is_folder DESC, updated_at DESC LIMIT 50');
        $stmt->execute([':sid' => $spaceId, ':uid' => $userId, ':kw' => $kw]);
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }
}
