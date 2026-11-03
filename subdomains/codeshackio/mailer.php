<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/../../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendActivationEmail($email, $username, $activation_code) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(dirname($_SERVER['PHP_SELF']), '/');
    $link   = $scheme . '://' . $host . $dir . '/activate.php?code=' . urlencode($activation_code);

    $mail = new PHPMailer(true);
    try {
        configureKrazeMailer($mail, null, 'CodeShack');
        $mail->addReplyTo('noreply@codeshack.io', 'CodeShack');
        $mail->addAddress($email, $username);
        $mail->isHTML(true);
        $mail->Subject = 'Activate Your Account - CodeShack';
        $mail->Body = '
        <div style="font-family:-apple-system,BlinkMacSystemFont,Roboto,sans-serif;background:#f3f5f7;padding:40px 20px;">
            <div style="max-width:500px;margin:0 auto;background:#ffffff;border:1px solid #dee1e6;border-radius:5px;padding:35px;">
                <h2 style="color:#474b50;font-size:22px;font-weight:500;margin:0 0 20px 0;">Activate Your Account</h2>
                <p style="color:#6b7179;font-size:14px;line-height:1.6;margin:0 0 20px 0;">
                    Hi <strong>' . htmlspecialchars($username) . '</strong>,<br>
                    Thank you for registering on CodeShack. Click the button below to activate your account.
                </p>
                <a href="' . htmlspecialchars($link) . '" style="display:inline-block;background:#3e7bd6;color:#ffffff;padding:10px 20px;border-radius:4px;text-decoration:none;font-size:14px;font-weight:600;">Activate Account</a>
                <p style="color:#989fa8;font-size:12px;margin:20px 0 0 0;">
                    Or copy this link: ' . htmlspecialchars($link) . '
                </p>
                <p style="color:#989fa8;font-size:12px;margin:12px 0 0 0;">
                    If you did not create an account, you can ignore this email.
                </p>
            </div>
        </div>';
        $mail->AltBody = "Hi " . $username . ",\n\nActivate your CodeShack account:\n" . $link . "\n\nIf you did not register, ignore this email.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}
