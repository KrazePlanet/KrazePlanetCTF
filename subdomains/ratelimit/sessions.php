<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tutoring Programs — UPchieve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0f172a; color: #f8fafc; }
        .upchieve-nav { background: #1e293b; border-bottom: 1px solid #334155; padding: 16px 0; }
    </style>
</head>
<body>
    <nav class="upchieve-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="text-white text-decoration-none fw-bold fs-5"><i class="bi bi-mortarboard-fill text-info me-2"></i> UPchieve Programs</a>
            <a href="index.php" class="btn btn-outline-light btn-sm">&larr; Return Home</a>
        </div>
    </nav>
    <div class="container py-5">
        <h1 class="fw-bold fs-2 mb-3">On-Demand Tutoring Programs</h1>
        <p class="text-secondary mb-4">Choose a subject and get paired with an academic coach within 5 minutes.</p>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card bg-dark text-white p-4 border-secondary border-opacity-25 rounded-3">
                    <h5 class="fw-bold">Math &amp; Algebra</h5>
                    <p class="text-secondary small">Pre-algebra, Algebra 1 &amp; 2, Geometry, Trigonometry, Calculus.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark text-white p-4 border-secondary border-opacity-25 rounded-3">
                    <h5 class="fw-bold">Science</h5>
                    <p class="text-secondary small">Biology, Chemistry, Physics, Environmental Science.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark text-white p-4 border-secondary border-opacity-25 rounded-3">
                    <h5 class="fw-bold">College Applications</h5>
                    <p class="text-secondary small">College essays, financial aid, application strategy.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
