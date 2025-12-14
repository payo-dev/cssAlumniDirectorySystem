<?php
// File: force_verify.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/database.php';

$pdo = Database::getPDO();
$email = 'gulaneithandeniel@gmail.com';

// Update verification and status
$stmt = $pdo->prepare("UPDATE users SET is_verified = 1, status = 'approved' WHERE email = ?");
$stmt->execute([$email]);

echo "<h1>Success!</h1>";
echo "<p>Account for <strong>$email</strong> is now VERIFIED and APPROVED.</p>";
echo "<a href='index.php'>Go to Login</a>";
?>