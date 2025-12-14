<?php
// File: debug_login.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/database.php';

$pdo = Database::getPDO();
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    echo "<h3>Debugging Login for: " . htmlspecialchars($email) . "</h3>";

    // 1. Check if User Exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<div style='color:red'>❌ User NOT FOUND in database.</div>";
    } else {
        echo "<div style='color:green'>✅ User FOUND (ID: " . $user['id'] . ")</div>";
        
        // 2. Check Verification Status
        if ($user['is_verified'] == 0) {
            echo "<div style='color:orange'>⚠️ Account is NOT VERIFIED. (Login checks usually block this)</div>";
        } else {
            echo "<div style='color:green'>✅ Account is Verified.</div>";
        }

        // 3. Check Password Hash
        echo "<div>Stored Hash starts with: " . substr($user['password_hash'], 0, 10) . "...</div>";
        
        if (password_verify($password, $user['password_hash'])) {
            echo "<div style='color:green; font-weight:bold; font-size:1.2em;'>✅ PASSWORD MATCHES!</div>";
            echo "<p>If you see this, your password is correct. The issue is likely your browser auto-filling the wrong thing on the real login page.</p>";
        } else {
            echo "<div style='color:red; font-weight:bold; font-size:1.2em;'>❌ PASSWORD MISMATCH</div>";
            echo "<p>The password you typed does not match the hash in the database.</p>";
        }
    }
    echo "<hr><a href='debug_login.php'>Try Again</a>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debug Login</title>
    <style>body { font-family: sans-serif; padding: 40px; background: #f4f4f4; }</style>
</head>
<body>
    <h2>Login Debugger</h2>
    <p>Enter the credentials you are trying to use.</p>
    <form method="POST">
        <p>Email: <br><input type="text" name="email" required style="width:300px; padding:8px;"></p>
        <p>Password: <br><input type="text" name="password" required style="width:300px; padding:8px;"></p>
        <button type="submit" style="padding:10px 20px; background:blue; color:white; border:none;">Check Credentials</button>
    </form>
</body>
</html>