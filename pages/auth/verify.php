<?php
// File: pages/auth/verify.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/auth.php';

$token = $_GET['token'] ?? '';
$ok = false;
$message = '';

if ($token) {
    if (Auth::verifyToken($token)) {
        $ok = true;
        $message = 'Your email has been verified. You can now sign in.';
    } else {
        $message = 'Verification failed or token expired. Please request a new verification email.';
    }
} else {
    $message = 'No verification token provided.';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Email Verification — WMSU Alumni</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
  <style>
    .verifyBox { max-width:720px; margin:60px auto; background:#fff; padding:26px; border-radius:8px; border-left:6px solid #b30000; box-shadow:0 6px 20px rgba(0,0,0,0.06); font-family:Arial, sans-serif; }
    .ok { background:#eefaf0; color:#157347; padding:12px; border-radius:6px; border:1px solid #d4f5dd; }
    .fail { background:#fff0f0; color:#b30000; padding:12px; border-radius:6px; border:1px solid #f5c2c2; }
    .cta { margin-top:14px; }
    .btn { padding:10px 14px; border-radius:6px; text-decoration:none; color:#fff; background:#b30000; font-weight:700; }
  </style>
</head>
<body class="default-program-bg">
  <div class="mainContainer">
    <div class="verifyBox">
      <h2 style="color:#b30000;">WMSU Alumni — Email Verification</h2>
      <?php if ($ok): ?>
        <div class="ok"><?= htmlspecialchars($message) ?></div>
        <div class="cta"><a href="<?= BASE_URL ?>/pages/auth/login.php" class="btn">Sign in</a></div>
      <?php else: ?>
        <div class="fail"><?= htmlspecialchars($message) ?></div>
        <div style="margin-top:12px;">
          <a href="<?= BASE_URL ?>/pages/auth/login.php">← Back to login</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
