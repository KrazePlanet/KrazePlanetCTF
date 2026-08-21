<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Findings — CloudGuard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #090f1d; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0d1527; color: #f1f5f9; margin: 0; display: flex; height: 100vh; }
        .app-sidebar { width: 260px; background: var(--sidebar-bg); color: #94a3b8; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 20px 24px; font-size: 18px; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #1f2a40; }
        .sidebar-menu { padding: 20px 14px; flex-grow: 1; }
        .nav-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; padding: 10px 12px 6px; }
        .sidebar-link { display: flex; align-items: center; gap: 12px; padding: 10px 14px; color: #94a3b8; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 4px; }
        .sidebar-link:hover { color: #ffffff; background: rgba(255, 255, 255, 0.05); }
        .sidebar-link.active { color: #ffffff; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); font-weight: 600; }
        .app-workspace { flex-grow: 1; overflow-y: auto; background: #090e1c; }
        .app-topbar { height: 64px; background: #0d1527; border-bottom: 1px solid #1f2a40; display: flex; align-items: center; justify-content: space-between; padding: 0 28px; }
    </style>
</head>
<body>
    <aside class="app-sidebar">
        <div class="sidebar-brand"><i class="bi bi-shield-lock-fill text-success"></i> CloudGuard</div>
        <div class="sidebar-menu">
            <div class="nav-section-title">Audits & Reports</div>
            <a href="index.php" class="sidebar-link"><i class="bi bi-file-earmark-bar-graph"></i> Report Studio</a>
            <a href="projects.php" class="sidebar-link"><i class="bi bi-folder-check"></i> Scanned Projects</a>
            <a href="compliance.php" class="sidebar-link"><i class="bi bi-patch-check"></i> Compliance Badges</a>
            <a href="findings.php" class="sidebar-link active"><i class="bi bi-bug"></i> Posture Findings</a>
            <div class="nav-section-title mt-3">Configuration</div>
            <a href="settings.php" class="sidebar-link"><i class="bi bi-sliders"></i> Engine Settings</a>
        </div>
    </aside>

    <main class="app-workspace">
        <header class="app-topbar">
            <h1 class="fs-5 fw-bold mb-0">Security Posture Findings</h1>
        </header>

        <div class="p-4">
            <div class="card p-3 border border-secondary border-opacity-25 bg-dark text-white mb-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-info">Informational</span>
                        <h6 class="fw-bold mt-2 mb-1">IAM Role Permissive Boundary Audit</h6>
                        <div class="text-muted small">Target: <code>arn:aws:iam::123456789012:role/DeployPipeline</code> &bull; Status: Remediated</div>
                    </div>
                    <span class="badge bg-success">Resolved</span>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
