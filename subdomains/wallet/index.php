<?php
// Lab 66 — DOM XSS via URL Hash Fragment on MyCrypto
// Platform: MyCrypto (mycrypto.com) | HackerOne Report #324303
// Vulnerability: window.location.hash content (after route name) injected into
//   the "connected successfully" status bar via innerHTML without sanitization.
//   Browsers do NOT encode < > in hash fragments — payload executes directly.
// Exploit: index.php#send-transaction<img src=x onerror=alert(document.domain)>
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MyCrypto — Ethereum Wallet</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#163347;color:#d4dde8;min-height:100vh;display:flex;flex-direction:column;}

/* ── Top bar ──────────────────────────────────────────────────────────────── */
.topbar{background:#163347;border-bottom:1px solid #1e4060;height:52px;display:flex;align-items:center;padding:0 20px;gap:16px;flex-shrink:0;z-index:10;}
.topbar-logo{display:flex;align-items:center;gap:8px;text-decoration:none;}
.topbar-gem{width:28px;height:28px;background:linear-gradient(135deg,#00b09e,#008f80);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:900;color:#fff;flex-shrink:0;}
.topbar-name{color:#e0e8f0;font-weight:800;font-size:1rem;letter-spacing:-.01em;}
.topbar-name span{color:#00b09e;}
.topbar-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.net-badge{display:flex;align-items:center;gap:5px;background:rgba(0,176,158,.12);border:1px solid rgba(0,176,158,.3);border-radius:20px;padding:4px 10px;font-size:.72rem;color:#00b09e;font-weight:600;}
.net-dot{width:6px;height:6px;border-radius:50%;background:#00b09e;animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.4;}}
.topbar-btn{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:6px;padding:5px 12px;font-size:.75rem;color:#a0b3c4;cursor:pointer;font-family:inherit;}
.topbar-btn:hover{background:rgba(255,255,255,.1);color:#e0e8f0;}

/* ── Connection status strip ──────────────────────────────────────────────── */
.conn-strip{background:#0e2535;border-bottom:1px solid #1a3a52;padding:7px 20px;font-size:.76rem;min-height:32px;display:flex;align-items:center;gap:8px;}
.conn-ok{color:#00b09e;font-weight:600;}
.conn-provider{color:#7a9ab0;}

/* ── Layout ───────────────────────────────────────────────────────────────── */
.layout{display:flex;flex:1;overflow:hidden;}

/* ── Sidebar ──────────────────────────────────────────────────────────────── */
.sidebar{width:200px;background:#0e2535;border-right:1px solid #1a3a52;flex-shrink:0;padding:16px 0;display:flex;flex-direction:column;}
.sidebar-section{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#4a7080;padding:16px 16px 6px;}
.sidebar-link{display:flex;align-items:center;gap:10px;padding:9px 16px;font-size:.8rem;color:#8aacbf;text-decoration:none;cursor:pointer;transition:background .15s,color .15s;border-left:3px solid transparent;}
.sidebar-link:hover{background:rgba(0,176,158,.08);color:#c0d8e8;}
.sidebar-link.active{background:rgba(0,176,158,.12);color:#00b09e;border-left-color:#00b09e;}
.sidebar-link svg{width:16px;height:16px;flex-shrink:0;opacity:.8;}
.sidebar-bottom{margin-top:auto;padding:12px 0;border-top:1px solid #1a3a52;}

/* ── Main content ─────────────────────────────────────────────────────────── */
.main{flex:1;overflow-y:auto;background:#1c2a3a;}
.view{display:none;padding:28px 32px;}
.view.active{display:block;}

/* ── Dashboard view ───────────────────────────────────────────────────────── */
.dash-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;}
.card{background:#163347;border:1px solid #1e4060;border-radius:10px;padding:20px;}
.card-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#4a7080;margin-bottom:6px;}
.card-value{font-size:1.6rem;font-weight:800;color:#e0e8f0;line-height:1;}
.card-sub{font-size:.72rem;color:#4a7080;margin-top:4px;}
.card-teal{color:#00b09e;}
.card-red{color:#e05555;}
.tx-list{background:#163347;border:1px solid #1e4060;border-radius:10px;overflow:hidden;}
.tx-hdr{padding:14px 20px;border-bottom:1px solid #1e4060;font-size:.8rem;font-weight:700;color:#a0b8c8;}
.tx-row{display:flex;align-items:center;padding:12px 20px;border-bottom:1px solid rgba(30,64,96,.5);gap:14px;}
.tx-row:last-child{border-bottom:none;}
.tx-icon{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0;}
.tx-icon.out{background:rgba(224,85,85,.15);color:#e05555;}
.tx-icon.in{background:rgba(0,176,158,.15);color:#00b09e;}
.tx-info{flex:1;}
.tx-type{font-size:.78rem;font-weight:600;color:#c0d4e0;margin-bottom:2px;}
.tx-addr{font-size:.67rem;color:#4a7080;font-family:monospace;}
.tx-amount{font-size:.82rem;font-weight:700;}
.tx-amount.out{color:#e05555;}
.tx-amount.in{color:#00b09e;}
.tx-time{font-size:.67rem;color:#4a7080;}

/* ── Send view ────────────────────────────────────────────────────────────── */
.view-title{font-size:1.1rem;font-weight:800;color:#e0e8f0;margin-bottom:4px;}
.view-sub{font-size:.78rem;color:#4a7080;margin-bottom:24px;}
.form-group{margin-bottom:18px;}
.form-label{display:block;font-size:.75rem;font-weight:600;color:#7a9ab0;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;}
.form-input{width:100%;background:#0e2535;border:1px solid #1e4060;border-radius:7px;padding:10px 13px;font-size:.84rem;color:#d4dde8;font-family:monospace;transition:border-color .15s;}
.form-input:focus{outline:none;border-color:#00b09e;}
.form-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;}
.send-btn{background:linear-gradient(135deg,#00b09e,#007d70);color:#fff;border:none;border-radius:8px;padding:12px 28px;font-size:.88rem;font-weight:700;cursor:pointer;font-family:inherit;margin-top:8px;transition:opacity .15s;}
.send-btn:hover{opacity:.9;}
.fee-info{background:#0e2535;border:1px solid #1e4060;border-radius:8px;padding:14px 16px;margin-top:16px;font-size:.75rem;color:#4a7080;}
.fee-row{display:flex;justify-content:space-between;padding:3px 0;}
.fee-val{color:#a0b8c8;}

/* ── Receive view ─────────────────────────────────────────────────────────── */
.qr-mock{width:140px;height:140px;background:#0e2535;border:1px solid #1e4060;border-radius:8px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;}
.qr-grid{display:grid;grid-template-columns:repeat(10,12px);gap:2px;padding:8px;}
.qr-cell{width:12px;height:12px;border-radius:1px;}
.addr-box{background:#0e2535;border:1px solid #1e4060;border-radius:8px;padding:12px 14px;font-family:monospace;font-size:.75rem;color:#00b09e;word-break:break-all;margin-bottom:14px;}
.copy-btn{background:rgba(0,176,158,.12);border:1px solid rgba(0,176,158,.3);border-radius:6px;padding:7px 14px;font-size:.75rem;color:#00b09e;cursor:pointer;font-family:inherit;}
.copy-btn:hover{background:rgba(0,176,158,.2);}

/* ── Swap / View Wallet ───────────────────────────────────────────────────── */
.placeholder-card{background:#163347;border:1px solid #1e4060;border-radius:10px;padding:40px;text-align:center;}
.placeholder-icon{font-size:2.5rem;margin-bottom:14px;opacity:.6;}
.placeholder-title{font-size:.9rem;font-weight:700;color:#a0b8c8;margin-bottom:6px;}
.placeholder-sub{font-size:.76rem;color:#4a7080;}

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media(max-width:700px){
    .sidebar{width:56px;}
    .sidebar-link span,.sidebar-section,.topbar-name{display:none;}
    .dash-grid{grid-template-columns:1fr;}
    .form-row{grid-template-columns:1fr;}
    .view{padding:16px;}
}
</style>
</head>
<body>

<!-- Top bar -->
<header class="topbar">
  <a href="#dashboard" class="topbar-logo">
    <div class="topbar-gem">M</div>
    <span class="topbar-name">My<span>Crypto</span></span>
  </a>
  <div class="topbar-right">
    <div class="net-badge">
      <span class="net-dot"></span>
      <span>Ethereum (ETH)</span>
    </div>
    <button class="topbar-btn">Testnet</button>
    <button class="topbar-btn">Settings</button>
  </div>
</header>

<!-- Connection status strip — ⚠ VULNERABLE innerHTML SINK -->
<div class="conn-strip" id="conn-strip">
  <span class="conn-ok">✓ Connected</span>
  <span class="conn-provider">Provider: Infura · Ethereum Mainnet · Block #17,841,209</span>
</div>

<!-- Main layout -->
<div class="layout">

  <!-- Sidebar -->
  <nav class="sidebar">
    <div class="sidebar-section">Wallet</div>
    <a class="sidebar-link" href="#dashboard" data-view="dashboard">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      <span>Dashboard</span>
    </a>
    <a class="sidebar-link" href="#send-transaction" data-view="send-transaction">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      <span>Send ETH</span>
    </a>
    <a class="sidebar-link" href="#receive" data-view="receive">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="8 17 12 21 16 17"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.88 18.09A5 5 0 0018 9h-1.26A8 8 0 103 16.29"/></svg>
      <span>Receive</span>
    </a>
    <a class="sidebar-link" href="#swap" data-view="swap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
      <span>Swap</span>
    </a>
    <div class="sidebar-section">Advanced</div>
    <a class="sidebar-link" href="#view-wallet" data-view="view-wallet">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      <span>View Wallet</span>
    </a>
    <a class="sidebar-link" href="#contracts" data-view="contracts">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
      <span>Contracts</span>
    </a>
    <div class="sidebar-bottom">
      <a class="sidebar-link" href="https://hackerone.com/reports/324303" target="_blank">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>Report #324303</span>
      </a>
    </div>
  </nav>

  <!-- Main content -->
  <main class="main">

    <!-- Dashboard -->
    <div class="view" id="view-dashboard">
      <div class="dash-grid">
        <div class="card">
          <div class="card-label">ETH Balance</div>
          <div class="card-value">4.8271 <span style="font-size:.9rem;color:#4a7080;">ETH</span></div>
          <div class="card-sub card-teal">≈ $8,412.60 USD</div>
        </div>
        <div class="card">
          <div class="card-label">Portfolio Value</div>
          <div class="card-value card-teal">$11,284</div>
          <div class="card-sub" style="color:#4a7080;">+$234 today <span style="color:#00b09e;">+2.1%</span></div>
        </div>
      </div>
      <div class="tx-list">
        <div class="tx-hdr">Recent Transactions</div>
        <div class="tx-row">
          <div class="tx-icon out">↑</div>
          <div class="tx-info">
            <div class="tx-type">Sent ETH</div>
            <div class="tx-addr">To: 0xd8dA6BF26964aF9D7eEd9e03E53415D37aA96045</div>
          </div>
          <div>
            <div class="tx-amount out">-1.2000 ETH</div>
            <div class="tx-time">2 hours ago</div>
          </div>
        </div>
        <div class="tx-row">
          <div class="tx-icon in">↓</div>
          <div class="tx-info">
            <div class="tx-type">Received ETH</div>
            <div class="tx-addr">From: 0xAb5801a7D398351b8bE11C439e05C5B3259aeC9B</div>
          </div>
          <div>
            <div class="tx-amount in">+2.5000 ETH</div>
            <div class="tx-time">Yesterday</div>
          </div>
        </div>
        <div class="tx-row">
          <div class="tx-icon out">↑</div>
          <div class="tx-info">
            <div class="tx-type">Contract Interaction</div>
            <div class="tx-addr">0xC02aaA39b223FE8D0A0e5C4F27eAD9083C756Cc2</div>
          </div>
          <div>
            <div class="tx-amount out">-0.0084 ETH</div>
            <div class="tx-time">3 days ago</div>
          </div>
        </div>
        <div class="tx-row">
          <div class="tx-icon in">↓</div>
          <div class="tx-info">
            <div class="tx-type">Received ETH</div>
            <div class="tx-addr">From: 0x71C7656EC7ab88b098defB751B7401B5f6d8976F</div>
          </div>
          <div>
            <div class="tx-amount in">+0.5500 ETH</div>
            <div class="tx-time">5 days ago</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Send Transaction -->
    <div class="view" id="view-send-transaction">
      <div class="view-title">Send Transaction</div>
      <div class="view-sub">Send ETH or tokens to any Ethereum address.</div>
      <div class="form-group">
        <label class="form-label">Recipient Address</label>
        <input class="form-input" type="text" placeholder="0x..." value="">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Amount (ETH)</label>
          <input class="form-input" type="number" placeholder="0.00" step="0.0001">
        </div>
        <div class="form-group">
          <label class="form-label">Gas Price (Gwei)</label>
          <input class="form-input" type="number" placeholder="20" value="20">
        </div>
        <div class="form-group">
          <label class="form-label">Gas Limit</label>
          <input class="form-input" type="number" placeholder="21000" value="21000">
        </div>
      </div>
      <button class="send-btn">Send Transaction →</button>
      <div class="fee-info">
        <div class="fee-row"><span>Estimated Fee</span><span class="fee-val">0.000420 ETH (~$0.73)</span></div>
        <div class="fee-row"><span>Transaction Speed</span><span class="fee-val">Standard (~2 min)</span></div>
        <div class="fee-row"><span>Network</span><span class="fee-val">Ethereum Mainnet</span></div>
      </div>
    </div>

    <!-- Receive -->
    <div class="view" id="view-receive">
      <div class="view-title">Receive ETH</div>
      <div class="view-sub">Share your address to receive Ethereum or tokens.</div>
      <div class="qr-mock">
        <svg width="100" height="100" viewBox="0 0 10 10" style="image-rendering:pixelated;">
          <rect x="0" y="0" width="3" height="3" fill="#00b09e"/><rect x="1" y="1" width="1" height="1" fill="#0e2535"/>
          <rect x="4" y="0" width="1" height="1" fill="#00b09e"/><rect x="6" y="0" width="1" height="1" fill="#00b09e"/>
          <rect x="0" y="4" width="1" height="1" fill="#00b09e"/><rect x="2" y="4" width="2" height="1" fill="#00b09e"/>
          <rect x="7" y="0" width="3" height="3" fill="#00b09e"/><rect x="8" y="1" width="1" height="1" fill="#0e2535"/>
          <rect x="5" y="4" width="1" height="1" fill="#00b09e"/><rect x="7" y="4" width="1" height="1" fill="#00b09e"/>
          <rect x="0" y="7" width="3" height="3" fill="#00b09e"/><rect x="1" y="8" width="1" height="1" fill="#0e2535"/>
          <rect x="4" y="6" width="2" height="1" fill="#00b09e"/><rect x="7" y="7" width="1" height="2" fill="#00b09e"/>
          <rect x="4" y="8" width="1" height="2" fill="#00b09e"/><rect x="6" y="9" width="1" height="1" fill="#00b09e"/>
          <rect x="9" y="5" width="1" height="2" fill="#00b09e"/>
        </svg>
      </div>
      <div class="addr-box">0x71C7656EC7ab88b098defB751B7401B5f6d8976F</div>
      <button class="copy-btn">Copy Address</button>
    </div>

    <!-- Swap -->
    <div class="view" id="view-swap">
      <div class="view-title">Swap Tokens</div>
      <div class="view-sub">Exchange tokens instantly via DEX aggregation.</div>
      <div class="placeholder-card">
        <div class="placeholder-icon">🔄</div>
        <div class="placeholder-title">Swap Coming Soon</div>
        <div class="placeholder-sub">DEX aggregation via 0x Protocol — connect wallet to continue.</div>
      </div>
    </div>

    <!-- View Wallet -->
    <div class="view" id="view-view-wallet">
      <div class="view-title">View Wallet Info</div>
      <div class="view-sub">Check any Ethereum address without connecting your wallet.</div>
      <div class="form-group">
        <label class="form-label">Ethereum Address or ENS Name</label>
        <input class="form-input" type="text" placeholder="0x... or vitalik.eth">
      </div>
      <button class="send-btn">View →</button>
    </div>

    <!-- Contracts -->
    <div class="view" id="view-contracts">
      <div class="view-title">Interact with Contracts</div>
      <div class="view-sub">Call read/write functions on any verified smart contract.</div>
      <div class="placeholder-card">
        <div class="placeholder-icon">📋</div>
        <div class="placeholder-title">Contract Interaction</div>
        <div class="placeholder-sub">Paste a contract ABI and address to interact with it directly on-chain.</div>
      </div>
    </div>

  </main>
</div>

<script>
// ============================================================
//  MyCrypto SPA router + vulnerable status bar
//  Mirrors the logic in mycrypto-master.js (HackerOne #324303)
//
//  Vulnerability: window.location.hash is split on the route
//  name. Everything AFTER the route name is appended to the
//  connection status bar via innerHTML WITHOUT sanitization.
//
//  Browsers do NOT encode < > in hash fragments, so the
//  payload reaches innerHTML as raw HTML.
// ============================================================

var ROUTES = [
    'send-transaction',
    'view-wallet',
    'receive',
    'swap',
    'contracts',
    'dashboard'
];

function getView(name) {
    // Maps route name to view element id
    var id = 'view-' + name;
    return document.getElementById(id);
}

function activateRoute(route, statusExtra) {
    // Hide all views
    ROUTES.forEach(function(r) {
        var v = getView(r);
        if (v) v.classList.remove('active');
    });

    // Show matched view
    var target = getView(route);
    if (target) target.classList.add('active');

    // Update sidebar active state
    document.querySelectorAll('.sidebar-link').forEach(function(el) {
        el.classList.toggle('active', el.getAttribute('data-view') === route);
    });

    // ── VULNERABLE SINK (mirrors mycrypto-master.js line 4072) ──────────────
    // statusExtra is the unsanitized remainder of window.location.hash
    // after the route prefix. Injected into innerHTML without encoding.
    if (statusExtra) {
        document.getElementById('conn-strip').innerHTML =
            '<span class="conn-ok">✓ Connected successfully</span>' +
            '<span class="conn-provider"> · ' + statusExtra + '</span>';
    } else {
        document.getElementById('conn-strip').innerHTML =
            '<span class="conn-ok">✓ Connected</span>' +
            '<span class="conn-provider"> Provider: Infura · Ethereum Mainnet · Block #17,841,209</span>';
    }
}

function router() {
    // Read the raw hash (browsers never encode < > in fragments)
    var hash = window.location.hash.substring(1); // strips leading #

    var matchedRoute  = 'dashboard';
    var statusExtra   = '';

    // Find which route the hash starts with
    for (var i = 0; i < ROUTES.length; i++) {
        var route = ROUTES[i];
        if (hash.indexOf(route) === 0) {
            matchedRoute = route;
            // ⚠ Everything after the route name — NOT sanitized
            statusExtra  = decodeURIComponent(hash.substring(route.length));
            break;
        }
    }

    activateRoute(matchedRoute, statusExtra);
}

// Run on load and on hash change
window.addEventListener('hashchange', router);
router();
</script>

</body>
</html>
