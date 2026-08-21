<?php
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['h1_user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['username_or_email'] ?? '');
    $password    = trim($_POST['password'] ?? '');

    if (empty($login_input) || empty($password)) {
        $error = 'Please enter your username/email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['h1_user_id'] = $user['id'];
            header("Location: index.php");
            exit;
        } else {
            $error = 'Invalid credentials. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in to HackerOne — HackerOne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0b0f19;
            color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }

        .auth-card {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .h1-brand {
            font-size: 26px;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: -0.5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 24px;
        }

        .h1-brand span {
            color: #4f46e5;
        }

        .form-control {
            background-color: #1f2937;
            border: 1px solid #374151;
            color: #f9fafb;
            font-size: 14px;
        }

        .form-control:focus {
            background-color: #1f2937;
            border-color: #6366f1;
            color: #f9fafb;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }

        .btn-h1 {
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            padding: 12px;
            border-radius: 6px;
            border: none;
            width: 100%;
            transition: background 0.15s;
        }

        .btn-h1:hover {
            background-color: #4338ca;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <center>
            <a href="login.php" class="h1-brand">hacker<span>one</span></a>
            <h4 class="fw-bold mb-1 text-white">Sign in to HackerOne</h4>
            <p class="text-secondary small mb-4">Welcome back! Sign in to view your private programs.</p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3 border-0" style="background:#7f1d1d; color:#fecaca;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label class="form-label small fw-bold text-light mb-1">Username or Email</label>
                <input type="text" name="username_or_email" class="form-control" placeholder="flashdisk" required autofocus>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label small fw-bold text-light mb-0">Password</label>
                    <a href="#" class="small text-decoration-none" style="color:#818cf8;">Forgot password?</a>
                </div>
                <input type="password" name="password" class="form-control" placeholder="••••••••••••" required>
            </div>

            <button type="submit" class="btn-h1 mb-3">Sign In &rarr;</button>

            <div class="text-center small text-secondary">
                New to HackerOne? <a href="register.php" class="text-decoration-none fw-bold" style="color:#818cf8;">Create an account</a>
            </div>
        </form>
    </div>

</body>
</html>
