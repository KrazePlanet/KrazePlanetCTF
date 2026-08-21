<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics — PulseMail Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --sidebar-bg: #0f172a; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; margin: 0; display: flex; height: 100vh; }
        .app-sidebar { width: 260px; background: var(--sidebar-bg); color: #94a3b8; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-brand { padding: 20px 24px; font-size: 19px; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #1e293b; }
        .sidebar-menu { padding: 20px 14px; flex-grow: 1; }
        .nav-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; padding: 10px 12px 6px; }
        .sidebar-link { display: flex; align-items: center; gap: 12px; padding: 10px 14px; color: #94a3b8; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 4px; }
        .sidebar-link:hover { color: #ffffff; background: rgba(255, 255, 255, 0.05); }
        .sidebar-link.active { color: #ffffff; background: var(--primary); font-weight: 600; }
        .app-workspace { flex-grow: 1; overflow-y: auto; }
        .app-topbar { height: 64px; background: #ffffff; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 28px; }
    </style>
</head>
<body>
    <aside class="app-sidebar">
        <div class="sidebar-brand"><i class="bi bi-envelope-paper-heart-fill text-info"></i> PulseMail</div>
        <div class="sidebar-menu">
            <div class="nav-section-title">Campaigns & Marketing</div>
            <a href="campaigns.php" class="sidebar-link"><i class="bi bi-send"></i> Campaigns</a>
            <a href="index.php" class="sidebar-link"><i class="bi bi-file-earmark-richtext"></i> Template Studio</a>
            <a href="audience.php" class="sidebar-link"><i class="bi bi-people"></i> Audiences & Lists</a>
            <a href="automations.php" class="sidebar-link"><i class="bi bi-lightning-charge"></i> Automations</a>
            <div class="nav-section-title mt-3">Insights & Configuration</div>
            <a href="analytics.php" class="sidebar-link active"><i class="bi bi-bar-chart-line"></i> Analytics</a>
            <a href="settings.php" class="sidebar-link"><i class="bi bi-gear"></i> Platform Settings</a>
        </div>
    </aside>

    <main class="app-workspace">
        <header class="app-topbar">
            <h1 class="fs-5 fw-bold mb-0">Delivery & Conversion Insights</h1>
            <div class="text-muted small">Last 30 Days</div>
        </header>

        <div class="p-4">
            <div class="card p-4 border-0 shadow-sm mb-4">
                <h6 class="fw-bold mb-3">Email Delivery Performance</h6>
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: 98.4%"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>Successful Deliveries (98.4%)</span>
                    <span>Bounces / Rejections (1.6%)</span>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
