<?php
// File: pages/educationalBackground.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
Auth::requireLogin();

$user = Auth::getUser();
$pdo = Database::getPDO();

// ==========================================================
// 1. GET COLLEGE CONTEXT
// ==========================================================
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
            $border_color = '#006400'; // Green
            $bg_gradient = 'linear-gradient(to top, rgba(0, 80, 0, 0.9) 0%, rgba(0, 80, 0, 0.1) 100%)';
        } elseif (strtoupper($col['code']) === 'CN') {
            $bg_image = 'cn-bg.jpg';
            $border_color = '#e91e63'; // Pink
            $bg_gradient = 'linear-gradient(to top, rgba(233, 30, 99, 0.9) 0%, rgba(233, 30, 99, 0.1) 100%)';
        }
    }
}

// ==========================================================
// 2. HANDLE FORM SUBMISSION (Save & Traverse)
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Merge new data with existing session data
    $_SESSION['app_data'] = array_merge($_SESSION['app_data'] ?? [], $_POST);
    
    // Check navigation
    $isRenewal = (isset($_GET['type']) && $_GET['type'] === 'Renewal');
    $qs = $isRenewal ? "?type=Renewal" : "";

    if (isset($_POST['action']) && $_POST['action'] === 'back') {
        header("Location: alumniInfo.php" . $qs);
    } else {
        header("Location: employmentRecord.php" . $qs);
    }
    exit;
}

$data = $_SESSION['app_data'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Education - Application</title>
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
        
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }

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
            <h2 style="margin:0; color:#333;">Educational Background</h2>
            <p style="color:#666; margin-top:5px;">Portal: <strong><?= htmlspecialchars($college_name) ?></strong></p>
        </div>

        <div class="step-progress">
            <span class="step">1. Personal Info</span>
            <span class="step active">2. Education</span>
            <span class="step">3. Employment</span>
            <span class="step">4. Emergency</span>
            <span class="step">5. Review</span>
        </div>

        <div class="instruction-text">
            <strong>Note:</strong> Please provide the complete names of the schools you attended.
        </div>

        <form method="POST">
            <?php
            $sections = [
                'Elementary' => ['school' => 'elem_school', 'year' => 'elem_year'],
                'Junior High School' => ['school' => 'jhs_school', 'year' => 'jhs_year'],
                'Senior High School' => ['school' => 'shs_school', 'year' => 'shs_year'],
                'Tertiary' => ['school' => 'tertiary_school', 'year' => 'tertiary_year'],
                'Graduate School' => ['school' => 'grad_school', 'year' => 'grad_year'],
            ];

            foreach ($sections as $label => $fields): 
                $schoolKey = $fields['school'];
                $yearKey = $fields['year'];
                $isRequired = ($label !== 'Graduate School') ? 'required' : ''; 
            ?>
                <fieldset>
                    <legend><?= $label ?></legend>
                    <div class="two-col">
                        <div class="form-group">
                            <label>School Name</label>
                            <input type="text" name="<?= $schoolKey ?>" 
                                   value="<?= htmlspecialchars($data[$schoolKey] ?? '') ?>" 
                                   placeholder="Name of School" <?= $isRequired ?>>
                        </div>
                        <div class="form-group">
                            <label>Year Graduated</label>
                            <input type="number" name="<?= $yearKey ?>" 
                                   value="<?= htmlspecialchars($data[$yearKey] ?? '') ?>" 
                                   placeholder="YYYY" min="1900" max="<?= date('Y') ?>" <?= $isRequired ?>>
                        </div>
                    </div>
                </fieldset>
            <?php endforeach; ?>

            <div style="display:flex; justify-content:space-between; margin-top:30px;">
                <button type="submit" name="action" value="back" class="btn-back">&larr; Back</button>
                <button type="submit" name="action" value="next" class="btn-continue">Next Step &rarr;</button>
            </div>

        </form>
    </div>
</div>
</body>
</html>