<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gift Gallery — Redditgifts</title>
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
                redditgifts Gallery
            </a>
            <a href="index.php" class="btn btn-outline-light btn-sm">&larr; Return to Featured Post</a>
        </div>
    </nav>
    <div class="container py-5">
        <h1 class="fw-bold fs-2 mb-3">Community Gift Gallery</h1>
        <p class="text-secondary mb-4">Explore thousands of heartwarming exchanges sent by Secret Santas across 130 countries.</p>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card bg-dark text-white border-secondary border-opacity-25 rounded-3 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1513885535751-8b9238bd345a?w=600&auto=format&fit=crop&q=80" class="card-img-top" height="200" style="object-fit:cover;">
                    <div class="p-3">
                        <h6 class="fw-bold mb-1">Handmade Coffee Roasting Kit</h6>
                        <span class="text-secondary small">Posted in Coffee &amp; Tea Exchange</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark text-white border-secondary border-opacity-25 rounded-3 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=600&auto=format&fit=crop&q=80" class="card-img-top" height="200" style="object-fit:cover;">
                    <div class="p-3">
                        <h6 class="fw-bold mb-1">Signed First Edition Sci-Fi Novels</h6>
                        <span class="text-secondary small">Posted in Books &amp; Novels Exchange</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
