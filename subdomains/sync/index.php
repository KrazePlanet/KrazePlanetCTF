<?php
$page = $_GET['page'] ?? 'dashboard';
$logged_in = false;
$auth_err = '';

// ─── Login logic ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'login') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    // Credentials exposed in user.txt — admin / SarahM@2024
    if ($u === 'admin' && $p === 'SarahM@2024') {
        $logged_in = true;
        $page = 'dashboard';
    } else {
        $auth_err = 'Invalid credentials.';
    }
}

$title = 'StackWatch — Server Monitoring';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?=htmlspecialchars($title)?></title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{min-height:100vh;background:#0d1117;color:#c9d1d9;font-family:'Inter',-apple-system,sans-serif;font-size:13.5px;}
body{display:flex;flex-direction:column;}
a{text-decoration:none;color:inherit;}
::selection{background:#264f78;}
.topbar{display:flex;align-items:center;height:44px;background:#161b22;border-bottom:1px solid #30363d;padding:0 16px;flex-shrink:0;gap:12px;}
.topbar .brand{display:flex;align-items:center;gap:7px;font-weight:700;font-size:.85rem;color:#f0f6fc;}
.topbar .brand svg{width:18px;height:18px;}
.brand-fill{fill:#58a6ff;}
.topbar .sep{width:1px;height:22px;background:#30363d;}
.topbar .nav-item{font-size:.72rem;color:#8b949e;padding:3px 10px;border-radius:4px;cursor:pointer;transition:background .12s,color .12s;font-weight:500;}
.topbar .nav-item:hover{color:#f0f6fc;background:#1c2128;}
.topbar .nav-item.active{color:#f0f6fc;background:#1f6feb33;border:1px solid #1f6feb66;}
.topbar .spacer{flex:1;}
.topbar .status-dot{width:7px;height:7px;border-radius:50%;background:#3fb950;display:inline-block;margin-right:5px;box-shadow:0 0 6px #3fb95066;}
.topbar .status-text{font-size:.65rem;color:#8b949e;}
.main{display:flex;flex:1;min-height:0;}
.sidebar{width:200px;flex-shrink:0;background:#161b22;border-right:1px solid #30363d;padding:6px 0;overflow-y:auto;}
.sidebar .sec-title{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#484f58;padding:8px 14px 4px;}
.sidebar .s-item{display:flex;align-items:center;gap:8px;padding:6px 14px;color:#8b949e;font-size:.74rem;cursor:pointer;border-left:2px solid transparent;transition:all .1s;}
.sidebar .s-item:hover{background:#1c2128;color:#c9d1d9;}
.sidebar .s-item.active{border-left-color:#58a6ff;background:#1c2128;color:#f0f6fc;font-weight:500;}
.sidebar .s-item svg{width:14px;height:14px;fill:currentColor;flex-shrink:0;}
.sidebar .s-item .alert-dot{width:6px;height:6px;border-radius:50%;background:#f85149;margin-left:auto;box-shadow:0 0 4px #f8514980;}
.content{flex:1;padding:18px 22px;overflow-y:auto;min-width:0;}
.crumb{font-size:.7rem;color:#484f58;margin-bottom:10px;}
.crumb span{color:#8b949e;}
.crumb .current{color:#c9d1d9;}
.header-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;}
.header-row h1{font-size:1.3rem;font-weight:700;color:#f0f6fc;display:flex;align-items:center;gap:8px;}
.header-row h1 svg{width:18px;height:18px;fill:#58a6ff;}
.header-row .time-range{display:flex;gap:3px;}
.header-row .time-range button{background:transparent;border:1px solid #30363d;color:#8b949e;padding:4px 10px;font-size:.68rem;border-radius:4px;cursor:pointer;font-family:inherit;transition:all .1s;}
.header-row .time-range button:hover{background:#1c2128;color:#c9d1d9;}
.header-row .time-range button.active{background:#1f6feb33;border-color:#1f6feb66;color:#58a6ff;}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-bottom:16px;}
.stat-card{background:#161b22;border:1px solid #30363d;border-radius:6px;padding:13px 15px;}
.stat-card .sc-title{font-size:.6rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#484f58;margin-bottom:3px;display:flex;align-items:center;gap:5px;}
.stat-card .sc-title svg{width:12px;height:12px;fill:currentColor;}
.stat-card .sc-value{font-size:1.4rem;font-weight:800;color:#f0f6fc;font-family:'JetBrains Mono',monospace;letter-spacing:-.5px;line-height:1.2;}
.stat-card .sc-sub{font-size:.62rem;color:#8b949e;margin-top:2px;display:flex;align-items:center;gap:4px;}
.stat-card .sc-sub .arrow-up{color:#3fb950;}
.stat-card .sc-sub .arrow-down{color:#f85149;}
.stat-card .sc-sub .dot-ok{width:6px;height:6px;border-radius:50%;background:#3fb950;display:inline-block;}
.stat-card .sc-sub .dot-warn{background:#d29922;}
.stat-card .sc-sub .dot-err{background:#f85149;}
.sc-green .sc-value{color:#3fb950;}
.sc-blue .sc-value{color:#58a6ff;}
.sc-yellow .sc-value{color:#d29922;}
.sc-red .sc-value{color:#f85149;}
.panel-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;}
.panel{background:#161b22;border:1px solid #30363d;border-radius:6px;overflow:hidden;}
.panel.full{grid-column:1/-1;}
.panel .ph{display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border-bottom:1px solid #30363d;background:#0d1117;}
.panel .ph .ph-title{font-size:.68rem;font-weight:600;color:#8b949e;display:flex;align-items:center;gap:5px;}
.panel .ph .ph-title svg{width:12px;height:12px;fill:currentColor;}
.panel .pb{padding:10px 12px;}
.mini-bars{display:flex;align-items:flex-end;gap:3px;height:50px;}
.mini-bars .bar{flex:1;border-radius:2px 2px 0 0;min-height:4px;background:#1f6feb;opacity:.7;transition:opacity .12s;}
.mini-bars .bar:hover{opacity:1;}
.mini-bars .bar.crit{background:#f85149;}
.mini-bars .bar.warn{background:#d29922;}
table.dt{width:100%;border-collapse:collapse;font-size:.72rem;}
table.dt th{text-align:left;padding:6px 8px;font-size:.6rem;font-weight:600;color:#484f58;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #30363d;background:#0d1117;}
table.dt td{padding:5px 8px;border-bottom:1px solid #21262d;color:#c9d1d9;font-family:'JetBrains Mono',monospace;font-size:.68rem;}
table.dt tr:last-child td{border-bottom:none;}
table.dt tr:hover td{background:#1c2128;}
.badge{display:inline-block;padding:1px 6px;border-radius:3px;font-size:.58rem;font-weight:600;text-transform:uppercase;}
.badge-ok{background:#0f2d1a;color:#3fb950;border:1px solid #3fb95033;}
.badge-warn{background:#2d1f0a;color:#d29922;border:1px solid #d2992233;}
.badge-err{background:#2d0f0f;color:#f85149;border:1px solid #f8514933;}
.badge-info{background:#0f1d2d;color:#58a6ff;border:1px solid #58a6ff33;}
.login-wrap{display:flex;align-items:center;justify-content:center;flex:1;background:#0d1117;}
.login-box{background:#161b22;border:1px solid #30363d;border-radius:8px;padding:32px 28px 28px;width:100%;max-width:340px;box-shadow:0 8px 32px rgba(0,0,0,.6);}
.login-box .l-icon{width:42px;height:42px;border-radius:8px;background:#1f6feb33;border:1px solid #1f6feb66;display:flex;align-items:center;justify-content:center;margin-bottom:14px;}
.login-box .l-icon svg{width:20px;height:20px;fill:#58a6ff;}
.login-box h1{font-size:1.1rem;font-weight:700;color:#f0f6fc;margin-bottom:2px;}
.login-box .l-sub{font-size:.72rem;color:#8b949e;margin-bottom:18px;}
.form-group{margin-bottom:12px;}
.form-group label{display:block;font-size:.62rem;font-weight:600;color:#8b949e;margin-bottom:3px;}
.form-group input{width:100%;padding:8px 10px;background:#0d1117;border:1px solid #30363d;border-radius:5px;color:#f0f6fc;font-size:.82rem;font-family:'JetBrains Mono',monospace;outline:none;transition:border-color .12s;}
.form-group input:focus{border-color:#58a6ff;}
.btn-submit{width:100%;padding:9px;background:#238636;color:#fff;border:none;border-radius:5px;font-size:.82rem;font-weight:600;cursor:pointer;font-family:inherit;transition:background .12s;}
.btn-submit:hover{background:#2ea043;}
.err-box{background:#2d0f0f;border:1px solid #f8514944;border-left:3px solid #f85149;border-radius:5px;padding:8px 10px;margin-bottom:12px;font-size:.72rem;color:#fca5a5;}
.err-box svg{width:12px;height:12px;fill:#f85149;vertical-align:middle;margin-right:5px;}
</style>
</head>
<body>

<?php if ($page === 'dashboard'): ?>

<header class="topbar">
  <a href="index.php" class="brand">
    <svg viewBox="0 0 24 24"><path class="brand-fill" d="M21 6h-2v2h-2V6h-2V4h2V2h2v2h2v2zm-10 3c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm0 4c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
    StackWatch
  </a>
  <div class="sep"></div>
  <a href="index.php" class="nav-item active">Dashboard</a>
  <a href="#" class="nav-item">Servers</a>
  <a href="#" class="nav-item">Alerts</a>
  <a href="#" class="nav-item">Logs</a>
  <div class="spacer"></div>
  <span><span class="status-dot"></span><span class="status-text">All systems operational</span></span>
</header>

<div class="main">
  <nav class="sidebar">
    <div class="sec-title">Infrastructure</div>
    <div class="s-item active"><svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>Overview</div>
    <div class="s-item"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>Servers<span class="alert-dot"></span></div>
    <div class="s-item"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>Network</div>
    <div class="s-item"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>Databases</div>
    <div class="sec-title">Monitoring</div>
    <div class="s-item"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>Metrics</div>
    <div class="s-item"><svg viewBox="0 0 24 24"><path d="M17.66 17.66l-1.42 1.42-.71-.71-1.41 1.41.71.71-2.83 2.83L7.37 18H4v-3.37l7.07-7.07 3.54 3.54 1.41-1.41-3.54-3.54 1.06-1.06L20 3.34V8c0 2.21-1.79 4-4 4h-.34l-1.41 1.41 2.12 2.12 1.42-1.42 1.41 1.41-3.54 3.54zM4 20h3.37l6.36-6.36-3.54-3.54L4 16.63V20z"/></svg>Alerts<span class="alert-dot"></span></div>
    <div class="s-item"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4v-4h4v2h2v-2h10v4zm0-7H10V9H8v2H4V6h16v5z"/></svg>Logs</div>
  </nav>

  <main class="content">
    <div class="crumb">Infrastructure <span>/</span> <span class="current">Overview</span></div>

    <div class="header-row">
      <h1><svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>Dashboard</h1>
      <div class="time-range">
        <button class="active">1h</button>
        <button>6h</button>
        <button>24h</button>
        <button>7d</button>
        <button>30d</button>
      </div>
    </div>

    <div class="stat-grid">
      <div class="stat-card sc-green">
        <div class="sc-title"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Servers Online</div>
        <div class="sc-value">24/26</div>
        <div class="sc-sub"><span class="dot-ok"></span> 2 servers offline</div>
      </div>
      <div class="stat-card sc-yellow">
        <div class="sc-title"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>CPU Avg</div>
        <div class="sc-value">47%</div>
        <div class="sc-sub"><span class="arrow-up">▲</span> 12% from baseline</div>
      </div>
      <div class="stat-card sc-blue">
        <div class="sc-title"><svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>Memory Avg</div>
        <div class="sc-value">62%</div>
        <div class="sc-sub">32.4 GB / 64 GB used</div>
      </div>
      <div class="stat-card sc-red">
        <div class="sc-title"><svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>Active Alerts</div>
        <div class="sc-value">7</div>
        <div class="sc-sub"><span class="dot-err"></span> 3 critical, 4 warnings</div>
      </div>
    </div>

    <div class="panel-grid">
      <div class="panel">
        <div class="ph">
          <span class="ph-title"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>CPU Usage — Last 60 min</span>
        </div>
        <div class="pb">
          <div class="mini-bars">
            <div class="bar" style="height:38%"></div>
            <div class="bar" style="height:42%"></div>
            <div class="bar" style="height:35%"></div>
            <div class="bar warn" style="height:55%"></div>
            <div class="bar" style="height:48%"></div>
            <div class="bar warn" style="height:62%"></div>
            <div class="bar" style="height:44%"></div>
            <div class="bar" style="height:39%"></div>
            <div class="bar crit" style="height:71%"></div>
            <div class="bar warn" style="height:58%"></div>
            <div class="bar" style="height:45%"></div>
            <div class="bar" style="height:41%"></div>
            <div class="bar" style="height:36%"></div>
            <div class="bar warn" style="height:52%"></div>
            <div class="bar" style="height:47%"></div>
            <div class="bar" style="height:43%"></div>
            <div class="bar" style="height:38%"></div>
            <div class="bar" style="height:33%"></div>
            <div class="bar" style="height:49%"></div>
            <div class="bar warn" style="height:56%"></div>
          </div>
        </div>
      </div>
      <div class="panel">
        <div class="ph">
          <span class="ph-title"><svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>Memory — Last 60 min</span>
        </div>
        <div class="pb">
          <div class="mini-bars">
            <div class="bar" style="height:58%"></div>
            <div class="bar" style="height:62%"></div>
            <div class="bar" style="height:55%"></div>
            <div class="bar" style="height:59%"></div>
            <div class="bar" style="height:64%"></div>
            <div class="bar" style="height:61%"></div>
            <div class="bar" style="height:57%"></div>
            <div class="bar" style="height:63%"></div>
            <div class="bar warn" style="height:68%"></div>
            <div class="bar" style="height:60%"></div>
            <div class="bar" style="height:56%"></div>
            <div class="bar" style="height:59%"></div>
            <div class="bar" style="height:65%"></div>
            <div class="bar" style="height:62%"></div>
            <div class="bar" style="height:58%"></div>
            <div class="bar" style="height:54%"></div>
            <div class="bar" style="height:61%"></div>
            <div class="bar warn" style="height:67%"></div>
            <div class="bar" style="height:63%"></div>
            <div class="bar" style="height:60%"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="panel full">
      <div class="ph">
        <span class="ph-title"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>Server Status</span>
      </div>
      <div class="pb" style="padding:0;">
        <table class="dt">
          <thead>
            <tr><th>Hostname</th><th>IP</th><th>CPU</th><th>Memory</th><th>Disk</th><th>Uptime</th><th>Status</th></tr>
          </thead>
          <tbody>
            <tr><td>web-01</td><td>10.0.1.12</td><td>23%</td><td>45%</td><td>67%</td><td>143d</td><td><span class="badge badge-ok">Online</span></td></tr>
            <tr><td>web-02</td><td>10.0.1.13</td><td>31%</td><td>52%</td><td>71%</td><td>143d</td><td><span class="badge badge-ok">Online</span></td></tr>
            <tr><td>api-01</td><td>10.0.2.05</td><td>47%</td><td>68%</td><td>44%</td><td>89d</td><td><span class="badge badge-ok">Online</span></td></tr>
            <tr><td>api-02</td><td>10.0.2.06</td><td>52%</td><td>71%</td><td>48%</td><td>89d</td><td><span class="badge badge-warn">Degraded</span></td></tr>
            <tr><td>db-master</td><td>10.0.3.01</td><td>38%</td><td>82%</td><td>55%</td><td>201d</td><td><span class="badge badge-warn">High Mem</span></td></tr>
            <tr><td>db-replica-01</td><td>10.0.3.02</td><td>12%</td><td>31%</td><td>42%</td><td>67d</td><td><span class="badge badge-ok">Online</span></td></tr>
            <tr><td>redis-01</td><td>10.0.4.10</td><td>8%</td><td>22%</td><td>38%</td><td>34d</td><td><span class="badge badge-ok">Online</span></td></tr>
            <tr><td>worker-01</td><td>10.0.5.20</td><td>89%</td><td>93%</td><td>76%</td><td>12d</td><td><span class="badge badge-err">Critical</span></td></tr>
            <tr><td>worker-02</td><td>10.0.5.21</td><td>76%</td><td>88%</td><td>72%</td><td>12d</td><td><span class="badge badge-warn">High Load</span></td></tr>
            <tr><td>cdn-edge-01</td><td>10.0.6.30</td><td>15%</td><td>34%</td><td>29%</td><td>321d</td><td><span class="badge badge-ok">Online</span></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel full">
      <div class="ph">
        <span class="ph-title"><svg viewBox="0 0 24 24"><path d="M17.66 17.66l-1.42 1.42-.71-.71-1.41 1.41.71.71-2.83 2.83L7.37 18H4v-3.37l7.07-7.07 3.54 3.54 1.41-1.41-3.54-3.54 1.06-1.06L20 3.34V8c0 2.21-1.79 4-4 4h-.34l-1.41 1.41 2.12 2.12 1.42-1.42 1.41 1.41-3.54 3.54zM4 20h3.37l6.36-6.36-3.54-3.54L4 16.63V20z"/></svg>Recent Alerts</span>
      </div>
      <div class="pb" style="padding:0;">
        <table class="dt">
          <thead>
            <tr><th>Severity</th><th>Host</th><th>Alert</th><th>Value</th><th>Triggered</th></tr>
          </thead>
          <tbody>
            <tr><td><span class="badge badge-err">Critical</span></td><td>worker-01</td><td>CPU overload</td><td>89%</td><td>2026-06-06 03:22:14</td></tr>
            <tr><td><span class="badge badge-err">Critical</span></td><td>worker-01</td><td>Memory threshold exceeded</td><td>93%</td><td>2026-06-06 03:15:00</td></tr>
            <tr><td><span class="badge badge-err">Critical</span></td><td>db-master</td><td>Replication lag &gt; 30s</td><td>47s</td><td>2026-06-06 02:58:33</td></tr>
            <tr><td><span class="badge badge-warn">Warning</span></td><td>api-02</td><td>High error rate (5xx)</td><td>3.2%</td><td>2026-06-06 02:30:11</td></tr>
            <tr><td><span class="badge badge-warn">Warning</span></td><td>worker-02</td><td>Queue depth spike</td><td>1,847</td><td>2026-06-06 01:55:00</td></tr>
            <tr><td><span class="badge badge-warn">Warning</span></td><td>web-02</td><td>SSL cert expires in 7d</td><td>7d</td><td>2026-06-06 00:00:00</td></tr>
            <tr><td><span class="badge badge-info">Info</span></td><td>cdn-edge-01</td><td>Bandwidth spike</td><td>42 Gbps</td><td>2026-06-05 22:10:00</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?php elseif ($page === 'login'): ?>

<div class="login-wrap">
  <div class="login-box">
    <div class="l-icon">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
    </div>
    <h1>StackWatch Login</h1>
    <p class="l-sub">Authorized personnel only</p>
    <?php if (!empty($auth_err)): ?>
      <div class="err-box"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg> <?=htmlspecialchars($auth_err)?></div>
    <?php endif; ?>
    <form method="post">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="username" autocomplete="off">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" autocomplete="off">
      </div>
      <button type="submit" class="btn-submit">Sign In</button>
    </form>
  </div>
</div>

<?php endif; ?>
</body>
</html>
</style>
</head>
<body>

<?php if ($page === 'home'): ?>

<!-- ════════════════════ NAV ════════════════════ -->
<nav class="nav">
  <a href="index.php" class="nav-logo">
    <svg viewBox="0 0 32 32"><path class="logomark" d="M16 2L2 9v14l14 7 14-7V9L16 2zm0 3.2l10.2 5.1-4.2 2.1L16 9.4 9.8 12.4 5.8 10.3 16 5.2zM6 13.4l4 2v4.6l-4-2v-4.6zm8 4.6l-4-2v-4.6l4 2v4.6zm2 1l-4-2v-4.6l4 2v4.6zm6-1l-4 2v-4.6l4-2v4.6zm-4-6.6l4-2 4 2-4 2-4-2zm8 3.6l-4 2v4.6l4-2v-4.6z"/></svg>
    CloudSync
  </a>
  <div class="nav-links">
    <a href="#">Products</a>
    <a href="#">Solutions</a>
    <a href="#">Pricing</a>
    <a href="#">Docs</a>
    <a href="#">Status</a>
  </div>
  <div class="nav-spacer"></div>
  <div class="nav-ctas">
    <a href="#" class="btn-outline">Sign In</a>
    <a href="#" class="btn-filled">Start Free</a>
  </div>
</nav>

<!-- ════════════════════ HERO ════════════════════ -->
<section class="hero">
  <div class="tag">
    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
    50,000+ businesses trust CloudSync
  </div>
  <h1>Enterprise File Sync,<br><span>Built for Scale</span></h1>
  <p>CloudSync provides secure, real-time file synchronization and collaboration for enterprises worldwide. With military-grade encryption and 99.99% uptime, your data is always available and always protected.</p>
  <div class="hero-btns">
    <a href="#" class="hb-primary">Start for free</a>
    <a href="#" class="hb-secondary">Talk to sales →</a>
  </div>
</section>

<!-- ════════════════════ STATS ════════════════════ -->
<section class="stats-bar">
  <div class="stat">
    <div class="s-icon"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></div>
    <div class="val">50K+</div>
    <div class="lbl">Businesses</div>
  </div>
  <div class="stat">
    <div class="s-icon"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-8 2.5c1.93 0 3.5 1.57 3.5 3.5s-1.57 3.5-3.5 3.5S8.5 11.93 8.5 10 10.07 6.5 12 6.5zM18 18H6v-.75c0-2.5 5-3.75 6-3.75s6 1.25 6 3.75V18z"/></svg></div>
    <div class="val">2PB+</div>
    <div class="lbl">Files Synced</div>
  </div>
  <div class="stat">
    <div class="s-icon"><svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg></div>
    <div class="val">99.99%</div>
    <div class="lbl">Uptime SLA</div>
  </div>
  <div class="stat">
    <div class="s-icon"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg></div>
    <div class="val">200+</div>
    <div class="lbl">Countries</div>
  </div>
</section>

<!-- ════════════════════ FEATURES ════════════════════ -->
<section class="features">
  <h2>Everything you need for enterprise sync</h2>
  <p class="fsub">Secure, scalable, and built for the way your team works.</p>
  <div class="feat-grid">
    <div class="feat-card">
      <div class="feat-icon" style="background:linear-gradient(135deg,#1e40af,#3b82f6);">
        <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
      </div>
      <h3>Sync Engine</h3>
      <p>Real-time file synchronization across unlimited devices. Delta sync technology ensures only changed bytes are transferred.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:linear-gradient(135deg,#166534,#22c55e);">
        <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
      </div>
      <h3>Security &amp; Compliance</h3>
      <p>End-to-end encryption with AES-256. SOC 2 Type II, HIPAA, and GDPR compliant. Full audit trail on every file operation.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:linear-gradient(135deg,#6d28d9,#8b5cf6);">
        <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
      </div>
      <h3>Team Collaboration</h3>
      <p>Shared workspaces, real-time co-editing, version history with 30-day file recovery. Comment and review workflows built in.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:linear-gradient(135deg,#b45309,#f59e0b);">
        <svg viewBox="0 0 24 24"><path d="M19 14s-2 2.67-2 4c0 1.1.9 2 2 2s2-.9 2-2c0-1.33-2-4-2-4zm-4.5-3.5C13.5 12.5 12 14 9 14c-3 0-4.5-1.5-4.5-3.5S7 7 9 7s5.5 1.5 5.5 3.5zM3 20v-1c0-2.67 5.33-4 8-4 .75 0 1.72.07 2.72.22C12.25 16.63 12 17.88 12 19c0 1.08.26 2.1.72 3H3z"/></svg>
      </div>
      <h3>API &amp; Integrations</h3>
      <p>RESTful API with webhook support. Native integrations with Slack, Teams, Jira, Salesforce, and 500+ enterprise tools.</p>
    </div>
  </div>
</section>

<!-- ════════════════════ CTA ════════════════════ -->
<section class="cta-section">
  <h2>Ready to move your files to the cloud?</h2>
  <p>Join 50,000+ enterprises already syncing with CloudSync.</p>
  <a href="#">Get started today →</a>
</section>

<!-- ════════════════════ FOOTER ════════════════════ -->
<footer class="footer">
  <div class="links">
    <a href="#">Privacy Policy</a>
    <a href="#">Terms of Service</a>
    <a href="#">Security</a>
    <a href="#">Status</a>
    <a href="#">Contact</a>
  </div>
  <p>© 2026 CloudSync Inc. All rights reserved.</p>
</footer>

<?php elseif ($page === 'admin'): ?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{background:#0a0f1a;color:#e2e8f0;font-family:'Inter',sans-serif;font-size:14px;display:flex;flex-direction:column;min-height:100vh;}
.top-bar{background:#0f172a;border-bottom:1px solid #1e293b;padding:0 24px;height:52px;display:flex;align-items:center;gap:10px;flex-shrink:0;}
.top-bar .logo{font-weight:800;font-size:.88rem;color:#f1f5f9;display:flex;align-items:center;gap:7px;}
.top-bar .logo svg{width:17px;height:17px;fill:#3b82f6;}
.tbadge{font-size:.58rem;text-transform:uppercase;letter-spacing:.7px;border:1px solid #1e3a5f;padding:1px 7px;border-radius:3px;color:#60a5fa;background:#0f1a2e;}
.top-bar .spacer{flex:1;}
.top-bar .env{font-size:.66rem;color:#64748b;}
.main{flex:1;display:flex;align-items:center;justify-content:center;padding:24px;}
.login-card{background:#0f172a;border:1px solid #1e293b;border-radius:10px;width:100%;max-width:380px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.5);}
.accent{height:3px;background:linear-gradient(90deg,#1e40af,#3b82f6,#60a5fa);}
.card-body{padding:30px 26px 26px;}
.brand-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#1e40af,#3b82f6);display:flex;align-items:center;justify-content:center;margin-bottom:14px;}
.brand-icon svg{width:18px;height:18px;fill:#fff;}
.card-body h1{font-size:1.1rem;font-weight:800;color:#f1f5f9;margin-bottom:2px;}
.card-body .sub{font-size:.75rem;color:#64748b;margin-bottom:20px;}
.form-group{margin-bottom:14px;}
.form-group label{display:block;font-size:.65rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;}
.input-wrap{display:flex;align-items:center;border:1px solid #1e293b;border-radius:6px;background:#0a0f1a;transition:border-color .15s;}
.input-wrap:focus-within{border-color:#3b82f6;}
.input-wrap .icon{padding:0 0 0 10px;display:flex;align-items:center;}
.input-wrap .icon svg{width:14px;height:14px;fill:#64748b;}
.input-wrap input{flex:1;padding:9px 10px;border:none;outline:none;background:transparent;color:#f1f5f9;font-size:.85rem;font-family:inherit;}
.input-wrap input::placeholder{color:#475569;}
.btn-login{width:100%;padding:10px;background:#3b82f6;color:#fff;border:none;border-radius:6px;font-size:.85rem;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;}
.btn-login:hover{background:#2563eb;}
.error-alert{background:#1c0f0f;border:1px solid #4a1c1c;border-left:3px solid #ef4444;border-radius:6px;padding:9px 11px;margin-bottom:15px;display:flex;align-items:flex-start;gap:7px;font-size:.76rem;}
.error-alert svg{width:13px;height:13px;fill:#ef4444;flex-shrink:0;margin-top:1px;}
.error-alert span{color:#fca5a5;}
.footer-bar{background:#0f172a;border-top:1px solid #1e293b;padding:11px 24px;text-align:center;font-size:.65rem;color:#475569;flex-shrink:0;}
</style>

<nav class="top-bar">
  <a href="index.php" class="logo">
    <svg viewBox="0 0 32 32"><path d="M16 2L2 9v14l14 7 14-7V9L16 2zm0 3.2l10.2 5.1-4.2 2.1L16 9.4 9.8 12.4 5.8 10.3 16 5.2zM6 13.4l4 2v4.6l-4-2v-4.6zm8 4.6l-4-2v-4.6l4 2v4.6zm2 1l-4-2v-4.6l4 2v4.6zm6-1l-4 2v-4.6l4-2v4.6zm-4-6.6l4-2 4 2-4 2-4-2zm8 3.6l-4 2v4.6l4-2v-4.6z"/></svg>
    CloudSync
  </a>
  <span class="tbadge">ADMIN</span>
  <div class="spacer"></div>
  <span class="env">admin.cloudsync.io</span>
</nav>

<div class="main">
  <div class="login-card">
    <div class="accent"></div>
    <div class="card-body">
      <div class="brand-icon">
        <svg viewBox="0 0 24 24"><path d="M19 14s-2 2.67-2 4c0 1.1.9 2 2 2s2-.9 2-2c0-1.33-2-4-2-4zm-4.5-3.5C13.5 12.5 12 14 9 14c-3 0-4.5-1.5-4.5-3.5S7 7 9 7s5.5 1.5 5.5 3.5zM3 20v-1c0-2.67 5.33-4 8-4 .75 0 1.72.07 2.72.22C12.25 16.63 12 17.88 12 19c0 1.08.26 2.1.72 3H3z"/></svg>
      </div>
      <h1>Admin Console</h1>
      <p class="sub">Authorized personnel only</p>
      <?php if (!empty($auth_err)): ?>
        <div class="error-alert">
          <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
          <span><?=htmlspecialchars($auth_err)?></span>
        </div>
      <?php endif; ?>
      <form method="post">
        <div class="form-group">
          <label>Username</label>
          <div class="input-wrap">
            <div class="icon"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div>
            <input type="text" name="username" placeholder="Username" autocomplete="off">
          </div>
        </div>
        <div class="form-group">
          <label>Password</label>
          <div class="input-wrap">
            <div class="icon"><svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/></svg></div>
            <input type="password" name="password" placeholder="Password" autocomplete="off">
          </div>
        </div>
        <button type="submit" class="btn-login">Sign In</button>
      </form>
    </div>
  </div>
</div>

<footer class="footer-bar">© 2026 CloudSync Inc. — Admin Console. Unauthorized access is prohibited.</footer>

<?php endif; ?>
</body>
</html>
