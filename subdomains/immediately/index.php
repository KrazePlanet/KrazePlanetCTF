<?php
// Weblate — the libre continuous localization platform
session_start();

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$path     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path     = rtrim($path, '/');
if ($path === '') $path = '/';
$host     = $_SERVER['HTTP_HOST'];
$hostOnly = preg_replace('/:\d+$/', '', $host);

// ── Redirect sanitiser (mirrors python-social-auth sanitize_redirect) ─────────
// The destination's host is extracted and compared with the site's own host; a
// different host is rejected and the user goes to the dashboard instead.
function sanitize_redirect($next, $hostOnly) {
    if ($next === '') return '/dashboard';
    $h = parse_url($next, PHP_URL_HOST);
    if ($h && strtolower($h) !== strtolower($hostOnly)) return '/dashboard';
    return $next;   // relative / same-host
}

$providers = [
    'github' => ['GitHub', '#1b1f23'],
    'gitlab' => ['GitLab', '#fc6d26'],
    'google' => ['Google', '#4285f4'],
];

// ── OAuth callback: the provider "authenticated" the user → return to next ───
if (preg_match('#^/accounts/complete/(github|gitlab|google)$#', $path, $m)) {
    $prov = $m[1];
    $_SESSION['wluser'] = ['name' => ucfirst($prov) . ' User', 'handle' => $prov . '-user', 'prov' => $prov];
    $next = $_GET['next'] ?? '';
    header('Location: ' . sanitize_redirect($next, $hostOnly));
    exit;
}

// ── OAuth start: provider authorise screen ───────────────────────────────────
if (preg_match('#^/accounts/login/(github|gitlab|google)$#', $path, $m)) {
    $prov = $m[1];
    $next = $_GET['next'] ?? '';
    // The login flow always remembers where to send the user afterwards; default
    // it so the parameter is present in the URL.
    if ($next === '') { header("Location: /accounts/login/$prov/?next=/dashboard"); exit; }
    render_authorize($prov, $providers, $next);
    exit;
}

// ── Username/password login (also honours ?next=) ────────────────────────────
if ($path === '/accounts/login') {
    $next = $_POST['next'] ?? ($_GET['next'] ?? '');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $next === '') { header('Location: /accounts/login/?next=/dashboard'); exit; }
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $u = trim($_POST['username'] ?? ''); $p = $_POST['password'] ?? '';
        if ($u !== '' && $p !== '') {
            $_SESSION['wluser'] = ['name' => $u, 'handle' => $u, 'prov' => 'password'];
            header('Location: ' . sanitize_redirect($next, $hostOnly));
            exit;
        }
        $err = 'Please enter your username and password.';
    }
    render_login($next, $err, $providers);
    exit;
}

if ($path === '/accounts/logout') { unset($_SESSION['wluser']); header('Location: /'); exit; }

$me = $_SESSION['wluser'] ?? null;

if ($path === '/dashboard') {
    if (!$me) { header('Location: /accounts/login/?next=/dashboard'); exit; }
    render_dashboard($me);
    exit;
}

render_landing($me);

// =====================================================================
//  VIEWS
// =====================================================================
function wl_head($title) { ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($title); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--green:#2eccaa;--green2:#17a98b;--dark:#144d3e;--ink:#1a2b29;--muted:#6b7f7b;--line:#e3ece9;--bg:#f4f8f6;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:var(--ink);background:#fff;line-height:1.55;}
a{text-decoration:none;color:inherit;}
.wrap{max-width:1100px;margin:0 auto;padding:0 20px;}
.nav{background:var(--dark);color:#fff;}
.nav-in{max-width:1100px;margin:0 auto;padding:13px 20px;display:flex;align-items:center;gap:28px;}
.logo{display:flex;align-items:center;gap:9px;font-size:19px;font-weight:800;}
.logo .mk{width:28px;height:28px;border-radius:7px;background:var(--green);display:flex;align-items:center;justify-content:center;color:var(--dark);font-weight:900;}
.menu{display:flex;gap:20px;font-size:14px;font-weight:500;color:#bfe4da;}
.menu a:hover{color:#fff;}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:14px;font-size:14px;}
.nav-right a:hover{color:#fff;}
.btn{background:var(--green);color:var(--dark);border:none;border-radius:7px;padding:9px 16px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-block;}
.btn:hover{background:#28b89a;}
.av{width:30px;height:30px;border-radius:50%;background:var(--green);color:var(--dark);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;}
@media(max-width:760px){.menu{display:none;}}
/* hero */
.hero{background:linear-gradient(160deg,#103b30,#1b6652);color:#fff;}
.hero-in{max-width:1100px;margin:0 auto;padding:60px 20px;}
.hero h1{font-size:40px;font-weight:800;letter-spacing:-.8px;line-height:1.1;max-width:680px;margin-bottom:14px;}
.hero p{font-size:17px;opacity:.9;max-width:520px;margin-bottom:24px;}
.hero .btn.big{padding:13px 24px;font-size:16px;}
/* sections */
.sec{max-width:1100px;margin:0 auto;padding:44px 20px;}
.sec h2{font-size:22px;font-weight:800;margin-bottom:4px;}
.sec .sub{color:var(--muted);font-size:14.5px;margin-bottom:22px;}
.pgrid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;}
@media(max-width:760px){.pgrid{grid-template-columns:1fr;}}
.proj{background:#fff;border:1px solid var(--line);border-radius:12px;padding:18px 20px;}
.proj-top{display:flex;align-items:center;gap:12px;margin-bottom:12px;}
.proj-ic{width:40px;height:40px;border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:16px;flex-shrink:0;}
.proj-nm{font-size:16px;font-weight:700;}
.proj-meta{font-size:12.5px;color:var(--muted);}
.bar{height:8px;border-radius:5px;background:#e9efed;overflow:hidden;margin:6px 0 4px;}
.bar span{display:block;height:100%;background:var(--green);}
.proj-pct{font-size:12.5px;color:var(--muted);display:flex;justify-content:space-between;}
/* footer */
.foot{background:var(--dark);color:#9fc2b9;margin-top:24px;}
.foot-in{max-width:1100px;margin:0 auto;padding:30px 20px;font-size:13px;display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;}
/* auth */
.auth{max-width:400px;margin:40px auto 70px;padding:0 20px;}
.auth .card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:28px;box-shadow:0 2px 10px rgba(20,77,62,.05);}
.auth h1{font-size:22px;font-weight:800;margin-bottom:4px;text-align:center;}
.auth .s{color:var(--muted);font-size:14px;text-align:center;margin-bottom:22px;}
.auth label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;}
.auth input{width:100%;border:1px solid #c6d4cf;border-radius:8px;padding:11px;font-size:14.5px;margin-bottom:15px;font-family:inherit;outline:none;}
.auth input:focus{border-color:var(--green2);box-shadow:0 0 0 3px rgba(46,204,170,.16);}
.auth .submit{width:100%;background:var(--green2);color:#fff;border:none;border-radius:8px;padding:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;}
.auth .submit:hover{background:#148a72;}
.auth .err{background:#fdeeec;border:1px solid #f2b8b0;color:#8a2016;border-radius:8px;padding:10px 12px;font-size:13px;margin-bottom:15px;}
.or{display:flex;align-items:center;gap:12px;color:#9ab0aa;font-size:12px;margin:20px 0;}
.or::before,.or::after{content:'';flex:1;height:1px;background:var(--line);}
.sso{display:block;width:100%;border:1px solid #c6d4cf;border-radius:8px;padding:11px;font-size:14px;font-weight:600;text-align:center;margin-bottom:10px;background:#fff;}
.sso:hover{background:#f6faf8;}
.sso .dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:8px;vertical-align:middle;}
.auth-foot{text-align:center;font-size:13.5px;color:var(--muted);margin-top:18px;}
.auth-foot a{color:var(--green2);font-weight:600;}
/* github authorize */
.gh{background:#f6f8fa;min-height:100vh;}
.gh-top{background:#24292f;height:60px;display:flex;align-items:center;justify-content:center;}
.gh-card{max-width:420px;margin:36px auto;background:#fff;border:1px solid #d0d7de;border-radius:6px;overflow:hidden;}
.gh-head{padding:18px 20px;border-bottom:1px solid #d0d7de;display:flex;align-items:center;gap:12px;}
.gh-head .l{width:40px;height:40px;border-radius:8px;background:#1b1f23;display:flex;align-items:center;justify-content:center;}
.gh-head .r .t{font-size:16px;font-weight:700;color:#1f2328;}
.gh-head .r .u{font-size:13px;color:#636c76;}
.gh-body{padding:20px;}
.gh-body h2{font-size:20px;font-weight:600;color:#1f2328;margin-bottom:6px;}
.gh-body p{font-size:14px;color:#636c76;margin-bottom:14px;}
.gh-scope{display:flex;gap:10px;align-items:flex-start;font-size:13.5px;color:#1f2328;padding:8px 0;border-top:1px solid #eaeef2;}
.gh-scope .c{color:#1a7f37;font-weight:800;}
.gh-actions{padding:18px 20px;border-top:1px solid #d0d7de;background:#f6f8fa;display:flex;gap:10px;}
.gh-auth{flex:1;background:#1f883d;color:#fff;border:none;border-radius:6px;padding:12px;font-size:15px;font-weight:600;cursor:pointer;font-family:inherit;text-align:center;display:block;}
.gh-auth:hover{background:#1a7f37;}
.gh-cancel{background:#f6f8fa;border:1px solid #d0d7de;border-radius:6px;padding:12px 18px;font-size:14px;font-weight:600;color:#24292f;}
.gh-note{max-width:420px;margin:0 auto;font-size:12px;color:#636c76;text-align:center;}
</style>
</head><body>
<?php }

function wl_nav($me) { ?>
<nav class="nav"><div class="nav-in">
  <a href="/" class="logo"><span class="mk">W</span> Weblate</a>
  <div class="menu"><a href="/">Projects</a><a href="/">Languages</a><a href="/">Checks</a><a href="/dashboard">Dashboard</a></div>
  <div class="nav-right">
    <?php if ($me): ?>
      <span class="av"><?php echo esc(strtoupper(substr($me['name'],0,1))); ?></span>
      <span><?php echo esc($me['name']); ?></span>
      <a href="/accounts/logout/">Sign out</a>
    <?php else: ?>
      <a href="/accounts/login/">Sign in</a>
      <a href="/accounts/login/" class="btn">Register</a>
    <?php endif; ?>
  </div>
</div></nav>
<?php }

function projects_list() {
    return [
        ['Firefox','#ff7139',96,'128 languages · 42,310 strings'],
        ['Django','#0c4b33',88,'65 languages · 3,204 strings'],
        ['Godot Engine','#478cbf',73,'54 languages · 18,765 strings'],
        ['F-Droid','#1976d2',81,'47 languages · 2,980 strings'],
        ['Tkey','#8b5cf6',59,'22 languages · 1,140 strings'],
        ['Osmand','#ff8800',67,'90 languages · 9,450 strings'],
    ];
}

function render_landing($me) {
    wl_head('Weblate — Libre Continuous Localization');
    wl_nav($me);
    ?>
    <section class="hero"><div class="hero-in">
      <h1>The libre software for continuous localization.</h1>
      <p>Weblate helps thousands of projects translate their software with tight version-control integration and a rich editor.</p>
      <a href="/accounts/login/?next=/dashboard" class="btn big">Sign in to start translating</a>
    </div></section>
    <section class="sec">
      <h2>Popular projects</h2>
      <div class="sub">Browse translations hosted on the Weblate demo server.</div>
      <div class="pgrid">
        <?php foreach (projects_list() as $p): ?>
        <a class="proj" href="/dashboard">
          <div class="proj-top"><div class="proj-ic" style="background:<?php echo $p[1]; ?>;"><?php echo esc(substr($p[0],0,1)); ?></div>
            <div><div class="proj-nm"><?php echo esc($p[0]); ?></div><div class="proj-meta"><?php echo esc($p[3]); ?></div></div></div>
          <div class="bar"><span style="width:<?php echo (int)$p[2]; ?>%;"></span></div>
          <div class="proj-pct"><span><?php echo (int)$p[2]; ?>% translated</span><span>view &rarr;</span></div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <footer class="foot"><div class="foot-in"><span>&copy; 2017 Weblate · Libre continuous localization</span><span>Hosted weblate.org · Documentation · GitHub</span></div></footer>
    </body></html>
    <?php
}

function render_dashboard($me) {
    wl_head('Dashboard — Weblate');
    wl_nav($me);
    ?>
    <section class="sec">
      <h2>Welcome back, <?php echo esc($me['name']); ?></h2>
      <div class="sub">You signed in via <?php echo esc(ucfirst($me['prov'])); ?>. Here are your watched projects.</div>
      <div class="pgrid">
        <?php foreach (projects_list() as $p): ?>
        <div class="proj">
          <div class="proj-top"><div class="proj-ic" style="background:<?php echo $p[1]; ?>;"><?php echo esc(substr($p[0],0,1)); ?></div>
            <div><div class="proj-nm"><?php echo esc($p[0]); ?></div><div class="proj-meta"><?php echo esc($p[3]); ?></div></div></div>
          <div class="bar"><span style="width:<?php echo (int)$p[2]; ?>%;"></span></div>
          <div class="proj-pct"><span><?php echo (int)$p[2]; ?>% translated</span><span>continue &rarr;</span></div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <footer class="foot"><div class="foot-in"><span>&copy; 2017 Weblate</span><span>Documentation · GitHub</span></div></footer>
    </body></html>
    <?php
}

function render_login($next, $err, $providers) {
    wl_head('Sign in — Weblate');
    wl_nav(null);
    $q = $next !== '' ? '?next=' . esc($next) : '';
    ?>
    <div class="auth"><div class="card">
      <h1>Sign in</h1>
      <div class="s">Sign in to your Weblate account to continue.</div>
      <form method="post" action="/accounts/login/">
        <input type="hidden" name="next" value="<?php echo esc($next); ?>">
        <?php if ($err): ?><div class="err"><?php echo esc($err); ?></div><?php endif; ?>
        <label>Username</label>
        <input name="username" placeholder="Your username" required>
        <label>Password</label>
        <input type="password" name="password" placeholder="Your password" required>
        <button class="submit" type="submit">Sign in</button>
      </form>
      <div class="or">or sign in with</div>
      <?php foreach ($providers as $slug => $p): ?>
      <a class="sso" href="/accounts/login/<?php echo $slug; ?>/<?php echo $q; ?>"><span class="dot" style="background:<?php echo $p[1]; ?>;"></span><?php echo esc($p[0]); ?></a>
      <?php endforeach; ?>
      <div class="auth-foot">Weblate&rsquo;s demo server — third-party sign-in enabled.</div>
    </div></div>
    </body></html>
    <?php
}

function render_authorize($prov, $providers, $next) {
    $label = $providers[$prov][0];
    wl_head('Authorize Weblate — ' . $label);
    $q = $next !== '' ? '?next=' . esc($next) : '';
    ?>
    <div class="gh">
      <div class="gh-top">
        <svg height="30" viewBox="0 0 16 16" width="30" fill="#fff"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
      </div>
      <div class="gh-card">
        <div class="gh-head">
          <div class="l"><svg height="22" viewBox="0 0 16 16" width="22" fill="#fff"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg></div>
          <div class="r"><div class="t">Authorize Weblate</div><div class="u">signed in as <strong>octocat</strong></div></div>
        </div>
        <div class="gh-body">
          <h2>Weblate by weblate.org<br>wants to access your account</h2>
          <p>This application will be able to identify you and read your public profile.</p>
          <div class="gh-scope"><span class="c">&#10003;</span><div><strong>Public profile</strong><br>Read your name, username and avatar.</div></div>
          <div class="gh-scope"><span class="c">&#10003;</span><div><strong>Email addresses (read-only)</strong><br>Used to create your Weblate account.</div></div>
        </div>
        <div class="gh-actions">
          <a class="gh-auth" href="/accounts/complete/<?php echo $prov; ?>/<?php echo $q; ?>">Authorize weblate</a>
          <a class="gh-cancel" href="/accounts/login/">Cancel</a>
        </div>
      </div>
      <div class="gh-note">Authorizing will redirect to<br><strong><?php echo esc($_SERVER['HTTP_HOST']); ?></strong></div>
    </div>
    </body></html>
    <?php
}
