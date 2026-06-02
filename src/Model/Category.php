<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class Category
{
    public static function findByLedger(int $ledgerId, int $id, ?string $type = null): ?array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT * FROM categories WHERE id = :id AND ledger_id = :lid';
        $params = [':id' => $id, ':lid' => $ledgerId];
        if ($type !== null && $type !== '') {
            $sql .= ' AND type = :type';
            $params[':type'] = $type;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByUser(int $userId, int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function allByLedger(int $ledgerId, ?string $type = null): array
    {
        $pdo = Database::getConnection();
        if ($type) {
            $stmt = $pdo->prepare('SELECT * FROM categories WHERE ledger_id = :lid AND type = :type ORDER BY sort_order, id');
            $stmt->execute([':lid' => $ledgerId, ':type' => $type]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM categories WHERE ledger_id = :lid ORDER BY type, sort_order, id');
            $stmt->execute([':lid' => $ledgerId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function allByUser(int $userId, ?string $type = null): array
    {
        $pdo = Database::getConnection();
        if ($type) {
            $stmt = $pdo->prepare('SELECT * FROM categories WHERE user_id = :uid AND type = :type ORDER BY sort_order, id');
            $stmt->execute([':uid' => $userId, ':type' => $type]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM categories WHERE user_id = :uid ORDER BY type, sort_order, id');
            $stmt->execute([':uid' => $userId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function createForLedger(int $userId, int $ledgerId, string $type, string $name, int $sortOrder = 0, ?string $iconType = null, ?string $iconValue = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO categories (user_id, ledger_id, type, name, sort_order, icon_type, icon_value) VALUES (:uid,:lid,:type,:name,:sort,:icon_type,:icon_value)');
        $stmt->execute([
            ':uid' => $userId,
            ':lid' => $ledgerId,
            ':type' => $type,
            ':name' => $name,
            ':sort' => $sortOrder,
            ':icon_type' => $iconType,
            ':icon_value' => $iconValue,
        ]);
    }

    public static function create(int $userId, string $type, string $name, int $sortOrder = 0, ?string $iconType = null, ?string $iconValue = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO categories (user_id, type, name, sort_order, icon_type, icon_value) VALUES (:uid,:type,:name,:sort,:icon_type,:icon_value)');
        $stmt->execute([
            ':uid' => $userId,
            ':type' => $type,
            ':name' => $name,
            ':sort' => $sortOrder,
            ':icon_type' => $iconType,
            ':icon_value' => $iconValue,
        ]);
    }

    public static function updateForLedger(int $ledgerId, int $id, string $name, int $sortOrder = 0, ?string $iconType = null, ?string $iconValue = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE categories SET name = :name, sort_order = :sort, icon_type = :icon_type, icon_value = :icon_value WHERE id = :id AND ledger_id = :lid');
        $stmt->execute([
            ':name' => $name,
            ':sort' => $sortOrder,
            ':icon_type' => $iconType,
            ':icon_value' => $iconValue,
            ':id' => $id,
            ':lid' => $ledgerId,
        ]);
    }

    public static function update(int $userId, int $id, string $name, int $sortOrder = 0, ?string $iconType = null, ?string $iconValue = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE categories SET name = :name, sort_order = :sort, icon_type = :icon_type, icon_value = :icon_value WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':name' => $name,
            ':sort' => $sortOrder,
            ':icon_type' => $iconType,
            ':icon_value' => $iconValue,
            ':id' => $id,
            ':uid' => $userId,
        ]);
    }

    public static function deleteForLedger(int $ledgerId, int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE ledger_id = :lid AND category_id = :id');
        $stmt->execute([':lid' => $ledgerId, ':id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            return false;
        }
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id AND ledger_id = :lid');
        $stmt->execute([':id' => $id, ':lid' => $ledgerId]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $userId, int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE category_id = :id');
        $stmt->execute([':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            return false;
        }
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
