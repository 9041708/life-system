<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class KbSpace
{
    public static function getOrCreate(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kb_spaces WHERE user_id = :uid ORDER BY id LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
        $pdo->prepare('INSERT INTO kb_spaces (user_id) VALUES (:uid)')->execute([':uid' => $userId]);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kb_spaces WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function update(int $id, int $userId, array $data): void
    {
        $pdo = Database::getConnection();
        $sets = [];
        $vals = [':id' => $id, ':uid' => $userId];
        foreach ($data as $k => $v) {
            $sets[] = "{$k} = :{$k}";
            $vals[":{$k}"] = $v;
        }
        if (empty($sets)) return;
        $pdo->prepare('UPDATE kb_spaces SET ' . implode(', ', $sets) . ' WHERE id = :id AND user_id = :uid')->execute($vals);
    }
}
