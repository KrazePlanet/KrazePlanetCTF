<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['h101_user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['h101_user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_user) {
    header("Location: logout.php");
    exit;
}

// Fetch all challenges
$challenges = $pdo->query("SELECT * FROM challenges ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Calculate user points and invitations
$total_points = (int)$current_user['points'];
$invitations = floor($total_points / 26);
$points_to_next = $total_points % 26;
$progress_pct = min(100, round(($points_to_next / 26) * 100));

// Count completions per challenge for this user
$stmt_counts = $pdo->prepare("SELECT challenge_id, COUNT(*) as count FROM submissions WHERE user_id = ? GROUP BY challenge_id");
$stmt_counts->execute([$user_id]);
$completions_map = [];
while ($row = $stmt_counts->fetch(PDO::FETCH_ASSOC)) {
    $completions_map[$row['challenge_id']] = (int)$row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hacker101 CTF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: #ffffff;
            color: #212529;
            margin: 0;
            padding: 0;
        }

        /* 1:1 Hacker101 CTF Navbar matching Screenshot */
        .ctf-navbar {
            background-color: #2b303a;
            padding: 12px 0;
            color: #ffffff;
        }

        .ctf-brand {
            font-size: 20px;
            font-weight: 900;
            color: #00ea64;
            text-decoration: none;
            margin-right: 25px;
            letter-spacing: -0.3px;
        }

        .ctf-brand span {
            color: #ffffff;
        }

        .nav-link-ctf {
            color: #e2e8f0;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            margin-right: 18px;
            transition: color 0.15s;
        }

        .nav-link-ctf:hover, .nav-link-ctf.active {
            color: #ffffff;
            font-weight: 700;
        }

        /* 1:1 Alert Banner matching Screenshot F474110: congrats=many */
        .congrats-banner {
            background-color: #d4edda;
            color: #155724;
            border-radius: 4px;
            padding: 16px 20px;
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }

        /* 1:1 Progress Bar matching Screenshot */
        .ctf-progress-container {
            margin: 25px 0 20px 0;
        }

        .ctf-progress-bar-bg {
            background-color: #e9ecef;
            height: 12px;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 8px;
            max-width: 320px;
        }

        .ctf-progress-bar-fill {
            background-color: #007bff;
            height: 100%;
            border-radius: 6px;
            transition: width 0.3s ease;
        }

        .ctf-progress-text {
            font-size: 14px;
            color: #212529;
            font-weight: 500;
        }

        .ctf-progress-text a {
            color: #007bff;
            text-decoration: none;
        }

        .ctf-progress-text a:hover {
            text-decoration: underline;
        }

        /* 1:1 Challenges Table matching Screenshots */
        .ctf-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .ctf-table th {
            background-color: #111827;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            padding: 14px 18px;
            text-align: left;
            border: none;
        }

        .ctf-table td {
            padding: 16px 18px;
            font-size: 15px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .ctf-table tr:hover {
            background-color: #f8fafc;
        }

        .btn-go {
            background-color: #007bff;
            color: #ffffff !important;
            font-size: 14px;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 4px;
            border: none;
            text-decoration: none;
            display: inline-block;
            margin-right: 8px;
        }

        .btn-go:hover {
            background-color: #0056b3;
        }

        .action-links {
            font-size: 14px;
            color: #007bff;
        }

        .action-links a {
            color: #007bff;
            text-decoration: none;
        }

        .action-links a:hover {
            text-decoration: underline;
        }

        .completion-count {
            font-weight: 700;
            font-size: 15px;
        }
    </style>
</head>
<body>

    <!-- 1:1 Hacker101 CTF Navigation Header -->
    <header class="ctf-navbar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <a href="index.php" class="ctf-brand">Hacker101 <span>CTF</span></a>
                <a href="index.php" class="nav-link-ctf active">Home</a>
                <a href="#" class="nav-link-ctf">About</a>
                <a href="#" class="nav-link-ctf">How To Play</a>
                <a href="#" class="nav-link-ctf">Groups</a>
                <a href="#" class="nav-link-ctf" data-bs-toggle="modal" data-bs-target="#submitFlagModal">Submit Flag</a>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="text-white-50 small">Player: <strong class="text-white"><?= htmlspecialchars($current_user['username']) ?></strong></span>
                <a href="logout.php" class="nav-link-ctf text-danger me-0">Log Out</a>
            </div>
        </div>
    </header>

    <div class="container py-4">

        <!-- 1:1 Alert Banner matching Screenshot congrats=many -->
        <?php if (isset($_GET['congrats']) || isset($_GET['success'])): ?>
            <div class="congrats-banner shadow-sm">
                Congratulations, you found a flag!
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-warning py-2 px-3 small mb-4">
                <?php if ($_GET['error'] === 'already_submitted'): ?>
                    You have already submitted this flag.
                <?php else: ?>
                    Invalid flag code submitted. Please verify and try again.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 1:1 Progress Bar & Invitation Counter matching Screenshot -->
        <div class="ctf-progress-container">
            <div class="ctf-progress-bar-bg">
                <div class="ctf-progress-bar-fill" style="width: <?= max(5, $progress_pct) ?>%;"></div>
            </div>
            <div class="ctf-progress-text">
                You've earned <strong><?= $invitations ?> invitations</strong>. <?= $points_to_next ?> / 26 points to your next private invitation. <a href="#">Learn more about invitations</a>.
            </div>
        </div>

        <!-- Submit Flag Inline Quick Card -->
        <div class="card p-3 mb-4 bg-light border">
            <form method="POST" action="submit.php" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="fw-bold small text-dark"><i class="bi bi-flag-fill text-success me-1"></i> Submit Flag:</label>
                </div>
                <div class="col-md-6">
                    <input type="text" name="flag" class="form-control form-control-sm font-monospace" placeholder="FLAG^...^" value="FLAG^a1b2c3d4e5f6g7h8^" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">Submit</button>
                </div>
                <div class="col-auto">
                    <span class="text-secondary small font-monospace">Sample Trivial Flag: <code>FLAG^a1b2c3d4e5f6g7h8^</code></span>
                </div>
            </form>
        </div>

        <!-- 1:1 Challenges Table matching Screenshots -->
        <table class="ctf-table shadow-sm">
            <thead>
                <tr>
                    <th style="width: 20%;">Difficulty (Points)</th>
                    <th style="width: 35%;">Name</th>
                    <th style="width: 15%;">Skills</th>
                    <th style="width: 15%;">Completion</th>
                    <th style="width: 15%; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($challenges as $c): ?>
                    <?php $user_comp = $completions_map[$c['id']] ?? 0; ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($c['difficulty']) ?></td>
                        <td>
                            <strong class="text-dark"><?= htmlspecialchars($c['name']) ?></strong>
                        </td>
                        <td class="text-secondary"><?= htmlspecialchars($c['skills']) ?></td>
                        <td>
                            <span class="completion-count <?= $user_comp > $c['total_flags'] ? 'text-danger' : '' ?>">
                                <?= $user_comp ?> / <?= $c['total_flags'] ?>
                            </span>
                            <?php if ($user_comp > $c['total_flags']): ?>
                                <span class="badge bg-warning text-dark ms-1" style="font-size:10px;">Race Triggered</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <a href="#" class="btn-go" data-bs-toggle="modal" data-bs-target="#submitFlagModal">Go</a>
                            <span class="action-links">
                                <a href="#">Hints</a> | <a href="#">Restart</a>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>

    <!-- Submit Flag Modal -->
    <div class="modal fade" id="submitFlagModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Submit Flag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form method="POST" action="submit.php">
                    <div class="modal-body py-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Flag Code</label>
                            <input type="text" name="flag" class="form-control font-monospace" placeholder="FLAG^...^" value="FLAG^a1b2c3d4e5f6g7h8^" required autofocus>
                        </div>
                        <p class="text-secondary small mb-0">
                            Flags must be submitted in the exact format received (e.g. <code>FLAG^...^</code>).
                        </p>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold">
                            Submit Flag &rarr;
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
