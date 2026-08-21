<?php
session_start();
// SSRF Lab 4 - Real-World Domain Restriction Bypass with Redirects (SecureAuth)
// Bug hunters find SSRF in "Test Callback" features where domain is validated
// on the initial URL but redirects are followed without validation.

if (!isset($_SESSION['sso_tests'])) {
    $_SESSION['sso_tests'] = [
        [
            'url' => 'https://app1.secureauth.io/saml/callback',
            'final_url' => 'https://app1.secureauth.io/saml/callback',
            'status' => 200,
            'timestamp' => '2024-06-01 09:14:22',
            'bypassed' => false
        ],
        [
            'url' => 'https://partner.secureauth.io/auth/redirect',
            'final_url' => 'https://partner.secureauth.io/auth/redirect',
            'status' => 200,
            'timestamp' => '2024-06-01 08:55:01',
            'bypassed' => false
        ]
    ];
}

$testResult = null;
$error = '';

if (isset($_POST['url']) && isset($_POST['test_callback'])) {
    $url = $_POST['url'];
    $allowedDomain = 'secureauth.io';

    // Domain validation on the INITIAL URL only
    $url_sp = explode("/", $url);
    if (isset($url_sp[2]) && ($url_sp[2] == $allowedDomain || substr($url_sp[2], -(strlen($allowedDomain) + 1)) == '.' . $allowedDomain)) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Follow redirects!
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Strip headers from body for display
        $body = '';
        if ($response && ($headerEnd = strpos($response, "\r\n\r\n")) !== false) {
            $body = substr($response, $headerEnd + 4);
        } elseif ($response) {
            $body = $response;
        }

        $testResult = [
            'url' => $url,
            'final_url' => $finalUrl,
            'status' => $httpCode,
            'body' => $body,
            'curl_error' => $curlError,
            'timestamp' => date('Y-m-d H:i:s'),
            'bypassed' => ($finalUrl !== $url)
        ];

        $_SESSION['sso_tests'][] = $testResult;
        $_SESSION['sso_tests'] = array_slice($_SESSION['sso_tests'], -8);
    } else {
        $error = 'Only URLs from the <strong>secureauth.io</strong> domain are permitted for callback testing.';
    }
}

$tests = array_reverse($_SESSION['sso_tests']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Applications - SecureAuth</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-indigo: #4f46e5;
            --brand-indigo-hover: #4338ca;
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
            color: var(--brand-indigo);
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
            background: var(--brand-indigo);
            color: #fff;
            border: none;
            font-weight: 600;
        }
        .btn-brand:hover { background: var(--brand-indigo-hover); color: #fff; }
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
        .test-row {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
        }
        .bypass-badge {
            background: #fee2e2;
            color: #b91c1c;
            font-size: 0.72rem;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="brand"><i class="bi bi-shield-lock"></i> SecureAuth</div>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted" style="font-size:0.85rem;">Tenant: <strong>acme-corp</strong></span>
        <span class="badge bg-success" style="font-size:0.75rem;">Production</span>
        <div class="rounded-circle bg-secondary" style="width:32px;height:32px;"></div>
    </div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <nav class="nav flex-column">
        <a class="nav-link" href="#"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a class="nav-link active" href="#"><i class="bi bi-window"></i> Applications</a>
        <a class="nav-link" href="#"><i class="bi bi-shield-check"></i> Identity Providers</a>
        <a class="nav-link" href="#"><i class="bi bi-people"></i> Users</a>
        <a class="nav-link" href="#"><i class="bi bi-gear"></i> Settings</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="hint-banner">
        <i class="bi bi-lightbulb"></i>
        <strong>Bug Bounty Tip:</strong> SecureAuth tests callback URLs before saving SSO configurations. Domain restrictions are validated on the <em>initial URL</em>, but redirects are followed during the test — a classic redirect-based SSRF bypass.
    </div>

    <div class="row g-4">
        <!-- Application Config -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-window"></i> Application: CRM Portal</span>
                    <span class="badge bg-success-subtle text-success" style="font-size:0.78rem;">SAML 2.0 &middot; Active</span>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.9rem;">Application Name</label>
                        <input type="text" class="form-control" value="CRM Portal" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.9rem;">SSO Protocol</label>
                        <input type="text" class="form-control" value="SAML 2.0" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.9rem;">Single Sign-On URL</label>
                        <input type="text" class="form-control" value="https://crm.acme-corp.com/saml/sso" disabled>
                    </div>

                    <form method="post" class="mt-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.9rem;">Callback URL <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="url" class="form-control" placeholder="https://app.secureauth.io/callback" required>
                                <button type="submit" name="test_callback" value="1" class="btn btn-brand">
                                    <i class="bi bi-play-fill"></i> Test Callback
                                </button>
                            </div>
                            <div class="form-text" style="font-size:0.8rem;">
                                Only URLs from the <strong>secureauth.io</strong> domain are permitted.
                                <span data-bs-toggle="tooltip" title="The backend validates the initial domain, then follows HTTP 301/302 redirects. If an allowed domain redirects to an internal service, the restriction is bypassed."><i class="bi bi-question-circle" style="cursor:pointer;color:var(--brand-indigo);"></i></span>
                            </div>
                        </div>
                    </form>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 mt-3" style="font-size:0.88rem;">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($testResult): ?>
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold" style="font-size:0.9rem;">Test Result</span>
                            <?php if ($testResult['bypassed']): ?>
                                <span class="bypass-badge"><i class="bi bi-arrow-left-right"></i> REDIRECT DETECTED</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted mb-2" style="font-size:0.82rem;">
                            <i class="bi bi-link-45deg"></i> Initial: <?php echo htmlspecialchars($testResult['url']); ?><br>
                            <i class="bi bi-box-arrow-in-right"></i> Final: <strong><?php echo htmlspecialchars($testResult['final_url']); ?></strong><br>
                            <i class="bi bi-globe"></i> HTTP <?php echo $testResult['status']; ?> &middot; <?php echo $testResult['timestamp']; ?>
                        </div>
                        <?php if (!empty($testResult['curl_error'])): ?>
                            <div class="alert alert-danger py-2" style="font-size:0.85rem;">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($testResult['curl_error']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="raw-panel">
<?php echo htmlspecialchars(mb_strimwidth($testResult['body'], 0, 2000, '…')); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Test History + Methodology -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-clock-history"></i> Recent Callback Tests</div>
                <div class="card-body p-0">
                    <?php foreach ($tests as $t): ?>
                    <div class="test-row">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold" style="font-size:0.88rem;"><?php echo htmlspecialchars(parse_url($t['url'], PHP_URL_HOST) ?: $t['url']); ?></div>
                                <div class="text-muted" style="font-size:0.78rem;">
                                    <?php echo $t['timestamp']; ?>
                                    <?php if (!empty($t['bypassed']) && $t['bypassed']): ?>
                                        <span class="bypass-badge ms-1">Redirect</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge <?php echo ($t['status'] >= 200 && $t['status'] < 300) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                                HTTP <?php echo $t['status']; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card mt-4" style="border-left:4px solid var(--brand-indigo);">
                <div class="card-body">
                    <h6 class="fw-bold mb-2" style="color:var(--brand-indigo);"><i class="bi bi-bug"></i> Where Bug Hunters Find This</h6>
                    <ul class="mb-0" style="font-size:0.85rem; padding-left:1.2rem;">
                        <li>SSO/SAML callback URL validators</li>
                        <li>OAuth redirect_uri testers</li>
                        <li>Webhook domain allowlists with redirect following</li>
                        <li>URL shortener resolution</li>
                        <li>CDN origin validation with redirect support</li>
                        <li>Open Graph / oEmbed fetchers with redirect chains</li>
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
