<?php
namespace App\Service;

use App\Model\Ledger;
use App\Model\LedgerMember;

class LedgerContext
{
    public static function requireActiveLedgerId(int $userId): int
    {
        // 兼容：若库未升级，返回 0 代表旧模式
        $personal = Ledger::ensurePersonalLedger($userId);
        if ($personal === null) {
            return 0;
        }

        $active = (int)($_SESSION['active_ledger_id'] ?? 0);
        if ($active > 0) {
            return $active;
        }

        // 兜底从 users.active_ledger_id 取
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT active_ledger_id, personal_ledger_id FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
            $activeDb = (int)($row['active_ledger_id'] ?? 0);
            if ($activeDb > 0) {
                $_SESSION['active_ledger_id'] = $activeDb;
                return $activeDb;
            }
            $personalDb = (int)($row['personal_ledger_id'] ?? 0);
            if ($personalDb > 0) {
                $_SESSION['active_ledger_id'] = $personalDb;
                return $personalDb;
            }
        } catch (\Throwable $e) {
        }

        $_SESSION['active_ledger_id'] = $personal;
        return $personal;
    }

    public static function setActiveLedgerId(int $userId, int $ledgerId): bool
    {
        if ($ledgerId <= 0) {
            return false;
        }

        // 验证访问权限：个人账本仅 owner；共享账本需 member
        $ledger = Ledger::findById($ledgerId);
        if (!$ledger) {
            return false;
        }
        if (($ledger['type'] ?? '') === 'personal') {
            if ((int)($ledger['owner_user_id'] ?? 0) !== $userId) {
                return false;
            }
        } else {
            if (!LedgerMember::isMember($ledgerId, $userId)) {
                return false;
            }
        }

        $_SESSION['active_ledger_id'] = $ledgerId;
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('UPDATE users SET active_ledger_id = :lid WHERE id = :uid');
            $stmt->execute([':lid' => $ledgerId, ':uid' => $userId]);
        } catch (\Throwable $e) {
            // 忽略
        }
        return true;
    }

    public static function assertCanAccessLedger(int $userId, int $ledgerId): bool
    {
        if ($ledgerId <= 0) {
            return true; // 旧模式
        }
        $ledger = Ledger::findById($ledgerId);
        if (!$ledger) {
            return false;
        }
        if (($ledger['type'] ?? '') === 'personal') {
            return (int)($ledger['owner_user_id'] ?? 0) === $userId;
        }
        return LedgerMember::isMember($ledgerId, $userId);
    }
}
