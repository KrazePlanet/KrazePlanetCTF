<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pricing — WakaTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0b0f19; color: #f3f4f6; }
        .nav-waka { background: #111827; border-bottom: 1px solid #1f2937; padding: 16px 0; }
    </style>
</head>
<body>
    <nav class="nav-waka">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="text-white text-decoration-none fw-bold fs-5"><i class="bi bi-clock-history text-info me-2"></i> WakaTime Pricing</a>
            <a href="index.php" class="btn btn-outline-light btn-sm">&larr; Return Home</a>
        </div>
    </nav>
    <div class="container py-5 text-center">
        <h1 class="fw-bold fs-2 mb-2">Simple, Developer-Friendly Pricing</h1>
        <p class="text-secondary mb-5">Free for individuals forever. Upgrade for team leaderboards and unlimited dashboard history.</p>
        <div class="row g-4 justify-content-center text-start">
            <div class="col-md-4">
                <div class="card bg-dark text-white p-4 border-secondary border-opacity-25 rounded-4 h-100">
                    <h5 class="fw-bold mb-1">Free Tier</h5>
                    <div class="fs-3 fw-bold my-3">$0 <span class="fs-6 text-secondary font-normal">/ forever</span></div>
                    <ul class="text-secondary small ps-3 mb-4">
                        <li class="mb-2">Unlimited IDE plugin integrations</li>
                        <li class="mb-2">7 days of dashboard metrics history</li>
                        <li class="mb-2">Public profile coding badge</li>
                    </ul>
                    <a href="index.php" class="btn btn-outline-light w-100">Get Started</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark text-white p-4 border-2 border-info rounded-4 h-100">
                    <span class="badge bg-info text-dark mb-2" style="width: fit-content;">Premium</span>
                    <h5 class="fw-bold mb-1">Developer Pro</h5>
                    <div class="fs-3 fw-bold my-3">$9 <span class="fs-6 text-secondary font-normal">/ month</span></div>
                    <ul class="text-secondary small ps-3 mb-4">
                        <li class="mb-2">Unlimited dashboard metrics history</li>
                        <li class="mb-2">Export stats to CSV, JSON, and BigQuery</li>
                        <li class="mb-2">Private team leaderboards</li>
                    </ul>
                    <a href="index.php" class="btn btn-info w-100">Start Free Pro Trial</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
