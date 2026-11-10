<?php

/*
 * CAMPUSCLOUD ENTERPRISE PLATFORM
 * Department Compute Projects Registry
 */

$projects = [
    [
        'code' => 'PRJ-BIO-2026',
        'title' => 'Genomics Deep Learning Lab (CUDA Compute)',
        'dept' => 'Computational Biology',
        'pi' => 'Dr. Eleanor Weiss',
        'grant' => 'NIH-R01-HG004829',
        'budget' => '$142,000',
        'status' => 'Active',
        'nodes' => 4
    ],
    [
        'code' => 'PRJ-ROB-2025',
        'title' => 'Autonomous Robotics Navigation Sim (ROS2)',
        'dept' => 'Robotics & Mechatronics',
        'pi' => 'Prof. Kenji Sato',
        'grant' => 'DARPA-RACER-2025',
        'budget' => '$210,000',
        'status' => 'Active',
        'nodes' => 8
    ],
    [
        'code' => 'PRJ-SEC-2026',
        'title' => 'Cyber Defense Multi-Tenant Range Sandbox',
        'dept' => 'Computer Science & Security',
        'pi' => 'Dr. Aris Vance',
        'grant' => 'NSF-SaTC-2210481',
        'budget' => '$98,500',
        'status' => 'Active',
        'nodes' => 16
    ],
    [
        'code' => 'PRJ-PHY-2026',
        'title' => 'Astrophysics N-Body Gravitational Cluster Sim',
        'dept' => 'Theoretical Physics',
        'pi' => 'Dr. Miriam Al-Hassan',
        'grant' => 'NASA-ATP-80NSSC22K',
        'budget' => '$185,000',
        'status' => 'Review',
        'nodes' => 2
    ],
    [
        'code' => 'PRJ-MED-2026',
        'title' => 'Clinical Imaging Neural Radiance Fields (NeRF)',
        'dept' => 'Biomedical Informatics',
        'pi' => 'Prof. David Thorne',
        'grant' => 'Chan-Zuckerberg-MedAI',
        'budget' => '$320,000',
        'status' => 'Active',
        'nodes' => 6
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Research Projects — CampusCloud</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<style>
:root {
    --bg-page: #f8fafc;
    --bg-surface: #ffffff;
    --border: #e2e8f0;
    --primary: #2563eb;
    --text-main: #0f172a;
    --text-muted: #64748b;
    --sidebar-bg: #0f172a;
    --sidebar-text: #94a3b8;
}

* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    background: var(--bg-page);
    color: var(--text-main);
    display: flex;
    min-height: 100vh;
}

aside {
    width: 250px;
    background: var(--sidebar-bg);
    color: var(--sidebar-text);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}
.sidebar-header {
    padding: 22px 20px;
    border-bottom: 1px solid #1e293b;
    display: flex;
    align-items: center;
    gap: 12px;
}
.brand-badge {
    width: 34px;
    height: 34px;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    color: white;
    font-weight: 800;
    font-size: 14px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.brand-name {
    font-size: 15px;
    font-weight: 800;
    color: #ffffff;
}
.brand-name span { color: #38bdf8; }
.brand-tag {
    font-size: 10px;
    color: #64748b;
    text-transform: uppercase;
}
.sidebar-menu {
    padding: 20px 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}
.menu-label {
    font-size: 10px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 8px 12px 4px;
}
.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 6px;
    color: #94a3b8;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
}
.nav-link:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #ffffff;
}
.nav-link.active {
    background: rgba(56, 189, 248, 0.12);
    color: #38bdf8;
    font-weight: 600;
}
.nav-link svg { width: 16px; height: 16px; stroke-width: 2; }

.main-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
}
header {
    background: var(--bg-surface);
    height: 64px;
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
main {
    padding: 32px;
    max-width: 1360px;
    width: 100%;
    margin: auto;
}
.page-title h1 {
    font-size: 22px;
    font-weight: 800;
    margin: 0 0 4px 0;
}
.page-title p {
    margin: 0 0 24px 0;
    color: var(--text-muted);
    font-size: 13px;
}

.projects-grid {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.project-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.p-code {
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    color: #2563eb;
    background: #eff6ff;
    padding: 2px 6px;
    border-radius: 4px;
}
.p-title {
    font-size: 16px;
    font-weight: 700;
    margin: 4px 0;
}
.p-meta {
    font-size: 12px;
    color: var(--text-muted);
}
.p-stats {
    text-align: right;
}
.badge-status {
    font-size: 11px;
    font-weight: 600;
    background: #ecfdf5;
    color: #059669;
    padding: 3px 8px;
    border-radius: 4px;
}
</style>
</head>
<body>

<aside>
    <div class="sidebar-header">
        <div class="brand-badge">CC</div>
        <div>
            <div class="brand-name">Campus<span>Cloud</span></div>
            <div class="brand-tag">Academic HPC Platform</div>
        </div>
    </div>
    <div class="sidebar-menu">
        <div class="menu-label">Compute Infrastructure</div>
        <a href="dashboard.php" class="nav-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Compute Workspaces
        </a>
        <a href="projects.php" class="nav-link active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
            Research Projects
        </a>
        <div class="menu-label" style="margin-top: 14px;">Administration</div>
        <a href="profile.php" class="nav-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            Faculty Profile
        </a>
        <a href="settings.php" class="nav-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            Workspace Settings
        </a>
    </div>
</aside>

<div class="main-wrapper">
    <header>
        <div style="font-weight:600;font-size:13px;">Oakridge University Research Portal</div>
        <span style="color:#64748b;font-size:11px;">HPC Cluster Partition: us-east-1a</span>
    </header>

    <main>
        <div class="page-title">
            <h1>Department Research Projects & Compute Quotas</h1>
            <p>Active university research grants with dedicated High Performance Computing (HPC) allocations.</p>
        </div>

        <div class="projects-grid">
            <?php foreach ($projects as $p): ?>
                <div class="project-card">
                    <div>
                        <span class="p-code"><?php echo $p['code']; ?></span>
                        <div class="p-title"><?php echo htmlspecialchars($p['title']); ?></div>
                        <div class="p-meta">
                            Department: <?php echo htmlspecialchars($p['dept']); ?> &bull; PI: <?php echo htmlspecialchars($p['pi']); ?> &bull; Grant: <?php echo htmlspecialchars($p['grant']); ?>
                        </div>
                    </div>
                    <div class="p-stats">
                        <span class="badge-status"><?php echo $p['status']; ?></span>
                        <div style="font-size:12px;color:#64748b;margin-top:4px;">Nodes: <?php echo $p['nodes']; ?> VMs &bull; Budget: <?php echo $p['budget']; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

</body>
</html>
