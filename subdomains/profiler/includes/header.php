<?php
$active_nav = $active_nav ?? 'profile';
$page_title = $page_title ?? 'Peerstack — Engineering & Open Source Profiles';
$search_q = $search_q ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/static/css/style.css">
</head>
<body>

<header class="app-header">
    <div class="container navbar">
        <div class="navbar-left">
            <a href="/" class="brand-mark">
                <span class="brand-icon">P</span>
                <span>Peerstack</span>
            </a>

            <form method="GET" action="/explore.php" class="nav-search">
                <input type="text" name="q" value="<?= htmlspecialchars($search_q) ?>" placeholder="Search engineers, crates, repos...">
                <kbd>/</kbd>
            </form>

            <nav class="nav-links">
                <a href="/" class="<?= $active_nav === 'profile' ? 'active' : '' ?>">Featured Profile</a>
                <a href="/explore.php" class="<?= $active_nav === 'explore' ? 'active' : '' ?>">Explore</a>
                <a href="/projects.php" class="<?= $active_nav === 'projects' ? 'active' : '' ?>">Repositories</a>
                <a href="/changelog.php" class="<?= $active_nav === 'changelog' ? 'active' : '' ?>">Changelog</a>
            </nav>
        </div>

        <div style="display:flex;align-items:center;gap:10px;">
            <a href="/explore.php" class="btn">Directory (6)</a>
        </div>
    </div>
</header>
