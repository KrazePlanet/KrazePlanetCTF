<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['h1_user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['h1_user_id'];
$stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_u->execute([$user_id]);
$current_user = $stmt_u->fetch(PDO::FETCH_ASSOC);

if (!$current_user) {
    header("Location: logout.php");
    exit;
}

$team_id = 'dGVhbV8xMjk0';
$stmt_t = $pdo->prepare("SELECT * FROM program_teams WHERE id = ?");
$stmt_t->execute([$team_id]);
$program = $stmt_t->fetch(PDO::FETCH_ASSOC);

// Fetch all credentials claimed by this user
$stmt_creds = $pdo->prepare("SELECT c.*, u.created_at as claimed_time FROM credential_pool c JOIN user_claims u ON c.cred_gid = u.cred_gid WHERE u.user_id = ? AND u.team_id = ? ORDER BY u.id ASC");
$stmt_creds->execute([$user_id, $team_id]);
$claimed_creds = $stmt_creds->fetchAll(PDO::FETCH_ASSOC);

// Handle single UI claim button
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'claim_ui') {
    // Forward to GraphQL internal logic
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM user_claims WHERE user_id = ? AND team_id = ?");
    $stmt_check->execute([$user_id, $team_id]);
    if ($stmt_check->fetchColumn() == 0) {
        $stmt_pool = $pdo->prepare("SELECT * FROM credential_pool WHERE team_id = ? AND claimed_by_user_id IS NULL ORDER BY id ASC LIMIT 1");
        $stmt_pool->execute([$team_id]);
        $cred = $stmt_pool->fetch(PDO::FETCH_ASSOC);
        if ($cred) {
            $pdo->prepare("UPDATE credential_pool SET claimed_by_user_id = ?, claimed_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$user_id, $cred['id']]);
            $pdo->prepare("INSERT INTO user_claims (user_id, team_id, cred_gid) VALUES (?, ?, ?)")->execute([$user_id, $team_id, $cred['cred_gid']]);
            header("Location: index.php?claimed=1");
            exit;
        }
    } else {
        $msg = 'You have already claimed credentials for this program.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($program['name']) ?> — HackerOne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0b0f19;
            color: #f3f4f6;
            margin: 0;
            padding: 0;
        }

        .h1-navbar {
            background-color: #111827;
            border-bottom: 1px solid #1f2937;
            padding: 14px 0;
        }

        .h1-brand {
            font-size: 22px;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: -0.5px;
            text-decoration: none;
        }

        .h1-brand span {
            color: #4f46e5;
        }

        .program-hero {
            background: linear-gradient(180deg, #111827 0%, #0b0f19 100%);
            border-bottom: 1px solid #1f2937;
            padding: 36px 0 24px 0;
        }

        .program-avatar {
            width: 72px;
            height: 72px;
            background: #4f46e5;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        }

        .h1-card {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .btn-claim {
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            transition: background 0.15s;
        }

        .btn-claim:hover {
            background-color: #4338ca;
            color: #ffffff;
        }

        .cred-box {
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 8px;
            padding: 16px 20px;
            margin-top: 14px;
        }

        .scope-table th {
            background: #1f2937;
            color: #9ca3af;
            font-size: 12px;
            text-transform: uppercase;
            border: none;
            padding: 12px 16px;
        }

        .scope-table td {
            border-color: #1f2937;
            color: #e5e7eb;
            padding: 14px 16px;
            font-size: 14px;
        }

        .nav-tabs-h1 {
            border-bottom: 1px solid #1f2937;
        }

        .nav-tabs-h1 .nav-link {
            color: #9ca3af;
            border: none;
            border-bottom: 2px solid transparent;
            font-weight: 600;
            padding: 12px 18px;
        }

        .nav-tabs-h1 .nav-link.active {
            background: transparent;
            color: #ffffff;
            border-bottom: 2px solid #4f46e5;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header class="h1-navbar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-4">
                <a href="index.php" class="h1-brand">hacker<span>one</span></a>
                <span class="badge bg-dark border border-secondary text-secondary font-monospace">Bounty Program</span>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary small">
                    <i class="bi bi-person-fill me-1"></i> <strong><?= htmlspecialchars($current_user['username']) ?></strong>
                    <span class="badge bg-secondary ms-1"><?= $current_user['reputation'] ?> rep</span>
                </span>
                <a href="logout.php" class="btn btn-outline-secondary btn-sm" style="font-size:12px;">Logout</a>
            </div>
        </div>
    </header>

    <!-- Program Banner -->
    <div class="program-hero">
        <div class="container">
            <div class="d-flex align-items-center gap-4">
                <div class="program-avatar">
                    S
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h2 class="fw-bold mb-0 text-white"><?= htmlspecialchars($program['name']) ?></h2>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50">Private</span>
                    </div>
                    <p class="text-secondary small mb-0"><?= htmlspecialchars($program['description']) ?></p>
                </div>
                <div class="ms-auto text-end">
                    <span class="text-secondary small d-block">Bounty Range</span>
                    <strong class="text-success fs-5"><?= htmlspecialchars($program['bounty_range']) ?></strong>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs-h1 mt-4">
                <li class="nav-item">
                    <a class="nav-link active" href="#"><i class="bi bi-shield-check me-1"></i> Policy &amp; Scope</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-trophy me-1"></i> Hacktivity</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-bar-chart me-1"></i> Leaderboard</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row">
            
            <!-- Left Column: Scope and Policy -->
            <div class="col-lg-8">
                
                <?php if (!empty($msg)): ?>
                    <div class="alert alert-warning border-0 py-2 px-3 small mb-4" style="background:#78350f; color:#fef3c7;">
                        <i class="bi bi-exclamation-circle-fill me-1"></i> <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>

                <div class="h1-card">
                    <h5 class="fw-bold text-white mb-3"><i class="bi bi-bullseye me-2 text-primary"></i> Program Scope</h5>
                    <div class="table-responsive">
                        <table class="table scope-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Asset</th>
                                    <th>Type</th>
                                    <th>Eligible for Bounty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>*.shopify-sandbox.io</code></td>
                                    <td><span class="badge bg-secondary">Domain</span></td>
                                    <td><span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Yes</span></td>
                                </tr>
                                <tr>
                                    <td><code>api.staging.shopify.com</code></td>
                                    <td><span class="badge bg-secondary">API</span></td>
                                    <td><span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Yes</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="h1-card">
                    <h5 class="fw-bold text-white mb-2"><i class="bi bi-file-earmark-text me-2 text-primary"></i> Testing Guidelines</h5>
                    <p class="text-secondary small mb-3">
                        Researchers must use their provisioned testing credentials for authenticated testing. Do not access real customer data or attempt social engineering against program staff.
                    </p>
                    <div class="p-3 bg-dark rounded border border-secondary border-opacity-25 text-secondary small">
                        <strong>GraphQL Endpoint:</strong> <code>POST /graphql</code> (or <code>/flashdisk/graphql.php</code>)<br>
                        <strong>Team ID:</strong> <code>dGVhbV8xMjk0</code>
                    </div>
                </div>

            </div>

            <!-- Right Column: Program Credentials Claiming Area -->
            <div class="col-lg-4">

                <div class="h1-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="fw-bold text-white mb-0"><i class="bi bi-key-fill me-2 text-warning"></i> Test Credentials</h5>
                        <span class="badge bg-secondary font-monospace" style="font-size:11px;">1 per researcher</span>
                    </div>
                    <p class="text-secondary small mb-3">Claim a dedicated sandbox account from the program's credential pool to begin testing.</p>

                    <?php if (empty($claimed_creds)): ?>
                        <form method="POST" action="index.php" id="claimForm">
                            <input type="hidden" name="action" value="claim_ui">
                            <button type="submit" class="btn btn-claim w-100 py-2">
                                <i class="bi bi-plus-circle me-1"></i> Claim Test Credentials
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-success small fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Claimed Credentials (<?= count($claimed_creds) ?>)</span>
                        </div>

                        <?php foreach ($claimed_creds as $idx => $c): ?>
                            <div class="cred-box mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-dark border border-secondary text-light font-monospace" style="font-size:10px;"><?= htmlspecialchars($c['cred_gid']) ?></span>
                                    <span class="text-muted" style="font-size:11px;">#<?= $idx + 1 ?></span>
                                </div>
                                <div class="mb-1 small">
                                    <span class="text-secondary">Email:</span> <code class="text-white"><?= htmlspecialchars($c['email']) ?></code>
                                </div>
                                <div class="mb-1 small">
                                    <span class="text-secondary">Password:</span> <code class="text-warning"><?= htmlspecialchars($c['password']) ?></code>
                                </div>
                                <div class="small">
                                    <span class="text-secondary">Private ID:</span> <code class="text-info"><?= htmlspecialchars($c['private_id']) ?></code>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count($claimed_creds) > 1): ?>
                            <div class="alert alert-warning py-2 px-3 small border-0 mt-3" style="background:#78350f; color:#fef3c7;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Multiple test credentials claimed for this researcher account!
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>

</body>
</html>
