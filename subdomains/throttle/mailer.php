<?php
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/../../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendNextcloudConfirmationEmail($email) {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    try {
        $mail = new PHPMailer(false);
        configureKrazeMailer($mail, 'noreply@krazeplanet.com', 'Nextcloud');
        $mail->addAddress($email);
        $mail->isHTML(true);
        // Subject matching HackerOne #224927 Gmail screenshot: Finish your subscription to Nextcloud newsletter
        $mail->Subject = 'Finish your subscription to Nextcloud newsletter';

        $confirm_token = bin2hex(random_bytes(16));
        $confirm_link = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/throttle/confirm.php?token=" . $confirm_token;

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0082c9; padding: 24px; color: #ffffff; }
                .card { max-width: 550px; margin: 0 auto; background: #ffffff; border-radius: 10px; padding: 32px; color: #1e293b; box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
                .btn { display: inline-block; background: #0082c9; color: #ffffff !important; padding: 12px 24px; border-radius: 6px; font-weight: 700; text-decoration: none; margin: 20px 0; }
                .footer { font-size: 11px; color: #64748b; margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 14px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="card">
                <div style="text-align:center; margin-bottom: 20px;">
                    <h2 style="color: #0082c9; margin: 0; font-size: 26px;">Nextcloud</h2>
                </div>
                <h3 style="margin-top:0; color:#0f172a;">Confirm Your Newsletter Subscription</h3>
                <p>Hello,</p>
                <p>You recently requested to subscribe to the <strong>Nextcloud Monthly Community &amp; Security Newsletter</strong> for the email address <code>' . htmlspecialchars($email) . '</code>.</p>
                <p>To finalize your subscription and begin receiving release announcements, please click the confirmation button below:</p>
                <center>
                    <a href="' . $confirm_link . '" class="btn">Confirm Subscription &rarr;</a>
                </center>
                <p style="font-size: 13px; color: #64748b;">If you did not request this subscription, please ignore this email.</p>
                <div class="footer">
                    &copy; 2026 Nextcloud GmbH &bull; Regerstraße 27, D-70195 Stuttgart, Germany
                </div>
            </div>
        </body>
        </html>';

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}
