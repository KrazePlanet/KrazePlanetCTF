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
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_role'] = $admin['role'];
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
    <title>Kitchen Display & POS Admin — Buffet Box</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #ffffff;
        }
        .login-card {
            background: #ffffff;
            color: #0f172a;
            border-radius: 20px;
            max-width: 420px;
            width: 100%;
            padding: 40px 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            text-align: center;
        }
        .login-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f95724, #ff8a65);
            color: #ffffff;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin: 0 auto 16px;
            box-shadow: 0 10px 20px rgba(249, 87, 36, 0.3);
        }
        .login-card h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; margin-bottom: 6px; }
        .login-card p { font-size: 13px; color: #64748b; margin-bottom: 24px; }
        .alert-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }
        .form-group { text-align: left; margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #334155; }
        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-control:focus { border-color: #f95724; }
        .btn-submit {
            width: 100%;
            background: #f95724;
            color: #ffffff;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(249, 87, 36, 0.35);
            transition: background 0.2s;
            margin-top: 10px;
        }
        .btn-submit:hover { background: #e04413; }
        .hint-box {
            margin-top: 20px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-icon"><i class="fas fa-kitchen-set"></i></div>
        <h2>Kitchen & POS Login</h2>
        <p>Enter your management credentials to access live KDS & orders.</p>

        <?php if ($error): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Username / Email</label>
                <input type="text" name="username" class="form-control" placeholder="admin" value="admin" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" value="admin123" required>
            </div>
            <button type="submit" name="login" class="btn-submit">
                <i class="fas fa-arrow-right-to-bracket"></i> Open Kitchen Panel
            </button>
        </form>

        <div class="hint-box">
            <strong>Default Credentials:</strong><br>
            Username: <code>admin</code> | Password: <code>admin123</code>
        </div>
        
        <p style="margin-top: 20px; font-size: 13px;">
            <a href="../index.php" style="color: #f95724; text-decoration: none; font-weight: 600;">← Back to Customer Menu</a>
        </p>
    </div>
</body>
</html>
