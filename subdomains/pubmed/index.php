<?php
$is_search = isset($_GET['term']) || isset($_GET['q']) || isset($_GET['s']);
$term = isset($_GET['term']) ? $_GET['term'] : (isset($_GET['q']) ? $_GET['q'] : (isset($_GET['s']) ? $_GET['s'] : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $is_search ? htmlspecialchars($term) . " - Search Results - PubMed" : "Advanced Search Builder - PubMed"; ?></title>
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
    body {
      font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
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
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Top US Govt Banner */
    .govt-banner {
      background-color: #f0f0f0;
      border-bottom: 1px solid #e5e7eb;
      font-size: 0.75rem;
      color: #4b5563;
      padding: 4px 0;
    }
    .govt-banner-flex {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .flag-icon {
      width: 16px;
      height: 11px;
      background: linear-gradient(to bottom, #b91c1c 50%, #ffffff 50%);
      display: inline-block;
      border: 1px solid #d1d5db;
    }

    /* NIH Main Blue Header */
    .nih-header {
      background-color: #165280;
      color: #ffffff;
      padding: 14px 0;
    }
    .nih-flex {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .nih-brand {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .nih-logo-box {
      border: 2px solid #ffffff;
      border-radius: 50%;
      width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 0.95rem;
      letter-spacing: -0.5px;
    }
    .nih-titles {
      display: flex;
      flex-direction: column;
    }
    .nih-main-title {
      font-size: 1.15rem;
      font-weight: 700;
      line-height: 1.2;
    }
    .nih-sub-title {
      font-size: 0.75rem;
      opacity: 0.9;
      font-weight: 400;
    }
    .btn-login {
      border: 1px solid #ffffff;
      color: #ffffff;
      padding: 6px 16px;
      border-radius: 4px;
      font-size: 0.85rem;
      font-weight: 600;
      background: transparent;
      cursor: pointer;
    }
    .btn-login:hover {
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
      text-decoration: none;
    }

    /* PubMed Brand Logo Component */
    .pubmed-logo {
      display: flex;
      align-items: baseline;
      font-size: 2.2rem;
      font-weight: 800;
      letter-spacing: -0.5px;
      user-select: none;
    }
    .pubmed-pub { color: #165280; }
    .pubmed-med { color: #0284c7; }
    .pubmed-reg { font-size: 0.8rem; vertical-align: super; color: #165280; margin-left: 1px; }

    /* ================================================================= */
    /* VIEW 1: ADVANCED SEARCH BUILDER STYLES                            */
    /* ================================================================= */
    .advanced-main {
      padding: 32px 0 60px 0;
    }
    .advanced-header-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 32px;
    }
    .advanced-page-title {
      font-size: 1.4rem;
      font-weight: 700;
      color: #333333;
    }
    .user-guide-link {
      font-size: 0.88rem;
      color: #0066cc;
    }

    .form-section-title {
      font-size: 0.92rem;
      font-weight: 600;
      color: #4b5563;
      margin-bottom: 8px;
    }
    .builder-row {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 6px;
      flex-wrap: wrap;
    }
    .builder-select {
      height: 38px;
      padding: 0 12px;
      border: 1px solid #cbd5e1;
      border-radius: 4px;
      font-size: 0.9rem;
      background: #ffffff;
      outline: none;
      min-width: 180px;
    }
    .builder-input {
      flex: 1;
      min-width: 280px;
      height: 38px;
      padding: 0 12px;
      border: 1px solid #cbd5e1;
      border-radius: 4px;
      font-size: 0.9rem;
      outline: none;
    }
    .btn-add {
      background-color: #165280;
      color: #ffffff;
      border: none;
      border-radius: 4px;
      padding: 0 16px;
      height: 38px;
      font-size: 0.85rem;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .show-index-line {
      margin-bottom: 24px;
      font-size: 0.82rem;
    }

    .query-textarea-wrapper {
      display: flex;
      gap: 12px;
      align-items: flex-start;
      margin-bottom: 40px;
    }
    .query-textarea {
      flex: 1;
      height: 100px;
      padding: 12px;
      border: 1px solid #cbd5e1;
      border-radius: 4px;
      font-size: 0.9rem;
      font-family: inherit;
      outline: none;
      resize: vertical;
    }
    .btn-search-builder {
      background-color: #165280;
      color: #ffffff;
      border: none;
      border-radius: 4px;
      padding: 0 20px;
      height: 38px;
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .history-section {
      border-top: 1px solid #e5e7eb;
      padding-top: 24px;
    }
    .history-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: #333333;
      margin-bottom: 8px;
    }
    .history-empty-text {
      font-size: 0.88rem;
      color: #6b7280;
    }

    /* ================================================================= */
    /* VIEW 2: SEARCH RESULTS PAGE STYLES                                */
    /* ================================================================= */
    .search-top-bar {
      padding: 20px 0 10px 0;
      background-color: #ffffff;
    }
    .search-bar-row {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .search-input-form {
      flex: 1;
      display: flex;
      max-width: 650px;
      position: relative;
    }
    .search-main-input {
      flex: 1;
      height: 42px;
      padding: 0 35px 0 14px;
      border: 1px solid #cbd5e1;
      border-radius: 4px 0 0 4px;
      font-size: 0.95rem;
      outline: none;
    }
    .search-clear-btn {
      position: absolute;
      right: 90px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #94a3b8;
      cursor: pointer;
      font-size: 1rem;
    }
    .search-submit-btn {
      height: 42px;
      background-color: #165280;
      color: #ffffff;
      border: none;
      border-radius: 0 4px 4px 0;
      padding: 0 24px;
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
    }

    .sub-search-nav {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 650px;
      margin-left: 170px;
      margin-top: 6px;
      font-size: 0.82rem;
    }
    .sub-nav-left {
      display: flex;
      gap: 12px;
    }

    .action-controls-bar {
      border-top: 1px solid #e5e7eb;
      border-bottom: 1px solid #e5e7eb;
      padding: 10px 0;
      margin-top: 16px;
      font-size: 0.85rem;
    }
    .action-flex {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .action-btns {
      display: flex;
      gap: 8px;
    }
    .action-btn {
      border: 1px solid #cbd5e1;
      background: #ffffff;
      padding: 4px 12px;
      border-radius: 4px;
      font-size: 0.82rem;
      font-weight: 600;
      color: #333;
      cursor: pointer;
    }
    .action-right {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .sort-select {
      border: 1px solid #cbd5e1;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.82rem;
      background: #ffffff;
    }

    /* Main Grid: Sidebar & Results */
    .results-grid {
      display: grid;
      grid-template-columns: 240px 1fr;
      gap: 32px;
      padding: 24px 0 60px 0;
    }

    /* Sidebar Filters */
    .filters-sidebar {
      font-size: 0.82rem;
    }
    .sidebar-section-title {
      font-size: 0.75rem;
      font-weight: 700;
      color: #6b7280;
      text-transform: uppercase;
      margin-bottom: 8px;
      letter-spacing: 0.5px;
    }
    .filter-group {
      margin-bottom: 24px;
    }
    .filter-option {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 6px;
      color: #333;
    }

    /* Histogram chart SVG placeholder */
    .chart-container {
      margin: 12px 0;
      height: 80px;
      display: flex;
      align-items: flex-end;
      gap: 3px;
      border-bottom: 1px solid #cbd5e1;
      padding-bottom: 4px;
    }
    .chart-bar {
      flex: 1;
      background: #0284c7;
      border-radius: 2px 2px 0 0;
    }

    /* Results Column */
    .results-column {}
    .results-meta-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.85rem;
      color: #4b5563;
      margin-bottom: 16px;
    }
    .pagination-box {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .page-num-input {
      width: 32px;
      height: 24px;
      text-align: center;
      border: 1px solid #cbd5e1;
      border-radius: 3px;
      font-size: 0.8rem;
    }

    /* Warning Alert Box */
    .alert-warning-box {
      background-color: #fffbeb;
      border-left: 4px solid #f59e0b;
      padding: 16px;
      border-radius: 4px;
      margin-bottom: 24px;
      display: flex;
      align-items: flex-start;
      gap: 12px;
      font-size: 0.9rem;
    }
    .alert-warning-icon {
      color: #d97706;
      font-size: 1.3rem;
      line-height: 1;
    }
    .alert-showing-title {
      font-weight: 700;
      color: #111111;
      margin-bottom: 2px;
    }
    .alert-retrieved-msg {
      color: #4b5563;
      font-size: 0.85rem;
    }

    /* Result Item Card */
    .result-card {
      margin-bottom: 28px;
    }
    .result-card-header {
      display: flex;
      align-items: flex-start;
      gap: 10px;
    }
    .result-num {
      font-weight: 700;
      font-size: 0.9rem;
      color: #333;
    }
    .btn-cite {
      font-size: 0.78rem;
      color: #0066cc;
      border: 1px solid #cbd5e1;
      padding: 2px 6px;
      border-radius: 3px;
      background: #fff;
    }
    .result-article-title {
      font-size: 1.05rem;
      font-weight: 700;
      line-height: 1.35;
      margin-bottom: 4px;
    }
    .result-authors {
      font-size: 0.85rem;
      color: #333333;
      margin-bottom: 2px;
    }
    .result-citation {
      font-size: 0.82rem;
      color: #6b7280;
      margin-bottom: 4px;
    }
    .result-pmid-row {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.8rem;
      margin-bottom: 6px;
    }
    .pmid-badge {
      background-color: #f1f5f9;
      color: #475569;
      padding: 1px 6px;
      border-radius: 3px;
      font-weight: 600;
    }
    .result-snippet-text {
      font-size: 0.85rem;
      color: #374151;
      line-height: 1.5;
    }

    /* ================================================================= */
    /* FOOTER STYLES                                                     */
    /* ================================================================= */
    footer {
      background-color: #e5e7eb;
      border-top: 1px solid #d1d5db;
    }
    .footer-top-links {
      padding: 16px 0;
      text-align: center;
      font-size: 0.82rem;
      color: #4b5563;
      border-bottom: 1px solid #d1d5db;
    }
    .footer-top-links a {
      color: #165280;
      font-weight: 600;
      margin: 0 8px;
    }

    .footer-trademark {
      padding: 14px 0;
      text-align: center;
      font-size: 0.72rem;
      color: #6b7280;
      max-width: 800px;
      margin: 0 auto;
      line-height: 1.4;
    }

    .follow-ncbi-strip {
      background-color: #165280;
      color: #ffffff;
      padding: 14px 0;
      text-align: center;
    }
    .follow-ncbi-title {
      font-size: 0.85rem;
      font-weight: 800;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }
    .social-icons-row {
      display: flex;
      justify-content: center;
      gap: 18px;
      font-size: 1.1rem;
    }
    .social-icons-row a {
      color: #ffffff;
    }
  </style>
</head>
<body>

  <!-- Top US Govt Banner -->
  <div class="govt-banner">
    <div class="container govt-banner-flex">
      <span class="flag-icon"></span>
      <span>An official website of the United States government</span>
      <a href="#" style="color: #0066cc; margin-left: 4px;">Here's how you know <i class="bi bi-chevron-down" style="font-size: 0.65rem;"></i></a>
    </div>
  </div>

  <!-- NIH NLM Blue Header -->
  <header class="nih-header">
    <div class="container nih-flex">
      <div class="nih-brand">
        <div class="nih-logo-box">NIH</div>
        <div class="nih-titles">
          <span class="nih-main-title">National Library of Medicine</span>
          <span class="nih-sub-title">National Center for Biotechnology Information</span>
        </div>
      </div>
      <a href="#" class="btn-login">Log in</a>
    </div>
  </header>

<?php if ($is_search): ?>
  <!-- ================================================================= -->
  <!-- VIEW 2: SEARCH RESULTS PAGE                                       -->
  <!-- ================================================================= -->
  
  <!-- Search Input Header Bar -->
  <div class="search-top-bar">
    <div class="container">
      <div class="search-bar-row">
        <!-- PubMed Logo -->
        <div class="pubmed-logo">
          <span class="pubmed-pub">Pub</span><span class="pubmed-med">Med</span><span class="pubmed-reg">®</span>
        </div>
        <!-- Search Form -->
        <form action="index.php" method="GET" class="search-input-form">
          <input type="text" name="term" class="search-main-input" value="<?php echo $term; ?>" placeholder="Search PubMed">
          <button type="button" class="search-clear-btn" onclick="this.previousElementSibling.value=''; this.previousElementSibling.focus();">✕</button>
          <button type="submit" class="search-submit-btn">Search</button>
        </form>
      </div>

      <!-- Sub Links Row -->
      <div class="sub-search-nav">
        <div class="sub-nav-left">
          <a href="index.php">Advanced</a>
          <a href="#">Create alert</a>
          <a href="#">Create RSS</a>
        </div>
        <a href="#" class="user-guide-link">User Guide</a>
      </div>
    </div>
  </div>

  <!-- Action Controls Strip -->
  <div class="action-controls-bar">
    <div class="container action-flex">
      <div class="action-btns">
        <button class="action-btn">Save</button>
        <button class="action-btn">Email</button>
        <button class="action-btn">Send to</button>
      </div>
      <div class="action-right">
        <span>Sort by:</span>
        <select class="sort-select">
          <option>Best match</option>
          <option>Most recent</option>
          <option>Publication date</option>
        </select>
        <button class="action-btn"><i class="bi bi-gear-fill"></i> Display options</button>
      </div>
    </div>
  </div>

  <!-- Main 2-Column Results Grid -->
  <main class="container results-grid">
    
    <!-- Left Filters Sidebar -->
    <aside class="filters-sidebar">
      <div class="sidebar-section-title">MY CUSTOM FILTERS</div>
      <div class="filter-group">
        <a href="#">Edit custom filters</a>
      </div>

      <div class="sidebar-section-title">RESULTS BY YEAR</div>
      <div class="chart-container">
        <div class="chart-bar" style="height: 15%;"></div>
        <div class="chart-bar" style="height: 25%;"></div>
        <div class="chart-bar" style="height: 40%;"></div>
        <div class="chart-bar" style="height: 60%;"></div>
        <div class="chart-bar" style="height: 85%;"></div>
        <div class="chart-bar" style="height: 100%;"></div>
      </div>
      <div style="display: flex; justify-content: space-between; color: #6b7280; font-size: 0.75rem; margin-bottom: 20px;">
        <span>1982</span>
        <span>2026</span>
      </div>

      <div class="sidebar-section-title">PUBLICATION DATE</div>
      <div class="filter-group">
        <label class="filter-option"><input type="radio" name="pub_date"> 1 year</label>
        <label class="filter-option"><input type="radio" name="pub_date"> 5 years</label>
        <label class="filter-option"><input type="radio" name="pub_date"> 10 years</label>
        <label class="filter-option"><input type="radio" name="pub_date"> Custom Range</label>
      </div>

      <div class="sidebar-section-title">TEXT AVAILABILITY</div>
      <div class="filter-group">
        <label class="filter-option"><input type="checkbox"> Abstract</label>
        <label class="filter-option"><input type="checkbox"> Free full text</label>
        <label class="filter-option"><input type="checkbox"> Full text</label>
      </div>
    </aside>

    <!-- Right Search Results Column -->
    <section class="results-column">
      
      <!-- Meta & Pagination -->
      <div class="results-meta-row">
        <span>1,015 results</span>
        <div class="pagination-box">
          <a href="#">&lt;</a>
          <span>Page</span>
          <input type="text" class="page-num-input" value="1">
          <span>of 102</span>
          <a href="#">&gt;</a>
          <a href="#">&gt;&gt;</a>
        </div>
      </div>

      <!-- Yellow Warning Box reflecting $term -->
      <div class="alert-warning-box">
        <div class="alert-warning-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div>
          <div class="alert-showing-title">Showing results for <em>ddgs</em></div>
          <div class="alert-retrieved-msg">Your search for <strong><?php echo $term; ?></strong> retrieved no results</div>
        </div>
      </div>

      <!-- Result Card 1 -->
      <article class="result-card">
        <div class="result-card-header">
          <input type="checkbox">
          <span class="result-num">1</span>
          <button class="btn-cite">Cite</button>
          <div>
            <h2 class="result-article-title">
              <a href="#">Distillers' dried grains with solubles (DDGS) and its potential as fermentation feedstock.</a>
            </h2>
            <div class="result-authors">Iram A, Cekmecelioglu D, Demirci A.</div>
            <div class="result-citation">Appl Microbiol Biotechnol. 2020 Jul;104(14):6115-6128. doi: 10.1007/s00253-020-10682-0. Epub 2020 May 21.</div>
            <div class="result-pmid-row">
              <span>PMID: 32440706</span>
              <span class="pmid-badge">Review.</span>
            </div>
            <p class="result-snippet-text">
              Numerous studies reported the production organic acids, methane, biohydrogen, and hydrolytic enzymes using <strong>DDGS</strong>. While <strong>DDGS</strong> contains remarkable amounts of macronutrients, pre-treatment of <strong>DDGS</strong> is required for release of the fermentable sugars. ...A review of ...
            </p>
          </div>
        </div>
      </article>

      <!-- Result Card 2 -->
      <article class="result-card">
        <div class="result-card-header">
          <input type="checkbox">
          <span class="result-num">2</span>
          <button class="btn-cite">Cite</button>
          <div>
            <h2 class="result-article-title">
              <a href="#">A strategy of co-fermentation of distillers dried grains with solubles (DDGS) and lignocellulosic feedstocks as swine feed.</a>
            </h2>
            <div class="result-authors">Fan W, Sun X, Cui G, Li Q, Xu Y, Wang L, Li X, Hu B, Chi Z.</div>
            <div class="result-citation">Crit Rev Biotechnol. 2023 Mar;43(2):212-225. doi: 10.1080/07388551.2022.2027337. Epub 2022 Jun 6.</div>
            <div class="result-pmid-row">
              <span>PMID: 35658696</span>
              <span class="pmid-badge">Review.</span>
            </div>
            <p class="result-snippet-text">
              Here, a strategy of co-fermentation of <strong>DDGS</strong> and lignocellulosic feedstocks for production of swine feed was discussed. The potential of the <strong>DDGS</strong> and lignocellulosic feedstocks as feedstock for fermented pig feed and the complementary relationship between them were d ...
            </p>
          </div>
        </div>
      </article>

    </section>

  </main>

<?php else: ?>
  <!-- ================================================================= -->
  <!-- VIEW 1: ADVANCED SEARCH BUILDER PAGE                              -->
  <!-- ================================================================= -->
  <main class="container advanced-main">
    
    <!-- Top Row -->
    <div class="advanced-header-row">
      <h1 class="advanced-page-title">PubMed Advanced Search Builder</h1>
      
      <div style="display: flex; align-items: center; gap: 16px;">
        <div class="pubmed-logo">
          <span class="pubmed-pub">Pub</span><span class="pubmed-med">Med</span><span class="pubmed-reg">®</span>
        </div>
      </div>
    </div>
    
    <div style="text-align: right; margin-top: -24px; margin-bottom: 24px;">
      <a href="#" class="user-guide-link">User Guide</a>
    </div>

    <!-- Section 1: Add terms -->
    <div class="form-section-title">Add terms to the query box</div>
    <form action="index.php" method="GET">
      <div class="builder-row">
        <select class="builder-select">
          <option>All Fields</option>
          <option>Title/Abstract</option>
          <option>Author</option>
          <option>Journal</option>
          <option>MeSH Terms</option>
        </select>

        <input type="text" name="term" class="builder-input" placeholder="Enter a search term">

        <button type="submit" class="btn-add">ADD <i class="bi bi-chevron-down"></i></button>
      </div>
      <div class="show-index-line">
        <a href="#">Show Index</a>
      </div>

      <!-- Section 2: Query Box -->
      <div class="form-section-title">Query box</div>
      <div class="query-textarea-wrapper">
        <textarea class="query-textarea" placeholder="Enter / edit your search query here"></textarea>
        <button type="submit" class="btn-search-builder">Search <i class="bi bi-chevron-down"></i></button>
      </div>
    </form>

    <!-- Section 3: History & Search Details -->
    <div class="history-section">
      <h2 class="history-title">History and Search Details</h2>
      <p class="history-empty-text">Your history is currently empty! As you use PubMed your recent searches will appear here.</p>
    </div>

  </main>
<?php endif; ?>

  <!-- Shared Footer -->
  <footer>
    <div class="footer-top-links">
      <a href="#">NCBI Literature Resources</a>
      <span>|</span>
      <a href="#">MeSH</a>
      <span>|</span>
      <a href="#">PMC</a>
      <span>|</span>
      <a href="#">Bookshelf</a>
      <span>|</span>
      <a href="#">Disclaimer</a>
    </div>

    <div class="footer-trademark">
      The PubMed wordmark and PubMed logo are registered trademarks of the U.S. Department of Health and Human Services (HHS). Unauthorized use of these marks is strictly prohibited.
    </div>

    <div class="follow-ncbi-strip">
      <div class="follow-ncbi-title">FOLLOW NCBI</div>
      <div class="social-icons-row">
        <a href="#" title="X (Twitter)"><i class="bi bi-twitter-x"></i></a>
        <a href="#" title="Facebook"><i class="bi bi-facebook"></i></a>
        <a href="#" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
        <a href="#" title="GitHub"><i class="bi bi-github"></i></a>
        <a href="#" title="RSS"><i class="bi bi-rss-fill"></i></a>
      </div>
    </div>
  </footer>

</body>
</html>