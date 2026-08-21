<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

$error = '';
$success = '';

$is_json = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false) || isset($_GET['api']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = '';
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        $email = trim($data['email'] ?? '');
    } else {
        $email = trim($_POST['email'] ?? '');
    }

    if (empty($email)) {
        if ($is_json) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 400, 'error' => 'Email address is required.']);
            exit;
        } else {
            $error = 'Please enter your email address.';
        }
    } else {
        // Query user by email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // User exists: generate OTP and send live email
            $otp = (string)mt_rand(100000, 999999);
            $stmt_upd = $pdo->prepare("UPDATE users SET reset_otp = ?, reset_otp_expiry = datetime('now', '+15 minutes') WHERE id = ?");
            $stmt_upd->execute([$otp, $user['id']]);

            sendPasswordResetEmail($user['email'], $user['username'], $otp);

            $_SESSION['reset_email'] = $user['email'];

            if ($is_json) {
                http_response_code(200);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 200,
                    'success' => true,
                    'message' => 'Password reset code sent to your email address.',
                    'email' => $user['email']
                ], JSON_PRETTY_PRINT);
                exit;
            } else {
                header("Location: verify_otp.php");
                exit;
            }
        } else {
            // User DOES NOT exist: 1:1 match with HackerOne #1166054 (Account not found / No account with that id found)
            if ($is_json) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 500,
                    'success' => false,
                    'err' => 'No account with that email found.',
                    'error' => 'Account not found'
                ], JSON_PRETTY_PRINT);
                exit;
            } else {
                $error = 'Account not found. No user is registered with that email address.';
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
    <title>Forgot Password — UPchieve</title>
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
            max-width: 440px;
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

        .form-control {
            border: 1px solid #cbd5e1;
            font-size: 14px;
            padding: 10px 14px;
            border-radius: 8px;
        }

        .form-control:focus {
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
            <a href="login.php" class="brand-logo">UPchieve</a>
            <h4 class="fw-bold mb-1 text-dark">Reset your Password</h4>
            <p class="text-secondary small mb-4">Enter your email address and we'll send you a password reset code.</p>
        </center>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="forgot.php">
            <div class="mb-4">
                <label class="form-label small fw-bold text-dark mb-1">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="alex.smith@upchieve.org" required autofocus>
            </div>

            <button type="submit" class="btn-brand mb-3">Send Reset Code &rarr;</button>

            <div class="text-center small text-secondary">
                Remember your password? <a href="login.php" class="text-success text-decoration-none fw-bold">Back to log in</a>
            </div>
        </form>
    </div>

</body>
</html>
