<?php
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/../../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationOTP($email_val, $username_val, $otp_code) {
    $mail = new PHPMailer(true);
    try {
        configureKrazeMailer($mail, 'noreply@codeshack.io', 'CodeShack');
        $mail->addAddress($email_val, $username_val);
        $mail->isHTML(true);
        $mail->Subject = 'Your Verification Code - CodeShack';
        $mail->Body    = '
        <div style="font-family: -apple-system, BlinkMacSystemFont, Roboto, sans-serif; background:#0f172a; padding:40px 20px; color:#ffffff;">
            <div style="max-width:500px; margin:0 auto; background:#1e293b; border:1px solid #334155; border-radius:12px; padding:32px;">
                <div style="font-size:22px; font-weight:800; color:#38bdf8; margin-bottom:16px;">CodeShack</div>
                <h2 style="font-size:18px; margin-bottom:8px; color:#f8fafc;">Verify your Email Address</h2>
                <p style="color:#94a3b8; font-size:14px; line-height:1.6; margin-bottom:24px;">
                    Hi <strong>' . htmlspecialchars($username_val) . '</strong>,<br>
                    Please use the following 6-digit verification code to complete your account setup. This code is valid for 10 minutes.
                </p>
                <div style="background:#0f172a; border:1px dashed #38bdf8; border-radius:8px; padding:16px; text-align:center; font-size:32px; font-weight:900; letter-spacing:8px; color:#38bdf8; font-family:monospace; margin-bottom:24px;">
                    ' . htmlspecialchars($otp_code) . '
                </div>
                <p style="color:#64748b; font-size:12px; line-height:1.5; margin:0;">
                    If you did not request this verification code, please ignore this email.
                </p>
            </div>
        </div>';
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Fallback logging if SMTP is unreachable
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}
