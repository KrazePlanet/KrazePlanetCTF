<?php
function page_open(string $title, string $extra=''): void {
    $u=user();
    $fl=$_SESSION['flash']??[]; unset($_SESSION['flash']);
    $cart_count=0;
    if($u){
        $st=db()->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id=?");
        $st->execute([$u['id']]); $cart_count=(int)$st->fetchColumn();
    }
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title)?> — RetailZone</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#fdf2f7;--bg2:#fff;--bg3:#fce7f3;--card:#fff;--border:#fce7f3;
  --p:#db2777;--p2:#ec4899;--p3:#f9a8d4;
  --text:#1f2937;--text2:#374151;--text3:#9ca3af;
  --green:#10b981;--red:#ef4444;--amber:#f59e0b;--blue:#3b82f6;
  --radius:12px;--shadow:0 2px 12px rgba(219,39,119,.08);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:15px}
a{color:var(--p2);text-decoration:none}a:hover{color:var(--p)}
/* NAV */
.nav{background:#fff;border-bottom:1px solid var(--border);padding:0 24px;position:sticky;top:0;z-index:100;box-shadow:var(--shadow)}
.nav-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:16px;height:62px}
.logo{font-size:1.25rem;font-weight:900;color:var(--text);letter-spacing:-.5px}
.logo span{color:var(--p)}
.nav-links{display:flex;gap:4px;flex:1}
.nav-links a{color:var(--text3);font-size:.84rem;font-weight:500;padding:6px 11px;border-radius:7px;transition:.15s}
.nav-links a:hover{background:var(--bg3);color:var(--p);font-weight:600}
.nav-actions{display:flex;align-items:center;gap:10px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:600;cursor:pointer;border:none;border-radius:9px;padding:9px 18px;transition:.18s;font-size:.875rem;font-family:inherit}
.btn-primary{background:var(--p);color:#fff}
.btn-primary:hover{background:var(--p2);transform:translateY(-1px);box-shadow:0 4px 16px rgba(219,39,119,.3)}
.btn-secondary{background:#f9fafb;color:var(--text2);border:1px solid #e5e7eb}
.btn-secondary:hover{border-color:var(--p);color:var(--p);background:var(--bg3)}
.btn-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.btn-danger:hover{background:#fecaca}
.btn-outline{background:transparent;color:var(--p);border:1.5px solid var(--p)}
.btn-outline:hover{background:var(--bg3)}
.btn-sm{padding:6px 13px;font-size:.8rem;border-radius:7px}
.btn-lg{padding:12px 26px;font-size:.95rem}
.cart-btn{position:relative;background:var(--bg3);border:1px solid var(--border);color:var(--p);border-radius:8px;padding:7px 13px;font-size:.82rem;font-weight:700;cursor:pointer;transition:.15s}
.cart-btn:hover{background:var(--p);color:#fff}
.cart-badge{position:absolute;top:-6px;right:-6px;background:var(--p);color:#fff;font-size:.6rem;font-weight:700;padding:2px 5px;border-radius:10px;min-width:18px;text-align:center}
/* FLASH */
.flash-wrap{max-width:1200px;margin:14px auto 0;padding:0 24px}
.flash{padding:11px 16px;border-radius:9px;font-size:.875rem;font-weight:500;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.flash-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46}
.flash-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
.flash-info{background:#fce7f3;border:1px solid #f9a8d4;color:#831843}
.flash-warning{background:#fef3c7;border:1px solid #fcd34d;color:#92400e}
/* MAIN */
.main{max-width:1200px;margin:0 auto;padding:28px 24px 60px}
/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
.card-pad{padding:24px}
/* PRODUCT GRID */
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px}
.product-card{background:#fff;border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:.2s;display:flex;flex-direction:column;cursor:pointer}
.product-card:hover{border-color:var(--p);box-shadow:0 6px 24px rgba(219,39,119,.12);transform:translateY(-2px)}
.product-thumb{aspect-ratio:3/4;display:flex;align-items:center;justify-content:center;font-size:3.5rem;position:relative}
.product-discount{position:absolute;top:8px;left:8px;background:var(--p);color:#fff;font-size:.65rem;font-weight:700;padding:3px 8px;border-radius:4px}
.product-info{padding:12px 14px;flex:1}
.product-brand{font-size:.7rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.6px}
.product-name{font-size:.875rem;font-weight:600;color:var(--text);margin-top:3px;line-height:1.35}
.product-rating{display:flex;align-items:center;gap:4px;margin-top:5px;font-size:.75rem}
.stars{color:var(--amber);font-size:.75rem;letter-spacing:1px}
.product-price-row{display:flex;align-items:center;gap:8px;margin-top:6px}
.product-price{font-size:1rem;font-weight:800}
.product-orig{font-size:.78rem;color:var(--text3);text-decoration:line-through}
/* ORDER STATUS TIMELINE */
.timeline{display:flex;flex-direction:column;gap:0}
.tl-step{display:flex;gap:16px;padding-bottom:20px;position:relative}
.tl-step:last-child{padding-bottom:0}
.tl-step::before{content:'';position:absolute;left:15px;top:30px;bottom:0;width:2px;background:var(--border)}
.tl-step:last-child::before{display:none}
.tl-dot{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;z-index:1}
.tl-dot-done{background:var(--p);color:#fff}
.tl-dot-active{background:#fff;border:2px solid var(--p);color:var(--p)}
.tl-dot-pending{background:#f3f4f6;border:2px solid #e5e7eb;color:var(--text3)}
.tl-content{flex:1;padding-top:4px}
.tl-label{font-weight:600;font-size:.875rem}
.tl-time{font-size:.75rem;color:var(--text3);margin-top:2px}
/* FORMS */
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:16px}
.form-label{font-size:.75rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px}
.form-control{background:#fafafa;border:1.5px solid #e5e7eb;color:var(--text);border-radius:8px;padding:10px 14px;transition:.15s;outline:none;width:100%;font-family:inherit;font-size:.925rem}
.form-control:focus{border-color:var(--p);background:#fff;box-shadow:0 0 0 3px rgba(219,39,119,.08)}
/* MISC */
.page-title{font-size:1.35rem;font-weight:800;letter-spacing:-.3px}
.section-sub{font-size:.875rem;color:var(--text3);margin-top:3px}
.divider{border:none;border-top:1px solid var(--border);margin:20px 0}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.tag{display:inline-block;padding:3px 9px;border-radius:5px;font-size:.7rem;font-weight:700;letter-spacing:.3px}
.tag-success{background:#d1fae5;color:#065f46}
.tag-danger{background:#fee2e2;color:#991b1b}
.tag-info{background:#fce7f3;color:#831843}
.tag-warning{background:#fef3c7;color:#92400e}
.table{width:100%;border-collapse:collapse}
.table th{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);padding:10px 14px;border-bottom:2px solid var(--border);text-align:left}
.table td{padding:11px 14px;border-bottom:1px solid #f9fafb;font-size:.875rem;vertical-align:middle}
/* HERO */
.hero{background:linear-gradient(135deg,#be185d 0%,#db2777 40%,#ec4899 100%);padding:52px 24px;text-align:center;color:#fff;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 70% 50% at 80% 50%,rgba(255,255,255,.08),transparent)}
.hero-title{font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900;letter-spacing:-1px;line-height:1.15}
.hero-sub{font-size:1rem;opacity:.9;max-width:480px;margin:10px auto 22px}
.hero-chips{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
.hero-chip{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:20px;padding:5px 14px;font-size:.78rem;font-weight:600}
.cat-strip{max-width:1200px;margin:22px auto 0;padding:0 24px;display:flex;gap:8px;flex-wrap:wrap}
.cat-pill{padding:7px 16px;border-radius:25px;font-size:.82rem;font-weight:600;border:1px solid var(--border);background:#fff;color:var(--text3);cursor:pointer;transition:.15s;text-decoration:none;display:flex;align-items:center;gap:5px}
.cat-pill:hover,.cat-pill.active{background:var(--p);border-color:var(--p);color:#fff}
/* FLAG */
.flag-box{background:linear-gradient(135deg,#064e3b,#065f46);border:2px solid #059669;border-radius:14px;padding:24px 28px;margin-bottom:24px}
.flag-box h3{color:#34d399;font-size:1rem;font-weight:700;margin-bottom:10px;display:flex;align-items:center;gap:8px}
.flag-val{font-family:monospace;font-size:1.3rem;font-weight:800;color:#6ee7b7;background:#052e1c;border:1px solid #065f46;padding:8px 18px;border-radius:7px;display:inline-block;margin-top:6px;letter-spacing:2px}
/* WALLET */
.wallet-card{background:linear-gradient(135deg,var(--p),#9d174d);color:#fff;border-radius:16px;padding:24px;margin-bottom:20px;position:relative;overflow:hidden}
.wallet-card::before{content:'';position:absolute;top:-30px;right:-30px;width:150px;height:150px;background:rgba(255,255,255,.08);border-radius:50%}
/* FOOTER */
.footer{background:#fff;border-top:1px solid var(--border);padding:24px;text-align:center;color:var(--text3);font-size:.8rem}
@media(max-width:700px){.nav-links{display:none}.grid-2{grid-template-columns:1fr}}
</style>
<?=$extra?>
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="<?=url()?>" class="logo">Retail<span>Zone</span></a>
    <div class="nav-links">
      <?php foreach([['women','👗 Women'],['men','👔 Men'],['kids','🧒 Kids'],['footwear','👟 Footwear'],['accessories','👜 Accessories'],['beauty','💄 Beauty']] as[$s,$l]):?>
      <a href="<?=url("category/$s")?>"><?=$l?></a>
      <?php endforeach;?>
    </div>
    <div class="nav-actions">
      <?php if($u):?>
        <a href="<?=url('cart')?>" class="cart-btn">
          🛒 Bag<?php if($cart_count):?><span class="cart-badge"><?=$cart_count?></span><?php endif;?>
        </a>
        <a href="<?=url('dashboard')?>" class="btn btn-secondary btn-sm">👤 <?=h(explode(' ',$u['name'])[0])?></a>
        <?php if($u['is_admin']):?><a href="<?=url('admin')?>" class="btn btn-secondary btn-sm">⚙️</a><?php endif;?>
        <form method="POST" action="<?=url('logout')?>" style="margin:0">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <button class="btn btn-secondary btn-sm">Out</button>
        </form>
      <?php else:?>
        <a href="<?=url('login')?>" class="btn btn-secondary btn-sm">Sign In</a>
        <a href="<?=url('register')?>" class="btn btn-primary btn-sm">Register</a>
      <?php endif;?>
    </div>
  </div>
</nav>
<?php if($fl):?>
<div class="flash-wrap"><?php foreach($fl as[$t,$m]):?><div class="flash flash-<?=h($t)?>"><?=h($m)?></div><?php endforeach;?></div>
<?php endif;?>
<?php
}

function page_close(): void {?>
<footer class="footer">
  <div>© 2025 RetailZone Fashion — India's Favourite Fashion Destination</div>
  <div style="margin-top:5px;font-size:.72rem">🚚 Free delivery ₹799+ &nbsp;·&nbsp; 7-Day Easy Returns &nbsp;·&nbsp; Genuine Products &nbsp;·&nbsp; Secure Payments</div>
</footer>
</body></html>
<?php
}

function product_card(array $p): void {
    $disc=$p['original_price']?(int)round((1-$p['price']/$p['original_price'])*100):0;
    $emojis=['women'=>'👗','men'=>'👔','kids'=>'🧒','footwear'=>'👟','accessories'=>'👜','beauty'=>'💄'];
    $bgs=['women'=>'#fdf2f8','men'=>'#eff6ff','kids'=>'#f0fdf4','footwear'=>'#fffbeb','accessories'=>'#faf5ff','beauty'=>'#fdf2f8'];
    $em=$emojis[$p['cat_slug']]??'🛍️';
    $bg=$bgs[$p['cat_slug']]??'#fdf2f8';
    ?>
<div class="product-card" onclick="location.href='<?=url("product/{$p['id']}")?>'">
  <div class="product-thumb" style="background:<?=$bg?>">
    <div style="font-size:4rem"><?=$em?></div>
    <?php if($disc>0):?><div class="product-discount"><?=$disc?>% OFF</div><?php endif;?>
  </div>
  <div class="product-info">
    <div class="product-brand"><?=h($p['brand'])?></div>
    <div class="product-name"><?=h($p['name'])?></div>
    <div class="product-rating">
      <span class="stars"><?=stars((float)$p['rating'])?></span>
      <span style="font-weight:600;color:var(--text2)"><?=$p['rating']?></span>
      <span style="color:var(--text3)">(<?=number_format($p['review_count'])?>)</span>
    </div>
    <div class="product-price-row">
      <span class="product-price"><?=inr((int)$p['price'])?></span>
      <?php if($p['original_price']):?>
      <span class="product-orig"><?=inr((int)$p['original_price'])?></span>
      <?php endif;?>
    </div>
  </div>
</div>
<?php
}
