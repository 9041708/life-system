<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class SystemIconSubmission
{
    private static function ensureIndexes(PDO $pdo): void
    {
        try {
            $st = $pdo->query("SHOW TABLES LIKE 'system_icon_submissions'");
            $exists = $st ? (bool)$st->fetch(PDO::FETCH_NUM) : false;
            if (!$exists) {
                return;
            }

            $existing = [];
            $idx = $pdo->query("SHOW INDEX FROM system_icon_submissions");
            $rows = $idx ? ($idx->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            foreach ($rows as $r) {
                $k = (string)($r['Key_name'] ?? '');
                if ($k !== '') {
                    $existing[$k] = true;
                }
            }

            if (empty($existing['idx_sis_user_path_id'])) {
                $pdo->exec('CREATE INDEX idx_sis_user_path_id ON system_icon_submissions (user_id, file_path, id)');
            }
            if (empty($existing['idx_sis_user_status_id'])) {
                $pdo->exec('CREATE INDEX idx_sis_user_status_id ON system_icon_submissions (user_id, status, id)');
            }
            if (empty($existing['idx_sis_status_id'])) {
                $pdo->exec('CREATE INDEX idx_sis_status_id ON system_icon_submissions (status, id)');
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }
    /**
     * 返回：file_path => latest_row_meta
     *
     * 说明：用于图标库列表展示“驳回原因/审核备注”等。
     */
    public static function listLatestMetaByUser(int $userId, int $limit = 20000): array
    {
        $limit = max(1, min(50000, (int)$limit));
        $pdo = Database::getConnection();

        self::ensureIndexes($pdo);

        try {
            $sql = "SELECT s.file_path, s.status, s.review_note, s.reviewed_at\n" .
                "  FROM system_icon_submissions s\n" .
                "  JOIN (\n" .
                "        SELECT file_path, MAX(id) AS max_id\n" .
                "          FROM system_icon_submissions\n" .
                "         WHERE user_id = :uid\n" .
                "         GROUP BY file_path\n" .
                "       ) t ON t.max_id = s.id\n" .
                " ORDER BY s.id DESC\n" .
                " LIMIT " . $limit;
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        $map = [];
        foreach ($rows as $r) {
            $p = (string)($r['file_path'] ?? '');
            if ($p === '') continue;
            $map[$p] = [
                'status' => (string)($r['status'] ?? ''),
                'review_note' => (string)($r['review_note'] ?? ''),
                'reviewed_at' => (string)($r['reviewed_at'] ?? ''),
            ];
        }
        return $map;
    }

    /**
     * 返回：file_path => latest_status
     *
     * 说明：用于图标库列表渲染“待审核/已通过/已驳回”。
     */
    public static function listLatestStatusByUser(int $userId, int $limit = 20000): array
    {
        $limit = max(1, min(50000, (int)$limit));
        $pdo = Database::getConnection();

        self::ensureIndexes($pdo);

        // 兼容：不要求窗口函数，使用 MAX(id) 取每个 file_path 的最新记录。
        try {
            $sql = "SELECT s.file_path, s.status\n" .
                "  FROM system_icon_submissions s\n" .
                "  JOIN (\n" .
                "        SELECT file_path, MAX(id) AS max_id\n" .
                "          FROM system_icon_submissions\n" .
                "         WHERE user_id = :uid\n" .
                "         GROUP BY file_path\n" .
                "       ) t ON t.max_id = s.id\n" .
                " ORDER BY s.id DESC\n" .
                " LIMIT " . $limit;
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        $map = [];
        foreach ($rows as $r) {
            $p = (string)($r['file_path'] ?? '');
            if ($p === '') continue;
            $map[$p] = (string)($r['status'] ?? '');
        }
        return $map;
    }

    public static function listOpenFilePathsByUser(int $userId, int $limit = 5000): array
    {
        $limit = max(1, min(20000, (int)$limit));
        $pdo = Database::getConnection();
        self::ensureIndexes($pdo);
        try {
            $stmt = $pdo->prepare("SELECT DISTINCT file_path FROM system_icon_submissions WHERE user_id = :uid AND status IN ('pending','approved') ORDER BY id DESC LIMIT " . $limit);
            $stmt->execute([':uid' => $userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $paths = [];
            foreach ($rows as $r) {
                $p = (string)($r['file_path'] ?? '');
                if ($p !== '') {
                    $paths[$p] = true;
                }
            }
            return $paths;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function findLatestByUserAndPath(int $userId, string $filePath): ?array
    {
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare('SELECT * FROM system_icon_submissions WHERE user_id = :uid AND file_path = :path ORDER BY id DESC LIMIT 1');
            $stmt->execute([':uid' => $userId, ':path' => $filePath]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 视为“已提交”：存在 pending 或 approved 的记录（rejected 允许再次提交）。
     */
    public static function hasOpenSubmission(int $userId, string $filePath): bool
    {
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT id FROM system_icon_submissions WHERE user_id = :uid AND file_path = :path AND status IN ('pending','approved') ORDER BY id DESC LIMIT 1");
            $stmt->execute([':uid' => $userId, ':path' => $filePath]);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function createIfNotOpen(int $userId, string $name, string $filePath): ?int
    {
        if (self::hasOpenSubmission($userId, $filePath)) {
            return null;
        }
        try {
            return self::create($userId, $name, $filePath);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function create(int $userId, string $name, string $filePath): int
    {
        $pdo = Database::getConnection();
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare('INSERT INTO system_icon_submissions (user_id, name, file_path, status, created_at, updated_at) VALUES (:uid,:name,:path,\'pending\',:ca,:ua)');
        $stmt->execute([
            ':uid' => $userId,
            ':name' => $name,
            ':path' => $filePath,
            ':ca' => $now,
            ':ua' => $now,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function listPending(int $limit = 50): array
    {
        $limit = max(1, min(200, (int)$limit));
        $pdo = Database::getConnection();
        self::ensureIndexes($pdo);
        $stmt = $pdo->prepare('SELECT * FROM system_icon_submissions WHERE status = \'pending\' ORDER BY id DESC LIMIT ' . $limit);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function countPending(): int
    {
        $pdo = Database::getConnection();
        self::ensureIndexes($pdo);
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM system_icon_submissions WHERE status = 'pending'");
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function listPendingPaged(int $offset, int $limit): array
    {
        $offset = max(0, (int)$offset);
        $limit = max(1, min(200, (int)$limit));
        $pdo = Database::getConnection();
        self::ensureIndexes($pdo);
        try {
            $stmt = $pdo->query("SELECT * FROM system_icon_submissions WHERE status = 'pending' ORDER BY id DESC LIMIT " . $limit . ' OFFSET ' . $offset);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM system_icon_submissions WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function markReviewed(int $id, string $status, int $reviewedBy, ?string $reviewAction = null, ?string $reviewNote = null): bool
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return false;
        }
        if ($reviewAction !== null && !in_array($reviewAction, ['publish', 'replace'], true)) {
            $reviewAction = null;
        }
        $pdo = Database::getConnection();
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare('UPDATE system_icon_submissions SET status = :status, reviewed_by = :rb, reviewed_at = :ra, review_action = :act, review_note = :note, updated_at = :ua WHERE id = :id AND status = \'pending\'');
        $stmt->execute([
            ':status' => $status,
            ':rb' => $reviewedBy,
            ':ra' => $now,
            ':act' => $reviewAction,
            ':note' => $reviewNote,
            ':ua' => $now,
            ':id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }
}
