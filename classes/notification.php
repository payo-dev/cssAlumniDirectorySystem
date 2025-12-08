<?php
// File: classes/notification.php
require_once __DIR__ . '/database.php';

class Notification
{
    public static function fetchForUser(int $userId, int $limit = 10): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("SELECT id, type, message, is_read, created_at, meta FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT :l");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function unreadCount(int $userId): int
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function markRead(int $userId, int $notificationId): bool
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid");
        return $stmt->execute([':id' => $notificationId, ':uid' => $userId]);
    }
}
