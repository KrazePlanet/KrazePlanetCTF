<?php
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/../../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendUpchieveContactEmail($email, $topic, $message_text) {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    try {
        $mail = new PHPMailer(false);
        configureKrazeMailer($mail, 'noreply@krazeplanet.com', 'UPchieve Support');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = '[UPchieve Support] We received your message: ' . htmlspecialchars($topic);

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; padding: 20px; color: #f8fafc; }
                .card { max-width: 540px; margin: 0 auto; background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 32px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
                .logo { font-size: 24px; font-weight: 800; color: #38bdf8; margin-bottom: 20px; }
                .badge { display: inline-block; background: #0284c7; color: #ffffff; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }
                .msg-box { background: #0f172a; border-left: 3px solid #38bdf8; padding: 14px; margin: 16px 0; border-radius: 4px; font-size: 14px; color: #cbd5e1; }
                .footer { font-size: 11px; color: #64748b; margin-top: 24px; border-top: 1px solid #334155; padding-top: 14px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="logo">UPchieve</div>
                <h3 style="margin-top:0; color:#ffffff;">Thank You for Reaching Out!</h3>
                <p style="color:#94a3b8; font-size: 14px;">Our student support team has received your ticket:</p>
                <div class="badge">Topic: ' . htmlspecialchars($topic) . '</div>
                <div class="msg-box">' . nl2br(htmlspecialchars($message_text)) . '</div>
                <p style="color:#94a3b8; font-size: 13px;">A dedicated UPchieve volunteer coordinator will respond to <strong>' . htmlspecialchars($email) . '</strong> shortly.</p>
                <div class="footer">
                    &copy; 2026 UPchieve Inc. &bull; Free 24/7 Online Tutoring &amp; College Counseling
                </div>
            </div>
        </body>
        </html>';

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}
