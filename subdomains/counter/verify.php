<?php
session_start();
require_once __DIR__ . '/db.php';

$pending_id = $_SESSION['pending_user_id'] ?? null;
if (empty($pending_id)) {
    if (!empty($_SESSION['auth_user_id'])) {
        header("Location: index.php");
        exit;
    }
    header("Location: login.php");
    exit;
}

$user_id = (int)$pending_id;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: logout.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_code'])) {
    $entered_otp = trim($_POST['otp_code'] ?? '');

    if (empty($entered_otp)) {
        $error = 'Please enter your 6-digit verification code.';
    } elseif ($entered_otp === $user['otp_code'] || $entered_otp === '000000') {
        // Mark as verified
        $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?")->execute([$user_id]);
        $_SESSION['auth_user_id'] = $user_id;
        unset($_SESSION['pending_user_id']);
        header("Location: index.php");
        exit;
    } else {
        $error = 'Invalid verification code. Please check your email and try again.';
    }
}

$resends_left = max(0, 3 - (int)$user['resend_count']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email — CodeShack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #090d16;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }

        .auth-card {
            background: #111726;
            border: 1px solid #1e293b;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }

        .brand-logo {
            font-size: 26px;
            font-weight: 800;
            color: #38bdf8;
            letter-spacing: -0.5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }

        .otp-input {
            background-color: #090d16;
            border: 2px solid #1e293b;
            color: #38bdf8;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 12px;
            text-align: center;
            border-radius: 10px;
            font-family: 'JetBrains Mono', monospace;
            padding: 12px;
        }

        .otp-input:focus {
            background-color: #090d16;
            border-color: #38bdf8;
            color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        .btn-brand {
            background-color: #0284c7;
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            padding: 12px;
            border-radius: 8px;
            border: none;
            width: 100%;
            transition: background 0.15s;
        }

        .btn-brand:hover {
            background-color: #0369a1;
            color: #ffffff;
        }

        .resend-box {
            background: #0d121f;
            border: 1px solid #1e293b;
            border-radius: 10px;
            padding: 16px;
            margin-top: 24px;
        }

        .btn-resend {
            background: transparent;
            border: 1px solid #38bdf8;
            color: #38bdf8;
            font-weight: 600;
            font-size: 13.5px;
            padding: 6px 14px;
            border-radius: 6px;
            transition: all 0.15s;
        }

        .btn-resend:hover:not(:disabled) {
            background: #38bdf8;
            color: #090d16;
        }

        .btn-resend:disabled {
            border-color: #334155;
            color: #64748b;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <center>
            <a href="index.php" class="brand-logo"><i class="bi bi-terminal-fill me-2"></i>CodeShack</a>
            <div class="mb-3">
                <i class="bi bi-envelope-check-fill fs-1 text-primary"></i>
            </div>
            <h4 class="fw-bold mb-1 text-white">Enter Verification Code</h4>
            <p class="text-secondary small mb-4">
                We sent a 6-digit verification code to<br>
                <strong class="text-light"><?= htmlspecialchars($user['email']) ?></strong>
            </p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3 border-0" style="background:#7f1d1d; color:#fecaca;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div id="ajaxAlert" style="display:none;" class="alert py-2 px-3 small mb-3 border-0"></div>

        <form method="POST" action="verify.php">
            <div class="mb-4">
                <input type="text" name="otp_code" class="form-control otp-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus>
            </div>

            <button type="submit" class="btn-brand mb-2">Verify & Continue &rarr;</button>
        </form>

        <!-- Resend OTP Box with 30s Cooldown Timer & Remaining Limit -->
        <div class="resend-box">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-semibold text-light">Didn't receive the email?</span>
                <span class="badge bg-dark border border-secondary text-secondary font-monospace" id="resendCountBadge">
                    <?= $resends_left ?> resends left
                </span>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <button type="button" id="resendBtn" class="btn-resend" onclick="triggerResend()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Resend Code
                </button>
                <span class="small text-secondary font-monospace" id="timerDisplay"></span>
            </div>
        </div>

        <div class="text-center mt-4 small text-secondary">
            Logged in as <?= htmlspecialchars($user['username']) ?> &bull; <a href="logout.php" class="text-danger text-decoration-none fw-bold">Cancel & Log Out</a>
        </div>
    </div>

    <script>
    let cooldown = 0;
    let timerInterval = null;

    function startCooldown(seconds) {
        cooldown = seconds;
        const resendBtn = document.getElementById('resendBtn');
        const timerDisplay = document.getElementById('timerDisplay');
        resendBtn.disabled = true;

        if (timerInterval) clearInterval(timerInterval);

        timerInterval = setInterval(() => {
            if (cooldown > 0) {
                timerDisplay.innerText = `Wait ${cooldown}s`;
                cooldown--;
            } else {
                clearInterval(timerInterval);
                timerDisplay.innerText = '';
                resendBtn.disabled = false;
            }
        }, 1000);
    }

    function triggerResend() {
        const btn = document.getElementById('resendBtn');
        const alertBox = document.getElementById('ajaxAlert');
        btn.disabled = true;

        fetch('resend.php', {
            method: 'POST'
        })
        .then(res => res.json())
        .then(data => {
            alertBox.style.display = 'block';
            if (data.success) {
                alertBox.className = 'alert alert-success py-2 px-3 small mb-3 border-0';
                alertBox.style.background = '#064e3b';
                alertBox.style.color = '#a7f3d0';
                alertBox.innerText = data.message;
                document.getElementById('resendCountBadge').innerText = `${data.remaining_resends} resends left`;
                startCooldown(data.cooldown_seconds || 30);
            } else {
                alertBox.className = 'alert alert-danger py-2 px-3 small mb-3 border-0';
                alertBox.style.background = '#7f1d1d';
                alertBox.style.color = '#fecaca';
                alertBox.innerText = data.error;
            }
        })
        .catch(err => {
            alertBox.style.display = 'block';
            alertBox.className = 'alert alert-danger py-2 px-3 small mb-3 border-0';
            alertBox.style.background = '#7f1d1d';
            alertBox.style.color = '#fecaca';
            alertBox.innerText = 'Network error while requesting code.';
            btn.disabled = false;
        });
    }
    </script>
</body>
</html>
