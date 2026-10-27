<?php
// Slack — Acme workspace
session_start();

$db = @new mysqli(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', getenv('DB_NAME') ?: 'KrazePlanet');
if (!$db->connect_error) {
    $db->query("CREATE TABLE IF NOT EXISTS slack_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name  VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function current_user($db) {
    if (empty($_SESSION['suid']) || !$db || $db->connect_error) return null;
    $st = $db->prepare("SELECT * FROM slack_users WHERE id=?");
    $st->bind_param('i', $_SESSION['suid']); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function initials($name) {
    $p = preg_split('/\s+/', trim($name));
    $i = strtoupper(substr($p[0], 0, 1));
    if (count($p) > 1) $i .= strtoupper(substr($p[count($p)-1], 0, 1));
    return $i !== '' ? $i : 'U';
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');
if ($path === '') $path = '/';

// ── Outbound link wrapper: /llink?url= sends the user on to url ───────────────
if ($path === '/llink') {
    $url = $_GET['url'] ?? '';
    if ($url !== '') { header('Location: ' . $url); exit; }
    header('Location: /'); exit;
}

$me = current_user($db);

// ── Sign in (posts to the workspace root) → return the member to ?redir= ──────
if ($path === '/' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $redir = $_POST['redir'] ?? '';
    $email = strtolower(trim($_POST['email'] ?? '')); $pass = $_POST['password'] ?? '';
    $u = null;
    if ($db && !$db->connect_error) {
        $st = $db->prepare("SELECT * FROM slack_users WHERE email=?");
        $st->bind_param('s', $email); $st->execute();
        $u = $st->get_result()->fetch_assoc();
    }
    if ($u && password_verify($pass, $u['password_hash'])) {
        $_SESSION['suid'] = (int)$u['id'];
        header('Location: ' . ($redir !== '' ? $redir : '/messages'));
        exit;
    }
    render_signin($redir, 'Sorry, you entered an incorrect email address or password.');
    exit;
}

// ── Create an account → also honours ?redir= ─────────────────────────────────
if ($path === '/register' || $path === '/signup') {
    $redir = $_POST['redir'] ?? ($_GET['redir'] ?? '');
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $pass = $_POST['password'] ?? '';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
            $err = 'Enter your name, a valid email, and a password of at least 6 characters.';
        } elseif (!$db || $db->connect_error) {
            $err = 'Service unavailable. Please try again later.';
        } else {
            $st = $db->prepare("SELECT id FROM slack_users WHERE email=?");
            $st->bind_param('s', $email); $st->execute();
            if ($st->get_result()->fetch_row()) {
                $err = 'An account with that email already exists.';
            } else {
                $h = password_hash($pass, PASSWORD_BCRYPT);
                $st = $db->prepare("INSERT INTO slack_users (name,email,password_hash) VALUES (?,?,?)");
                $st->bind_param('sss', $name, $email, $h); $st->execute();
                $_SESSION['suid'] = $db->insert_id;
                header('Location: ' . ($redir !== '' ? $redir : '/messages'));
                exit;
            }
        }
    }
    render_signup($redir, $err);
    exit;
}

// ── Sign out ─────────────────────────────────────────────────────────────────
if ($path === '/signout') { unset($_SESSION['suid']); header('Location: /'); exit; }

// ── The workspace (requires sign-in) ─────────────────────────────────────────
if ($path === '/messages' || $path === '/client') {
    if (!$me) { header('Location: /?redir=' . $path); exit; }   // bounce to sign-in, remembering where
    render_workspace($me);
    exit;
}

// ── Root: workspace when signed in, otherwise the sign-in screen ─────────────
if ($me) { header('Location: /messages'); exit; }
$redir = $_GET['redir'] ?? '';
// Slack's sign-in always carries where to send you afterwards. Default it so the
// parameter is present in the URL.
if ($redir === '') { header('Location: /?redir=llink?url=/messages'); exit; }
render_signin($redir, '');

// =====================================================================
//  VIEWS
// =====================================================================
function slack_logo($size = 26) {
    return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 122 122"><path fill="#36C5F0" d="M25.8 77c0 7.1-5.8 12.9-12.9 12.9S0 84.1 0 77s5.8-12.9 12.9-12.9h12.9V77zM32.3 77c0-7.1 5.8-12.9 12.9-12.9s12.9 5.8 12.9 12.9v32.1c0 7.1-5.8 12.9-12.9 12.9s-12.9-5.8-12.9-12.9V77z"/><path fill="#2EB67D" d="M45.2 25.3c-7.1 0-12.9-5.8-12.9-12.9S38.1-.5 45.2-.5 58.1 5.3 58.1 12.4v12.9H45.2zM45.2 31.8c7.1 0 12.9 5.8 12.9 12.9s-5.8 12.9-12.9 12.9H13.1C6 57.6.2 51.8.2 44.7s5.8-12.9 12.9-12.9h32.1z"/><path fill="#ECB22E" d="M96.9 44.7c0-7.1 5.8-12.9 12.9-12.9s12.9 5.8 12.9 12.9-5.8 12.9-12.9 12.9H96.9V44.7zM90.4 44.7c0 7.1-5.8 12.9-12.9 12.9s-12.9-5.8-12.9-12.9V12.6c0-7.1 5.8-12.9 12.9-12.9s12.9 5.8 12.9 12.9v32.1z"/><path fill="#E01E5A" d="M77.5 96.4c7.1 0 12.9 5.8 12.9 12.9s-5.8 12.9-12.9 12.9-12.9-5.8-12.9-12.9V96.4h12.9zM77.5 89.9c-7.1 0-12.9-5.8-12.9-12.9s5.8-12.9 12.9-12.9h32.1c7.1 0 12.9 5.8 12.9 12.9s-5.8 12.9-12.9 12.9H77.5z"/></svg>';
}

function auth_head($title) { ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($title); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--aubergine:#4a154b;--aub2:#611f69;--ink:#1d1c1d;--muted:#616061;--line:#e0e0e0;--blue:#1264a3;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:var(--ink);background:#fff;line-height:1.5;}
a{text-decoration:none;color:var(--blue);}
.top{max-width:1000px;margin:0 auto;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;}
.brand{display:flex;align-items:center;gap:10px;font-size:22px;font-weight:800;color:var(--ink);}
.top .r{font-size:14px;color:var(--muted);}
.top .r a{font-weight:700;}
.auth{max-width:420px;margin:30px auto 60px;padding:0 24px;text-align:center;}
.ws{display:inline-flex;align-items:center;gap:8px;background:#f8f8f8;border:1px solid var(--line);border-radius:8px;padding:6px 12px;font-size:13px;color:var(--muted);font-weight:600;margin-bottom:20px;}
.ws b{color:var(--ink);}
.auth h1{font-size:34px;font-weight:900;letter-spacing:-1px;margin-bottom:10px;}
.auth .s{color:var(--muted);font-size:15px;margin-bottom:28px;}
.card{background:#fff;border:1px solid var(--line);border-radius:10px;padding:26px;text-align:left;box-shadow:0 1px 3px rgba(0,0,0,.04);}
.card label{display:block;font-size:13px;font-weight:700;margin-bottom:6px;}
.card input{width:100%;border:1px solid #868686;border-radius:8px;padding:12px;font-size:15px;margin-bottom:16px;font-family:inherit;outline:none;}
.card input:focus{border-color:var(--aubergine);box-shadow:0 0 0 3px rgba(74,21,75,.12);}
.auth-btn{width:100%;background:var(--aubergine);color:#fff;border:none;border-radius:8px;padding:14px;font-size:16px;font-weight:800;cursor:pointer;font-family:inherit;}
.auth-btn:hover{background:#3d1140;}
.err{background:#fdedeb;border:1px solid #f2b4ad;color:#8a1c10;border-radius:8px;padding:11px 13px;font-size:13.5px;margin-bottom:16px;}
.alt{font-size:14px;color:var(--muted);margin-top:22px;}
.divider{display:flex;align-items:center;gap:12px;color:#9a9a9a;font-size:12px;margin:18px 0;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--line);}
.open-ws{display:inline-block;margin-top:10px;font-size:13.5px;font-weight:700;}
.foot{border-top:1px solid var(--line);max-width:1000px;margin:0 auto;padding:22px 24px;font-size:12.5px;color:#9a9a9a;display:flex;gap:18px;flex-wrap:wrap;justify-content:center;}
</style></head><body>
<div class="top">
  <div class="brand"><?php echo slack_logo(28); ?> slack</div>
  <div class="r">New to Slack? <a href="/register">Create an account</a></div>
</div>
<?php }

function render_signin($redir, $err) {
    auth_head('Sign in to Acme | Slack');
    ?>
    <div class="auth">
      <div class="ws"><?php echo slack_logo(16); ?> <b>acme</b>.slack.com</div>
      <h1>Sign in to Acme</h1>
      <div class="s">We suggest using the email address you use at work.</div>
      <div class="card">
        <form method="post" action="/">
          <input type="hidden" name="redir" value="<?php echo esc($redir); ?>">
          <?php if ($err): ?><div class="err"><?php echo esc($err); ?></div><?php endif; ?>
          <label>Email address</label>
          <input type="email" name="email" placeholder="name@work-email.com" required autocomplete="username">
          <label>Password</label>
          <input type="password" name="password" placeholder="Your password" required autocomplete="current-password">
          <button class="auth-btn" type="submit">Sign In</button>
        </form>
      </div>
      <div class="alt">Don&rsquo;t have an account? <a href="/register?redir=<?php echo rawurlencode($redir); ?>">Create one</a></div>
      <a class="open-ws" href="/messages">Open the Acme workspace &rarr;</a>
    </div>
    <div class="foot"><span>&copy; 2014 Slack Technologies</span><a href="/">Privacy &amp; Terms</a><a href="/">Contact Us</a><a href="/">Help</a></div>
    </body></html>
    <?php
}

function render_signup($redir, $err) {
    auth_head('Create your account | Slack');
    ?>
    <div class="auth">
      <div class="ws"><?php echo slack_logo(16); ?> <b>acme</b>.slack.com</div>
      <h1>Create your account</h1>
      <div class="s">Join the Acme workspace on Slack.</div>
      <div class="card">
        <form method="post" action="/register">
          <input type="hidden" name="redir" value="<?php echo esc($redir); ?>">
          <?php if ($err): ?><div class="err"><?php echo esc($err); ?></div><?php endif; ?>
          <label>Full name</label>
          <input type="text" name="name" placeholder="Your name" required>
          <label>Email address</label>
          <input type="email" name="email" placeholder="name@work-email.com" required autocomplete="username">
          <label>Password</label>
          <input type="password" name="password" placeholder="At least 6 characters" required autocomplete="new-password">
          <button class="auth-btn" type="submit">Create Account</button>
        </form>
      </div>
      <div class="alt">Already using Slack? <a href="/">Sign in</a></div>
    </div>
    <div class="foot"><span>&copy; 2014 Slack Technologies</span><a href="/">Privacy &amp; Terms</a><a href="/">Contact Us</a><a href="/">Help</a></div>
    </body></html>
    <?php
}

function render_workspace($me) {
    $av = initials($me['name']);
    $channels = ['general','random','design','engineering','marketing'];
    $dms = [['Alex Kim','#2eb67d','online'],['Priya Nair','#e01e5a','online'],['Sam Cho','#ecb22e','away']];
    $msgs = [
        ['Alex Kim','#2eb67d','9:02 AM','Morning team! Standup in 10 — anything blocking you?'],
        ['Priya Nair','#e01e5a','9:04 AM','Shipping the checkout fix this morning. Need a quick review on the PR 🙏'],
        ['Sam Cho','#ecb22e','9:06 AM','I can take that review after standup.'],
        ['Priya Nair','#e01e5a','9:07 AM','Perfect, thanks Sam!'],
        ['Alex Kim','#2eb67d','9:12 AM','Reminder: design sync moved to 2pm in #design.'],
    ];
    ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acme | general | Slack</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#1d1c1d;height:100vh;overflow:hidden;}
a{text-decoration:none;color:inherit;}
.shell{display:grid;grid-template-columns:260px 1fr;height:100vh;}
.side{background:#3f0e40;color:#cfc3cf;display:flex;flex-direction:column;}
.side-top{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:space-between;}
.side-top .nm{font-size:17px;font-weight:800;color:#fff;}
.side-top .you{width:26px;height:26px;border-radius:6px;background:#1264a3;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;}
.side-sec{padding:14px 16px 4px;font-size:13px;color:#b9a9ba;font-weight:600;}
.ch{display:flex;align-items:center;gap:8px;padding:5px 16px;font-size:15px;color:#cfc3cf;cursor:pointer;}
.ch:hover{background:rgba(255,255,255,.06);}
.ch.active{background:#1164a3;color:#fff;}
.ch .h{opacity:.7;}
.dot{width:9px;height:9px;border-radius:50%;display:inline-block;border:1.5px solid #cfc3cf;}
.dot.on{background:#2bac76;border-color:#2bac76;}
.side-bot{margin-top:auto;padding:14px 16px;border-top:1px solid rgba(255,255,255,.1);font-size:13px;}
.side-bot a{color:#cfc3cf;}
.side-bot a:hover{color:#fff;}
.main{display:flex;flex-direction:column;min-width:0;background:#fff;}
.ch-head{border-bottom:1px solid #e8e8e8;padding:14px 20px;display:flex;align-items:center;gap:8px;}
.ch-head .t{font-size:18px;font-weight:800;}
.ch-head .meta{color:#616061;font-size:13px;margin-left:8px;}
.feed{flex:1;overflow-y:auto;padding:16px 20px;}
.msg{display:flex;gap:10px;padding:8px 0;}
.msg .av{width:36px;height:36px;border-radius:8px;color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0;}
.msg .who{font-weight:800;font-size:15px;}
.msg .ts{color:#9a9a9a;font-size:12px;margin-left:8px;font-weight:500;}
.msg .txt{font-size:15px;margin-top:2px;}
.compose{border-top:1px solid #e8e8e8;padding:14px 20px;}
.compose .box{border:1px solid #868686;border-radius:10px;padding:12px 14px;color:#9a9a9a;font-size:14px;}
</style></head><body>
<div class="shell">
  <aside class="side">
    <div class="side-top"><span class="nm">Acme</span><span class="you"><?php echo esc($av); ?></span></div>
    <div class="side-sec">Channels</div>
    <?php foreach ($channels as $i => $c): ?>
    <div class="ch<?php echo $i === 0 ? ' active' : ''; ?>"><span class="h">#</span><?php echo esc($c); ?></div>
    <?php endforeach; ?>
    <div class="side-sec">Direct messages</div>
    <?php foreach ($dms as $d): ?>
    <div class="ch"><span class="dot <?php echo $d[2] === 'online' ? 'on' : ''; ?>"></span><?php echo esc($d[0]); ?></div>
    <?php endforeach; ?>
    <div class="side-bot"><?php echo esc($me['name']); ?> &middot; <a href="/signout">Sign out</a></div>
  </aside>
  <section class="main">
    <div class="ch-head"><span style="font-size:18px;">#</span><span class="t">general</span><span class="meta">Company-wide announcements and chatter.</span></div>
    <div class="feed">
      <?php foreach ($msgs as $m): ?>
      <div class="msg">
        <span class="av" style="background:<?php echo $m[1]; ?>;"><?php echo esc(strtoupper(substr($m[0],0,1))); ?></span>
        <div><div><span class="who"><?php echo esc($m[0]); ?></span><span class="ts"><?php echo esc($m[2]); ?></span></div><div class="txt"><?php echo esc($m[3]); ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="compose"><div class="box">Message #general</div></div>
  </section>
</div>
</body></html>
    <?php
}
