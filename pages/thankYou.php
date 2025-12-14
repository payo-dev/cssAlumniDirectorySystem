<?php
// File: pages/thankYou.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

Auth::requireLogin();

$user = Auth::getUser();
$pdo = Database::getPDO();

// 1. GET COLLEGE CONTEXT
$college_id = $_SESSION['selected_college'] ?? 0;
$college_name = "Western Mindanao State University"; 
$college_code = "";

// Check DB linkage for styling
$stmt = $pdo->prepare("
    SELECT ar.college_id, c.name as college_name, c.code as college_code
    FROM academic_records ar 
    JOIN colleges c ON ar.college_id = c.id 
    JOIN alumni a ON ar.alumni_id = a.id 
    WHERE a.user_id = ? 
    LIMIT 1
");
$stmt->execute([$user['id']]);
$linked_data = $stmt->fetch();

if ($linked_data) {
    $college_id = $linked_data['college_id'];
    $college_name = $linked_data['college_name'];
    $college_code = $linked_data['college_code'];
} elseif ($college_id) {
    $stmt = $pdo->prepare("SELECT name, code FROM colleges WHERE id = ?");
    $stmt->execute([$college_id]);
    $col = $stmt->fetch();
    if ($col) {
        $college_name = $col['name'];
        $college_code = $col['code'];
    }
}

// 2. DETERMINE MESSAGE TYPE
$type = $_GET['type'] ?? 'new';
$id = $_GET['id'] ?? ($_GET['student_id'] ?? null);

$typeText = strtolower($type) === 'renewal' ? 'Renewal Request' : 'New Application';
$nextAction = strtolower($type) === 'renewal'
    ? 'Our office will review your updated details soon.'
    : 'Your alumni record has been successfully submitted for verification.';

// --- DYNAMIC STYLES ---
$bg_image = 'default-bg.jpg';
$border_color = '#b30000'; // Default Red
$bg_gradient = 'linear-gradient(to top, rgba(139, 0, 0, 0.9) 0%, rgba(139, 0, 0, 0.1) 100%)';
$theme_color = '#b30000';
$light_theme_color = '#f8d7da'; // Light red background for tags

$code_upper = strtoupper($college_code);
if ($code_upper === 'CCS') {
    $bg_image = 'ccs-bg.jpg';
    $border_color = '#006400';
    $bg_gradient = 'linear-gradient(to top, rgba(0, 80, 0, 0.9) 0%, rgba(0, 80, 0, 0.1) 100%)';
    $theme_color = '#198754';
    $light_theme_color = '#d1e7dd';
} elseif ($code_upper === 'CN') {
    $bg_image = 'cn-bg.jpg';
    $border_color = '#e91e63';
    $bg_gradient = 'linear-gradient(to top, rgba(233, 30, 99, 0.9) 0%, rgba(233, 30, 99, 0.1) 100%)';
    $theme_color = '#e91e63';
    $light_theme_color = '#fce4ec';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Thank You - Alumni Submission</title>
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
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.2);
            z-index: -1;
        }
        
        .thankyou-container {
            background: #ffffff;
            max-width: 520px;
            width: 92%;
            margin: 60px auto;
            padding: 40px 35px;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            text-align: center;
            animation: fadeIn 0.7s ease-in-out;
            position: relative;
            z-index: 10;
            /* DYNAMIC TOP BORDER */
            border-top: 6px solid <?= $border_color ?>;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(25px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .thankyou-icon {
            font-size: 3.8em;
            color: <?= $theme_color ?>;
            margin-bottom: 15px;
            animation: pulse 1.4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        h1 {
            color: <?= $theme_color ?>;
            font-size: 1.9em;
            margin-bottom: 10px;
        }

        p {
            font-size: 1rem;
            margin: 8px 0;
            color: #444;
        }

        strong {
            color: <?= $theme_color ?>;
        }

        .thankyou-type {
            background: <?= $light_theme_color ?>;
            color: <?= $theme_color ?>;
            display: inline-block;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.9em;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .thankyou-btn {
            display: inline-block;
            background: <?= $theme_color ?>;
            color: white;
            text-decoration: none;
            padding: 12px 26px;
            border-radius: 8px;
            font-size: 1rem;
            margin-top: 25px;
            transition: 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0, 0.15);
        }

        .thankyou-btn:hover {
            opacity: 0.9;
            transform: scale(1.02);
        }

        .thankyou-note {
            margin-top: 25px;
            font-size: 0.9rem;
            color: #555;
            line-height: 1.5;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="thankyou-container">
        <div class="thankyou-icon">✅</div>
        <span class="thankyou-type"><?= strtoupper($typeText) ?></span>
        <h1>Submission Received!</h1>

        <?php if (strtolower($type) === 'renewal'): ?>
            <p>Your <strong>renewal request</strong> has been successfully submitted.</p>
        <?php else: ?>
            <p>Your <strong>new alumni registration</strong> has been recorded.</p>
        <?php endif; ?>

        <?php if ($id): ?>
            <p><strong>Reference ID:</strong> <?= htmlspecialchars($id) ?></p>
        <?php endif; ?>

        <p><?= htmlspecialchars($nextAction) ?></p>
        <p>You will receive updates via your registered email: <strong><?= htmlspecialchars($user['email']) ?></strong></p>

        <a href="auth/logout.php" class="thankyou-btn">🏠 Return to Home</a>

        <div class="thankyou-note">
            <?php if (strtolower($type) === 'renewal'): ?>
                <p>Our Alumni Office will verify your updates.  
                You’ll be notified once your record is approved.</p>
            <?php else: ?>
                <p>Need to modify your record later?  
                You can use the Renewal option on our homepage anytime.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>