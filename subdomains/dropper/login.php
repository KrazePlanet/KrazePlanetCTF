<?php
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['h101_user_id'])) {
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
            $_SESSION['h101_user_id'] = $user['id'];
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
    <title>Log In — Hacker101 CTF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #2b303a;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }

        .ctf-card {
            background: #ffffff;
            color: #212529;
            border-radius: 8px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .ctf-brand {
            font-size: 26px;
            font-weight: 900;
            color: #00ea64;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }

        .ctf-brand span {
            color: #212529;
        }

        .btn-ctf {
            background-color: #007bff;
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            padding: 10px;
            border-radius: 4px;
            border: none;
            width: 100%;
            transition: background 0.15s;
        }

        .btn-ctf:hover {
            background-color: #0056b3;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="ctf-card">
        <center>
            <a href="login.php" class="ctf-brand">Hacker101 <span>CTF</span></a>
            <h5 class="fw-bold mb-1">Log in to Hacker101 CTF</h5>
            <p class="text-secondary small mb-4">Enter your credentials to continue solving challenges.</p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label class="form-label small fw-bold mb-1">Username or Email</label>
                <input type="text" name="username_or_email" class="form-control" placeholder="dropper" required autofocus>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold mb-1">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-ctf mb-3">Log In &rarr;</button>

            <div class="text-center small text-secondary">
                Don't have an account? <a href="register.php" class="text-primary text-decoration-none fw-bold">Sign up</a>
            </div>
        </form>
    </div>

</body>
</html>
