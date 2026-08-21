<?php
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['urban_user_id'])) {
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
            $_SESSION['urban_user_id'] = $user['id'];
            header("Location: index.php");
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in — Urban Dictionary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@400;600;700;900&display=swap" rel="stylesheet">
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
            <a href="login.php" class="urban-logo">urban <span>dictionary</span></a>
            <h4 class="fw-bold mb-1 text-white">Sign in to Urban Dictionary</h4>
            <p class="text-secondary small mb-4">Vote on definitions and contribute to the dictionary.</p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3 border-0" style="background:#7f1d1d; color:#fecaca;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label class="form-label small fw-bold text-light mb-1">Username or Email</label>
                <input type="text" name="username_or_email" class="form-control" placeholder="cablej" required autofocus>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-light mb-1">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-urban mb-3">Sign In &rarr;</button>

            <div class="text-center small text-secondary">
                Don't have an account? <a href="register.php" class="text-decoration-none fw-bold" style="color:#efff00;">Sign up</a>
            </div>
        </form>
    </div>

</body>
</html>
