<?php
session_start();

$default_title = 'Production Cluster Ingress Recovery Runbook';
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
</div>';

$title_input = $_GET['title'] ?? $default_title;
$content_input = $_GET['content'] ?? $default_content;

$b64_title = base64_encode($title_input);
$cmd_title = sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64_title));
$rendered_title = shell_exec($cmd_title);

$b64_body = base64_encode($content_input);
$cmd_body = sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64_body));
$rendered_content = shell_exec($cmd_body);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($default_title) ?> — DevWiki Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #050811; color: #f1f5f9; min-height: 100vh; }
        .doc-navbar { background: #090d16; border-bottom: 1px solid #1e293b; padding: 14px 28px; }
        .doc-container { max-width: 860px; margin: 40px auto; background: #0a0f1e; border: 1px solid #1a233a; border-radius: 14px; padding: 36px; line-height: 1.7; }
        .wiki-callout { border-left: 4px solid #818cf8; background: rgba(99, 102, 241, 0.08); border-radius: 0 8px 8px 0; padding: 14px 18px; margin: 18px 0; font-size: 13.5px; }
        .code-block-preview { background: #03060d; border: 1px solid #1e293b; border-radius: 8px; padding: 14px; font-family: 'JetBrains Mono', monospace; font-size: 12.5px; color: #38bdf8; margin: 16px 0; white-space: pre-wrap; }
    </style>
</head>
<body>
    <header class="doc-navbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-journal-bookmark-fill fs-4" style="color: #818cf8;"></i>
            <span class="fw-bold fs-5 text-white">DevWiki</span>
            <span class="text-secondary small ms-2">/ Public Knowledge Base</span>
        </div>
        <div>
            <a href="index.php" class="btn btn-sm text-white" style="background: #6366f1;">
                <i class="bi bi-pencil-square me-1"></i> Edit in Wiki Studio
            </a>
        </div>
    </header>

    <div class="container">
        <div class="doc-container">
            <h1 class="fw-bold text-white mb-2 fs-2"><?= $rendered_title ?></h1>
            <div class="d-flex align-items-center gap-3 text-secondary small pb-3 mb-4 border-bottom border-secondary border-opacity-25">
                <div><i class="bi bi-person-fill me-1" style="color: #818cf8;"></i> Elena Rostova</div>
                <div><i class="bi bi-clock me-1"></i> September 2026</div>
                <div><span class="badge bg-dark text-muted border border-secondary">Architecture & Runbooks</span></div>
            </div>
            <div>
                <?= $rendered_content ?>
            </div>
        </div>
    </div>
</body>
</html>
