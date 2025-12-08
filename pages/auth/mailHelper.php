<?php
// File: classes/mailHelper.php
// Simple wrapper for PHPMailer. Update SMTP settings in config.php as needed.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // adjust if your autoload path differs
require_once __DIR__ . '/../config.php';

class MailHelper {
    public function sendMail(string $toEmail, string $toName, string $subject, string $bodyHtml): bool {
        $mail = new PHPMailer(true);
        try {
            // Server settings - update these in config.php
            // Please set SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM, SMTP_FROM_NAME in config.php
            $mail->isSMTP();
            $mail->Host = SMTP_HOST ?? 'smtp.example.com';
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER ?? 'user@example.com';
            $mail->Password = SMTP_PASS ?? 'secret';
            $mail->SMTPSecure = SMTP_SECURE ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT ?? 587;

            // Recipients
            $mail->setFrom(SMTP_FROM ?? 'no-reply@example.com', SMTP_FROM_NAME ?? 'WMSU Alumni');
            $mail->addAddress($toEmail, $toName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->AltBody = strip_tags($bodyHtml);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("MailHelper sendMail error: " . $mail->ErrorInfo);
            return false;
        }
    }
}