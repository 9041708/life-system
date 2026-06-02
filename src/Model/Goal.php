<?php
namespace App\Model;

use App\Service\Database;
use App\Model\GoalTransactionLink;
use PDO;

class Goal
{
    public static function listByUserAndLedger(int $userId, int $ledgerId): array
    {
                GoalTransactionLink::ensureTable();
        $pdo = Database::getConnection();
                // 若目标绑定了账户，则可将记账记录“同步到目标”。目标进度按同步的记账计算：
                // - 入账（income）与转账转入（transfer, to_account_id=account_id）计为 +amount
                // - 支出（expense）与转账转出（transfer, from_account_id=account_id）计为 -amount
                // saved_amount 字段作为“初始值/手动调整”，最终展示为 初始值 + 同步记账净额。
                $sql = 'SELECT g.*,
                                (g.saved_amount + COALESCE((
                                        SELECT SUM(
                                                CASE
                                                        WHEN t.type IN (\'income\', \'transfer\') AND t.to_account_id = g.account_id THEN t.amount
                                                        WHEN t.type IN (\'expense\', \'transfer\') AND t.from_account_id = g.account_id THEN -t.amount
                                                        ELSE 0
                                                END
                                        )
                                        FROM goal_transaction_links l
                                        INNER JOIN transactions t ON t.id = l.transaction_id
                                        WHERE l.goal_id = g.id
                                            AND (
                                                        (g.ledger_id IS NOT NULL AND t.ledger_id = g.ledger_id)
                                                        OR (g.ledger_id IS NULL AND t.user_id = g.user_id AND t.ledger_id IS NULL)
                                                    )
                                ), 0)) AS saved_amount
                        FROM goals g
                        WHERE g.user_id = :uid
                            AND (g.ledger_id = :lid OR (:lid = 0 AND g.ledger_id IS NULL))
                            AND g.status != "archived"
                        ORDER BY g.status = "done", g.sort_order ASC, g.id DESC';
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $userId, ':lid' => $ledgerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            // 若无建表权限或表不存在，则退化为仅使用手动维护的 saved_amount。
            $stmt = $pdo->prepare('SELECT * FROM goals WHERE user_id = :uid AND (ledger_id = :lid OR (:lid = 0 AND ledger_id IS NULL)) AND status != "archived" ORDER BY status = "done", sort_order ASC, id DESC');
            $stmt->execute([':uid' => $userId, ':lid' => $ledgerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }

    public static function findOne(int $userId, int $id): ?array
    {
        GoalTransactionLink::ensureTable();
        $pdo = Database::getConnection();
        $sql = 'SELECT g.*,
                (g.saved_amount + COALESCE((
                    SELECT SUM(
                        CASE
                            WHEN t.type IN (\'income\', \'transfer\') AND t.to_account_id = g.account_id THEN t.amount
                            WHEN t.type IN (\'expense\', \'transfer\') AND t.from_account_id = g.account_id THEN -t.amount
                            ELSE 0
                        END
                    )
                    FROM goal_transaction_links l
                    INNER JOIN transactions t ON t.id = l.transaction_id
                    WHERE l.goal_id = g.id
                      AND (
                            (g.ledger_id IS NOT NULL AND t.ledger_id = g.ledger_id)
                            OR (g.ledger_id IS NULL AND t.user_id = g.user_id AND t.ledger_id IS NULL)
                          )
                ), 0)) AS saved_amount
            FROM goals g
            WHERE g.id = :id AND g.user_id = :uid
            LIMIT 1';
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id, ':uid' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? $row : null;
        } catch (\Throwable $e) {
            $stmt = $pdo->prepare('SELECT * FROM goals WHERE id = :id AND user_id = :uid LIMIT 1');
            $stmt->execute([':id' => $id, ':uid' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? $row : null;
        }
    }

    public static function listRecentByUserAndLedger(int $userId, int $ledgerId, int $limit = 3): array
    {
                GoalTransactionLink::ensureTable();
        $pdo = Database::getConnection();
                $sql = 'SELECT g.*,
                                (g.saved_amount + COALESCE((
                                        SELECT SUM(
                                                CASE
                                                        WHEN t.type IN (\'income\', \'transfer\') AND t.to_account_id = g.account_id THEN t.amount
                                                        WHEN t.type IN (\'expense\', \'transfer\') AND t.from_account_id = g.account_id THEN -t.amount
                                                        ELSE 0
                                                END
                                        )
                                        FROM goal_transaction_links l
                                        INNER JOIN transactions t ON t.id = l.transaction_id
                                        WHERE l.goal_id = g.id
                                            AND (
                                                        (g.ledger_id IS NOT NULL AND t.ledger_id = g.ledger_id)
                                                        OR (g.ledger_id IS NULL AND t.user_id = g.user_id AND t.ledger_id IS NULL)
                                                    )
                                ), 0)) AS saved_amount
                        FROM goals g
                        WHERE g.user_id = :uid
                            AND (g.ledger_id = :lid OR (:lid = 0 AND g.ledger_id IS NULL))
                            AND g.status != "archived"
                        ORDER BY g.id DESC
                        LIMIT :limit';
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':lid', $ledgerId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $sql2 = 'SELECT * FROM goals WHERE user_id = :uid AND (ledger_id = :lid OR (:lid = 0 AND ledger_id IS NULL)) AND status != "archived" ORDER BY id DESC LIMIT :limit';
            $stmt = $pdo->prepare($sql2);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':lid', $ledgerId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }

    public static function create(int $userId, int $ledgerId, int $accountId, string $title, float $targetAmount, float $savedAmount, ?string $deadline): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO goals (user_id, ledger_id, account_id, title, target_amount, saved_amount, deadline, status) VALUES (:uid,:lid,:account,:title,:target,:saved,:deadline,\'active\')');
        $stmt->execute([
            ':uid' => $userId,
            ':lid' => $ledgerId > 0 ? $ledgerId : null,
            ':account' => $accountId > 0 ? $accountId : null,
            ':title' => $title,
            ':target' => $targetAmount,
            ':saved' => $savedAmount,
            ':deadline' => $deadline !== null && $deadline !== '' ? $deadline : null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $userId, int $id, int $accountId, string $title, float $targetAmount, float $savedAmount, ?string $deadline, string $status): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE goals SET title = :title, target_amount = :target, saved_amount = :saved, deadline = :deadline, status = :status, account_id = :account WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':title' => $title,
            ':target' => $targetAmount,
            ':saved' => $savedAmount,
            ':deadline' => $deadline !== null && $deadline !== '' ? $deadline : null,
            ':status' => $status,
            ':account' => $accountId > 0 ? $accountId : null,
            ':id' => $id,
            ':uid' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $userId, int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM goals WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
    }
}
