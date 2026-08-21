<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subscription Confirmed — Nextcloud</title>
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
        <div class="container">
            <h2 class="fw-bold mb-0 text-white">Nextcloud</h2>
        </div>
    </header>
    <div class="container py-5 text-center">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <i class="bi bi-patch-check-fill text-success fs-1 mb-3"></i>
                <h2 class="fw-bold fs-3 mb-2">Subscription Confirmed!</h2>
                <p class="text-muted mb-4">Your membership to the Nextcloud Newsletter has been confirmed. You will receive all future community announcements.</p>
                <a href="index.php" class="btn btn-primary" style="background:#0082c9; border:none;">Return to Nextcloud &rarr;</a>
            </div>
        </div>
    </div>
</body>
</html>
