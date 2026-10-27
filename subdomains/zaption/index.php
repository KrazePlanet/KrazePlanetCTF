<?php
// Zaption — interactive video lessons
session_start();

$db = @new mysqli(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', getenv('DB_NAME') ?: 'KrazePlanet');
if (!$db->connect_error) {
    $db->query("CREATE TABLE IF NOT EXISTS zaption_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name  VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function img($id) { return "https://images.unsplash.com/{$id}?w=640&q=70&auto=format&fit=crop"; }
function current_user($db) {
    if (empty($_SESSION['zuid']) || !$db || $db->connect_error) return null;
    $st = $db->prepare("SELECT * FROM zaption_users WHERE id=?");
    $st->bind_param('i', $_SESSION['zuid']); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function initials($name) {
    $p = preg_split('/\s+/', trim($name));
    $i = strtoupper(substr($p[0], 0, 1));
    if (count($p) > 1) $i .= strtoupper(substr($p[count($p)-1], 0, 1));
    return $i !== '' ? $i : 'U';
}

$host     = $_SERVER['HTTP_HOST'];
$hostOnly = preg_replace('/:\d+$/', '', $host);
$path     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path     = rtrim($path, '/');
if ($path === '') $path = '/';

// ── Logout → returnTo ─────────────────────────────────────────────────────────
// After signing out the user is sent back to wherever ?returnTo= points.
if ($path === '/logout') {
    unset($_SESSION['zuid']);
    $returnTo = $_GET['returnTo'] ?? '';
    if ($returnTo === '') { header('Location: /'); exit; }
    header('Location: ' . $returnTo);
    exit;
}

// ── Log in ────────────────────────────────────────────────────────────────────
if ($path === '/login') {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = strtolower(trim($_POST['email'] ?? '')); $pass = $_POST['password'] ?? '';
        $u = null;
        if ($db && !$db->connect_error) {
            $st = $db->prepare("SELECT * FROM zaption_users WHERE email=?");
            $st->bind_param('s', $email); $st->execute();
            $u = $st->get_result()->fetch_assoc();
        }
        if ($u && password_verify($pass, $u['password_hash'])) {
            $_SESSION['zuid'] = (int)$u['id']; header('Location: /'); exit;
        }
        $err = 'Incorrect email or password.';
    }
    render_auth('login', null, $err); exit;
}

// ── Create an account ─────────────────────────────────────────────────────────
if ($path === '/register' || $path === '/signup') {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $pass = $_POST['password'] ?? '';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
            $err = 'Enter your name, a valid email, and a password of at least 6 characters.';
        } elseif (!$db || $db->connect_error) {
            $err = 'Service unavailable. Please try again later.';
        } else {
            $st = $db->prepare("SELECT id FROM zaption_users WHERE email=?");
            $st->bind_param('s', $email); $st->execute();
            if ($st->get_result()->fetch_row()) {
                $err = 'An account with that email already exists.';
            } else {
                $h = password_hash($pass, PASSWORD_BCRYPT);
                $st = $db->prepare("INSERT INTO zaption_users (name,email,password_hash) VALUES (?,?,?)");
                $st->bind_param('sss', $name, $email, $h); $st->execute();
                $_SESSION['zuid'] = $db->insert_id; header('Location: /'); exit;
            }
        }
    }
    render_auth('register', null, $err); exit;
}

$me = current_user($db);

$lessons = [
    ['t'=>'Cell Structure &amp; Function','s'=>'Biology','sc'=>'#16a34a','a'=>'Ms. Rivera','v'=>'1,204','q'=>8,'img'=>'photo-1532094349884-543bc11b234d'],
    ['t'=>'Balancing Chemical Equations','s'=>'Chemistry','sc'=>'#db2777','a'=>'Mr. Adeyemi','v'=>'892','q'=>12,'img'=>'photo-1554475901-4538ddfbccc2'],
    ['t'=>'Our Solar System','s'=>'Astronomy','sc'=>'#6d28d9','a'=>'Dr. Osei','v'=>'2,310','q'=>6,'img'=>'photo-1451187580459-43490279c0fa'],
    ['t'=>'The Industrial Revolution','s'=>'History','sc'=>'#b45309','a'=>'Ms. Lind','v'=>'1,567','q'=>10,'img'=>'photo-1461360228754-6e81c478b882'],
    ['t'=>'Intro to Quadratic Equations','s'=>'Algebra','sc'=>'#2563eb','a'=>'Mr. Park','v'=>'3,045','q'=>15,'img'=>'photo-1635070041078-e363dbe005cb'],
    ['t'=>'Continents &amp; Oceans','s'=>'Geography','sc'=>'#0891b2','a'=>'Ms. Rivera','v'=>'760','q'=>5,'img'=>'photo-1526778548025-fa2f459cd5c1'],
];

zaption_head('Zaption — Interactive Video Lessons');
zaption_nav($me);
?>
<section class="hero"><div class="hero-in">
  <div>
    <h1>Turn any video into an interactive lesson.</h1>
    <p>Add questions, discussions and polls to videos — then see exactly how your students respond, moment by moment.</p>
    <div class="hero-cta"><a href="<?php echo $me ? '/' : '/register'; ?>" class="btn-white">Create a lesson</a><a href="/" class="btn-outline">Browse the gallery</a></div>
  </div>
  <div class="hero-media">
    <img src="<?php echo img('photo-1522202176988-66273c2fd55f'); ?>" alt="" onerror="this.remove()">
    <div class="play"><span><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span></div>
  </div>
</div></section>

<section class="sec">
  <div class="sec-head">
    <div><h2>Popular lessons</h2><div class="sub">Interactive video lessons from educators around the world.</div></div>
    <a href="/" style="font-size:13.5px;color:var(--blue);font-weight:600;">View gallery &rarr;</a>
  </div>
  <div class="grid">
    <?php foreach ($lessons as $l): ?>
    <a class="lesson" href="/">
      <div class="thumb">
        <img src="<?php echo img($l['img']); ?>" alt="" loading="lazy" onerror="this.style.background='#cbd5e1'">
        <span class="tag" style="background:<?php echo $l['sc']; ?>;"><?php echo esc($l['s']); ?></span>
        <span class="qz"><?php echo (int)$l['q']; ?> questions</span>
        <div class="play"><span><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span></div>
      </div>
      <div class="body">
        <h3><?php echo $l['t']; ?></h3>
        <div class="meta"><span class="a"><span class="d"><?php echo esc(strtoupper($l['a'][0])); ?></span><?php echo esc($l['a']); ?></span><span>·</span><span><?php echo esc($l['v']); ?> views</span></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<?php zaption_footer(); ?>
</body>
</html>
<?php

// =====================================================================
//  SHARED CHROME
// =====================================================================
function zaption_head($title) { ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($title); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--blue:#1d4ed8;--blue2:#2563eb;--teal:#06b6d4;--ink:#0f172a;--muted:#64748b;--line:#e6ebf2;--bg:#f6f8fb;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:var(--ink);background:#fff;line-height:1.55;}
a{text-decoration:none;color:inherit;}
.wrap{max-width:1120px;margin:0 auto;padding:0 20px;}
.nav{border-bottom:1px solid var(--line);position:sticky;top:0;background:#fff;z-index:20;}
.nav-in{max-width:1120px;margin:0 auto;padding:13px 20px;display:flex;align-items:center;gap:30px;}
.logo{display:flex;align-items:center;gap:9px;font-size:21px;font-weight:800;color:var(--blue);}
.logo .pl{width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,var(--blue2),var(--teal));display:flex;align-items:center;justify-content:center;}
.menu{display:flex;gap:22px;font-size:14px;font-weight:600;color:var(--muted);}
.menu a:hover,.menu a.active{color:var(--ink);}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:14px;}
.btn{background:var(--blue);color:#fff;border:none;border-radius:8px;padding:9px 16px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;}
.btn:hover{background:#1736a8;}
.login-link{font-size:14px;font-weight:600;color:var(--muted);}
.login-link:hover{color:var(--ink);}
.acct{position:relative;display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;font-weight:600;}
.av{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#06b6d4);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12.5px;font-weight:700;}
.acct-menu{position:absolute;top:44px;right:0;background:#fff;border:1px solid var(--line);border-radius:10px;box-shadow:0 10px 34px rgba(15,23,42,.14);min-width:180px;overflow:hidden;display:none;z-index:40;}
.acct.open .acct-menu{display:block;}
.acct-menu a{display:block;padding:11px 15px;font-size:14px;border-bottom:1px solid #f1f5f9;font-weight:500;}
.acct-menu a:last-child{border-bottom:none;color:#dc2626;}
.acct-menu a:hover{background:#f6f9fc;}
@media(max-width:760px){.menu{display:none;}}
.hero{background:linear-gradient(120deg,#0b2a6b,#1d4ed8 60%,#06b6d4);color:#fff;}
.hero-in{max-width:1120px;margin:0 auto;padding:54px 20px;display:grid;grid-template-columns:1.1fr .9fr;gap:40px;align-items:center;}
@media(max-width:820px){.hero-in{grid-template-columns:1fr;}}
.hero h1{font-size:38px;font-weight:800;letter-spacing:-.6px;line-height:1.1;margin-bottom:14px;}
.hero p{font-size:16.5px;opacity:.92;margin-bottom:24px;max-width:460px;}
.hero-cta{display:flex;gap:12px;flex-wrap:wrap;}
.btn-white{background:#fff;color:var(--blue);border-radius:8px;padding:12px 22px;font-size:15px;font-weight:700;}
.btn-outline{border:1px solid rgba(255,255,255,.5);color:#fff;border-radius:8px;padding:12px 22px;font-size:15px;font-weight:600;}
.hero-media{border-radius:14px;overflow:hidden;position:relative;box-shadow:0 20px 50px rgba(0,0,0,.3);aspect-ratio:16/10;}
.hero-media img{width:100%;height:100%;object-fit:cover;}
.hero-media .play{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;}
.hero-media .play span{width:62px;height:62px;border-radius:50%;background:rgba(255,255,255,.92);display:flex;align-items:center;justify-content:center;color:var(--blue);box-shadow:0 8px 24px rgba(0,0,0,.3);}
.sec{max-width:1120px;margin:0 auto;padding:44px 20px;}
.sec-head{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:22px;}
.sec-head h2{font-size:24px;font-weight:800;letter-spacing:-.3px;}
.sec-head .sub{color:var(--muted);font-size:14.5px;}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;}
@media(max-width:820px){.grid{grid-template-columns:1fr 1fr;}}
@media(max-width:560px){.grid{grid-template-columns:1fr;}}
.lesson{background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;transition:box-shadow .2s,transform .15s;}
.lesson:hover{box-shadow:0 12px 34px rgba(15,23,42,.12);transform:translateY(-2px);}
.thumb{position:relative;aspect-ratio:16/9;overflow:hidden;background:#dbe3ee;}
.thumb img{width:100%;height:100%;object-fit:cover;}
.thumb::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 55%,rgba(0,0,0,.35));}
.thumb .play{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;z-index:2;}
.thumb .play span{width:46px;height:46px;border-radius:50%;background:rgba(255,255,255,.9);display:flex;align-items:center;justify-content:center;color:var(--blue);}
.thumb .tag{position:absolute;top:10px;left:10px;z-index:2;font-size:11.5px;font-weight:700;color:#fff;padding:3px 9px;border-radius:20px;}
.thumb .qz{position:absolute;bottom:10px;right:10px;z-index:2;font-size:11.5px;font-weight:600;color:#fff;background:rgba(0,0,0,.55);border-radius:6px;padding:2px 8px;}
.lesson .body{padding:14px 16px;}
.lesson h3{font-size:15.5px;font-weight:700;line-height:1.35;margin-bottom:10px;}
.lesson h3:hover{color:var(--blue);}
.lesson .meta{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--muted);}
.lesson .meta .a{display:flex;align-items:center;gap:6px;font-weight:600;color:var(--ink);}
.lesson .meta .a .d{width:22px;height:22px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#475569;}
.foot{background:#0b1324;color:#9fb0c9;margin-top:20px;}
.foot-in{max-width:1120px;margin:0 auto;padding:40px 20px;display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:24px;font-size:13.5px;}
@media(max-width:760px){.foot-in{grid-template-columns:1fr 1fr;}}
.foot .flogo{color:#fff;margin-bottom:10px;display:flex;align-items:center;gap:8px;font-weight:800;font-size:19px;}
.foot h4{color:#fff;font-size:13px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;}
.foot ul{list-style:none;line-height:2;}
.foot-bot{border-top:1px solid rgba(255,255,255,.08);text-align:center;padding:16px;font-size:12.5px;}
/* auth */
.auth-wrap{min-height:calc(100vh - 62px);display:flex;align-items:flex-start;justify-content:center;background:var(--bg);padding:48px 20px;}
.auth-card{background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 10px 34px rgba(15,23,42,.08);width:100%;max-width:410px;padding:30px;}
.auth-card h1{font-size:23px;font-weight:800;margin-bottom:4px;}
.auth-card .s{color:var(--muted);font-size:14px;margin-bottom:22px;}
.auth-card label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;}
.auth-card input{width:100%;border:1px solid var(--line);border-radius:9px;padding:11px;font-size:14.5px;margin-bottom:15px;font-family:inherit;outline:none;}
.auth-card input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.14);}
.auth-btn{width:100%;background:var(--blue);color:#fff;border:none;border-radius:9px;padding:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;}
.auth-btn:hover{background:#1736a8;}
.auth-err{background:#fff0f0;border:1px solid #f3b4b4;color:#a12020;border-radius:8px;padding:10px 12px;font-size:13px;margin-bottom:16px;}
.auth-alt{text-align:center;font-size:13.5px;color:var(--muted);margin-top:18px;}
.auth-alt a{color:var(--blue);font-weight:600;}
</style>
</head>
<body>
<?php }

function zaption_nav($me) { ?>
<nav class="nav"><div class="nav-in">
  <a href="/" class="logo"><span class="pl">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="#fff"><path d="M8 5v14l11-7z"/></svg>
  </span>Zaption</a>
  <div class="menu"><a href="/" class="active">Discover</a><a href="/">Gallery</a><a href="/">My Lessons</a><a href="/">Analytics</a></div>
  <div class="nav-right">
    <?php if ($me): ?>
      <a href="/" class="btn">+ New Lesson</a>
      <div class="acct" onclick="this.classList.toggle('open')">
        <span class="av"><?php echo esc(initials($me['name'])); ?></span> <?php echo esc($me['name']); ?> &#9662;
        <div class="acct-menu" onclick="event.stopPropagation()">
          <a href="/">My lessons</a>
          <a href="/">Account settings</a>
          <a href="/logout?returnTo=/">Sign out</a>
        </div>
      </div>
    <?php else: ?>
      <a href="/login" class="login-link">Log in</a>
      <a href="/register" class="btn">Sign up</a>
    <?php endif; ?>
  </div>
</div></nav>
<script>
document.addEventListener('click', function (e) {
  if (!e.target.closest('.acct')) {
    document.querySelectorAll('.acct.open').forEach(function (a) { a.classList.remove('open'); });
  }
});
</script>
<?php }

function zaption_footer() { ?>
<footer class="foot"><div class="foot-in">
  <div><div class="flogo"><span style="width:24px;height:24px;border-radius:7px;background:linear-gradient(135deg,#2563eb,#06b6d4);display:inline-flex;align-items:center;justify-content:center;"><svg width="13" height="13" viewBox="0 0 24 24" fill="#fff"><path d="M8 5v14l11-7z"/></svg></span>Zaption</div><p style="max-width:230px;">Interactive video lessons that make every student a participant.</p></div>
  <div><h4>Product</h4><ul><li>Create lessons</li><li>Gallery</li><li>Analytics</li><li>Pricing</li></ul></div>
  <div><h4>Company</h4><ul><li>About</li><li>Blog</li><li>Careers</li><li>Contact</li></ul></div>
  <div><h4>Support</h4><ul><li>Help center</li><li>Educators</li><li>Privacy</li><li>Terms</li></ul></div>
</div><div class="foot-bot">&copy; 2016 Zaption, Inc. All rights reserved.</div></footer>
<?php }

function render_auth($mode, $me, $err) {
    $isReg = ($mode === 'register');
    zaption_head(($isReg ? 'Create your account' : 'Log in') . ' — Zaption');
    zaption_nav($me);
    ?>
    <div class="auth-wrap"><div class="auth-card">
      <h1><?php echo $isReg ? 'Create your account' : 'Log in to Zaption'; ?></h1>
      <div class="s"><?php echo $isReg ? 'Build interactive video lessons for your students — free.' : 'Welcome back! Sign in to continue.'; ?></div>
      <form method="post" action="<?php echo $isReg ? '/register' : '/login'; ?>">
        <?php if ($err): ?><div class="auth-err"><?php echo esc($err); ?></div><?php endif; ?>
        <?php if ($isReg): ?>
        <label>Full name</label>
        <input type="text" name="name" placeholder="Your name" required>
        <?php endif; ?>
        <label>Email address</label>
        <input type="email" name="email" placeholder="you@school.edu" required autocomplete="username">
        <label>Password</label>
        <input type="password" name="password" placeholder="<?php echo $isReg ? 'At least 6 characters' : '••••••••'; ?>" required autocomplete="<?php echo $isReg ? 'new-password' : 'current-password'; ?>">
        <button class="auth-btn" type="submit"><?php echo $isReg ? 'Create account' : 'Log in'; ?></button>
      </form>
      <div class="auth-alt">
        <?php echo $isReg ? 'Already have an account? <a href="/login">Log in</a>' : 'New to Zaption? <a href="/register">Create an account</a>'; ?>
      </div>
    </div></div>
    <?php zaption_footer(); ?>
    </body></html>
    <?php
}
