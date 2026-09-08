<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verified Survey Submissions — PulseFeedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #090b10; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #05070a; color: #f1f5f9; margin: 0; display: flex; height: 100vh; }
        .app-sidebar { width: 270px; background: var(--sidebar-bg); color: #94a3b8; display: flex; flex-direction: column; border-right: 1px solid #1f2533; }
        .sidebar-brand { padding: 20px 24px; font-size: 18px; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #1f2533; }
        .sidebar-brand i { color: #f59e0b; }
        .sidebar-menu { padding: 20px 14px; flex-grow: 1; }
        .nav-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; padding: 10px 12px 6px; }
        .sidebar-link { display: flex; align-items: center; gap: 12px; padding: 10px 14px; color: #94a3b8; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 4px; }
        .sidebar-link:hover { color: #ffffff; background: rgba(255, 255, 255, 0.05); }
        .sidebar-link.active { color: #ffffff; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); font-weight: 600; }
        .app-workspace { flex-grow: 1; overflow-y: auto; background: #05070a; }
        .app-topbar { height: 64px; background: #090b10; border-bottom: 1px solid #1f2533; display: flex; align-items: center; justify-content: space-between; padding: 0 28px; }
    </style>
</head>
<body>
    <aside class="app-sidebar">
        <div class="sidebar-brand"><i class="bi bi-star-fill"></i> PulseFeedback</div>
        <div class="sidebar-menu">
            <div class="nav-section-title">Onboarding & Feedback</div>
            <a href="index.php" class="sidebar-link"><i class="bi bi-card-checklist"></i> Survey & Receipt Studio</a>
            <a href="receipt.php" class="sidebar-link"><i class="bi bi-receipt-cutoff"></i> Customer Receipt View</a>
            <a href="responses.php" class="sidebar-link active"><i class="bi bi-inbox-fill"></i> Verified Submissions</a>
            <a href="analytics.php" class="sidebar-link"><i class="bi bi-graph-up"></i> Satisfaction Analytics</a>
            <div class="nav-section-title mt-3">Settings</div>
            <a href="settings.php" class="sidebar-link"><i class="bi bi-sliders"></i> Engine Settings</a>
        </div>
    </aside>

    <main class="app-workspace">
        <header class="app-topbar">
            <h1 class="fs-5 fw-bold mb-0">Verified Customer Submissions</h1>
            <a href="index.php" class="btn btn-sm btn-warning text-dark fw-bold">&larr; Back to Survey Studio</a>
        </header>

        <div class="p-4" style="max-width: 900px;">
            <div class="card bg-dark border-secondary p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-white mb-1">Alex Morgan — Apex Global Systems</h6>
                        <div class="small text-secondary">Ticket Ref: <code>FBK-2026-9042</code> &bull; Rating: 10/10 Promoter</div>
                    </div>
                    <a href="receipt.php" class="btn btn-sm btn-outline-warning">View Receipt</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>