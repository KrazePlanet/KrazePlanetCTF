<?php
session_start();

$db_file = __DIR__ . '/accounts.db';
if (file_exists($db_file)) {
    @chmod($db_file, 0666);
}
@chmod(__DIR__, 0777);
$pdo = new PDO('sqlite:' . $db_file);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE,
    user_sub TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Prepopulate some existing accounts for email enumeration testing
$sample_users = ['admin@courier.com', 'developer@trycourier.app', 'alex@techcorp.io'];
foreach ($sample_users as $s_email) {
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (email, user_sub) VALUES (?, ?)");
    $stmt->execute([$s_email, 'sub_' . md5($s_email)]);
}

$is_json = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false) ||
           isset($_GET['api']) ||
           isset($_POST['is_ajax']);

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = '';
    
    if ((isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false)) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        $email = trim($data['email'] ?? $data['username'] ?? '');
    } else {
        $email = trim($_POST['email'] ?? $_POST['input_email'] ?? '');
    }

    if (!empty($email)) {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT user_sub FROM users WHERE LOWER(email) = LOWER(?)");
        $stmt->execute([$email]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Matching HackerOne #905692 duplicate email behavior
            http_response_code(500);
            if ($is_json) {
                header('Content-Type: application/x-amz-json-1.1; charset=utf-8');
                header('x-amzn-RequestId: ' . bin2hex(random_bytes(16)));
                header('Access-Control-Allow-Origin: *');
                echo json_encode([
                    '__type' => 'UsernameExistsException',
                    'message' => 'An account with the given email already exists.'
                ]);
                exit;
            } else {
                $error = 'An account with the given email already exists.';
            }
        } else {
            // New user registration - generates a unique sub UUID
            $user_sub = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            // Insert into DB without any rate limiting
            $stmt = $pdo->prepare("INSERT INTO users (email, user_sub) VALUES (?, ?)");
            $stmt->execute([$email, $user_sub]);

            $_SESSION['authenticated'] = true;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_sub'] = $user_sub;

            // Matching HackerOne #905692 success response exactly
            http_response_code(200);
            if ($is_json) {
                header('Content-Type: application/x-amz-json-1.1; charset=utf-8');
                header('x-amzn-RequestId: ' . bin2hex(random_bytes(16)));
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Expose-Headers: x-amzn-RequestId, x-amzn-ErrorType, x-amzn-ErrorMessage, Date');
                echo json_encode([
                    'UserConfirmed' => true,
                    'UserSub' => $user_sub
                ]);
                exit;
            } else {
                $msg = 'Account created successfully! UserSub: ' . $user_sub;
            }
        }
    } else {
        http_response_code(400);
        if ($is_json) {
            echo json_encode(['error' => 'Email is required']);
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
    <title>Courier — The Multi-Channel Notification Platform for Developers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --courier-purple: #7c3aed;
            --courier-purple-hover: #6d28d9;
            --courier-dark: #09090b;
            --courier-card: #141417;
            --courier-border: #27272a;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--courier-dark);
            color: #f4f4f5;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        /* Navbar */
        .courier-nav {
            background: rgba(9, 9, 11, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--courier-border);
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

        .brand-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 17px;
        }

        .nav-link-courier {
            font-size: 14px;
            font-weight: 600;
            color: #a1a1aa;
            text-decoration: none;
            padding: 8px 16px;
            transition: color 0.15s;
        }

        .nav-link-courier:hover {
            color: #ffffff;
        }

        .btn-courier {
            background: #7c3aed;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 22px;
            border-radius: 8px;
            border: none;
            transition: all 0.15s;
        }

        .btn-courier:hover {
            background: #6d28d9;
            color: #ffffff;
            box-shadow: 0 0 20px rgba(124, 58, 237, 0.4);
        }

        /* Hero */
        .hero-section {
            padding: 90px 0 70px;
            background: radial-gradient(circle at top center, rgba(124, 58, 237, 0.15) 0%, transparent 70%);
        }

        .badge-tag {
            background: rgba(124, 58, 237, 0.15);
            color: #c084fc;
            border: 1px solid rgba(168, 85, 247, 0.3);
            font-size: 12px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 20px;
        }

        .hero-title {
            font-size: 52px;
            font-weight: 800;
            letter-spacing: -1.5px;
            line-height: 1.15;
            color: #ffffff;
            margin-bottom: 20px;
        }

        .hero-desc {
            font-size: 19px;
            color: #a1a1aa;
            max-width: 650px;
            margin-bottom: 36px;
        }

        /* Signup Box */
        .signup-card {
            background: var(--courier-card);
            border: 1px solid var(--courier-border);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .form-control-courier {
            background: #09090b;
            border: 1px solid var(--courier-border);
            color: #ffffff;
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 15px;
        }

        .form-control-courier:focus {
            background: #09090b;
            border-color: #7c3aed;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.25);
        }

        /* Features */
        .feature-card {
            background: #141417;
            border: 1px solid #27272a;
            border-radius: 12px;
            padding: 26px;
            height: 100%;
            transition: border-color 0.2s;
        }

        .feature-card:hover {
            border-color: #52525b;
        }

        .feature-icon-box {
            width: 44px;
            height: 44px;
            background: rgba(124, 58, 237, 0.1);
            color: #a855f7;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="courier-nav sticky-top">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-4">
                <a href="index.php" class="brand-logo">
                    <div class="brand-icon"><i class="bi bi-send-fill"></i></div>
                    Courier
                </a>
                <a href="docs.php" class="nav-link-courier d-none d-md-block">Documentation</a>
                <a href="templates.php" class="nav-link-courier d-none d-md-block">Channels &amp; Routing</a>
                <a href="docs.php" class="nav-link-courier d-none d-md-block">SDKs</a>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="dashboard.php" class="nav-link-courier">Console Login</a>
                <a href="#register-section" class="btn-courier">Get Started Free</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section text-center">
        <div class="container">
            <span class="badge-tag"><i class="bi bi-cpu me-1"></i> Developer Notifications Infrastructure</span>
            <h1 class="hero-title">One API for Email, SMS, Slack, &amp; Push</h1>
            <p class="hero-desc mx-auto">
                Design and route transactional notifications across 50+ communication channels. Deliver intelligent alerts without managing complex SMTP and SMS gateways.
            </p>

            <!-- Registration Form Box (Target of HackerOne #905692) -->
            <div class="row justify-content-center" id="register-section">
                <div class="col-lg-6">
                    <div class="signup-card text-start">
                        <h4 class="fw-bold mb-2 text-white">Create your developer account</h4>
                        <p class="text-secondary small mb-4">Start sending 10,000 free notifications per month. No credit card required.</p>

                        <?php if (!empty($msg)): ?>
                            <div class="alert alert-success py-2 px-3 small border-0 d-flex align-items-center gap-2 mb-3" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                                <i class="bi bi-check-circle-fill"></i>
                                <span><?= htmlspecialchars($msg) ?> &bull; <a href="dashboard.php" class="text-white fw-bold">Open Console &rarr;</a></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger py-2 px-3 small border-0 d-flex align-items-center gap-2 mb-3" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">
                                <i class="bi bi-exclamation-octagon-fill"></i>
                                <span><?= htmlspecialchars($error) ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="index.php#register-section">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-light mb-1">Work Email</label>
                                <input type="email" name="email" class="form-control form-control-courier" placeholder="developer@company.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>

                            <button type="submit" class="btn btn-courier w-100 py-3 fw-bold">
                                <i class="bi bi-arrow-right-circle me-1"></i> Register Free Developer Account
                            </button>
                        </form>

                        <div class="mt-3 text-center">
                            <span class="text-secondary small" style="font-size: 12px;">API Endpoint: <code>POST /stress/index.php</code> (or <code>Content-Type: application/json</code>)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Channels Supported -->
    <section class="py-5 border-top border-dark">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h3 class="fw-bold mb-2">Pre-built integrations with modern message brokers</h3>
                <p class="text-secondary small">SendGrid &bull; Twilio &bull; AWS SES &bull; Slack Bot &bull; Firebase FCM &bull; Discord</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-box"><i class="bi bi-envelope-at-fill"></i></div>
                        <h5 class="fw-bold mb-2 text-white">Unified Template Studio</h5>
                        <p class="text-secondary small mb-0">Build responsive email and mobile push templates with dynamic JSON merge tokens and conditional channel routing.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-box"><i class="bi bi-diagram-3-fill"></i></div>
                        <h5 class="fw-bold mb-2 text-white">Automated Fallback Routing</h5>
                        <p class="text-secondary small mb-0">If Push notification fails or user is offline, automatically failover to SMS or Slack in sub-second latency.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-box"><i class="bi bi-code-slash"></i></div>
                        <h5 class="fw-bold mb-2 text-white">Multi-Language SDKs</h5>
                        <p class="text-secondary small mb-0">First-class SDK libraries for Python, Node.js, Ruby, Go, and Java with built-in retry mechanisms and webhook handlers.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 border-top border-dark text-center text-secondary small">
        <div class="container">
            &copy; 2026 Courier Technology Inc. &bull; <a href="docs.php" class="text-secondary text-decoration-none">API Documentation</a> &bull; <a href="templates.php" class="text-secondary text-decoration-none">Channel Integrations</a>
        </div>
    </footer>

</body>
</html>
