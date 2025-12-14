<?php
// File: pages/alumniLanding.php (The New Central Alumni Landing Page - NEW)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';

Auth::requireLogin();
$user = Auth::getUser();
$pdo = Database::getPDO();

// Ensure this is an Alumni role hitting this page (Admin role redirects in index.php)
if (Auth::isAdmin()) {
    header("Location: adminDashboard.php"); 
    exit;
}

// 1. Check User Status from the 'users' table
// This implements the core approval logic: if approved, show record; otherwise, show application form.
$stmt = $pdo->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user['id']]);
$status = $stmt->fetchColumn();

// 2. Redirection Logic
if ($status === 'approved') {
    // User is fully approved -> Show their permanent record
    header("Location: viewCurrentRecord.php");
    exit;
} else {
    // Status is 'pending' or 'declined' -> Must complete or re-do the initial application
    // alumniInfo.php is Step 1 of the multi-step form.
    header("Location: alumniInfo.php");
    exit;
}
?>