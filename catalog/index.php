<?php
// Lab 1202 — Reflected HTML Injection via Search Parameter
// Vulnerability: GET parameter ?q= echoed directly via echo $_GET['q'] without htmlspecialchars
// Common real-world pattern found across e-commerce platforms, SaaS, and custom PHP stores

// ── Hardcoded product catalogue (no DB needed — reflected HTMLI is stateless) ─
$products = [
    ['id'=>1, 'name'=>'iPhone 15 Pro Max',         'category'=>'Smartphones',  'price'=>134900, 'rating'=>4.5, 'reviews'=>3821, 'img_color'=>'#1c1c1e', 'badge'=>'BESTSELLER'],
    ['id'=>2, 'name'=>'Samsung Galaxy S24 Ultra',  'category'=>'Smartphones',  'price'=>124999, 'rating'=>4.4, 'reviews'=>2104, 'img_color'=>'#3a3153', 'badge'=>''],
    ['id'=>3, 'name'=>'Sony WH-1000XM5 Headphones','category'=>'Audio',        'price'=>29990,  'rating'=>4.7, 'reviews'=>5670, 'img_color'=>'#191919', 'badge'=>'TOP RATED'],
    ['id'=>4, 'name'=>'MacBook Air M3 13"',         'category'=>'Laptops',      'price'=>114900, 'rating'=>4.8, 'reviews'=>1923, 'img_color'=>'#c0bdb8', 'badge'=>'NEW'],
    ['id'=>5, 'name'=>'Dell XPS 15 Laptop',         'category'=>'Laptops',      'price'=>159990, 'rating'=>4.3, 'reviews'=>876,  'img_color'=>'#2d2d2d', 'badge'=>''],
    ['id'=>6, 'name'=>'iPad Pro 12.9" M4',          'category'=>'Tablets',      'price'=>109900, 'rating'=>4.6, 'reviews'=>1342, 'img_color'=>'#d1d1d6', 'badge'=>''],
    ['id'=>7, 'name'=>'Canon EOS R8 Camera',        'category'=>'Cameras',      'price'=>89999,  'rating'=>4.5, 'reviews'=>642,  'img_color'=>'#1a1a1a', 'badge'=>''],
    ['id'=>8, 'name'=>'Apple Watch Ultra 2',        'category'=>'Wearables',    'price'=>89900,  'rating'=>4.6, 'reviews'=>2213, 'img_color'=>'#f5f1e8', 'badge'=>''],
    ['id'=>9, 'name'=>'JBL Flip 6 Speaker',         'category'=>'Audio',        'price'=>13999,  'rating'=>4.4, 'reviews'=>8901, 'img_color'=>'#e53935', 'badge'=>''],
    ['id'=>10,'name'=>'Logitech MX Master 3S',      'category'=>'Accessories',  'price'=>10495,  'rating'=>4.7, 'reviews'=>4321, 'img_color'=>'#3c3c3c', 'badge'=>''],
    ['id'=>11,'name'=>'Samsung 4K QLED 55" TV',     'category'=>'TVs',          'price'=>74990,  'rating'=>4.4, 'reviews'=>1560, 'img_color'=>'#0d0d0d', 'badge'=>''],
    ['id'=>12,'name'=>'Kindle Paperwhite 2024',     'category'=>'E-readers',    'price'=>14999,  'rating'=>4.6, 'reviews'=>7230, 'img_color'=>'#f5f0e8', 'badge'=>''],
];

$categories = ['Smartphones','Audio','Laptops','Tablets','Cameras','Wearables','Accessories','TVs','E-readers'];

// ── Search logic ──────────────────────────────────────────────────────────────
$hasSearch   = isset($_GET['q']);
$rawQuery    = $_GET['q'] ?? '';          // ⚠ raw, never sanitised
$safeQuery   = htmlspecialchars($rawQuery, ENT_QUOTES); // only used for element attrs
$results     = [];
if ($hasSearch && trim($rawQuery) !== '') {
    foreach ($products as $p) {
        if (stripos($p['name'], $rawQuery) !== false || stripos($p['category'], $rawQuery) !== false) {
            $results[] = $p;
        }
    }
}

function fmt($n){ return '₹'.number_format($n); }
function stars($r){
    $full = floor($r); $half = ($r - $full) >= 0.5 ? 1 : 0; $empty = 5 - $full - $half;
    return str_repeat('★',$full).str_repeat('½',$half).str_repeat('☆',$empty);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if ($hasSearch): ?>
<title>Search: <?php echo $rawQuery; ?> — ShopZone</title>
<?php else: ?>
<title>ShopZone — India's Favourite Online Store</title>
<?php endif; ?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:14px;background:#f1f3f6;color:#212121;}
a{text-decoration:none;color:inherit;}

/* ── ShopZone Navbar ─────────────────────────────────────────────────────── */
.sz-nav{background:#2874f0;position:sticky;z-index:900;box-shadow:0 1px 4px rgba(0,0,0,.15);}
.sz-nav-inner{max-width:1280px;margin:0 auto;padding:0 16px;display:flex;align-items:center;height:56px;gap:12px;}
.sz-logo{color:#fff;font-size:1.15rem;font-weight:800;letter-spacing:-.5px;flex-shrink:0;display:flex;flex-direction:column;line-height:1.1;}
.sz-logo span{font-size:.55rem;font-weight:400;opacity:.85;font-style:italic;}
.sz-search-wrap{flex:1;display:flex;max-width:680px;}
.sz-search-wrap form{display:flex;width:100%;}
.sz-search-input{flex:1;border:none;outline:none;padding:0 16px;font-size:.88rem;height:36px;border-radius:2px 0 0 2px;background:#fff;}
.sz-search-btn{background:#fb641b;border:none;padding:0 16px;height:36px;cursor:pointer;border-radius:0 2px 2px 0;display:flex;align-items:center;}
.sz-search-btn svg{width:18px;height:18px;fill:#fff;}
.sz-nav-right{margin-left:auto;display:flex;align-items:center;gap:20px;color:#fff;flex-shrink:0;}
.sz-nav-item{display:flex;flex-direction:column;align-items:center;font-size:.72rem;cursor:pointer;gap:1px;}
.sz-nav-item svg{width:20px;height:20px;fill:#fff;}
.sz-nav-item span{font-size:.7rem;}

/* ── Category Bar ────────────────────────────────────────────────────────── */
.sz-cat-bar{background:#fff;border-bottom:1px solid #e0e0e0;overflow-x:auto;white-space:nowrap;}
.sz-cat-inner{max-width:1280px;margin:0 auto;padding:0 16px;display:flex;gap:0;}
.sz-cat-item{display:inline-flex;align-items:center;padding:10px 16px;font-size:.78rem;font-weight:600;color:#212121;cursor:pointer;border-bottom:2px solid transparent;}
.sz-cat-item:hover{color:#2874f0;border-bottom-color:#2874f0;}

/* ── Hero Banner ─────────────────────────────────────────────────────────── */
.sz-hero{max-width:1280px;margin:16px auto 0;padding:0 16px;}
.sz-hero-inner{background:linear-gradient(135deg,#1a237e 0%,#283593 40%,#1565c0 100%);border-radius:4px;padding:40px 48px;display:flex;align-items:center;justify-content:space-between;overflow:hidden;position:relative;}
.sz-hero-inner::after{content:'';position:absolute;right:-40px;top:-40px;width:280px;height:280px;border-radius:50%;background:rgba(255,255,255,.05);}
.sz-hero-text h1{color:#fff;font-size:2rem;font-weight:800;line-height:1.2;margin-bottom:8px;}
.sz-hero-text p{color:rgba(255,255,255,.85);font-size:.88rem;margin-bottom:20px;}
.sz-hero-btn{background:#fb641b;color:#fff;border:none;padding:12px 28px;border-radius:2px;font-size:.88rem;font-weight:700;cursor:pointer;}
.sz-hero-graphic{display:flex;gap:12px;flex-shrink:0;}
.sz-hero-phone{width:70px;height:120px;background:rgba(255,255,255,.12);border-radius:10px;border:2px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;}
.sz-hero-phone svg{width:32px;height:32px;fill:rgba(255,255,255,.7);}

/* ── Section Heading ─────────────────────────────────────────────────────── */
.sz-section{max-width:1280px;margin:20px auto 0;padding:0 16px;}
.sz-section-head{background:#fff;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f0f0f0;}
.sz-section-title{font-size:1.1rem;font-weight:800;color:#212121;}
.sz-section-view{color:#2874f0;font-size:.82rem;font-weight:600;}

/* ── Product Grid ────────────────────────────────────────────────────────── */
.sz-product-grid{background:#fff;display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:#f1f3f6;}
@media(max-width:900px){.sz-product-grid{grid-template-columns:repeat(2,1fr);}}
.sz-product-card{background:#fff;padding:20px 16px;cursor:pointer;transition:box-shadow .15s;display:flex;flex-direction:column;align-items:center;text-align:center;}
.sz-product-card:hover{box-shadow:0 2px 16px rgba(0,0,0,.08);z-index:1;}
.sz-product-img{width:140px;height:140px;border-radius:4px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;flex-shrink:0;position:relative;}
.sz-product-img svg{width:64px;height:64px;fill:rgba(255,255,255,.7);}
.sz-product-badge{position:absolute;top:6px;left:6px;background:#fb641b;color:#fff;font-size:.58rem;font-weight:800;padding:2px 5px;border-radius:2px;letter-spacing:.5px;}
.sz-product-name{font-size:.85rem;font-weight:600;color:#212121;margin-bottom:4px;line-height:1.3;}
.sz-product-cat{font-size:.72rem;color:#878787;margin-bottom:6px;}
.sz-product-stars{color:#f57c00;font-size:.78rem;margin-bottom:2px;}
.sz-product-reviews{font-size:.7rem;color:#878787;margin-bottom:8px;}
.sz-product-price{font-size:1rem;font-weight:800;color:#212121;margin-bottom:2px;}
.sz-product-old{font-size:.75rem;color:#878787;text-decoration:line-through;}
.sz-add-btn{margin-top:10px;background:#ff9f00;border:none;color:#fff;padding:7px 20px;border-radius:2px;font-size:.78rem;font-weight:700;cursor:pointer;width:100%;}
.sz-add-btn:hover{background:#f08c00;}

/* ── Search Results Page ─────────────────────────────────────────────────── */
.sz-search-page{max-width:1280px;margin:16px auto;padding:0 16px;}
.sz-breadcrumb{font-size:.78rem;color:#878787;margin-bottom:12px;}
.sz-breadcrumb a{color:#2874f0;}
.sz-breadcrumb span{margin:0 4px;}
.sz-results-wrap{display:grid;grid-template-columns:220px 1fr;gap:16px;align-items:start;}
@media(max-width:768px){.sz-results-wrap{grid-template-columns:1fr;}}

/* Filters sidebar */
.sz-filters{background:#fff;border-radius:2px;padding:0;}
.sz-filter-head{padding:14px 16px;font-size:.88rem;font-weight:800;border-bottom:1px solid #f0f0f0;color:#212121;}
.sz-filter-section{padding:14px 16px;border-bottom:1px solid #f5f5f5;}
.sz-filter-title{font-size:.78rem;font-weight:700;color:#212121;margin-bottom:8px;text-transform:uppercase;letter-spacing:.3px;}
.sz-filter-item{display:flex;align-items:center;gap:8px;padding:3px 0;font-size:.78rem;color:#333;cursor:pointer;}
.sz-filter-item:hover{color:#2874f0;}
.sz-filter-check{width:14px;height:14px;border:1px solid #ccc;border-radius:2px;flex-shrink:0;}

/* Results main */
.sz-results-main{}
.sz-results-header{background:#fff;padding:12px 20px;margin-bottom:1px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.sz-results-count{font-size:.82rem;color:#878787;}

/* ⚠ VULNERABLE: this element's content is the injection point */
.sz-results-query{font-size:1rem;font-weight:600;color:#212121;}

.sz-sort-wrap{margin-left:auto;display:flex;align-items:center;gap:8px;font-size:.78rem;color:#878787;}
.sz-sort-opt{padding:4px 10px;border-radius:2px;cursor:pointer;}
.sz-sort-opt.active{background:#2874f0;color:#fff;font-weight:600;}
.sz-results-list{display:flex;flex-direction:column;gap:1px;}
.sz-result-card{background:#fff;padding:20px;display:flex;gap:20px;cursor:pointer;}
.sz-result-card:hover{box-shadow:0 2px 8px rgba(0,0,0,.06);}
.sz-result-img{width:130px;height:130px;border-radius:4px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.sz-result-img svg{width:56px;height:56px;fill:rgba(255,255,255,.7);}
.sz-result-info{flex:1;}
.sz-result-name{font-size:.95rem;font-weight:600;color:#212121;margin-bottom:4px;}
.sz-result-rating{display:flex;align-items:center;gap:6px;margin-bottom:8px;}
.sz-result-stars-badge{background:#388e3c;color:#fff;font-size:.72rem;font-weight:700;padding:2px 6px;border-radius:2px;display:flex;align-items:center;gap:2px;}
.sz-result-stars-badge svg{width:10px;height:10px;fill:#fff;}
.sz-result-reviews{font-size:.75rem;color:#878787;}
.sz-result-price{font-size:1.2rem;font-weight:800;color:#212121;margin-bottom:2px;}
.sz-result-old{font-size:.78rem;color:#878787;text-decoration:line-through;display:inline;margin-right:6px;}
.sz-result-discount{font-size:.78rem;color:#388e3c;font-weight:700;}
.sz-result-delivery{font-size:.75rem;color:#388e3c;margin-top:6px;}
.sz-result-add{margin-top:12px;background:#ff9f00;border:none;color:#fff;padding:10px 28px;border-radius:2px;font-size:.82rem;font-weight:700;cursor:pointer;}

/* No results */
.sz-no-results{background:#fff;padding:60px 20px;text-align:center;}
.sz-no-results svg{width:80px;height:80px;fill:#ccc;margin-bottom:16px;}
.sz-no-results h3{font-size:1rem;font-weight:700;color:#212121;margin-bottom:6px;}
.sz-no-results p{font-size:.82rem;color:#878787;}

/* ── Vuln Callout ─────────────────────────────────────────────────────────── */
.sz-vuln-box{background:#fff8f3;border:1px solid #fb923c;border-left:4px solid #fb641b;border-radius:4px;padding:14px 16px;margin-bottom:16px;font-size:.82rem;color:#7c2d12;line-height:1.6;}
.sz-vuln-box strong{display:block;margin-bottom:4px;}
.sz-vuln-box code{background:#fee2d5;border-radius:3px;padding:1px 5px;font-family:monospace;font-size:.78rem;}
.sz-payload-examples{margin-top:8px;display:flex;flex-direction:column;gap:4px;}
.sz-payload{background:#fff;border:1px solid #fbd8c0;border-radius:3px;padding:4px 8px;font-family:monospace;font-size:.72rem;cursor:pointer;color:#c2410c;word-break:break-all;}
.sz-payload:hover{background:#fff3ed;}

/* ── Footer ──────────────────────────────────────────────────────────────── */
.sz-footer{background:#172337;color:#9ea0a5;margin-top:40px;padding:32px 0;}
.sz-footer-inner{max-width:1280px;margin:0 auto;padding:0 16px;display:grid;grid-template-columns:repeat(4,1fr);gap:24px;}
@media(max-width:768px){.sz-footer-inner{grid-template-columns:repeat(2,1fr);}}
.sz-footer h4{color:#fff;font-size:.82rem;margin-bottom:12px;}
.sz-footer ul{list-style:none;display:flex;flex-direction:column;gap:7px;}
.sz-footer li{font-size:.78rem;cursor:pointer;}
.sz-footer li:hover{color:#fff;}
.sz-footer-bottom{border-top:1px solid rgba(255,255,255,.08);margin-top:24px;padding-top:16px;text-align:center;font-size:.72rem;}
</style>
</head>
<body>

<!-- ── ShopZone Navbar ────────────────────────────────────────────────────── -->
<nav class="sz-nav">
  <div class="sz-nav-inner">
    <div class="sz-logo">ShopZone<span>Explore Plus</span></div>
    <div class="sz-search-wrap">
      <form method="GET" action="index.php" style="width:100%;display:flex;">
        <input type="text" name="q" class="sz-search-input" placeholder='Search for products, brands and more'
               value="<?php echo $safeQuery; ?>">
        <button type="submit" class="sz-search-btn">
          <svg viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round"/></svg>
        </button>
      </form>
    </div>
    <div class="sz-nav-right">
      <div class="sz-nav-item">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span>Login</span>
      </div>
      <div class="sz-nav-item">
        <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
        <span>Wishlist</span>
      </div>
      <div class="sz-nav-item" style="position:relative;">
        <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        <span>Cart</span>
        <div style="position:absolute;top:-4px;right:-4px;background:#fb641b;color:#fff;width:16px;height:16px;border-radius:50%;font-size:.6rem;display:flex;align-items:center;justify-content:center;font-weight:700;">3</div>
      </div>
    </div>
  </div>
</nav>

<!-- ── Category Bar ───────────────────────────────────────────────────────── -->
<div class="sz-cat-bar">
  <div class="sz-cat-inner">
    <?php foreach($categories as $cat): ?>
    <a href="index.php?q=<?php echo urlencode($cat); ?>" class="sz-cat-item"><?php echo htmlspecialchars($cat); ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if (!$hasSearch): ?>
<!-- ════════════════════════════════════════════════════════════════════════
     HOMEPAGE
════════════════════════════════════════════════════════════════════════ -->

<!-- Hero Banner -->
<div class="sz-hero">
  <div class="sz-hero-inner">
    <div class="sz-hero-text">
      <h1>Up to 70% Off<br>Top Electronics</h1>
      <p>Smartphones, Laptops, Audio &amp; more — deals ending soon</p>
      <button class="sz-hero-btn" onclick="document.querySelector('.sz-search-input').focus()">Shop Now</button>
    </div>
    <div class="sz-hero-graphic">
      <div class="sz-hero-phone">
        <svg viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18.01" stroke-width="2" stroke="rgba(255,255,255,.7)"/></svg>
      </div>
      <div class="sz-hero-phone" style="width:90px;height:130px;">
        <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
      </div>
      <div class="sz-hero-phone">
        <svg viewBox="0 0 24 24"><path d="M3 18v-6a9 9 0 0118 0v6"/><path d="M21 19a2 2 0 01-2 2H5a2 2 0 01-2-2v-3a2 2 0 012-2h14a2 2 0 012 2v3z"/></svg>
      </div>
    </div>
  </div>
</div>

<!-- Products Grid -->
<div class="sz-section" style="margin-top:16px;">
  <div class="sz-section-head">
    <div class="sz-section-title">Best Sellers</div>
    <div class="sz-section-view">View All →</div>
  </div>
  <div class="sz-product-grid">
    <?php foreach(array_slice($products,0,8) as $p): ?>
    <div class="sz-product-card" onclick="window.location='index.php?q=<?php echo urlencode($p['name']); ?>'">
      <div class="sz-product-img" style="background:<?php echo htmlspecialchars($p['img_color']); ?>;">
        <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        <?php if($p['badge']): ?><div class="sz-product-badge"><?php echo htmlspecialchars($p['badge']); ?></div><?php endif; ?>
      </div>
      <div class="sz-product-name"><?php echo htmlspecialchars($p['name']); ?></div>
      <div class="sz-product-cat"><?php echo htmlspecialchars($p['category']); ?></div>
      <div class="sz-product-stars"><?php echo stars($p['rating']); ?> <span style="color:#878787;font-size:.7rem;">(<?php echo number_format($p['reviews']); ?>)</span></div>
      <div class="sz-product-price"><?php echo fmt($p['price']); ?></div>
      <div class="sz-product-old"><?php echo fmt((int)($p['price']*1.25)); ?></div>
      <button class="sz-add-btn">ADD TO CART</button>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php else: ?>
<!-- ════════════════════════════════════════════════════════════════════════
     SEARCH RESULTS PAGE — Vulnerable rendering
════════════════════════════════════════════════════════════════════════ -->
<div class="sz-search-page">

  <!-- Breadcrumb (safe) -->
  <div class="sz-breadcrumb">
    <a href="index.php">Home</a><span>›</span>Search<span>›</span>
    <span><?php echo $safeQuery; ?></span>
  </div>

  <div class="sz-results-wrap">

    <!-- Filters sidebar (cosmetic) -->
    <aside class="sz-filters">
      <div class="sz-filter-head">Filters</div>
      <div class="sz-filter-section">
        <div class="sz-filter-title">Category</div>
        <?php foreach(array_slice($categories,0,5) as $cat): ?>
        <div class="sz-filter-item"><div class="sz-filter-check"></div><?php echo htmlspecialchars($cat); ?></div>
        <?php endforeach; ?>
      </div>
      <div class="sz-filter-section">
        <div class="sz-filter-title">Price</div>
        <div class="sz-filter-item"><div class="sz-filter-check"></div>Under ₹5,000</div>
        <div class="sz-filter-item"><div class="sz-filter-check"></div>₹5,000 – ₹20,000</div>
        <div class="sz-filter-item"><div class="sz-filter-check"></div>₹20,000 – ₹60,000</div>
        <div class="sz-filter-item"><div class="sz-filter-check"></div>Above ₹60,000</div>
      </div>
      <div class="sz-filter-section">
        <div class="sz-filter-title">Customer Rating</div>
        <div class="sz-filter-item"><div class="sz-filter-check"></div>4★ &amp; above</div>
        <div class="sz-filter-item"><div class="sz-filter-check"></div>3★ &amp; above</div>
      </div>
      <div class="sz-filter-section">
        <div class="sz-filter-title">Availability</div>
        <div class="sz-filter-item"><div class="sz-filter-check"></div>In Stock</div>
        <div class="sz-filter-item"><div class="sz-filter-check"></div>Free Delivery</div>
      </div>
    </aside>

    <!-- Results main -->
    <div class="sz-results-main">
      <div class="sz-results-header">
        <!-- ⚠ VULNERABLE: raw echo of $_GET['q'] — HTML injection fires here -->
        <div class="sz-results-query">
          <?php if(count($results) > 0): ?>
          Showing results for: <?php echo $rawQuery; ?>
          <?php else: ?>
          No results found for "<?php echo $rawQuery; ?>"
          <?php endif; ?>
        </div>
        <?php if(count($results) > 0): ?>
        <div class="sz-results-count"><?php echo count($results); ?> product<?php echo count($results)!==1?'s':''; ?></div>
        <div class="sz-sort-wrap">
          Sort by:
          <span class="sz-sort-opt active">Relevance</span>
          <span class="sz-sort-opt">Price ↑</span>
          <span class="sz-sort-opt">Rating</span>
        </div>
        <?php endif; ?>
      </div>

      <?php if(empty($results)): ?>
      <!-- No results (also vulnerable) -->
      <div class="sz-no-results">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <h3>Sorry, no results for "<?php echo $rawQuery; ?>"</h3>
        <p>Check your spelling or try different keywords</p>
        <div style="margin-top:20px;font-size:.82rem;color:#bbb;">Try searching for: <a href="index.php?q=iPhone" style="color:#2874f0;">iPhone</a>, <a href="index.php?q=Laptops" style="color:#2874f0;">Laptops</a>, <a href="index.php?q=Audio" style="color:#2874f0;">Audio</a></div>
      </div>

      <?php else: ?>
      <div class="sz-results-list">
        <?php foreach($results as $p): ?>
        <div class="sz-result-card">
          <div class="sz-result-img" style="background:<?php echo htmlspecialchars($p['img_color']); ?>;">
            <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
          </div>
          <div class="sz-result-info">
            <div class="sz-result-name"><?php echo htmlspecialchars($p['name']); ?></div>
            <div class="sz-result-rating">
              <div class="sz-result-stars-badge">
                <?php echo $p['rating']; ?>
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              </div>
              <div class="sz-result-reviews"><?php echo number_format($p['reviews']); ?> ratings</div>
            </div>
            <div class="sz-result-price"><?php echo fmt($p['price']); ?></div>
            <span class="sz-result-old"><?php echo fmt((int)($p['price']*1.3)); ?></span>
            <span class="sz-result-discount"><?php echo round((1 - $p['price']/($p['price']*1.3))*100); ?>% off</span>
            <div class="sz-result-delivery">✓ Free Delivery on orders above ₹499</div>
            <button class="sz-result-add">ADD TO CART</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div><!-- /.sz-results-main -->
  </div><!-- /.sz-results-wrap -->
</div>

<?php endif; ?>

<!-- ── Footer ─────────────────────────────────────────────────────────────── -->
<div class="sz-footer">
  <div class="sz-footer-inner">
    <div>
      <h4>ABOUT</h4>
      <ul><li>About Us</li><li>Careers</li><li>Press</li><li>Corporate Information</li></ul>
    </div>
    <div>
      <h4>HELP</h4>
      <ul><li>Payments</li><li>Shipping</li><li>Cancellation & Returns</li><li>FAQ</li></ul>
    </div>
    <div>
      <h4>POLICY</h4>
      <ul><li>Return Policy</li><li>Terms Of Use</li><li>Security</li><li>Privacy</li></ul>
    </div>
    <div>
      <h4>SOCIAL</h4>
      <ul><li>Facebook</li><li>Twitter</li><li>YouTube</li><li>Instagram</li></ul>
    </div>
  </div>
  <div class="sz-footer-bottom">© 2024 ShopZone Internet Private Limited — Security Lab for Educational Purposes Only</div>
</div>

<script>
function copySearch(el){
    const input = document.querySelector('.sz-search-input');
    input.value = el.textContent.trim();
    input.closest('form').submit();
}
</script>
</body>
</html>
