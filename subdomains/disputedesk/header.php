<?php require_once __DIR__.'/config.php'; ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title??SITE_NAME)?> · <?=SITE_NAME?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css"></head><body>
<header class="nav"><a class="logo" href="index.php"><span class="logo-icon">D</span><span>DisputeDesk</span></a>
<nav><a href="file.php">File a dispute</a><a href="track.php">Track a case</a><a href="guide.php">How it works</a><a class="admin-link" href="admin/login.php">Admin dashboard</a></nav></header>