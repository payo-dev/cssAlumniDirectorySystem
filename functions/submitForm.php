<?php
// File: functions/submitForm.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

Auth::requireLogin();

$user = Auth::getUser();
$pdo = Database::getPDO();
$data = $_SESSION['app_data'] ?? [];

// Basic Validation
if (empty($data) || empty($_POST['alumni_id'])) {
    $_SESSION['flash_error'] = "No data to submit.";
    header("Location: ../pages/reviewSubmit.php");
    exit;
}

$alumni_id = $_POST['alumni_id'];

try {
    $pdo->beginTransaction();

    // ==========================================================
    // 1. UPDATE ALUMNI PERSONAL INFO (FIXED FOR 3NF)
    // ==========================================================
    // We now save 'barangay_id' instead of region/province/city text
    $sqlAlumni = "UPDATE alumni SET 
        birthday = ?, 
        civil_status = ?, 
        sex = ?, 
        address_street = ?, 
        barangay_id = ?, 
        zip_code = ?,
        blood_type = ?
        WHERE id = ?";
        
    $stmt = $pdo->prepare($sqlAlumni);
    $stmt->execute([
        $data['birthday'] ?? null,
        $data['civil_status'] ?? 'Single',
        $data['sex'] ?? null,
        $data['address_street'] ?? '',
        $data['barangay_id'] ?? null, // This ID links to the entire address chain
        $data['zip_code'] ?? '',
        $data['blood_type'] ?? null,
        $alumni_id
    ]);

    // ==========================================================
    // 2. INSERT CONTACTS
    // ==========================================================
    $pdo->prepare("DELETE FROM contacts WHERE alumni_id = ?")->execute([$alumni_id]);
    
    if (!empty($data['mobile_number'])) {
        $stmt = $pdo->prepare("INSERT INTO contacts (alumni_id, type, value, is_primary) VALUES (?, 'mobile', ?, 1)");
        $stmt->execute([$alumni_id, $data['mobile_number']]);
    }
    if (!empty($data['tel_number'])) {
        $stmt = $pdo->prepare("INSERT INTO contacts (alumni_id, type, value, is_primary) VALUES (?, 'phone', ?, 0)");
        $stmt->execute([$alumni_id, $data['tel_number']]);
    }

    // ==========================================================
    // 3. INSERT EDUCATION HISTORY
    // ==========================================================
    $pdo->prepare("DELETE FROM education_history WHERE alumni_id = ?")->execute([$alumni_id]);
    $edu_sql = "INSERT INTO education_history (alumni_id, level, school_name, year_completed) VALUES (?, ?, ?, ?)";
    $stmtEdu = $pdo->prepare($edu_sql);

    $educations = [
        ['elementary', $data['elem_school'] ?? '', $data['elem_year'] ?? ''],
        ['junior_high', $data['jhs_school'] ?? '', $data['jhs_year'] ?? ''],
        ['senior_high', $data['shs_school'] ?? '', $data['shs_year'] ?? ''],
        ['tertiary', $data['tertiary_school'] ?? '', $data['tertiary_year'] ?? ''],
        ['graduate', $data['grad_school'] ?? '', $data['grad_year'] ?? '']
    ];

    foreach ($educations as $edu) {
        if (!empty($edu[1])) { 
            $stmtEdu->execute([$alumni_id, $edu[0], $edu[1], $edu[2]]);
        }
    }

    // ==========================================================
    // 4. INSERT EMPLOYMENT RECORD
    // ==========================================================
    $pdo->prepare("DELETE FROM employment_records WHERE alumni_id = ?")->execute([$alumni_id]);
    
    if (($data['employment_status'] ?? '') !== 'Unemployed' && !empty($data['company_name'])) {
        // Adjusted to match your database columns exactly
        // (Removed salary_range/employment_type if they are not in your DB table)
        $emp_sql = "INSERT INTO employment_records (alumni_id, company_name, position, start_date, company_address, status) 
                    VALUES (?, ?, ?, ?, ?, ?)";
        $stmtEmp = $pdo->prepare($emp_sql);
        $stmtEmp->execute([
            $alumni_id,
            $data['company_name'],
            $data['position'] ?? '',
            $data['date_hired'] ?? null,
            $data['company_address'] ?? '',
            'active'
        ]);
    }

    // ==========================================================
    // 5. INSERT EMERGENCY CONTACT
    // ==========================================================
    $pdo->prepare("DELETE FROM emergency_contacts WHERE alumni_id = ?")->execute([$alumni_id]);
    
    if (!empty($data['emergency_name'])) {
        $emer_sql = "INSERT INTO emergency_contacts (alumni_id, name, phone, address) VALUES (?, ?, ?, ?)";
        $stmtEmer = $pdo->prepare($emer_sql);
        $stmtEmer->execute([
            $alumni_id,
            $data['emergency_name'],
            $data['emergency_contact'] ?? '',
            $data['emergency_address'] ?? ''
        ]);
    }

    // ==========================================================
    // 6. PROCESS PROFILE PICTURE
    // ==========================================================
    if (!empty($data['profile_pic_path'])) {
        $tempPath = __DIR__ . '/../' . $data['profile_pic_path'];
        
        if (file_exists($tempPath)) {
            // Define permanent path
            $uploadDir = __DIR__ . '/../assets/uploads/alumni/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = basename($tempPath);
            $newPath = $uploadDir . $fileName;
            
            if (rename($tempPath, $newPath)) {
                // Save DB record with relative path
                $relativePath = 'assets/uploads/alumni/' . $fileName;
                // Delete existing photo record if any
                $pdo->prepare("DELETE FROM attachments WHERE alumni_id = ? AND type = 'photo'")->execute([$alumni_id]);
                
                $stmtAttach = $pdo->prepare("INSERT INTO attachments (alumni_id, type, path, is_primary) VALUES (?, 'photo', ?, 1)");
                $stmtAttach->execute([$alumni_id, $relativePath]);
            }
        }
    }

    // ==========================================================
    // 7. CREATE APPLICATION RECORD
    // ==========================================================
    // Determine type (New vs Renewal) based on GET/Session
    $appType = (isset($_SESSION['app_data']['barangay_id'])) ? 'Renewal' : 'New'; 
    // ^ Logic: If we are editing, it's likely a renewal/update. 
    // Or simpler: Just assume 'New' unless we explicitly passed a flag. 
    // Let's stick to 'New' as default, or check if we passed a flag in the form.
    $finalType = isset($_GET['type']) && $_GET['type'] === 'Renewal' ? 'Renewal' : 'New';

    $app_sql = "INSERT INTO applications (alumni_id, created_by_user_id, application_type, status) VALUES (?, ?, ?, 'pending')";
    $stmtApp = $pdo->prepare($app_sql);
    $stmtApp->execute([$alumni_id, $user['id'], $finalType]);

    // ==========================================================
    // 8. NOTIFY ADMIN
    // ==========================================================
    $msg = "New alumni application received from ID: $alumni_id";
    $notif_sql = "INSERT INTO notifications (user_id, type, message) 
                  SELECT id, 'new_application', ?, 0, NOW() 
                  FROM users WHERE role = 'admin'";
    $stmtNotif = $pdo->prepare($notif_sql);
    $stmtNotif->execute([$msg]);

    $pdo->commit();

    unset($_SESSION['app_data']);
    // Pass type to Thank You page
    header("Location: ../pages/thankYou.php?type=" . $finalType . "&id=" . $alumni_id);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Submission Error: " . $e->getMessage());
    $_SESSION['flash_error'] = "An error occurred during submission: " . $e->getMessage();
    header("Location: ../pages/reviewSubmit.php");
    exit;
}
?>