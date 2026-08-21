<?php
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['omise_user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['omise_user_id'] = $user['id'];
            header("Location: index.php");
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in to Omise Dashboard — Omise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .brand-logo {
            font-size: 28px;
            font-weight: 800;
            color: #1a56db;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 24px;
        }

        .btn-omise {
            background-color: #1a56db;
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            padding: 12px;
            border-radius: 6px;
            border: none;
            width: 100%;
            transition: background 0.15s;
        }

        .btn-omise:hover {
            background-color: #1e429f;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <center>
            <a href="login.php" class="brand-logo">omise</a>
            <h4 class="fw-bold mb-1">Sign in to Dashboard</h4>
            <p class="text-secondary small mb-4">Enter your credentials to access your Omise workspace.</p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label class="form-label small fw-bold text-dark mb-1">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@company.com" required autofocus>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label small fw-bold text-dark mb-0">Password</label>
                    <a href="#" class="small text-primary text-decoration-none">Forgot password?</a>
                </div>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-omise mb-3">Sign In &rarr;</button>

            <div class="text-center small text-secondary">
                Don't have an account? <a href="register.php" class="text-primary text-decoration-none fw-bold">Sign up</a>
            </div>
        </form>
    </div>

</body>
</html>
