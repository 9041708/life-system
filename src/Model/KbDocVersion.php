<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class KbDocVersion
{
    public static function create(int $docId, int $userId, string $title, string $content): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(version_num), 0) FROM kb_doc_versions WHERE doc_id = :did');
        $stmt->execute([':did' => $docId]);
        $nextVer = (int)$stmt->fetchColumn() + 1;
        $stmt = $pdo->prepare('INSERT INTO kb_doc_versions (doc_id, user_id, title, content, version_num) VALUES (:did, :uid, :title, :content, :ver)');
        $stmt->execute([':did' => $docId, ':uid' => $userId, ':title' => $title, ':content' => $content, ':ver' => $nextVer]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByDoc(int $docId, int $userId, int $limit = 20): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, title, version_num, created_at FROM kb_doc_versions WHERE doc_id = :did AND user_id = :uid ORDER BY version_num DESC LIMIT :lim');
        $stmt->bindValue(':did', $docId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function findById(int $id, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kb_doc_versions WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function cleanOldVersions(int $docId, int $keepMax): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM kb_doc_versions WHERE doc_id = :did ORDER BY version_num DESC');
        $stmt->execute([':did' => $docId]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        if (count($ids) <= $keepMax) return;
        $toDelete = array_slice($ids, $keepMax);
        foreach ($toDelete as $id) {
            $pdo->prepare('DELETE FROM kb_doc_versions WHERE id = :id')->execute([':id' => $id]);
        }
    }

    public static function countByDoc(int $docId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM kb_doc_versions WHERE doc_id = :did');
        $stmt->execute([':did' => $docId]);
        return (int)$stmt->fetchColumn();
    }
}
