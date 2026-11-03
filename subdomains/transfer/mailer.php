<?php
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/../../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ⚠ $nickname inserted RAW into the email body — intentionally vulnerable
// (stored HTML injection, HackerOne #57914)
function sendWalletInvite($toEmail, $nickname) {
    $mail = new PHPMailer(true);
    try {
        configureKrazeMailer($mail, null, 'Romit');
        $mail->addReplyTo('noreply@romit.io', 'Romit');
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'You have been invited to Romit!';
        $mail->Body    = renderInviteBody($nickname);
        $mail->AltBody = "You have been invited to Romit!\n\n"
            . strip_tags($nickname) . " has shared their wallet with you.\n"
            . "Visit https://romit.io to accept the invitation.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Romit mailer: " . $mail->ErrorInfo);
        return false;
    }
}
