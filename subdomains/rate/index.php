<?php
session_start();

// Valid 2FA OTP code matching HackerOne report screenshot (5800)
$valid_2fa_code = '5800';

$error = '';
if (isset($_GET['error']) && $_GET['error'] === 'invalid_code') {
    $error = 'Invalid 2FA verification code. Please check your authenticator app and try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication — Algolia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --algolia-blue: #003dff;
            --algolia-blue-hover: #002ec7;
            --algolia-dark: #0a0b10;
            --algolia-card: #12141d;
            --algolia-border: #232736;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--algolia-dark);
            color: #f1f5f9;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        /* Navbar */
        .algolia-nav {
            background: #12141d;
            border-bottom: 1px solid var(--algolia-border);
            padding: 16px 0;
        }

        .brand-logo {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #003dff 0%, #5468ff 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 16px;
        }

        /* Main 2FA Card */
        .auth-card {
            background: var(--algolia-card);
            border: 1px solid var(--algolia-border);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
            margin-top: 50px;
        }

        .qr-placeholder {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .form-control-algolia {
            background: #0a0b10;
            border: 1px solid var(--algolia-border);
            color: #ffffff;
            padding: 14px 18px;
            border-radius: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 20px;
            letter-spacing: 4px;
            text-align: center;
        }

        .form-control-algolia:focus {
            background: #0a0b10;
            border-color: #5468ff;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(84, 104, 255, 0.25);
        }

        .btn-algolia {
            background: var(--algolia-blue);
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            padding: 14px 24px;
            border-radius: 8px;
            border: none;
            transition: all 0.15s;
        }

        .btn-algolia:hover {
            background: var(--algolia-blue-hover);
            color: #ffffff;
            box-shadow: 0 0 20px rgba(0, 61, 255, 0.5);
        }
    </style>
</head>
<body>

    <nav class="algolia-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="brand-logo">
                <div class="brand-icon"><i class="bi bi-search"></i></div>
                algolia
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary small font-monospace">developer@enterprise.io</span>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="auth-card text-center">
                    <div class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50 px-3 py-2 rounded-pill mb-3">
                        <i class="bi bi-shield-lock-fill me-1"></i> Two-Factor Authentication
                    </div>
                    <h3 class="fw-bold text-white mb-2">Verify Your 2FA Code</h3>
                    <p class="text-secondary small mb-4">Scan the QR code with Google Authenticator or enter the 4-digit verification code below.</p>

                    <!-- QR Code display matching HackerOne /users/displayqr -->
                    <div class="mb-4">
                        <div class="qr-placeholder">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=otpauth://totp/Algolia:developer@enterprise.io?secret=JBSWY3DPEHPK3PXP&issuer=Algolia" alt="2FA QR Code" width="150" height="150">
                        </div>
                        <div class="text-secondary small mt-2 font-monospace" style="font-size: 11px;">Secret: JBSWY3DPEHPK3PXP</div>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2 px-3 small border-0 d-flex align-items-center justify-content-center gap-2 mb-3" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">
                            <i class="bi bi-exclamation-octagon-fill"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Form matching HackerOne POST /users/testqr -->
                    <form method="POST" action="users/testqr.php">
                        <input type="hidden" name="utf8" value="✓">
                        <input type="hidden" name="authenticity_token" value="twHnV25SUnlKr2rqoBCjEcZ5M749eY1aLiX8gL9f7NiR4PJreIlBlBtn3X6F6qi7Z1JBQOKNgFxFVKapX4lCdg==">

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-light mb-2">Authenticator Code (GAuth)</label>
                            <input type="text" name="users[gauth_token]" class="form-control form-control-algolia" placeholder="5800" maxlength="6" required autofocus autocomplete="off">
                        </div>

                        <button type="submit" name="commit" value="Verify" class="btn btn-algolia w-100 fw-bold">
                            <i class="bi bi-shield-check me-1"></i> Verify &amp; Access Dashboard
                        </button>
                    </form>

                    <div class="mt-4 text-center">
                        <span class="text-secondary small" style="font-size: 11px;">Target Endpoint: <code>POST /rate/users/testqr.php</code> &bull; Parameter: <code>users[gauth_token]</code></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
