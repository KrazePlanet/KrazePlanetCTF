<?php
function page_open(string $title, string $extra=''): void {
    $u=user();
    $fl=$_SESSION['flash']??[]; unset($_SESSION['flash']);
    $pending=0;
    if($u&&$u['is_admin']) {
        $st=db()->prepare("SELECT COUNT(*) FROM claims WHERE status='submitted'");
        $st->execute(); $pending=(int)$st->fetchColumn();
    }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title)?> — ClaimDesk</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f1f5f9;--bg2:#fff;--bg3:#eff6ff;--card:#fff;--border:#e2e8f0;
  --p:#2563eb;--p2:#1d4ed8;--p3:#bfdbfe;
  --text:#0f172a;--text2:#334155;--text3:#94a3b8;
  --green:#16a34a;--red:#dc2626;--amber:#d97706;--blue:#2563eb;
  --nav:#0f172a;--nav2:#1e293b;
  --radius:10px;--shadow:0 1px 8px rgba(15,23,42,.08);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:15px;display:flex;flex-direction:column}
a{color:var(--p);text-decoration:none}a:hover{color:var(--p2);text-decoration:underline}
/* NAV */
.nav{background:var(--nav);padding:0 28px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.25)}
.nav-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:16px;height:58px}
.logo{display:flex;align-items:center;gap:8px;text-decoration:none!important}
.logo-icon{width:32px;height:32px;background:var(--p);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:900;color:#fff}
.logo-text{font-size:1.05rem;font-weight:800;color:#fff;letter-spacing:-.3px}
.logo-text span{color:#93c5fd}
.nav-sep{height:24px;width:1px;background:rgba(255,255,255,.12);margin:0 4px}
.nav-app{font-size:.72rem;color:#64748b;font-weight:600;letter-spacing:.3px;text-transform:uppercase}
.nav-links{display:flex;gap:2px;flex:1;margin-left:20px}
.nav-link{color:#94a3b8;font-size:.83rem;font-weight:500;padding:6px 12px;border-radius:7px;transition:.15s;text-decoration:none!important;display:flex;align-items:center;gap:5px}
.nav-link:hover,.nav-link.active{background:rgba(255,255,255,.08);color:#e2e8f0;text-decoration:none}
.nav-actions{display:flex;align-items:center;gap:8px;margin-left:auto}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:600;cursor:pointer;border:none;border-radius:8px;padding:8px 16px;transition:.18s;font-size:.84rem;font-family:inherit;text-decoration:none!important}
.btn:hover{text-decoration:none!important}
.btn-primary{background:var(--p);color:#fff}
.btn-primary:hover{background:var(--p2);transform:translateY(-1px);box-shadow:0 4px 14px rgba(37,99,235,.35)}
.btn-secondary{background:#fff;color:var(--text2);border:1px solid var(--border)}
.btn-secondary:hover{border-color:var(--p);color:var(--p);background:var(--bg3)}
.btn-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.btn-ghost{background:rgba(255,255,255,.08);color:#e2e8f0;border:1px solid rgba(255,255,255,.12)}
.btn-ghost:hover{background:rgba(255,255,255,.15)}
.btn-success{background:#dcfce7;color:#14532d;border:1px solid #86efac}
.btn-sm{padding:5px 12px;font-size:.78rem;border-radius:6px}
.btn-lg{padding:11px 24px;font-size:.925rem}
/* FLASH */
.flash-wrap{max-width:1200px;margin:14px auto 0;padding:0 28px}
.flash{padding:10px 16px;border-radius:8px;font-size:.84rem;font-weight:500;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.flash-success{background:#dcfce7;border:1px solid #86efac;color:#14532d}
.flash-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
.flash-info{background:#eff6ff;border:1px solid #93c5fd;color:#1e40af}
.flash-warning{background:#fef3c7;border:1px solid #fcd34d;color:#92400e}
/* LAYOUT */
.app{max-width:1200px;margin:0 auto;padding:28px 28px 60px;flex:1}
/* SIDEBAR LAYOUT */
.page-with-sidebar{display:grid;grid-template-columns:240px 1fr;gap:24px;align-items:start}
.sidebar{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);padding:8px}
.sidebar-section{font-size:.68rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.7px;padding:10px 12px 4px}
.sidebar-link{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:7px;font-size:.84rem;font-weight:500;color:var(--text2);text-decoration:none!important;transition:.15s}
.sidebar-link:hover{background:var(--bg3);color:var(--p);text-decoration:none}
.sidebar-link.active{background:var(--bg3);color:var(--p);font-weight:600}
.sidebar-badge{background:var(--p);color:#fff;font-size:.6rem;font-weight:700;padding:1px 6px;border-radius:10px;min-width:18px;text-align:center}
/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
.card-pad{padding:24px}
.card-header{padding:16px 20px;border-bottom:1px solid var(--border);font-weight:700;display:flex;align-items:center;justify-content:space-between}
/* STAT CARDS */
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);padding:20px}
.stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-bottom:12px}
.stat-val{font-size:1.6rem;font-weight:800;letter-spacing:-.5px}
.stat-lbl{font-size:.75rem;color:var(--text3);margin-top:3px;font-weight:500}
/* FORMS */
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:18px}
.form-label{font-size:.75rem;font-weight:700;color:var(--text2);letter-spacing:.2px}
.form-hint{font-size:.72rem;color:var(--text3);margin-top:3px}
.form-control{background:#fff;border:1.5px solid var(--border);color:var(--text);border-radius:8px;padding:9px 13px;transition:.15s;outline:none;width:100%;font-family:inherit;font-size:.875rem}
.form-control:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
/* TABLE */
.table{width:100%;border-collapse:collapse}
.table th{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);padding:10px 16px;border-bottom:2px solid var(--border);text-align:left;white-space:nowrap}
.table td{padding:12px 16px;border-bottom:1px solid #f8fafc;font-size:.875rem;vertical-align:middle}
.table tr:last-child td{border-bottom:none}
.table tr:hover td{background:#fafafa}
/* TAGS */
.tag{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:5px;font-size:.7rem;font-weight:700;letter-spacing:.2px}
.tag-success{background:#dcfce7;color:#14532d}
.tag-danger{background:#fee2e2;color:#991b1b}
.tag-info{background:#eff6ff;color:#1e40af}
.tag-warning{background:#fef3c7;color:#92400e}
.tag-gray{background:#f1f5f9;color:#475569}
/* PAGE TITLE */
.page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px}
.page-title{font-size:1.3rem;font-weight:800;letter-spacing:-.4px}
.page-sub{font-size:.84rem;color:var(--text3);margin-top:4px}
/* CLAIM DETAIL */
.claim-field{padding:12px 0;border-bottom:1px solid #f1f5f9;display:flex;gap:16px}
.claim-field:last-child{border-bottom:none}
.claim-label{font-size:.75rem;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.4px;width:140px;flex-shrink:0;padding-top:1px}
.claim-value{font-size:.875rem;color:var(--text);flex:1}
/* PROGRESS STEPS */
.steps{display:flex;align-items:center;gap:0;margin-bottom:28px}
.step{display:flex;align-items:center;gap:8px;flex:1}
.step-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0}
.step-done{background:var(--p);color:#fff}
.step-active{background:#fff;border:2px solid var(--p);color:var(--p)}
.step-pending{background:#f1f5f9;border:2px solid #e2e8f0;color:var(--text3)}
.step-label{font-size:.78rem;font-weight:600;color:var(--text3)}
.step-label.active{color:var(--text)}
.step-line{flex:1;height:2px;background:var(--border);margin:0 6px}
.step-line.done{background:var(--p)}
/* FLAG */
.flag-box{background:linear-gradient(135deg,#064e3b,#065f46);border:2px solid #059669;border-radius:12px;padding:24px 28px;margin-bottom:24px}
.flag-box h3{color:#34d399;font-size:1rem;font-weight:700;margin-bottom:10px}
.flag-val{font-family:monospace;font-size:1.25rem;font-weight:800;color:#6ee7b7;background:#052e1c;border:1px solid #065f46;padding:8px 18px;border-radius:7px;display:inline-block;margin-top:6px;letter-spacing:2px}
/* AMOUNT */
.amount-big{font-size:2rem;font-weight:900;color:var(--text);letter-spacing:-.5px}
/* FOOTER */
.footer{background:var(--nav);padding:20px 28px;text-align:center;color:#475569;font-size:.78rem}
@media(max-width:800px){.page-with-sidebar{grid-template-columns:1fr}.stat-grid{grid-template-columns:repeat(2,1fr)}.form-row{grid-template-columns:1fr}}
</style>
<?=$extra?>
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="<?=url()?>" class="logo">
      <div class="logo-icon">CD</div>
      <div class="logo-text">Claim<span>Desk</span></div>
    </a>
    <div class="nav-sep"></div>
    <div class="nav-app">Expense Portal</div>
    <?php if($u):?>
    <div class="nav-links">
      <a href="<?=url()?>" class="nav-link">🏠 Home</a>
      <a href="<?=url('claims/new')?>" class="nav-link">➕ New Claim</a>
      <a href="<?=url('claims')?>" class="nav-link">📋 My Claims</a>
      <?php if($u['is_admin']):?>
      <a href="<?=url('admin')?>" class="nav-link">
        ⚙️ Admin
        <?php if($pending):?><span class="sidebar-badge" style="font-size:.58rem"><?=$pending?></span><?php endif;?>
      </a>
      <?php endif;?>
    </div>
    <?php endif;?>
    <div class="nav-actions">
      <?php if($u):?>
        <div style="display:flex;align-items:center;gap:8px;padding:5px 10px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:7px">
          <div style="width:26px;height:26px;background:var(--p);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#fff"><?=strtoupper(substr($u['name'],0,1))?></div>
          <div>
            <div style="font-size:.78rem;font-weight:600;color:#f1f5f9"><?=h(explode(' ',$u['name'])[0])?></div>
            <div style="font-size:.62rem;color:#64748b"><?=h($u['department'])?></div>
          </div>
        </div>
        <form method="POST" action="<?=url('logout')?>" style="margin:0">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <button class="btn btn-ghost btn-sm">Sign Out</button>
        </form>
      <?php else:?>
        <a href="<?=url('login')?>" class="btn btn-ghost btn-sm">Sign In</a>
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
  <div>© 2025 ClaimDesk · Expense Management Portal · Powered by ClaimDesk Inc.</div>
  <div style="margin-top:6px;font-size:.68rem">🔒 Authorized Cybersecurity Training Lab · For Educational Use Only</div>
</footer>
</body></html>
<?php
}

function claim_row(array $c, bool $show_user=false): void {
    $sc=status_tag($c['status']); $si=status_icon($c['status']);
    echo '<tr>';
    echo '<td style="font-weight:700;font-size:.8rem;color:var(--text3);font-family:monospace">'.h(substr($c['claim_ref'],0,8)).'…</td>';
    if($show_user) echo '<td>'.h($c['uname']??'').'</td>';
    echo '<td>'.category_icon($c['category']).' '.h($c['category']).'</td>';
    echo '<td style="font-weight:700">'.inr((int)$c['amount']).'</td>';
    echo '<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'.h($c['description']).'</td>';
    echo '<td><span class="tag '.$sc.'">'.$si.' '.ucfirst($c['status']).'</span></td>';
    echo '<td style="font-size:.75rem;color:var(--text3)">'.date('d M Y',strtotime($c['submitted_at'])).'</td>';
    echo '<td><a href="'.url('claims/'.$c['id']).'" class="btn btn-secondary btn-sm">View</a></td>';
    echo '</tr>';
}
