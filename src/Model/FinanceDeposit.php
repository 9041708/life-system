<?php
namespace App\Model;

use App\Service\Database;

class FinanceDeposit
{
    private static function ensureTable(): void
    {
        $pdo = Database::getConnection();
        try {
            $pdo->query('SELECT 1 FROM finance_deposit LIMIT 1');
        } catch (\Throwable $e) {
            $pdo->exec('CREATE TABLE IF NOT EXISTS finance_deposit (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                deposit_date DATE NOT NULL COMMENT \'存款日期\',
                amount DECIMAL(12,2) NOT NULL COMMENT \'存款金额\',
                method VARCHAR(20) DEFAULT \'存单\' COMMENT \'存款方式(存单/存折/硬卡/其它)\',
                maturity_date DATE NULL COMMENT \'到期时间\',
                annual_rate DECIMAL(6,4) NULL COMMENT \'年化利率(如0.025表示2.5%)\',
                estimated_interest DECIMAL(12,2) NULL COMMENT \'预估利息\',
                auto_renew TINYINT(1) DEFAULT 0 COMMENT \'自动续期\',
                notes TEXT NULL COMMENT \'备注\',
                status VARCHAR(10) DEFAULT \'active\' COMMENT \'状态(active存续/withdrawn已取出)\',
                withdraw_date DATE NULL COMMENT \'取出日期\',
                withdraw_principal DECIMAL(12,2) NULL COMMENT \'取出本金\',
                withdraw_interest DECIMAL(12,2) NULL COMMENT \'获得利息\',
                withdraw_notes TEXT NULL COMMENT \'取出备注\',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }
    }

    public static function create(int $userId, array $data): int
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO finance_deposit (user_id, deposit_date, amount, method, maturity_date, annual_rate, estimated_interest, auto_renew, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $userId,
            $data['deposit_date'] ?? date('Y-m-d'),
            $data['amount'] ?? 0,
            $data['method'] ?? '存单',
            $data['maturity_date'] ?? null,
            $data['annual_rate'] ?? null,
            $data['estimated_interest'] ?? null,
            (int)($data['auto_renew'] ?? 0),
            $data['notes'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByUser(int $userId, ?string $status = null): array
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $sql = 'SELECT * FROM finance_deposit WHERE user_id = ?';
        $params = [$userId];
        if ($status) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY deposit_date DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function withdraw(int $id, int $userId, array $data): bool
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE finance_deposit SET status = \'withdrawn\',
            withdraw_date = ?, withdraw_principal = ?, withdraw_interest = ?, withdraw_notes = ?
            WHERE id = ? AND user_id = ? AND status = \'active\'');
        $stmt->execute([
            $data['withdraw_date'] ?? date('Y-m-d'),
            $data['withdraw_principal'] ?? null,
            $data['withdraw_interest'] ?? null,
            $data['withdraw_notes'] ?? null,
            $id,
            $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id, int $userId): bool
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM finance_deposit WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function summary(int $userId): array
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT
            COALESCE(SUM(CASE WHEN status = \'active\' THEN amount ELSE 0 END), 0) AS total_principal,
            COALESCE(SUM(CASE WHEN status = \'active\' THEN estimated_interest ELSE 0 END), 0) AS total_interest,
            COALESCE(SUM(CASE WHEN status = \'withdrawn\' THEN withdraw_interest ELSE 0 END), 0) AS earned_interest
            FROM finance_deposit WHERE user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['total_principal' => 0, 'total_interest' => 0, 'earned_interest' => 0];
    }

    public static function findById(int $id, int $userId): ?array
    {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM finance_deposit WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
