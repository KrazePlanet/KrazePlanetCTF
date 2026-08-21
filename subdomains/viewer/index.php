<?php
session_start();
// SSRF Lab 1 - Real-World Webhook Integration (PulsePay)
// Bug hunters commonly find SSRF in "Test Webhook" / "Verify Endpoint" features

if (!isset($_SESSION['webhooks'])) {
    $_SESSION['webhooks'] = [
        [
            'id' => 1,
            'url' => 'https://hooks.example.com/payments',
            'events' => 'payment.received, invoice.paid',
            'secret' => 'whsec_xxxxxxxxxxxxxxxx',
            'active' => true,
            'created' => '2024-01-15'
        ],
        [
            'id' => 2,
            'url' => 'https://myserver.internal:8080/events',
            'events' => 'user.created, user.deleted',
            'secret' => 'whsec_yyyyyyyyyyyyyyyy',
            'active' => false,
            'created' => '2024-02-20'
        ]
    ];
}
if (!isset($_SESSION['delivery_log'])) {
    $_SESSION['delivery_log'] = [];
}

$testResult = null;
$error = '';

// Handle "Send Test Event" — this is the SSRF injection point
if (isset($_POST['test_webhook']) && isset($_POST['webhook_url'])) {
    $url = $_POST['webhook_url'];
    $event = $_POST['event_type'] ?? 'payment.received';
    $payload = json_encode([
        'id' => 'evt_' . bin2hex(random_bytes(12)),
        'type' => $event,
        'created' => time(),
        'data' => [
            'object' => ['id' => 'obj_' . bin2hex(random_bytes(8)), 'amount' => 999]
        ]
    ], JSON_PRETTY_PRINT);

    $start = microtime(true);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: PulsePay-Webhook/1.0'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    $curlError = curl_error($ch);
    curl_close($ch);

    $testResult = [
        'url' => $url,
        'event' => $event,
        'http_code' => $httpCode,
        'response_time' => round($totalTime * 1000, 1),
        'response' => $response,
        'curl_error' => $curlError,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    $_SESSION['delivery_log'][] = $testResult;
    // Keep only last 10 entries
    $_SESSION['delivery_log'] = array_slice($_SESSION['delivery_log'], -10);
}

$webhooks = $_SESSION['webhooks'];
$deliveryLog = array_reverse($_SESSION['delivery_log']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Webhooks - PulsePay Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --brand-purple: #7c3aed;
            --brand-purple-hover: #6d28d9;
        }
        body {
            background: #f8fafc;
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .sidebar {
            width: 240px;
            background: var(--sidebar-bg);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 20px;
            z-index: 1000;
        }
        .sidebar .brand {
            color: #fff;
            font-size: 1.3rem;
            font-weight: 700;
            padding: 0 20px 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar .brand i {
            color: var(--brand-purple);
            font-size: 1.5rem;
        }
        .sidebar .nav-link {
            color: #94a3b8;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
            border-radius: 0;
            transition: all 0.15s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: var(--sidebar-hover);
            color: #fff;
        }
        .sidebar .nav-link.active {
            border-left: 3px solid var(--brand-purple);
        }
        .main-content {
            margin-left: 240px;
            padding: 24px 32px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }
        .topbar h4 {
            font-weight: 600;
            color: #1e293b;
        }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            padding: 16px 20px;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-brand {
            background: var(--brand-purple);
            color: #fff;
            border: none;
        }
        .btn-brand:hover {
            background: var(--brand-purple-hover);
            color: #fff;
        }
        .webhook-row {
            padding: 14px 20px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .webhook-row:last-child { border-bottom: none; }
        .badge-active { background: #dcfce7; color: #15803d; }
        .badge-inactive { background: #f1f5f9; color: #64748b; }
        .delivery-row {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }
        .http-2xx { color: #15803d; font-weight: 600; }
        .http-err { color: #dc2626; font-weight: 600; }
        .response-preview {
            background: #0f172a;
            color: #e2e8f0;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 0.82rem;
            border-radius: 6px;
            padding: 12px;
            max-height: 180px;
            overflow-y: auto;
            margin-top: 8px;
        }
        .pulsepay-hint {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 0.88rem;
            color: #92400e;
            margin-bottom: 20px;
        }
        .pulsepay-hint i {
            color: #f59e0b;
            margin-right: 6px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand"><i class="bi bi-lightning-charge-fill"></i> PulsePay</div>
    <nav class="nav flex-column">
        <a class="nav-link" href="#"><i class="bi bi-house-door"></i> Dashboard</a>
        <a class="nav-link" href="#"><i class="bi bi-credit-card"></i> Payments</a>
        <a class="nav-link active" href="#"><i class="bi bi-broadcast"></i> Webhooks</a>
        <a class="nav-link" href="#"><i class="bi bi-key"></i> API Keys</a>
        <a class="nav-link" href="#"><i class="bi bi-people"></i> Customers</a>
        <a class="nav-link" href="#"><i class="bi bi-gear"></i> Settings</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="topbar">
        <div>
            <h4>Webhooks</h4>
            <p class="text-muted mb-0" style="font-size:0.9rem">Manage event notifications sent to your endpoints.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted" style="font-size:0.88rem">Account: <strong>Acme Corp</strong></span>
            <div class="rounded-circle bg-secondary" style="width:36px;height:36px;"></div>
        </div>
    </div>

    <div class="pulsepay-hint">
        <i class="bi bi-lightbulb"></i>
        <strong>Bug Bounty Tip:</strong> Webhook "Test Endpoint" features are a prime SSRF target. The server makes an HTTP request to a user-supplied URL to "verify" or "test" it — exactly what happens when you click <em>Send Test Event</em> below.
    </div>

    <div class="row g-4">
        <!-- Left: Existing Webhooks -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Your Endpoints</span>
                    <button class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New Endpoint</button>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($webhooks as $wh): ?>
                    <div class="webhook-row">
                        <div>
                            <div class="fw-semibold" style="font-size:0.95rem;"><?php echo htmlspecialchars($wh['url']); ?></div>
                            <div class="text-muted" style="font-size:0.82rem;">
                                <?php echo htmlspecialchars($wh['events']); ?> &middot; Created <?php echo $wh['created']; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge <?php echo $wh['active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $wh['active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <button class="btn btn-sm btn-outline-secondary" disabled>Edit</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Delivery Log -->
            <div class="card mt-4">
                <div class="card-header"><i class="bi bi-clock-history"></i> Recent Delivery Attempts</div>
                <div class="card-body p-0">
                    <?php if (empty($deliveryLog)): ?>
                        <div class="p-4 text-center text-muted" style="font-size:0.9rem;">No test events sent yet. Use the form on the right to send one.</div>
                    <?php else: ?>
                        <?php foreach ($deliveryLog as $log): ?>
                        <div class="delivery-row">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold" style="font-size:0.9rem;"><?php echo htmlspecialchars($log['event']); ?></div>
                                    <div class="text-muted" style="font-size:0.82rem;"><?php echo htmlspecialchars($log['url']); ?></div>
                                </div>
                                <div class="text-end">
                                    <span class="<?php echo ($log['http_code'] >= 200 && $log['http_code'] < 300) ? 'http-2xx' : 'http-err'; ?>">
                                        HTTP <?php echo $log['http_code']; ?>
                                    </span>
                                    <div class="text-muted" style="font-size:0.75rem;"><?php echo $log['response_time']; ?>ms &middot; <?php echo $log['timestamp']; ?></div>
                                </div>
                            </div>
                            <?php if (!empty($log['curl_error'])): ?>
                                <div class="text-danger mt-1" style="font-size:0.82rem;"><i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($log['curl_error']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($log['response'])): ?>
                            <div class="response-preview">
<?php echo htmlspecialchars(mb_strimwidth($log['response'], 0, 1500, '…')); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Test Webhook Form -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-broadcast"></i> Send Test Event</div>
                <div class="card-body">
                    <form method="post" id="webhookForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.9rem;">Webhook Endpoint URL</label>
                            <input type="text" name="webhook_url" class="form-control" placeholder="https://your-server.com/webhook" required>
                            <div class="form-text" style="font-size:0.8rem;">
                                Must be a valid URL reachable from our servers.
                                <span data-bs-toggle="tooltip" title="The PulsePay backend will make an HTTP POST request to this URL to verify it is reachable. This is exactly where SSRF vulnerabilities are found in production."><i class="bi bi-question-circle" style="cursor:pointer;color:#7c3aed;"></i></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.9rem;">Event Type</label>
                            <select name="event_type" class="form-select">
                                <option value="payment.received">payment.received</option>
                                <option value="invoice.paid">invoice.paid</option>
                                <option value="user.created">user.created</option>
                                <option value="user.deleted">user.deleted</option>
                                <option value="subscription.canceled">subscription.canceled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.9rem;">Signing Secret (optional)</label>
                            <input type="text" class="form-control" placeholder="whsec_xxxxxxxxxxxxxxxx" disabled>
                            <div class="form-text" style="font-size:0.8rem;">Used to sign the webhook payload. Not required for testing.</div>
                        </div>
                        <input type="hidden" name="test_webhook" value="1">
                        <button type="submit" class="btn btn-brand w-100">
                            <i class="bi bi-send"></i> Send Test Event
                        </button>
                    </form>
                </div>
            </div>

            <!-- Methodology Card -->
            <div class="card mt-4" style="border-left:4px solid #7c3aed;">
                <div class="card-body">
                    <h6 class="fw-bold mb-2" style="color:#7c3aed;"><i class="bi bi-bug"></i> Where Bug Hunters Find This</h6>
                    <ul class="mb-0" style="font-size:0.85rem; padding-left:1.2rem;">
                        <li>Webhook "Test Endpoint" buttons</li>
                        <li>URL validation / URL preview features</li>
                        <li>PDF generators, screenshot tools, link expanders</li>
                        <li>API integrations that fetch user-supplied URLs</li>
                        <li>OAuth callbacks, SAML integrations</li>
                        <li>Import-from-URL features (RSS, OPML, images)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(t => new bootstrap.Tooltip(t));
</script>
</body>
</html>
