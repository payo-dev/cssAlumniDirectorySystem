<?php
// File: pages/auth/resetPassword.php
session_start();
require_once __DIR__ . '/../../classes/database.php';
$pdo = Database::getPDO();

$token = $_GET['token'] ?? '';
$msg = '';

if (!$token) { echo "Invalid token"; exit; }

// Verify token and expiry
$stmt = $pdo->prepare("SELECT id, password_reset_expiry FROM users WHERE password_reset_token = :tok LIMIT 1");
$stmt->execute([':tok' => $token]);
$u = $stmt->fetch();

if (!$u) { echo "Invalid or expired token."; exit; }
if (strtotime($u['password_reset_expiry']) < time()) { echo "Token expired."; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    $pw2 = $_POST['password_confirm'] ?? '';
    if ($pw === '' || $pw2 === '') $msg = 'Enter password twice.';
    elseif ($pw !== $pw2) $msg = 'Passwords do not match.';
    else {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = :h, password_reset_token = NULL, password_reset_expiry = NULL, updated_at = NOW() WHERE id = :id")
            ->execute([':h' => $hash, ':id' => $u['id']]);
        echo "Password updated. <a href='/cssAlumniDirectorySystem/pages/auth/login.php'>Login</a>";
        exit;
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Reset Password</title></head><body>
<div style="max-width:520px;margin:40px auto">
  <h3>Reset Password</h3>
  <?php if ($msg): ?><div style="color:#b30000"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <form method="POST">
    <label>New Password</label><input type="password" name="password" required>
    <label>Confirm Password</label><input type="password" name="password_confirm" required>
    <div style="margin-top:8px"><button>Update Password</button></div>
  </form>
</div>
</body></html>
