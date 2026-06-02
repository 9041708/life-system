<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class Ledger
{
    public static function hasSharedLedgersForUser(int $userId): bool
    {
        $pdo = Database::getConnection();

        // 兼容旧库：无 ledgers 表 / ledger_members 表时直接视为无共享账本
        try {
            $check = $pdo->query("SHOW TABLES LIKE 'ledgers'");
            if (!$check || !$check->fetchColumn()) {
                return false;
            }
            $check2 = $pdo->query("SHOW TABLES LIKE 'ledger_members'");
            if (!$check2 || !$check2->fetchColumn()) {
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }

        try {
            $stmt = $pdo->prepare("SELECT 1
                FROM ledgers l
                INNER JOIN ledger_members m ON m.ledger_id = l.id
                WHERE l.type = 'shared' AND m.user_id = :uid
                LIMIT 1");
            $stmt->execute([':uid' => $userId]);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function findById(int $ledgerId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ledgers WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $ledgerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function ensurePersonalLedger(int $userId): ?int
    {
        $pdo = Database::getConnection();

        // 若库尚未升级（无 ledgers 表 / 无 personal_ledger_id 列），直接返回 null 以兼容旧版本
        try {
            $check = $pdo->query("SHOW TABLES LIKE 'ledgers'");
            if (!$check || !$check->fetchColumn()) {
                return null;
            }
        } catch (\Throwable $e) {
            return null;
        }

        // 读取用户现有 personal_ledger_id
        $user = null;
        try {
            $stmt = $pdo->prepare('SELECT id, username, nickname, personal_ledger_id FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            return null;
        }

        if ($user && !empty($user['personal_ledger_id'])) {
            return (int)$user['personal_ledger_id'];
        }

        // 创建个人账本（若不存在）
        $name = (string)($user['nickname'] ?? $user['username'] ?? '个人');
        $name = trim($name) !== '' ? $name . '的个人账本' : '个人账本';

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id FROM ledgers WHERE owner_user_id = :uid AND type = 'personal' LIMIT 1");
            $stmt->execute([':uid' => $userId]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);

            $ledgerId = $existingId;
            if ($ledgerId <= 0) {
                $stmt = $pdo->prepare("INSERT INTO ledgers (type, name, owner_user_id) VALUES ('personal', :name, :uid)");
                $stmt->execute([':name' => $name, ':uid' => $userId]);
                $ledgerId = (int)$pdo->lastInsertId();
            }

            // 写回 users.personal_ledger_id / active_ledger_id
            try {
                $stmt = $pdo->prepare('UPDATE users SET personal_ledger_id = :lid, active_ledger_id = COALESCE(active_ledger_id, :lid) WHERE id = :uid');
                $stmt->execute([':lid' => $ledgerId, ':uid' => $userId]);
            } catch (\Throwable $e) {
                // 忽略（旧库无列）
            }

            // 把旧数据补上 ledger_id（仅当列存在且数据为 NULL 时）
            foreach (['accounts', 'categories', 'items', 'budgets', 'transactions', 'icon_library'] as $table) {
                try {
                    $pdo->exec("UPDATE {$table} SET ledger_id = {$ledgerId} WHERE user_id = {$userId} AND ledger_id IS NULL");
                } catch (\Throwable $e) {
                    // 忽略（旧库无列/无表）
                }
            }

            $pdo->commit();
            return $ledgerId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return null;
        }
    }

    public static function listForUser(int $userId): array
    {
        $pdo = Database::getConnection();

        // 个人账本 + 加入的共享账本
        $sql = "SELECT l.*, 
                       CASE 
                         WHEN l.type = 'personal' AND l.owner_user_id = :uid THEN 'admin'
                         ELSE COALESCE(m.role, 'member')
                       END AS member_role
                FROM ledgers l
                LEFT JOIN ledger_members m ON m.ledger_id = l.id AND m.user_id = :uid
                WHERE (l.type = 'personal' AND l.owner_user_id = :uid)
                   OR (l.type = 'shared' AND m.user_id = :uid)
                ORDER BY l.type DESC, l.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function createShared(int $userId, string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            $name = '公共账本';
        }

        $pdo = Database::getConnection();
        $inviteCode = bin2hex(random_bytes(8));

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO ledgers (type, name, owner_user_id, invite_code) VALUES ('shared', :name, :uid, :code)");
            $stmt->execute([':name' => $name, ':uid' => $userId, ':code' => $inviteCode]);
            $ledgerId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO ledger_members (ledger_id, user_id, role) VALUES (:lid, :uid, 'admin')");
            $stmt->execute([':lid' => $ledgerId, ':uid' => $userId]);

            $pdo->commit();
            return $ledgerId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return null;
        }
    }

    public static function updateSharedName(int $ledgerId, int $userId, string $name): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }
        $pdo = Database::getConnection();

        // 仅共享账本管理员可改名
        $stmt = $pdo->prepare("SELECT l.type, m.role FROM ledgers l LEFT JOIN ledger_members m ON m.ledger_id = l.id AND m.user_id = :uid WHERE l.id = :lid LIMIT 1");
        $stmt->execute([':lid' => $ledgerId, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row || ($row['type'] ?? '') !== 'shared' || ($row['role'] ?? '') !== 'admin') {
            return false;
        }

        $stmt = $pdo->prepare('UPDATE ledgers SET name = :name WHERE id = :id');
        $stmt->execute([':name' => $name, ':id' => $ledgerId]);
        return $stmt->rowCount() > 0;
    }

    public static function regenerateInviteCode(int $ledgerId, int $userId): ?string
    {
        $pdo = Database::getConnection();
        $code = bin2hex(random_bytes(8));

        $stmt = $pdo->prepare("SELECT l.type, m.role FROM ledgers l LEFT JOIN ledger_members m ON m.ledger_id = l.id AND m.user_id = :uid WHERE l.id = :lid LIMIT 1");
        $stmt->execute([':lid' => $ledgerId, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row || ($row['type'] ?? '') !== 'shared' || ($row['role'] ?? '') !== 'admin') {
            return null;
        }

        $stmt = $pdo->prepare('UPDATE ledgers SET invite_code = :code WHERE id = :id');
        $stmt->execute([':code' => $code, ':id' => $ledgerId]);
        return $code;
    }

    public static function joinByInviteCode(int $userId, string $inviteCode): ?int
    {
        $inviteCode = trim($inviteCode);
        if ($inviteCode === '') {
            return null;
        }
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT id FROM ledgers WHERE type = 'shared' AND invite_code = :code LIMIT 1");
        $stmt->execute([':code' => $inviteCode]);
        $ledgerId = (int)($stmt->fetchColumn() ?: 0);
        if ($ledgerId <= 0) {
            return null;
        }

        // 幂等加入
        $stmt = $pdo->prepare('INSERT IGNORE INTO ledger_members (ledger_id, user_id, role) VALUES (:lid, :uid, \"member\")');
        $stmt->execute([':lid' => $ledgerId, ':uid' => $userId]);

        return $ledgerId;
    }

    public static function deleteShared(int $ledgerId, int $userId): bool
    {
        $pdo = Database::getConnection();

        // 仅允许共享账本管理员删除
        $stmt = $pdo->prepare("SELECT l.type, l.owner_user_id, m.role FROM ledgers l LEFT JOIN ledger_members m ON m.ledger_id = l.id AND m.user_id = :uid WHERE l.id = :lid LIMIT 1");
        $stmt->execute([':lid' => $ledgerId, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row || ($row['type'] ?? '') !== 'shared') {
            return false;
        }
        $ownerId = (int)($row['owner_user_id'] ?? 0);
        $role = (string)($row['role'] ?? '');
        if (!($ownerId === $userId || $role === 'admin')) {
            return false;
        }

        $pdo->beginTransaction();
        try {
            // 删除与该账本相关的业务数据
            // 注意：后续新增了 goals 等带 ledger_id 外键的表，需要一并删除以避免外键约束导致删除失败
            $tables = ['transaction_attachments', 'transactions', 'budgets', 'icon_library', 'items', 'categories', 'accounts', 'goals'];
            foreach ($tables as $table) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM {$table} WHERE ledger_id = :lid");
                    $stmt->execute([':lid' => $ledgerId]);
                } catch (\Throwable $e) {
                    // 兼容旧库：无对应表或列时忽略
                }
            }

            // 删除成员关系
            try {
                $stmt = $pdo->prepare('DELETE FROM ledger_members WHERE ledger_id = :lid');
                $stmt->execute([':lid' => $ledgerId]);
            } catch (\Throwable $e) {
                // 忽略
            }

            // 调整受影响用户的当前账本为其个人账本（如有）
            try {
                $stmt = $pdo->prepare('UPDATE users SET active_ledger_id = personal_ledger_id WHERE active_ledger_id = :lid');
                $stmt->execute([':lid' => $ledgerId]);
            } catch (\Throwable $e) {
                // 忽略旧库
            }

            // 删除账本本身
            $stmt = $pdo->prepare("DELETE FROM ledgers WHERE id = :id AND type = 'shared'");
            $stmt->execute([':id' => $ledgerId]);
            $affected = $stmt->rowCount() > 0;
            $pdo->commit();
            return $affected;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }
}
