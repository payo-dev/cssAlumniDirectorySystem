<?php
// File: pages/renewalVerification.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';
require_once __DIR__ . '/../classes/mailer.php';

if (session_status() === PHP_SESSION_NONE) session_start();
Auth::requireLogin(); // Must be logged in

$user = Auth::getUser();
$pdo = Database::getPDO();
$error = "";
$success = "";

// 1. INITIALIZE OTP (Generate & Send)
if (isset($_GET['action']) && $_GET['action'] === 'init') {
    try {
        // Generate 6-digit Code
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes')); // Valid for 10 mins

        // Save to DB (Reuse email_verification table or create a temporary one)
        // We will repurpose the 'email_verification' table for this:
        // We delete old tokens for this user first to keep it clean
        $pdo->prepare("DELETE FROM email_verification WHERE user_id = ?")->execute([$user['id']]);
        
        $stmt = $pdo->prepare("INSERT INTO email_verification (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $code, $expiry]);

        // Send Email
        $mailer = new Mailer();
        if ($mailer->sendOTP($user['email'], $code)) {
            $success = "A 6-digit code has been sent to <strong>" . htmlspecialchars($user['email']) . "</strong>";
        } else {
            $error = "Failed to send email. Please check your network or contact admin.";
        }

    } catch (Exception $e) {
        $error = "System Error: " . $e->getMessage();
    }
}

// 2. VERIFY OTP (Form Submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_code = trim($_POST['otp_code'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM email_verification WHERE user_id = ? AND token = ? AND expires_at > NOW()");
    $stmt->execute([$user['id'], $input_code]);
    $valid = $stmt->fetch();

    if ($valid) {
        // SUCCESS: Delete token and redirect
        $pdo->prepare("DELETE FROM email_verification WHERE user_id = ?")->execute([$user['id']]);
        
        // Redirect to form with 'Renewal' type
        header("Location: alumniInfo.php?type=Renewal");
        exit;
    } else {
        $error = "Invalid or expired code. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Identity - WMSU Alumni</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex; justify-content: center; align-items: center; height: 100vh;
        }
        .verify-box {
            background: white; padding: 40px; border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 450px;
            text-align: center; border-top: 5px solid #b30000;
        }
        input[type="text"] {
            font-size: 1.5rem; letter-spacing: 8px; text-align: center;
            width: 200px; margin: 20px auto; padding: 10px;
        }
        .btn-verify {
            background: #b30000; color: white; padding: 12px 30px; border: none;
            border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%;
        }
        .btn-resend {
            background: none; border: none; color: #666; font-size: 0.9em;
            cursor: pointer; text-decoration: underline; margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="verify-box">
    <h2 style="color:#333; margin-bottom:10px;">Security Verification</h2>
    <p style="color:#666; font-size:0.95em;">
        To protect your account, please enter the code sent to your registered email.
    </p>

    <?php if ($success): ?>
        <div style="background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin:15px 0; font-size:0.9em;">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin:15px 0; font-size:0.9em;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="otp_code" maxlength="6" placeholder="000000" required autofocus>
        <button type="submit" class="btn-verify">Verify Code</button>
    </form>

    <form method="GET">
        <input type="hidden" name="action" value="init">
        <button type="submit" class="btn-resend">Resend Code</button>
    </form>

    <div style="margin-top: 20px;">
        <a href="selectAction.php" style="color: #999; text-decoration: none; font-size: 0.9em;">&larr; Cancel and Go Back</a>
    </div>
</div>

</body>
</html>