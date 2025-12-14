<?php
// File: pages/reviewSubmit.php
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

// Check DB linkage
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

// 2. GET FORM DATA
$form = $_SESSION['app_data'] ?? [];

if (empty($form)) {
    header("Location: alumniInfo.php");
    exit;
}

// 3. FETCH STATIC DATA (Name, ID) for display
$stmt = $pdo->prepare("SELECT * FROM alumni WHERE user_id = ?");
$stmt->execute([$user['id']]);
$alumni = $stmt->fetch();

// --- DYNAMIC STYLES ---
$bg_image = 'default-bg.jpg';
$border_color = '#b30000'; // Default Red
$bg_gradient = 'linear-gradient(to top, rgba(139, 0, 0, 0.9) 0%, rgba(139, 0, 0, 0.1) 100%)';

$code_upper = strtoupper($college_code);
if ($code_upper === 'CCS') {
    $bg_image = 'ccs-bg.jpg';
    $border_color = '#006400';
    $bg_gradient = 'linear-gradient(to top, rgba(0, 80, 0, 0.9) 0%, rgba(0, 80, 0, 0.1) 100%)';
} elseif ($code_upper === 'CN') {
    $bg_image = 'cn-bg.jpg';
    $border_color = '#e91e63';
    $bg_gradient = 'linear-gradient(to top, rgba(233, 30, 99, 0.9) 0%, rgba(233, 30, 99, 0.1) 100%)';
}

// GET FLASH MESSAGE
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review & Submit - New Application</title>
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
        .form-container { max-width: 900px; margin: 40px auto; padding: 0 20px; position: relative; z-index: 1; }
        .form-card {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-top: 5px solid <?= $border_color ?>; 
        }
        
        /* Step Progress */
        .step-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .step { font-size: 0.9rem; color: #999; font-weight: 600; }
        .step.active { color: <?= $border_color ?>; }

        /* Review Sections */
        .review-section {
            margin-bottom: 30px;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
        }
        .review-header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            font-weight: 700;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .edit-link {
            font-size: 0.85em;
            color: <?= $border_color ?>;
            text-decoration: none;
        }
        .edit-link:hover { text-decoration: underline; }
        
        .review-content { padding: 20px; }
        .review-row {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px solid #f9f9f9;
            padding-bottom: 5px;
        }
        .review-label {
            flex: 0 0 180px;
            font-weight: 600;
            color: #666;
            font-size: 0.95em;
        }
        .review-value {
            flex: 1;
            color: #333;
            font-size: 0.95em;
        }

        .btn-submit {
            background-color: #28a745; /* Green for submit action */
            color: white;
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 700;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
            box-shadow: 0 4px 6px rgba(40, 167, 69, 0.2);
        }
        .btn-submit:hover { background-color: #218838; }
        
        .btn-back { background-color: transparent; border: 1px solid #ccc; color: #666; padding: 12px 20px; border-radius: 5px; cursor: pointer; transition: all 0.3s; }
        .btn-back:hover { background-color: #f0f0f0; color: #333; }

        .profile-thumb {
            width: 80px; height: 80px; border-radius: 50%; object-fit: cover;
            border: 2px solid <?= $border_color ?>;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="form-container">
    <div class="form-card">
        
        <div style="text-align:center; margin-bottom:30px;">
            <img src="../assets/images/logo1.png" alt="Logo" style="width:70px; margin-bottom:10px;">
            <h2 style="margin:0; color:#333;">Review Application</h2>
            <p style="color:#666; margin-top:5px;">Please review your details before submitting.</p>
        </div>

        <div class="step-progress">
            <span class="step">1. Personal Info</span>
            <span class="step">2. Education</span>
            <span class="step">3. Employment</span>
            <span class="step">4. Emergency</span>
            <span class="step active">5. Review</span>
        </div>

        <?php if($flash_error): ?>
            <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; margin-bottom:20px; border:1px solid #f5c6cb;">
                <strong>Error:</strong> <?= htmlspecialchars($flash_error) ?>
            </div>
        <?php endif; ?>

        <form action="../functions/submitForm.php" method="POST">
            
            <div class="review-section">
                <div class="review-header">
                    Personal Information
                    <a href="alumniInfo.php" class="edit-link">Edit</a>
                </div>
                <div class="review-content">
                    <?php 
                    // Logic to display the uploaded or default profile picture
                    $pic_path = $form['profile_pic_path'] ?? '';
                    $display_path = !empty($pic_path) ? '../' . htmlspecialchars($pic_path) : '../assets/images/default-avatar.png';
                    ?>
                    <div style="text-align:center; margin-bottom:15px;">
                        <img src="<?= $display_path ?>" class="profile-thumb" alt="Profile Pic">
                    </div>

                    <div class="review-row"><span class="review-label">Full Name:</span> <span class="review-value"><?= htmlspecialchars($alumni['given_name'] . ' ' . $alumni['middle_name'] . ' ' . $alumni['surname']) ?></span></div>
                    <div class="review-row"><span class="review-label">Student ID:</span> <span class="review-value"><?= htmlspecialchars($alumni['student_id']) ?></span></div>
                    <div class="review-row"><span class="review-label">Birth Date:</span> <span class="review-value"><?= htmlspecialchars($form['birthday'] ?? '') ?></span></div>
                    <div class="review-row"><span class="review-label">Sex:</span> <span class="review-value"><?= htmlspecialchars($form['sex'] ?? '') ?></span></div>
                    <div class="review-row"><span class="review-label">Civil Status:</span> <span class="review-value"><?= htmlspecialchars($form['civil_status'] ?? '') ?></span></div>
                    <div class="review-row"><span class="review-label">Mobile:</span> <span class="review-value"><?= htmlspecialchars($form['mobile_number'] ?? '') ?></span></div>
                    <div class="review-row"><span class="review-label">Email:</span> <span class="review-value"><?= htmlspecialchars($user['email']) ?></span></div>
                    <div class="review-row"><span class="review-label">Address:</span> <span class="review-value">
                        <?= htmlspecialchars($form['address_street'] ?? '') ?>, 
                        <?= htmlspecialchars($form['barangay'] ?? '') ?>, 
                        <?= htmlspecialchars($form['city'] ?? '') ?>, 
                        <?= htmlspecialchars($form['province'] ?? '') ?> 
                        (<?= htmlspecialchars($form['zip_code'] ?? '') ?>)
                    </span></div>
                </div>
            </div>

            <div class="review-section">
                <div class="review-header">
                    Educational Background
                    <a href="educationalBackground.php" class="edit-link">Edit</a>
                </div>
                <div class="review-content">
                    <div class="review-row"><span class="review-label">Elementary:</span> <span class="review-value"><?= htmlspecialchars($form['elem_school'] ?? '') ?> (<?= htmlspecialchars($form['elem_year'] ?? '') ?>)</span></div>
                    <div class="review-row"><span class="review-label">Junior High:</span> <span class="review-value"><?= htmlspecialchars($form['jhs_school'] ?? '') ?> (<?= htmlspecialchars($form['jhs_year'] ?? '') ?>)</span></div>
                    <div class="review-row"><span class="review-label">Senior High:</span> <span class="review-value"><?= htmlspecialchars($form['shs_school'] ?? '') ?> (<?= htmlspecialchars($form['shs_year'] ?? '') ?>)</span></div>
                    <div class="review-row"><span class="review-label">Tertiary:</span> <span class="review-value"><?= htmlspecialchars($form['tertiary_school'] ?? '') ?> (<?= htmlspecialchars($form['tertiary_year'] ?? '') ?>)</span></div>
                    <?php if(!empty($form['grad_school'])): ?>
                    <div class="review-row"><span class="review-label">Graduate:</span> <span class="review-value"><?= htmlspecialchars($form['grad_school']) ?> (<?= htmlspecialchars($form['grad_year'] ?? '') ?>)</span></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="review-section">
                <div class="review-header">
                    Employment Record
                    <a href="employmentRecord.php" class="edit-link">Edit</a>
                </div>
                <div class="review-content">
                    <div class="review-row"><span class="review-label">Status:</span> <span class="review-value"><?= htmlspecialchars($form['employment_status'] ?? '') ?></span></div>
                    <?php if(($form['employment_status'] ?? '') !== 'Unemployed'): ?>
                        <div class="review-row"><span class="review-label">Company:</span> <span class="review-value"><?= htmlspecialchars($form['company_name'] ?? '') ?></span></div>
                        <div class="review-row"><span class="review-label">Position:</span> <span class="review-value"><?= htmlspecialchars($form['position'] ?? '') ?></span></div>
                        <div class="review-row"><span class="review-label">Address (Company):</span> <span class="review-value"><?= htmlspecialchars($form['company_address'] ?? '') ?></span></div>
                        <div class="review-row"><span class="review-label">Contact (Company):</span> <span class="review-value"><?= htmlspecialchars($form['company_contact'] ?? '') ?></span></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="review-section">
                <div class="review-header">
                    Emergency Contact
                    <a href="emergencyContact.php" class="edit-link">Edit</a>
                </div>
                <div class="review-content">
                    <div class="review-row"><span class="review-label">Name:</span> <span class="review-value"><?= htmlspecialchars($form['emergency_name'] ?? '') ?></span></div>
                    <div class="review-row"><span class="review-label">Contact:</span> <span class="review-value"><?= htmlspecialchars($form['emergency_contact'] ?? '') ?></span></div>
                    <div class="review-row"><span class="review-label">Address:</span> <span class="review-value"><?= htmlspecialchars($form['emergency_address'] ?? '') ?></span></div>
                </div>
            </div>

            <input type="hidden" name="alumni_id" value="<?= htmlspecialchars($alumni['id']) ?>">

            <div style="display:flex; justify-content:space-between; margin-top:30px; align-items:center;">
                <a href="emergencyContact.php" class="btn-back" style="text-decoration:none;">&larr; Back</a>
                <div style="flex:1; margin-left:20px;">
                    <button type="submit" class="btn-submit">✅ Submit Application</button>
                </div>
            </div>

        </form>
    </div>
</div>

</body>
</html>