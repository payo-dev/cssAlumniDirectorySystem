<?php
// File: classes/mailer.php

require_once __DIR__ . '/../config.php';

// ---------------------------------------------------------
// LOAD PHPMAILER
// ---------------------------------------------------------
$path = __DIR__ . '/../PHPMailer/PHPMailer-master/PHPMailer-master/src/';

if (!file_exists($path . 'PHPMailer.php')) {
    // Fallback if folder structure is different
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        die("Error: Could not find PHPMailer files. Please check the folder structure.");
    }
} else {
    require_once $path . 'Exception.php';
    require_once $path . 'PHPMailer.php';
    require_once $path . 'SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);

        try {
            // SERVER SETTINGS
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com';
            $this->mail->SMTPAuth   = true;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = 587;

            // CREDENTIALS
            $this->mail->Username   = 'mathewpayopelin.payo.dev@gmail.com';
            $this->mail->Password   = 'csmz bhta oltn ctik'; // Your App Password

            // SENDER INFO
            $this->mail->setFrom('mathewpayopelin.payo.dev@gmail.com', 'WMSU Alumni System');
            $this->mail->isHTML(true);

        } catch (Exception $e) {
            error_log("Mailer Setup Error: " . $this->mail->ErrorInfo);
        }
    }

    // FUNCTION 1: Registration Verification
    public function sendVerificationEmail($toEmail, $token) {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '/cssAlumniDirectorySystem';
        // LINK to setupPassword.php
        $link = "http://localhost" . $baseUrl . "/pages/auth/setupPassword.php?token=" . $token;

        $subject = "Complete Your Registration - WMSU Alumni";
        $body    = "
            <h3>Welcome to WMSU Alumni Tracking System!</h3>
            <p>Your alumni record has been verified.</p>
            <p>Please click the button below to <strong>set your password</strong> and activate your account:</p>
            <br>
            <p><a href='$link' style='background:#b30000; color:white; padding:12px 20px; text-decoration:none; border-radius:5px; font-weight:bold;'>Activate Account</a></p>
            <br>
            <p><small>If you did not request this, please ignore this email.</small></p>
        ";

        return $this->send($toEmail, $subject, $body);
    }

    // FUNCTION 2: Password Reset
    public function sendPasswordResetEmail($toEmail, $token) {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '/cssAlumniDirectorySystem';
        $link = "http://localhost" . $baseUrl . "/pages/auth/resetPassword.php?token=" . $token;

        $subject = "Reset Your Password";
        $body    = "
            <h3>Password Reset Request</h3>
            <p>Click the link below to reset your password:</p>
            <p><a href='$link' style='background:#b30000; color:white; padding:10px 15px; text-decoration:none; border-radius:5px;'>Reset Password</a></p>
            <p><small>This link expires in 1 hour.</small></p>
        ";

        return $this->send($toEmail, $subject, $body);
    }

    // INTERNAL SEND FUNCTION
    private function send($to, $subject, $body) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail Send Error: " . $this->mail->ErrorInfo);
            return false;
        }
    }
}
?>