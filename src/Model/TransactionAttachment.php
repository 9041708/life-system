<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class TransactionAttachment
{
    public static function listByTransactionIds(array $transactionIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $transactionIds), static fn($v) => $v > 0));
        if (!$ids) {
            return [];
        }
        try {
            $pdo = Database::getConnection();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT * FROM transaction_attachments WHERE transaction_id IN ($placeholders) ORDER BY transaction_id, sort_order, id");
            $stmt->execute($ids);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        $map = [];
        foreach ($rows as $r) {
            $tid = (int)$r['transaction_id'];
            if (!isset($map[$tid])) {
                $map[$tid] = [];
            }
            $map[$tid][] = $r;
        }
        return $map;
    }

    public static function replaceForTransaction(int $transactionId, array $relativePaths): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('DELETE FROM transaction_attachments WHERE transaction_id = :tid');
            $stmt->execute([':tid' => $transactionId]);

            $insert = $pdo->prepare('INSERT INTO transaction_attachments (transaction_id, relative_path, sort_order) VALUES (:tid, :path, :sort)');
            $sort = 0;
            foreach ($relativePaths as $p) {
                $p = trim((string)$p);
                if ($p === '') continue;
                $insert->execute([':tid' => $transactionId, ':path' => $p, ':sort' => $sort++]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
        }
    }
}
