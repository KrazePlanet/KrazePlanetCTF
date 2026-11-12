<?php
$active_nav = 'products';
$page_title = 'Products & Infrastructure | VaultTech';
require_once __DIR__ . '/includes/header.php';
?>

<section class="sub-hero">
    <div class="container">
        <h1>Unified Acquiring, Risk &amp; Ledger Stack</h1>
        <p>Modular infrastructure covering card tokenization, real-time fraud scoring, and split-settlement disbursements.</p>
    </div>
</section>

<section class="section">
    <div class="container split-row">
        <div>
            <span class="kicker">Direct Card Acquiring</span>
            <h2>Network Tokens &amp; Adaptive 3DS2 Routing</h2>
            <p style="color:var(--ink-soft);">
                Automatically swap raw PANs for issuer-issued Visa and Mastercard network tokens to prevent expired-card churn and elevate authorization acceptance rates across recurring SaaS billing.
            </p>
        </div>
        <div>
            <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=960&q=80" alt="Enterprise financial architecture" class="split-photo">
        </div>
    </div>
</section>

<section class="section section-canvas" id="fraud">
    <div class="container">
        <div class="cards-3">
            <div class="card">
                <h3>Vault Radar ML</h3>
                <p>Evaluates 450+ device, BIN, and behavioral velocity signals before authorization leaves the gateway.</p>
            </div>
            <div class="card">
                <h3>Multi-Rail Payouts</h3>
                <p>Dispatch marketplace vendor balances over FedNow, SEPA Instant, and Faster Payments with automated FX locks.</p>
            </div>
            <div class="card">
                <h3>Audit-Ready Reconciliation</h3>
                <p>Every gateway authorization ties directly to an immutable double-entry ledger line item and settlement report.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

