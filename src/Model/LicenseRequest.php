<?php
namespace App\Model;

use App\Service\Database;

class LicenseRequest
{
    private static function ensureTableExists(): void
    {
        $pdo = Database::getConnection();
        try {
            $pdo->query('SELECT 1 FROM license_requests LIMIT 1');
        } catch (\Throwable $e) {
            $sql = 'CREATE TABLE IF NOT EXISTS license_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NULL,
                domain VARCHAR(255) NULL,
                request_type VARCHAR(50) NULL,
                period VARCHAR(50) NULL,
                status VARCHAR(50) NULL,
                note TEXT NULL,
                pay_proof_path VARCHAR(255) NULL,
                created_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
            $pdo->exec($sql);
        }
    }

    public static function create(string $email, string $domain, string $type, ?string $period, ?string $payProofPath = null, ?string $note = null): void
    {
        self::ensureTableExists();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO license_requests (email, domain, request_type, period, status, note, pay_proof_path, created_at) VALUES (:email, :domain, :type, :period, :status, :note, :pay_proof_path, NOW())');
        $status = 'pending';
        $stmt->execute([
            ':email' => $email,
            ':domain' => $domain,
            ':type' => $type,
            ':period' => $period,
            ':status' => $status,
            ':note' => $note,
            ':pay_proof_path' => $payProofPath,
        ]);
    }

    public static function listLatest(int $limit = 50): array
    {
        self::ensureTableExists();
        $pdo = Database::getConnection();
        $limit = max(1, min($limit, 200));
        $stmt = $pdo->query('SELECT * FROM license_requests ORDER BY id DESC LIMIT ' . $limit);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function listAll(): array
    {
        self::ensureTableExists();
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM license_requests ORDER BY id DESC');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function updateNote(int $id, string $note): void
    {
        if ($id <= 0) {
            return;
        }
        self::ensureTableExists();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE license_requests SET note = :note WHERE id = :id');
        $stmt->execute([
            ':note' => $note,
            ':id' => $id,
        ]);
    }

    public static function deleteById(int $id): void
    {
        if ($id <= 0) {
            return;
        }
        self::ensureTableExists();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM license_requests WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
