<?php
session_start();

$default_headline = 'Serverless Edge Compute Powered by <%= brand.name %>';
$default_tagline = 'Deploy micro-services in milliseconds across <%= metrics.global_regions %> with zero operational overhead.';
$default_layout = '<div class="hero-campaign-pill mb-3">
    <span class="badge bg-danger me-2">LIMITED LAUNCH</span>
    <span>Use code <strong><%= campaign.code %></strong> for <strong><%= campaign.discount %></strong>! Expires <%= campaign.expiry %>.</span>
</div>
<div class="hero-cta-group d-flex gap-3 justify-content-center my-4">
    <a href="#" class="btn btn-danger btn-lg px-4 py-2 fw-bold">
        <%= campaign.cta_text %> &rarr;
    </a>
</div>';

$headline_input = $_GET['headline'] ?? $default_headline;
$tagline_input = $_GET['tagline'] ?? $default_tagline;
$layout_input = $_GET['layout'] ?? $default_layout;

$b64_h = base64_encode($headline_input);
$cmd_h = sprintf('ruby %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.rb'), escapeshellarg($b64_h));
$rendered_headline = shell_exec($cmd_h);

$b64_t = base64_encode($tagline_input);
$cmd_t = sprintf('ruby %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.rb'), escapeshellarg($b64_t));
$rendered_tagline = shell_exec($cmd_t);

$b64_l = base64_encode($layout_input);
$cmd_l = sprintf('ruby %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.rb'), escapeshellarg($b64_l));
$rendered_layout = shell_exec($cmd_l);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CloudScale — Next-Gen Serverless Edge Cloud (Ruby ERB)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #090409; color: #f1f5f9; min-height: 100vh; }
        .pub-nav { background: #120914; border-bottom: 1px solid #2d1629; padding: 16px 32px; }
        .hero-section { padding: 80px 20px; text-align: center; max-width: 900px; margin: 0 auto; }
        .hero-campaign-pill { display: inline-flex; align-items: center; background: rgba(225, 29, 72, 0.12); border: 1px solid rgba(225, 29, 72, 0.3); border-radius: 30px; padding: 6px 16px; font-size: 14px; color: #fda4af; }
    </style>
</head>
<body>
    <header class="pub-nav d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-lightning-charge-fill text-danger fs-4"></i>
            <span class="fw-bold fs-4 text-white">CloudScale</span>
        </div>
        <div>
            <a href="index.php" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-pencil-square me-1"></i> Open CMS Studio
            </a>
        </div>
    </header>

    <div class="hero-section">
        <h1 class="display-4 fw-bold text-white mb-3"><?= $rendered_headline ?></h1>
        <p class="lead text-secondary mb-4 fs-5"><?= $rendered_tagline ?></p>
        <div><?= $rendered_layout ?></div>
    </div>
</body>
</html>
