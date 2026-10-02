<?php
// PCHub – shared layout helpers

function page_open(string $title, string $extra_head = ''): void {
    $u = user();
    $cartCount = $u ? cart_count($u['id']) : 0;
    $csrfToken = csrf_token();
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?> — PCHub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #0f0f13;
  --bg2: #18181f;
  --bg3: #22222c;
  --card: #1c1c26;
  --border: #2e2e3a;
  --accent: #7c3aed;
  --accent2: #a855f7;
  --accent3: #c084fc;
  --green: #10b981;
  --red: #ef4444;
  --yellow: #f59e0b;
  --text: #f1f5f9;
  --text2: #94a3b8;
  --text3: #64748b;
  --radius: 10px;
  --shadow: 0 4px 24px rgba(0,0,0,.45);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:15px;line-height:1.6}
a{color:var(--accent2);text-decoration:none}a:hover{color:var(--accent3)}
img{max-width:100%;display:block}
input,select,textarea,button{font-family:inherit;font-size:inherit}
/* ── NAV ── */
.nav{background:rgba(15,15,19,.92);backdrop-filter:blur(14px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;padding:0 24px}
.nav-inner{max-width:1280px;margin:0 auto;display:flex;align-items:center;gap:16px;height:62px}
.logo{font-size:1.4rem;font-weight:800;letter-spacing:-.5px;color:var(--text)}
.logo span{color:var(--accent2)}
.logo-tag{font-size:.62rem;font-weight:600;color:var(--accent2);background:rgba(124,58,237,.18);border:1px solid rgba(124,58,237,.35);padding:2px 7px;border-radius:4px;vertical-align:middle;margin-left:6px}
.nav-links{display:flex;gap:8px;flex:1;flex-wrap:wrap}
.nav-links a{color:var(--text2);font-size:.85rem;font-weight:500;padding:5px 10px;border-radius:6px;transition:.15s}
.nav-links a:hover{color:var(--text);background:var(--bg3)}
.nav-actions{display:flex;align-items:center;gap:10px}
.badge-btn{position:relative;background:var(--bg3);border:1px solid var(--border);color:var(--text2);border-radius:8px;padding:7px 13px;font-size:.83rem;font-weight:600;cursor:pointer;transition:.15s;display:flex;align-items:center;gap:6px}
.badge-btn:hover{border-color:var(--accent);color:var(--text);background:var(--bg2)}
.badge{position:absolute;top:-6px;right:-6px;background:var(--accent);color:#fff;font-size:.65rem;font-weight:700;padding:2px 5px;border-radius:10px;min-width:18px;text-align:center}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;font-weight:600;cursor:pointer;border:none;border-radius:var(--radius);padding:9px 20px;transition:.18s;font-size:.875rem}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}
.btn-primary:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 6px 24px rgba(124,58,237,.4)}
.btn-secondary{background:var(--bg3);color:var(--text2);border:1px solid var(--border)}
.btn-secondary:hover{border-color:var(--accent);color:var(--text);background:var(--bg2)}
.btn-danger{background:#7f1d1d;color:#fca5a5;border:1px solid #991b1b}
.btn-danger:hover{background:#991b1b;color:#fee2e2}
.btn-sm{padding:6px 14px;font-size:.8rem}
.btn-lg{padding:12px 28px;font-size:1rem}
/* ── FLASH ── */
.flash{max-width:1280px;margin:16px auto 0;padding:0 24px}
.flash-msg{padding:12px 16px;border-radius:var(--radius);font-size:.875rem;font-weight:500;margin-bottom:8px;display:flex;align-items:center;gap:10px}
.flash-success{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);color:#34d399}
.flash-error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#f87171}
.flash-info{background:rgba(124,58,237,.12);border:1px solid rgba(124,58,237,.3);color:var(--accent3)}
.flash-warning{background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.3);color:#fbbf24}
/* ── MAIN ── */
.main{max-width:1280px;margin:0 auto;padding:32px 24px 60px}
/* ── CARDS ── */
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden}
.card-pad{padding:24px}
/* ── PRODUCT GRID ── */
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px}
.product-card{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:.2s;display:flex;flex-direction:column}
.product-card:hover{border-color:var(--accent);box-shadow:0 8px 32px rgba(124,58,237,.2);transform:translateY(-2px)}
.product-img-wrap{aspect-ratio:1;background:var(--bg3);display:flex;align-items:center;justify-content:center;font-size:3.5rem;position:relative;overflow:hidden}
.product-img-wrap img{width:100%;height:100%;object-fit:contain;padding:16px;transition:.3s}
.product-card:hover .product-img-wrap img{transform:scale(1.05)}
.product-info{padding:16px;flex:1;display:flex;flex-direction:column;gap:6px}
.product-brand{font-size:.72rem;font-weight:700;color:var(--accent2);text-transform:uppercase;letter-spacing:.8px}
.product-name{font-size:.9rem;font-weight:600;color:var(--text);line-height:1.35}
.product-rating{display:flex;align-items:center;gap:6px;font-size:.78rem}
.stars{color:var(--yellow);letter-spacing:1px;font-size:.8rem}
.rating-count{color:var(--text3)}
.product-price-row{display:flex;align-items:center;gap:8px;margin-top:4px}
.product-price{font-size:1.05rem;font-weight:700;color:var(--text)}
.product-original{font-size:.82rem;color:var(--text3);text-decoration:line-through}
.product-discount{font-size:.75rem;font-weight:700;color:var(--green);background:rgba(16,185,129,.12);padding:2px 6px;border-radius:4px}
.product-actions{padding:0 16px 16px;display:flex;gap:8px}
/* ── BADGE PILL ── */
.cat-badge{display:inline-block;font-size:.72rem;font-weight:600;padding:3px 9px;border-radius:20px;background:rgba(124,58,237,.15);color:var(--accent3);border:1px solid rgba(124,58,237,.25)}
/* ── FORMS ── */
.form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
.form-label{font-size:.8rem;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:.5px}
.form-control{background:var(--bg3);border:1px solid var(--border);color:var(--text);border-radius:8px;padding:10px 14px;transition:.15s;outline:none;width:100%}
.form-control:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(124,58,237,.15)}
select.form-control option{background:var(--bg3)}
/* ── TABLES ── */
.table{width:100%;border-collapse:collapse}
.table th{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);padding:10px 14px;border-bottom:1px solid var(--border);text-align:left}
.table td{padding:12px 14px;border-bottom:1px solid rgba(46,46,58,.5);font-size:.875rem;vertical-align:middle}
.table tr:last-child td{border-bottom:none}
/* ── SECTION TITLES ── */
.section-title{font-size:1.35rem;font-weight:800;color:var(--text);letter-spacing:-.3px}
.section-sub{font-size:.875rem;color:var(--text3);margin-top:4px}
/* ── HERO ── */
.hero{background:linear-gradient(135deg,#1a0533 0%,#0f0f13 50%,#0d1a2e 100%);border-bottom:1px solid var(--border);padding:60px 24px;text-align:center;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% -20%,rgba(124,58,237,.3),transparent);pointer-events:none}
.hero-title{font-size:clamp(2rem,5vw,3.2rem);font-weight:900;letter-spacing:-1.5px;line-height:1.1}
.hero-title span{background:linear-gradient(135deg,var(--accent2),var(--accent3));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-sub{font-size:1.1rem;color:var(--text2);max-width:540px;margin:16px auto 28px}
.hero-chips{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
.hero-chip{background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:30px;padding:6px 16px;font-size:.82rem;color:var(--text2)}
/* ── CATEGORY PILLS ── */
.cat-strip{max-width:1280px;margin:28px auto 0;padding:0 24px;display:flex;gap:8px;flex-wrap:wrap}
.cat-pill{padding:7px 16px;border-radius:25px;font-size:.82rem;font-weight:600;border:1px solid var(--border);background:var(--bg2);color:var(--text2);cursor:pointer;transition:.15s}
.cat-pill:hover,.cat-pill.active{background:var(--accent);border-color:var(--accent);color:#fff}
/* ── PAGE HEADERS ── */
.page-header{margin-bottom:28px;padding-bottom:20px;border-bottom:1px solid var(--border)}
/* ── FLAG BANNER ── */
.flag-banner{background:linear-gradient(135deg,#052e16,#14532d);border:2px solid #16a34a;border-radius:14px;padding:28px 32px;margin-bottom:28px;position:relative;overflow:hidden}
.flag-banner::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 100% 100% at 0 100%,rgba(22,163,74,.25),transparent);pointer-events:none}
.flag-banner h2{font-size:1.1rem;font-weight:700;color:#4ade80;margin-bottom:10px;display:flex;align-items:center;gap:8px}
.flag-banner .flag-val{font-family:monospace;font-size:1.5rem;font-weight:800;color:#86efac;background:#052e16;border:1px solid #166534;padding:10px 20px;border-radius:8px;letter-spacing:2px;display:inline-block;margin-top:8px}
/* ── FOOTER ── */
.footer{background:var(--bg2);border-top:1px solid var(--border);padding:36px 24px;margin-top:auto}
.footer-inner{max-width:1280px;margin:0 auto;display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:32px}
.footer-brand{font-size:1.3rem;font-weight:800;color:var(--text)}
.footer-brand span{color:var(--accent2)}
.footer-desc{color:var(--text3);font-size:.82rem;margin-top:8px;line-height:1.6}
.footer-title{font-size:.72rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px}
.footer ul{list-style:none;display:flex;flex-direction:column;gap:8px}
.footer ul a{color:var(--text3);font-size:.82rem;transition:.15s}
.footer ul a:hover{color:var(--text2)}
.footer-copy{text-align:center;color:var(--text3);font-size:.78rem;margin-top:32px;padding-top:24px;border-top:1px solid var(--border)}
/* ── MISC ── */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px}
.flex{display:flex;align-items:center}
.flex-between{display:flex;align-items:center;justify-content:space-between}
.gap-8{gap:8px}
.gap-12{gap:12px}
.text-sm{font-size:.82rem}
.text-xs{font-size:.72rem}
.text-muted{color:var(--text3)}
.text-success{color:var(--green)}
.text-danger{color:var(--red)}
.text-warning{color:var(--yellow)}
.text-accent{color:var(--accent2)}
.mt-4{margin-top:4px}.mt-8{margin-top:8px}.mt-12{margin-top:12px}.mt-16{margin-top:16px}.mt-24{margin-top:24px}
.mb-8{margin-bottom:8px}.mb-16{margin-bottom:16px}.mb-24{margin-bottom:24px}
.divider{border:none;border-top:1px solid var(--border);margin:20px 0}
.tag{display:inline-block;padding:3px 9px;border-radius:5px;font-size:.72rem;font-weight:700}
.tag-success{background:rgba(16,185,129,.15);color:#34d399}
.tag-warning{background:rgba(245,158,11,.15);color:#fbbf24}
.tag-danger{background:rgba(239,68,68,.15);color:#f87171}
.tag-info{background:rgba(124,58,237,.15);color:var(--accent3)}
@media(max-width:900px){
  .footer-inner{grid-template-columns:1fr 1fr}
  .grid-2,.grid-3{grid-template-columns:1fr}
}
@media(max-width:600px){
  .hero{padding:40px 16px}.nav{padding:0 16px}.main{padding:24px 16px 48px}
  .footer-inner{grid-template-columns:1fr}
  .nav-links{display:none}
}
</style>
<?= $extra_head ?>
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="<?= url() ?>" class="logo">PC<span>Hub</span><span class="logo-tag">STORE</span></a>
    <div class="nav-links">
      <a href="<?= url() ?>">Home</a>
      <a href="<?= url('products') ?>">All Products</a>
      <?php foreach (['gpu'=>'🎮 GPUs','cpu'=>'⚡ CPUs','ram'=>'💾 RAM','ssd'=>'⚡ SSDs','mb'=>'🔌 Motherboards'] as $slug=>$label): ?>
      <a href="<?= url("category/$slug") ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
    <div class="nav-actions">
      <?php if ($u): ?>
        <a href="<?= url('cart') ?>" class="badge-btn">
          🛒 Cart
          <?php if ($cartCount > 0): ?><span class="badge"><?= $cartCount ?></span><?php endif; ?>
        </a>
        <a href="<?= url('dashboard') ?>" class="badge-btn">👤 <?= h($u['name']) ?></a>
        <?php if ($u['is_admin']): ?>
        <a href="<?= url('admin') ?>" class="badge-btn">⚙️ Admin</a>
        <?php endif; ?>
        <form method="POST" action="<?= url('logout') ?>" style="margin:0">
          <input type="hidden" name="_csrf" value="<?= h($csrfToken) ?>">
          <button class="badge-btn">Sign Out</button>
        </form>
      <?php else: ?>
        <a href="<?= url('login') ?>" class="badge-btn">Sign In</a>
        <a href="<?= url('register') ?>" class="btn btn-primary btn-sm">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<?php if ($flashes): ?>
<div class="flash">
  <?php foreach ($flashes as [$type, $msg]): ?>
  <div class="flash-msg flash-<?= h($type) ?>"><?= h($msg) ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php
}

function page_close(): void { ?>
<footer class="footer">
  <div class="footer-inner">
    <div>
      <div class="footer-brand">PC<span>Hub</span></div>
      <p class="footer-desc">India's trusted destination for PC components.<br>Build your dream rig today.</p>
    </div>
    <div>
      <div class="footer-title">Shop</div>
      <ul>
        <?php foreach (['gpu'=>'Graphics Cards','cpu'=>'Processors','ram'=>'RAM','ssd'=>'SSDs','accessories'=>'Accessories'] as $s=>$n): ?>
        <li><a href="<?= url("category/$s") ?>"><?= $n ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <div class="footer-title">Account</div>
      <ul>
        <li><a href="<?= url('dashboard') ?>">Dashboard</a></li>
        <li><a href="<?= url('orders') ?>">My Orders</a></li>
        <li><a href="<?= url('cart') ?>">Cart</a></li>
        <li><a href="<?= url('login') ?>">Sign In</a></li>
      </ul>
    </div>
    <div>
      <div class="footer-title">Lab</div>
      <ul>
        <li><a href="<?= url('admin') ?>">Admin Panel</a></li>
        <li><a href="<?= url('install') ?>">Reset DB</a></li>
      </ul>
      <div style="margin-top:16px;background:rgba(124,58,237,.08);border:1px solid rgba(124,58,237,.2);border-radius:8px;padding:10px 12px;font-size:.72rem;color:var(--accent3);line-height:1.5">
        ⚗️ <strong>Training Lab</strong><br>Intentional vulnerability present.<br>For authorized pen-testing only.
      </div>
    </div>
  </div>
  <div class="footer-copy">© 2025 PCHub Training Lab · Security Research Environment</div>
</footer>
</body>
</html>
<?php
}

function product_card(array $p): void {
    $discount = $p['original_price'] ? (int)round((1 - $p['price']/$p['original_price'])*100) : 0;
    ?>
<div class="product-card">
  <a href="<?= url("product/{$p['id']}") ?>" style="display:contents">
    <div class="product-img-wrap">
      <img src="<?= url("assets/pimg/{$p['id']}.svg") ?>" alt="<?= h($p['name']) ?>"
           onerror="this.onerror=null;this.src='<?= url("assets/pimg/default.svg") ?>'">
    </div>
    <div class="product-info">
      <div class="product-brand"><?= h($p['brand']) ?></div>
      <div class="product-name"><?= h($p['name']) ?></div>
      <div class="product-rating">
        <span class="stars"><?= stars((float)$p['rating']) ?></span>
        <span class="rating-count">(<?= number_format($p['review_count']) ?>)</span>
      </div>
      <div class="product-price-row">
        <span class="product-price"><?= inr((int)$p['price']) ?></span>
        <?php if ($p['original_price']): ?>
        <span class="product-original"><?= inr((int)$p['original_price']) ?></span>
        <span class="product-discount"><?= $discount ?>% off</span>
        <?php endif; ?>
      </div>
    </div>
  </a>
  <div class="product-actions">
    <form method="POST" action="<?= url('cart/add') ?>" style="flex:1">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
      <button class="btn btn-primary" style="width:100%">🛒 Add to Cart</button>
    </form>
  </div>
</div>
<?php }

function generate_product_svg(string $name, string $brand, string $cat_slug): string {
    $colors = ['gpu'=>['#7c3aed','#a855f7'],'cpu'=>['#0ea5e9','#38bdf8'],'ram'=>['#10b981','#34d399'],
               'ssd'=>['#f59e0b','#fbbf24'],'hdd'=>['#6366f1','#818cf8'],'mb'=>['#ec4899','#f472b6'],
               'psu'=>['#ef4444','#f87171'],'case'=>['#64748b','#94a3b8'],'cooler'=>['#06b6d4','#22d3ee'],
               'accessories'=>['#8b5cf6','#a78bfa']];
    [$c1,$c2] = $colors[$cat_slug] ?? ['#7c3aed','#a855f7'];
    $initials = strtoupper(substr($brand,0,1).substr($name,0,1));
    $lines = explode(' ', $name);
    $l1 = implode(' ', array_slice($lines,0,2));
    $l2 = implode(' ', array_slice($lines,2,2));
    $l3 = implode(' ', array_slice($lines,4));
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$c1}" stop-opacity=".18"/>
      <stop offset="100%" stop-color="{$c2}" stop-opacity=".08"/>
    </linearGradient>
  </defs>
  <rect width="200" height="200" fill="#18181f"/>
  <rect width="200" height="200" fill="url(#g)"/>
  <circle cx="100" cy="72" r="36" fill="{$c1}" fill-opacity=".22" stroke="{$c2}" stroke-width="1.5"/>
  <text x="100" y="80" font-family="Inter,sans-serif" font-size="24" font-weight="800" fill="{$c2}" text-anchor="middle">{$initials}</text>
  <text x="100" y="130" font-family="Inter,sans-serif" font-size="10.5" font-weight="600" fill="#e2e8f0" text-anchor="middle">{$l1}</text>
  <text x="100" y="145" font-family="Inter,sans-serif" font-size="10.5" font-weight="600" fill="#e2e8f0" text-anchor="middle">{$l2}</text>
  <text x="100" y="160" font-family="Inter,sans-serif" font-size="9.5" font-weight="400" fill="#94a3b8" text-anchor="middle">{$l3}</text>
  <text x="100" y="185" font-family="Inter,sans-serif" font-size="8.5" font-weight="700" fill="{$c2}" text-anchor="middle" opacity=".7">{$brand}</text>
</svg>
SVG;
}
