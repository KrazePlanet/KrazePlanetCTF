<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edge Nodes — TornadoAlert</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #090d16; }
        body { font-family: 'Space Grotesk', sans-serif; background: #0f172a; color: #f1f5f9; margin: 0; display: flex; height: 100vh; }
        .app-sidebar { width: 260px; background: var(--sidebar-bg); color: #94a3b8; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 20px 24px; font-size: 18px; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #1e293b; }
        .sidebar-menu { padding: 20px 14px; flex-grow: 1; }
        .nav-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; padding: 10px 12px 6px; }
        .sidebar-link { display: flex; align-items: center; gap: 12px; padding: 10px 14px; color: #94a3b8; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 4px; }
        .sidebar-link:hover { color: #ffffff; background: rgba(255, 255, 255, 0.05); }
        .sidebar-link.active { color: #ffffff; background: rgba(6, 182, 212, 0.15); border: 1px solid rgba(6, 182, 212, 0.3); font-weight: 600; }
        .app-workspace { flex-grow: 1; overflow-y: auto; background: #0b1120; }
        .app-topbar { height: 64px; background: #0f172a; border-bottom: 1px solid #1e293b; display: flex; align-items: center; justify-content: space-between; padding: 0 28px; }
    </style>
</head>
<body>
    <aside class="app-sidebar">
        <div class="sidebar-brand"><i class="bi bi-lightning-charge-fill text-info"></i> TornadoAlert</div>
        <div class="sidebar-menu">
            <div class="nav-section-title">Telemetry & Incident Response</div>
            <a href="index.php" class="sidebar-link"><i class="bi bi-bell-fill text-cyan"></i> Alert Studio</a>
            <a href="incidents.php" class="sidebar-link"><i class="bi bi-activity"></i> Active Incidents</a>
            <a href="nodes.php" class="sidebar-link active"><i class="bi bi-hdd-network"></i> Edge Nodes</a>
            <a href="metrics.php" class="sidebar-link"><i class="bi bi-speedometer2"></i> Telemetry & Metrics</a>
            <div class="nav-section-title mt-3">Platform Configuration</div>
            <a href="settings.php" class="sidebar-link"><i class="bi bi-sliders"></i> Engine Settings</a>
        </div>
    </aside>

    <main class="app-workspace">
        <header class="app-topbar">
            <h1 class="fs-5 fw-bold mb-0">High-Concurrency Edge Nodes</h1>
        </header>

        <div class="p-4">
            <div class="card p-3 border border-secondary border-opacity-25 bg-dark text-white mb-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-1">edge-gw-08.us-east.prod (10.240.18.94)</h6>
                        <div class="text-muted small">Region: us-east-1 &bull; Active Async Connections: 14,280 &bull; Throughput: 2.14M req/sec</div>
                    </div>
                    <span class="badge bg-warning text-dark">Degraded</span>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
