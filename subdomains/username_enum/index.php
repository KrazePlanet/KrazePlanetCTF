<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['upchieve_user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['upchieve_user_id'];
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
    <title>Student Dashboard — UPchieve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        .navbar-custom {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 0;
        }

        .brand-logo {
            font-size: 24px;
            font-weight: 800;
            color: #10b981;
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .dash-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .subject-badge {
            background: #ecfdf5;
            color: #059669;
            font-weight: 600;
            font-size: 13px;
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #a7f3d0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 0 6px 8px 0;
            text-decoration: none;
            transition: all 0.15s;
        }

        .subject-badge:hover {
            background: #10b981;
            color: #ffffff;
            border-color: #10b981;
        }
    </style>
</head>
<body>

    <nav class="navbar-custom">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="brand-logo">UPchieve</a>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                    <i class="bi bi-mortarboard-fill me-1"></i> <?= ucfirst(htmlspecialchars($current_user['role'])) ?>
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
                    <h5 class="fw-bold text-dark mb-1">Request Free Tutoring Session</h5>
                    <p class="text-secondary small mb-4">Select a subject below to instantly match with a certified volunteer coach.</p>

                    <div>
                        <a href="#" class="subject-badge"><i class="bi bi-calculator"></i> Algebra & Geometry</a>
                        <a href="#" class="subject-badge"><i class="bi bi-graph-up"></i> Calculus & Trigonometry</a>
                        <a href="#" class="subject-badge"><i class="bi bi-virus"></i> Biology & Chemistry</a>
                        <a href="#" class="subject-badge"><i class="bi bi-lightning-charge"></i> Physics</a>
                        <a href="#" class="subject-badge"><i class="bi bi-pen"></i> Essay Review</a>
                        <a href="#" class="subject-badge"><i class="bi bi-compass"></i> College Counseling</a>
                    </div>
                </div>

                <div class="dash-card">
                    <h5 class="fw-bold text-dark mb-3">Past Learning Sessions</h5>
                    <div class="p-3 bg-light border border-secondary border-opacity-25 rounded-3 text-secondary small">
                        <i class="bi bi-info-circle me-1"></i> No past sessions recorded yet. Start by requesting a tutoring session above!
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="dash-card">
                    <h6 class="fw-bold text-dark mb-3">Profile Information</h6>
                    <div class="text-secondary small mb-2">Full Name: <strong class="text-dark"><?= htmlspecialchars($current_user['fullname']) ?></strong></div>
                    <div class="text-secondary small mb-2">Username: <strong class="text-dark"><?= htmlspecialchars($current_user['username']) ?></strong></div>
                    <div class="text-secondary small mb-2">Email: <strong class="text-dark"><?= htmlspecialchars($current_user['email']) ?></strong></div>
                    <div class="text-secondary small mb-0">Member Since: <strong class="text-dark"><?= date('M j, Y', strtotime($current_user['created_at'])) ?></strong></div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
