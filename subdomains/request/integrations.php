<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IDE Plugins — WakaTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0b0f19; color: #f3f4f6; }
        .nav-waka { background: #111827; border-bottom: 1px solid #1f2937; padding: 16px 0; }
    </style>
</head>
<body>
    <nav class="nav-waka">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="text-white text-decoration-none fw-bold fs-5"><i class="bi bi-clock-history text-info me-2"></i> WakaTime Plugins</a>
            <a href="index.php" class="btn btn-outline-light btn-sm">&larr; Return Home</a>
        </div>
    </nav>
    <div class="container py-5">
        <h1 class="fw-bold fs-2 mb-3">Supported IDEs &amp; Text Editors</h1>
        <p class="text-secondary mb-4">Install the open-source WakaTime plugin in your favorite code editor.</p>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card bg-dark text-white p-4 border-secondary border-opacity-25 rounded-3 text-center">
                    <i class="bi bi-code-slash text-info fs-1 mb-2"></i>
                    <h5 class="fw-bold">VS Code</h5>
                    <p class="text-secondary small mb-0">Official Visual Studio Code Extension</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-dark text-white p-4 border-secondary border-opacity-25 rounded-3 text-center">
                    <i class="bi bi-box-seam text-warning fs-1 mb-2"></i>
                    <h5 class="fw-bold">JetBrains</h5>
                    <p class="text-secondary small mb-0">IntelliJ, PyCharm, WebStorm, GoLand</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-dark text-white p-4 border-secondary border-opacity-25 rounded-3 text-center">
                    <i class="bi bi-terminal text-success fs-1 mb-2"></i>
                    <h5 class="fw-bold">Vim / Neovim</h5>
                    <p class="text-secondary small mb-0">Lightweight CLI &amp; Terminal Tracker</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-dark text-white p-4 border-secondary border-opacity-25 rounded-3 text-center">
                    <i class="bi bi-braces text-danger fs-1 mb-2"></i>
                    <h5 class="fw-bold">Sublime Text</h5>
                    <p class="text-secondary small mb-0">Sublime Package Control Plugin</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
