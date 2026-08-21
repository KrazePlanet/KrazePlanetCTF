<?php
$q = isset($_GET['q']) ? $_GET['q'] : (isset($_GET['s']) ? $_GET['s'] : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $q ? htmlspecialchars($q) . " - RefSeek Search" : "RefSeek - Academic Search Engine"; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background-color: #ffffff;
      color: #333333;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow-x: hidden;
    }

    a {
      color: #0066cc;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }

    /* Top Right Header Nav */
    .top-nav {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      padding: 16px 32px;
      gap: 24px;
      font-size: 0.88rem;
    }
    .top-nav-link {
      display: flex;
      align-items: center;
      gap: 6px;
      color: #4b5563;
      font-weight: 500;
      transition: color 0.2s;
    }
    .top-nav-link:hover {
      color: #0066cc;
      text-decoration: none;
    }
    .top-nav-link.search-link {
      color: #0066cc;
      font-weight: 600;
    }

    /* Main Content Container */
    .main-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding-top: 80px;
      padding-bottom: 120px;
      z-index: 2;
    }

    /* RefSeek Logo */
    .logo-container {
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      user-select: none;
    }
    .logo-text {
      font-size: 3.2rem;
      font-weight: 700;
      color: #4a5568;
      letter-spacing: -1px;
    }
    .logo-star {
      font-size: 2.8rem;
      font-weight: 800;
      color: #0066cc;
      margin-left: 2px;
      line-height: 1;
      transform: translateY(-8px);
      display: inline-block;
    }

    /* Search Section Wrapper */
    .search-wrapper {
      width: 100%;
      max-width: 620px;
      padding: 0 20px;
    }

    /* Tabs (Web | Documents) */
    .search-tabs {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 8px;
      font-size: 0.85rem;
      margin-bottom: 8px;
      padding-right: 4px;
    }
    .tab-item {
      color: #0066cc;
      font-weight: 500;
    }
    .tab-item.active {
      color: #333333;
      font-weight: 700;
      cursor: default;
    }
    .tab-divider {
      color: #9ca3af;
      font-weight: 300;
    }

    /* Search Form Box */
    .search-form {
      position: relative;
      width: 100%;
      margin-bottom: 24px;
    }
    .search-input {
      width: 100%;
      height: 48px;
      padding: 0 45px 0 20px;
      font-size: 1rem;
      font-family: inherit;
      color: #222222;
      background-color: #ffffff;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .search-input:focus {
      border-color: #0066cc;
      box-shadow: 0 2px 10px rgba(0, 102, 204, 0.15);
    }
    .search-btn {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #64748b;
      font-size: 1.15rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s;
    }
    .search-btn:hover {
      color: #0066cc;
    }

    /* Sub-search Links */
    .sub-links {
      text-align: center;
      font-size: 0.88rem;
      color: #555555;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    .learn-about-line {
      margin-bottom: 4px;
    }
    .learn-about-line a {
      color: #0066cc;
    }
    .directory-line {
      font-size: 0.88rem;
      color: #4b5563;
    }
    .directory-line a {
      color: #004080;
      font-weight: 700;
    }

    /* Search Results Block (if query submitted) */
    .results-container {
      width: 100%;
      max-width: 680px;
      margin-top: 20px;
      text-align: left;
    }
    .query-header {
      font-size: 1rem;
      color: #64748b;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #e2e8f0;
    }
    .result-item {
      margin-bottom: 24px;
    }
    .result-title {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 4px;
    }
    .result-url {
      font-size: 0.82rem;
      color: #166534;
      margin-bottom: 6px;
    }
    .result-snippet {
      font-size: 0.88rem;
      color: #475569;
      line-height: 1.5;
    }

    /* Decorative Bottom-Right Artwork */
    .artwork-container {
      position: absolute;
      bottom: 48px;
      right: 20px;
      width: 380px;
      height: auto;
      pointer-events: none;
      z-index: 1;
      opacity: 0.85;
    }

    /* Bottom Footer Bar */
    .footer-bar {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background-color: #0066cc;
      color: #ffffff;
      padding: 12px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.85rem;
      z-index: 10;
    }
    .footer-links {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .footer-links a {
      color: #ffffff;
      font-weight: 500;
    }
    .footer-links a:hover {
      text-decoration: underline;
    }
    .globe-dropdown {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      cursor: pointer;
    }
    .copyright-text {
      color: rgba(255, 255, 255, 0.9);
      font-weight: 400;
    }

    @media (max-width: 640px) {
      .artwork-container {
        display: none;
      }
      .top-nav {
        padding: 12px 16px;
      }
      .logo-text {
        font-size: 2.5rem;
      }
      .logo-star {
        font-size: 2.2rem;
      }
      .footer-bar {
        flex-direction: column;
        gap: 8px;
        text-align: center;
        padding: 10px 16px;
      }
    }
  </style>
</head>
<body>

  <!-- Top Right Navigation -->
  <nav class="top-nav">
    <a href="#" class="top-nav-link">
      <i class="bi bi-list" style="font-size: 1.1rem;"></i> Directory
    </a>
    <a href="index.php" class="top-nav-link search-link">
      <i class="bi bi-search"></i> Search
    </a>
  </nav>

  <!-- Main Search Area -->
  <main class="main-container">
    
    <!-- RefSeek Logo -->
    <div class="logo-container">
      <span class="logo-text">refseek</span>
      <span class="logo-star">*</span>
    </div>

    <!-- Search Box Wrapper -->
    <div class="search-wrapper">
      
      <!-- Mode Tabs (Web | Documents) -->
      <div class="search-tabs">
        <span class="tab-item active">Web</span>
        <span class="tab-divider">|</span>
        <a href="#" class="tab-item">Documents</a>
      </div>

      <!-- Search Input Form -->
      <form action="index.php" method="GET" class="search-form">
        <input 
          type="text" 
          name="q" 
          class="search-input" 
          value="<?php echo $q; ?>" 
          placeholder="Search"
          autocomplete="off"
          autofocus
        >
        <button type="submit" class="search-btn" aria-label="Submit Search">
          <i class="bi bi-search"></i>
        </button>
      </form>

      <!-- Sub Links -->
      <div class="sub-links">
        <div class="learn-about-line">
          Learn about: 
          <a href="index.php?q=Solar+System">Solar System</a>, 
          <a href="index.php?q=Natural+Selection">Natural Selection</a>
        </div>

        <div class="directory-line">
          Browse the <a href="#">Reference Site Directory</a>
        </div>
      </div>

      <?php if ($q !== ''): ?>
      <!-- Search Results Reflection -->
      <div class="results-container">
        <div class="query-header">
          Academic search results for <strong>"<?php echo $q; ?>"</strong>
        </div>
        
        <div class="result-item">
          <div class="result-title"><a href="#">Academic Overview & Research Papers on <?php echo $q; ?></a></div>
          <div class="result-url">https://www.refseek.com/directory/<?php echo urlencode($q); ?>.html</div>
          <div class="result-snippet">Explore peer-reviewed articles, documents, and reference resources related to <?php echo $q; ?> across university libraries and academic databases.</div>
        </div>

        <div class="result-item">
          <div class="result-title"><a href="#">Encyclopedia Reference & Studies: <?php echo $q; ?></a></div>
          <div class="result-url">https://edu.refseek.com/resources/<?php echo urlencode($q); ?></div>
          <div class="result-snippet">Comprehensive reference guide and educational resources for students and researchers studying <?php echo $q; ?>.</div>
        </div>
      </div>
      <?php endif; ?>

    </div>

  </main>

  <!-- Decorative Bottom-Right Artwork -->
  <svg class="artwork-container" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
    <g stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
      <!-- Globe -->
      <circle cx="280" cy="140" r="70" stroke-dasharray="3 3"/>
      <ellipse cx="280" cy="140" rx="30" ry="70"/>
      <line x1="210" y1="140" x2="350" y2="140"/>
      <line x1="220" y1="100" x2="340" y2="100"/>
      <line x1="220" y1="180" x2="340" y2="180"/>
      <!-- Globe Stand -->
      <path d="M 280,210 L 280,240 M 250,240 L 310,240 M 200,140 C 200,200 240,220 280,220"/>

      <!-- Stack of Books -->
      <rect x="180" y="220" width="80" height="15" rx="2"/>
      <rect x="175" y="235" width="90" height="15" rx="2"/>
      <rect x="170" y="250" width="100" height="18" rx="2"/>
      <!-- Vertical Books -->
      <rect x="130" y="190" width="16" height="78" rx="2"/>
      <rect x="148" y="200" width="14" height="68" rx="2"/>
      <rect x="164" y="210" width="12" height="58" rx="2"/>

      <!-- Bag / Backpack contour -->
      <path d="M 310,180 L 370,180 C 380,180 390,190 390,200 L 390,268 C 390,274 384,280 378,280 L 310,280 C 304,280 300,274 300,268 L 300,190 Z"/>
      <rect x="320" y="210" width="50" height="40" rx="4"/>

      <!-- Ruler & Pencil in cup -->
      <rect x="100" y="210" width="24" height="58" rx="2"/>
      <line x1="90" y1="180" x2="110" y2="210"/>
      <line x1="115" y1="170" x2="118" y2="210"/>
    </g>
  </svg>

  <!-- Bottom Footer Bar -->
  <footer class="footer-bar">
    <div class="footer-links">
      <a href="#">About</a>
      <a href="#">Help</a>
      <a href="#">Feedback</a>
      <a href="#">Terms</a>
      <span class="globe-dropdown">
        <i class="bi bi-globe"></i> <i class="bi bi-caret-down-fill" style="font-size: 0.65rem;"></i>
      </span>
    </div>
    <div class="copyright-text">
      © 2026 RefSeek.com
    </div>
  </footer>

</body>
</html>