<?php
$page = $_GET['page'] ?? 'home';
if ($page === 'admin') {
    require __DIR__ . '/admin.php';
    exit;
}
if ($page === 'manage') {
    require __DIR__ . '/manage.php';
    exit;
}

$active_nav = 'home';
$page_title = 'VaultTech — Enterprise Payment Processing & Settlement';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero-section">
    <div class="container hero-grid">
        <div>
            <span class="badge-pill">PCI-DSS Level 1 &middot; Unified Payment Rails</span>
            <h1>Financial infrastructure that routes every dollar cleanly.</h1>
            <p class="hero-lead">
                Accept global card schemes, automate multi-party marketplace payouts, and score anomalous behavior in under 15 milliseconds via one unified API.
            </p>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="/docs.php" class="btn btn-brand">Explore API Reference</a>
                <a href="/pricing.php" class="btn btn-outline">Calculate Processing Rates</a>
            </div>
        </div>

        <div class="api-preview">
            <div class="api-tabs">
                <button type="button" class="api-tab active" data-snippet="charge">payment_intents.create</button>
                <button type="button" class="api-tab" data-snippet="payout">settlements.dispatch</button>
                <button type="button" class="api-tab" data-snippet="webhook">charge.succeeded</button>
            </div>
            <pre class="api-code" id="hero-code"></pre>
        </div>
    </div>
</section>

<section class="metrics-band">
    <div class="container metrics-grid">
        <div class="metric-item">
            <strong>$2.4T+</strong>
            <span>Annualized Volume Processed</span>
        </div>
        <div class="metric-item">
            <strong>99.995%</strong>
            <span>Historical Gateway Uptime</span>
        </div>
        <div class="metric-item">
            <strong>150+</strong>
            <span>Supported Settlement Currencies</span>
        </div>
        <div class="metric-item">
            <strong>&lt;14 ms</strong>
            <span>Median Risk Scoring Latency</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="container split-row">
        <div>
            <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=960&q=80" alt="Real-time settlement analytics console" class="split-photo">
        </div>
        <div>
            <span class="kicker">Smart Authorization Routing</span>
            <h2>Recover up to 2.1% in false card declines with issuer-level telemetry.</h2>
            <p style="color:var(--ink-soft);margin-bottom:18px;">
                Network tokens, adaptive 3DS2 exemptions, and direct Visa/Mastercard acquiring connections maximize approval rates while shielding merchants from cross-border interchange surcharges.
            </p>
            <a href="/products.php" class="btn btn-ink">Inspect Acquiring Stack &rarr;</a>
        </div>
    </div>
</section>

<section class="section section-canvas">
    <div class="container">
        <div style="text-align:center;max-width:620px;margin:0 auto 40px;">
            <span class="kicker">Core Architecture</span>
            <h2>Built for SaaS Platforms, FinTechs &amp; Marketplaces</h2>
        </div>
        <div class="cards-3">
            <div class="card">
                <h3>Instant Acquiring</h3>
                <p>Direct scheme connections in North America, EU, UK, and APAC with same-day local clearing rails.</p>
            </div>
            <div class="card">
                <h3>Radar ML Fraud Shield</h3>
                <p>Device fingerprinting, velocity limits, and real-time chargeback rules tuned across 10M+ daily charges.</p>
            </div>
            <div class="card">
                <h3>Double-Entry Ledgers</h3>
                <p>Immutable sub-account balance tracking, split-fee marketplace payouts, and automated 1099-K tax exports.</p>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var snippets = {
        charge: 'curl https://api.vaulttech.io/v2/payment_intents \\\n' +
                '  -H "Authorization: Bearer vt_live_84c1..." \\\n' +
                '  -d amount=14900 \\\n' +
                '  -d currency="usd" \\\n' +
                '  -d "capture_method=automatic"\n\n' +
                '// 200 OK (11ms)\n' +
                '{\n  "id": "pi_3Q9aK1Vt882",\n  "status": "succeeded",\n  "risk_score": 8\n}',
        payout: 'POST /v2/settlements/dispatch HTTP/1.1\nHost: api.vaulttech.io\n\n' +
                '{\n' +
                '  "merchant_account": "acct_enterprise_409",\n' +
                '  "amount": 4820000,\n' +
                '  "currency": "eur",\n' +
                '  "rail": "sepa_instant",\n' +
                '  "idempotency_key": "settle_2026_11_11"\n' +
                '}',
        webhook: '{\n' +
                '  "id": "evt_99182041",\n' +
                '  "type": "charge.succeeded",\n' +
                '  "api_version": "2026-01-15",\n' +
                '  "data": {\n' +
                '    "object": "charge",\n' +
                '    "amount_captured": 14900,\n' +
                '    "network_token_used": true\n' +
                '  }\n' +
                '}'
    };

    var codeEl = document.getElementById("hero-code");
    codeEl.textContent = snippets.charge;

    document.querySelectorAll(".api-tab").forEach(function (btn) {
        btn.addEventListener("click", function () {
            document.querySelectorAll(".api-tab").forEach(function (b) { b.classList.remove("active"); });
            btn.classList.add("active");
            codeEl.textContent = snippets[btn.getAttribute("data-snippet")] || snippets.charge;
        });
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
