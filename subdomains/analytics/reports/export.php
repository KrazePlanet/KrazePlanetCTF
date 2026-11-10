<?php

/*
 * PULSEMETRICS — Enterprise Telemetry Export Receipt Generator
 * Generates verified audit certificates and telemetry archive dumps
 */

$title = $_GET['title'] ?? 'Telemetry Ingestion Batch';
$format = strtolower($_GET['format'] ?? 'csv');
$download = isset($_GET['download']) && $_GET['download'] === '1';

// If direct download requested, stream the sample CSV
if ($download) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="pulsemetrics_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $title) . '.csv"');
    
    echo "event_id,event_name,tenant_id,geo,latency_ms,status,timestamp\n";
    echo "evt_94821a,checkout.payment_intent_succeeded,org_acme_corp,US-VA,12.4,200,2026-11-10T16:42:10Z\n";
    echo "evt_73812b,workspace.retention_job_dispatched,org_nordic_media,EU-DE,18.2,200,2026-11-10T16:41:58Z\n";
    echo "evt_62910c,auth.sso_saml_asserted,org_apex_fintech,US-CA,9.7,200,2026-11-10T16:41:45Z\n";
    echo "evt_51829d,pipeline.kafka_batch_flushed,system_telemetry_bus,US-OH,15.6,200,2026-11-10T16:41:31Z\n";
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Telemetry Export Manifest — PulseMetrics</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
:root {
    --bg-page: #0b0f19;
    --bg-card: #111827;
    --border: #1f293d;
    --primary: #38bdf8;
    --text-primary: #f8fafc;
    --text-secondary: #94a3b8;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    background: var(--bg-page);
    color: var(--text-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 24px;
}
.manifest-box {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    max-width: 680px;
    width: 100%;
    box-shadow: 0 12px 36px rgba(0,0,0,0.4);
    overflow: hidden;
}
.manifest-header {
    padding: 24px 28px;
    border-bottom: 1px solid var(--border);
    background: #0d1320;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.brand-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 800;
    font-size: 14px;
    letter-spacing: -0.3px;
}
.brand-badge span { color: var(--primary); }
.status-pill {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #10b981;
    background: rgba(16, 185, 129, 0.15);
    border: 1px solid rgba(16, 185, 129, 0.3);
    padding: 4px 10px;
    border-radius: 20px;
}
.manifest-body {
    padding: 28px;
}
.manifest-title {
    font-size: 20px;
    font-weight: 700;
    margin: 0 0 12px 0;
    color: #ffffff;
}
.manifest-meta {
    background: #090d16;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 16px 20px;
    margin: 20px 0;
    font-size: 13px;
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 12px;
}
.meta-label {
    color: var(--text-secondary);
    font-weight: 600;
}
.meta-value {
    color: #f1f5f9;
}
.meta-value code {
    font-family: 'JetBrains Mono', monospace;
    color: var(--primary);
    background: rgba(56, 189, 248, 0.1);
    padding: 2px 6px;
    border-radius: 4px;
}
.actions-row {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}
.btn-primary {
    background: var(--primary);
    color: #0b0f19;
    padding: 10px 22px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 700;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: opacity 0.2s;
}
.btn-primary:hover { opacity: 0.9; }
.btn-secondary {
    background: #1e293b;
    border: 1px solid var(--border);
    color: var(--text-primary);
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
}
.btn-secondary:hover { background: #334155; }
</style>
</head>
<body>

<div class="manifest-box">
    <div class="manifest-header">
        <div class="brand-badge">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.5">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
            Pulse<span>Metrics</span> Manifest
        </div>
        <div class="status-pill">Ready for Archive</div>
    </div>

    <div class="manifest-body">
        <!-- VULNERABILITY: Reflected XSS via title parameter -->
        <h1 class="manifest-title">Export Dataset: <?php echo $title; ?></h1>
        <p style="color:#94a3b8;font-size:13px;margin:0;">
            The requested telemetry snapshot has been compiled from the ClickHouse OLAP cluster and verified with the partition hash.
        </p>

        <div class="manifest-meta">
            <div class="meta-label">Dataset Title:</div>
            <div class="meta-value"><?php echo $title; ?></div>

            <div class="meta-label">Export Format:</div>
            <div class="meta-value"><code><?php echo htmlspecialchars(strtoupper($format)); ?></code></div>

            <div class="meta-label">Cluster Region:</div>
            <div class="meta-value">us-east-1 (VPC Partition: shard-04)</div>

            <div class="meta-label">Generated At:</div>
            <div class="meta-value"><?php echo gmdate('Y-m-d H:i:s'); ?> UTC</div>

            <div class="meta-label">Record Count:</div>
            <div class="meta-value">4,819 rows (2.4 MB)</div>
        </div>

        <div class="actions-row">
            <a href="?title=<?php echo urlencode($title); ?>&format=<?php echo urlencode($format); ?>&download=1" class="btn-primary">
                Download <?php echo htmlspecialchars(strtoupper($format)); ?> Dataset
            </a>
            <a href="/" class="btn-secondary">Return to Console</a>
        </div>
    </div>
</div>

</body>
</html>

