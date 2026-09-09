<?php require_once __DIR__.'/config.php'; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? SITE_NAME) ?> · <?= SITE_NAME ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php"><span class="shield">✦</span><span>TrustDesk</span></a>
  <nav>
    <a href="report.php">Report an issue</a>
    <a href="track.php">Track a case</a>
    <a href="safety.php">Safety center</a>
    <a class="nav-admin" href="admin/login.php">Admin dashboard</a>
  </nav>
</header>
