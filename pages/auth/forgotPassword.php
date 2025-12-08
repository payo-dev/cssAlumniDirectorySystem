<?php
// File: pages/auth/forgotPassword.php
session_start();
require_once __DIR__ . '/../../classes/database.php';
require_once __DIR__ . '/../../classes/mailHelper.php';
$pdo = Database::getPDO();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '') $msg = 'Provide your email.';
    else {
        $stmt = $pdo->prepare("SELECT id, display_name FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        $u = $stmt->fetch();
        if (!$u) $msg = 'No user found.';
        else {
            $token = bin2hex(random_bytes(20));
            $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            $pdo->prepare("UPDATE users SET password_reset_token = :tok, password_reset_expiry = :exp WHERE id = :id")
                ->execute([':tok' => $token, ':exp' => $expiry, ':id' => $u['id']]);
            $resetUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}/cssAlumniDirectorySystem/pages/auth/resetPassword.php?token={$token}";
            $body = "Hello {$u['display_name']},<br><br>To reset your password click: <a href=\"$resetUrl\">Reset password</a><br>This link expires in 1 hour.";
            $mh = new MailHelper();
            $mh->sendMail($email, $u['display_name'], 'Password reset', $body);
            $msg = 'Password reset link sent to your email.';
        }
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Forgot Password</title></head><body>
<div style="max-width:520px;margin:40px auto">
  <h3>Forgot Password</h3>
  <?php if ($msg): ?><div style="color:#198754"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <form method="POST"><label>Email</label><input type="email" name="email" required><div style="margin-top:8px"><button>Send Reset Link</button></div></form>
</div>
</body></html>
