<?php
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['upchieve_user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = trim($_POST['role'] ?? 'student');

    if (empty($username) || empty($fullname) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'An account with this username or email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt_ins = $pdo->prepare("INSERT INTO users (username, fullname, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt_ins->execute([$username, $fullname, $email, $hashed, $role]);
            $user_id = $pdo->lastInsertId();

            $_SESSION['upchieve_user_id'] = $user_id;
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
    <title>Sign Up — UPchieve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }

        .auth-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .brand-logo {
            font-size: 28px;
            font-weight: 800;
            color: #10b981;
            text-decoration: none;
            letter-spacing: -0.5px;
            display: inline-block;
            margin-bottom: 20px;
        }

        .form-control, .form-select {
            border: 1px solid #cbd5e1;
            font-size: 14px;
            padding: 10px 14px;
            border-radius: 8px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }

        .btn-brand {
            background-color: #10b981;
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            padding: 11px;
            border-radius: 8px;
            border: none;
            width: 100%;
            transition: background 0.15s;
        }

        .btn-brand:hover {
            background-color: #059669;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <center>
            <a href="register.php" class="brand-logo">UPchieve</a>
            <h4 class="fw-bold mb-1 text-dark">Join UPchieve Today</h4>
            <p class="text-secondary small mb-4">Free, 24/7 online tutoring and college counseling for students.</p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="mb-3">
                <label class="form-label small fw-bold text-dark mb-1">Username</label>
                <input type="text" name="username" class="form-control" placeholder="johndoe" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-dark mb-1">Full Name</label>
                <input type="text" name="fullname" class="form-control" placeholder="John Doe" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-dark mb-1">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-dark mb-1">I am a</label>
                <select name="role" class="form-select">
                    <option value="student">High School / Middle School Student</option>
                    <option value="volunteer">Volunteer Academic Coach</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-dark mb-1">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••••••" required>
            </div>

            <button type="submit" class="btn-brand mb-3">Create Account &rarr;</button>

            <div class="text-center small text-secondary">
                Already have an account? <a href="login.php" class="text-success text-decoration-none fw-bold">Log in</a>
            </div>
        </form>
    </div>

</body>
</html>
