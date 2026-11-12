<?php
$active_nav = 'docs';
$page_title = 'Developer Documentation & REST API | VaultTech';
require_once __DIR__ . '/includes/header.php';
?>

<section class="sub-hero">
    <div class="container">
        <h1>VaultTech API v2 Reference</h1>
        <p>Predictable JSON endpoints, idempotent mutations, and HMAC-SHA256 webhook verification.</p>
    </div>
</section>

<section class="section">
    <div class="container split-row">
        <div>
            <span class="kicker">Authentication</span>
            <h2>Bearer Token &amp; Idempotency</h2>
            <p style="color:var(--ink-soft);">
                Authenticate API requests using HTTP Bearer credentials scoped to your environment. Include an <code>Idempotency-Key</code> header on every <code>POST</code> request to safely retry transient network drops.
            </p>
        </div>
        <div class="api-preview">
            <pre class="api-code" style="min-height:auto;">GET /api/telemetry.php HTTP/1.1
Host: <?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'vaulttech.io') ?>

Accept: application/json

// 200 OK
{
  "service": "vaulttech-gateway",
  "status": "operational",
  "version": "4.2.1",
  "region": "us-east-1"
}</pre>
        </div>
    </div>
</section>

<section class="section section-canvas" id="webhooks">
    <div class="container split-row">
        <div>
            <span class="kicker">Webhook Security</span>
            <h2>Verify Cryptographic Event Signatures</h2>
            <p style="color:var(--ink-soft);">
                VaultTech signs every asynchronous settlement event with an HMAC-SHA256 digest in the <code>VaultTech-Signature</code> header to prevent replay or payload tampering.
            </p>
        </div>
        <div class="api-preview">
            <pre class="api-code" style="min-height:auto;">$sig = hash_hmac('sha256', "$timestamp.$rawBody", $webhookSecret);
if (!hash_equals($expectedSig, $sig)) {
    http_response_code(400);
    exit;
}</pre>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

