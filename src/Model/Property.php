<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class Property
{
    public static function create(int $userId, array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO properties (user_id, name, city, address, area, layout, current_price, unit_price, source_url, source_id, note) VALUES (:uid, :name, :city, :addr, :area, :layout, :price, :up, :url, :sid, :note)');
        $stmt->execute([
            ':uid' => $userId, ':name' => $data['name'], ':city' => $data['city'] ?? '',
            ':addr' => $data['address'] ?? '', ':area' => $data['area'] ?? 0,
            ':layout' => $data['layout'] ?? '', ':price' => $data['current_price'] ?? 0,
            ':up' => $data['unit_price'] ?? 0, ':url' => $data['source_url'] ?? '',
            ':sid' => $data['source_id'] ?? '', ':note' => $data['note'] ?? '',
        ]);
        $id = (int)$pdo->lastInsertId();
        if ($id > 0 && ($data['current_price'] ?? 0) > 0) {
            PropertyPrice::add($id, $userId, (float)$data['current_price'], (float)($data['unit_price'] ?? 0), 'manual');
        }
        return $id;
    }

    public static function update(int $id, int $userId, array $data): void
    {
        $pdo = Database::getConnection();
        $sets = []; $vals = [':id' => $id, ':uid' => $userId];
        foreach ($data as $k => $v) { $sets[] = "{$k} = :{$k}"; $vals[":{$k}"] = $v; }
        if (!empty($sets)) $pdo->prepare('UPDATE properties SET ' . implode(', ', $sets) . ' WHERE id = :id AND user_id = :uid')->execute($vals);
    }

    public static function delete(int $id, int $userId): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('DELETE FROM property_prices WHERE property_id = :pid AND user_id = :uid')->execute([':pid' => $id, ':uid' => $userId]);
        $pdo->prepare('DELETE FROM properties WHERE id = :id AND user_id = :uid')->execute([':id' => $id, ':uid' => $userId]);
    }

    public static function findById(int $id, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM properties WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function listByUser(int $userId, string $status = ''): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT * FROM properties WHERE user_id = :uid';
        $params = [':uid' => $userId];
        if ($status !== '' && $status !== 'all') { $sql .= ' AND status = :s'; $params[':s'] = $status; }
        $sql .= ' ORDER BY updated_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $rows[] = $row;
        return $rows;
    }

    public static function getAllWithSourceId(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM properties WHERE source_id IS NOT NULL AND source_id != "" AND status = "watching"');
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $rows[] = $row;
        return $rows;
    }
}
