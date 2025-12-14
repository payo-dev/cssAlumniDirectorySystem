<?php
session_start();
require_once __DIR__ . '/../classes/auth.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/mailer.php';
Auth::restrict('applications.approve');

$pdo = Database::getPDO();
$sid = $_GET['id'] ?? '';

if ($sid) {
    // Get User ID from Alumni ID
    $stmt = $pdo->prepare("SELECT u.id, u.email, u.display_name FROM users u JOIN alumni a ON u.id = a.user_id WHERE a.student_id = ?");
    $stmt->execute([$sid]);
    $user = $stmt->fetch();

    if ($user) {
        // Update Status
        $pdo->prepare("UPDATE users SET status = 'approved', approved_at = NOW() WHERE id = ?")->execute([$user['id']]);
        $pdo->prepare("UPDATE applications SET status = 'active' WHERE alumni_id = (SELECT id FROM alumni WHERE student_id = ?)")->execute([$sid]);
        
        // Notify
        $m = new Mailer();
        $m->sendNotification($user['email'], "Your account has been APPROVED. You may now login.");
    }
}
header("Location: ../pages/adminDashboard.php");