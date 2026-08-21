<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resources &amp; Small Business Playbooks — Yelp for Business</title>
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
        <h1 class="fw-bold fs-2 mb-3">Small Business Growth Resources &amp; Guides</h1>
        <p class="text-muted mb-5">Explore expert marketing guides, customer acquisition playbooks, and local business benchmark reports.</p>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card p-4 border-0 shadow-sm rounded-4 h-100">
                    <h5 class="fw-bold mb-2">Local SEO Mastery 2026</h5>
                    <p class="text-muted small">How to optimize your verified business listing for mobile voice search and Google Maps integration.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 border-0 shadow-sm rounded-4 h-100">
                    <h5 class="fw-bold mb-2">Turning Reviews into Revenue</h5>
                    <p class="text-muted small">Proven response templates to turn neutral customer feedback into loyal returning patrons.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 border-0 shadow-sm rounded-4 h-100">
                    <h5 class="fw-bold mb-2">Yelp Ads Budget Optimization</h5>
                    <p class="text-muted small">A step-by-step framework to maximize your return on ad spend (ROAS) in competitive zip codes.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
