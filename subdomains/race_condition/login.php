<?php
session_start();
require_once __DIR__ . '/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['workspace_name'] = $user['workspace_name'];
            $_SESSION['subdomain'] = $user['subdomain'];
            $_SESSION['email'] = $user['email'];

            header("Location: index.php");
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign In to Your Workspace | Slack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ffffff; color: #1d1c1d; }
        .slack-header { border-bottom: 1px solid #e2e8f0; padding: 18px 0; }
        .slack-brand { font-size: 26px; font-weight: 800; color: #1d1c1d; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .slack-logo-icon { width: 30px; height: 30px; background: linear-gradient(135deg, #ECB22E, #2EB67D, #E01E5A, #36C5F0); border-radius: 8px; }
        .auth-card { max-width: 440px; margin: 50px auto; padding: 40px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .btn-slack { background-color: #4A154B; color: #ffffff; font-weight: 700; padding: 12px; border-radius: 6px; width: 100%; border: none; }
        .btn-slack:hover { background-color: #3F0E40; color: #ffffff; }
    </style>
</head>
<body>
    <header class="slack-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="login.php" class="slack-brand"><div class="slack-logo-icon"></div> slack</a>
            <div>
                <span class="text-secondary small">New to Slack?</span>
                <a href="signup.php" class="btn btn-outline-dark btn-sm fw-bold ms-2">Create Workspace</a>
            </div>
        </div>
    </header>

    <div class="container py-4">
        <div class="auth-card">
            <h3 class="fw-bold text-center mb-1">Sign in to Slack</h3>
            <p class="text-secondary text-center small mb-4">Enter your workspace account email and password.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 px-3 small mb-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label class="form-label small fw-bold mb-1">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="name@work-email.com" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold mb-1">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-slack">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
