<?php
// File: pages/alumniInfo.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
Auth::requireLogin();

$user = Auth::getUser();
$pdo = Database::getPDO();

// ==========================================================
// 1. CONTEXT & PRE-FILL LOGIC
// ==========================================================
$isRenewal = (isset($_GET['type']) && $_GET['type'] === 'Renewal');
$appData = $_SESSION['app_data'] ?? [];

// Fetch existing Alumni Record linked to this User
$stmt = $pdo->prepare("SELECT * FROM alumni WHERE user_id = ?");
$stmt->execute([$user['id']]);
$alumniDB = $stmt->fetch();

// If Renewal, merge DB data into the form data (unless user already edited it in session)
if ($isRenewal && $alumniDB && empty($appData)) {
    $appData = [
        'birthday'      => $alumniDB['birthday'],
        'sex'           => $alumniDB['sex'],
        'civil_status'  => $alumniDB['civil_status'],
        'blood_type'    => $alumniDB['blood_type'],
        'address_street'=> $alumniDB['address_street'],
        'barangay_id'   => $alumniDB['barangay_id'] ?? ''
    ];

    $stmtC = $pdo->prepare("SELECT type, value FROM contacts WHERE alumni_id = ?");
    $stmtC->execute([$alumniDB['id']]);
    while ($row = $stmtC->fetch()) {
        if ($row['type'] === 'mobile') $appData['mobile_number'] = $row['value'];
        if ($row['type'] === 'phone') $appData['tel_number'] = $row['value'];
    }
    $_SESSION['app_data'] = $appData;
}

$data = $_SESSION['app_data'] ?? [];

// ==========================================================
// 2. COLLEGE STYLE SETUP
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
// 3. HANDLE FORM SUBMISSION
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle File Upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $dir = __DIR__ . '/../assets/uploads/temp/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            
            $newName = md5(time() . $user['id']) . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dir . $newName)) {
                $_POST['profile_pic_path'] = 'assets/uploads/temp/' . $newName;
            }
        }
    } else {
        if (isset($_SESSION['app_data']['profile_pic_path'])) {
            $_POST['profile_pic_path'] = $_SESSION['app_data']['profile_pic_path'];
        }
    }

    $_SESSION['app_data'] = array_merge($_SESSION['app_data'] ?? [], $_POST);
    
    // Redirect to NEXT STEP
    header("Location: educationalBackground.php" . ($isRenewal ? "?type=Renewal" : ""));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $isRenewal ? 'Renewal' : 'New' ?> Application - Personal Info</title>
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
        
        .form-row { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 15px; }
        .form-group { flex: 1; min-width: 200px; margin-bottom: 0; }
        
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        input[readonly] { background-color: #e9ecef; cursor: not-allowed; }
        
        .btn-continue { background-color: <?= $border_color ?>; border: none; color: white; padding: 12px 20px; border-radius: 5px; cursor: pointer; transition: opacity 0.3s; }
        .btn-continue:hover { opacity: 0.9; }

        /* The Blue Instruction Box */
        .instruction-text {
            background-color: #e3f2fd;
            color: #0d47a1;
            padding: 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 25px;
            border-left: 4px solid #2196f3;
        }

        .photo-upload { display: flex; align-items: center; gap: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; margin-bottom: 25px; }
        .photo-preview { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid <?= $border_color ?>; }
    </style>
</head>
<body>

<div class="form-container">
    <div class="form-card">
        
        <div style="text-align:center; margin-bottom:30px;">
            <img src="../assets/images/logo1.png" alt="Logo" style="width:70px; margin-bottom:10px;">
            <h2 style="margin:0; color:#333;"><?= $isRenewal ? 'Renew Application' : 'New Application' ?></h2>
            <p style="color:#666; margin-top:5px;">Portal: <strong><?= htmlspecialchars($college_name) ?></strong></p>
        </div>

        <div class="step-progress">
            <span class="step active">1. Personal Info</span>
            <span class="step">2. Education</span>
            <span class="step">3. Employment</span>
            <span class="step">4. Emergency</span>
            <span class="step">5. Review</span>
        </div>

        <div class="instruction-text">
            <strong>Note:</strong> Please fill in your personal details. Fields highlighted in gray are read-only.
        </div>

        <form method="POST" enctype="multipart/form-data">
            
            <div class="photo-upload">
                <img src="<?= isset($data['profile_pic_path']) ? '../' . $data['profile_pic_path'] : '../assets/images/default-avatar.png' ?>" 
                     id="preview-img" class="photo-preview">
                <div>
                    <label style="font-weight:bold;">2x2 ID Photo</label>
                    <input type="file" name="profile_pic" accept="image/*" onchange="previewImage(this)">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" value="<?= htmlspecialchars($alumniDB['surname'] ?? '') ?>" readonly>
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" value="<?= htmlspecialchars($alumniDB['given_name'] ?? '') ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" value="<?= htmlspecialchars($alumniDB['student_id'] ?? '') ?>" readonly>
                </div>
            </div>

            <hr style="border:0; border-top:1px solid #eee; margin: 20px 0;">

            <div class="form-row">
                <div class="form-group">
                    <label>Birth Date</label>
                    <input type="date" name="birthday" required value="<?= htmlspecialchars($data['birthday'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Sex</label>
                    <select name="sex" required>
                        <option value="">-- Select --</option>
                        <option value="Male" <?= ($data['sex'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($data['sex'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Civil Status</label>
                    <select name="civil_status" required>
                        <option value="Single" <?= ($data['civil_status'] ?? '') == 'Single' ? 'selected' : '' ?>>Single</option>
                        <option value="Married" <?= ($data['civil_status'] ?? '') == 'Married' ? 'selected' : '' ?>>Married</option>
                        <option value="Widowed" <?= (isset($data['civil_status']) && $data['civil_status'] === 'Widowed') ? 'selected' : '' ?>>Widowed</option>
                        <option value="Separated" <?= (isset($data['civil_status']) && $data['civil_status'] === 'Separated') ? 'selected' : '' ?>>Separated</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="text" name="mobile_number" required value="<?= htmlspecialchars($data['mobile_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Blood Type</label>
                    <select name="blood_type">
                        <option value="">-- Select --</option>
                        <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bt): ?>
                            <option value="<?= $bt ?>" <?= ($data['blood_type'] ?? '') == $bt ? 'selected' : '' ?>><?= $bt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h4 style="color: <?= $border_color ?>; margin-bottom: 10px; margin-top: 20px;">Permanent Address</h4>
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Street / House No.</label>
                <input type="text" name="address_street" value="<?= htmlspecialchars($data['address_street'] ?? '') ?>" placeholder="Lot 1, Blk 2...">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Region</label>
                    <select id="region_select" required><option>Loading...</option></select>
                </div>
                <div class="form-group">
                    <label>Province</label>
                    <select id="province_select" disabled required><option>Select Region First</option></select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>City / Municipality</label>
                    <select id="city_select" disabled required><option>Select Province First</option></select>
                </div>
                <div class="form-group">
                    <label>Barangay</label>
                    <select id="barangay_select" name="barangay_id" disabled required><option>Select City First</option></select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Zip Code</label>
                <input type="text" name="zip_code" value="<?= htmlspecialchars($data['zip_code'] ?? '') ?>">
            </div>

            <div style="display:flex; justify-content:space-between; margin-top:30px;">
                <a href="selectAction.php" style="padding:12px; color:#666; text-decoration:none;">Cancel</a>
                <button type="submit" class="btn-continue">Next Step &rarr;</button>
            </div>
        </form>
    </div>
</div>

<script>
// Photo Preview
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { document.getElementById('preview-img').src = e.target.result; }
        reader.readAsDataURL(input.files[0]);
    }
}

// ADDRESS DROPDOWN LOGIC
const regionSelect = document.getElementById('region_select');
const provinceSelect = document.getElementById('province_select');
const citySelect = document.getElementById('city_select');
const barangaySelect = document.getElementById('barangay_select');

// Load Regions on Start
fetch('../functions/addressHelper.php?action=regions')
    .then(res => res.json())
    .then(data => {
        populate(regionSelect, data, 'Select Region');
    });

regionSelect.addEventListener('change', function() {
    loadNext(provinceSelect, 'provinces', this.value);
    reset(citySelect); reset(barangaySelect);
});

provinceSelect.addEventListener('change', function() {
    loadNext(citySelect, 'cities', this.value);
    reset(barangaySelect);
});

citySelect.addEventListener('change', function() {
    loadNext(barangaySelect, 'barangays', this.value);
});

function loadNext(target, type, id) {
    target.innerHTML = '<option>Loading...</option>';
    target.disabled = true;
    fetch(`../functions/addressHelper.php?action=${type}&id=${id}`)
        .then(res => res.json())
        .then(data => {
            populate(target, data, `Select ${type.slice(0,-1)}`); 
            target.disabled = false;
        });
}

function populate(select, data, placeholder) {
    select.innerHTML = `<option value="">-- ${placeholder} --</option>`;
    data.forEach(item => {
        let opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.name;
        select.appendChild(opt);
    });
}

function reset(select) {
    select.innerHTML = '<option value="">-- Select --</option>';
    select.disabled = true;
}
</script>

</body>
</html>