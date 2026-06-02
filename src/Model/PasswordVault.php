<?php
namespace App\Model;

use App\Service\Database;

class PasswordVault
{
    private static function getCipherKey(): string
    {
        return hash('sha256', 'sanshi-password-vault-key-2026', true);
    }

    public static function encrypt(string $plain): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($plain, 'aes-256-cbc', self::getCipherKey(), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt(string $cipher): string
    {
        $data = base64_decode($cipher);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'aes-256-cbc', self::getCipherKey(), OPENSSL_RAW_DATA, $iv);
    }

    public static function create(int $userId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO password_vault (user_id, name, url, username, password, notes)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId,
            $data['name'],
            $data['url'] ?? null,
            $data['username'],
            self::encrypt($data['password']),
            $data['notes'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByUser(int $userId, string $search = ''): array
    {
        $pdo = Database::getConnection();
        if ($search !== '') {
            $like = '%' . $search . '%';
            $stmt = $pdo->prepare(
                "SELECT id, user_id, name, url, username, password, notes, created_at, updated_at
                 FROM password_vault
                 WHERE user_id = ? AND (name LIKE ? OR url LIKE ? OR username LIKE ? OR notes LIKE ?)
                 ORDER BY updated_at DESC"
            );
            $stmt->execute([$userId, $like, $like, $like, $like]);
        } else {
            $stmt = $pdo->prepare(
                "SELECT id, user_id, name, url, username, password, notes, created_at, updated_at
                 FROM password_vault
                 WHERE user_id = ?
                 ORDER BY updated_at DESC"
            );
            $stmt->execute([$userId]);
        }
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            if (!empty($row['password'])) {
                $row['password'] = self::decrypt($row['password']);
            }
        }
        return $rows;
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM password_vault WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row && !empty($row['password'])) {
            $row['password'] = self::decrypt($row['password']);
        }
        return $row ?: null;
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $pdo = Database::getConnection();
        $fields = [];
        $values = [];
        foreach (['name', 'url', 'username', 'notes'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (array_key_exists('password', $data) && $data['password'] !== '') {
            $fields[] = "password = ?";
            $values[] = self::encrypt($data['password']);
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $values[] = $userId;
        $stmt = $pdo->prepare(
            "UPDATE password_vault SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute($values);
    }

    public static function delete(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM password_vault WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}
