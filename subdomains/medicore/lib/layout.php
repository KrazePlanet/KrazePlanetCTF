<?php
function page_open(string $title, string $extra=''): void {
    $u=user();
    $fl=$_SESSION['flash']??[]; unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title)?> — MediCore</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f0fdf8;--bg2:#fff;--bg3:#e6f7f3;--card:#fff;--border:#b2dfdb;
  --p:#0d9488;--p2:#0f766e;--p3:#ccfbf1;
  --text:#0f2421;--text2:#134e4a;--text3:#5eead4;
  --green:#059669;--red:#dc2626;--amber:#d97706;--blue:#2563eb;
  --nav:#0f2421;
  --radius:12px;--shadow:0 2px 12px rgba(13,148,136,.08);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:15px}
a{color:var(--p);text-decoration:none}a:hover{color:var(--p2)}
/* NAV */
.nav{background:var(--nav);border-bottom:1px solid rgba(255,255,255,.06);padding:0 28px;position:sticky;top:0;z-index:100;box-shadow:0 2px 16px rgba(0,0,0,.25)}
.nav-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;height:58px;gap:20px}
.logo{font-size:1.25rem;font-weight:800;color:#fff;letter-spacing:-.4px;display:flex;align-items:center;gap:8px}
.logo span{color:#2dd4bf}
.nav-links{display:flex;gap:4px;flex:1;margin-left:12px}
.nav-link{color:#94a3b8;font-size:.83rem;font-weight:500;padding:6px 11px;border-radius:7px;transition:.15s}
.nav-link:hover{background:rgba(255,255,255,.07);color:#e2e8f0}
.nav-actions{display:flex;align-items:center;gap:8px;margin-left:auto}
/* BUTTONS */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:600;cursor:pointer;border:none;border-radius:9px;padding:8px 18px;transition:.18s;font-size:.84rem;font-family:inherit;text-decoration:none!important}
.btn-primary{background:var(--p);color:#fff}.btn-primary:hover{background:var(--p2);transform:translateY(-1px);box-shadow:0 4px 14px rgba(13,148,136,.3)}
.btn-secondary{background:rgba(255,255,255,.06);color:#e2e8f0;border:1px solid rgba(255,255,255,.12)}.btn-secondary:hover{background:rgba(255,255,255,.12)}
.btn-outline{background:transparent;border:1.5px solid var(--p);color:var(--p)}.btn-outline:hover{background:var(--bg3)}
.btn-light{background:#fff;color:var(--text);border:1px solid var(--border)}.btn-light:hover{border-color:var(--p);color:var(--p)}
.btn-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.btn-sm{padding:5px 12px;font-size:.78rem;border-radius:7px}
.btn-lg{padding:11px 24px;font-size:.95rem}
/* FLASH */
.flash-wrap{max-width:1100px;margin:14px auto 0;padding:0 20px}
.flash{padding:11px 16px;border-radius:9px;font-size:.84rem;font-weight:500;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.flash-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46}
.flash-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
.flash-info{background:#e0f2fe;border:1px solid #7dd3fc;color:#0c4a6e}
/* LAYOUT */
.wrap{max-width:1100px;margin:0 auto;padding:28px 20px 60px}
.two-col{display:grid;grid-template-columns:260px 1fr;gap:28px;align-items:start}
/* SIDEBAR NAV */
.sidenav{background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;position:sticky;top:76px}
.sidenav-header{background:linear-gradient(135deg,var(--p),#14b8a6);padding:20px;color:#fff}
.sidenav-name{font-weight:700;font-size:.95rem}
.sidenav-role{font-size:.75rem;opacity:.8;margin-top:3px}
.sidenav-item{display:flex;align-items:center;gap:10px;padding:11px 18px;font-size:.85rem;font-weight:500;color:var(--text2);border-bottom:1px solid #f0fdf9;transition:.15s}
.sidenav-item:last-child{border-bottom:none}
.sidenav-item:hover{background:var(--bg3);color:var(--p)}
.sidenav-item.active{background:var(--bg3);color:var(--p);font-weight:700}
/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
.card-pad{padding:22px}
/* STAT GRID */
.stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
.stat-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:18px;box-shadow:var(--shadow)}
.stat-icon{font-size:1.6rem;margin-bottom:8px}
.stat-val{font-size:1.6rem;font-weight:800;color:var(--p)}
.stat-lbl{font-size:.72rem;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-top:2px}
/* REPORT CARD */
.report-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:14px;overflow:hidden}
.report-header{padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--bg3)}
.report-type{font-weight:700;font-size:.9rem}
.report-date{font-size:.75rem;color:var(--text3)}
.report-body{padding:14px 20px}
.report-summary{font-size:.85rem;color:var(--text2);line-height:1.6}
/* FORMS */
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:16px}
.form-label{font-size:.74rem;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.4px}
.form-control{background:#fafafa;border:1.5px solid var(--border);color:var(--text);border-radius:8px;padding:10px 14px;transition:.15s;outline:none;width:100%;font-family:inherit;font-size:.9rem}
.form-control:focus{border-color:var(--p);background:#fff;box-shadow:0 0 0 3px rgba(13,148,136,.1)}
/* BADGE */
.badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:5px;font-size:.7rem;font-weight:700}
.badge-green{background:#d1fae5;color:#065f46}
.badge-blue{background:#dbeafe;color:#1e40af}
.badge-amber{background:#fef3c7;color:#92400e}
.badge-red{background:#fee2e2;color:#991b1b}
.badge-teal{background:var(--p3);color:var(--p2)}
/* PAGE TITLE */
.page-title{font-size:1.3rem;font-weight:800;letter-spacing:-.3px;margin-bottom:4px}
.page-subtitle{font-size:.84rem;color:var(--text3);margin-bottom:24px}
/* TABLE */
.table{width:100%;border-collapse:collapse;font-size:.84rem}
.table th{text-align:left;padding:10px 14px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--text3);background:var(--bg3);border-bottom:1px solid var(--border)}
.table td{padding:11px 14px;border-bottom:1px solid #f0fdf8;vertical-align:middle}
.table tr:last-child td{border-bottom:none}
/* FLAG */
.flag-box{background:linear-gradient(135deg,#064e3b,#065f46);border:2px solid #059669;border-radius:12px;padding:24px 28px;margin-bottom:20px}
.flag-box h3{color:#34d399;font-size:1rem;font-weight:700;margin-bottom:10px}
.flag-val{font-family:monospace;font-size:1.2rem;font-weight:800;color:#6ee7b7;background:#052e1c;border:1px solid #065f46;padding:8px 18px;border-radius:7px;display:inline-block;margin-top:6px;letter-spacing:2px}
/* REPORT DETAIL */
.result-pre{background:var(--bg3);border:1px solid var(--border);border-radius:9px;padding:18px;font-family:monospace;font-size:.84rem;line-height:1.7;white-space:pre-wrap;color:var(--text2)}
/* FOOTER */
.footer{background:var(--nav);padding:20px;text-align:center;color:#4b5563;font-size:.78rem}
@media(max-width:800px){.two-col{grid-template-columns:1fr}.stat-grid{grid-template-columns:repeat(2,1fr)}}
</style>
<?=$extra?>
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="<?=url()?>" class="logo">🏥 <span>Medi</span>Core</a>
    <?php if($u):?>
    <div class="nav-links">
      <a href="<?=url()?>" class="nav-link">📊 Dashboard</a>
      <a href="<?=url('reports')?>" class="nav-link">🧪 My Reports</a>
      <a href="<?=url('appointments')?>" class="nav-link">📅 Appointments</a>
      <?php if(is_doctor()):?>
      <a href="<?=url('doctor/patients')?>" class="nav-link" style="color:#f59e0b">👨‍⚕️ Patients</a>
      <?php endif;?>
    </div>
    <?php endif;?>
    <div class="nav-actions">
      <?php if($u):?>
        <span style="color:#94a3b8;font-size:.8rem">Hi, <?=h(explode(' ',$u['name'])[0])?></span>
        <form method="POST" action="<?=url('logout')?>" style="margin:0">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <button class="btn btn-secondary btn-sm">Sign Out</button>
        </form>
      <?php else:?>
        <a href="<?=url('login')?>" class="btn btn-primary btn-sm">Patient Login</a>
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
  <div style="color:#2dd4bf;font-size:1.1rem;font-weight:800;margin-bottom:6px">🏥 MediCore</div>
  <div>Your Health, Simplified · Trusted by 2 Lakh+ Patients</div>
  <div style="margin-top:8px;font-size:.68rem">🔒 Authorized Cybersecurity Training Lab · For Educational Use Only</div>
</footer>
</body></html>
<?php
}

function sidenav(string $active=''): void {
    $u=user(); if(!$u) return;
    ?>
<div class="sidenav">
  <div class="sidenav-header">
    <div style="font-size:2rem;margin-bottom:8px">👤</div>
    <div class="sidenav-name"><?=h($u['name'])?></div>
    <div class="sidenav-role"><?=ucfirst(h($u['role']))?> · ID: P<?=str_pad($u['id'],4,'0',STR_PAD_LEFT)?></div>
    <?php if($u['blood_group']):?>
    <div style="margin-top:8px;font-size:.75rem;background:rgba(255,255,255,.15);border-radius:5px;padding:4px 10px;display:inline-block">🩸 <?=h($u['blood_group'])?></div>
    <?php endif;?>
  </div>
  <a href="<?=url()?>" class="sidenav-item <?=$active==='home'?'active':''?>">📊 Dashboard</a>
  <a href="<?=url('reports')?>" class="sidenav-item <?=$active==='reports'?'active':''?>">🧪 Lab Reports</a>
  <a href="<?=url('appointments')?>" class="sidenav-item <?=$active==='appointments'?'active':''?>">📅 Appointments</a>
  <a href="<?=url('profile')?>" class="sidenav-item <?=$active==='profile'?'active':''?>">👤 My Profile</a>
  <?php if(is_doctor()):?>
  <a href="<?=url('doctor/patients')?>" class="sidenav-item <?=$active==='patients'?'active':''?>" style="color:#f59e0b">👨‍⚕️ All Patients</a>
  <?php endif;?>
</div>
<?php
}
