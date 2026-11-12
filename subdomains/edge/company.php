<?php
$active_nav = 'company';
$page_title = 'Company & Security Governance | KestrelEdge';
require_once __DIR__ . '/includes/header.php';
?>

<section class="subpage-header">
    <div class="container">
        <span class="pill-tag">About KestrelEdge</span>
        <h1>Global Financial Plumbing Built for Auditability</h1>
        <p>Founded in Boston in 2021, KestrelEdge partners with commercial banks and software platforms to modernize cross-border settlement.</p>
    </div>
</section>

<section class="section">
    <div class="container split-feature">
        <div>
            <span class="kicker">Our Mission</span>
            <h2>Transparent Rails for Borderless Teams</h2>
            <p>
                Modern platforms hire contractors, procure cloud capacity, and serve customers worldwide — yet legacy banking corridors force five-day settlement delays and opaque currency spreads.
            </p>
            <p>
                Our distributed engineering and compliance teams operate out of Boston, London, and Singapore to keep treasury infrastructure predictable, auditable, and developer-first.
            </p>
        </div>
        <div>
            <img src="/images/about-team-photo.jpg" alt="KestrelEdge headquarters collaboration" class="feature-photo">
        </div>
    </div>
</section>

<section class="section section-stone" id="security">
    <div class="container">
        <div class="cards-3">
            <div class="story-card" style="padding:26px;">
                <img src="/images/briefcase-svgrepo-com.svg" alt="Enterprise governance icon" style="width:36px;height:36px;margin-bottom:14px;">
                <h3 style="margin-bottom:8px;">SOC 2 Type II Audited</h3>
                <p style="margin:0;color:var(--ink-600);font-size:13.5px;">Independent annual attestation covering logical access controls, production change management, and encryption key rotation.</p>
            </div>
            <div class="story-card" style="padding:26px;">
                <h3 style="margin-bottom:8px;">PCI-DSS Level 1</h3>
                <p style="margin:0;color:var(--ink-600);font-size:13.5px;">Card verification workflows and vault storage follow strict network segmentation and least-privilege IAM boundaries.</p>
            </div>
            <div class="story-card" style="padding:26px;" id="contact">
                <h3 style="margin-bottom:8px;">Enterprise &amp; Security Desk</h3>
                <p style="margin:0 0 10px 0;color:var(--ink-600);font-size:13.5px;">For commercial treasury onboarding or responsible security disclosures, contact our operations desk directly:</p>
                <div style="font-weight:600;font-size:13px;color:var(--forest-950);">security@kestreledge.test &middot; treasury@kestreledge.test</div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

