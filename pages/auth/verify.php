<?php
// File: pages/auth/verify.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/auth.php';

$token = $_GET['token'] ?? '';
$ok = false;
$message = '';
$registered = $_GET['registered'] ?? 0;
$email = $_GET['email'] ?? '';

if ($registered) {
    // Just showing the "Please check your email" message
    $message = "Registration successful! We have sent a verification link to <strong>" . htmlspecialchars($email) . "</strong>.<br>Please check your inbox (and spam folder).";
    $heading = "Check Your Email";
    $icon = "✉️";
    $color = "#004085"; // Blue
    $bg = "#cce5ff";
} elseif ($token) {
    // Actually trying to verify
    if (Auth::verifyToken($token)) {
        $ok = true;
        $heading = "Email Verified!";
        $message = "Thank you! Your account has been successfully verified. You may now sign in.";
        $icon = "✅";
        $color = "#155724"; // Green
        $bg = "#d4edda";
    } else {
        $heading = "Verification Failed";
        $message = "This link is invalid or has already been used.";
        $icon = "❌";
        $color = "#721c24"; // Red
        $bg = "#f8d7da";
    }
} else {
    $heading = "Error";
    $message = "No token provided.";
    $icon = "⚠️";
    $color = "#856404"; // Yellow
    $bg = "#fff3cd";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification - WMSU Alumni</title>
    <link rel="stylesheet" href="../../assets/css/index.css">
    <style>
        body {
            background-image: 
                linear-gradient(to top, rgba(139, 0, 0, 0.9) 0%, rgba(139, 0, 0, 0.1) 100%),
                url('../../assets/images/default-bg.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .verify-box {
            background: white;
            padding: 40px;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border-top: 5px solid #b30000;
        }
        .status-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            display: block;
        }
        .message-box {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            background-color: <?= $bg ?>;
            color: <?= $color ?>;
            border: 1px solid <?= $color ?>;
        }
        .btn-home {
            display: inline-block;
            background-color: #b30000;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .btn-home:hover {
            background-color: #8a0000;
        }
    </style>
</head>
<body>

    <div class="verify-box">
        <span class="status-icon"><?= $icon ?></span>
        <h1 style="color: #333; margin-bottom: 10px;"><?= $heading ?></h1>
        
        <div class="message-box">
            <?= $message ?>
        </div>

        <?php if ($ok || !$registered): ?>
            <a href="../../index.php" class="btn-home">Go to Login</a>
        <?php else: ?>
             <a href="../../index.php" style="color:#666; text-decoration:none;">&larr; Return to Home</a>
        <?php endif; ?>
    </div>

</body>
</html>