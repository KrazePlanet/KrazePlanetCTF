<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Indices — Algolia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0a0b10; color: #f1f5f9; }
        .nav-algolia { background: #12141d; border-bottom: 1px solid #232736; padding: 16px 0; }
    </style>
</head>
<body>
    <nav class="nav-algolia">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="dashboard.php" class="text-white text-decoration-none fw-bold fs-5"><i class="bi bi-search text-primary me-2"></i> Algolia Indices</a>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">&larr; Return to Dashboard</a>
        </div>
    </nav>
    <div class="container py-5">
        <h1 class="fw-bold fs-2 mb-4">Production Search Indices</h1>
        <div class="card bg-dark text-white p-4 border-secondary border-opacity-25 rounded-3 mb-3">
            <h5 class="fw-bold mb-1">products_catalog_v2</h5>
            <p class="text-secondary small mb-0">Records: 48,290 &bull; Size: 34.2 MB &bull; Primary Search Index</p>
        </div>
    </div>
</body>
</html>
