<?php
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendPasswordResetEmail($email_val, $username_val, $otp_code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host        = 'mailpit';
        $mail->SMTPAuth    = false;
        $mail->Port        = 1025;
        $mail->SMTPSecure  = '';
        $mail->SMTPAutoTLS = false;
        $mail->Timeout     = 5;

        $mail->setFrom('noreply@krazeplanet.com', 'UPchieve');
        $mail->addAddress($email_val, $username_val);
        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset Code - UPchieve';
        $mail->Body    = '
        <div style="font-family: -apple-system, BlinkMacSystemFont, Roboto, sans-serif; background:#f8fafc; padding:40px 20px; color:#1e293b;">
            <div style="max-width:520px; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:36px; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                <div style="font-size:24px; font-weight:800; color:#10b981; margin-bottom:16px;">UPchieve</div>
                <h2 style="font-size:18px; margin-bottom:8px; color:#0f172a;">Password Reset Request</h2>
                <p style="color:#64748b; font-size:14px; line-height:1.6; margin-bottom:24px;">
                    Hi <strong>' . htmlspecialchars($username_val) . '</strong>,<br>
                    We received a request to reset your password. Use the 6-digit verification code below to reset your UPchieve password.
                </p>
                <div style="background:#ecfdf5; border:1px dashed #10b981; border-radius:8px; padding:16px; text-align:center; font-size:32px; font-weight:900; letter-spacing:8px; color:#059669; font-family:monospace; margin-bottom:24px;">
                    ' . htmlspecialchars($otp_code) . '
                </div>
                <p style="color:#94a3b8; font-size:12px; line-height:1.5; margin:0;">
                    If you did not request this password reset, please ignore this email or contact support.
                </p>
            </div>
        </div>';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Reset Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}
