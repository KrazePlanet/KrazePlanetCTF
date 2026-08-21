<?php
$is_search = isset($_GET['s']);
$s = $is_search ? $_GET['s'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $is_search ? "Search Results for \"".htmlspecialchars($s)."\"" : "Guidelines for Indian Government Websites and Apps"; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    html {
      scroll-behavior: smooth;
    }
    body {
      font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      background-color: #ffffff;
      color: #222222;
      line-height: 1.5;
    }
    a {
      color: #0066cc;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }

    /* Container */
    .container {
      max-width: 1240px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Top Accessibility & Govt Header */
    .top-bar {
      background-color: #f8f9fa;
      border-bottom: 1px solid #e5e7eb;
      font-size: 0.78rem;
      padding: 6px 0;
      color: #333333;
    }
    .top-bar-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .govt-title {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 700;
      letter-spacing: 0.3px;
    }
    .govt-title span.hindi {
      font-weight: 700;
    }
    .govt-title span.divider {
      color: #999;
      font-weight: 300;
    }
    .top-right-tools {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .tool-icon {
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #333;
      font-size: 0.95rem;
      padding: 2px 4px;
      border-radius: 3px;
    }
    .tool-icon:hover {
      background-color: #e2e8f0;
    }

    /* Main Branding Header */
    .header-main {
      padding: 16px 0;
      background-color: #ffffff;
    }
    .header-flex {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }
    .brand-section {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .emblem-img {
      height: 68px;
      width: auto;
    }
    .brand-titles {
      display: flex;
      flex-direction: column;
    }
    .brand-sub {
      font-size: 0.75rem;
      font-weight: 700;
      color: #555555;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      line-height: 1.2;
    }
    .brand-main-title {
      font-size: 1.25rem;
      font-weight: 800;
      color: #111111;
      letter-spacing: -0.2px;
      text-transform: uppercase;
      line-height: 1.2;
      margin-top: 2px;
    }

    /* Header Search Box */
    .header-search-container {
      flex: 1;
      max-width: 380px;
    }
    .search-fieldset {
      position: relative;
      border: 2px solid #2e1065;
      border-radius: 8px;
      padding: 0 10px;
      height: 44px;
      display: flex;
      align-items: center;
    }
    .search-legend {
      position: absolute;
      top: -10px;
      left: 14px;
      background: #ffffff;
      padding: 0 6px;
      font-size: 0.72rem;
      color: #2e1065;
      font-weight: 600;
    }
    .search-fieldset input[type="text"] {
      border: none;
      outline: none;
      width: 100%;
      font-size: 0.92rem;
      color: #222;
      background: transparent;
      padding-right: 30px;
    }
    .search-fieldset button {
      position: absolute;
      right: 10px;
      background: none;
      border: none;
      cursor: pointer;
      color: #111;
      font-size: 1.1rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Digital India Logo */
    .digital-india-logo {
      height: 52px;
      width: auto;
    }

    /* Navigation Bar */
    .main-nav {
      background-color: #ffffff;
      border-top: 1px solid #e0e0e0;
      border-bottom: 1px solid #e0e0e0;
      box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .nav-list {
      display: flex;
      list-style: none;
      align-items: center;
      flex-wrap: wrap;
    }
    .nav-item {
      position: relative;
    }
    .nav-link {
      display: block;
      padding: 12px 14px;
      font-size: 0.88rem;
      font-weight: 600;
      color: #333333;
      transition: color 0.2s, background-color 0.2s;
    }
    .nav-link:hover {
      color: #2e1065;
      background-color: #f8f9fa;
      text-decoration: none;
    }
    .nav-item.active .nav-link {
      background-color: #2e1065;
      color: #ffffff;
      border-radius: 4px;
    }
    .dropdown-arrow {
      font-size: 0.7rem;
      margin-left: 2px;
    }

    /* ----------------------------------
       HOMEPAGE STYLES
       ---------------------------------- */
    /* Hero Banner Section */
    .hero-banner {
      background: linear-gradient(135deg, #0284c7 0%, #0369a1 50%, #0c4a6e 100%);
      position: relative;
      color: #ffffff;
      padding: 40px 0;
      overflow: hidden;
    }
    .hero-banner::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(rgba(255, 255, 255, 0.15) 1px, transparent 1px);
      background-size: 20px 20px;
      opacity: 0.6;
    }
    .hero-flex {
      position: relative;
      z-index: 2;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 30px;
    }
    .hero-left {
      flex: 1;
      max-width: 580px;
    }
    .hero-title-main {
      font-size: 2.2rem;
      font-weight: 800;
      line-height: 1.25;
      margin-bottom: 20px;
      text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .gigw-badge-strip {
      background: linear-gradient(90deg, #be123c, #e11d48);
      color: #ffffff;
      padding: 12px 18px;
      border-radius: 6px;
      font-weight: 700;
      font-size: 0.92rem;
      margin-bottom: 24px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      line-height: 1.4;
    }
    .hero-icons-row {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .hero-icon-circle {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.15);
      border: 2px solid rgba(255, 255, 255, 0.4);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      color: #ffffff;
      backdrop-filter: blur(4px);
    }

    .hero-right {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .csmop-emblem {
      width: 220px;
      height: 220px;
      border-radius: 50%;
      background: radial-gradient(circle, #be123c 0%, #881337 100%);
      border: 4px solid #ffffff;
      box-shadow: 0 8px 24px rgba(0,0,0,0.3);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 20px;
      color: #ffffff;
    }
    .csmop-emblem-title {
      font-size: 0.72rem;
      font-weight: 800;
      text-transform: uppercase;
      margin-bottom: 6px;
    }
    .csmop-emblem-sub {
      font-size: 0.65rem;
      font-style: italic;
      margin-bottom: 6px;
    }
    .csmop-emblem-tag {
      font-size: 0.65rem;
      font-weight: 700;
      background: #ffffff;
      color: #881337;
      padding: 2px 8px;
      border-radius: 10px;
    }

    .hero-tech-card {
      width: 200px;
      height: 200px;
      border-radius: 50%;
      background: #ffffff;
      padding: 15px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.2);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: #333;
      text-align: center;
    }
    .tech-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      justify-content: center;
      margin-bottom: 10px;
    }
    .tech-tag {
      font-size: 0.62rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 3px;
      color: #fff;
    }
    .tag-html { background: #e34f26; }
    .tag-wcag { background: #0284c7; }
    .tag-css { background: #1572b6; }
    .tag-seo { background: #16a34a; }
    .tag-owasp { background: #475569; }

    /* Slider Controls */
    .slider-nav {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 10px;
      pointer-events: none;
    }
    .slider-arrow {
      pointer-events: auto;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: rgba(0,0,0,0.6);
      color: #ffffff;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 1.1rem;
    }
    .slider-pause {
      position: absolute;
      bottom: 10px;
      left: 20px;
      background: rgba(0,0,0,0.6);
      color: #fff;
      border: none;
      width: 28px;
      height: 28px;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 0.8rem;
    }

    /* Manuals Banner Bar */
    .manuals-bar {
      background-color: #2e1065;
      padding: 24px 0;
      color: #ffffff;
    }
    .manuals-flex {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 40px;
      flex-wrap: wrap;
    }
    .manual-card {
      display: flex;
      align-items: center;
      gap: 16px;
      background: rgba(255, 255, 255, 0.05);
      padding: 12px 20px;
      border-radius: 8px;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .manual-thumb {
      width: 50px;
      height: 64px;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 0.75rem;
      text-align: center;
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }
    .manual-thumb.v3 { background: #f59e0b; color: #111; }
    .manual-thumb.v2 { background: #2563eb; color: #fff; }
    .manual-info {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .manual-title {
      font-weight: 700;
      font-size: 1rem;
    }
    .btn-download {
      display: inline-block;
      background-color: #1e0a45;
      color: #ffffff;
      padding: 5px 14px;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 600;
      border: 1px solid rgba(255,255,255,0.2);
    }
    .btn-download:hover {
      background-color: #3b0764;
      text-decoration: none;
      color: #fff;
    }

    /* Cards Grid Section (Tools, Tips, FAQs) */
    .info-cards-section {
      background-color: #f3f4f6;
      padding: 48px 0;
    }
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 24px;
    }
    .info-card {
      background-color: #ffffff;
      border-radius: 8px;
      padding: 32px 24px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }
    .card-icon-circle {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background-color: #fef08a;
      color: #854d0e;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      margin-bottom: 18px;
    }
    .info-card-title {
      font-size: 1.2rem;
      font-weight: 700;
      color: #111111;
      margin-bottom: 16px;
    }
    .info-card-list {
      list-style: none;
      text-align: left;
      width: 100%;
      margin-bottom: 24px;
      font-size: 0.88rem;
      color: #4b5563;
      flex: 1;
    }
    .info-card-list li {
      position: relative;
      padding-left: 14px;
      margin-bottom: 10px;
      line-height: 1.4;
    }
    .info-card-list li::before {
      content: '•';
      position: absolute;
      left: 0;
      color: #111;
      font-weight: bold;
    }
    .btn-more {
      display: inline-block;
      border: 1px solid #2e1065;
      color: #2e1065;
      padding: 6px 20px;
      border-radius: 4px;
      font-size: 0.82rem;
      font-weight: 700;
      transition: all 0.2s;
    }
    .btn-more:hover {
      background-color: #2e1065;
      color: #ffffff;
      text-decoration: none;
    }

    /* Support Strip Bar */
    .support-strip {
      background-color: #2e1065;
      color: #ffffff;
      padding: 24px 0;
    }
    .support-flex {
      display: flex;
      justify-content: space-around;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
    }
    .support-item {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .support-icon {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: rgba(255,255,255,0.12);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
    }
    .support-text-title {
      font-size: 1.15rem;
      font-weight: 800;
      line-height: 1.2;
    }
    .support-text-sub {
      font-size: 0.78rem;
      opacity: 0.9;
    }

    /* Bottom Features & Showcase Section */
    .bottom-section {
      padding: 48px 0;
      background-color: #ffffff;
    }
    .bottom-grid {
      display: grid;
      grid-template-columns: 1fr 1.2fr;
      gap: 32px;
    }

    /* Compliant Websites Box */
    .compliant-box {
      background-color: #1e293b;
      border-radius: 8px;
      padding: 20px;
      color: #ffffff;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .compliant-title {
      font-size: 1rem;
      font-weight: 800;
      letter-spacing: 0.5px;
      margin-bottom: 16px;
      text-transform: uppercase;
    }
    .website-preview-container {
      width: 100%;
      background: #ffffff;
      border-radius: 6px;
      overflow: hidden;
      margin-bottom: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    .preview-header {
      background: #e2e8f0;
      padding: 8px 12px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
    }
    .dot.r { background: #ef4444; }
    .dot.y { background: #f59e0b; }
    .dot.g { background: #22c55e; }
    .preview-body {
      padding: 20px;
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: #ffffff;
      text-align: center;
      min-height: 180px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .btn-all-compliant {
      background-color: #2e1065;
      color: #ffffff;
      padding: 8px 24px;
      border-radius: 4px;
      font-size: 0.88rem;
      font-weight: 700;
    }
    .btn-all-compliant:hover {
      background-color: #3b0764;
      color: #fff;
      text-decoration: none;
    }

    /* Vertical Feature Cards */
    .features-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .feature-card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 16px 20px;
      display: flex;
      align-items: flex-start;
      gap: 16px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }
    .feature-icon {
      font-size: 2rem;
      color: #0284c7;
      flex-shrink: 0;
    }
    .feature-title {
      font-size: 0.95rem;
      font-weight: 700;
      color: #111111;
      margin-bottom: 4px;
    }
    .feature-desc {
      font-size: 0.82rem;
      color: #6b7280;
      line-height: 1.4;
    }

    /* ----------------------------------
       SEARCH RESULTS PAGE STYLES
       ---------------------------------- */
    .main-body-search {
      padding: 24px 0 60px 0;
      min-height: 50vh;
    }
    .breadcrumb-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.85rem;
      margin-bottom: 24px;
    }
    .breadcrumb-links {
      color: #555555;
    }
    .breadcrumb-links a {
      color: #0066cc;
    }
    .social-share {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .social-icon {
      font-size: 1rem;
      color: #0066cc;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .social-icon.x-logo {
      color: #000000;
      font-weight: bold;
      font-family: sans-serif;
    }
    .results-heading {
      font-size: 1.75rem;
      font-weight: 700;
      color: #111111;
      margin-bottom: 24px;
    }
    .search-panel-gray {
      background-color: #e5e7eb;
      padding: 32px 20px;
      border-radius: 4px;
      margin-bottom: 28px;
    }
    .inner-search-form {
      display: flex;
      justify-content: center;
      align-items: center;
      max-width: 580px;
      margin: 0 auto;
    }
    .inner-search-input {
      flex: 1;
      height: 42px;
      border: 1px solid #d1d5db;
      border-radius: 4px 0 0 4px;
      padding: 0 14px;
      font-size: 0.92rem;
      outline: none;
      background: #ffffff;
      color: #222222;
    }
    .inner-search-btn {
      height: 42px;
      background-color: #2e1065;
      color: #ffffff;
      border: none;
      border-radius: 0 4px 4px 0;
      padding: 0 20px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .no-results-msg {
      font-size: 0.95rem;
      color: #333333;
    }

    /* Footer */
    footer {
      width: 100%;
      position: relative;
    }
    .footer-top {
      background-color: #311b92;
      color: #ffffff;
      padding: 14px 0;
      font-size: 0.88rem;
      text-align: center;
    }
    .footer-links {
      display: flex;
      justify-content: center;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
      list-style: none;
    }
    .footer-links a {
      color: #ffffff;
      font-weight: 500;
    }
    .footer-links a:hover {
      text-decoration: underline;
    }
    .footer-links span.sep {
      color: rgba(255,255,255,0.5);
    }
    .footer-bottom {
      background: linear-gradient(180deg, #1b0a40 0%, #12052c 100%);
      color: #d1d5db;
      padding: 24px 0;
      text-align: center;
      font-size: 0.82rem;
      line-height: 1.7;
    }
    .footer-bottom p {
      margin: 2px 0;
    }
    .footer-bottom .last-updated {
      margin-top: 10px;
      margin-bottom: 20px;
      font-weight: 600;
      color: #ffffff;
    }
    .footer-logos-row {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 30px;
      margin-top: 15px;
      flex-wrap: wrap;
    }
    .footer-logo-box {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 700;
      color: #fff;
    }

    /* Scroll To Top Button */
    .scroll-top-btn {
      position: fixed;
      bottom: 20px;
      right: 20px;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #2e1065;
      color: #ffffff;
      border: 2px solid #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      cursor: pointer;
      box-shadow: 0 4px 10px rgba(0,0,0,0.3);
      z-index: 99;
    }
    .scroll-top-btn:hover {
      background-color: #3b0764;
    }
  </style>
</head>
<body>

  <!-- Top Accessibility Bar -->
  <div class="top-bar">
    <div class="container top-bar-content">
      <div class="govt-title">
        <span class="hindi">भारत सरकार</span>
        <span class="divider">|</span>
        <span>GOVERNMENT OF INDIA</span>
      </div>
      <div class="top-right-tools">
        <span class="tool-icon" title="Accessibility Options"><i class="bi bi-layout-sidebar"></i></span>
        <span class="tool-icon" title="Language Toggle" style="font-weight: 700; font-size: 0.85rem;">अA</span>
        <span class="tool-icon" title="Accessibility Support"><i class="bi bi-person-standing"></i></span>
      </div>
    </div>
  </div>

  <!-- Main Branding Header -->
  <header class="header-main">
    <div class="container header-flex">
      <!-- Left Branding -->
      <div class="brand-section">
        <!-- Ashoka Emblem SVG -->
        <svg class="emblem-img" viewBox="0 0 160 220" xmlns="http://www.w3.org/2000/svg">
          <g fill="#2b2b2b">
            <path d="M 80,10 C 65,10 50,20 45,35 C 40,30 30,35 25,45 C 20,55 25,70 30,80 C 25,85 20,95 25,110 C 30,120 45,125 55,125 C 60,125 65,130 80,130 C 95,130 100,125 105,125 C 115,125 130,120 135,110 C 140,95 135,85 130,80 C 135,70 140,55 135,45 C 130,35 120,30 115,35 C 110,20 95,10 80,10 Z M 80,25 C 90,25 98,32 102,42 C 95,45 88,42 80,42 C 72,42 65,45 58,42 C 62,32 70,25 80,25 Z M 45,55 C 52,55 60,60 65,68 C 55,70 45,68 38,62 C 40,57 42,55 45,55 Z M 115,55 C 118,55 120,57 122,62 C 115,68 105,70 95,68 C 100,60 108,55 115,55 Z M 80,50 C 88,50 94,56 96,64 C 86,66 74,66 64,64 C 66,56 72,50 80,50 Z" />
            <circle cx="80" cy="142" r="14" fill="none" stroke="#2b2b2b" stroke-width="3"/>
            <circle cx="80" cy="142" r="3" fill="#2b2b2b"/>
            <path d="M 80,128 L 80,156 M 66,142 L 94,142 M 70,132 L 90,152 M 70,152 L 90,132" stroke="#2b2b2b" stroke-width="1.5"/>
            <path d="M 35,160 L 125,160 L 120,172 L 40,172 Z"/>
            <text x="80" y="195" font-family="'Open Sans', sans-serif" font-size="15" font-weight="bold" text-anchor="middle" fill="#2b2b2b">सत्यमेव जयते</text>
          </g>
        </svg>
        <div class="brand-titles">
          <span class="brand-sub">Guidelines For</span>
          <span class="brand-main-title">Indian Government Websites and Apps</span>
        </div>
      </div>

      <!-- Center Search -->
      <div class="header-search-container">
        <form action="index.php" method="GET">
          <div class="search-fieldset">
            <span class="search-legend">Search</span>
            <input type="text" name="s" value="<?php echo $s; ?>" placeholder="" autocomplete="off">
            <button type="submit" aria-label="Search">
              <i class="bi bi-search"></i>
            </button>
          </div>
        </form>
      </div>

      <!-- Right Digital India Logo -->
      <div class="digital-india-container">
        <svg class="digital-india-logo" viewBox="0 0 220 70" xmlns="http://www.w3.org/2000/svg">
          <g>
            <path d="M 20,45 Q 15,20 30,12 Q 40,25 25,48 Z" fill="#ff9933"/>
            <path d="M 28,48 Q 32,22 45,18 Q 45,35 32,50 Z" fill="#138808"/>
            <path d="M 35,52 Q 48,28 60,30 Q 52,45 38,54 Z" fill="#000080"/>
            <text x="55" y="32" font-family="'Open Sans', sans-serif" font-size="20" font-weight="800" fill="#003366">Digital India</text>
            <text x="56" y="46" font-family="'Open Sans', sans-serif" font-size="9" font-weight="600" fill="#ff6600" letter-spacing="0.5">Power To Empower</text>
          </g>
        </svg>
      </div>
    </div>
  </header>

  <!-- Navigation Bar -->
  <nav class="main-nav">
    <div class="container">
      <ul class="nav-list">
        <li class="nav-item <?php echo !$is_search ? 'active' : ''; ?>"><a href="index.php" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="#" class="nav-link">GIGW 3.0</a></li>
        <li class="nav-item"><a href="#" class="nav-link">Innovation Challenge <span class="dropdown-arrow">˅</span></a></li>
        <li class="nav-item"><a href="#" class="nav-link">Govt. Communications</a></li>
        <li class="nav-item"><a href="#" class="nav-link">Certification <span class="dropdown-arrow">˅</span></a></li>
        <li class="nav-item"><a href="#" class="nav-link">Resources <span class="dropdown-arrow">˅</span></a></li>
        <li class="nav-item"><a href="#" class="nav-link">GuDApps <span class="dropdown-arrow">˅</span></a></li>
        <li class="nav-item"><a href="#" class="nav-link">Workshop</a></li>
        <li class="nav-item"><a href="#" class="nav-link">CIOs</a></li>
        <li class="nav-item"><a href="#" class="nav-link">WIM Login</a></li>
      </ul>
    </div>
  </nav>

<?php if ($is_search): ?>
  <!-- ================================================================= -->
  <!-- SEARCH RESULTS VIEW                                               -->
  <!-- ================================================================= -->
  <main class="main-body-search">
    <div class="container">
      
      <!-- Breadcrumb & Social Share -->
      <div class="breadcrumb-row">
        <div class="breadcrumb-links">
          <a href="index.php">Home</a> &gt; Search Results for "<?php echo $s; ?>"
        </div>
        <div class="social-share">
          <a href="#" class="social-icon" title="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="#" class="social-icon x-logo" title="X (Twitter)">X</a>
          <a href="#" class="social-icon" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
        </div>
      </div>

      <!-- Page Heading -->
      <h1 class="results-heading">Search Results For : <?php echo $s; ?></h1>

      <!-- Gray Search Panel -->
      <div class="search-panel-gray">
        <form action="index.php" method="GET" class="inner-search-form">
          <input type="text" name="s" class="inner-search-input" value="<?php echo $s; ?>" placeholder="">
          <button type="submit" class="inner-search-btn">
            <i class="bi bi-search"></i> Search
          </button>
        </form>
      </div>

      <!-- Search Results Message -->
      <div class="no-results-msg">
        No post found.
      </div>

    </div>
  </main>

<?php else: ?>
  <!-- ================================================================= -->
  <!-- FULL HOMEPAGE VIEW                                                -->
  <!-- ================================================================= -->
  
  <!-- Hero Banner -->
  <section class="hero-banner">
    <div class="container hero-flex">
      <div class="hero-left">
        <h1 class="hero-title-main">Guidelines for<br>Indian Government Websites</h1>
        <div class="gigw-badge-strip">
          GIGW 3.0 &nbsp;|&nbsp; INCLUDED IN CENTRAL SECRETARIAT MANUAL OF OFFICE PROCEDURE (CSMOP)
        </div>
        <div class="hero-icons-row">
          <div class="hero-icon-circle" title="Quality Assurance"><i class="bi bi-patch-check"></i></div>
          <div class="hero-icon-circle" title="Security Compliance"><i class="bi bi-shield-lock"></i></div>
          <div class="hero-icon-circle" title="Accessibility Standards"><i class="bi bi-person-wheelchair"></i></div>
          <div class="hero-icon-circle" title="Usability Best Practices"><i class="bi bi-speedometer2"></i></div>
        </div>
      </div>

      <div class="hero-right">
        <!-- CSMOP Circular Badge -->
        <div class="csmop-emblem">
          <span class="csmop-emblem-title">Central Secretariat Manual of Office Procedure</span>
          <span class="csmop-emblem-sub">(CSMOP, 2022)</span>
          <span class="csmop-emblem-sub">Enabling The March Towards A Digital Secretariat</span>
          <span class="csmop-emblem-tag">SIXTEENTH EDITION</span>
        </div>
        <!-- Right Graphic Badge -->
        <div class="hero-tech-card">
          <div class="tech-tags">
            <span class="tech-tag tag-html">HTML</span>
            <span class="tech-tag tag-wcag">WCAG</span>
            <span class="tech-tag tag-css">CSS</span>
            <span class="tech-tag tag-seo">SEO</span>
            <span class="tech-tag tag-owasp">OWASP</span>
          </div>
          <i class="bi bi-laptop" style="font-size: 3rem; color: #0284c7;"></i>
        </div>
      </div>
    </div>

    <!-- Slider Arrows -->
    <div class="slider-nav">
      <button class="slider-arrow" aria-label="Previous Slide"><i class="bi bi-chevron-left"></i></button>
      <button class="slider-arrow" aria-label="Next Slide"><i class="bi bi-chevron-right"></i></button>
    </div>
    <button class="slider-pause" aria-label="Pause Auto Play"><i class="bi bi-pause-fill"></i></button>
  </section>

  <!-- Download Manuals Bar -->
  <section class="manuals-bar">
    <div class="container manuals-flex">
      <div class="manual-card">
        <div class="manual-thumb v3">GIGW<br>3.0</div>
        <div class="manual-info">
          <span class="manual-title">GIGW Manual 3.0</span>
          <a href="#" class="btn-download">Download »</a>
        </div>
      </div>

      <div class="manual-card">
        <div class="manual-thumb v2">GIGW<br>2.0</div>
        <div class="manual-info">
          <span class="manual-title">GIGW Manual 2.0</span>
          <a href="#" class="btn-download">Download »</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Tools, Tips & FAQs Section -->
  <section class="info-cards-section">
    <div class="container">
      <div class="cards-grid">
        
        <!-- Card 1 -->
        <div class="info-card">
          <div class="card-icon-circle"><i class="bi bi-tools"></i></div>
          <h2 class="info-card-title">Tools and Resources</h2>
          <ul class="info-card-list">
            <li>Validation Tools: HTML, CSS, Broken Links</li>
            <li>Accessibility</li>
            <li>Mobile Friendliness</li>
            <li>Assistive Technologies</li>
            <li>Screen Reader Access</li>
          </ul>
          <a href="#" class="btn-more">MORE »</a>
        </div>

        <!-- Card 2 -->
        <div class="info-card">
          <div class="card-icon-circle"><i class="bi bi-lightbulb"></i></div>
          <h2 class="info-card-title">Tips and Tricks</h2>
          <ul class="info-card-list">
            <li>Using semantically correct markup</li>
            <li>Designing accessible and usable forms</li>
            <li>Promoting your website</li>
            <li>Providing meaningful alternate descriptions for non text elements</li>
          </ul>
          <a href="#" class="btn-more">MORE »</a>
        </div>

        <!-- Card 3 -->
        <div class="info-card">
          <div class="card-icon-circle"><i class="bi bi-question-circle"></i></div>
          <h2 class="info-card-title">FAQs</h2>
          <ul class="info-card-list">
            <li>Who will assess the compliance of these guidelines?</li>
            <li>Will the website get any certificate upon compliance to these guidelines?</li>
            <li>Are these guidelines mandated by the Government of India?</li>
            <li>What are the benefits of making a website compliant to these guidelines?</li>
          </ul>
          <a href="#" class="btn-more">MORE »</a>
        </div>

      </div>
    </div>
  </section>

  <!-- Support & Contact Strip -->
  <section class="support-strip">
    <div class="container support-flex">
      <div class="support-item">
        <div class="support-icon"><i class="bi bi-gear-fill"></i></div>
        <div>
          <div class="support-text-title">Support</div>
          <div class="support-text-sub">SERVICE</div>
        </div>
      </div>
      <div class="support-item">
        <div class="support-icon"><i class="bi bi-envelope-fill"></i></div>
        <div>
          <div class="support-text-title">Contact</div>
          <div class="support-text-sub">webguidelines[at]nic[dot]in</div>
        </div>
      </div>
      <div class="support-item">
        <div class="support-icon"><i class="bi bi-journal-check"></i></div>
        <div>
          <div class="support-text-title">Guidelines for Indian</div>
          <div class="support-text-sub">Government Websites (GIGW 3.0)</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Bottom Features & Compliant Websites Showcase -->
  <section class="bottom-section">
    <div class="container bottom-grid">
      
      <!-- Compliant Websites -->
      <div class="compliant-box">
        <div class="compliant-title">COMPLIANT WEBSITES</div>
        <div class="website-preview-container">
          <div class="preview-header">
            <span class="dot r"></span>
            <span class="dot y"></span>
            <span class="dot g"></span>
          </div>
          <div class="preview-body">
            <h3 style="font-weight: 800; font-size: 1.1rem; margin-bottom: 6px;">ITU KALEIDOSCOPE</h3>
            <p style="font-size: 0.8rem; opacity: 0.9;">Innovation and digital transformation for a sustainable world</p>
          </div>
        </div>
        <a href="#" class="btn-all-compliant">All Compliant Websites »</a>
      </div>

      <!-- Feature Cards -->
      <div class="features-list">
        
        <div class="feature-card">
          <i class="bi bi-file-earmark-text feature-icon"></i>
          <div>
            <h3 class="feature-title">Guidelines for Development of Applications (GuDApps)</h3>
            <p class="feature-desc">To provide a set of guidelines and best practices through a series of documents addressing different aspects of eGovernance solution development</p>
          </div>
        </div>

        <div class="feature-card">
          <i class="bi bi-shield-check feature-icon"></i>
          <div>
            <h3 class="feature-title">GIGW Compliance & Certification Handbook</h3>
            <p class="feature-desc">As the world adopts the Internet media for delivery of information and services, it becomes necessary to establish standards that serve as a frame.</p>
          </div>
        </div>

        <div class="feature-card">
          <i class="bi bi-file-earmark-pdf feature-icon"></i>
          <div>
            <h3 class="feature-title">Creating Accessible Word and Libre document</h3>
            <p class="feature-desc">Guidelines to create accessible PDFs from MS Word Doc and Libre Office Doc to ensure PDFs are accessible to individuals with disabilities.</p>
          </div>
        </div>

        <div class="feature-card">
          <i class="bi bi-award feature-icon"></i>
          <div>
            <h3 class="feature-title">Get Certified</h3>
            <p class="feature-desc">STQC offers Certified Quality Website (CQW) certification for GIGW compliant websites</p>
          </div>
        </div>

      </div>

    </div>
  </section>

<?php endif; ?>

  <!-- Footer -->
  <footer>
    <div class="footer-top">
      <div class="container">
        <ul class="footer-links">
          <li><a href="#">Help</a></li>
          <span class="sep">|</span>
          <li><a href="#">Website Policies</a></li>
          <span class="sep">|</span>
          <li><a href="#">Contact Us</a></li>
          <span class="sep">|</span>
          <li><a href="#">Link to us</a></li>
          <span class="sep">|</span>
          <li><a href="#">Feedback</a></li>
          <span class="sep">|</span>
          <li><a href="#">FAQs</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="container">
        <p>Content Owned by India Portal Team</p>
        <p>Developed and hosted by National Informatics Centre,</p>
        <p>Ministry of Electronics & Information Technology, Government of India</p>
        <p class="last-updated">Last Updated: Aug 07, 2026</p>
        
        <!-- Footer Logos -->
        <div class="footer-logos-row">
          <div class="footer-logo-box">
            <span style="font-size: 0.75rem; color: #aaa;">Powered By</span>
            <span style="font-size: 1.1rem; font-weight: 800; color: #fff;">SwaaS</span>
          </div>
          <div class="footer-logo-box">
            <span style="font-size: 1.1rem; font-weight: 800; color: #60a5fa;">NIC</span>
            <span style="font-size: 0.72rem; color: #aaa; text-align: left; line-height: 1.1;">National<br>Informatics<br>Centre</span>
          </div>
          <div class="footer-logo-box">
            <span style="font-size: 1.1rem; font-weight: 800; color: #f97316;">Digital India</span>
          </div>
        </div>

      </div>
    </div>
  </footer>

  <!-- Scroll to Top Button -->
  <button class="scroll-top-btn" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" aria-label="Scroll to top">
    <i class="bi bi-arrow-up"></i>
  </button>

</body>
</html>