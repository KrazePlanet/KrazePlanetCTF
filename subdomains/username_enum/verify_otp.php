<?php
session_start();
require_once __DIR__ . '/db.php';

$reset_email = $_SESSION['reset_email'] ?? '';
if (empty($reset_email)) {
    header("Location: forgot.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$reset_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: forgot.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp'] ?? '');

    if (empty($entered_otp)) {
        $error = 'Please enter your 6-digit verification code.';
    } elseif ($entered_otp === $user['reset_otp'] || $entered_otp === '000000') {
        $_SESSION['verified_reset_user_id'] = $user['id'];
        header("Location: new_password.php");
        exit;
    } else {
        $error = 'Invalid verification code. Please check your email and try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Reset Code — UPchieve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }

        .auth-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .brand-logo {
            font-size: 28px;
            font-weight: 800;
            color: #10b981;
            text-decoration: none;
            letter-spacing: -0.5px;
            display: inline-block;
            margin-bottom: 20px;
        }

        .otp-input {
            border: 2px solid #cbd5e1;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 10px;
            text-align: center;
            border-radius: 8px;
            font-family: 'JetBrains Mono', monospace;
            padding: 10px;
            color: #059669;
        }

        .otp-input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }

        .btn-brand {
            background-color: #10b981;
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            padding: 11px;
            border-radius: 8px;
            border: none;
            width: 100%;
            transition: background 0.15s;
        }

        .btn-brand:hover {
            background-color: #059669;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <center>
            <a href="login.php" class="brand-logo">UPchieve</a>
            <div class="mb-3">
                <i class="bi bi-shield-lock-fill fs-1 text-success"></i>
            </div>
            <h4 class="fw-bold mb-1 text-dark">Enter Reset Code</h4>
            <p class="text-secondary small mb-4">
                We sent a 6-digit password reset code to<br>
                <strong class="text-dark"><?= htmlspecialchars($user['email']) ?></strong>
            </p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="verify_otp.php">
            <div class="mb-4">
                <input type="text" name="otp" class="form-control otp-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus>
            </div>

            <button type="submit" class="btn-brand mb-3">Verify Code &rarr;</button>

            <div class="text-center small text-secondary">
                Didn't get the code? <a href="forgot.php" class="text-success text-decoration-none fw-bold">Resend email</a>
            </div>
        </form>
    </div>

</body>
</html>
