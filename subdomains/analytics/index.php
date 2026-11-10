<?php

/*
 * PULSEMETRICS — Enterprise Telemetry & Product Analytics
 * Production Cloud Observability Console (v4.18.2)
 */

$filter = $_GET['filter'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PulseMetrics — Enterprise Telemetry & Product Analytics</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<style>
:root {
    --bg-app: #0b0f19;
    --bg-card: #111827;
    --bg-surface: #1e293b;
    --border-subtle: #1f293d;
    --border-strong: #334155;
    --primary: #38bdf8;
    --primary-glow: rgba(56, 189, 248, 0.15);
    --accent: #6366f1;
    --success: #10b981;
    --warning: #f59e0b;
    --text-primary: #f8fafc;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
}

* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    background: var(--bg-app);
    color: var(--text-primary);
    line-height: 1.5;
    display: flex;
    min-height: 100vh;
}

/* SIDEBAR */
aside {
    width: 250px;
    background: #090d16;
    border-right: 1px solid var(--border-subtle);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}
.sidebar-brand {
    padding: 22px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid var(--border-subtle);
}
.brand-icon {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #38bdf8, #6366f1);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 0 16px rgba(56, 189, 248, 0.4);
}
.brand-name {
    font-size: 15px;
    font-weight: 800;
    letter-spacing: -0.3px;
    color: #ffffff;
}
.brand-name span {
    color: var(--primary);
}
.brand-env {
    font-size: 10px;
    color: var(--text-muted);
    font-weight: 500;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.sidebar-menu {
    padding: 20px 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}
.menu-group-title {
    font-size: 10px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 8px 12px 4px;
}
.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 9px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-secondary);
    text-decoration: none;
    transition: all 0.15s ease;
}
.nav-item:hover {
    background: rgba(255, 255, 255, 0.04);
    color: var(--text-primary);
}
.nav-item.active {
    background: var(--primary-glow);
    color: var(--primary);
    font-weight: 600;
}
.nav-item svg {
    width: 16px;
    height: 16px;
    stroke-width: 2;
    opacity: 0.85;
}

.sidebar-footer {
    padding: 16px;
    border-top: 1px solid var(--border-subtle);
    display: flex;
    align-items: center;
    gap: 12px;
}
.avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #334155;
    color: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
}
.user-info {
    line-height: 1.2;
}
.user-name {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-primary);
}
.user-role {
    font-size: 10px;
    color: var(--text-muted);
}

/* MAIN CONTENT */
.content-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    overflow-y: auto;
}

/* TOPBAR */
header {
    height: 64px;
    background: #090d16;
    border-bottom: 1px solid var(--border-subtle);
    padding: 0 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.workspace-selector {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #111827;
    border: 1px solid var(--border-subtle);
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-primary);
}
.live-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.25);
    color: var(--success);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.live-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--success);
    box-shadow: 0 0 8px var(--success);
}
.header-actions {
    display: flex;
    align-items: center;
    gap: 14px;
}
.date-badge {
    font-size: 12px;
    color: var(--text-secondary);
    background: #111827;
    border: 1px solid var(--border-subtle);
    padding: 6px 12px;
    border-radius: 6px;
}

/* MAIN DASHBOARD */
main {
    padding: 32px;
    max-width: 1380px;
    width: 100%;
    margin: auto;
}

.dashboard-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 24px;
    gap: 20px;
    flex-wrap: wrap;
}
.dashboard-title-row h1 {
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 4px 0;
    letter-spacing: -0.4px;
}
.dashboard-title-row p {
    margin: 0;
    font-size: 13px;
    color: var(--text-secondary);
}

.search-form {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #111827;
    border: 1px solid var(--border-subtle);
    padding: 4px 6px 4px 12px;
    border-radius: 6px;
}
.search-form input {
    background: transparent;
    border: none;
    outline: none;
    color: #ffffff;
    font-size: 13px;
    width: 280px;
    font-family: inherit;
}
.search-form input::placeholder {
    color: var(--text-muted);
}
.btn-filter {
    background: var(--primary);
    color: #0b0f19;
    border: none;
    padding: 6px 14px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.2s;
}
.btn-filter:hover { opacity: 0.9; }
.btn-clear {
    color: var(--text-muted);
    font-size: 12px;
    text-decoration: none;
    padding: 0 8px;
}
.btn-clear:hover { color: #f87171; }

.filter-alert {
    background: rgba(56, 189, 248, 0.08);
    border: 1px solid rgba(56, 189, 248, 0.25);
    border-radius: 6px;
    padding: 10px 16px;
    margin-bottom: 24px;
    font-size: 13px;
    color: #bae6fd;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* KPI CARDS */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 28px;
}
.kpi-card {
    background: var(--bg-card);
    border: 1px solid var(--border-subtle);
    border-radius: 10px;
    padding: 22px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}
.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--primary), transparent);
    opacity: 0;
    transition: opacity 0.2s;
}
.kpi-card:hover::before { opacity: 1; }

.kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}
.kpi-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.6px;
}
.kpi-value {
    font-size: 30px;
    font-weight: 800;
    letter-spacing: -0.8px;
    color: #ffffff;
    line-height: 1.1;
    margin-bottom: 8px;
}
.kpi-sub {
    font-size: 11px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}
.kpi-sub.up { color: var(--success); }
.kpi-sub.neutral { color: var(--primary); }

/* CHART PANEL */
.chart-panel {
    background: var(--bg-card);
    border: 1px solid var(--border-subtle);
    border-radius: 10px;
    padding: 24px;
    margin-bottom: 28px;
}
.panel-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 16px;
    flex-wrap: wrap;
}
.panel-title {
    font-size: 15px;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
}
.panel-subtitle {
    font-size: 12px;
    color: var(--text-secondary);
    margin: 2px 0 0 0;
}
.panel-controls {
    display: flex;
    gap: 8px;
    align-items: center;
}
.period-btn {
    background: #1e293b;
    border: 1px solid var(--border-subtle);
    color: var(--text-secondary);
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.period-btn:hover, .period-btn.active {
    background: var(--primary);
    color: #0b0f19;
    border-color: var(--primary);
}
.btn-export-link {
    background: #1e293b;
    border: 1px solid var(--border-strong);
    color: #f1f5f9;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.btn-export-link:hover {
    background: var(--primary);
    color: #0b0f19;
    border-color: var(--primary);
}

.chart-svg-wrap {
    width: 100%;
    height: 220px;
}

/* EVENT STREAM TABLE */
.data-panel {
    background: var(--bg-card);
    border: 1px solid var(--border-subtle);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 28px;
}
.table-responsive {
    width: 100%;
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    text-align: left;
}
th {
    background: #0d131f;
    padding: 12px 18px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--text-secondary);
    border-bottom: 1px solid var(--border-subtle);
}
td {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border-subtle);
    color: #cbd5e1;
}
tr:hover td {
    background: rgba(255, 255, 255, 0.02);
}
.evt-code {
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    color: #38bdf8;
    background: rgba(56, 189, 248, 0.1);
    padding: 3px 8px;
    border-radius: 4px;
}
.badge-geo {
    font-family: 'JetBrains Mono', monospace;
    font-size: 10px;
    font-weight: 700;
    background: #334155;
    color: #e2e8f0;
    padding: 2px 5px;
    border-radius: 3px;
    margin-right: 6px;
}
.badge-status {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 4px;
}
.status-success {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
}
.text-muted { color: var(--text-muted); font-size: 12px; }

/* INFRASTRUCTURE TOPOLOGY WIDGET */
.infra-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
.infra-card {
    background: #0d1322;
    border: 1px solid var(--border-subtle);
    border-radius: 8px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.infra-name {
    font-size: 12px;
    font-weight: 700;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.infra-metric {
    font-size: 11px;
    color: var(--text-secondary);
    font-family: 'JetBrains Mono', monospace;
}
.infra-status {
    font-size: 10px;
    font-weight: 700;
    color: var(--success);
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

@media (max-width: 1024px) {
    .kpi-grid, .infra-grid { grid-template-columns: repeat(2, 1fr); }
    aside { display: none; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside>
    <div class="sidebar-brand">
        <div class="brand-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
        </div>
        <div>
            <div class="brand-name">Pulse<span>Metrics</span></div>
            <div class="brand-env">Enterprise Cloud</div>
        </div>
    </div>

    <div class="sidebar-menu">
        <div class="menu-group-title">Observability</div>
        <a href="/" class="nav-item active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Ingestion Overview
        </a>
        <a href="#" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            Live Event Stream
        </a>
        <a href="#" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M18 20V10"></path><path d="M12 20V4"></path><path d="M6 20v-6"></path></svg>
            Funnels & Cohorts
        </a>

        <div class="menu-group-title" style="margin-top: 14px;">Developer & Ops</div>
        <a href="/api/v2/internal/cluster/health" class="nav-item" target="_blank">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
            Cluster Topology
        </a>
        <a href="/src/config/secrets.ts" class="nav-item" target="_blank">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>
            API Credentials
        </a>
        <a href="/reports/export.php?title=Production+Telemetry+Snapshot+2026&format=csv" class="nav-item" target="_blank">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export Telemetry
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="avatar">SC</div>
        <div class="user-info">
            <div class="user-name">Sarah Chen</div>
            <div class="user-role">Principal SRE</div>
        </div>
    </div>
</aside>

<!-- MAIN APP BODY -->
<div class="content-area">

    <!-- TOPBAR -->
    <header>
        <div class="workspace-selector">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
            <span>Acme Global Operations (Prod · us-east-1)</span>
        </div>

        <div class="header-actions">
            <div class="live-pill">
                <span class="live-dot"></span>
                Live Ingestion Stream
            </div>
            <div class="date-badge">Nov 03 - Nov 10, 2026</div>
        </div>
    </header>

    <!-- DASHBOARD VIEW -->
    <main>
        <div class="dashboard-title-row">
            <div>
                <h1>Real-Time Telemetry & Event Ingestion</h1>
                <p>Global event streaming metrics across production Kafka partitions and ClickHouse OLAP stores.</p>
            </div>

            <!-- SEARCH / FILTER BAR -->
            <form method="GET" action="/" class="search-form">
                <input type="text" name="filter" placeholder="Filter telemetry namespace..." value="<?php echo htmlspecialchars($filter, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn-filter">Search</button>
                <?php if (!empty($filter)): ?>
                    <a href="/" class="btn-clear" title="Reset filter">✕</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($filter)): ?>
            <!-- VULNERABILITY: Reflected XSS via filter parameter -->
            <div class="filter-alert">
                <div>Active Event Namespace Query: <strong><?php echo $filter; ?></strong> &mdash; Filtered 4 active streams</div>
                <a href="/" style="color:#38bdf8;text-decoration:none;font-weight:600;font-size:12px;">Reset View</a>
            </div>
        <?php endif; ?>

        <!-- KPI SUMMARY CARDS -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <span class="kpi-label">Events Ingested (24h)</span>
                </div>
                <div class="kpi-value" id="metric-events">42.8M</div>
                <div class="kpi-sub up">
                    ▲ +14.2% vs previous period
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <span class="kpi-label">Daily Active Users</span>
                </div>
                <div class="kpi-value" id="metric-users">184,920</div>
                <div class="kpi-sub up">
                    ▲ +8.6% week-over-week
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <span class="kpi-label">P95 Ingestion Latency</span>
                </div>
                <div class="kpi-value" id="metric-latency">14.2ms</div>
                <div class="kpi-sub neutral">
                    ● SLA Target &lt; 50ms (Optimal)
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <span class="kpi-label">Gateway Traffic</span>
                </div>
                <div class="kpi-value" id="metric-requests">1.29M/hr</div>
                <div class="kpi-sub neutral">
                    ● 0.001% error rate (Nominal)
                </div>
            </div>
        </div>

        <!-- TELEMETRY CHART PANEL -->
        <div class="chart-panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Ingestion Throughput Velocity (msgs / sec)</h2>
                    <p class="panel-subtitle">Aggregated over 24-hour rolling window across primary broker nodes</p>
                </div>
                <div class="panel-controls">
                    <button class="period-btn">1h</button>
                    <button class="period-btn active">24h</button>
                    <button class="period-btn">7d</button>
                    <button class="period-btn">30d</button>
                    <a href="/reports/export.php?title=Production+Telemetry+Snapshot+2026&format=csv" class="btn-export-link" target="_blank">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Export Dataset
                    </a>
                </div>
            </div>

            <div class="chart-svg-wrap">
                <svg viewBox="0 0 1000 200" preserveAspectRatio="none" style="width:100%;height:100%;">
                    <defs>
                        <linearGradient id="areaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.35"/>
                            <stop offset="100%" stop-color="#38bdf8" stop-opacity="0.0"/>
                        </linearGradient>
                    </defs>
                    <!-- Grid Lines -->
                    <line x1="0" y1="40" x2="1000" y2="40" stroke="#1f293d" stroke-dasharray="4"/>
                    <line x1="0" y1="90" x2="1000" y2="90" stroke="#1f293d" stroke-dasharray="4"/>
                    <line x1="0" y1="140" x2="1000" y2="140" stroke="#1f293d" stroke-dasharray="4"/>
                    
                    <!-- Area Fill -->
                    <polygon fill="url(#areaGradient)" points="
                        0,160 
                        90,140 
                        180,150 
                        270,120 
                        360,85 
                        450,55 
                        540,30 
                        630,25 
                        720,40 
                        810,65 
                        900,80 
                        1000,100 
                        1000,200 
                        0,200
                    "/>
                    <!-- Stroke Curve -->
                    <polyline fill="none" stroke="#38bdf8" stroke-width="3" points="
                        0,160 
                        90,140 
                        180,150 
                        270,120 
                        360,85 
                        450,55 
                        540,30 
                        630,25 
                        720,40 
                        810,65 
                        900,80 
                        1000,100
                    "/>
                </svg>
            </div>
        </div>

        <!-- RECENT LIVE EVENT LOG TABLE -->
        <div class="data-panel">
            <div class="panel-head" style="padding: 20px 24px 10px;">
                <div>
                    <h2 class="panel-title">Realtime Ingestion Event Feed</h2>
                    <p class="panel-subtitle">Inspecting latest telemetry packets processed by edge collector nodes</p>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Event Action</th>
                            <th>Tenant / Organization</th>
                            <th>Origin Geo & IP</th>
                            <th>Ingest Latency</th>
                            <th>Status</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody id="events-tbody">
                        <tr>
                            <td><code class="evt-code">checkout.payment_intent_succeeded</code></td>
                            <td>org_acme_corp</td>
                            <td><span class="badge-geo">US-VA</span> 198.51.100.44</td>
                            <td>12.4ms</td>
                            <td><span class="badge-status status-success">200 OK</span></td>
                            <td class="text-muted">16:42:10 UTC</td>
                        </tr>
                        <tr>
                            <td><code class="evt-code">workspace.retention_job_dispatched</code></td>
                            <td>org_nordic_media</td>
                            <td><span class="badge-geo">EU-DE</span> 10.244.3.118</td>
                            <td>18.2ms</td>
                            <td><span class="badge-status status-success">200 OK</span></td>
                            <td class="text-muted">16:41:58 UTC</td>
                        </tr>
                        <tr>
                            <td><code class="evt-code">auth.sso_saml_asserted</code></td>
                            <td>org_apex_fintech</td>
                            <td><span class="badge-geo">US-CA</span> 203.0.113.89</td>
                            <td>9.7ms</td>
                            <td><span class="badge-status status-success">200 OK</span></td>
                            <td class="text-muted">16:41:45 UTC</td>
                        </tr>
                        <tr>
                            <td><code class="evt-code">pipeline.kafka_batch_flushed</code></td>
                            <td>system_telemetry_bus</td>
                            <td><span class="badge-geo">US-OH</span> 10.244.7.42</td>
                            <td>15.6ms</td>
                            <td><span class="badge-status status-success">200 OK</span></td>
                            <td class="text-muted">16:41:31 UTC</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- INFRASTRUCTURE HEALTH WIDGET -->
        <div class="infra-grid">
            <div class="infra-card">
                <div class="infra-name">
                    <span>Kafka Cluster Bus</span>
                    <span class="infra-status">● Healthy</span>
                </div>
                <div class="infra-metric">3/3 broker nodes in sync</div>
            </div>

            <div class="infra-card">
                <div class="infra-name">
                    <span>ClickHouse OLAP</span>
                    <span class="infra-status">● Healthy</span>
                </div>
                <div class="infra-metric">1.49B telemetry rows indexed</div>
            </div>

            <div class="infra-card">
                <div class="infra-name">
                    <span>Redis Session Cache</span>
                    <span class="infra-status">● Healthy</span>
                </div>
                <div class="infra-metric">99.4% cache hit ratio</div>
            </div>

            <div class="infra-card">
                <div class="infra-name">
                    <span>Ingress Collector Pool</span>
                    <span class="infra-status">● Healthy</span>
                </div>
                <div class="infra-metric">16/16 worker pods active</div>
            </div>
        </div>
    </main>
</div>

<script src="/static/js/app.min.js"></script>
</body>
</html>
