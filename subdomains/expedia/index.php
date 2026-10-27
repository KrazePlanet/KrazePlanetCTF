<?php
// Expedia — travel booking
session_start();

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function img($id) { return "https://images.unsplash.com/{$id}?w=800&q=70&auto=format&fit=crop"; }

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');
if ($path === '') $path = '/';

// ── Sign-out handler (homepage) ───────────────────────────────────────────────
// ?logout=1 just drops the session and returns to the homepage; any other value
// is used as the page to send the user to after signing out.
if (isset($_GET['logout'])) {
    unset($_SESSION['expedia_user']);
    $lv = $_GET['logout'];
    if ($lv !== '' && $lv !== '1') {
        header('Location: ' . $lv);
        exit;
    }
    // otherwise fall through and render the (logged-out) homepage
}

// ── Sign-in + return URL ──────────────────────────────────────────────────────
// After signing in the user is returned to ?rurl=.
if ($path === '/login') {
    $rurl = $_POST['rurl'] ?? ($_GET['rurl'] ?? '');
    $err  = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        if ($email !== '' && $pass !== '') {
            $_SESSION['expedia_user'] = $email;
            if ($rurl !== '' && $rurl !== '1') { header('Location: ' . $rurl); exit; }
            header('Location: /'); exit;
        }
        $err = 'Enter your email and password to sign in.';
    }
    render_login($rurl, $err);
    exit;
}

render_home();

// =====================================================================
//  VIEWS
// =====================================================================
function page_head($title) { ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($title); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--navy:#191e3b;--navy2:#000a3c;--blue:#1668e3;--blue-dark:#0b4bb3;--ink:#1a1a1a;--muted:#5a6b82;--line:#e2e8f0;--bg:#f2f5f9;--yellow:#ffd700;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:var(--ink);background:#fff;line-height:1.5;}
a{text-decoration:none;color:inherit;}
.wrap{max-width:1140px;margin:0 auto;padding:0 20px;}

/* header */
.top{background:var(--navy);color:#fff;}
.top-in{max-width:1140px;margin:0 auto;padding:12px 20px;display:flex;align-items:center;gap:10px;}
.logo{display:flex;align-items:center;gap:9px;font-size:22px;font-weight:800;letter-spacing:-.3px;}
.logo .mark{width:30px;height:30px;border-radius:50%;background:var(--yellow);display:flex;align-items:center;justify-content:center;}
.top-right{margin-left:auto;display:flex;align-items:center;gap:8px;}
.pill{border:1px solid rgba(255,255,255,.3);border-radius:20px;padding:8px 15px;font-size:13.5px;font-weight:600;color:#fff;display:flex;align-items:center;gap:7px;}
.pill:hover{background:rgba(255,255,255,.1);}
.avatar{width:30px;height:30px;border-radius:50%;background:var(--blue);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;}
.acct{position:relative;}
.acct-menu{position:absolute;top:44px;right:0;background:#fff;color:var(--ink);border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,.18);min-width:190px;overflow:hidden;display:none;z-index:40;}
.acct:hover .acct-menu{display:block;}
.acct-menu a{display:block;padding:11px 16px;font-size:14px;border-bottom:1px solid #f1f5f9;}
.acct-menu a:last-child{border-bottom:none;color:#c81e1e;}
.acct-menu a:hover{background:#f6f9fc;}
/* nav tabs */
.tabs{background:var(--navy);border-top:1px solid rgba(255,255,255,.08);}
.tabs-in{max-width:1140px;margin:0 auto;padding:0 20px;display:flex;gap:4px;overflow-x:auto;}
.tab{color:#dfe6f3;font-size:14px;font-weight:600;padding:12px 14px;border-bottom:3px solid transparent;white-space:nowrap;display:flex;align-items:center;gap:7px;}
.tab.active,.tab:hover{color:#fff;border-bottom-color:var(--yellow);}

/* hero */
.hero{position:relative;background:var(--navy);color:#fff;overflow:hidden;}
.hero-bg{position:absolute;inset:0;z-index:0;}
.hero-bg img{width:100%;height:100%;object-fit:cover;opacity:.55;}
.hero-bg::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(25,30,59,.5),rgba(25,30,59,.2));}
.hero-in{position:relative;z-index:1;max-width:1140px;margin:0 auto;padding:46px 20px 90px;}
.hero h1{font-size:34px;font-weight:800;letter-spacing:-.6px;margin-bottom:6px;text-shadow:0 2px 12px rgba(0,0,0,.3);}
.hero p{font-size:16px;opacity:.95;text-shadow:0 1px 8px rgba(0,0,0,.3);}
/* search widget */
.search{max-width:1140px;margin:-56px auto 0;position:relative;z-index:5;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 10px 40px rgba(16,30,54,.14);padding:12px;display:grid;grid-template-columns:2fr 1.4fr 1.4fr auto;gap:10px;}
@media(max-width:820px){.search{grid-template-columns:1fr;margin-top:-30px;}.tabs-in{justify-content:flex-start;}}
.field{border:1px solid var(--line);border-radius:10px;padding:9px 13px;display:flex;align-items:center;gap:10px;}
.field .ic{font-size:16px;}
.field .fl{font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.3px;}
.field input{border:none;outline:none;font-size:14.5px;font-family:inherit;color:var(--ink);width:100%;background:transparent;}
.field .fv{font-size:14.5px;font-weight:600;}
.search .go{background:var(--blue);color:#fff;border:none;border-radius:10px;padding:0 28px;font-size:16px;font-weight:700;cursor:pointer;font-family:inherit;}
.search .go:hover{background:var(--blue-dark);}

/* sections */
.sec{max-width:1140px;margin:0 auto;padding:40px 20px;}
.sec h2{font-size:24px;font-weight:800;letter-spacing:-.3px;margin-bottom:4px;}
.sec .sub{color:var(--muted);font-size:14.5px;margin-bottom:22px;}
.dgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;}
@media(max-width:820px){.dgrid{grid-template-columns:1fr 1fr;}}
.dcard{border-radius:14px;overflow:hidden;position:relative;height:200px;box-shadow:0 4px 18px rgba(16,30,54,.1);}
.dcard img{width:100%;height:100%;object-fit:cover;transition:transform .3s;}
.dcard:hover img{transform:scale(1.05);}
.dcard::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 45%,rgba(0,0,0,.6));}
.dcard .cap{position:absolute;left:14px;bottom:12px;z-index:2;color:#fff;}
.dcard .cap .c{font-size:17px;font-weight:700;}
.dcard .cap .p{font-size:13px;opacity:.9;}

.strip{background:#eaf2fe;border:1px solid #cfe0fb;border-radius:14px;padding:20px 24px;display:flex;align-items:center;gap:16px;margin-top:8px;}
.strip .em{font-size:28px;}
.strip h3{font-size:16px;font-weight:700;}
.strip p{font-size:14px;color:var(--muted);}
.strip .btn{margin-left:auto;background:var(--blue);color:#fff;border-radius:8px;padding:10px 18px;font-size:14px;font-weight:700;}

/* footer */
.foot{background:var(--navy);color:#c3cde0;margin-top:30px;}
.foot-in{max-width:1140px;margin:0 auto;padding:40px 20px;display:grid;grid-template-columns:repeat(4,1fr);gap:24px;}
@media(max-width:820px){.foot-in{grid-template-columns:1fr 1fr;}}
.foot h4{color:#fff;font-size:13px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;}
.foot ul{list-style:none;font-size:13.5px;line-height:2;}
.foot-bot{border-top:1px solid rgba(255,255,255,.1);font-size:12.5px;text-align:center;padding:16px 20px;}

/* login */
.auth-wrap{min-height:calc(100vh - 56px);display:flex;align-items:flex-start;justify-content:center;background:var(--bg);padding:48px 20px;}
.auth-card{background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 8px 30px rgba(16,30,54,.1);width:100%;max-width:420px;padding:32px;}
.auth-card h1{font-size:24px;font-weight:800;margin-bottom:4px;}
.auth-card .s{color:var(--muted);font-size:14px;margin-bottom:22px;}
.auth-card label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;}
.auth-card input{width:100%;border:1px solid var(--line);border-radius:9px;padding:12px;font-size:14.5px;margin-bottom:16px;font-family:inherit;outline:none;}
.auth-card input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(22,104,227,.14);}
.auth-card .go{width:100%;background:var(--blue);color:#fff;border:none;border-radius:9px;padding:13px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;}
.auth-card .go:hover{background:var(--blue-dark);}
.auth-err{background:#fff0f0;border:1px solid #f3b4b4;color:#a12020;border-radius:8px;padding:10px 12px;font-size:13px;margin-bottom:16px;}
.auth-alt{text-align:center;font-size:13.5px;color:var(--muted);margin-top:18px;}
.auth-alt a{color:var(--blue);font-weight:600;}
.auth-or{display:flex;align-items:center;gap:12px;color:#9aa7b8;font-size:12px;margin:20px 0;}
.auth-or::before,.auth-or::after{content:'';flex:1;height:1px;background:var(--line);}
.sso{width:100%;border:1px solid var(--line);background:#fff;border-radius:9px;padding:11px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:9px;font-family:inherit;margin-bottom:10px;}
.sso:hover{background:#f6f9fc;}
</style></head><body>
<?php }

function render_header() {
    $user = $_SESSION['expedia_user'] ?? null;
    ?>
<header class="top"><div class="top-in">
  <a href="/" class="logo"><span class="mark">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="#191e3b"><path d="M2 16.5l20-9-9 20-2.2-8.8L2 16.5z"/></svg>
  </span>Expedia</a>
  <div class="top-right">
    <a href="/" class="pill">🌐 English</a>
    <a href="/" class="pill">List your property</a>
    <?php if ($user): ?>
      <div class="acct">
        <span class="pill"><span class="avatar"><?php echo esc(strtoupper(substr($user,0,1))); ?></span> <?php echo esc(explode('@',$user)[0]); ?> ▾</span>
        <div class="acct-menu">
          <a href="/">My trips</a>
          <a href="/">Account</a>
          <a href="/?logout=1">Sign out</a>
        </div>
      </div>
    <?php else: ?>
      <a href="/login?rurl=/" class="pill">Sign in</a>
    <?php endif; ?>
  </div>
</div>
<div class="tabs"><div class="tabs-in">
  <a href="/" class="tab active">🛏 Stays</a>
  <a href="/" class="tab">✈ Flights</a>
  <a href="/" class="tab">🚗 Cars</a>
  <a href="/" class="tab">📦 Packages</a>
  <a href="/" class="tab">🎟 Things to do</a>
  <a href="/" class="tab">🚢 Cruises</a>
</div></div>
</header>
<?php }

function render_home() {
    page_head('Expedia Travel: Vacation Homes, Hotels, Car Rentals, Flights & More');
    render_header();
    $dests = [
        ['Santorini, Greece','Hotels from $120/night','photo-1570077188670-e3a8d69ac5ff'],
        ['Bali, Indonesia','Villas from $65/night','photo-1537996194471-e657df975ab4'],
        ['Paris, France','Hotels from $140/night','photo-1502602898657-3e91760cbb34'],
        ['Tokyo, Japan','Hotels from $95/night','photo-1540959733332-eab4deabeeaf'],
        ['New York, USA','Hotels from $180/night','photo-1496442226666-8d4d0e62e6e9'],
        ['Maldives','Resorts from $320/night','photo-1514282401047-d79a71a590e8'],
    ];
    ?>
    <section class="hero">
      <div class="hero-bg"><img src="<?php echo img('photo-1507525428034-b723cf961d3e'); ?>" alt="" onerror="this.remove()"></div>
      <div class="hero-in">
        <h1>Go further for less</h1>
        <p>Compare hotels, flights and more — and save with Member Prices on thousands of stays.</p>
      </div>
    </section>

    <form class="search" action="/" method="get" onsubmit="return false;">
      <div class="field"><span class="ic">📍</span><div><div class="fl">Going to</div><input placeholder="Where to?" value="Santorini, Greece"></div></div>
      <div class="field"><span class="ic">📅</span><div><div class="fl">Dates</div><div class="fv">Jun 12 — Jun 18</div></div></div>
      <div class="field"><span class="ic">👤</span><div><div class="fl">Travelers</div><div class="fv">2 travelers, 1 room</div></div></div>
      <button class="go" type="submit">Search</button>
    </form>

    <section class="sec">
      <h2>Explore stays in trending destinations</h2>
      <div class="sub">Discover the most popular places to stay, picked for you.</div>
      <div class="dgrid">
        <?php foreach ($dests as $d): ?>
        <a class="dcard" href="/">
          <img src="<?php echo img($d[2]); ?>" alt="" loading="lazy" onerror="this.style.background='#cbd5e1'">
          <div class="cap"><div class="c"><?php echo esc($d[0]); ?></div><div class="p"><?php echo esc($d[1]); ?></div></div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="sec" style="padding-top:0;">
      <div class="strip">
        <span class="em">🏷️</span>
        <div><h3>Members save 10% or more</h3><p>Sign in to unlock Member Prices on over 100,000 hotels worldwide.</p></div>
        <a href="/login?rurl=/" class="btn">Sign in &amp; save</a>
      </div>
    </section>

    <footer class="foot"><div class="foot-in">
      <div><h4>Company</h4><ul><li>About</li><li>Jobs</li><li>List your property</li><li>Partnerships</li></ul></div>
      <div><h4>Explore</h4><ul><li>Hotels</li><li>Vacation rentals</li><li>Flights</li><li>Car rentals</li></ul></div>
      <div><h4>Policies</h4><ul><li>Privacy</li><li>Terms of use</li><li>Vrbo terms</li><li>Accessibility</li></ul></div>
      <div><h4>Help</h4><ul><li>Support</li><li>Cancel your booking</li><li>Refund basics</li><li>Use a coupon</li></ul></div>
    </div><div class="foot-bot">© 2024 Expedia, Inc. All rights reserved. Expedia and the Airplane Logo are trademarks of Expedia, Inc.</div></footer>
    </body></html>
    <?php
}

function render_login($rurl, $err) {
    page_head('Sign in — Expedia');
    render_header();
    ?>
    <div class="auth-wrap"><div class="auth-card">
      <h1>Sign in to your account</h1>
      <div class="s">Members get our best prices when they're signed in.</div>
      <form method="post" action="/login">
        <input type="hidden" name="rurl" value="<?php echo esc($rurl); ?>">
        <?php if ($err): ?><div class="auth-err"><?php echo esc($err); ?></div><?php endif; ?>
        <label>Email address</label>
        <input type="email" name="email" placeholder="you@email.com" required autocomplete="username">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
        <button class="go" type="submit">Sign in</button>
      </form>
      <div class="auth-or">or</div>
      <button class="sso" type="button"><svg width="16" height="16" viewBox="0 0 24 24" fill="#1668e3"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 4a3 3 0 110 6 3 3 0 010-6zm0 14a7.97 7.97 0 01-5.5-2.2c0-1.8 3.67-2.8 5.5-2.8s5.5 1 5.5 2.8A7.97 7.97 0 0112 20z"/></svg> Continue with Google</button>
      <div class="auth-alt">Don't have an account? <a href="/login?rurl=<?php echo urlencode($rurl); ?>">Create one</a></div>
    </div></div>
    </body></html>
    <?php
}
