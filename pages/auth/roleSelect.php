<?php
// File: pages/auth/roleSelect.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$user = Auth::currentUser();
if (!$user) {
    header('Location: ' . BASE_URL . '/pages/auth/login.php');
    exit;
}
if ($user['role'] !== 'both') {
    // Not applicable
    if ($user['role'] === 'admin') header('Location: ' . BASE_URL . '/pages/adminDashboard.php');
    else header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $choice = $_POST['as'] ?? 'alumni';
    if ($choice === 'admin') {
        header('Location: ' . BASE_URL . '/pages/adminDashboard.php');
        exit;
    } else {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Choose role</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>
<body>
  <div style="max-width:480px;margin:60px auto;padding:26px;background:#fff;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.06);text-align:center;">
    <h2>Continue as</h2>
    <p>Welcome, <?= htmlspecialchars($user['display_name'] ?? $user['email']) ?> — choose how you want to proceed.</p>
    <form method="POST">
      <button type="submit" name="as" value="alumni" style="margin:8px;padding:12px 18px;">Alumni</button>
      <button type="submit" name="as" value="admin" style="margin:8px;padding:12px 18px;">Admin</button>
    </form>
  </div>
</body>
</html>
