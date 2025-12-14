<?php
// File: pages/auth/forgotPassword.php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/database.php';
require_once __DIR__ . '/../../classes/mailer.php'; 

$pdo = Database::getPDO();
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if ($email === '') {
        $error = 'Please provide your email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id, display_name FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        $u = $stmt->fetch();

        if (!$u) {
            $error = 'No account found with that email address.';
        } else {
            try {
                // 1. Generate Token
                $token = bin2hex(random_bytes(32));
                
                // 2. Save Token using MySQL Time (DATE_ADD)
                // We use NOW() + INTERVAL 1 HOUR so it matches the DB clock exactly
                $sql = "UPDATE users 
                        SET password_reset_token = :tok, 
                            password_reset_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) 
                        WHERE id = :id";
                
                $pdo->prepare($sql)->execute([':tok' => $token, ':id' => $u['id']]);

                // 3. Send Email
                $mailer = new Mailer();
                if ($mailer->sendPasswordResetEmail($email, $token)) {
                    $msg = 'A password reset link has been sent to your email.';
                } else {
                    $error = 'Failed to send email. Check error logs.';
                }

            } catch (Exception $e) {
                $error = "System Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - WMSU Alumni</title>
    <link rel="stylesheet" href="../../assets/css/index.css">
    <style>
        body {
            background-image: 
                linear-gradient(to top, rgba(139, 0, 0, 0.9) 0%, rgba(139, 0, 0, 0.1) 100%),
                url('../../assets/images/default-bg.jpg');
            background-size: cover; background-position: center; height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .card {
            background: white; padding: 40px; width: 100%; max-width: 450px;
            border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border-top: 5px solid #b30000; text-align: center;
        }
        .btn-submit {
            width: 100%; padding: 14px; background: #b30000; color: white; border: none;
            border-radius: 6px; font-weight: bold; cursor: pointer;
        }
        .btn-submit:hover { background: #8a0000; }
        .back-link { display: block; margin-top: 20px; color: #666; text-decoration: none; }
        input[type="email"] { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="card">
    <img src="../../assets/images/logo1.png" alt="WMSU" style="width:80px; margin-bottom:15px;">
    <h2 style="margin:0 0 10px; color:#333;">Forgot Password?</h2>
    
    <?php if ($msg): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border-radius:6px; margin-bottom:20px;">
            ✅ <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:6px; margin-bottom:20px;">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!$msg): ?>
        <form method="POST">
            <input type="email" name="email" required placeholder="name@email.com">
            <button type="submit" class="btn-submit">Send Reset Link</button>
        </form>
    <?php endif; ?>

    <a href="../../index.php" class="back-link">&larr; Back to Login</a>
</div>

</body>
</html>