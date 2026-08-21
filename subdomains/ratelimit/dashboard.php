<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal — UPchieve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .upchieve-nav { background: #1e293b; border-bottom: 1px solid #334155; padding: 16px 0; }
        .card-dark { background: #1e293b; border: 1px solid #334155; border-radius: 12px; }
    </style>
</head>
<body>
    <nav class="upchieve-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="text-white text-decoration-none fw-bold fs-5"><i class="bi bi-mortarboard-fill text-info me-2"></i> UPchieve Student Console</a>
            <a href="index.php" class="btn btn-outline-light btn-sm">&larr; Return Home</a>
        </div>
    </nav>
    <div class="container py-5">
        <h1 class="fw-bold fs-3 mb-4">Welcome to Your Academic Tutoring Workspace</h1>
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card-dark p-4">
                    <div class="text-secondary small mb-1">Completed Tutoring Sessions</div>
                    <div class="fs-2 fw-bold text-info">14 Sessions</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-dark p-4">
                    <div class="text-secondary small mb-1">Available Academic Coaches</div>
                    <div class="fs-2 fw-bold text-success">42 Online</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-dark p-4">
                    <div class="text-secondary small mb-1">Account Security</div>
                    <div class="fs-2 fw-bold text-white">Active</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
