<?php
// Lab 69 — DOM XSS via document.location.replace(javascript:)
// Platform: iqcard.informatica.com | HackerOne Report #1004833
// Vulnerable file: /pub/fujitsu/fm3v2/player/attach.html
// Source: document.location.search (query string stripped of leading ?)
// Sink:   document.location.replace(strSearch) — accepts javascript: URI scheme
// Exploit: 69.php?javascript:alert(document.domain)
// Bonus:   69.php?https://evil.com  (open redirect)
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Informatica IQ Card — Attachment Viewer</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f2f5;color:#1a2a4a;min-height:100vh;display:flex;flex-direction:column;}

/* ── Header ───────────────────────────────────────────────────────────────── */
.header{background:#1b3a6b;height:54px;display:flex;align-items:center;padding:0 24px;box-shadow:0 2px 8px rgba(0,0,0,.3);flex-shrink:0;z-index:10;}
.header-logo{display:flex;align-items:center;gap:10px;text-decoration:none;margin-right:28px;}
.header-mark{width:30px;height:30px;background:#c8102e;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:900;color:#fff;letter-spacing:-.02em;flex-shrink:0;}
.header-brand{color:#fff;font-size:.92rem;font-weight:800;letter-spacing:-.01em;}
.header-brand span{color:#7ab3e8;font-weight:400;}
.header-divider{width:1px;height:24px;background:rgba(255,255,255,.2);margin:0 18px;}
.header-product{color:rgba(255,255,255,.7);font-size:.78rem;font-weight:500;}
.header-nav{display:flex;gap:4px;flex:1;}
.header-nav a{color:rgba(255,255,255,.65);font-size:.72rem;font-weight:500;text-decoration:none;padding:6px 10px;border-radius:3px;transition:background .15s,color .15s;}
.header-nav a:hover{background:rgba(255,255,255,.1);color:#fff;}
.header-right{display:flex;gap:8px;align-items:center;}
.hdr-user{display:flex;align-items:center;gap:7px;color:rgba(255,255,255,.75);font-size:.72rem;}
.hdr-avatar{width:26px;height:26px;background:#c8102e;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:#fff;}
.hdr-btn{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:4px;color:rgba(255,255,255,.8);font-size:.7rem;font-weight:600;padding:5px 12px;cursor:pointer;font-family:inherit;}
.hdr-btn:hover{background:rgba(255,255,255,.18);}

/* ── Sub-header / breadcrumb ──────────────────────────────────────────────── */
.subheader{background:#fff;border-bottom:1px solid #dce3ef;padding:9px 24px;display:flex;align-items:center;gap:6px;font-size:.72rem;color:#5a6a8a;}
.subheader a{color:#1b3a6b;text-decoration:none;font-weight:500;}
.subheader a:hover{text-decoration:underline;}
.subheader-sep{color:#b0bdd0;}
.subheader-current{color:#5a6a8a;}
.subheader-path{margin-left:auto;font-family:monospace;font-size:.67rem;color:#b0bdd0;background:#f5f7fa;padding:2px 8px;border-radius:3px;border:1px solid #e4eaf4;}

/* ── Main layout ──────────────────────────────────────────────────────────── */
.layout{display:flex;flex:1;overflow:hidden;}

/* ── Sidebar ──────────────────────────────────────────────────────────────── */
.sidebar{width:240px;background:#fff;border-right:1px solid #dce3ef;flex-shrink:0;display:flex;flex-direction:column;overflow-y:auto;}
.sidebar-hdr{padding:14px 16px 10px;border-bottom:1px solid #dce3ef;}
.sidebar-hdr-title{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#5a6a8a;}
.sidebar-search{display:flex;align-items:center;gap:6px;margin-top:8px;background:#f5f7fa;border:1px solid #dce3ef;border-radius:5px;padding:5px 10px;}
.sidebar-search input{border:none;background:transparent;font-size:.74rem;color:#1a2a4a;outline:none;flex:1;font-family:inherit;}
.sidebar-search input::placeholder{color:#b0bdd0;}
.sidebar-section{padding:10px 16px 4px;font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#b0bdd0;}
.card-item{display:flex;align-items:center;gap:10px;padding:9px 16px;cursor:pointer;border-left:3px solid transparent;transition:background .12s;text-decoration:none;color:inherit;}
.card-item:hover{background:#f5f7fa;}
.card-item.active{background:#eef3fb;border-left-color:#1b3a6b;}
.card-thumb{width:34px;height:28px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0;}
.card-info{flex:1;min-width:0;}
.card-name{font-size:.76rem;font-weight:600;color:#1a2a4a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.card-meta{font-size:.65rem;color:#8a9ab8;margin-top:1px;}
.card-badge{font-size:.6rem;font-weight:700;padding:1px 5px;border-radius:3px;background:#eef3fb;color:#1b3a6b;flex-shrink:0;}

/* ── Main content ─────────────────────────────────────────────────────────── */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;}

/* ── Viewer toolbar ───────────────────────────────────────────────────────── */
.viewer-toolbar{background:#fff;border-bottom:1px solid #dce3ef;padding:10px 20px;display:flex;align-items:center;gap:10px;}
.viewer-title{font-size:.84rem;font-weight:700;color:#1a2a4a;flex:1;}
.viewer-title span{color:#5a6a8a;font-weight:400;font-size:.76rem;margin-left:6px;}
.toolbar-btn{background:#f5f7fa;border:1px solid #dce3ef;border-radius:4px;padding:5px 12px;font-size:.72rem;color:#5a6a8a;cursor:pointer;font-family:inherit;transition:all .15s;}
.toolbar-btn:hover{background:#eef3fb;border-color:#bbd0ed;color:#1b3a6b;}
.toolbar-btn.primary{background:#1b3a6b;color:#fff;border-color:#1b3a6b;}
.toolbar-btn.primary:hover{background:#15305a;}

/* ── Attachment loader area ───────────────────────────────────────────────── */
.viewer-body{flex:1;display:flex;align-items:center;justify-content:center;padding:40px;background:#f8f9fb;}
.attach-panel{background:#fff;border:1px solid #dce3ef;border-radius:10px;padding:40px 48px;text-align:center;min-width:360px;box-shadow:0 2px 12px rgba(27,58,107,.06);}
.attach-icon{width:60px;height:60px;border-radius:14px;background:#eef3fb;display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin:0 auto 18px;}
.attach-status{font-size:1rem;font-weight:700;color:#1a2a4a;margin-bottom:6px;}
.attach-sub{font-size:.78rem;color:#8a9ab8;margin-bottom:20px;line-height:1.5;}
.attach-spinner{width:32px;height:32px;border:3px solid #eef3fb;border-top-color:#1b3a6b;border-radius:50%;margin:0 auto 16px;display:none;}
.attach-spinner.spin{display:block;animation:spin .7s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}
.attach-progress{height:4px;background:#eef3fb;border-radius:2px;overflow:hidden;margin-bottom:18px;display:none;}
.attach-progress-bar{height:100%;background:linear-gradient(90deg,#1b3a6b,#4a7abf);border-radius:2px;width:0;transition:width .8s ease;}
.attach-filepath{font-family:monospace;font-size:.72rem;background:#f5f7fa;border:1px solid #e4eaf4;border-radius:4px;padding:6px 12px;color:#5a6a8a;text-align:left;word-break:break-all;margin-bottom:14px;display:none;}
.attach-actions{display:flex;gap:8px;justify-content:center;margin-top:8px;}
.attach-btn{background:#1b3a6b;color:#fff;border:none;border-radius:5px;padding:9px 20px;font-size:.78rem;font-weight:700;cursor:pointer;font-family:inherit;transition:opacity .15s;}
.attach-btn:hover{opacity:.88;}
.attach-btn.secondary{background:#f5f7fa;color:#5a6a8a;border:1px solid #dce3ef;}
.attach-btn.secondary:hover{background:#eef3fb;}

/* ── Info cards row ───────────────────────────────────────────────────────── */
.info-row{display:flex;gap:12px;padding:16px 20px;background:#fff;border-top:1px solid #dce3ef;}
.info-card{flex:1;background:#f5f7fa;border:1px solid #dce3ef;border-radius:6px;padding:12px 14px;}
.info-card-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#8a9ab8;margin-bottom:4px;}
.info-card-val{font-size:.8rem;font-weight:600;color:#1a2a4a;}

/* ── Footer ───────────────────────────────────────────────────────────────── */
.footer{background:#fff;border-top:1px solid #dce3ef;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;font-size:.68rem;color:#b0bdd0;flex-shrink:0;}
.footer a{color:#b0bdd0;text-decoration:none;}
.footer a:hover{color:#5a6a8a;}

@media(max-width:768px){.sidebar{display:none;}.subheader-path{display:none;}}
</style>
</head>
<body>

<!-- Header -->
<header class="header">
  <a href="#" class="header-logo">
    <div class="header-mark">i</div>
    <span class="header-brand">Informatica <span>IQ Card</span></span>
  </a>
  <div class="header-divider"></div>
  <span class="header-product">Document Viewer</span>
  <nav class="header-nav">
    <a href="#">Library</a>
    <a href="#">Shared</a>
    <a href="#">Recent</a>
    <a href="#">Admin</a>
  </nav>
  <div class="header-right">
    <div class="hdr-user">
      <div class="hdr-avatar">RN</div>
      <span>rodnt</span>
    </div>
    <button class="hdr-btn">Sign Out</button>
  </div>
</header>

<!-- Breadcrumb / sub-header -->
<div class="subheader">
  <a href="#">Home</a><span class="subheader-sep">›</span>
  <a href="#">IQ Cards</a><span class="subheader-sep">›</span>
  <a href="#">Fujitsu Library</a><span class="subheader-sep">›</span>
  <span class="subheader-current">attach.html</span>
  <span class="subheader-path">/pub/fujitsu/fm3v2/player/attach.html</span>
</div>

<!-- Layout -->
<div class="layout">

  <!-- Sidebar -->
  <nav class="sidebar">
    <div class="sidebar-hdr">
      <div class="sidebar-hdr-title">IQ Card Library</div>
      <div class="sidebar-search">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#b0bdd0" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Search cards...">
      </div>
    </div>
    <div class="sidebar-section">Fujitsu Collection</div>
    <a href="?player/attach.html" class="card-item active">
      <div class="card-thumb" style="background:#eef3fb;">📄</div>
      <div class="card-info">
        <div class="card-name">FM3V2 Player</div>
        <div class="card-meta">attach.html · 229 B</div>
      </div>
      <span class="card-badge">HTML</span>
    </a>
    <a href="?demo_v2.mp4" class="card-item">
      <div class="card-thumb" style="background:#fff4f0;">🎬</div>
      <div class="card-info">
        <div class="card-name">Product Demo</div>
        <div class="card-meta">demo_v2.mp4 · 14.2 MB</div>
      </div>
      <span class="card-badge">MP4</span>
    </a>
    <a href="?datasheet_q3.pdf" class="card-item">
      <div class="card-thumb" style="background:#f0fdf4;">📊</div>
      <div class="card-info">
        <div class="card-name">Q3 Data Sheet</div>
        <div class="card-meta">datasheet_q3.pdf · 1.8 MB</div>
      </div>
      <span class="card-badge">PDF</span>
    </a>
    <a href="?spec_rev2.docx" class="card-item">
      <div class="card-thumb" style="background:#fffbeb;">📋</div>
      <div class="card-info">
        <div class="card-name">Spec Sheet Rev2</div>
        <div class="card-meta">spec_rev2.docx · 340 KB</div>
      </div>
      <span class="card-badge">DOC</span>
    </a>
    <div class="sidebar-section">Informatica Assets</div>
    <a href="?mdm_2020.pdf" class="card-item">
      <div class="card-thumb" style="background:#fdf0f3;">📈</div>
      <div class="card-info">
        <div class="card-name">MDM Overview</div>
        <div class="card-meta">mdm_2020.pdf · 4.1 MB</div>
      </div>
      <span class="card-badge">PDF</span>
    </a>
    <a href="?api_ref_v3.html" class="card-item">
      <div class="card-thumb" style="background:#eef3fb;">🗂</div>
      <div class="card-info">
        <div class="card-name">API Reference</div>
        <div class="card-meta">api_ref_v3.html · 88 KB</div>
      </div>
      <span class="card-badge">HTML</span>
    </a>
  </nav>

  <!-- Main viewer -->
  <main class="main">
    <div class="viewer-toolbar">
      <span class="viewer-title">attach.html <span>— FM3V2 Attachment Handler</span></span>
      <button class="toolbar-btn">⬇ Download</button>
      <button class="toolbar-btn">🔗 Share</button>
      <button class="toolbar-btn primary">Open</button>
    </div>

    <div class="viewer-body">
      <div class="attach-panel">
        <div class="attach-icon" id="attach-icon">📎</div>
        <div class="attach-spinner" id="attach-spinner"></div>
        <div class="attach-status" id="attach-status">Attachment Handler</div>
        <div class="attach-sub" id="attach-sub">This page loads an external attachment via the URL query string.<br>Provide a target path after the <code style="background:#f5f7fa;padding:1px 4px;border-radius:3px;font-size:.85em;">?</code> to open it.</div>
        <div class="attach-progress" id="attach-progress">
          <div class="attach-progress-bar" id="attach-progress-bar"></div>
        </div>
        <div class="attach-filepath" id="attach-filepath"></div>
        <div class="attach-actions">
          <button class="attach-btn secondary" onclick="window.history.back()">← Back</button>
        </div>
      </div>
    </div>

    <div class="info-row">
      <div class="info-card">
        <div class="info-card-label">Source File</div>
        <div class="info-card-val" style="font-family:monospace;font-size:.72rem;">attach.html</div>
      </div>
      <div class="info-card">
        <div class="info-card-label">Player</div>
        <div class="info-card-val">Fujitsu FM3V2</div>
      </div>
      <div class="info-card">
        <div class="info-card-label">Path</div>
        <div class="info-card-val" style="font-family:monospace;font-size:.68rem;">/pub/fujitsu/fm3v2/player/</div>
      </div>
      <div class="info-card">
        <div class="info-card-label">Size</div>
        <div class="info-card-val">229 bytes</div>
      </div>
    </div>
  </main>
</div>

<!-- Footer -->
<footer class="footer">
  <span>© 2020 Informatica Corporation. All rights reserved.</span>
  <span>
    <a href="#">Privacy</a> · <a href="#">Terms</a> · <a href="#">Security</a> ·
    <a href="https://hackerone.com/reports/1004833" target="_blank">Report #1004833</a>
  </span>
</footer>

<script>
// ============================================================
//  Fujitsu FM3V2 player — attach.html
//  Exact code from iqcard.informatica.com as reported in #1004833
//
//  Source: document.location.search
//  Sink:   document.location.replace()
//
//  ⚠ document.location.replace() accepts the javascript: URI
//    scheme — the browser evaluates it as JavaScript in the
//    current page's execution context.
//
//  Exploit 1 — DOM XSS:
//    69.php?javascript:alert(document.domain)
//
//  Exploit 2 — Open Redirect:
//    69.php?https://evil.com
// ============================================================

function GetAttach() {
    var strSearch = document.location.search;   // e.g. "?javascript:alert(1)"
    strSearch = strSearch.substring(1);         // strips leading ? → "javascript:alert(1)"

    if (strSearch) {
        // ⚠ VULNERABLE SINK — mirrors exact code from attach.html
        // Passes raw user input to location.replace() with no validation.
        // javascript: URIs execute immediately in the current browsing context.
        document.location.replace(strSearch);
    }
}

// ── UI: show loading state while GetAttach() fires ──────────────────────────
(function initUI() {
    var search = document.location.search;
    var param  = search ? search.substring(1) : '';

    if (!param) {
        return; // no param — keep default "Attachment Handler" state
    }

    // Show "Opening..." state
    document.getElementById('attach-icon').style.display   = 'none';
    document.getElementById('attach-spinner').className    = 'attach-spinner spin';
    document.getElementById('attach-status').textContent   = 'Opening attachment\u2026';
    document.getElementById('attach-sub').textContent      = 'Resolving target location via FM3V2 player\u2026';
    document.getElementById('attach-progress').style.display = 'block';
    document.getElementById('attach-filepath').style.display = 'block';
    document.getElementById('attach-filepath').textContent = 'Target: ' + param;

    // Animate progress bar
    setTimeout(function() {
        document.getElementById('attach-progress-bar').style.width = '100%';
    }, 80);
})();

// ── Fire the vulnerable function on load (exact original: <BODY onload='GetAttach()'>) ──
window.addEventListener('load', GetAttach);
</script>

</body>
</html>
