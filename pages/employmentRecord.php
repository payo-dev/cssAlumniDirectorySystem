<?php
// File: pages/employmentRecord.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
Auth::requireLogin();

$user = Auth::getUser();
$pdo = Database::getPDO();

// 1. GET COLLEGE CONTEXT
$college_id = $_SESSION['selected_college'] ?? 0;
$college_name = "Western Mindanao State University"; 
$bg_image = 'default-bg.jpg';
$border_color = '#b30000';
$bg_gradient = 'linear-gradient(to top, rgba(139, 0, 0, 0.9) 0%, rgba(139, 0, 0, 0.1) 100%)';

if ($college_id) {
    $stmt = $pdo->prepare("SELECT name, code FROM colleges WHERE id = ?");
    $stmt->execute([$college_id]);
    $col = $stmt->fetch();
    if ($col) {
        $college_name = $col['name'];
        if (strtoupper($col['code']) === 'CCS') {
            $bg_image = 'ccs-bg.jpg';
            $border_color = '#006400';
            $bg_gradient = 'linear-gradient(to top, rgba(0, 80, 0, 0.9) 0%, rgba(0, 80, 0, 0.1) 100%)';
        } elseif (strtoupper($col['code']) === 'CN') {
            $bg_image = 'cn-bg.jpg';
            $border_color = '#e91e63';
            $bg_gradient = 'linear-gradient(to top, rgba(233, 30, 99, 0.9) 0%, rgba(233, 30, 99, 0.1) 100%)';
        }
    }
}

// 2. HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isRenewal = (isset($_GET['type']) && $_GET['type'] === 'Renewal');
    $qs = $isRenewal ? "?type=Renewal" : "";

    $_SESSION['app_data'] = array_merge($_SESSION['app_data'] ?? [], $_POST);
    
    if (isset($_POST['action']) && $_POST['action'] === 'back') {
        header("Location: educationalBackground.php" . $qs);
    } else {
        header("Location: emergencyContact.php" . $qs);
    }
    exit;
}

$data = $_SESSION['app_data'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employment - Application</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        body { 
            background-image: <?= $bg_gradient ?>, url('../assets/images/<?= $bg_image ?>');
            background-size: cover; background-position: center; background-attachment: fixed;
            overflow-y: auto; 
        }
        body::before {
            content: ''; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.2); z-index: -1;
        }
        .form-container { max-width: 800px; margin: 40px auto; padding: 0 20px; position: relative; z-index: 1; }
        .form-card {
            background: white; padding: 40px; border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15); border-top: 5px solid <?= $border_color ?>; 
        }
        .step-progress { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .step { font-size: 0.9rem; color: #999; font-weight: 600; }
        .step.active { color: <?= $border_color ?>; }

        fieldset { border: 1px solid #eee; border-radius: 8px; padding: 20px; margin-bottom: 25px; background-color: #fcfcfc; }
        legend { font-weight: 700; color: #555; padding: 0 10px; text-transform: uppercase; font-size: 0.85rem; }
        
        .two-col { display: flex; gap: 20px; flex-wrap: wrap; }
        .two-col .form-group { flex: 1; min-width: 200px; }
        
        .btn-continue { background-color: <?= $border_color ?>; border: none; color: white; padding: 12px 20px; border-radius: 5px; cursor: pointer; transition: opacity 0.3s; }
        .btn-continue:hover { opacity: 0.9; }
        
        .btn-back { background-color: transparent; border: 1px solid #ccc; color: #666; padding: 12px 20px; border-radius: 5px; cursor: pointer; transition: all 0.3s; }
        .btn-back:hover { background-color: #f0f0f0; color: #333; }
        
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .employment-status-group { margin-bottom: 25px; padding: 15px; background: #e8f0fe; border-radius: 6px; border-left: 4px solid #1976d2; }

        /* THE BLUE INSTRUCTION BOX */
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
            <h2 style="margin:0; color:#333;">Employment Record</h2>
            <p style="color:#666; margin-top:5px;">Portal: <strong><?= htmlspecialchars($college_name) ?></strong></p>
        </div>

        <div class="step-progress">
            <span class="step">1. Personal Info</span>
            <span class="step">2. Education</span>
            <span class="step active">3. Employment</span>
            <span class="step">4. Emergency</span>
            <span class="step">5. Review</span>
        </div>

        <div class="instruction-text">
            <strong>Note:</strong> Please provide details about your current or most recent employment. 
            <br>
            For now, only your **primary employment** is recorded. You will be able to manage and add **multiple employment records** when you process a renewal application.
        </div>

        <form method="POST">
            
            <div class="employment-status-group">
                <label style="font-weight:bold; display:block; margin-bottom:10px;">Employment Status</label>
                <select name="employment_status" onchange="toggleEmploymentFields(this.value)" style="width:100%; padding:10px;">
                    <option value="Employed" <?= (isset($data['employment_status']) && $data['employment_status'] === 'Employed') ? 'selected' : '' ?>>Employed</option>
                    <option value="Unemployed" <?= (isset($data['employment_status']) && $data['employment_status'] === 'Unemployed') ? 'selected' : '' ?>>Unemployed</option>
                    <option value="Self-employed" <?= (isset($data['employment_status']) && $data['employment_status'] === 'Self-employed') ? 'selected' : '' ?>>Self-employed</option>
                </select>
            </div>

            <div id="employment-details">
                <fieldset>
                    <legend>Job Details</legend>
                    
                    <div class="form-group">
                        <label>Name of Company / Organization</label>
                        <input type="text" name="company_name" value="<?= htmlspecialchars($data['company_name'] ?? '') ?>" placeholder="e.g. ABC Corp">
                    </div>
                    
                    <div class="form-group">
                        <label>Position / Job Title</label>
                        <input type="text" name="position" value="<?= htmlspecialchars($data['position'] ?? '') ?>" placeholder="e.g. Software Engineer">
                    </div>

                    <div class="form-group">
                        <label>Address (Company)</label>
                        <input type="text" name="company_address" value="<?= htmlspecialchars($data['company_address'] ?? '') ?>" placeholder="City, Country">
                    </div>

                    <div class="form-group">
                        <label>Contact Number (Company)</label>
                        <input type="text" name="company_contact" value="<?= htmlspecialchars($data['company_contact'] ?? '') ?>" placeholder="e.g. (062) 991-xxxx">
                    </div>

                </fieldset>
            </div>

            <div style="display:flex; justify-content:space-between; margin-top:30px;">
                <button type="submit" name="action" value="back" class="btn-back">&larr; Back</button>
                <button type="submit" name="action" value="next" class="btn-continue">Next Step &rarr;</button>
            </div>

        </form>
    </div>
</div>

<script>
    function toggleEmploymentFields(status) {
        const details = document.getElementById('employment-details');
        const inputs = details.querySelectorAll('input');
        
        if (status === 'Unemployed') {
            details.style.opacity = '0.5';
            details.style.pointerEvents = 'none';
            // Optional: clear inputs if switching to unemployed
            // inputs.forEach(input => input.value = ''); 
        } else {
            details.style.opacity = '1';
            details.style.pointerEvents = 'auto';
        }
    }
    
    // Run on load
    window.onload = function() {
        const status = document.querySelector('select[name="employment_status"]').value;
        toggleEmploymentFields(status);
    };
</script>

</body>
</html>