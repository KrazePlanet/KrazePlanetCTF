<?php
// Shared layout helpers – ControlZone brand, fresh white+neon design

function layout_head(string $title, string $extra_css = ''): void {
    $u   = user();
    $cnt = $u ? cart_count((int)$u['id']) : 0;
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    $base = BASE;
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title} — ControlZone</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{
  --c-bg:#f5f6fa; --c-white:#ffffff; --c-surface:#ffffff;
  --c-border:#e2e5ef; --c-border2:#d0d5e8;
  --c-text:#111827; --c-muted:#6b7280; --c-light:#9ca3af;
  --c-accent:#2563eb; --c-accent2:#1d4ed8;
  --c-green:#16a34a;  --c-red:#dc2626; --c-amber:#d97706;
  --c-purple:#7c3aed; --c-tag-bg:#eff6ff; --c-tag:#2563eb;
  --r:10px; --r-sm:6px; --shadow:0 1px 4px rgba(0,0,0,.08);
  --shadow-md:0 4px 16px rgba(0,0,0,.1);
  --font:'Inter',system-ui,sans-serif;
}
*{box-sizing:border-box;margin:0;padding:0;-webkit-font-smoothing:antialiased}
body{background:var(--c-bg);color:var(--c-text);font-family:var(--font);line-height:1.55}
a{color:var(--c-accent);text-decoration:none}
a:hover{text-decoration:underline;text-underline-offset:3px}
img{display:block;max-width:100%}

/* ── TOP BAR ── */
.topbar{background:#111827;color:#9ca3af;font-size:.76rem;text-align:center;padding:7px 20px;letter-spacing:.02em}
.topbar strong{color:#fff}

/* ── NAV ── */
nav{background:#fff;border-bottom:1px solid var(--c-border);position:sticky;top:0;z-index:200;box-shadow:var(--shadow)}
.nav-inner{max-width:1340px;margin:0 auto;padding:0 24px;display:flex;align-items:center;gap:20px;height:64px}
.nav-logo{display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit}
.nav-logo-icon{width:32px;height:32px;background:var(--c-accent);border-radius:7px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;font-weight:900;flex-shrink:0}
.nav-logo-text{font-size:1.15rem;font-weight:800;color:var(--c-text);letter-spacing:-.5px}
.nav-logo-text span{color:var(--c-accent)}
.nav-search{flex:1;max-width:460px;position:relative}
.nav-search input{width:100%;background:var(--c-bg);border:1.5px solid var(--c-border);border-radius:8px;
  padding:9px 16px 9px 40px;color:var(--c-text);outline:none;font-size:.9rem}
.nav-search input:focus{border-color:var(--c-accent);background:#fff}
.nav-search .search-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--c-light);font-size:1rem;pointer-events:none}
.nav-right{display:flex;align-items:center;gap:12px;margin-left:auto}
.nav-cart-btn{position:relative;background:var(--c-bg);border:1.5px solid var(--c-border);
  border-radius:8px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;
  font-size:1.15rem;text-decoration:none;transition:border-color .15s}
.nav-cart-btn:hover{border-color:var(--c-accent)}
.cart-badge{position:absolute;top:-6px;right:-6px;background:var(--c-accent);color:#fff;
  font-size:.62rem;font-weight:700;border-radius:10px;padding:1px 5px;min-width:17px;text-align:center;line-height:1.5}
.nav-user-area{display:flex;align-items:center;gap:8px;font-size:.85rem}
.nav-user-name{font-weight:600;color:var(--c-text);white-space:nowrap}
.nav-divider{color:var(--c-border2)}
.btn-nav-primary{background:var(--c-accent);color:#fff;border:none;border-radius:7px;
  padding:8px 18px;cursor:pointer;font-size:.85rem;font-weight:600;white-space:nowrap;
  font-family:var(--font);transition:background .15s;text-decoration:none;display:inline-flex;align-items:center}
.btn-nav-primary:hover{background:var(--c-accent2);text-decoration:none}
.btn-nav-secondary{background:transparent;color:var(--c-text);border:1.5px solid var(--c-border);
  border-radius:7px;padding:7px 16px;cursor:pointer;font-size:.85rem;font-weight:500;
  font-family:var(--font);transition:border-color .15s;text-decoration:none;display:inline-flex;align-items:center}
.btn-nav-secondary:hover{border-color:var(--c-accent);color:var(--c-accent);text-decoration:none}

/* ── FLASH ── */
.flashes{max-width:1340px;margin:14px auto 0;padding:0 24px}
.flash{padding:13px 18px;border-radius:var(--r-sm);margin-bottom:8px;font-size:.88rem;font-weight:500;display:flex;align-items:center;gap:10px}
.flash-success{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--c-green)}
.flash-error{background:#fef2f2;border:1px solid #fecaca;color:var(--c-red)}
.flash-info{background:#eff6ff;border:1px solid #bfdbfe;color:var(--c-accent)}

/* ── MAIN ── */
main{max-width:1340px;margin:0 auto;padding:32px 24px 60px}

/* ── HERO ── */
.hero{background:linear-gradient(135deg,#111827 0%,#1e3a5f 60%,#2563eb 100%);
  border-radius:16px;padding:52px 48px;color:#fff;display:grid;
  grid-template-columns:1fr 1fr;gap:40px;align-items:center;margin-bottom:40px;overflow:hidden;position:relative}
.hero::before{content:'';position:absolute;right:-60px;top:-60px;width:340px;height:340px;
  background:rgba(255,255,255,.04);border-radius:50%}
.hero-eyebrow{font-size:.78rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;
  color:#93c5fd;margin-bottom:14px}
.hero h1{font-size:2.4rem;font-weight:900;line-height:1.15;margin-bottom:16px}
.hero h1 span{color:#60a5fa}
.hero-sub{color:#cbd5e1;font-size:1rem;margin-bottom:28px;line-height:1.6}
.hero-cta{display:inline-flex;align-items:center;gap:8px;background:#fff;color:#1e3a5f;
  border-radius:8px;padding:12px 24px;font-weight:700;font-size:.95rem;text-decoration:none;transition:opacity .15s}
.hero-cta:hover{opacity:.9;text-decoration:none}
.hero-img-wrap{display:flex;justify-content:center;align-items:center}
.hero-img-wrap img{width:280px;height:280px;object-fit:contain;filter:drop-shadow(0 20px 40px rgba(0,0,0,.4))}

/* ── SECTION ── */
.section-hd{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
.section-title{font-size:1.35rem;font-weight:800;color:var(--c-text)}
.section-link{font-size:.85rem;color:var(--c-accent);font-weight:600}

/* ── FILTER BAR ── */
.filter-bar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:28px}
.filter-pill{background:#fff;border:1.5px solid var(--c-border);color:var(--c-muted);
  border-radius:99px;padding:6px 16px;cursor:pointer;font-size:.82rem;font-weight:500;
  transition:all .15s;white-space:nowrap}
.filter-pill:hover,.filter-pill.active{border-color:var(--c-accent);color:var(--c-accent);
  background:var(--c-tag-bg)}

/* ── PRODUCT GRID ── */
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(236px,1fr));gap:20px}
.p-item{} /* wrapper for JS hide/show */
.p-card{background:#fff;border:1.5px solid var(--c-border);border-radius:14px;overflow:hidden;
  display:flex;flex-direction:column;transition:box-shadow .2s,border-color .2s;cursor:default}
.p-card:hover{box-shadow:var(--shadow-md);border-color:var(--c-accent)}
.p-img-wrap{background:#f8f9fc;aspect-ratio:1;display:flex;align-items:center;justify-content:center;
  padding:24px;position:relative}
.p-img-wrap img{width:100%;height:100%;object-fit:contain;transition:transform .3s}
.p-card:hover .p-img-wrap img{transform:scale(1.04)}
.p-badge{position:absolute;top:10px;left:10px;background:var(--c-red);color:#fff;
  font-size:.68rem;font-weight:700;padding:3px 8px;border-radius:4px;letter-spacing:.03em}
.p-badge-new{background:var(--c-green)}
.p-body{padding:16px;flex:1;display:flex;flex-direction:column}
.p-cat{font-size:.7rem;font-weight:700;color:var(--c-accent);text-transform:uppercase;
  letter-spacing:.08em;margin-bottom:6px}
.p-name{font-size:.9rem;font-weight:600;color:var(--c-text);line-height:1.35;margin-bottom:10px;flex:1}
.p-name a{color:inherit;text-decoration:none}
.p-name a:hover{color:var(--c-accent)}
.p-stars{color:#f59e0b;font-size:.82rem;margin-bottom:10px}
.p-stars span{color:var(--c-light);font-size:.77rem}
.p-price-row{display:flex;align-items:baseline;flex-wrap:wrap;gap:6px;margin-bottom:12px}
.p-price{font-size:1.15rem;font-weight:800;color:var(--c-text)}
.p-old{font-size:.8rem;color:var(--c-light);text-decoration:line-through}
.p-discount{font-size:.75rem;font-weight:700;color:var(--c-green);background:#f0fdf4;padding:1px 6px;border-radius:4px}
.btn-add{background:var(--c-accent);color:#fff;border:none;border-radius:8px;
  padding:10px 16px;cursor:pointer;font-size:.85rem;font-weight:600;
  font-family:var(--font);width:100%;transition:background .15s}
.btn-add:hover{background:var(--c-accent2)}

/* ── TRUST BAR ── */
.trust-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:40px}
.trust-item{background:#fff;border:1.5px solid var(--c-border);border-radius:12px;
  padding:20px;display:flex;align-items:center;gap:14px}
.trust-icon{font-size:1.6rem;flex-shrink:0}
.trust-text{font-size:.82rem;color:var(--c-muted);line-height:1.4}
.trust-text strong{display:block;color:var(--c-text);font-size:.88rem;margin-bottom:2px}

/* ── BREADCRUMB ── */
.bc{font-size:.8rem;color:var(--c-muted);margin-bottom:22px;display:flex;align-items:center;gap:6px}
.bc a{color:var(--c-muted)}
.bc a:hover{color:var(--c-accent)}
.bc-sep{opacity:.4;font-size:.7rem}

/* ── PAGE TITLE ── */
.pg-title{font-size:1.8rem;font-weight:900;margin-bottom:4px}
.pg-sub{color:var(--c-muted);font-size:.92rem;margin-bottom:28px}

/* ── PRODUCT DETAIL ── */
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:start}
.detail-gallery{background:#f8f9fc;border:1.5px solid var(--c-border);border-radius:14px;
  aspect-ratio:1;display:flex;align-items:center;justify-content:center;padding:40px}
.detail-gallery img{max-width:100%;max-height:100%;object-fit:contain}
.detail-cat{font-size:.75rem;font-weight:700;color:var(--c-accent);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px}
.detail-name{font-size:1.7rem;font-weight:900;line-height:1.2;margin-bottom:14px}
.detail-stars{font-size:.95rem;color:#f59e0b;margin-bottom:18px}
.detail-stars span{color:var(--c-muted);font-size:.85rem}
.detail-price-row{display:flex;align-items:baseline;gap:10px;margin-bottom:6px}
.detail-price{font-size:2rem;font-weight:900;color:var(--c-text)}
.detail-old{font-size:1rem;color:var(--c-light);text-decoration:line-through}
.detail-saving{font-size:.85rem;color:var(--c-green);font-weight:600;background:#f0fdf4;padding:3px 10px;border-radius:99px}
.detail-stock{font-size:.88rem;color:var(--c-green);font-weight:600;margin-bottom:18px;display:flex;align-items:center;gap:6px}
.detail-desc{color:var(--c-muted);font-size:.93rem;line-height:1.65;margin-bottom:24px;
  padding:16px;background:#f8f9fc;border-radius:var(--r-sm);border:1px solid var(--c-border)}
.detail-form{display:flex;gap:12px;align-items:center}
.detail-qty{width:80px;border:1.5px solid var(--c-border);border-radius:8px;padding:11px 14px;
  font-family:var(--font);font-size:.95rem;color:var(--c-text);background:#fff}
.detail-qty:focus{outline:none;border-color:var(--c-accent)}

/* ── CARD ── */
.card{background:#fff;border:1.5px solid var(--c-border);border-radius:14px;padding:24px}
.card + .card{margin-top:16px}

/* ── FORMS ── */
.form-section-title{font-size:1rem;font-weight:700;margin-bottom:18px;padding-bottom:12px;
  border-bottom:1px solid var(--c-border)}
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:.82rem;font-weight:600;color:var(--c-text);margin-bottom:6px}
.form-input{width:100%;background:#fff;border:1.5px solid var(--c-border);border-radius:8px;
  padding:10px 14px;color:var(--c-text);outline:none;font-family:var(--font);font-size:.92rem;
  transition:border-color .15s}
.form-input:focus{border-color:var(--c-accent)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}

/* ── BUTTONS ── */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:none;
  border-radius:8px;padding:11px 22px;cursor:pointer;font-size:.88rem;font-weight:600;
  font-family:var(--font);transition:all .15s;text-decoration:none}
.btn:hover{text-decoration:none;opacity:.88}
.btn-primary{background:var(--c-accent);color:#fff}
.btn-primary:hover{background:var(--c-accent2);opacity:1}
.btn-danger{background:var(--c-red);color:#fff}
.btn-ghost{background:transparent;border:1.5px solid var(--c-border);color:var(--c-text)}
.btn-ghost:hover{border-color:var(--c-accent);color:var(--c-accent);opacity:1}
.btn-sm{padding:7px 14px;font-size:.8rem}
.btn-block{display:flex;width:100%}
.btn-lg{padding:14px 28px;font-size:.97rem}

/* ── TABLE ── */
table{width:100%;border-collapse:collapse}
th{text-align:left;font-size:.75rem;font-weight:700;color:var(--c-muted);padding:10px 14px;
  border-bottom:2px solid var(--c-border);text-transform:uppercase;letter-spacing:.06em}
td{padding:14px;border-bottom:1px solid var(--c-border);font-size:.88rem;vertical-align:middle}
tr:last-child td{border-bottom:none}
tbody tr:hover td{background:#fafbff}

/* ── CART ── */
.cart-layout{display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start}
.order-panel{position:sticky;top:82px}
.summary-line{display:flex;justify-content:space-between;align-items:center;padding:9px 0;
  font-size:.88rem;color:var(--c-muted);border-bottom:1px solid var(--c-border)}
.summary-line:last-of-type{border-bottom:none}
.summary-total{display:flex;justify-content:space-between;align-items:center;
  font-size:1.1rem;font-weight:800;padding-top:14px;border-top:2px solid var(--c-border);margin-top:8px}
.qty-row{display:flex;align-items:center;gap:8px}
.qty-row input{width:60px;border:1.5px solid var(--c-border);border-radius:6px;
  padding:6px 8px;text-align:center;font-family:var(--font);font-size:.88rem}

/* ── CHECKOUT ── */
.checkout-layout{display:grid;grid-template-columns:1fr 380px;gap:28px;align-items:start}
.checkout-panel{position:sticky;top:82px}

/* ── ORDERS ── */
.status-chip{display:inline-block;padding:3px 10px;border-radius:99px;font-size:.73rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.s-processing,.s-pending-payment{background:#fffbeb;color:#92400e;border:1px solid #fde68a}
.s-paid,.s-delivered{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0}
.s-cancelled{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}

/* ── PAYMENT ── */
.payment-wrap{max-width:520px;margin:0 auto}
.fake-card-field{background:#f8f9fc;border:1.5px solid var(--c-border);border-radius:8px;
  padding:11px 14px;font-size:.92rem;color:var(--c-muted);margin-bottom:16px;font-family:var(--font)}
.pay-secure{display:flex;align-items:center;gap:8px;font-size:.78rem;color:var(--c-muted);margin-top:12px;justify-content:center}

/* ── EMPTY STATE ── */
.empty{text-align:center;padding:72px 24px;color:var(--c-muted)}
.empty-icon{font-size:3.5rem;margin-bottom:16px}
.empty h3{font-size:1.2rem;font-weight:700;color:var(--c-text);margin-bottom:8px}

/* ── UTILS ── */
.text-muted{color:var(--c-muted)}
.text-green{color:var(--c-green)}
.text-red{color:var(--c-red)}
.text-right{text-align:right}
.mt-2{margin-top:16px}.mt-3{margin-top:24px}.mb-2{margin-bottom:16px}
.fw-700{font-weight:700}.fw-800{font-weight:800}
.divider{border:none;border-top:1px solid var(--c-border);margin:20px 0}

/* ── FOOTER ── */
footer{background:#111827;color:#6b7280;text-align:center;padding:40px 24px;font-size:.82rem;margin-top:48px}
footer strong{color:#fff}

/* ── RESPONSIVE ── */
@media(max-width:900px){
  .trust-bar{grid-template-columns:1fr 1fr}
  .cart-layout,.checkout-layout,.detail-grid{grid-template-columns:1fr}
  .order-panel,.checkout-panel{position:static}
  .hero{grid-template-columns:1fr;padding:36px 28px;text-align:center}
  .hero-img-wrap{display:none}
  .hero h1{font-size:1.8rem}
}
@media(max-width:600px){
  .trust-bar{grid-template-columns:1fr}
  .form-row{grid-template-columns:1fr}
  main{padding:20px 16px 48px}
  .nav-inner{padding:0 16px}
}
{$extra_css}
</style>
</head>
<body>
<div class="topbar">🚚 <strong>Free delivery</strong> across India on orders above ₹999 &nbsp;|&nbsp; 1-year warranty on all controllers</div>
<nav>
  <div class="nav-inner">
    <a class="nav-logo" href="{$base}">
      <div class="nav-logo-icon">🎮</div>
      <span class="nav-logo-text">Control<span>Zone</span></span>
    </a>
    <form class="nav-search" method="get" action="{$base}">
      <span class="search-icon">🔍</span>
      <input name="q" placeholder="Search controllers, brands…" autocomplete="off">
    </form>
    <div class="nav-right">
HTML;
    if ($u) {
        echo '<a class="nav-cart-btn" href="' . url('cart') . '" title="Cart">🛒<span class="cart-badge">' . $cnt . '</span></a>';
        echo '<div class="nav-user-area">';
        echo '<a href="' . url('profile') . '" class="nav-user-name">' . h($u['name']) . '</a>';
        echo '<span class="nav-divider">·</span>';
        echo '<a href="' . url('orders') . '" style="color:var(--c-muted);font-size:.83rem">Orders</a>';
        echo '<span class="nav-divider">·</span>';
        echo '<a href="' . url('logout') . '" style="color:var(--c-muted);font-size:.83rem">Logout</a>';
        echo '</div>';
    } else {
        echo '<a href="' . url('login') . '" class="btn-nav-secondary">Login</a>';
        echo '<a href="' . url('register') . '" class="btn-nav-primary">Register</a>';
    }
    echo <<<HTML
    </div>
  </div>
</nav>
HTML;
    if ($flashes) {
        echo '<div class="flashes">';
        foreach ($flashes as [$type, $msg]) {
            $icons = ['success' => '✓', 'error' => '✕', 'info' => 'ℹ'];
            echo '<div class="flash flash-' . h($type) . '">';
            echo '<span>' . ($icons[$type] ?? '') . '</span> ' . h($msg);
            echo '</div>';
        }
        echo '</div>';
    }
    echo '<main>';
}

function layout_foot(): void {
    echo <<<HTML
</main>
<footer>
  <div style="margin-bottom:10px;font-size:1.4rem">🎮</div>
  <strong>ControlZone</strong> — India's gaming controller destination
  <div style="margin-top:8px;font-size:.75rem">Local Training Environment · Not a real store</div>
</footer>
<script>
// Category filter pills
document.querySelectorAll('.filter-pill').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const cat = btn.dataset.cat;
    document.querySelectorAll('.p-item').forEach(item => {
      item.style.display = (!cat || item.dataset.cat === cat) ? '' : 'none';
    });
  });
});
// Auto-dismiss flashes
setTimeout(() => document.querySelectorAll('.flash').forEach(el => {
  el.style.transition = 'opacity .4s'; el.style.opacity = '0';
  setTimeout(() => el.remove(), 400);
}), 4500);
</script>
</body>
</html>
HTML;
}

function product_img(string $image, string $name, string $size = '100%'): string {
    $src = BASE . '/assets/' . h($image);
    return '<img src="' . $src . '" alt="' . h($name) . '" loading="lazy" style="width:' . $size . ';height:' . $size . ';object-fit:contain">';
}

function stars(float $r): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $r >= $i ? '★' : ($r >= $i - 0.5 ? '⯨' : '☆');
    }
    return $out;
}
