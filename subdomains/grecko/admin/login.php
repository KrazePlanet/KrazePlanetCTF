<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

$error = '';
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['grecko_admin_id'] = $admin['id'];
        $_SESSION['grecko_admin_name'] = $admin['name'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grecko Restaurant — Staff & Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Montserrat', sans-serif; }
        body {
            background: linear-gradient(135deg, #091726, #112d4e);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #ffffff;
        }
        .login-box {
            background: #ffffff;
            color: #112d4e;
            border-radius: 16px;
            max-width: 420px;
            width: 100%;
            padding: 40px 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            text-align: center;
        }
        .logo-title { font-family: 'Cinzel', serif; font-size: 26px; font-weight: 800; letter-spacing: 2px; color: #112d4e; margin-bottom: 4px; }
        .logo-sub { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #3f72af; font-weight: 700; margin-bottom: 24px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; font-weight: 600; }
        .form-group { text-align: left; margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 6px; color: #475569; }
        .form-control { width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; }
        .form-control:focus { border-color: #3f72af; }
        .btn-submit {
            width: 100%;
            background: #112d4e;
            color: #ffffff;
            border: none;
            padding: 13px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: background 0.2s;
            margin-top: 10px;
        }
        .btn-submit:hover { background: #3f72af; }
        .hint-box { margin-top: 20px; padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1 class="logo-title">GRECKO</h1>
        <div class="logo-sub">Bar & Seafood Restaurant Management</div>

        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" class="form-control" placeholder="admin" value="admin" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" value="admin123" required>
            </div>
            <button type="submit" name="login" class="btn-submit">Sign In to Dashboard</button>
        </form>

        <div class="hint-box">
            Default Login: <code>admin</code> / <code>admin123</code>
        </div>
        
        <p style="margin-top: 20px; font-size: 13px;">
            <a href="../index.php" style="color: #3f72af; text-decoration: none; font-weight: 600;">← Back to Restaurant Website</a>
        </p>
    </div>
</body>
</html>
