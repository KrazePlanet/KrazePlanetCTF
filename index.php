<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/db.php';
$isAdmin = false;
if (isset($_SESSION['user_id']) && $pdo) {
    $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    if ($u && ($u['role'] === 'admin' || strtolower($u['username']) === 'admin')) {
        $isAdmin = true;
    }
}
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

        /* Modern Vexium Dark Fixed Left Sidebar */
    .portswigger-sidebar {
      position: fixed !important;
      top: 58px !important;
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

    .sidebar-item .item-count {
      font-size: 0.75rem !important;
      font-weight: 700 !important;
      padding: 3px 8px !important;
      border-radius: 12px !important;
      background: rgba(15, 23, 42, 0.9) !important;
      color: #64748b !important;
      border: 1px solid rgba(255, 255, 255, 0.1) !important;
      flex: 0 0 auto !important;
      margin-left: 8px !important;
      font-family: 'JetBrains Mono', monospace !important;
      transition: all 0.2s ease;
      display: inline-block !important;
      text-align: center !important;
    }

    .sidebar-item:hover .item-count {
      color: #38bdf8 !important;
      border-color: rgba(56, 189, 248, 0.3) !important;
    }

    .sidebar-item.active-item .item-count {
      background: #38bdf8 !important;
      color: #070b14 !important;
      border-color: #38bdf8 !important;
      font-weight: 800 !important;
      box-shadow: 0 0 10px rgba(56, 189, 248, 0.4) !important;
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
  
    /* Admin Lab Selection & Task Conversion Dock */
    .admin-lab-select-wrapper {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-left: 8px;
      position: relative;
    }
    .admin-lab-checkbox {
      appearance: none !important;
      -webkit-appearance: none !important;
      -moz-appearance: none !important;
      width: 22px !important;
      height: 22px !important;
      cursor: pointer !important;
      background: rgba(11, 19, 36, 0.85) !important;
      border: 1.5px solid rgba(56, 189, 248, 0.4) !important;
      border-radius: 6px !important;
      outline: none !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      position: relative !important;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
      box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.5), 0 2px 5px rgba(0, 0, 0, 0.2) !important;
      vertical-align: middle !important;
      margin: 0 !important;
    }
    .admin-lab-checkbox:hover {
      border-color: #38bdf8 !important;
      background: rgba(56, 189, 248, 0.15) !important;
      box-shadow: 0 0 10px rgba(56, 189, 248, 0.4) !important;
      transform: scale(1.08) !important;
    }
    .admin-lab-checkbox:checked {
      background: linear-gradient(135deg, #0284c7, #38bdf8) !important;
      border-color: #38bdf8 !important;
      box-shadow: 0 0 12px rgba(56, 189, 248, 0.6) !important;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E") !important;
      background-size: 14px 14px !important;
      background-position: center !important;
      background-repeat: no-repeat !important;
      transform: scale(1.05) !important;
    }
    .admin-lab-checkbox:focus-visible {
      box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.4), 0 0 12px rgba(56, 189, 248, 0.5) !important;
    }
    .lab-card.is-selected-for-task {
      border-color: rgba(56, 189, 248, 0.6) !important;
      background: linear-gradient(90deg, rgba(56, 189, 248, 0.08), rgba(15, 23, 42, 0.95)) !important;
      box-shadow: 0 0 15px rgba(56, 189, 248, 0.15) !important;
    }
    .select-all-cat-btn {
      background: rgba(56, 189, 248, 0.12);
      color: #38bdf8;
      border: 1px solid rgba(56, 189, 248, 0.25);
      border-radius: 6px;
      font-size: 11.5px;
      font-weight: 600;
      padding: 3px 10px;
      transition: all 0.2s;
      cursor: pointer;
    }
    .select-all-cat-btn:hover {
      background: rgba(56, 189, 248, 0.25);
      color: #ffffff;
      border-color: #38bdf8;
    }
    #adminTaskDock {
      position: fixed;
      bottom: 25px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 1060;
      background: rgba(15, 23, 42, 0.95);
      border: 1px solid rgba(56, 189, 248, 0.4);
      border-radius: 30px;
      padding: 10px 24px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6), 0 0 20px rgba(56, 189, 248, 0.25);
      backdrop-filter: blur(16px);
      display: none;
      align-items: center;
      gap: 16px;
      animation: slideUpDock 0.3s ease-out;
    }
    @keyframes slideUpDock {
      from { transform: translate(-50%, 50px); opacity: 0; }
      to { transform: translate(-50%, 0); opacity: 1; }
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
    <!-- Sidebar Header with Quick Search -->
    <div class="sidebar-header-box p-3 pb-2 border-bottom border-secondary border-opacity-25" style="background: rgba(7, 11, 20, 0.5);">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="fw-bold text-white small" style="letter-spacing: 0.5px; font-family: 'Outfit', sans-serif;">
          <i class="bi bi-grid-3x3-gap-fill text-info me-1"></i> CATEGORIES
        </span>
        <span class="badge" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.25); font-size: 11px;" id="sidebarTopicCount">24 Topics</span>
      </div>
      <div class="position-relative">
        <i class="bi bi-search position-absolute text-muted" style="left: 10px; top: 7px; font-size: 0.78rem;"></i>
        <input type="text" id="sidebarCategorySearch" class="form-control form-control-sm ps-4" placeholder="Filter categories..." style="background: rgba(7, 11, 20, 0.9); border: 1px solid rgba(255,255,255,0.12); color: #fff; border-radius: 8px; font-size: 12px;">
      </div>
    </div>

    <!-- Vertical Topic Items Container -->
    <div id="categorySidebarList" class="nav flex-column py-2 pb-4">
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

    <!-- Cross-Site Scripting -->
    <h3 class="category-title">Cross-Site Scripting</h3>
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/tutorialrepublic" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/guidelines" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/refseek" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/pubmed" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/bigbasket" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/search" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/feedback" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/cookbook" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/board" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/path-fetch" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/support" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/directory" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/helpdesk" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/news" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/docs" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/profile" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/account" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/tickets" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/checkout" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/mail" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/settings" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/portal" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/useruploads" class="btn-ACCESS" target="_blank">
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
            Reflected XSS - URL Encoding Context
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/dashboard" class="btn-ACCESS" target="blank">
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
            Reflected XSS - Search Filter Bypass
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/kb" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/shop" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1818163" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1818163</a>
          </div>
          <div class="lab-title">Reflected XSS - Equifax</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/tracking" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/751870" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #751870</a>
          </div>
          <div class="lab-title">Reflected XSS - PUBG</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/assets" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1940245" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1940245</a>
          </div>
          <div class="lab-title">Reflected XSS - Shopify</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/go" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/149855" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #149855</a>
          </div>
          <div class="lab-title">Reflected XSS - Imgur Mobile</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/media" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1549206" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1549206</a>
          </div>
          <div class="lab-title">Reflected XSS - Reddit</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/widgets" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/community" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/members" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/articles" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/digitalocean" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/hackerone" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/netflix" class="btn-ACCESS" target="blank">
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
            CSP Bypass - Unsafe Inline Scripts
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/48.php" class="btn-ACCESS" target="blank">
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
            CSP Protected Page
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/49.php" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/485748" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #485748</a>
          </div>
          <div class="lab-title">Stored XSS - Twitter</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/ads" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1147433" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1147433</a>
          </div>
          <div class="lab-title">Stored XSS - Shopify</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/cms" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1084183" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1084183</a>
          </div>
          <div class="lab-title">Stored XSS - Acronis</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/forum" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/contact" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1011888" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1011888</a>
          </div>
          <div class="lab-title">Blind Stored XSS - Informatica</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/partners" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/imdb" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/anilist" class="btn-ACCESS" target="_blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/milesweb" class="btn-ACCESS" target="_blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/censys" class="btn-ACCESS" target="_blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/careers" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/wallet" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag" style="background:#6366f1;color:#fff;">Real World</span>
            <a href="https://hackerone.com/reports/396493" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #396493</a>
          </div>
          <div class="lab-title">DOM XSS - Starbucks</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/cafe" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/704266" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #704266</a>
          </div>
          <div class="lab-title">
            DOM XSS - ForeScout Technologies
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/gallery" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/auth" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/forms" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/apply" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/survey" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/reports" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/instance" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/pixeleet" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/webmail" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/cpanel" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/webdisk" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/cpcontacts" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/cpcalendars" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/autodiscover" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/wildcard" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/hostmaster" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/protection" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/cloudapp" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/pay" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/atlas" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/avito" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/app" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/admin" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/dev" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/staging" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/sberbank" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/demo" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/test" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/nalozhka" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/sitemap" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/sbermarket" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/pochtabank" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/remote" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/store" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/smtp" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/cdn" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/pop" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/secure" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/apps" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/cust" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/old" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/uaecentral" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>


    <!-- HTML Injection -->
    <h3 class="category-title">HTML Injection</h3>
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/catalog" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/3079966" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #3079966</a>
          </div>
          <div class="lab-title">HTML Injection - LinkedIn</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/chat" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/57914" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #57914</a>
          </div>
          <div class="lab-title">Stored HTML Injection - Romit</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/transfer" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/358001" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #358001</a>
          </div>
          <div class="lab-title">Stored HTML Tag Injection - GitLab</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/code" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1374017" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1374017</a>
          </div>
          <div class="lab-title">HTML Injection - HackerOne</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/notifications" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/redirect" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/504751" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #504751</a>
          </div>
          <div class="lab-title">Open Redirect - Omise</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/links" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/311330" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #311330</a>
          </div>
          <div class="lab-title">Open Redirect - Semrush</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/out" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/2812583" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #2812583</a>
          </div>
          <div class="lab-title">Open Redirect - Tumblr</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/sso" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1490470" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1490470</a>
          </div>
          <div class="lab-title">Admin Auth Bypass - UPS</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/adminpanel" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/verify" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/mobile" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/codeshackio" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1708824" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1708824</a>
          </div>
          <div class="lab-title">
            Yelp for Business - Missing Rate Limiting on Subscription Form
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/rl-testing" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1322243" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1322243</a>
          </div>
          <div class="lab-title">
            On Running - Missing Rate Limiting on Partner Authentication Endpoint
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/security-test" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/905692" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #905692</a>
          </div>
          <div class="lab-title">
            Courier - Missing Rate Limiting on User Registration &amp; Email Enumeration
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/stress" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/658089" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #658089</a>
          </div>
          <div class="lab-title">
            WakaTime - Rate Limit Too Lenient on Password Reset Endpoint
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/request" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1202408" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1202408</a>
          </div>
          <div class="lab-title">
            Redditgifts - Missing Rate Limiting on Adding Comments
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/load" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/224927" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #224927</a>
          </div>
          <div class="lab-title">
            Nextcloud - Missing Rate Limiting on Newsletter Subscription Endpoint
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/throttle" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1166069" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1166069</a>
          </div>
          <div class="lab-title">
            UPchieve - Missing Rate Limiting on Contact Us Endpoint
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/limit" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/128777" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #128777</a>
          </div>
          <div class="lab-title">
            Algolia - Missing Rate Limiting on Two-Factor Authentication (2FA) Code Verification
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/rate" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1166066" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1166066</a>
          </div>
          <div class="lab-title">
            UPchieve - Missing Rate Limiting on Password Reset Endpoint
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/ratelimit" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/774050" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #774050</a>
          </div>
          <div class="lab-title">
            Yelp for Business - Missing Rate Limiting on Resend Confirmation Email Endpoint
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/reset" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1060541" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1060541</a>
          </div>
          <div class="lab-title">
            MTN Group - Missing Rate Limiting on 5-Digit OTP Verification Endpoint
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/resend" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>


    <!-- Race Condition -->
    <h3 class="category-title">Race Condition</h3>
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
            CodeShack - Race Condition in OTP Resend Limit Bypass
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/counter" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/165570" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #165570</a>
          </div>
          <div class="lab-title">
            Slack - Race Condition in Account Creation Survey (Unlimited Credits)
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/race_condition" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1285538" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1285538</a>
          </div>
          <div class="lab-title">
            Omise - Race Condition in Team Member Invitations
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/condition" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/488985" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #488985</a>
          </div>
          <div class="lab-title">
            HackerOne - Race Condition in Claiming Program Credentials
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/flashdisk" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/454949" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #454949</a>
          </div>
          <div class="lab-title">
            Hacker101 CTF - Race Condition in Flag Submission
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/dropper" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/152717" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #152717</a>
          </div>
          <div class="lab-title">
            Urban Dictionary - Race Condition in Definition Votes
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/cablej" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/3104355" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #3104355</a>
          </div>
          <div class="lab-title">
            Dust - Race Condition in Folder Creation (Folder Limit Bypass)
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/duststaff" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>


    <!-- Username/Email Enumeration -->
    <h3 class="category-title">Username/Email Enumeration</h3>
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
            UPchieve - User & Email Enumeration via Password Reset
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/username_enum" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/parameter-tampering" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>


    <!-- SQL Injection -->
    <h3 class="category-title">SQL Injection</h3>
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/login" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/reviews" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/library" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/inventory" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/orders" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/stats" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/affiliate" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/proxy" class="btn-ACCESS" target="blank">
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
            Blind SQL Injection via Parameter name - Executive Dashboard
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/executive" class="btn-ACCESS" target="blank">
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
            Blind SQL Injection via PATH_INFO - Industrial Asset Registry
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/registry" class="btn-ACCESS" target="blank">
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
            Blind SQL Injection via Filename - University Course Catalog
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/academic" class="btn-ACCESS" target="blank">
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
            Time-based Blind SQLi via sitemap.xml - ACME Corp Industrial
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/industrial" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/403616" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #403616</a>
          </div>
          <div class="lab-title">Time-based Blind SQLi - Zomato</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/menu" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/297478" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #297478</a>
          </div>
          <div class="lab-title">Time-based Blind SQLi - GSA Bounty</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/collector" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1046084" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1046084</a>
          </div>
          <div class="lab-title">UNION-based SQLi - IntenseDebate</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/directoryapi" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1069531" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1069531</a>
          </div>
          <div class="lab-title">Blind SQLi - MTN Group</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/customers" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/273946" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #273946</a>
          </div>
          <div class="lab-title">ORDER BY SQLi - Grab</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/content" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/2051931" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #2051931</a>
          </div>
          <div class="lab-title">Boolean-blind SQLi - inDrive</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/api" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/433792" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #433792</a>
          </div>
          <div class="lab-title">Time-Based Blind SQLi - Rocket.Chat</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/events" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1044716" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1044716</a>
          </div>
          <div class="lab-title">Boolean-Blind SQLi - Zomato</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/rest" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/923020" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #923020</a>
          </div>
          <div class="lab-title">UNION-Based SQLi - Acronis</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/adminapi" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/3198980" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #3198980</a>
          </div>
          <div class="lab-title">UNION SQLi - Automattic</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/coupons" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1224660" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1224660</a>
          </div>
          <div class="lab-title">Time-Based Blind SQLi - Acronis</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/memberships" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/3127198" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #3127198</a>
          </div>
          <div class="lab-title">UNION SQLi - U.S. Dept Of Defense</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/ajax" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/838855" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #838855</a>
          </div>
          <div class="lab-title">Blind SQLi - Zomato</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/banners" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1042746" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1042746</a>
          </div>
          <div class="lab-title">Time-Based Blind SQLi - Automattic</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/moderation" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/491191" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #491191</a>
          </div>
          <div class="lab-title">String SQLi - U.S. Dept Of Defense</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/publications" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/2312334" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #2312334</a>
          </div>
          <div class="lab-title">Time-Based Blind SQLi - U.S. Dept Of Defense</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/knowledge" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- Cross-Site Request Forgery -->
    <h3 class="category-title">Cross-Site Request Forgery</h3>
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/security" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/preferences" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/privacy" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/mfa" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/834366" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #834366</a>
          </div>
          <div class="lab-title">Login CSRF - HackerOne</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/session" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/339352" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #339352</a>
          </div>
          <div class="lab-title">Login CSRF - Unikrn</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/identity" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1122408" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1122408</a>
          </div>
          <div class="lab-title">CSRF - GitLab</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/graphql" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/177508" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #177508</a>
          </div>
          <div class="lab-title">CSRF - Starbucks</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/wishlist" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/2712857" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #2712857</a>
          </div>
          <div class="lab-title">CSRF - U.S. Dept of Defense</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/myaccount" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1118521" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1118521</a>
          </div>
          <div class="lab-title">CSRF - U.S. Dept of Defense</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/academy" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>


    <!-- Server-Side Template Injection -->
    <h3 class="category-title">Server-Side Template Injection</h3>
    <div class="labs-list">
      <div class="lab-card">
        <div class="lab-badge">
          <svg viewBox="0 0 24 24"><path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M20 18l2-1v-2.5"></path></svg>
          LAB
        </div>
        <div class="lab-content">
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="difficulty-tag hard">Hard</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            CloudGuard - Enterprise Compliance &amp; Security Report Engine Code Execution
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/jinja2" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            TornadoAlert - Python SRE Incident &amp; Webhook Notification Code Execution
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/tornado" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            DocuCraft - Cloud Invoice &amp; Billing Template Engine Code Execution
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/erb" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PulseMail - Marketing Campaign Template Studio Code Execution
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/freemarker" class="btn-ACCESS" target="blank">
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
            Template Engine Code Injection
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/templates" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1104349" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1104349</a>
          </div>
          <div class="lab-title">SSTI - Glovo</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/onboarding" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/125980" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #125980</a>
          </div>
          <div class="lab-title">SSTI - Uber</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/accounts" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/164224" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #164224</a>
          </div>
          <div class="lab-title">SSTI - Unikrn</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/invite" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>


    <!-- Server-Side Request Forgery -->
    <h3 class="category-title">Server-Side Request Forgery</h3>
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/viewer" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/capture" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/scanner" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/fetch" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/monitor" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/cloud" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/print" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- Local File Inclusion -->
    <h3 class="category-title">Local File Inclusion</h3>
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/files" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/storage" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/uploads" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/archive" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/corporate" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/doc-portal" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/multilang-blog" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/hr-portal" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/helpdesk" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/lms" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/hospital" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/realestate" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/hosting-panel" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/ecommerce" class="btn-ACCESS" target="blank">
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
            RecipeBox - Base64-Encoded Path LFI Bypass
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/recipes" class="btn-ACCESS" target="blank">
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
            GovDocs - Double URL Encoding LFI Bypass
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/gov" class="btn-ACCESS" target="blank">
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
            Admin Portal - Error Parameter Path Traversal
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/control" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- Remote Code Execution -->
    <h3 class="category-title">Remote Code Execution</h3>
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/diagnostics" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>

    </div>

    <!-- Insecure Direct Object Reference -->
    <h3 class="category-title">Insecure Direct Object Reference</h3>
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/billing" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/driver" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/patient" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/social" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/bank" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- Remote File Inclusion -->
    <h3 class="category-title">Remote File Inclusion</h3>
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/include" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/192940" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #192940</a>
          </div>
          <div class="lab-title">RFI + XSS + SSRF via Unvalidated URL Proxy in GIS Portal (U.S. DoD)</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/maps" class="btn-ACCESS" target="blank">
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
            PageForge CMS - Content Manager Remote &amp; Local File Inclusion
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/pages" class="btn-ACCESS" target="blank">
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
            ShopStream - E-Commerce Bulk Product Import Remote &amp; Local File Inclusion
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/import" class="btn-ACCESS" target="blank">
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
            StreamFlux - Video Analytics CDN Origin Asset Proxy Remote &amp; Local File Inclusion
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/origin" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
        </div>
      </div>
    </div>

    <!-- XML External Entity -->
    <h3 class="category-title">XML External Entity</h3>
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/xml" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/248668" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #248668</a>
          </div>
          <div class="lab-title">XXE on Twitter SMS SXMP API (File Read via operatorId Error Reflection)</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/sms" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/347139" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #347139</a>
          </div>
          <div class="lab-title">LFI + SSRF via XXE in SVG Emblem Editor (Rockstar Games ImageMagick)</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/designer" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/836877" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #836877</a>
          </div>
          <div class="lab-title">Blind XXE via JPEG XMP Metadata Injection (Informatica OOB Exfiltration)</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/images" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/500515" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #500515</a>
          </div>
          <div class="lab-title">XXE via XML Resume Upload Starbucks China Career Portal (IIS + ASP.NET)</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/careers-api" class="btn-ACCESS" target="blank">
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
            XXE via XML Registration API - SecureVault Password Manager
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/register" class="btn-ACCESS" target="blank">
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
            XXE via XML Login API - SecureVault Password Manager
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/auth-api" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/image-api" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/notifications-api" class="btn-ACCESS" target="blank">
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
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/file-upload-api" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag easy">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            PHP User Authentication & Secure Registration System
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/authentication" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            BookStore - Online Bookstore & Shopping Cart Platform
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/bookstore" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag easy">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Burger Palace - Fast Food Restaurant Portal
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/burger" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Club Manager - Membership & Events Management Portal
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/club" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            CoWork Space - Shared Office & Meeting Room Booking System
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/coworkingspace" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag easy">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Employee Management CRUD System
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/crud" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            DocVault - Enterprise Document Management System
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/docvault" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag easy">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Restaurant El Paso - Dining & Table Reservations Portal
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/elpaso" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag easy">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            English Learning & Vocabulary Portal
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/english" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Enigma - College Symposium & Event Management Platform
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/event" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Food Order CMS & Restaurant System
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/food" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Food Ordering & POS Restaurant Management System
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/food-ordering" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            FoodDelivery - Online Food Ordering & Cart Platform
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/fooddelivery" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Foodie - Gourmet Burger Bar & Table Reservation Platform
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/foodie" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Buffet Box - Digital QR Menu & Cloud Kitchen POS
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/foodmenu" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag easy">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            GiftStore - Online Gifts & Souvenirs Portal
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/gift" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Grecko - Mediterranean Bar & Seafood Restaurant
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/grecko" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag easy">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Grilli - Fine Dining Restaurant & Chef Specials
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/grilli" class="btn-ACCESS" target="blank">
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
            Hospital Management System (HMS) - Multi-Portal Healthcare
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/hms" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Internship & Student Placement Management System
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/internship" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Johnny's Dining & Bar - Restaurant POS & Management
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/johnnys" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Krables - Multi-Vendor E-Commerce Platform
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/krables" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Management Lab - Resource & Laboratory Booking Portal
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/management" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag easy">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Picture Perfect - Real Estate & Property Showcase
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/pictureperfect" class="btn-ACCESS" target="blank">
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
            Planet - CSP Protected Content Security Bypass
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/planet" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag easy">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            RainyRoof - Roofing Services & Quotation Portal
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/rainyroof" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Rate Limiting & Brute Force Protection Defense Lab
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/rate-limiting" class="btn-ACCESS" target="blank">
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
            ReadSphere - Book Review & Community Library Platform
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/readsphere" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Vincent Pizza - Authentic Italian Restaurant & Ordering
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/restaurant" class="btn-ACCESS" target="blank">
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
            Space - Content Security Policy (CSP) Bypass Lab
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/space" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            EduPro - Student & Academic Management System
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/student" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            ET LAB - Student & College Administration Portal
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/studentportal" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag easy">Easy</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Tour & Travel Vacation Booking Portal
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/tour" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            TripTrip - Tour Agency & Travel Booking Portal
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/triptrip" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            University Academic & Admissions Portal
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/university" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            WebFilesDesk - Self-Hosted File Explorer & Manager
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/webfiles" class="btn-ACCESS" target="blank">
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
            <span class="difficulty-tag medium">Medium</span>
            <span class="difficulty-tag" style="background:#0D9488;color:#fff;">Training</span>
          </div>
          <div class="lab-title">
            Yummy - Food Delivery & Restaurant Platform
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/yummy" class="btn-ACCESS" target="blank">
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
            ControlHub - JSON Response Manipulation Auth Bypass
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/hub" class="btn-ACCESS" target="blank">
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
            VaultTech - JWT Token Credential Reuse Across Admin Panels
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/vault" class="btn-ACCESS" target="blank">
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
            CloudSync - PII Leaked on Unauthorized File
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/sync" class="btn-ACCESS" target="blank">
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
            CBSE - Default Credentials Authentication Bypass
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/school" class="btn-ACCESS" target="blank">
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
          <div class="lab-title">CDN Directory Listing - PII Exposed</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/edge" class="btn-ACCESS" target="blank">
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
            Aliyun WAF Bypass - Bypass WAF Rules
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/shield" class="btn-ACCESS" target="blank">
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
            Aliyun WAF Bypass - Bypass WAF Rules
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/blog" class="btn-ACCESS" target="blank">
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
          <div class="lab-title">Aliyun WAF Bypass</div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/kzlabs" class="btn-ACCESS" target="blank">
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
            <a href="https://hackerone.com/reports/1164452" target="_blank" rel="noopener noreferrer" class="report-badge">HackerOne #1164452</a>
          </div>
          <div class="lab-title">
            Unrestricted File Upload - PHP Profile Picture Leads to Remote Code Execution
          </div>
        </div>
        <div class="lab-action">
          <a href="/subdomains/avatar" class="btn-ACCESS" target="blank">
            <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            ACCESS THE LAB
          </a>
      </div>
    </div>
  </main>


  
  <?php if ($isAdmin): ?>
    <!-- Floating Admin Task Action Dock -->
    <div id="adminTaskDock">
      <div class="d-flex align-items-center gap-2">
        <span class="badge" style="background: rgba(56, 189, 248, 0.2); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.4); font-size: 13px; padding: 6px 12px; border-radius: 20px;">
          🎯 <span id="selectedLabsCount">0</span> Labs Selected
        </span>
        <span id="selectedCategoryPreview" class="badge" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); font-size: 12px;"></span>
      </div>
      <button type="button" id="openTaskModalFromSelectionBtn" class="btn btn-sm btn-primary px-3 py-2 fw-bold d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #0284c7, #0369a1); border: none; border-radius: 20px; box-shadow: 0 4px 15px rgba(2, 132, 199, 0.4);">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Create Assignment from Selection &rarr;</span>
      </button>
      <button type="button" id="clearLabSelectionBtn" class="btn btn-sm btn-outline-secondary px-3 py-1" style="border-radius: 20px; font-size: 12px;">
        Clear
      </button>
    </div>

    <!-- Admin Task Creation Modal from Selected Labs -->
    <div class="modal fade" id="adminCreateTaskModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background: #0f172a; border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 18px; color: #fff; box-shadow: 0 20px 50px rgba(0,0,0,0.8);">
          <div class="modal-header border-bottom border-secondary border-opacity-25 pb-3">
            <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2">
              <i class="bi bi-clipboard-plus-fill text-info"></i> Create & Assign Assignment from Selected Labs
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <form id="adminSelectionTaskForm">
            <div class="modal-body p-4">
              <div id="adminTaskAlert" class="alert alert-danger py-2 px-3 small" style="display: none; border-radius: 8px;"></div>

              <div class="mb-3">
                <label class="form-label small fw-semibold text-light mb-1">Assignment Title</label>
                <input type="text" id="taskTitleAutoInput" class="form-control" placeholder="e.g. Create report for all of: HTML Injection (HTMLI)" required style="background:#070b14; border:1px solid rgba(255,255,255,0.14); color:#fff; font-weight:600;">
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-light mb-1">Category Name</label>
                  <input type="text" id="taskCategoryAutoInput" class="form-control" placeholder="e.g. HTML Injection (HTMLI)" required style="background:#070b14; border:1px solid rgba(255,255,255,0.14); color:#fff;">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-light mb-1">Tag Users (with @)</label>
                  <input type="text" id="taskUsersAutoInput" class="form-control" placeholder="e.g. @Tkamer @Anil" value="@Tkamer @Anil" required style="background:#070b14; border:1px solid rgba(255,255,255,0.14); color:#38bdf8; font-weight:600;">
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold text-light mb-1">Submission Deadline (Date & Time)</label>
                <input type="datetime-local" id="taskDateAutoInput" class="form-control" required style="background:#070b14; border:1px solid rgba(255,255,255,0.14); color:#fff;">
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold text-light mb-1">Assignment Scope & Instructions</label>
                <textarea id="taskDescAutoInput" class="form-control" rows="3" placeholder="Provide assignment instructions for trainees..." style="background:#070b14; border:1px solid rgba(255,255,255,0.14); color:#fff;">Complete penetration testing report for the selected laboratories. Document impact, payloads used, and remediation recommendations.</textarea>
              </div>

              <div class="mb-2">
                <label class="form-label small fw-semibold text-light mb-1">Selected Labs Preview (<span id="modalSelectedLabsCount">0</span> labs)</label>
                <div id="modalSelectedLabsPreview" class="p-3 rounded-3" style="background:#070b14; border:1px solid rgba(255,255,255,0.08); max-height:160px; overflow-y:auto; font-size:12.5px; display:flex; flex-direction:column; gap:6px;"></div>
                <input type="hidden" id="taskLabsJsonAutoInput" value="[]">
              </div>
            </div>

            <div class="modal-footer border-top border-secondary border-opacity-25 p-3">
              <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
              <button type="submit" class="btn btn-primary px-4 fw-bold" style="background: linear-gradient(135deg, #0284c7, #0369a1); border:none; border-radius: 8px;">
                Create & Assign Assignment &rarr;
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

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

          if (typeof IS_USER_LOGGED_IN !== 'undefined' && !IS_USER_LOGGED_IN) {
            if (typeof openLoginModal === 'function') {
              openLoginModal();
            } else {
              const modalEl = document.getElementById('loginModal');
              if (modalEl) {
                const m = bootstrap.Modal.getOrCreateInstance(modalEl);
                m.show();
              }
            }
            return;
          }

          const targetLabId = this.getAttribute('data-lab-id');
          const formData = new FormData();
          formData.append('lab_id', targetLabId);

          fetch('/navbar/portal.php?action=toggle_bookmark', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
              if (res.requireLogin) {
                if (typeof openLoginModal === 'function') {
                  openLoginModal();
                } else {
                  const modalEl = document.getElementById('loginModal');
                  if (modalEl) {
                    const m = bootstrap.Modal.getOrCreateInstance(modalEl);
                    m.show();
                  }
                }
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

          if (typeof IS_USER_LOGGED_IN !== 'undefined' && !IS_USER_LOGGED_IN) {
            if (typeof openLoginModal === 'function') {
              openLoginModal();
            } else {
              const modalEl = document.getElementById('loginModal');
              if (modalEl) {
                const m = bootstrap.Modal.getOrCreateInstance(modalEl);
                m.show();
              }
            }
            return;
          }

          const targetLabId = this.getAttribute('data-lab-id');
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

          fetch('/navbar/portal.php?action=toggle_solved', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
              if (res.requireLogin) {
                if (typeof openLoginModal === 'function') {
                  openLoginModal();
                } else {
                  const modalEl = document.getElementById('loginModal');
                  if (modalEl) {
                    const m = bootstrap.Modal.getOrCreateInstance(modalEl);
                    m.show();
                  }
                }
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

      // 1. Tag and store pristine Category Name on each heading before injecting any admin buttons
      categoryTitles.forEach((catTitle) => {
        const rawName = catTitle.childNodes[0]?.textContent?.trim() || catTitle.textContent.trim();
        catTitle.setAttribute('data-category-name', rawName);
      });

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

          // Setup Sidebar Filter Input Handler
          const catFilterInput = document.getElementById('sidebarCategorySearch');
          if (catFilterInput) {
            catFilterInput.addEventListener('input', function(e) {
              const query = (e.target.value || '').toLowerCase().trim();
              const items = categorySidebarList.querySelectorAll('.sidebar-item');
              items.forEach(btn => {
                const cat = (btn.getAttribute('data-category') || '').toLowerCase();
                const text = (btn.querySelector('.item-title')?.textContent || btn.textContent).toLowerCase();
                if (!query || cat === 'all' || cat === 'bookmarked' || text.includes(query)) {
                  btn.style.setProperty('display', 'flex', 'important');
                } else {
                  btn.style.setProperty('display', 'none', 'important');
                }
              });
            });
          }

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
            item.innerHTML = `<span class="item-title"><i class="bi bi-chevron-right me-2 text-muted" style="font-size: 0.72rem;"></i>${catName}</span><span class="item-count">${count}</span>`;
            categorySidebarList.appendChild(item);
          });
        }
      }

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
          const targetY = Math.max(0, rect.top + scrollTop - 75);

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
          fetch('/api/auth_api.php?action=logout')
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

      // --- Admin Selection & Task Auto-Generation Logic ---
      const isAdminUser = <?php echo $isAdmin ? 'true' : 'false'; ?>;
      
      if (isAdminUser) {
        // 1. Add "Select Category" buttons to all category titles
        categoryTitles.forEach(catTitle => {
          const catName = catTitle.getAttribute('data-category-name') || catTitle.childNodes[0]?.textContent?.trim() || catTitle.textContent.trim();
          const selectCatBtn = document.createElement('button');
          selectCatBtn.type = 'button';
          selectCatBtn.className = 'select-all-cat-btn ms-3';
          selectCatBtn.innerHTML = `<i class="bi bi-check2-square me-1"></i>Select Category`;
          
          selectCatBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const labsList = catTitle.nextElementSibling;
            if (!labsList) return;
            const checkboxes = labsList.querySelectorAll('.admin-lab-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => {
              cb.checked = !allChecked;
              const card = cb.closest('.lab-card');
              if (card) {
                if (cb.checked) card.classList.add('is-selected-for-task');
                else card.classList.remove('is-selected-for-task');
              }
            });
            updateAdminTaskDock();
          });

          catTitle.appendChild(selectCatBtn);
        });

        // 2. Inject Checkbox into each lab card
        allLabCards.forEach((card, idx) => {
          const labAction = card.querySelector('.lab-action');
          if (!labAction) return;

          const checkWrapper = document.createElement('div');
          checkWrapper.className = 'admin-lab-select-wrapper';
          checkWrapper.title = 'Select lab for task assignment';
          
          const checkbox = document.createElement('input');
          checkbox.type = 'checkbox';
          checkbox.className = 'admin-lab-checkbox';
          checkbox.setAttribute('data-card-index', idx);

          checkbox.addEventListener('change', function() {
            if (this.checked) card.classList.add('is-selected-for-task');
            else card.classList.remove('is-selected-for-task');
            updateAdminTaskDock();
          });

          checkWrapper.appendChild(checkbox);
          labAction.appendChild(checkWrapper);
        });

        const adminTaskDock = document.getElementById('adminTaskDock');
        const selectedLabsCountEl = document.getElementById('selectedLabsCount');
        const selectedCategoryPreviewEl = document.getElementById('selectedCategoryPreview');
        const openTaskModalBtn = document.getElementById('openTaskModalFromSelectionBtn');
        const clearSelectionBtn = document.getElementById('clearLabSelectionBtn');

        function getSelectedLabCards() {
          const selectedCards = [];
          document.querySelectorAll('.admin-lab-checkbox:checked').forEach(cb => {
            const card = cb.closest('.lab-card');
            if (card) selectedCards.push(card);
          });
          return selectedCards;
        }

        function updateAdminTaskDock() {
          const selectedCards = getSelectedLabCards();
          const count = selectedCards.length;

          if (count > 0) {
            adminTaskDock.style.display = 'flex';
            selectedLabsCountEl.textContent = count;

            // Detect primary category
            const categories = new Set();
            selectedCards.forEach(card => {
              const labsList = card.closest('.labs-list');
              if (labsList && labsList.previousElementSibling) {
                const rawTitle = labsList.previousElementSibling.textContent.replace('Select Category', '').trim();
                categories.add(rawTitle);
              }
            });

            if (categories.size === 1) {
              selectedCategoryPreviewEl.textContent = Array.from(categories)[0];
              selectedCategoryPreviewEl.style.display = 'inline-block';
            } else if (categories.size > 1) {
              selectedCategoryPreviewEl.textContent = `${categories.size} Categories`;
              selectedCategoryPreviewEl.style.display = 'inline-block';
            } else {
              selectedCategoryPreviewEl.style.display = 'none';
            }
          } else {
            adminTaskDock.style.display = 'none';
          }
        }

        if (clearSelectionBtn) {
          clearSelectionBtn.addEventListener('click', function() {
            document.querySelectorAll('.admin-lab-checkbox').forEach(cb => {
              cb.checked = false;
            });
            document.querySelectorAll('.lab-card').forEach(c => c.classList.remove('is-selected-for-task'));
            updateAdminTaskDock();
          });
        }

        // Open Modal and prefill auto-extracted values
        if (openTaskModalBtn) {
          openTaskModalBtn.addEventListener('click', function() {
            const selectedCards = getSelectedLabCards();
            if (selectedCards.length === 0) return;

            const labsData = [];
            const categories = new Set();

            selectedCards.forEach(card => {
              const badgeText = card.querySelector('.lab-badge')?.textContent.trim() || 'LAB';
              const diffTags = card.querySelectorAll('.difficulty-tag');
              const diff = diffTags[0]?.textContent.trim() || 'Easy';
              const type = diffTags[1]?.textContent.trim() || 'Training';
              const titleEl = card.querySelector('.lab-title') || card.querySelector('.lab-content');
              const fullTitle = titleEl ? titleEl.textContent.trim() : 'Lab';
              
              // Extract report link if any
              const reportTag = card.querySelector('.report-tag') || card.querySelector('a[href*="hackerone.com"]');
              const reportNum = reportTag ? reportTag.textContent.trim().replace('↗', '').trim() : '';
              const reportUrl = reportTag ? reportTag.getAttribute('href') : '';
              const labHref = card.querySelector('a.btn-ACCESS')?.getAttribute('href') || '#';

              // Clean title (remove report tag text and trailing arrows)
              let cleanTitle = fullTitle.replace(reportNum, '').replace('→', '').replace('↗', '').trim();
              if (diffTags.length > 0) {
                diffTags.forEach(t => { cleanTitle = cleanTitle.replace(t.textContent.trim(), '').trim(); });
              }

              labsData.push({
                badge: badgeText,
                difficulty: diff,
                type: type,
                title: cleanTitle,
                link: labHref,
                report_num: reportNum,
                report_url: reportUrl
              });

              const labsList = card.closest('.labs-list');
              if (labsList && labsList.previousElementSibling) {
                const rawTitle = labsList.previousElementSibling.textContent.replace('Select Category', '').trim();
                categories.add(rawTitle);
              }
            });

            const catName = categories.size === 1 ? Array.from(categories)[0] : (categories.size > 1 ? Array.from(categories).join(', ') : 'General');
            const suggestedTitle = categories.size === 1 ? `Create report for all of: ${catName}` : `Security Report Assignment (${selectedCards.length} Labs)`;

            document.getElementById('taskTitleAutoInput').value = suggestedTitle;
            document.getElementById('taskCategoryAutoInput').value = catName;
            document.getElementById('taskLabsJsonAutoInput').value = JSON.stringify(labsData);
            document.getElementById('modalSelectedLabsCount').textContent = selectedCards.length;

            // Automatically pick 48 hours / 2 days ahead at 23:59 (e.g. 18th -> 20th 23:59)
            const deadline48h = new Date();
            deadline48h.setDate(deadline48h.getDate() + 2);
            deadline48h.setHours(23, 59, 0, 0);
            const isoLocal = new Date(deadline48h.getTime() - deadline48h.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
            document.getElementById('taskDateAutoInput').value = isoLocal;

            // Render Preview Chips in Modal
            const previewContainer = document.getElementById('modalSelectedLabsPreview');
            previewContainer.innerHTML = '';
            labsData.forEach(l => {
              const item = document.createElement('div');
              item.className = 'd-flex align-items-center justify-content-between text-white';
              item.innerHTML = `<span><strong class="text-info">${l.badge}</strong>: ${l.title}</span><span class="badge bg-secondary">${l.difficulty}</span>`;
              previewContainer.appendChild(item);
            });

            const modalEl = document.getElementById('adminCreateTaskModal');
            if (modalEl) {
              const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
              modalInstance.show();
            }
          });
        }

        // Handle Admin Form Submission
        const adminTaskForm = document.getElementById('adminSelectionTaskForm');
        if (adminTaskForm) {
          adminTaskForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const alertEl = document.getElementById('adminTaskAlert');
            alertEl.style.display = 'none';

            const formData = new FormData();
            formData.append('title', document.getElementById('taskTitleAutoInput').value.trim());
            formData.append('category_name', document.getElementById('taskCategoryAutoInput').value.trim());
            formData.append('assigned_users', document.getElementById('taskUsersAutoInput').value.trim());
            formData.append('submission_date', document.getElementById('taskDateAutoInput').value);
            formData.append('description', document.getElementById('taskDescAutoInput').value.trim());
            formData.append('labs_json', document.getElementById('taskLabsJsonAutoInput').value.trim());

            fetch('assignments.php?action=create_assignment', { method: 'POST', body: formData })
              .then(res => res.json())
              .then(res => {
                if (res.success) {
                  window.location.href = 'assignments.php';
                } else {
                  alertEl.textContent = res.message || 'Failed to create task.';
                  alertEl.style.display = 'block';
                }
              })
              .catch(err => {
                alertEl.textContent = 'Server error occurred.';
                alertEl.style.display = 'block';
              });
          });
        }
      }

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
