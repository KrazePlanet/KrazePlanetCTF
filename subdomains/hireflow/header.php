<?php require_once __DIR__ . '/config.php'; ?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? SITE_NAME) ?> · <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head><body>
<header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">H</span><span>HireFlow</span></a>
<nav><a href="jobs.php">Open roles</a><a href="status.php">Application status</a><a href="about.php">Why HireFlow</a><a class="nav-admin" href="admin/login.php">Admin dashboard</a></nav></header>
