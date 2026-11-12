<?php
$active_nav = $active_nav ?? 'home';
$page_title = $page_title ?? 'VaultTech — Enterprise Payment Processing & Settlement';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/static/css/style.css">
<script src="/static/js/runtime.js" defer></script>
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="/" class="brand">
            <span class="brand-mark">V</span>
            <span>VaultTech</span>
        </a>

        <nav class="nav-links">
            <a href="/products.php" class="<?= $active_nav === 'products' ? 'active' : '' ?>">Products</a>
            <a href="/pricing.php" class="<?= $active_nav === 'pricing' ? 'active' : '' ?>">Pricing</a>
            <a href="/docs.php" class="<?= $active_nav === 'docs' ? 'active' : '' ?>">Developers</a>
            <a href="/status.php" class="<?= $active_nav === 'status' ? 'active' : '' ?>">System Status</a>
        </nav>

        <div style="display:flex;gap:10px;align-items:center;">
            <a href="/docs.php" class="btn btn-outline">API Docs</a>
            <a href="/pricing.php#enterprise" class="btn btn-brand">Contact Sales</a>
        </div>
    </div>
</header>

