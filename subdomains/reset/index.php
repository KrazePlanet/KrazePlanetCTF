<?php
session_start();
if (!isset($_SESSION['business_email'])) {
    $_SESSION['business_email'] = 'owner@artisanbakery.com';
    $_SESSION['business_name'] = 'Artisan Bakery & Cafe';
}

$sent_notice = isset($_GET['sent']) && $_GET['sent'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yelp for Business Owners — Messaging Inbox</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --yelp-red: #d32323;
            --yelp-red-hover: #bd1f1f;
            --yelp-bg: #f5f5f5;
            --yelp-banner-bg: #fff3cd;
            --yelp-banner-border: #ffeeba;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #ffffff;
            color: #333333;
            margin: 0;
            padding: 0;
            font-size: 14px;
        }

        /* Top Blue Notice */
        .top-notice {
            background-color: #e8f4f8;
            padding: 10px 0;
            font-size: 13px;
            color: #0073bb;
            border-bottom: 1px solid #d4ebf2;
        }

        /* Red Yelp Header */
        .yelp-header {
            background-color: var(--yelp-red);
            color: #ffffff;
            padding: 14px 0;
        }

        .yelp-brand {
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .biz-title {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            margin-left: 6px;
        }

        .nav-item-yelp {
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            padding: 6px 12px;
            border-radius: 4px;
        }

        .nav-item-yelp.active {
            background: rgba(0, 0, 0, 0.2);
        }

        /* Warning Yellow Banner */
        .claim-banner {
            background-color: var(--yelp-banner-bg);
            border-bottom: 1px solid var(--yelp-banner-border);
            padding: 14px 0;
            color: #856404;
            font-size: 13px;
        }

        /* Left Navigation */
        .sidebar-link {
            display: block;
            padding: 10px 14px;
            color: #333333;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border-radius: 4px;
            margin-bottom: 2px;
        }

        .sidebar-link:hover, .sidebar-link.active {
            background-color: #f5f5f5;
            color: var(--yelp-red);
        }

        .btn-resend {
            background-color: #f7f7f7;
            border: 1px solid #cccccc;
            color: #333333;
            font-weight: 600;
            font-size: 13px;
            padding: 8px 18px;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.15s;
        }

        .btn-resend:hover {
            background-color: #e6e6e6;
            border-color: #adadad;
            color: #333333;
        }

        .inbox-alert-box {
            background-color: #ffffff;
            border: 1px solid #e6e6e6;
            border-radius: 6px;
            padding: 24px;
            margin-top: 16px;
        }
    </style>
</head>
<body>

    <!-- Top Terms Notice -->
    <div class="top-notice">
        <div class="container d-flex justify-content-between align-items-center">
            <span>Hey there, we've updated our <a href="#" class="text-primary fw-bold text-decoration-none">Terms of Service</a> and <a href="#" class="text-primary fw-bold text-decoration-none">Privacy Policy</a>. Take a look to see what's new.</span>
            <button class="btn btn-sm btn-light border py-0 px-2 text-muted" style="font-size: 11px;">Close</button>
        </div>
    </div>

    <!-- Yelp Red Header -->
    <header class="yelp-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <a href="index.php" class="yelp-brand">
                    yelp<i class="bi bi-asterisk"></i>
                </a>
                <span class="biz-title">for Business Owners</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="nav-item-yelp active">Your Business</a>
                <a href="settings.php" class="nav-item-yelp">Account Settings</a>
                <a href="index.php" class="nav-item-yelp">Support</a>
                <a href="index.php" class="nav-item-yelp">Inbox</a>
                <span class="text-white fw-bold ms-3"><i class="bi bi-telephone-fill me-1"></i> (877) 767-9357</span>
            </div>
        </div>
    </header>

    <!-- Claim Confirmation Banner -->
    <div class="claim-banner">
        <div class="container">
            <strong>Thank you for claiming <?= htmlspecialchars($_SESSION['business_name']) ?> on Yelp.</strong> Your page won't appear on Yelp until Yelp moderators approve your business. You will be notified shortly when the status changes. <a href="#" class="fw-bold text-dark">Learn more</a>
        </div>
    </div>

    <!-- Main Body Layout (1:1 matching HackerOne F683815 biz.png) -->
    <div class="container py-4">
        <div class="row">
            
            <!-- Left Sidebar -->
            <div class="col-md-3">
                <div class="mb-4">
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($_SESSION['business_name']) ?> <a href="#" class="text-primary"><i class="bi bi-box-arrow-up-right small"></i></a></h5>
                    <div class="text-muted small">
                        1125 Mission St San Francisco<br>
                        CA 94103 USA<br>
                        San Francisco, CA 94103
                    </div>
                    <div class="mt-2">
                        <a href="#" class="text-primary text-decoration-none small fw-bold">+ Add a Location</a>
                    </div>
                </div>

                <nav class="d-flex flex-column">
                    <a href="#" class="sidebar-link">Activity</a>
                    <a href="#" class="sidebar-link">Yelp Ads</a>
                    <a href="#" class="sidebar-link">Page Upgrades</a>
                    <a href="#" class="sidebar-link">Call to Action</a>
                    <a href="#" class="sidebar-link">Business Information</a>
                    <a href="#" class="sidebar-link">Reviews</a>
                    <a href="index.php" class="sidebar-link active">Inbox</a>
                </nav>
            </div>

            <!-- Main Inbox Content -->
            <div class="col-md-9 border-start ps-md-4">
                <h2 class="fw-bold mb-3">Inbox</h2>

                <?php if ($sent_notice): ?>
                    <div class="alert alert-success py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Confirmation email has been resent to <strong><?= htmlspecialchars($_SESSION['business_email']) ?></strong>.</span>
                    </div>
                <?php endif; ?>

                <div class="inbox-alert-box">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-envelope-paper text-success fs-3"></i>
                        <div>
                            <p class="mb-3 text-dark fw-semibold">
                                To activate this feature please click the link in the confirmation email you received.
                            </p>

                            <!-- Vulnerable Resend Confirmation Form matching HackerOne POST /welcome/resend_confirmation -->
                            <form method="POST" action="welcome/resend_confirmation.php">
                                <input type="hidden" name="csrftok" value="dbe6010b3183f275b85d61f6dbce0417">
                                <input type="hidden" name="return_url" value="/messaging/oj517fznD2Gw2v5CUUIw_Q/inbox">
                                <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['business_email']) ?>">

                                <button type="submit" class="btn btn-resend">
                                    Re-send email now
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-light rounded border text-muted small">
                    <strong>Target Endpoint:</strong> <code>POST /reset/welcome/resend_confirmation.php</code><br>
                    <strong>Parameters:</strong> <code>csrftok=...&amp;return_url=...&amp;email=victim@domain.com</code>
                </div>

            </div>

        </div>
    </div>

</body>
</html>
