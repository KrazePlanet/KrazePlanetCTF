<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Handle plan upgrades using credits
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upgrade_plan') {
    $plan = trim($_POST['plan'] ?? 'Pro');
    $cost = ($plan === 'Business+') ? 150 : 87; // monthly cost

    $stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($u && (int)$u['credits'] >= $cost) {
        $stmt_upd = $pdo->prepare("UPDATE users SET credits = credits - ?, plan = ? WHERE id = ?");
        $stmt_upd->execute([$cost, $plan, $user_id]);

        $stmt_ins = $pdo->prepare("INSERT INTO billing_history (user_id, date_str, item, amount) VALUES (?, ?, ?, ?)");
        $stmt_ins->execute([$user_id, date('Y-m-d'), "Upgraded to Slack {$plan} Plan", -$cost]);
        $msg = "Successfully upgraded your workspace to Slack {$plan}!";
    } else {
        $msg = "Insufficient credits. You need at least \${$cost} in credits to upgrade to {$plan}.";
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$credits = $user ? (int)$user['credits'] : 0;

$stmt_hist = $pdo->prepare("SELECT * FROM billing_history WHERE user_id = ? ORDER BY id ASC");
$stmt_hist->execute([$user_id]);
$history = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing — Slack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8f8f8;
            color: #1d1c1d;
            margin: 0;
            padding: 0;
        }

        .slack-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 0;
        }

        .slack-brand {
            font-size: 22px;
            font-weight: 800;
            color: #1d1c1d;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .slack-logo-icon {
            width: 26px;
            height: 26px;
            background: linear-gradient(135deg, #ECB22E, #2EB67D, #E01E5A, #36C5F0);
            border-radius: 6px;
        }

        .billing-title-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 20px;
        }

        /* 1:1 Credit Banner matching HackerOne F117127 Screenshot */
        .credit-banner {
            background: #ffffff;
            border-left: 4px solid #2bac76;
            border-radius: 4px;
            padding: 16px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 28px;
        }

        .credit-banner .credit-text {
            font-size: 15px;
            color: #1d1c1d;
        }

        .credit-banner .credit-text strong {
            font-size: 17px;
            color: #1d1c1d;
        }

        .billing-tabs {
            display: flex;
            gap: 24px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }

        .billing-tab-link {
            font-size: 15px;
            font-weight: 600;
            color: #1264a3;
            text-decoration: none;
            padding-bottom: 12px;
            border-bottom: 2px solid transparent;
        }

        .billing-tab-link.active {
            color: #1d1c1d;
            font-weight: 700;
            border-bottom: 2px solid #1d1c1d;
        }

        .table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin-bottom: 30px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th {
            border-bottom: 2px solid #2bac76;
            padding: 12px 8px;
            font-size: 14px;
            font-weight: 700;
            color: #1d1c1d;
            text-align: left;
        }

        .history-table td {
            padding: 14px 8px;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
        }

        .credited-amount {
            color: #2bac76;
            font-weight: 700;
            text-align: right;
        }

        .debited-amount {
            color: #e11d48;
            font-weight: 700;
            text-align: right;
        }

        .plan-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
        }
    </style>
</head>
<body>

    <header class="slack-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="slack-brand">
                <div class="slack-logo-icon"></div>
                slack
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="survey.php" class="btn btn-outline-success btn-sm fw-bold"><i class="bi bi-gift-fill me-1"></i> Account Setup Survey ($100 Credits)</a>
                <a href="index.php" class="btn btn-outline-primary btn-sm">&larr; Return to Workspace</a>
                <span class="text-secondary small ms-2 d-none d-md-inline">Signed in as <strong><?= htmlspecialchars($user['email']) ?></strong></span>
            </div>
        </div>
    </header>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                
                <?php if (!empty($msg)): ?>
                    <div class="alert alert-info py-2 px-3 small mb-3"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <div class="billing-title-bar">
                    <i class="bi bi-credit-card-2-front text-success"></i>
                    <span>Billing</span>
                </div>

                <!-- 1:1 Credit Banner matching HackerOne F117127 Screenshot: You have $X in credits! -->
                <div class="credit-banner">
                    <span style="font-size: 22px;">💸</span>
                    <div class="credit-text">
                        You have <strong>$<?= number_format($credits) ?></strong> in credits!<br>
                        <span class="text-secondary small">Learn more in our <a href="#" class="text-primary text-decoration-none fw-semibold">Guide to billing at Slack</a>.</span>
                    </div>
                </div>

                <!-- Tabs matching HackerOne Screenshot -->
                <div class="billing-tabs">
                    <a href="#" class="billing-tab-link">Overview</a>
                    <a href="#" class="billing-tab-link active">History</a>
                    <a href="survey.php" class="billing-tab-link text-success fw-bold"><i class="bi bi-gift-fill me-1"></i> Earn Credits (Survey)</a>
                    <a href="#" class="billing-tab-link">Settings</a>
                    <a href="#" class="billing-tab-link">Contacts</a>
                    <a href="#" class="billing-tab-link">Team Changes</a>
                    <a href="#" class="billing-tab-link">Payment Methods</a>
                </div>

                <!-- History Table Card matching HackerOne Screenshot -->
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="btn btn-sm btn-outline-secondary text-dark" style="font-size: 13px;">&larr; Previous</span>
                        <h6 class="fw-bold mb-0 text-dark"><?= date('F Y') ?></h6>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control form-control-sm" placeholder="Pick Date" style="width: 130px;">
                            <button class="btn btn-sm btn-success px-3" style="background:#2bac76; border:none;">Go To</button>
                        </div>
                    </div>

                    <table class="history-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Date</th>
                                <th style="width: 50%;">Item</th>
                                <th style="width: 25%; text-align: right;">Charges</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($history)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        No billing ledger items recorded yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars($h['date_str']) ?></td>
                                        <td><?= htmlspecialchars($h['item']) ?></td>
                                        <?php if ($h['amount'] >= 0): ?>
                                            <td class="credited-amount">Credited $<?= number_format($h['amount']) ?></td>
                                        <?php else: ?>
                                            <td class="debited-amount">Charged $<?= number_format(abs($h['amount'])) ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Subscription Plans Card -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="plan-card">
                            <h5 class="fw-bold">Slack Pro</h5>
                            <p class="text-secondary small">Unlimited message archive, unlimited integrations, and group huddles.</p>
                            <div class="fs-4 fw-bold mb-3">$87 <span class="fs-6 fw-normal text-secondary">/ year (or with credits)</span></div>
                            <form method="POST" action="billing.php">
                                <input type="hidden" name="action" value="upgrade_plan">
                                <input type="hidden" name="plan" value="Pro">
                                <button type="submit" class="btn btn-outline-dark w-100 fw-bold">Upgrade to Pro</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="plan-card">
                            <h5 class="fw-bold">Slack Business+</h5>
                            <p class="text-secondary small">SAML-based single sign-on (SSO), data exports, and 99.99% guaranteed uptime SLA.</p>
                            <div class="fs-4 fw-bold mb-3">$150 <span class="fs-6 fw-normal text-secondary">/ year (or with credits)</span></div>
                            <form method="POST" action="billing.php">
                                <input type="hidden" name="action" value="upgrade_plan">
                                <input type="hidden" name="plan" value="Business+">
                                <button type="submit" class="btn btn-dark w-100 fw-bold" style="background:#4A154B;">Upgrade to Business+</button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
