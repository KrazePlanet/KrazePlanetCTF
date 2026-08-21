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
    $topic = 'General Feedback';
    $message_text = '';

    if ($is_json) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        if (isset($data['responseData'])) {
            $email = trim($data['responseData']['email'] ?? '');
            $topic = trim($data['responseData']['topic'] ?? 'Feedback');
            $message_text = trim($data['responseData']['message'] ?? '');
        } else {
            $email = trim($data['email'] ?? '');
            $topic = trim($data['topic'] ?? 'Feedback');
            $message_text = trim($data['message'] ?? '');
        }
    } else {
        $email = trim($_POST['email'] ?? '');
        $topic = trim($_POST['topic'] ?? 'General Feedback');
        $message_text = trim($_POST['message'] ?? '');
    }

    if (!empty($email) && !empty($message_text)) {
        // Send real SMTP message without rate limiting (HackerOne #1166069)
        sendUpchieveContactEmail($email, $topic, $message_text);

        if ($is_json) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => 'Your message has been sent successfully to the UPchieve team.',
                'ticket' => [
                    'email' => $email,
                    'topic' => $topic,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            $msg = 'Your message has been submitted successfully to the UPchieve team!';
        }
    } else {
        if ($is_json) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Email and message fields are required.']);
            exit;
        } else {
            $error = 'Please provide both your email and a message.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPchieve — Free 24/7 Online Tutoring &amp; College Counseling</title>
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

        .btn-upchieve {
            background: #0ea5e9;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 22px;
            border-radius: 8px;
            border: none;
            transition: all 0.15s;
        }

        .btn-upchieve:hover {
            background: #0284c7;
            color: #ffffff;
            box-shadow: 0 0 20px rgba(14, 165, 233, 0.4);
        }

        /* Hero */
        .hero-section {
            padding: 80px 0 70px;
            background: radial-gradient(circle at top center, rgba(14, 165, 233, 0.15) 0%, transparent 65%);
        }

        .badge-tag {
            background: rgba(14, 165, 233, 0.15);
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

        /* Contact Box */
        .contact-card {
            background: var(--upchieve-card);
            border: 1px solid var(--upchieve-border);
            border-radius: 16px;
            padding: 36px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        .form-control-upchieve {
            background: #0f172a;
            border: 1px solid var(--upchieve-border);
            color: #ffffff;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-control-upchieve:focus {
            background: #0f172a;
            border-color: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
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
                <a href="tutoring.php" class="nav-link-upchieve d-none d-md-block">Subjects &amp; Tutors</a>
                <a href="volunteers.php" class="nav-link-upchieve d-none d-md-block">Volunteer Coaching</a>
                <a href="contact.php" class="nav-link-upchieve d-none d-md-block">Contact Us</a>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="#contact-section" class="btn-upchieve">Get Support</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="badge-tag"><i class="bi bi-stars me-1"></i> 100% Free for High School Students</span>
                    <h1 class="hero-title">Free, on-demand online tutoring and college counseling.</h1>
                    <p class="text-secondary fs-5 mb-4">
                        Connect with certified volunteer coaches in under 5 minutes. Available 24/7 across Math, Science, Reading, and SAT Prep.
                    </p>
                    <div class="d-flex gap-3">
                        <div class="p-3 bg-dark border border-secondary rounded-3 text-center flex-fill">
                            <div class="fs-4 fw-bold text-info">24/7</div>
                            <div class="text-secondary small">Live Assistance</div>
                        </div>
                        <div class="p-3 bg-dark border border-secondary rounded-3 text-center flex-fill">
                            <div class="fs-4 fw-bold text-success">&lt; 5 mins</div>
                            <div class="text-secondary small">Average Match Time</div>
                        </div>
                    </div>
                </div>

                <!-- Contact Us Form (Target of HackerOne #1166069) -->
                <div class="col-lg-6" id="contact-section">
                    <div class="contact-card">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-chat-heart-fill text-info fs-5"></i>
                            <h4 class="fw-bold mb-0 text-white">Contact Student Support</h4>
                        </div>
                        <p class="text-secondary small mb-4">Have questions about tutoring or your account? Send a message directly to our team.</p>

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

                        <form method="POST" action="index.php#contact-section">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-light mb-1">Your Email</label>
                                <input type="email" name="email" class="form-control form-control-upchieve" placeholder="student@school.edu" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-light mb-1">Topic</label>
                                <select name="topic" class="form-select form-control-upchieve">
                                    <option value="Feedback">Feedback / Suggestions</option>
                                    <option value="Tutoring Help">Tutoring Session Help</option>
                                    <option value="Account Issue">Account &amp; Login Issue</option>
                                    <option value="Volunteer Question">Volunteer Question</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-light mb-1">Message</label>
                                <textarea name="message" class="form-control form-control-upchieve" rows="3" placeholder="Tell us how we can help..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-upchieve w-100 py-3 fw-bold">
                                <i class="bi bi-send-fill me-1"></i> Send Support Message
                            </button>
                        </form>

                        <div class="mt-3 text-center">
                            <span class="text-secondary small" style="font-size: 11px;">API: <code>POST /limit/api-public/contact/send</code></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Footer -->
    <footer class="py-4 border-top border-dark text-center text-secondary small">
        <div class="container">
            &copy; 2026 UPchieve Inc. &bull; <a href="contact.php" class="text-secondary text-decoration-none">Contact Us</a> &bull; <a href="tutoring.php" class="text-secondary text-decoration-none">Tutoring Programs</a>
        </div>
    </footer>

</body>
</html>
