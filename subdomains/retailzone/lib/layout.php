<?php
// Unsplash cover photo for a product: a hand-picked shot per seeded item, with a
// per-category fallback so anything without its own photo still shows a real image.
function product_image(array $p, int $w = 600): string {
    static $by_name = [
        'Floral Wrap Midi Dress'        => '1572804013309-59a88b7e92f1',
        'High-Rise Skinny Jeans'        => '1541099649105-f69ad21f3246',
        'Embroidered Kurta Set'         => '1610030469983-98e550d6193c',
        'Ribbed Crop Sweater'           => '1434389677669-e08b4cac3105',
        'Slim Fit Oxford Shirt'         => '1596755094514-f87e34085b2c',
        'Chino Trousers'                => '1473966968600-fa801b869a1a',
        'Polo T-Shirt 3-Pack'           => '1602810318383-e386cc2a3ccf',
        'Bomber Jacket'                 => '1591047139829-d91aecb6caea',
        'Dino Print T-Shirt Set'        => '1519238263530-99bdd11df2ea',
        'Dungaree with T-Shirt'         => '1476234251651-f353703a034d',
        'Air Cushion Running Shoes'     => '1542291026-7eec264c27ff',
        'Block Heel Sandals'            => '1596703263926-eb0762ee17e4',
        'High-Top Canvas Sneakers'      => '1595950653106-6c9ebd614d3a',
        'Crossbody Sling Bag'           => '1584917865442-de89df76afd3',
        'Minimalist Watch'              => '1524592094714-0f0654e20314',
        'Silk Scrunchie Set (5-pack)'   => '1596944924616-7b38e7cfac36',
        'Hydrating Face Serum 30ml'     => '1620916297397-a4a5402a3c6c',
        'Matte Lipstick Quad'           => '1586495777744-4413f21062fa',
        'Sunscreen SPF 50+ PA++++'      => '1556228578-8c89e6adf883',
        'Vitamin C Brightening Kit'     => '1627384113743-6bd5a479fffd',
    ];
    static $by_cat = [
        'women'=>'1572804013309-59a88b7e92f1','men'=>'1596755094514-f87e34085b2c',
        'kids'=>'1519238263530-99bdd11df2ea','footwear'=>'1542291026-7eec264c27ff',
        'accessories'=>'1584917865442-de89df76afd3','beauty'=>'1620916297397-a4a5402a3c6c',
    ];
    $id = $by_name[$p['name']] ?? $by_cat[$p['cat_slug'] ?? ''] ?? '1572804013309-59a88b7e92f1';
    return "https://images.unsplash.com/photo-$id?w=$w&h=".(int)round($w*4/3)."&fit=crop&auto=format&q=75";
}

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
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f6f2ec;--bg2:#fff;--bg3:#efe7db;--card:#fff;--border:#e7ddce;
  --ink:#17140f;--accent:#8c2f39;--accent2:#a93f49;
  --text:#2a2620;--text2:#574f44;--text3:#9c9183;
  --green:#2f7d53;--red:#b4231f;--amber:#b8860b;
  --radius:4px;--shadow:0 1px 2px rgba(23,20,15,.04),0 8px 24px rgba(23,20,15,.05);
  --serif:'Fraunces',Georgia,serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:15px;-webkit-font-smoothing:antialiased}
a{color:var(--accent);text-decoration:none}a:hover{color:var(--accent2)}
img{display:block;max-width:100%}
/* NAV */
.nav{background:rgba(246,242,236,.92);backdrop-filter:blur(10px);border-bottom:1px solid var(--border);padding:0 28px;position:sticky;top:0;z-index:100}
.nav-inner{max-width:1180px;margin:0 auto;display:flex;align-items:center;gap:22px;height:66px}
.logo{font-family:var(--serif);font-size:1.5rem;font-weight:600;color:var(--ink);letter-spacing:.2px}
.logo span{font-style:italic;color:var(--accent)}
.nav-links{display:flex;gap:2px;flex:1}
.nav-links a{color:var(--text2);font-size:.82rem;font-weight:500;padding:7px 13px;border-radius:3px;letter-spacing:.3px;text-transform:uppercase;transition:.15s}
.nav-links a:hover{color:var(--ink);background:var(--bg3)}
.nav-actions{display:flex;align-items:center;gap:9px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;font-weight:600;cursor:pointer;border:none;border-radius:3px;padding:10px 20px;transition:.18s;font-size:.84rem;font-family:inherit;letter-spacing:.3px}
.btn-primary{background:var(--ink);color:#f6f2ec}
.btn-primary:hover{background:#000}
.btn-secondary{background:transparent;color:var(--text2);border:1px solid var(--border)}
.btn-secondary:hover{border-color:var(--ink);color:var(--ink)}
.btn-danger{background:transparent;color:var(--red);border:1px solid #e4c3c1}
.btn-danger:hover{background:#fbeceb}
.btn-outline{background:transparent;color:var(--ink);border:1px solid var(--ink)}
.btn-outline:hover{background:var(--ink);color:#f6f2ec}
.btn-sm{padding:6px 13px;font-size:.78rem}
.btn-lg{padding:13px 28px;font-size:.9rem}
.cart-btn{position:relative;color:var(--ink);font-size:.82rem;font-weight:600;padding:8px 14px;border:1px solid var(--border);border-radius:3px;transition:.15s;text-transform:uppercase;letter-spacing:.3px}
.cart-btn:hover{border-color:var(--ink)}
.cart-badge{position:absolute;top:-7px;right:-7px;background:var(--accent);color:#fff;font-size:.6rem;font-weight:700;padding:2px 6px;border-radius:20px;min-width:18px;text-align:center}
/* FLASH */
.flash-wrap{max-width:1180px;margin:16px auto 0;padding:0 28px}
.flash{padding:12px 16px;border-radius:3px;font-size:.875rem;font-weight:500;margin-bottom:8px;border-left:3px solid}
.flash-success{background:#eaf4ee;border-color:var(--green);color:#1e5338}
.flash-error{background:#fbeceb;border-color:var(--red);color:#8a1a17}
.flash-info{background:var(--bg3);border-color:var(--accent);color:#6b232b}
.flash-warning{background:#f7efda;border-color:var(--amber);color:#7a5a0c}
/* MAIN */
.main{max-width:1180px;margin:0 auto;padding:34px 28px 70px}
/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
.card-pad{padding:24px}
/* PRODUCT GRID */
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:26px}
.product-card{background:var(--card);border-radius:var(--radius);overflow:hidden;transition:.2s;display:flex;flex-direction:column;cursor:pointer}
.product-thumb{aspect-ratio:3/4;position:relative;overflow:hidden;background:var(--bg3)}
.product-thumb img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease}
.product-card:hover .product-thumb img{transform:scale(1.045)}
.product-discount{position:absolute;top:10px;left:10px;background:var(--accent);color:#fff;font-size:.64rem;font-weight:700;padding:4px 9px;border-radius:2px;letter-spacing:.4px}
.product-info{padding:13px 2px 4px}
.product-brand{font-size:.68rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.8px}
.product-name{font-size:.92rem;font-weight:500;color:var(--ink);margin-top:4px;line-height:1.35}
.product-rating{display:flex;align-items:center;gap:4px;margin-top:6px;font-size:.74rem}
.stars{color:var(--amber);font-size:.74rem;letter-spacing:1px}
.product-price-row{display:flex;align-items:baseline;gap:8px;margin-top:7px}
.product-price{font-size:1rem;font-weight:700;color:var(--ink)}
.product-orig{font-size:.78rem;color:var(--text3);text-decoration:line-through}
/* ORDER STATUS TIMELINE */
.timeline{display:flex;flex-direction:column;gap:0}
.tl-step{display:flex;gap:16px;padding-bottom:20px;position:relative}
.tl-step:last-child{padding-bottom:0}
.tl-step::before{content:'';position:absolute;left:15px;top:30px;bottom:0;width:2px;background:var(--border)}
.tl-step:last-child::before{display:none}
.tl-dot{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;z-index:1}
.tl-dot-done{background:var(--ink);color:#f6f2ec}
.tl-dot-active{background:#fff;border:2px solid var(--ink);color:var(--ink)}
.tl-dot-pending{background:#f1ece3;border:2px solid var(--border);color:var(--text3)}
.tl-content{flex:1;padding-top:4px}
.tl-label{font-weight:600;font-size:.875rem}
.tl-time{font-size:.75rem;color:var(--text3);margin-top:2px}
/* FORMS */
.form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
.form-label{font-size:.72rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.6px}
.form-control{background:#fcfaf7;border:1px solid var(--border);color:var(--text);border-radius:3px;padding:11px 14px;transition:.15s;outline:none;width:100%;font-family:inherit;font-size:.925rem}
.form-control:focus{border-color:var(--ink);background:#fff}
/* MISC */
.page-title{font-family:var(--serif);font-size:1.6rem;font-weight:600;letter-spacing:.2px;color:var(--ink)}
.section-sub{font-size:.875rem;color:var(--text3);margin-top:4px}
.divider{border:none;border-top:1px solid var(--border);margin:20px 0}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.tag{display:inline-block;padding:3px 9px;border-radius:2px;font-size:.68rem;font-weight:700;letter-spacing:.4px;text-transform:uppercase}
.tag-success{background:#eaf4ee;color:#1e5338}
.tag-danger{background:#fbeceb;color:#8a1a17}
.tag-info{background:var(--bg3);color:#6b232b}
.tag-warning{background:#f7efda;color:#7a5a0c}
.table{width:100%;border-collapse:collapse}
.table th{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);padding:12px 16px;border-bottom:1px solid var(--border);text-align:left}
.table td{padding:13px 16px;border-bottom:1px solid var(--bg3);font-size:.875rem;vertical-align:middle}
/* HERO */
.hero{position:relative;overflow:hidden;background:var(--ink)}
.hero-bg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.55}
.hero-inner{position:relative;max-width:1180px;margin:0 auto;padding:84px 28px;color:#f6f2ec}
.hero-eyebrow{font-size:.74rem;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#e7c9cb;margin-bottom:14px}
.hero-title{font-family:var(--serif);font-size:clamp(2.2rem,5vw,3.6rem);font-weight:600;letter-spacing:.3px;line-height:1.05;max-width:620px}
.hero-title em{font-style:italic;color:#f0b7bc}
.hero-sub{font-size:1.02rem;opacity:.85;max-width:440px;margin:18px 0 26px;line-height:1.6}
.hero-chips{display:flex;gap:22px;flex-wrap:wrap;font-size:.8rem;color:#e9ddd0;letter-spacing:.3px}
.cat-strip{max-width:1180px;margin:26px auto 0;padding:0 28px;display:flex;gap:9px;flex-wrap:wrap}
.cat-pill{padding:8px 18px;border-radius:40px;font-size:.8rem;font-weight:600;border:1px solid var(--border);background:var(--bg2);color:var(--text2);cursor:pointer;transition:.15s;letter-spacing:.3px}
.cat-pill:hover,.cat-pill.active{background:var(--ink);border-color:var(--ink);color:#f6f2ec}
.section-head{font-family:var(--serif);font-size:1.7rem;font-weight:600;color:var(--ink)}
/* WALLET */
.wallet-card{background:var(--ink);color:#f6f2ec;border-radius:var(--radius);padding:26px;margin-bottom:20px;position:relative;overflow:hidden}
.wallet-card::before{content:'';position:absolute;top:-40px;right:-30px;width:170px;height:170px;background:rgba(240,183,188,.12);border-radius:50%}
/* FOOTER */
.footer{background:var(--bg2);border-top:1px solid var(--border);padding:30px 24px;text-align:center;color:var(--text3);font-size:.8rem}
.footer .f-logo{font-family:var(--serif);font-size:1.1rem;color:var(--ink);margin-bottom:6px}
@media(max-width:760px){.nav-links{display:none}.grid-2{grid-template-columns:1fr}}
</style>
<?=$extra?>
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="<?=url()?>" class="logo">Retail<span>Zone</span></a>
    <div class="nav-links">
      <?php foreach([['women','Women'],['men','Men'],['kids','Kids'],['footwear','Footwear'],['accessories','Accessories'],['beauty','Beauty']] as[$s,$l]):?>
      <a href="<?=url("category/$s")?>"><?=$l?></a>
      <?php endforeach;?>
    </div>
    <div class="nav-actions">
      <?php if($u):?>
        <a href="<?=url('cart')?>" class="cart-btn">
          Bag<?php if($cart_count):?><span class="cart-badge"><?=$cart_count?></span><?php endif;?>
        </a>
        <a href="<?=url('dashboard')?>" class="btn btn-secondary btn-sm"><?=h(explode(' ',$u['name'])[0])?></a>
        <?php if($u['is_admin']):?><a href="<?=url('admin')?>" class="btn btn-secondary btn-sm">Admin</a><?php endif;?>
        <form method="POST" action="<?=url('logout')?>" style="margin:0">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <button class="btn btn-secondary btn-sm">Sign Out</button>
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
  <div class="f-logo">RetailZone</div>
  <div>© 2025 RetailZone Fashion — India's Favourite Fashion Destination</div>
  <div style="margin-top:6px;font-size:.72rem">Free delivery over ₹799 · 7-Day Easy Returns · Genuine Products · Secure Payments</div>
</footer>
</body></html>
<?php
}

function product_card(array $p): void {
    $disc=$p['original_price']?(int)round((1-$p['price']/$p['original_price'])*100):0;
    ?>
<div class="product-card" onclick="location.href='<?=url("product/{$p['id']}")?>'">
  <div class="product-thumb">
    <img src="<?=h(product_image($p))?>" alt="<?=h($p['name'])?>" loading="lazy">
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
