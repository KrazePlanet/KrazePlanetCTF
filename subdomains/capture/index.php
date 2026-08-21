<?php
session_start();
// SSRF Lab 2 - Real-World Link Preview Generator (LinkSnap)
// Bug hunters commonly find SSRF in URL unfurling / link preview features

if (!isset($_SESSION['previews'])) {
    $_SESSION['previews'] = [
        [
            'id' => 'prv_8a3f9c',
            'url' => 'https://stripe.com/blog',
            'title' => 'Stripe Blog',
            'description' => 'News, insights, and product updates from Stripe.',
            'domain' => 'stripe.com',
            'http_code' => 200,
            'response_time' => 245,
            'status' => 'success',
            'timestamp' => '2024-06-01 14:32:11'
        ],
        [
            'id' => 'prv_2b7e1a',
            'url' => 'https://example.com/article/123',
            'title' => 'Example Domain',
            'description' => 'This domain is for use in illustrative examples.',
            'domain' => 'example.com',
            'http_code' => 200,
            'response_time' => 89,
            'status' => 'success',
            'timestamp' => '2024-06-01 10:15:42'
        ]
    ];
}

$preview = null;
$error = '';

if (isset($_POST['url']) && isset($_POST['generate'])) {
    $url = $_POST['url'];
    $start = microtime(true);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'LinkSnap-Bot/1.0 (+https://linksnap.io/bot)');
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    $curlError = curl_error($ch);
    curl_close($ch);

    $title = '';
    $description = '';
    $domain = parse_url($url, PHP_URL_HOST) ?: $url;

    if (!$curlError && $html) {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
            $title = trim(html_entity_decode($m[1]));
        }
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)/si', $html, $m)) {
            $description = trim(html_entity_decode($m[1]));
        } elseif (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\']/si', $html, $m)) {
            $description = trim(html_entity_decode($m[1]));
        }
    }

    $preview = [
        'id' => 'prv_' . bin2hex(random_bytes(3)),
        'url' => $url,
        'title' => $title ?: ($curlError ? 'Fetch Failed' : 'No Title'),
        'description' => $description ?: 'No description available.',
        'domain' => $domain,
        'http_code' => $httpCode,
        'response_time' => round($totalTime * 1000, 1),
        'status' => ($httpCode >= 200 && $httpCode < 300 && !$curlError) ? 'success' : 'error',
        'timestamp' => date('Y-m-d H:i:s'),
        'raw' => $html,
        'curl_error' => $curlError
    ];

    $_SESSION['previews'][] = $preview;
    $_SESSION['previews'] = array_slice($_SESSION['previews'], -8);
}

$previews = array_reverse($_SESSION['previews']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compose - LinkSnap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-teal: #0d9488;
            --brand-teal-hover: #0f766e;
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
            color: var(--brand-teal);
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
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            background: #fff;
        }
        .compose-area {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            font-size: 1rem;
            resize: vertical;
            min-height: 100px;
        }
        .preview-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            margin-top: 16px;
        }
        .preview-header {
            background: #0f172a;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 0.85rem;
        }
        .preview-body {
            padding: 14px 16px;
        }
        .preview-domain {
            font-size: 0.78rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }
        .preview-title {
            font-weight: 700;
            font-size: 1rem;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .preview-desc {
            font-size: 0.88rem;
            color: #475569;
            line-height: 1.4;
        }
        .btn-brand {
            background: var(--brand-teal);
            color: #fff;
            border: none;
            font-weight: 600;
        }
        .btn-brand:hover { background: var(--brand-teal-hover); color: #fff; }
        .status-success { color: #15803d; }
        .status-error { color: #dc2626; }
        .hint-banner {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 0.88rem;
            color: #92400e;
            margin-bottom: 20px;
        }
        .raw-response {
            background: #0f172a;
            color: #e2e8f0;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 0.8rem;
            border-radius: 6px;
            padding: 12px;
            max-height: 150px;
            overflow-y: auto;
        }
        .preview-row {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
        }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="brand"><i class="bi bi-link-45deg"></i> LinkSnap</div>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted" style="font-size:0.85rem;">Team: <strong>Social Media Team</strong></span>
        <div class="rounded-circle bg-secondary" style="width:32px;height:32px;"></div>
    </div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <nav class="nav flex-column">
        <a class="nav-link" href="#"><i class="bi bi-house-door"></i> Feed</a>
        <a class="nav-link active" href="#"><i class="bi bi-pencil-square"></i> Compose</a>
        <a class="nav-link" href="#"><i class="bi bi-clock-history"></i> Scheduled</a>
        <a class="nav-link" href="#"><i class="bi bi-bar-chart-line"></i> Analytics</a>
        <a class="nav-link" href="#"><i class="bi bi-gear"></i> Settings</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="hint-banner">
        <i class="bi bi-lightbulb"></i>
        <strong>Bug Bounty Tip:</strong> Link preview / URL unfurling features fetch remote URLs to extract metadata and generate cards. This is exactly where SSRF is found in platforms like Twitter, Slack, Discord, WhatsApp, and LinkedIn.
    </div>

    <div class="row g-4">
        <!-- Compose + Preview -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square"></i> Create New Post</h5>
                    <form method="post">
                        <div class="mb-3">
                            <textarea class="form-control compose-area" placeholder="What's on your mind? Paste a link and we'll generate a preview..." rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.9rem;">Link URL</label>
                            <div class="input-group">
                                <input type="text" name="url" class="form-control" placeholder="https://example.com/blog-post" required>
                                <button type="submit" name="generate" value="1" class="btn btn-brand">
                                    <i class="bi bi-magic"></i> Generate Preview
                                </button>
                            </div>
                            <div class="form-text" style="font-size:0.8rem;">
                                LinkSnap visits this URL to extract the page title, description, and screenshot.
                                <span data-bs-toggle="tooltip" title="The LinkSnap backend makes an HTTP GET request to this URL. No host validation is performed. This is a classic SSRF attack surface."><i class="bi bi-question-circle" style="cursor:pointer;color:var(--brand-teal);"></i></span>
                            </div>
                        </div>
                    </form>

                    <?php if ($preview): ?>
                    <div class="preview-card">
                        <div class="preview-header">
                            <span><i class="bi bi-image"></i> Screenshot Preview</span>
                        </div>
                        <div class="preview-body">
                            <div class="preview-domain"><?php echo htmlspecialchars($preview['domain']); ?></div>
                            <div class="preview-title"><?php echo htmlspecialchars($preview['title']); ?></div>
                            <div class="preview-desc"><?php echo htmlspecialchars($preview['description']); ?></div>
                            <div class="mt-2 d-flex gap-3" style="font-size:0.78rem;color:#64748b;">
                                <span><i class="bi bi-globe"></i> HTTP <?php echo $preview['http_code']; ?></span>
                                <span><i class="bi bi-clock"></i> <?php echo $preview['response_time']; ?>ms</span>
                                <?php if (!empty($preview['curl_error'])): ?>
                                    <span class="text-danger"><i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($preview['curl_error']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($preview['raw'])): ?>
                    <div class="mt-3">
                        <div class="fw-semibold mb-1" style="font-size:0.85rem;color:#475569;"><i class="bi bi-file-code"></i> Raw Response</div>
                        <div class="raw-response"><?php echo htmlspecialchars(mb_strimwidth($preview['raw'], 0, 2000, '…')); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Recent Previews + Methodology -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header fw-bold" style="background:#fff;border-bottom:1px solid #e2e8f0;">
                    <i class="bi bi-clock-history"></i> Recent Previews
                </div>
                <div class="card-body p-0">
                    <?php foreach ($previews as $p): ?>
                    <div class="preview-row">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold" style="font-size:0.88rem;"><?php echo htmlspecialchars($p['title']); ?></div>
                                <div class="text-muted" style="font-size:0.78rem;"><?php echo htmlspecialchars($p['domain']); ?> &middot; <?php echo $p['timestamp']; ?></div>
                            </div>
                            <span class="badge <?php echo $p['status'] === 'success' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                                <?php echo $p['http_code']; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card mt-4" style="border-left:4px solid var(--brand-teal);">
                <div class="card-body">
                    <h6 class="fw-bold mb-2" style="color:var(--brand-teal);"><i class="bi bi-bug"></i> Where Bug Hunters Find This</h6>
                    <ul class="mb-0" style="font-size:0.85rem; padding-left:1.2rem;">
                        <li>Twitter, Slack, Discord link unfurling</li>
                        <li>WhatsApp / Telegram link previews</li>
                        <li>LinkedIn post sharing cards</li>
                        <li>URL shorteners (Bitly, TinyURL)</li>
                        <li>CMS auto-embed (WordPress, Medium)</li>
                        <li>Marketing tools (Buffer, Hootsuite)</li>
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
