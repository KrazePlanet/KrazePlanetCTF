<?php
// Lab 1204 — Stored HTML Tag Injection via Profile Name in Snippets Page
// Platform: gitlab.com | HackerOne Report #358001
// Vulnerability: Profile "Full Name" stored unsanitized; rendered raw in author byline on public Snippets page
// Confirmed working tags: <a href>, <img src>, <h1>, <div>, <b>, <br>
// Payloads:
//   <a href="http://evil.com/gitlab-phishing/">rootbakar_</a>
//   </br><h1>HACKED BY TALAOHU28</h1><img src="http://evil.com/img.jpg"></br>

session_start();

// ── Handle Profile Save ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_profile') {
    // ⚠ VULNERABLE — Full Name stored raw, no sanitization
    $_SESSION['profile_name']     = $_POST['full_name']  ?? '';
    $_SESSION['profile_username'] = $_POST['username']   ?? 'rootbakar_';
    $_SESSION['profile_bio']      = $_POST['bio']        ?? '';
    header('Location: ' . $_SERVER['PHP_SELF'] . '?view=settings&saved=1');
    exit;
}

$view     = $_GET['view'] ?? 'snippets';
$saved    = isset($_GET['saved']);

// ── Profile data (unsanitized for sink, safe for non-sink display) ──────────
$name     = $_SESSION['profile_name']     ?? 'Talaohu28';
$username = htmlspecialchars($_SESSION['profile_username'] ?? 'rootbakar_', ENT_QUOTES, 'UTF-8');
$bio      = htmlspecialchars($_SESSION['profile_bio']      ?? 'Security researcher. Bug hunter.', ENT_QUOTES, 'UTF-8');
$nameRaw  = $name; // ← used in the vulnerable sink
$nameSafe = htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); // ← used in non-sink places
$initial  = strtoupper(substr(strip_tags($name), 0, 1)) ?: 'T';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Snippet: render_template_fix.rb · GitLab</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',sans-serif;background:#fafafa;color:#303030;min-height:100vh;display:flex;flex-direction:column;font-size:14px;}

/* ── Top header ───────────────────────────────────────────────────────────── */
.topbar{background:#292961;height:48px;display:flex;align-items:center;padding:0 16px;flex-shrink:0;border-bottom:1px solid #1f1e38;}
.topbar-logo{display:flex;align-items:center;gap:8px;text-decoration:none;margin-right:16px;}
.gl-logo{width:26px;height:26px;flex-shrink:0;}
.topbar-search{flex:1;max-width:340px;margin-right:auto;}
.topbar-search input{width:100%;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:4px;padding:5px 10px;color:#fff;font-size:.78rem;outline:none;font-family:inherit;}
.topbar-search input::placeholder{color:rgba(255,255,255,.45);}
.topbar-search input:focus{background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.4);}
.topbar-nav{display:flex;align-items:center;gap:2px;margin-right:8px;}
.topbar-nav a{color:rgba(255,255,255,.75);font-size:.78rem;text-decoration:none;padding:6px 10px;border-radius:4px;white-space:nowrap;}
.topbar-nav a:hover{background:rgba(255,255,255,.1);color:#fff;}
.topbar-right{display:flex;align-items:center;gap:8px;}
.topbar-icon{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:rgba(255,255,255,.7);}
.topbar-icon:hover{background:rgba(255,255,255,.1);color:#fff;}
.topbar-icon svg{width:16px;height:16px;}
.topbar-avatar{width:28px;height:28px;border-radius:50%;background:#fc6d26;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:#fff;cursor:pointer;border:2px solid rgba(255,255,255,.3);}

/* ── Breadcrumb ───────────────────────────────────────────────────────────── */
.breadcrumb-bar{background:#fff;border-bottom:1px solid #e5e5e5;padding:0 16px;height:40px;display:flex;align-items:center;gap:6px;font-size:.78rem;color:#8c8c8c;}
.breadcrumb-bar a{color:#1f75cb;text-decoration:none;}
.breadcrumb-bar a:hover{text-decoration:underline;}
.breadcrumb-sep{color:#c5c5c5;}
.breadcrumb-current{color:#303030;font-weight:500;}

/* ── Page layout ──────────────────────────────────────────────────────────── */
.page{display:flex;flex:1;max-width:1200px;margin:0 auto;width:100%;padding:16px;gap:16px;}

/* ── Settings sidebar ─────────────────────────────────────────────────────── */
.settings-sidebar{width:200px;flex-shrink:0;}
.settings-sidebar-title{font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8c;padding:8px 12px 4px;}
.settings-nav a{display:block;padding:8px 12px;border-radius:4px;font-size:.82rem;color:#303030;text-decoration:none;margin-bottom:1px;}
.settings-nav a:hover{background:#f0f0f0;}
.settings-nav a.active{background:#e8f0fe;color:#1a56e8;font-weight:600;}

/* ── Main content ─────────────────────────────────────────────────────────── */
.main-content{flex:1;min-width:0;}

/* ── Card / panel ─────────────────────────────────────────────────────────── */
.gl-card{background:#fff;border:1px solid #dcdcdc;border-radius:4px;margin-bottom:16px;}
.gl-card-header{padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;gap:8px;}
.gl-card-header h3{font-size:.95rem;font-weight:600;color:#303030;flex:1;}
.gl-card-body{padding:20px 16px;}
.gl-card-footer{padding:12px 16px;border-top:1px solid #e5e5e5;background:#fafafa;}

/* ── Forms ────────────────────────────────────────────────────────────────── */
.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:.82rem;font-weight:600;color:#303030;margin-bottom:5px;}
.form-input{width:100%;padding:7px 10px;border:1px solid #dcdcdc;border-radius:4px;font-size:.84rem;color:#303030;background:#fff;outline:none;font-family:inherit;transition:border-color .15s,box-shadow .15s;}
.form-input:focus{border-color:#1a56e8;box-shadow:0 0 0 2px rgba(26,86,232,.15);}
.form-hint{font-size:.72rem;color:#8c8c8c;margin-top:3px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
textarea.form-input{resize:vertical;min-height:80px;}
.btn-gl{border:none;border-radius:4px;padding:7px 16px;font-size:.82rem;font-weight:600;cursor:pointer;font-family:inherit;transition:background .15s;}
.btn-gl-primary{background:#1a56e8;color:#fff;}
.btn-gl-primary:hover{background:#1a4bcc;}
.btn-gl-default{background:#fafafa;border:1px solid #dcdcdc;color:#303030;}
.btn-gl-default:hover{background:#f0f0f0;}
.gl-alert{padding:9px 12px;border-radius:4px;font-size:.8rem;margin-bottom:14px;display:flex;align-items:center;gap:7px;}
.gl-alert-success{background:#ecf8ee;border:1px solid #b8dbbe;color:#24663b;}
.gl-alert-info{background:#eff5ff;border:1px solid #cde1f9;color:#1a56e8;}

/* ── Snippet view ─────────────────────────────────────────────────────────── */
.snippet-header{display:flex;align-items:flex-start;gap:12px;margin-bottom:16px;}
.snippet-visibility{display:inline-flex;align-items:center;gap:4px;background:#f0f0f0;border:1px solid #dcdcdc;border-radius:10px;padding:2px 8px;font-size:.7rem;color:#585858;font-weight:500;}
.snippet-title{font-size:1.3rem;font-weight:700;color:#303030;margin-bottom:4px;}
.snippet-meta{font-size:.76rem;color:#8c8c8c;margin-bottom:16px;}
.snippet-meta a{color:#1f75cb;text-decoration:none;}
.snippet-meta a:hover{text-decoration:underline;}

/* ── Code block ───────────────────────────────────────────────────────────── */
.code-header{background:#f0f0f0;border:1px solid #dcdcdc;border-bottom:none;border-radius:4px 4px 0 0;padding:8px 12px;display:flex;align-items:center;justify-content:space-between;}
.code-filename{font-size:.78rem;font-weight:600;color:#303030;font-family:monospace;}
.code-actions{display:flex;gap:6px;}
.code-action-btn{background:transparent;border:1px solid #dcdcdc;border-radius:3px;padding:3px 8px;font-size:.7rem;cursor:pointer;color:#585858;}
.code-action-btn:hover{background:#e8e8e8;}
.code-block{background:#f8f8f2;border:1px solid #dcdcdc;border-radius:0 0 4px 4px;overflow:auto;margin-bottom:16px;}
.code-table{width:100%;border-collapse:collapse;font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:.78rem;line-height:1.6;}
.code-table td{padding:0;}
.code-nums{width:40px;padding:0 8px;text-align:right;color:#b0b0b0;background:#f0f0f0;border-right:1px solid #dcdcdc;user-select:none;vertical-align:top;}
.code-nums span{display:block;padding:1px 0;}
.code-line{padding:1px 12px;white-space:pre;color:#383a42;vertical-align:top;}
.code-line span{display:block;}
/* minimal syntax coloring */
.kw{color:#a626a4;}.str{color:#50a14f;}.cmt{color:#a0a1a7;font-style:italic;}.fn{color:#4078f2;}.num{color:#986801;}

/* ── Snippet author box ───────────────────────────────────────────────────── */
.author-box{background:#fff;border:1px solid #dcdcdc;border-radius:4px;padding:16px;display:flex;gap:12px;align-items:flex-start;}
.author-avatar-lg{width:48px;height:48px;border-radius:50%;background:#fc6d26;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;color:#fff;flex-shrink:0;}
.author-info{flex:1;}
.author-name{font-size:.95rem;font-weight:700;color:#303030;margin-bottom:2px;}
.author-handle{font-size:.78rem;color:#8c8c8c;margin-bottom:6px;}
.author-bio{font-size:.78rem;color:#585858;line-height:1.5;}
.author-label{font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8c;margin-bottom:8px;}

/* ── Sidebar ──────────────────────────────────────────────────────────────── */
.snippet-sidebar{width:200px;flex-shrink:0;}
.sidebar-section{background:#fff;border:1px solid #dcdcdc;border-radius:4px;margin-bottom:12px;overflow:hidden;}
.sidebar-section-title{padding:9px 12px;background:#f0f0f0;border-bottom:1px solid #dcdcdc;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#585858;}
.sidebar-item{padding:8px 12px;border-bottom:1px solid #f0f0f0;font-size:.76rem;}
.sidebar-item:last-child{border-bottom:none;}
.sidebar-item-label{color:#8c8c8c;margin-bottom:2px;}
.sidebar-item-val{color:#303030;font-weight:500;}
.sidebar-item-val a{color:#1f75cb;text-decoration:none;}
.sidebar-item-val a:hover{text-decoration:underline;}

/* ── Footer ───────────────────────────────────────────────────────────────── */
.gl-footer{background:#fff;border-top:1px solid #dcdcdc;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;font-size:.7rem;color:#8c8c8c;flex-shrink:0;}
.gl-footer a{color:#8c8c8c;text-decoration:none;margin:0 4px;}
.gl-footer a:hover{color:#303030;}

@media(max-width:800px){.snippet-sidebar{display:none;}.settings-sidebar{display:none;}.form-row{grid-template-columns:1fr;}}
</style>
</head>
<body>

<!-- Top Header -->
<header class="topbar">
  <a href="index.php" class="topbar-logo">
    <svg class="gl-logo" viewBox="0 0 380 380" xmlns="http://www.w3.org/2000/svg">
      <path d="M282.83 170.73l-7.08-21.77-30.7 94.31h72.5l-24.92-72.54z" fill="#e24329"/>
      <path d="M190 373.58l62.83-192.85H127.17L190 373.58z" fill="#e24329"/>
      <path d="M127.17 180.73L190 373.58l-83.5-240.87 20.67 48.02z" fill="#fc6d26"/>
      <path d="M43.5 132.71L10.75 235.58 .82 264.45l186.18 109.13L127.17 180.73 43.5 132.71z" fill="#fca326"/>
      <path d="M336.5 132.71l-83.67 48.02-62.83 192.85 186.18-109.13L336.5 132.71z" fill="#fca326"/>
      <path d="M282.83 170.73l51.67-38.02-8.75-26.89L282.83 170.73z" fill="#e24329"/>
      <path d="M336.5 132.71l8.75-26.89-17.5-5.38 8.75 32.27z" fill="#fc6d26"/>
    </svg>
    <span style="color:#fff;font-size:.9rem;font-weight:600;letter-spacing:-.01em;">GitLab</span>
  </a>
  <div class="topbar-search">
    <input type="text" placeholder="Search GitLab" value="">
  </div>
  <nav class="topbar-nav">
    <a href="#">Projects</a>
    <a href="#">Groups</a>
    <a href="#">Activity</a>
    <a href="index.php?view=snippets" style="<?= $view==='snippets' ? 'color:#fff;background:rgba(255,255,255,.15);' : '' ?>">Snippets</a>
  </nav>
  <div class="topbar-right">
    <div class="topbar-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
    </div>
    <div class="topbar-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <a href="index.php?view=settings">
      <div class="topbar-avatar"><?= $initial ?></div>
    </a>
  </div>
</header>

<?php if ($view === 'settings'): ?>
<!-- ════════════════════════════════════════════════════════════════════════ -->
<!--  SETTINGS VIEW                                                          -->
<!-- ════════════════════════════════════════════════════════════════════════ -->
<div class="breadcrumb-bar">
  <a href="#"><?= $username ?></a>
  <span class="breadcrumb-sep">/</span>
  <span class="breadcrumb-current">Edit Profile</span>
</div>

<div class="page">
  <!-- Sidebar -->
  <aside class="settings-sidebar">
    <div class="settings-sidebar-title">User Settings</div>
    <nav class="settings-nav">
      <a href="index.php?view=settings" class="active">Profile</a>
      <a href="#">Account</a>
      <a href="#">Notifications</a>
      <a href="#">SSH Keys</a>
      <a href="#">Access Tokens</a>
      <a href="#">Preferences</a>
    </nav>
  </aside>

  <!-- Main -->
  <main class="main-content">
    <div class="gl-card">
      <div class="gl-card-header">
        <h3>Public Profile</h3>
      </div>
      <div class="gl-card-body">
        <?php if ($saved): ?>
        <div class="gl-alert gl-alert-success">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
          Profile was successfully updated.
        </div>
        <?php endif; ?>
        <div class="gl-alert gl-alert-info">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          Your profile name will appear on <a href="index.php?view=snippets" style="color:#1a56e8;">snippet pages</a> and other public areas.
        </div>

        <form method="POST" action="index.php">
          <input type="hidden" name="action" value="save_profile">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="full_name">Full Name</label>
              <input class="form-input" type="text" id="full_name" name="full_name"
                value="<?= $nameSafe ?>"
                placeholder='e.g. <a href="http://evil.com">GitLab</a>'
                autocomplete="off">
              <div class="form-hint">Your full name as it appears publicly. No sanitization is applied.</div>
            </div>
            <div class="form-group">
              <label class="form-label" for="username">Username</label>
              <input class="form-input" type="text" id="username" name="username"
                value="<?= $username ?>" autocomplete="off">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input class="form-input" type="email" id="email" name="email"
              value="talaohu28@gmail.com">
          </div>
          <div class="form-group">
            <label class="form-label" for="bio">Bio</label>
            <textarea class="form-input" id="bio" name="bio" rows="3"><?= $bio ?></textarea>
            <div class="form-hint">Tell us about yourself in 250 characters or fewer.</div>
          </div>
          <div class="form-group">
            <label class="form-label" for="website">Website</label>
            <input class="form-input" type="url" id="website" name="website"
              value="http://progress28.web.id" placeholder="https://yourwebsite.com">
          </div>
          <div class="form-group">
            <label class="form-label" for="location">Location</label>
            <input class="form-input" type="text" id="location" name="location"
              value="Indonesia" placeholder="City, Country">
          </div>
          <div style="display:flex;gap:8px;">
            <button class="btn-gl btn-gl-primary" type="submit">Update Profile Settings</button>
            <button class="btn-gl btn-gl-default" type="reset" onclick="return false;">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <div class="gl-card">
      <div class="gl-card-header"><h3>Current Public Profile Preview</h3></div>
      <div class="gl-card-body">
        <div style="display:flex;gap:12px;align-items:center;">
          <div class="author-avatar-lg" style="width:64px;height:64px;font-size:1.4rem;"><?= $initial ?></div>
          <div>
            <div style="font-size:1rem;font-weight:700;"><?= $nameSafe ?></div>
            <div style="font-size:.8rem;color:#8c8c8c;">@<?= $username ?></div>
            <div style="font-size:.8rem;color:#585858;margin-top:4px;"><?= $bio ?></div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<?php else: ?>
<!-- ════════════════════════════════════════════════════════════════════════ -->
<!--  SNIPPETS VIEW (default)                                                -->
<!-- ════════════════════════════════════════════════════════════════════════ -->
<div class="breadcrumb-bar">
  <a href="#">GitLab</a>
  <span class="breadcrumb-sep">/</span>
  <a href="#">Snippets</a>
  <span class="breadcrumb-sep">/</span>
  <span class="breadcrumb-current">render_template_fix.rb · Snippet #1718284</span>
</div>

<div class="page">

  <!-- Snippet sidebar -->
  <aside class="snippet-sidebar">
    <div class="sidebar-section">
      <div class="sidebar-section-title">Snippet Info</div>
      <div class="sidebar-item">
        <div class="sidebar-item-label">Visibility</div>
        <div class="sidebar-item-val">
          <span style="display:inline-flex;align-items:center;gap:4px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            Public
          </span>
        </div>
      </div>
      <div class="sidebar-item">
        <div class="sidebar-item-label">Created</div>
        <div class="sidebar-item-val">May 27, 2018</div>
      </div>
      <div class="sidebar-item">
        <div class="sidebar-item-label">Updated</div>
        <div class="sidebar-item-val">May 27, 2018</div>
      </div>
      <div class="sidebar-item">
        <div class="sidebar-item-label">Author</div>
        <div class="sidebar-item-val"><a href="#">@<?= $username ?></a></div>
      </div>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-title">Actions</div>
      <div class="sidebar-item"><a href="index.php?view=settings" style="color:#1f75cb;font-size:.76rem;">✏ Edit Profile Name</a></div>
      <div class="sidebar-item"><a href="#" style="color:#1f75cb;font-size:.76rem;">⬇ Download</a></div>
      <div class="sidebar-item"><a href="#" style="color:#1f75cb;font-size:.76rem;">⭐ Star (3)</a></div>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-title">Tags</div>
      <div class="sidebar-item">
        <span style="background:#e8f0fe;color:#1a56e8;padding:2px 6px;border-radius:10px;font-size:.7rem;">ruby</span>
        <span style="background:#f0fdf4;color:#166534;padding:2px 6px;border-radius:10px;font-size:.7rem;margin-left:4px;">rails</span>
      </div>
    </div>
  </aside>

  <!-- Main snippet content -->
  <main class="main-content">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:8px;gap:12px;flex-wrap:wrap;">
      <div>
        <div class="snippet-title">render_template_fix.rb</div>
        <div class="snippet-meta">
          Snippet #1718284 &middot; Created by
          <a href="index.php?view=settings">@<?= $username ?></a>
          &middot; May 27, 2018
        </div>
      </div>
      <div style="display:flex;gap:6px;flex-shrink:0;">
        <button class="code-action-btn">Edit</button>
        <button class="code-action-btn">Delete</button>
      </div>
    </div>

    <!-- Code block -->
    <div class="code-header">
      <span class="code-filename">render_template_fix.rb</span>
      <div class="code-actions">
        <button class="code-action-btn">Raw</button>
        <button class="code-action-btn">Copy</button>
      </div>
    </div>
    <div class="code-block">
      <table class="code-table">
        <tbody>
          <tr><td class="code-nums"><span>1</span></td><td class="code-line"><span><span class="cmt"># Fix: escape user-supplied template variables before render</span></span></td></tr>
          <tr><td class="code-nums"><span>2</span></td><td class="code-line"><span><span class="kw">module</span> <span class="fn">TemplateRenderer</span></span></td></tr>
          <tr><td class="code-nums"><span>3</span></td><td class="code-line"><span>  <span class="kw">def</span> <span class="fn">safe_render</span>(template, vars)</span></td></tr>
          <tr><td class="code-nums"><span>4</span></td><td class="code-line"><span>    sanitized = vars.transform_values { |v| CGI.escapeHTML(v.to_s) }</span></td></tr>
          <tr><td class="code-nums"><span>5</span></td><td class="code-line"><span>    template.gsub(<span class="str">/\{\{(\w+)\}\}/</span>) { sanitized[$1] || <span class="str">''</span> }</span></td></tr>
          <tr><td class="code-nums"><span>6</span></td><td class="code-line"><span>  <span class="kw">end</span></span></td></tr>
          <tr><td class="code-nums"><span>7</span></td><td class="code-line"><span><span class="kw">end</span></span></td></tr>
          <tr><td class="code-nums"><span>8</span></td><td class="code-line"><span></span></td></tr>
          <tr><td class="code-nums"><span>9</span></td><td class="code-line"><span><span class="cmt"># Usage</span></span></td></tr>
          <tr><td class="code-nums"><span>10</span></td><td class="code-line"><span>renderer = <span class="fn">TemplateRenderer</span>.new</span></td></tr>
          <tr><td class="code-nums"><span>11</span></td><td class="code-line"><span>output = renderer.safe_render(<span class="str">"Hello {{name}}"</span>, { <span class="str">"name"</span> => user_input })</span></td></tr>
        </tbody>
      </table>
    </div>

    <!-- Author box — VULNERABLE SINK ─────────────────────────────────────── -->
    <div class="author-label">Created by</div>
    <div class="author-box">
      <div class="author-avatar-lg"><?= $initial ?></div>
      <div class="author-info">
        <div class="author-name">
          <?php
          // ⚠ VULNERABLE SINK — $nameRaw echoed without htmlspecialchars().
          // Report #358001: profile Full Name is not sanitized on the Snippets page.
          // Confirmed working tags: <a href>, <img src>, <h1>, <div>, <b>, <br>
          //
          // Payload 1 — Phishing link injection:
          //   <a href="http://evil.com/gitlab-phishing/">rootbakar_</a>
          //
          // Payload 2 — Defacement:
          //   </br><h1>HACKED BY TALAOHU28</h1><img src="http://evil.com/img.jpg"></br>
          echo $nameRaw;
          ?>
        </div>
        <div class="author-handle">@<?= $username ?> &middot; <a href="index.php?view=snippets" style="color:#8c8c8c;">1 snippet</a></div>
        <div class="author-bio"><?= $bio ?></div>
      </div>
    </div>

    <!-- Comments section (decorative) -->
    <div class="gl-card" style="margin-top:16px;">
      <div class="gl-card-header"><h3 style="font-size:.88rem;">Comments (1)</h3></div>
      <div class="gl-card-body">
        <div style="display:flex;gap:10px;align-items:flex-start;">
          <div style="width:32px;height:32px;border-radius:50%;background:#8b5cf6;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#fff;flex-shrink:0;">A</div>
          <div>
            <div style="font-size:.8rem;font-weight:600;color:#303030;">asaba <span style="color:#8c8c8c;font-weight:400;">· May 30, 2018</span></div>
            <div style="font-size:.8rem;color:#585858;margin-top:4px;line-height:1.5;">The <code style="background:#f0f0f0;padding:1px 4px;border-radius:2px;font-size:.78em;">&lt;a&gt;</code> being passed through is an inconsistency that could lead to a user clicking on a bad link.</div>
          </div>
        </div>
      </div>
    </div>

  </main><!-- /main-content -->
</div>

<?php endif; ?>

<!-- Footer -->
<footer class="gl-footer">
  <span>GitLab Community Edition</span>
  <span>
    <a href="#">Help</a>
    <a href="#">About GitLab</a>
    <a href="https://hackerone.com/reports/358001" target="_blank">Report #358001</a>
  </span>
</footer>

</body>
</html>
