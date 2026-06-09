<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class MindfulnessTreasure
{
    public static function create(int $userId, string $content, string $aiReply = '', string $sentiment = 'neutral', float $scoreChange = 0): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO mindfulness_treasures (user_id, content, ai_reply, sentiment, score_change) VALUES (:uid, :content, :reply, :sentiment, :score)');
        $stmt->execute([':uid' => $userId, ':content' => $content, ':reply' => $aiReply, ':sentiment' => $sentiment, ':score' => $scoreChange]);
        return (int)$pdo->lastInsertId();
    }

    public static function listByUser(int $userId, int $limit = 10, int $offset = 0): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM mindfulness_treasures WHERE user_id = :uid ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function countByUser(int $userId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM mindfulness_treasures WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function findById(int $id, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM mindfulness_treasures WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function delete(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM mindfulness_treasures WHERE id = :id AND user_id = :uid');
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    public static function getRecentNegativeCount(int $userId, int $limit = 5): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM (SELECT sentiment FROM mindfulness_treasures WHERE user_id = :uid ORDER BY created_at DESC LIMIT :limit) t WHERE sentiment = \'negative\'');
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
