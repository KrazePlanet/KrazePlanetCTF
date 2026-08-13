<?php session_start(); ?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>VexiumCTF - Security Training Platform</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
  integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="icon" href="favicon.ico" />
  
  <!-- Google Fonts: Inter, Outfit, JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  
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
      border-bottom: 1px solid rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(16px);
      padding: 0.85rem 0;
    }

    .navbar-nav {
      margin: 0 auto;
      gap: 1.5rem;
    }

    .navbar-nav .nav-link {
      color: #cbd5e1 !important;
      font-weight: 500;
      padding: 0.5rem 1rem !important;
      border-radius: 0.5rem;
      transition: all 0.3s;
    }

    .navbar-nav .nav-link:hover,
    .navbar-nav .nav-link.active {
      color: #ffffff !important;
      background: rgba(255, 255, 255, 0.08);
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
      scroll-margin-top: 75px;
    }

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
      padding: 1.1rem 1.5rem;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 800;
      font-size: 0.92rem;
      font-family: var(--font-mono);
      letter-spacing: 0.08em;
      text-transform: uppercase;
      align-self: stretch;
      min-width: 130px;
      justify-content: center;
      border-right: 1px solid rgba(255, 255, 255, 0.14);
    }

    .lab-badge svg {
      width: 20px;
      height: 20px;
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

    .report-badge {
      color: #ffffff;
      background: linear-gradient(135deg, #be185d, #9d174d);
      padding: 3px 8px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.74rem;
      font-family: var(--font-mono);
      letter-spacing: 0.02em;
      flex-shrink: 0;
      box-shadow: 0 2px 8px rgba(190, 24, 93, 0.4);
    }
    .report-badge:hover { background: #9d174d; color: #ffffff; }

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

    /* PortSwigger Full Height Fixed Left Sidebar */
    .portswigger-sidebar {
      position: fixed !important;
      top: 58px !important;
      left: 0 !important;
      bottom: 0 !important;
      width: 280px !important;
      background: #005fb8 !important;
      border-right: 1px solid rgba(255, 255, 255, 0.15) !important;
      z-index: 1010 !important;
      overflow-y: auto !important;
      display: flex !important;
      flex-direction: column !important;
      box-shadow: 4px 0 20px rgba(0, 0, 0, 0.35) !important;
    }

    .portswigger-sidebar::-webkit-scrollbar {
      width: 5px;
    }
    .portswigger-sidebar::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.2);
    }
    .portswigger-sidebar::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.3);
      border-radius: 4px;
    }
    .portswigger-sidebar::-webkit-scrollbar-thumb:hover {
      background: rgba(255, 255, 255, 0.5);
    }

    .main-content-layout {
      margin-left: 280px !important;
      width: calc(100% - 280px) !important;
      padding: 2rem 2.5rem 4rem 2.5rem !important;
    }

    /* PortSwigger Item Button Style Override */
    .portswigger-sidebar .sidebar-item,
    button.sidebar-item {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      width: 100% !important;
      padding: 11px 16px !important;
      color: #e2e8f0 !important;
      font-size: 0.88rem !important;
      font-weight: 500 !important;
      text-decoration: none !important;
      background: transparent !important;
      background-color: transparent !important;
      border: none !important;
      border-left: 4px solid transparent !important;
      border-radius: 0 !important;
      box-shadow: none !important;
      outline: none !important;
      transition: all 0.15s ease !important;
      cursor: pointer !important;
      text-align: left !important;
      margin: 0 !important;
    }

    .portswigger-sidebar .sidebar-item:hover,
    button.sidebar-item:hover {
      background: rgba(255, 255, 255, 0.12) !important;
      background-color: rgba(255, 255, 255, 0.12) !important;
      color: #ffffff !important;
    }

    .portswigger-sidebar .sidebar-item.active-item,
    button.sidebar-item.active-item {
      background: #00366b !important;
      background-color: #00366b !important;
      color: #ffffff !important;
      font-weight: 700 !important;
      border-left: 4px solid #ffffff !important;
    }

    .sidebar-item .item-title {
      display: flex !important;
      align-items: center !important;
      gap: 8px !important;
      color: inherit !important;
      white-space: nowrap !important;
      overflow: hidden !important;
      text-overflow: ellipsis !important;
    }

    .sidebar-item .item-count {
      font-size: 0.76rem !important;
      padding: 2px 8px !important;
      border-radius: 12px !important;
      background: rgba(0, 0, 0, 0.25) !important;
      color: #ffffff !important;
      font-weight: 600 !important;
      flex-shrink: 0 !important;
      margin-left: auto !important;
    }

    .sidebar-item.active-item .item-count {
      background: rgba(255, 255, 255, 0.25) !important;
      color: #ffffff !important;
    }

    @media (max-width: 991.98px) {
      .portswigger-sidebar {
        position: relative !important;
        top: 0 !important;
        width: 100% !important;
        height: auto !important;
        max-height: 420px !important;
        border-right: none !important;
        border-bottom: 1px solid #1e293b !important;
        box-shadow: none !important;
      }
      .main-content-layout {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 1.5rem 1rem 3rem 1rem !important;
      }
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <!-- PortSwigger Style Full-Screen Fixed Left Sidebar -->
  <aside class="portswigger-sidebar">
    
    <!-- Your Lab Progress Section inside top of sidebar -->
    <div class="sidebar-progress-section p-3 border-bottom border-light border-opacity-25" style="background: rgba(0, 0, 0, 0.2);">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-light fw-bold" style="font-size: 0.85rem;">
          <i class="bi bi-trophy-fill text-warning me-2"></i>Progress
        </span>
        <button type="button" id="hideSolvedBtn" class="btn btn-sm btn-outline-light py-0 px-2" style="font-size: 0.72rem; border-radius: 12px; opacity: 0.9;">
          <i class="bi bi-eye-slash me-1"></i>Hide Solved
        </button>
      </div>
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span id="userProgressText" class="fw-bold text-light" style="font-size: 0.8rem;">
          0 / 199 Solved (0%)
        </span>
      </div>
      <div class="progress" style="height: 6px; background: rgba(0, 0, 0, 0.25); border-radius: 6px; overflow: hidden;">
        <div id="userProgressBar" class="progress-bar" role="progressbar" style="width: 0%; background: #ffffff;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
      </div>
    </div>

    <!-- Vertical Topic Items Container -->
    <div id="categorySidebarList" class="nav flex-column pb-4">
      <!-- Dynamically populated vertical topic items -->
    </div>

  </aside>

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

    <!-- Cross-Site Scripting (XSS)-->
    <h3 class="category-title">Cross-Site Scripting (XSS)</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - TutorialRepublic: Web Development Reference Search
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/tutorialrepublic" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - GIGW: Government Website Guidelines Portal
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/guidelines" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - RefSeek: Academic & Scientific Search Engine
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/refseek" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - PubMed: National Library of Medicine Search Builder
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/pubmed" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - BigBasket: Online Supermarket Catalog
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/bigbasket" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Global Site Search Portal
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/search" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Script Tag Filter Evasion
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/feedback" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Multi-Parameter Script Filter Evasion
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/cookbook" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - HTML Tag Blacklist Filter
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/board" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Path Based
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/path-fetch" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Script & Img Tag Filter
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/support" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Case-Insensitive Filter Bypass
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/directory" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Less-Than Sign Filter
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/helpdesk" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - HTML Tag Filter Bypass
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/news" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
            <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag easy">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Page Heading
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/docs" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag easy">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Function Name Filter
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/profile" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Extended Function Filter
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/account" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Event Handler Filter
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/tickets" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Multi-Parameter Filter Evasion
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/checkout" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>

      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Encoding Bypass Attempts
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/mail" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Mixed Security Parameters
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/settings" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - String Concatenation Bypass
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/portal" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
            <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag easy">Easy</span>
            <span class="difficulty-tag" style="background:#059669;color:#fff;">DOM XSS</span>
          </div>
          <div class="lab-title">
            DOM XSS in document.write sink using source location.search
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/useruploads" class="btn-ACCESS" target="_blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <!-- <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - URL Encoding Context
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/dashboard" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div> -->
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Search Filter Bypass
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/kb" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Category Filter
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/shop" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Equifax <a href="https://hackerone.com/reports/1818163" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1818163</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/tracking" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag">Low</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Reflected XSS - PUBG <a href="https://hackerone.com/reports/751870" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#751870</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/assets" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag">Low</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Shopify <a href="https://hackerone.com/reports/1940245" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1940245</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/go" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Imgur Mobile<a href="https://hackerone.com/reports/149855" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#149855</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/media" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Reddit <a href="https://hackerone.com/reports/1549206" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1549206</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/widgets" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Stored XSS - Forum Discussions (Reddit Clone)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/community" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Stored XSS - Product Reviews (Flipkart Clone)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/members" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Stored XSS - Events & Polls Activity Center
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/articles" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Stored XSS - Support Center (DigitalOcean Clone)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/digitalocean" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Stored XSS - Bug Bounty Platform (HackerOne Clone)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/hackerone" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Stored XSS - Video Streaming Service (Netflix Clone)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/netflix" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <!-- <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            CSP Bypass - Unsafe Inline Scripts
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="48.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div> -->
      <!-- <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            CSP Protected Page
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="49.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div> -->
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Stored XSS - Twitter <a href="https://hackerone.com/reports/485748" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#485748</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/ads" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Stored XSS - Shopify <a href="https://hackerone.com/reports/1147433" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1147433</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/cms" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Stored XSS - Acronis <a href="https://hackerone.com/reports/1084183" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1084183</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/forum" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Blind XSS - ZAP-Hosting
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/contact" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Blind Stored XSS - Informatica <a href="https://hackerone.com/reports/1011888" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1011888</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/partners" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            IMDb: Ratings, Reviews, and Where to Watch the Best Movies & TV Shows
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/imdb" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            AniList: Explore, Track, and Discover Anime &amp; Manga
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/anilist" class="btn-ACCESS" target="_blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            MilesWeb: Fast, Secure &amp; Reliable Web Hosting Built for Indian Websites
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/milesweb" class="btn-ACCESS" target="_blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Censys Search - Internet Intelligence Platform
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/censys" class="btn-ACCESS" target="_blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
            <a href="https://hackerone.com/reports/474656" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #474656</a>
          </div>
          <div class="lab-title">
            DOM XSS - HackerOne
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/careers" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
            <a href="https://hackerone.com/reports/324303" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #324303</a>
          </div>
          <div class="lab-title">
            DOM XSS - MyCrypto
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/wallet" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <!-- <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
            <a href="https://hackerone.com/reports/396493" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #396493</a>
          </div>
          <div class="lab-title">
            Reflected DOM XSS via URL + prettyPhoto Hash Chain — Starbucks UK
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
          <div class="lab-desc">?slug= → canonical link attr injection + prettyPhoto jQuery trigger</div>
        </div>
        <div class="lab-action">
          <a href="/cafe" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div> -->
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
            <a href="https://hackerone.com/reports/704266" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #704266</a>
          </div>
          <div class="lab-title">
            DOM XSS - ForeScout Technologies
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/gallery" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
            <a href="https://hackerone.com/reports/1004833" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1004833</a>
          </div>
          <div class="lab-title">
            DOM XSS - Informatica
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/auth" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Self XSS - via POST Parameter
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/forms" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Self XSS - POST-Based Reflected XSS
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/apply" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Self XSS - POST XSS in Input Tag Value
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/survey" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Self XSS - in Document Title
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/reports" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag" style="background:#02a9ff;color:#fff;">Secure</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Reflected XSS - Cloud Instance Console
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/instance" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Stored XSS via Profile Bio &amp; Reflected XSS via Search Bar
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/pixeleet" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 1
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/1.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 2
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/2.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 3
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/3.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 4
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/4.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 5
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/5.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 6
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/6.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
            <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 7
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/7.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 8
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/8.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 9
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/9.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 10
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/10.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 11
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/11.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>

      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 12
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/12.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 13
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/13.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 14
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/14.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 15
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/15.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 16
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/16.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 17
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/17.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 18
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/18.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 19
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/19.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 20
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/20.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 21
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/21.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 22
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/22.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 23
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/23.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 24
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/24.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 25
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/25.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 26
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/26.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 27
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/27.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 28
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/28.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 29
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/29.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 30
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/30.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 31
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/31.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 32
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/32.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 33
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/33.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PUNISHMENT LAB 34
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="punishment/34.php" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>


    <!-- HTML Injection (HTMLI) -->
    <h3 class="category-title">HTML Injection (HTMLI)</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            HTML Injection - E-commerce
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/catalog" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            HTML Injection - LinkedIn <a href="https://hackerone.com/reports/3079966" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#3079966</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/chat" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Stored HTML Injection - Romit <a href="https://hackerone.com/reports/57914" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#57914</a>)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/transfer" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Stored HTML Tag Injection - GitLab <a href="https://hackerone.com/reports/358001" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#358001</a>)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/code" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            HTML Injection - HackerOne <a href="https://hackerone.com/reports/1374017" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1374017</a>)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/notifications" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- Server-Side Template Injection (SSTI) -->
    <h3 class="category-title">Server-Side Template Injection (SSTI)</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Template Engine Code Injection
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/templates" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            SSTI - Glovo <a href="https://hackerone.com/reports/1104349" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1104349</a>)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/onboarding" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            SSTI - Uber <a href="https://hackerone.com/reports/125980" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#125980</a>)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/accounts" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            SSTI - Unikrn <a href="https://hackerone.com/reports/164224" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#164224</a>)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/invite" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- Open Redirect -->
    <h3 class="category-title">Open Redirect</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Basic URL Parameter Redirect
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/redirect" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Open Redirect - Omise <a href="https://hackerone.com/reports/504751" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#504751</a>)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/links" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Open Redirect - Semrush <a href="https://hackerone.com/reports/311330" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#311330</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/out" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag" style="background:#ea580c;color:#fff;">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Open Redirect - Tumblr <a href="https://hackerone.com/reports/2812583" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#2812583</a>)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/sso" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- Authentication Bypass -->
    <h3 class="category-title">Authentication Bypass</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Admin Auth Bypass - UPS <a href="https://hackerone.com/reports/1490470" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1490470</a>)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/adminpanel" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            OTP Verification Bypass via Response Manipulation
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/verify" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Phone OTP Bypass via Response Manipulation
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/mobile" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- SQL Injection (SQLI) -->
    <h3 class="category-title">SQL Injection (SQLI)</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            SQL Injection - Login Bypass
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/login" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            INSERT SQL Injection - Comment System
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/reviews" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            CRUD SQL Injection - Book Management
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/library" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Time-based Blind SQL Injection
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/inventory" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Integer-based SQL Injection
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/orders" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            User-Agent Header Blind SQL Injection
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/stats" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Referer Header Blind SQL Injection
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/affiliate" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            X-Forwarded-For Header Blind SQL Injection
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/proxy" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Blind SQL Injection via Parameter name &mdash; Executive Dashboard
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/executive" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Blind SQL Injection via PATH_INFO &mdash; Industrial Asset Registry
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/registry" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Blind SQL Injection via Filename &mdash; University Course Catalog
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/academic" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Time-based Blind SQLi via sitemap.xml &mdash; ACME Corp Industrial
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/industrial" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Time-based Blind SQLi - Zomato <a href="https://hackerone.com/reports/403616" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#403616</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/menu" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Time-based Blind SQLi - GSA Bounty <a href="https://hackerone.com/reports/297478" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#297478</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/collector" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            UNION-based SQLi - IntenseDebate <a href="https://hackerone.com/reports/1046084" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1046084</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/directoryapi" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Blind SQLi - MTN Group <a href="https://hackerone.com/reports/1069531" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1069531</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/customers" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            ORDER BY SQLi - Grab <a href="https://hackerone.com/reports/273946" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#273946</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/content" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Boolean-blind SQLi - inDrive <a href="https://hackerone.com/reports/2051931" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#2051931</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/api" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Time-Based Blind SQLi - Rocket.Chat <a href="https://hackerone.com/reports/433792" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#433792</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/events" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Boolean-Blind SQLi - Zomato <a href="https://hackerone.com/reports/1044716" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1044716</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/rest" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            UNION-Based SQLi - Acronis <a href="https://hackerone.com/reports/923020" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#923020</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/adminapi" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            UNION SQLi - Automattic <a href="https://hackerone.com/reports/3198980" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#3198980</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/coupons" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Time-Based Blind SQLi - Acronis <a href="https://hackerone.com/reports/1224660" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1224660</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/memberships" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            UNION SQLi - U.S. Dept Of Defense <a href="https://hackerone.com/reports/3127198" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#3127198</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/ajax" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Blind SQLi - Zomato <a href="https://hackerone.com/reports/838855" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#838855</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/banners" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Time-Based Blind SQLi - Automattic <a href="https://hackerone.com/reports/1042746" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1042746</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/moderation" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            String SQLi - U.S. Dept Of Defense <a href="https://hackerone.com/reports/491191" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#491191</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/publications" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Time-Based Blind SQLi - U.S. Dept Of Defense <a href="https://hackerone.com/reports/2312334" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#2312334</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/knowledge" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- Cross-Site Request Forgery (CSRF) -->
    <h3 class="category-title">Cross-Site Request Forgery (CSRF)</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            CSRF Password Change - Unprotected Account Settings
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/security" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            CSRF Email Hijack - Silent Account Takeover
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/preferences" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            CSRF Account Wipe - Irreversible Data Deletion
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/privacy" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            CSRF 2FA Bypass - Silent Security Downgrade
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/mfa" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Login CSRF - HackerOne <a href="https://hackerone.com/reports/834366" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#834366</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/session" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Login CSRF - Unikrn <a href="https://hackerone.com/reports/339352" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#339352</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/identity" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            CSRF - GitLab <a href="https://hackerone.com/reports/1122408" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1122408</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/graphql" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            CSRF - Starbucks <a href="https://hackerone.com/reports/177508" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#177508</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/wishlist" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            CSRF - U.S. Dept of Defense <a href="https://hackerone.com/reports/2712857" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#2712857</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/myaccount" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            CSRF - U.S. Dept of Defense <a href="https://hackerone.com/reports/1118521" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1118521</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/academy" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- Server-Side Request Forgery (SSRF) -->
    <h3 class="category-title">Server-Side Request Forgery (SSRF)</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Source Code Viewer - Basic cURL SSRF
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/viewer" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Screenshot Tool - URL to Image
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/capture" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Port-based Timing Attack
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/scanner" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Domain Restriction Bypass with Redirects
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/fetch" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Website Checker with IP Blacklist
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/monitor" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            AWS Metadata Filter Bypass
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/cloud" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PDF Generator - URL to PDF
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/print" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- Local File Inclusion (LFI) -->
    <h3 class="category-title">Local File Inclusion (LFI)</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Path Traversal - Basic
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/files" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            CMS Local File Inclusion
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/storage" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            File Upload with LFI Vulnerability
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/uploads" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Image Gallery File Inclusion
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/archive" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0v-4a2 2 0 012-2h2a2 2 0 012 2v4"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Local File Inclusion - Corporate Page Routing
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/corporate" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Local File Inclusion - Documentation Portal Engine
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/doc-portal" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Local File Inclusion - Multi-Language Blog CMS
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/multilang-blog" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Local File Inclusion - HR Portal File Preview
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/hr-portal" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Local File Inclusion - Help Desk Attachment Preview
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/helpdesk" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Local File Inclusion - LMS Course Resource Viewer
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/lms" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Local File Inclusion - Hospital EMR Medical Report Viewer
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/hospital" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Local File Inclusion - Real Estate CMS Media Loader
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/realestate" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M5 12l-2 0l9 -9l9 9l-2 0M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7M9 21v-6a2 2 0 012-2h2a2 2 0 012 2v6"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Local File Inclusion - Hosting Panel Log Viewer
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/hosting-panel" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Local File Inclusion - E-Commerce Invoice & Template Renderer
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/ecommerce" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            RecipeBox — Base64-Encoded Path LFI Bypass
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/recipes" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            GovDocs — Double URL Encoding LFI Bypass
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/gov" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Admin Portal — Error Parameter Path Traversal
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/control" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- Remote Code Execution (RCE) -->
    <h3 class="category-title">Remote Code Execution (RCE)</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            OS Command Injection
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/diagnostics" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>

    </div>

    <!-- Insecure Direct Object Reference (IDOR) -->
    <h3 class="category-title">Insecure Direct Object Reference (IDOR)</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            SwiftCart - Insecure Order Invoice Disclosure
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/billing" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
            <a href="https://hackerone.com/reports/150095" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #150095</a>
          </div>
          <div class="lab-title">
            Uber Driver Portal - Trip &amp; Earnings Disclosure
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/driver" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            MediCare+ - Healthcare Records IDOR
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/patient" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            FriendZone - Social Media Profile IDOR
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/social" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            SecureBank - Banking Portal Account IDOR
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/bank" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- Remote File Inclusion (RFI) -->
    <h3 class="category-title">Remote File Inclusion (RFI)</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Remote File Inclusion via URL
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/include" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            RFI + XSS + SSRF via Unvalidated URL Proxy in GIS Portal (U.S. DoD — <a href="https://hackerone.com/reports/192940" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#192940</a>)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/maps" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PageForge CMS — Content Manager Remote &amp; Local File Inclusion
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/pages" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            ShopStream — E-Commerce Bulk Product Import Remote &amp; Local File Inclusion
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/import" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            StreamFlux — Video Analytics CDN Origin Asset Proxy Remote &amp; Local File Inclusion
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/origin" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- XML External Entity (XXE) -->
    <h3 class="category-title">XML External Entity (XXE)</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            XML External Entity (XXE) via URL
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/xml" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            XXE on Twitter SMS SXMP API (File Read via operatorId Error Reflection) — <a href="https://hackerone.com/reports/248668" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#248668</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/sms" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            LFI + SSRF via XXE in SVG Emblem Editor (Rockstar Games ImageMagick) — <a href="https://hackerone.com/reports/347139" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#347139</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/designer" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            Blind XXE via JPEG XMP Metadata Injection (Informatica OOB Exfiltration) — <a href="https://hackerone.com/reports/836877" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#836877</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/images" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
          </div>
          <div class="lab-title">
            XXE via XML Resume Upload Starbucks China Career Portal (IIS + ASP.NET) — <a href="https://hackerone.com/reports/500515" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#500515</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/careers-api" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            XXE via XML Registration API &mdash; SecureVault Password Manager
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/register" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            XXE via XML Login API &mdash; SecureVault Password Manager
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/auth-api" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- Subdomain Takeovers -->
    <h3 class="category-title">Subdomain Takeovers</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Subdomain Takeovers
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="https://vulnera.xyz" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- Business Logic Vulnerabilities -->
    <h3 class="category-title">Business Logic Vulnerabilities</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Business Logic Vulnerability
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/image-api" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>


    <!-- Information Disclosure -->
    <h3 class="category-title">Information Disclosure</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Information Disclosure
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/notifications-api" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>


    <!-- File Upload Vulnerabilities -->
    <h3 class="category-title">File Upload Vulnerabilities</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            File Upload Vulnerabilities
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/file-upload-api" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>


    <!-- Race Conditions -->
    <h3 class="category-title">Race Conditions</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Race Conditions in Booking System
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/booking-api" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>


    <!-- No Rate Limiting -->
    <h3 class="category-title">No Rate Limiting</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            No Rate Limiting
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/codeshackio" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>


    <!-- Parameter Tampering -->
    <h3 class="category-title">Parameter Tampering</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Parameter Tampering
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/parameter-tampering" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>


    <!-- Special Vulnerabilities -->
    <h3 class="category-title">Special Vulnerabilities</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            ControlHub — JSON Response Manipulation Auth Bypass
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/hub" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <!-- LAB 15 -- VaultTech JWT Credential Reuse -->
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            VaultTech — JWT Token Credential Reuse Across Admin Panels
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/vault" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <!-- LAB 16 -- CloudSync PII Leaked on Unauthorized File -->
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            CloudSync — PII Leaked on Unauthorized File
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/sync" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <!-- LAB 17 -- CBSE Portal Default Credentials -->
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            CBSE — Default Credentials Authentication Bypass
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/school" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>

      <!-- LAB 21 -- CDN Directory Listing PII Disclosure -->
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            CDN Directory Listing — PII Exposed via Open /cdn/ Path (Passport Scans, National IDs, Credit Cards)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/edge" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <!-- LAB 22 -- Aliyun WAF Bypass: cat Blocked, Alternative Commands Bypass WAF -->
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Aliyun WAF Bypass — Bypass WAF Rules (Real-World Finding)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/shield" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
      <!-- LAB 23 -- Unrestricted File Upload: PHP Profile Picture RCE -->
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Unrestricted File Upload — PHP Profile Picture Leads to Remote Code Execution (MTN Group — <a href="https://hackerone.com/reports/1164452" target="_blank" rel="noopener noreferrer" style="color:#fff;background:#be185d;padding:1px 5px;border-radius:3px;text-decoration:none;font-weight:600;font-size:.85em;">#1164452</a>)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
        </div>
        <div class="lab-action">
          <a href="/avatar" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
      </div>
    </div>
  </main>


  <?php include __DIR__ . '/includes/footer.php'; ?>



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

      let selectedCategory = 'all';
      let selectedDifficulty = 'all';
      let hideSolved = false;

      // User State variables (synced via MySQL backend)
      let isLoggedIn = false;
      let currentUsername = '';
      let solvedLabs = [];
      let bookmarkedLabs = [];

      // Fetch User State from Backend MySQL Database
      function fetchUserState() {
        fetch('portal.php?action=get_state')
          .then(res => res.json())
          .then(res => {
            if (res.success && res.data) {
              isLoggedIn = res.data.loggedIn;
              currentUsername = res.data.username;
              solvedLabs = res.data.solvedLabs || [];
              bookmarkedLabs = res.data.bookmarkedLabs || [];

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
          const labLink = card.querySelector('a.btn-ACCESS')?.getAttribute('href') || '';
          if (!labLink) return;

          const isSolved = solvedLabs.includes(labLink);
          const isBookmarked = bookmarkedLabs.includes(labLink);

          if (isSolved) card.classList.add('is-solved');
          else card.classList.remove('is-solved');

          if (isBookmarked) card.classList.add('is-bookmarked');
          else card.classList.remove('is-bookmarked');

          const starBtn = card.querySelector('.btn-star-toggle');
          if (starBtn) {
            if (isBookmarked) {
              starBtn.classList.add('bookmarked');
              starBtn.title = 'Remove bookmark';
              starBtn.innerHTML = `<i class="bi bi-star-fill text-warning"></i>`;
            } else {
              starBtn.classList.remove('bookmarked');
              starBtn.title = 'Bookmark lab';
              starBtn.innerHTML = `<i class="bi bi-star"></i>`;
            }
          }

          const solvedBtn = card.querySelector('.btn-solved-toggle');
          if (solvedBtn) {
            if (isSolved) {
              solvedBtn.classList.add('solved');
              solvedBtn.title = 'Click to mark as unsolved';
              solvedBtn.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i><span>Solved</span>`;
            } else {
              solvedBtn.classList.remove('solved');
              solvedBtn.title = 'Click to mark as solved';
              solvedBtn.innerHTML = `<i class="bi bi-circle me-1"></i><span>Mark Solved</span>`;
            }
          }
        });
      }

      // Inject Solved & Bookmark Toggle Buttons into every lab-card
      allLabCards.forEach((card) => {
        const labLink = card.querySelector('a.btn-ACCESS')?.getAttribute('href') || '';
        if (!labLink) return;

        const labAction = card.querySelector('.lab-action');
        if (!labAction) return;

        // Star / Bookmark Button
        const starBtn = document.createElement('button');
        starBtn.type = 'button';
        starBtn.className = 'btn-star-toggle';
        starBtn.setAttribute('data-lab-id', labLink);
        starBtn.title = 'Bookmark lab';
        starBtn.innerHTML = `<i class="bi bi-star"></i>`;

        starBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();

          const targetLabId = this.getAttribute('data-lab-id');
          const formData = new FormData();
          formData.append('lab_id', targetLabId);

          fetch('portal.php?action=toggle_bookmark', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
              if (res.requireLogin) {
                window.location.href = 'login.php';
                return;
              }
              if (res.success && res.data) {
                solvedLabs = res.data.solvedLabs || [];
                bookmarkedLabs = res.data.bookmarkedLabs || [];
                updateAllLabCardsUI();
                updateBookmarkTabCount();
                updateSearch();
              }
            });
        });

        // Solved Toggle Button
        const solvedBtn = document.createElement('button');
        solvedBtn.type = 'button';
        solvedBtn.className = 'btn-solved-toggle';
        solvedBtn.setAttribute('data-lab-id', labLink);
        solvedBtn.title = 'Click to mark as solved';
        solvedBtn.innerHTML = `<i class="bi bi-circle me-1"></i><span>Mark Solved</span>`;

        solvedBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();

          const targetLabId = this.getAttribute('data-lab-id');
          const formData = new FormData();
          formData.append('lab_id', targetLabId);

          fetch('portal.php?action=toggle_solved', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
              if (res.requireLogin) {
                window.location.href = 'login.php';
                return;
              }
              if (res.success && res.data) {
                solvedLabs = res.data.solvedLabs || [];
                bookmarkedLabs = res.data.bookmarkedLabs || [];
                updateAllLabCardsUI();
                updateProgressBar();
                updateSearch();
              }
            });
        });

        labAction.appendChild(starBtn);
        labAction.appendChild(solvedBtn);
      });

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
      const sidebarTopicCount = document.getElementById('sidebarTopicCount');

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

      // Dynamically populate Category Dropdown options and Left Sidebar Topics from DOM headings
      if (categoryTitles.length > 0) {
        if (sidebarTopicCount) {
          sidebarTopicCount.textContent = `${categoryTitles.length} Topics`;
        }

        if (categorySidebarList) {
          categorySidebarList.innerHTML = '';

          // "All Vulnerabilities" item
          const allItem = document.createElement('button');
          allItem.type = 'button';
          allItem.className = 'sidebar-item active-item';
          allItem.setAttribute('data-category', 'all');
          allItem.innerHTML = `<span class="item-title"><i class="bi bi-grid-fill me-2 opacity-75"></i>All Vulnerabilities</span><span class="item-count">${totalLabs}</span>`;
          categorySidebarList.appendChild(allItem);

          // "Bookmarked Labs" item
          const bookmarkedItem = document.createElement('button');
          bookmarkedItem.type = 'button';
          bookmarkedItem.className = 'sidebar-item';
          bookmarkedItem.setAttribute('data-category', 'bookmarked');
          bookmarkedItem.innerHTML = `<span class="item-title"><i class="bi bi-star-fill me-2 text-warning"></i>Bookmarked Labs</span><span class="item-count" id="sidebarBookmarkCount">0</span>`;
          categorySidebarList.appendChild(bookmarkedItem);

          // Category Topics items
          categoryTitles.forEach((catTitle) => {
            const labsList = catTitle.nextElementSibling;
            const count = labsList ? labsList.querySelectorAll('.lab-card').length : 0;
            const catName = catTitle.textContent.trim();

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
            item.innerHTML = `<span class="item-title"><i class="bi bi-chevron-right me-2 text-muted" style="font-size: 0.72rem;"></i>${catName}</span><span class="item-count">${count}</span>`;
            categorySidebarList.appendChild(item);
          });
        }
      }

      function scrollToCategory(catName) {
        if (!catName || catName === 'all') {
          window.scrollTo({ top: 0, behavior: 'auto' });
          return;
        }

        let found = false;
        categoryTitles.forEach((catTitle) => {
          if (!found && catTitle.textContent.trim().toLowerCase() === catName.toLowerCase()) {
            found = true;
            const rect = catTitle.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const targetY = Math.max(0, rect.top + scrollTop - 75);

            window.scrollTo({
              top: targetY,
              behavior: 'auto'
            });
          }
        });
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
          scrollToCategory(selectedCategory);
        });
      }

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

          const catName = catTitle.textContent.trim();
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
            const labLink = card.querySelector('a.btn-ACCESS')?.getAttribute('href') || '';
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
          const href = card.querySelector('a.btn-ACCESS')?.getAttribute('href') || '';
          
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

          fetch('portal.php?action=login', { method: 'POST', body: formData })
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

          fetch('portal.php?action=signup', { method: 'POST', body: formData })
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

      document.addEventListener('click', function(e) {
        if (e.target.closest('#logoutBtnNav')) {
          e.preventDefault();
          fetch('portal.php?action=logout')
            .then(res => res.json())
            .then(res => {
              if (res.success) {
                location.reload();
              }
            });
        }
      });

      // Initial Fetch & Count Sync
      fetchUserState();
      if (totalLabs > 0) {
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

  <!-- Floating Back to Top Button -->
  <button type="button" id="backToTopBtn" class="btn border-0" style="display: none; position: fixed; bottom: 30px; right: 30px; z-index: 1050; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; width: 48px; height: 48px; border-radius: 50%; box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4); transition: all 0.3s ease; cursor: pointer;" title="Back to top">
    <i class="bi bi-arrow-up" style="font-size: 1.3rem;"></i>
  </button>
</body>
</html>