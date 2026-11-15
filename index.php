<?php
require_once __DIR__ . '/config/sso.php';
// Anyone can browse the labs. Signing in is asked for only when a visitor
// opens a lab, bookmarks it, marks it solved or writes a report.
startKrazeSession();

// Shared data for the labs listing
$categories = json_decode(file_get_contents(__DIR__ . '/data/labs.json'), true) ?: [];
require __DIR__ . '/includes/category_icons.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

// Each category links to its write-up on the blog: blog.krazeplanet.com/templates/blog-post.html?id=<slug>
// "Username/Email Enumeration" -> "username-email-enumeration"
$catSlug = fn($name) => trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
$blogUrl = fn($name) => 'https://blog.krazeplanet.com/templates/blog-post.html?id=' . rawurlencode($catSlug($name));

// Per-lab slug, used by the Solution and Report links in each lab's actions menu.
// "/subdomains/tutorialrepublic" -> "tutorialrepublic"; a full URL -> its first host label.
require_once __DIR__ . '/config/labs.php';
$labSlug = fn($access) => krazeLabSlug($access); // same rule the Solved check uses (navbar/portal.php)

// Solution:  blog.krazeplanet.com/templates/blog-post.html?id=<slug>
// Report:    github.com/<repo>/new/<branch>?filename=<slug>.md&value=<template>
// The repo is the student's own, set on their profile ("GitHub repository").
// Without one, "Write Report" shows a toast pointing to the profile instead.
$reportRepo = '';
$reportBranch = 'main';
if (preg_match('#^https://github\.com/([A-Za-z0-9-]+/[A-Za-z0-9._-]+)(?:/tree/([A-Za-z0-9._/-]+))?$#', krazeGithubRepo(), $gm)) {
    $reportRepo = $gm[1];
    if (!empty($gm[2])) $reportBranch = $gm[2];
}
$labLinks = [
    'solutionBase'   => 'https://blog.krazeplanet.com/templates/blog-post.html?id=',
    'reportRepo'     => $reportRepo,
    'reportBranch'   => $reportBranch,
    'connectUrl'     => '/navbar/profile.php?tab=settings#github',
    'reportTemplate' => <<<'MD'
## Title
<!-- Write a short, clear title describing the vulnerability and where it was found. Example: Stored XSS in User Profile -->

## Description
<!-- Explain what the vulnerability is, where it exists, and why it is a security issue. Include the affected URL or endpoint. -->

## Steps to Reproduce
<!-- Provide clear, numbered steps so another person can reproduce the issue. Include required requests, parameters, and actions. -->

## POC (Proof of Concept)
<!-- Add screenshots, HTTP requests/responses, payloads, or videos that demonstrate the vulnerability. -->

## Impact
<!-- Explain what an attacker could achieve, who could be affected, and the realistic security consequences. -->

## Remediation
<!-- Suggest specific steps to fix the vulnerability. Include secure coding practices or configuration changes where appropriate. -->
MD,
];
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KrazePlanet - Web Security Training Platform</title>
  <?php
    $labTotal = 0;
    foreach ($categories as $catLabs) $labTotal += is_array($catLabs) ? count($catLabs) : 0;
    $metaDesc = "Free hands-on web security labs: {$labTotal} labs across " . count($categories) . " bug types, from XSS and SQL injection to SSRF and request smuggling. Find the vulnerability yourself, then check the solution.";
  ?>
  <meta name="description" content="<?= $e($metaDesc) ?>">
  <meta property="og:title" content="KrazePlanet - Web Security Training Platform">
  <meta property="og:description" content="<?= $e($metaDesc) ?>">
  <meta property="og:type" content="website">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
  integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="icon" href="favicon.ico" />
  
  <!-- Google Fonts: Inter, Outfit, JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root {
      --bg-dark: #070b14;
      --bg-card: rgba(15, 23, 42, 0.75);
      --border-card: rgba(255, 255, 255, 0.08);
      --accent-green: #10b981;
      --accent-green-glow: rgba(16, 185, 129, 0.3);
      --accent-blue: #3b82f6;
      --accent-orange: #f59e0b;
      --accent-red: #f43f5e;
      --accent-purple: #8b5cf6;
      --accent-cyan: #06b6d4;
      --text-main: #f1f5f9;
      --text-muted: #94a3b8;
      --font-primary: 'Inter', system-ui, -apple-system, sans-serif;
      --font-heading: 'Outfit', sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
    }
    
    body {
      background: #0a0f1c;
      color: #f8fafc;
      min-height: 100vh;
      font-family: var(--font-primary);
      -webkit-font-smoothing: antialiased;
    }

    h1, h2, h3, h4, h5, h6, .hero-title, .category-title {
      font-family: var(--font-heading);
    }

    .nav-link {
      font-weight: 500;
      transition: color 0.3s;
    }

    .nav-link:hover {
      color: var(--accent-green) !important;
    }

    .navbar {
      background: rgba(15, 23, 42, 0.92) !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      padding: 0.75rem 0;
    }

    .btn-cta {
      background: linear-gradient(135deg, #10b981, #059669);
      border: none;
      color: white;
      font-weight: 600;
      border-radius: 0.75rem;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
    }

    .btn-cta:hover {
      background: linear-gradient(135deg, #059669, #047857);
      transform: translateY(-2px);
      color: white;
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
    }

    /* ---- Hero: pitch + action on the left, progress / proof on the right ---- */
    .hero {
      display: grid;
      grid-template-columns: minmax(0, 1.55fr) minmax(280px, 1fr);
      gap: 28px;
      align-items: stretch;
      padding: 32px;
      margin-bottom: 18px;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      background:
        radial-gradient(900px 300px at 0% 0%, rgba(56, 189, 248, 0.10), transparent 60%),
        linear-gradient(180deg, #0f182b, #0c1424);
      position: relative;
      overflow: hidden;
    }

    /* faint grid texture, top-right */
    .hero > * { position: relative; z-index: 1; }
    .hero-main { display: flex; flex-direction: column; justify-content: center; min-width: 0; }

    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      width: fit-content;
      font-size: 0.8rem;
      font-weight: 600;
      color: #bae6fd;
      background: rgba(56, 189, 248, 0.1);
      border: 1px solid rgba(56, 189, 248, 0.25);
      border-radius: 999px;
      padding: 4px 12px;
      margin-bottom: 14px;
    }
    .hero-eyebrow .dot { width: 7px; height: 7px; border-radius: 50%; background: #34d399; box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.2); }

    .hero-title {
      font-size: 2.35rem;
      font-weight: 800;
      line-height: 1.12;
      color: #ffffff;
      margin: 0 0 12px;
      letter-spacing: -0.8px;
      max-width: 620px;
      text-wrap: balance; /* split lines evenly - no single word left on its own line */
    }

    .hero-subtitle {
      font-size: 1.08rem;
      color: #c3cddb;
      max-width: 560px;
      margin: 0;
      line-height: 1.55;
      text-wrap: pretty;
    }

    /* Signed-in hero: "Up next" lab card */
    .next-card {
      margin-top: 18px;
      padding: 18px 20px;
      border-radius: 14px;
      background: rgba(7, 11, 20, 0.55);
      border: 1px solid rgba(255, 255, 255, 0.09);
      border-left: 3px solid #38bdf8;
    }
    .next-card[hidden] { display: none; }
    .nc-label { font-size: 0.74rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #7dd3fc; margin-bottom: 6px; }
    .nc-title { font-size: 1.12rem; font-weight: 700; color: #ffffff; line-height: 1.35; }
    .nc-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
    .nc-cat { font-size: 0.86rem; color: #94a3b8; }
    .nc-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 6px 16px; margin-top: 16px; }
    .nc-link { border: 0; background: none; padding: 4px 0; color: #94a3b8; font-size: 0.9rem; font-weight: 500; cursor: pointer; }
    .nc-link:hover { color: #ffffff; text-decoration: underline; text-underline-offset: 3px; }
    .next-card.nc-done { border-left-color: #34d399; }
    .nc-when { font-size: 0.86rem; color: #64748b; display: inline-flex; align-items: center; gap: 5px; }
    .nc-when::before { content: '\F293'; font-family: 'bootstrap-icons'; font-size: 0.8rem; } /* clock icon */
    .nc-when[hidden] { display: none; }

    .hero-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 22px; }
    .hero-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 0.95rem;
      font-weight: 600;
      border-radius: 10px;
      padding: 10px 18px;
      border: 1px solid transparent;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .hero-btn-primary { background: #0284c7; color: #ffffff; }
    .hero-btn-primary:hover { background: #0ea5e9; color: #ffffff; }
    .hero-btn-ghost { color: #e2e8f0; border-color: rgba(255, 255, 255, 0.18); background: rgba(255, 255, 255, 0.03); }
    .hero-btn-ghost:hover { color: #ffffff; border-color: rgba(255, 255, 255, 0.35); }
    .hero-btn:focus-visible { outline: 2px solid #38bdf8; outline-offset: 2px; }

    /* No box of its own: a thin divider separates it from the left side, so the hero
       has one frame and the "Up next" card is the only box inside it. */
    .hero-panel {
      background: none;
      border: 0;
      border-left: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 0;
      padding: 4px 0 4px 28px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 14px;
    }
    .hp-label { font-size: 0.8rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; }
    /* Progress ring + per-difficulty bars */
    .hp-progress { display: flex; align-items: center; gap: 22px; }
    .hp-ring { position: relative; width: 132px; height: 132px; flex-shrink: 0; }
    .hp-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
    .hp-ring-track { fill: none; stroke: rgba(255, 255, 255, 0.07); stroke-width: 9; }
    .hp-ring-fill { fill: none; stroke: url(#hpRingGrad); stroke-width: 9; stroke-linecap: round; transition: stroke-dashoffset 0.9s cubic-bezier(.2,.7,.2,1); }
    .hp-ring-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
    .hp-ring-num { font-family: var(--font-heading); font-size: 2rem; font-weight: 800; color: #ffffff; line-height: 1; }
    .hp-ring-of { font-size: 0.78rem; color: #94a3b8; margin-top: 3px; }
    .hp-ring-pct { font-size: 0.72rem; font-weight: 600; color: #7dd3fc; margin-top: 4px; }
    .hp-diffs { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 9px; }
    .hp-diff-top { display: flex; justify-content: space-between; align-items: baseline; font-size: 0.86rem; color: #cbd5e1; margin-bottom: 6px; }
    .hp-diff-top strong { color: #ffffff; font-weight: 700; }
    .hp-diff-of { color: #64748b; font-size: 0.8rem; }
    .hp-diff-bar { height: 6px; border-radius: 999px; background: rgba(255, 255, 255, 0.07); overflow: hidden; }
    .hp-diff-bar span { display: block; height: 100%; width: 0; border-radius: 999px; transition: width 0.7s ease; }
    .hp-diff.easy .hp-diff-top > span:first-child { color: #6ee7b7; }
    .hp-diff.medium .hp-diff-top > span:first-child { color: #fcd34d; }
    .hp-diff.hard .hp-diff-top > span:first-child { color: #fca5a5; }
    .hp-diff.easy .hp-diff-bar span { background: #34d399; }
    .hp-diff.medium .hp-diff-bar span { background: #fbbf24; }
    .hp-diff.hard .hp-diff-bar span { background: #f87171; }
    .hp-diff.secure .hp-diff-top > span:first-child { color: #7dd3fc; }
    .hp-diff.secure .hp-diff-bar span { background: #38bdf8; }
    @media (max-width: 400px) {
      .hp-progress { gap: 16px; }
      .hp-ring { width: 112px; height: 112px; }
      .hp-ring-num { font-size: 1.7rem; }
    }
    .hp-split { display: flex; flex-wrap: wrap; gap: 6px 18px; font-size: 0.86rem; color: #a3b1c6; }
    .hp-split strong { color: #f1f5f9; font-weight: 700; margin-left: 4px; }
    .hp-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; vertical-align: 1px; }
    .hp-dot.easy { background: #34d399; }
    .hp-dot.medium { background: #fbbf24; }
    .hp-dot.hard { background: #f87171; }
    .hp-dot.secure { background: #38bdf8; }
    .hp-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .hp-num { font-family: var(--font-heading); font-size: 1.85rem; font-weight: 800; color: #ffffff; line-height: 1.1; }
    .hp-cap { font-size: 0.8rem; color: #94a3b8; line-height: 1.35; margin-top: 4px; }

    /* ---- Toolbar: search + difficulty ---- */
    .lab-toolbar {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px 14px;
      margin-bottom: 6px;
      position: sticky;
      top: var(--kp-nav-h, 71px);
      z-index: 20;
      padding: 10px 0;
      background: #0a0f1c;
      /* a clear edge, so rows scrolling underneath don't look cut off */
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .lt-search { position: relative; flex: 1 1 320px; max-width: 460px; }
    .lt-search > i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 0.95rem; }
    .lt-search input {
      width: 100%;
      background: #0d1526;
      border: 1px solid rgba(255, 255, 255, 0.12);
      color: #f1f5f9;
      border-radius: 10px;
      padding: 9px 36px 9px 38px;
      font-size: 0.92rem;
      outline: none;
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .lt-search input::placeholder { color: #64748b; }
    .lt-search input:focus { border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15); }
    .lt-search input::-webkit-search-cancel-button { display: none; }
    .lt-clear { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: #94a3b8; padding: 4px 8px; border-radius: 6px; }
    .lt-clear:hover { color: #ffffff; background: rgba(255, 255, 255, 0.08); }
    .lt-filters { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; }
    .lt-chip {
      display: inline-flex;
      align-items: center;
      font-size: 0.86rem;
      font-weight: 500;
      color: #cbd5e1;
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 999px;
      padding: 6px 13px;
      cursor: pointer;
      transition: all 0.12s ease;
    }
    .lt-chip:hover { border-color: rgba(255, 255, 255, 0.28); color: #ffffff; }
    .lt-chip.is-on { background: rgba(56, 189, 248, 0.14); border-color: rgba(56, 189, 248, 0.5); color: #e0f2fe; font-weight: 600; }

    @media (max-width: 1199.98px) {
      .hero { grid-template-columns: minmax(0, 1fr); padding: 24px; }
      .hero-panel { border-left: 0; border-top: 1px solid rgba(255, 255, 255, 0.08); padding: 20px 0 0; }
    }
    @media (max-width: 640px) {
      .hero { padding: 20px 18px; gap: 18px; border-radius: 14px; }
      .hero-title { font-size: 1.7rem; }
      .hero-subtitle { font-size: 0.96rem; }
      .hero-btn { flex: 1 1 auto; justify-content: center; }
      .hp-num { font-size: 1.45rem; }
      .lt-search { max-width: none; flex-basis: 100%; }
      .lab-toolbar { position: static; }
    }


    .search-container {
      background: rgba(30, 41, 59, 0.85) !important;
      border: 1px solid rgba(255, 255, 255, 0.15) !important;
      border-radius: 16px !important;
      padding: 1.25rem 1.5rem !important;
      box-shadow: 0 12px 35px rgba(0, 0, 0, 0.35) !important;
      backdrop-filter: blur(12px);
    }

    .search-box {
      background: rgba(15, 23, 42, 0.8) !important;
      border: 1px solid rgba(255, 255, 255, 0.16) !important;
      color: #ffffff !important;
      font-family: var(--font-primary);
    }

    .search-box::placeholder {
      color: #94a3b8 !important;
    }

    .search-box:focus {
      background: rgba(15, 23, 42, 0.95) !important;
      border-color: #34d399 !important;
      box-shadow: 0 0 0 0.25rem rgba(52, 211, 153, 0.2) !important;
    }

    .cat-pill {
      background: rgba(255, 255, 255, 0.06);
      color: #cbd5e1;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 10px;
      padding: 0.45rem 1rem;
      font-size: 0.84rem;
      font-weight: 600;
      transition: all 0.2s ease;
      white-space: nowrap;
      cursor: pointer;
    }

    .cat-pill:hover {
      background: rgba(255, 255, 255, 0.14);
      color: #ffffff;
      border-color: rgba(255, 255, 255, 0.25);
    }

    .cat-pill.active-pill {
      background: linear-gradient(135deg, #10b981, #059669);
      color: #ffffff;
      font-weight: 700;
      border-color: #34d399;
      box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
    }


    .btn-solved-toggle {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.18);
      color: #cbd5e1;
      border-radius: 10px;
      padding: 7px 14px;
      font-size: 0.8rem;
      font-weight: 600;
      transition: all 0.2s ease;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
    }

    .btn-solved-toggle:hover {
      background: rgba(255, 255, 255, 0.16);
      color: #ffffff;
      border-color: rgba(255, 255, 255, 0.3);
    }

    .btn-solved-toggle.solved {
      background: rgba(16, 185, 129, 0.2);
      border-color: #10b981;
      color: #34d399;
    }

    .btn-solved-toggle.solved:hover {
      background: rgba(16, 185, 129, 0.3);
      color: #6ee7b7;
    }

    .progress-bar-glow {
      background: linear-gradient(90deg, #10b981, #38bdf8);
      box-shadow: 0 0 12px rgba(52, 211, 153, 0.4);
    }

    /* "Jump to lab": briefly highlight the row */
    @keyframes labPulse {
      0%, 100% { background: transparent; }
      30% { background: rgba(56, 189, 248, 0.16); }
    }

    .lab-highlight-pulse {
      animation: labPulse 1.4s ease-in-out 2;
    }

    .btn-star-toggle {
      background: transparent;
      border: none;
      color: #cbd5e1;
      font-size: 1.2rem;
      padding: 4px 8px;
      transition: all 0.2s ease;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-right: 2px;
    }

    .btn-star-toggle:hover {
      color: #fbbf24;
      transform: scale(1.2);
    }

    .btn-star-toggle.bookmarked {
      color: #fbbf24;
    }

    /* Per-lab "more actions" button + shared menu */
    .btn-lab-menu {
      width: 34px;
      height: 34px;
      border-radius: 9px;
      background: transparent;
      border: 1px solid transparent;
      color: #94a3b8;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.05rem;
      cursor: pointer;
      transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
      flex: 0 0 auto;
    }
    .btn-lab-menu:hover,
    .btn-lab-menu[aria-expanded="true"] {
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(255, 255, 255, 0.14);
      color: #ffffff;
    }
    .btn-lab-menu:focus-visible { outline: 2px solid #38bdf8; outline-offset: 2px; }
    .lab-card.menu-open { transform: none; }

    /* Per-lab ⋯ menu: a plain dropdown like the avatar menu. Icons are grey;
       colour is used only for the "on" state (green = solved, amber = saved). */
    .lab-menu {
      position: fixed;
      z-index: 1080;
      width: 220px;
      padding: 6px;
      display: flex;
      flex-direction: column;
      gap: 2px;
      background: #0f172a;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 12px;
      box-shadow: 0 16px 40px rgba(0, 0, 0, 0.6);
      animation: labMenuIn 0.12s ease-out;
    }
    @keyframes labMenuIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }
    .lab-menu[hidden] { display: none; }
    .lab-menu-item {
      display: flex;
      align-items: center;
      gap: 10px;
      width: 100%;
      padding: 8px 10px;
      background: transparent;
      border: 0;
      border-radius: 8px;
      color: #cbd5e1;
      font-family: inherit;
      font-size: 0.86rem;
      font-weight: 500;
      text-align: left;
      text-decoration: none;
      cursor: pointer;
      transition: background 0.12s ease, color 0.12s ease;
    }
    .lab-menu-item:hover,
    .lab-menu-item:focus-visible {
      background: rgba(255, 255, 255, 0.07);
      color: #ffffff;
      outline: none;
    }
    .lab-menu-item > i { font-size: 0.95rem; width: 16px; text-align: center; color: #94a3b8; }
    .lab-menu-item:hover > i { color: #e2e8f0; }
    .lab-menu-item .ext { margin-left: auto; font-size: 0.7rem; opacity: 0.6; }
    /* Save + Solved are on/off toggles, shown as two plain rows */
    .lab-menu-row { display: flex; flex-direction: column; gap: 2px; }
    .lab-menu-item.is-on-solved, .lab-menu-item.is-on-solved > i { color: #34d399; }
    .lab-menu-item.is-on-bm, .lab-menu-item.is-on-bm > i { color: #fbbf24; }
    .lab-menu-sep { height: 1px; margin: 4px 6px; background: rgba(255, 255, 255, 0.08); }

    /* "Write it up" nudge shown after a lab is marked solved */
    .solve-nudge {
      position: fixed;
      left: 50%;
      bottom: 24px;
      z-index: 1090;
      transform: translateX(-50%);
      display: flex;
      align-items: center;
      gap: 12px;
      max-width: calc(100vw - 32px);
      padding: 10px 10px 10px 16px;
      background: #0f172a;
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: 12px;
      box-shadow: 0 16px 40px rgba(0, 0, 0, 0.6);
      color: #e2e8f0;
      font-size: 0.85rem;
      animation: nudgeIn 0.22s ease-out;
    }
    @keyframes nudgeIn { from { opacity: 0; transform: translate(-50%, 10px); } to { opacity: 1; transform: translateX(-50%); } }
    .solve-nudge > i { color: #34d399; font-size: 1.1rem; }
    .solve-nudge.is-warn { border-color: rgba(251, 191, 36, 0.4); }
    .solve-nudge.is-warn > i { color: #fbbf24; }
    .solve-nudge span { min-width: 0; }
    .solve-nudge a {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
      padding: 7px 12px;
      border-radius: 9px;
      background: #0284c7;
      border: 0;
      color: #ffffff;
      font-weight: 600;
      font-size: 0.8rem;
      text-decoration: none;
    }
    .solve-nudge a:hover { background: #0ea5e9; color: #ffffff; }
    .solve-nudge button {
      border: 0;
      background: transparent;
      color: #94a3b8;
      font-size: 1rem;
      padding: 4px 6px;
      cursor: pointer;
    }
    .solve-nudge button:hover { color: #fff; }
    @media (max-width: 480px) {
      .solve-nudge { flex-wrap: wrap; bottom: 80px; width: calc(100vw - 32px); }
    }

    /* State chips beside the difficulty tags (shown by .is-solved / .is-bookmarked) */
    .lab-state-chip {
      display: none;
      align-items: center;
      gap: 4px;
      font-size: 0.8rem;
      font-weight: 600;
      line-height: 1.5;
    }
    .lab-state-chip.chip-bm { color: #fbbf24; }
    .lab-card.is-bookmarked .chip-bm { display: inline-flex; }

    #backToTopBtn:hover {
      transform: translateY(-4px) scale(1.08);
      box-shadow: 0 6px 25px rgba(16, 185, 129, 0.6) !important;
    }

    .btn-outline-success {
      border-color: var(--accent-green);
      color: var(--accent-green);
    }

    .btn-outline-success:hover {
      background-color: var(--accent-green);
      border-color: var(--accent-green);
      color: #1a202c;
    }

    .stats-card {
      background: rgba(30, 41, 59, 0.75);
      border-radius: 16px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      padding: 1.25rem 1rem;
      backdrop-filter: blur(8px);
      transition: all 0.3s ease;
    }

    .stats-card:hover {
      transform: translateY(-3px);
      border-color: rgba(56, 189, 248, 0.4);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }

    .stats-number {
      font-family: var(--font-heading);
      font-size: 2.2rem;
      font-weight: 800;
      background: linear-gradient(135deg, #34d399, #38bdf8);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 0.2rem;
    }

    .stats-label {
      color: #cbd5e1;
      font-size: 0.82rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    a:hover {
      color: inherit;
    }

    /* ---- Lab list (one bordered list per bug type, PortSwigger-style rows) ---- */
    :root {
      --ll-surface: #0d1526;
      --ll-row-hover: #111c33;
      --ll-line: rgba(255, 255, 255, 0.07);
      --ll-text: #f1f5f9;
      --ll-muted: #94a3b8;
      --ll-faint: #64748b;
      --ll-accent: #38bdf8;
      --ll-accent-strong: #0284c7;
    }

    .category-title {
      font-family: var(--font-heading);
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--ll-text);
      margin: 2.25rem 0 0.75rem;
      padding: 0;
      letter-spacing: -0.2px;
      scroll-margin-top: calc(var(--kp-nav-h, 71px) + 12px);
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }

    .category-writeup-link {
      font-family: var(--font-primary);
      font-size: 0.85rem;
      font-weight: 500;
      color: var(--ll-accent);
      display: inline-flex;
      align-items: center;
      gap: 4px;
      white-space: nowrap;
    }

    .category-writeup-link:hover { color: #7dd3fc; text-decoration: underline; text-underline-offset: 3px; }

    .labs-list {
      display: flex;
      flex-direction: column;
      margin-bottom: 1rem;
      background: var(--ll-surface);
      border: 1px solid rgba(255, 255, 255, 0.09);
      border-radius: 12px;
      overflow: hidden;
    }

    .lab-card {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 14px 16px 14px 12px;
      background: transparent;
      cursor: pointer;
      position: relative;
      transition: background 0.12s ease;
    }

    .lab-card + .lab-card { border-top: 1px solid var(--ll-line); }
    .lab-card:hover { background: var(--ll-row-hover); }
    .lab-card:hover .lab-title { color: #ffffff; }
    .lab-card:hover .btn-ACCESS { border-color: rgba(56, 189, 248, 0.6); color: #ffffff; }

    /* Number on the left; becomes a green check when solved */
    .lab-badge {
      flex: 0 0 auto;
      width: 36px;
      height: 36px;
      border-radius: 9px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: var(--font-mono);
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--ll-muted);
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--ll-line);
    }

    /* Solved labs: the whole row turns green, so they're easy to spot while scrolling */
    .lab-card.is-solved { background: rgba(52, 211, 153, 0.07); box-shadow: inset 3px 0 0 #34d399; }
    .lab-card.is-solved:hover { background: rgba(52, 211, 153, 0.11); }
    .lab-card.is-solved .lab-badge { color: #052e1f; background: #34d399; border-color: #34d399; font-weight: 700; }
    .lab-card.is-solved .lab-title { color: #d1fae5; }

    .lab-content {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .lab-title {
      font-family: var(--font-primary);
      font-size: 0.98rem;
      font-weight: 600;
      line-height: 1.4;
      color: #e2e8f0;
      letter-spacing: -0.005em;
    }

    .lab-meta {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 6px 12px;
      font-size: 0.8rem;
      color: var(--ll-muted);
    }

    /* Difficulty: small tinted text badge, colour only here */
    .difficulty-tag {
      display: inline-flex;
      align-items: center;
      font-size: 0.74rem;
      font-weight: 600;
      line-height: 1.5;
      padding: 0 8px;
      border-radius: 6px;
      color: #34d399;
      background: rgba(52, 211, 153, 0.1);
      border: 1px solid rgba(52, 211, 153, 0.28);
    }
    .difficulty-tag.medium { color: #fbbf24; background: rgba(251, 191, 36, 0.1); border-color: rgba(251, 191, 36, 0.3); }
    .difficulty-tag.hard { color: #f87171; background: rgba(248, 113, 113, 0.1); border-color: rgba(248, 113, 113, 0.3); }
    .difficulty-tag.secure { color: #7dd3fc; background: rgba(56, 189, 248, 0.1); border-color: rgba(56, 189, 248, 0.3); }

    /* Link to the original public report: a neutral chip, same size as the difficulty badge.
       The report number is in the tooltip, not on the chip. */
    .report-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 0.74rem;
      font-weight: 500;
      line-height: 1.5;
      padding: 0 8px;
      border-radius: 6px;
      color: #cbd5e1;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.12);
      text-decoration: none;
      transition: color 0.12s ease, border-color 0.12s ease, background 0.12s ease;
    }
    .report-badge > .bi-file-earmark-text { font-size: 0.78rem; color: #94a3b8; }
    .report-badge .rb-ext { font-size: 0.66rem; opacity: 0.6; }
    .report-badge:hover { color: #e0f2fe; border-color: rgba(56, 189, 248, 0.5); background: rgba(56, 189, 248, 0.1); }
    .report-badge:hover > i { color: inherit; opacity: 1; }
    .report-badge:focus-visible { outline: 2px solid var(--ll-accent); outline-offset: 2px; }

    .lab-action {
      flex: 0 0 auto;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* "Open lab": calm outlined button, the same blue as the rest of the site */
    .btn-ACCESS {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 14px;
      font-size: 0.85rem;
      font-weight: 600;
      color: #e2e8f0;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.16);
      border-radius: 9px;
      text-decoration: none;
      white-space: nowrap;
      cursor: pointer;
      transition: background 0.12s ease, border-color 0.12s ease, color 0.12s ease;
    }
    .btn-ACCESS i { font-size: 0.8rem; }
    .btn-ACCESS:hover,
    .lab-card:hover .btn-ACCESS:hover { background: var(--ll-accent-strong); border-color: var(--ll-accent-strong); color: #ffffff; }
    .btn-ACCESS:focus-visible { outline: 2px solid var(--ll-accent); outline-offset: 2px; }

    @media (max-width: 768px) {
      .lab-card { flex-wrap: wrap; gap: 10px 12px; padding: 14px; }
      .lab-badge { width: 30px; height: 30px; font-size: 0.75rem; }
      .lab-content { flex: 1 1 calc(100% - 46px); }
      .lab-action { flex: 1 1 100%; padding-left: 42px; }
      .lab-action .btn-ACCESS { flex: 1; justify-content: center; }
    }

        /* Modern Vexium Dark Fixed Left Sidebar */
    .portswigger-sidebar {
      position: fixed !important;
      top: var(--kp-nav-h, 71px) !important;
      left: 0 !important;
      bottom: 0 !important;
      width: 340px !important;
      background: rgba(11, 19, 36, 0.98) !important;
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
      z-index: 1010 !important;
      overflow-y: auto !important;
      overflow-x: hidden !important;
      display: flex !important;
      flex-direction: column !important;
      box-shadow: 6px 0 25px rgba(0, 0, 0, 0.45) !important;
    }

    .portswigger-sidebar::-webkit-scrollbar {
      width: 5px;
    }
    .portswigger-sidebar::-webkit-scrollbar-track {
      background: rgba(7, 11, 20, 0.4);
    }
    .portswigger-sidebar::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.15);
      border-radius: 4px;
    }
    .portswigger-sidebar::-webkit-scrollbar-thumb:hover {
      background: rgba(255, 255, 255, 0.3);
    }

    .main-content-layout {
      margin-left: 340px !important;
      width: calc(100% - 340px) !important;
      padding: 2.2rem 3rem 4rem 3rem !important;
    }

    /* Modern Floating Sidebar Item Style */
    .portswigger-sidebar .sidebar-item,
    button.sidebar-item {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      box-sizing: border-box !important;
      width: calc(100% - 16px) !important;
      margin: 3px 8px !important;
      padding: 9px 12px !important;
      color: #94a3b8 !important;
      font-size: 0.88rem !important;
      font-weight: 500 !important;
      text-decoration: none !important;
      background: transparent !important;
      border: 1px solid transparent !important;
      border-radius: 8px !important;
      outline: none !important;
      transition: all 0.18s ease-in-out !important;
      cursor: pointer !important;
      text-align: left !important;
    }

    .portswigger-sidebar .sidebar-item:hover,
    button.sidebar-item:hover {
      background: rgba(255, 255, 255, 0.05) !important;
      color: #ffffff !important;
      transform: translateX(2px);
    }

    .portswigger-sidebar .sidebar-item.active-item,
    button.sidebar-item.active-item {
      background: rgba(56, 189, 248, 0.12) !important;
      border: 1px solid transparent !important;
      color: #7dd3fc !important;
      font-weight: 600 !important;
      box-shadow: none !important;
    }

    .sidebar-item .item-title {
      display: flex !important;
      align-items: center !important;
      gap: 8px !important;
      color: inherit !important;
      flex: 1 1 auto !important;
      min-width: 0 !important;
      overflow: hidden !important;
      text-overflow: ellipsis !important;
      white-space: nowrap !important;
      font-size: 0.88rem !important;
    }

    .sidebar-item .sidebar-icon {
      width: 17px;
      height: 17px;
      flex: 0 0 17px;
      margin-right: 4px;
      color: #64748b;
      transition: color 0.18s ease;
    }

    .sidebar-item .item-label {
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .sidebar-item:hover .sidebar-icon {
      color: #cbd5e1;
    }

    .sidebar-item.active-item .sidebar-icon {
      color: #38bdf8;
    }

    .sidebar-item .item-count {
      font-size: 0.86rem !important;
      font-weight: 600 !important;
      padding: 0 !important;
      background: none !important;
      color: #7c8aa0 !important;
      border: none !important;
      flex: 0 0 auto !important;
      margin-left: 8px !important;
      font-family: 'JetBrains Mono', monospace !important;
      transition: color 0.2s ease;
      display: inline-block !important;
      text-align: right !important;
    }

    .sidebar-item:hover .item-count {
      color: #cbd5e1 !important;
    }

    .sidebar-item.active-item .item-count {
      color: #38bdf8 !important;
      font-weight: 800 !important;
    }
    /* Categories drawer toggle + backdrop (only shown below 992px) */
    .sidebar-toggle-btn {
      display: none;
      position: fixed;
      left: 16px;
      bottom: 18px;
      z-index: 1025;
      align-items: center;
      gap: 8px;
      background: #0284c7;
      border: 0;
      color: #ffffff;
      font-weight: 600;
      font-size: 0.88rem;
      padding: 10px 18px;
      border-radius: 30px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
      cursor: pointer;
    }

    .sidebar-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 1009;
      background: rgba(3, 7, 15, 0.6);
      backdrop-filter: blur(2px);
    }

    @media (min-width: 992px) and (max-width: 1199.98px) {
      .portswigger-sidebar {
        width: 280px !important;
      }
      .main-content-layout {
        margin-left: 280px !important;
        width: calc(100% - 280px) !important;
        padding: 2rem 1.75rem 4rem 1.75rem !important;
      }
    }

    @media (max-width: 991.98px) {
      .portswigger-sidebar {
        width: min(320px, 86vw) !important;
        transform: translateX(-105%);
        transition: transform 0.28s ease;
        visibility: hidden;
      }
      body.sidebar-open .portswigger-sidebar {
        transform: translateX(0);
        visibility: visible;
      }
      body.kp-resizing .portswigger-sidebar {
        transition: none !important;
      }
      body.sidebar-open .sidebar-backdrop {
        display: block;
      }
      body.sidebar-open {
        overflow: hidden;
      }
      body.sidebar-open .sidebar-toggle-btn {
        display: none;
      }
      /* Hidden while scrolling down (reading), back when scrolling up */
      .sidebar-toggle-btn { transition: transform 0.25s ease, opacity 0.25s ease; }
      body.kp-scroll-down .sidebar-toggle-btn { transform: translateY(160%); opacity: 0; pointer-events: none; }
      .sidebar-toggle-btn {
        display: inline-flex;
      }
      .main-content-layout {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 1.5rem 1rem 5rem 1rem !important;
      }
    }

    @media (max-width: 768px) {
      .category-title {
        font-size: 1.1rem;
        margin: 1.75rem 0 0.6rem 0;
      }
      .lab-title {
        font-size: 0.95rem;
      }
    }

    @media (max-width: 480px) {
      .main-content-layout {
        padding: 1.25rem 0.75rem 5rem 0.75rem !important;
      }
    }


    @media (min-width: 992px) {
      .krazeplanet-footer {
        margin-left: 340px !important;
        width: calc(100% - 340px) !important;
      }
    }

  </style>
</head>

<body>
  <?php include __DIR__ . '/navbar/navbar.php'; ?>

  <!-- Modern KrazePlanet Fixed Left Sidebar -->
  <aside class="portswigger-sidebar">
    <!-- Vertical Topic Items Container -->
    <div id="categorySidebarList" class="nav flex-column py-2 pb-4">
      <!-- Dynamically populated vertical topic items -->
    </div>
  </aside>

  <!-- Mobile/tablet: categories drawer toggle + backdrop -->
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
  <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" aria-label="Open categories">
    <i class="bi bi-grid-3x3-gap-fill"></i> Categories
  </button>

  <!-- Right Main Content Area -->
  <main class="main-content-layout">

<?php
  // Numbers for the hero: labs per difficulty and how many recreate a public HackerOne report.
  $diffTotals = ['easy' => 0, 'medium' => 0, 'hard' => 0, 'secure' => 0];
  $h1Count = 0;
  foreach ($categories as $catLabs) {
      foreach ((array)$catLabs as $lab) {
          $d = strtolower((string)($lab['difficulty'] ?? ''));
          $d = $d === 'high' ? 'hard' : $d;
          if (isset($diffTotals[$d])) $diffTotals[$d]++;
          if (preg_match('~hackerone\.com/reports/\d+~', (string)($lab['report'] ?? ''))) $h1Count++;
      }
  }
  $signedIn = krazeLoggedIn();
?>
    <section class="hero">
      <div class="hero-main">
        <!-- Shown to everyone, so it's clear straight away that the labs are free -->
        <div class="hero-eyebrow"><span class="dot"></span> Free · Hands-on web security labs</div>
<?php if ($signedIn): ?>
        <!-- Returning student: a personal greeting and their next lab, ready to open -->
        <h1 class="hero-title">Welcome back, <?= $e($_SESSION['username'] ?? 'hacker') ?></h1>
        <div class="next-card" id="heroNext" hidden>
          <div class="nc-label" id="heroNextLabel">Up next</div>
          <div class="nc-title" id="heroNextTitle"></div>
          <div class="nc-meta">
            <span class="difficulty-tag" id="heroNextDiff"></span>
            <span class="nc-cat" id="heroNextCat"></span>
            <span class="nc-when" id="heroNextWhen" hidden></span>
          </div>
          <div class="nc-actions">
            <button type="button" class="hero-btn hero-btn-primary" id="heroOpenNext">Open lab <i class="bi bi-arrow-up-right"></i></button>
            <button type="button" class="nc-link" id="heroShowNext">Show in list</button>
          </div>
        </div>
        <div class="next-card nc-done" id="heroAllDone" hidden>
          <div class="nc-title">You've solved every lab 🎉</div>
          <div class="nc-cat">New labs are added regularly. Check back soon.</div>
        </div>
<?php else: ?>
        <h1 class="hero-title">Learn web hacking by finding real bugs</h1>
        <p class="hero-subtitle">Every lab hides a real vulnerability. Find it yourself, then check the solution.</p>
        <div class="hero-actions">
          <button type="button" class="hero-btn hero-btn-primary" id="heroStartBtn">
            <i class="bi bi-play-fill"></i> <span>Start your first lab</span>
          </button>
          <a class="hero-btn hero-btn-ghost" href="<?= $e(krazeLoginUrl('/index.php', 'signup')) ?>">Create free account</a>
        </div>
<?php endif; ?>
      </div>

      <aside class="hero-panel">
<?php if ($signedIn): ?>
        <div class="hp-label">Your progress</div>
        <div class="hp-progress">
          <!-- Ring: solved / all labs -->
          <div class="hp-ring" role="img" id="heroRingWrap" aria-label="0 of <?= (int)$labTotal ?> labs solved">
            <svg viewBox="0 0 120 120" aria-hidden="true">
              <defs>
                <linearGradient id="hpRingGrad" x1="0" y1="0" x2="1" y2="1">
                  <stop offset="0%" stop-color="#38bdf8"/>
                  <stop offset="100%" stop-color="#34d399"/>
                </linearGradient>
              </defs>
              <circle class="hp-ring-track" cx="60" cy="60" r="52"/>
              <circle class="hp-ring-fill" id="heroRing" cx="60" cy="60" r="52" pathLength="100" stroke-dasharray="100" stroke-dashoffset="100"/>
            </svg>
            <div class="hp-ring-center">
              <div class="hp-ring-num" id="heroSolved">0</div>
              <div class="hp-ring-of">of <?= (int)$labTotal ?></div>
              <div class="hp-ring-pct"><span id="heroPct">0</span>% solved</div>
            </div>
          </div>
          <!-- Per difficulty -->
          <div class="hp-diffs">
<?php foreach (['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard', 'secure' => 'Secure'] as $dk => $dl): ?>
            <div class="hp-diff <?= $dk ?>">
              <div class="hp-diff-top"><span><?= $dl ?></span><span><strong id="heroSolved<?= $dl ?>">0</strong><span class="hp-diff-of"> / <?= $diffTotals[$dk] ?></span></span></div>
              <div class="hp-diff-bar"><span id="heroBar<?= $dl ?>" data-total="<?= $diffTotals[$dk] ?>"></span></div>
            </div>
<?php endforeach; ?>
          </div>
        </div>
<?php else: ?>
        <div class="hp-stats">
          <div><div class="hp-num"><?= (int)$labTotal ?></div><div class="hp-cap">hands-on labs</div></div>
          <div><div class="hp-num"><?= count($categories) ?></div><div class="hp-cap">bug types</div></div>
          <div><div class="hp-num"><?= (int)$h1Count ?></div><div class="hp-cap">from public HackerOne reports</div></div>
        </div>
        <div class="hp-split">
          <div><span class="hp-dot easy"></span>Easy <strong><?= $diffTotals['easy'] ?></strong></div>
          <div><span class="hp-dot medium"></span>Medium <strong><?= $diffTotals['medium'] ?></strong></div>
          <div><span class="hp-dot hard"></span>Hard <strong><?= $diffTotals['hard'] ?></strong></div>
          <div><span class="hp-dot secure"></span>Secure <strong><?= $diffTotals['secure'] ?></strong></div>
        </div>
<?php endif; ?>
      </aside>
    </section>

    <!-- Search and filters -->
    <div class="lab-toolbar">
      <div class="lt-search">
        <i class="bi bi-search"></i>
        <input type="search" id="labSearchInput" placeholder="Search labs, e.g. IDOR, password reset, GraphQL" autocomplete="off" aria-label="Search labs">
        <button type="button" id="clearSearchBtn" class="lt-clear" aria-label="Clear search" style="display:none"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="lt-filters" role="group" aria-label="Difficulty">
        <button type="button" class="lt-chip is-on" data-diff="all">All</button>
        <button type="button" class="lt-chip" data-diff="easy"><span class="hp-dot easy"></span>Easy</button>
        <button type="button" class="lt-chip" data-diff="medium"><span class="hp-dot medium"></span>Medium</button>
        <button type="button" class="lt-chip" data-diff="hard"><span class="hp-dot hard"></span>Hard</button>
        <button type="button" class="lt-chip" data-diff="secure" title="Secure versions of the labs: see how the bug is fixed"><span class="hp-dot secure"></span>Secure</button>
      </div>
    </div>

    <!-- No Results State -->
    <div id="noResultsState" style="display: none; text-align: center; padding: 4rem 1rem; background: rgba(30, 41, 59, 0.4); border-radius: 16px; border: 1px dashed #334155; margin-bottom: 2rem;">
      <i class="bi bi-search text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
      <h4 class="mt-3 text-light">No matching labs found</h4>
      <p class="text-muted mb-3">Try checking for typos or searching with different keywords.</p>
      <button type="button" id="resetSearchBtn" class="btn btn-outline-success rounded-pill px-4">
        Reset Search
      </button>
    </div>

<?php
  // $categories, category icons and learn helpers are loaded at the top of this file.
  $difficultyClass = ['medium' => 'medium', 'hard' => 'hard', 'high' => 'hard', 'secure' => 'secure'];

  foreach ($categories as $category => $catLabs):
?>
    <h3 class="category-title" data-category-name="<?= $e($category) ?>">
      <span class="cat-name"><?= $e($category) ?></span>
      <a class="category-writeup-link" href="<?= $e($blogUrl($category)) ?>" target="_blank" rel="noopener noreferrer">Write-up <i class="bi bi-arrow-up-right"></i></a>
    </h3>
    <div class="labs-list">
<?php foreach ($catLabs as $lab):
        $report = trim($lab['report'] ?? '');
        $diffCls = $difficultyClass[strtolower($lab['difficulty'])] ?? 'easy';
        $isH1 = (bool)preg_match('~hackerone\.com/reports/(\d+)~', $report, $m);
        $reportLabel = $isH1 ? 'HackerOne report' : 'Bug bounty tip';
        $reportTitle = $isH1 ? 'Read the original HackerOne report #' . $m[1] : 'Read the bug bounty tip this lab is based on';
?>
      <div class="lab-card" data-lab-slug="<?= $e($labSlug($lab['access'])) ?>">
        <div class="lab-badge" title="Lab number"><span class="lab-num">LAB</span></div>
        <div class="lab-content">
          <div class="lab-title"><?= $e($lab['title']) ?></div>
          <div class="lab-meta">
            <span class="difficulty-tag <?= $diffCls ?>"><?= $e(ucfirst(strtolower($lab['difficulty']))) ?></span>
<?php if ($report !== ''): ?>
            <a href="<?= $e($report) ?>" target="_blank" rel="noopener noreferrer" class="report-badge" title="<?= $e($reportTitle) ?>"><i class="bi bi-file-earmark-text"></i><span class="rb-label"><?= $e($reportLabel) ?></span><i class="bi bi-arrow-up-right rb-ext"></i></a>
<?php endif; ?>
            <span class="lab-state-chip chip-bm" title="Bookmarked"><i class="bi bi-bookmark-fill"></i> Saved</span>
          </div>
        </div>
        <div class="lab-action">
          <a href="<?= $e(krazeLabUrl($lab['access'])) ?>" data-lab-id="<?= $e($lab['access']) ?>" class="btn-ACCESS" target="_blank">Open lab <i class="bi bi-arrow-up-right"></i></a>
        </div>
      </div>
<?php endforeach; ?>
    </div>
<?php endforeach; ?>
  </main>


  <?php include __DIR__ . '/footer/footer.php'; ?>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa"
    crossorigin="anonymous"></script>
  
  <!-- Script for Search, Filters, MySQL Auth & Lab Tracking -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('labSearchInput');
      const clearBtn = document.getElementById('clearSearchBtn');
      const resetBtn = document.getElementById('resetSearchBtn');
      const noResultsState = document.getElementById('noResultsState');
      const categorySelect = document.getElementById('categorySelect');
      const difficultySelect = document.getElementById('difficultySelect');
      const categoryPillsContainer = document.getElementById('categoryPillsContainer');
      const categoryTitles = document.querySelectorAll('.category-title');
      const userProgressBar = document.getElementById('userProgressBar');
      const userProgressText = document.getElementById('userProgressText');

      const allLabCards = document.querySelectorAll('.lab-card');
      const totalLabs = allLabCards.length;

      // Number the labs 1..N across the whole list (the number turns into a ✓ once solved)
      allLabCards.forEach((card, idx) => {
        const num = card.querySelector('.lab-num');
        if (num) num.textContent = idx + 1;
        const badge = card.querySelector('.lab-badge');
        if (badge) badge.title = 'Lab ' + (idx + 1);
      });

      // The whole row opens the lab (links and buttons inside it keep their own action)
      document.addEventListener('click', (e) => {
        const card = e.target.closest('.lab-card');
        if (!card || e.target.closest('a, button, input, .lab-menu')) return;
        if (window.getSelection && String(window.getSelection()).length) return; // selecting text
        card.querySelector('a.btn-ACCESS')?.click();
      });

      let selectedCategory = 'all';
      let selectedDifficulty = 'all';

      // User State variables (synced via MySQL backend)
      let isLoggedIn = false;
      let currentUsername = '';
      let solvedLabs = [];
      let bookmarkedLabs = [];
      let serverLastLab = null; // { id, at } - last lab opened on any device (central login server)

      // PHP-inlined session user id — lets us key the localStorage cache per user
      const KP_SESSION_UID = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
      // Visitors who aren't signed in are sent to sign in (then straight back to
      // the lab they picked) when they open a lab or use Bookmark / Solved / Report.
      const IS_USER_LOGGED_IN = <?php echo krazeLoggedIn() ? 'true' : 'false'; ?>;
      const KP_STATE_KEY   = 'kp_lab_state_' + KP_SESSION_UID;

      function kpSaveStateCache() {
        if (!KP_SESSION_UID) return;
        try {
          localStorage.setItem(KP_STATE_KEY, JSON.stringify({ solvedLabs, bookmarkedLabs }));
        } catch (_) {}
      }

      function kpClearStateCache() {
        try { localStorage.removeItem(KP_STATE_KEY); } catch (_) {}
      }

      // Apply cached state instantly before the network fetch returns
      if (KP_SESSION_UID) {
        try {
          const cached = JSON.parse(localStorage.getItem(KP_STATE_KEY) || 'null');
          if (cached) {
            solvedLabs = cached.solvedLabs || [];
            bookmarkedLabs = cached.bookmarkedLabs || [];
          }
        } catch (_) {}
      }

      // Fetch User State from Backend MySQL Database
      function fetchUserState() {
        fetch('/navbar/portal.php?action=get_state')
          .then(res => res.json())
          .then(res => {
            if (res.success && res.data) {
              isLoggedIn = res.data.loggedIn;
              currentUsername = res.data.username;
              solvedLabs = res.data.solvedLabs || [];
              bookmarkedLabs = res.data.bookmarkedLabs || [];
              serverLastLab = res.data.lastLab || null;

              if (isLoggedIn) {
                kpSaveStateCache();
              } else {
                kpClearStateCache();
              }

              updateAllLabCardsUI();
              updateProgressBar();
              updateBookmarkTabCount();
              updateSearch();
            }
          })
          .catch(err => console.error('Error fetching user state:', err));
      }

      function updateAllLabCardsUI() {
        allLabCards.forEach((card) => {
          const labLink = card.querySelector('a.btn-ACCESS')?.getAttribute('data-lab-id') || '';
          if (!labLink) return;

          const isSolved = solvedLabs.includes(labLink);
          const isBookmarked = bookmarkedLabs.includes(labLink);

          if (isSolved) card.classList.add('is-solved');
          else card.classList.remove('is-solved');

          if (isBookmarked) card.classList.add('is-bookmarked');
          else card.classList.remove('is-bookmarked');
          // The state chips next to the difficulty tags are shown by these two classes (CSS)
        });
      }

      // Tracks in-flight toggle requests per lab so rapid clicks cancel the stale one
      const solvedToggleControllers = {};
      const bookmarkToggleControllers = {};

      // Bookmark / Solved state changes, called from the per-lab actions menu
      function promptLoginIfNeeded() {
        if (typeof IS_USER_LOGGED_IN !== 'undefined' && !IS_USER_LOGGED_IN) {
          if (typeof openLoginModal === 'function') {
            openLoginModal();
          } else {
            const modalEl = document.getElementById('loginModal');
            if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
          }
          return true;
        }
        return false;
      }

      function toggleBookmark(targetLabId) {
          // Abort any in-flight request for this lab
          if (bookmarkToggleControllers[targetLabId]) {
            bookmarkToggleControllers[targetLabId].abort();
          }
          const abortCtrl = new AbortController();
          bookmarkToggleControllers[targetLabId] = abortCtrl;

          // Snapshot for revert
          const prevSolvedLabs = [...solvedLabs];
          const prevBookmarkedLabs = [...bookmarkedLabs];

          // Optimistic UI update — toggle immediately
          if (bookmarkedLabs.includes(targetLabId)) {
            bookmarkedLabs = bookmarkedLabs.filter(id => id !== targetLabId);
          } else {
            bookmarkedLabs = [...bookmarkedLabs, targetLabId];
          }
          updateAllLabCardsUI();
          updateBookmarkTabCount();
          updateSearch();

          const formData = new FormData();
          formData.append('lab_id', targetLabId);

          fetch('/navbar/portal.php?action=toggle_bookmark', { method: 'POST', body: formData, signal: abortCtrl.signal })
            .then(res => res.json())
            .then(res => {
              delete bookmarkToggleControllers[targetLabId];
              if (res.requireLogin) {
                solvedLabs = prevSolvedLabs;
                bookmarkedLabs = prevBookmarkedLabs;
                updateAllLabCardsUI();
                updateBookmarkTabCount();
                updateSearch();
                if (typeof openLoginModal === 'function') {
                  openLoginModal();
                } else {
                  const modalEl = document.getElementById('loginModal');
                  if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
                return;
              }
              if (res.success && res.data) {
                solvedLabs = res.data.solvedLabs || [];
                bookmarkedLabs = res.data.bookmarkedLabs || [];
                kpSaveStateCache();
                updateAllLabCardsUI();
                updateBookmarkTabCount();
                updateSearch();
              } else {
                solvedLabs = prevSolvedLabs;
                bookmarkedLabs = prevBookmarkedLabs;
                updateAllLabCardsUI();
                updateBookmarkTabCount();
                updateSearch();
              }
            })
            .catch(err => {
              if (err.name === 'AbortError') return;
              delete bookmarkToggleControllers[targetLabId];
              solvedLabs = prevSolvedLabs;
              bookmarkedLabs = prevBookmarkedLabs;
              updateAllLabCardsUI();
              updateBookmarkTabCount();
              updateSearch();
            });
      }

      function toggleSolved(targetLabId, card) {
          // Abort any in-flight request for this lab (rapid clicking sends stale toggles)
          if (solvedToggleControllers[targetLabId]) {
            solvedToggleControllers[targetLabId].abort();
          }
          const abortCtrl = new AbortController();
          solvedToggleControllers[targetLabId] = abortCtrl;

          // Snapshot current state so we can revert on server error
          const prevSolvedLabs = [...solvedLabs];
          const prevBookmarkedLabs = [...bookmarkedLabs];
          const marking = !solvedLabs.includes(targetLabId);

          if (marking) {
            // Marking as solved needs the lab's report in the student's GitHub repo,
            // so wait for the server's check before turning the row green.
            showToast({ icon: 'bi-hourglass-split', text: 'Checking your report on GitHub…', sticky: true });
          } else {
            // Un-marking is instant
            solvedLabs = solvedLabs.filter(id => id !== targetLabId);
            updateAllLabCardsUI();
            updateProgressBar();
            updateSearch();
          }

          const formData = new FormData();
          formData.append('lab_id', targetLabId);

          let diffText = 'easy';
          const diffTags = Array.from(card.querySelectorAll('.difficulty-tag'));
          for (const t of diffTags) {
            const txt = t.textContent.trim().toLowerCase();
            if (t.classList.contains('hard') || txt === 'hard' || txt.includes('hard')) {
              diffText = 'hard';
              break;
            } else if (t.classList.contains('medium') || txt === 'medium' || txt.includes('medium')) {
              diffText = 'medium';
            }
          }
          formData.append('difficulty', diffText);

          fetch('/navbar/portal.php?action=toggle_solved', { method: 'POST', body: formData, signal: abortCtrl.signal })
            .then(res => res.json())
            .then(res => {
              delete solvedToggleControllers[targetLabId];
              if (res.requireLogin) {
                // Revert optimistic update and show login
                solvedLabs = prevSolvedLabs;
                bookmarkedLabs = prevBookmarkedLabs;
                updateAllLabCardsUI();
                updateProgressBar();
                updateSearch();
                if (typeof openLoginModal === 'function') {
                  openLoginModal();
                } else {
                  const modalEl = document.getElementById('loginModal');
                  if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
                return;
              }
              if (res.success && res.data) {
                // Sync with authoritative server state
                solvedLabs = res.data.solvedLabs || [];
                bookmarkedLabs = res.data.bookmarkedLabs || [];
                kpSaveStateCache();
                updateAllLabCardsUI();
                updateProgressBar();
                updateSearch();
                if (marking) showToast({ icon: 'bi-check-circle-fill', text: 'Report found. Lab marked as solved.' });
                else hideToast();
              } else {
                // Not saved — put the row back as it was
                solvedLabs = prevSolvedLabs;
                bookmarkedLabs = prevBookmarkedLabs;
                updateAllLabCardsUI();
                updateProgressBar();
                updateSearch();
                const slug = card.getAttribute('data-lab-slug') || 'lab';
                if (res.needsReport === 'no_repo') {
                  showToast({ icon: 'bi-github', tone: 'warn', text: res.message,
                    href: LAB_LINKS.connectUrl, label: 'Add GitHub username', labelIcon: 'bi-person-gear' });
                } else if (res.needsReport === 'no_report') {
                  showToast({ icon: 'bi-file-earmark-x', tone: 'warn', text: res.message,
                    href: LAB_LINKS.reportRepo ? reportUrl(slug, card) : LAB_LINKS.connectUrl,
                    label: 'Write Report', labelIcon: 'bi-file-earmark-text', newTab: !!LAB_LINKS.reportRepo });
                } else {
                  showToast({ icon: 'bi-exclamation-triangle', tone: 'warn', text: res.message || 'Could not save. Please try again.' });
                }
              }
            })
            .catch(err => {
              if (err.name === 'AbortError') return; // new click took over — UI already correct
              delete solvedToggleControllers[targetLabId];
              // Network error — revert
              solvedLabs = prevSolvedLabs;
              bookmarkedLabs = prevBookmarkedLabs;
              updateAllLabCardsUI();
              updateProgressBar();
              updateSearch();
              showToast({ icon: 'bi-exclamation-triangle', tone: 'warn', text: 'Network error. Please try again.' });
            });
      }

      // ---- Per-lab actions menu (Bookmark, Mark Solved, Solution, Report) ----
      // One shared menu lives in <body>: lab cards use overflow:hidden and a hover
      // transform, which would clip or mis-position a menu placed inside them.
      const LAB_LINKS = <?= json_encode($labLinks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) ?>;

      function solutionUrl(slug) {
        return LAB_LINKS.solutionBase + encodeURIComponent(slug);
      }
      // Template with the lab's title and URL already filled in, so students start from something
      function reportBody(card) {
        const title = card.querySelector('.lab-title')?.textContent.trim() || '';
        const url = card.querySelector('a.btn-ACCESS')?.href || '';
        return LAB_LINKS.reportTemplate
          .replace(/^## Title\n/m, `## Title\n${title}\n`)
          .replace(/^## Description\n/m, `## Description\n**Affected URL:** ${url}\n`);
      }
      function reportUrl(slug, card) {
        const q = new URLSearchParams({ filename: slug + '.md', value: reportBody(card) });
        return `https://github.com/${LAB_LINKS.reportRepo}/new/${LAB_LINKS.reportBranch}?${q}`;
      }

      const labMenu = document.createElement('div');
      labMenu.id = 'labActionsMenu';
      labMenu.className = 'lab-menu';
      labMenu.setAttribute('role', 'menu');
      labMenu.hidden = true;
      document.body.appendChild(labMenu);
      let labMenuTrigger = null;

      // Focus goes back to the ⋯ button only for keyboard users (mouse users would
      // otherwise see the button stay outlined after the menu closes).
      let labMenuByKeyboard = false;
      function closeLabMenu(returnFocus) {
        if (labMenu.hidden) return;
        labMenu.hidden = true;
        if (labMenuTrigger) {
          labMenuTrigger.setAttribute('aria-expanded', 'false');
          labMenuTrigger.closest('.lab-card')?.classList.remove('menu-open');
          if (returnFocus && labMenuByKeyboard) labMenuTrigger.focus();
          else labMenuTrigger.blur();
        }
        labMenuTrigger = null;
      }

      function positionLabMenu(trigger) {
        const r = trigger.getBoundingClientRect();
        const mw = labMenu.offsetWidth, mh = labMenu.offsetHeight;
        let left = Math.min(r.right - mw, window.innerWidth - mw - 8);
        left = Math.max(8, left);
        let top = r.bottom + 6;
        if (top + mh > window.innerHeight - 8 && r.top - mh - 6 > 8) top = r.top - mh - 6;
        labMenu.style.left = left + 'px';
        labMenu.style.top = top + 'px';
      }

      function openLabMenu(trigger) {
        const card = trigger.closest('.lab-card');
        const labId = trigger.getAttribute('data-lab-id');
        const slug = card.getAttribute('data-lab-slug');
        const bm = bookmarkedLabs.includes(labId);
        const solved = solvedLabs.includes(labId);

        labMenu.innerHTML = `
          <div class="lab-menu-row">
            <button type="button" role="menuitemcheckbox" aria-checked="${bm}" class="lab-menu-item${bm ? ' is-on-bm' : ''}" data-act="bookmark" title="${bm ? 'Remove from saved labs' : 'Save this lab for later'}">
              <i class="bi ${bm ? 'bi-bookmark-fill' : 'bi-bookmark'}"></i><span>${bm ? 'Saved' : 'Save'}</span>
            </button>
            <button type="button" role="menuitemcheckbox" aria-checked="${solved}" class="lab-menu-item${solved ? ' is-on-solved' : ''}" data-act="solved" title="${solved ? 'Click to mark as unsolved' : 'Mark as solved (your report for this lab must be in your KrazePlanet-Reports repository)'}">
              <i class="bi ${solved ? 'bi-check-circle-fill' : 'bi-circle'}"></i><span>Solved</span>
            </button>
          </div>
          <div class="lab-menu-sep" role="separator"></div>
          <a role="menuitem" class="lab-menu-item" data-act="solution" href="${solutionUrl(slug)}" target="_blank" rel="noopener noreferrer" title="Step-by-step walkthrough">
            <i class="bi bi-lightbulb"></i><span>Solution</span><i class="bi bi-box-arrow-up-right ext"></i>
          </a>
          ${LAB_LINKS.reportRepo
            ? `<a role="menuitem" class="lab-menu-item" data-act="report" href="${reportUrl(slug, card)}" target="_blank" rel="noopener noreferrer" title="New report file in ${LAB_LINKS.reportRepo}">
                 <i class="bi bi-file-earmark-text"></i><span>Write Report</span><i class="bi bi-box-arrow-up-right ext"></i>
               </a>`
            : `<button type="button" role="menuitem" class="lab-menu-item" data-act="report" data-no-repo="1" title="Add your GitHub username first">
                 <i class="bi bi-file-earmark-text"></i><span>Write Report</span>
               </button>`}`;

        labMenu.querySelector('[data-act="bookmark"]').addEventListener('click', () => {
          closeLabMenu(true);
          if (!promptLoginIfNeeded()) toggleBookmark(labId);
        });
        labMenu.querySelector('[data-act="solved"]').addEventListener('click', () => {
          closeLabMenu(true);
          if (promptLoginIfNeeded()) return;
          toggleSolved(labId, card);
        });
        labMenu.querySelectorAll('a.lab-menu-item').forEach(a => a.addEventListener('click', () => closeLabMenu(false)));
        labMenu.querySelector('[data-no-repo]')?.addEventListener('click', () => {
          closeLabMenu(true);
          if (promptLoginIfNeeded()) return;
          showConnectGithubToast();
        });

        labMenuTrigger = trigger;
        trigger.setAttribute('aria-expanded', 'true');
        card.classList.add('menu-open');
        labMenu.hidden = false;
        positionLabMenu(trigger);
      }

      // Small toast at the bottom of the screen: message + one action link + close.
      let solveNudgeTimer = null;
      // The action link is optional; `sticky` keeps it up until replaced (e.g. "Checking…").
      function showToast({ icon, tone, text, href, label, labelIcon, newTab, sticky }) {
        document.getElementById('solveNudge')?.remove();
        clearTimeout(solveNudgeTimer);
        const n = document.createElement('div');
        n.id = 'solveNudge';
        n.className = 'solve-nudge' + (tone === 'warn' ? ' is-warn' : '');
        n.setAttribute('role', 'status');
        const target = newTab ? ' target="_blank" rel="noopener noreferrer"' : '';
        const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        n.innerHTML = `<i class="bi ${icon}"></i>
          <span>${esc(text)}</span>
          ${href ? `<a href="${esc(href)}"${target}><i class="bi ${labelIcon}"></i> ${esc(label)}</a>` : ''}
          <button type="button" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>`;
        const close = () => { clearTimeout(solveNudgeTimer); n.remove(); };
        n.querySelector('button').addEventListener('click', close);
        n.querySelector('a')?.addEventListener('click', close);
        if (!sticky) {
          n.addEventListener('mouseenter', () => clearTimeout(solveNudgeTimer));
          n.addEventListener('mouseleave', () => { solveNudgeTimer = setTimeout(close, 4000); });
          solveNudgeTimer = setTimeout(close, 9000);
        }
        document.body.appendChild(n);
      }

      function hideToast() {
        clearTimeout(solveNudgeTimer);
        document.getElementById('solveNudge')?.remove();
      }

      // No GitHub repo on the profile yet: explain instead of opening GitHub
      function showConnectGithubToast() {
        showToast({ icon: 'bi-github', tone: 'warn',
          text: 'Add your GitHub username and create a KrazePlanet-Reports repository to write reports.',
          href: LAB_LINKS.connectUrl, label: 'Add GitHub username', labelIcon: 'bi-person-gear' });
      }

      allLabCards.forEach((card) => {
        const labLink = card.querySelector('a.btn-ACCESS')?.getAttribute('data-lab-id') || '';
        const labAction = card.querySelector('.lab-action');
        if (!labLink || !labAction) return;

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'btn-lab-menu';
        trigger.setAttribute('data-lab-id', labLink);
        trigger.setAttribute('aria-haspopup', 'menu');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-label', 'More actions');
        trigger.title = 'Bookmark, mark solved, solution, report';
        trigger.innerHTML = `<i class="bi bi-three-dots"></i>`;
        trigger.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          if (labMenuTrigger === trigger) { closeLabMenu(false); return; }
          closeLabMenu(false);
          openLabMenu(trigger);
          labMenuByKeyboard = e.detail === 0;
          if (labMenuByKeyboard) labMenu.querySelector('.lab-menu-item')?.focus(); // opened by keyboard
        });
        labAction.appendChild(trigger);
      });

      document.addEventListener('click', (e) => {
        if (!labMenu.hidden && !labMenu.contains(e.target)) closeLabMenu(false);
      });
      window.addEventListener('resize', () => closeLabMenu(false));
      window.addEventListener('scroll', () => { if (labMenuTrigger) positionLabMenu(labMenuTrigger); }, { passive: true });
      labMenu.addEventListener('keydown', (e) => {
        const items = Array.from(labMenu.querySelectorAll('.lab-menu-item'));
        const i = items.indexOf(document.activeElement);
        if (e.key === 'Escape') { e.preventDefault(); closeLabMenu(true); }
        else if (e.key === 'ArrowDown') { e.preventDefault(); items[(i + 1) % items.length].focus(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); items[(i - 1 + items.length) % items.length].focus(); }
        else if (e.key === 'Tab') closeLabMenu(false);
      });
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeLabMenu(true); });

      // Apply cached state immediately after buttons are in the DOM
      if (solvedLabs.length || bookmarkedLabs.length) {
        updateAllLabCardsUI();
        updateProgressBar();
        updateBookmarkTabCount();
      }

      function updateProgressBar() {
        const solvedCount = solvedLabs.length;
        const percent = totalLabs > 0 ? Math.round((solvedCount / totalLabs) * 100) : 0;

        if (userProgressBar) {
          userProgressBar.style.width = `${percent}%`;
          userProgressBar.setAttribute('aria-valuenow', percent);
        }
        if (userProgressText) {
          userProgressText.textContent = `${solvedCount} / ${totalLabs} Solved (${percent}%)`;
        }
        const dropdownSolved = document.getElementById('dropdownSolvedCount');
        if (dropdownSolved) {
          dropdownSolved.textContent = `${solvedCount} / ${totalLabs}`;
        }
        updateHeroNext();
        // Hero "Your progress" panel (signed-in students only)
        const heroSolved = document.getElementById('heroSolved');
        if (heroSolved) {
          const byDiff = { easy: 0, medium: 0, hard: 0, secure: 0 };
          allLabCards.forEach((card) => {
            const id = card.querySelector('a.btn-ACCESS')?.getAttribute('data-lab-id') || '';
            if (!solvedLabs.includes(id)) return;
            const d = (card.querySelector('.difficulty-tag')?.textContent || '').trim().toLowerCase();
            if (d in byDiff) byDiff[d]++;
          });
          const total = byDiff.easy + byDiff.medium + byDiff.hard + byDiff.secure;
          const pct = totalLabs ? (total / totalLabs) * 100 : 0;
          heroSolved.textContent = total;
          document.getElementById('heroPct').textContent = pct > 0 && pct < 1 ? '<1' : Math.round(pct);
          // A solved lab always shows at least a small arc
          document.getElementById('heroRing').style.strokeDashoffset = String(100 - (total > 0 ? Math.max(pct, 1.5) : 0));
          document.getElementById('heroRingWrap').setAttribute('aria-label', `${total} of ${totalLabs} labs solved`);
          [['easy', 'Easy'], ['medium', 'Medium'], ['hard', 'Hard'], ['secure', 'Secure']].forEach(([k, label]) => {
            document.getElementById('heroSolved' + label).textContent = byDiff[k];
            const bar = document.getElementById('heroBar' + label);
            const t = Number(bar.dataset.total) || 0;
            bar.style.width = (t && byDiff[k] ? Math.max((byDiff[k] / t) * 100, 2) : 0) + '%';
          });
        }
      }

      // Difficulty filter buttons in the toolbar
      const diffChips = document.querySelectorAll('.lt-chip[data-diff]');
      function setDifficulty(diff) {
        selectedDifficulty = diff;
        diffChips.forEach((c) => c.classList.toggle('is-on', c.dataset.diff === diff));
        if (difficultySelect) difficultySelect.value = diff;
        updateSearch();
      }
      diffChips.forEach((chip) => chip.addEventListener('click', () => setDifficulty(chip.dataset.diff)));

      // Hero button: jump to the next unsolved lab (signed in) or the first easy lab (visitors)
      // The lab the hero button leads to: the first unsolved easy lab (then any unsolved lab)
      // for students, the first easy lab for visitors.
      function nextLab() {
        const cards = Array.from(allLabCards);
        const idOf = (c) => c.querySelector('a.btn-ACCESS')?.getAttribute('data-lab-id') || '';
        const diffOf = (c) => (c.querySelector('.difficulty-tag')?.textContent || '').trim().toLowerCase();
        const target = IS_USER_LOGGED_IN
          ? (cards.find((c) => diffOf(c) === 'easy' && !solvedLabs.includes(idOf(c))) || cards.find((c) => !solvedLabs.includes(idOf(c))))
          : cards.find((c) => diffOf(c) === 'easy');
        return target || cards[0] || null;
      }

      // The last lab this student opened, on any device. Offered first in the hero as
      // "Continue where you left off" until it's solved. Saved on the central login server
      // (works across devices) and in this browser (shows instantly, before the server answers).
      // (var, not const: updateHeroNext can run from the early cached-state render above this line)
      var LAST_LAB_KEY = 'kp_last_lab_' + KP_SESSION_UID;
      function rememberLastLab(labId) {
        if (!KP_SESSION_UID || !labId) return;
        const entry = { id: labId, at: Date.now() };
        try { localStorage.setItem(LAST_LAB_KEY, JSON.stringify(entry)); } catch (_) {}
        serverLastLab = entry;
        updateHeroNext();
        const fd = new FormData();
        fd.append('lab_id', labId);
        fetch('/navbar/portal.php?action=set_last_lab', { method: 'POST', body: fd, keepalive: true }).catch(() => {});
      }
      function lastOpenedLab() {
        if (!KP_SESSION_UID) return null;
        let local = null;
        try { local = JSON.parse(localStorage.getItem(LAST_LAB_KEY) || 'null'); } catch (_) {}
        // whichever was opened most recently: this browser's or the server's (another device)
        const saved = [local, serverLastLab].filter((x) => x && x.id).sort((a, b) => (b.at || 0) - (a.at || 0))[0];
        if (!saved || solvedLabs.includes(saved.id)) return null;
        const card = Array.from(allLabCards).find((c) => c.querySelector('a.btn-ACCESS')?.getAttribute('data-lab-id') === saved.id);
        return card ? { card, at: saved.at } : null;
      }
      // What the student's hero card shows: the unsolved lab they last opened, else the next new one
      function heroTarget() {
        const last = lastOpenedLab();
        return last ? { card: last.card, resumed: true, at: last.at } : { card: nextLab(), resumed: false };
      }
      function timeAgo(ms) {
        const s = Math.max(0, (Date.now() - ms) / 1000);
        if (s < 60) return 'just now';
        if (s < 3600) return Math.floor(s / 60) + ' min ago';
        if (s < 86400) return Math.floor(s / 3600) + (Math.floor(s / 3600) === 1 ? ' hour ago' : ' hours ago');
        const d = Math.floor(s / 86400);
        return d === 1 ? 'yesterday' : d + ' days ago';
      }

      function goToNextLab() {
        const target = IS_USER_LOGGED_IN ? heroTarget().card : nextLab();
        if (!target) return;
        if (target.offsetParent === null && resetBtn) resetBtn.click(); // hidden by a filter: clear filters first
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.remove('lab-highlight-pulse');
        void target.offsetWidth;
        target.classList.add('lab-highlight-pulse');
        setTimeout(() => target.classList.remove('lab-highlight-pulse'), 3000);
      }

      // Signed-in hero: the "Up next" card shows the next unsolved lab and opens it directly
      function updateHeroNext() {
        const box = document.getElementById('heroNext');
        if (!box) return;
        const done = document.getElementById('heroAllDone');
        const idOf = (c) => c.querySelector('a.btn-ACCESS')?.getAttribute('data-lab-id') || '';
        const pick = heroTarget();
        const target = pick.card;
        const allSolved = !target || solvedLabs.includes(idOf(target));
        box.hidden = allSolved;
        if (done) done.hidden = !allSolved;
        if (allSolved) return;
        box.classList.toggle('is-resumed', pick.resumed);
        document.getElementById('heroNextLabel').textContent = pick.resumed ? 'Continue where you left off' : 'Up next';
        const when = document.getElementById('heroNextWhen');
        when.textContent = pick.resumed ? 'Opened ' + timeAgo(pick.at) : '';
        when.hidden = !pick.resumed;
        const diffEl = target.querySelector('.difficulty-tag');
        const tag = document.getElementById('heroNextDiff');
        tag.textContent = (diffEl?.textContent || '').trim();
        tag.className = diffEl ? diffEl.className : 'difficulty-tag';
        document.getElementById('heroNextTitle').textContent = target.querySelector('.lab-title')?.textContent.trim() || '';
        document.getElementById('heroNextCat').textContent = target.closest('.labs-list')?.previousElementSibling?.getAttribute('data-category-name') || '';
      }

      document.getElementById('heroStartBtn')?.addEventListener('click', goToNextLab);   // visitors
      document.getElementById('heroShowNext')?.addEventListener('click', goToNextLab);   // students
      document.getElementById('heroOpenNext')?.addEventListener('click', () => {
        heroTarget().card?.querySelector('a.btn-ACCESS')?.click(); // same as the lab's own "Open lab" button
      });
      // Another tab opened a lab: show it here too
      window.addEventListener('storage', (e) => { if (e.key === LAST_LAB_KEY) updateHeroNext(); });
      updateHeroNext();

      const categorySidebarList = document.getElementById('categorySidebarList');

      function updateBookmarkTabCount() {
        const bCount = bookmarkedLabs.length;
        const sidebarBCount = document.getElementById('sidebarBookmarkCount');
        if (sidebarBCount) {
          sidebarBCount.textContent = bCount;
        }
        const bOpt = categorySelect ? categorySelect.querySelector('option[value="bookmarked"]') : null;
        if (bOpt) {
          bOpt.textContent = `Saved labs (${bCount})`;
        }
        const dropdownBookmark = document.getElementById('dropdownBookmarkCount');
        if (dropdownBookmark) {
          dropdownBookmark.textContent = `${bCount}`;
        }
      }

      // 1. Tag and store pristine Category Name on each heading before injecting any admin buttons
      categoryTitles.forEach((catTitle) => {
        if (!catTitle.getAttribute('data-category-name')) {
          const rawName = catTitle.querySelector('span')?.textContent?.trim() || catTitle.childNodes[0]?.textContent?.trim() || catTitle.textContent.trim();
          catTitle.setAttribute('data-category-name', rawName);
        }
      });

      // Dynamically populate Category Dropdown options and Left Sidebar Topics from DOM headings
      // Category icons come from the same PHP list the lab badges use ($categoryIconPaths)
      const sidebarIconPaths = <?= json_encode($categoryIconPaths, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) ?>;
      const sidebarDefaultIcon = <?= json_encode($defaultIconPaths, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) ?>;
      const sidebarIcon = (key) => `<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${sidebarIconPaths[key] || sidebarDefaultIcon}</svg>`;

      if (categoryTitles.length > 0) {
        if (categorySidebarList) {
          categorySidebarList.innerHTML = '';

          // "All Vulnerabilities" item
          const allItem = document.createElement('button');
          allItem.type = 'button';
          allItem.className = 'sidebar-item active-item';
          allItem.setAttribute('data-category', 'all');
          allItem.innerHTML = `<span class="item-title">${sidebarIcon('all')}All Vulnerabilities</span><span class="item-count">${totalLabs}</span>`;
          categorySidebarList.appendChild(allItem);

          // "Saved labs" item (labs the student bookmarked)
          const bookmarkedItem = document.createElement('button');
          bookmarkedItem.type = 'button';
          bookmarkedItem.className = 'sidebar-item';
          bookmarkedItem.setAttribute('data-category', 'bookmarked');
          bookmarkedItem.innerHTML = `<span class="item-title">${sidebarIcon('bookmarked')}Saved labs</span><span class="item-count" id="sidebarBookmarkCount">0</span>`;
          categorySidebarList.appendChild(bookmarkedItem);

          // Category Topics items
          categoryTitles.forEach((catTitle) => {
            const labsList = catTitle.nextElementSibling;
            const count = labsList ? labsList.querySelectorAll('.lab-card').length : 0;
            const catName = catTitle.getAttribute('data-category-name') || catTitle.textContent.trim();

            // Add option to Dropdown
            if (categorySelect) {
              const option = document.createElement('option');
              option.value = catName;
              option.textContent = `${catName} (${count})`;
              categorySelect.appendChild(option);
            }

            // Add Sidebar Item
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'sidebar-item';
            item.setAttribute('data-category', catName);
            item.innerHTML = `<span class="item-title">${sidebarIcon(catName)}<span class="item-label">${catName}</span></span><span class="item-count">${count}</span>`;
            categorySidebarList.appendChild(item);
          });
        }
      }

      // Height of the sticky navbar plus a small gap, used when scrolling headings into view
      // Height covered at the top of the screen: the navbar, plus the search bar when it's sticky (desktop)
      function navOffset() {
        const nav = document.querySelector('nav.navbar');
        let bottom = nav ? nav.getBoundingClientRect().bottom : 71;
        const toolbar = document.querySelector('.lab-toolbar');
        if (toolbar && getComputedStyle(toolbar).position === 'sticky') bottom += toolbar.offsetHeight;
        return bottom + 8;
      }

      // Categories drawer (below 992px the sidebar slides in from the left)
      const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
      const sidebarBackdrop = document.getElementById('sidebarBackdrop');
      const isDrawerMode = () => window.matchMedia('(max-width: 991.98px)').matches;

      function setSidebarOpen(open) {
        // Only one menu at a time: opening the categories drawer closes the navbar's menu drawer
        if (open && window.kpNavDrawer && window.kpNavDrawer.isOpen()) window.kpNavDrawer.close();
        document.body.classList.toggle('sidebar-open', open);
        if (sidebarToggleBtn) sidebarToggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      }

      // ...and opening the navbar's menu drawer closes the categories drawer
      document.addEventListener('kp:navdrawer-open', () => setSidebarOpen(false));

      if (sidebarToggleBtn) sidebarToggleBtn.addEventListener('click', () => setSidebarOpen(true));
      if (sidebarBackdrop) sidebarBackdrop.addEventListener('click', () => setSidebarOpen(false));
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.body.classList.contains('sidebar-open')) setSidebarOpen(false);
      });
      window.addEventListener('resize', () => {
        if (!isDrawerMode() && document.body.classList.contains('sidebar-open')) setSidebarOpen(false);
      });

      function scrollToCategory(catName) {
        if (!catName || catName === 'all') {
          window.scrollTo({ top: 0, behavior: 'smooth' });
          return;
        }

        let targetEl = null;
        categoryTitles.forEach((catTitle) => {
          const name = catTitle.getAttribute('data-category-name') || catTitle.childNodes[0]?.textContent?.trim() || '';
          if (!targetEl && name.toLowerCase() === catName.toLowerCase()) {
            targetEl = catTitle;
          }
        });

        if (targetEl) {
          const rect = targetEl.getBoundingClientRect();
          const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
          const targetY = Math.max(0, rect.top + scrollTop - navOffset());

          window.scrollTo({
            top: targetY,
            behavior: 'smooth'
          });

          targetEl.classList.add('category-highlight-pulse');
          setTimeout(() => {
            targetEl.classList.remove('category-highlight-pulse');
          }, 1600);
        }
      }

      // Handle Sidebar Item click (PortSwigger exact anchor jump behavior)
      function updateSidebarStyles() {
        if (!categorySidebarList) return;
        const items = categorySidebarList.querySelectorAll('.sidebar-item');
        items.forEach(item => {
          if (item.getAttribute('data-category') === selectedCategory) {
            item.classList.add('active-item');
          } else {
            item.classList.remove('active-item');
          }
        });
      }

      // Handle Sidebar Item click (PortSwigger exact anchor jump behavior)
      if (categorySidebarList) {
        categorySidebarList.addEventListener('click', function(e) {
          const targetItem = e.target.closest('.sidebar-item');
          if (!targetItem) return;

          selectedCategory = targetItem.getAttribute('data-category');
          if (categorySelect) categorySelect.value = selectedCategory;

          updateSidebarStyles();
          updateSearch();
          if (isDrawerMode()) setSidebarOpen(false);
          spyPausedUntil = Date.now() + 1000; // let the smooth scroll finish before following again
          scrollToCategory(selectedCategory);
        });
      }

      // The sidebar follows your scrolling: highlight the category you're reading.
      // (Not while "Bookmarked/Saved" is selected - that's a filter, not a place on the page.)
      let spyPausedUntil = 0;
      let spyFrame = 0;
      function followScroll() {
        spyFrame = 0;
        if (!categorySidebarList || selectedCategory === 'bookmarked' || Date.now() < spyPausedUntil) return;
        const line = navOffset() + 12;
        let current = 'all';
        categoryTitles.forEach((t) => {
          if (t.offsetParent !== null && t.getBoundingClientRect().top <= line) current = t.getAttribute('data-category-name');
        });
        let activeEl = null;
        categorySidebarList.querySelectorAll('.sidebar-item').forEach((item) => {
          const on = item.getAttribute('data-category') === current;
          item.classList.toggle('active-item', on);
          if (on) activeEl = item;
        });
        // keep the highlighted item visible inside the sidebar (desktop only)
        if (activeEl && !isDrawerMode()) {
          const box = categorySidebarList.closest('.portswigger-sidebar');
          const r = activeEl.getBoundingClientRect(), b = box.getBoundingClientRect();
          if (r.top < b.top + 8 || r.bottom > b.bottom - 8) box.scrollTop += r.top - b.top - b.height / 2;
        }
      }
      window.addEventListener('scroll', () => { if (!spyFrame) spyFrame = requestAnimationFrame(followScroll); }, { passive: true });

      // Phones: tuck the floating "Categories" button away while scrolling down, show it on scroll up
      let lastScrollY = window.scrollY;
      window.addEventListener('scroll', () => {
        const y = window.scrollY;
        if (Math.abs(y - lastScrollY) < 8) return;
        document.body.classList.toggle('kp-scroll-down', y > lastScrollY && y > 300);
        lastScrollY = y;
      }, { passive: true });

      // Keep the same category/lab at the top of the viewport when the window is resized
      // (e.g. split screen): cards reflow taller, so the old pixel scroll position lands on an earlier category.
      let scrollAnchor = null;
      let isRestoringScroll = false;
      let resizeTimer = null;

      function captureScrollAnchor() {
        if (isRestoringScroll || window.scrollY <= 0) {
          if (!isRestoringScroll) scrollAnchor = null;
          return;
        }
        const els = document.querySelectorAll('.category-title, .lab-card');
        for (const el of els) {
          if (el.offsetParent === null) continue;
          const rect = el.getBoundingClientRect();
          if (rect.bottom > navOffset()) {
            scrollAnchor = { el: el, top: rect.top };
            return;
          }
        }
      }

      function restoreScrollAnchor() {
        if (!scrollAnchor || !document.body.contains(scrollAnchor.el)) return;
        isRestoringScroll = true;
        const delta = scrollAnchor.el.getBoundingClientRect().top - scrollAnchor.top;
        if (Math.abs(delta) > 1) {
          window.scrollTo({ top: window.scrollY + delta, behavior: 'instant' });
        }
        requestAnimationFrame(() => { isRestoringScroll = false; });
      }

      window.addEventListener('scroll', () => {
        if (!resizeTimer) captureScrollAnchor();
      }, { passive: true });

      window.addEventListener('resize', () => {
        restoreScrollAnchor();
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
          restoreScrollAnchor();
          resizeTimer = null;
          captureScrollAnchor();
        }, 150);
      });

      function updateSearch() {
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        
        if (clearBtn) {
          if (query.length > 0) {
            clearBtn.style.display = 'block';
          } else {
            clearBtn.style.display = 'none';
          }
        }

        let visibleCount = 0;

        categoryTitles.forEach((catTitle) => {
          const labsList = catTitle.nextElementSibling;
          if (!labsList || !labsList.classList.contains('labs-list')) return;

          const catName = catTitle.getAttribute('data-category-name') || catTitle.childNodes[0]?.textContent?.trim() || catTitle.textContent.trim();
          // ("Saved labs" is handled per lab below; categories with no saved lab hide themselves)

          const cards = labsList.querySelectorAll('.lab-card');
          const catMatches = catName.toLowerCase().includes(query);

          let visibleCardCountInCategory = 0;

          cards.forEach((card) => {
            const labLink = card.querySelector('a.btn-ACCESS')?.getAttribute('data-lab-id') || '';
            const isBookmarked = bookmarkedLabs.includes(labLink);

            if (selectedCategory === 'bookmarked' && !isBookmarked) {
              card.style.display = 'none';
              return;
            }

            const titleText = card.querySelector('.lab-title')?.textContent.toLowerCase() || '';
            const badgeText = card.querySelector('.lab-badge')?.textContent.toLowerCase() || '';
            const descText = card.querySelector('.lab-desc')?.textContent.toLowerCase() || '';
            const cardFullText = titleText + ' ' + badgeText + ' ' + descText;

            // Difficulty matching
            const diffText = card.querySelector('.difficulty-tag')?.textContent.trim().toLowerCase() || '';
            const isDifficultySelected = (selectedDifficulty === 'all' || diffText.includes(selectedDifficulty.toLowerCase()));

            if (isDifficultySelected && (catMatches || cardFullText.includes(query))) {
              card.style.display = 'flex';
              visibleCardCountInCategory++;
              visibleCount++;
            } else {
              card.style.display = 'none';
            }
          });

          if (visibleCardCountInCategory > 0) {
            catTitle.style.display = '';
            labsList.style.display = 'flex';
          } else {
            catTitle.style.display = 'none';
            labsList.style.display = 'none';
          }
        });

        if (noResultsState) noResultsState.style.display = visibleCount === 0 ? 'block' : 'none';
      }

      if (searchInput) searchInput.addEventListener('input', updateSearch);

      if (clearBtn) {
        clearBtn.addEventListener('click', function() {
          if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
          }
          updateSearch();
        });
      }

      if (resetBtn) {
        resetBtn.addEventListener('click', function() {
          if (searchInput) searchInput.value = '';
          selectedCategory = 'all';
          selectedDifficulty = 'all';
          if (categorySelect) categorySelect.value = 'all';
          if (difficultySelect) difficultySelect.value = 'all';
          document.querySelectorAll('.lt-chip[data-diff]').forEach((c) => c.classList.toggle('is-on', c.dataset.diff === 'all'));
          updateSidebarStyles();
          if (searchInput) searchInput.focus();
          updateSearch();
        });
      }

      // --- Direct Jump to Lab # Functionality ---
      const jumpLabInput = document.getElementById('jumpLabInput');

      function jumpToLab(labNum) {
        if (!labNum) return;
        const targetNum = labNum.toString().trim();

        let foundCard = null;

        allLabCards.forEach((card) => {
          const labNum = card.querySelector('.lab-num')?.textContent.trim() || '';
          const href = card.querySelector('a.btn-ACCESS')?.getAttribute('data-lab-id') || '';

          if (labNum === targetNum.replace(/^lab\s*/i, '') || href === `${targetNum}.php` || href === `${targetNum}`) {
            foundCard = card;
          }
        });

        if (foundCard) {
          if (foundCard.style.display === 'none') {
            if (resetBtn) resetBtn.click();
          }

          foundCard.scrollIntoView({ behavior: 'smooth', block: 'center' });

          foundCard.classList.remove('lab-highlight-pulse');
          void foundCard.offsetWidth;
          foundCard.classList.add('lab-highlight-pulse');
          setTimeout(() => {
            foundCard.classList.remove('lab-highlight-pulse');
          }, 3500);
        } else {
          alert(`Lab #${targetNum} was not found.`);
        }
      }

      if (jumpLabInput) {
        jumpLabInput.addEventListener('keydown', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            jumpToLab(this.value);
          }
        });
      }

      // AJAX Auth Handlers
      const loginForm = document.getElementById('loginForm');
      const loginAlert = document.getElementById('loginAlert');

      if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
          e.preventDefault();
          loginAlert.style.display = 'none';

          const formData = new FormData();
          formData.append('login_input', document.getElementById('loginInput').value);
          formData.append('password', document.getElementById('loginPassword').value);

          fetch('/api/auth_api.php?action=login', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
              if (res.success) {
                const modalEl = document.getElementById('loginModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                loginForm.reset();
                location.reload();
              } else {
                loginAlert.textContent = res.message || 'Login failed.';
                loginAlert.style.display = 'block';
              }
            });
        });
      }

      const signupForm = document.getElementById('signupForm');
      const signupAlert = document.getElementById('signupAlert');

      if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
          e.preventDefault();
          signupAlert.style.display = 'none';

          const formData = new FormData();
          formData.append('username', document.getElementById('signupUsername').value);
          formData.append('email', document.getElementById('signupEmail').value);
          formData.append('password', document.getElementById('signupPassword').value);
          formData.append('confirm_password', document.getElementById('signupConfirmPassword').value);

          fetch('/api/auth_api.php?action=signup_send_otp', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
              if (res.success) {
                const modalEl = document.getElementById('signupModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                signupForm.reset();
                location.reload();
              } else {
                signupAlert.textContent = res.message || 'Signup failed.';
                signupAlert.style.display = 'block';
              }
            });
        });
      }

      // Direct High-Speed Native Lab Navigation on .btn-ACCESS
      document.addEventListener('click', function(e) {
        const accessBtn = e.target.closest('a.btn-ACCESS');
        if (!accessBtn) return;

        const labHref = accessBtn.getAttribute('href') || '';
        if (!labHref || labHref === '#') return;

        if (typeof IS_USER_LOGGED_IN !== 'undefined' && !IS_USER_LOGGED_IN) {
          e.preventDefault();
          e.stopPropagation();
          sessionStorage.setItem('kraze_pending_lab_url', labHref);
          if (typeof openLoginModal === 'function') {
            openLoginModal();
          }
          return;
        }

        // Direct navigation to lab
        window.open(labHref, '_blank');
        e.preventDefault();
        // Remember it, so the hero can offer "Continue where you left off"
        rememberLastLab(accessBtn.getAttribute('data-lab-id') || '');
      });

      document.addEventListener('click', function(e) {
        if (e.target.closest('#logoutBtnNav')) {
          e.preventDefault();
          window.location.href = '/sso/logout.php';
        }
      });

      // Initial Fetch & Count Sync
      fetchUserState();

      // Keep this page in sync with changes made elsewhere, with no reload:
      //  - another tab on this site saved new progress (shared localStorage),
      //  - the student came back with the Back button (page restored from memory),
      //  - the student switched back to this tab (a change may have come from another site).
      const kpTogglesPending = () => Object.keys(solvedToggleControllers).length + Object.keys(bookmarkToggleControllers).length > 0;
      window.addEventListener('storage', (e) => {
        if (e.key !== KP_STATE_KEY || !e.newValue) return;
        try {
          const s = JSON.parse(e.newValue);
          solvedLabs = s.solvedLabs || [];
          bookmarkedLabs = s.bookmarkedLabs || [];
          updateAllLabCardsUI();
          updateProgressBar();
          updateBookmarkTabCount();
          updateSearch();
        } catch (_) {}
      });
      window.addEventListener('pageshow', (e) => { if (e.persisted) fetchUserState(); });
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && !kpTogglesPending()) fetchUserState();
      });


    });

    // --- Floating Back to Top Button Logic ---
    document.addEventListener('DOMContentLoaded', function() {
      const backToTopBtn = document.getElementById('backToTopBtn');

      if (backToTopBtn) {
        window.addEventListener('scroll', function() {
          if (window.scrollY > 350) {
            backToTopBtn.style.display = 'flex';
            backToTopBtn.style.alignItems = 'center';
            backToTopBtn.style.justifyContent = 'center';
          } else {
            backToTopBtn.style.display = 'none';
          }
        });

        backToTopBtn.addEventListener('click', function() {
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
      }
    });
  </script>

  
</body>
</html>
