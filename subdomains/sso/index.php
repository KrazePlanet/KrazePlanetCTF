<?php
// Lab 304 — Open Redirect via \@ Validator Bypass (Tumblr / Automattic)
// Based on HackerOne Report #2812583 (Automattic/Tumblr — Low, Resolved Nov 4, 2024)
// Vulnerability: parse_url() sees www.tumblr.com as host for "evil.com\@www.tumblr.com"
//               but browser normalises \ → / and navigates to evil.com instead

// ── Absolute base URL for attack panel links ───────────────────────────────
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$labBase = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/index.php';

// ── Logout + redirect handler ──────────────────────────────────────────────
$action     = $_GET['action'] ?? '';
$redirectTo = $_GET['redirect_to'] ?? '';
$blocked    = false;
$blockedUrl = '';
$blockedHost = '';

if ($action === 'logout' && $redirectTo !== '') {
    // Developer's validator — uses parse_url() to extract host
    // Intended to only allow same-domain redirects after logout
    $host = parse_url($redirectTo, PHP_URL_HOST);

    $allowedHosts = ['tumblr.com', 'www.tumblr.com'];

    if (in_array($host, $allowedHosts, true)) {
        // ⚠ VULNERABLE: "evil.com\@www.tumblr.com" passes this check
        // parse_url() treats @ as userinfo separator → host = www.tumblr.com ✓
        // but browser normalises \ to / → navigates to evil.com
        header('Location: ' . $redirectTo);
        exit;
    } else {
        // Validator correctly blocks raw external URLs (e.g. ?redirect_to=https://evil.com)
        $blocked    = true;
        $blockedUrl = $redirectTo;
        $blockedHost = $host ?? '(none)';
    }
}

// ── Blog post data ─────────────────────────────────────────────────────────
$posts = [
    ['type'=>'text','avatar'=>'🌙','user'=>'nightthoughts','handle'=>'nightthoughts',
     'time'=>'2 hours ago','likes'=>4821,'reblogs'=>1203,
     'title'=>'the strange comfort of 3am',
     'body'=>'there\'s something about being awake when the rest of the world is sleeping. like you\'ve found a secret door that only exists for a few hours, and you\'re the only one who knows how to open it.',
     'tags'=>['3am','night thoughts','prose','writing']],
    ['type'=>'quote','avatar'=>'📚','user'=>'readmorereadoften','handle'=>'readmorereadoften',
     'time'=>'5 hours ago','likes'=>12440,'reblogs'=>6733,
     'quote'=>'"Not all those who wander are lost."',
     'source'=>'— J.R.R. Tolkien, The Lord of the Rings',
     'tags'=>['tolkien','quotes','literature']],
    ['type'=>'text','avatar'=>'🎨','user'=>'colorsofmymind','handle'=>'colorsofmymind',
     'time'=>'8 hours ago','likes'=>2904,'reblogs'=>887,
     'title'=>'why I still use film photography',
     'body'=>'digital is perfect. film is true. there\'s a difference between something being technically flawless and something feeling real. the grain, the light leaks, the slight overexposure — that\'s not a bug. that\'s a mood.',
     'tags'=>['photography','film','analog','art']],
    ['type'=>'text','avatar'=>'🌿','user'=>'softearth','handle'=>'softearth',
     'time'=>'12 hours ago','likes'=>7812,'reblogs'=>3201,
     'title'=>'a small guide to doing nothing',
     'body'=>'1. make tea. do not rush it.\n2. sit by the window.\n3. watch clouds. not your phone.\n4. remember that rest is productive.\n5. repeat tomorrow.',
     'tags'=>['slow living','wellness','lists','self care']],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $blocked ? 'Redirect Blocked — Tumblr' : 'Tumblr — Dashboard'; ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:14px;background:#f2f2f2;color:#222;}
a{text-decoration:none;color:inherit;}

/* ── Navbar ──────────────────────────────────────────────────────────────── */
.tb-nav{background:#35465c;height:48px;display:flex;align-items:center;padding:0 16px;position:sticky;top:0;z-index:900;box-shadow:0 1px 4px rgba(0,0,0,.3);}
.tb-logo{font-size:1.5rem;font-weight:900;color:#fff;margin-right:24px;letter-spacing:-1px;flex-shrink:0;}
.tb-nav-icons{display:flex;align-items:center;gap:2px;}
.tb-nav-icon{width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:4px;cursor:pointer;color:rgba(255,255,255,.7);font-size:1rem;transition:background .15s;}
.tb-nav-icon:hover,.tb-nav-icon.active{background:rgba(255,255,255,.12);color:#fff;}
.tb-nav-right{margin-left:auto;display:flex;align-items:center;gap:8px;}
.tb-avatar{width:32px;height:32px;border-radius:50%;background:#00b8ff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;color:#fff;cursor:pointer;position:relative;}
.tb-avatar-menu{position:absolute;top:38px;right:0;background:#fff;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.15);min-width:160px;overflow:hidden;display:none;}
.tb-avatar:hover .tb-avatar-menu{display:block;}
.tb-avatar-menu a{display:block;padding:10px 16px;font-size:.8rem;color:#35465c;border-bottom:1px solid #f5f5f5;}
.tb-avatar-menu a:last-child{border-bottom:none;color:#e63946;}
.tb-avatar-menu a:hover{background:#f8f8f8;}
.tb-new-post{background:#ff4500;color:#fff;border:none;border-radius:20px;padding:7px 16px;font-size:.78rem;font-weight:700;cursor:pointer;font-family:inherit;}

/* ── Blocked Banner ───────────────────────────────────────────────────────── */
.tb-blocked-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f2f2f2;padding:24px;}
.tb-blocked-card{background:#fff;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,.1);max-width:580px;width:100%;overflow:hidden;}
.tb-blocked-header{background:#35465c;padding:20px 24px;color:#fff;}
.tb-blocked-header h2{font-size:1.1rem;font-weight:800;margin-bottom:4px;}
.tb-blocked-header p{font-size:.8rem;opacity:.8;}
.tb-blocked-body{padding:24px;}
.tb-blocked-row{margin-bottom:16px;}
.tb-blocked-label{font-size:.68rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;}
.tb-blocked-val{background:#f8f8f8;border:1px solid #e0e0e0;border-radius:5px;padding:9px 12px;font-family:monospace;font-size:.8rem;color:#333;word-break:break-all;}
.tb-blocked-val.bad{background:#fef2f2;border-color:#fca5a5;color:#991b1b;}
.tb-blocked-val.good{background:#f0fdf4;border-color:#bbf7d0;color:#166534;}
.tb-blocked-info{background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:14px;font-size:.8rem;color:#1e40af;line-height:1.6;}
.tb-blocked-info code{background:#dbeafe;padding:0 4px;border-radius:3px;}
.tb-back-btn{display:inline-block;margin-top:16px;background:#35465c;color:#fff;border-radius:6px;padding:9px 18px;font-size:.82rem;font-weight:700;}

/* ── Layout ──────────────────────────────────────────────────────────────── */
.tb-layout{max-width:1060px;margin:0 auto;padding:24px 16px;display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start;}
@media(max-width:860px){.tb-layout{grid-template-columns:1fr;}}

/* ── Post Cards ──────────────────────────────────────────────────────────── */
.tb-feed{display:flex;flex-direction:column;gap:16px;}
.tb-post{background:#fff;border-radius:4px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.tb-post-header{padding:12px 14px;display:flex;align-items:center;gap:10px;}
.tb-post-avatar{width:34px;height:34px;border-radius:50%;background:#35465c;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.tb-post-user{font-size:.8rem;font-weight:700;color:#35465c;}
.tb-post-time{font-size:.7rem;color:#aaa;margin-top:1px;}
.tb-post-body{padding:4px 14px 12px;}
.tb-post-title{font-size:.95rem;font-weight:800;color:#222;margin-bottom:8px;line-height:1.3;}
.tb-post-text{font-size:.82rem;color:#444;line-height:1.7;white-space:pre-line;}
.tb-post-quote{font-size:1.1rem;font-style:italic;color:#35465c;line-height:1.5;border-left:4px solid #00b8ff;padding-left:14px;margin-bottom:8px;}
.tb-post-source{font-size:.75rem;color:#888;}
.tb-post-tags{padding:8px 14px;display:flex;flex-wrap:wrap;gap:6px;border-top:1px solid #f5f5f5;}
.tb-tag{font-size:.7rem;color:#00b8ff;cursor:pointer;}
.tb-tag:hover{text-decoration:underline;}
.tb-tag::before{content:'#';}
.tb-post-actions{padding:8px 14px;display:flex;gap:16px;border-top:1px solid #f5f5f5;}
.tb-action{display:flex;align-items:center;gap:5px;font-size:.73rem;color:#aaa;cursor:pointer;}
.tb-action svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;}
.tb-action:hover{color:#00b8ff;}
.tb-action.reblog:hover{color:#2ecc71;}
.tb-action.like:hover{color:#e63946;}
.tb-action-count{font-weight:600;}

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.tb-sidebar-widget{background:#fff;border-radius:4px;padding:16px;margin-bottom:14px;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.tb-sidebar-title{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#aaa;margin-bottom:12px;}
.tb-blog-name{font-size:1rem;font-weight:800;color:#35465c;margin-bottom:4px;}
.tb-blog-url{font-size:.75rem;color:#00b8ff;margin-bottom:12px;}
.tb-blog-stat{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f5f5f5;font-size:.78rem;}
.tb-blog-stat:last-child{border-bottom:none;}
.tb-blog-stat-val{font-weight:700;color:#35465c;}
.tb-logout-btn{display:block;width:100%;margin-top:14px;text-align:center;background:#35465c;color:#fff;border-radius:4px;padding:9px;font-size:.78rem;font-weight:700;}
.tb-logout-btn:hover{background:#2c3a4d;}

/* ── Attack Panel ────────────────────────────────────────────────────────── */
.tb-attack{background:#fff8f3;border:1px solid #fb923c;border-radius:4px;padding:16px;}
.tb-attack-title{font-size:.78rem;font-weight:700;color:#7c2d12;margin-bottom:8px;display:flex;align-items:center;gap:5px;}
.tb-attack-desc{font-size:.72rem;color:#7c2d12;line-height:1.5;margin-bottom:10px;}
.tb-attack-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-top:10px;margin-bottom:4px;}
.tb-attack-label.safe{color:#166534;}
.tb-attack-label.blocked{color:#991b1b;}
.tb-attack-label.bypass{color:#92400e;}
.tb-attack-code{font-family:monospace;font-size:.64rem;border-radius:4px;padding:6px 8px;word-break:break-all;display:block;margin:3px 0;}
.tb-attack-code.safe{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;}
.tb-attack-code.blocked{background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;}
.tb-attack-code.bypass{background:#fff7ed;border:1px solid #fed7aa;color:#c2410c;}
.tb-attack-code a{color:inherit;display:block;}
.tb-attack-note{font-size:.68rem;color:#9a3412;margin-top:10px;line-height:1.5;}
.tb-attack-key{background:#fef3c7;border:1px solid #fde68a;border-radius:4px;padding:8px;font-size:.7rem;color:#78350f;margin-top:10px;line-height:1.5;}
.tb-attack-ref{margin-top:10px;padding-top:10px;border-top:1px solid #fed7aa;font-size:.67rem;color:#9a3412;}
.tb-attack-ref a{color:#c2410c;text-decoration:underline;}
</style>
</head>
<body>

<!-- Navbar -->
<div class="tb-nav">
  <div class="tb-logo">t</div>
  <div class="tb-nav-icons">
    <div class="tb-nav-icon active" title="Home">🏠</div>
    <div class="tb-nav-icon" title="Explore">🔍</div>
    <div class="tb-nav-icon" title="Activity">🔔</div>
    <div class="tb-nav-icon" title="Inbox">✉</div>
  </div>
  <div class="tb-nav-right">
    <button class="tb-new-post">✚ New Post</button>
    <div class="tb-avatar">
      SM
      <div class="tb-avatar-menu">
        <a href="#">My Blog</a>
        <a href="#">Settings</a>
        <a href="#">Help</a>
        <a href="<?php echo $labBase; ?>?action=logout&redirect_to=https://www.tumblr.com/dashboard">Log out</a>
      </div>
    </div>
  </div>
</div>

<?php if ($blocked): ?>
<!-- ── Redirect Blocked Page ──────────────────────────────────────────────── -->
<div class="tb-blocked-wrap">
  <div class="tb-blocked-card">
    <div class="tb-blocked-header">
      <h2>🛡 Redirect Blocked</h2>
      <p>The validator detected an unauthorized redirect destination</p>
    </div>
    <div class="tb-blocked-body">
      <div class="tb-blocked-row">
        <div class="tb-blocked-label">Attempted redirect_to value</div>
        <div class="tb-blocked-val bad"><?php echo htmlspecialchars($blockedUrl); ?></div>
      </div>
      <div class="tb-blocked-row">
        <div class="tb-blocked-label">Host extracted by parse_url()</div>
        <div class="tb-blocked-val bad"><?php echo htmlspecialchars($blockedHost); ?> — not in allowed list</div>
      </div>
      <div class="tb-blocked-row">
        <div class="tb-blocked-label">Allowed hosts</div>
        <div class="tb-blocked-val good">tumblr.com, www.tumblr.com</div>
      </div>
      <div class="tb-blocked-info">
        <strong>The validator works for raw external URLs.</strong><br>
        <code>parse_url('<?php echo htmlspecialchars($blockedUrl); ?>', PHP_URL_HOST)</code>
        → <code><?php echo htmlspecialchars($blockedHost); ?></code> → blocked ✓<br><br>
        But try the <strong>bypass</strong> — use <code>evil.com\@www.tumblr.com</code> and the same validator will <em>pass it</em> while the browser goes to evil.com.
      </div>
      <a href="<?php echo $labBase; ?>" class="tb-back-btn">← Back to Dashboard</a>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ── Dashboard ──────────────────────────────────────────────────────────── -->
<div class="tb-layout">

  <!-- Feed -->
  <div class="tb-feed">
    <?php foreach($posts as $p): ?>
    <div class="tb-post">
      <div class="tb-post-header">
        <div class="tb-post-avatar"><?php echo $p['avatar']; ?></div>
        <div>
          <div class="tb-post-user"><?php echo htmlspecialchars($p['user']); ?></div>
          <div class="tb-post-time"><?php echo $p['time']; ?></div>
        </div>
      </div>
      <div class="tb-post-body">
        <?php if($p['type']==='text'): ?>
          <div class="tb-post-title"><?php echo htmlspecialchars($p['title']); ?></div>
          <div class="tb-post-text"><?php echo htmlspecialchars($p['body']); ?></div>
        <?php else: ?>
          <div class="tb-post-quote"><?php echo htmlspecialchars($p['quote']); ?></div>
          <div class="tb-post-source"><?php echo htmlspecialchars($p['source']); ?></div>
        <?php endif; ?>
      </div>
      <div class="tb-post-tags">
        <?php foreach($p['tags'] as $t): ?><span class="tb-tag"><?php echo htmlspecialchars($t); ?></span><?php endforeach; ?>
      </div>
      <div class="tb-post-actions">
        <div class="tb-action reblog">
          <svg viewBox="0 0 24 24"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 014-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
          <span class="tb-action-count"><?php echo number_format($p['reblogs']); ?></span>
        </div>
        <div class="tb-action like">
          <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
          <span class="tb-action-count"><?php echo number_format($p['likes']); ?></span>
        </div>
        <div class="tb-action" style="margin-left:auto;">
          <svg viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
          Share
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Sidebar -->
  <aside>

    <!-- Blog widget -->
    <div class="tb-sidebar-widget">
      <div class="tb-sidebar-title">Your Blog</div>
      <div class="tb-blog-name">shivangmauryaa</div>
      <div class="tb-blog-url">shivangmauryaa.tumblr.com</div>
      <div class="tb-blog-stat"><span>Posts</span><span class="tb-blog-stat-val">847</span></div>
      <div class="tb-blog-stat"><span>Following</span><span class="tb-blog-stat-val">312</span></div>
      <div class="tb-blog-stat"><span>Followers</span><span class="tb-blog-stat-val">2,841</span></div>
      <div class="tb-blog-stat"><span>Likes</span><span class="tb-blog-stat-val">14,203</span></div>
      <a href="<?php echo $labBase; ?>?action=logout&redirect_to=https://www.tumblr.com/dashboard" class="tb-logout-btn">Log out</a>
    </div>

  </aside>
</div>

<!-- Footer -->
<div style="background:#35465c;color:rgba(255,255,255,.4);padding:20px;text-align:center;font-size:.72rem;margin-top:24px;">
  © 2024 Tumblr Inc. — Security Lab for Educational Purposes
</div>

<?php endif; ?>
</body>
</html>
