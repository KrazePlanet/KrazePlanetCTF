<?php
session_start();
// If the user is logged in, redirect to the home page
if (isset($_SESSION['account_loggedin'])) {
    header('Location: home.php');
    exit;
}

require_once 'db.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$error = '';
$username_val = '';
$email_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_val = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email_val    = isset($_POST['email'])    ? trim($_POST['email'])    : '';
    $password_raw = isset($_POST['password']) ? $_POST['password']      : '';

    // Validate fields
    if (empty($username_val) || empty($password_raw) || empty($email_val)) {
        $error = 'Please complete all fields.';
    } elseif (!filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (preg_match('/^[a-zA-Z0-9]+$/', $username_val) == 0) {
        $error = 'Username may only contain letters and numbers.';
    } elseif (strlen($password_raw) < 5 || strlen($password_raw) > 20) {
        $error = 'Password must be between 5 and 20 characters.';
    } else {
        // Check if username or email already exists
        $stmt = $con->prepare('SELECT id FROM accounts WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $username_val, $email_val);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'That username or email is already registered.';
        }
        $stmt->close();

        if (!$error) {
            $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);
            $otp           = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires_at    = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Upsert pending registration
            $stmt = $con->prepare('REPLACE INTO pending_registrations (username, password, email, otp, expires_at) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssss', $username_val, $password_hash, $email_val, $otp, $expires_at);
            if (!$stmt->execute()) {
                $error = 'Could not save registration. Please try again.';
            }
            $stmt->close();

            if (!$error) {
                // Send OTP email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host        = 'mailpit';
                    $mail->SMTPAuth    = false;
                    $mail->Port        = 1025;
                    $mail->SMTPSecure  = '';
                    $mail->SMTPAutoTLS = false;
                    $mail->Timeout     = 3;
                    $mail->setFrom('noreply@codeshack.io', 'CodeShack');
                    $mail->addAddress($email_val, $username_val);
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Verification Code - CodeShack';
                    $mail->Body    = '
                    <div style="font-family:Arial,sans-serif;max-width:480px;margin:0 auto;background:#fff;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;">
                        <div style="background:#3474e6;padding:24px;text-align:center;">
                            <h2 style="color:#fff;margin:0;font-size:22px;">Email Verification</h2>
                        </div>
                        <div style="padding:32px;text-align:center;">
                            <p style="color:#444;font-size:15px;margin-bottom:8px;">Hi <strong>' . htmlspecialchars($username_val) . '</strong>, use the code below to verify your email address.</p>
                            <p style="color:#888;font-size:13px;margin-bottom:24px;">This code expires in <strong>15 minutes</strong>.</p>
                            <div style="background:#f4f7ff;border:2px dashed #3474e6;border-radius:8px;padding:20px;display:inline-block;margin-bottom:24px;">
                                <span style="font-size:38px;font-weight:bold;letter-spacing:10px;color:#3474e6;">' . $otp . '</span>
                            </div>
                            <p style="color:#aaa;font-size:12px;">If you did not request this, please ignore this email.</p>
                        </div>
                    </div>';
                    $mail->AltBody = "Your CodeShack verification code is: $otp\nThis code expires in 15 minutes.";
                    $mail->send();

                    $_SESSION['pending_email'] = $email_val;
                    header('Location: verify-otp.php');
                    exit;

                } catch (Exception $e) {
                    $con->query("DELETE FROM pending_registrations WHERE email = '" . $con->real_escape_string($email_val) . "'");
                    $error = 'Failed to send verification email. Please check your email address and try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,minimum-scale=1">
        <title>Register</title>
        <link href="style.css" rel="stylesheet" type="text/css">
        <style>
            .form-error {
                background: #fff0f0;
                border: 1px solid #f5c6c6;
                color: #c0392b;
                border-radius: 6px;
                padding: 10px 14px;
                font-size: 13px;
                margin-bottom: 16px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="login">

            <h1>Member Register</h1>

            <form action="register.php" method="post" class="form login-form">

                <?php if ($error): ?>
                    <div class="form-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <label class="form-label" for="username">Username</label>
                <div class="form-group">
                    <svg class="form-icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"/></svg>
                    <input class="form-input<?= $error ? ' input-error' : '' ?>" type="text" name="username" placeholder="Username" id="username" value="<?= htmlspecialchars($username_val) ?>" required>
                </div>

                <label class="form-label" for="email">Email</label>
                <div class="form-group">
                    <svg class="form-icon-left" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 512 512"><path d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48H48zM0 176V384c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V176L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"/></svg>
                    <input class="form-input<?= $error ? ' input-error' : '' ?>" type="email" name="email" placeholder="Email" id="email" value="<?= htmlspecialchars($email_val) ?>" required>
                </div>

                <label class="form-label" for="password">Password</label>
                <div class="form-group mar-bot-5">
                    <svg class="form-icon-left" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 448 512"><path d="M144 144v48H304V144c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192V144C80 64.5 144.5 0 224 0s144 64.5 144 144v48h16c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H80z"/></svg>
                    <input class="form-input<?= $error ? ' input-error' : '' ?>" type="password" name="password" placeholder="Password" id="password" autocomplete="new-password" required>
                </div>

                <button class="btn blue" type="submit">Register</button>

                <p class="register-link">Already have an account? <a href="index.php" class="form-link">Login</a></p>

            </form>

        </div>
    </body>
</html>