<?php
// Vend — point of sale & retail management

// ── Trailing-slash URL canonicalisation ──────────────────────────────────────
// Requests without a trailing slash are redirected to the same URL with one
// appended. For a path that begins with // this emits a protocol-relative
// Location (//host/), which the browser follows off-site. A path that already
// ends in a slash and starts with // is forwarded the same way.
$pathOnly = strtok($_SERVER['REQUEST_URI'], '?');
if ($pathOnly !== '/' && substr($pathOnly, -1) !== '/') {
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . $pathOnly . '/' . ($qs !== '' ? '?' . $qs : ''));
    exit;
}
if (preg_match('#^//#', $pathOnly)) {
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function img($id) { return "https://images.unsplash.com/{$id}?w=900&q=70&auto=format&fit=crop"; }

$features = [
    ['🛒','Point of Sale','Sell in store or on the go from any device — fast, intuitive, and works offline.'],
    ['📦','Inventory','Manage products, stock levels and purchase orders across every outlet in one place.'],
    ['📊','Reporting','Real-time sales, margin and performance insights so you always know what&rsquo;s working.'],
    ['🌐','Ecommerce','Sync your online and in-store catalog so inventory and sales stay in step.'],
    ['⭐','Loyalty','Build a customer base with built-in loyalty, store credit and gift cards.'],
    ['🖥️','Hardware','Pairs with receipt printers, barcode scanners and cash drawers out of the box.'],
];
$brands = ['Nourished','Hallensteins','Seed Heritage','The Fix','Goodfair','Partridge'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vend POS — Point of Sale &amp; Retail Management Software</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--teal:#12b3a8;--teal2:#0b8f87;--navy:#0e2b3b;--ink:#13293a;--muted:#5d7488;--line:#e4edf1;--bg:#f2f8f8;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:var(--ink);background:#fff;line-height:1.55;}
a{text-decoration:none;color:inherit;}
.wrap{max-width:1120px;margin:0 auto;padding:0 22px;}
.nav{background:#fff;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:20;}
.nav-in{max-width:1120px;margin:0 auto;padding:16px 22px;display:flex;align-items:center;gap:30px;}
.logo{font-size:25px;font-weight:900;color:var(--teal);letter-spacing:-1px;display:flex;align-items:center;gap:8px;}
.logo .d{width:10px;height:10px;border-radius:50%;background:var(--teal);}
.menu{display:flex;gap:24px;font-size:14.5px;font-weight:600;color:var(--ink);}
.menu a:hover{color:var(--teal);}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:16px;}
.login{font-size:14.5px;font-weight:600;}
.login:hover{color:var(--teal);}
.btn{background:var(--teal);color:#fff;border:none;border-radius:8px;padding:11px 20px;font-size:14.5px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-block;}
.btn:hover{background:var(--teal2);}
@media(max-width:820px){.menu{display:none;}}
/* hero */
.hero{background:var(--bg);overflow:hidden;}
.hero-in{max-width:1120px;margin:0 auto;padding:62px 22px;display:grid;grid-template-columns:1fr 1fr;gap:46px;align-items:center;}
@media(max-width:860px){.hero-in{grid-template-columns:1fr;}}
.hero h1{font-size:47px;font-weight:900;letter-spacing:-1.4px;line-height:1.05;margin-bottom:16px;color:var(--navy);}
.hero h1 span{color:var(--teal);}
.hero p{font-size:18px;color:var(--muted);max-width:420px;margin-bottom:26px;}
.hero-cta{display:flex;gap:12px;flex-wrap:wrap;align-items:center;}
.hero-cta .secondary{font-size:14.5px;font-weight:700;color:var(--teal);}
.hero-img{border-radius:18px;overflow:hidden;aspect-ratio:4/3;box-shadow:0 24px 60px rgba(18,179,168,.22);}
.hero-img img{width:100%;height:100%;object-fit:cover;}
/* strip */
.strip{border-bottom:1px solid var(--line);}
.strip-in{max-width:1120px;margin:0 auto;padding:26px 22px;text-align:center;}
.strip .lbl{font-size:12.5px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);font-weight:600;margin-bottom:16px;}
.brand-row{display:flex;flex-wrap:wrap;justify-content:center;gap:14px 38px;}
.brand-row span{font-size:18px;font-weight:800;color:#aab8c2;letter-spacing:-.4px;}
/* features */
.sec{max-width:1120px;margin:0 auto;padding:56px 22px;}
.sec h2{font-size:30px;font-weight:800;letter-spacing:-.6px;text-align:center;margin-bottom:6px;}
.sec .sub{text-align:center;color:var(--muted);font-size:16px;margin-bottom:36px;}
.fgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;}
@media(max-width:820px){.fgrid{grid-template-columns:1fr;}}
.feat{background:#fff;border:1px solid var(--line);border-radius:14px;padding:24px;}
.feat:hover{box-shadow:0 10px 30px rgba(14,43,59,.08);}
.feat .ic{width:48px;height:48px;border-radius:12px;background:var(--bg);display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:14px;}
.feat h3{font-size:17px;font-weight:700;margin-bottom:6px;}
.feat p{font-size:14px;color:var(--muted);}
/* cta band */
.band{background:linear-gradient(120deg,var(--navy),#145667);color:#fff;}
.band-in{max-width:1120px;margin:0 auto;padding:52px 22px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;}
.band-in h2{font-size:28px;font-weight:800;}
.band-in p{opacity:.85;font-size:15.5px;}
.band-in .btn{background:var(--teal);}
/* footer */
.foot{background:#0b2330;color:#9fc0c7;}
.foot-in{max-width:1120px;margin:0 auto;padding:44px 22px;display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:26px;font-size:13.5px;}
@media(max-width:760px){.foot-in{grid-template-columns:1fr 1fr;}}
.foot .flogo{font-size:24px;font-weight:900;color:#fff;letter-spacing:-1px;margin-bottom:10px;}
.foot h4{color:#fff;font-size:13px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;}
.foot ul{list-style:none;line-height:2;}
.foot-bot{border-top:1px solid rgba(255,255,255,.08);text-align:center;padding:16px;font-size:12px;color:#7fa3ab;}
</style>
</head>
<body>
<nav class="nav"><div class="nav-in">
  <a href="/" class="logo"><span class="d"></span>vend</a>
  <div class="menu"><a href="/">Features</a><a href="/">Pricing</a><a href="/">For retailers</a><a href="/">Resources</a></div>
  <div class="nav-right"><a href="/" class="login">Log in</a><a href="/" class="btn">Start free trial</a></div>
</div></nav>

<section class="hero"><div class="hero-in">
  <div>
    <h1>The point of sale<br>your store will <span>love.</span></h1>
    <p>Sell, manage, report and grow your retail business — in store and online — all from one powerful platform.</p>
    <div class="hero-cta"><a href="/" class="btn">Start your free trial</a><a href="/" class="secondary">Watch a demo &rarr;</a></div>
  </div>
  <div class="hero-img"><img src="<?php echo img('photo-1556742049-0cfed4f6a45d'); ?>" alt="" onerror="this.remove()"></div>
</div></section>

<div class="strip"><div class="strip-in">
  <div class="lbl">Trusted by 25,000+ retailers worldwide</div>
  <div class="brand-row"><?php foreach ($brands as $b): ?><span><?php echo esc($b); ?></span><?php endforeach; ?></div>
</div></div>

<section class="sec">
  <h2>Everything you need to run your store</h2>
  <div class="sub">One platform for selling, inventory, customers and reporting.</div>
  <div class="fgrid">
    <?php foreach ($features as $f): ?>
    <div class="feat">
      <div class="ic"><?php echo $f[0]; ?></div>
      <h3><?php echo esc($f[1]); ?></h3>
      <p><?php echo $f[2]; ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<div class="band"><div class="band-in">
  <div><h2>Start selling in minutes.</h2><p>Try Vend free for 14 days. No credit card required.</p></div>
  <a href="/" class="btn">Start free trial</a>
</div></div>

<footer class="foot"><div class="foot-in">
  <div><div class="flogo">vend</div><p style="max-width:240px;">Point of sale and retail management software for ambitious retailers.</p></div>
  <div><h4>Product</h4><ul><li>Point of Sale</li><li>Inventory</li><li>Reporting</li><li>Ecommerce</li></ul></div>
  <div><h4>Company</h4><ul><li>About</li><li>Customers</li><li>Careers</li><li>Partners</li></ul></div>
  <div><h4>Support</h4><ul><li>Help Center</li><li>Contact</li><li>Status</li><li>Privacy</li></ul></div>
</div><div class="foot-bot">&copy; 2019 Vend Limited. Point of sale &amp; retail management software.</div></footer>
</body>
</html>
