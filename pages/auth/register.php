<?php
// File: pages/auth/register.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/database.php';
require_once __DIR__ . '/../../classes/auth.php';
require_once __DIR__ . '/../../classes/mailer.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$error = "";
$msg = "";
$step = 1;
$student_id_input = "";
$alumni_data = null;

// ==========================================================
// 1. GET COLLEGE CONTEXT & BACKGROUND RULES
// ==========================================================
if (isset($_GET['college_id'])) {
    $_SESSION['selected_college'] = $_GET['college_id'];
}
$college_id = $_SESSION['selected_college'] ?? null;
$college_name = ""; 

// DEFAULT THEME (WMSU Red)
$bg_image = 'default-bg.jpg';
$bg_gradient = 'linear-gradient(to top, rgba(139, 0, 0, 0.9) 0%, rgba(139, 0, 0, 0.1) 100%)'; 
$theme_color = '#b30000'; // Button color

if ($college_id) {
    try {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("SELECT name, code FROM colleges WHERE id = ?");
        $stmt->execute([$college_id]);
        $col = $stmt->fetch();

        if ($col) {
            $college_name = $col['name'];
            $code_upper = strtoupper($col['code']);

            // --- COLLEGE BACKGROUND RULES ---
            if ($code_upper === 'CCS') {
                // College of Computing Studies (Green)
                $bg_image = 'ccs-bg.jpg';
                $bg_gradient = 'linear-gradient(to top, rgba(0, 80, 0, 0.9) 0%, rgba(0, 80, 0, 0.1) 100%)';
                $theme_color = '#198754';
            } elseif ($code_upper === 'CN') {
                // College of Nursing (Pink/Red)
                $bg_image = 'cn-bg.jpg';
                $bg_gradient = 'linear-gradient(to top, rgba(233, 30, 99, 0.9) 0%, rgba(233, 30, 99, 0.1) 100%)';
                $theme_color = '#e91e63';
            }
        }
    } catch (Exception $e) { /* Ignore */ }
}

// ==========================================================
// 2. HANDLE FORM SUBMISSIONS
// ==========================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pdo = Database::getPDO();
    
    // --- STEP 1: CHECK STUDENT ID ---
    if (isset($_POST['action']) && $_POST['action'] === 'check_id') {
        $student_id_input = trim($_POST['student_id']);

        if (empty($student_id_input)) {
            $error = "Please enter your Student ID.";
        } else {
            // Check ID + College Context
            if ($college_id) {
                // Strict check: Must exist in THIS college
                $sql = "SELECT a.* FROM alumni a 
                        JOIN academic_records ar ON a.id = ar.alumni_id 
                        WHERE a.student_id = ? AND ar.college_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$student_id_input, $college_id]);
            } else {
                // Global check
                $stmt = $pdo->prepare("SELECT * FROM alumni WHERE student_id = ?");
                $stmt->execute([$student_id_input]);
            }
            
            $alumni_check = $stmt->fetch();

            if (!$alumni_check) {
                $error = $college_id ? "Student ID not found in " . htmlspecialchars($college_name) . "." : "Student ID not found.";
            } elseif ($alumni_check['user_id'] != 0) {
                 $error = "Account already exists. <a href='../../index.php' style='color:inherit;font-weight:bold;'>Login here</a>.";
            } else {
                $step = 2; // Proceed to Email Input
                $alumni_data = $alumni_check;
            }
        }
    }

    // --- STEP 2: SEND VERIFICATION EMAIL ---
    if (isset($_POST['action']) && $_POST['action'] === 'send_email') {
        $student_id_input = trim($_POST['student_id']);
        $email = trim($_POST['email']);
        
        // Re-Verify Alumni Data (Security)
        $stmt = $pdo->prepare("SELECT * FROM alumni WHERE student_id = ?");
        $stmt->execute([$student_id_input]);
        $alumni_data = $stmt->fetch();

        if (!$alumni_data) {
            $error = "Invalid session. Please try again.";
            $step = 1;
        } else {
             // Check Email Uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email address is already in use.";
                $step = 2;
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    $token = bin2hex(random_bytes(32)); 
                    $tempPass = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
                    $displayName = $alumni_data['given_name'] . " " . $alumni_data['surname'];

                    // Create Pending User
                    $sql = "INSERT INTO users (email, password_hash, role, status, is_verified, verification_token, alumni_student_id, display_name) 
                            VALUES (?, ?, 'alumni', 'pending', 0, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$email, $tempPass, $token, $student_id_input, $displayName]);
                    $new_user_id = $pdo->lastInsertId();

                    // Link Alumni Record
                    $pdo->prepare("UPDATE alumni SET user_id = ? WHERE id = ?")->execute([$new_user_id, $alumni_data['id']]);

                    $pdo->commit();
                    
                    // Send Email
                    $mailer = new Mailer();
                    if ($mailer->sendVerificationEmail($email, $token)) {
                        $msg = "We sent a confirmation link to <strong>$email</strong>.<br>Please check your inbox to set your password.";
                        $step = 3; // Show Success Message
                    } else {
                        $error = "Account created, but email failed. Contact Admin.";
                        $step = 2;
                    }
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "System Error: " . $e->getMessage();
                    $step = 2;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - <?= htmlspecialchars($college_name ?: 'Alumni') ?></title>
    <link rel="stylesheet" href="../../assets/css/index.css">
    <style>
        .split-container { display: flex; height: 100vh; }
        .left-pane { flex: 1; background-size: cover; background-position: center; }
        .right-pane { flex: 1; display: flex; align-items: center; justify-content: center; background: white; }
        .login-box { width: 100%; max-width: 400px; padding: 40px; text-align: center; }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; }
        
        /* DYNAMIC BUTTON COLOR */
        .btn-continue { 
            width: 100%; padding: 12px; 
            background: <?= $theme_color ?>; 
            color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px; 
        }
        .btn-continue:hover { opacity: 0.9; }
        
        .footer-link { margin-top: 15px; font-size: 0.9em; }
        .footer-link a { color: <?= $theme_color ?>; text-decoration: none; }
        .logo { width: 80px; margin-bottom: 15px; }
        .step-indicator { color: <?= $theme_color ?>; font-weight: bold; font-size: 0.9em; margin-bottom: 5px; text-transform: uppercase; }
        .found-record { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 25px; text-align: left; border-left: 5px solid #28a745; font-size: 0.95rem; }
    </style>
</head>
<body>

    <div class="split-container">
        <div class="left-pane" style="background-image: <?= $bg_gradient ?>, url('../../assets/images/<?= $bg_image ?>');"></div>

        <div class="right-pane">
            <div class="login-box">
                <img src="../../assets/images/logo1.png" alt="WMSU Logo" class="logo">
                
                <h1>Create Account</h1>
                <div class="title-underline" style="width: 50px; height: 3px; background: <?= $theme_color ?>; margin: 10px auto;"></div>

                <?php if($college_name): ?>
                    <p class="subtitle">Portal: <strong><?= htmlspecialchars($college_name) ?></strong></p>
                <?php else: ?>
                    <p class="subtitle">Alumni Registration</p>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div style="background:#ffe3e3; color:#b30000; padding:10px; border-radius:5px; margin-bottom:20px; text-align: left;">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <?php if ($step === 1): ?>
                    <p class="step-indicator">Step 1 of 2: Verify Identity</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="check_id">
                        <div class="form-group">
                            <label>Student ID Number</label>
                            <input type="text" name="student_id" required placeholder="e.g. 2023-01619" value="<?= htmlspecialchars($student_id_input) ?>">
                        </div>
                        <button type="submit" class="btn-continue">Verify ID &rarr;</button>
                        <div class="footer-link">
                            <a href="college.php">&larr; Change College</a> | <a href="../../index.php">Login</a>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($step === 2): ?>
                    <p class="step-indicator">Step 2 of 2: Connect Email</p>
                    
                    <div class="found-record">
                        <strong>Record Found:</strong><br>
                        <?= htmlspecialchars($alumni_data['given_name'] . " " . $alumni_data['surname']) ?>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="send_email">
                        <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id_input) ?>">

                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" required placeholder="name@email.com">
                        </div>
                        
                        <button type="submit" class="btn-continue">Send Verification Link</button>
                        
                        <div class="footer-link">
                            <a href="register.php">&larr; Go Back</a>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($step === 3): ?>
                    <div style="background:#e8f5e9; padding:20px; border-radius:8px; border:1px solid #c3e6cb; color:#2e7d32;">
                        <h3 style="margin-top:0;">✅ Verification Sent!</h3>
                        <p><?= $msg ?></p>
                    </div>
                    <div class="footer-link">
                        <a href="../../index.php">&larr; Back to Login</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</body>
</html>