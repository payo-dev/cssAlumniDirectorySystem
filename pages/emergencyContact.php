<?php
// File: pages/emergencyContact.php
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

// 2. CHECK RENEWAL STATUS
$isRenewal = (isset($_GET['type']) && $_GET['type'] === 'Renewal');

// 3. HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['app_data'] = array_merge($_SESSION['app_data'] ?? [], $_POST);
    
    if (isset($_POST['action']) && $_POST['action'] === 'back') {
        // Correctly pass renewal status back to the previous page
        $qs = $isRenewal ? "?type=Renewal" : "";
        header("Location: employmentRecord.php" . $qs);
    } else {
        header("Location: reviewSubmit.php"); // Next Step
    }
    exit;
}

$data = $_SESSION['app_data'] ?? [];

// --- DYNAMIC STYLES ---
$bg_image = 'default-bg.jpg';
$border_color = '#b30000';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Emergency Contact - New Application</title>
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
        .form-container { max-width: 800px; margin: 40px auto; padding: 0 20px; position: relative; z-index: 1; }
        .form-card {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-top: 5px solid <?= $border_color ?>; 
        }
        .step-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .step { font-size: 0.9rem; color: #999; font-weight: 600; }
        .step.active { color: <?= $border_color ?>; }

        .btn-continue { background-color: <?= $border_color ?>; border: none; color: white; padding: 12px 20px; border-radius: 5px; cursor: pointer; transition: opacity 0.3s; }
        .btn-continue:hover { opacity: 0.9; }
        
        .btn-back { background-color: transparent; border: 1px solid #ccc; color: #666; padding: 12px 20px; border-radius: 5px; cursor: pointer; transition: all 0.3s; }
        .btn-back:hover { background-color: #f0f0f0; color: #333; }

        .instruction-text {
            background-color: #e3f2fd;
            color: #0d47a1;
            padding: 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 25px;
            border-left: 4px solid #2196f3;
        }
    </style>
</head>
<body>

<div class="form-container">
    <div class="form-card">
        
        <div style="text-align:center; margin-bottom:30px;">
            <img src="../assets/images/logo1.png" alt="Logo" style="width:70px; margin-bottom:10px;">
            <h2 style="margin:0; color:#333;">Emergency Contact</h2>
            <p style="color:#666; margin-top:5px;">Portal: <strong><?= htmlspecialchars($college_name) ?></strong></p>
        </div>

        <div class="step-progress">
            <span class="step">1. Personal Info</span>
            <span class="step">2. Education</span>
            <span class="step">3. Employment</span>
            <span class="step active">4. Emergency</span>
            <span class="step">5. Review</span>
        </div>

        <div class="instruction-text">
            <strong>In case of emergency, please notify:</strong>
            <?php if($isRenewal): ?><br>(Optional for renewal — update only if there are changes)<?php else: ?><br>(Required for new applicants)<?php endif; ?>
        </div>

        <form method="POST">
            
            <div class="form-group">
                <label>Full Name of Contact Person</label>
                <input type="text" name="emergency_name" 
                        value="<?= htmlspecialchars($data['emergency_name'] ?? '') ?>" 
                        placeholder="Last Name, First Name" 
                        <?= $isRenewal ? '' : 'required' ?>>
            </div>

            <div class="form-group">
                <label>Address</label>
                <textarea name="emergency_address" rows="3" 
                            placeholder="Full address (Region, Province, City, Barangay)" 
                            style="width:100%; padding:14px 18px; border:2px solid #e9ecef; border-radius:8px; font-family:inherit;"
                            <?= $isRenewal ? '' : 'required' ?>><?= htmlspecialchars($data['emergency_address'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Contact Number</label>
                <input type="tel" name="emergency_contact" 
                        value="<?= htmlspecialchars($data['emergency_contact'] ?? '') ?>" 
                        placeholder="e.g., 09xxxxxxxxx" 
                        pattern="[0-9]{11,13}" 
                        title="Phone number must be 11 to 13 digits." 
                        <?= $isRenewal ? '' : 'required' ?>>
            </div>

            <div style="display:flex; justify-content:space-between; margin-top:30px;">
                <button type="submit" name="action" value="back" class="btn-back">&larr; Back</button>
                <button type="submit" name="action" value="next" class="btn-continue">Review & Submit &rarr;</button>
            </div>

        </form>
    </div>
</div>

</body>
</html>