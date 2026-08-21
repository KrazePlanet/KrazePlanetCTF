<?php
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['h101_user_id'])) {
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
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'A CTF account with this username or email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt_ins = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt_ins->execute([$username, $email, $hashed]);
            $user_id = $pdo->lastInsertId();

            $_SESSION['h101_user_id'] = $user_id;
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
    <title>Sign Up — Hacker101 CTF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
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
            <a href="register.php" class="ctf-brand">Hacker101 <span>CTF</span></a>
            <h5 class="fw-bold mb-1">Create your CTF Player Account</h5>
            <p class="text-secondary small mb-4">Solve challenges, earn flags, and win private program invitations.</p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="mb-3">
                <label class="form-label small fw-bold mb-1">Username</label>
                <input type="text" name="username" class="form-control" placeholder="dropper" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold mb-1">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="player@secops.io" required>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold mb-1">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-ctf mb-3">Sign Up &rarr;</button>

            <div class="text-center small text-secondary">
                Already have a CTF account? <a href="login.php" class="text-primary text-decoration-none fw-bold">Log in</a>
            </div>
        </form>
    </div>

</body>
</html>
