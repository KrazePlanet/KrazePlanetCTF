<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DevWiki Team Spaces — Documentation Spaces</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #090d16; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #050811; color: #f1f5f9; margin: 0; display: flex; height: 100vh; }
        .app-sidebar { width: 270px; background: var(--sidebar-bg); color: #94a3b8; display: flex; flex-direction: column; border-right: 1px solid #1e293b; }
        .sidebar-brand { padding: 20px 24px; font-size: 18px; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #1e293b; }
        .sidebar-menu { padding: 20px 14px; flex-grow: 1; }
        .nav-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; padding: 10px 12px 6px; }
        .sidebar-link { display: flex; align-items: center; gap: 12px; padding: 10px 14px; color: #94a3b8; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 4px; }
        .sidebar-link:hover { color: #ffffff; background: rgba(255, 255, 255, 0.05); }
        .sidebar-link.active { color: #ffffff; background: rgba(99, 102, 241, 0.18); border: 1px solid rgba(99, 102, 241, 0.35); font-weight: 600; }
        .app-workspace { flex-grow: 1; overflow-y: auto; background: #050811; }
        .app-topbar { height: 64px; background: #090d16; border-bottom: 1px solid #1e293b; display: flex; align-items: center; justify-content: space-between; padding: 0 28px; }
        .space-card { background: #0a0f1e; border: 1px solid #1a233a; border-radius: 12px; padding: 24px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <aside class="app-sidebar">
        <div class="sidebar-brand"><i class="bi bi-journal-bookmark-fill" style="color: #818cf8;"></i> DevWiki</div>
        <div class="sidebar-menu">
            <div class="nav-section-title">Knowledge Base</div>
            <a href="index.php" class="sidebar-link"><i class="bi bi-pencil-square"></i> Article Editor</a>
            <a href="article.php" class="sidebar-link"><i class="bi bi-file-earmark-text"></i> Documentation View</a>
            <a href="search.php" class="sidebar-link"><i class="bi bi-search"></i> Wiki Search & Tags</a>
            <a href="spaces.php" class="sidebar-link active"><i class="bi bi-collection"></i> Team Spaces</a>
            <div class="nav-section-title mt-3">Settings</div>
            <a href="settings.php" class="sidebar-link"><i class="bi bi-sliders"></i> Engine Settings</a>
        </div>
    </aside>

    <main class="app-workspace">
        <header class="app-topbar">
            <h1 class="fs-5 fw-bold mb-0">Documentation Spaces</h1>
            <a href="index.php" class="btn btn-sm text-white fw-bold" style="background: #6366f1;">&larr; Back to Wiki Editor</a>
        </header>

        <div class="p-4" style="max-width: 900px;">
            <div class="space-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold mb-0 text-white"><i class="bi bi-hdd-network me-2" style="color: #818cf8;"></i> Architecture & Runbooks</h5>
                    <span class="badge bg-primary">48 Articles</span>
                </div>
                <p class="text-secondary small mb-3">Operational playbooks, disaster recovery strategies, and ingress gateway architectures for production services.</p>
                <a href="index.php" class="btn btn-sm btn-outline-light">Browse Space Articles &rarr;</a>
            </div>

            <div class="space-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold mb-0 text-white"><i class="bi bi-shield-lock me-2 text-warning"></i> Security Policies & Threat Models</h5>
                    <span class="badge bg-secondary">32 Articles</span>
                </div>
                <p class="text-secondary small mb-3">Enterprise threat modeling, perimeter ingress firewall rules, and zero-trust authentication guidelines.</p>
                <a href="index.php" class="btn btn-sm btn-outline-light">Browse Space Articles &rarr;</a>
            </div>
        </div>
    </main>
</body>
</html>
