<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

if (!empty($_SESSION['auth_user_id'])) {
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
            if ($user['is_verified'] == 1) {
                $_SESSION['auth_user_id'] = $user['id'];
                header("Location: index.php");
                exit;
            } else {
                // Not verified -> redirect to verify
                $_SESSION['pending_user_id'] = $user['id'];
                header("Location: verify.php");
                exit;
            }
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
    <title>Sign in — CodeShack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            max-width: 440px;
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

        .form-control {
            background-color: #090d16;
            border: 1px solid #1e293b;
            color: #ffffff;
            font-size: 14px;
            padding: 10px 14px;
            border-radius: 8px;
        }

        .form-control:focus {
            background-color: #090d16;
            border-color: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
        }

        .btn-brand {
            background-color: #0284c7;
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
            background-color: #0369a1;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <center>
            <a href="login.php" class="brand-logo"><i class="bi bi-terminal-fill me-2"></i>CodeShack</a>
            <h4 class="fw-bold mb-1 text-white">Sign in to CodeShack</h4>
            <p class="text-secondary small mb-4">Enter your credentials to access your developer console.</p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3 border-0" style="background:#7f1d1d; color:#fecaca;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label class="form-label small fw-bold text-light mb-1">Username or Email</label>
                <input type="text" name="username_or_email" class="form-control" placeholder="johndoe" required autofocus>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-light mb-1">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••••••" required>
            </div>

            <button type="submit" class="btn-brand mb-3">Sign In &rarr;</button>

            <div class="text-center small text-secondary">
                Don't have an account? <a href="register.php" class="text-primary text-decoration-none fw-bold">Sign up</a>
            </div>
        </form>
    </div>

</body>
</html>
