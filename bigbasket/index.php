<?php
$is_search = isset($_GET['q']) || isset($_GET['s']) || isset($_GET['search']);
$q = isset($_GET['q']) ? $_GET['q'] : (isset($_GET['s']) ? $_GET['s'] : (isset($_GET['search']) ? $_GET['search'] : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $is_search ? htmlspecialchars($q) . " - BigBasket Search Results" : "Online Grocery Shopping and Online Supermarket in India - bigbasket"; ?></title>
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
      background-color: #f7f7f7;
      color: #333333;
      line-height: 1.4;
    }
    a {
      color: #0066cc;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }

    .container {
      max-width: 1240px;
      margin: 0 auto;
      padding: 0 16px;
    }

    /* Main Header Bar */
    .main-header {
      background-color: #ffffff;
      padding: 12px 0;
      border-bottom: 1px solid #e0e0e0;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .header-flex {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }
    .logo-container {
      display: flex;
      flex-direction: column;
    }
    .logo-main {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 1.5rem;
      font-weight: 800;
      color: #333333;
    }
    .logo-bb {
      background-color: #84c225;
      color: #ffffff;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 1.1rem;
    }
    .logo-sub {
      font-size: 0.65rem;
      color: #666666;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      margin-top: -2px;
    }

    /* Search Box Container */
    .search-box-form {
      flex: 1;
      max-width: 520px;
      position: relative;
    }
    .search-input {
      width: 100%;
      height: 38px;
      padding: 0 40px 0 38px;
      border: 1px solid #cccccc;
      border-radius: 4px;
      font-size: 0.88rem;
      outline: none;
    }
    .search-icon-left {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #84c225;
      font-size: 1rem;
    }

    /* Location Widget */
    .location-widget {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.78rem;
      background: #f8f9fa;
      padding: 4px 10px;
      border-radius: 4px;
      border: 1px solid #e9ecef;
    }
    .flash-icon {
      color: #84c225;
      font-weight: bold;
    }

    /* Right Login & Cart */
    .right-header-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .btn-login-dark {
      background-color: #262626;
      color: #ffffff;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
    }
    .cart-badge-icon {
      width: 36px;
      height: 36px;
      background-color: #e53935;
      color: #ffffff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      cursor: pointer;
    }

    /* Category Nav Bar */
    .cat-nav-bar {
      background-color: #ffffff;
      border-bottom: 1px solid #e0e0e0;
      padding: 8px 0;
    }
    .cat-nav-flex {
      display: flex;
      align-items: center;
      gap: 16px;
      overflow-x: auto;
    }
    .btn-shop-category {
      background-color: #84c225;
      color: #ffffff;
      border: none;
      padding: 8px 14px;
      border-radius: 4px;
      font-size: 0.85rem;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
    }
    .cat-quick-link {
      font-size: 0.82rem;
      font-weight: 600;
      color: #333333;
      white-space: nowrap;
    }

    /* ================================================================= */
    /* HOMEPAGE SPECIFIC STYLES                                          */
    /* ================================================================= */
    .promo-pills-row {
      display: flex;
      gap: 12px;
      margin: 24px 0;
      overflow-x: auto;
    }
    .promo-pill {
      background-color: #e5e7eb;
      color: #111111;
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 800;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
      white-space: nowrap;
      cursor: pointer;
    }
    .promo-pill.neupass {
      background: linear-gradient(90deg, #2e1065, #3b0764);
      color: #ffffff;
    }
    .promo-pill.ayurveda {
      background-color: #4d602a;
      color: #ffffff;
    }

    /* Smart Basket Card Container */
    .smart-basket-section {
      background-color: #ffffff;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 40px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    }
    .smart-basket-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .basket-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #222222;
    }
    .basket-controls {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.85rem;
    }
    .circle-btn {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      border: 1px solid #cccccc;
      background: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }

    .product-grid-4 {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
    }
    .product-card {
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      padding: 12px;
      position: relative;
      background: #ffffff;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .discount-badge {
      position: absolute;
      top: 12px;
      left: 12px;
      background-color: #84c225;
      color: #ffffff;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 2px;
      z-index: 2;
    }
    .product-img-box {
      width: 100%;
      height: 180px;
      border-radius: 6px;
      overflow: hidden;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #fdfbf7;
    }
    .product-img-box svg, .product-img-box img {
      max-height: 160px;
      width: auto;
    }

    .delivery-tag {
      font-size: 0.72rem;
      color: #84c225;
      font-weight: 700;
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 3px;
    }
    .brand-name {
      font-size: 0.78rem;
      color: #888888;
    }
    .product-name {
      font-size: 0.88rem;
      font-weight: 600;
      color: #222222;
      margin-bottom: 12px;
      min-height: 38px;
    }
    .qty-select {
      width: 100%;
      height: 32px;
      border: 1px solid #cccccc;
      border-radius: 4px;
      padding: 0 8px;
      font-size: 0.78rem;
      background: #ffffff;
      margin-bottom: 12px;
    }
    .price-row {
      font-size: 0.95rem;
      font-weight: 700;
      color: #222222;
      margin-bottom: 12px;
    }
    .price-original {
      font-size: 0.78rem;
      color: #888888;
      text-decoration: line-through;
      margin-left: 6px;
      font-weight: 400;
    }
    .card-footer-actions {
      display: flex;
      gap: 8px;
      align-items: center;
    }
    .btn-heart {
      border: 1px solid #cccccc;
      background: #ffffff;
      width: 32px;
      height: 32px;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #666;
      cursor: pointer;
    }
    .btn-add-red {
      flex: 1;
      border: 1px solid #e53935;
      background: #ffffff;
      color: #e53935;
      height: 32px;
      border-radius: 4px;
      font-weight: 700;
      font-size: 0.85rem;
      cursor: pointer;
    }

    /* ================================================================= */
    /* SEARCH RESULTS SPECIFIC STYLES                                    */
    /* ================================================================= */
    .search-breadcrumb {
      font-size: 0.82rem;
      color: #666666;
      padding: 14px 0;
    }
    .reflected-search-title {
      font-size: 0.95rem;
      color: #333333;
      margin-bottom: 16px;
    }

    .shop-undefined-title {
      font-size: 0.85rem;
      font-weight: 700;
      color: #666666;
      margin-bottom: 10px;
    }
    .chips-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 24px;
    }
    .chip-item {
      background: #ffffff;
      border: 1px solid #e0e0e0;
      padding: 6px 16px;
      border-radius: 4px;
      font-size: 0.78rem;
      font-weight: 600;
      color: #333333;
      cursor: pointer;
    }

    .filter-sort-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .btn-toggle-filter {
      background: #ffffff;
      border: 1px solid #cccccc;
      padding: 6px 14px;
      border-radius: 4px;
      font-size: 0.82rem;
      font-weight: 600;
      cursor: pointer;
    }
    .relevance-dropdown {
      background: #ffffff;
      border: 1px solid #cccccc;
      padding: 6px 14px;
      border-radius: 4px;
      font-size: 0.82rem;
      font-weight: 600;
    }

    .search-layout-grid {
      display: grid;
      grid-template-columns: 240px 1fr;
      gap: 20px;
    }
    .search-sidebar {
      background: #ffffff;
      border-radius: 6px;
      padding: 16px;
      font-size: 0.85rem;
    }
    .sidebar-heading {
      font-weight: 700;
      color: #222;
      margin-bottom: 12px;
    }
    .side-cat-list {
      list-style: none;
      color: #555555;
      font-size: 0.82rem;
    }
    .side-cat-list li {
      margin-bottom: 8px;
    }

    .tv-grid-3 {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
    }
    .rating-badge {
      background: #e8f5e9;
      color: #2e7d32;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 3px;
      display: inline-block;
      margin-top: 4px;
    }

    @media (max-width: 900px) {
      .product-grid-4, .tv-grid-3 {
        grid-template-columns: repeat(2, 1fr);
      }
      .search-layout-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <!-- Main Header Bar -->
  <header class="main-header">
    <div class="container header-flex">
      
      <!-- BigBasket Logo -->
      <a href="index.php" class="logo-container">
        <div class="logo-main">
          <span class="logo-bb">bb</span>
          <span>bigbasket</span>
        </div>
        <span class="logo-sub">A TATA Enterprise</span>
      </a>

      <!-- Search Input Box -->
      <form action="index.php" method="GET" class="search-box-form">
        <i class="bi bi-search search-icon-left"></i>
        <input type="text" name="q" class="search-input" value="<?php echo $q; ?>" placeholder="Search for Products...">
      </form>

      <!-- Delivery Widget -->
      <div class="location-widget">
        <span class="flash-icon"><i class="bi bi-lightning-charge-fill"></i> Delivery in 31 mins</span>
        <span style="color: #999;">560004, Bangalore</span>
      </div>

      <!-- Login & Basket -->
      <div class="right-header-actions">
        <button class="btn-login-dark">Login / Sign Up</button>
        <div class="cart-badge-icon" title="Basket">
          <i class="bi bi-basket-fill"></i>
        </div>
      </div>

    </div>
  </header>

  <!-- Category Nav Bar -->
  <nav class="cat-nav-bar">
    <div class="container cat-nav-flex">
      <button class="btn-shop-category">
        Shop by Category <i class="bi bi-chevron-down"></i>
      </button>
      <a href="#" class="cat-quick-link">Exotic Fruits & V...</a>
      <a href="#" class="cat-quick-link">Tea</a>
      <a href="#" class="cat-quick-link">Ghee</a>
      <a href="#" class="cat-quick-link">Nandini</a>
      <a href="#" class="cat-quick-link">Fresh Vegetables »</a>
    </div>
  </nav>

<?php if ($is_search): ?>
  <!-- ================================================================= -->
  <!-- SEARCH RESULTS VIEW                                               -->
  <!-- ================================================================= -->
  <main class="container">
    
    <!-- Breadcrumb -->
    <div class="search-breadcrumb">
      <i class="bi bi-house-door-fill"></i> Home / Search results
    </div>

    <!-- Reflected Search Text -->
    <div class="reflected-search-title">
      No results found for <strong><?php echo $q; ?></strong>, instead showing results for <strong>4k</strong>
    </div>

    <!-- Shop by undefined Chips -->
    <div class="shop-undefined-title">Shop by undefined</div>
    <div class="chips-grid">
      <span class="chip-item">Notebook & Journal</span>
      <span class="chip-item">Art Supplies</span>
      <span class="chip-item">Office Supplies</span>
      <span class="chip-item">Pens & Markers</span>
      <span class="chip-item">Scissors, Glue & Tape</span>
      <span class="chip-item">Exam Pads & Pencil Boxes</span>
      <span class="chip-item">Pencil, Eraser & Sharpener</span>
      <span class="chip-item">Action Toys</span>
      <span class="chip-item">Die Cast & Vehicles</span>
      <span class="chip-item">Board Games & Puzzles</span>
      <span class="chip-item">Learning & Education</span>
      <span class="chip-item" style="color: #0066cc;">Show more +</span>
    </div>

    <!-- Filter & Sort Header -->
    <div class="filter-sort-bar">
      <button class="btn-toggle-filter"><i class="bi bi-eye-slash"></i> Hide Filter</button>
      <div class="relevance-dropdown">Relevance <i class="bi bi-sliders"></i></div>
    </div>

    <!-- Main Grid -->
    <div class="search-layout-grid">
      
      <!-- Left Sidebar -->
      <aside class="search-sidebar">
        <div class="sidebar-heading">Shop by Category</div>
        <ul class="side-cat-list">
          <li style="font-weight: 700; color: #111;">Baby Care</li>
          <li style="padding-left: 10px;">Baby Accessories</li>
          <li style="padding-left: 10px;">Baby Bath & Hygiene</li>
          <li style="padding-left: 10px;">Baby Food & Formula</li>
          <li style="padding-left: 10px;">Diapers & Wipes</li>
          <li style="padding-left: 10px;">Feeding & Nursing</li>
          <li style="padding-left: 10px; color: #0066cc;">Show more +</li>
        </ul>

        <div class="sidebar-heading" style="margin-top: 20px;">Refined by</div>
        <div style="font-size: 0.82rem; color: #555;">Product Rating <i class="bi bi-chevron-down"></i></div>
      </aside>

      <!-- Right TV Products Grid -->
      <section class="tv-grid-3">
        
        <!-- TV Card 1 -->
        <div class="product-card">
          <span class="discount-badge">20% OFF</span>
          <div class="product-img-box" style="background: #ffffff;">
            <svg viewBox="0 0 200 140" xmlns="http://www.w3.org/2000/svg">
              <rect x="20" y="10" width="160" height="100" rx="4" fill="#1e293b" stroke="#334155" stroke-width="2"/>
              <rect x="25" y="15" width="150" height="90" fill="#0284c7"/>
              <polygon points="25,15 175,15 175,105" fill="rgba(255,255,255,0.15)"/>
              <path d="M 85,110 L 75,130 M 115,110 L 125,130" stroke="#334155" stroke-width="3"/>
            </svg>
          </div>
          <div class="delivery-tag"><i class="bi bi-lightning-charge-fill"></i> 105 MINS</div>
          <div class="brand-name">Croma</div>
          <div class="product-name" style="min-height: auto;">Croma 109 cm (43 inch) 2 Star QLED 4K Ultra HD Smart Google</div>
          <div class="rating-badge">4.2 ★ 6 Ratings</div>
          <div style="font-size: 0.78rem; color: #777; margin-top: 8px;">1 Unit</div>
          <div class="price-row" style="margin-top: 6px;">₹23290.00 <span class="price-original">₹29000.00</span></div>
        </div>

        <!-- TV Card 2 -->
        <div class="product-card">
          <span class="discount-badge">23% OFF</span>
          <div class="product-img-box" style="background: #ffffff;">
            <svg viewBox="0 0 200 140" xmlns="http://www.w3.org/2000/svg">
              <rect x="20" y="10" width="160" height="100" rx="4" fill="#0f172a" stroke="#334155" stroke-width="2"/>
              <rect x="25" y="15" width="150" height="90" fill="#4f46e5"/>
              <circle cx="100" cy="60" r="30" fill="#ec4899" opacity="0.6"/>
              <path d="M 85,110 L 75,130 M 115,110 L 125,130" stroke="#334155" stroke-width="3"/>
            </svg>
          </div>
          <div class="delivery-tag"><i class="bi bi-lightning-charge-fill"></i> 105 MINS</div>
          <div class="brand-name">Samsung</div>
          <div class="product-name" style="min-height: auto;">SAMSUNG UE81A 109 cm (43 inch) 4K Ultra HD LED Smart Tizen</div>
          <div style="font-size: 0.78rem; color: #777; margin-top: 8px;">1 Unit</div>
          <div class="price-row" style="margin-top: 6px;">₹30490.00 <span class="price-original">₹39500.00</span></div>
        </div>

        <!-- TV Card 3 -->
        <div class="product-card">
          <span class="discount-badge">51% OFF</span>
          <div class="product-img-box" style="background: #ffffff;">
            <svg viewBox="0 0 200 140" xmlns="http://www.w3.org/2000/svg">
              <rect x="20" y="10" width="160" height="100" rx="4" fill="#0284c7" stroke="#334155" stroke-width="2"/>
              <rect x="25" y="15" width="150" height="90" fill="#0f172a"/>
              <path d="M 40,80 Q 100,20 160,80" fill="none" stroke="#22c55e" stroke-width="4"/>
              <path d="M 85,110 L 75,130 M 115,110 L 125,130" stroke="#334155" stroke-width="3"/>
            </svg>
          </div>
          <div class="delivery-tag"><i class="bi bi-lightning-charge-fill"></i> 105 MINS</div>
          <div class="brand-name">TCL</div>
          <div class="product-name" style="min-height: auto;">TCL V6C 140 cm (55 inch) 4 Star 4K Ultra HD LED Smart Google TV</div>
          <div style="font-size: 0.78rem; color: #777; margin-top: 8px;">1 Unit</div>
          <div class="price-row" style="margin-top: 6px;">₹34990.00 <span class="price-original">₹71850.00</span></div>
        </div>

      </section>

    </div>

  </main>

<?php else: ?>
  <!-- ================================================================= -->
  <!-- FULL HOMEPAGE VIEW                                                -->
  <!-- ================================================================= -->
  <main class="container">
    
    <!-- Promo Pills Row -->
    <div class="promo-pills-row">
      <div class="promo-pill">EGGS, MEAT AND FISH</div>
      <div class="promo-pill neupass">⚡ NEUPASS</div>
      <div class="promo-pill ayurveda">AYURVEDA</div>
      <div class="promo-pill">BUY MORE SAVE MORE</div>
      <div class="promo-pill">DEALS OF THE WEEK</div>
      <div class="promo-pill">COMBO STORE</div>
    </div>

    <!-- My Smart Basket Section -->
    <section class="smart-basket-section">
      <div class="smart-basket-header">
        <h2 class="basket-title">My Smart Basket</h2>
        <div class="basket-controls">
          <a href="#" style="font-weight: 600;">View All</a>
          <button class="circle-btn"><i class="bi bi-chevron-left"></i></button>
          <button class="circle-btn"><i class="bi bi-chevron-right"></i></button>
        </div>
      </div>

      <!-- 4 Grocery Product Cards Grid -->
      <div class="product-grid-4">
        
        <!-- Card 1: Capsicum -->
        <div class="product-card">
          <span class="discount-badge">69% OFF</span>
          <div class="product-img-box">
            <!-- Vector Illustration of Capsicum on Wooden Board -->
            <svg viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
              <ellipse cx="80" cy="100" rx="60" ry="30" fill="#d97706"/>
              <ellipse cx="80" cy="95" rx="56" ry="26" fill="#f59e0b"/>
              <path d="M 60,65 C 50,75 50,95 65,100 C 80,105 95,95 95,75 C 95,60 75,55 60,65 Z" fill="#22c55e"/>
              <path d="M 68,55 Q 70,40 75,35" stroke="#15803d" stroke-width="4" fill="none" stroke-linecap="round"/>
            </svg>
          </div>
          <div class="delivery-tag"><i class="bi bi-lightning-charge-fill"></i> 31 MINS</div>
          <div class="brand-name">fresho!</div>
          <div class="product-name">Capsicum - Green</div>
          <select class="qty-select">
            <option>500 g</option>
            <option>250 g</option>

            <option>1 kg</option>
          </select>
          <div class="price-row">₹20.00 <span class="price-original">₹64.00</span></div>
          <div class="card-footer-actions">
            <button class="btn-heart"><i class="bi bi-heart"></i></button>
            <button class="btn-add-red">Add</button>
          </div>
        </div>

        <!-- Card 2: Carrot -->
        <div class="product-card">
          <span class="discount-badge">72% OFF</span>
          <div class="product-img-box">
            <!-- Vector Illustration of Carrots -->
            <svg viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
              <ellipse cx="80" cy="100" rx="60" ry="30" fill="#d97706"/>
              <ellipse cx="80" cy="95" rx="56" ry="26" fill="#f59e0b"/>
              <polygon points="55,85 105,45 115,55 65,95" fill="#ea580c"/>
              <polygon points="45,95 95,55 105,65 55,105" fill="#f97316"/>
              <path d="M 105,45 L 125,35 M 105,45 L 120,45" stroke="#16a34a" stroke-width="3" fill="none"/>
            </svg>
          </div>
          <div class="delivery-tag"><i class="bi bi-lightning-charge-fill"></i> 31 MINS</div>
          <div class="brand-name">fresho!</div>
          <div class="product-name">Carrot - Orange</div>
          <select class="qty-select">
            <option>500 g</option>
            <option>250 g</option>
            <option>1 kg</option>
          </select>
          <div class="price-row">₹20.00 <span class="price-original">₹72.00</span></div>
          <div class="card-footer-actions">
            <button class="btn-heart"><i class="bi bi-heart"></i></button>
            <button class="btn-add-red">Add</button>
          </div>
        </div>

        <!-- Card 3: Cauliflower -->
        <div class="product-card">
          <span class="discount-badge">45% OFF</span>
          <div class="product-img-box">
            <!-- Vector Illustration of Cauliflower -->
            <svg viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
              <ellipse cx="80" cy="100" rx="60" ry="30" fill="#d97706"/>
              <ellipse cx="80" cy="95" rx="56" ry="26" fill="#f59e0b"/>
              <circle cx="80" cy="65" r="30" fill="#fef08a"/>
              <path d="M 50,85 C 40,65 60,50 80,60 C 100,50 120,65 110,85 Z" fill="#16a34a" opacity="0.8"/>
            </svg>
          </div>
          <div class="delivery-tag"><i class="bi bi-lightning-charge-fill"></i> 31 MINS</div>
          <div class="brand-name">fresho!</div>
          <div class="product-name">Cauliflower</div>
          <select class="qty-select">
            <option>1 pc - (approx. 400 to 600 g)</option>
          </select>
          <div class="price-row">₹24.00 <span class="price-original">₹43.75</span></div>
          <div class="card-footer-actions">
            <button class="btn-heart"><i class="bi bi-heart"></i></button>
            <button class="btn-add-red">Add</button>
          </div>
        </div>

        <!-- Card 4: Coriander Leaves -->
        <div class="product-card">
          <span class="discount-badge">39% OFF</span>
          <div class="product-img-box">
            <!-- Vector Illustration of Coriander -->
            <svg viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
              <ellipse cx="80" cy="100" rx="60" ry="30" fill="#d97706"/>
              <ellipse cx="80" cy="95" rx="56" ry="26" fill="#f59e0b"/>
              <path d="M 60,90 Q 75,50 90,40 M 70,85 Q 85,55 105,45 M 50,85 Q 65,60 75,50" stroke="#15803d" stroke-width="3" fill="none"/>
              <circle cx="90" cy="40" r="8" fill="#22c55e"/>
              <circle cx="105" cy="45" r="8" fill="#22c55e"/>
              <circle cx="75" cy="50" r="8" fill="#22c55e"/>
            </svg>
          </div>
          <div class="delivery-tag"><i class="bi bi-lightning-charge-fill"></i> 31 MINS</div>
          <div class="brand-name">fresho!</div>
          <div class="product-name">Coriander Leaves</div>
          <select class="qty-select">
            <option>250 g</option>
            <option>100 g</option>
          </select>
          <div class="price-row">₹30.00 <span class="price-original">₹49.00</span></div>
          <div class="card-footer-actions">
            <button class="btn-heart"><i class="bi bi-heart"></i></button>
            <button class="btn-add-red">Add</button>
          </div>
        </div>

      </div>
    </section>

  </main>
<?php endif; ?>

</body>
</html>