<?php
// ======================================================
// Global System Configuration
// ======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DATABASE ---------------------------------------------
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'wmsu_alumni_db'); 
define('DB_USER', 'root');
define('DB_PASS', '');

// BASE URL ---------------------------------------------
define('BASE_URL', '/cssAlumniDirectorySystem');

// SMTP CONFIG (GMAIL APP PASSWORD REQUIRED) -------------
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'mathewpayopelin.payo.dev@gmail.com');
define('SMTP_PASSWORD', 'csmz bhta oltn ctik');
define('SMTP_FROM_EMAIL', 'mathewpayopelin.payo.dev@gmail.com');
define('SMTP_FROM_NAME', 'WMSU Alumni Office');

// Helper redirect
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit();
}

// GLOBAL PDO (optional)
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}
?>
