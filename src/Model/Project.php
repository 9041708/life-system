<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class Project
{
    public static function create(int $userId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO projects (user_id, name, description, tasks, status, progress, start_date) VALUES (:uid, :name, :desc, :tasks, :status, :progress, :start)');
        $stmt->execute([
            ':uid' => $userId,
            ':name' => $data['name'],
            ':desc' => $data['description'] ?? '',
            ':tasks' => $data['tasks'] ?? null,
            ':status' => $data['status'] ?? 'planning',
            ':progress' => (int)($data['progress'] ?? 0),
            ':start' => $data['start_date'] ?: null,
        ]);
        $projectId = (int)$pdo->lastInsertId();
        self::addMember($projectId, $userId, 'owner');
        return $projectId;
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE projects SET name = :name, description = :desc, tasks = :tasks, status = :status, progress = :progress, start_date = :start WHERE id = :id AND user_id = :uid');
        return $stmt->execute([
            ':name' => $data['name'],
            ':desc' => $data['description'] ?? '',
            ':tasks' => $data['tasks'] ?? null,
            ':status' => $data['status'] ?? 'planning',
            ':progress' => (int)($data['progress'] ?? 0),
            ':start' => $data['start_date'] ?: null,
            ':id' => $id,
            ':uid' => $userId,
        ]);
    }

    public static function updateTasks(int $id, int $userId, string $tasks): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE projects SET tasks = :tasks WHERE id = :id AND user_id = :uid');
        return $stmt->execute([':tasks' => $tasks, ':id' => $id, ':uid' => $userId]);
    }

    public static function findById(int $id, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        if (!empty($row['tasks'])) {
            $row['tasks'] = json_decode($row['tasks'], true) ?: [];
        } else {
            $row['tasks'] = [];
        }
        return $row;
    }

    public static function delete(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $pdo->prepare('DELETE FROM project_updates WHERE project_id = :pid AND user_id = :uid')->execute([':pid' => $id, ':uid' => $userId]);
        $stmt = $pdo->prepare('DELETE FROM projects WHERE id = :id AND user_id = :uid');
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    public static function listByUser(int $userId, string $status = ''): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT p.*, p.tasks as tasks_json, (SELECT COUNT(*) FROM project_updates WHERE project_id = p.id) as update_count, (SELECT update_date FROM project_updates WHERE project_id = p.id ORDER BY update_date DESC LIMIT 1) as last_update_date, pm.role as my_role FROM projects p INNER JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = :uid';
        $params = [':uid' => $userId];
        if ($status !== '' && $status !== 'all') {
            $sql .= ' AND p.status = :status';
            $params[':status'] = $status;
        }
        $sql .= ' ORDER BY FIELD(p.status, "active", "planning", "completed", "archived"), p.updated_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks = [];
            if (!empty($row['tasks_json'])) {
                $tasks = json_decode($row['tasks_json'], true) ?: [];
            }
            $row['tasks'] = $tasks;
            $row['tasks_total'] = count($tasks);
            $row['tasks_done'] = count(array_filter($tasks, fn($t) => !empty($t['done'])));
            unset($row['tasks_json']);
            $rows[] = $row;
        }
        return $rows;
    }

    public static function syncProgress(int $projectId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT progress FROM project_updates WHERE project_id = :pid ORDER BY update_date DESC, created_at DESC LIMIT 1');
        $stmt->execute([':pid' => $projectId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $progress = $row ? (int)$row['progress'] : 0;
        $pdo->prepare('UPDATE projects SET progress = :p WHERE id = :pid')->execute([':p' => $progress, ':pid' => $projectId]);
    }

    public static function addMember(int $projectId, int $userId, string $role = 'member'): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT IGNORE INTO project_members (project_id, user_id, role) VALUES (:pid, :uid, :role)');
        $stmt->execute([':pid' => $projectId, ':uid' => $userId, ':role' => $role]);
    }

    public static function removeMember(int $projectId, int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM project_members WHERE project_id = :pid AND user_id = :uid AND role != "owner"');
        $stmt->execute([':pid' => $projectId, ':uid' => $userId]);
    }

    public static function isMember(int $projectId, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM project_members WHERE project_id = :pid AND user_id = :uid');
        $stmt->execute([':pid' => $projectId, ':uid' => $userId]);
        return $stmt->fetch() !== false;
    }

    public static function isOwner(int $projectId, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM project_members WHERE project_id = :pid AND user_id = :uid AND role = "owner"');
        $stmt->execute([':pid' => $projectId, ':uid' => $userId]);
        return $stmt->fetch() !== false;
    }

    public static function getMembers(int $projectId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT pm.user_id, pm.role, pm.joined_at, u.username, u.nickname, u.email FROM project_members pm LEFT JOIN users u ON u.id = pm.user_id WHERE pm.project_id = :pid ORDER BY pm.role, pm.joined_at');
        $stmt->execute([':pid' => $projectId]);
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }
}
