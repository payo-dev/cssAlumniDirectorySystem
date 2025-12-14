<?php
session_start();
require_once __DIR__ . '/../classes/auth.php';
require_once __DIR__ . '/../classes/database.php';
Auth::restrict();

$pdo = Database::getPDO();
$sid = $_GET['id'] ?? '';

if ($sid) {
    $stmt = $pdo->prepare("SELECT u.id FROM users u JOIN alumni a ON u.id = a.user_id WHERE a.student_id = ?");
    $stmt->execute([$sid]);
    $uid = $stmt->fetchColumn();

    if ($uid) {
        $pdo->prepare("UPDATE users SET status = 'archived' WHERE id = ?")->execute([$uid]);
    }
}
header("Location: ../pages/adminDashboard.php");