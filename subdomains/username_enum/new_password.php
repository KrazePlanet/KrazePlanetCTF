<?php
session_start();
require_once __DIR__ . '/db.php';

$user_id = $_SESSION['verified_reset_user_id'] ?? null;
if (empty($user_id)) {
    header("Location: forgot.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    if (empty($password) || empty($confirm)) {
        $error = 'Please enter and confirm your new password.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt_upd = $pdo->prepare("UPDATE users SET password = ?, reset_otp = NULL, reset_otp_expiry = NULL WHERE id = ?");
        $stmt_upd->execute([$hashed, $user_id]);

        unset($_SESSION['verified_reset_user_id']);
        unset($_SESSION['reset_email']);

        header("Location: login.php?reset=success");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password — UPchieve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

        .form-control {
            border: 1px solid #cbd5e1;
            font-size: 14px;
            padding: 10px 14px;
            border-radius: 8px;
        }

        .form-control:focus {
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
            <h4 class="fw-bold mb-1 text-dark">Create New Password</h4>
            <p class="text-secondary small mb-4">Please choose a strong password for your account.</p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="new_password.php">
            <div class="mb-3">
                <label class="form-label small fw-bold text-dark mb-1">New Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••••••" required autofocus>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-dark mb-1">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••••••" required>
            </div>

            <button type="submit" class="btn-brand mb-3">Update Password &rarr;</button>
        </form>
    </div>

</body>
</html>
