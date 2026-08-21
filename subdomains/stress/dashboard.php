<?php
session_start();
if (empty($_SESSION['authenticated'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Courier Developer Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #09090b; color: #f4f4f5; }
        .console-nav { background: #141417; border-bottom: 1px solid #27272a; padding: 16px 0; }
        .card-dark { background: #141417; border: 1px solid #27272a; border-radius: 12px; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body>
    <nav class="console-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <a href="index.php" class="text-white text-decoration-none fw-bold fs-5"><i class="bi bi-send-fill text-purple me-2"></i> Courier Console</a>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary small font-mono"><?= htmlspecialchars($_SESSION['user_email']) ?></span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Log Out</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold fs-3 mb-1">Developer Workspace</h1>
                <p class="text-secondary small mb-0">User Sub: <code><?= htmlspecialchars($_SESSION['user_sub'] ?? 'sub_demo') ?></code></p>
            </div>
            <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> UserConfirmed: true</span>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card-dark p-4">
                    <div class="text-secondary small mb-1">Messages Dispatched (This Month)</div>
                    <div class="fs-3 fw-bold text-white font-mono">0 / 10,000</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-dark p-4">
                    <div class="text-secondary small mb-1">Active Integrations</div>
                    <div class="fs-3 fw-bold text-white font-mono">3 Channels</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-dark p-4">
                    <div class="text-secondary small mb-1">API Key Status</div>
                    <div class="fs-3 fw-bold text-success font-mono">ACTIVE</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
