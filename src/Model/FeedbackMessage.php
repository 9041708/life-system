<?php
namespace App\Model;

use App\Service\Database;
use PDO;

class FeedbackMessage
{
    public static function listByFeedbackIds(array $feedbackIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $feedbackIds), static fn($v) => $v > 0));
        if (!$ids) {
            return [];
        }
        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM feedback_messages WHERE feedback_id IN ($placeholders) ORDER BY feedback_id, id");
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $map = [];
        foreach ($rows as $r) {
            $fid = (int)$r['feedback_id'];
            if (!isset($map[$fid])) {
                $map[$fid] = [];
            }
            $r['images_array'] = [];
            if (!empty($r['images'])) {
                $decoded = json_decode((string)$r['images'], true);
                if (is_array($decoded)) {
                    $r['images_array'] = array_values(array_filter($decoded, static fn($v) => is_string($v) && $v !== ''));
                }
            }
            $map[$fid][] = $r;
        }
        return $map;
    }

    public static function create(int $feedbackId, string $senderType, ?int $senderUserId, string $content, array $imagePaths = []): int
    {
        $senderType = $senderType === 'admin' ? 'admin' : 'user';
        $content = trim($content);
        if ($content === '') {
            return 0;
        }
        $imagesJson = $imagePaths ? json_encode(array_values($imagePaths), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO feedback_messages (feedback_id, sender_type, sender_user_id, content, images) VALUES (:fid,:st,:suid,:c,:img)');
        $stmt->execute([
            ':fid' => $feedbackId,
            ':st' => $senderType,
            ':suid' => $senderUserId,
            ':c' => $content,
            ':img' => $imagesJson,
        ]);
        return (int)$pdo->lastInsertId();
    }
}
