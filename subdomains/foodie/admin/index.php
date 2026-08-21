<?php
session_start();
require_once dirname(__DIR__) . '/includes/config.php';

$error = '';
$success = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = md5($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE (username = ? OR email = ?) AND password = ?");
    $stmt->execute([$username, $username, $password]);
    $admin = $stmt->fetch();

    if ($admin) {
        $_SESSION['foodie_admin'] = $admin['username'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid administrator username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie Admin Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&family=Shadows+Into+Light&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #f84525;
            --primary-dark: #d63314;
            --bg-dark: #121824;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Rubik', sans-serif; }
        body {
            background: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text-main);
        }
        .login-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.08);
            position: relative;
            overflow: hidden;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, #f84525, #ff8c00);
        }
        .logo-wrap {
            text-align: center;
            margin-bottom: 24px;
        }
        .logo-text {
            font-size: 32px;
            font-weight: 800;
            color: #fff;
            text-decoration: none;
        }
        .logo-text span { color: var(--primary); }
        .badge-admin {
            display: inline-block;
            background: rgba(248, 69, 37, 0.15);
            color: var(--primary);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 20px;
            margin-top: 6px;
            border: 1px solid rgba(248,69,37,0.3);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            outline: none;
            transition: all 0.2s;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(248,69,37,0.2);
        }
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        .footer-note {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: var(--text-muted);
        }
        .footer-note a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .hint-box {
            background: rgba(255,255,255,0.04);
            padding: 10px;
            border-radius: 6px;
            font-size: 12px;
            color: #94a3b8;
            margin-top: 15px;
            text-align: center;
        }
        .hint-box code { color: #f84525; font-weight: bold; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo-wrap">
        <a href="../index.php" class="logo-text">Foodie<span>.</span></a>
        <br>
        <span class="badge-admin">Executive Command Center</span>
    </div>

    <?php if(!empty($error)): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="username">Admin Username / Email</label>
            <input type="text" id="username" name="username" placeholder="admin" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" name="login" class="btn-submit">Sign In to Dashboard</button>
    </form>

    <div class="hint-box">
        Default Access: <code>admin</code> / <code>admin</code>
    </div>

    <div class="footer-note">
        <a href="../index.php">← Back to Main Restaurant Website</a>
    </div>
</div>

</body>
</html>
