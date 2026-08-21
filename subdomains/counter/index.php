<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['auth_user_id'])) {
    if (!empty($_SESSION['pending_user_id'])) {
        header("Location: verify.php");
        exit;
    }
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['auth_user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_user) {
    header("Location: logout.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — CodeShack Developer Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #090d16;
            color: #f1f5f9;
            margin: 0;
            padding: 0;
        }

        .navbar-custom {
            background-color: #111726;
            border-bottom: 1px solid #1e293b;
            padding: 14px 0;
        }

        .brand-logo {
            font-size: 22px;
            font-weight: 800;
            color: #38bdf8;
            letter-spacing: -0.5px;
            text-decoration: none;
        }

        .dash-card {
            background: #111726;
            border: 1px solid #1e293b;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .api-key-box {
            background: #090d16;
            border: 1px solid #1e293b;
            border-radius: 8px;
            padding: 12px 16px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13.5px;
            color: #38bdf8;
        }
    </style>
</head>
<body>

    <nav class="navbar-custom">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="brand-logo"><i class="bi bi-terminal-fill me-2"></i>CodeShack</a>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                    <i class="bi bi-check-circle-fill me-1"></i> Verified Developer
                </span>
                <span class="text-secondary small">
                    <?= htmlspecialchars($current_user['fullname']) ?> (<strong><?= htmlspecialchars($current_user['username']) ?></strong>)
                </span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm" style="font-size:12px;">Log Out</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="dash-card">
                    <h5 class="fw-bold text-white mb-1">Developer API Access</h5>
                    <p class="text-secondary small mb-4">Your account is fully verified. Use the API credentials below to integrate CodeShack services.</p>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-light mb-1">Production Secret Key</label>
                        <div class="api-key-box d-flex justify-content-between align-items-center">
                            <span>csk_live_<?= substr(md5($current_user['email']), 0, 24) ?></span>
                            <i class="bi bi-copy text-secondary cursor-pointer" title="Copy Key"></i>
                        </div>
                    </div>
                </div>

                <div class="dash-card">
                    <h5 class="fw-bold text-white mb-3">Active Services & Webhooks</h5>
                    <div class="d-flex align-items-center justify-content-between p-3 bg-dark border border-secondary border-opacity-25 rounded-3 mb-2">
                        <div>
                            <strong class="text-white small d-block">Authentication Service</strong>
                            <span class="text-secondary small">Email & OTP verification active</span>
                        </div>
                        <span class="badge bg-primary">Active</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="dash-card">
                    <h6 class="fw-bold text-white mb-3">Account Information</h6>
                    <div class="text-secondary small mb-2">Full Name: <strong class="text-light"><?= htmlspecialchars($current_user['fullname']) ?></strong></div>
                    <div class="text-secondary small mb-2">Email: <strong class="text-light"><?= htmlspecialchars($current_user['email']) ?></strong></div>
                    <div class="text-secondary small mb-2">Status: <strong class="text-success">Email Verified</strong></div>
                    <div class="text-secondary small mb-0">Total OTP Resends: <strong class="text-light"><?= (int)$current_user['resend_count'] ?></strong></div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
