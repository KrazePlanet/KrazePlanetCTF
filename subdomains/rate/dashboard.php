<?php
session_start();
if (empty($_SESSION['2fa_verified'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Algolia Search &amp; Discovery Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --algolia-dark: #0a0b10; --algolia-card: #12141d; --algolia-border: #232736; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--algolia-dark); color: #f1f5f9; }
        .nav-algolia { background: var(--algolia-card); border-bottom: 1px solid var(--algolia-border); padding: 16px 0; }
        .card-dark { background: var(--algolia-card); border: 1px solid var(--algolia-border); border-radius: 12px; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body>
    <nav class="nav-algolia">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <a href="dashboard.php" class="text-white text-decoration-none fw-bold fs-5"><i class="bi bi-search text-primary me-2"></i> Algolia Console</a>
                <a href="indices.php" class="text-secondary text-decoration-none small">Indices</a>
                <a href="apikeys.php" class="text-secondary text-decoration-none small">API Keys</a>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-success px-3 py-2"><i class="bi bi-shield-check me-1"></i> 2FA Authenticated</span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Log Out</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h1 class="fw-bold fs-3 mb-4">Search &amp; Discovery Cluster: <code>prod-us-east-1</code></h1>
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card-dark p-4">
                    <div class="text-secondary small font-mono">Monthly Search Operations</div>
                    <div class="fs-2 fw-bold text-primary font-mono">1.4M / 10M</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-dark p-4">
                    <div class="text-secondary small font-mono">Search Latency (P95)</div>
                    <div class="fs-2 fw-bold text-success font-mono">4.2 ms</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-dark p-4">
                    <div class="text-secondary small font-mono">Active Indices</div>
                    <div class="fs-2 fw-bold text-white font-mono">12 Indices</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
