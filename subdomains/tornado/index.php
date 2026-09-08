<?php
session_start();

$default_title = 'Production Cluster Ingress Recovery Runbook';
$default_category = 'Infrastructure & Runbooks';
$default_tags = 'sre, ingress, failover, disaster-recovery';
$default_summary = 'Standard operating procedure for edge proxy failover and BGP route redirection during traffic spikes.';
$default_content = '<div class="wiki-callout info">
    <div class="callout-title"><i class="bi bi-info-circle-fill me-2"></i> Document Notice</div>
    <p class="mb-0">Maintained by <strong>{{author["name"]}}</strong> ({{author["role"]}}) for the <strong>{{wiki["space"]}}</strong> team. Version: <code>{{wiki["version"]}}</code>.</p>
</div>

<h3>1. Architecture Overview</h3>
<p>When external ingress proxies experience latency degradation exceeding 120ms, automatic failover diverts traffic to the secondary edge cluster in <strong>{{wiki["space"]}}</strong>.</p>

<div class="code-block-preview">
# Verify current ingress connection health
kubectl get ingress -n production -o wide
curl -I -s -o /dev/null -w "%{http_code}" https://ingress.edge.internal/healthz
</div>

<h3>2. Automated Escalation Matrix</h3>
<table class="table table-dark table-bordered table-sm my-3">
    <thead>
        <tr>
            <th>Severity</th>
            <th>Trigger Condition</th>
            <th>On-Call Contact</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><span class="badge bg-danger">SEV-1</span></td>
            <td>Packet loss &gt; 5% across edge nodes</td>
            <td>{{author["email"]}}</td>
        </tr>
        <tr>
            <td><span class="badge bg-warning text-dark">SEV-2</span></td>
            <td>p99 Latency &gt; 250ms for 3 minutes</td>
            <td>Secondary SRE Pager</td>
        </tr>
    </tbody>
</table>

<h3>3. Verification & Metrics Verification</h3>
<p>Ensure that connection pools have returned to nominal capacity before resolving the PagerDuty incident record.</p>';

$title_input = $_POST['title'] ?? $default_title;
$category_input = $_POST['category'] ?? $default_category;
$tags_input = $_POST['tags'] ?? $default_tags;
$summary_input = $_POST['summary'] ?? $default_summary;
$content_input = $_POST['content'] ?? $default_content;

$rendered_title = '';
$rendered_summary = '';
$rendered_content = '';
$render_time = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['preview'])) {
    $start = microtime(true);
    
    // Evaluate Title with Tornado
    $b64_title = base64_encode($title_input);
    $cmd_title = sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64_title));
    $rendered_title = shell_exec($cmd_title);

    // Evaluate Summary with Tornado
    $b64_sum = base64_encode($summary_input);
    $cmd_sum = sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64_sum));
    $rendered_summary = shell_exec($cmd_sum);

    // Evaluate Content with Tornado
    $b64_body = base64_encode($content_input);
    $cmd_body = sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64_body));
    $rendered_content = shell_exec($cmd_body);
    
    $render_time = round((microtime(true) - $start) * 1000, 2);
} else {
    $b64_title = base64_encode($default_title);
    $rendered_title = shell_exec(sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64_title)));

    $b64_sum = base64_encode($default_summary);
    $rendered_summary = shell_exec(sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64_sum)));

    $b64_body = base64_encode($default_content);
    $rendered_content = shell_exec(sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64_body)));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevWiki — Engineering Knowledge Base & Markdown Macro SSTI (Tornado)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --sidebar-bg: #090d16;
            --card-border: #1e293b;
            --app-bg: #050811;
            --surface-bg: #0e1526;
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
            background: rgba(99, 102, 241, 0.18);
            border: 1px solid rgba(99, 102, 241, 0.35);
            font-weight: 600;
        }

        .doc-item {
            padding: 7px 12px 7px 28px;
            font-size: 13px;
            color: #94a3b8;
            display: block;
            text-decoration: none;
            border-radius: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .doc-item:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.04);
        }

        .doc-item.active {
            color: var(--primary-light);
            font-weight: 600;
            background: rgba(99, 102, 241, 0.12);
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
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
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
            background: #050811;
        }

        .app-topbar {
            height: 64px;
            background: #090d16;
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
            box-shadow: 0 4px 20px rgba(0,0,0,0.35);
        }

        .preview-panel {
            flex: 1.1;
            background: var(--surface-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.35);
        }

        .panel-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #070b14;
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
            background: #050811;
            resize: vertical;
            min-height: 280px;
        }

        .code-textarea:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }

        .preview-viewport {
            flex-grow: 1;
            background: #050811;
            overflow-y: auto;
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .wiki-article-view {
            background: #0a0f1e;
            border: 1px solid #1a233a;
            border-radius: 14px;
            padding: 32px;
            line-height: 1.7;
        }

        .wiki-breadcrumb {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .wiki-meta-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 18px;
            margin-bottom: 22px;
            border-bottom: 1px solid #1e293b;
            font-size: 13px;
            color: #94a3b8;
            flex-wrap: wrap;
        }

        .wiki-callout {
            border-left: 4px solid var(--primary-light);
            background: rgba(99, 102, 241, 0.08);
            border-radius: 0 8px 8px 0;
            padding: 14px 18px;
            margin: 18px 0;
            font-size: 13.5px;
        }

        .code-block-preview {
            background: #03060d;
            border: 1px solid #1e293b;
            border-radius: 8px;
            padding: 14px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12.5px;
            color: #38bdf8;
            margin: 16px 0;
            white-space: pre-wrap;
        }

        .tag-pill {
            background: #090d16;
            color: var(--primary-light);
            border: 1px solid #1e293b;
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
            <i class="bi bi-journal-bookmark-fill"></i> DevWiki
        </div>

        <div class="sidebar-menu">
            <div class="nav-section-title">Knowledge Base</div>
            <a href="index.php" class="sidebar-link active">
                <i class="bi bi-pencil-square"></i> Article Editor
            </a>
            <a href="article.php" class="sidebar-link">
                <i class="bi bi-file-earmark-text"></i> Documentation View
            </a>
            <a href="search.php" class="sidebar-link">
                <i class="bi bi-search"></i> Wiki Search & Tags
            </a>
            <a href="spaces.php" class="sidebar-link">
                <i class="bi bi-collection"></i> Team Spaces
            </a>

            <div class="nav-section-title mt-3">Documentation Tree</div>
            <a href="#" class="doc-item active">📖 Ingress Recovery Runbook</a>
            <a href="#" class="doc-item">📄 Pod Autoscaling Playbook</a>
            <a href="#" class="doc-item">📄 Zero-Trust Mesh Guide</a>
            <a href="#" class="doc-item">📄 Post-Mortem Template</a>

            <div class="nav-section-title mt-3">Settings</div>
            <a href="settings.php" class="sidebar-link">
                <i class="bi bi-sliders"></i> Engine Settings
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="user-avatar">ER</div>
            <div style="overflow: hidden;">
                <div class="text-white fw-bold small text-truncate">Elena Rostova</div>
                <div class="text-secondary small text-truncate" style="font-size: 11px;">Lead SRE</div>
            </div>
        </div>
    </aside>

    <!-- Main Workspace -->
    <main class="app-workspace">
        
        <!-- Top App Navigation -->
        <header class="app-topbar">
            <div class="topbar-title">
                <i class="bi bi-journal-text text-indigo fs-5" style="color: #818cf8;"></i>
                <h1>Knowledge Base Runbook & Wiki Macro Editor</h1>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 11px;">Python Tornado</span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="article.php" target="_blank" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-up-right"></i> Open Clean Doc View
                </a>
                <a href="search.php" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-search"></i> Search Docs
                </a>
                <button type="submit" form="wikiForm" class="btn btn-sm text-white d-flex align-items-center gap-2 px-3 fw-bold" style="background: #6366f1;">
                    <i class="bi bi-play-circle-fill"></i> Compile & Preview Runbook
                </button>
            </div>
        </header>

        <!-- Studio Body -->
        <div class="studio-body">
            
            <!-- Left Form: Wiki Editor -->
            <div class="editor-panel">
                <div class="panel-header">
                    <h2><i class="bi bi-code-square text-indigo" style="color: #818cf8;"></i> Article Content & Tornado Macro Tags</h2>
                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <span class="small text-muted me-1" style="font-size: 11px;">Macros:</span>
                        <span class="tag-pill" onclick="insertMacro('{{wiki[\'space\']}}')">{{wiki.space}}</span>
                        <span class="tag-pill" onclick="insertMacro('{{author[\'name\']}}')">{{author.name}}</span>
                        <span class="tag-pill" onclick="insertMacro('{{wiki[\'version\']}}')">{{wiki.version}}</span>
                    </div>
                </div>

                <form id="wikiForm" method="POST" action="" class="p-4 d-flex flex-column gap-3">
                    
                    <div>
                        <label class="form-label small fw-bold text-muted mb-1">Article Title (Dynamic Macro Supported)</label>
                        <input type="text" name="title" id="titleInput" class="form-control bg-dark text-white border-secondary font-monospace" value="<?= htmlspecialchars($title_input) ?>" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">Documentation Space / Category</label>
                            <input type="text" name="category" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($category_input) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">Index Tags (Comma-separated)</label>
                            <input type="text" name="tags" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($tags_input) ?>">
                        </div>
                    </div>

                    <!-- Summary Callout (Dynamic Tornado Template Input) -->
                    <div class="border rounded-3 p-3" style="background: rgba(99, 102, 241, 0.08); border-color: rgba(99, 102, 241, 0.25) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold mb-0" style="color: #818cf8;">
                                <i class="bi bi-chat-left-quote-fill me-1"></i> Executive Runbook Summary Header
                            </label>
                            <span class="badge bg-dark font-monospace" style="color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); font-size: 10px;">Tornado Evaluated</span>
                        </div>
                        <input type="text" name="summary" id="summaryInput" class="form-control bg-dark text-white border-secondary font-monospace mt-1" value="<?= htmlspecialchars($summary_input) ?>" placeholder="e.g. SRE SOP for {{wiki['space']}}">
                    </div>

                    <!-- Article Markdown / Macro Content -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-muted mb-0">
                                <i class="bi bi-file-earmark-richtext me-1"></i> Wiki Layout & Macro Body (HTML & Tornado Code)
                            </label>
                            <span class="badge bg-dark text-muted border border-secondary font-monospace" style="font-size: 10px;">Tornado Engine</span>
                        </div>
                        <textarea name="content" id="contentInput" class="code-textarea" placeholder="Write documentation body with Tornado syntax..."><?= htmlspecialchars($content_input) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-25">
                        <div class="small text-muted">
                            <i class="bi bi-cpu me-1" style="color: #818cf8;"></i> Template Engine: <strong>Python 3.12 (Tornado Template 6.4)</strong>
                        </div>
                        <button type="submit" class="btn btn-sm text-white px-3 fw-bold" style="background: #6366f1;">
                            <i class="bi bi-arrow-repeat me-1"></i> Render Documentation
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Panel: Live Published Documentation Preview -->
            <div class="preview-panel">
                <div class="panel-header">
                    <h2><i class="bi bi-eye text-indigo" style="color: #818cf8;"></i> Live Documentation Runbook View</h2>
                    <?php if ($render_time !== null): ?>
                        <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 font-monospace" style="font-size: 11px;">
                            <i class="bi bi-lightning-charge-fill me-1"></i> Compiled in <?= $render_time ?> ms
                        </span>
                    <?php endif; ?>
                </div>

                <div class="preview-viewport">
                    
                    <article class="wiki-article-view">
                        <div class="wiki-breadcrumb">
                            <span>Docs</span>
                            <i class="bi bi-chevron-right" style="font-size: 10px;"></i>
                            <span><?= htmlspecialchars($category_input) ?></span>
                            <i class="bi bi-chevron-right" style="font-size: 10px;"></i>
                            <span class="text-white">Runbook</span>
                        </div>

                        <!-- Rendered Article Title -->
                        <h1 class="fw-bold text-white mb-2 fs-3">
                            <?= $rendered_title ?>
                        </h1>

                        <div class="wiki-meta-row">
                            <div><i class="bi bi-person-fill me-1" style="color: #818cf8;"></i> Elena Rostova</div>
                            <div><i class="bi bi-clock me-1"></i> Last revised September 2026</div>
                            <div><i class="bi bi-eye me-1"></i> 4,310 reads</div>
                            <div><span class="badge bg-dark text-muted border border-secondary"><?= htmlspecialchars($category_input) ?></span></div>
                        </div>

                        <!-- Rendered Summary -->
                        <div class="p-3 mb-4 rounded-2" style="background: rgba(255,255,255,0.03); border: 1px solid #1e293b; font-size: 14px; color: #cbd5e1;">
                            <strong>Summary:</strong> <?= $rendered_summary ?>
                        </div>

                        <!-- Rendered Content Body -->
                        <div class="rendered-wiki-body">
                            <?= $rendered_content ?>
                        </div>
                    </article>

                    <!-- Testing / Educational Context Callout -->
                    <div class="card bg-dark bg-opacity-50 border-secondary border-opacity-50 p-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-lightbulb text-warning"></i>
                            <span class="small fw-bold text-white">How SSTI occurs in Wikis & Blogs:</span>
                        </div>
                        <p class="small text-muted mb-2">
                            Many internal wikis, documentation hubs, or CMS platforms allow dynamic macros (e.g. <code>{{wiki['space']}}</code>). When user articles or titles are passed directly into the template compiler, attackers can inject arbitrary Python statements like:
                        </p>
                        <div class="small font-monospace text-primary mb-1">
                            Expression test: <code>{{ 7*7 }}</code>
                        </div>
                        <div class="small font-monospace" style="color: #818cf8;">
                            Arbitrary execution: <code>{% import os %}{{ os.popen('id').read() }}</code>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <script>
        function insertMacro(tag) {
            const textarea = document.getElementById('contentInput');
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
