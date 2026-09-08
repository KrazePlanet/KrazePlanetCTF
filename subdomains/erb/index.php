<?php
session_start();

$default_headline = 'Serverless Edge Compute Powered by <%= brand.name %>';
$default_tagline = 'Deploy micro-services in milliseconds across <%= metrics.global_regions %> with zero operational overhead.';
$default_layout = '<div class="hero-campaign-pill mb-3">
    <span class="badge bg-danger me-2">LIMITED LAUNCH</span>
    <span>Use code <strong><%= campaign.code %></strong> for <strong><%= campaign.discount %></strong>! Expires <%= campaign.expiry %>.</span>
</div>

<div class="hero-cta-group d-flex gap-3 justify-content-center my-4">
    <a href="#" class="btn btn-primary-ruby btn-lg px-4 py-2 fw-bold">
        <%= campaign.cta_text %> &rarr;
    </a>
    <a href="#" class="btn btn-outline-light btn-lg px-4 py-2">
        <i class="bi bi-play-circle me-1"></i> Watch 2-Min Demo
    </a>
</div>

<div class="hero-trust-metrics d-flex justify-content-center gap-4 text-secondary small mt-4 pt-3 border-top border-secondary border-opacity-25">
    <div><i class="bi bi-cpu text-danger me-1"></i> <strong><%= metrics.active_deployments %></strong> Deployments</div>
    <div><i class="bi bi-globe-americas text-danger me-1"></i> <strong><%= metrics.global_regions %></strong></div>
    <div><i class="bi bi-shield-check text-success me-1"></i> <strong><%= metrics.uptime %></strong> SLA Guaranteed</div>
</div>';

$headline_input = $_POST['headline'] ?? $default_headline;
$tagline_input = $_POST['tagline'] ?? $default_tagline;
$layout_input = $_POST['layout_body'] ?? $default_layout;

$rendered_headline = '';
$rendered_tagline = '';
$rendered_layout = '';
$render_time = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['preview'])) {
    $start = microtime(true);
    
    // Evaluate Headline with Ruby ERB
    $b64_h = base64_encode($headline_input);
    $cmd_h = sprintf('ruby %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.rb'), escapeshellarg($b64_h));
    $rendered_headline = shell_exec($cmd_h);

    // Evaluate Tagline with Ruby ERB
    $b64_t = base64_encode($tagline_input);
    $cmd_t = sprintf('ruby %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.rb'), escapeshellarg($b64_t));
    $rendered_tagline = shell_exec($cmd_t);

    // Evaluate Layout Body with Ruby ERB
    $b64_l = base64_encode($layout_input);
    $cmd_l = sprintf('ruby %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.rb'), escapeshellarg($b64_l));
    $rendered_layout = shell_exec($cmd_l);
    
    $render_time = round((microtime(true) - $start) * 1000, 2);
} else {
    $b64_h = base64_encode($default_headline);
    $rendered_headline = shell_exec(sprintf('ruby %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.rb'), escapeshellarg($b64_h)));

    $b64_t = base64_encode($default_tagline);
    $rendered_tagline = shell_exec(sprintf('ruby %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.rb'), escapeshellarg($b64_t)));

    $b64_l = base64_encode($default_layout);
    $rendered_layout = shell_exec(sprintf('ruby %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.rb'), escapeshellarg($b64_l)));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PageCraft CMS — Modular SaaS Landing Page & Hero Studio (Ruby ERB)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #e11d48;
            --primary-light: #fb7185;
            --primary-dark: #be123c;
            --sidebar-bg: #100812;
            --card-border: #2e1628;
            --app-bg: #090409;
            --surface-bg: #150b18;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--app-bg);
            color: #f1f5f9;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .app-sidebar {
            width: 270px;
            background: var(--sidebar-bg);
            color: #94a3b8;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid var(--card-border);
        }

        .sidebar-brand {
            padding: 20px 24px;
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--card-border);
        }

        .sidebar-brand i {
            color: var(--primary-light);
            font-size: 22px;
        }

        .sidebar-menu {
            padding: 20px 14px;
            flex-grow: 1;
            overflow-y: auto;
        }

        .nav-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            padding: 10px 12px 6px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
            transition: all 0.15s;
        }

        .sidebar-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar-link.active {
            color: #ffffff;
            background: rgba(225, 29, 72, 0.18);
            border: 1px solid rgba(225, 29, 72, 0.35);
            font-weight: 600;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 13px;
        }

        .app-workspace {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #090409;
        }

        .app-topbar {
            height: 64px;
            background: #100812;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            flex-shrink: 0;
        }

        .topbar-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-title h1 {
            font-size: 17px;
            font-weight: 700;
            margin: 0;
            color: #ffffff;
        }

        .studio-body {
            flex-grow: 1;
            display: flex;
            overflow: hidden;
            padding: 20px;
            gap: 20px;
        }

        .editor-panel {
            flex: 1.1;
            background: var(--surface-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }

        .preview-panel {
            flex: 1.2;
            background: var(--surface-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }

        .panel-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0d060e;
            flex-shrink: 0;
        }

        .panel-header h2 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .code-textarea {
            width: 100%;
            border: 1px solid var(--card-border);
            border-radius: 8px;
            outline: none;
            padding: 14px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.6;
            color: #e2e8f0;
            background: #090409;
            resize: vertical;
            min-height: 220px;
        }

        .code-textarea:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 2px rgba(225, 29, 72, 0.25);
        }

        .preview-viewport {
            flex-grow: 1;
            background: #090409;
            overflow-y: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Landing Page Rendered Styles */
        .landing-mockup {
            background: #110915;
            border: 1px solid #2d1629;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .landing-nav {
            background: #150b1a;
            border-bottom: 1px solid #281224;
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .landing-hero {
            padding: 48px 32px;
            text-align: center;
            background: radial-gradient(circle at 50% 20%, rgba(225, 29, 72, 0.15) 0%, rgba(17, 9, 21, 0) 70%);
        }

        .hero-campaign-pill {
            display: inline-flex;
            align-items: center;
            background: rgba(225, 29, 72, 0.12);
            border: 1px solid rgba(225, 29, 72, 0.3);
            border-radius: 30px;
            padding: 6px 16px;
            font-size: 13px;
            color: #fda4af;
        }

        .btn-primary-ruby {
            background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
            border: none;
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.4);
            transition: all 0.2s;
        }

        .btn-primary-ruby:hover {
            box-shadow: 0 6px 20px rgba(225, 29, 72, 0.6);
            transform: translateY(-1px);
        }

        .feature-card {
            background: #180c1d;
            border: 1px solid #2b1427;
            border-radius: 10px;
            padding: 18px;
            text-align: left;
        }

        .tag-pill {
            background: #180c1d;
            color: #fda4af;
            border: 1px solid #2b1427;
            font-size: 11px;
            font-family: 'JetBrains Mono', monospace;
            padding: 2px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .tag-pill:hover {
            background: var(--primary);
            color: #ffffff;
        }
    </style>
</head>
<body>

    <!-- Left App Sidebar -->
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-window-stack"></i> PageCraft CMS
        </div>

        <div class="sidebar-menu">
            <div class="nav-section-title">Site Builder</div>
            <a href="index.php" class="sidebar-link active">
                <i class="bi bi-layout-text-window-reverse"></i> Landing Studio
            </a>
            <a href="landing.php" class="sidebar-link">
                <i class="bi bi-browser-chrome"></i> Live Public Page
            </a>
            <a href="modules.php" class="sidebar-link">
                <i class="bi bi-boxes"></i> Modular Page Blocks
            </a>
            <a href="campaigns.php" class="sidebar-link">
                <i class="bi bi-tag-fill"></i> Marketing Campaigns
            </a>

            <div class="nav-section-title mt-3">Settings</div>
            <a href="settings.php" class="sidebar-link">
                <i class="bi bi-sliders"></i> Engine Settings
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="user-avatar">MV</div>
            <div style="overflow: hidden;">
                <div class="text-white fw-bold small text-truncate">Marcus Vance</div>
                <div class="text-secondary small text-truncate" style="font-size: 11px;">Head of Growth</div>
            </div>
        </div>
    </aside>

    <!-- Main Workspace -->
    <main class="app-workspace">
        
        <!-- Top App Navigation -->
        <header class="app-topbar">
            <div class="topbar-title">
                <i class="bi bi-gem text-danger fs-5"></i>
                <h1>Modular Landing Page & Hero Section Customizer</h1>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 11px;">Ruby ERB</span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="landing.php" target="_blank" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-up-right"></i> Open Public URL
                </a>
                <button type="submit" form="cmsForm" class="btn btn-sm btn-primary-ruby d-flex align-items-center gap-2 px-3 fw-bold">
                    <i class="bi bi-check-circle-fill"></i> Save & Publish Hero
                </button>
            </div>
        </header>

        <!-- Studio Body -->
        <div class="studio-body">
            
            <!-- Left Form: CMS Theme Editor -->
            <div class="editor-panel">
                <div class="panel-header">
                    <h2><i class="bi bi-code-slash text-danger"></i> Hero Module Dynamic ERB Tags</h2>
                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <span class="small text-muted me-1" style="font-size: 11px;">Tags:</span>
                        <span class="tag-pill" onclick="insertTag('<%= brand.name %>')">&lt;%= brand.name %&gt;</span>
                        <span class="tag-pill" onclick="insertTag('<%= campaign.code %>')">&lt;%= campaign.code %&gt;</span>
                        <span class="tag-pill" onclick="insertTag('<%= metrics.global_regions %>')">&lt;%= metrics.global_regions %&gt;</span>
                    </div>
                </div>

                <form id="cmsForm" method="POST" action="" class="p-4 d-flex flex-column gap-3">
                    
                    <div>
                        <label class="form-label small fw-bold text-muted mb-1">Hero Main Headline (Dynamic ERB Supported)</label>
                        <input type="text" name="headline" id="headlineInput" class="form-control bg-dark text-white border-secondary font-monospace" value="<?= htmlspecialchars($headline_input) ?>" required>
                    </div>

                    <div>
                        <label class="form-label small fw-bold text-muted mb-1">Promotional Tagline / Subtitle</label>
                        <input type="text" name="tagline" id="taglineInput" class="form-control bg-dark text-white border-secondary font-monospace" value="<?= htmlspecialchars($tagline_input) ?>" required>
                    </div>

                    <!-- Modular Hero Section / Layout Block -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-muted mb-0">
                                <i class="bi bi-boxes me-1"></i> Modular Hero Layout & CTA Block (HTML & Ruby ERB)
                            </label>
                            <span class="badge bg-dark text-danger border border-danger border-opacity-25 font-monospace" style="font-size: 10px;">Ruby ERB Engine</span>
                        </div>
                        <textarea name="layout_body" id="layoutInput" class="code-textarea" placeholder="Write modular HTML and Ruby ERB template tags..."><?= htmlspecialchars($layout_input) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-25">
                        <div class="small text-muted">
                            <i class="bi bi-cpu me-1 text-danger"></i> Engine: <strong>Ruby 3.2 (ERB Template Renderer)</strong>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary-ruby px-3 fw-bold">
                            <i class="bi bi-arrow-repeat me-1"></i> Re-Render Landing Page
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Panel: Live SaaS Landing Page Preview -->
            <div class="preview-panel">
                <div class="panel-header">
                    <h2><i class="bi bi-eye text-danger"></i> Live SaaS Landing Page Preview</h2>
                    <?php if ($render_time !== null): ?>
                        <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 font-monospace" style="font-size: 11px;">
                            <i class="bi bi-lightning-charge-fill me-1"></i> Compiled in <?= $render_time ?> ms
                        </span>
                    <?php endif; ?>
                </div>

                <div class="preview-viewport">
                    
                    <div class="landing-mockup">
                        
                        <!-- Mini Nav -->
                        <div class="landing-nav">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-lightning-charge-fill text-danger fs-5"></i>
                                <span class="fw-bold text-white">CloudScale</span>
                            </div>
                            <div class="d-flex align-items-center gap-3 text-secondary small">
                                <span class="text-light">Features</span>
                                <span>Network</span>
                                <span>Pricing</span>
                                <button class="btn btn-sm btn-outline-light px-3 py-1" style="font-size: 11px;">Sign In</button>
                            </div>
                        </div>

                        <!-- Rendered Hero Section -->
                        <div class="landing-hero">
                            
                            <!-- Headline -->
                            <h1 class="display-6 fw-bold text-white mb-3">
                                <?= $rendered_headline ?>
                            </h1>

                            <!-- Tagline -->
                            <p class="lead text-secondary mb-4 fs-6" style="max-width: 650px; margin: 0 auto;">
                                <?= $rendered_tagline ?>
                            </p>

                            <!-- Rendered Modular Layout & CTA Block -->
                            <div class="rendered-layout-container">
                                <?= $rendered_layout ?>
                            </div>

                        </div>

                        <!-- Feature Grid -->
                        <div class="p-4 border-top border-secondary border-opacity-25 bg-black bg-opacity-25">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="feature-card">
                                        <i class="bi bi-speedometer2 text-danger fs-4 mb-2 d-block"></i>
                                        <h6 class="fw-bold text-white mb-1">0ms Cold Starts</h6>
                                        <p class="text-secondary small mb-0">Pre-warmed V8 worker isolates deployed on global Anycast edge networks.</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="feature-card">
                                        <i class="bi bi-shield-lock-fill text-danger fs-4 mb-2 d-block"></i>
                                        <h6 class="fw-bold text-white mb-1">Automated TLS 1.3</h6>
                                        <p class="text-secondary small mb-0">Instant zero-trust SSL certificates provisioned automatically at DNS setup.</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="feature-card">
                                        <i class="bi bi-hdd-rack text-danger fs-4 mb-2 d-block"></i>
                                        <h6 class="fw-bold text-white mb-1">Regional Failover</h6>
                                        <p class="text-secondary small mb-0">Dynamic health probes reroute traffic around data center disruptions.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Educational Context -->
                    <div class="card bg-dark bg-opacity-50 border-secondary border-opacity-50 p-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-lightbulb text-warning"></i>
                            <span class="small fw-bold text-white">How SSTI occurs in CMS & Page Builders:</span>
                        </div>
                        <p class="small text-muted mb-2">
                            When CMS platforms allow marketing managers to design modular landing page blocks with embedded ERB tags (e.g. <code>&lt;%= brand.name %&gt;</code>), server-side evaluation of untrusted input leads to Remote Code Execution in Ruby:
                        </p>
                        <div class="small font-monospace text-primary mb-1">
                            Arithmetic: <code>&lt;%= 7*7 %&gt;</code>
                        </div>
                        <div class="small font-monospace text-danger">
                            Arbitrary Command Execution: <code>&lt;%= %x(id) %&gt;</code> or <code>&lt;%= `id` %&gt;</code>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <script>
        function insertTag(tag) {
            const textarea = document.getElementById('layoutInput');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            textarea.value = text.substring(0, start) + tag + text.substring(end);
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = start + tag.length;
        }
    </script>
</body>
</html>
