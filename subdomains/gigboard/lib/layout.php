<?php
function page_open(string $title, string $extra=''): void {
    $u=user();
    $fl=$_SESSION['flash']??[]; unset($_SESSION['flash']);
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title)?> — GigBoard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f9fafb;--bg2:#fff;--bg3:#f0fdfa;--card:#fff;--border:#e5e7eb;
  --t:#0d9488;--t2:#14b8a6;--t3:#5eead4;
  --text:#111827;--text2:#374151;--text3:#6b7280;
  --amber:#f59e0b;--red:#ef4444;--green:#10b981;--blue:#3b82f6;
  --radius:12px;--shadow:0 1px 8px rgba(0,0,0,.07);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:15px}
a{color:var(--t);text-decoration:none}a:hover{color:var(--t2)}
/* NAV */
.nav{background:#fff;border-bottom:1px solid var(--border);padding:0 24px;position:sticky;top:0;z-index:100;box-shadow:var(--shadow)}
.nav-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:16px;height:60px}
.logo{font-size:1.25rem;font-weight:900;color:var(--text);letter-spacing:-.5px}
.logo span{color:var(--t)}
.logo-pro{font-size:.6rem;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:2px 6px;border-radius:4px;font-weight:700;vertical-align:middle;margin-left:5px}
.nav-links{display:flex;gap:4px;flex:1}
.nav-links a{color:var(--text3);font-size:.84rem;font-weight:500;padding:6px 11px;border-radius:7px;transition:.15s}
.nav-links a:hover,.nav-links a.active{background:#f0fdfa;color:var(--t);font-weight:600}
.nav-actions{display:flex;align-items:center;gap:10px}
.nav-plan{font-size:.72rem;font-weight:700;padding:4px 10px;border-radius:20px}
.plan-free{background:#f3f4f6;color:var(--text3)}
.plan-pro{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e;border:1px solid #fcd34d}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:600;cursor:pointer;border:none;border-radius:9px;padding:9px 18px;transition:.18s;font-size:.875rem;font-family:inherit}
.btn-primary{background:var(--t);color:#fff}
.btn-primary:hover{background:var(--t2);transform:translateY(-1px);box-shadow:0 3px 14px rgba(13,148,136,.3)}
.btn-secondary{background:#f9fafb;color:var(--text2);border:1px solid var(--border)}
.btn-secondary:hover{border-color:var(--t);color:var(--t);background:#f0fdfa}
.btn-amber{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff}
.btn-amber:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 3px 14px rgba(245,158,11,.35)}
.btn-outline{background:transparent;color:var(--t);border:1.5px solid var(--t)}
.btn-outline:hover{background:#f0fdfa}
.btn-sm{padding:6px 13px;font-size:.8rem;border-radius:7px}
.btn-lg{padding:12px 26px;font-size:.95rem}
/* FLASH */
.flash-wrap{max-width:1200px;margin:14px auto 0;padding:0 24px}
.flash{padding:11px 16px;border-radius:9px;font-size:.875rem;font-weight:500;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.flash-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46}
.flash-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
.flash-info{background:#e0f2fe;border:1px solid #7dd3fc;color:#0c4a6e}
.flash-warning{background:#fef3c7;border:1px solid #fcd34d;color:#92400e}
/* MAIN */
.main{max-width:1200px;margin:0 auto;padding:28px 24px 60px}
/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
.card-pad{padding:24px}
/* GIG CARD */
.gig-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px}
.gig-card{background:#fff;border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:.2s;display:flex;flex-direction:column}
.gig-card:hover{border-color:var(--t);box-shadow:0 6px 24px rgba(13,148,136,.12);transform:translateY(-2px)}
.gig-thumb{aspect-ratio:16/9;display:flex;align-items:center;justify-content:center;font-size:2.8rem;position:relative;overflow:hidden}
.gig-body{padding:14px;flex:1;display:flex;flex-direction:column;gap:6px}
.gig-seller{display:flex;align-items:center;gap:8px;font-size:.78rem;color:var(--text3)}
.gig-seller-name{font-weight:600;color:var(--text2)}
.gig-title{font-size:.875rem;font-weight:600;color:var(--text);line-height:1.4}
.gig-rating{display:flex;align-items:center;gap:5px;font-size:.78rem}
.stars{color:var(--amber);font-size:.8rem;letter-spacing:1px}
.gig-footer{padding:12px 14px;border-top:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between}
.gig-price{font-weight:800;font-size:.95rem}
.gig-delivery{font-size:.72rem;color:var(--text3)}
.featured-badge{position:absolute;top:8px;left:8px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:.65rem;font-weight:700;padding:3px 8px;border-radius:4px}
/* FORMS */
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:16px}
.form-label{font-size:.75rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px}
.form-control{background:#fafafa;border:1.5px solid var(--border);color:var(--text);border-radius:8px;padding:10px 14px;transition:.15s;outline:none;width:100%;font-family:inherit;font-size:.925rem}
.form-control:focus{border-color:var(--t);background:#fff;box-shadow:0 0 0 3px rgba(13,148,136,.1)}
select.form-control option{background:#fff}
/* PAGE TITLE */
.page-title{font-size:1.35rem;font-weight:800;letter-spacing:-.3px}
.section-sub{font-size:.875rem;color:var(--text3);margin-top:3px}
/* TABLES */
.table{width:100%;border-collapse:collapse}
.table th{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);padding:10px 14px;border-bottom:2px solid var(--border);text-align:left}
.table td{padding:11px 14px;border-bottom:1px solid #f9fafb;font-size:.875rem;vertical-align:middle}
/* TAG */
.tag{display:inline-block;padding:3px 9px;border-radius:5px;font-size:.7rem;font-weight:700;letter-spacing:.3px}
.tag-success{background:#d1fae5;color:#065f46}
.tag-danger{background:#fee2e2;color:#991b1b}
.tag-info{background:#e0f2fe;color:#0c4a6e}
.tag-warning{background:#fef3c7;color:#92400e}
.tag-pro{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e;border:1px solid #fcd34d}
.divider{border:none;border-top:1px solid var(--border);margin:20px 0}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
/* HERO */
.hero{background:linear-gradient(135deg,#0f766e 0%,#0d9488 50%,#0891b2 100%);padding:56px 24px;text-align:center;color:#fff;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
.hero-title{font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900;letter-spacing:-1px;line-height:1.15;margin-bottom:12px}
.hero-sub{font-size:1rem;opacity:.85;max-width:500px;margin:0 auto 24px}
.hero-search{display:flex;max-width:540px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.15)}
.hero-search input{flex:1;padding:13px 18px;border:none;outline:none;font-size:.95rem;font-family:inherit;color:var(--text)}
.hero-search button{background:var(--t);color:#fff;border:none;padding:13px 22px;font-weight:700;cursor:pointer;font-size:.9rem;font-family:inherit}
.cat-strip{max-width:1200px;margin:24px auto 0;padding:0 24px;display:flex;gap:8px;flex-wrap:wrap}
.cat-pill{padding:7px 16px;border-radius:25px;font-size:.82rem;font-weight:600;border:1px solid var(--border);background:#fff;color:var(--text3);cursor:pointer;transition:.15s;text-decoration:none}
.cat-pill:hover,.cat-pill.active{background:var(--t);border-color:var(--t);color:#fff}
/* PRO UPGRADE BANNER */
.upgrade-banner{background:linear-gradient(135deg,#fffbeb,#fef3c7);border:2px solid #fcd34d;border-radius:14px;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px}
.upgrade-banner h3{font-weight:700;color:#92400e;font-size:1rem;display:flex;align-items:center;gap:8px}
.upgrade-banner p{font-size:.82rem;color:#78350f;margin-top:4px}
/* FLAG BOX */
.flag-box{background:linear-gradient(135deg,#064e3b,#065f46);border:2px solid #059669;border-radius:14px;padding:24px 28px;margin-bottom:24px}
.flag-box h3{color:#34d399;font-size:1rem;font-weight:700;margin-bottom:10px;display:flex;align-items:center;gap:8px}
.flag-val{font-family:monospace;font-size:1.3rem;font-weight:800;color:#6ee7b7;background:#052e1c;border:1px solid #065f46;padding:8px 18px;border-radius:7px;display:inline-block;margin-top:6px;letter-spacing:2px}
/* ANALYTICS */
.stat-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px;text-align:center}
.stat-num{font-size:2rem;font-weight:900;color:var(--t)}
.stat-label{font-size:.78rem;color:var(--text3);font-weight:600;margin-top:4px;text-transform:uppercase;letter-spacing:.5px}
/* FOOTER */
.footer{background:#fff;border-top:1px solid var(--border);padding:24px;text-align:center;color:var(--text3);font-size:.8rem}
@media(max-width:700px){.nav-links{display:none}.grid-2{grid-template-columns:1fr}}
</style>
<?=$extra?>
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="<?=url()?>" class="logo">Gig<span>Board</span><?php if($u&&$u['plan']==='pro'):?><span class="logo-pro">PRO</span><?php endif;?></a>
    <div class="nav-links">
      <a href="<?=url()?>">Explore</a>
      <?php foreach([['design','🎨 Design'],['tech','💻 Tech'],['writing','✍️ Writing'],['marketing','📈 Marketing'],['video','🎬 Video']] as [$s,$l]):?>
      <a href="<?=url("category/$s")?>"><?=$l?></a>
      <?php endforeach;?>
    </div>
    <div class="nav-actions">
      <?php if($u):?>
        <span class="nav-plan <?=$u['plan']==='pro'?'plan-pro':'plan-free'?>"><?=$u['plan']==='pro'?'⭐ Pro':'Free'?></span>
        <a href="<?=url('dashboard')?>" class="btn btn-secondary btn-sm">👤 <?=h(explode(' ',$u['name'])[0])?></a>
        <?php if($u['is_admin']):?><a href="<?=url('admin')?>" class="btn btn-secondary btn-sm">⚙️</a><?php endif;?>
        <form method="POST" action="<?=url('logout')?>" style="margin:0">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <button class="btn btn-secondary btn-sm">Out</button>
        </form>
      <?php else:?>
        <a href="<?=url('login')?>" class="btn btn-secondary btn-sm">Sign In</a>
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

function page_close(): void {?>
<footer class="footer">
  <div>© 2025 GigBoard — The Freelancing Marketplace &nbsp;·&nbsp; 50,000+ Freelancers &nbsp;·&nbsp; 1M+ Projects Completed</div>
  <div style="margin-top:5px;font-size:.72rem">🔒 Secure Payments &nbsp;·&nbsp; Money-Back Guarantee &nbsp;·&nbsp; 24/7 Support</div>
</footer>
</body></html>
<?php
}

function gig_card(array $g): void {
    $bg_colors=['design'=>'#fdf2f8','tech'=>'#f0fdfa','writing'=>'#fffbeb','marketing'=>'#eff6ff','video'=>'#faf5ff','audio'=>'#f0fdf4','business'=>'#f9fafb'];
    $emoji=['design'=>'🎨','tech'=>'💻','writing'=>'✍️','marketing'=>'📈','video'=>'🎬','audio'=>'🎵','business'=>'💼'];
    $bg=$bg_colors[$g['cat_slug']]??'#f9fafb';
    $em=$emoji[$g['cat_slug']]??'📋';
    ?>
<div class="gig-card">
  <a href="<?=url("gig/{$g['id']}")?>" style="display:contents">
    <div class="gig-thumb" style="background:<?=$bg?>">
      <div style="font-size:3rem"><?=$em?></div>
      <?php if($g['featured']):?><div class="featured-badge">⭐ Featured</div><?php endif;?>
    </div>
    <div class="gig-body">
      <div class="gig-seller">
        <div style="width:24px;height:24px;border-radius:50%;background:<?=h($g['avatar_color'])?>;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;color:#fff">
          <?=strtoupper(substr($g['seller_name'],0,1))?>
        </div>
        <span class="gig-seller-name"><?=h($g['seller_name'])?></span>
        <?php if($g['seller_plan']==='pro'):?><span class="tag tag-pro" style="font-size:.6rem;padding:1px 5px">PRO</span><?php endif;?>
      </div>
      <div class="gig-title"><?=h($g['title'])?></div>
      <div class="gig-rating">
        <span class="stars"><?=stars((float)$g['rating'])?></span>
        <span style="font-weight:700;color:var(--text2)"><?=$g['rating']?></span>
        <span style="color:var(--text3)">(<?=number_format($g['review_count'])?>)</span>
      </div>
    </div>
  </a>
  <div class="gig-footer">
    <div>
      <div style="font-size:.7rem;color:var(--text3)">Starting at</div>
      <div class="gig-price"><?=inr((int)$g['price'])?></div>
    </div>
    <div class="gig-delivery">🚚 <?=$g['delivery_days']?> day<?=$g['delivery_days']>1?'s':''?></div>
  </div>
</div>
<?php
}
