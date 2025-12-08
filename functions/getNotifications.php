<?php
// File: functions/getNotifications.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/auth.php';
require_once __DIR__ . '/../classes/notification.php';

header('Content-Type: application/json');
session_start();

$user = Auth::currentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$list = Notification::fetchForUser((int)$user['id'], 10);
$count = Notification::unreadCount((int)$user['id']);

echo json_encode(['unread' => $count, 'items' => $list]);
