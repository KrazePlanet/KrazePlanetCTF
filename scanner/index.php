<?php
session_start();
// SSRF Lab 3 - Real-World Port-based Timing Attack (PulseOps)
// Bug hunters find SSRF in health check / connectivity test features

if (!isset($_SESSION['services'])) {
    $_SESSION['services'] = [
        [
            'id' => 'svc_1',
            'name' => 'Payment Service',
            'endpoint' => 'https://payments-api.internal:8080/health',
            'status' => 'healthy',
            'last_check' => '2 min ago',
            'response_time' => 142
        ],
        [
            'id' => 'svc_2',
            'name' => 'Auth Service',
            'endpoint' => 'https://auth-service.internal:9090/status',
            'status' => 'healthy',
            'last_check' => '5 min ago',
            'response_time' => 89
        ],
        [
            'id' => 'svc_3',
            'name' => 'Legacy Database',
            'endpoint' => 'https://legacy-db.internal:10000/',
            'status' => 'degraded',
            'last_check' => '12 min ago',
            'response_time' => 4120
        ]
    ];
}
if (!isset($_SESSION['check_history'])) {
    $_SESSION['check_history'] = [];
}

$checkResult = null;

if (isset($_POST['url']) && isset($_POST['health_check'])) {
    $url = $_POST['url'];
    $start = microtime(true);

    $url_sp = explode("/", $url);
    if (isset($url_sp[2]) && substr($url_sp[2], -6, 6) == ':10000') {
        // Timing side-channel: port 10000 simulates a filtered/closed port
        sleep(4);
        $checkResult = [
            'url' => $url,
            'status_code' => 0,
            'response_time' => round((microtime(true) - $start) * 1000, 1),
            'response' => 'Connection Timeout',
            'error' => 'Connection timed out after 4000ms',
            'timing_leak' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } else {
        $errorMsg = '';
        set_error_handler(function ($e, $f) use (&$errorMsg) {
            $errorMsg = trim(explode('failed to open stream:', $f)[1] ?? $f);
        });
        $html = @file_get_contents($url);
        restore_error_handler();
        $elapsed = round((microtime(true) - $start) * 1000, 1);

        $checkResult = [
            'url' => $url,
            'status_code' => $http_response_header[0] ?? 200,
            'response_time' => $elapsed,
            'response' => $html ?: ($errorMsg ?: 'Request failed'),
            'error' => $errorMsg,
            'timing_leak' => false,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    $_SESSION['check_history'][] = $checkResult;
    $_SESSION['check_history'] = array_slice($_SESSION['check_history'], -8);
}

$services = $_SESSION['services'];
$history = array_reverse($_SESSION['check_history']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Services - PulseOps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --dark-bg: #0f172a;
            --sidebar-bg: #1e293b;
            --card-bg: #1e293b;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --green: #22c55e;
            --yellow: #eab308;
            --red: #ef4444;
            --brand: #6366f1;
        }
        body {
            background: #0b1121;
            min-height: 100vh;
            color: var(--text);
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .topbar {
            background: var(--dark-bg);
            border-bottom: 1px solid #334155;
            padding: 12px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .brand {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--brand);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sidebar {
            width: 220px;
            min-height: calc(100vh - 60px);
            background: var(--sidebar-bg);
            border-right: 1px solid #334155;
            padding: 16px 0;
            position: fixed;
            left: 0;
        }
        .sidebar .nav-link {
            color: var(--muted);
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.92rem;
            border-radius: 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #334155;
            color: #fff;
            font-weight: 600;
        }
        .main-content {
            margin-left: 220px;
            padding: 28px 32px;
        }
        .card {
            background: var(--card-bg);
            border: 1px solid #334155;
            border-radius: 10px;
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid #334155;
            color: #fff;
            font-weight: 600;
            padding: 16px 20px;
        }
        .service-row {
            padding: 14px 20px;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .service-row:last-child { border-bottom: none; }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .status-healthy { background: var(--green); box-shadow: 0 0 6px var(--green); }
        .status-degraded { background: var(--yellow); box-shadow: 0 0 6px var(--yellow); }
        .status-down { background: var(--red); box-shadow: 0 0 6px var(--red); }
        .rt-badge {
            font-size: 0.78rem;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        .rt-green { background: rgba(34,197,94,0.15); color: var(--green); }
        .rt-yellow { background: rgba(234,179,8,0.15); color: var(--yellow); }
        .rt-red { background: rgba(239,68,68,0.15); color: var(--red); }
        .btn-brand {
            background: var(--brand);
            color: #fff;
            border: none;
            font-weight: 600;
        }
        .btn-brand:hover { background: #4f46e5; color: #fff; }
        .hint-banner {
            background: rgba(234,179,8,0.1);
            border: 1px solid rgba(234,179,8,0.3);
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 0.88rem;
            color: #fbbf24;
            margin-bottom: 20px;
        }
        .raw-panel {
            background: #0b1121;
            color: #e2e8f0;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 0.82rem;
            border-radius: 6px;
            padding: 12px;
            max-height: 180px;
            overflow-y: auto;
            border: 1px solid #334155;
        }
        .history-row {
            padding: 12px 16px;
            border-bottom: 1px solid #334155;
            font-size: 0.88rem;
        }
        .form-control, .form-select {
            background: #1e293b;
            border: 1px solid #334155;
            color: #e2e8f0;
        }
        .form-control:focus, .form-select:focus {
            background: #1e293b;
            border-color: var(--brand);
            color: #e2e8f0;
            box-shadow: 0 0 0 2px rgba(99,102,241,0.2);
        }
        .form-text { color: var(--muted); font-size: 0.8rem; }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="brand"><i class="bi bi-activity"></i> PulseOps</div>
    <div class="d-flex align-items-center gap-3">
        <span style="font-size:0.85rem;color:var(--muted);">Cluster: <strong style="color:#fff;">prod-us-east-1</strong></span>
        <span class="badge bg-danger" style="font-size:0.75rem;">3 Alerts</span>
        <div class="rounded-circle bg-secondary" style="width:32px;height:32px;"></div>
    </div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <nav class="nav flex-column">
        <a class="nav-link" href="#"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a class="nav-link active" href="#"><i class="bi bi-hdd-rack"></i> Services</a>
        <a class="nav-link" href="#"><i class="bi bi-bell"></i> Alerts</a>
        <a class="nav-link" href="#"><i class="bi bi-journal-text"></i> Logs</a>
        <a class="nav-link" href="#"><i class="bi bi-gear"></i> Settings</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="hint-banner">
        <i class="bi bi-lightbulb"></i>
        <strong>Bug Bounty Tip:</strong> PulseOps tests backend connectivity. Response times can leak internal network topology — a <em>timing side-channel</em> that reveals whether a port is open, closed, or filtered.
    </div>

    <div class="row g-4">
        <!-- Monitored Services -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-hdd-rack"></i> Monitored Services</span>
                    <span class="badge bg-success-subtle text-success" style="font-size:0.78rem;">2 Healthy / 1 Degraded</span>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($services as $svc): ?>
                    <div class="service-row">
                        <div>
                            <div class="fw-semibold" style="font-size:0.95rem;color:#fff;">
                                <span class="status-dot status-<?php echo $svc['status']; ?>"></span>
                                <?php echo htmlspecialchars($svc['name']); ?>
                            </div>
                            <div class="text-muted" style="font-size:0.82rem;">
                                <?php echo htmlspecialchars($svc['endpoint']); ?> &middot; <?php echo $svc['last_check']; ?>
                            </div>
                        </div>
                        <span class="rt-badge rt-<?php echo $svc['response_time'] > 2000 ? 'red' : ($svc['response_time'] > 500 ? 'yellow' : 'green'); ?>">
                            <?php echo $svc['response_time']; ?>ms
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Health Check Form -->
            <div class="card mt-4">
                <div class="card-header"><i class="bi bi-lightning-charge"></i> Run Health Check</div>
                <div class="card-body p-4">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.9rem;">Service Endpoint URL</label>
                            <div class="input-group">
                                <input type="text" name="url" class="form-control" placeholder="https://service.internal:8080/health" required>
                                <button type="submit" name="health_check" value="1" class="btn btn-brand">
                                    <i class="bi bi-play-fill"></i> Check Now
                                </button>
                            </div>
                            <div class="form-text">
                                PulseOps will probe this endpoint from our cluster.
                                <span data-bs-toggle="tooltip" title="The backend makes an HTTP GET request to the provided URL with no host validation. Response time differences reveal whether a port is open, closed, or filtered — a classic SSRF timing attack surface."><i class="bi bi-question-circle" style="cursor:pointer;color:var(--brand);"></i></span>
                            </div>
                        </div>
                    </form>

                    <?php if ($checkResult): ?>
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold" style="font-size:0.9rem;color:#fff;">Result</span>
                            <span class="rt-badge rt-<?php echo $checkResult['response_time'] > 2000 ? 'red' : ($checkResult['response_time'] > 500 ? 'yellow' : 'green'); ?>">
                                <?php echo $checkResult['response_time']; ?>ms
                            </span>
                        </div>
                        <div class="text-muted mb-2" style="font-size:0.82rem;">
                            <i class="bi bi-globe"></i> <?php echo htmlspecialchars($checkResult['url']); ?> &middot;
                            <i class="bi bi-clock"></i> <?php echo $checkResult['timestamp']; ?>
                            <?php if ($checkResult['timing_leak']): ?>
                                <span class="badge bg-warning text-dark ms-2" style="font-size:0.72rem;">TIMING LEAK DETECTED</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($checkResult['error'])): ?>
                            <div class="alert alert-danger py-2" style="font-size:0.85rem;">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($checkResult['error']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="raw-panel">
<?php echo htmlspecialchars(mb_strimwidth($checkResult['response'], 0, 2000, '…')); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Check History + Methodology -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-clock-history"></i> Recent Checks</div>
                <div class="card-body p-0">
                    <?php if (empty($history)): ?>
                        <div class="p-4 text-center text-muted" style="font-size:0.9rem;">No health checks run yet.</div>
                    <?php else: ?>
                        <?php foreach ($history as $h): ?>
                        <div class="history-row">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold" style="font-size:0.88rem;color:#fff;">
                                        <?php echo htmlspecialchars(parse_url($h['url'], PHP_URL_HOST) ?: $h['url']); ?>
                                    </div>
                                    <div class="text-muted" style="font-size:0.78rem;"><?php echo $h['timestamp']; ?></div>
                                </div>
                                <span class="rt-badge rt-<?php echo $h['response_time'] > 2000 ? 'red' : ($h['response_time'] > 500 ? 'yellow' : 'green'); ?>">
                                    <?php echo $h['response_time']; ?>ms
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-4" style="border-left:4px solid var(--brand);">
                <div class="card-body">
                    <h6 class="fw-bold mb-2" style="color:var(--brand);"><i class="bi bi-bug"></i> Where Bug Hunters Find This</h6>
                    <ul class="mb-0" style="font-size:0.85rem; padding-left:1.2rem;color:var(--muted);">
                        <li>Kubernetes / Docker health check endpoints</li>
                        <li>API gateway connectivity tests</li>
                        <li>CDN origin server testers</li>
                        <li>Microservice mesh discovery tools</li>
                        <li>VPN / firewall port testers</li>
                        <li>Cloud load balancer health probes</li>
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
