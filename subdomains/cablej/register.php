<?php
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['urban_user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'An account with this username or email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt_ins = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt_ins->execute([$username, $email, $hashed]);
            $user_id = $pdo->lastInsertId();

            $_SESSION['urban_user_id'] = $user_id;
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
    <title>Sign Up — Urban Dictionary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@400;600;700;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Source Sans Pro', sans-serif;
            background-color: #131414;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }

        .urban-card {
            background: #1d2436;
            border-radius: 8px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            border: 1px solid #2d3748;
        }

        .urban-logo {
            font-size: 26px;
            font-weight: 900;
            color: #efff00;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            letter-spacing: -0.5px;
        }

        .urban-logo span {
            color: #ffffff;
        }

        .form-control {
            background-color: #131414;
            border: 1px solid #2d3748;
            color: #ffffff;
            font-size: 15px;
        }

        .form-control:focus {
            background-color: #131414;
            border-color: #efff00;
            color: #ffffff;
            box-shadow: 0 0 0 2px rgba(239, 255, 0, 0.2);
        }

        .btn-urban {
            background-color: #1b85f2;
            color: #ffffff;
            font-weight: 700;
            font-size: 16px;
            padding: 10px;
            border-radius: 4px;
            border: none;
            width: 100%;
            transition: background 0.15s;
        }

        .btn-urban:hover {
            background-color: #156fc9;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="urban-card">
        <center>
            <a href="register.php" class="urban-logo">urban <span>dictionary</span></a>
            <h4 class="fw-bold mb-1 text-white">Join the Community</h4>
            <p class="text-secondary small mb-4">Define your world, vote on terms, and contribute slang.</p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3 border-0" style="background:#7f1d1d; color:#fecaca;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="mb-3">
                <label class="form-label small fw-bold text-light mb-1">Username</label>
                <input type="text" name="username" class="form-control" placeholder="cablej" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-light mb-1">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="user@urbandict.com" required>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-light mb-1">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-urban mb-3">Sign Up &rarr;</button>

            <div class="text-center small text-secondary">
                Already have an account? <a href="login.php" class="text-decoration-none fw-bold" style="color:#efff00;">Sign in</a>
            </div>
        </form>
    </div>

</body>
</html>
