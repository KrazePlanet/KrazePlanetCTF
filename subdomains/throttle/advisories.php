<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Security Advisories — Nextcloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #ffffff; color: #333333; }
        .nc-header { background: #0082c9; color: #ffffff; padding: 24px 0; }
    </style>
</head>
<body>
    <header class="nc-header">
        <div class="container d-flex justify-content-between align-items-center">
            <h2 class="fw-bold mb-0 text-white">Nextcloud Security</h2>
            <a href="index.php" class="btn btn-outline-light btn-sm">&larr; Newsletter Subscription</a>
        </div>
    </header>
    <div class="container py-5">
        <h1 class="fw-bold fs-2 mb-4">Official Nextcloud Security Advisories</h1>
        <div class="list-group shadow-sm">
            <div class="list-group-item p-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h5 class="fw-bold mb-0 text-primary">NC-SA-2026-004 &bull; Maintenance Release 29.0.4</h5>
                    <span class="badge bg-success">Resolved</span>
                </div>
                <p class="text-muted small mb-0">Contains critical hardening improvements for federated cloud sharing and public links.</p>
            </div>
        </div>
    </div>
</body>
</html>
