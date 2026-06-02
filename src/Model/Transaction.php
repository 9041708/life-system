<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class Transaction
{
    public static function countByLedger(int $ledgerId, array $filters): int
    {
        $pdo = Database::getConnection();
        $where = ['ledger_id = :lid'];
        $params = [':lid' => $ledgerId];

        if (!empty($filters['type'])) {
            $where[] = 'type = :type';
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'category_id = :cid';
            $params[':cid'] = (int)$filters['category_id'];
        }
        if (!empty($filters['item_id'])) {
            $where[] = 'item_id = :iid';
            $params[':iid'] = (int)$filters['item_id'];
        }
        if (!empty($filters['account_id'])) {
            $where[] = '(from_account_id = :acc OR to_account_id = :acc)';
            $params[':acc'] = (int)$filters['account_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'trans_time >= :dfrom';
            $params[':dfrom'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'trans_time <= :dto';
            $params[':dto'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['amount_min'])) {
            $where[] = 'amount >= :amin';
            $params[':amin'] = (float)$filters['amount_min'];
        }
        if (!empty($filters['amount_max'])) {
            $where[] = 'amount <= :amax';
            $params[':amax'] = (float)$filters['amount_max'];
        }
        if (!empty($filters['remark'])) {
            $where[] = 'remark LIKE :remark';
            $params[':remark'] = '%' . $filters['remark'] . '%';
        }

        $sql = 'SELECT COUNT(*) FROM transactions WHERE ' . implode(' AND ', $where);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    public static function count(int $userId, array $filters): int
    {
        $pdo = Database::getConnection();
        $where = ['user_id = :uid'];
        $params = [':uid' => $userId];

        if (!empty($filters['type'])) {
            $where[] = 'type = :type';
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'category_id = :cid';
            $params[':cid'] = (int)$filters['category_id'];
        }
        if (!empty($filters['item_id'])) {
            $where[] = 'item_id = :iid';
            $params[':iid'] = (int)$filters['item_id'];
        }
        if (!empty($filters['account_id'])) {
            $where[] = '(from_account_id = :acc OR to_account_id = :acc)';
            $params[':acc'] = (int)$filters['account_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'trans_time >= :dfrom';
            $params[':dfrom'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'trans_time <= :dto';
            $params[':dto'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['amount_min'])) {
            $where[] = 'amount >= :amin';
            $params[':amin'] = (float)$filters['amount_min'];
        }
        if (!empty($filters['amount_max'])) {
            $where[] = 'amount <= :amax';
            $params[':amax'] = (float)$filters['amount_max'];
        }
        if (!empty($filters['remark'])) {
            $where[] = 'remark LIKE :remark';
            $params[':remark'] = '%' . $filters['remark'] . '%';
        }

        $sql = 'SELECT COUNT(*) FROM transactions WHERE ' . implode(' AND ', $where);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    public static function findByIdInLedger(int $id, int $ledgerId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = :id AND ledger_id = :lid LIMIT 1');
        $stmt->execute([':id' => $id, ':lid' => $ledgerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findById(int $id, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function searchByLedger(int $ledgerId, array $filters, ?int $limit = null, ?int $offset = null): array
    {
        $pdo = Database::getConnection();
        $where = ['t.ledger_id = :lid'];
        $params = [':lid' => $ledgerId];

        if (!empty($filters['type'])) {
            $where[] = 't.type = :type';
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = 't.category_id = :cid';
            $params[':cid'] = (int)$filters['category_id'];
        }
        if (!empty($filters['item_id'])) {
            $where[] = 't.item_id = :iid';
            $params[':iid'] = (int)$filters['item_id'];
        }
        if (!empty($filters['account_id'])) {
            $where[] = '(t.from_account_id = :acc OR t.to_account_id = :acc)';
            $params[':acc'] = (int)$filters['account_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 't.trans_time >= :dfrom';
            $params[':dfrom'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 't.trans_time <= :dto';
            $params[':dto'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['amount_min'])) {
            $where[] = 't.amount >= :amin';
            $params[':amin'] = (float)$filters['amount_min'];
        }
        if (!empty($filters['amount_max'])) {
            $where[] = 't.amount <= :amax';
            $params[':amax'] = (float)$filters['amount_max'];
        }
        if (!empty($filters['remark'])) {
            $where[] = 't.remark LIKE :remark';
            $params[':remark'] = '%' . $filters['remark'] . '%';
        }

        $sql = 'SELECT t.*, c.name AS category_name, c.icon_type AS category_icon_type, c.icon_value AS category_icon_value,
            i.name AS item_name, i.icon_type AS item_icon_type, i.icon_value AS item_icon_value,
            fa.name AS from_account_name, fa.icon_type AS from_account_icon_type, fa.icon_value AS from_account_icon_value, fa.current_balance AS from_account_balance,
            ta.name AS to_account_name, ta.icon_type AS to_account_icon_type, ta.icon_value AS to_account_icon_value, ta.current_balance AS to_account_balance
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN items i ON t.item_id = i.id
                LEFT JOIN accounts fa ON t.from_account_id = fa.id
                LEFT JOIN accounts ta ON t.to_account_id = ta.id
                WHERE ' . implode(' AND ', $where) . ' ORDER BY trans_time DESC, id DESC';

        if ($limit !== null) {
            $lim = max(1, (int)$limit);
            $off = max(0, (int)($offset ?? 0));
            $sql .= ' LIMIT ' . $lim . ' OFFSET ' . $off;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 附件：优先 transaction_attachments；兼容旧 attachment_path
        try {
            $ids = array_values(array_filter(array_map(static function ($r) {
                return (int)($r['id'] ?? 0);
            }, $rows)));
            if ($ids) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $aStmt = $pdo->prepare("SELECT transaction_id, relative_path FROM transaction_attachments WHERE transaction_id IN ($placeholders) ORDER BY transaction_id, sort_order, id");
                $aStmt->execute($ids);
                $att = $aStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $map = [];
                foreach ($att as $a) {
                    $tid = (int)$a['transaction_id'];
                    if (!isset($map[$tid])) {
                        $map[$tid] = [];
                    }
                    $p = (string)($a['relative_path'] ?? '');
                    if ($p !== '') {
                        $map[$tid][] = $p;
                    }
                }
                foreach ($rows as &$r) {
                    $tid = (int)($r['id'] ?? 0);
                    $r['attachments'] = $map[$tid] ?? [];
                    if (empty($r['attachments']) && !empty($r['attachment_path'])) {
                        $r['attachments'] = [(string)$r['attachment_path']];
                    }
                }
                unset($r);
            }
        } catch (\Throwable $e) {
            foreach ($rows as &$r) {
                $r['attachments'] = !empty($r['attachment_path']) ? [(string)$r['attachment_path']] : [];
            }
            unset($r);
        }

        return $rows;
    }

    public static function search(int $userId, array $filters, ?int $limit = null, ?int $offset = null): array
    {
        $pdo = Database::getConnection();
		$where = ['t.user_id = :uid'];
        $params = [':uid' => $userId];

        if (!empty($filters['type'])) {
			$where[] = 't.type = :type';
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['category_id'])) {
			$where[] = 't.category_id = :cid';
            $params[':cid'] = (int)$filters['category_id'];
        }
        if (!empty($filters['item_id'])) {
			$where[] = 't.item_id = :iid';
            $params[':iid'] = (int)$filters['item_id'];
        }
        if (!empty($filters['account_id'])) {
			$where[] = '(t.from_account_id = :acc OR t.to_account_id = :acc)';
            $params[':acc'] = (int)$filters['account_id'];
        }
        if (!empty($filters['date_from'])) {
			$where[] = 't.trans_time >= :dfrom';
            $params[':dfrom'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
			$where[] = 't.trans_time <= :dto';
            $params[':dto'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['amount_min'])) {
			$where[] = 't.amount >= :amin';
            $params[':amin'] = (float)$filters['amount_min'];
        }
        if (!empty($filters['amount_max'])) {
			$where[] = 't.amount <= :amax';
            $params[':amax'] = (float)$filters['amount_max'];
        }
        if (!empty($filters['remark'])) {
			$where[] = 't.remark LIKE :remark';
            $params[':remark'] = '%' . $filters['remark'] . '%';
        }

        $sql = 'SELECT t.*, c.name AS category_name, c.icon_type AS category_icon_type, c.icon_value AS category_icon_value,
            i.name AS item_name, i.icon_type AS item_icon_type, i.icon_value AS item_icon_value,
            fa.name AS from_account_name, fa.icon_type AS from_account_icon_type, fa.icon_value AS from_account_icon_value, fa.current_balance AS from_account_balance,
            ta.name AS to_account_name, ta.icon_type AS to_account_icon_type, ta.icon_value AS to_account_icon_value, ta.current_balance AS to_account_balance
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN items i ON t.item_id = i.id
                LEFT JOIN accounts fa ON t.from_account_id = fa.id
                LEFT JOIN accounts ta ON t.to_account_id = ta.id
                WHERE ' . implode(' AND ', $where) . ' ORDER BY trans_time DESC, id DESC';

        if ($limit !== null) {
            $lim = max(1, (int)$limit);
            $off = max(0, (int)($offset ?? 0));
            $sql .= ' LIMIT ' . $lim . ' OFFSET ' . $off;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 附件：兼容旧 attachment_path；若已升级到多附件表则合并输出
        try {
            $ids = array_values(array_filter(array_map(static function ($r) {
                return (int)($r['id'] ?? 0);
            }, $rows)));
            if ($ids) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $aStmt = $pdo->prepare("SELECT transaction_id, relative_path FROM transaction_attachments WHERE transaction_id IN ($placeholders) ORDER BY transaction_id, sort_order, id");
                $aStmt->execute($ids);
                $att = $aStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $map = [];
                foreach ($att as $a) {
                    $tid = (int)$a['transaction_id'];
                    if (!isset($map[$tid])) {
                        $map[$tid] = [];
                    }
                    $p = (string)($a['relative_path'] ?? '');
                    if ($p !== '') {
                        $map[$tid][] = $p;
                    }
                }
                foreach ($rows as &$r) {
                    $tid = (int)($r['id'] ?? 0);
                    $r['attachments'] = $map[$tid] ?? [];
                    if (empty($r['attachments']) && !empty($r['attachment_path'])) {
                        $r['attachments'] = [(string)$r['attachment_path']];
                    }
                }
                unset($r);
            }
        } catch (\Throwable $e) {
            foreach ($rows as &$r) {
                $r['attachments'] = !empty($r['attachment_path']) ? [(string)$r['attachment_path']] : [];
            }
            unset($r);
        }

        return $rows;
    }

    public static function summarize(int $userId, array $filters): array
    {
        $pdo = Database::getConnection();
        $baseWhere = ['user_id = :uid'];
        $params = [':uid' => $userId];

        if (!empty($filters['category_id'])) {
            $baseWhere[] = 'category_id = :cid';
            $params[':cid'] = (int)$filters['category_id'];
        }
        if (!empty($filters['item_id'])) {
            $baseWhere[] = 'item_id = :iid';
            $params[':iid'] = (int)$filters['item_id'];
        }
        if (!empty($filters['account_id'])) {
            $baseWhere[] = '(from_account_id = :acc OR to_account_id = :acc)';
            $params[':acc'] = (int)$filters['account_id'];
        }
        if (!empty($filters['date_from'])) {
            $baseWhere[] = 'trans_time >= :dfrom';
            $params[':dfrom'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $baseWhere[] = 'trans_time <= :dto';
            $params[':dto'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['amount_min'])) {
            $baseWhere[] = 'amount >= :amin';
            $params[':amin'] = (float)$filters['amount_min'];
        }
        if (!empty($filters['amount_max'])) {
            $baseWhere[] = 'amount <= :amax';
            $params[':amax'] = (float)$filters['amount_max'];
        }
        if (!empty($filters['remark'])) {
            $baseWhere[] = 'remark LIKE :remark';
            $params[':remark'] = '%' . $filters['remark'] . '%';
        }

        $whereSql = implode(' AND ', $baseWhere);
        $sql = 'SELECT type, COALESCE(SUM(amount),0) AS total FROM transactions
            WHERE ' . $whereSql . ' GROUP BY type';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $result = [
            'income' => 0.0,
            'expense' => 0.0,
            'transfer' => 0.0,
        ];
        foreach ($rows as $r) {
            $key = $r['type'];
            if (isset($result[$key])) {
                $result[$key] = (float)$r['total'];
            }
        }
        return $result;
    }

    public static function summarizeByLedger(int $ledgerId, array $filters): array
    {
        $pdo = Database::getConnection();
        $baseWhere = ['ledger_id = :lid'];
        $params = [':lid' => $ledgerId];

        if (!empty($filters['category_id'])) {
            $baseWhere[] = 'category_id = :cid';
            $params[':cid'] = (int)$filters['category_id'];
        }
        if (!empty($filters['item_id'])) {
            $baseWhere[] = 'item_id = :iid';
            $params[':iid'] = (int)$filters['item_id'];
        }
        if (!empty($filters['account_id'])) {
            $baseWhere[] = '(from_account_id = :acc OR to_account_id = :acc)';
            $params[':acc'] = (int)$filters['account_id'];
        }
        if (!empty($filters['date_from'])) {
            $baseWhere[] = 'trans_time >= :dfrom';
            $params[':dfrom'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $baseWhere[] = 'trans_time <= :dto';
            $params[':dto'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['amount_min'])) {
            $baseWhere[] = 'amount >= :amin';
            $params[':amin'] = (float)$filters['amount_min'];
        }
        if (!empty($filters['amount_max'])) {
            $baseWhere[] = 'amount <= :amax';
            $params[':amax'] = (float)$filters['amount_max'];
        }
        if (!empty($filters['remark'])) {
            $baseWhere[] = 'remark LIKE :remark';
            $params[':remark'] = '%' . $filters['remark'] . '%';
        }

        $whereSql = implode(' AND ', $baseWhere);
        $sql = 'SELECT type, COALESCE(SUM(amount),0) AS total FROM transactions
            WHERE ' . $whereSql . ' GROUP BY type';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $result = [
            'income' => 0.0,
            'expense' => 0.0,
            'transfer' => 0.0,
        ];
        foreach ($rows as $r) {
            $key = $r['type'];
            if (isset($result[$key])) {
                $result[$key] = (float)$r['total'];
            }
        }
        return $result;
    }
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $source = self::normalizeSource((string)($data['source'] ?? 'manual'));
        // 兼容：新库包含 ledger_id；旧库没有该列
        try {
            $stmt = $pdo->prepare('INSERT INTO transactions (user_id, ledger_id, type, category_id, item_id, from_account_id, to_account_id, amount, trans_time, remark, attachment_path, source) VALUES (:uid,:lid,:type,:cid,:iid,:from_acc,:to_acc,:amount,:time,:remark,:attach,:source)');
            $stmt->execute([
                ':uid' => $data['user_id'],
                ':lid' => $data['ledger_id'] ?? null,
                ':type' => $data['type'],
                ':cid' => $data['category_id'],
                ':iid' => $data['item_id'] ?? null,
                ':from_acc' => $data['from_account_id'] ?? null,
                ':to_acc' => $data['to_account_id'] ?? null,
                ':amount' => $data['amount'],
                ':time' => $data['trans_time'],
                ':remark' => $data['remark'] ?? null,
                ':attach' => $data['attachment_path'] ?? null,
                ':source' => $source,
            ]);
        } catch (\Throwable $e) {
            $stmt = $pdo->prepare('INSERT INTO transactions (user_id, type, category_id, item_id, from_account_id, to_account_id, amount, trans_time, remark, attachment_path, source) VALUES (:uid,:type,:cid,:iid,:from_acc,:to_acc,:amount,:time,:remark,:attach,:source)');
            $stmt->execute([
                ':uid' => $data['user_id'],
                ':type' => $data['type'],
                ':cid' => $data['category_id'],
                ':iid' => $data['item_id'] ?? null,
                ':from_acc' => $data['from_account_id'] ?? null,
                ':to_acc' => $data['to_account_id'] ?? null,
                ':amount' => $data['amount'],
                ':time' => $data['trans_time'],
                ':remark' => $data['remark'] ?? null,
                ':attach' => $data['attachment_path'] ?? null,
                ':source' => $source,
            ]);
        }
        return (int)$pdo->lastInsertId();
    }

    private static function normalizeSource(string $source): string
    {
        $source = strtolower(trim($source));
        if ($source === 'qclaw') {
            return 'ai';
        }
        return in_array($source, ['manual', 'ai'], true) ? $source : 'manual';
    }

    public static function update(int $id, int $userId, array $data): void
    {
        $pdo = Database::getConnection();
        $source = array_key_exists('source', $data)
            ? self::normalizeSource((string)$data['source'])
            : null;
        $stmt = $pdo->prepare('UPDATE transactions SET type=:type, category_id=:cid, item_id=:iid, from_account_id=:from_acc, to_account_id=:to_acc, amount=:amount, trans_time=:time, remark=:remark, attachment_path=:attach, source=COALESCE(:source, source) WHERE id=:id AND user_id=:uid');
        $stmt->execute([
            ':type' => $data['type'],
            ':cid' => $data['category_id'],
            ':iid' => $data['item_id'] ?? null,
            ':from_acc' => $data['from_account_id'] ?? null,
            ':to_acc' => $data['to_account_id'] ?? null,
            ':amount' => $data['amount'],
            ':time' => $data['trans_time'],
            ':remark' => $data['remark'] ?? null,
            ':attach' => $data['attachment_path'] ?? null,
            ':source' => $source,
            ':id' => $id,
            ':uid' => $userId,
        ]);
    }

    public static function updateInLedger(int $id, int $ledgerId, array $data): void
    {
        $pdo = Database::getConnection();
        $source = array_key_exists('source', $data)
            ? self::normalizeSource((string)$data['source'])
            : null;
        $stmt = $pdo->prepare('UPDATE transactions SET type=:type, category_id=:cid, item_id=:iid, from_account_id=:from_acc, to_account_id=:to_acc, amount=:amount, trans_time=:time, remark=:remark, attachment_path=:attach, source=COALESCE(:source, source) WHERE id=:id AND ledger_id=:lid');
        $stmt->execute([
            ':type' => $data['type'],
            ':cid' => $data['category_id'],
            ':iid' => $data['item_id'] ?? null,
            ':from_acc' => $data['from_account_id'] ?? null,
            ':to_acc' => $data['to_account_id'] ?? null,
            ':amount' => $data['amount'],
            ':time' => $data['trans_time'],
            ':remark' => $data['remark'] ?? null,
            ':attach' => $data['attachment_path'] ?? null,
            ':source' => $source,
            ':id' => $id,
            ':lid' => $ledgerId,
        ]);
    }

    public static function deleteMany(int $userId, array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;
        $params[] = $userId;
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id IN ($placeholders) AND user_id = ?");
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function deleteManyInLedger(int $ledgerId, array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn($v) => $v > 0));
        if (empty($ids)) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;
        $params[] = $ledgerId;
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id IN ($placeholders) AND ledger_id = ?");
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}
