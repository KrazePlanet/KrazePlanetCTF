<?php
function page_open(string $title, string $extra=''): void {
    $u=user();
    $fl=$_SESSION['flash']??[]; unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title)?> — AccountHub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f8f9ff;--bg2:#fff;--bg3:#eef0fb;--card:#fff;--border:#e0e3f0;
  --p:#4f46e5;--p2:#4338ca;--p3:#ede9fe;
  --text:#0f0f23;--text2:#1e1e3f;--text3:#6b7280;
  --green:#059669;--red:#dc2626;--amber:#d97706;
  --nav:#0f0f23;
  --radius:12px;--shadow:0 1px 8px rgba(79,70,229,.07);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:15px}
a{color:var(--p);text-decoration:none}a:hover{color:var(--p2)}
/* NAV */
.nav{background:var(--nav);padding:0 24px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.18)}
.nav-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;height:56px;gap:18px}
.logo{font-size:1.15rem;font-weight:800;color:#fff;letter-spacing:-.3px;display:flex;align-items:center;gap:7px}
.logo span{color:#a5b4fc}
.nav-links{display:flex;gap:2px;flex:1;margin-left:10px}
.nav-link{color:#94a3b8;font-size:.82rem;font-weight:500;padding:6px 11px;border-radius:6px;transition:.15s}
.nav-link:hover{background:rgba(255,255,255,.08);color:#e2e8f0}
.nav-link.admin{color:#fbbf24}
.nav-actions{display:flex;align-items:center;gap:8px;margin-left:auto}
/* LAYOUT */
.wrap{max-width:1200px;margin:0 auto;padding:28px 20px 60px}
.two-col{display:grid;grid-template-columns:220px 1fr;gap:28px;align-items:start}
/* SIDEBAR */
.sidenav{background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;position:sticky;top:70px}
.sidenav-user{padding:16px;border-bottom:1px solid var(--border);background:linear-gradient(135deg,var(--p3),#fff)}
.sidenav-link{display:flex;align-items:center;gap:9px;padding:10px 16px;font-size:.84rem;font-weight:500;color:var(--text2);border-bottom:1px solid #f0f0fb;transition:.15s}
.sidenav-link:hover{background:var(--p3);color:var(--p)}
.sidenav-link.active{background:var(--p3);color:var(--p);font-weight:700}
.sidenav-link.locked{color:#94a3b8;cursor:default}
/* BUTTONS */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:600;cursor:pointer;border:none;border-radius:9px;padding:8px 18px;transition:.18s;font-size:.84rem;font-family:inherit;text-decoration:none!important}
.btn-primary{background:var(--p);color:#fff}.btn-primary:hover{background:var(--p2);transform:translateY(-1px)}
.btn-secondary{background:rgba(255,255,255,.07);color:#e2e8f0;border:1px solid rgba(255,255,255,.12)}.btn-secondary:hover{background:rgba(255,255,255,.14)}
.btn-outline{background:transparent;border:1.5px solid var(--p);color:var(--p)}.btn-outline:hover{background:var(--p3)}
.btn-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.btn-sm{padding:5px 12px;font-size:.78rem;border-radius:7px}
.btn-lg{padding:11px 24px;font-size:.95rem}
/* FLASH */
.flash-wrap{max-width:1200px;margin:12px auto 0;padding:0 20px}
.flash{padding:11px 16px;border-radius:9px;font-size:.84rem;font-weight:500;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.flash-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46}
.flash-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
/* STAT GRID */
.stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
.stat-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:18px;box-shadow:var(--shadow)}
.stat-num{font-size:1.6rem;font-weight:800;color:var(--p);margin-bottom:4px}
.stat-lbl{font-size:.7rem;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:.4px}
/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
.card-pad{padding:22px}
/* FORMS */
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:15px}
.form-label{font-size:.72rem;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.4px}
.form-control{background:#fafafa;border:1.5px solid var(--border);color:var(--text);border-radius:8px;padding:9px 13px;outline:none;width:100%;font-family:inherit;font-size:.9rem;transition:.15s}
.form-control:focus{border-color:var(--p);background:#fff;box-shadow:0 0 0 3px rgba(79,70,229,.1)}
/* PLAN CARD */
.plan-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.plan-card{border:2px solid var(--border);border-radius:var(--radius);padding:20px;text-align:center;transition:.15s}
.plan-card.current{border-color:var(--p);background:var(--p3)}
.plan-name{font-size:1.1rem;font-weight:800;margin-bottom:8px}
.plan-price{font-size:1.6rem;font-weight:900;margin-bottom:12px}
.plan-feature{font-size:.78rem;color:var(--text3);padding:4px 0;border-bottom:1px solid var(--border)}
/* TABLE */
.table{width:100%;border-collapse:collapse;font-size:.84rem}
.table th{text-align:left;padding:10px 14px;font-size:.7rem;font-weight:700;text-transform:uppercase;color:var(--text3);background:var(--bg3);border-bottom:2px solid var(--border)}
.table td{padding:11px 14px;border-bottom:1px solid var(--bg3)}
/* FLAG */
.flag-box{background:linear-gradient(135deg,#064e3b,#065f46);border:2px solid #059669;border-radius:12px;padding:24px 28px;margin-bottom:20px}
.flag-box h3{color:#34d399;font-size:1rem;font-weight:700;margin-bottom:10px}
.flag-val{font-family:monospace;font-size:1.2rem;font-weight:800;color:#6ee7b7;background:#052e1c;border:1px solid #065f46;padding:8px 18px;border-radius:7px;display:inline-block;margin-top:6px;letter-spacing:2px}
/* ADMIN BANNER */
.admin-banner{background:linear-gradient(135deg,#fef3c7,#fffbeb);border:2px solid var(--amber);border-radius:12px;padding:16px 20px;margin-bottom:20px}
/* FOOTER */
.footer{background:var(--nav);padding:18px;text-align:center;color:#4b5563;font-size:.76rem}
@media(max-width:900px){.two-col{grid-template-columns:1fr}.plan-grid{grid-template-columns:1fr}.stat-grid{grid-template-columns:repeat(2,1fr)}}
</style>
<?=$extra?>
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="<?=url()?>" class="logo">⚡ <span>Account</span>Hub</a>
    <?php if($u=user()):?>
    <div class="nav-links">
      <a href="<?=url()?>" class="nav-link">📊 Dashboard</a>
      <a href="<?=url('projects')?>" class="nav-link">📁 Projects</a>
      <a href="<?=url('billing')?>" class="nav-link">💳 Billing</a>
      <a href="<?=url('settings')?>" class="nav-link">⚙️ Settings</a>
      <?php if($u['is_admin']):?>
      <a href="<?=url('admin')?>" class="nav-link admin">🛡️ Admin</a>
      <?php endif;?>
    </div>
    <?php endif;?>
    <div class="nav-actions">
      <?php if($u=user()):?>
        <?=plan_badge($u['plan'])?>
        <form method="POST" action="<?=url('logout')?>" style="margin:0">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <button class="btn btn-secondary btn-sm">Sign Out</button>
        </form>
      <?php else:?>
        <a href="<?=url('login')?>" class="btn btn-primary btn-sm">Sign In</a>
      <?php endif;?>
    </div>
  </div>
</nav>
<?php if($fl):?>
<div class="flash-wrap"><?php foreach($fl as[$t,$m]):?><div class="flash flash-<?=h($t)?>"><?=h($m)?></div><?php endforeach;?></div>
<?php endif;?>
<?php
}

function sidenav(string $active=''): void {
    $u=user(); if(!$u) return;
    $p=PLANS[$u['plan']]??PLANS['free'];
    ?>
<div class="sidenav">
  <div class="sidenav-user">
    <div style="font-weight:700;font-size:.9rem"><?=h($u['name'])?></div>
    <div style="font-size:.75rem;color:var(--text3);margin-top:2px"><?=h($u['email'])?></div>
    <div style="margin-top:8px"><?=plan_badge($u['plan'])?></div>
  </div>
  <a href="<?=url()?>" class="sidenav-link <?=$active==='home'?'active':''?>">📊 Dashboard</a>
  <a href="<?=url('projects')?>" class="sidenav-link <?=$active==='projects'?'active':''?>">📁 Projects</a>
  <a href="<?=url('billing')?>" class="sidenav-link <?=$active==='billing'?'active':''?>">💳 Billing</a>
  <a href="<?=url('settings')?>" class="sidenav-link <?=$active==='settings'?'active':''?>">⚙️ Settings</a>
  <?php if($u['is_admin']):?>
  <a href="<?=url('admin')?>" class="sidenav-link <?=$active==='admin'?'active':''?>" style="color:#d97706;font-weight:700">🛡️ Admin Panel</a>
  <?php else:?>
  <div class="sidenav-link locked">🔒 Admin Panel <span style="font-size:.67rem;color:#cbd5e1">(admin only)</span></div>
  <?php endif;?>
</div>
<?php
}

function page_close(): void {?>
<footer class="footer">
  <div style="color:#a5b4fc;font-size:1rem;font-weight:800;margin-bottom:4px">⚡ AccountHub</div>
  <div>Manage. Scale. Grow. · Trusted by 10,000+ developers</div>
  <div style="margin-top:6px;font-size:.67rem">🔒 Authorized Cybersecurity Training Lab · For Educational Use Only</div>
</footer>
</body></html>
<?php
}
