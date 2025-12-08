<?php
// File: pages/auth/register.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/auth.php';
require_once __DIR__ . '/../../classes/database.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $studentId = trim($_POST['student_id'] ?? '');
    $role = ($_POST['role'] ?? 'alumni');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please provide a valid email.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($role === 'alumni' && $studentId === '') $errors[] = 'Student ID is required for alumni accounts.';

    // check duplicate email
    $pdo = Database::getPDO();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) $errors[] = 'This email is already registered.';

    if (empty($errors)) {
        // create user (is_verified remains 0)
        $userId = Auth::createUser([
            'email' => $email,
            'password' => $password,
            'display_name' => $displayName ?: $email,
            'role' => $role,
            'is_verified' => 0
        ]);

        // optionally link student_id in pending_users for admin review if student_id mismatch
        if ($role === 'alumni') {
            $stmt = $pdo->prepare("INSERT INTO pending_users (student_id, full_name, email, course, college_id, year_graduated, status) VALUES (:sid, :fn, :em, NULL, NULL, NULL, 'pending')");
            $stmt->execute([':sid' => $studentId, ':fn' => $displayName, ':em' => $email]);
        }

        // send verification email using WMSU-styled HTML
        Auth::sendVerificationEmail($userId);
        $success = 'Account created. A verification email has been sent — check your inbox (and spam).';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Register — WMSU Alumni</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
  <style>
    .authBox { max-width:620px; margin:40px auto; background:#fff; border-left:6px solid #198754;
              padding:28px; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.06); font-family:Arial, sans-serif; }
    .header { color:#198754; font-size:1.4em; margin-bottom:12px; }
    label { display:block; font-weight:700; margin-bottom:6px; }
    input, select { width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; margin-bottom:12px; }
    button { background:#198754; color:#fff; border:none; padding:10px 14px; border-radius:6px; cursor:pointer; font-weight:700; }
    .errors { background:#fff0f0; color:#b30000; padding:10px; border-radius:6px; margin-bottom:12px; border:1px solid #f5c2c2; }
    .success { background:#eefaf0; color:#157347; padding:10px; border-radius:6px; margin-bottom:12px; border:1px solid #d4f5dd; }
  </style>
</head>
<body class="default-program-bg">
  <div class="mainContainer">
    <div class="authBox">
      <div class="header">Create Account — WMSU Alumni</div>

      <?php if ($errors): ?>
        <div class="errors">
          <?php foreach ($errors as $e) echo "<div>" . htmlspecialchars($e) . "</div>"; ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="POST">
        <label for="display_name">Full name</label>
        <input id="display_name" name="display_name" placeholder="Juan dela Cruz" value="<?= htmlspecialchars($_POST['display_name'] ?? '') ?>">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>

        <label for="role">Role</label>
        <select id="role" name="role">
          <option value="alumni" <?= (($_POST['role'] ?? '') === 'alumni') ? 'selected' : '' ?>>Alumni</option>
          <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
          <option value="both" <?= (($_POST['role'] ?? '') === 'both') ? 'selected' : '' ?>>Both (Admin + Alumni)</option>
        </select>

        <label for="student_id">Student ID (for alumni)</label>
        <input id="student_id" name="student_id" placeholder="e.g. 2017-0001" value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>">

        <button type="submit">Create account & send verification</button>
      </form>

      <p style="margin-top:12px;">
        <a href="<?= BASE_URL ?>/pages/auth/login.php">Already have an account? Sign in</a>
      </p>
    </div>
  </div>
</body>
</html>
