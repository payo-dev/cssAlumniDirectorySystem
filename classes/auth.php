<?php
// File: classes/auth.php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/../config.php';

class Auth
{
    public static function currentUser(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user_id'])) return null;

        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }

    public static function login(string $email, string $password): bool
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $pdo = Database::getPDO();

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        if (!$user) return false;

        if (!password_verify($password, $user['password_hash'])) return false;

        // not verified (alumni-only)
        if ((int)$user['is_verified'] === 0 && $user['role'] !== 'admin') {
            $_SESSION['pending_verification_user'] = $user['id'];
            return false;
        }

        // success
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];

        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")
            ->execute([':id' => $user['id']]);

        return true;
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_unset();
        session_destroy();
    }

    public static function createUser(array $data): int
    {
        $pdo = Database::getPDO();
        $sql = "INSERT INTO users (email, password_hash, display_name, role, is_verified)
                VALUES (:email, :password_hash, :display_name, :role, :is_verified)";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':email'        => $data['email'],
            ':password_hash'=> password_hash($data['password'], PASSWORD_DEFAULT),
            ':display_name' => $data['display_name'] ?? null,
            ':role'         => $data['role'] ?? 'alumni',
            ':is_verified'  => $data['is_verified'] ?? 0,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function sendVerificationEmail(int $userId): bool
    {
        $pdo = Database::getPDO();
        $token = bin2hex(random_bytes(24));

        $pdo->prepare("UPDATE users SET verification_token = :token WHERE id = :id")
            ->execute([':token' => $token, ':id' => $userId]);

        $row = $pdo->query("SELECT email, display_name FROM users WHERE id = $userId")->fetch();
        if (!$row) return false;

        $url = BASE_URL . "/pages/auth/verify.php?token=" . urlencode($token);

        $subject = "Verify Your Email — WMSU Alumni";
        $body = "
            <p>Hello <strong>" . htmlspecialchars($row['display_name'] ?? $row['email']) . "</strong>,</p>
            <p>Please click the button below to verify your email:</p>
            <p><a href='$url' style='padding:10px 20px; background:#28a745; color:white; text-decoration:none;'>Verify Email</a></p>
            <p>If the button does not work, open this link:</p>
            <p>$url</p>
        ";

        return Mailer::send($row['email'], $subject, $body);
    }

    public static function verifyToken(string $token): bool
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);

        $row = $stmt->fetch();
        if (!$row) return false;

        $pdo->prepare("
            UPDATE users 
            SET is_verified = 1, verification_token = NULL, approved_at = NOW() 
            WHERE id = :id
        ")->execute([':id' => $row['id']]);

        return true;
    }

    public static function isAdmin(): bool
    {
        $u = self::currentUser();
        return $u && ($u['role'] === 'admin' || $u['role'] === 'both');
    }

    public static function isAlumni(): bool
    {
        $u = self::currentUser();
        return $u && ($u['role'] === 'alumni' || $u['role'] === 'both');
    }

    public static function requireLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/pages/auth/login.php");
            exit;
        }
    }
}
