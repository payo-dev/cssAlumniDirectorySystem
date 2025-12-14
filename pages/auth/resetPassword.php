<?php
// File: pages/auth/resetPassword.php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/database.php';

$pdo = Database::getPDO();
$token = $_GET['token'] ?? '';
$msg = '';
$error = '';
$validToken = false;

// 1. Verify Token (Using MySQL NOW() to avoid timezone mismatch)
if ($token) {
    // Check if token matches AND expiry time is in the future
    $stmt = $pdo->prepare("SELECT id FROM users WHERE password_reset_token = :tok AND password_reset_expiry > NOW()");
    $stmt->execute([':tok' => $token]);
    $user = $stmt->fetch();

    if ($user) {
        $validToken = true;
    } else {
        $error = "This password reset link is invalid or has expired.";
    }
} else {
    $error = "No token provided.";
}

// 2. Handle Password Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    // ✅ FIX: Added trim() here to remove accidental spaces
    $pass = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if (strlen($pass) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($pass !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Update Password & Clear Token
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash, password_reset_token = NULL, password_reset_expiry = NULL WHERE id = :id");
        $stmt->execute([':hash' => $hash, ':id' => $user['id']]);
        
        $msg = "Password updated successfully! You can now log in.";
        $validToken = false; // Hide form
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - WMSU Alumni</title>
    <link rel="stylesheet" href="../../assets/css/index.css">
    <style>
        body {
            background-image: linear-gradient(to top, rgba(139, 0, 0, 0.9) 0%, rgba(139, 0, 0, 0.1) 100%), url('../../assets/images/default-bg.jpg');
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
            border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px;
        }
        .btn-submit:hover { background: #8a0000; }

        /* PASSWORD WRAPPER & TOGGLE ICON */
        .password-wrapper {
            position: relative;
            width: 100%;
            margin-bottom: 15px;
        }
        .password-wrapper input {
            width: 100%;
            padding: 12px;
            padding-right: 45px; /* Space for the eye icon */
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        /* Icon Button Style */
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            padding: 0;
            display: flex;
            align-items: center;
        }
        .toggle-password:focus { outline: none; }
    </style>
</head>
<body>

<div class="card">
    <h2 style="margin-top:0; color:#333;">Reset Password</h2>
    
    <?php if ($msg): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border-radius:6px; margin-bottom:20px;">
            ✅ <?= htmlspecialchars($msg) ?>
        </div>
        <a href="../../index.php" class="btn-submit" style="display:block; text-decoration:none;">Go to Login</a>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:6px; margin-bottom:20px;">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
        <a href="forgotPassword.php" style="color:#666;">Request a new link</a>
    <?php endif; ?>

    <?php if ($validToken && !$msg): ?>
        <form method="POST">
            
            <div style="text-align:left; font-size:0.9em; font-weight:bold; margin-bottom:5px; color:#555;">New Password</div>
            <div class="password-wrapper">
                <input type="password" name="password" id="new_pass" required placeholder="Enter new password (min. 8 chars)">
                <button type="button" class="toggle-password" onclick="togglePassword('new_pass', this)">
                    <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <svg class="eye-closed" style="display:none;" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                </button>
            </div>
            
            <div style="text-align:left; font-size:0.9em; font-weight:bold; margin-bottom:5px; color:#555;">Confirm Password</div>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" id="confirm_pass" required placeholder="Re-enter your new password">
            </div>
            
            <button type="submit" class="btn-submit">Update Password</button>
        </form>
    <?php endif; ?>
</div>

<script>
    function togglePassword(fieldId, btn) {
        const input = document.getElementById(fieldId);
        const eyeOpen = btn.querySelector('.eye-open');
        const eyeClosed = btn.querySelector('.eye-closed');

        if (input.type === "password") {
            input.type = "text";
            eyeOpen.style.display = 'none';
            eyeClosed.style.display = 'block';
        } else {
            input.type = "password";
            eyeOpen.style.display = 'block';
            eyeClosed.style.display = 'none';
        }
    }
</script>

</body>
</html>