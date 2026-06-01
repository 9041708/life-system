<?php
namespace App\Model;

use App\Service\Database;
use PDO;

/**
 * 报销记录 Model
 */
class Reimbursement
{
    const STATUS_PENDING   = 'pending';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REIMBURSED = 'reimbursed';
    const STATUS_REJECTED  = 'rejected';

    public static function getPending(int $ledgerId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT r.*, c.name AS category_name, t.amount AS transaction_amount, t.trans_time AS transaction_date
            FROM reimbursement r
            LEFT JOIN categories c ON r.category_id = c.id
            LEFT JOIN `transactions` t ON r.transaction_id = t.id
            WHERE r.ledger_id = :lid AND r.status IN ("pending", "approved")
            ORDER BY r.created_at DESC
        ');
        $stmt->execute([':lid' => $ledgerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getPendingCount(int $ledgerId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reimbursement WHERE ledger_id = :lid AND status IN ("pending", "approved")');
        $stmt->execute([':lid' => $ledgerId]);
        return (int)$stmt->fetchColumn();
    }

    public static function getPendingTotal(int $ledgerId): float
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM reimbursement WHERE ledger_id = :lid AND status IN ("pending", "approved")');
        $stmt->execute([':lid' => $ledgerId]);
        return (float)$stmt->fetchColumn();
    }

    public static function getReimbursed(int $ledgerId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT r.*, c.name AS category_name, t.amount AS transaction_amount, t.trans_time AS transaction_date
            FROM reimbursement r
            LEFT JOIN categories c ON r.category_id = c.id
            LEFT JOIN `transactions` t ON r.transaction_id = t.id
            WHERE r.ledger_id = :lid AND r.status = "reimbursed"
            ORDER BY r.reimbursed_at DESC
        ');
        $stmt->execute([':lid' => $ledgerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(int $userId, int $ledgerId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            INSERT INTO reimbursement
            (user_id, ledger_id, title, amount, category_id, transaction_id,
             description, receipt_path, status, created_at, updated_at)
            VALUES
            (:uid, :lid, :title, :amount, :cat_id, :txn_id,
             :desc, :receipt, "pending", NOW(), NOW())
        ');
        $stmt->execute([
            ':uid'    => $userId,
            ':lid'    => $ledgerId,
            ':title'  => $data['title'] ?? ($data['description'] ?? '报销记录'),
            ':amount' => $data['amount'],
            ':cat_id' => $data['category_id'] ?? null,
            ':txn_id' => $data['transaction_id'] ?? null,
            ':desc'   => $data['description'] ?? '',
            ':receipt'=> $data['receipt_path'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateStatus(int $id, int $ledgerId, string $status, ?int $reimbursedBy = null): bool
    {
        $pdo = Database::getConnection();
        $params = [':id' => $id, ':lid' => $ledgerId, ':status' => $status];
        $extra = '';
        if ($status === self::STATUS_REIMBURSED) {
            $extra = ', reimbursed_at = NOW(), reimbursed_by = :by';
            $params[':by'] = $reimbursedBy;
        }
        $sql = "UPDATE reimbursement SET status = :status{$extra}, updated_at = NOW() WHERE id = :id AND ledger_id = :lid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id, int $ledgerId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM reimbursement WHERE id = :id AND ledger_id = :lid');
        $stmt->execute([':id' => $id, ':lid' => $ledgerId]);
        return $stmt->rowCount() > 0;
    }

    public static function getMonthlyStats(int $ledgerId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT DATE_FORMAT(reimbursed_at, "%Y-%m") AS month,
                   COUNT(*) AS count, SUM(amount) AS total_amount
            FROM reimbursement
            WHERE ledger_id = :lid AND status = "reimbursed"
            GROUP BY DATE_FORMAT(reimbursed_at, "%Y-%m")
            ORDER BY month DESC LIMIT 12
        ');
        $stmt->execute([':lid' => $ledgerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getCategoryStats(int $ledgerId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT COALESCE(c.name, "未分类") AS category_name,
                   COUNT(*) AS count, SUM(r.amount) AS total_amount
            FROM reimbursement r
            LEFT JOIN categories c ON r.category_id = c.id
            WHERE r.ledger_id = :lid AND r.status = "reimbursed"
            GROUP BY r.category_id, c.name
            ORDER BY total_amount DESC
        ');
        $stmt->execute([':lid' => $ledgerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getOverview(int $ledgerId): array
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total FROM reimbursement WHERE ledger_id = :lid AND status IN ("pending","approved")');
        $stmt->execute([':lid' => $ledgerId]);
        $pending = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total FROM reimbursement WHERE ledger_id = :lid AND status = "reimbursed"');
        $stmt->execute([':lid' => $ledgerId]);
        $reimbursed = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total FROM reimbursement WHERE ledger_id = :lid AND status = "reimbursed" AND DATE_FORMAT(reimbursed_at,"%Y-%m") = DATE_FORMAT(NOW(),"%Y-%m")');
        $stmt->execute([':lid' => $ledgerId]);
        $thisMonth = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'pending_count'     => (int)($pending['cnt'] ?? 0),
            'pending_amount'    => (float)($pending['total'] ?? 0),
            'reimbursed_count'  => (int)($reimbursed['cnt'] ?? 0),
            'reimbursed_amount' => (float)($reimbursed['total'] ?? 0),
            'this_month_count'  => (int)($thisMonth['cnt'] ?? 0),
            'this_month_amount' => (float)($thisMonth['total'] ?? 0),
        ];
    }

    /**
     * 根据 transaction_id 查找报销记录
     */
    public static function findByTransactionId(int $txnId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM reimbursement WHERE transaction_id = :tid LIMIT 1');
        $stmt->execute([':tid' => $txnId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * 获取所有报销记录的月份列表（用于筛选下拉）
     */
    public static function getMonths(int $ledgerId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, COUNT(*) AS count
            FROM reimbursement
            WHERE ledger_id = :lid
            GROUP BY DATE_FORMAT(created_at, "%Y-%m")
            ORDER BY month DESC
            LIMIT 24
        ');
        $stmt->execute([':lid' => $ledgerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 获取所有报销记录（合并页面用）
     */
    public static function getAll(int $ledgerId, string $statusFilter = ''): array
    {
        $pdo = Database::getConnection();
        $sql = '
            SELECT r.*, c.name AS category_name, t.amount AS transaction_amount, t.trans_time AS transaction_date
            FROM reimbursement r
            LEFT JOIN categories c ON r.category_id = c.id
            LEFT JOIN `transactions` t ON r.transaction_id = t.id
            WHERE r.ledger_id = :lid
        ';
        $params = [':lid' => $ledgerId];
        if ($statusFilter !== '') {
            $sql .= ' AND r.status = :status';
            $params[':status'] = $statusFilter;
        }
        $sql .= ' ORDER BY r.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
