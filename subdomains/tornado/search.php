<?php
session_start();

$q = $_GET['q'] ?? '';
$rendered_q = '';
$results_count = 0;

if (!empty($q)) {
    $b64 = base64_encode($q);
    $cmd = sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64));
    $rendered_q = shell_exec($cmd);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DevWiki Search — Knowledge Base Search (Tornado)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #050811; color: #f1f5f9; min-height: 100vh; }
        .search-navbar { background: #090d16; border-bottom: 1px solid #1e293b; padding: 14px 28px; }
        .search-box { max-width: 800px; margin: 40px auto; }
        .result-card { background: #0a0f1e; border: 1px solid #1a233a; border-radius: 10px; padding: 20px; margin-bottom: 14px; }
    </style>
</head>
<body>
    <header class="search-navbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-journal-bookmark-fill fs-4" style="color: #818cf8;"></i>
            <span class="fw-bold fs-5 text-white">DevWiki</span>
            <span class="text-secondary small ms-2">/ Documentation Search</span>
        </div>
        <div>
            <a href="index.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-pencil-square me-1"></i> Open Wiki Editor
            </a>
        </div>
    </header>

    <div class="container search-box">
        <h2 class="fw-bold text-white mb-3">Search Engineering Runbooks</h2>
        <form method="GET" action="search.php" class="mb-4">
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-dark border-secondary text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control bg-dark text-white border-secondary" placeholder="Search articles, runbooks, tags, or macro queries..." value="<?= htmlspecialchars($q) ?>">
                <button type="submit" class="btn text-white px-4 fw-bold" style="background: #6366f1;">Search</button>
            </div>
        </form>

        <?php if (!empty($q)): ?>
            <div class="alert alert-dark border-secondary mb-4">
                <div class="small text-muted mb-1">Search query reflection (Tornado Evaluated):</div>
                <div class="fw-bold fs-5 text-white">Results for: <span style="color: #818cf8;"><?= $rendered_q ?></span></div>
            </div>

            <div class="result-card">
                <h5 class="fw-bold mb-1"><a href="index.php" class="text-white text-decoration-none">Production Cluster Ingress Recovery Runbook</a></h5>
                <div class="text-secondary small mb-2">Space: Architecture & Runbooks &bull; Last revised September 2026</div>
                <p class="text-secondary small mb-0">Standard operating procedure for edge proxy failover and BGP route redirection during traffic spikes...</p>
            </div>

            <div class="result-card">
                <h5 class="fw-bold mb-1"><a href="#" class="text-white text-decoration-none">Kubernetes Pod Horizontal Autoscaling Policies</a></h5>
                <div class="text-secondary small mb-2">Space: Infrastructure &bull; Last revised August 2026</div>
                <p class="text-secondary small mb-0">HPA threshold configurations and Prometheus adapter custom metrics setup for auto-scaling...</p>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-secondary">
                <i class="bi bi-search fs-1 d-block mb-3 opacity-25"></i>
                <p>Try searching for runbooks or test SSTI: <code>?q={{7*7}}</code> or <code>?q={{wiki['space']}}</code></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
