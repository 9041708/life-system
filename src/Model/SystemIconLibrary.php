<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class SystemIconLibrary
{
    private static function ensureColumns(): void
    {
        $pdo = Database::getConnection();

        $need = [
            'source_type' => "ALTER TABLE system_icon_library ADD COLUMN source_type VARCHAR(16) NULL DEFAULT NULL",
            'source_mode' => "ALTER TABLE system_icon_library ADD COLUMN source_mode VARCHAR(16) NULL DEFAULT NULL",
            'created_at' => "ALTER TABLE system_icon_library ADD COLUMN created_at DATETIME NULL DEFAULT NULL",
            'updated_at' => "ALTER TABLE system_icon_library ADD COLUMN updated_at DATETIME NULL DEFAULT NULL",
        ];

        foreach ($need as $col => $sql) {
            $has = false;
            try {
                $colStmt = $pdo->query("SHOW COLUMNS FROM system_icon_library LIKE '" . $col . "'");
                $has = (bool)$colStmt->fetch(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                $has = true;
            }
            if ($has) {
                continue;
            }
            try {
                $pdo->exec($sql);
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public static function findById(int $id): ?array
    {
        self::ensureColumns();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM system_icon_library WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByName(string $name): ?array
    {
        self::ensureColumns();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM system_icon_library WHERE name = :name ORDER BY id LIMIT 1');
        $stmt->execute([':name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function all(): array
    {
        self::ensureColumns();
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM system_icon_library ORDER BY id');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function countAll(): int
    {
        self::ensureColumns();
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->query('SELECT COUNT(*) FROM system_icon_library');
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function countBySearch(string $search): int
    {
        self::ensureColumns();
        $pdo = Database::getConnection();
        try {
            $query = '%' . str_replace('%', '\\%', $search) . '%';
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM system_icon_library WHERE name LIKE :q OR file_path LIKE :q');
            $stmt->execute([':q' => $query]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function listPaged(int $offset, int $limit): array
    {
        self::ensureColumns();
        $offset = max(0, (int)$offset);
        $limit = max(1, min(200, (int)$limit));
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->query('SELECT * FROM system_icon_library ORDER BY id LIMIT ' . $limit . ' OFFSET ' . $offset);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function listPagedBySearch(int $offset, int $limit, string $search): array
    {
        self::ensureColumns();
        $offset = max(0, (int)$offset);
        $limit = max(1, min(200, (int)$limit));
        $pdo = Database::getConnection();
        try {
            $query = '%' . str_replace('%', '\\%', $search) . '%';
            $stmt = $pdo->prepare('SELECT * FROM system_icon_library WHERE name LIKE :q OR file_path LIKE :q ORDER BY id LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':q', $query, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function create(string $name, string $filePath, ?string $sourceType = null, ?string $sourceMode = null): int
    {
        self::ensureColumns();
        $pdo = Database::getConnection();
        $now = date('Y-m-d H:i:s');

        // 动态检查字段是否存在（避免旧库报错）
        $hasSourceType = false;
        $hasSourceMode = false;
        $hasCreatedAt = false;
        $hasUpdatedAt = false;
        try { $hasSourceType = (bool)$pdo->query("SHOW COLUMNS FROM system_icon_library LIKE 'source_type'")->fetch(\PDO::FETCH_ASSOC); } catch (\Throwable $e) {}
        try { $hasSourceMode = (bool)$pdo->query("SHOW COLUMNS FROM system_icon_library LIKE 'source_mode'")->fetch(\PDO::FETCH_ASSOC); } catch (\Throwable $e) {}
        try { $hasCreatedAt = (bool)$pdo->query("SHOW COLUMNS FROM system_icon_library LIKE 'created_at'")->fetch(\PDO::FETCH_ASSOC); } catch (\Throwable $e) {}
        try { $hasUpdatedAt = (bool)$pdo->query("SHOW COLUMNS FROM system_icon_library LIKE 'updated_at'")->fetch(\PDO::FETCH_ASSOC); } catch (\Throwable $e) {}

        $cols = ['name', 'file_path'];
        $vals = [':name', ':path'];
        $params = [':name' => $name, ':path' => $filePath];

        if ($hasSourceType) {
            $cols[] = 'source_type';
            $vals[] = ':st';
            $params[':st'] = $sourceType;
        }
        if ($hasSourceMode) {
            $cols[] = 'source_mode';
            $vals[] = ':sm';
            $params[':sm'] = $sourceMode;
        }
        if ($hasCreatedAt) {
            $cols[] = 'created_at';
            $vals[] = ':ca';
            $params[':ca'] = $now;
        }
        if ($hasUpdatedAt) {
            $cols[] = 'updated_at';
            $vals[] = ':ua';
            $params[':ua'] = $now;
        }

        $sql = 'INSERT INTO system_icon_library (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, string $name, ?string $filePath = null): bool
    {
        self::ensureColumns();
        $pdo = Database::getConnection();
        $now = date('Y-m-d H:i:s');

        $hasUpdatedAt = false;
        try {
            $hasUpdatedAt = (bool)$pdo->query("SHOW COLUMNS FROM system_icon_library LIKE 'updated_at'")->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasUpdatedAt = false;
        }

        if ($filePath !== null && $filePath !== '') {
            $sql = 'UPDATE system_icon_library SET name = :name, file_path = :path' . ($hasUpdatedAt ? ', updated_at = :ua' : '') . ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $params = [
                ':name' => $name,
                ':path' => $filePath,
                ':id' => $id,
            ];
            if ($hasUpdatedAt) {
                $params[':ua'] = $now;
            }
            $stmt->execute($params);
        } else {
            $sql = 'UPDATE system_icon_library SET name = :name' . ($hasUpdatedAt ? ', updated_at = :ua' : '') . ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $params = [
                ':name' => $name,
                ':id' => $id,
            ];
            if ($hasUpdatedAt) {
                $params[':ua'] = $now;
            }
            $stmt->execute($params);
        }
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        self::ensureColumns();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM system_icon_library WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function setSource(int $id, ?string $sourceType, ?string $sourceMode): void
    {
        self::ensureColumns();
        $pdo = Database::getConnection();

        $hasSourceType = false;
        $hasSourceMode = false;
        try { $hasSourceType = (bool)$pdo->query("SHOW COLUMNS FROM system_icon_library LIKE 'source_type'")->fetch(\PDO::FETCH_ASSOC); } catch (\Throwable $e) { $hasSourceType = false; }
        try { $hasSourceMode = (bool)$pdo->query("SHOW COLUMNS FROM system_icon_library LIKE 'source_mode'")->fetch(\PDO::FETCH_ASSOC); } catch (\Throwable $e) { $hasSourceMode = false; }

        $set = [];
        $params = [':id' => $id];

        if ($hasSourceType) {
            $set[] = 'source_type = :st';
            $params[':st'] = $sourceType;
        }
        if ($hasSourceMode) {
            $set[] = 'source_mode = :sm';
            $params[':sm'] = $sourceMode;
        }
        if (empty($set)) {
            return;
        }

        try {
            $stmt = $pdo->prepare('UPDATE system_icon_library SET ' . implode(', ', $set) . ' WHERE id = :id');
            $stmt->execute($params);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
