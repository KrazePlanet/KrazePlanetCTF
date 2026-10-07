<?php
function page_open(string $title, string $extra=''): void {
    $u=user();
    $fl=$_SESSION['flash']??[]; unset($_SESSION['flash']);
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title)?> — FlashBuy</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#fff8f3;--bg2:#fff;--bg3:#fff3e8;--card:#fff;--border:#ffe4cc;
  --p:#f97316;--p2:#ea580c;--p3:#fed7aa;
  --text:#1a1a1a;--text2:#374151;--text3:#9ca3af;
  --green:#16a34a;--red:#dc2626;--amber:#d97706;--blue:#2563eb;
  --dark:#111827;
  --radius:12px;--shadow:0 2px 12px rgba(249,115,22,.1);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:15px}
a{color:var(--p2);text-decoration:none}a:hover{color:var(--p)}
/* NAV */
.nav{background:var(--dark);padding:0 24px;position:sticky;top:0;z-index:100;box-shadow:0 2px 16px rgba(0,0,0,.3)}
.nav-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:16px;height:60px}
.logo{font-size:1.3rem;font-weight:900;color:#fff;letter-spacing:-.5px;display:flex;align-items:center;gap:6px}
.logo span{color:var(--p)}
.logo-badge{background:var(--p);color:#fff;font-size:.55rem;font-weight:800;padding:2px 6px;border-radius:4px;letter-spacing:.5px;text-transform:uppercase;margin-left:4px}
.nav-search{flex:1;max-width:420px;margin:0 16px}
.nav-search input{width:100%;padding:9px 16px;border-radius:8px;border:none;background:#1f2937;color:#fff;font-size:.875rem;outline:none}
.nav-search input::placeholder{color:#6b7280}
.nav-actions{display:flex;align-items:center;gap:10px;margin-left:auto}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:600;cursor:pointer;border:none;border-radius:9px;padding:9px 18px;transition:.18s;font-size:.875rem;font-family:inherit}
.btn-primary{background:var(--p);color:#fff}
.btn-primary:hover{background:var(--p2);transform:translateY(-1px);box-shadow:0 4px 16px rgba(249,115,22,.4)}
.btn-secondary{background:#f9fafb;color:var(--text2);border:1px solid #e5e7eb}
.btn-secondary:hover{border-color:var(--p);color:var(--p);background:var(--bg3)}
.btn-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.btn-ghost{background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2)}
.btn-ghost:hover{background:rgba(255,255,255,.2)}
.btn-sm{padding:6px 13px;font-size:.8rem;border-radius:7px}
.btn-lg{padding:13px 28px;font-size:1rem}
/* FLASH */
.flash-wrap{max-width:1200px;margin:14px auto 0;padding:0 24px}
.flash{padding:11px 16px;border-radius:9px;font-size:.875rem;font-weight:500;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.flash-success{background:#dcfce7;border:1px solid #86efac;color:#14532d}
.flash-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
.flash-info{background:#fff3e8;border:1px solid var(--p3);color:#7c2d12}
.flash-warning{background:#fef3c7;border:1px solid #fcd34d;color:#92400e}
/* MAIN */
.main{max-width:1200px;margin:0 auto;padding:28px 24px 60px}
/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
.card-pad{padding:24px}
/* PRODUCT GRID */
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px}
.deal-card{background:#fff;border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:.2s;display:flex;flex-direction:column;position:relative}
.deal-card:hover{border-color:var(--p);box-shadow:0 8px 28px rgba(249,115,22,.15);transform:translateY(-3px)}
.deal-badge{position:absolute;top:10px;left:10px;background:var(--p);color:#fff;font-size:.6rem;font-weight:800;padding:3px 9px;border-radius:4px;text-transform:uppercase;letter-spacing:.5px;z-index:1}
.limit-badge{position:absolute;top:10px;right:10px;background:var(--dark);color:#fff;font-size:.6rem;font-weight:700;padding:3px 8px;border-radius:4px;z-index:1}
.deal-thumb{padding:28px 20px;display:flex;align-items:center;justify-content:center;font-size:5rem;background:linear-gradient(135deg,#fff8f3,#fff3e8)}
.deal-info{padding:14px 16px;flex:1;display:flex;flex-direction:column;gap:4px}
.deal-brand{font-size:.68rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.6px}
.deal-name{font-size:.9rem;font-weight:700;color:var(--text);line-height:1.35}
.deal-rating{display:flex;align-items:center;gap:4px;font-size:.72rem;margin-top:2px}
.deal-stars{color:var(--amber)}
.deal-prices{display:flex;align-items:baseline;gap:8px;margin-top:6px}
.deal-price{font-size:1.15rem;font-weight:900;color:var(--dark)}
.deal-orig{font-size:.8rem;color:var(--text3);text-decoration:line-through}
.deal-pct{background:#dcfce7;color:#14532d;font-size:.65rem;font-weight:800;padding:2px 7px;border-radius:4px}
.deal-stock{margin-top:8px}
.stock-bar-bg{height:5px;background:#f3f4f6;border-radius:3px;overflow:hidden;margin-bottom:4px}
.stock-bar{height:100%;background:linear-gradient(90deg,var(--green),#4ade80);border-radius:3px;transition:.3s}
.stock-label{font-size:.7rem;color:var(--red);font-weight:600}
.deal-footer{padding:10px 16px 14px}
/* HERO */
.hero{background:linear-gradient(135deg,#111827 0%,#1f2937 50%,#111827 100%);padding:56px 24px;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 70% 50%,rgba(249,115,22,.15),transparent 60%)}
.hero-inner{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center}
.hero-eyebrow{display:flex;align-items:center;gap:8px;margin-bottom:12px}
.live-dot{width:8px;height:8px;background:#ef4444;border-radius:50%;animation:pulse 1.5s ease-in-out infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.4)}}
.live-text{font-size:.72rem;font-weight:800;color:#ef4444;text-transform:uppercase;letter-spacing:1px}
.hero-title{font-size:clamp(1.8rem,3.5vw,2.6rem);font-weight:900;color:#fff;letter-spacing:-.5px;line-height:1.15}
.hero-title span{color:var(--p)}
.hero-sub{color:#9ca3af;font-size:.95rem;margin:10px 0 24px;line-height:1.6}
.countdown{display:flex;gap:14px;margin-bottom:24px}
.cd-box{background:rgba(249,115,22,.15);border:1px solid rgba(249,115,22,.3);border-radius:10px;padding:10px 16px;text-align:center;min-width:70px}
.cd-num{font-size:1.6rem;font-weight:900;color:var(--p);font-variant-numeric:tabular-nums;display:block}
.cd-label{font-size:.6rem;text-transform:uppercase;letter-spacing:.6px;color:#6b7280;font-weight:600}
.cd-sep{color:var(--p);font-size:1.8rem;font-weight:900;align-self:center;margin-bottom:8px}
.hero-chips{display:flex;gap:8px;flex-wrap:wrap}
.hero-chip{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:5px 13px;font-size:.75rem;color:#d1d5db;font-weight:500}
.hero-visual{display:flex;flex-direction:column;gap:12px}
.mini-deal{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:14px;display:flex;gap:12px;align-items:center}
.mini-emoji{font-size:1.6rem}
.mini-info{flex:1}
.mini-name{font-size:.8rem;font-weight:600;color:#f9fafb}
.mini-price{font-size:.7rem;color:var(--p);font-weight:700;margin-top:2px}
/* FORMS */
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:16px}
.form-label{font-size:.75rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px}
.form-control{background:#fafafa;border:1.5px solid #e5e7eb;color:var(--text);border-radius:8px;padding:10px 14px;transition:.15s;outline:none;width:100%;font-family:inherit;font-size:.925rem}
.form-control:focus{border-color:var(--p);background:#fff;box-shadow:0 0 0 3px rgba(249,115,22,.08)}
/* MISC */
.page-title{font-size:1.35rem;font-weight:800;letter-spacing:-.3px}
.section-sub{font-size:.875rem;color:var(--text3);margin-top:3px}
.tag{display:inline-block;padding:3px 9px;border-radius:5px;font-size:.7rem;font-weight:700;letter-spacing:.3px}
.tag-success{background:#dcfce7;color:#14532d}
.tag-danger{background:#fee2e2;color:#991b1b}
.tag-info{background:#fff3e8;color:#7c2d12}
.tag-warning{background:#fef3c7;color:#92400e}
.tag-dark{background:#1f2937;color:#f9fafb}
.table{width:100%;border-collapse:collapse}
.table th{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);padding:10px 14px;border-bottom:2px solid var(--border);text-align:left}
.table td{padding:11px 14px;border-bottom:1px solid #f9fafb;font-size:.875rem;vertical-align:middle}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
/* FLAG */
.flag-box{background:linear-gradient(135deg,#064e3b,#065f46);border:2px solid #059669;border-radius:14px;padding:24px 28px;margin-bottom:24px}
.flag-box h3{color:#34d399;font-size:1rem;font-weight:700;margin-bottom:10px}
.flag-val{font-family:monospace;font-size:1.3rem;font-weight:800;color:#6ee7b7;background:#052e1c;border:1px solid #065f46;padding:8px 18px;border-radius:7px;display:inline-block;margin-top:6px;letter-spacing:2px}
/* FOOTER */
.footer{background:var(--dark);padding:28px 24px;text-align:center;color:#4b5563;font-size:.8rem}
.footer a{color:#6b7280}
@media(max-width:700px){.hero-inner{grid-template-columns:1fr}.grid-2{grid-template-columns:1fr}}
</style>
<?=$extra?>
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="<?=url()?>" class="logo">⚡ Flash<span>Buy</span><span class="logo-badge">LIVE</span></a>
    <div class="nav-search">
      <input type="text" placeholder="Search deals, phones, laptops...">
    </div>
    <div class="nav-actions">
      <?php if($u):?>
        <a href="<?=url('orders')?>" class="btn btn-ghost btn-sm">📦 Orders</a>
        <a href="<?=url('dashboard')?>" class="btn btn-ghost btn-sm">👤 <?=h(explode(' ',$u['name'])[0])?></a>
        <?php if($u['is_admin']):?><a href="<?=url('admin')?>" class="btn btn-ghost btn-sm">⚙️</a><?php endif;?>
        <form method="POST" action="<?=url('logout')?>" style="margin:0">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <button class="btn btn-ghost btn-sm">Sign Out</button>
        </form>
      <?php else:?>
        <a href="<?=url('login')?>" class="btn btn-ghost btn-sm">Sign In</a>
        <a href="<?=url('register')?>" class="btn btn-primary btn-sm">Join Free</a>
      <?php endif;?>
    </div>
  </div>
</nav>
<?php if($fl):?>
<div class="flash-wrap"><?php foreach($fl as[$t,$m]):?><div class="flash flash-<?=h($t)?>"><?=h($m)?></div><?php endforeach;?></div>
<?php endif;?>
<?php
}

function page_close(): void { ?>
<footer class="footer">
  <div style="font-size:1rem;font-weight:800;color:#f9fafb;margin-bottom:8px">⚡ FlashBuy</div>
  <div>India's #1 Flash Sale Platform · Genuine Products · Secure Payments · 24/7 Support</div>
  <div style="margin-top:10px;display:flex;gap:18px;justify-content:center;flex-wrap:wrap">
    <a href="#">About Us</a><a href="#">Seller Hub</a><a href="#">Help Center</a><a href="#">Terms</a><a href="#">Privacy</a>
  </div>
  <div style="margin-top:12px;padding:10px;background:#1f2937;border-radius:8px;font-size:.72rem;color:#6b7280;display:inline-block">
    🔒 Authorized Cybersecurity Training Lab · For Educational Use Only
  </div>
</footer>
<script>
// Countdown timer — cycles to next hour
(function(){
  function tick(){
    var now=new Date(),end=new Date(now);
    end.setHours(end.getHours()+1,0,0,0);
    var diff=Math.max(0,Math.floor((end-now)/1000));
    var h=Math.floor(diff/3600),m=Math.floor((diff%3600)/60),s=diff%60;
    ['cd-h','cd-m','cd-s'].forEach(function(id,i){
      var el=document.getElementById(id);
      if(el) el.textContent=String([h,m,s][i]).padStart(2,'0');
    });
  }
  tick(); setInterval(tick,1000);
})();
</script>
</body></html>
<?php
}

function deal_card(array $p, bool $bought=false): void {
    $disc=pct($p['original_price'],$p['sale_price']);
    $stock_pct=min(100,max(0,(int)round($p['stock']/10*100)));
    ?>
<div class="deal-card">
  <?php if($p['deal_label']):?><div class="deal-badge"><?=h($p['deal_label'])?></div><?php endif;?>
  <div class="limit-badge">Limit: <?=$p['limit_per_user']?>/customer</div>
  <a href="<?=url("product/{$p['id']}")?>">
    <div class="deal-thumb"><?=h($p['emoji'])?></div>
  </a>
  <div class="deal-info">
    <div class="deal-brand"><?=h($p['brand'])?> · <?=h($p['category'])?></div>
    <a href="<?=url("product/{$p['id']}")?>" style="color:inherit"><div class="deal-name"><?=h($p['name'])?></div></a>
    <div class="deal-rating">
      <span class="deal-stars"><?=stars((float)$p['rating'])?></span>
      <span style="font-weight:700;color:var(--text2)"><?=$p['rating']?></span>
      <span style="color:var(--text3)">(<?=number_format($p['review_count'])?>)</span>
    </div>
    <div class="deal-prices">
      <span class="deal-price"><?=inr($p['sale_price'])?></span>
      <span class="deal-orig"><?=inr($p['original_price'])?></span>
      <span class="deal-pct"><?=$disc?>% OFF</span>
    </div>
    <div class="deal-stock">
      <div class="stock-bar-bg"><div class="stock-bar" style="width:<?=$stock_pct?>%"></div></div>
      <div class="stock-label"><?php
        if($p['stock']<=0) echo '❌ Sold Out';
        elseif($p['stock']<=3) echo "⚠️ Only {$p['stock']} left!";
        else echo "✅ {$p['stock']} units remaining";
      ?></div>
    </div>
  </div>
  <div class="deal-footer">
    <?php if($bought):?>
    <div class="btn" style="width:100%;background:#dcfce7;color:#14532d;cursor:default">✅ Purchased</div>
    <?php elseif($p['stock']<=0):?>
    <div class="btn" style="width:100%;background:#f3f4f6;color:var(--text3);cursor:not-allowed">Sold Out</div>
    <?php else:?>
    <a href="<?=url("product/{$p['id']}")?>" class="btn btn-primary" style="width:100%">Buy Now</a>
    <?php endif;?>
  </div>
</div>
<?php
}
