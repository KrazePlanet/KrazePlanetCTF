<?php
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['omise_user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $full_name    = trim($_POST['full_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = trim($_POST['password'] ?? '');
    $currency     = trim($_POST['currency'] ?? 'USD');

    if (empty($company_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetch()) {
            $error = 'An account with this email address already exists. Please sign in.';
        } else {
            $account_id = 'acct_live_' . substr(bin2hex(random_bytes(6)), 0, 10);
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt_ins = $pdo->prepare("INSERT INTO users (company_name, full_name, email, password, account_id, currency) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_ins->execute([$company_name, $full_name, $email, $hashed, $account_id, $currency]);
            $user_id = $pdo->lastInsertId();

            // Insert owner as default active member
            $pdo->prepare("INSERT INTO memberships (owner_id, email, is_admin, is_technical, status, token) VALUES (?, ?, 1, 1, 'active', 'owner')")
                ->execute([$user_id, $email]);

            $_SESSION['omise_user_id'] = $user_id;
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
    <title>Create your Omise Account — Omise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .brand-logo {
            font-size: 28px;
            font-weight: 800;
            color: #1a56db;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 24px;
        }

        .btn-omise {
            background-color: #1a56db;
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            padding: 12px;
            border-radius: 6px;
            border: none;
            width: 100%;
            transition: background 0.15s;
        }

        .btn-omise:hover {
            background-color: #1e429f;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <center>
            <a href="register.php" class="brand-logo">omise</a>
            <h4 class="fw-bold mb-1">Create merchant account</h4>
            <p class="text-secondary small mb-4">Start processing payments online with modern fintech infrastructure.</p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="mb-3">
                <label class="form-label small fw-bold text-dark mb-1">Company / Organization Name</label>
                <input type="text" name="company_name" class="form-control" placeholder="Acme Payments Inc." required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-dark mb-1">Full Name</label>
                <input type="text" name="full_name" class="form-control" placeholder="Alex Morgan" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-dark mb-1">Work Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="alex@acmepayments.com" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-dark mb-1">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Minimum 8 characters" required>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-dark mb-1">Primary Settlement Currency</label>
                <select name="currency" class="form-select">
                    <option value="USD" selected>USD — United States Dollar ($)</option>
                    <option value="THB">THB — Thai Baht (฿)</option>
                    <option value="SGD">SGD — Singapore Dollar (S$)</option>
                    <option value="JPY">JPY — Japanese Yen (¥)</option>
                </select>
            </div>

            <button type="submit" class="btn-omise mb-3">Create Account &rarr;</button>

            <div class="text-center small text-secondary">
                Already have an account? <a href="login.php" class="text-primary text-decoration-none fw-bold">Sign in</a>
            </div>
        </form>
    </div>

</body>
</html>
