<?php
$is_search = isset($_GET['q']) || isset($_GET['s']) || isset($_GET['search']);
$q = isset($_GET['q']) ? $_GET['q'] : (isset($_GET['s']) ? $_GET['s'] : (isset($_GET['search']) ? $_GET['search'] : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $is_search ? htmlspecialchars($q) . ' - FreshCart Search' : 'FreshCart - Fresh Groceries Delivered in 31 Mins'; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: #f5f7f2; color: #1a1a1a; line-height: 1.5;
    }
    a { color: inherit; text-decoration: none; }
    img { display: block; width: 100%; }
    .container { max-width: 1240px; margin: 0 auto; padding: 0 20px; }

    /* ── HEADER ── */
    .header { background: #fff; box-shadow: 0 1px 8px rgba(0,0,0,.08); position: sticky; top: 0; z-index: 200; }
    .header-inner { display: flex; align-items: center; gap: 20px; height: 68px; }
    .logo { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .logo-badge {
      background: #16a34a; color: #fff; font-size: 0.95rem; font-weight: 800;
      width: 40px; height: 40px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
    }
    .logo-name { font-size: 1.3rem; font-weight: 800; color: #111; letter-spacing: -.5px; }
    .logo-name span { color: #16a34a; }
    .logo-tagline { font-size: 0.6rem; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
    .search-wrap { flex: 1; max-width: 560px; position: relative; }
    .search-wrap input {
      width: 100%; height: 42px; padding: 0 16px 0 44px;
      border: 2px solid #e5e7eb; border-radius: 10px;
      font-size: .9rem; font-family: inherit; outline: none;
      transition: border-color .2s; background: #f9fafb;
    }
    .search-wrap input:focus { border-color: #16a34a; background: #fff; }
    .search-ico { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 1rem; }
    .location-chip {
      display: flex; align-items: center; gap: 6px;
      background: #f0fdf4; border: 1px solid #bbf7d0;
      padding: 6px 12px; border-radius: 8px;
      font-size: .8rem; color: #15803d; font-weight: 600; white-space: nowrap; flex-shrink: 0;
    }
    .header-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .btn-login {
      background: #1a1a1a; color: #fff; border: none;
      padding: 9px 20px; border-radius: 9px;
      font-size: .85rem; font-weight: 600; font-family: inherit;
      cursor: pointer; white-space: nowrap; transition: background .2s;
    }
    .btn-login:hover { background: #16a34a; }
    .cart-btn {
      width: 42px; height: 42px; background: #dc2626; color: #fff;
      border: none; border-radius: 50%; font-size: 1.1rem; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      position: relative; transition: background .2s;
    }
    .cart-btn:hover { background: #b91c1c; }
    .cart-count {
      position: absolute; top: -4px; right: -4px;
      background: #16a34a; color: #fff; font-size: .6rem; font-weight: 700;
      width: 16px; height: 16px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
    }

    /* ── CATEGORY NAV ── */
    .cat-nav { background: #fff; border-bottom: 1px solid #f3f4f6; padding: 10px 0; }
    .cat-nav-inner { display: flex; align-items: center; gap: 8px; overflow-x: auto; scrollbar-width: none; }
    .cat-nav-inner::-webkit-scrollbar { display: none; }
    .btn-category {
      background: #16a34a; color: #fff; border: none;
      padding: 8px 16px; border-radius: 8px; font-size: .82rem; font-weight: 700;
      font-family: inherit; cursor: pointer; display: flex; align-items: center; gap: 6px;
      white-space: nowrap; flex-shrink: 0;
    }
    .cat-link {
      font-size: .82rem; font-weight: 600; color: #374151; white-space: nowrap;
      padding: 6px 12px; border-radius: 6px; transition: all .2s; flex-shrink: 0;
    }
    .cat-link:hover { background: #f0fdf4; color: #16a34a; }

    /* ── HERO ── */
    .hero { position: relative; height: 380px; overflow: hidden; }
    .hero-img { width: 100%; height: 100%; object-fit: cover; object-position: center; }
    .hero-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(90deg, rgba(0,0,0,.68) 0%, rgba(0,0,0,.3) 55%, transparent 100%);
      display: flex; align-items: center;
    }
    .hero-content { max-width: 1240px; margin: 0 auto; padding: 0 20px; width: 100%; }
    .hero-eyebrow {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(22,163,74,.9); color: #fff;
      font-size: .72rem; font-weight: 700; padding: 5px 12px; border-radius: 20px;
      margin-bottom: 14px; text-transform: uppercase; letter-spacing: 1px;
    }
    .hero-title { font-size: 2.5rem; font-weight: 800; color: #fff; line-height: 1.12; margin-bottom: 10px; max-width: 480px; }
    .hero-title span { color: #86efac; }
    .hero-sub { font-size: 1rem; color: rgba(255,255,255,.82); margin-bottom: 24px; max-width: 400px; }
    .hero-badges { display: flex; gap: 10px; flex-wrap: wrap; }
    .hero-badge {
      background: rgba(255,255,255,.15); backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,.3); color: #fff;
      font-size: .78rem; font-weight: 600; padding: 7px 14px; border-radius: 20px;
    }

    /* ── SECTIONS ── */
    .section { padding: 32px 0; }
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .section-title { font-size: 1.3rem; font-weight: 800; color: #111; }
    .section-title span { color: #16a34a; }
    .view-all { font-size: .82rem; font-weight: 600; color: #16a34a; display: flex; align-items: center; gap: 4px; }
    .view-all:hover { text-decoration: underline; }

    /* ── CATEGORY GRID ── */
    .cat-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; }
    .cat-card {
      background: #fff; border-radius: 14px; overflow: hidden;
      text-align: center; padding-bottom: 12px; cursor: pointer;
      border: 1px solid #f3f4f6; transition: transform .2s, box-shadow .2s;
    }
    .cat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.1); }
    .cat-card img { width: 100%; height: 90px; object-fit: cover; }
    .cat-card-name { font-size: .78rem; font-weight: 700; color: #1a1a1a; margin-top: 10px; padding: 0 8px; }
    .cat-card-count { font-size: .68rem; color: #9ca3af; margin-top: 2px; }

    /* ── PROMO STRIP ── */
    .promo-strip { display: flex; gap: 14px; margin-bottom: 32px; }
    .promo-card {
      flex: 1; border-radius: 14px; overflow: hidden;
      position: relative; height: 150px; cursor: pointer;
    }
    .promo-card img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
    .promo-card:hover img { transform: scale(1.05); }
    .promo-overlay { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: center; padding: 18px 22px; }
    .promo-card.green .promo-overlay  { background: linear-gradient(135deg, rgba(22,163,74,.88), rgba(5,150,105,.72)); }
    .promo-card.orange .promo-overlay { background: linear-gradient(135deg, rgba(234,88,12,.88), rgba(220,38,38,.72)); }
    .promo-card.purple .promo-overlay { background: linear-gradient(135deg, rgba(109,40,217,.88), rgba(76,29,149,.72)); }
    .promo-tag { font-size: .65rem; font-weight: 800; color: rgba(255,255,255,.8); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
    .promo-title { font-size: 1.05rem; font-weight: 800; color: #fff; line-height: 1.2; }
    .promo-cta { margin-top: 10px; font-size: .72rem; font-weight: 700; color: rgba(255,255,255,.9); display: flex; align-items: center; gap: 4px; }

    /* ── PRODUCT CARDS ── */
    .products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .product-card {
      background: #fff; border-radius: 14px; overflow: hidden;
      border: 1px solid #f3f4f6; transition: box-shadow .2s, transform .2s;
      display: flex; flex-direction: column; position: relative;
    }
    .product-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.1); transform: translateY(-2px); }
    .product-img-wrap { position: relative; height: 200px; overflow: hidden; background: #f9fafb; }
    .product-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
    .product-card:hover .product-img-wrap img { transform: scale(1.06); }
    .badge-off {
      position: absolute; top: 10px; left: 10px;
      background: #16a34a; color: #fff;
      font-size: .7rem; font-weight: 800; padding: 3px 8px; border-radius: 6px; z-index: 2;
    }
    .badge-speed {
      position: absolute; bottom: 10px; right: 10px;
      background: rgba(0,0,0,.65); color: #fff;
      font-size: .65rem; font-weight: 700; padding: 3px 8px; border-radius: 6px;
      display: flex; align-items: center; gap: 3px; z-index: 2;
    }
    .badge-speed i { color: #4ade80; }
    .btn-wish {
      position: absolute; top: 10px; right: 10px;
      width: 30px; height: 30px; background: rgba(255,255,255,.92);
      border: none; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: #9ca3af; font-size: .85rem; transition: color .2s; z-index: 2;
    }
    .btn-wish:hover { color: #dc2626; }
    .product-body { padding: 12px 14px 14px; flex: 1; display: flex; flex-direction: column; }
    .product-brand { font-size: .7rem; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
    .product-name { font-size: .9rem; font-weight: 700; color: #111; margin-bottom: 10px; flex: 1; }
    .product-qty {
      width: 100%; height: 32px; border: 1px solid #e5e7eb; border-radius: 7px;
      padding: 0 10px; font-size: .78rem; font-family: inherit;
      background: #f9fafb; margin-bottom: 10px; cursor: pointer;
    }
    .product-price-row { display: flex; align-items: center; justify-content: space-between; }
    .product-price { font-size: 1rem; font-weight: 800; color: #111; }
    .product-price-old { font-size: .78rem; color: #9ca3af; text-decoration: line-through; margin-left: 6px; font-weight: 400; }
    .btn-add {
      background: #fff; border: 2px solid #16a34a; color: #16a34a;
      font-size: .82rem; font-weight: 700; font-family: inherit;
      padding: 7px 18px; border-radius: 8px; cursor: pointer;
      transition: all .2s; white-space: nowrap;
    }
    .btn-add:hover { background: #16a34a; color: #fff; }

    /* ── DEAL BANNER ── */
    .deal-banner {
      background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%);
      border-radius: 16px; padding: 32px 40px;
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 32px; overflow: hidden; position: relative;
    }
    .deal-banner::after {
      content: ''; position: absolute; right: -60px; top: -60px;
      width: 240px; height: 240px; background: rgba(255,255,255,.05); border-radius: 50%;
    }
    .deal-label { font-size: .72rem; font-weight: 800; color: #86efac; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px; }
    .deal-title { font-size: 1.9rem; font-weight: 800; color: #fff; line-height: 1.15; margin-bottom: 6px; }
    .deal-sub { font-size: .9rem; color: rgba(255,255,255,.75); }
    .btn-deal {
      background: #fff; color: #064e3b; border: none;
      padding: 13px 30px; border-radius: 10px;
      font-size: .9rem; font-weight: 800; font-family: inherit;
      cursor: pointer; white-space: nowrap; transition: all .2s; flex-shrink: 0;
    }
    .btn-deal:hover { background: #86efac; }

    /* ── FOOTER ── */
    .footer { background: #111; color: #9ca3af; padding: 48px 0 24px; margin-top: 48px; }
    .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 36px; }
    .footer-logo { font-size: 1.3rem; font-weight: 800; color: #fff; margin-bottom: 10px; }
    .footer-logo span { color: #4ade80; }
    .footer-desc { font-size: .82rem; line-height: 1.7; margin-bottom: 16px; }
    .footer-socials { display: flex; gap: 12px; }
    .footer-socials a { color: #4ade80; font-size: 1.1rem; transition: opacity .2s; }
    .footer-socials a:hover { opacity: .7; }
    .footer-h { font-size: .82rem; font-weight: 700; color: #fff; margin-bottom: 14px; text-transform: uppercase; letter-spacing: .5px; }
    .footer-links { list-style: none; }
    .footer-links li { margin-bottom: 8px; }
    .footer-links a { font-size: .82rem; color: #9ca3af; transition: color .2s; }
    .footer-links a:hover { color: #4ade80; }
    .footer-bottom { border-top: 1px solid #1f2937; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; font-size: .78rem; }

    /* ── SEARCH RESULTS ── */
    .breadcrumb { font-size: .8rem; color: #6b7280; padding: 14px 0 10px; display: flex; align-items: center; gap: 6px; }
    .search-status { font-size: .95rem; color: #374151; padding: 12px 0 20px; border-bottom: 1px solid #e5e7eb; margin-bottom: 20px; }
    .filter-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
    .filter-chip {
      background: #fff; border: 1px solid #e5e7eb; padding: 6px 14px; border-radius: 20px;
      font-size: .78rem; font-weight: 600; color: #374151; cursor: pointer; transition: all .2s;
    }
    .filter-chip:hover, .filter-chip.active { background: #16a34a; border-color: #16a34a; color: #fff; }
    .sort-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .result-count { font-size: .85rem; color: #6b7280; }
    .sort-select { border: 1px solid #e5e7eb; padding: 7px 14px; border-radius: 8px; font-size: .82rem; font-family: inherit; background: #fff; }
    .search-layout { display: grid; grid-template-columns: 220px 1fr; gap: 24px; align-items: start; }
    .search-sidebar { background: #fff; border-radius: 14px; padding: 20px; border: 1px solid #f3f4f6; position: sticky; top: 90px; }
    .sidebar-section { margin-bottom: 24px; }
    .sidebar-section:last-child { margin-bottom: 0; }
    .sidebar-title { font-size: .82rem; font-weight: 800; color: #111; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px; }
    .sidebar-list { list-style: none; }
    .sidebar-list li { font-size: .82rem; color: #6b7280; padding: 5px 0; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: color .2s; }
    .sidebar-list li:hover { color: #16a34a; }
    .sidebar-list li input[type=checkbox] { accent-color: #16a34a; cursor: pointer; }
    .search-products { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }

    /* ── RESPONSIVE ── */
    @media (max-width: 1024px) {
      .cat-grid { grid-template-columns: repeat(4, 1fr); }
      .products-grid { grid-template-columns: repeat(3, 1fr); }
      .footer-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 768px) {
      .hero { height: 260px; }
      .hero-title { font-size: 1.6rem; }
      .products-grid, .search-products { grid-template-columns: repeat(2, 1fr); }
      .cat-grid { grid-template-columns: repeat(3, 1fr); }
      .search-layout { grid-template-columns: 1fr; }
      .deal-banner { flex-direction: column; gap: 20px; text-align: center; }
      .promo-strip { flex-direction: column; }
      .footer-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- HEADER -->
<header class="header">
  <div class="container header-inner">
    <a href="index.php" class="logo">
      <div class="logo-badge">fc</div>
      <div>
        <div class="logo-name">Fresh<span>Cart</span></div>
        <div class="logo-tagline">Fresh &amp; Fast Delivery</div>
      </div>
    </a>
    <form action="index.php" method="GET" class="search-wrap">
      <i class="bi bi-search search-ico"></i>
      <input type="text" name="q" value="<?php echo $q; ?>" placeholder="Search for vegetables, fruits, dairy...">
    </form>
    <div class="location-chip">
      <i class="bi bi-lightning-charge-fill"></i>
      <span>31 mins · Bangalore</span>
    </div>
    <div class="header-right">
      <button class="btn-login">Login / Sign Up</button>
      <button class="cart-btn" title="Cart">
        <i class="bi bi-cart3"></i>
        <span class="cart-count">0</span>
      </button>
    </div>
  </div>
</header>

<!-- CATEGORY NAV -->
<nav class="cat-nav">
  <div class="container cat-nav-inner">
    <button class="btn-category">Shop by Category <i class="bi bi-grid-3x3-gap-fill"></i></button>
    <a href="#" class="cat-link">🥦 Vegetables</a>
    <a href="#" class="cat-link">🍎 Fruits</a>
    <a href="#" class="cat-link">🥛 Dairy &amp; Eggs</a>
    <a href="#" class="cat-link">🍗 Meat &amp; Fish</a>
    <a href="#" class="cat-link">☕ Beverages</a>
    <a href="#" class="cat-link">🧁 Bakery</a>
    <a href="#" class="cat-link">🧴 Personal Care</a>
    <a href="#" class="cat-link">🏠 Household</a>
    <a href="#" class="cat-link">🍿 Snacks</a>
  </div>
</nav>

<?php if ($is_search): ?>
<!-- ══════════════════════════════════════
     SEARCH RESULTS
══════════════════════════════════════ -->
<main>
  <div class="container">
    <div class="breadcrumb">
      <i class="bi bi-house-door-fill"></i> <span>/</span> <span>Search Results</span>
    </div>

    <!-- Reflected XSS — intentionally unescaped for CTF lab -->
    <div class="search-status">
      Showing results for: <strong><?php echo $q; ?></strong>
    </div>

    <div class="filter-chips">
      <span class="filter-chip active">All</span>
      <span class="filter-chip">Vegetables</span>
      <span class="filter-chip">Fruits</span>
      <span class="filter-chip">Dairy</span>
      <span class="filter-chip">Beverages</span>
      <span class="filter-chip">Snacks</span>
      <span class="filter-chip">Meat &amp; Fish</span>
    </div>

    <div class="sort-row">
      <span class="result-count">Showing 9 results</span>
      <select class="sort-select">
        <option>Relevance</option>
        <option>Price: Low to High</option>
        <option>Price: High to Low</option>
        <option>Discount</option>
      </select>
    </div>

    <div class="search-layout">
      <aside class="search-sidebar">
        <div class="sidebar-section">
          <div class="sidebar-title">Category</div>
          <ul class="sidebar-list">
            <li><input type="checkbox"> Fresh Vegetables</li>
            <li><input type="checkbox"> Fresh Fruits</li>
            <li><input type="checkbox"> Dairy &amp; Eggs</li>
            <li><input type="checkbox"> Meat &amp; Fish</li>
            <li><input type="checkbox"> Snacks</li>
            <li><input type="checkbox"> Beverages</li>
          </ul>
        </div>
        <div class="sidebar-section">
          <div class="sidebar-title">Brand</div>
          <ul class="sidebar-list">
            <li><input type="checkbox"> farmco!</li>
            <li><input type="checkbox"> Organic Valley</li>
            <li><input type="checkbox"> Nature's Best</li>
            <li><input type="checkbox"> Farm Fresh</li>
          </ul>
        </div>
        <div class="sidebar-section">
          <div class="sidebar-title">Rating</div>
          <ul class="sidebar-list">
            <li><input type="checkbox"> ★★★★★ 5.0</li>
            <li><input type="checkbox"> ★★★★☆ 4.0+</li>
            <li><input type="checkbox"> ★★★☆☆ 3.0+</li>
          </ul>
        </div>
      </aside>

      <section class="search-products">
        <?php
        $search_items = [
          ['name'=>'Fresh Tomatoes 500g',   'brand'=>'farmco!',        'price'=>'&#8377;35',  'old'=>'&#8377;60',  'off'=>'42% OFF', 'img'=>'1546094096-0df4bcaaa337'],
          ['name'=>'Baby Spinach 200g',      'brand'=>'Organic Valley', 'price'=>'&#8377;49',  'old'=>'&#8377;80',  'off'=>'39% OFF', 'img'=>'1576045057995-568f588f82fb'],
          ['name'=>'Red Onions 500g',        'brand'=>'farmco!',        'price'=>'&#8377;29',  'old'=>'&#8377;50',  'off'=>'42% OFF', 'img'=>'1518977676601-b53f82aba655'],
          ['name'=>'Fresh Mangoes 1kg',      'brand'=>"Nature's Best",  'price'=>'&#8377;120', 'old'=>'&#8377;180', 'off'=>'33% OFF', 'img'=>'1591189863430-ab87e120f312'],
          ['name'=>'Lemon 250g',             'brand'=>'farmco!',        'price'=>'&#8377;25',  'old'=>'&#8377;40',  'off'=>'38% OFF', 'img'=>'1582449812516-9b59cc6d50ca'],
          ['name'=>'Royal Gala Apples 4pc',  'brand'=>'Farm Fresh',     'price'=>'&#8377;89',  'old'=>'&#8377;130', 'off'=>'32% OFF', 'img'=>'1567306301408-9b74779a11af'],
          ['name'=>'Bananas 6 pcs',          'brand'=>'farmco!',        'price'=>'&#8377;39',  'old'=>'&#8377;65',  'off'=>'40% OFF', 'img'=>'1603833665858-e61d17a86224'],
          ['name'=>'Cauliflower 1 pc',       'brand'=>'farmco!',        'price'=>'&#8377;19',  'old'=>'&#8377;35',  'off'=>'46% OFF', 'img'=>'1568584711075-3d021a7c3ca3'],
          ['name'=>'Button Mushrooms 200g',  'brand'=>'Organic Valley', 'price'=>'&#8377;55',  'old'=>'&#8377;85',  'off'=>'35% OFF', 'img'=>'1504545102780-26b9bab9d2a7'],
        ];
        foreach ($search_items as $item): ?>
        <div class="product-card">
          <div class="product-img-wrap">
            <img src="https://images.unsplash.com/photo-<?= $item['img'] ?>?w=400&h=250&fit=crop&auto=format&q=80"
                 alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy">
            <span class="badge-off"><?= $item['off'] ?></span>
            <span class="badge-speed"><i class="bi bi-lightning-charge-fill"></i> 31 MINS</span>
            <button class="btn-wish"><i class="bi bi-heart"></i></button>
          </div>
          <div class="product-body">
            <div class="product-brand"><?= htmlspecialchars($item['brand']) ?></div>
            <div class="product-name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="product-price-row">
              <div>
                <span class="product-price"><?= $item['price'] ?></span>
                <span class="product-price-old"><?= $item['old'] ?></span>
              </div>
              <button class="btn-add">Add</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </section>
    </div>
  </div>
</main>

<?php else: ?>
<!-- ══════════════════════════════════════
     HOMEPAGE
══════════════════════════════════════ -->

<!-- Hero Banner -->
<div class="hero">
  <img class="hero-img"
       src="https://images.unsplash.com/photo-1542838132-92c53300491e?w=1400&h=380&fit=crop&auto=format&q=85"
       alt="Fresh Groceries">
  <div class="hero-overlay">
    <div class="hero-content">
      <div class="hero-eyebrow"><i class="bi bi-lightning-charge-fill"></i> Express Delivery Available</div>
      <h1 class="hero-title">Fresh Groceries,<br><span>Delivered Fast</span></h1>
      <p class="hero-sub">From farm to your doorstep in just 31 minutes. 10,000+ products at the best prices.</p>
      <div class="hero-badges">
        <span class="hero-badge">🌿 100% Fresh</span>
        <span class="hero-badge">🚴 31-min Delivery</span>
        <span class="hero-badge">💰 Best Prices</span>
        <span class="hero-badge">🔒 Secure Checkout</span>
      </div>
    </div>
  </div>
</div>

<main>
  <div class="container">

    <!-- Shop by Category -->
    <section class="section">
      <div class="section-header">
        <h2 class="section-title">Shop by <span>Category</span></h2>
        <a href="#" class="view-all">View All <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="cat-grid">
        <?php
        $categories = [
          ['name'=>'Fruits & Veg',  'count'=>'500+ items', 'img'=>'1512621776951-a57141f2eefd'],
          ['name'=>'Dairy & Eggs',  'count'=>'180+ items', 'img'=>'1550583724-b2692b85b150'],
          ['name'=>'Meat & Fish',   'count'=>'220+ items', 'img'=>'1544025162-d76694265947'],
          ['name'=>'Beverages',     'count'=>'310+ items', 'img'=>'1544145945-f90425340c7e'],
          ['name'=>'Bakery',        'count'=>'120+ items', 'img'=>'1509440159596-0249088772ff'],
          ['name'=>'Snacks',        'count'=>'400+ items', 'img'=>'1621939514649-280e2ee25f60'],
        ];
        foreach ($categories as $cat): ?>
        <div class="cat-card">
          <img src="https://images.unsplash.com/photo-<?= $cat['img'] ?>?w=200&h=90&fit=crop&auto=format&q=80"
               alt="<?= htmlspecialchars($cat['name']) ?>">
          <div class="cat-card-name"><?= htmlspecialchars($cat['name']) ?></div>
          <div class="cat-card-count"><?= $cat['count'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Promo Strip -->
    <div class="promo-strip">
      <div class="promo-card green">
        <img src="https://images.unsplash.com/photo-1573246123716-6b1782bfc499?w=600&h=150&fit=crop&auto=format&q=80" alt="">
        <div class="promo-overlay">
          <div class="promo-tag">Limited Time</div>
          <div class="promo-title">Fresh Veggies<br>Up to 70% Off</div>
          <div class="promo-cta">Shop Now <i class="bi bi-arrow-right"></i></div>
        </div>
      </div>
      <div class="promo-card orange">
        <img src="https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=600&h=150&fit=crop&auto=format&q=80" alt="">
        <div class="promo-overlay">
          <div class="promo-tag">Deals of the Week</div>
          <div class="promo-title">Exotic Fruits<br>Starting &#8377;49</div>
          <div class="promo-cta">Explore <i class="bi bi-arrow-right"></i></div>
        </div>
      </div>
      <div class="promo-card purple">
        <img src="https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=600&h=150&fit=crop&auto=format&q=80" alt="">
        <div class="promo-overlay">
          <div class="promo-tag">New Arrivals</div>
          <div class="promo-title">Premium Tea<br>Collection</div>
          <div class="promo-cta">View All <i class="bi bi-arrow-right"></i></div>
        </div>
      </div>
    </div>

    <!-- Best Sellers -->
    <section class="section" style="padding-top:0">
      <div class="section-header">
        <h2 class="section-title">🛒 Best <span>Sellers</span></h2>
        <a href="#" class="view-all">View All <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="products-grid">
        <?php
        $best_sellers = [
          ['name'=>'Capsicum - Green 500g',  'brand'=>'farmco!', 'price'=>'&#8377;20', 'old'=>'&#8377;64', 'off'=>'69% OFF',
           'img'=>'1563565375-f3fdfdbefa83', 'qty'=>['500 g','250 g','1 kg']],
          ['name'=>'Fresh Carrots 500g',      'brand'=>'farmco!', 'price'=>'&#8377;20', 'old'=>'&#8377;72', 'off'=>'72% OFF',
           'img'=>'1447175008436-054170c2e979', 'qty'=>['500 g','250 g','1 kg']],
          ['name'=>'Cauliflower 1 pc',        'brand'=>'farmco!', 'price'=>'&#8377;24', 'old'=>'&#8377;44', 'off'=>'45% OFF',
           'img'=>'1568584711075-3d021a7c3ca3', 'qty'=>['1 pc (400–600 g)']],
          ['name'=>'Coriander Leaves 250g',   'brand'=>'farmco!', 'price'=>'&#8377;30', 'old'=>'&#8377;49', 'off'=>'39% OFF',
           'img'=>'1500771602571-19fc8d6e78e7', 'qty'=>['250 g','100 g']],
        ];
        foreach ($best_sellers as $p): ?>
        <div class="product-card">
          <div class="product-img-wrap">
            <img src="https://images.unsplash.com/photo-<?= $p['img'] ?>?w=400&h=200&fit=crop&auto=format&q=80"
                 alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
            <span class="badge-off"><?= $p['off'] ?></span>
            <span class="badge-speed"><i class="bi bi-lightning-charge-fill"></i> 31 MINS</span>
            <button class="btn-wish"><i class="bi bi-heart"></i></button>
          </div>
          <div class="product-body">
            <div class="product-brand"><?= htmlspecialchars($p['brand']) ?></div>
            <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
            <select class="product-qty">
              <?php foreach ($p['qty'] as $qty): ?><option><?= htmlspecialchars($qty) ?></option><?php endforeach; ?>
            </select>
            <div class="product-price-row">
              <div>
                <span class="product-price"><?= $p['price'] ?></span>
                <span class="product-price-old"><?= $p['old'] ?></span>
              </div>
              <button class="btn-add">Add</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Deal Banner -->
    <div class="deal-banner">
      <div>
        <div class="deal-label">🎉 Weekend Special Offer</div>
        <div class="deal-title">Get 20% Off<br>Your First Order</div>
        <div class="deal-sub">Use code <strong>FRESH20</strong> at checkout &middot; Min. order &#8377;299</div>
      </div>
      <button class="btn-deal">Shop Now &rarr;</button>
    </div>

    <!-- Fresh Picks -->
    <section class="section" style="padding-top:0">
      <div class="section-header">
        <h2 class="section-title">🌿 Fresh <span>Picks</span></h2>
        <a href="#" class="view-all">View All <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="products-grid">
        <?php
        $fresh_picks = [
          ['name'=>'Fresh Tomatoes 500g',   'brand'=>'farmco!',        'price'=>'&#8377;35',  'old'=>'&#8377;60',  'off'=>'42% OFF',
           'img'=>'1546094096-0df4bcaaa337', 'qty'=>['500 g','1 kg','250 g']],
          ['name'=>'Baby Spinach 200g',      'brand'=>'Organic Valley', 'price'=>'&#8377;49',  'old'=>'&#8377;80',  'off'=>'39% OFF',
           'img'=>'1576045057995-568f588f82fb', 'qty'=>['200 g','500 g']],
          ['name'=>'Fresh Mangoes 1kg',      'brand'=>"Nature's Best",  'price'=>'&#8377;120', 'old'=>'&#8377;180', 'off'=>'33% OFF',
           'img'=>'1591189863430-ab87e120f312', 'qty'=>['1 kg','500 g']],
          ['name'=>'Royal Gala Apples 4pc',  'brand'=>'Farm Fresh',     'price'=>'&#8377;89',  'old'=>'&#8377;130', 'off'=>'32% OFF',
           'img'=>'1567306301408-9b74779a11af', 'qty'=>['4 pcs','6 pcs']],
        ];
        foreach ($fresh_picks as $p): ?>
        <div class="product-card">
          <div class="product-img-wrap">
            <img src="https://images.unsplash.com/photo-<?= $p['img'] ?>?w=400&h=200&fit=crop&auto=format&q=80"
                 alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
            <span class="badge-off"><?= $p['off'] ?></span>
            <span class="badge-speed"><i class="bi bi-lightning-charge-fill"></i> 31 MINS</span>
            <button class="btn-wish"><i class="bi bi-heart"></i></button>
          </div>
          <div class="product-body">
            <div class="product-brand"><?= htmlspecialchars($p['brand']) ?></div>
            <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
            <select class="product-qty">
              <?php foreach ($p['qty'] as $qty): ?><option><?= htmlspecialchars($qty) ?></option><?php endforeach; ?>
            </select>
            <div class="product-price-row">
              <div>
                <span class="product-price"><?= $p['price'] ?></span>
                <span class="product-price-old"><?= $p['old'] ?></span>
              </div>
              <button class="btn-add">Add</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

  </div>
</main>

<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-logo">Fresh<span>Cart</span></div>
        <p class="footer-desc">Your neighborhood grocery store, now online. We deliver fresh produce, dairy, and essentials to your door in 31 minutes.</p>
        <div class="footer-socials">
          <a href="#"><i class="bi bi-instagram"></i></a>
          <a href="#"><i class="bi bi-twitter-x"></i></a>
          <a href="#"><i class="bi bi-facebook"></i></a>
        </div>
      </div>
      <div>
        <div class="footer-h">Company</div>
        <ul class="footer-links">
          <li><a href="#">About Us</a></li>
          <li><a href="#">Careers</a></li>
          <li><a href="#">Press</a></li>
          <li><a href="#">Blog</a></li>
        </ul>
      </div>
      <div>
        <div class="footer-h">Help</div>
        <ul class="footer-links">
          <li><a href="#">FAQ</a></li>
          <li><a href="#">Track Order</a></li>
          <li><a href="#">Return Policy</a></li>
          <li><a href="#">Contact Us</a></li>
        </ul>
      </div>
      <div>
        <div class="footer-h">Legal</div>
        <ul class="footer-links">
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms of Service</a></li>
          <li><a href="#">Cookie Policy</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; 2024 FreshCart. All rights reserved.</span>
      <span>Made with 🌿 for fresh living</span>
    </div>
  </div>
</footer>

<?php endif; ?>
</body>
</html>
