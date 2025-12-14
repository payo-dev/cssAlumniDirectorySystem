<?php
// File: index.php (Global Login - FIXED)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/auth.php';
require_once __DIR__ . '/classes/database.php';

// HELPER: CENTRALIZED REDIRECT LOGIC
function redirectUser() {
    $user = Auth::getUser(); // Get full user details including role
    
    // 1. ADMINS & DUAL ROLES -> Go to Admin Dashboard
    if ($user['role'] === 'admin' || $user['role'] === 'both') {
        header("Location: pages/adminDashboard.php");
    } 
    // 2. ALUMNI -> Go to Central Alumni Landing Page
    else {
        // Directs to the new gatekeeper page
        header("Location: pages/alumniLanding.php"); 
    }
    exit;
}

// IF ALREADY LOGGED IN:
if (Auth::isLoggedIn()) {
    redirectUser();
}

$message = "";

// HANDLE LOGIN FORM
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? "");
    $password = trim($_POST['password'] ?? "");

    if (Auth::login($email, $password)) {
        redirectUser(); // Use the same logic after fresh login
    } else {
        $message = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - WMSU Alumni</title>
    <link rel="stylesheet" href="assets/css/index.css"> 
    <style>
        /* Default Background Setup */
        .left-pane {
            background-image: 
                linear-gradient(to top, rgba(139, 0, 0, 0.9) 0%, rgba(139, 0, 0, 0.1) 100%),
                url('assets/images/default-bg.jpg');
            background-size: cover;
            background-position: center;
        }

        /* Right Pane Texture */
        .right-pane {
            background-color: #f8f9fa; 
            background-image: radial-gradient(#e9ecef 1px, transparent 1px);
            background-size: 20px 20px; 
        }

        /* Full Red Border */
        .login-box { border: 3px solid #b30000; }
    </style>
</head>
<body>

    <div class="split-container">
        <div class="left-pane" id="bg-pane"></div>

        <div class="right-pane">
            <div class="login-box">
                <img src="assets/images/logo1.png" alt="WMSU Logo" class="logo">
                
                <h1>Alumni Portal</h1>
                <div class="title-underline"></div>
                
                <p class="subtitle">Welcome! Please sign in to your account.</p>

                <?php if ($message): ?>
                    <div style="background:#ffe3e3; color:#b30000; padding:12px; border-radius:5px; margin-bottom:20px; font-size:0.9em; border-left: 4px solid #b30000; text-align: left;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="name@email.com">
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" required placeholder="Enter your password" style="padding-right: 45px;">
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#6c757d;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-continue">Sign In</button>
                </form>

                <div class="footer-link">
                    <div style="margin-bottom:10px;">
                        <a href="pages/auth/forgotPassword.php">Forgot Password?</a>
                    </div>
                    <div>
                        New Alumni? <a href="pages/auth/college.php" style="font-weight:800;">Create Account</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pass = document.getElementById('password');
            pass.type = pass.type === 'password' ? 'text' : 'password';
        }
    </script>

</body>
</html>