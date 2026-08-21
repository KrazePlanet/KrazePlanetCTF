<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Settings — Yelp for Business Owners</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .yelp-header { background-color: #d32323; color: #ffffff; padding: 14px 0; }
        .yelp-brand { font-size: 24px; font-weight: 800; color: #ffffff; text-decoration: none; }
    </style>
</head>
<body>
    <header class="yelp-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="yelp-brand">yelp<i class="bi bi-asterisk"></i> for Business Owners</a>
            <a href="index.php" class="btn btn-outline-light btn-sm">&larr; Return to Inbox</a>
        </div>
    </header>
    <div class="container py-5">
        <h2 class="fw-bold mb-4">Business Account Settings</h2>
        <div class="card p-4 border rounded">
            <div class="mb-3">
                <label class="fw-bold small text-secondary">Business Name</label>
                <div class="fs-5 fw-bold">Artisan Bakery &amp; Cafe</div>
            </div>
            <div class="mb-3">
                <label class="fw-bold small text-secondary">Owner Email</label>
                <div class="fs-5 font-monospace text-primary"><?= htmlspecialchars($_SESSION['business_email'] ?? 'owner@artisanbakery.com') ?></div>
            </div>
        </div>
    </div>
</body>
</html>
