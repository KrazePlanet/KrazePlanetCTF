<?php
function page_open(string $title, string $extra = ''): void {
    $u = user();
    $fl = $_SESSION['flash'] ?? []; unset($_SESSION['flash']);
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?> — SwiftPay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f5f4ff;--bg2:#ffffff;--bg3:#f0eeff;
  --card:#ffffff;--border:#e4e0ff;
  --p:#6c3de8;--p2:#8b5cf6;--p3:#a78bfa;
  --green:#059669;--red:#dc2626;--amber:#d97706;
  --text:#1e1b4b;--text2:#4338ca;--text3:#6b7280;
  --radius:14px;--shadow:0 2px 16px rgba(108,61,232,.1);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:15px}
a{color:var(--p2);text-decoration:none}a:hover{color:var(--p)}
/* NAV */
.nav{background:var(--p);padding:0 24px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(108,61,232,.3)}
.nav-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;gap:16px;height:58px}
.logo{font-size:1.3rem;font-weight:900;color:#fff;letter-spacing:-.5px}
.logo span{color:#c4b5fd}
.logo-tag{font-size:.6rem;background:rgba(255,255,255,.15);color:#e9d5ff;padding:2px 7px;border-radius:4px;margin-left:6px;font-weight:700;vertical-align:middle}
.nav-links{display:flex;gap:4px;flex:1}
.nav-links a{color:rgba(255,255,255,.8);font-size:.84rem;font-weight:500;padding:6px 12px;border-radius:7px;transition:.15s}
.nav-links a:hover{background:rgba(255,255,255,.15);color:#fff}
.nav-actions{display:flex;align-items:center;gap:10px}
.nav-bal{background:rgba(255,255,255,.15);color:#fff;border-radius:8px;padding:6px 13px;font-size:.82rem;font-weight:700}
.nav-btn{background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.25);border-radius:8px;padding:6px 13px;font-size:.82rem;font-weight:600;cursor:pointer;transition:.15s}
.nav-btn:hover{background:rgba(255,255,255,.28)}
/* FLASH */
.flash-wrap{max-width:1100px;margin:16px auto 0;padding:0 24px}
.flash{padding:12px 16px;border-radius:10px;font-size:.875rem;font-weight:500;margin-bottom:8px;display:flex;align-items:center;gap:10px}
.flash-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46}
.flash-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
.flash-info{background:#ede9fe;border:1px solid #c4b5fd;color:#4c1d95}
.flash-warning{background:#fef3c7;border:1px solid #fcd34d;color:#92400e}
/* MAIN */
.main{max-width:1100px;margin:0 auto;padding:28px 24px 60px}
/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
.card-pad{padding:24px}
/* BUTTONS */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;font-weight:600;cursor:pointer;border:none;border-radius:10px;padding:10px 20px;transition:.18s;font-size:.875rem;font-family:inherit}
.btn-primary{background:var(--p);color:#fff}
.btn-primary:hover{background:var(--p2);transform:translateY(-1px);box-shadow:0 4px 16px rgba(108,61,232,.3)}
.btn-secondary{background:var(--bg3);color:var(--p);border:1px solid var(--border)}
.btn-secondary:hover{border-color:var(--p);background:#ede9fe}
.btn-danger{background:#fef2f2;color:var(--red);border:1px solid #fecaca}
.btn-danger:hover{background:#fee2e2}
.btn-sm{padding:7px 14px;font-size:.8rem;border-radius:8px}
.btn-lg{padding:13px 28px;font-size:1rem}
/* FORM */
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:16px}
.form-label{font-size:.78rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px}
.form-control{background:#fafafa;border:1.5px solid var(--border);color:var(--text);border-radius:9px;padding:10px 14px;transition:.15s;outline:none;width:100%;font-family:inherit;font-size:.925rem}
.form-control:focus{border-color:var(--p);background:#fff;box-shadow:0 0 0 3px rgba(108,61,232,.1)}
/* HERO BALANCE CARD */
.balance-hero{background:linear-gradient(135deg,var(--p) 0%,#7c3aed 50%,#4f46e5 100%);border-radius:20px;padding:32px;color:#fff;position:relative;overflow:hidden;margin-bottom:24px}
.balance-hero::before{content:'';position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:rgba(255,255,255,.07);border-radius:50%}
.balance-hero::after{content:'';position:absolute;bottom:-60px;left:-20px;width:160px;height:160px;background:rgba(255,255,255,.05);border-radius:50%}
.balance-label{font-size:.78rem;font-weight:600;opacity:.75;text-transform:uppercase;letter-spacing:.8px}
.balance-amount{font-size:2.8rem;font-weight:900;letter-spacing:-1px;margin:6px 0}
.balance-upi{font-size:.82rem;opacity:.7;font-family:monospace}
.balance-chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:16px}
.balance-chip{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);border-radius:20px;padding:5px 14px;font-size:.78rem;font-weight:600;backdrop-filter:blur(4px)}
/* QUICK ACTIONS */
.quick-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}
.qa-btn{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px 12px;text-align:center;cursor:pointer;transition:.2s;text-decoration:none;display:block;color:var(--text)}
.qa-btn:hover{border-color:var(--p);box-shadow:0 4px 16px rgba(108,61,232,.12);transform:translateY(-2px)}
.qa-icon{font-size:1.6rem;margin-bottom:6px}
.qa-label{font-size:.78rem;font-weight:600;color:var(--text3)}
/* TRANSACTION LIST */
.tx-item{display:flex;align-items:center;gap:14px;padding:14px 0;border-bottom:1px solid #f3f4f6}
.tx-item:last-child{border-bottom:none}
.tx-icon{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.tx-icon-credit{background:#d1fae5;color:#059669}
.tx-icon-debit{background:#fee2e2;color:#dc2626}
.tx-info{flex:1}
.tx-desc{font-weight:600;font-size:.875rem}
.tx-meta{font-size:.75rem;color:var(--text3);margin-top:2px}
.tx-amount{font-weight:700;font-size:.95rem}
.tx-credit{color:var(--green)}
.tx-debit{color:var(--red)}
/* TABLES */
.table{width:100%;border-collapse:collapse}
.table th{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);padding:10px 14px;border-bottom:2px solid var(--border);text-align:left}
.table td{padding:12px 14px;border-bottom:1px solid #f9fafb;font-size:.875rem;vertical-align:middle}
/* MISC */
.page-title{font-size:1.4rem;font-weight:800;color:var(--text);letter-spacing:-.3px}
.section-sub{font-size:.875rem;color:var(--text3);margin-top:3px}
.tag{display:inline-block;padding:3px 9px;border-radius:5px;font-size:.72rem;font-weight:700;letter-spacing:.3px}
.tag-success{background:#d1fae5;color:#065f46}
.tag-danger{background:#fee2e2;color:#991b1b}
.tag-info{background:#ede9fe;color:#4c1d95}
.tag-warning{background:#fef3c7;color:#92400e}
.divider{border:none;border-top:1px solid var(--border);margin:20px 0}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.mt-16{margin-top:16px}.mt-24{margin-top:24px}
.flag-box{background:linear-gradient(135deg,#064e3b,#065f46);border:2px solid #059669;border-radius:14px;padding:24px 28px;margin-bottom:24px}
.flag-box h3{color:#34d399;font-size:1rem;font-weight:700;margin-bottom:10px;display:flex;align-items:center;gap:8px}
.flag-val{font-family:monospace;font-size:1.35rem;font-weight:800;color:#6ee7b7;background:#052e1c;border:1px solid #065f46;padding:8px 18px;border-radius:7px;display:inline-block;margin-top:6px;letter-spacing:2px}
/* FOOTER */
.footer{background:#fff;border-top:1px solid var(--border);padding:24px;text-align:center;color:var(--text3);font-size:.8rem;margin-top:auto}
@media(max-width:640px){
  .quick-actions{grid-template-columns:repeat(2,1fr)}
  .grid-2{grid-template-columns:1fr}
  .nav-links{display:none}
}
</style>
<?= $extra ?>
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <div class="logo">Swift<span>Pay</span><span class="logo-tag">UPI</span></div>
    <?php if ($u): ?>
    <div class="nav-links">
      <a href="<?= url() ?>">Home</a>
      <a href="<?= url('transfer') ?>">Send Money</a>
      <a href="<?= url('topup') ?>">Add Money</a>
      <a href="<?= url('recharge') ?>">Recharge</a>
      <a href="<?= url('history') ?>">History</a>
      <?php if ($u['is_admin']): ?><a href="<?= url('admin') ?>">Admin</a><?php endif; ?>
    </div>
    <div class="nav-actions">
      <span class="nav-bal">💰 <?= inr((int)$u['balance']) ?></span>
      <span class="nav-bal" style="background:rgba(255,255,255,.1)">👤 <?= h(explode(' ',$u['name'])[0]) ?></span>
      <form method="POST" action="<?= url('logout') ?>" style="margin:0">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <button class="nav-btn">Sign Out</button>
      </form>
    </div>
    <?php else: ?>
    <div style="flex:1"></div>
    <div class="nav-actions">
      <a href="<?= url('login') ?>" class="nav-btn">Sign In</a>
      <a href="<?= url('register') ?>" class="btn btn-sm" style="background:#fff;color:var(--p);border-radius:8px;font-weight:700">Register</a>
    </div>
    <?php endif; ?>
  </div>
</nav>
<?php if ($fl): ?>
<div class="flash-wrap">
  <?php foreach ($fl as [$t,$m]): ?>
  <div class="flash flash-<?= h($t) ?>"><?= h($m) ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php
}

function page_close(): void { ?>
<footer class="footer">
  <div>SwiftPay — India's Fastest Digital Wallet &nbsp;·&nbsp; UPI · IMPS · NEFT · RTGS</div>
  <div style="margin-top:6px;font-size:.72rem;color:#9ca3af">🔒 256-bit SSL Secured &nbsp;·&nbsp; RBI Registered Payment Platform &nbsp;·&nbsp; NPCI Certified</div>
</footer>
</body>
</html>
<?php
}
