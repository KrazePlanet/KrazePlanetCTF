<?php
session_start();
require_once __DIR__ . '/mailer.php';

$submitted = false;
$email_val = '';

// Support both standard POST and HackerOne query string POST /?p=subscribe&id=1
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_val = trim($_POST['email'] ?? $_POST['emailconfirm'] ?? '');

    if (!empty($email_val)) {
        // Send real SMTP confirmation email without any rate limiting (HackerOne #224927)
        sendNextcloudConfirmationEmail($email_val);
        $submitted = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribe to our Newsletters — Nextcloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --nc-blue: #0082c9;
            --nc-blue-dark: #006aa4;
            --nc-bg: #ffffff;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #ffffff;
            color: #333333;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        /* Top Bar Header */
        .nc-header {
            background: #0082c9;
            color: #ffffff;
            padding: 30px 0;
            box-shadow: 0 4px 15px rgba(0, 130, 201, 0.2);
        }

        .nc-logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nc-circles {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .nc-circle {
            width: 14px;
            height: 14px;
            border: 3px solid #ffffff;
            border-radius: 50%;
        }

        .nc-circle.large {
            width: 22px;
            height: 22px;
            border-width: 4px;
        }

        .nc-brand-text {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #ffffff;
            text-decoration: none;
        }

        /* Main Form Container */
        .content-card {
            background: #ffffff;
            padding: 40px 0;
        }

        .subscribe-form-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }

        .form-label-nc {
            font-weight: 700;
            font-size: 13px;
            color: #475569;
            margin-bottom: 6px;
        }

        .form-control-nc {
            border: 1px solid #cbd5e1;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-control-nc:focus {
            border-color: #0082c9;
            box-shadow: 0 0 0 3px rgba(0, 130, 201, 0.15);
        }

        .btn-nc {
            background: #0082c9;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            padding: 12px 28px;
            border-radius: 6px;
            border: none;
            transition: background 0.15s;
        }

        .btn-nc:hover {
            background: #006aa4;
            color: #ffffff;
        }

        .confirmation-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 28px;
            margin-bottom: 24px;
        }

        .nc-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px 0;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Nextcloud Header -->
    <header class="nc-header">
        <div class="container">
            <div class="nc-logo-container">
                <div class="nc-circles">
                    <div class="nc-circle"></div>
                    <div class="nc-circle large"></div>
                    <div class="nc-circle"></div>
                </div>
                <a href="index.php" class="nc-brand-text">Nextcloud</a>
            </div>
        </div>
    </header>

    <!-- Main Body Content -->
    <main class="content-card">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <?php if ($submitted): ?>
                        <!-- 1:1 Confirmation matching HackerOne #224927 screenshot -->
                        <div class="confirmation-box">
                            <h3 class="fw-bold text-success mb-3"><i class="bi bi-check-circle-fill me-2"></i>Thank you for subscribing to our newsletter!</h3>
                            <p class="mb-2 text-dark">
                                Your email address (<code><?= htmlspecialchars($email_val) ?></code>) has been added to our system. You will receive a message with a request to confirm your membership. Please make sure to click the link in that message to confirm your subscription.
                            </p>
                            <div class="mt-4">
                                <a href="index.php" class="btn btn-sm btn-outline-secondary">&larr; Subscribe another email</a>
                            </div>
                        </div>
                    <?php else: ?>

                        <div class="mb-4">
                            <h1 class="fw-bold fs-2 text-dark mb-2">Subscribe to our Newsletters</h1>
                            <p class="text-muted">
                                Stay up to date with the latest Nextcloud releases, security advisories, ecosystem updates, and enterprise whitepapers.
                            </p>
                        </div>

                        <div class="subscribe-form-box">
                            <form method="POST" action="index.php?p=subscribe&id=1">
                                <input type="hidden" name="htmlemail" value="1">
                                <input type="hidden" name="list[3]" value="signup">
                                <input type="hidden" name="listname[3]" value="Nextcloud newsletter">
                                <input type="hidden" name="VerificationCodeX" value="">

                                <div class="mb-3">
                                    <label class="form-label-nc">Your Email Address</label>
                                    <input type="email" name="email" class="form-control form-control-nc" placeholder="name@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label-nc">Confirm Email Address</label>
                                    <input type="email" name="emailconfirm" class="form-control form-control-nc" placeholder="name@example.com" required value="<?= htmlspecialchars($_POST['emailconfirm'] ?? '') ?>">
                                </div>

                                <div class="mb-4">
                                    <label class="form-label-nc">Select Newsletter Subscriptions</label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" checked id="chk_monthly">
                                        <label class="form-check-label small text-dark" for="chk_monthly">
                                            <strong>Nextcloud Monthly Newsletter</strong> &bull; Feature highlights and community blogs
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" checked id="chk_sec">
                                        <label class="form-check-label small text-dark" for="chk_sec">
                                            <strong>Security &amp; Maintenance Advisories</strong> &bull; Critical CVE notifications and patch notes
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" name="subscribe" value="Subscribe to the newsletter" class="btn-nc">
                                    <i class="bi bi-envelope-check me-1"></i> Subscribe to the newsletter
                                </button>
                            </form>
                        </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="nc-footer">
        <div class="container">
            &copy; 2026 Nextcloud GmbH &bull; <a href="advisories.php" class="text-secondary text-decoration-none">Security Advisories</a> &bull; <a href="index.php" class="text-secondary text-decoration-none">Privacy Policy</a>
        </div>
    </footer>

</body>
</html>
