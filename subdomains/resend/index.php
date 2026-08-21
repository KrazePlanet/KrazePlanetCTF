<?php
session_start();

// Pre-set valid 5-digit OTP matching the 5-digit format from HackerOne #1060541
$valid_otp = '51000';

$phone = $_SESSION['phone'] ?? '08031234567';
$nin = $_SESSION['nin'] ?? '78291048291';
$error = '';

if (isset($_GET['error']) && $_GET['error'] === 'invalid_otp') {
    $error = 'Invalid OTP code. Please check your SMS and try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>National Identity Number (NIN) SIM Verification — MTN Y'ello</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --mtn-yellow: #ffcc00;
            --mtn-yellow-hover: #e6b800;
            --mtn-dark: #000000;
            --mtn-blue: #002f6c;
            --mtn-card: #111111;
            --mtn-border: #262626;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--mtn-dark);
            color: #ffffff;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        /* Navbar */
        .mtn-nav {
            background: #000000;
            border-bottom: 3px solid var(--mtn-yellow);
            padding: 16px 0;
        }

        .mtn-brand {
            font-size: 24px;
            font-weight: 800;
            color: var(--mtn-yellow);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .yello-tag {
            background: var(--mtn-yellow);
            color: #000000;
            font-weight: 800;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .nav-link-mtn {
            font-size: 14px;
            font-weight: 600;
            color: #a3a3a3;
            text-decoration: none;
            padding: 8px 16px;
            transition: color 0.15s;
        }

        .nav-link-mtn:hover {
            color: var(--mtn-yellow);
        }

        /* Main Form Card */
        .mtn-card {
            background: var(--mtn-card);
            border: 1px solid var(--mtn-border);
            border-radius: 16px;
            padding: 36px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8);
            margin-top: 40px;
        }

        .form-control-mtn {
            background: #1c1c1c;
            border: 1px solid #333333;
            color: #ffffff;
            padding: 14px 18px;
            border-radius: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 22px;
            letter-spacing: 6px;
            text-align: center;
        }

        .form-control-mtn:focus {
            background: #1c1c1c;
            border-color: var(--mtn-yellow);
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(255, 204, 0, 0.25);
        }

        .btn-mtn {
            background: var(--mtn-yellow);
            color: #000000;
            font-weight: 800;
            font-size: 16px;
            padding: 14px 24px;
            border-radius: 8px;
            border: none;
            transition: all 0.15s;
        }

        .btn-mtn:hover {
            background: var(--mtn-yellow-hover);
            color: #000000;
            box-shadow: 0 0 20px rgba(255, 204, 0, 0.4);
        }

        .step-pill {
            background: #262626;
            color: #ffffff;
            font-weight: 700;
            font-size: 12px;
            padding: 6px 14px;
            border-radius: 20px;
        }

        .step-pill.active {
            background: var(--mtn-yellow);
            color: #000000;
        }
    </style>
</head>
<body>

    <!-- MTN Header -->
    <nav class="mtn-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="mtn-brand">
                MTN <span class="yello-tag">Y'ello</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="faq.php" class="nav-link-mtn d-none d-md-block">NIN FAQs</a>
                <a href="plans.php" class="nav-link-mtn d-none d-md-block">Data Plans</a>
                <span class="text-secondary small font-monospace">MSISDN: 08031234567</span>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="step-pill">1. Enter NIN</span>
                    <span class="step-pill active">2. Verify 5-Digit OTP</span>
                    <span class="step-pill">3. Confirmed</span>
                </div>

                <div class="mtn-card text-center">
                    <div class="d-inline-flex p-3 bg-dark border border-secondary rounded-circle mb-3">
                        <i class="bi bi-phone-vibrate text-warning fs-3"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-1">Verify SMS One-Time PIN</h3>
                    <p class="text-secondary small mb-4">
                        A 5-digit verification code has been dispatched to your primary MTN line (<strong><?= htmlspecialchars($phone) ?></strong>) to link NIN: <code><?= htmlspecialchars($nin) ?></code>.
                    </p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2 px-3 small border-0 d-flex align-items-center justify-content-center gap-2 mb-3" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">
                            <i class="bi bi-exclamation-octagon-fill"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Vulnerable OTP Form matching HackerOne POST /nim/submit or POST /nin/submit -->
                    <form method="POST" action="nim/submit.php">
                        <input type="hidden" name="phone" value="<?= htmlspecialchars($phone) ?>">
                        <input type="hidden" name="nin" value="<?= htmlspecialchars($nin) ?>">

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-light mb-2">5-Digit OTP Code</label>
                            <input type="text" name="otp" class="form-control form-control-mtn" placeholder="51000" maxlength="5" required autofocus autocomplete="off">
                        </div>

                        <button type="submit" class="btn btn-mtn w-100 fw-bold">
                            <i class="bi bi-check-circle-fill me-1"></i> Verify &amp; Link NIN to SIM
                        </button>
                    </form>

                    <div class="mt-4 text-center">
                        <span class="text-secondary small" style="font-size: 11px;">Target Endpoint: <code>POST /resend/nim/submit.php</code> &bull; Payload: <code>otp=§50000§</code> (5 digits)</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 border-top border-secondary border-opacity-25 text-center text-secondary small mt-5">
        <div class="container">
            &copy; 2026 MTN Nigeria Communications PLC &bull; Official National Identity Management Commission (NIMC) Registration Agent
        </div>
    </footer>

</body>
</html>
