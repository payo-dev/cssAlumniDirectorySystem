<?php
// File: pages/auth/identityCheck.php
// Identity check: student_id + email. For Option A (admin-seeded alumni).
session_start();
require_once __DIR__ . '/../../classes/database.php';

$pdo = Database::getPDO();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['student_id'] ?? '');
    $email     = trim($_POST['email'] ?? '');

    if ($studentId === '' || $email === '') {
        $message = 'Please provide both Student ID and Email.';
    } else {
        // Try find alumni record (alumni.user_id may link to users.id)
        $stmt = $pdo->prepare("
            SELECT a.*, u.id AS user_id, u.email AS user_email
            FROM alumni a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.student_id = :sid
            LIMIT 1
        ");
        $stmt->execute([':sid' => $studentId]);
        $alumni = $stmt->fetch();

        if ($alumni) {
            // Alumni exists. If linked user exists and email matches -> ask to login
            if (!empty($alumni['user_id'])) {
                if (strcasecmp($alumni['user_email'], $email) === 0) {
                    // Email matches linked account -> redirect to login (or register if no password)
                    $_SESSION['pre_auth_student_id'] = $studentId;
                    $_SESSION['pre_auth_email'] = $email;
                    header('Location: /cssAlumniDirectorySystem/pages/auth/register.php?student_id=' . urlencode($studentId));
                    exit;
                } else {
                    // Linked user exists but email mismatch -> create pending_users record (mismatch)
                    $stmtP = $pdo->prepare("INSERT INTO pending_users (student_id, full_name, email, course, year_graduated, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                    $full = trim(($alumni['surname'] ?? '') . ' ' . ($alumni['given_name'] ?? ''));
                    $stmtP->execute([$studentId, $full, $email, $alumni['course'] ?? null, $alumni['year_graduated'] ?? null]);
                    $message = "We found a pre-seeded record for Student ID {$studentId} but the email you provided doesn't match our records. We've created a pending registration. Ask your admin to review it.";
                }
            } else {
                // Alumni exists but not linked to a user -> allow registration
                $_SESSION['pre_auth_student_id'] = $studentId;
                $_SESSION['pre_auth_email'] = $email;
                header('Location: /cssAlumniDirectorySystem/pages/auth/register.php?student_id=' . urlencode($studentId));
                exit;
            }
        } else {
            // No alumni found -> create pending user for admin (id_missing)
            $stmtP = $pdo->prepare("INSERT INTO pending_users (student_id, email, full_name, status, reason) VALUES (?, ?, ?, 'pending', 'id_missing')");
            $stmtP->execute([$studentId, $email, null]);
            $message = "No pre-seeded record found for Student ID {$studentId}. We've notified your admin (pending registration). Please contact your college admin for faster approval.";
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Verify Identity — Alumni</title>
  <link rel="stylesheet" href="/cssAlumniDirectorySystem/assets/css/styles.css">
  <style>
    .container{max-width:520px;margin:40px auto;padding:24px;background:#f8fff8;border-radius:10px}
    label{font-weight:700;color:#198754}
    .err{color:#b30000;margin:10px 0}
  </style>
</head>
<body>
  <div class="container">
    <h2>Alumni Renewal / Registration — Verify Identity</h2>
    <p>Please enter your <strong>Student ID</strong> and the <strong>email</strong> you want to use.</p>

    <?php if ($message): ?>
      <div class="err"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Student ID</label>
        <input type="text" name="student_id" required value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div style="margin-top:12px;">
        <button type="submit" style="background:#198754;color:#fff;padding:10px 16px;border:none;border-radius:6px;">Verify</button>
        <a href="/cssAlumniDirectorySystem/index.php" style="margin-left:8px;color:#198754;">← Back</a>
      </div>
    </form>
  </div>
</body>
</html>
