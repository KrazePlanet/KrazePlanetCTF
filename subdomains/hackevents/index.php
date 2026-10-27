<?php
// HackerOne Events — live hacking events

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function img($id) { return "https://images.unsplash.com/{$id}?w=800&q=70&auto=format&fit=crop"; }
// Outbound links are routed through the events site so clicks can be counted.
function go($url) { return '/redirect?url=' . rawurlencode($url); }

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');
if ($path === '') $path = '/';

// ── Outbound link tracker ─────────────────────────────────────────────────────
// Forwards the visitor on to the ?url= destination.
if ($path === '/redirect') {
    $url = $_GET['url'] ?? '';
    if ($url !== '') { header('Location: ' . $url); exit; }
    header('Location: /'); exit;
}

$events = [
    ['h1-702','Las Vegas, USA','Aug 12–14, 2021','photo-1605833556294-ea5c7a74f57d'],
    ['h1-4420','London, UK','Sep 23–25, 2021','photo-1513635269975-59663e0ac1ad'],
    ['h1-65','Singapore','Oct 14–16, 2021','photo-1565967511849-76a60a516170'],
    ['h1-31','Amsterdam, NL','Nov 18–20, 2021','photo-1534351590666-13e3e96b5017'],
];
$speakers = [
    ['Jordan Vega','Security Researcher','photo-1507003211169-0a1dd7228f2d','https://jordanvega.dev'],
    ['Priya Anand','Bug Bounty Hunter','photo-1494790108377-be9c29b29330','https://priya.sh'],
    ['Aya Nakamura','Red Team Lead','photo-1534528741775-53994a69daeb','https://ayanakamura.io'],
    ['Nagli','Appsec Researcher','photo-1500648767791-00dcc994a43e','https://naglinagli.github.io'],
];
$sponsors = [['GitLab','https://about.gitlab.com'],['Shopify','https://shopify.com'],['PayPal','https://paypal.com'],['Uber','https://uber.com'],['Slack','https://slack.com'],['Adobe','https://adobe.com']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HackerOne Events — Live Hacking Events</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0d14;--panel:#121722;--line:#222a39;--ink:#e8edf6;--muted:#8793a8;--accent:#46e3b7;--accent2:#3fb6ff;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg);color:var(--ink);line-height:1.55;}
a{text-decoration:none;color:inherit;}
.wrap{max-width:1120px;margin:0 auto;padding:0 20px;}
.nav{border-bottom:1px solid var(--line);position:sticky;top:0;background:rgba(10,13,20,.9);backdrop-filter:blur(8px);z-index:20;}
.nav-in{max-width:1120px;margin:0 auto;padding:15px 20px;display:flex;align-items:center;gap:30px;}
.logo{display:flex;align-items:center;gap:10px;font-weight:800;font-size:18px;}
.logo .h{width:28px;height:28px;background:#fff;color:#0a0d14;border-radius:7px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:13px;}
.logo .sub{color:var(--muted);font-weight:600;font-size:13px;border-left:1px solid var(--line);padding-left:10px;}
.menu{display:flex;gap:22px;font-size:14px;font-weight:500;color:var(--muted);}
.menu a:hover{color:var(--ink);}
.nav-right{margin-left:auto;}
.btn{background:var(--accent);color:#06241c;border:none;border-radius:8px;padding:10px 18px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-block;}
.btn:hover{filter:brightness(1.08);}
.btn-ghost{border:1px solid var(--line);color:var(--ink);background:transparent;border-radius:8px;padding:10px 18px;font-size:14px;font-weight:600;display:inline-block;}
.btn-ghost:hover{border-color:var(--muted);}
@media(max-width:760px){.menu{display:none;}}
/* hero */
.hero{position:relative;overflow:hidden;border-bottom:1px solid var(--line);}
.hero-bg{position:absolute;inset:0;z-index:0;}
.hero-bg img{width:100%;height:100%;object-fit:cover;opacity:.32;}
.hero-bg::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(10,13,20,.5),var(--bg));}
.hero-in{position:relative;z-index:1;max-width:1120px;margin:0 auto;padding:72px 20px 56px;}
.kicker{display:inline-block;font-size:12.5px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--accent);border:1px solid #46e3b755;border-radius:20px;padding:5px 12px;margin-bottom:18px;}
.hero h1{font-size:48px;font-weight:900;letter-spacing:-1.4px;line-height:1.05;max-width:720px;margin-bottom:16px;}
.hero p{font-size:18px;color:var(--muted);max-width:520px;margin-bottom:26px;}
.hero-cta{display:flex;gap:12px;flex-wrap:wrap;}
/* sections */
.sec{max-width:1120px;margin:0 auto;padding:54px 20px;}
.sec-head{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:24px;}
.sec-head h2{font-size:28px;font-weight:800;letter-spacing:-.5px;}
.sec-head .lk{font-size:13.5px;color:var(--accent);font-weight:600;}
.ev-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;}
@media(max-width:760px){.ev-grid{grid-template-columns:1fr;}}
.ev{background:var(--panel);border:1px solid var(--line);border-radius:16px;overflow:hidden;display:flex;}
.ev-img{width:150px;flex-shrink:0;position:relative;}
.ev-img img{width:100%;height:100%;object-fit:cover;}
.ev-body{padding:18px 20px;flex:1;}
.ev-code{font-size:19px;font-weight:800;}
.ev-loc{color:var(--muted);font-size:13.5px;margin:3px 0 2px;}
.ev-date{color:var(--accent);font-size:13px;font-weight:600;margin-bottom:12px;}
.ev-body .row{display:flex;gap:10px;}
.ev-body .btn,.ev-body .btn-ghost{padding:7px 14px;font-size:13px;}
/* speakers */
.sp-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;}
@media(max-width:760px){.sp-grid{grid-template-columns:1fr 1fr;}}
.sp{background:var(--panel);border:1px solid var(--line);border-radius:16px;overflow:hidden;text-align:center;}
.sp-img{aspect-ratio:1/1;overflow:hidden;}
.sp-img img{width:100%;height:100%;object-fit:cover;}
.sp-body{padding:16px;}
.sp-body .nm{font-size:16px;font-weight:700;}
.sp-body .ro{font-size:13px;color:var(--muted);margin:2px 0 12px;}
.sp-body .prof{font-size:13px;font-weight:700;color:var(--accent);}
.sp-body .prof:hover{text-decoration:underline;}
/* sponsors */
.sponsors{background:var(--panel);border-top:1px solid var(--line);border-bottom:1px solid var(--line);}
.sponsors-in{max-width:1120px;margin:0 auto;padding:36px 20px;text-align:center;}
.sponsors .lbl{font-size:12.5px;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);font-weight:600;margin-bottom:22px;}
.sp-row{display:flex;flex-wrap:wrap;justify-content:center;gap:16px 40px;}
.sp-row a{font-size:20px;font-weight:800;color:#6b7788;letter-spacing:-.5px;}
.sp-row a:hover{color:var(--ink);}
/* footer */
.foot{border-top:1px solid var(--line);}
.foot-in{max-width:1120px;margin:0 auto;padding:40px 20px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;color:var(--muted);font-size:13px;}
.social{display:flex;gap:16px;}
.social a{color:var(--muted);font-weight:600;}
.social a:hover{color:var(--ink);}
</style>
</head>
<body>
<nav class="nav"><div class="nav-in">
  <a href="/" class="logo"><span class="h">h1</span> HackerOne <span class="sub">Events</span></a>
  <div class="menu"><a href="/">Live Events</a><a href="/">Speakers</a><a href="/">Sponsors</a><a href="/">Apply to hack</a></div>
  <div class="nav-right"><a href="<?php echo esc(go('https://register.hackerone-events.com')); ?>" class="btn">Register</a></div>
</div></nav>

<section class="hero">
  <div class="hero-bg"><img src="<?php echo img('photo-1540575467063-178a50c2df87'); ?>" alt="" onerror="this.remove()"></div>
  <div class="hero-in">
    <span class="kicker">◆ 2021 Season</span>
    <h1>Live Hacking Events, where the world&rsquo;s best hackers gather.</h1>
    <p>Four days, one city, the top hackers on the planet — testing the security of the world&rsquo;s most innovative companies, live.</p>
    <div class="hero-cta">
      <a href="<?php echo esc(go('https://register.hackerone-events.com')); ?>" class="btn">Register now</a>
      <a href="/" class="btn-ghost">View the schedule</a>
    </div>
  </div>
</section>

<section class="sec">
  <div class="sec-head"><h2>Upcoming events</h2><a href="/" class="lk">All events &rarr;</a></div>
  <div class="ev-grid">
    <?php foreach ($events as $e): ?>
    <div class="ev">
      <div class="ev-img"><img src="<?php echo img($e[3]); ?>" alt="" loading="lazy" onerror="this.style.background='#1b2130'"></div>
      <div class="ev-body">
        <div class="ev-code"><?php echo esc($e[0]); ?></div>
        <div class="ev-loc"><?php echo esc($e[1]); ?></div>
        <div class="ev-date"><?php echo esc($e[2]); ?></div>
        <div class="row">
          <a href="<?php echo esc(go('https://register.hackerone-events.com/' . $e[0])); ?>" class="btn">Register</a>
          <a href="/" class="btn-ghost">Learn more</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="sec" style="padding-top:0;">
  <div class="sec-head"><h2>Featured speakers</h2><a href="/" class="lk">All speakers &rarr;</a></div>
  <div class="sp-grid">
    <?php foreach ($speakers as $s): ?>
    <div class="sp">
      <div class="sp-img"><img src="<?php echo img($s[2]); ?>" alt="" loading="lazy" onerror="this.style.background='#1b2130'"></div>
      <div class="sp-body">
        <div class="nm"><?php echo esc($s[0]); ?></div>
        <div class="ro"><?php echo esc($s[1]); ?></div>
        <a class="prof" href="<?php echo esc(go($s[3])); ?>">Visit profile &rarr;</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<div class="sponsors"><div class="sponsors-in">
  <div class="lbl">Proudly supported by</div>
  <div class="sp-row">
    <?php foreach ($sponsors as $sp): ?><a href="<?php echo esc(go($sp[1])); ?>"><?php echo esc($sp[0]); ?></a><?php endforeach; ?>
  </div>
</div></div>

<footer class="foot"><div class="foot-in">
  <span>&copy; 2021 HackerOne Events. Live hacking events powered by HackerOne.</span>
  <div class="social">
    <a href="<?php echo esc(go('https://twitter.com/hacker0x01')); ?>">Twitter</a>
    <a href="<?php echo esc(go('https://youtube.com/hackerone')); ?>">YouTube</a>
    <a href="<?php echo esc(go('https://linkedin.com/company/hackerone')); ?>">LinkedIn</a>
  </div>
</div></footer>
</body>
</html>
