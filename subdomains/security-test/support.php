<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Partner Helpdesk — On Running</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0f1015; color: #ffffff; }
        .on-navbar { background: #18181b; border-bottom: 1px solid #27272a; padding: 16px 0; }
        .on-logo-badge { background: #ffffff; color: #000000; font-weight: 900; font-size: 16px; padding: 2px 8px; border-radius: 4px; }
    </style>
</head>
<body>
    <nav class="on-navbar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <a href="index.php" class="text-decoration-none on-logo-badge">On</a>
                <span class="fw-bold fs-6">Partner Helpdesk &amp; Support</span>
            </div>
            <a href="index.php" class="btn btn-outline-light btn-sm">&larr; Back to Login</a>
        </div>
    </nav>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card p-4 bg-dark text-white border-secondary border-opacity-25 rounded-4 shadow-lg">
                    <h3 class="fw-bold mb-2">Need Partner Account Access?</h3>
                    <p class="text-secondary small mb-4">Retail accounts are managed directly by regional distributor coordinators.</p>
                    <div class="p-3 bg-black rounded-3 mb-3 border border-secondary border-opacity-25">
                        <div class="text-muted small">Global B2B Support Desk</div>
                        <div class="fw-bold text-light">partners@on-running.com</div>
                    </div>
                    <a href="index.php" class="btn btn-light w-100 py-2 fw-bold">Return to Login Page</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
