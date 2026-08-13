<?php
session_start();
require_once 'db.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If no pending email in session, redirect to register
if (!isset($_SESSION['pending_email'])) {
    header('Location: register.php');
    exit;
}

$email  = $_SESSION['pending_email'];
$error  = '';
$success = '';

// ── RESEND OTP ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend') {

    // Rate-limit: only allow resend every 60 seconds
    if (isset($_SESSION['otp_last_sent']) && (time() - $_SESSION['otp_last_sent']) < 60) {
        $wait = 60 - (time() - $_SESSION['otp_last_sent']);
        $error = "Please wait {$wait} seconds before requesting a new code.";
    } else {
        // Fetch existing pending record to make sure it exists
        $stmt = $con->prepare('SELECT id FROM pending_registrations WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        if (!$exists) {
            unset($_SESSION['pending_email']);
            header('Location: register.php');
            exit;
        }

        // Generate new OTP and refresh expiry
        $otp        = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $stmt = $con->prepare('UPDATE pending_registrations SET otp = ?, expires_at = ? WHERE email = ?');
        $stmt->bind_param('sss', $otp, $expires_at, $email);
        $stmt->execute();
        $stmt->close();

        // Send email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'mail.kzlabs.store';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'noreply@kzlabs.store';
            $mail->Password   = '^^N670#5&c72#5$*6&!';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->setFrom('noreply@kzlabs.store', 'CodeShack');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your New Verification Code - CodeShack';
            $mail->Body    = '
            <div style="font-family:Arial,sans-serif;max-width:480px;margin:0 auto;background:#fff;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;">
                <div style="background:#3474e6;padding:24px;text-align:center;">
                    <h2 style="color:#fff;margin:0;font-size:22px;">Email Verification</h2>
                </div>
                <div style="padding:32px;text-align:center;">
                    <p style="color:#444;font-size:15px;margin-bottom:8px;">Here is your new verification code.</p>
                    <p style="color:#888;font-size:13px;margin-bottom:24px;">This code expires in <strong>15 minutes</strong>.</p>
                    <div style="background:#f4f7ff;border:2px dashed #3474e6;border-radius:8px;padding:20px;display:inline-block;margin-bottom:24px;">
                        <span style="font-size:38px;font-weight:bold;letter-spacing:10px;color:#3474e6;">' . $otp . '</span>
                    </div>
                    <p style="color:#aaa;font-size:12px;">If you did not request this, please ignore this email.</p>
                </div>
            </div>';
            $mail->AltBody = "Your new CodeShack verification code is: $otp\nThis code expires in 15 minutes.";
            $mail->send();

            $_SESSION['otp_last_sent'] = time();
            $success = 'A new code has been sent to your email.';

        } catch (Exception $e) {
            $error = 'Failed to resend email. Please try again.';
        }
    }
}

// ── VERIFY OTP ────────────────────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = isset($_POST['otp']) ? trim($_POST['otp']) : '';

    if (strlen($submitted) !== 6 || !ctype_digit($submitted)) {
        $error = 'Please enter a valid 6-digit code.';
    } else {
        $stmt = $con->prepare('SELECT username, password, otp, expires_at FROM pending_registrations WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($username, $password, $stored_otp, $expires_at);

        if (!$stmt->fetch()) {
            $stmt->close();
            $error = 'No pending registration found. Please <a href="register.php">register again</a>.';
        } else {
            $stmt->close();

            if (new DateTime() > new DateTime($expires_at)) {
                $con->query("DELETE FROM pending_registrations WHERE email = '" . $con->real_escape_string($email) . "'");
                unset($_SESSION['pending_email']);
                $error = 'Your code has expired. Please <a href="register.php">register again</a>.';
            } elseif ($submitted !== $stored_otp) {
                $error = 'Incorrect code. Please check your email and try again.';
            } else {
                $registered = date('Y-m-d H:i:s');
                $stmt = $con->prepare('INSERT INTO accounts (username, password, email, registered) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssss', $username, $password, $email, $registered);

                if ($stmt->execute()) {
                    $new_id = $con->insert_id;
                    $stmt->close();
                    $con->query("DELETE FROM pending_registrations WHERE email = '" . $con->real_escape_string($email) . "'");
                    unset($_SESSION['pending_email']);
                    unset($_SESSION['otp_last_sent']);

                    session_regenerate_id();
                    $_SESSION['account_loggedin'] = TRUE;
                    $_SESSION['account_name']     = $username;
                    $_SESSION['account_id']       = $new_id;

                    header('Location: home.php');
                    exit;
                } else {
                    $stmt->close();
                    $error = 'Could not create account. Please try again.';
                }
            }
        }
    }
}

// Compute how many seconds until resend is allowed
$resend_wait = 0;
if (isset($_SESSION['otp_last_sent'])) {
    $elapsed = time() - $_SESSION['otp_last_sent'];
    $resend_wait = max(0, 60 - $elapsed);
}

$email_display = htmlspecialchars($email);
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,minimum-scale=1">
        <title>Verify Email</title>
        <link href="style.css" rel="stylesheet" type="text/css">
        <style>
            .otp-inputs {
                display: flex;
                gap: 10px;
                justify-content: center;
                margin: 8px 0 20px;
            }
            .otp-inputs input {
                width: 48px;
                height: 56px;
                text-align: center;
                font-size: 24px;
                font-weight: bold;
                border: 2px solid #dde3ef;
                border-radius: 8px;
                color: #2d3a5e;
                background: #f8faff;
                outline: none;
                transition: border-color .2s;
                caret-color: transparent;
            }
            .otp-inputs input:focus { border-color: #3474e6; background: #fff; }
            .otp-inputs input.error-box { border-color: #e05454; background: #fff5f5; }
            .otp-note {
                text-align: center;
                font-size: 13px;
                color: #888;
                margin-bottom: 18px;
            }
            .otp-note strong { color: #3474e6; display: block; margin-top: 4px; }
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
            .form-error a { color: #c0392b; font-weight: 600; }
            .form-success {
                background: #f0fff4;
                border: 1px solid #b7ebc8;
                color: #1e7e44;
                border-radius: 6px;
                padding: 10px 14px;
                font-size: 13px;
                margin-bottom: 16px;
                text-align: center;
            }
            .resend-area {
                text-align: center;
                margin-top: 14px;
                font-size: 13px;
                color: #888;
            }
            .btn-resend {
                background: none;
                border: none;
                color: #3474e6;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                padding: 0;
                text-decoration: none;
            }
            .btn-resend:hover { text-decoration: underline; }
            .btn-resend:disabled {
                color: #aaa;
                cursor: not-allowed;
                text-decoration: none;
            }
            #countdown { font-weight: 600; color: #3474e6; }
        </style>
    </head>
    <body>
        <div class="login">
            <h1>Verify Your Email</h1>

            <!-- Verify form -->
            <form action="verify-otp.php" method="post" class="form login-form" id="otp-form">

                <p class="otp-note">
                    We sent a 6-digit code to
                    <strong><?= $email_display ?></strong>
                </p>

                <?php if ($error): ?>
                    <div class="form-error"><?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="form-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <div class="otp-inputs">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                    <input type="text" maxlength="1" class="otp-box<?= $error ? ' error-box' : '' ?>"
                           inputmode="numeric" pattern="[0-9]*" autocomplete="off" id="otp<?= $i ?>">
                    <?php endfor; ?>
                </div>

                <input type="hidden" name="otp" id="otp-hidden">
                <button class="btn blue" type="submit">Verify Email</button>
            </form>

            <!-- Resend form (separate so it does not conflict with OTP submit) -->
            <form action="verify-otp.php" method="post" id="resend-form">
                <input type="hidden" name="action" value="resend">
                <div class="resend-area">
                    Did not receive it?
                    <button type="submit" class="btn-resend" id="resend-btn"
                        <?= $resend_wait > 0 ? 'disabled' : '' ?>>
                        Resend Code<?= $resend_wait > 0 ? ' (<span id="countdown">' . $resend_wait . '</span>s)' : '' ?>
                    </button>
                </div>
            </form>
        </div>

        <script>
            // ── OTP box logic ──────────────────────────────────────────────
            const boxes = document.querySelectorAll('.otp-box');
            const hiddenOtp = document.getElementById('otp-hidden');
            const form = document.getElementById('otp-form');

            boxes.forEach((box, index) => {
                box.addEventListener('input', (e) => {
                    e.target.value = e.target.value.replace(/\D/g, '');
                    boxes.forEach(b => b.classList.remove('error-box'));
                    if (e.target.value && index < boxes.length - 1) boxes[index + 1].focus();
                    updateHidden();
                });
                box.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !box.value && index > 0) boxes[index - 1].focus();
                });
                box.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                    pasted.split('').slice(0, 6).forEach((char, i) => { if (boxes[i]) boxes[i].value = char; });
                    boxes.forEach(b => b.classList.remove('error-box'));
                    const nextEmpty = [...boxes].findIndex(b => !b.value);
                    if (nextEmpty !== -1) boxes[nextEmpty].focus(); else boxes[5].focus();
                    updateHidden();
                });
            });

            function updateHidden() {
                hiddenOtp.value = [...boxes].map(b => b.value).join('');
            }

            form.addEventListener('submit', (e) => {
                updateHidden();
                if (hiddenOtp.value.length !== 6) {
                    e.preventDefault();
                    boxes.forEach(b => b.classList.add('error-box'));
                    boxes[0].focus();
                }
            });

            boxes[0].focus();

            // ── Resend countdown ───────────────────────────────────────────
            const resendBtn   = document.getElementById('resend-btn');
            const countdownEl = document.getElementById('countdown');
            let remaining     = <?= (int)$resend_wait ?>;

            if (remaining > 0) {
                const timer = setInterval(() => {
                    remaining--;
                    if (countdownEl) countdownEl.textContent = remaining;
                    if (remaining <= 0) {
                        clearInterval(timer);
                        resendBtn.disabled = false;
                        resendBtn.innerHTML = 'Resend Code';
                    }
                }, 1000);
            }
        </script>
    </body>
</html>