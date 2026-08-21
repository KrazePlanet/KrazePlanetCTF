<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Confirmed — Yelp for Business Owners</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .yelp-header { background-color: #d32323; color: #ffffff; padding: 14px 0; }
        .yelp-brand { font-size: 24px; font-weight: 800; color: #ffffff; text-decoration: none; }
    </style>
</head>
<body>
    <header class="yelp-header">
        <div class="container">
            <a href="index.php" class="yelp-brand">yelp<i class="bi bi-asterisk"></i> for Business Owners</a>
        </div>
    </header>
    <div class="container py-5 text-center">
        <i class="bi bi-patch-check-fill text-success fs-1 mb-3"></i>
        <h2 class="fw-bold mb-2">Email Address Verified!</h2>
        <p class="text-muted mb-4">Your email address has been confirmed. Customer messaging is now active.</p>
        <a href="index.php" class="btn btn-danger px-4" style="background:#d32323;">Go to Inbox &rarr;</a>
    </div>
</body>
</html>
