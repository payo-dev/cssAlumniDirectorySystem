<?php
// File: pages/auth/resendVerification.php
session_start();
require_once __DIR__ . '/../../classes/database.php';
require_once __DIR__ . '/../../classes/mailHelper.php';

$pdo = Database::getPDO();
$message = '';

if (isset($_GET['sent'])) {
    $message = 'A verification email was sent. Check your inbox.';
}

// Resend form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '') $message = 'Provide your email.';
    else {
        $stmt = $pdo->prepare("SELECT id, display_name, is_verified, verification_token FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        $u = $stmt->fetch();
        if (!$u) $message = 'No account found with that email.';
        elseif (!empty($u['is_verified'])) $message = 'Account already verified. Please login.';
        else {
            $token = $u['verification_token'] ?? bin2hex(random_bytes(20));
            $pdo->prepare("UPDATE users SET verification_token = :tok WHERE id = :id")->execute([':tok' => $token, ':id' => $u['id']]);
            $verifyUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}/cssAlumniDirectorySystem/pages/auth/verify.php?token={$token}";
            $body = "Hello {$u['display_name']},<br><br>Please verify: <a href=\"$verifyUrl\">Verify</a>";
            $mh = new MailHelper();
            $mh->sendMail($email, $u['display_name'], 'Verify your email', $body);
            $message = 'Verification email resent. Check your inbox.';
        }
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Resend Verification</title></head>
<body style="font-family:Arial, sans-serif">
  <div style="max-width:520px;margin:40px auto;">
    <h3>Resend Verification Email</h3>
    <?php if ($message): ?><div style="color:#198754"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <form method="POST">
      <label>Email</label><input type="email" name="email" required>
      <div style="margin-top:8px"><button type="submit">Resend</button></div>
    </form>
    <p><a href="/cssAlumniDirectorySystem/index.php">← Home</a></p>
  </div>
</body>
</html>
