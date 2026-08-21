<?php
session_start();
if (empty($_SESSION['nin_verified'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NIN Linked Successfully — MTN Y'ello</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #000000; color: #ffffff; }
        .mtn-nav { background: #000000; border-bottom: 3px solid #ffcc00; padding: 16px 0; }
        .card-dark { background: #111111; border: 1px solid #262626; border-radius: 12px; }
    </style>
</head>
<body>
    <nav class="mtn-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="dashboard.php" class="text-warning fw-bold fs-4 text-decoration-none">MTN Y'ello</a>
            <a href="logout.php" class="btn btn-outline-warning btn-sm">Log Out</a>
        </div>
    </nav>

    <div class="container py-5 text-center">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <i class="bi bi-patch-check-fill text-warning fs-1 mb-3"></i>
                <h2 class="fw-bold mb-2">NIN Successfully Linked!</h2>
                <p class="text-secondary mb-4">Your National Identity Number is now verified and permanently bound to line <strong><?= htmlspecialchars($_SESSION['phone']) ?></strong>.</p>
                <div class="card-dark p-4 text-start mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small">KYC Status</span>
                        <span class="badge bg-success">Tier 3 Verified</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary small">SIM Line Active</span>
                        <span class="text-warning fw-bold">UNRESTRICTED</span>
                    </div>
                </div>
                <a href="index.php" class="btn btn-warning px-4 fw-bold">Return to Portal</a>
            </div>
        </div>
    </div>
</body>
</html>
