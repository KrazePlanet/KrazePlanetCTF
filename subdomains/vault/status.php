<?php
$active_nav = 'status';
$page_title = 'System Status & Control Planes | VaultTech';
require_once __DIR__ . '/includes/header.php';
?>

<section class="sub-hero">
    <div class="container">
        <h1>Gateway &amp; Control Plane Telemetry</h1>
        <p>Live operational availability across card acquiring nodes, settlement ledgers, and internal management consoles.</p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:860px;">
        <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">
            <div class="status-row">
                <div>
                    <strong>Core Acquiring Gateway (US-East-1)</strong>
                    <div style="font-size:12.5px;color:var(--ink-muted);">REST v2 charge &amp; authorization routing</div>
                </div>
                <span class="status-ok">Operational &middot; 99.996%</span>
            </div>
            <div class="status-row">
                <div>
                    <strong>Settlement Ledger &amp; Webhook Dispatcher</strong>
                    <div style="font-size:12.5px;color:var(--ink-muted);">SEPA Instant, FedNow, and SWIFT batch reconciliation</div>
                </div>
                <span class="status-ok">Operational &middot; 14ms</span>
            </div>
            <div class="status-row">
                <div>
                    <strong>Administration Console (<code>/admin/</code>)</strong>
                    <div style="font-size:12.5px;color:var(--ink-muted);">Internal IAM, role governance &amp; API key rotation</div>
                </div>
                <span class="status-ok">Operational</span>
            </div>
            <div class="status-row">
                <div>
                    <strong>Operations Center (<code>/manage/</code>)</strong>
                    <div style="font-size:12.5px;color:var(--ink-muted);">Merchant settlement monitoring &amp; risk review portal</div>
                </div>
                <span class="status-ok">Operational</span>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

