<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Public Repositories — DevSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #0b1120; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #070b14; color: #f1f5f9; margin: 0; display: flex; height: 100vh; }
        .app-sidebar { width: 260px; background: var(--sidebar-bg); color: #94a3b8; display: flex; flex-direction: column; border-right: 1px solid #1e293b; }
        .sidebar-brand { padding: 20px 24px; font-size: 18px; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #1e293b; }
        .sidebar-brand i { color: #06b6d4; }
        .sidebar-menu { padding: 20px 14px; flex-grow: 1; }
        .nav-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; padding: 10px 12px 6px; }
        .sidebar-link { display: flex; align-items: center; gap: 12px; padding: 10px 14px; color: #94a3b8; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 4px; }
        .sidebar-link:hover { color: #ffffff; background: rgba(255, 255, 255, 0.05); }
        .sidebar-link.active { color: #ffffff; background: rgba(6, 182, 212, 0.15); border: 1px solid rgba(6, 182, 212, 0.3); font-weight: 600; }
        .app-workspace { flex-grow: 1; overflow-y: auto; background: #070b14; }
        .app-topbar { height: 64px; background: #0b1120; border-bottom: 1px solid #1e293b; display: flex; align-items: center; justify-content: space-between; padding: 0 28px; }
        .repo-card { background: #0d1527; border: 1px solid #1e293b; border-radius: 10px; padding: 18px; margin-bottom: 14px; }
    </style>
</head>
<body>
    <aside class="app-sidebar">
        <div class="sidebar-brand"><i class="bi bi-person-badge-fill"></i> DevSpace</div>
        <div class="sidebar-menu">
            <div class="nav-section-title">Personalization</div>
            <a href="index.php" class="sidebar-link"><i class="bi bi-palette2"></i> Profile & Bio Studio</a>
            <a href="profile.php" class="sidebar-link"><i class="bi bi-person-circle"></i> Public Profile View</a>
            <a href="projects.php" class="sidebar-link active"><i class="bi bi-code-slash"></i> Public Repositories</a>
            <a href="compliance.php" class="sidebar-link"><i class="bi bi-award"></i> Badges & Credentials</a>
            <div class="nav-section-title mt-3">Account</div>
            <a href="settings.php" class="sidebar-link"><i class="bi bi-gear-wide-connected"></i> Account Security</a>
        </div>
    </aside>

    <main class="app-workspace">
        <header class="app-topbar">
            <h1 class="fs-5 fw-bold mb-0">Public Repositories</h1>
            <a href="index.php" class="btn btn-sm btn-info text-dark fw-bold">&larr; Back to Profile Studio</a>
        </header>

        <div class="p-4" style="max-width: 900px;">
            <div class="repo-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold text-info mb-0"><i class="bi bi-journal-code me-2"></i>cloud-defense-mesh</h5>
                    <span class="badge bg-secondary">Public</span>
                </div>
                <p class="text-secondary small mb-3">Automated eBPF-based runtime workload inspection for multi-tenant Kubernetes clusters.</p>
                <div class="d-flex gap-3 text-secondary small">
                    <span><i class="bi bi-circle-fill text-warning me-1"></i> Python</span>
                    <span><i class="bi bi-star me-1"></i> 342 stars</span>
                    <span><i class="bi bi-bezier2 me-1"></i> 48 forks</span>
                </div>
            </div>

            <div class="repo-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold text-info mb-0"><i class="bi bi-journal-code me-2"></i>jinja-security-linter</h5>
                    <span class="badge bg-secondary">Public</span>
                </div>
                <p class="text-secondary small mb-3">Static analysis rule pack for detecting unescaped template string interpolation in Jinja2 and Tornado.</p>
                <div class="d-flex gap-3 text-secondary small">
                    <span><i class="bi bi-circle-fill text-info me-1"></i> Python</span>
                    <span><i class="bi bi-star me-1"></i> 819 stars</span>
                    <span><i class="bi bi-bezier2 me-1"></i> 112 forks</span>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
