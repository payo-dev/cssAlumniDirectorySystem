<?php
// File: pages/selectAction.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/auth.php';
require_once __DIR__ . '/../classes/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Ensure user is logged in
Auth::requireLogin();

$user = Auth::getUser();
$pdo = Database::getPDO();

// 1. GET COLLEGE CONTEXT FOR STYLING
$college_id = $_SESSION['selected_college'] ?? 0;
$college_code = "";

// Check DB for linked college first
$stmt = $pdo->prepare("
    SELECT c.code 
    FROM academic_records ar 
    JOIN colleges c ON ar.college_id = c.id 
    JOIN alumni a ON ar.alumni_id = a.id 
    WHERE a.user_id = ? 
    LIMIT 1
");
$stmt->execute([$user['id']]);
$db_code = $stmt->fetchColumn();

if ($db_code) {
    $college_code = $db_code;
} elseif ($college_id) {
    // Fallback to session if not linked yet
    $stmt = $pdo->prepare("SELECT code FROM colleges WHERE id = ?");
    $stmt->execute([$college_id]);
    $college_code = $stmt->fetchColumn();
}

// 2. DYNAMIC STYLES CONFIG
$bg_image = 'default-bg.jpg';
$border_color = '#b30000'; // Default Red
$bg_gradient = 'linear-gradient(to top, rgba(139, 0, 0, 0.9) 0%, rgba(139, 0, 0, 0.1) 100%)';

$code_upper = strtoupper($college_code);
if ($code_upper === 'CCS') {
    $bg_image = 'ccs-bg.jpg';
    $border_color = '#006400'; // Dark Green
    $bg_gradient = 'linear-gradient(to top, rgba(0, 80, 0, 0.9) 0%, rgba(0, 80, 0, 0.1) 100%)';
} elseif ($code_upper === 'CN') {
    $bg_image = 'cn-bg.jpg';
    $border_color = '#e91e63'; // Pink/Magenta
    $bg_gradient = 'linear-gradient(to top, rgba(233, 30, 99, 0.9) 0%, rgba(233, 30, 99, 0.1) 100%)';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Action - WMSU Alumni</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        body { 
            background-image: <?= $bg_gradient ?>, url('../assets/images/<?= $bg_image ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            overflow-y: auto; 
        }
        body::before {
            content: ''; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.2); z-index: -1;
        }
        .right-pane { background: none !important; background-image: none !important; }
        
        .login-box {
            max-width: 550px;
            border-top: 5px solid <?= $border_color ?>; 
            border-bottom: 5px solid <?= $border_color ?>;
            border-left: none; border-right: none;
        }

        .action-container {
            display: flex; gap: 20px; justify-content: center; width: 100%; margin-top: 20px;
        }
        .action-btn {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            width: 100%; padding: 25px; border: 2px solid #eee; border-radius: 10px;
            text-decoration: none; color: #333; transition: all 0.3s; background: #fff;
        }
        .action-btn:hover {
            border-color: <?= $border_color ?>; transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .action-icon { font-size: 2.5rem; margin-bottom: 15px; color: <?= $border_color ?>; }
        .action-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 5px; }
        .action-desc { font-size: 0.9rem; color: #666; text-align: center; }
        
        .footer-link a:hover { color: <?= $border_color ?> !important; }
    </style>
</head>
<body>

    <div class="split-container">
        <div class="left-pane" style="opacity: 0;"></div> 

        <div class="right-pane">
            <div class="login-box">
                <img src="../assets/images/logo1.png" alt="WMSU Logo" class="logo">
                
                <h1>Welcome, <?= htmlspecialchars(explode(' ', $user['display_name'])[0]) ?>!</h1>
                <div class="title-underline" style="background-color: <?= $border_color ?>;"></div>
                <p class="subtitle">What would you like to do today?</p>

                <div class="action-container">
                    <a href="alumniInfo.php?type=new" class="action-btn">
                        <div class="action-icon">📝</div>
                        <div class="action-title">New Application</div>
                        <div class="action-desc">First-time registration for the Alumni Directory.</div>
                    </a>

                    <a href="renewalVerification.php?action=init" class="action-btn">
                        <div class="action-icon">🔄</div>
                        <div class="action-title">Renewal</div>
                        <div class="action-desc">Verify your identity and update your records.</div>
                    </a>
                </div>

                <div class="footer-link">
                    <?php if (Auth::isAdmin()): ?>
                        <a href="adminDashboard.php" style="margin-right: 15px;">&larr; Back to Dashboard</a>
                    <?php endif; ?>
                    <a href="auth/logout.php" style="color:#dc3545;">Logout</a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>