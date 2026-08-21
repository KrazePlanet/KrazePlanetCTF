<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pricing &amp; Plans — Yelp for Business</title>
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
    <div class="container py-5 text-center">
        <h1 class="fw-bold fs-2 mb-2">Flexible plans tailored for your budget</h1>
        <p class="text-muted mb-5">Start with a free business profile and upgrade to targeted local ads whenever you are ready.</p>
        <div class="row g-4 justify-content-center text-start">
            <div class="col-md-4">
                <div class="card p-4 border-0 shadow-sm rounded-4 h-100">
                    <h5 class="fw-bold mb-1">Free Listing</h5>
                    <div class="fs-3 fw-bold my-3">$0 <span class="fs-6 text-muted font-normal">/ month</span></div>
                    <ul class="text-muted small ps-3 mb-4">
                        <li class="mb-2">Claim &amp; manage basic business profile</li>
                        <li class="mb-2">Respond to direct customer reviews</li>
                        <li class="mb-2">Upload high-res photos &amp; store hours</li>
                    </ul>
                    <a href="claim.php" class="btn btn-outline-dark w-100">Claim Profile</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 border-2 border-danger shadow-sm rounded-4 h-100">
                    <span class="badge bg-danger mb-2" style="width: fit-content;">Most Popular</span>
                    <h5 class="fw-bold mb-1">Yelp Ads Growth</h5>
                    <div class="fs-3 fw-bold my-3">$150 <span class="fs-6 text-muted font-normal">/ flexible credit</span></div>
                    <ul class="text-muted small ps-3 mb-4">
                        <li class="mb-2">Top search placement above competitors</li>
                        <li class="mb-2">Target high-intent customers in your zip code</li>
                        <li class="mb-2">Enhanced listing with Call-to-Action button</li>
                    </ul>
                    <a href="claim.php" class="btn btn-danger w-100">Start Advertising</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
