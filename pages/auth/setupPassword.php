<?php
// File: pages/auth/setupPassword.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$token = $_GET['token'] ?? '';
$error = "";
$msg = "";
$validToken = false;
$pdo = Database::getPDO();

// ==========================================================
// 1. GET COLLEGE CONTEXT (Styles matching Register)
// ==========================================================
$college_id = $_SESSION['selected_college'] ?? null;
$bg_image = 'default-bg.jpg';
$bg_gradient = 'linear-gradient(to top, rgba(139, 0, 0, 0.9) 0%, rgba(139, 0, 0, 0.1) 100%)'; 
$theme_color = '#b30000'; // Default Red

if ($college_id) {
    try {
        $stmt = $pdo->prepare("SELECT code FROM colleges WHERE id = ?");
        $stmt->execute([$college_id]);
        $col = $stmt->fetch();
        if ($col) {
            $code_upper = strtoupper($col['code']);
            if ($code_upper === 'CCS') {
                $bg_image = 'ccs-bg.jpg';
                $bg_gradient = 'linear-gradient(to top, rgba(0, 80, 0, 0.9) 0%, rgba(0, 80, 0, 0.1) 100%)';
                $theme_color = '#198754';
            } elseif ($code_upper === 'CN') {
                $bg_image = 'cn-bg.jpg';
                $bg_gradient = 'linear-gradient(to top, rgba(233, 30, 99, 0.9) 0%, rgba(233, 30, 99, 0.1) 100%)';
                $theme_color = '#e91e63';
            }
        }
    } catch (Exception $e) {}
}

// ==========================================================
// 2. VERIFY TOKEN
// ==========================================================
if ($token) {
    // Check for user with this token who is NOT verified yet
    $stmt = $pdo->prepare("SELECT id, email, role, display_name FROM users WHERE verification_token = :tok AND is_verified = 0");
    $stmt->execute([':tok' => $token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $validToken = true;
    } else {
        $error = "This link is invalid or has expired.";
    }
} else {
    $error = "No verification token provided.";
}

// ==========================================================
// 3. HANDLE PASSWORD SET & AUTO-LOGIN
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $pass = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (strlen($pass) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($pass !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        // A. Update DB: Verify Email, Set Password, Keep Status Pending
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash, is_verified = 1, verification_token = NULL, status = 'pending' WHERE id = :id");
        $stmt->execute([':hash' => $hash, ':id' => $user['id']]);
        
        // B. AUTO-LOGIN (Set Session)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['logged_in'] = true;

        // C. Redirect to Alumni Form
        header("Location: ../alumniInfo.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set Password - WMSU Alumni</title>
    <link rel="stylesheet" href="../../assets/css/index.css">
    <style>
        .split-container { display: flex; height: 100vh; }
        .left-pane { flex: 1; background-size: cover; background-position: center; }
        .right-pane { flex: 1; display: flex; align-items: center; justify-content: center; background: white; }
        .login-box { width: 100%; max-width: 400px; padding: 40px; text-align: center; }
        
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        .form-group input { width: 100%; padding: 12px; padding-right: 45px; border: 1px solid #ccc; border-radius: 6px; }
        
        .btn-continue { 
            width: 100%; padding: 12px; 
            background: <?= $theme_color ?>; 
            color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px; 
        }
        .btn-continue:hover { opacity: 0.9; }
        
        .logo { width: 80px; margin-bottom: 15px; }

        /* Password Eye */
        .password-wrapper { position: relative; width: 100%; }
        .toggle-password { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #666; display: flex; }
    </style>
</head>
<body>

    <div class="split-container">
        <div class="left-pane" style="background-image: <?= $bg_gradient ?>, url('../../assets/images/<?= $bg_image ?>');"></div>

        <div class="right-pane">
            <div class="login-box">
                <img src="../../assets/images/logo1.png" alt="WMSU Logo" class="logo">
                
                <h1>Set Password</h1>
                <div class="title-underline" style="width: 50px; height: 3px; background: <?= $theme_color ?>; margin: 10px auto;"></div>

                <?php if ($error): ?>
                    <div style="background:#ffe3e3; color:#b30000; padding:15px; border-radius:6px; margin-bottom:20px; text-align:left;">
                        ⚠️ <?= $error ?>
                    </div>
                <?php endif; ?>

                <?php if ($validToken): ?>
                    <form method="POST">
                        <p style="color:#666; margin-bottom:20px;">Create a secure password to activate your account.</p>

                        <div class="form-group">
                            <label>New Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="password" id="new_pass" required placeholder="Min. 8 characters">
                                <button type="button" class="toggle-password" onclick="togglePassword('new_pass', this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Confirm Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="confirm_password" id="confirm_pass" required placeholder="Re-type password">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-continue">Confirm and Proceed</button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId, btn) {
            const input = document.getElementById(fieldId);
            if (input.type === "password") {
                input.type = "text";
                btn.style.color = "<?= $theme_color ?>"; 
            } else {
                input.type = "password";
                btn.style.color = "#666";
            }
        }
    </script>
</body>
</html>