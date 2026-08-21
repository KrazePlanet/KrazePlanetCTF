<?php
session_start();
if (empty($_SESSION['authenticated'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Training Dashboard — On Running</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f1015; color: #ffffff; margin: 0; }
        .on-navbar { background: #18181b; border-bottom: 1px solid #27272a; padding: 16px 0; }
        .on-logo-badge { background: #ffffff; color: #000000; font-weight: 900; font-size: 16px; padding: 2px 8px; border-radius: 4px; }
        .module-card { background: #18181b; border: 1px solid #27272a; border-radius: 12px; padding: 24px; transition: all 0.2s; }
        .module-card:hover { border-color: #52525b; transform: translateY(-3px); }
        .progress-bar-on { background: #e5ff00; }
    </style>
</head>
<body>
    <nav class="on-navbar sticky-top">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <span class="on-logo-badge">On</span>
                <span class="fw-bold fs-6">Partner Bootcamp Dashboard</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary small"><i class="bi bi-person-check-fill text-success me-1"></i> <?= htmlspecialchars($_SESSION['user_email']) ?></span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Log Out</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold fs-3 mb-1">Welcome back, Partner Manager!</h1>
                <p class="text-secondary small mb-0">Your retail branch is currently <strong>85% Certified</strong> for the Fall 2026 Footwear Collection.</p>
            </div>
            <span class="badge bg-success px-3 py-2">Partner ID: <?= htmlspecialchars($_SESSION['partner_id'] ?? 'ON-88912') ?></span>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="module-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary">Core Curriculum</span>
                        <span class="text-secondary small">Module 1/4</span>
                    </div>
                    <h5 class="fw-bold mb-2">Cloudmonster 2 &bull; Max Cushioning</h5>
                    <p class="text-secondary small mb-3">Master the dual-density Helion™ superfoam mechanics and Speedboard® propulsion geometry for distance runners.</p>
                    <div class="progress mb-3" style="height: 6px; background: #27272a;">
                        <div class="progress-bar progress-bar-on" style="width: 100%"></div>
                    </div>
                    <span class="badge bg-success bg-opacity-25 text-success small">Completed &bull; Certified</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="module-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-warning text-dark">In Progress</span>
                        <span class="text-secondary small">Module 2/4</span>
                    </div>
                    <h5 class="fw-bold mb-2">Cloudrunner 2 Waterproof Tech</h5>
                    <p class="text-secondary small mb-3">Learn breathable membrane waterproofing, enhanced cradle stability, and all-weather traction sales points.</p>
                    <div class="progress mb-3" style="height: 6px; background: #27272a;">
                        <div class="progress-bar progress-bar-on" style="width: 65%"></div>
                    </div>
                    <a href="#" class="btn btn-sm btn-outline-light w-100">Resume Training &rarr;</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="module-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-secondary">Upcoming</span>
                        <span class="text-secondary small">Module 3/4</span>
                    </div>
                    <h5 class="fw-bold mb-2">Swiss Circular Cyclon™ Program</h5>
                    <p class="text-secondary small mb-3">Sustainable 100% recyclable subscription running gear made from castor beans. Customer pitching guidelines.</p>
                    <div class="progress mb-3" style="height: 6px; background: #27272a;">
                        <div class="progress-bar progress-bar-on" style="width: 0%"></div>
                    </div>
                    <a href="#" class="btn btn-sm btn-secondary w-100 disabled">Locked</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
