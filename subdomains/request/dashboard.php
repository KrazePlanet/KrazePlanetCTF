<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — WakaTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0b0f19; color: #f3f4f6; }
        .nav-waka { background: #111827; border-bottom: 1px solid #1f2937; padding: 16px 0; }
        .card-dark { background: #111827; border: 1px solid #1f2937; border-radius: 12px; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body>
    <nav class="nav-waka">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="text-white text-decoration-none fw-bold fs-5"><i class="bi bi-clock-history text-info me-2"></i> WakaTime Dashboard</a>
            <a href="index.php" class="btn btn-outline-light btn-sm">&larr; Return Home</a>
        </div>
    </nav>
    <div class="container py-5">
        <h1 class="fw-bold fs-3 mb-4">Programming Activity Telemetry</h1>
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card-dark p-4">
                    <div class="text-secondary small font-mono">Today's Coding Time</div>
                    <div class="fs-2 fw-bold text-info font-mono">6h 42m</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-dark p-4">
                    <div class="text-secondary small font-mono">Daily Average</div>
                    <div class="fs-2 fw-bold text-success font-mono">5h 18m</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-dark p-4">
                    <div class="text-secondary small font-mono">Active Editors</div>
                    <div class="fs-2 fw-bold text-white font-mono">VS Code</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
