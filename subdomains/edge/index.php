<?php
$active_nav = 'home';
$page_title = 'KestrelEdge | Global Treasury & Cross-Border Settlement';
require_once __DIR__ . '/includes/header.php';
?>

<section class="fintech-hero">
    <div class="container hero-split">
        <div>
            <span class="pill-tag">Cross-Border Settlement Infrastructure</span>
            <h1>Move institutional treasury across 42 currencies at mid-market speed.</h1>
            <p class="hero-sub">
                Embed local IBAN collections, real-time FX conversion, and automated KYB compliance workflows directly inside your global commerce platform.
            </p>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="/treasury.php" class="btn btn-lime">Explore Treasury Rails</a>
                <a href="/onboarding.php" class="btn" style="background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.2);">KYC Architecture</a>
            </div>
        </div>

        <div class="fx-card">
            <div class="fx-card-head">
                <strong>Instant Settlement Estimator</strong>
                <span>Mid-Market Interbank Feed</span>
            </div>
            <div class="fx-input-group">
                <input type="number" id="fx-amount" value="25000" min="1" step="100">
                <select id="fx-from">
                    <option value="USD" selected>USD</option>
                    <option value="GBP">GBP</option>
                    <option value="EUR">EUR</option>
                </select>
            </div>
            <div class="fx-breakdown">
                <div class="fx-row">
                    <span>Platform settlement fee (0.28%)</span>
                    <strong id="fx-fee">$70.00</strong>
                </div>
                <div class="fx-row">
                    <span>Guaranteed FX conversion rate</span>
                    <strong id="fx-rate">1 USD = 0.9214 EUR</strong>
                </div>
                <div class="fx-row">
                    <span>Estimated rail delivery</span>
                    <strong>Same-day SEPA / Faster Payments</strong>
                </div>
            </div>
            <div class="fx-input-group" style="background:var(--stone-50);margin-bottom:16px;">
                <input type="text" id="fx-result" value="22,970.50" readonly style="background:transparent;">
                <select id="fx-to">
                    <option value="EUR" selected>EUR</option>
                    <option value="GBP">GBP</option>
                    <option value="JPY">JPY</option>
                    <option value="SGD">SGD</option>
                </select>
            </div>
            <a href="/company.php#contact" class="btn btn-dark" style="width:100%;">Open Corporate Sandbox</a>
        </div>
    </div>
</section>

<section class="trust-strip">
    <div class="container trust-grid">
        <div class="trust-item">
            <strong>$4.8B+</strong>
            <span>Annualized Cross-Border Volume</span>
        </div>
        <div class="trust-item">
            <strong>42</strong>
            <span>Local Clearing Currencies</span>
        </div>
        <div class="trust-item">
            <strong>99.99%</strong>
            <span>Edge Gateway Availability</span>
        </div>
        <div class="trust-item">
            <strong>1.4 sec</strong>
            <span>Median KYC Document Check</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="container split-feature">
        <div>
            <img src="/images/hero-banner-2024.jpg" alt="Global corporate headquarters infrastructure" class="feature-photo">
        </div>
        <div>
            <span class="kicker">Local Collection Accounts</span>
            <h2>Receive vendor settlements like a local entity in New York, London, and Singapore.</h2>
            <p>
                Eliminate correspondent wire delays and hidden SWIFT lifting fees. Issue dedicated virtual USD routing numbers, UK sort codes, and Euro vIBANs via a single REST endpoint.
            </p>
            <ul class="check-list">
                <li>Automated ISO 20022 remittance reconciliation</li>
                <li>Segregated safeguarding accounts with Tier-1 clearing banks</li>
                <li>Programmable sweep rules into multi-currency liquidity pools</li>
            </ul>
            <a href="/treasury.php" class="btn btn-ghost">Inspect Treasury Specs &rarr;</a>
        </div>
    </div>
</section>

<section class="section section-stone">
    <div class="container split-feature">
        <div>
            <span class="kicker">Automated Risk &amp; Compliance</span>
            <h2>Built-in KYB, liveness capture, and document verification for regulated marketplaces.</h2>
            <p>
                Verify beneficial owners, government-issued photo IDs, and settlement bank accounts in under two seconds before releasing cross-border merchant payouts.
            </p>
            <ul class="check-list">
                <li>Real-time PEP, OFAC, and EU sanctions screening</li>
                <li>Optical MRZ extraction for passports and national ID cards</li>
                <li>Encrypted WORM retention vaults for 7-year compliance audits</li>
            </ul>
            <a href="/onboarding.php" class="btn btn-dark">Explore Compliance Pipeline &rarr;</a>
        </div>
        <div>
            <img src="/file/dmytro-koplyk-zTGzTv5nTWM-unsplash.jpg" alt="Global commerce logistics and settlements" class="feature-photo">
        </div>
    </div>
</section>

<section class="section" id="insights">
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:32px;flex-wrap:wrap;gap:16px;">
            <div>
                <span class="kicker">Field Briefings &amp; Announcements</span>
                <h2 style="font-size:32px;">Latest From KestrelEdge</h2>
            </div>
            <a href="/company.php" class="btn btn-ghost">About Our Organization &rarr;</a>
        </div>

        <div class="cards-3">
            <article class="story-card">
                <img src="/images/blog-thumbnail-q2-2024.jpg" alt="Asia-Pacific treasury expansion">
                <div class="story-body">
                    <span class="kicker" style="font-size:10.5px;">Infrastructure</span>
                    <h3>Expanding Real-Time SGD &amp; JPY Corridors Across APAC</h3>
                    <p>How regional SaaS marketplaces cut settlement windows from T+3 wires to sub-hour local clearing via FAST and Zengin.</p>
                    <a href="/treasury.php" style="font-size:13px;font-weight:600;color:var(--emerald-600);">Read Technical Note &rarr;</a>
                </div>
            </article>

            <article class="story-card">
                <img src="/images/product-launch-event.jpg" alt="Annual fintech summit briefing">
                <div class="story-body">
                    <span class="kicker" style="font-size:10.5px;">Compliance</span>
                    <h3>Harmonizing Multi-Jurisdiction KYB Document Workflows</h3>
                    <p>Balancing low onboarding friction with strict beneficial-ownership verification across US, UK, and EEA entities.</p>
                    <a href="/onboarding.php" style="font-size:13px;font-weight:600;color:var(--emerald-600);">Explore KYB Engine &rarr;</a>
                </div>
            </article>

            <article class="story-card">
                <img src="/images/about-team-photo.jpg" alt="KestrelEdge operations and engineering team">
                <div class="story-body">
                    <span class="kicker" style="font-size:10.5px;">Company</span>
                    <h3>Behind Our US-East &amp; EU-Central Edge Asset Architecture</h3>
                    <p>Serving distributed web assets and partner media portals cleanly at low latency across global edge POPs.</p>
                    <a href="/company.php" style="font-size:13px;font-weight:600;color:var(--emerald-600);">Meet Leadership &rarr;</a>
                </div>
            </article>
        </div>
    </div>
</section>

<script>
(function () {
    var rates = {
        USD: { EUR: 0.9214, GBP: 0.7865, JPY: 152.40, SGD: 1.3410 },
        GBP: { EUR: 1.1715, GBP: 1.0000, JPY: 193.75, SGD: 1.7050 },
        EUR: { EUR: 1.0000, GBP: 0.8536, JPY: 165.40, SGD: 1.4554 }
    };
    var sym = { USD: "$", GBP: "£", EUR: "€" };

    var amtEl = document.getElementById("fx-amount");
    var fromEl = document.getElementById("fx-from");
    var toEl = document.getElementById("fx-to");
    var resEl = document.getElementById("fx-result");
    var feeEl = document.getElementById("fx-fee");
    var rateEl = document.getElementById("fx-rate");

    function recalc() {
        var amount = Math.max(0, parseFloat(amtEl.value) || 0);
        var from = fromEl.value;
        var to = toEl.value;
        var fee = amount * 0.0028;
        var rate = (rates[from] && rates[from][to]) ? rates[from][to] : 1;
        var converted = (amount - fee) * rate;
        feeEl.textContent = (sym[from] || "") + fee.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        rateEl.textContent = "1 " + from + " = " + rate.toFixed(4) + " " + to;
        resEl.value = converted.toLocaleString(undefined, { minimumFractionDigits: to === "JPY" ? 0 : 2, maximumFractionDigits: to === "JPY" ? 0 : 2 });
    }

    amtEl.addEventListener("input", recalc);
    fromEl.addEventListener("change", recalc);
    toEl.addEventListener("change", recalc);
    recalc();
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

