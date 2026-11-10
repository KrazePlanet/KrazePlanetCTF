<?php

/*
 * CAMPUSCLOUD ENTERPRISE PLATFORM
 * High Performance Academic Cloud & Virtual Lab Console
 */

$workspaces = [
    [
        'id' => 'ws-genomics-ai',
        'name' => 'Genomics Deep Learning Lab',
        'dept' => 'Computational Biology',
        'lead' => 'Dr. Eleanor Weiss',
        'instances' => '4x NVIDIA A100 (80GB)',
        'storage' => '1.8 TB NVMe',
        'status' => 'Running',
        'uptime' => '18d 6h'
    ],
    [
        'id' => 'ws-robotics-sim',
        'name' => 'Autonomous Robotics Sim (ROS2)',
        'dept' => 'Robotics & Mechatronics',
        'lead' => 'Prof. Kenji Sato',
        'instances' => '8x Compute Optimized c6i.4xlarge',
        'storage' => '840 GB EBS',
        'status' => 'Running',
        'uptime' => '9d 14h'
    ],
    [
        'id' => 'ws-cyber-sandbox',
        'name' => 'Cyber Defense Sandbox & Range',
        'dept' => 'Computer Science & Security',
        'lead' => 'Dr. Aris Vance',
        'instances' => '16x Isolated Linux VPC Pods',
        'storage' => '500 GB S3 Vault',
        'status' => 'Running',
        'uptime' => '34d 2h'
    ],
    [
        'id' => 'ws-quantum-sim',
        'name' => 'Quantum Algorithmic Simulator',
        'dept' => 'Theoretical Physics',
        'lead' => 'Dr. Miriam Al-Hassan',
        'instances' => '2x Memory Optimized r6i.8xlarge',
        'storage' => '450 GB High-IOPS',
        'status' => 'Suspended',
        'uptime' => 'Idle'
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CampusCloud — Enterprise Academic Compute & LMS Workspace</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<style>
:root {
    --bg-page: #f8fafc;
    --bg-surface: #ffffff;
    --border: #e2e8f0;
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
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

/* SIDEBAR */
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
    letter-spacing: 0.5px;
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
    transition: all 0.15s;
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
.nav-link svg {
    width: 16px;
    height: 16px;
    stroke-width: 2;
}

.sidebar-user {
    padding: 16px;
    border-top: 1px solid #1e293b;
    display: flex;
    align-items: center;
    gap: 12px;
}
.user-avatar {
    width: 34px;
    height: 34px;
    background: #1e293b;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #38bdf8;
    font-weight: 700;
    font-size: 12px;
}
.user-name {
    font-size: 12px;
    font-weight: 600;
    color: #ffffff;
}
.user-role {
    font-size: 11px;
    color: #64748b;
}

/* MAIN CONTENT */
.main-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
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
.org-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-main);
}
.badge-vpc {
    font-size: 11px;
    font-weight: 600;
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
    padding: 3px 8px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.vpc-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
}

main {
    padding: 32px;
    max-width: 1360px;
    width: 100%;
    margin: auto;
}

.page-title {
    margin-bottom: 24px;
}
.page-title h1 {
    font-size: 22px;
    font-weight: 800;
    margin: 0 0 4px 0;
    letter-spacing: -0.4px;
}
.page-title p {
    margin: 0;
    color: var(--text-muted);
    font-size: 13px;
}

/* KPI CARDS */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 28px;
}
.kpi-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 20px;
}
.kpi-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
    margin-bottom: 8px;
}
.kpi-val {
    font-size: 26px;
    font-weight: 800;
    color: var(--text-main);
    line-height: 1.1;
    margin-bottom: 6px;
}
.kpi-sub {
    font-size: 12px;
    color: #059669;
    font-weight: 500;
}

/* WORKSPACES TABLE */
.card-table {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 28px;
}
.card-head {
    padding: 18px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.card-head h2 {
    font-size: 15px;
    font-weight: 700;
    margin: 0;
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    text-align: left;
}
th {
    background: #f8fafc;
    padding: 12px 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border);
}
td {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}
tr:last-child td { border-bottom: none; }
tr:hover td { background: #f8fafc; }

.ws-name {
    font-weight: 600;
    color: var(--text-main);
    display: block;
}
.ws-id {
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    color: var(--text-muted);
}
.badge-status {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 4px;
}
.status-running {
    background: #ecfdf5;
    color: #059669;
}
.status-suspended {
    background: #fef2f2;
    color: #dc2626;
}
.btn-launch {
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
    background: #eff6ff;
    color: var(--primary);
    border: 1px solid #bfdbfe;
    border-radius: 4px;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-launch:hover {
    background: var(--primary);
    color: white;
}

/* FOOTER */
footer {
    padding: 24px 32px;
    border-top: 1px solid var(--border);
    background: var(--bg-surface);
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--text-muted);
}
.footer-meta {
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
}
</style>
</head>
<body>

<!-- SIDEBAR -->
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
        <a href="dashboard.php" class="nav-link active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Compute Workspaces
        </a>
        <a href="projects.php" class="nav-link">
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

    <div class="sidebar-user">
        <div class="user-avatar">AV</div>
        <div>
            <div class="user-name">Dr. Alexander Vance</div>
            <div class="user-role">HPC Lead Architect</div>
        </div>
    </div>
</aside>

<!-- MAIN APP BODY -->
<div class="main-wrapper">
    <header>
        <div class="org-info">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
            <span>Oakridge National University &bull; High Performance Computing Cluster</span>
        </div>
        <div>
            <span class="badge-vpc">
                <span class="vpc-dot"></span>
                VPC us-east-1a &bull; Nominal (99.98%)
            </span>
        </div>
    </header>

    <main>
        <div class="page-title">
            <h1>Active Compute Workspaces & Research Sandboxes</h1>
            <p>Provisioned Kubernetes pods, dedicated GPU clusters, and S3 student backup volumes.</p>
        </div>

        <!-- KPI SUMMARY CARDS -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Active Compute Instances</div>
                <div class="kpi-val">142 VMs</div>
                <div class="kpi-sub">&bull; 18 GPU nodes allocated</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">S3 Storage Utilized</div>
                <div class="kpi-val">4.2 TB</div>
                <div class="kpi-sub">&bull; 10 TB quota (42%)</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Active Research Projects</div>
                <div class="kpi-val">28 Labs</div>
                <div class="kpi-sub">&bull; 6 pending peer review</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Monthly Compute Hours</div>
                <div class="kpi-val">8,420 hrs</div>
                <div class="kpi-sub">&bull; NSF Grant funded</div>
            </div>
        </div>

        <!-- WORKSPACES TABLE -->
        <div class="card-table">
            <div class="card-head">
                <h2>Allocated High Performance Virtual Labs</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Workspace Title</th>
                        <th>Department</th>
                        <th>Principal Investigator</th>
                        <th>Compute Resources</th>
                        <th>Assigned Storage</th>
                        <th>Cluster Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workspaces as $ws): ?>
                        <tr>
                            <td>
                                <span class="ws-name"><?php echo htmlspecialchars($ws['name']); ?></span>
                                <span class="ws-id"><?php echo htmlspecialchars($ws['id']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($ws['dept']); ?></td>
                            <td><?php echo htmlspecialchars($ws['lead']); ?></td>
                            <td><code><?php echo htmlspecialchars($ws['instances']); ?></code></td>
                            <td><?php echo htmlspecialchars($ws['storage']); ?></td>
                            <td>
                                <?php if ($ws['status'] === 'Running'): ?>
                                    <span class="badge-status status-running">Running (<?php echo $ws['uptime']; ?>)</span>
                                <?php else: ?>
                                    <span class="badge-status status-suspended">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="#" class="btn-launch">Connect</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer>
        <div>&copy; 2026 CampusCloud Higher Education Systems &bull; Oakridge University IT Infrastructure</div>
        <div class="footer-meta">Deployment: v4.8.3-prod &bull; Node: node04 &bull; Cluster: camp-vpc-us-east-1</div>
    </footer>
</div>

</body>
</html>
