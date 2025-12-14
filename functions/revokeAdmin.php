<?php
// File: functions/revokeAdmin.php
session_start();
require_once __DIR__ . '/../classes/auth.php';
require_once __DIR__ . '/../classes/database.php';
Auth::restrict();

$id = $_GET['id'] ?? null;
if ($id) {
    $pdo = Database::getPDO();
    // 1. Get User ID
    $stmt = $pdo->prepare("SELECT user_id FROM admins WHERE id = ?");
    $stmt->execute([$id]);
    $uid = $stmt->fetchColumn();

    if ($uid) {
        // 2. Downgrade User Role
        $pdo->prepare("UPDATE users SET role = 'alumni' WHERE id = ?")->execute([$uid]);
        // 3. Remove Admin Profile & Perms
        $pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$id]);
    }
}
header("Location: ../pages/manageAdmins.php");