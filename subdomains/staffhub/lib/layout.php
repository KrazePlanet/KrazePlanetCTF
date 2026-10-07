<?php
function page_open(string $title, string $extra=''): void {
    $u=user();
    $fl=$_SESSION['flash']??[]; unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title)?> — StaffHub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f8fafc;--bg2:#fff;--bg3:#f1f5f9;--card:#fff;--border:#e2e8f0;
  --p:#1e40af;--p2:#1d4ed8;--p3:#dbeafe;
  --text:#0f172a;--text2:#1e293b;--text3:#64748b;
  --green:#059669;--red:#dc2626;--amber:#d97706;
  --nav:#0f172a;
  --radius:10px;--shadow:0 1px 8px rgba(0,0,0,.06);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:15px}
a{color:var(--p);text-decoration:none}a:hover{color:var(--p2)}
/* NAV */
.nav{background:var(--nav);padding:0 28px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.2)}
.nav-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;height:56px;gap:20px}
.logo{font-size:1.15rem;font-weight:800;color:#fff;letter-spacing:-.3px;display:flex;align-items:center;gap:7px}
.logo span{color:#60a5fa}
.nav-links{display:flex;gap:2px;flex:1;margin-left:8px}
.nav-link{color:#94a3b8;font-size:.82rem;font-weight:500;padding:6px 11px;border-radius:6px;transition:.15s}
.nav-link:hover{background:rgba(255,255,255,.07);color:#e2e8f0}
.nav-link.hr-only{color:#fbbf24}
.nav-actions{display:flex;align-items:center;gap:8px;margin-left:auto}
/* LAYOUT */
.shell{display:flex;min-height:calc(100vh - 56px)}
.sidebar{width:230px;background:#fff;border-right:1px solid var(--border);padding:20px 0;flex-shrink:0;position:sticky;top:56px;height:calc(100vh - 56px);overflow-y:auto}
.sidebar-section{padding:10px 16px 4px;font-size:.66rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.6px}
.sidebar-link{display:flex;align-items:center;gap:9px;padding:9px 20px;font-size:.84rem;font-weight:500;color:var(--text2);transition:.15s}
.sidebar-link:hover{background:var(--bg3);color:var(--p)}
.sidebar-link.active{background:var(--p3);color:var(--p);font-weight:700}
.sidebar-link.locked{color:#94a3b8;cursor:default}
.main{flex:1;padding:28px;max-width:960px}
/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
.card-pad{padding:22px}
/* STAT GRID */
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px}
.stat-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:16px;box-shadow:var(--shadow)}
.stat-num{font-size:1.5rem;font-weight:800;color:var(--p);margin-bottom:4px}
.stat-lbl{font-size:.72rem;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:.4px}
/* BUTTONS */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:600;cursor:pointer;border:none;border-radius:8px;padding:8px 18px;transition:.18s;font-size:.84rem;font-family:inherit;text-decoration:none!important}
.btn-primary{background:var(--p);color:#fff}.btn-primary:hover{background:var(--p2)}
.btn-secondary{background:rgba(255,255,255,.06);color:#e2e8f0;border:1px solid rgba(255,255,255,.12)}.btn-secondary:hover{background:rgba(255,255,255,.12)}
.btn-outline{background:transparent;border:1.5px solid var(--p);color:var(--p)}.btn-outline:hover{background:var(--p3)}
.btn-light{background:#fff;color:var(--text);border:1px solid var(--border)}.btn-light:hover{border-color:var(--p)}
.btn-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.btn-sm{padding:5px 12px;font-size:.78rem;border-radius:6px}
.btn-lg{padding:11px 24px;font-size:.95rem}
/* FLASH */
.flash-wrap{max-width:1200px;margin:12px auto 0;padding:0 20px}
.flash{padding:10px 16px;border-radius:8px;font-size:.84rem;font-weight:500;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.flash-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46}
.flash-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
.flash-info{background:#dbeafe;border:1px solid #93c5fd;color:#1e3a8a}
/* PAGE HEADER */
.page-header{margin-bottom:24px}
.page-title{font-size:1.2rem;font-weight:800;letter-spacing:-.3px}
.page-subtitle{font-size:.83rem;color:var(--text3);margin-top:3px}
/* BADGE */
.badge{display:inline-flex;align-items:center;padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}
.badge-blue{background:#dbeafe;color:#1e40af}
.badge-green{background:#d1fae5;color:#065f46}
.badge-amber{background:#fef3c7;color:#92400e}
.badge-red{background:#fee2e2;color:#991b1b}
.badge-gray{background:#f1f5f9;color:#475569}
/* TABLE */
.table{width:100%;border-collapse:collapse;font-size:.84rem}
.table th{text-align:left;padding:10px 14px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--text3);background:var(--bg3);border-bottom:2px solid var(--border)}
.table td{padding:11px 14px;border-bottom:1px solid var(--bg3)}
.table tbody tr:hover{background:#fafbfc}
/* FORMS */
.form-group{display:flex;flex-direction:column;gap:4px;margin-bottom:14px}
.form-label{font-size:.73rem;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.4px}
.form-control{background:#fafafa;border:1.5px solid var(--border);color:var(--text);border-radius:7px;padding:9px 13px;outline:none;width:100%;font-family:inherit;font-size:.9rem;transition:.15s}
.form-control:focus{border-color:var(--p);background:#fff;box-shadow:0 0 0 3px rgba(30,64,175,.1)}
/* PAYSLIP */
.payslip-row{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--bg3);font-size:.875rem}
.payslip-row:last-child{border-bottom:none}
.payslip-section{font-weight:700;padding:10px 0 6px;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text3)}
/* FLAG */
.flag-box{background:linear-gradient(135deg,#064e3b,#065f46);border:2px solid #059669;border-radius:12px;padding:24px 28px;margin-bottom:20px}
.flag-box h3{color:#34d399;font-size:1rem;font-weight:700;margin-bottom:10px}
.flag-val{font-family:monospace;font-size:1.2rem;font-weight:800;color:#6ee7b7;background:#052e1c;border:1px solid #065f46;padding:8px 18px;border-radius:7px;display:inline-block;margin-top:6px;letter-spacing:2px}
/* FOOTER */
.footer{background:var(--nav);padding:18px;text-align:center;color:#4b5563;font-size:.76rem}
@media(max-width:900px){.shell{flex-direction:column}.sidebar{width:100%;height:auto;position:static}.stat-grid{grid-template-columns:repeat(2,1fr)}}
</style>
<?=$extra?>
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="<?=url()?>" class="logo">💼 <span>Staff</span>Hub</a>
    <div class="nav-actions">
      <?php if($u=user()):?>
        <span style="color:#94a3b8;font-size:.8rem"><?=h($u['name'])?> · <span style="color:#60a5fa"><?=ucfirst(h($u['role']))?></span></span>
        <form method="POST" action="<?=url('logout')?>" style="margin:0">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <button class="btn btn-secondary btn-sm">Sign Out</button>
        </form>
      <?php else:?>
        <a href="<?=url('login')?>" class="btn btn-primary btn-sm">Employee Login</a>
      <?php endif;?>
    </div>
  </div>
</nav>
<?php if($fl):?>
<div class="flash-wrap"><?php foreach($fl as[$t,$m]):?><div class="flash flash-<?=h($t)?>"><?=h($m)?></div><?php endforeach;?></div>
<?php endif;?>
<?php
}

function sidebar(string $active=''): void {
    $u=user(); if(!$u) return;
    $is_hr=in_array($u['role'],['hr']);
    $is_mgr=in_array($u['role'],['hr','manager']);
    ?>
<aside class="sidebar">
  <div style="padding:16px 20px 12px;border-bottom:1px solid var(--border)">
    <div style="font-weight:700;font-size:.9rem"><?=h($u['name'])?></div>
    <div style="font-size:.75rem;color:var(--text3);margin-top:2px"><?=h($u['designation'])?></div>
    <div style="font-size:.72rem;color:var(--p);margin-top:2px"><?=h($u['employee_id'])?> · <?=h($u['department'])?></div>
  </div>

  <div class="sidebar-section">My Space</div>
  <a href="<?=url()?>" class="sidebar-link <?=$active==='home'?'active':''?>">📊 Dashboard</a>
  <a href="<?=url('payslips')?>" class="sidebar-link <?=$active==='payslips'?'active':''?>">💰 My Payslips</a>
  <a href="<?=url('leaves')?>" class="sidebar-link <?=$active==='leaves'?'active':''?>">📋 Leave Management</a>
  <a href="<?=url('directory')?>" class="sidebar-link <?=$active==='directory'?'active':''?>">👥 Employee Directory</a>

  <?php if($is_mgr):?>
  <div class="sidebar-section">Manager</div>
  <a href="<?=url('manager/leaves')?>" class="sidebar-link <?=$active==='mgr-leaves'?'active':''?>">✅ Leave Approvals</a>
  <?php endif;?>

  <?php if($is_hr):?>
  <div class="sidebar-section" style="color:#fbbf24">HR Admin</div>
  <a href="<?=url('hr/dashboard')?>" class="sidebar-link <?=$active==='hr'?'active':''?>" style="color:#f59e0b">📈 HR Dashboard</a>
  <a href="<?=url('hr/payroll/export')?>" class="sidebar-link <?=$active==='hr-export'?'active':''?>" style="color:#f59e0b">📤 Export Payroll</a>
  <?php else:?>
  <div class="sidebar-section" style="color:#94a3b8">HR Admin</div>
  <div class="sidebar-link locked">🔒 HR Dashboard <span style="font-size:.68rem;margin-left:4px;color:#cbd5e1">(HR only)</span></div>
  <div class="sidebar-link locked">🔒 Export Payroll <span style="font-size:.68rem;margin-left:4px;color:#cbd5e1">(HR only)</span></div>
  <?php endif;?>
</aside>
<?php
}

function page_close(): void {?>
<footer class="footer">
  <div style="color:#60a5fa;font-size:1rem;font-weight:800;margin-bottom:4px">💼 StaffHub</div>
  <div>Everything HR, One Place · Empowering 500+ Employees</div>
  <div style="margin-top:6px;font-size:.67rem">🔒 Authorized Cybersecurity Training Lab · For Educational Use Only</div>
</footer>
</body></html>
<?php
}
