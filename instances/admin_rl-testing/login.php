<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Business Owner Login — Yelp for Business</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; color: #1e293b; }
        .navbar-yelp { background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 16px 0; }
        .brand-logo { font-size: 24px; font-weight: 900; color: #d32323; text-decoration: none; display: flex; align-items: center; gap: 6px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-yelp">
        <div class="container">
            <a href="index.php" class="brand-logo"><i class="bi bi-yelp"></i> yelp <span class="fs-6 fw-bold text-muted ps-2 border-start">for Business</span></a>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">&larr; Return Home</a>
        </div>
    </nav>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4">
                    <h2 class="fw-bold fs-3 mb-2">Log In to Yelp for Business</h2>
                    <p class="text-muted small mb-4">Manage your business profile, reviews, and ad campaigns.</p>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" class="form-control" placeholder="you@yourbusiness.com" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <button type="button" class="btn btn-danger w-100 py-3 fw-bold" onclick="alert('Please use the newsletter subscription form to test rate limiting features.');">Log In &rarr;</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
