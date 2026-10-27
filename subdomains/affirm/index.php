<?php
// Affirm — buy now, pay over time

// ── Legacy path redirector ───────────────────────────────────────────────────
// A request whose path begins with an extra slash is handed straight to the
// Location header, so the browser follows it wherever it points.
$pathOnly = strtok($_SERVER['REQUEST_URI'], '?');
if (preg_match('#^//#', $pathOnly)) {
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function img($id) { return "https://images.unsplash.com/{$id}?w=700&q=70&auto=format&fit=crop"; }

$cats = [
    ['Fashion','photo-1441984904996-e0b6ba687e04'],
    ['Electronics','photo-1498049794561-7780e7231661'],
    ['Home','photo-1555041469-a586c61ea9bc'],
    ['Travel','photo-1488646953014-85cb44e25828'],
    ['Fitness','photo-1517836357463-d25dfeac3438'],
    ['Beauty','photo-1522335789203-aabd1fc54bc9'],
];
$brands = ['Walmart','Amazon','Target','Expedia','Peloton','Adidas','Casper','Dyson'];

// ── Newsroom (browsable by ?category= and ?page=) ─────────────────────────────
$categories  = ['all'=>'All stories','interview'=>'Interviews','product'=>'Product','company'=>'Company'];
$currentCat  = $_GET['category'] ?? 'all';
if (!isset($categories[$currentCat])) $currentCat = 'all';
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$catColors   = ['interview'=>'#4a4af4','product'=>'#0891b2','company'=>'#b45309'];
$articles = [
    ['interview','Interview: How Affirm keeps approvals fast and fair','Our risk team on building models that approve more people, responsibly.','Jun 2, 2021'],
    ['product','Introducing Affirm for travel','Book flights and hotels now and pay over time at thousands of partners.','May 28, 2021'],
    ['company','Affirm&rsquo;s 2021 social impact report','How transparent financing is helping millions of people budget better.','May 20, 2021'],
    ['interview','Interview: Designing a checkout people actually trust','A conversation with our design lead on clarity over dark patterns.','May 14, 2021'],
    ['product','Pay in 4, now at more of your favorite stores','Four interest-free payments, every two weeks, with no hidden fees.','May 6, 2021'],
    ['company','Welcoming new partners to the Affirm network','245,000+ merchants now offer Affirm at checkout.','Apr 29, 2021'],
    ['interview','Interview: What fair finance means to our engineers','The team behind the numbers on building transparency by default.','Apr 21, 2021'],
    ['product','A smarter way to manage your payments','The redesigned Affirm app makes autopay and scheduling effortless.','Apr 12, 2021'],
];
$filtered    = $currentCat === 'all' ? $articles : array_values(array_filter($articles, fn($a) => $a[0] === $currentCat));
$perPage     = 3;
$totalPages  = max(1, (int)ceil(count($filtered) / $perPage));
$currentPage = min($currentPage, $totalPages);
$pageItems   = array_slice($filtered, ($currentPage - 1) * $perPage, $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Affirm: Buy Now, Pay Later — Flexible Payments, No Hidden Fees</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--ink:#102d6d;--brand:#4a4af4;--brand2:#7a5cfa;--navy:#0a0a3c;--muted:#5b6b8c;--line:#e7e9f5;--bg:#f3f0ff;--cream:#fbf6ef;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:var(--navy);background:#fff;line-height:1.55;}
a{text-decoration:none;color:inherit;}
.wrap{max-width:1140px;margin:0 auto;padding:0 22px;}
/* nav */
.nav{background:#fff;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:20;}
.nav-in{max-width:1140px;margin:0 auto;padding:16px 22px;display:flex;align-items:center;gap:32px;}
.logo{font-size:26px;font-weight:800;color:var(--brand);letter-spacing:-1px;}
.menu{display:flex;gap:24px;font-size:14.5px;font-weight:500;color:var(--navy);}
.menu a:hover{color:var(--brand);}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:16px;}
.login{font-size:14.5px;font-weight:600;}
.login:hover{color:var(--brand);}
.btn{background:var(--navy);color:#fff;border:none;border-radius:40px;padding:11px 22px;font-size:14.5px;font-weight:600;cursor:pointer;font-family:inherit;}
.btn:hover{background:#1a1a5c;}
.btn-brand{background:var(--brand);}
.btn-brand:hover{background:#3a3ae0;}
@media(max-width:800px){.menu{display:none;}}
/* hero */
.hero{background:var(--bg);overflow:hidden;}
.hero-in{max-width:1140px;margin:0 auto;padding:60px 22px;display:grid;grid-template-columns:1fr 1fr;gap:46px;align-items:center;}
@media(max-width:860px){.hero-in{grid-template-columns:1fr;}}
.hero h1{font-size:50px;font-weight:800;letter-spacing:-1.6px;line-height:1.04;margin-bottom:18px;color:var(--navy);}
.hero h1 span{color:var(--brand);}
.hero p{font-size:18px;color:var(--muted);max-width:420px;margin-bottom:28px;}
.hero-cta{display:flex;gap:12px;flex-wrap:wrap;}
.hero-img{border-radius:22px;overflow:hidden;aspect-ratio:1/1;box-shadow:0 24px 60px rgba(74,74,244,.22);}
.hero-img img{width:100%;height:100%;object-fit:cover;}
/* chip strip */
.feat{background:var(--navy);color:#fff;}
.feat-in{max-width:1140px;margin:0 auto;padding:22px;display:flex;justify-content:space-around;gap:20px;flex-wrap:wrap;}
.feat-item{display:flex;align-items:center;gap:10px;font-size:14.5px;font-weight:600;}
.feat-item .d{width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;}
/* sections */
.sec{max-width:1140px;margin:0 auto;padding:58px 22px;}
.sec h2{font-size:32px;font-weight:800;letter-spacing:-.6px;text-align:center;margin-bottom:8px;}
.sec .sub{text-align:center;color:var(--muted);font-size:16px;margin-bottom:34px;}
.cats{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
@media(max-width:800px){.cats{grid-template-columns:1fr 1fr;}}
.cat{border-radius:18px;overflow:hidden;position:relative;aspect-ratio:4/3;}
.cat img{width:100%;height:100%;object-fit:cover;transition:transform .3s;}
.cat:hover img{transform:scale(1.05);}
.cat::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 45%,rgba(10,10,60,.65));}
.cat .nm{position:absolute;left:18px;bottom:16px;z-index:2;color:#fff;font-size:19px;font-weight:700;}
.cat .pay{position:absolute;left:18px;bottom:40px;z-index:2;color:#fff;font-size:12.5px;opacity:.9;}
/* steps */
.steps{background:var(--cream);}
.steps-in{max-width:1140px;margin:0 auto;padding:58px 22px;}
.step-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:26px;margin-top:30px;}
@media(max-width:800px){.step-grid{grid-template-columns:1fr;}}
.step{text-align:center;}
.step .n{width:46px;height:46px;border-radius:50%;background:var(--brand);color:#fff;font-weight:800;font-size:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;}
.step h3{font-size:18px;font-weight:700;margin-bottom:6px;}
.step p{font-size:14.5px;color:var(--muted);max-width:280px;margin:0 auto;}
/* brands */
.brands{max-width:1140px;margin:0 auto;padding:40px 22px 10px;}
.brands .lbl{text-align:center;font-size:13px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);font-weight:600;margin-bottom:20px;}
.brand-row{display:flex;flex-wrap:wrap;justify-content:center;gap:16px 40px;}
.brand-row span{font-size:19px;font-weight:800;color:#b9c0d6;letter-spacing:-.5px;}
/* cta band */
.band{max-width:1140px;margin:40px auto;padding:0 22px;}
.band-in{background:linear-gradient(120deg,var(--brand),var(--brand2));color:#fff;border-radius:24px;padding:44px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;}
.band-in h2{font-size:26px;font-weight:800;}
.band-in p{opacity:.9;font-size:15px;}
.band-in .btn{background:#fff;color:var(--brand);}
/* newsroom */
.ntabs{display:flex;gap:6px;justify-content:center;flex-wrap:wrap;margin-bottom:28px;}
.ntab{font-size:14px;font-weight:600;color:var(--muted);padding:9px 18px;border-radius:40px;border:1px solid var(--line);}
.ntab:hover{color:var(--navy);border-color:#cfd3ea;}
.ntab.active{background:var(--brand);color:#fff;border-color:var(--brand);}
.news-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
@media(max-width:800px){.news-grid{grid-template-columns:1fr;}}
.news-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:22px;transition:box-shadow .2s,transform .15s;}
.news-card:hover{box-shadow:0 12px 34px rgba(74,74,244,.12);transform:translateY(-2px);}
.news-cat{display:inline-block;font-size:11.5px;font-weight:700;color:#fff;padding:3px 10px;border-radius:20px;margin-bottom:12px;}
.news-card h3{font-size:17px;font-weight:700;line-height:1.35;margin-bottom:8px;color:var(--navy);}
.news-card:hover h3{color:var(--brand);}
.news-card p{font-size:14px;color:var(--muted);line-height:1.55;margin-bottom:14px;}
.news-date{font-size:12.5px;color:#9aa3bd;font-weight:600;}
.pager{display:flex;gap:6px;justify-content:center;margin-top:30px;}
.pg{min-width:38px;text-align:center;padding:8px 13px;border:1px solid var(--line);border-radius:9px;font-size:13.5px;font-weight:600;color:var(--navy);}
.pg:hover{border-color:var(--brand);color:var(--brand);}
.pg.active{background:var(--brand);color:#fff;border-color:var(--brand);}
.pg.disabled{opacity:.4;pointer-events:none;}
/* footer */
.foot{background:var(--navy);color:#aeb6d6;margin-top:30px;}
.foot-in{max-width:1140px;margin:0 auto;padding:46px 22px;display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:26px;font-size:13.5px;}
@media(max-width:760px){.foot-in{grid-template-columns:1fr 1fr;}}
.foot .flogo{font-size:24px;font-weight:800;color:#fff;letter-spacing:-1px;margin-bottom:10px;}
.foot h4{color:#fff;font-size:13px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;}
.foot ul{list-style:none;line-height:2;}
.foot-bot{border-top:1px solid rgba(255,255,255,.1);padding:16px 22px;text-align:center;font-size:12px;max-width:1140px;margin:0 auto;line-height:1.7;}
</style>
</head>
<body>
<nav class="nav"><div class="nav-in">
  <a href="/" class="logo">affirm</a>
  <div class="menu"><a href="/">How it works</a><a href="/">Shop</a><a href="/?category=all&amp;page=1">Newsroom</a><a href="/">Help</a></div>
  <div class="nav-right"><a href="/" class="login">Log in</a><a href="/" class="btn">Sign up</a></div>
</div></nav>

<section class="hero"><div class="hero-in">
  <div>
    <h1>Buy now,<br><span>pay over time.</span></h1>
    <p>Split your purchase into easy payments at your favorite brands. No late fees, no hidden costs — ever.</p>
    <div class="hero-cta"><a href="/" class="btn btn-brand">See how it works</a><a href="/" class="btn" style="background:#fff;color:var(--navy);border:1px solid var(--line);">Shop with Affirm</a></div>
  </div>
  <div class="hero-img"><img src="<?php echo img('photo-1556742049-0cfed4f6a45d'); ?>" alt="" onerror="this.remove()"></div>
</div></section>

<div class="feat"><div class="feat-in">
  <div class="feat-item"><span class="d">✓</span> 0% APR financing available</div>
  <div class="feat-item"><span class="d">✓</span> No late or hidden fees</div>
  <div class="feat-item"><span class="d">✓</span> Know your total upfront</div>
  <div class="feat-item"><span class="d">✓</span> Won't hurt your credit score</div>
</div></div>

<section class="sec">
  <h2>Shop your favorite categories</h2>
  <div class="sub">Pay over time at over 245,000 brands — online and in store.</div>
  <div class="cats">
    <?php foreach ($cats as $c): ?>
    <a class="cat" href="/">
      <img src="<?php echo img($c[1]); ?>" alt="" loading="lazy" onerror="this.style.background='#d9d9f6'">
      <div class="pay">From 4 interest-free payments</div>
      <div class="nm"><?php echo esc($c[0]); ?></div>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<div class="steps"><div class="steps-in">
  <h2 style="font-size:32px;font-weight:800;letter-spacing:-.6px;text-align:center;">How Affirm works</h2>
  <div class="sub" style="text-align:center;color:var(--muted);font-size:16px;">Pay at your pace in three simple steps.</div>
  <div class="step-grid">
    <div class="step"><div class="n">1</div><h3>Fill your cart</h3><p>Shop at your favorite store and choose Affirm at checkout.</p></div>
    <div class="step"><div class="n">2</div><h3>Pick a plan</h3><p>Select the payment schedule that works for your budget.</p></div>
    <div class="step"><div class="n">3</div><h3>Pay over time</h3><p>Make easy payments by autopay or at affirm.com — no surprises.</p></div>
  </div>
</div></div>

<div class="brands">
  <div class="lbl">Pay over time at</div>
  <div class="brand-row"><?php foreach ($brands as $b): ?><span><?php echo esc($b); ?></span><?php endforeach; ?></div>
</div>

<div class="band"><div class="band-in">
  <div><h2>Download the Affirm app</h2><p>Get prequalified, manage payments, and find exclusive deals.</p></div>
  <a href="/" class="btn">Get the app</a>
</div></div>

<section class="sec" id="newsroom">
  <h2>From the Affirm Newsroom</h2>
  <div class="sub">Product news, company updates, and conversations with our team.</div>
  <div class="ntabs">
    <?php foreach ($categories as $slug => $label): ?>
    <a href="/?category=<?php echo esc($slug); ?>&amp;page=1" class="ntab<?php echo $currentCat === $slug ? ' active' : ''; ?>"><?php echo esc($label); ?></a>
    <?php endforeach; ?>
  </div>
  <div class="news-grid">
    <?php foreach ($pageItems as $a): ?>
    <a class="news-card" href="/?category=<?php echo esc($a[0]); ?>&amp;page=<?php echo (int)$currentPage; ?>">
      <span class="news-cat" style="background:<?php echo $catColors[$a[0]] ?? '#4a4af4'; ?>;"><?php echo esc($categories[$a[0]] ?? $a[0]); ?></span>
      <h3><?php echo $a[1]; ?></h3>
      <p><?php echo esc($a[2]); ?></p>
      <div class="news-date"><?php echo esc($a[3]); ?></div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php if ($totalPages > 1): ?>
  <div class="pager">
    <a href="/?category=<?php echo esc($currentCat); ?>&amp;page=<?php echo $currentPage - 1; ?>" class="pg<?php echo $currentPage <= 1 ? ' disabled' : ''; ?>">&larr; Prev</a>
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a href="/?category=<?php echo esc($currentCat); ?>&amp;page=<?php echo $p; ?>" class="pg<?php echo $p === $currentPage ? ' active' : ''; ?>"><?php echo $p; ?></a>
    <?php endfor; ?>
    <a href="/?category=<?php echo esc($currentCat); ?>&amp;page=<?php echo $currentPage + 1; ?>" class="pg<?php echo $currentPage >= $totalPages ? ' disabled' : ''; ?>">Next &rarr;</a>
  </div>
  <?php endif; ?>
</section>

<footer class="foot"><div class="foot-in">
  <div><div class="flogo">affirm</div><p style="max-width:240px;">Buy now, pay over time. Flexible payments at the brands you love.</p></div>
  <div><h4>Shop</h4><ul><li>Fashion</li><li>Electronics</li><li>Travel</li><li>Home</li></ul></div>
  <div><h4>Company</h4><ul><li>About</li><li>Careers</li><li>Investors</li><li><a href="/?category=all&amp;page=1">Newsroom</a></li></ul></div>
  <div><h4>Support</h4><ul><li>Help center</li><li>Contact us</li><li>Privacy</li><li>Terms</li></ul></div>
</div><div class="foot-bot">© 2021 Affirm, Inc. Payment options through Affirm are subject to an eligibility check and are provided by these lending partners.</div></footer>
</body>
</html>
