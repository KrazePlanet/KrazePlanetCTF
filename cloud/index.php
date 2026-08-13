<?php
session_start();
// SSRF Lab 6 - Real-World Cloud Metadata Filter Bypass (CloudLens)
// Bug hunters find SSRF in cloud tools where metadata service IPs are blocked
// but alternative representations (decimal, hex, octal) bypass the filter.

if (!isset($_SESSION['instances'])) {
    $_SESSION['instances'] = [
        [
            'id' => 'i-0a1b2c3d4e5f',
            'type' => 't3.medium',
            'cost' => '$42.00/mo',
            'region' => 'us-east-1a',
            'status' => 'running'
        ],
        [
            'id' => 'i-0f9e8d7c6b5a',
            'type' => 'm5.large',
            'cost' => '$87.84/mo',
            'region' => 'us-east-1b',
            'status' => 'running'
        ],
        [
            'id' => 'i-0x1y2z3w4v5u',
            'type' => 'c5.xlarge',
            'cost' => '$140.16/mo',
            'region' => 'us-east-1c',
            'status' => 'running'
        ]
    ];
}
if (!isset($_SESSION['metadata_fetches'])) {
    $_SESSION['metadata_fetches'] = [];
}

$fetchResult = null;
$error = '';
$blocked = false;

if (isset($_POST['url']) && isset($_POST['fetch_metadata'])) {
    $url = $_POST['url'];
    ini_set('default_socket_timeout', 2);
    $url_sp = explode("/", $url);
    $continue = true;

    // Metadata service blacklist — same logic as original lab
    if (substr($url, 0, 7) == 'http://' || substr($url, 0, 8) == 'https://') {
        if (isset($url_sp[2]) && ($url_sp[2] == 'localhost' || $url_sp[2] == 'kzlabs.in' || $url_sp[2] == '169.254.169.254' || $url_sp[2] == '142.93.35.49' || substr($url_sp[2], 0, 4) == '127.')) {
            $continue = false;
            $blocked = true;
            $error = 'Security Policy: Cloud metadata endpoints, localhost, and internal IPs are blocked.';
        }
    } else {
        $continue = false;
        $error = 'URL must start with http:// or https://';
    }

    if ($continue) {
        $start = microtime(true);
        $errorMsg = '';
        set_error_handler(function ($e, $f) use (&$errorMsg) {
            $errorMsg = trim(explode('failed to open stream:', $f)[1] ?? $f);
        });

        $html = @file_get_contents($url);
        restore_error_handler();
        $elapsed = round((microtime(true) - $start) * 1000, 1);

        if ($html) {
            $fetchResult = [
                'url' => $url,
                'status' => 'success',
                'status_text' => 'Fetch Successful',
                'http_code' => 200,
                'response_time' => $elapsed,
                'body' => $html,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } else {
            $statusText = 'Fetch Failed';
            if (strstr($errorMsg, 'php_network_getaddresses')) {
                $statusText = 'Invalid Remote Host';
            } elseif (strstr($errorMsg, 'HTTP request failed!')) {
                $statusText = 'Invalid HTTP Response';
            } elseif (strstr($errorMsg, 'Connection refused')) {
                $statusText = 'Connection Refused';
            }
            $fetchResult = [
                'url' => $url,
                'status' => 'error',
                'status_text' => $statusText,
                'http_code' => 0,
                'response_time' => $elapsed,
                'body' => $errorMsg ?: 'Request failed',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }

        $_SESSION['metadata_fetches'][] = $fetchResult;
        $_SESSION['metadata_fetches'] = array_slice($_SESSION['metadata_fetches'], -8);
    }
}

$fetches = array_reverse($_SESSION['metadata_fetches']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instances - CloudLens</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-blue: #0077cc;
            --brand-blue-hover: #005fa3;
            --sidebar-bg: #f8fafc;
        }
        body {
            background: #f1f5f9;
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .topbar {
            background: #232f3e;
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
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .brand i { color: var(--brand-blue); }
        .sidebar {
            width: 220px;
            min-height: calc(100vh - 60px);
            background: var(--sidebar-bg);
            border-right: 1px solid #e2e8f0;
            padding: 16px 0;
            position: fixed;
            left: 0;
        }
        .sidebar .nav-link {
            color: #475569;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.92rem;
            border-radius: 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #e2e8f0;
            color: #0f172a;
            font-weight: 600;
        }
        .main-content {
            margin-left: 220px;
            padding: 28px 32px;
        }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            background: #fff;
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            padding: 16px 20px;
        }
        .btn-brand {
            background: var(--brand-blue);
            color: #fff;
            border: none;
            font-weight: 600;
        }
        .btn-brand:hover { background: var(--brand-blue-hover); color: #fff; }
        .hint-banner {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 0.88rem;
            color: #92400e;
            margin-bottom: 20px;
        }
        .raw-panel {
            background: #0f172a;
            color: #e2e8f0;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 0.82rem;
            border-radius: 6px;
            padding: 12px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #334155;
        }
        .instance-row {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
        }
        .fetch-row {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
        }
        .status-running { color: #15803d; }
        .status-stopped { color: #dc2626; }
        .bypass-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            font-size: 0.82rem;
        }
        .bypass-card h6 {
            font-size: 0.8rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 6px;
        }
        .bypass-card code {
            background: #e2e8f0;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 0.78rem;
            color: #b91c1c;
        }
        .topbar-right {
            color: #a0a0a0;
            font-size: 0.85rem;
        }
        .topbar-right strong { color: #fff; }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="brand"><i class="bi bi-cloud"></i> CloudLens</div>
    <div class="d-flex align-items-center gap-3 topbar-right">
        <span>Account: <strong>Acme-Corp-Prod</strong></span>
        <span>Region: <strong>us-east-1</strong></span>
        <div class="rounded-circle bg-secondary" style="width:32px;height:32px;"></div>
    </div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <nav class="nav flex-column">
        <a class="nav-link" href="#"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a class="nav-link active" href="#"><i class="bi bi-hdd-rack"></i> Instances</a>
        <a class="nav-link" href="#"><i class="bi bi-currency-dollar"></i> Cost Analysis</a>
        <a class="nav-link" href="#"><i class="bi bi-shield-check"></i> Security</a>
        <a class="nav-link" href="#"><i class="bi bi-gear"></i> Settings</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="hint-banner">
        <i class="bi bi-lightbulb"></i>
        <strong>Bug Bounty Tip:</strong> CloudLens fetches instance metadata for analysis. A metadata service filter prevents credential theft, but <em>alternative IP representations</em> (decimal, hex, octal) may bypass the filter and reach cloud metadata endpoints.
    </div>

    <div class="row g-4">
        <!-- Instances + Metadata Fetcher -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-hdd-rack"></i> Running Instances</span>
                    <span class="badge bg-success-subtle text-success" style="font-size:0.78rem;">3 Active</span>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($_SESSION['instances'] as $inst): ?>
                    <div class="instance-row">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold" style="font-size:0.9rem;">
                                    <i class="bi bi-circle-fill status-running" style="font-size:0.5rem;vertical-align:middle;"></i>
                                    <?php echo htmlspecialchars($inst['id']); ?> — <?php echo htmlspecialchars($inst['type']); ?>
                                </div>
                                <div class="text-muted" style="font-size:0.78rem;">
                                    <?php echo htmlspecialchars($inst['region']); ?> &middot; <?php echo $inst['cost']; ?>/mo
                                </div>
                            </div>
                            <span class="badge bg-success-subtle text-success" style="font-size:0.72rem;">Running</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header"><i class="bi bi-cloud-download"></i> Fetch Instance Metadata</div>
                <div class="card-body p-4">
                    <div class="alert alert-info py-2" style="font-size:0.85rem;">
                        <i class="bi bi-info-circle"></i> Enter a metadata URL to fetch instance tags, IAM roles, or user-data for analysis.
                    </div>

                    <form method="post" class="mt-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.9rem;">Metadata URL</label>
                            <div class="input-group">
                                <input type="text" name="url" class="form-control" placeholder="http://169.254.169.254/latest/meta-data/" required>
                                <button type="submit" name="fetch_metadata" value="1" class="btn btn-brand">
                                    <i class="bi bi-play-fill"></i> Fetch
                                </button>
                            </div>
                            <div class="form-text" style="font-size:0.8rem;">
                                CloudLens will fetch the metadata endpoint for analysis.
                                <span data-bs-toggle="tooltip" title="The backend fetches the provided URL using file_get_contents(). A filter blocks the metadata IP (169.254.169.254), localhost, and internal IPs. But decimal (2852039166), hex (0xa9fea9fe), or octal representations may bypass it."><i class="bi bi-question-circle" style="cursor:pointer;color:var(--brand-blue);"></i></span>
                            </div>
                        </div>
                    </form>

                    <?php if ($blocked): ?>
                        <div class="alert alert-warning py-2 mt-3" style="font-size:0.88rem;">
                            <i class="bi bi-shield-fill-exclamation"></i> <?php echo $error; ?>
                        </div>
                    <?php elseif ($error && !$blocked): ?>
                        <div class="alert alert-danger py-2 mt-3" style="font-size:0.88rem;">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($fetchResult): ?>
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold" style="font-size:0.9rem;">Fetch Result</span>
                            <span class="badge <?php echo $fetchResult['status'] == 'success' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                                <?php echo $fetchResult['status_text']; ?>
                            </span>
                        </div>
                        <div class="text-muted mb-2" style="font-size:0.82rem;">
                            <i class="bi bi-link-45deg"></i> <?php echo htmlspecialchars($fetchResult['url']); ?><br>
                            <i class="bi bi-globe"></i> HTTP <?php echo $fetchResult['http_code']; ?> &middot;
                            <i class="bi bi-clock"></i> <?php echo $fetchResult['response_time']; ?>ms &middot;
                            <?php echo $fetchResult['timestamp']; ?>
                        </div>
                        <?php if (!empty($fetchResult['body'])): ?>
                        <div class="raw-panel">
<?php echo htmlspecialchars(mb_strimwidth($fetchResult['body'], 0, 2000, '…')); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Security Policy + Bypass Techniques -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-shield-lock"></i> Metadata Security Policy</div>
                <div class="card-body">
                    <div class="text-muted mb-3" style="font-size:0.85rem;">The following destinations are blocked from fetching:</div>
                    <ul class="mb-0" style="font-size:0.85rem; padding-left:1.2rem;">
                        <li><code>localhost</code></li>
                        <li><code>127.0.0.0/8</code> (any 127.x.x.x)</li>
                        <li><code>169.254.169.254</code> (AWS/Azure/GCP metadata)</li>
                        <li><code>kzlabs.in</code></li>
                        <li><code>142.93.35.49</code></li>
                    </ul>
                </div>
            </div>

            <div class="card mt-4" style="border-left:4px solid var(--brand-blue);">
                <div class="card-body">
                    <h6 class="fw-bold mb-2" style="color:var(--brand-blue);"><i class="bi bi-bug"></i> Where Bug Hunters Find This</h6>
                    <ul class="mb-3" style="font-size:0.85rem; padding-left:1.2rem;">
                        <li>Cloud cost optimizers and instance inspectors</li>
                        <li>Serverless function testing consoles</li>
                        <li>Container build pipelines fetching external resources</li>
                        <li>CI/CD runners with network access</li>
                        <li>Web application firewalls with misconfigured bypass rules</li>
                        <li>Cloud migration tools</li>
                    </ul>
                    <hr style="border-color:#e2e8f0;margin:12px 0;">
                    <h6 class="fw-bold mb-2" style="color:#475569;font-size:0.82rem;"><i class="bi bi-lightning-charge"></i> Common Bypass Techniques</h6>
                    <div class="bypass-card mb-2">
                        <h6>Decimal IP</h6>
                        <code>http://2852039166</code> = <code>169.254.169.254</code>
                    </div>
                    <div class="bypass-card mb-2">
                        <h6>Hexadecimal IP</h6>
                        <code>http://0xa9fea9fe</code> = <code>169.254.169.254</code>
                    </div>
                    <div class="bypass-card mb-2">
                        <h6>Octal IP</h6>
                        <code>http://0251.0376.0251.0376</code> = <code>169.254.169.254</code>
                    </div>
                    <div class="bypass-card">
                        <h6>IPv6 Mapped</h6>
                        <code>http://[::ffff:169.254.169.254]</code>
                    </div>
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
