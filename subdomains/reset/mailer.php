<?php
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendYelpConfirmationEmail($email, $business_name = "Artisan Bakery & Cafe") {
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

        $mail->setFrom('noreply@krazeplanet.com', 'Yelp for Business Owners');
        $mail->addAddress($email);
        $mail->isHTML(true);
        // Subject matching HackerOne #774050 screenshot: Confirm Your Email Address on Yelp
        $mail->Subject = 'Confirm Your Email Address on Yelp';

        $confirm_token = bin2hex(random_bytes(16));
        $confirm_link = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/reset/confirm.php?token=" . $confirm_token;

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; padding: 20px; color: #333333; }
                .card { max-width: 560px; margin: 0 auto; background: #ffffff; border: 1px solid #e6e6e6; border-radius: 8px; padding: 36px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
                .yelp-red { color: #d32323; font-weight: 800; font-size: 26px; }
                .btn { display: inline-block; background: #d32323; color: #ffffff !important; padding: 12px 26px; border-radius: 4px; font-weight: 700; text-decoration: none; margin: 20px 0; }
                .footer { font-size: 11px; color: #757575; margin-top: 24px; border-top: 1px solid #e6e6e6; padding-top: 14px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="yelp-red">yelp <span style="font-size:16px; color:#333; font-weight:600;">for Business Owners</span></div>
                <h3 style="margin-top:16px; color:#1f2937;">Confirm your email address</h3>
                <p>Hi there,</p>
                <p>Please click below to verify the email address (<strong>' . htmlspecialchars($email) . '</strong>) associated with your Yelp business page for <strong>' . htmlspecialchars($business_name) . '</strong>.</p>
                <center>
                    <a href="' . $confirm_link . '" class="btn">Confirm Email Address &rarr;</a>
                </center>
                <p style="font-size: 13px; color: #6b7280;">Once confirmed, you will unlock customer messaging, review notifications, and page upgrade tools.</p>
                <div class="footer">
                    &copy; 2026 Yelp Inc. &bull; 140 New Montgomery St, San Francisco, CA 94105
                </div>
            </div>
        </body>
        </html>';

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}
