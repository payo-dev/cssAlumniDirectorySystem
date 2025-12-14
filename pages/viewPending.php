<?php
// File: pages/viewPending.php (Works for Pending AND Active users)
session_start();
require_once __DIR__ . '/../classes/auth.php';
require_once __DIR__ . '/../classes/database.php';
Auth::restrict();

$pdo = Database::getPDO();
$student_id = $_GET['id'] ?? null;
if (!$student_id) die("<h3>Invalid ID.</h3>");

// UNIFIED QUERY: Joins Users, Alumni, Academic, Employment, Emergency, Attachments
$sql = "
    SELECT 
        a.student_id, a.surname, a.given_name, a.middle_name, a.birthday, a.sex, a.civil_status, a.address_street, a.zip_code,
        u.email, u.status, u.created_at,
        ar.year_graduated, c.name as college_name, co.name as course_name,
        er.company_name, er.position, er.company_address,
        ec.name as emer_name, ec.phone as emer_phone, ec.address as emer_address,
        att.path as photo_path,
        b.name as barangay, ci.name as city, p.name as province
    FROM alumni a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN academic_records ar ON ar.alumni_id = a.id
    LEFT JOIN colleges c ON ar.college_id = c.id
    LEFT JOIN courses co ON ar.course_id = co.id
    LEFT JOIN employment_records er ON er.alumni_id = a.id
    LEFT JOIN emergency_contacts ec ON ec.alumni_id = a.id
    LEFT JOIN attachments att ON (att.alumni_id = a.id AND att.type = 'photo')
    LEFT JOIN barangays b ON a.barangay_id = b.id
    LEFT JOIN cities ci ON b.city_id = ci.id
    LEFT JOIN provinces p ON ci.province_id = p.id
    WHERE a.student_id = :sid
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':sid' => $student_id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) die("<h3>Record not found.</h3>");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>View Alumni Profile</title>
  <link rel="stylesheet" href="../assets/css/index.css">
  <style>
    body { background:#f4f6f9; padding:20px; }
    .view-container { max-width:900px; margin:0 auto; background:white; padding:30px; border-radius:8px; border-top:5px solid #b30000; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
    .header { display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px; }
    .status-badge { padding:5px 10px; border-radius:4px; font-weight:bold; font-size:0.9em; text-transform:uppercase; }
    .status-pending { background:#fff3cd; color:#856404; }
    .status-approved { background:#d4edda; color:#155724; }
    
    .profile-grid { display:grid; grid-template-columns: 200px 1fr; gap:30px; }
    .photo-area img { width:100%; border-radius:8px; border:3px solid #eee; }
    
    .info-section { margin-bottom:20px; }
    .section-title { font-size:1.1em; color:#b30000; border-bottom:2px solid #f1f1f1; padding-bottom:5px; margin-bottom:10px; font-weight:bold; }
    .info-row { display:flex; margin-bottom:8px; }
    .label { width:140px; font-weight:bold; color:#666; font-size:0.9em; }
    .val { flex:1; color:#333; }
    
    .actions { margin-top:30px; border-top:1px solid #eee; padding-top:20px; text-align:right; }
    .btn { padding:10px 20px; text-decoration:none; color:white; border-radius:5px; margin-left:10px; font-weight:bold; }
  </style>
</head>
<body>

<div class="view-container">
    <div class="header">
        <h1 style="margin:0; font-size:1.5rem;">Alumni Profile</h1>
        <span class="status-badge status-<?= $record['status'] ?>">
            <?= $record['status'] ?>
        </span>
    </div>

    <div class="profile-grid">
        <div class="photo-area">
            <?php if (!empty($record['photo_path'])): ?>
                <img src="../<?= htmlspecialchars($record['photo_path']) ?>" alt="Profile">
            <?php else: ?>
                <img src="../assets/images/default-avatar.png" alt="No Photo">
            <?php endif; ?>
        </div>

        <div class="details-area">
            
            <div class="info-section">
                <div class="section-title">Personal Info</div>
                <div class="info-row"><span class="label">Name:</span><span class="val"><?= htmlspecialchars($record['surname'] . ', ' . $record['given_name'] . ' ' . $record['middle_name']) ?></span></div>
                <div class="info-row"><span class="label">Student ID:</span><span class="val"><?= htmlspecialchars($record['student_id']) ?></span></div>
                <div class="info-row"><span class="label">Email:</span><span class="val"><?= htmlspecialchars($record['email']) ?></span></div>
                <div class="info-row"><span class="label">Birthday:</span><span class="val"><?= htmlspecialchars($record['birthday']) ?> (<?= htmlspecialchars($record['sex']) ?>)</span></div>
                <div class="info-row"><span class="label">Civil Status:</span><span class="val"><?= htmlspecialchars($record['civil_status']) ?></span></div>
                <div class="info-row"><span class="label">Address:</span><span class="val">
                    <?= htmlspecialchars($record['address_street']) ?><br>
                    <?= htmlspecialchars($record['barangay']) ?>, <?= htmlspecialchars($record['city']) ?>, <?= htmlspecialchars($record['province']) ?>
                </span></div>
            </div>

            <div class="info-section">
                <div class="section-title">Academic Record</div>
                <div class="info-row"><span class="label">College:</span><span class="val"><?= htmlspecialchars($record['college_name']) ?></span></div>
                <div class="info-row"><span class="label">Course:</span><span class="val"><?= htmlspecialchars($record['course_name']) ?></span></div>
                <div class="info-row"><span class="label">Year Graduated:</span><span class="val"><?= htmlspecialchars($record['year_graduated']) ?></span></div>
            </div>

            <div class="info-section">
                <div class="section-title">Employment</div>
                <div class="info-row"><span class="label">Company:</span><span class="val"><?= htmlspecialchars($record['company_name'] ?? 'Unemployed') ?></span></div>
                <div class="info-row"><span class="label">Position:</span><span class="val"><?= htmlspecialchars($record['position'] ?? '-') ?></span></div>
            </div>

            <div class="info-section">
                <div class="section-title">Emergency Contact</div>
                <div class="info-row"><span class="label">Name:</span><span class="val"><?= htmlspecialchars($record['emer_name'] ?? '-') ?></span></div>
                <div class="info-row"><span class="label">Contact:</span><span class="val"><?= htmlspecialchars($record['emer_phone'] ?? '-') ?></span></div>
            </div>

        </div>
    </div>

    <div class="actions">
        <a href="adminDashboard.php" class="btn" style="background:#6c757d;">Back</a>
        <?php if ($record['status'] === 'pending'): ?>
            <a href="../functions/approve.php?id=<?= urlencode($record['student_id']) ?>" class="btn" style="background:#28a745;" onclick="return confirm('Approve this user?')">Approve</a>
            <a href="../functions/reject.php?id=<?= urlencode($record['student_id']) ?>" class="btn" style="background:#dc3545;" onclick="return confirm('Reject this user?')">Reject</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>