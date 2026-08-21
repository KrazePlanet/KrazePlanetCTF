<?php
session_start();
require_once __DIR__ . '/mailer.php';

$is_json = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false) ||
           isset($_GET['api']);

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = '';
    
    if ((isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false)) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        $email = trim($data['email'] ?? $data['username'] ?? '');
    } else {
        $email = trim($_POST['email'] ?? '');
    }

    if (!empty($email)) {
        // Trigger actual SMTP password reset email dispatch without rate limiting
        sendPasswordResetEmail($email);

        http_response_code(200);
        if ($is_json) {
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            echo json_encode([
                'success' => true,
                'status' => 200,
                'message' => 'Password reset instructions have been sent to your email address.'
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            $msg = 'Password reset instructions have been sent to ' . htmlspecialchars($email);
        }
    } else {
        http_response_code(400);
        if ($is_json) {
            echo json_encode(['success' => false, 'error' => 'Email address is required.']);
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
    <title>WakaTime — Dashboards &amp; Time Tracking for Programmers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --waka-blue: #0284c7;
            --waka-blue-hover: #0369a1;
            --waka-dark: #0b0f19;
            --waka-card: #111827;
            --waka-border: #1f2937;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--waka-dark);
            color: #f3f4f6;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        /* Navbar */
        .waka-nav {
            background: rgba(11, 15, 25, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--waka-border);
            padding: 16px 0;
        }

        .brand-logo {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-logo i {
            color: #38bdf8;
            font-size: 24px;
        }

        .nav-link-waka {
            font-size: 14px;
            font-weight: 600;
            color: #9ca3af;
            text-decoration: none;
            padding: 8px 16px;
            transition: color 0.15s;
        }

        .nav-link-waka:hover {
            color: #ffffff;
        }

        .btn-waka {
            background: var(--waka-blue);
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 22px;
            border-radius: 8px;
            border: none;
            transition: all 0.15s;
        }

        .btn-waka:hover {
            background: var(--waka-blue-hover);
            color: #ffffff;
            box-shadow: 0 0 20px rgba(2, 132, 199, 0.4);
        }

        /* Hero */
        .hero-section {
            padding: 80px 0 70px;
            background: radial-gradient(circle at top right, rgba(2, 132, 199, 0.15) 0%, transparent 65%);
        }

        .badge-tag {
            background: rgba(2, 132, 199, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
            font-size: 12px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 20px;
        }

        .hero-title {
            font-size: 48px;
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1.2;
            color: #ffffff;
            margin-bottom: 20px;
        }

        .hero-desc {
            font-size: 18px;
            color: #9ca3af;
            margin-bottom: 32px;
        }

        /* Form Card */
        .form-card {
            background: var(--waka-card);
            border: 1px solid var(--waka-border);
            border-radius: 16px;
            padding: 36px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }

        .form-control-waka {
            background: #0b0f19;
            border: 1px solid var(--waka-border);
            color: #ffffff;
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 15px;
        }

        .form-control-waka:focus {
            background: #0b0f19;
            border-color: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        .code-stat-card {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 14px;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body>

    <!-- Main Navigation Bar -->
    <nav class="waka-nav sticky-top">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-4">
                <a href="index.php" class="brand-logo">
                    <i class="bi bi-clock-history"></i> WakaTime
                </a>
                <a href="integrations.php" class="nav-link-waka d-none d-md-block">Plugins &amp; IDEs</a>
                <a href="pricing.php" class="nav-link-waka d-none d-md-block">Pricing</a>
                <a href="dashboard.php" class="nav-link-waka d-none d-md-block">Leaderboards</a>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="dashboard.php" class="nav-link-waka">Dashboard</a>
                <a href="#reset-section" class="btn-waka">Forgot Password</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section">
        <div class="container">
            <div class="row align-items-center g-5">
                
                <div class="col-lg-6">
                    <span class="badge-tag"><i class="bi bi-code-square me-1"></i> Automated Time Metrics for Developers</span>
                    <h1 class="hero-title">Open source plugins for metrics, insights, and time tracking.</h1>
                    <p class="hero-desc">
                        Automatically track programming activity directly inside VS Code, JetBrains, Vim, and Sublime Text. Generate beautiful automated coding reports.
                    </p>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="code-stat-card">
                                <div class="text-secondary small font-mono">Top Language</div>
                                <div class="fs-4 fw-bold text-info font-mono">Python &bull; 64.2%</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="code-stat-card">
                                <div class="text-secondary small font-mono">Coding Today</div>
                                <div class="fs-4 fw-bold text-success font-mono">6 hrs 42 mins</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password Reset Recovery Endpoint (Target of HackerOne #658089) -->
                <div class="col-lg-6" id="reset-section">
                    <div class="form-card">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-shield-lock-fill text-info fs-5"></i>
                            <h4 class="fw-bold mb-0 text-white">Reset Account Password</h4>
                        </div>
                        <p class="text-secondary small mb-4">Enter your developer account email to receive a password reset link.</p>

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

                        <form method="POST" action="index.php#reset-section">
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-light mb-1">Developer Account Email</label>
                                <input type="email" name="email" class="form-control form-control-waka" placeholder="developer@wakatime.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>

                            <button type="submit" class="btn btn-waka w-100 py-3 fw-bold">
                                <i class="bi bi-send-fill me-1"></i> Send Password Reset Link
                            </button>
                        </form>

                        <div class="mt-3 text-center">
                            <span class="text-secondary small" style="font-size: 12px;">API Endpoint: <code>POST /request/index.php</code> (or <code>/api/v1/users/reset_password</code>)</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </header>

    <!-- Footer -->
    <footer class="py-4 border-top border-dark text-center text-secondary small">
        <div class="container">
            &copy; 2026 WakaTime Inc. &bull; <a href="integrations.php" class="text-secondary text-decoration-none">IDE Plugins</a> &bull; <a href="pricing.php" class="text-secondary text-decoration-none">Pricing</a>
        </div>
    </footer>

</body>
</html>
