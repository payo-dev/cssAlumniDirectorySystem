<?php
// File: classes/auth.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {

    // --- 1. NEW: REQUIRE LOGIN (Missing Function Fixed) ---
    // Used by: alumniInfo.php, educationalBackground.php, etc.
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header("Location: " . BASE_URL . "/index.php");
            exit;
        }
    }

    // --- LOGIN FUNCTION ---
    public static function login($email, $password) {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            
            // Check verification
            if ($user['is_verified'] == 0) return false; 

            // 1. SET BASIC SESSION
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['display_name'] = $user['display_name'];
            $_SESSION['permissions'] = []; // Default empty

            // 2. FETCH PERMISSIONS (If Admin)
            if ($user['role'] === 'admin' || $user['role'] === 'both') {
                $sql = "SELECT p.code 
                        FROM permissions p 
                        JOIN admin_permissions ap ON p.id = ap.permission_id 
                        JOIN admins a ON ap.admin_id = a.id 
                        WHERE a.user_id = ?";
                $stmtPerms = $pdo->prepare($sql);
                $stmtPerms->execute([$user['id']]);
                $_SESSION['permissions'] = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);
            }

            // 3. AUTO-DETECT COLLEGE (If Alumni)
            if ($user['role'] === 'alumni' || $user['role'] === 'both') {
                try {
                    $sql = "SELECT ar.college_id FROM academic_records ar JOIN alumni a ON ar.alumni_id = a.id WHERE a.user_id = ? ORDER BY ar.year_graduated DESC LIMIT 1";
                    $stmtCollege = $pdo->prepare($sql);
                    $stmtCollege->execute([$user['id']]);
                    if ($cid = $stmtCollege->fetchColumn()) $_SESSION['selected_college'] = $cid;
                } catch (Exception $e) {}
            }
            
            // 4. UPDATE LAST LOGIN
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            
            return true;
        }
        return false;
    }

    // --- PERMISSION CHECKER ---
    public static function hasPermission($requiredPermission) {
        if (!self::isLoggedIn()) return false;
        
        // If no specific permission required, just being logged in is enough
        if ($requiredPermission === null) return true;

        $myPerms = $_SESSION['permissions'] ?? [];
        return in_array($requiredPermission, $myPerms);
    }

    // --- RESTRICT ACCESS (ADMIN PAGES) ---
    public static function restrict($requiredPermission = null) {
        // 1. Ensure Logged In
        self::requireLogin(); 

        // 2. Basic Admin Check
        if (!self::isAdmin()) {
            header("Location: " . BASE_URL . "/pages/selectAction.php");
            exit;
        }

        // 3. Granular Permission Check
        if ($requiredPermission !== null) {
            if (!self::hasPermission($requiredPermission)) {
                die("<h1>Access Denied</h1><p>You do not have permission to access this page.</p><a href='" . BASE_URL . "/pages/adminDashboard.php'>Back to Dashboard</a>");
            }
        }
    }

    // --- STANDARD HELPERS ---
    public static function getUser() {
        if (!self::isLoggedIn()) return null;
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'display_name' => $_SESSION['display_name'] ?? 'User',
            'permissions' => $_SESSION['permissions'] ?? []
        ];
    }

    public static function isLoggedIn() { return isset($_SESSION['user_id']); }
    
    public static function isAdmin() {
        return self::isLoggedIn() && (($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['role'] ?? '') === 'both');
    }

    public static function logout() {
        session_unset();
        session_destroy();
        header("Location: " . BASE_URL . "/index.php");
        exit;
    }
}
?>