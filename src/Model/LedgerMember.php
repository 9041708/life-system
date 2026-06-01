<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class LedgerMember
{
    public static function isAdmin(int $ledgerId, int $userId): bool
    {
        return self::getRole($ledgerId, $userId) === 'admin';
    }

    public static function getRole(int $ledgerId, int $userId): ?string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT role FROM ledger_members WHERE ledger_id = :lid AND user_id = :uid LIMIT 1');
        $stmt->execute([':lid' => $ledgerId, ':uid' => $userId]);
        $role = $stmt->fetchColumn();
        if (!$role) {
            return null;
        }
        return (string)$role;
    }

    public static function isMember(int $ledgerId, int $userId): bool
    {
        return self::getRole($ledgerId, $userId) !== null;
    }

    public static function listMembers(int $ledgerId): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT m.user_id, m.role,
                       u.username, u.nickname, u.email
                FROM ledger_members m
                LEFT JOIN users u ON u.id = m.user_id
                WHERE m.ledger_id = :lid
                ORDER BY (CASE WHEN m.role = 'admin' THEN 0 ELSE 1 END), m.user_id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':lid' => $ledgerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function addMember(int $ledgerId, int $operatorUserId, int $targetUserId): bool
    {
        if ($ledgerId <= 0 || $operatorUserId <= 0 || $targetUserId <= 0) {
            return false;
        }

        $ledger = Ledger::findById($ledgerId);
        if (!$ledger || ($ledger['type'] ?? '') !== 'shared') {
            return false;
        }

        $ownerId = (int)($ledger['owner_user_id'] ?? 0);
        if (!($ownerId === $operatorUserId || self::isAdmin($ledgerId, $operatorUserId))) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT IGNORE INTO ledger_members (ledger_id, user_id, role) VALUES (:lid, :uid, 'member')");
        $stmt->execute([':lid' => $ledgerId, ':uid' => $targetUserId]);
        return $stmt->rowCount() > 0;
    }

    public static function removeMember(int $ledgerId, int $operatorUserId, int $targetUserId): bool
    {
        if ($ledgerId <= 0 || $operatorUserId <= 0 || $targetUserId <= 0) {
            return false;
        }

        $ledger = Ledger::findById($ledgerId);
        if (!$ledger || ($ledger['type'] ?? '') !== 'shared') {
            return false;
        }

        $ownerId = (int)($ledger['owner_user_id'] ?? 0);
        if ($targetUserId === $ownerId) {
            return false;
        }
        if ($targetUserId === $operatorUserId) {
            return false;
        }
        if (!($ownerId === $operatorUserId || self::isAdmin($ledgerId, $operatorUserId))) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM ledger_members WHERE ledger_id = :lid AND user_id = :uid');
        $stmt->execute([':lid' => $ledgerId, ':uid' => $targetUserId]);
        return $stmt->rowCount() > 0;
    }
}
