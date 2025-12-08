<?php
// File: functions/markNotificationRead.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/auth.php';
require_once __DIR__ . '/../classes/notification.php';

session_start();
header('Content-Type: application/json');

$user = Auth::currentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid notification id']);
    exit;
}

$ok = Notification::markRead((int)$user['id'], $id);
echo json_encode(['success' => (bool)$ok]);
