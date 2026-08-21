<?php
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['dust_user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workspace_name = trim($_POST['workspace_name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $password       = trim($_POST['password'] ?? '');

    if (empty($workspace_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email address already exists.';
        } else {
            $workspace_id = 'w_' . substr(bin2hex(random_bytes(6)), 0, 10);
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt_ins = $pdo->prepare("INSERT INTO users (workspace_name, workspace_id, email, password) VALUES (?, ?, ?, ?)");
            $stmt_ins->execute([$workspace_name, $workspace_id, $email, $hashed]);
            $user_id = $pdo->lastInsertId();

            seedInitialUserSpace($pdo, $user_id);

            $_SESSION['dust_user_id'] = $user_id;
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
    <title>Sign Up — Dust</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0b0d11;
            color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }

        .dust-auth-card {
            background: #14171d;
            border: 1px solid #23272f;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        .dust-logo {
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }

        .form-control {
            background-color: #0b0d11;
            border: 1px solid #23272f;
            color: #ffffff;
            font-size: 14px;
        }

        .form-control:focus {
            background-color: #0b0d11;
            border-color: #3b82f6;
            color: #ffffff;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .btn-dust {
            background-color: #3b82f6;
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            padding: 10px;
            border-radius: 6px;
            border: none;
            width: 100%;
            transition: background 0.15s;
        }

        .btn-dust:hover {
            background-color: #2563eb;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="dust-auth-card">
        <center>
            <a href="register.php" class="dust-logo">dust</a>
            <h4 class="fw-bold mb-1 text-white">Create your Workspace</h4>
            <p class="text-secondary small mb-4">Enterprise custom AI assistants and company knowledge platform.</p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3 border-0" style="background:#7f1d1d; color:#fecaca;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="mb-3">
                <label class="form-label small fw-bold text-light mb-1">Company / Workspace Name</label>
                <input type="text" name="workspace_name" class="form-control" placeholder="Acme AI Labs" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-light mb-1">Work Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="admin@acme.ai" required>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-light mb-1">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••••••" required>
            </div>

            <button type="submit" class="btn-dust mb-3">Create Workspace &rarr;</button>

            <div class="text-center small text-secondary">
                Already have a workspace? <a href="login.php" class="text-primary text-decoration-none fw-bold">Sign in</a>
            </div>
        </form>
    </div>

</body>
</html>
