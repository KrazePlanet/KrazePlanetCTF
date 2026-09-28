<?php header('X-Content-Type-Options: nosniff'); ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driftly — Files</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f4f5fb; --surface:#ffffff; --surface-2:#f7f8fd; --line:#e7e9f4;
  --line-2:#eef0f8; --ink:#1a1c2e; --ink-2:#5b607c; --ink-3:#9aa0be;
  --brand:#6d5efc; --brand-2:#8b7cff; --brand-soft:#efedff; --brand-ink:#4b3fd6;
  --teal:#12c8b0; --amber:#ffb020; --rose:#ff6b81;
  --shadow-sm:0 1px 2px rgba(26,28,46,.05),0 4px 14px rgba(26,28,46,.05);
  --shadow-md:0 8px 30px rgba(45,40,110,.12);
  --shadow-lg:0 24px 60px rgba(45,40,110,.24);
  --radius:18px; --radius-sm:12px; --sb:264px; --topbar:64px;
}
@media(prefers-color-scheme:dark){
  :root:not([data-theme="light"]){
    --bg:#0b0c15; --surface:#14161f; --surface-2:#191c28; --line:#262a3a;
    --line-2:#20232f; --ink:#eef0fb; --ink-2:#a2a8c6; --ink-3:#6d7396;
    --brand-soft:#221f3f; --brand-ink:#a99dff;
    --shadow-sm:0 1px 2px rgba(0,0,0,.4); --shadow-md:0 8px 30px rgba(0,0,0,.5);
    --shadow-lg:0 24px 60px rgba(0,0,0,.6);
  }
}
:root[data-theme="dark"]{
  --bg:#0b0c15; --surface:#14161f; --surface-2:#191c28; --line:#262a3a;
  --line-2:#20232f; --ink:#eef0fb; --ink-2:#a2a8c6; --ink-3:#6d7396;
  --brand-soft:#221f3f; --brand-ink:#a99dff;
  --shadow-sm:0 1px 2px rgba(0,0,0,.4); --shadow-md:0 8px 30px rgba(0,0,0,.5);
  --shadow-lg:0 24px 60px rgba(0,0,0,.6);
}

*{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;}
body{
  font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
  background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased;
  overflow:hidden;
}
.mono{font-family:'JetBrains Mono',ui-monospace,Menlo,monospace;}
::selection{background:var(--brand);color:#fff;}

/* ── App shell ─────────────────────────────────────────────────────────── */
.app{display:grid;grid-template-columns:var(--sb) 1fr;grid-template-rows:var(--topbar) 1fr;
  height:100vh;}

/* ── Topbar ────────────────────────────────────────────────────────────── */
.topbar{grid-column:1 / -1;display:flex;align-items:center;gap:18px;padding:0 22px;
  background:var(--surface);border-bottom:1px solid var(--line);z-index:20;}
.logo{display:flex;align-items:center;gap:11px;text-decoration:none;color:inherit;}
.logo-mark{width:34px;height:34px;border-radius:10px;flex-shrink:0;
  background:linear-gradient(135deg,var(--brand),var(--brand-2));display:flex;align-items:center;
  justify-content:center;box-shadow:0 4px 12px rgba(109,94,252,.4);}
.logo-mark svg{width:19px;height:19px;stroke:#fff;}
.logo-name{font-size:1.02rem;font-weight:800;letter-spacing:-.02em;}
.logo-name b{color:var(--brand);}
.searchbar{flex:1;max-width:460px;margin:0 auto;display:flex;align-items:center;gap:9px;
  background:var(--surface-2);border:1px solid var(--line);border-radius:11px;padding:9px 14px;transition:.15s;}
.searchbar:focus-within{border-color:var(--brand);box-shadow:0 0 0 4px rgba(109,94,252,.12);}
.searchbar input{border:none;background:none;outline:none;flex:1;font:inherit;font-size:.85rem;color:var(--ink);}
.searchbar input::placeholder{color:var(--ink-3);}
.searchbar kbd{font-size:.65rem;color:var(--ink-3);border:1px solid var(--line);border-radius:5px;
  padding:2px 6px;font-family:'JetBrains Mono',monospace;}
.top-actions{display:flex;align-items:center;gap:10px;}
.pill-btn{width:38px;height:38px;border-radius:11px;border:1px solid var(--line);background:var(--surface-2);
  color:var(--ink-2);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.15s;}
.pill-btn:hover{background:var(--brand-soft);color:var(--brand-ink);border-color:transparent;}
.pill-btn svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:2;}
.avatar{display:flex;align-items:center;gap:9px;padding:4px 4px 4px 12px;border-radius:24px;
  border:1px solid var(--line);background:var(--surface-2);}
.avatar-name{font-size:.8rem;font-weight:600;color:var(--ink-2);}
.avatar-img{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--teal),#0aa3ce);
  color:#fff;font-size:.72rem;font-weight:700;display:flex;align-items:center;justify-content:center;}

/* ── Sidebar ───────────────────────────────────────────────────────────── */
.sidebar{background:var(--surface);border-right:1px solid var(--line);overflow-y:auto;
  display:flex;flex-direction:column;}
.new-btn{margin:18px 18px 8px;padding:11px;border:none;border-radius:13px;cursor:pointer;font:inherit;
  font-size:.85rem;font-weight:700;color:#fff;background:linear-gradient(135deg,var(--brand),var(--brand-2));
  box-shadow:0 6px 18px rgba(109,94,252,.34);display:flex;align-items:center;justify-content:center;gap:8px;transition:.15s;}
.new-btn:hover{filter:brightness(1.06);transform:translateY(-1px);}
.new-btn svg{width:16px;height:16px;stroke:#fff;stroke-width:2.4;fill:none;}
.nav{padding:8px 12px;}
.nav-item{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:11px;cursor:pointer;
  text-decoration:none;color:var(--ink-2);font-size:.86rem;font-weight:600;transition:.13s;margin-bottom:2px;}
.nav-item svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:2;flex-shrink:0;}
.nav-item:hover{background:var(--surface-2);color:var(--ink);}
.nav-item.on{background:var(--brand-soft);color:var(--brand-ink);}
.nav-item .count{margin-left:auto;font-size:.68rem;font-weight:700;color:var(--ink-3);}
.nav-sep{margin:12px 22px 6px;font-size:.66rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-3);}
.tag-item{display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:10px;cursor:pointer;
  font-size:.82rem;color:var(--ink-2);transition:.13s;}
.tag-item:hover{background:var(--surface-2);}
.dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;}
.storage{margin-top:auto;padding:16px 20px;border-top:1px solid var(--line);}
.storage-top{display:flex;justify-content:space-between;font-size:.72rem;color:var(--ink-2);font-weight:600;margin-bottom:8px;}
.storage-bar{height:7px;background:var(--surface-2);border-radius:4px;overflow:hidden;}
.storage-fill{height:100%;width:63%;border-radius:4px;background:linear-gradient(90deg,var(--brand),var(--teal));}
.storage-sub{font-size:.68rem;color:var(--ink-3);margin-top:7px;}

/* ── Main ──────────────────────────────────────────────────────────────── */
.main{overflow-y:auto;padding:26px 30px 30px;}
.crumbs{display:flex;align-items:center;gap:8px;font-size:.78rem;color:var(--ink-3);margin-bottom:18px;flex-wrap:wrap;}
.crumbs a{color:var(--ink-2);text-decoration:none;font-weight:600;}
.crumbs a:hover{color:var(--brand-ink);}
.crumbs .sep{opacity:.5;}
.crumbs .cur{color:var(--ink);font-weight:700;}

.page-head{display:flex;align-items:flex-end;gap:16px;margin-bottom:22px;flex-wrap:wrap;}
.page-title{font-size:1.5rem;font-weight:800;letter-spacing:-.02em;}
.page-sub{font-size:.82rem;color:var(--ink-2);margin-top:3px;}
.chip-live{margin-left:2px;display:inline-flex;align-items:center;gap:6px;font-size:.66rem;font-weight:800;
  color:var(--teal);background:rgba(18,200,176,.13);padding:4px 10px;border-radius:20px;letter-spacing:.03em;}
.chip-live::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--teal);animation:blip 1.5s infinite;}
@keyframes blip{0%,100%{opacity:1;}50%{opacity:.25;}}
.head-actions{margin-left:auto;display:flex;gap:9px;}
.btn{border:1px solid var(--line);background:var(--surface);color:var(--ink-2);border-radius:11px;
  padding:9px 15px;font:inherit;font-size:.82rem;font-weight:600;cursor:pointer;transition:.15s;
  display:inline-flex;align-items:center;gap:7px;}
.btn svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;}
.btn:hover{background:var(--surface-2);border-color:var(--brand);color:var(--brand-ink);}
.btn.primary{background:linear-gradient(135deg,var(--brand),var(--brand-2));color:#fff;border-color:transparent;
  box-shadow:0 6px 16px rgba(109,94,252,.3);}
.btn.primary:hover{filter:brightness(1.06);}

/* ── Loader stage ──────────────────────────────────────────────────────── */
.stage{background:
  radial-gradient(1000px 400px at 50% -8%,rgba(109,94,252,.10),transparent 70%),var(--surface);
  border:1px solid var(--line);border-radius:24px;box-shadow:var(--shadow-sm);
  min-height:400px;display:flex;align-items:center;justify-content:center;padding:48px 24px;margin-bottom:24px;
  position:relative;overflow:hidden;}
.stage::after{content:"";position:absolute;inset:0;pointer-events:none;
  background-image:radial-gradient(circle at 1px 1px,var(--line) 1px,transparent 0);
  background-size:26px 26px;opacity:.5;}
.loader{position:relative;z-index:1;text-align:center;max-width:440px;}
.orb{width:92px;height:92px;margin:0 auto 24px;border-radius:26px;position:relative;
  background:linear-gradient(135deg,var(--brand-soft),var(--surface-2));display:flex;align-items:center;
  justify-content:center;box-shadow:inset 0 0 0 1px var(--line),var(--shadow-md);}
.orb svg{width:40px;height:40px;stroke:var(--brand);fill:none;stroke-width:1.7;}
.orb.busy::before{content:"";position:absolute;inset:-4px;border-radius:30px;
  background:conic-gradient(from 0deg,transparent,var(--brand),transparent 60%);
  animation:spin 1.1s linear infinite;z-index:-1;}
.orb.busy::after{content:"";position:absolute;inset:0;border-radius:26px;background:var(--surface);}
.orb.busy svg{position:relative;z-index:2;}
@keyframes spin{to{transform:rotate(360deg);}}
.loader h2{font-size:1.28rem;font-weight:800;letter-spacing:-.02em;margin-bottom:9px;}
.loader p{font-size:.88rem;color:var(--ink-2);line-height:1.6;margin-bottom:26px;}
.loader p code{background:var(--surface-2);border:1px solid var(--line);border-radius:6px;
  padding:1px 7px;font-family:'JetBrains Mono',monospace;font-size:.82em;color:var(--brand-ink);}
.track{height:6px;background:var(--surface-2);border:1px solid var(--line);border-radius:6px;overflow:hidden;
  margin-bottom:20px;display:none;}
.track-fill{height:100%;width:0;border-radius:6px;background:linear-gradient(90deg,var(--brand),var(--teal));
  transition:width 1s ease;}
.target{display:none;font-family:'JetBrains Mono',monospace;font-size:.78rem;text-align:left;
  background:var(--surface-2);border:1px solid var(--line);border-radius:11px;padding:11px 14px;
  color:var(--ink-2);word-break:break-all;margin-bottom:22px;}
.loader-actions{display:flex;gap:11px;justify-content:center;flex-wrap:wrap;}

/* ── Meta grid ─────────────────────────────────────────────────────────── */
.meta-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;}
.meta-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:16px 18px;
  box-shadow:var(--shadow-sm);}
.meta-card .k{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-3);
  display:flex;align-items:center;gap:7px;margin-bottom:9px;}
.meta-card .k svg{width:14px;height:14px;stroke:var(--brand);fill:none;stroke-width:2;}
.meta-card .v{font-size:.96rem;font-weight:700;}

@media(max-width:920px){.app{grid-template-columns:1fr;}.sidebar{display:none;}.searchbar{display:none;}}
</style>
</head>
<body>

<div class="app">

  <!-- ── Topbar ── -->
  <header class="topbar">
    <a href="?" class="logo">
      <span class="logo-mark"><svg viewBox="0 0 24 24" fill="none"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 12l9 4 9-4M3 17l9 4 9-4"/></svg></span>
      <span class="logo-name">Drift<b>ly</b></span>
    </a>
    <div class="searchbar">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--ink-3)" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" placeholder="Search files, folders, people…">
      <kbd>⌘K</kbd>
    </div>
    <div class="top-actions">
      <button class="pill-btn" id="theme-btn" title="Theme"><svg viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg></button>
      <button class="pill-btn" title="Notifications"><svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></button>
      <div class="avatar"><span class="avatar-name">rodnt</span><span class="avatar-img">RN</span></div>
    </div>
  </header>

  <!-- ── Sidebar ── -->
  <nav class="sidebar">
    <button class="new-btn" onclick="location.href='?upload'"><svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Upload</button>
    <div class="nav">
      <a href="?" class="nav-item on"><svg viewBox="0 0 24 24"><path d="M3 7v13h18V7"/><path d="M3 7l2-4h14l2 4"/><path d="M3 7h18"/></svg> All files <span class="count">1,204</span></a>
      <a href="?recent" class="nav-item"><svg viewBox="0 0 24 24"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M13 2v7h7"/></svg> Recent</a>
      <a href="?starred" class="nav-item"><svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z"/></svg> Starred <span class="count">18</span></a>
      <a href="?shared" class="nav-item"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg> Shared</a>
      <a href="?trash" class="nav-item"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg> Trash</a>
    </div>
    <div class="nav-sep">Collections</div>
    <div class="nav" style="padding-top:2px;">
      <div class="tag-item" onclick="location.href='pub/fujitsu/'" style="cursor:pointer"><span class="dot" style="background:var(--brand)"></span> Fujitsu Library</div>
      <div class="tag-item" onclick="location.href='?assets'" style="cursor:pointer"><span class="dot" style="background:var(--teal)"></span> Product Assets</div>
      <div class="tag-item" onclick="location.href='?datasheets'" style="cursor:pointer"><span class="dot" style="background:var(--amber)"></span> Datasheets</div>
      <div class="tag-item" onclick="location.href='?archive'" style="cursor:pointer"><span class="dot" style="background:var(--rose)"></span> Archive</div>
    </div>
    <div class="storage">
      <div class="storage-top"><span>Storage</span><span>63%</span></div>
      <div class="storage-bar"><div class="storage-fill"></div></div>
      <div class="storage-sub">12.6 GB of 20 GB used</div>
    </div>
  </nav>

  <!-- ── Main ── -->
  <main class="main">
    <div class="crumbs">
      <a href="?">Home</a><span class="sep">/</span>
      <a href="pub/">pub</a><span class="sep">/</span>
      <a href="pub/fujitsu/">fujitsu</a><span class="sep">/</span>
      <a href="pub/fujitsu/fm3v2/">fm3v2</a><span class="sep">/</span>
      <a href="pub/fujitsu/fm3v2/player/">player</a><span class="sep">/</span>
      <a href="pub/fujitsu/fm3v2/player/attach.html">attach.html</a>
    </div>

    <div class="page-head">
      <div>
        <h1 class="page-title">Attachment Handler</h1>
        <div class="page-sub">FM3V2 Player · attach.html <span class="chip-live">LIVE</span></div>
      </div>
      <div class="head-actions">
        <button class="btn" onclick="location.href='?download'"><svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Download</button>
        <button class="btn" onclick="location.href='?share'"><svg viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.6" y1="13.5" x2="15.4" y2="17.5"/><line x1="15.4" y1="6.5" x2="8.6" y2="10.5"/></svg> Share</button>
        <button class="btn primary" onclick="window.location.href='pub/fujitsu/fm3v2/player/attach.html'"><svg viewBox="0 0 24 24"><path d="M15 3h6v6"/><path d="M10 14L21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg> Open</button>
      </div>
    </div>

    <section class="stage">
      <div class="loader">
        <div class="orb" id="orb"><svg id="orb-icon" viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg></div>
        <h2 id="ldr-title">Attachment Handler</h2>
        <p id="ldr-sub">Open an attachment by adding a target after the <code>?</code> in the address bar.</p>
        <div class="track" id="track"><div class="track-fill" id="track-fill"></div></div>
        <div class="target" id="target"></div>
        <div class="loader-actions">
          <button class="btn primary" onclick="location.reload()"><svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> Reload</button>
          <button class="btn" onclick="window.history.back()">← Back</button>
        </div>
      </div>
    </section>

    <div class="meta-grid">
      <div class="meta-card"><div class="k"><svg viewBox="0 0 24 24"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M13 2v7h7"/></svg> Name</div><div class="v mono" style="font-size:.86rem;">attach.html</div></div>
      <div class="meta-card"><div class="k"><svg viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg> Player</div><div class="v">Fujitsu FM3V2</div></div>
      <div class="meta-card"><div class="k"><svg viewBox="0 0 24 24"><path d="M3 7v13h18V7"/><path d="M3 7l2-4h14l2 4"/></svg> Location</div><div class="v mono" style="font-size:.78rem;">/fm3v2/player/</div></div>
      <div class="meta-card"><div class="k"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Size</div><div class="v">229 bytes</div></div>
      <div class="meta-card"><div class="k"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg> Status</div><div class="v" style="color:var(--teal)">Active</div></div>
    </div>
  </main>
</div>

<script>
// Attachment handler
function GetAttach() {
    var strSearch = document.location.search;
    strSearch = strSearch.substring(1);
    if (strSearch) {
        document.location.replace(strSearch);
    }
}

(function initUI(){
    var param = (document.location.search || '').substring(1);
    
    var safeParams = ['recent', 'starred', 'shared', 'trash', 'fujitsu', 'assets', 'datasheets', 'archive', 'fm3v2', 'download', 'share', 'upload'];
    var isSafeParam = safeParams.some(function(p) { return param === p; });
    
    if (param) {
        if (isSafeParam) {
            document.getElementById('orb').className = 'orb busy';
            document.getElementById('ldr-title').textContent = 'Navigating…';
            document.getElementById('ldr-sub').textContent = 'Loading ' + param + '…';
            document.getElementById('track').style.display = 'block';
            var t = document.getElementById('target');
            t.style.display = 'block';
            t.textContent = 'Navigation · ' + param;
            setTimeout(function(){ 
                document.getElementById('track-fill').style.width = '100%'; 
                setTimeout(function(){
                    document.getElementById('orb').className = 'orb';
                    document.getElementById('ldr-title').textContent = 'Attachment Handler';
                    document.getElementById('ldr-sub').textContent = 'Open an attachment by adding a target after the ? in the address bar.';
                    document.getElementById('track').style.display = 'none';
                    document.getElementById('track-fill').style.width = '0';
                    t.style.display = 'none';
                }, 1500);
            }, 80);
        } else {
            document.getElementById('orb').className = 'orb busy';
            document.getElementById('ldr-title').textContent = 'Opening attachment…';
            document.getElementById('ldr-sub').textContent = 'Resolving target location…';
            document.getElementById('track').style.display = 'block';
            var t = document.getElementById('target');
            t.style.display = 'block';
            t.textContent = 'Target · ' + param;
            setTimeout(function(){ document.getElementById('track-fill').style.width = '100%'; }, 80);
        }
    }

    var root = document.documentElement, tb = document.getElementById('theme-btn');
    try { var s = localStorage.getItem('driftly-theme'); if (s) root.setAttribute('data-theme', s); } catch(e){}
    tb.addEventListener('click', function(){
        var n = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', n);
        try { localStorage.setItem('driftly-theme', n); } catch(e){}
    });
})();

window.addEventListener('load', GetAttach);
</script>

</body>
</html>
