<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$message = "";
$resent = false;

// ============================
// Handle Login Form
// ============================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? "");
    $password = trim($_POST['password'] ?? "");

    if (Auth::login($email, $password)) {

        // If admin account
        if (Auth::isAdmin()) {
            header("Location: " . BASE_URL . "/pages/adminDashboard.php");
            exit;
        }

        // Alumni account
        header("Location: " . BASE_URL . "/index.php");
        exit;

    } else {
        // Check if user exists but not verified
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $u = $stmt->fetch();

        if ($u && $u["is_verified"] == 0) {
            $_SESSION['pending_verification_user'] = $u['id'];
            $message = "Your email is not verified. Please verify before logging in.";
        } else {
            $message = "Invalid email or password.";
        }
    }
}

// ============================
// Handle Resend Verification
// ============================
if (isset($_GET['resend']) && isset($_SESSION['pending_verification_user'])) {
    $id = $_SESSION['pending_verification_user'];

    if (Auth::sendVerificationEmail($id)) {
        $resent = true;
    } else {
        $message = "Failed to resend verification email.";
    }
}

?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div style="
    max-width:420px;
    margin:60px auto;
    background:#fff;
    padding:30px;
    border-radius:12px;
    box-shadow:0 4px 18px rgba(0,0,0,0.18);
    font-family:Arial;
">

    <h2 style="text-align:center; color:#b30000; margin-bottom:25px;">
        Login to Your Account
    </h2>

    <?php if ($message): ?>
        <div style="
            background:#ffe3e3;
            color:#b30000;
            padding:10px;
            border-radius:6px;
            margin-bottom:15px;
        ">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($resent): ?>
        <div style="
            background:#e0ffe5;
            color:#0b7a22;
            padding:10px;
            border-radius:6px;
            margin-bottom:15px;
        ">
            Verification email sent again!
        </div>
    <?php endif; ?>

    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" required
               style="width:100%; padding:10px; margin-bottom:12px; border:1px solid #ccc; border-radius:6px;">

        <label>Password</label>
        <input type="password" name="password" required
               style="width:100%; padding:10px; margin-bottom:12px; border:1px solid #ccc; border-radius:6px;">

        <button type="submit" style="
            width:100%;
            padding:12px;
            background:#b30000;
            color:white;
            border:none;
            border-radius:6px;
            font-size:1.1em;
            cursor:pointer;
        ">
            Login
        </button>
    </form>

    <?php if (isset($_SESSION['pending_verification_user'])): ?>
        <p style="margin-top:15px; text-align:center; color:#333;">
            Didn’t get the email?  
            <a href="?resend=1" style="color:#b30000; font-weight:bold;">Resend Verification</a>
        </p>
    <?php endif; ?>

    <p style="margin-top:20px; text-align:center;">
        <a href="<?= BASE_URL ?>/pages/auth/forgotPassword.php"
           style="color:#b30000; font-weight:600;">
           Forgot Password?
        </a>
    </p>
</div>
