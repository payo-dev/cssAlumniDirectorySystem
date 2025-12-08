<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Mailer.php';
session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = strtolower(trim($_POST['email'] ?? ''));
    $studentId = trim($_POST['student_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email.";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if ($password !== $confirm) $errors[] = "Password mismatch.";
    if ($studentId === '') $errors[] = "Student ID required.";

    if (empty($errors)) {
        $pdo = Database::getPDO();

        // Existing email?
        $check = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $check->execute([':email' => $email]);
        if ($check->fetch()) $errors[] = "Email already taken.";

        // If clean, verify student ID in alumni table
        if (empty($errors)) {
            $q = $pdo->prepare("SELECT id FROM alumni WHERE student_id = :sid LIMIT 1");
            $q->execute([':sid' => $studentId]);
            $found = $q->fetch();

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(24));

            if ($found) {
                // Create verified alumni account
                $insert = $pdo->prepare("
                    INSERT INTO users (email, password_hash, full_name, role, alumni_student_id, verification_token)
                    VALUES (:email, :pass, :name, 'alumni', :sid, :token)
                ");
                $insert->execute([
                    ':email'=>$email,
                    ':pass'=>$passwordHash,
                    ':name'=>$fullName,
                    ':sid'=>$studentId,
                    ':token'=>$token
                ]);

                Mailer::sendVerificationEmail($email, $token);

                $_SESSION['flash'] = "Check your email to verify your account.";
                header("Location: Login.php");
                exit;
            }

            // Not found → pending user
            $pdo->prepare("
                INSERT INTO pending_users (student_id, full_name, email, status)
                VALUES (:sid, :name, :email, 'pending')
            ")->execute([
                ':sid'=>$studentId,
                ':name'=>$fullName,
                ':email'=>$email
            ]);

            $_SESSION['flash'] = "Your Student ID was not found. Admin will review your request.";
            header("Location: Login.php");
            exit;
        }
    }
}
?>
