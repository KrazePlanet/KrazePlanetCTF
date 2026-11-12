<?php
$active_nav = 'treasury';
$page_title = 'Treasury & Multi-Currency FX API | KestrelEdge';
require_once __DIR__ . '/includes/header.php';
?>

<section class="subpage-header">
    <div class="container">
        <span class="pill-tag">Treasury &amp; Settlement Rails</span>
        <h1>Programmable Multi-Currency Liquidity &amp; Payouts</h1>
        <p>Hold, convert, and disburse operational balances across 42 clearing networks with deterministic settlement webhook guarantees.</p>
    </div>
</section>

<section class="section">
    <div class="container split-feature" id="api">
        <div>
            <span class="kicker">Developer Settlement API</span>
            <h2>Execute Atomic FX Transfers With Built-In Idempotency</h2>
            <p>
                Lock guaranteed interbank quotes for up to 30 seconds and dispatch batch vendor payouts across SEPA Instant, FedNow, and UK Faster Payments using one unified payload.
            </p>
            <ul class="check-list">
                <li>HMAC-SHA256 signed webhook delivery verification</li>
                <li>Per-subaccount ledger isolation for marketplace escrow</li>
                <li>Automated ISO 20022 camt.053 daily statement exports</li>
            </ul>
        </div>
        <div>
            <pre class="code-box">POST /v2/treasury/payouts HTTP/1.1
Host: api.kestreledge.test
Authorization: Bearer live_key_9f82a4...
Idempotency-Key: pay_batch_2026_0318

{
  "source_account": "acct_us_treasury_01",
  "source_currency": "USD",
  "target_currency": "EUR",
  "amount": "25000.00",
  "rail": "sepa_instant",
  "beneficiary": {
    "legal_name": "Nordic Logistics GmbH",
    "iban": "DE89370400440532013000"
  }
}</pre>
        </div>
    </div>
</section>

<section class="section section-stone">
    <div class="container split-feature">
        <div>
            <img src="/file/zongnan-bao-kLTqQ5b8pRc-unsplash.jpg" alt="European commercial district clearing hub" class="feature-photo">
        </div>
        <div>
            <span class="kicker">Liquidity Corridors</span>
            <h2>Direct Participation Across Primary Financial Hubs</h2>
            <p>
                By connecting directly to domestic clearing schemes in New York, Frankfurt, London, and Tokyo, KestrelEdge eliminates intermediary correspondent hops and opaque wire deductions.
            </p>
            <a href="/company.php#contact" class="btn btn-dark">Request Corridor Schedule</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

