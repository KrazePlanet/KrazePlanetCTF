<?php
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendPasswordResetEmail($email) {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    try {
        $mail = new PHPMailer(false);
        $mail->isSMTP();
        $mail->Host       = 'mailpit';
        $mail->SMTPAuth   = false;
                $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
        $mail->Port       = 1025;
        $mail->Timeout    = 5;

        $mail->setFrom('noreply@krazeplanet.com', 'WakaTime');
        $mail->addAddress($email);
        $mail->isHTML(true);
        // Subject matching HackerOne #658089 screenshot exactly: [WakaTime] Reset your password
        $mail->Subject = '[WakaTime] Reset your password';
        
        $reset_token = bin2hex(random_bytes(20));
        $reset_link = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/request/reset.php?token=" . $reset_token;

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; padding: 20px; color: #f8fafc; }
                .card { max-width: 540px; margin: 0 auto; background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 32px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
                .logo { font-size: 22px; font-weight: 800; color: #38bdf8; margin-bottom: 20px; }
                .btn { display: inline-block; background: #0284c7; color: #ffffff !important; padding: 12px 24px; border-radius: 6px; font-weight: 700; text-decoration: none; margin: 20px 0; }
                .footer { font-size: 11px; color: #64748b; margin-top: 24px; border-top: 1px solid #334155; padding-top: 16px; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="logo">WakaTime</div>
                <h3 style="margin-top:0; color:#ffffff;">Reset Your WakaTime Password</h3>
                <p style="color:#94a3b8; font-size: 14px;">We received a request to reset your password for your WakaTime developer account (<strong>' . htmlspecialchars($email) . '</strong>).</p>
                <center>
                    <a href="' . $reset_link . '" class="btn">Reset Password &rarr;</a>
                </center>
                <p style="color:#64748b; font-size: 12px;">This password reset link is valid for the next 24 hours. If you did not make this request, please disregard this email.</p>
                <div class="footer">
                    &copy; 2026 WakaTime Inc. &bull; Automated Programming Telemetry Engine
                </div>
            </div>
        </body>
        </html>';

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}
