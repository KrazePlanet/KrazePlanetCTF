<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Active Exchanges — Redditgifts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0e1113; color: #d7dadc; }
        .reddit-nav { background: #1a1a1b; border-bottom: 1px solid #343536; padding: 12px 0; }
        .snoo-badge { width: 32px; height: 32px; background: #ff4500; color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    <nav class="reddit-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="text-white text-decoration-none fw-bold fs-5 d-flex align-items-center gap-2">
                <div class="snoo-badge"><i class="bi bi-gift-fill"></i></div>
                redditgifts Exchanges
            </a>
            <a href="index.php" class="btn btn-outline-light btn-sm">&larr; Return to Featured Post</a>
        </div>
    </nav>
    <div class="container py-5">
        <h1 class="fw-bold fs-2 mb-3">Seasonal Gift Exchanges</h1>
        <p class="text-secondary mb-4">Join active gift exchanges and get matched with fellow Reddit members worldwide.</p>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card bg-dark text-white p-4 border-secondary border-opacity-25 rounded-3">
                    <span class="badge bg-danger mb-2" style="width:fit-content;">Major Event</span>
                    <h5 class="fw-bold">Secret Santa 2026</h5>
                    <p class="text-secondary small">The world's largest online Secret Santa exchange with over 100,000 participants.</p>
                    <a href="index.php" class="btn btn-outline-danger btn-sm">View Post Thread &rarr;</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark text-white p-4 border-secondary border-opacity-25 rounded-3">
                    <span class="badge bg-warning text-dark mb-2" style="width:fit-content;">Active</span>
                    <h5 class="fw-bold">Board Games Exchange</h5>
                    <p class="text-secondary small">Exchange your favorite tabletop strategy games and card expansions.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
