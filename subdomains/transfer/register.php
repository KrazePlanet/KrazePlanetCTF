<?php
session_start();
if (!empty($_SESSION['romit_user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $nickname = trim($_POST['nickname'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($nickname) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $check = $conn->prepare("SELECT id FROM lab57914_users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        $check->store_result();
        $exists = $check->num_rows > 0;
        $check->close();

        if ($exists) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO lab57914_users (email, nickname, password) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $email, $nickname, $hash);
            if ($stmt->execute()) {
                $_SESSION['romit_user_id'] = $stmt->insert_id;
                $stmt->close();
                header('Location: index.php');
                exit;
            } else {
                $stmt->close();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Romit — Create Account</title>
<style>
*,*:before,*:after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f3f4f8;color:#1a1a2e;min-height:100vh;display:flex;flex-direction:column;}
.header{background:#6c3be4;height:56px;display:flex;align-items:center;padding:0 28px;box-shadow:0 2px 10px rgba(108,59,228,.35);}
.header-logo{display:flex;align-items:center;gap:9px;text-decoration:none;}
.logo-mark{width:32px;height:32px;background:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;}
.logo-mark svg{width:20px;height:20px;}
.logo-text{color:#fff;font-size:1.1rem;font-weight:800;letter-spacing:-.02em;}
.auth-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 16px;}
.auth-card{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.09);padding:36px 40px;width:100%;max-width:420px;}
.auth-title{font-size:1.3rem;font-weight:800;color:#1a1a2e;margin-bottom:4px;}
.auth-sub{font-size:.8rem;color:#8a9ab8;margin-bottom:28px;}
.form-label{display:block;font-size:.75rem;font-weight:600;color:#3a4a6a;margin-bottom:5px;}
.form-input{width:100%;padding:10px 12px;border:1.5px solid #dce3ef;border-radius:8px;font-size:.84rem;color:#1a1a2e;background:#fff;outline:none;font-family:inherit;transition:border-color .15s,box-shadow .15s;margin-bottom:16px;}
.form-input:focus{border-color:#6c3be4;box-shadow:0 0 0 3px rgba(108,59,228,.12);}
.form-hint{font-size:.68rem;color:#b0bdd0;margin-top:-12px;margin-bottom:16px;}
.btn-primary{width:100%;background:#6c3be4;color:#fff;border:none;border-radius:8px;padding:11px;font-size:.9rem;font-weight:700;cursor:pointer;font-family:inherit;transition:opacity .15s;}
.btn-primary:hover{opacity:.88;}
.auth-footer{text-align:center;margin-top:20px;font-size:.78rem;color:#8a9ab8;}
.auth-footer a{color:#6c3be4;text-decoration:none;font-weight:600;}
.auth-footer a:hover{text-decoration:underline;}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:7px;padding:10px 14px;font-size:.78rem;margin-bottom:16px;}
</style>
</head>
<body>
<header class="header">
  <a href="login.php" class="header-logo">
    <div class="logo-mark">
      <svg viewBox="0 0 24 24" fill="none" stroke="#6c3be4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    </div>
    <span class="logo-text">Romit</span>
  </a>
</header>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-title">Create your Romit account</div>
    <div class="auth-sub">Start sending money instantly to anyone.</div>

    <?php if ($error): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
      <label class="form-label" for="email">Email</label>
      <input class="form-input" type="email" id="email" name="email"
        placeholder="you@example.com" required autofocus
        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

      <label class="form-label" for="nickname">Display Name</label>
      <input class="form-input" type="text" id="nickname" name="nickname"
        placeholder="Your name (shown to recipients)"
        value="<?= htmlspecialchars($_POST['nickname'] ?? '') ?>" required>
      <div class="form-hint">This name will appear in wallet invitation emails you send.</div>

      <label class="form-label" for="password">Password</label>
      <input class="form-input" type="password" id="password" name="password"
        placeholder="Min. 6 characters" required>

      <button class="btn-primary" type="submit">Create Account</button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="login.php">Sign in</a>
    </div>
  </div>
</div>
</body>
</html>
