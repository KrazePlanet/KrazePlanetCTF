<?php
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendOmiseInvitationEmail($email, $roles = ['Technical']) {
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

        $mail->setFrom('noreply@krazeplanet.com', 'Omise Payment Gateway');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Invitation to join Acme Payments on Omise';

        $invite_token = bin2hex(random_bytes(16));
        $invite_link = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/condition/accept.php?token=" . $invite_token;
        $role_str = implode(', ', $roles);

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; padding: 20px; color: #1e293b; }
                .card { max-width: 560px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 36px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
                .brand { font-size: 22px; font-weight: 800; color: #1a56db; margin-bottom: 20px; }
                .btn { display: inline-block; background: #1a56db; color: #ffffff !important; padding: 12px 26px; border-radius: 6px; font-weight: 700; text-decoration: none; margin: 20px 0; }
                .footer { font-size: 11px; color: #64748b; margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 14px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="brand">omise</div>
                <h3 style="margin-top:0; color:#0f172a;">You have been invited to join Acme Payments</h3>
                <p>Hello,</p>
                <p>An administrator at <strong>Acme Payments</strong> has invited you to collaborate on Omise Dashboard with the following permissions: <strong>' . htmlspecialchars($role_str) . '</strong>.</p>
                <center>
                    <a href="' . $invite_link . '" class="btn">Accept Invitation &rarr;</a>
                </center>
                <p style="font-size: 12px; color: #64748b;">This invitation link will expire in 7 days. If you did not expect this invitation, please ignore this email.</p>
                <div class="footer">
                    &copy; 2026 Omise Payments Co., Ltd. &bull; Enterprise Payment Infrastructure
                </div>
            </div>
        </body>
        </html>';

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}
