<?php
// ==========================================================
// pages/viewCurrentRecord.php — Enhanced Alumni Record View (FIXED)
// ==========================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';

Auth::requireLogin();
$user = Auth::getUser();
$pdo = Database::getPDO();

// Get Alumni ID linked to the logged-in user
$stmtAlumni = $pdo->prepare("SELECT id, student_id FROM alumni WHERE user_id = ? LIMIT 1");
$stmtAlumni->execute([$user['id']]);
$alumni = $stmtAlumni->fetch();

if (!$alumni) {
    echo "<p style='text-align:center; color:#dc3545;'>Alumni record not found for this user.</p>";
    echo "<p style='text-align:center;'><a href='alumniLanding.php' class='back-btn'>← Back to Landing</a></p>";
    exit;
}
$alumniId = $alumni['id'];
$studentId = $alumni['student_id'];

// ----------------------------------------------------------
// 1️⃣ MAIN INFO (alumni table + join for barangay name)
// ----------------------------------------------------------
$sqlMain = "
    SELECT 
        a.*, 
        CONCAT(b.name, ', ', c.name, ', ', p.name, ', ', r.name) AS full_address
    FROM alumni a
    LEFT JOIN barangays b ON a.barangay_id = b.id
    LEFT JOIN cities c ON b.city_id = c.id
    LEFT JOIN provinces p ON c.province_id = p.id
    LEFT JOIN regions r ON p.region_id = r.id
    WHERE a.id = :alumni_id
";
$stmtMain = $pdo->prepare($sqlMain);
$stmtMain->execute([':alumni_id' => $alumniId]);
$main = $stmtMain->fetch();

// ----------------------------------------------------------
// 2️⃣ CONTACTS (contacts table for mobile/email)
// ----------------------------------------------------------
$sqlContacts = "SELECT type, value FROM contacts WHERE alumni_id = ?";
$stmtContacts = $pdo->prepare($sqlContacts);
$stmtContacts->execute([$alumniId]);
$contacts = $stmtContacts->fetchAll(PDO::FETCH_KEY_PAIR); // [type => value]

// ----------------------------------------------------------
// 3️⃣ ACADEMIC RECORDS (for Course and Grad Year)
// ----------------------------------------------------------
$sqlAcademic = "
    SELECT co.name AS course_name, ar.year_graduated, cl.name AS college_name 
    FROM academic_records ar
    JOIN courses co ON ar.course_id = co.id
    JOIN colleges cl ON ar.college_id = cl.id
    WHERE ar.alumni_id = ?
    ORDER BY ar.year_graduated DESC
";
$stmtAcademic = $pdo->prepare($sqlAcademic);
$stmtAcademic->execute([$alumniId]);
$academic = $stmtAcademic->fetch(); // Assuming one primary degree for display

// ----------------------------------------------------------
// 4️⃣ EDUCATION HISTORY (education_history table)
// ----------------------------------------------------------
$sqlEdu = "SELECT * FROM education_history WHERE alumni_id = :alumni_id ORDER BY level DESC";
$stmtEdu = $pdo->prepare($sqlEdu);
$stmtEdu->execute([':alumni_id' => $alumniId]);
$eduHistory = $stmtEdu->fetchAll();

// ----------------------------------------------------------
// 5️⃣ EMPLOYMENT HISTORY (employment_records table)
// ----------------------------------------------------------
$sqlEmp = "SELECT * FROM employment_records WHERE alumni_id = :alumni_id ORDER BY start_date DESC";
$stmtEmp = $pdo->prepare($sqlEmp);
$stmtEmp->execute([':alumni_id' => $alumniId]);
$empHistory = $stmtEmp->fetchAll();

// ----------------------------------------------------------
// 6️⃣ EMERGENCY CONTACT (emergency_contacts table)
// ----------------------------------------------------------
$sqlEmer = "SELECT * FROM emergency_contacts WHERE alumni_id = :alumni_id LIMIT 1";
$stmtEmer = $pdo->prepare($sqlEmer);
$stmtEmer->execute([':alumni_id' => $alumniId]);
$emer = $stmtEmer->fetch();

// ----------------------------------------------------------
// 7️⃣ ATTACHMENT / PROFILE PIC
// ----------------------------------------------------------
$sqlPic = "SELECT path FROM attachments WHERE alumni_id = ? AND type = 'photo' LIMIT 1";
$stmtPic = $pdo->prepare($sqlPic);
$stmtPic->execute([$alumniId]);
$picPath = $stmtPic->fetchColumn();

?>
<div class="record-container">
  <h1>Current Alumni Record</h1>
  <p class="instruction">Below is your information and history from our records.</p>

    <div class="record-card">
    <h2>Personal Information</h2>
    <div class="info-section">
      <?php if (!empty($picPath)): ?>
        <img src="../<?= htmlspecialchars($picPath) ?>" alt="2x2 Photo" class="profile-pic">
      <?php endif; ?>
      <ul>
        <li><strong>Student ID:</strong> <?= htmlspecialchars($studentId) ?></li>
        <li><strong>Full Name:</strong> <?= htmlspecialchars($main['surname'] . ', ' . $main['given_name'] . ' ' . ($main['middle_name'] ?? '')) ?></li>
        <li><strong>College/Course:</strong> <?= htmlspecialchars(($academic['college_name'] ?? 'N/A') . ' / ' . ($academic['course_name'] ?? 'N/A')) ?></li>
        <li><strong>Year Graduated:</strong> <?= htmlspecialchars($academic['year_graduated'] ?? 'N/A') ?></li>
        <li><strong>Address:</strong> <?= htmlspecialchars($main['full_address'] ?? 'N/A') ?></li>
        <li><strong>Mobile Contact:</strong> <?= htmlspecialchars($contacts['mobile'] ?? 'N/A') ?></li>
        <li><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></li>
        <li><strong>Birthday:</strong> <?= htmlspecialchars($main['birthday'] ?? 'N/A') ?></li>
        <li><strong>Blood Type:</strong> <?= htmlspecialchars($main['blood_type'] ?? 'N/A') ?></li>
      </ul>
    </div>
  </div>

    <div class="record-card">
    <h2>Educational Background</h2>
    <?php if (empty($eduHistory)): ?>
      <p>No education records found.</p>
    <?php else: ?>
      <table class="history-table">
        <thead>
          <tr><th>Level</th><th>School Name</th><th>Year Completed</th></tr>
        </thead>
        <tbody>
        <?php foreach ($eduHistory as $row): ?>
            <tr>
                <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $row['level']))) ?></td>
                <td><?= htmlspecialchars($row['school_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['year_completed'] ?? '-') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

    <div class="record-card">
    <h2>Employment History</h2>
    <p class="sub-instruction">Note: This view displays all recorded employment history.</p>
    <?php if (empty($empHistory)): ?>
      <p>No employment records found.</p>
    <?php else: ?>
      <table class="history-table">
        <thead>
          <tr><th>Company Name</th><th>Position</th><th>Address</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($empHistory as $emp): ?>
            <tr class="<?= ($emp['status'] ?? '') === 'active' ? 'active-row' : 'inactive-row' ?>">
              <td><?= htmlspecialchars($emp['company_name'] ?? '-') ?></td>
              <td><?= htmlspecialchars($emp['position'] ?? '-') ?></td>
              <td><?= htmlspecialchars($emp['company_address'] ?? '-') ?></td>
              <td><?= htmlspecialchars(ucfirst($emp['status'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

    <div class="record-card">
    <h2>Emergency Contact</h2>
    <?php if (!$emer): ?>
      <p>No emergency contact on record.</p>
    <?php else: ?>
      <ul>
        <li><strong>Name:</strong> <?= htmlspecialchars($emer['name'] ?? '-') ?></li>
        <li><strong>Address:</strong> <?= htmlspecialchars($emer['address'] ?? '-') ?></li>
        <li><strong>Contact No.:</strong> <?= htmlspecialchars($emer['phone'] ?? '-') ?></li>
      </ul>
    <?php endif; ?>
  </div>

    <div class="btn-group">
        <a href="alumniInfo.php?type=Renewal" class="renew-btn">Update Your Information / Renew</a>
  </div>
</div>

<style>
.record-container { max-width: 950px; margin: 40px auto; background: #f8fff8; padding: 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); font-family: Arial, sans-serif; }
h1 { color: #198754; text-align: center; margin-bottom: 10px; }
.instruction { text-align: center; color: #555; margin-bottom: 20px; }
.record-card { background: white; border-left: 6px solid #198754; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 0 5px rgba(0,0,0,0.05); }
.record-card h2 { color: #198754; margin-bottom: 10px; }
.record-card ul { list-style: none; padding-left: 0; margin: 0; }
.record-card li { padding: 5px 0; border-bottom: 1px solid #eaeaea; }
.record-card li strong { color: #198754; }
.profile-pic { float: right; width: 120px; height: 120px; border-radius: 8px; border: 2px solid #198754; object-fit: cover; margin-left: 15px; }
.history-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
.history-table th, .history-table td { padding: 8px 10px; border: 1px solid #ddd; text-align: left; font-size: 0.95em; }
.history-table th { background: #e9f7ef; color: #198754; }
.active-row { background: #e8ffed; }
.inactive-row { color: #999; background: #f9f9f9; }
.btn-group { text-align: center; margin-top: 25px; }
.renew-btn { background: #198754; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; }
.renew-btn:hover { background: #157347; }
@media (max-width: 768px) { .profile-pic { float: none; display:block; margin:0 auto 10px; } .history-table th, .history-table td { font-size: 0.9em; } }
</style>