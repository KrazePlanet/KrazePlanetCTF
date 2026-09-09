<?php
require_once __DIR__ . '/../includes/functions.php';
if (is_admin()) redirect('index.php');
$error = flash('error');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1220">
    <title>Admin Login — <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-login-page">
    <main class="login-shell">
        <section class="login-card" aria-labelledby="login-title">
            <a class="login-brand" href="../index.php" aria-label="Back to website">
                <span class="brand-mark">&lt;/&gt;</span>
                <span><?= e(SITE_NAME) ?></span>
            </a>

            <div class="login-heading">
                <span class="login-eyebrow">ADMINISTRATION</span>
                <h1 id="login-title">Welcome back</h1>
                <p>Sign in to manage project inquiries and client requests.</p>
            </div>

            <?php if ($error): ?>
                <div class="login-alert" role="alert">⚠ <?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="authenticate.php" class="login-form">
                <?= csrf_field() ?>
                <label for="email">Admin email</label>
                <div class="login-input-wrap">
                    <span class="input-icon">✉</span>
                    <input id="email" type="email" name="email" placeholder="admin@yourdomain.com" required autocomplete="username" autofocus>
                </div>

                <label for="password">Password</label>
                <div class="login-input-wrap">
                    <span class="input-icon">●</span>
                    <input id="password" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    <button type="button" class="password-toggle" aria-label="Show password" onclick="togglePassword()">Show</button>
                </div>

                <button class="login-submit" type="submit">
                    <span>Sign in to dashboard</span><span>→</span>
                </button>
            </form>

            <div class="login-footer">
                <a href="../index.php">← Back to website</a>
                <span>Secure admin area</span>
            </div>
        </section>
    </main>
    <script>
    function togglePassword() {
        const input = document.getElementById('password');
        const button = document.querySelector('.password-toggle');
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        button.textContent = visible ? 'Show' : 'Hide';
        button.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
    }
    </script>
</body>
</html>
