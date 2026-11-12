<?php
$active_nav = 'pricing';
$page_title = 'Transparent Interchange+ Pricing | VaultTech';
require_once __DIR__ . '/includes/header.php';
?>

<section class="sub-hero">
    <div class="container">
        <h1>Predictable Pricing Built for Scale</h1>
        <p>Flat blended rates for emerging platforms, or custom Interchange++ routing once monthly volume exceeds $250k.</p>
    </div>
</section>

<section class="section">
    <div class="container cards-3">
        <div class="card">
            <span class="kicker">Growth</span>
            <h3>2.7% + $0.25</h3>
            <p style="margin-bottom:16px;">Per successful domestic card charge. No monthly platform minimum or setup fees.</p>
            <div style="font-size:13px;color:var(--ink-soft);">Includes Radar Fraud Shield, hosted Checkout, and standard T+1 settlements.</div>
        </div>

        <div class="card" style="border-color:var(--brand);box-shadow:0 12px 30px rgba(99,91,255,0.08);">
            <span class="kicker">Enterprise (IC++)</span>
            <h3>Interchange + 0.18%</h3>
            <p style="margin-bottom:16px;">Pass-through card scheme fees for platforms processing $250k+ monthly card volume.</p>
            <div style="font-size:13px;color:var(--ink-soft);">Includes dedicated TAM, network tokenization, and multi-currency ledger isolation.</div>
        </div>

        <div class="card" id="enterprise">
            <span class="kicker">Global Treasury</span>
            <h3>Custom Volume Tiers</h3>
            <p style="margin-bottom:16px;">Direct bespoke commercial routing for marketplaces, PSPs, and cross-border platforms.</p>
            <div style="font-size:13px;font-weight:600;color:var(--brand);">sales@vaulttech.io</div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

