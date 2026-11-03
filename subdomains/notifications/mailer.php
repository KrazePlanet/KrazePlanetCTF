<?php
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/../../config/mail.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendApplicationConfirmation($toEmail, $first, $last, $experience) {
    $mail = new PHPMailer(true);
    try {
        configureKrazeMailer($mail, null, 'HackerOne');
        $mail->addReplyTo('noreply@hackerone.com', 'HackerOne');
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Your HackerOne Pentest Community Application';
        $mail->Body    = renderConfirmBody($first, $last, $experience); // ⚠ first/last raw = the vuln
        $mail->AltBody = "Thank you for applying, $first $last! We will review your application within 5-7 business days.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Notifications mailer: " . $mail->ErrorInfo);
        return false;
    }
}
