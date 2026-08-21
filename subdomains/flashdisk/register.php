<?php
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['h1_user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->execute([$username, $email]);
        if ($stmt_check->fetch()) {
            $error = 'A researcher account with this username or email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt_ins = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt_ins->execute([$username, $email, $hashed]);
            $user_id = $pdo->lastInsertId();

            $_SESSION['h1_user_id'] = $user_id;
            header("Location: index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up — HackerOne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
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
            <a href="register.php" class="h1-brand">hacker<span>one</span></a>
            <h4 class="fw-bold mb-1 text-white">Join as a Security Researcher</h4>
            <p class="text-secondary small mb-4">Access private bounty programs and test environments.</p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3 border-0" style="background:#7f1d1d; color:#fecaca;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="mb-3">
                <label class="form-label small fw-bold text-light mb-1">Username</label>
                <input type="text" name="username" class="form-control" placeholder="flashdisk" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-light mb-1">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="hacker@secops.io" required>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-light mb-1">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••••••" required>
            </div>

            <button type="submit" class="btn-h1 mb-3">Create Researcher Account &rarr;</button>

            <div class="text-center small text-secondary">
                Already have an account? <a href="login.php" class="text-indigo text-decoration-none fw-bold" style="color:#818cf8;">Sign in</a>
            </div>
        </form>
    </div>

</body>
</html>
