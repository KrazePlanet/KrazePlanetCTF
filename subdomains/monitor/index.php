<?php
session_start();
// SSRF Lab 5 - Real-World IP Blacklist Bypass (MailFlow)
// Bug hunters find SSRF in link scanners where IP blacklists are bypassed
// using alternative representations: DNS rebinding, octal/decimal IPs, etc.

if (!isset($_SESSION['campaign_links'])) {
    $_SESSION['campaign_links'] = [
        [
            'id' => 'lnk_1',
            'url' => 'https://shop.example.com/sale',
            'title' => 'Summer Sale — Up to 50% Off',
            'status' => 'safe',
            'status_text' => 'Safe',
            'http_code' => 200,
            'response_time' => 312,
            'timestamp' => '2024-06-01 14:22:11'
        ],
        [
            'id' => 'lnk_2',
            'url' => 'https://blog.example.com/tips',
            'title' => '10 Tips for Summer Marketing',
            'status' => 'safe',
            'status_text' => 'Safe',
            'http_code' => 200,
            'response_time' => 189,
            'timestamp' => '2024-06-01 14:22:45'
        ],
        [
            'id' => 'lnk_3',
            'url' => 'http://127.0.0.1:8080/admin',
            'title' => '—',
            'status' => 'blocked',
            'status_text' => 'Blocked: Internal IP',
            'http_code' => 0,
            'response_time' => 0,
            'timestamp' => '2024-06-01 14:23:02'
        ]
    ];
}

$scanResult = null;
$error = '';
$blocked = false;

if (isset($_POST['url']) && isset($_POST['scan'])) {
    $url = $_POST['url'];
    ini_set('default_socket_timeout', 1);
    $url_sp = explode("/", $url);
    $continue = true;

    // IP Blacklist — same logic as original lab
    if (substr($url, 0, 7) == 'http://' || substr($url, 0, 8) == 'https://') {
        if (isset($url_sp[2]) && ($url_sp[2] == 'localhost' || $url_sp[2] == 'kzlabs.in' || $url_sp[2] == '142.93.35.49' || substr($url_sp[2], 0, 4) == '127.')) {
            $continue = false;
            $blocked = true;
            $error = 'Security Policy: Internal IPs, localhost, and kzlabs.in are blocked from scanning.';
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

        $title = '';
        if ($html && preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
            $title = trim(html_entity_decode($m[1]));
        }

        if ($html) {
            $scanResult = [
                'url' => $url,
                'title' => $title ?: 'No Title',
                'status' => 'safe',
                'status_text' => 'Safe',
                'http_code' => 200,
                'response_time' => $elapsed,
                'body' => $html,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } else {
            $statusText = 'Error';
            if (strstr($errorMsg, 'php_network_getaddresses')) {
                $statusText = 'Invalid Remote Host';
            } elseif (strstr($errorMsg, 'HTTP request failed!')) {
                $statusText = 'Invalid HTTP Response';
            } elseif (strstr($errorMsg, 'Connection refused')) {
                $statusText = 'Connection Refused';
            }
            $scanResult = [
                'url' => $url,
                'title' => '—',
                'status' => 'error',
                'status_text' => $statusText,
                'http_code' => 0,
                'response_time' => $elapsed,
                'body' => $errorMsg ?: 'Request failed',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }

        $_SESSION['campaign_links'][] = $scanResult;
        $_SESSION['campaign_links'] = array_slice($_SESSION['campaign_links'], -8);
    }
}

$links = array_reverse($_SESSION['campaign_links']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Campaign Editor - MailFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-orange: #f97316;
            --brand-orange-hover: #ea580c;
            --sidebar-bg: #f8fafc;
        }
        body {
            background: #f1f5f9;
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
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
            color: var(--brand-orange);
            display: flex;
            align-items: center;
            gap: 8px;
        }
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
            background: var(--brand-orange);
            color: #fff;
            border: none;
            font-weight: 600;
        }
        .btn-brand:hover { background: var(--brand-orange-hover); color: #fff; }
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
            max-height: 180px;
            overflow-y: auto;
            border: 1px solid #334155;
        }
        .link-row {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
        }
        .status-safe { color: #15803d; }
        .status-error { color: #dc2626; }
        .status-blocked { color: #92400e; }
        .form-control {
            background: #fff;
            border: 1px solid #e2e8f0;
        }
        .form-control:focus {
            border-color: var(--brand-orange);
            box-shadow: 0 0 0 2px rgba(249,115,22,0.2);
        }
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
    </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="brand"><i class="bi bi-envelope-paper"></i> MailFlow</div>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted" style="font-size:0.85rem;">Workspace: <strong>Acme Marketing</strong></span>
        <div class="rounded-circle bg-secondary" style="width:32px;height:32px;"></div>
    </div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <nav class="nav flex-column">
        <a class="nav-link" href="#"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a class="nav-link active" href="#"><i class="bi bi-envelope"></i> Campaigns</a>
        <a class="nav-link" href="#"><i class="bi bi-layout-text-window"></i> Templates</a>
        <a class="nav-link" href="#"><i class="bi bi-people"></i> Audiences</a>
        <a class="nav-link" href="#"><i class="bi bi-bar-chart-line"></i> Analytics</a>
        <a class="nav-link" href="#"><i class="bi bi-gear"></i> Settings</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="hint-banner">
        <i class="bi bi-lightbulb"></i>
        <strong>Bug Bounty Tip:</strong> MailFlow scans campaign links for safety. An IP blacklist prevents internal scanning, but <em>alternative representations</em> (octal IPs, DNS rebinding, IPv6) may bypass the filter and reach internal services.
    </div>

    <div class="row g-4">
        <!-- Campaign + Link Scanner -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-envelope"></i> Campaign: Summer Sale 2024</span>
                    <span class="badge bg-warning-subtle text-warning" style="font-size:0.78rem;">Draft</span>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.9rem;">Subject Line</label>
                        <input type="text" class="form-control" value="🌞 Summer Sale — Up to 50% Off Everything!" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.9rem;">Preview Text</label>
                        <input type="text" class="form-control" value="Don't miss our biggest sale of the year..." disabled>
                    </div>

                    <h6 class="fw-bold mt-4 mb-2" style="font-size:0.9rem;">Links in this campaign</h6>
                    <div class="card mb-3">
                        <div class="card-body p-0">
                            <?php foreach ($links as $lnk): ?>
                            <div class="link-row">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold" style="font-size:0.88rem;">
                                            <?php if ($lnk['status'] == 'safe'): ?>
                                                <i class="bi bi-check-circle-fill text-success"></i>
                                            <?php elseif ($lnk['status'] == 'blocked'): ?>
                                                <i class="bi bi-shield-fill-exclamation text-warning"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-circle-fill text-danger"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($lnk['title']); ?>
                                        </div>
                                        <div class="text-muted" style="font-size:0.78rem;">
                                            <?php echo htmlspecialchars($lnk['url']); ?> &middot; <?php echo $lnk['timestamp']; ?>
                                        </div>
                                    </div>
                                    <span class="badge <?php echo $lnk['status'] == 'safe' ? 'bg-success-subtle text-success' : ($lnk['status'] == 'blocked' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger'); ?>" style="font-size:0.72rem;">
                                        <?php echo $lnk['status_text']; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <form method="post" class="mt-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.9rem;">Add & Scan Link</label>
                            <div class="input-group">
                                <input type="text" name="url" class="form-control" placeholder="https://example.com/offer" required>
                                <button type="submit" name="scan" value="1" class="btn btn-brand">
                                    <i class="bi bi-shield-check"></i> Scan Link
                                </button>
                            </div>
                            <div class="form-text" style="font-size:0.8rem;">
                                MailFlow will fetch and scan the URL for safety before adding it to the campaign.
                                <span data-bs-toggle="tooltip" title="The backend fetches the provided URL using file_get_contents(). An IP blacklist blocks localhost, 127.x, and kzlabs.in. But alternative IP formats (octal, decimal, IPv6) or DNS rebinding may bypass it."><i class="bi bi-question-circle" style="cursor:pointer;color:var(--brand-orange);"></i></span>
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

                    <?php if ($scanResult): ?>
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold" style="font-size:0.9rem;">Scan Result</span>
                            <span class="badge <?php echo $scanResult['status'] == 'safe' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                                <?php echo $scanResult['status_text']; ?>
                            </span>
                        </div>
                        <div class="text-muted mb-2" style="font-size:0.82rem;">
                            <i class="bi bi-link-45deg"></i> <?php echo htmlspecialchars($scanResult['url']); ?><br>
                            <i class="bi bi-globe"></i> HTTP <?php echo $scanResult['http_code']; ?> &middot;
                            <i class="bi bi-clock"></i> <?php echo $scanResult['response_time']; ?>ms &middot;
                            <?php echo $scanResult['timestamp']; ?>
                        </div>
                        <?php if (!empty($scanResult['body']) && $scanResult['status'] == 'safe'): ?>
                        <div class="raw-panel">
<?php echo htmlspecialchars(mb_strimwidth($scanResult['body'], 0, 2000, '…')); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Bypass Techniques + Methodology -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-shield-lock"></i> Security Policy</div>
                <div class="card-body">
                    <div class="text-muted mb-3" style="font-size:0.85rem;">The following destinations are blocked from scanning:</div>
                    <ul class="mb-0" style="font-size:0.85rem; padding-left:1.2rem;">
                        <li><code>localhost</code></li>
                        <li><code>127.0.0.0/8</code> (any 127.x.x.x)</li>
                        <li><code>kzlabs.in</code></li>
                        <li><code>142.93.35.49</code></li>
                    </ul>
                </div>
            </div>

            <div class="card mt-4" style="border-left:4px solid var(--brand-orange);">
                <div class="card-body">
                    <h6 class="fw-bold mb-2" style="color:var(--brand-orange);"><i class="bi bi-bug"></i> Where Bug Hunters Find This</h6>
                    <ul class="mb-3" style="font-size:0.85rem; padding-left:1.2rem;">
                        <li>Email marketing link scanners (Mailchimp, SendGrid)</li>
                        <li>URL safety checkers (Google Safe Browsing)</li>
                        <li>Anti-virus URL scanners</li>
                        <li>Content moderation bots</li>
                        <li>Penetration testing report validators</li>
                    </ul>
                    <hr style="border-color:#e2e8f0;margin:12px 0;">
                    <h6 class="fw-bold mb-2" style="color:#475569;font-size:0.82rem;"><i class="bi bi-lightning-charge"></i> Common Bypass Techniques</h6>
                    <div class="bypass-card mb-2">
                        <h6>Alternative IP Formats</h6>
                        <code>http://0177.0.0.1</code> (octal)<br>
                        <code>http://2130706433</code> (decimal)<br>
                        <code>http://0x7f000001</code> (hex)<br>
                        <code>http://[::ffff:127.0.0.1]</code> (IPv6)
                    </div>
                    <div class="bypass-card mb-2">
                        <h6>DNS Rebinding</h6>
                        <code>http://attacker.com</code> resolves to <code>127.0.0.1</code> with low TTL
                    </div>
                    <div class="bypass-card">
                        <h6>Redirect Chains</h6>
                        <code>http://allowed.com</code> → 302 → <code>http://127.0.0.1</code>
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
