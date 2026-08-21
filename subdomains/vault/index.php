<?php
function b64url(string $s): string {
    return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
}
$jh  = b64url('{"alg":"HS256","typ":"JWT"}');
$jp  = b64url('{"sub":"1","username":"admin","email":"admin@vaulttech.io","role":"super_admin","iat":1704067200,"exp":1767139200}');
$js  = b64url(hash_hmac('sha256', "{$jh}.{$jp}", 'v@ultT3ch!2024', true));
$JWT = "{$jh}.{$jp}.{$js}";

$page      = $_GET['page'] ?? 'home';
$logged_in = false;
$auth_err  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($page, ['admin', 'manage'], true)) {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if ($u === 'admin' && $p === 'admin') {
        $logged_in = true;
    } else {
        $auth_err = 'Invalid username or password.';
    }
}

if ($page === 'admin')       $title = 'Administration Console — VaultTech';
elseif ($page === 'manage')  $title = 'Operations Center — VaultTech';
else                         $title = 'VaultTech — Enterprise Payment Infrastructure';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?=htmlspecialchars($title)?></title>
<?php if ($page === 'home'): ?>
<script>window.__vtk_config={"version":"3.1.2","env":"production","features":{"fraud_detection":true,"instant_pay":true,"analytics":true},"_token":"<?=$JWT?>"};</script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{min-height:100vh;background:#fff;color:#111827;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;font-size:15px;}
a{text-decoration:none;color:inherit;}
.nav{position:sticky;top:0;z-index:100;background:rgba(255,255,255,.96);backdrop-filter:blur(8px);border-bottom:1px solid #e5e7eb;padding:0 5%;height:62px;display:flex;align-items:center;gap:12px;}
.nav-logo{display:flex;align-items:center;gap:8px;font-weight:800;font-size:1.05rem;color:#1e3a8a;}
.nav-logo svg{width:26px;height:26px;fill:#2563eb;}
.nav-links{display:flex;gap:22px;margin-left:28px;}
.nav-links a{font-size:.84rem;color:#6b7280;font-weight:500;transition:color .15s;}
.nav-links a:hover{color:#1e3a8a;}
.nav-spacer{flex:1;}
.nav-ctas{display:flex;gap:8px;}
.nav-ctas a{padding:7px 16px;border-radius:6px;font-size:.8rem;font-weight:600;display:inline-block;}
.btn-ghost{color:#374151;border:1px solid #d1d5db;background:#fff;}
.btn-ghost:hover{background:#f9fafb;}
.btn-primary{background:#2563eb;color:#fff;}
.btn-primary:hover{background:#1d4ed8;}
.hero{padding:80px 5% 56px;text-align:center;max-width:820px;margin:0 auto;}
.hero .tag{display:inline-block;background:#eff6ff;color:#2563eb;font-size:.7rem;font-weight:700;padding:4px 12px;border-radius:20px;border:1px solid #bfdbfe;margin-bottom:18px;text-transform:uppercase;letter-spacing:.5px;}
.hero h1{font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900;color:#111827;line-height:1.2;margin-bottom:14px;}
.hero h1 span{color:#2563eb;}
.hero p{font-size:.97rem;color:#6b7280;line-height:1.75;max-width:580px;margin:0 auto 26px;}
.hero-btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;}
.hero-btns a{padding:12px 28px;border-radius:8px;font-weight:700;font-size:.88rem;}
.hb-primary{background:#2563eb;color:#fff;}
.hb-primary:hover{background:#1d4ed8;}
.hb-secondary{background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;}
.hb-secondary:hover{background:#e5e7eb;}
.stats-bar{background:#f8faff;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;padding:32px 5%;display:flex;justify-content:center;gap:48px;flex-wrap:wrap;}
.stat{text-align:center;}
.stat .val{font-size:1.6rem;font-weight:900;color:#111827;}
.stat .lbl{font-size:.7rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;margin-top:3px;}
.features{padding:60px 5%;max-width:1100px;margin:0 auto;}
.features h2{text-align:center;font-size:1.55rem;font-weight:800;color:#111827;margin-bottom:8px;}
.features .fsub{text-align:center;color:#6b7280;margin-bottom:36px;font-size:.88rem;}
.feat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;}
.feat-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;transition:box-shadow .2s;}
.feat-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.08);}
.feat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;}
.feat-icon svg{width:22px;height:22px;fill:#fff;}
.feat-card h3{font-size:.94rem;font-weight:700;color:#111827;margin-bottom:6px;}
.feat-card p{font-size:.81rem;color:#6b7280;line-height:1.65;}
.cta-section{background:linear-gradient(135deg,#1e3a8a,#2563eb);padding:60px 5%;text-align:center;}
.cta-section h2{font-size:1.5rem;font-weight:800;color:#fff;margin-bottom:8px;}
.cta-section p{color:#bfdbfe;font-size:.88rem;margin-bottom:22px;}
.cta-section a{padding:13px 32px;border-radius:8px;font-weight:700;font-size:.88rem;background:#fff;color:#1e3a8a;display:inline-block;}
.cta-section a:hover{background:#eff6ff;}
.footer{background:#111827;color:#6b7280;padding:28px 5%;text-align:center;font-size:.77rem;}
.footer .links{display:flex;gap:20px;justify-content:center;margin-bottom:10px;flex-wrap:wrap;}
.footer .links a{color:#6b7280;transition:color .15s;}
.footer .links a:hover{color:#d1d5db;}
</style>

<?php elseif ($page === 'admin'): ?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{min-height:100vh;background:#0a0c10;color:#e4e7eb;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:14px;display:flex;flex-direction:column;}
.top-bar{background:#111318;border-bottom:1px solid #272d3b;padding:0 24px;height:52px;display:flex;align-items:center;gap:10px;flex-shrink:0;}
.top-bar .logo{font-weight:800;font-size:.9rem;color:#f0f4f8;display:flex;align-items:center;gap:8px;}
.top-bar .logo svg{width:18px;height:18px;fill:#f59e0b;}
.tbadge{font-size:.6rem;text-transform:uppercase;letter-spacing:.8px;border:1px solid #78350f;padding:1px 7px;border-radius:3px;color:#f59e0b;background:#1c1007;}
.top-bar .spacer{flex:1;}
.top-bar .env{font-size:.68rem;color:#6b7280;}
.main{flex:1;display:flex;align-items:center;justify-content:center;padding:24px;}
.login-card{background:#111318;border:1px solid #272d3b;border-radius:10px;width:100%;max-width:400px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.5);}
.accent{height:3px;background:linear-gradient(90deg,#92400e,#f59e0b,#fcd34d);}
.card-body{padding:32px 28px 28px;}
.brand-icon{width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,#78350f,#b45309);display:flex;align-items:center;justify-content:center;margin-bottom:16px;}
.brand-icon svg{width:20px;height:20px;fill:#fff;}
.card-body h1{font-size:1.15rem;font-weight:800;color:#f0f4f8;margin-bottom:3px;}
.card-body .sub{font-size:.77rem;color:#6b7280;margin-bottom:22px;}
.form-group{margin-bottom:15px;}
.form-group label{display:block;font-size:.67rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.6px;margin-bottom:5px;}
.input-wrap{display:flex;align-items:center;border:1px solid #272d3b;border-radius:6px;background:#0a0c10;transition:border-color .15s;}
.input-wrap:focus-within{border-color:#f59e0b;}
.input-wrap .icon{padding:0 0 0 11px;display:flex;align-items:center;}
.input-wrap .icon svg{width:14px;height:14px;fill:#6b7280;}
.input-wrap input{flex:1;padding:10px 11px;border:none;outline:none;background:transparent;color:#f0f4f8;font-size:.87rem;font-family:inherit;}
.input-wrap input::placeholder{color:#4b5563;}
.btn-login{width:100%;padding:11px;background:#b45309;color:#fff;border:none;border-radius:6px;font-size:.87rem;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;}
.btn-login:hover{background:#d97706;}
.error-alert{background:#1c0f0f;border:1px solid #4a1c1c;border-left:3px solid #ef4444;border-radius:6px;padding:10px 12px;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px;font-size:.78rem;}
.error-alert svg{width:14px;height:14px;fill:#ef4444;flex-shrink:0;margin-top:1px;}
.error-alert span{color:#fca5a5;}
.footer-bar{background:#111318;border-top:1px solid #272d3b;padding:12px 24px;text-align:center;font-size:.67rem;color:#4b5563;flex-shrink:0;}
.dash-wrap{display:flex;flex:1;min-height:0;}
.sidebar{width:200px;flex-shrink:0;background:#0e1016;border-right:1px solid #272d3b;padding:8px 0 16px;}
.sidebar a{display:flex;align-items:center;gap:8px;padding:9px 16px;color:#9ca3af;font-size:.8rem;transition:background .1s,color .1s;text-decoration:none;}
.sidebar a:hover,.sidebar a.active{background:#1a1e28;color:#f59e0b;}
.sidebar a svg{width:15px;height:15px;fill:currentColor;}
.content{flex:1;padding:22px 28px;overflow-y:auto;}
.page-header{margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #272d3b;display:flex;align-items:center;gap:10px;}
.ph-icon{width:36px;height:36px;border-radius:7px;background:linear-gradient(135deg,#78350f,#b45309);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.ph-icon svg{width:16px;height:16px;fill:#fff;}
.ph-info h2{font-size:1.1rem;font-weight:800;color:#f0f4f8;}
.ph-info .ph-sub{font-size:.72rem;color:#6b7280;}
.ph-badge{margin-left:auto;background:#1c2a0f;color:#86efac;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;padding:4px 10px;border-radius:4px;border:1px solid #166534;}
.data-section{background:#111318;border:1px solid #272d3b;border-radius:8px;overflow:hidden;margin-bottom:18px;}
.ds-title{font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;padding:11px 16px;border-bottom:1px solid #272d3b;background:#0e1016;}
table.dt{width:100%;border-collapse:collapse;font-size:.77rem;}
table.dt th{padding:9px 16px;text-align:left;font-size:.62rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;background:#0e1016;border-bottom:1px solid #272d3b;}
table.dt td{padding:8px 16px;border-bottom:1px solid #1a1e28;color:#d1d5db;}
table.dt tr:last-child td{border-bottom:none;}
table.dt tr:hover td{background:#151820;}
.badge{display:inline-block;padding:2px 8px;border-radius:3px;font-size:.6rem;font-weight:700;text-transform:uppercase;}
.badge-green{background:#064e3b;color:#6ee7b7;}
.badge-yellow{background:#3b2f0f;color:#fcd34d;}
.badge-red{background:#3b1c1c;color:#fca5a5;}
.badge-blue{background:#1e3a5f;color:#93c5fd;}
</style>

<?php elseif ($page === 'manage'): ?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{min-height:100vh;background:#0c0a14;color:#e4e7eb;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:14px;display:flex;flex-direction:column;}
.top-bar{background:#110f1c;border-bottom:1px solid #2a2440;padding:0 24px;height:52px;display:flex;align-items:center;gap:10px;flex-shrink:0;}
.top-bar .logo{font-weight:800;font-size:.9rem;color:#f0f4f8;display:flex;align-items:center;gap:8px;}
.top-bar .logo svg{width:18px;height:18px;fill:#a78bfa;}
.tbadge{font-size:.6rem;text-transform:uppercase;letter-spacing:.8px;border:1px solid #3b1f6e;padding:1px 7px;border-radius:3px;color:#a78bfa;background:#15102a;}
.top-bar .spacer{flex:1;}
.top-bar .env{font-size:.68rem;color:#6b7280;}
.main{flex:1;display:flex;align-items:center;justify-content:center;padding:24px;}
.login-card{background:#110f1c;border:1px solid #2a2440;border-radius:10px;width:100%;max-width:400px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.5);}
.accent{height:3px;background:linear-gradient(90deg,#4c1d95,#7c3aed,#a78bfa);}
.card-body{padding:32px 28px 28px;}
.brand-icon{width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,#4c1d95,#6d28d9);display:flex;align-items:center;justify-content:center;margin-bottom:16px;}
.brand-icon svg{width:20px;height:20px;fill:#fff;}
.card-body h1{font-size:1.15rem;font-weight:800;color:#f0f4f8;margin-bottom:3px;}
.card-body .sub{font-size:.77rem;color:#6b7280;margin-bottom:22px;}
.form-group{margin-bottom:15px;}
.form-group label{display:block;font-size:.67rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.6px;margin-bottom:5px;}
.input-wrap{display:flex;align-items:center;border:1px solid #2a2440;border-radius:6px;background:#0c0a14;transition:border-color .15s;}
.input-wrap:focus-within{border-color:#7c3aed;}
.input-wrap .icon{padding:0 0 0 11px;display:flex;align-items:center;}
.input-wrap .icon svg{width:14px;height:14px;fill:#6b7280;}
.input-wrap input{flex:1;padding:10px 11px;border:none;outline:none;background:transparent;color:#f0f4f8;font-size:.87rem;font-family:inherit;}
.input-wrap input::placeholder{color:#4b5563;}
.btn-login{width:100%;padding:11px;background:#6d28d9;color:#fff;border:none;border-radius:6px;font-size:.87rem;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;}
.btn-login:hover{background:#7c3aed;}
.error-alert{background:#1c0f0f;border:1px solid #4a1c1c;border-left:3px solid #ef4444;border-radius:6px;padding:10px 12px;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px;font-size:.78rem;}
.error-alert svg{width:14px;height:14px;fill:#ef4444;flex-shrink:0;margin-top:1px;}
.error-alert span{color:#fca5a5;}
.footer-bar{background:#110f1c;border-top:1px solid #2a2440;padding:12px 24px;text-align:center;font-size:.67rem;color:#4b5563;flex-shrink:0;}
.dash-wrap{display:flex;flex:1;min-height:0;}
.sidebar{width:200px;flex-shrink:0;background:#0f0d1a;border-right:1px solid #2a2440;padding:8px 0 16px;}
.sidebar a{display:flex;align-items:center;gap:8px;padding:9px 16px;color:#9ca3af;font-size:.8rem;transition:background .1s,color .1s;text-decoration:none;}
.sidebar a:hover,.sidebar a.active{background:#1a1630;color:#a78bfa;}
.sidebar a svg{width:15px;height:15px;fill:currentColor;}
.content{flex:1;padding:22px 28px;overflow-y:auto;}
.page-header{margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #2a2440;display:flex;align-items:center;gap:10px;}
.ph-icon{width:36px;height:36px;border-radius:7px;background:linear-gradient(135deg,#4c1d95,#6d28d9);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.ph-icon svg{width:16px;height:16px;fill:#fff;}
.ph-info h2{font-size:1.1rem;font-weight:800;color:#f0f4f8;}
.ph-info .ph-sub{font-size:.72rem;color:#6b7280;}
.ph-badge{margin-left:auto;background:#15102a;color:#c4b5fd;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;padding:4px 10px;border-radius:4px;border:1px solid #4c1d95;}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:18px;}
.stat-card{background:#110f1c;border:1px solid #2a2440;border-radius:8px;padding:16px 18px;}
.stat-card .sc-title{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;margin-bottom:4px;}
.stat-card .sc-value{font-size:1.5rem;font-weight:800;color:#f0f4f8;}
.stat-card .sc-sub{font-size:.7rem;color:#6b7280;margin-top:2px;}
.stat-card .sc-sub.up{color:#6ee7b7;}
.data-section{background:#110f1c;border:1px solid #2a2440;border-radius:8px;overflow:hidden;margin-bottom:18px;}
.ds-title{font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;padding:11px 16px;border-bottom:1px solid #2a2440;background:#0f0d1a;}
table.dt{width:100%;border-collapse:collapse;font-size:.77rem;}
table.dt th{padding:9px 16px;text-align:left;font-size:.62rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;background:#0f0d1a;border-bottom:1px solid #2a2440;}
table.dt td{padding:8px 16px;border-bottom:1px solid #1a1630;color:#d1d5db;}
table.dt tr:last-child td{border-bottom:none;}
table.dt tr:hover td{background:#16132a;}
.badge{display:inline-block;padding:2px 8px;border-radius:3px;font-size:.6rem;font-weight:700;text-transform:uppercase;}
.badge-green{background:#064e3b;color:#6ee7b7;}
.badge-yellow{background:#3b2f0f;color:#fcd34d;}
.badge-purple{background:#2e1065;color:#c4b5fd;}
</style>
<?php endif; ?>
</head>
<body>

<?php if ($page === 'home'): ?>
<!-- =================== HOME =================== -->
<nav class="nav">
  <div class="nav-logo">
    <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
    VaultTech
  </div>
  <div class="nav-links">
    <a href="#">Products</a>
    <a href="#">Pricing</a>
    <a href="#">Docs</a>
    <a href="#">Status</a>
  </div>
  <div class="nav-spacer"></div>
  <div class="nav-ctas">
    <a href="index.php?page=admin" class="btn-ghost">Admin</a>
    <a href="index.php?page=manage" class="btn-primary">Operations</a>
  </div>
</nav>

<div class="hero">
  <div class="tag">Trusted by 10,000+ businesses worldwide</div>
  <h1>The Payment Infrastructure<br>That <span>Scales With You</span></h1>
  <p>VaultTech provides enterprise-grade payment processing, real-time fraud detection, and deep financial analytics — all on a single unified platform.</p>
  <div class="hero-btns">
    <a href="#" class="hb-primary">Start for free</a>
    <a href="#" class="hb-secondary">View documentation →</a>
  </div>
</div>

<div class="stats-bar">
  <div class="stat"><div class="val">$2.4T+</div><div class="lbl">Processed annually</div></div>
  <div class="stat"><div class="val">10M+</div><div class="lbl">Transactions/day</div></div>
  <div class="stat"><div class="val">99.99%</div><div class="lbl">Uptime SLA</div></div>
  <div class="stat"><div class="val">150+</div><div class="lbl">Countries</div></div>
</div>

<div class="features">
  <h2>Everything you need to run payments at scale</h2>
  <p class="fsub">From processing to compliance — VaultTech covers the full payment lifecycle.</p>
  <div class="feat-grid">
    <div class="feat-card">
      <div class="feat-icon" style="background:linear-gradient(135deg,#1e3a8a,#2563eb);">
        <svg viewBox="0 0 24 24"><path d="M13 2.05v2.02c3.95.49 7 3.85 7 7.93 0 3.21-1.81 6-4.72 7.28L13 17v5l5-2.88C21.73 17.24 24 13.67 24 12c0-5.92-4.58-10.77-11-11.95zM11 2.05C4.58 3.23 0 8.08 0 14c0 1.67 2.27 5.24 6 7.12L11 24v-5l-2.28-1.79C6.81 16 5 13.21 5 10c0-4.08 3.05-7.44 7-7.93V2.05z"/></svg>
      </div>
      <h3>Instant Settlement</h3>
      <p>Real-time fund settlement across 150+ countries with automatic currency conversion and local payment rails integration.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:linear-gradient(135deg,#065f46,#059669);">
        <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
      </div>
      <h3>Fraud Intelligence</h3>
      <p>ML-powered fraud detection with 99.97% accuracy, processing 10M+ signals per second to protect every transaction.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:linear-gradient(135deg,#4c1d95,#7c3aed);">
        <svg viewBox="0 0 24 24"><path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/></svg>
      </div>
      <h3>Real-time Analytics</h3>
      <p>Live financial dashboards, cohort analysis, and custom reporting — understand your revenue the moment it happens.</p>
    </div>
  </div>
</div>

<div class="cta-section">
  <h2>Ready to scale your payments?</h2>
  <p>Join thousands of businesses processing billions with VaultTech.</p>
  <a href="#">Get started today</a>
</div>

<div class="footer">
  <div class="links">
    <a href="#">Privacy Policy</a>
    <a href="#">Terms of Service</a>
    <a href="#">Security</a>
    <a href="#">Status</a>
    <a href="#">Contact</a>
  </div>
  &copy; <?=date('Y')?> VaultTech Inc. All rights reserved.
</div>

<?php elseif ($page === 'admin'): ?>
<!-- =================== ADMIN PORTAL =================== -->
<div class="top-bar">
  <div class="logo">
    <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
    VaultTech
  </div>
  <span class="tbadge">Administration</span>
  <div class="spacer"></div>
  <span class="env">admin.vaulttech.io</span>
</div>

<?php if (!$logged_in): ?>
<div class="main">
  <div class="login-card">
    <div class="accent"></div>
    <div class="card-body">
      <div class="brand-icon">
        <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
      </div>
      <h1>Administration Console</h1>
      <div class="sub">Authorized personnel only. All access is logged and monitored.</div>
      <?php if ($auth_err): ?>
      <div class="error-alert">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <span><?=htmlspecialchars($auth_err)?></span>
      </div>
      <?php endif; ?>
      <form method="POST" action="index.php?page=admin">
        <div class="form-group">
          <label>Username</label>
          <div class="input-wrap">
            <span class="icon"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></span>
            <input type="text" name="username" placeholder="Username" autocomplete="off">
          </div>
        </div>
        <div class="form-group">
          <label>Password</label>
          <div class="input-wrap">
            <span class="icon"><svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/></svg></span>
            <input type="password" name="password" placeholder="Password">
          </div>
        </div>
        <button type="submit" name="login" class="btn-login">Sign In</button>
      </form>
    </div>
  </div>
</div>

<?php else: ?>
<div class="dash-wrap">
  <div class="sidebar">
    <a href="#" class="active">
      <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
      Dashboard
    </a>
    <a href="#">
      <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
      User Accounts
    </a>
    <a href="#">
      <svg viewBox="0 0 24 24"><path d="M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/></svg>
      API Keys
    </a>
    <a href="#">
      <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
      Settings
    </a>
  </div>
  <div class="content">
    <div class="page-header">
      <div class="ph-icon"><svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg></div>
      <div class="ph-info">
        <h2>Administration Console</h2>
        <div class="ph-sub">Platform administration — full access granted</div>
      </div>
      <div class="ph-badge">Authenticated as admin</div>
    </div>
    <div class="data-section">
      <div class="ds-title">User Accounts</div>
      <table class="dt">
        <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>MFA</th></tr></thead>
        <tbody>
          <tr><td>1</td><td>admin</td><td>admin@vaulttech.io</td><td><span class="badge badge-yellow">Super Admin</span></td><td><span class="badge badge-green">Active</span></td><td><span class="badge badge-red">Disabled</span></td></tr>
          <tr><td>2</td><td>ops_manager</td><td>ops@vaulttech.io</td><td><span class="badge badge-blue">Operator</span></td><td><span class="badge badge-green">Active</span></td><td><span class="badge badge-green">Enabled</span></td></tr>
          <tr><td>3</td><td>analyst_01</td><td>analyst@vaulttech.io</td><td><span class="badge badge-blue">Analyst</span></td><td><span class="badge badge-green">Active</span></td><td><span class="badge badge-green">Enabled</span></td></tr>
          <tr><td>4</td><td>support_head</td><td>support@vaulttech.io</td><td><span class="badge badge-blue">Support</span></td><td><span class="badge badge-yellow">Suspended</span></td><td><span class="badge badge-red">Disabled</span></td></tr>
          <tr><td>5</td><td>finance_lead</td><td>finance@vaulttech.io</td><td><span class="badge badge-blue">Finance</span></td><td><span class="badge badge-green">Active</span></td><td><span class="badge badge-green">Enabled</span></td></tr>
        </tbody>
      </table>
    </div>
    <div class="data-section">
      <div class="ds-title">Recent System Activity</div>
      <table class="dt">
        <thead><tr><th>Timestamp</th><th>Event</th><th>Source IP</th><th>Result</th></tr></thead>
        <tbody>
          <tr><td>2026-06-06 08:01:14</td><td>Admin login</td><td>10.0.0.1</td><td><span class="badge badge-green">Success</span></td></tr>
          <tr><td>2026-06-06 07:58:33</td><td>Failed login attempt</td><td>185.220.101.47</td><td><span class="badge badge-red">Failed</span></td></tr>
          <tr><td>2026-06-05 23:44:07</td><td>API key rotated</td><td>10.0.0.8</td><td><span class="badge badge-green">Success</span></td></tr>
          <tr><td>2026-06-05 22:12:55</td><td>User suspended: support_head</td><td>10.0.0.1</td><td><span class="badge badge-yellow">Warning</span></td></tr>
          <tr><td>2026-06-05 18:00:00</td><td>Scheduled backup completed</td><td>10.0.0.5</td><td><span class="badge badge-green">Success</span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>
<div class="footer-bar">&copy; <?=date('Y')?> VaultTech Inc. — Administration Console. Unauthorized access is prohibited.</div>

<?php elseif ($page === 'manage'): ?>
<!-- =================== OPERATIONS CENTER =================== -->
<div class="top-bar">
  <div class="logo">
    <svg viewBox="0 0 24 24"><path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/></svg>
    VaultTech
  </div>
  <span class="tbadge">Operations</span>
  <div class="spacer"></div>
  <span class="env">manage.vaulttech.io</span>
</div>

<?php if (!$logged_in): ?>
<div class="main">
  <div class="login-card">
    <div class="accent"></div>
    <div class="card-body">
      <div class="brand-icon">
        <svg viewBox="0 0 24 24"><path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/></svg>
      </div>
      <h1>Operations Center</h1>
      <div class="sub">Access restricted to operations team members.</div>
      <?php if ($auth_err): ?>
      <div class="error-alert">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <span><?=htmlspecialchars($auth_err)?></span>
      </div>
      <?php endif; ?>
      <form method="POST" action="index.php?page=manage">
        <div class="form-group">
          <label>Username</label>
          <div class="input-wrap">
            <span class="icon"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></span>
            <input type="text" name="username" placeholder="Username" autocomplete="off">
          </div>
        </div>
        <div class="form-group">
          <label>Password</label>
          <div class="input-wrap">
            <span class="icon"><svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/></svg></span>
            <input type="password" name="password" placeholder="Password">
          </div>
        </div>
        <button type="submit" name="login" class="btn-login">Sign In</button>
      </form>
    </div>
  </div>
</div>

<?php else: ?>
<div class="dash-wrap">
  <div class="sidebar">
    <a href="#" class="active">
      <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
      Overview
    </a>
    <a href="#">
      <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
      Client Accounts
    </a>
    <a href="#">
      <svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
      Revenue
    </a>
    <a href="#">
      <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
      Team
    </a>
  </div>
  <div class="content">
    <div class="page-header">
      <div class="ph-icon"><svg viewBox="0 0 24 24"><path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/></svg></div>
      <div class="ph-info">
        <h2>Operations Center</h2>
        <div class="ph-sub">Full operations management access</div>
      </div>
      <div class="ph-badge">Authenticated as admin</div>
    </div>
    <div class="stat-grid">
      <div class="stat-card">
        <div class="sc-title">Total Revenue (MTD)</div>
        <div class="sc-value">$48.2M</div>
        <div class="sc-sub up">▲ 18.4% vs last month</div>
      </div>
      <div class="stat-card">
        <div class="sc-title">Active Clients</div>
        <div class="sc-value">1,847</div>
        <div class="sc-sub up">▲ 43 new this month</div>
      </div>
      <div class="stat-card">
        <div class="sc-title">Transactions (today)</div>
        <div class="sc-value">2.84M</div>
        <div class="sc-sub up">On track</div>
      </div>
      <div class="stat-card">
        <div class="sc-title">Chargebacks (MTD)</div>
        <div class="sc-value">0.08%</div>
        <div class="sc-sub" style="color:#fcd34d;">Within threshold</div>
      </div>
    </div>
    <div class="data-section">
      <div class="ds-title">Client Accounts — Top Tier</div>
      <table class="dt">
        <thead><tr><th>Client</th><th>Sector</th><th>Volume (MTD)</th><th>Status</th><th>CSM</th></tr></thead>
        <tbody>
          <tr><td>Acme Corp</td><td>Retail</td><td>$8.4M</td><td><span class="badge badge-green">Active</span></td><td>ops_manager</td></tr>
          <tr><td>TechVentures Ltd</td><td>SaaS</td><td>$6.1M</td><td><span class="badge badge-green">Active</span></td><td>analyst_01</td></tr>
          <tr><td>GlobalPay Systems</td><td>Payments</td><td>$12.7M</td><td><span class="badge badge-green">Active</span></td><td>ops_manager</td></tr>
          <tr><td>RetailPlus Group</td><td>E-commerce</td><td>$5.8M</td><td><span class="badge badge-yellow">Review</span></td><td>finance_lead</td></tr>
          <tr><td>FinServe LLC</td><td>Financial</td><td>$9.3M</td><td><span class="badge badge-green">Active</span></td><td>ops_manager</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>
<div class="footer-bar">&copy; <?=date('Y')?> VaultTech Inc. — Operations Center.</div>

<?php endif; ?>
</body>
</html>
