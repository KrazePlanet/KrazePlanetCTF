<?php
$active_nav = $active_nav ?? 'home';
$page_title = $page_title ?? 'KestrelEdge | Global Treasury & Cross-Border Settlement';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/static/css/style.css">
</head>
<body>

<header class="site-nav">
    <div class="container nav-inner">
        <a href="/" class="brand-link">
            <img src="/images/logo-transparent.png" alt="KestrelEdge">
        </a>

        <nav class="nav-menu">
            <a href="/" class="<?= $active_nav === 'home' ? 'active' : '' ?>">Platform</a>
            <a href="/treasury.php" class="<?= $active_nav === 'treasury' ? 'active' : '' ?>">Treasury &amp; FX</a>
            <a href="/onboarding.php" class="<?= $active_nav === 'onboarding' ? 'active' : '' ?>">Verification &amp; KYC</a>
            <a href="/company.php" class="<?= $active_nav === 'company' ? 'active' : '' ?>">Company</a>
        </nav>

        <div>
            <a href="/company.php#contact" class="btn btn-dark">Contact Sales</a>
        </div>
    </div>
</header>

