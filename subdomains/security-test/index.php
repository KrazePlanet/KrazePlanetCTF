<?php
session_start();

// Valid credentials matching HackerOne report #1322243
$VALID_EMAIL = 'partner@on-running.com';
$VALID_PASS = 'Hello771@';

$is_json = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
           isset($_GET['api']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = '';
    $password = '';

    if ($is_json) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        $email = trim($data['email'] ?? $data['username'] ?? '');
        $password = trim($data['password'] ?? '');
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
    }

    // No Rate Limiting (Vulnerable to Brute Force)
    if (strtolower($email) === strtolower($VALID_EMAIL) && $password === $VALID_PASS) {
        $_SESSION['authenticated'] = true;
        $_SESSION['user_email'] = $VALID_EMAIL;
        $_SESSION['user_name'] = 'Retail Partner Manager';
        $_SESSION['partner_id'] = 'ON-PARTNER-88912';

        if ($is_json) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => 'Authentication successful. Welcome to Partner Bootcamp.',
                'token' => 'on_jwt_' . bin2hex(random_bytes(24)),
                'user' => [
                    'email' => $VALID_EMAIL,
                    'name' => 'Retail Partner Manager',
                    'partner_id' => 'ON-PARTNER-88912',
                    'role' => 'Certified Global Retailer'
                ]
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            header('Location: dashboard.php');
            exit;
        }
    } else {
        // Invalid credentials respond with 401 Unauthorized
        http_response_code(401);
        if ($is_json) {
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            echo json_encode([
                'status' => 401,
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Invalid email or password combination. Please check your retail partner credentials.'
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            $error = 'Invalid email or password combination. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Bootcamp — On Running B2B Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --on-black: #000000;
            --on-dark: #121212;
            --on-gray: #767676;
            --on-light-gray: #f5f5f7;
            --on-accent: #e5ff00;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--on-dark);
            color: #ffffff;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Bar */
        .on-navbar {
            background: rgba(18, 18, 18, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #222222;
            padding: 16px 0;
        }

        .on-logo {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            text-decoration: none;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .on-logo-badge {
            background: #ffffff;
            color: #000000;
            font-weight: 900;
            font-size: 16px;
            padding: 2px 8px;
            border-radius: 4px;
        }

        .portal-label {
            font-size: 13px;
            font-weight: 600;
            color: #888888;
            border-left: 1px solid #333333;
            padding-left: 10px;
            margin-left: 6px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Main Container */
        .auth-container {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: radial-gradient(circle at top right, #1f1f23 0%, #0d0d0f 100%);
        }

        .auth-card {
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }

        .auth-title {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            color: #ffffff;
        }

        .auth-subtitle {
            font-size: 14px;
            color: #a1a1aa;
            margin-bottom: 28px;
        }

        .form-control-on {
            background: #09090b;
            border: 1px solid #27272a;
            color: #ffffff;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-control-on:focus {
            background: #09090b;
            border-color: #ffffff;
            color: #ffffff;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
        }

        .btn-on {
            background: #ffffff;
            color: #000000;
            font-weight: 700;
            font-size: 14px;
            padding: 13px 20px;
            border-radius: 8px;
            border: none;
            width: 100%;
            transition: all 0.15s;
        }

        .btn-on:hover {
            background: #e4e4e7;
            color: #000000;
        }

        .partner-badge {
            background: rgba(229, 255, 0, 0.1);
            color: #e5ff00;
            border: 1px solid rgba(229, 255, 0, 0.2);
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 16px;
        }

        /* Footer */
        .on-footer {
            background: #09090b;
            border-top: 1px solid #18181b;
            padding: 20px 0;
            font-size: 12px;
            color: #71717a;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="on-navbar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <a href="index.php" class="on-logo">
                    <span class="on-logo-badge">On</span>
                </a>
                <span class="portal-label">Partner Bootcamp</span>
            </div>
            <div>
                <a href="support.php" class="text-white-50 text-decoration-none small me-3"><i class="bi bi-question-circle me-1"></i> Partner Helpdesk</a>
                <span class="badge bg-secondary text-white font-monospace">B2B Portal</span>
            </div>
        </div>
    </nav>

    <!-- Main Login View -->
    <div class="auth-container">
        <div class="auth-card">
            
            <span class="partner-badge"><i class="bi bi-award-fill me-1"></i> Swiss Engineering &bull; Retailer Academy</span>
            
            <h1 class="auth-title">Sign In to Partner Bootcamp</h1>
            <p class="auth-subtitle">Access your specialized product education, CloudTec® training modules, and retail merchandise toolkit.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 px-3 small border-0 d-flex align-items-center gap-2 mb-4" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-light mb-1">Retail Partner Email</label>
                    <input type="email" name="email" class="form-control form-control-on" placeholder="partner@on-running.com" required value="<?= htmlspecialchars($_POST['email'] ?? 'partner@on-running.com') ?>">
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold text-light mb-0">Password</label>
                        <a href="support.php" class="text-secondary text-decoration-none small" style="font-size: 12px;">Forgot password?</a>
                    </div>
                    <input type="password" name="password" class="form-control form-control-on" placeholder="••••••••••••" required>
                </div>

                <button type="submit" class="btn-on mb-3">Sign In &rarr;</button>
            </form>

            <div class="border-top border-dark pt-3 text-center">
                <p class="text-secondary small mb-0" style="font-size: 12px;">
                    Authorized for registered On retail partners &amp; athletic footwear distributors only.
                </p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="on-footer">
        <div class="container">
            &copy; 2026 On AG. Zurich, Switzerland. All Rights Reserved. &bull; <a href="support.php" class="text-secondary text-decoration-none">Terms of Partner Distribution</a>
        </div>
    </footer>

</body>
</html>
