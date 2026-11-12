<?php
$active_nav = $active_nav ?? 'research';
$page_title = $page_title ?? 'Northbridge Research Institute';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/static/css/style.css">
</head>
<body>

<div class="utility-bar">
    <div class="container utility-inner">
        <div>Cambridge, MA &middot; Geneva &middot; Singapore</div>
        <div class="utility-links">
            <a href="/publications/">Policy Repository</a>
            <a href="/researchers.php">Fellows Directory</a>
            <a href="/about.php#press">Press &amp; Media</a>
        </div>
    </div>
</div>

<header class="site-header">
    <div class="container header-inner">
        <a href="/" class="brand">
            <div class="brand-mark">N</div>
            <div class="brand-text">
                <strong>Northbridge Research Institute</strong>
                <span>Science &middot; Policy &middot; Infrastructure</span>
            </div>
        </a>

        <nav class="main-nav">
            <a href="/" class="<?= $active_nav === 'research' ? 'active' : '' ?>">Research</a>
            <a href="/publications/" class="<?= $active_nav === 'publications' ? 'active' : '' ?>">Publications</a>
            <a href="/researchers.php" class="<?= $active_nav === 'researchers' ? 'active' : '' ?>">Researchers</a>
            <a href="/about.php" class="<?= $active_nav === 'about' ? 'active' : '' ?>">About</a>
        </nav>
    </div>
</header>

