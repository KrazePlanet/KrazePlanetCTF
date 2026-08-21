<?php
session_start();
require_once __DIR__ . '/mailer.php';

$msg = '';
$error = '';

$is_json = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false) ||
           isset($_GET['api']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = '';

    if ($is_json) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        $email = trim($data['email'] ?? $data['username'] ?? '');
    } else {
        $email = trim($_POST['email'] ?? '');
    }

    if (!empty($email)) {
        // Send real SMTP password reset email without rate limit (HackerOne #1166066)
        sendUpchievePasswordResetEmail($email);

        if ($is_json) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => 'Password reset instructions have been sent to your email address.'
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            $msg = 'Password reset instructions have been sent to ' . htmlspecialchars($email);
        }
    } else {
        if ($is_json) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Email address is required.']);
            exit;
        } else {
            $error = 'Please enter a valid email address.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — UPchieve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --upchieve-blue: #0ea5e9;
            --upchieve-dark: #0f172a;
            --upchieve-card: #1e293b;
            --upchieve-border: #334155;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--upchieve-dark);
            color: #f8fafc;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        /* Navbar */
        .upchieve-nav {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--upchieve-border);
            padding: 16px 0;
        }

        .brand-logo {
            font-size: 24px;
            font-weight: 800;
            color: #38bdf8;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-logo span {
            color: #ffffff;
        }

        .nav-link-upchieve {
            font-size: 14px;
            font-weight: 600;
            color: #94a3b8;
            text-decoration: none;
            padding: 8px 16px;
            transition: color 0.15s;
        }

        .nav-link-upchieve:hover {
            color: #ffffff;
        }

        /* Reset Box */
        .reset-card {
            background: var(--upchieve-card);
            border: 1px solid var(--upchieve-border);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            margin-top: 40px;
        }

        .form-control-upchieve {
            background: #0f172a;
            border: 1px solid var(--upchieve-border);
            color: #ffffff;
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 15px;
        }

        .form-control-upchieve:focus {
            background: #0f172a;
            border-color: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        .btn-upchieve {
            background: #0ea5e9;
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            padding: 14px 24px;
            border-radius: 8px;
            border: none;
            transition: all 0.15s;
        }

        .btn-upchieve:hover {
            background: #0284c7;
            color: #ffffff;
            box-shadow: 0 0 20px rgba(14, 165, 233, 0.4);
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="upchieve-nav sticky-top">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-4">
                <a href="index.php" class="brand-logo">
                    <i class="bi bi-mortarboard-fill"></i> UP<span>chieve</span>
                </a>
                <a href="sessions.php" class="nav-link-upchieve d-none d-md-block">Tutoring Programs</a>
                <a href="dashboard.php" class="nav-link-upchieve d-none d-md-block">Student Dashboard</a>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="dashboard.php" class="btn-upchieve">Log In</a>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-8">
                <div class="reset-card">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-key-fill text-info fs-4"></i>
                        <h4 class="fw-bold mb-0 text-white">Reset Your Password</h4>
                    </div>
                    <p class="text-secondary small mb-4">Enter your email address and we'll send you a link to reset your password.</p>

                    <?php if (!empty($msg)): ?>
                        <div class="alert alert-success py-2 px-3 small border-0 d-flex align-items-center gap-2 mb-3" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                            <i class="bi bi-check-circle-fill"></i>
                            <span><?= htmlspecialchars($msg) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2 px-3 small border-0 d-flex align-items-center gap-2 mb-3" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">
                            <i class="bi bi-exclamation-octagon-fill"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="resetpassword.php">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-light mb-1">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-upchieve" placeholder="student@highschool.edu" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autofocus>
                        </div>

                        <button type="submit" class="btn btn-upchieve w-100 fw-bold">
                            <i class="bi bi-send-fill me-1"></i> Send Password Reset Link
                        </button>
                    </form>

                    <div class="mt-4 text-center">
                        <a href="dashboard.php" class="text-secondary text-decoration-none small">&larr; Return to Sign In</a>
                    </div>

                    <div class="mt-3 text-center">
                        <span class="text-secondary small" style="font-size: 11px;">Target: <code>POST /ratelimit/resetpassword.php</code> (or <code>/index.php</code>)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 border-top border-dark text-center text-secondary small mt-5">
        <div class="container">
            &copy; 2026 UPchieve Inc. &bull; Free 24/7 Academic Tutoring
        </div>
    </footer>

</body>
</html>
