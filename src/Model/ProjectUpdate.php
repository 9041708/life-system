<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class ProjectUpdate
{
    public static function create(int $projectId, int $userId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO project_updates (project_id, user_id, title, content, progress, update_date, attachments) VALUES (:pid, :uid, :title, :content, :progress, :date, :attach)');
        $stmt->execute([
            ':pid' => $projectId,
            ':uid' => $userId,
            ':title' => $data['title'],
            ':content' => $data['content'] ?? '',
            ':progress' => (int)($data['progress'] ?? 0),
            ':date' => $data['update_date'],
            ':attach' => $data['attachments'] ?? '',
        ]);
        $id = (int)$pdo->lastInsertId();
        Project::syncProgress($projectId);
        return $id;
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE project_updates SET title = :title, content = :content, progress = :progress, update_date = :date, attachments = :attach WHERE id = :id AND user_id = :uid');
        $result = $stmt->execute([
            ':title' => $data['title'],
            ':content' => $data['content'] ?? '',
            ':progress' => (int)($data['progress'] ?? 0),
            ':date' => $data['update_date'],
            ':attach' => $data['attachments'] ?? '',
            ':id' => $id,
            ':uid' => $userId,
        ]);
        if ($result) {
            $row = self::findById($id, $userId);
            if ($row) Project::syncProgress($row['project_id']);
        }
        return $result;
    }

    public static function delete(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $row = self::findById($id, $userId);
        $stmt = $pdo->prepare('DELETE FROM project_updates WHERE id = :id AND user_id = :uid');
        $result = $stmt->execute([':id' => $id, ':uid' => $userId]);
        if ($result && $row) Project::syncProgress($row['project_id']);
        return $result;
    }

    public static function findById(int $id, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM project_updates WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['attachments'])) {
            $row['attachments'] = json_decode($row['attachments'], true) ?: [];
        }
        return $row ?: null;
    }

    public static function listByProject(int $projectId, int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM project_updates WHERE project_id = :pid AND user_id = :uid ORDER BY update_date DESC, created_at DESC');
        $stmt->execute([':pid' => $projectId, ':uid' => $userId]);
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['attachments'])) {
                $row['attachments'] = json_decode($row['attachments'], true) ?: [];
            }
            $rows[] = $row;
        }
        return $rows;
    }
}
