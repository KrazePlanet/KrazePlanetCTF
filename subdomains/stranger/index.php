<?php
// HackerOne — hacker-powered security platform

$raw      = $_SERVER['REQUEST_URI'];
$pathOnly = strtok($raw, '?');

// ── Legacy path redirector ───────────────────────────────────────────────────
// A request whose path begins with an extra slash is treated as a location to
// bounce the visitor to. A trailing ".<ext>" is dropped (it's read as a response
// format, e.g. .json/.xml), and the remainder is URL-decoded before redirecting.
if (preg_match('#^//(.+)$#', $pathOnly, $m)) {
    $rest = preg_replace('/\.[A-Za-z0-9]+$/', '', $m[1]);   // strip trailing format extension
    $dest = rawurldecode(rawurldecode($rest));              // decode the remainder
    if ($dest !== '') {
        header('Location: //' . $dest);
        exit;
    }
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$feed = [
    ['h'=>'m1rage','hc'=>'#6366f1','prog'=>'Shopify','pc'=>'#95bf47','sev'=>'Critical','title'=>'Stored XSS in product review images leads to session theft','bounty'=>'$5,000','time'=>'2 hours ago'],
    ['h'=>'0xcipher','hc'=>'#ec4899','prog'=>'Uber','pc'=>'#000000','sev'=>'High','title'=>'IDOR exposes other drivers\' trip history via trip_id','bounty'=>'$2,500','time'=>'5 hours ago'],
    ['h'=>'n0va','hc'=>'#14b8a6','prog'=>'GitLab','pc'=>'#fc6d26','sev'=>'High','title'=>'Blind SSRF in webhook URL validation bypass','bounty'=>'$3,000','time'=>'7 hours ago'],
    ['h'=>'byt3knight','hc'=>'#f59e0b','prog'=>'PayPal','pc'=>'#003087','sev'=>'Critical','title'=>'Account takeover through password-reset token leak in referer','bounty'=>'$10,000','time'=>'11 hours ago'],
    ['h'=>'qualw1n','hc'=>'#22c55e','prog'=>'Expedia','pc'=>'#191e3b','sev'=>'Low','title'=>'Open redirect in logout & login via rurl parameter','bounty'=>'$150','time'=>'1 day ago'],
    ['h'=>'r00tella','hc'=>'#06b6d4','prog'=>'Slack','pc'=>'#4a154b','sev'=>'Medium','title'=>'Open redirect on OAuth callback allows token phishing','bounty'=>'$750','time'=>'1 day ago'],
];
$sevColor = ['Critical'=>'#e11d48','High'=>'#f97316','Medium'=>'#eab308','Low'=>'#3b82f6'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HackerOne | #1 Trusted Security Platform and Hacker Program</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--bg:#0b0e14;--panel:#141922;--panel2:#1b2230;--line:#242c3a;--ink:#e8edf4;--muted:#8a93a6;--accent:#4f9cf9;--green:#36c6a0;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg);color:var(--ink);line-height:1.55;}
a{text-decoration:none;color:inherit;}
.wrap{max-width:1080px;margin:0 auto;padding:0 20px;}
/* nav */
.nav{border-bottom:1px solid var(--line);position:sticky;top:0;background:rgba(11,14,20,.92);backdrop-filter:blur(8px);z-index:20;}
.nav-in{max-width:1080px;margin:0 auto;padding:14px 20px;display:flex;align-items:center;gap:28px;}
.logo{display:flex;align-items:center;gap:10px;font-weight:800;font-size:18px;}
.logo .hex{width:28px;height:28px;background:#fff;color:#0b0e14;border-radius:7px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:14px;}
.menu{display:flex;gap:22px;font-size:14px;font-weight:500;color:var(--muted);}
.menu a:hover{color:var(--ink);}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:14px;font-size:14px;}
.nav-right .login{color:var(--muted);font-weight:600;}
.nav-right .login:hover{color:var(--ink);}
.btn{background:var(--accent);color:#07111f;border:none;border-radius:8px;padding:9px 18px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;}
.btn:hover{filter:brightness(1.08);}
@media(max-width:760px){.menu{display:none;}}
/* hero */
.hero{border-bottom:1px solid var(--line);background:radial-gradient(120% 120% at 80% 0%,rgba(79,156,249,.14),transparent 55%);}
.hero-in{max-width:1080px;margin:0 auto;padding:60px 20px 46px;}
.kicker{color:var(--green);font-size:13px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;margin-bottom:14px;}
.hero h1{font-size:42px;font-weight:900;letter-spacing:-1px;line-height:1.08;max-width:720px;margin-bottom:16px;}
.hero p{font-size:17px;color:var(--muted);max-width:560px;margin-bottom:26px;}
.hero-cta{display:flex;gap:12px;}
.btn-ghost{border:1px solid var(--line);background:transparent;color:var(--ink);border-radius:8px;padding:11px 20px;font-size:14px;font-weight:600;}
.btn-ghost:hover{border-color:var(--muted);}
.stats{display:flex;gap:48px;margin-top:46px;flex-wrap:wrap;}
.stat .n{font-size:28px;font-weight:800;}
.stat .l{font-size:13px;color:var(--muted);}
/* layout */
.main{max-width:1080px;margin:0 auto;padding:36px 20px 60px;display:grid;grid-template-columns:1fr 300px;gap:28px;align-items:start;}
@media(max-width:860px){.main{grid-template-columns:1fr;}}
.sec-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
.sec-head h2{font-size:20px;font-weight:800;}
.sec-head a{font-size:13px;color:var(--accent);font-weight:600;}
.feed{display:flex;flex-direction:column;gap:12px;}
.item{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:16px 18px;}
.item:hover{border-color:#3a4557;}
.item-top{display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--muted);margin-bottom:9px;flex-wrap:wrap;}
.av{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;}
.hacker{color:var(--ink);font-weight:600;}
.plogo{width:20px;height:20px;border-radius:5px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:#fff;}
.prog{color:var(--ink);font-weight:600;}
.item h3{font-size:15.5px;font-weight:600;margin-bottom:12px;line-height:1.4;}
.item h3:hover{color:var(--accent);}
.item-bot{display:flex;align-items:center;gap:10px;}
.sev{font-size:11.5px;font-weight:700;padding:3px 9px;border-radius:20px;color:#fff;}
.bounty{font-size:13px;font-weight:700;color:var(--green);background:rgba(54,198,160,.12);border:1px solid rgba(54,198,160,.3);border-radius:20px;padding:3px 10px;}
.disclosed{font-size:12px;color:var(--muted);margin-left:auto;}
/* sidebar */
.card{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:18px;margin-bottom:16px;}
.card h4{font-size:13px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:14px;}
.lb{display:flex;align-items:center;gap:11px;padding:7px 0;border-bottom:1px solid var(--line);}
.lb:last-child{border-bottom:none;}
.lb .rank{font-size:13px;font-weight:700;color:var(--muted);width:18px;}
.lb .nm{font-size:13.5px;font-weight:600;flex:1;}
.lb .rep{font-size:12.5px;color:var(--green);font-weight:700;}
.prog-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--line);}
.prog-row:last-child{border-bottom:none;}
.prog-row .rng{margin-left:auto;font-size:12px;color:var(--muted);}
/* footer */
.foot{border-top:1px solid var(--line);}
.foot-in{max-width:1080px;margin:0 auto;padding:36px 20px;display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:24px;color:var(--muted);font-size:13.5px;}
@media(max-width:760px){.foot-in{grid-template-columns:1fr 1fr;}}
.foot h5{color:var(--ink);font-size:13px;margin-bottom:12px;}
.foot ul{list-style:none;line-height:2;}
.foot-bot{border-top:1px solid var(--line);text-align:center;padding:16px;font-size:12.5px;color:var(--muted);}
</style>
</head>
<body>
<nav class="nav"><div class="nav-in">
  <a href="/" class="logo"><span class="hex">h1</span> HackerOne</a>
  <div class="menu"><a href="/">Hacktivity</a><a href="/">Directory</a><a href="/">Opportunities</a><a href="/">Leaderboard</a></div>
  <div class="nav-right"><a href="/" class="login">Log in</a><a href="/" class="btn">Sign up</a></div>
</div></nav>

<section class="hero"><div class="hero-in">
  <div class="kicker">◆ Hacktivity</div>
  <h1>See what the world's best hackers are finding.</h1>
  <p>A live feed of the latest publicly disclosed vulnerabilities across the HackerOne community.</p>
  <div class="hero-cta"><a href="/" class="btn">Start hacking</a><a href="/" class="btn-ghost">Explore programs</a></div>
  <div class="stats">
    <div class="stat"><div class="n">$300M+</div><div class="l">Paid to hackers</div></div>
    <div class="stat"><div class="n">2.1M</div><div class="l">Vulnerabilities resolved</div></div>
    <div class="stat"><div class="n">1,900+</div><div class="l">Customer programs</div></div>
  </div>
</div></section>

<div class="main">
  <div>
    <div class="sec-head"><h2>Latest hacktivity</h2><a href="/">View all →</a></div>
    <div class="feed">
      <?php foreach ($feed as $f): ?>
      <div class="item">
        <div class="item-top">
          <span class="av" style="background:<?php echo $f['hc']; ?>;"><?php echo esc(strtoupper(substr($f['h'],0,1))); ?></span>
          <span class="hacker"><?php echo esc($f['h']); ?></span>
          <span>found a bug in</span>
          <span class="plogo" style="background:<?php echo $f['pc']; ?>;"><?php echo esc(strtoupper(substr($f['prog'],0,1))); ?></span>
          <span class="prog"><?php echo esc($f['prog']); ?></span>
        </div>
        <h3><a href="/"><?php echo esc($f['title']); ?></a></h3>
        <div class="item-bot">
          <span class="sev" style="background:<?php echo $sevColor[$f['sev']]; ?>;"><?php echo esc($f['sev']); ?></span>
          <span class="bounty"><?php echo esc($f['bounty']); ?></span>
          <span class="disclosed">Disclosed <?php echo esc($f['time']); ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <aside>
    <div class="card">
      <h4>Top hackers this month</h4>
      <?php $lb=[['byt3knight','98,420','#f59e0b'],['m1rage','81,003','#6366f1'],['n0va','74,655','#14b8a6'],['0xcipher','69,210','#ec4899'],['r00tella','61,884','#06b6d4']];
      foreach ($lb as $i=>$r): ?>
      <div class="lb"><span class="rank"><?php echo $i+1; ?></span><span class="av" style="width:24px;height:24px;background:<?php echo $r[2]; ?>;"><?php echo esc(strtoupper($r[0][0])); ?></span><span class="nm"><?php echo esc($r[0]); ?></span><span class="rep"><?php echo esc($r[1]); ?></span></div>
      <?php endforeach; ?>
    </div>
    <div class="card">
      <h4>Programs hiring hackers</h4>
      <?php $pr=[['Shopify','$500–$50k','#95bf47'],['GitLab','$1k–$35k','#fc6d26'],['PayPal','$50–$30k','#003087'],['Slack','$100–$10k','#4a154b']];
      foreach ($pr as $p): ?>
      <div class="prog-row"><span class="plogo" style="background:<?php echo $p[2]; ?>;"><?php echo esc($p[0][0]); ?></span><span class="prog" style="font-size:13.5px;"><?php echo esc($p[0]); ?></span><span class="rng"><?php echo esc($p[1]); ?></span></div>
      <?php endforeach; ?>
    </div>
  </aside>
</div>

<footer class="foot"><div class="foot-in">
  <div><div class="logo" style="margin-bottom:10px;"><span class="hex">h1</span> HackerOne</div><p style="max-width:230px;">The world's most trusted hacker-powered security platform.</p></div>
  <div><h5>Platform</h5><ul><li>Bug Bounty</li><li>Pentest</li><li>Response</li><li>Attack Surface</li></ul></div>
  <div><h5>Company</h5><ul><li>About</li><li>Careers</li><li>Press</li><li>Partners</li></ul></div>
  <div><h5>Hackers</h5><ul><li>Hack</li><li>Hacktivity</li><li>Leaderboard</li><li>Directory</li></ul></div>
</div><div class="foot-bot">© 2024 HackerOne Inc. All rights reserved.</div></footer>
</body>
</html>
