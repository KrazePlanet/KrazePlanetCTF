<?php
session_start();

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$msg = '';
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$is_gform = isset($_POST['is_submit_6']) || isset($_POST['email']) || isset($_POST['input_1']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($is_gform || isset($_POST['subscribe']))) {
    $email_val = trim($_POST['email'] ?? $_POST['input_1'] ?? '');
    
    // No Rate Limit Enforced (Intentionally vulnerable as per HackerOne #1322243)
    if (!empty($email_val) && filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
        try {
            $mail = new PHPMailer(false);
            $mail->isSMTP();
            $mail->Host       = 'mailpit';
            $mail->SMTPAuth   = false;
                        $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
            $mail->Port       = 1025;
            $mail->Timeout    = 5;

            $mail->setFrom('noreply@krazeplanet.com', 'Yelp for Business');
            $mail->addAddress($email_val);
            $mail->isHTML(true);
            $mail->Subject = 'Thanks for Subscribing to Yelp for Business Updates';
            $mail->Body    = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; padding: 20px; color: #1e293b; }
                    .mail-card { max-width: 550px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
                    .mail-header { background: #d32323; color: #ffffff; padding: 24px; text-align: center; }
                    .mail-body { padding: 30px; }
                    .code-box { background: #f1f5f9; border: 1px dashed #cbd5e1; padding: 14px; text-align: center; font-size: 20px; font-weight: bold; letter-spacing: 2px; color: #d32323; margin: 20px 0; border-radius: 8px; }
                    .mail-footer { background: #f8fafc; padding: 16px; font-size: 12px; color: #64748b; text-align: center; border-top: 1px solid #e2e8f0; }
                </style>
            </head>
            <body>
                <div class="mail-card">
                    <div class="mail-header">
                        <h2 style="margin:0; font-size: 22px;">Yelp for Business</h2>
                    </div>
                    <div class="mail-body">
                        <h3 style="margin-top:0; color: #0f172a;">Welcome to Yelp for Business Insights!</h3>
                        <p>Thank you for subscribing to our weekly local business marketing newsletter. You will receive curated SEO guides, customer review playbooks, and local advertising trends directly in your inbox.</p>
                        <p>To verify your business email address and unlock your free $150 Yelp Ads credit, use the verification code below:</p>
                        <div class="code-box">YELP-' . rand(100000, 999999) . '</div>
                        <p style="font-size: 13px; color: #64748b;">If you did not request this newsletter, you can safely ignore this email or unsubscribe at any time.</p>
                    </div>
                    <div class="mail-footer">
                        &copy; 2026 Yelp Inc. &bull; 140 New Montgomery St, San Francisco, CA
                    </div>
                </div>
            </body>
            </html>';

            $mail->send();
        } catch (Exception $e) {
            // Silently continue or log so Intruder flood does not break
        }
    }

    // Response matching HackerOne #1322243 Gravity Forms AJAX postback
    if ($is_ajax || isset($_POST['gform_ajax'])) {
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Powered-By: WP Engine');
        header('Access-Control-Allow-Credentials: true');
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8' /></head><body class='GF_AJAX_POSTBACK'>";
        echo "<div id='gform_confirmation_wrapper_6' class='gform_confirmation_wrapper form--newsletter'>";
        echo "<div id='gform_confirmation_message_6' class='gform_confirmation_message_6 gform_confirmation_message'>Thanks for subscribing!</div>";
        echo "</div></body></html>";
        exit;
    } else {
        $msg = "Thanks for subscribing!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yelp for Business — Connect with Local Customers &amp; Grow Your Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --yelp-red: #d32323;
            --yelp-red-hover: #b31b1b;
            --yelp-dark: #1e293b;
            --yelp-blue: #0284c7;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
            background-color: #ffffff;
            line-height: 1.6;
        }

        /* Top Nav */
        .navbar-yelp {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 0;
        }

        .brand-logo {
            font-size: 24px;
            font-weight: 900;
            color: var(--yelp-red);
            text-decoration: none;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .brand-sub {
            font-size: 14px;
            font-weight: 700;
            color: #64748b;
            padding-left: 8px;
            border-left: 2px solid #cbd5e1;
        }

        .nav-link-yelp {
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            text-decoration: none;
            padding: 8px 14px;
            transition: color 0.15s;
        }

        .nav-link-yelp:hover {
            color: var(--yelp-red);
        }

        .btn-yelp-red {
            background: var(--yelp-red);
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 22px;
            border-radius: 8px;
            border: none;
            transition: background 0.15s;
        }

        .btn-yelp-red:hover {
            background: var(--yelp-red-hover);
            color: #ffffff;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 80px 0 70px;
            border-bottom: 1px solid #e2e8f0;
        }

        .hero-title {
            font-size: 44px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -1px;
            line-height: 1.2;
            margin-bottom: 18px;
        }

        .hero-desc {
            font-size: 18px;
            color: #475569;
            margin-bottom: 28px;
        }

        /* Feature Cards */
        .feature-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            transition: all 0.2s;
            height: 100%;
        }

        .feature-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            border-color: #cbd5e1;
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: rgba(211, 35, 35, 0.08);
            color: var(--yelp-red);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 18px;
        }

        /* Newsletter Subscription Section */
        .newsletter-section {
            background: #0f172a;
            color: #ffffff;
            padding: 70px 0;
        }

        .newsletter-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .newsletter-input {
            background: #0f172a;
            border: 1px solid #475569;
            color: #ffffff;
            font-size: 15px;
            padding: 14px 18px;
            border-radius: 8px;
        }

        .newsletter-input:focus {
            background: #0f172a;
            color: #ffffff;
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        .stats-badge {
            display: inline-block;
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
            font-size: 12px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 12px;
        }

        .gform_confirmation_message {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid #10b981;
            color: #34d399;
            padding: 16px 20px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 15px;
            margin-top: 16px;
        }
    </style>
</head>
<body>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-yelp sticky-top">
        <div class="container">
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="brand-logo">
                    <i class="bi bi-yelp"></i> yelp
                </a>
                <span class="brand-sub">for Business</span>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link-yelp" href="resources.php">Products &amp; Ads</a></li>
                    <li class="nav-item"><a class="nav-link-yelp" href="resources.php">Success Stories</a></li>
                    <li class="nav-item"><a class="nav-link-yelp" href="pricing.php">Pricing &amp; Plans</a></li>
                    <li class="nav-item"><a class="nav-link-yelp" href="resources.php">Resources &amp; Blog</a></li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <a href="login.php" class="nav-link-yelp">Log In</a>
                    <a href="claim.php" class="btn-yelp-red">Claim Free Profile</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Header -->
    <header class="hero-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="stats-badge"><i class="bi bi-graph-up-arrow me-1"></i> 90+ Million Monthly Local Searches</span>
                    <h1 class="hero-title">Put your business in front of customers who are ready to buy.</h1>
                    <p class="hero-desc">
                        3 out of 4 people on Yelp are looking for local businesses like yours. Claim your free profile, manage customer reviews, and launch targeted local campaigns today.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="claim.php" class="btn-yelp-red btn-lg px-4 py-3">Manage My Free Listing &rarr;</a>
                        <a href="pricing.php" class="btn btn-outline-secondary btn-lg px-4 py-3 fw-semibold">Calculate Ad ROI</a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card p-4 border-0 shadow-lg rounded-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=120&auto=format&fit=crop&q=80" alt="Restaurant" class="rounded-3" width="70" height="70" style="object-fit: cover;">
                            <div>
                                <h5 class="fw-bold mb-1">Bella Italia Bistro</h5>
                                <div class="text-warning small mb-1">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-half"></i>
                                    <span class="text-dark fw-bold ms-1">4.8 (342 reviews)</span>
                                </div>
                                <span class="badge bg-success bg-opacity-10 text-success">Verified Business Profile</span>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">"Since running targeted Yelp Ads, our dinner reservations surged by 65% in less than two months." &bull; <em>Marco Rossi, Owner</em></p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Platform Advantages -->
    <section class="py-5 bg-white">
        <div class="container py-4">
            <div class="text-center max-w-2xl mx-auto mb-5">
                <h2 class="fw-bold fs-2 mb-2">Everything you need to dominate local search</h2>
                <p class="text-muted">Simple, automated marketing tools engineered for small businesses and growing franchises.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="bi bi-badge-ad"></i></div>
                        <h5 class="fw-bold mb-2">Yelp Ads &amp; Search Placement</h5>
                        <p class="text-muted small mb-0">Get placed at the very top of local search results and competitor listings when potential clients search for your services.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="bi bi-chat-heart"></i></div>
                        <h5 class="fw-bold mb-2">Review Management &amp; Messaging</h5>
                        <p class="text-muted small mb-0">Respond to client reviews, chat directly with prospects via Request-a-Quote, and build credible word-of-mouth reputation.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="bi bi-bar-chart-steps"></i></div>
                        <h5 class="fw-bold mb-2">Live Customer Analytics</h5>
                        <p class="text-muted small mb-0">Track page visits, phone calls, directions requested, and conversion rates across mobile and desktop apps in real time.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter & Business Insights Form (Target for HackerOne #1322243) -->
    <section class="newsletter-section" id="subscribe-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="newsletter-card text-center">
                        <span class="stats-badge mb-3"><i class="bi bi-envelope-check me-1"></i> Business Growth Newsletter</span>
                        <h2 class="fw-bold fs-2 text-white mb-2">Get Weekly Small Business Tips &amp; Marketing Trends</h2>
                        <p class="text-light opacity-75 small mb-4">
                            Join over 250,000 local business owners. Receive curated marketing guides, local SEO strategies, and advertising insights straight to your inbox.
                        </p>

                        <!-- Gravity Forms Postback Form -->
                        <form id="gform_6" class="form--newsletter" method="POST" action="index.php#subscribe-section">
                            <input type="hidden" name="is_submit_6" value="1">
                            <input type="hidden" name="gform_submit" value="6">
                            
                            <div class="row g-2 justify-content-center">
                                <div class="col-md-8">
                                    <input type="email" name="input_1" id="input_6_1" class="form-control newsletter-input" placeholder="Enter your business email address..." required value="<?= htmlspecialchars($_POST['input_1'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" id="gform_submit_button_6" class="btn btn-yelp-red w-100 py-3 fw-bold">
                                        <i class="bi bi-send-fill me-1"></i> Subscribe
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div id="confirmation-container">
                            <?php if (!empty($msg)): ?>
                                <div id='gform_confirmation_wrapper_6' class='gform_confirmation_wrapper form--newsletter'>
                                    <div id='gform_confirmation_message_6' class='gform_confirmation_message_6 gform_confirmation_message'>
                                        <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($msg) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <p class="text-secondary small mt-3 mb-0" style="font-size: 11px;">
                            We respect your privacy. Unsubscribe at any time with a single click.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-5 bg-white border-top">
        <div class="container text-center text-muted small">
            <div class="mb-2">
                <a href="index.php" class="text-decoration-none text-muted me-3">About Yelp for Business</a>
                <a href="resources.php" class="text-decoration-none text-muted me-3">Business Support</a>
                <a href="pricing.php" class="text-decoration-none text-muted me-3">Advertise with Us</a>
            </div>
            &copy; 2026 Yelp Inc. Yelp, the Yelp logo, and related marks are registered trademarks of Yelp.
        </div>
    </footer>

</body>
</html>
