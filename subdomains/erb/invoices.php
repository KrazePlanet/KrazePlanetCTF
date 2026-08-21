<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoices — DocuCraft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #e11d48; --sidebar-bg: #111827; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f9fafb; margin: 0; display: flex; height: 100vh; }
        .app-sidebar { width: 260px; background: var(--sidebar-bg); color: #9ca3af; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 20px 24px; font-size: 19px; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #1f2937; }
        .sidebar-menu { padding: 20px 14px; flex-grow: 1; }
        .nav-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; padding: 10px 12px 6px; }
        .sidebar-link { display: flex; align-items: center; gap: 12px; padding: 10px 14px; color: #9ca3af; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 4px; }
        .sidebar-link:hover { color: #ffffff; background: rgba(255, 255, 255, 0.05); }
        .sidebar-link.active { color: #ffffff; background: var(--primary); font-weight: 600; }
        .app-workspace { flex-grow: 1; overflow-y: auto; }
        .app-topbar { height: 64px; background: #ffffff; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; padding: 0 28px; }
    </style>
</head>
<body>
    <aside class="app-sidebar">
        <div class="sidebar-brand"><i class="bi bi-file-earmark-code-fill text-danger"></i> DocuCraft</div>
        <div class="sidebar-menu">
            <div class="nav-section-title">Documents & Templates</div>
            <a href="index.php" class="sidebar-link"><i class="bi bi-receipt-cutoff"></i> Invoice Studio</a>
            <a href="invoices.php" class="sidebar-link active"><i class="bi bi-journal-text"></i> Invoices & Bills</a>
            <a href="customers.php" class="sidebar-link"><i class="bi bi-buildings"></i> Organizations</a>
            <a href="servers.php" class="sidebar-link"><i class="bi bi-hdd-stack"></i> Infrastructure</a>
            <div class="nav-section-title mt-3">Configuration</div>
            <a href="settings.php" class="sidebar-link"><i class="bi bi-sliders"></i> Engine Settings</a>
        </div>
    </aside>

    <main class="app-workspace">
        <header class="app-topbar">
            <h1 class="fs-5 fw-bold mb-0">Dispatched Invoices & Billing Statements</h1>
            <a href="index.php" class="btn btn-sm btn-danger"><i class="bi bi-plus-lg me-1"></i> Edit Template</a>
        </header>

        <div class="p-4">
            <div class="card border-0 shadow-sm overflow-hidden">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer Organization</th>
                            <th>Plan Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold font-monospace">INV-2026-0817</td>
                            <td>Apex Global Enterprise</td>
                            <td>RubyCloud Pro Dedicated</td>
                            <td class="fw-semibold">$1,250.00</td>
                            <td><span class="badge bg-warning text-dark">Pending</span></td>
                            <td><a href="index.php" class="btn btn-xs btn-outline-danger btn-sm">Preview</a></td>
                        </tr>
                        <tr>
                            <td class="fw-bold font-monospace">INV-2026-0715</td>
                            <td>Apex Global Enterprise</td>
                            <td>RubyCloud Pro Dedicated</td>
                            <td class="fw-semibold">$1,250.00</td>
                            <td><span class="badge bg-success">Paid</span></td>
                            <td><a href="index.php" class="btn btn-xs btn-outline-secondary btn-sm">View</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
