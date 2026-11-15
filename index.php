<?php
require_once __DIR__ . '/config/sso.php';
krazeRequireLogin();

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
$labSlug = function ($access) {
    if (preg_match('#^/subdomains/([A-Za-z0-9_-]+)#', $access, $m)) return strtolower($m[1]);
    $host = parse_url($access, PHP_URL_HOST);
    return $host ? strtolower(explode('.', $host)[0]) : 'lab';
};

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
    'connectUrl'     => '/navbar/profile.php?tab=profile#github',
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
      background: radial-gradient(circle at 50% 0%, #1e293b 0%, #0f172a 65%, #090d16 100%);
      background-attachment: fixed;
      color: #f8fafc;
      min-height: 100vh;
      font-family: var(--font-primary);
      -webkit-font-smoothing: antialiased;
    }

    h1, h2, h3, h4, h5, h6, .hero-title, .section-title, .category-title {
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

    .hero-title {
      font-size: 2.8rem;
      font-weight: 800;
      background: linear-gradient(135deg, #ffffff 30%, #38bdf8 70%, #34d399 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 1rem;
      letter-spacing: -0.5px;
    }

    .hero-subtitle {
      font-size: 1.15rem;
      color: #94a3b8;
      max-width: 650px;
      margin: 0 auto;
      line-height: 1.6;
    }

    .section-title {
      margin-top: 40px;
      margin-bottom: 25px;
      font-weight: 700;
      font-size: 1.8rem;
      position: relative;
      padding-bottom: 10px;
      color: #f8fafc;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 60px;
      height: 4px;
      background: linear-gradient(90deg, var(--accent-green), #38bdf8);
      border-radius: 2px;
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

    /* Solved Lab Styles */
    .lab-card.is-solved {
      border-color: rgba(16, 185, 129, 0.45) !important;
      background: rgba(16, 185, 129, 0.08) !important;
    }

    .lab-card.is-solved .lab-badge {
      background: rgba(16, 185, 129, 0.2);
      color: #34d399;
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

    @keyframes labPulse {
      0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.8); border-color: var(--accent-green); }
      50% { box-shadow: 0 0 0 15px rgba(16, 185, 129, 0); border-color: var(--accent-green); }
      100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .lab-highlight-pulse {
      animation: labPulse 1.5s ease-in-out 2;
      border-color: var(--accent-green) !important;
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
      width: 38px;
      height: 36px;
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.18);
      color: #cbd5e1;
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
      background: rgba(255, 255, 255, 0.16);
      border-color: rgba(52, 211, 153, 0.55);
      color: #ffffff;
    }
    .btn-lab-menu:focus-visible { outline: 2px solid #34d399; outline-offset: 2px; }
    .lab-card.menu-open { transform: none; }

    .lab-menu {
      position: fixed;
      z-index: 1080;
      width: 228px;
      padding: 8px;
      display: flex;
      flex-direction: column;
      gap: 6px;
      background: rgba(20, 29, 47, 0.97);
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: 14px;
      box-shadow: 0 14px 34px rgba(0, 0, 0, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.06);
      backdrop-filter: blur(12px);
      animation: labMenuIn 0.14s ease-out;
    }
    @keyframes labMenuIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }
    .lab-menu[hidden] { display: none; }
    /* Each item is a pill, same look as the old "Mark Solved" button */
    .lab-menu-item {
      display: flex;
      align-items: center;
      gap: 8px;
      width: 100%;
      padding: 7px 14px;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.18);
      border-radius: 10px;
      color: #cbd5e1;
      font-family: inherit;
      font-size: 0.8rem;
      font-weight: 600;
      text-align: left;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .lab-menu-item:hover,
    .lab-menu-item:focus-visible {
      background: rgba(255, 255, 255, 0.16);
      border-color: rgba(255, 255, 255, 0.3);
      color: #ffffff;
      outline: none;
    }
    .lab-menu-item > i { font-size: 0.95rem; width: 16px; text-align: center; color: var(--ic, currentColor); }
    .lab-menu-item .ext { margin-left: auto; font-size: 0.7rem; opacity: 0.6; color: currentColor; }
    /* One accent per action, so each is recognisable by colour */
    .lab-menu-item[data-act="bookmark"] { --ic: #fbbf24; }
    .lab-menu-item[data-act="solved"]   { --ic: #34d399; }
    .lab-menu-item[data-act="solution"] { --ic: #38bdf8; }
    .lab-menu-item[data-act="report"]   { --ic: #a78bfa; }
    /* Bookmark + Solved are on/off toggles: two compact buttons on one row */
    .lab-menu-row { display: flex; gap: 6px; }
    .lab-menu-row .lab-menu-item { justify-content: center; padding: 7px 8px; gap: 6px; }
    .lab-menu-item.is-on-solved { background: rgba(16, 185, 129, 0.2); border-color: #10b981; color: #34d399; }
    .lab-menu-item.is-on-solved:hover { background: rgba(16, 185, 129, 0.3); color: #6ee7b7; }
    .lab-menu-item.is-on-bm { background: rgba(251, 191, 36, 0.14); border-color: rgba(251, 191, 36, 0.55); color: #fbbf24; }
    .lab-menu-item.is-on-bm:hover { background: rgba(251, 191, 36, 0.24); color: #fcd34d; }
    .lab-menu-sep { height: 1px; margin: 2px 4px; background: rgba(255, 255, 255, 0.1); }

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
      background: rgba(20, 29, 47, 0.98);
      border: 1px solid rgba(16, 185, 129, 0.5);
      border-radius: 14px;
      box-shadow: 0 14px 34px rgba(0, 0, 0, 0.55), 0 0 22px rgba(16, 185, 129, 0.18);
      color: #e2e8f0;
      font-size: 0.85rem;
      animation: nudgeIn 0.22s ease-out;
    }
    @keyframes nudgeIn { from { opacity: 0; transform: translate(-50%, 10px); } to { opacity: 1; transform: translateX(-50%); } }
    .solve-nudge > i { color: #34d399; font-size: 1.1rem; }
    .solve-nudge.is-warn { border-color: rgba(251, 191, 36, 0.55); box-shadow: 0 14px 34px rgba(0, 0, 0, 0.55), 0 0 22px rgba(251, 191, 36, 0.15); }
    .solve-nudge.is-warn > i { color: #fbbf24; }
    .solve-nudge span { min-width: 0; }
    .solve-nudge a {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
      padding: 7px 12px;
      border-radius: 10px;
      background: rgba(167, 139, 250, 0.16);
      border: 1px solid rgba(167, 139, 250, 0.5);
      color: #c4b5fd;
      font-weight: 600;
      font-size: 0.8rem;
      text-decoration: none;
    }
    .solve-nudge a:hover { background: rgba(167, 139, 250, 0.26); color: #ddd6fe; }
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
      font-size: 0.7rem;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 6px;
      line-height: 1.4;
    }
    .lab-state-chip.chip-solved { background: rgba(16, 185, 129, 0.18); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.45); }
    .lab-state-chip.chip-bm { background: rgba(251, 191, 36, 0.14); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.4); padding: 3px 6px; }
    .lab-card.is-solved .chip-solved,
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

    /* Lab Cards Modern Styling */
    .category-title {
      font-size: 1.4rem;
      font-weight: 800;
      color: #ffffff;
      margin: 2.5rem 0 1.25rem 0;
      padding: 0.5rem 0 0.5rem 1.1rem;
      border-left: 4px solid #10b981;
      background: linear-gradient(90deg, rgba(16, 185, 129, 0.15), transparent);
      border-radius: 0 8px 8px 0;
      letter-spacing: -0.3px;
      scroll-margin-top: calc(var(--kp-nav-h, 71px) + 4px);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }

    .category-writeup-link {
      font-family: var(--font-primary);
      font-size: 0.8rem;
      font-weight: 600;
      color: #38bdf8;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 12px;
      border: 1px solid rgba(56, 189, 248, 0.3);
      border-radius: 8px;
      background: rgba(56, 189, 248, 0.08);
      white-space: nowrap;
      transition: all 0.2s ease;
    }

    .category-writeup-link:hover {
      background: rgba(56, 189, 248, 0.18);
      border-color: rgba(56, 189, 248, 0.5);
      color: #7dd3fc;
    }

    .category-writeup-link i { transition: transform 0.2s ease; }
    .category-writeup-link:hover i { transform: translateX(3px); }

    .labs-list {
      display: flex;
      flex-direction: column;
      gap: 0.95rem;
      margin-bottom: 2.5rem;
    }

    .lab-card {
      display: flex;
      background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(20, 29, 47, 0.95) 100%);
      border-radius: 14px;
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-left: 4px solid #10b981;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.08);
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: hidden;
      align-items: center;
      position: relative;
      backdrop-filter: blur(12px);
    }

    .lab-card:hover {
      transform: translateY(-3px);
      background: linear-gradient(135deg, rgba(35, 48, 68, 0.98) 0%, rgba(24, 34, 53, 0.98) 100%);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5), 0 0 25px rgba(52, 211, 153, 0.25);
      border-color: rgba(52, 211, 153, 0.6);
      border-left-color: #34d399;
    }

    .lab-badge {
      background: rgba(15, 23, 42, 0.95);
      color: #ffffff;
      padding: 1.1rem 1.35rem;
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 800;
      font-size: 0.88rem;
      font-family: var(--font-mono);
      letter-spacing: 0.06em;
      text-transform: uppercase;
      align-self: stretch;
      min-width: 120px;
      justify-content: center;
      border-right: 1px solid rgba(255, 255, 255, 0.14);
      white-space: nowrap;
    }

    .lab-badge svg {
      width: 20px;
      height: 20px;
      flex-shrink: 0;
      fill: none;
      stroke: #34d399;
      stroke-width: 2.3;
      filter: drop-shadow(0 0 6px rgba(52, 211, 153, 0.5));
    }

    .lab-content {
      flex: 1;
      padding: 0.9rem 1.35rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 6px;
    }

    
    .points-tag-pill {
      background: rgba(56, 189, 248, 0.15);
      border: 1px solid rgba(56, 189, 248, 0.35);
      color: #38bdf8;
      font-size: 0.7rem;
      font-weight: 800;
      padding: 4px 8px;
      border-radius: 6px;
      letter-spacing: 0.05em;
      font-family: var(--font-mono, monospace);
      display: inline-flex;
      align-items: center;
    }

    .difficulty-tag {
      background: linear-gradient(135deg, #10b981, #047857);
      color: #ffffff;
      font-size: 0.7rem;
      font-weight: 800;
      padding: 4px 10px;
      border-radius: 6px;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      display: inline-flex;
      width: fit-content;
      box-shadow: 0 2px 8px rgba(16, 185, 129, 0.35);
    }

    .difficulty-tag.medium {
      background: linear-gradient(135deg, #f59e0b, #b45309);
      color: #ffffff;
      box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35);
    }

    .difficulty-tag.hard {
      background: linear-gradient(135deg, #f43f5e, #be123c);
      color: #ffffff;
      box-shadow: 0 2px 8px rgba(244, 63, 94, 0.35);
    }

    .difficulty-tag.secure {
      background: linear-gradient(135deg, #0284c7, #0369a1);
      color: #ffffff;
      box-shadow: 0 2px 8px rgba(2, 132, 199, 0.35);
    }

    .lab-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: #ffffff !important;
      display: flex;
      align-items: center;
      gap: 8px;
      font-family: var(--font-primary);
      letter-spacing: -0.01em;
    }

    .lab-title svg {
      width: 16px;
      height: 16px;
      stroke: #cbd5e1;
      transition: transform 0.2s, stroke 0.2s;
    }

    .lab-card:hover .lab-title svg {
      transform: translateX(4px);
      stroke: #34d399;
    }

    .lab-desc {
      font-size: 0.8rem;
      color: #cbd5e1;
      margin-top: 3px;
      font-family: var(--font-mono);
      letter-spacing: 0.01em;
    }

    /* Link to the real HackerOne report. Styled as an outlined link (not a solid
       tag) in sky blue, so it can't be mistaken for the difficulty tag next to it. */
    .report-badge {
      color: #7dd3fc;
      background: rgba(56, 189, 248, 0.08);
      border: 1px solid rgba(56, 189, 248, 0.55);
      padding: 3px 9px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.74rem;
      letter-spacing: 0.01em;
      flex-shrink: 0;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      line-height: 1.35;
      transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
    }
    .report-badge .bi { font-size: 0.85em; }
    .report-badge .rb-num { font-family: var(--font-mono); font-weight: 600; opacity: 0.85; }
    .report-badge .rb-ext { transition: transform 0.15s ease; }
    .report-badge:hover {
      background: rgba(56, 189, 248, 0.2);
      border-color: #38bdf8;
      color: #e0f2fe;
      transform: translateY(-1px);
    }
    .report-badge:hover .rb-label { text-decoration: underline; text-underline-offset: 2px; }
    .report-badge:hover .rb-ext { transform: translate(2px, -2px); }
    .report-badge:focus-visible {
      outline: 2px solid #38bdf8;
      outline-offset: 2px;
    }

    .lab-action {
      padding: 0.85rem 1.25rem;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .btn-ACCESS {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      border: 1px solid rgba(52, 211, 153, 0.4);
      color: #ffffff;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.82rem;
      padding: 8px 18px;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s ease;
      box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
      text-transform: uppercase;
      letter-spacing: 0.04em;
      cursor: pointer;
    }

    .btn-ACCESS:hover {
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
      color: #ffffff;
    }

    .btn-ACCESS svg {
      width: 16px;
      height: 16px;
      fill: none;
      stroke: currentColor;
      stroke-width: 2.2;
    }

    @media (max-width: 768px) {
      .lab-card {
        flex-direction: column;
        align-items: stretch;
        border-radius: 14px;
      }
      .lab-badge {
        border-radius: 14px 14px 0 0;
        border-right: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        padding: 0.75rem 1rem;
      }
      .lab-content {
        padding: 1rem;
      }
      .lab-action {
        padding: 0 1rem 1rem;
        justify-content: flex-start;
      }
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
      background: linear-gradient(90deg, rgba(56, 189, 248, 0.18), rgba(16, 185, 129, 0.08)) !important;
      border: 1px solid rgba(56, 189, 248, 0.3) !important;
      color: #38bdf8 !important;
      font-weight: 600 !important;
      box-shadow: 0 2px 10px rgba(56, 189, 248, 0.12) !important;
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
      background: linear-gradient(135deg, #0284c7, #0369a1);
      border: 1px solid rgba(56, 189, 248, 0.45);
      color: #ffffff;
      font-weight: 700;
      font-size: 0.88rem;
      padding: 10px 18px;
      border-radius: 30px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5), 0 0 16px rgba(56, 189, 248, 0.3);
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
      .hero-title {
        font-size: 2rem;
      }
      .hero-subtitle {
        font-size: 1rem;
      }
      .section-title {
        font-size: 1.45rem;
        margin-top: 28px;
      }
      .category-title {
        font-size: 1.2rem;
        margin: 2rem 0 1rem 0;
      }
      .lab-badge {
        justify-content: flex-start;
      }
      .lab-title {
        font-size: 1rem;
      }
    }

    @media (max-width: 480px) {
      .hero-title {
        font-size: 1.65rem;
      }
      .main-content-layout {
        padding: 1.25rem 0.75rem 5rem 0.75rem !important;
      }
      .lab-action .btn-ACCESS {
        flex: 1 1 auto;
        justify-content: center;
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

    <div class="hero-section mb-4 text-center">
      <h1 class="hero-title">Web Security Training Platform</h1>
      <p class="hero-subtitle">Master cybersecurity vulnerabilities through hands-on labs designed to challenge and enhance your penetration testing skills.</p>
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

    <h2 class="section-title">Vulnerability Categories</h2>
<?php
  // $categories, category icons and learn helpers are loaded at the top of this file.
  $accessIcon = '<svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>';
  $difficultyClass = ['medium' => 'medium', 'hard' => 'hard', 'high' => 'hard', 'secure' => 'secure'];

  foreach ($categories as $category => $catLabs):
    $icon = $categoryIconSvg($categoryIconPaths[$category] ?? $defaultIconPaths);
?>
    <h3 class="category-title" data-category-name="<?= $e($category) ?>">
      <span><?= $e($category) ?></span>
      <a class="category-writeup-link" href="<?= $e($blogUrl($category)) ?>" target="_blank" rel="noopener noreferrer">Read the write-up <i class="bi bi-box-arrow-up-right"></i></a>
    </h3>
    <div class="labs-list">
<?php foreach ($catLabs as $lab):
        $report = trim($lab['report'] ?? '');
        $diffCls = $difficultyClass[strtolower($lab['difficulty'])] ?? '';
        $isH1 = (bool)preg_match('~hackerone\.com/reports/(\d+)~', $report, $m);
        $reportLabel = $isH1 ? 'HackerOne Report' : 'BugBountyTip';
        $reportNum = $isH1 ? $m[1] : '';
?>
      <div class="lab-card" data-lab-slug="<?= $e($labSlug($lab['access'])) ?>">
        <div class="lab-badge"><?= $icon ?> LAB</div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag <?= $diffCls ?>"><?= $e($lab['difficulty']) ?></span>
<?php if ($report !== ''): ?>
            <a href="<?= $e($report) ?>" target="_blank" rel="noopener noreferrer" class="report-badge" title="Read the original bug report"><i class="bi bi-file-earmark-text"></i><span class="rb-label"><?= $e($reportLabel) ?></span><?php if ($reportNum !== ''): ?><span class="rb-num">#<?= $e($reportNum) ?></span><?php endif; ?><i class="bi bi-box-arrow-up-right rb-ext"></i></a>
<?php endif; ?>
            <span class="lab-state-chip chip-solved"><i class="bi bi-check-circle-fill"></i> Solved</span>
            <span class="lab-state-chip chip-bm" title="Bookmarked"><i class="bi bi-star-fill"></i></span>
          </div>
          <div class="lab-title"><?= $e($lab['title']) ?></div>
        </div>
        <div class="lab-action">
          <a href="<?= $e(krazeLabUrl($lab['access'])) ?>" data-lab-id="<?= $e($lab['access']) ?>" class="btn-ACCESS" target="_blank"><?= $accessIcon ?> ACCESS THE LAB</a>
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
      const resultCount = document.getElementById('searchResultCount');
      const noResultsState = document.getElementById('noResultsState');
      const categorySelect = document.getElementById('categorySelect');
      const difficultySelect = document.getElementById('difficultySelect');
      const categoryPillsContainer = document.getElementById('categoryPillsContainer');
      const categoryTitles = document.querySelectorAll('.category-title');
      const hideSolvedBtn = document.getElementById('hideSolvedBtn');
      const userProgressBar = document.getElementById('userProgressBar');
      const userProgressText = document.getElementById('userProgressText');

      const allLabCards = document.querySelectorAll('.lab-card');
      const totalLabs = allLabCards.length;

      // Dynamically auto-number all lab badges (LAB 1, LAB 2, ..., LAB N)
      allLabCards.forEach((card, idx) => {
        const badge = card.querySelector('.lab-badge');
        if (badge) {
          const svg = badge.querySelector('svg');
          const svgHtml = svg ? svg.outerHTML : '<svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>';
          badge.innerHTML = `${svgHtml} LAB ${idx + 1}`;
        }
      });

      let selectedCategory = 'all';
      let selectedDifficulty = 'all';
      let hideSolved = false;

      // User State variables (synced via MySQL backend)
      let isLoggedIn = false;
      let currentUsername = '';
      let solvedLabs = [];
      let bookmarkedLabs = [];

      // PHP-inlined session user id — lets us key the localStorage cache per user
      const KP_SESSION_UID = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
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

          // Optimistic UI update — toggle immediately without waiting for server
          if (solvedLabs.includes(targetLabId)) {
            solvedLabs = solvedLabs.filter(id => id !== targetLabId);
          } else {
            solvedLabs = [...solvedLabs, targetLabId];
          }
          updateAllLabCardsUI();
          updateProgressBar();
          updateSearch();

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
              } else {
                // Server error — revert
                solvedLabs = prevSolvedLabs;
                bookmarkedLabs = prevBookmarkedLabs;
                updateAllLabCardsUI();
                updateProgressBar();
                updateSearch();
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

      function closeLabMenu(returnFocus) {
        if (labMenu.hidden) return;
        labMenu.hidden = true;
        if (labMenuTrigger) {
          labMenuTrigger.setAttribute('aria-expanded', 'false');
          labMenuTrigger.closest('.lab-card')?.classList.remove('menu-open');
          if (returnFocus) labMenuTrigger.focus();
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
            <button type="button" role="menuitemcheckbox" aria-checked="${bm}" class="lab-menu-item${bm ? ' is-on-bm' : ''}" data-act="bookmark" title="${bm ? 'Remove bookmark' : 'Save this lab for later'}">
              <i class="bi ${bm ? 'bi-star-fill' : 'bi-star'}"></i><span>${bm ? 'Saved' : 'Bookmark'}</span>
            </button>
            <button type="button" role="menuitemcheckbox" aria-checked="${solved}" class="lab-menu-item${solved ? ' is-on-solved' : ''}" data-act="solved" title="${solved ? 'Click to mark as unsolved' : 'Click to mark as solved'}">
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
            : `<button type="button" role="menuitem" class="lab-menu-item" data-act="report" data-no-repo="1" title="Add your GitHub repository first">
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
          if (!solved) showSolveNudge(slug, card);
        });
        labMenu.querySelectorAll('a.lab-menu-item').forEach(a => a.addEventListener('click', () => closeLabMenu(false)));
        labMenu.querySelector('[data-no-repo]')?.addEventListener('click', () => {
          closeLabMenu(true);
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
      function showToast({ icon, tone, text, href, label, labelIcon, newTab }) {
        document.getElementById('solveNudge')?.remove();
        clearTimeout(solveNudgeTimer);
        const n = document.createElement('div');
        n.id = 'solveNudge';
        n.className = 'solve-nudge' + (tone === 'warn' ? ' is-warn' : '');
        n.setAttribute('role', 'status');
        const target = newTab ? ' target="_blank" rel="noopener noreferrer"' : '';
        n.innerHTML = `<i class="bi ${icon}"></i>
          <span>${text}</span>
          <a href="${href}"${target}><i class="bi ${labelIcon}"></i> ${label}</a>
          <button type="button" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>`;
        const close = () => { clearTimeout(solveNudgeTimer); n.remove(); };
        n.querySelector('button').addEventListener('click', close);
        n.querySelector('a').addEventListener('click', close);
        n.addEventListener('mouseenter', () => clearTimeout(solveNudgeTimer));
        n.addEventListener('mouseleave', () => { solveNudgeTimer = setTimeout(close, 4000); });
        document.body.appendChild(n);
        solveNudgeTimer = setTimeout(close, 9000);
      }

      // No GitHub repo on the profile yet: explain instead of opening GitHub
      function showConnectGithubToast() {
        showToast({ icon: 'bi-github', tone: 'warn',
          text: 'Add your GitHub repository on your profile to write bug bounty reports.',
          href: LAB_LINKS.connectUrl, label: 'Add GitHub repo', labelIcon: 'bi-person-gear' });
      }

      // After a lab is marked solved: suggest writing it up as a report
      function showSolveNudge(slug, card) {
        if (!LAB_LINKS.reportRepo) {
          showToast({ icon: 'bi-check-circle-fill',
            text: 'Nice work! Add your GitHub repository to write it up as a bug bounty report.',
            href: LAB_LINKS.connectUrl, label: 'Add GitHub repo', labelIcon: 'bi-github' });
          return;
        }
        showToast({ icon: 'bi-check-circle-fill',
          text: 'Nice work! Write it up as a bug bounty report.',
          href: reportUrl(slug, card), label: 'Write Report', labelIcon: 'bi-file-earmark-text', newTab: true });
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
          if (e.detail === 0) labMenu.querySelector('.lab-menu-item')?.focus(); // opened by keyboard
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
      }

      if (hideSolvedBtn) {
        hideSolvedBtn.addEventListener('click', function() {
          hideSolved = !hideSolved;
          if (hideSolved) {
            this.classList.replace('btn-outline-secondary', 'btn-success');
            this.innerHTML = `<i class="bi bi-eye me-1"></i>Showing Unsolved Only`;
          } else {
            this.classList.replace('btn-success', 'btn-outline-secondary');
            this.innerHTML = `<i class="bi bi-eye-slash me-1"></i>Hide Solved`;
          }
          updateSearch();
        });
      }

      const categorySidebarList = document.getElementById('categorySidebarList');

      function updateBookmarkTabCount() {
        const bCount = bookmarkedLabs.length;
        const sidebarBCount = document.getElementById('sidebarBookmarkCount');
        if (sidebarBCount) {
          sidebarBCount.textContent = bCount;
        }
        const bOpt = categorySelect ? categorySelect.querySelector('option[value="bookmarked"]') : null;
        if (bOpt) {
          bOpt.textContent = `★ Bookmarked Labs (${bCount})`;
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

          // "Bookmarked Labs" item
          const bookmarkedItem = document.createElement('button');
          bookmarkedItem.type = 'button';
          bookmarkedItem.className = 'sidebar-item';
          bookmarkedItem.setAttribute('data-category', 'bookmarked');
          bookmarkedItem.innerHTML = `<span class="item-title">${sidebarIcon('bookmarked')}Bookmarked Labs</span><span class="item-count" id="sidebarBookmarkCount">0</span>`;
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
      function navOffset() {
        const nav = document.querySelector('nav.navbar');
        return (nav ? nav.getBoundingClientRect().bottom : 71) + 4;
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
          scrollToCategory(selectedCategory);
        });
      }

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
          const isCategorySelected = (selectedCategory !== 'bookmarked');

          if (!isCategorySelected) {
            catTitle.style.display = 'none';
            labsList.style.display = 'none';
            return;
          }

          const cards = labsList.querySelectorAll('.lab-card');
          const catMatches = catName.toLowerCase().includes(query);

          let visibleCardCountInCategory = 0;

          cards.forEach((card) => {
            const labLink = card.querySelector('a.btn-ACCESS')?.getAttribute('data-lab-id') || '';
            const isSolved = solvedLabs.includes(labLink);
            const isBookmarked = bookmarkedLabs.includes(labLink);

            if (selectedCategory === 'bookmarked' && !isBookmarked) {
              card.style.display = 'none';
              return;
            }

            if (hideSolved && isSolved) {
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

        if (resultCount) {
          if (query === '' && selectedCategory === 'all' && selectedDifficulty === 'all' && !hideSolved) {
            resultCount.textContent = `Showing all ${totalLabs} labs`;
            if (noResultsState) noResultsState.style.display = 'none';
          } else {
            resultCount.textContent = `Showing ${visibleCount} of ${totalLabs} labs`;
            if (noResultsState) {
              if (visibleCount === 0) {
                noResultsState.style.display = 'block';
              } else {
                noResultsState.style.display = 'none';
              }
            }
          }
        } else if (noResultsState) {
          if (visibleCount === 0) {
            noResultsState.style.display = 'block';
          } else {
            noResultsState.style.display = 'none';
          }
        }
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
          hideSolved = false;
          if (hideSolvedBtn) {
            hideSolvedBtn.className = 'btn btn-sm btn-outline-secondary py-0 px-2';
            hideSolvedBtn.innerHTML = `<i class="bi bi-eye-slash me-1"></i>Hide Solved`;
          }
          if (categorySelect) categorySelect.value = 'all';
          if (difficultySelect) difficultySelect.value = 'all';
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
          const badgeText = card.querySelector('.lab-badge')?.textContent.trim() || '';
          const href = card.querySelector('a.btn-ACCESS')?.getAttribute('data-lab-id') || '';
          
          if (badgeText.toLowerCase() === `lab ${targetNum}` || href === `${targetNum}.php` || href === `${targetNum}`) {
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


      if (totalLabs > 0 && resultCount) {
        resultCount.textContent = `Showing all ${totalLabs} labs`;
      }
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
