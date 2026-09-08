<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Marketing Campaigns — PageCraft CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #100812; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #090409; color: #f1f5f9; margin: 0; display: flex; height: 100vh; }
        .app-sidebar { width: 270px; background: var(--sidebar-bg); color: #94a3b8; display: flex; flex-direction: column; border-right: 1px solid #2e1628; }
        .sidebar-brand { padding: 20px 24px; font-size: 18px; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #2e1628; }
        .sidebar-brand i { color: #fb7185; }
        .sidebar-menu { padding: 20px 14px; flex-grow: 1; }
        .nav-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; padding: 10px 12px 6px; }
        .sidebar-link { display: flex; align-items: center; gap: 12px; padding: 10px 14px; color: #94a3b8; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 4px; }
        .sidebar-link:hover { color: #ffffff; background: rgba(255, 255, 255, 0.05); }
        .sidebar-link.active { color: #ffffff; background: rgba(225, 29, 72, 0.18); border: 1px solid rgba(225, 29, 72, 0.35); font-weight: 600; }
        .app-workspace { flex-grow: 1; overflow-y: auto; background: #090409; }
        .app-topbar { height: 64px; background: #100812; border-bottom: 1px solid #2e1628; display: flex; align-items: center; justify-content: space-between; padding: 0 28px; }
    </style>
</head>
<body>
    <aside class="app-sidebar">
        <div class="sidebar-brand"><i class="bi bi-window-stack"></i> PageCraft CMS</div>
        <div class="sidebar-menu">
            <div class="nav-section-title">Site Builder</div>
            <a href="index.php" class="sidebar-link"><i class="bi bi-layout-text-window-reverse"></i> Landing Studio</a>
            <a href="landing.php" class="sidebar-link"><i class="bi bi-browser-chrome"></i> Live Public Page</a>
            <a href="modules.php" class="sidebar-link"><i class="bi bi-boxes"></i> Modular Page Blocks</a>
            <a href="campaigns.php" class="sidebar-link active"><i class="bi bi-tag-fill"></i> Marketing Campaigns</a>
            <div class="nav-section-title mt-3">Settings</div>
            <a href="settings.php" class="sidebar-link"><i class="bi bi-sliders"></i> Engine Settings</a>
        </div>
    </aside>

    <main class="app-workspace">
        <header class="app-topbar">
            <h1 class="fs-5 fw-bold mb-0">Active Marketing Campaigns</h1>
            <a href="index.php" class="btn btn-sm btn-danger fw-bold">&larr; Back to Landing Studio</a>
        </header>

        <div class="p-4" style="max-width: 900px;">
            <table class="table table-dark table-bordered">
                <thead>
                    <tr>
                        <th>Campaign Code</th>
                        <th>Offer</th>
                        <th>Target Tier</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>LAUNCH2026</code></td>
                        <td>30% OFF Annual Tier</td>
                        <td>Enterprise Scale</td>
                        <td><span class="badge bg-success">Active</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
