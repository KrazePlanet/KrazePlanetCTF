<?php
// Lichess4545 — community chess leagues (signs in with Lichess OAuth)
session_start();

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$hostOnly = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
$self     = $scheme . '://' . $hostOnly;
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path   = rtrim($path, '/');
if ($path === '') $path = '/';

// ── Lichess OAuth: authorise screen (the identity provider) ──────────────────
if ($path === '/oauth/authorize') {
    render_authorize($_GET);
    exit;
}

// ── Lichess OAuth: the user clicked "Authorize" → send them to redirect_uri ──
// The authorization code is handed back to redirect_uri as-is; the value is not
// checked against the application's registered callback URLs.
if ($path === '/oauth/complete') {
    $ru = $_GET['redirect_uri'] ?? '';
    if ($ru === '') { header('Location: /'); exit; }
    $state = $_GET['state'] ?? '';
    // Our own callback is redirected to relatively so the browser keeps the
    // current origin (and port); an external redirect_uri is used verbatim.
    $ruHost = parse_url($ru, PHP_URL_HOST);
    if ($ruHost !== null && strtolower($ruHost) === strtolower($hostOnly)) {
        $ru = parse_url($ru, PHP_URL_PATH) ?: '/';
    }
    $sep = (strpos($ru, '?') !== false) ? '&' : '?';
    header('Location: ' . $ru . $sep . 'code=' . bin2hex(random_bytes(10)) . '&state=' . rawurlencode($state));
    exit;
}

// ── App's OAuth callback: exchange the code and sign the user in ─────────────
if ($path === '/auth/lichess') {
    if (!empty($_GET['code'])) {
        $_SESSION['liuser'] = ['name' => 'knightrider_42', 'rating' => 1687];
        header('Location: /blitzbattle/');
        exit;
    }
    header('Location: /'); exit;
}

if ($path === '/logout') { unset($_SESSION['liuser']); header('Location: /'); exit; }

$me = $_SESSION['liuser'] ?? null;

// ── Sign in with Lichess / a league page → start the OAuth flow ──────────────
if ($path === '/login' || $path === '/blitzbattle') {
    if (!$me) {
        $redirect_uri = $self . '/auth/lichess/';
        $state = bin2hex(random_bytes(6));
        // Hand the user to the Lichess authorisation endpoint (readable params).
        header('Location: /oauth/authorize?client_id=lichess4545&redirect_uri=' . $redirect_uri . '&response_type=code&scope=preference:read&state=' . $state);
        exit;
    }
    if ($path === '/blitzbattle') { render_league($me); exit; }
    header('Location: /'); exit;
}

render_landing($me);

// =====================================================================
//  VIEWS
// =====================================================================
function knight_svg($size, $fill) {
    return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 50 50" fill="'.$fill.'"><path d="M22 10c10.5 1 16.5 8 16 29H15c0-9 10-6.5 8-19-3 6-11 11.5-11 5.5 0-2 2-4 2-4l-2-2 2-3 3 1 2-3 3 .5V10z"/></svg>';
}

function app_head($title) { ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($title); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#161512;--panel:#262421;--panel2:#302e2b;--line:#3b3936;--ink:#e9e6e2;--muted:#9a948c;--accent:#629924;--accent2:#7cb342;--blue:#3692e7;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg);color:var(--ink);line-height:1.55;}
a{text-decoration:none;color:inherit;}
.nav{background:#2b2926;border-bottom:1px solid var(--line);}
.nav-in{max-width:1080px;margin:0 auto;padding:12px 20px;display:flex;align-items:center;gap:26px;}
.logo{display:flex;align-items:center;gap:9px;font-size:18px;font-weight:800;}
.menu{display:flex;gap:20px;font-size:14px;font-weight:500;color:var(--muted);}
.menu a:hover{color:var(--ink);}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:14px;font-size:14px;}
.btn{background:var(--accent);color:#fff;border:none;border-radius:6px;padding:9px 16px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:8px;}
.btn:hover{background:#6aa524;}
.av{width:30px;height:30px;border-radius:6px;background:var(--panel2);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:var(--accent2);}
@media(max-width:760px){.menu{display:none;}}
.hero{max-width:1080px;margin:0 auto;padding:54px 20px 30px;display:grid;grid-template-columns:1.2fr .8fr;gap:40px;align-items:center;}
@media(max-width:820px){.hero{grid-template-columns:1fr;}}
.hero h1{font-size:40px;font-weight:800;letter-spacing:-1px;line-height:1.08;margin-bottom:14px;}
.hero h1 span{color:var(--accent2);}
.hero p{font-size:17px;color:var(--muted);max-width:440px;margin-bottom:24px;}
.board{width:100%;max-width:300px;aspect-ratio:1;border-radius:10px;overflow:hidden;display:grid;grid-template-columns:repeat(8,1fr);box-shadow:0 14px 40px rgba(0,0,0,.4);margin:0 auto;}
.board div{display:flex;align-items:center;justify-content:center;font-size:20px;}
.sq-l{background:#f0d9b5;}.sq-d{background:#b58863;}
.sec{max-width:1080px;margin:0 auto;padding:36px 20px;}
.sec h2{font-size:22px;font-weight:800;margin-bottom:4px;}
.sec .sub{color:var(--muted);font-size:14.5px;margin-bottom:22px;}
.lgrid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;}
@media(max-width:820px){.lgrid{grid-template-columns:1fr 1fr;}}
.lcard{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:18px;}
.lcard .ic{font-size:26px;margin-bottom:10px;}
.lcard h3{font-size:16px;font-weight:700;margin-bottom:4px;}
.lcard p{font-size:13px;color:var(--muted);}
.table{background:var(--panel);border:1px solid var(--line);border-radius:12px;overflow:hidden;}
.table .th,.table .tr{display:grid;grid-template-columns:40px 1fr 90px 70px;gap:10px;padding:11px 18px;font-size:14px;align-items:center;}
.table .th{background:var(--panel2);color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.4px;font-weight:700;}
.table .tr{border-top:1px solid var(--line);}
.table .tr .rk{color:var(--accent2);font-weight:800;}
.foot{border-top:1px solid var(--line);margin-top:30px;}
.foot-in{max-width:1080px;margin:0 auto;padding:26px 20px;color:var(--muted);font-size:13px;display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;}
/* lichess oauth */
.liwrap{min-height:100vh;background:#161512;}
.litop{background:#2b2926;border-bottom:1px solid #3b3936;padding:14px 0;}
.litop-in{max-width:760px;margin:0 auto;padding:0 20px;display:flex;align-items:center;gap:10px;font-size:18px;font-weight:800;}
.licard{max-width:460px;margin:46px auto;background:#262421;border:1px solid #3b3936;border-radius:10px;overflow:hidden;}
.lihead{padding:22px 26px;border-bottom:1px solid #3b3936;text-align:center;}
.lihead h1{font-size:20px;font-weight:700;margin-bottom:6px;}
.lihead p{font-size:14px;color:#9a948c;}
.lihead b{color:#e9e6e2;}
.libody{padding:22px 26px;}
.liscope{display:flex;gap:12px;align-items:flex-start;padding:10px 0;border-bottom:1px solid #302e2b;font-size:14px;}
.liscope .c{color:#7cb342;font-weight:800;font-size:16px;}
.liscope .m{color:#9a948c;font-size:12.5px;}
.liredir{margin-top:16px;background:#302e2b;border:1px solid #3b3936;border-radius:8px;padding:12px 14px;}
.liredir .l{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#9a948c;font-weight:700;margin-bottom:5px;}
.liredir .u{font-family:ui-monospace,Menlo,monospace;font-size:13px;color:#e9e6e2;word-break:break-all;}
.liactions{padding:18px 26px;border-top:1px solid #3b3936;display:flex;gap:12px;}
.li-auth{flex:1;background:#629924;color:#fff;border:none;border-radius:6px;padding:13px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;text-align:center;display:block;}
.li-auth:hover{background:#6aa524;}
.li-cancel{background:#302e2b;border:1px solid #3b3936;border-radius:6px;padding:13px 20px;font-size:14px;font-weight:600;color:#e9e6e2;}
.linote{max-width:460px;margin:0 auto;text-align:center;font-size:12px;color:#9a948c;}
</style></head><body>
<?php }

function app_nav($me) { ?>
<nav class="nav"><div class="nav-in">
  <a href="/" class="logo"><?php echo knight_svg(26,'#7cb342'); ?> Lichess<span style="color:#7cb342;">4545</span></a>
  <div class="menu"><a href="/">Leagues</a><a href="/blitzbattle/">Schedule</a><a href="/">Standings</a><a href="/">Rules</a></div>
  <div class="nav-right">
    <?php if ($me): ?>
      <span class="av"><?php echo esc(strtoupper(substr($me['name'],0,2))); ?></span>
      <span><?php echo esc($me['name']); ?> (<?php echo (int)$me['rating']; ?>)</span>
      <a href="/logout/" style="color:#9a948c;">Sign out</a>
    <?php else: ?>
      <a href="/login/" class="btn"><?php echo knight_svg(16,'#fff'); ?> Sign in with Lichess</a>
    <?php endif; ?>
  </div>
</div></nav>
<?php }

function board_html() {
    $pieces = ['♜','♞','♝','♛','♚','♝','♞','♜'];
    $pawns = array_fill(0,8,'♟');
    $out = '<div class="board">';
    for ($r = 0; $r < 8; $r++) {
        for ($c = 0; $c < 8; $c++) {
            $cls = (($r + $c) % 2 === 0) ? 'sq-l' : 'sq-d';
            $p = '';
            if ($r === 0) $p = $pieces[$c];
            elseif ($r === 1) $p = $pawns[$c];
            elseif ($r === 6) $p = '♙';
            elseif ($r === 7) $p = ['♖','♘','♗','♕','♔','♗','♘','♖'][$c];
            $out .= '<div class="'.$cls.'">'.$p.'</div>';
        }
    }
    return $out . '</div>';
}

function render_landing($me) {
    app_head('Lichess4545 — Community Chess Leagues');
    app_nav($me);
    $leagues = [['♟','Team 4545','Classical 45+45 team league'],['♞','LoneWolf','Individual Swiss, 30+30'],['⚡','Blitz Battle','Fast-paced 3+2 blitz'],['♜','Chess960','Fischer random fun']];
    ?>
    <section class="hero">
      <div>
        <h1>Competitive chess leagues, <span>run by the community.</span></h1>
        <p>Join thousands of players in slow and fast team leagues. Sign in with your Lichess account to register for the next season.</p>
        <a href="/login/" class="btn" style="padding:12px 20px;font-size:15px;"><?php echo knight_svg(18,'#fff'); ?> Sign in with Lichess</a>
      </div>
      <?php echo board_html(); ?>
    </section>
    <section class="sec">
      <h2>Our leagues</h2>
      <div class="sub">Four seasons running right now — pick your time control.</div>
      <div class="lgrid">
        <?php foreach ($leagues as $l): ?>
        <a class="lcard" href="/blitzbattle/"><div class="ic"><?php echo $l[0]; ?></div><h3><?php echo esc($l[1]); ?></h3><p><?php echo esc($l[2]); ?></p></a>
        <?php endforeach; ?>
      </div>
    </section>
    <footer class="foot"><div class="foot-in"><span>&copy; 2025 Lichess4545 · a community project, not affiliated with Lichess.org</span><span>Powered by Lichess OAuth</span></div></footer>
    </body></html>
    <?php
}

function render_league($me) {
    app_head('Blitz Battle — Lichess4545');
    app_nav($me);
    $rows = [[1,'knightrider_42',2031,'8.5'],[2,'pawnstorm',1994,'8.0'],[3,'rookie_moves',1952,'7.5'],[4,'enpassant_andy',1920,'7.0'],[5,'zugzwang_zoe',1888,'6.5']];
    ?>
    <section class="sec" style="padding-top:40px;">
      <h2>⚡ Blitz Battle — Season 14 standings</h2>
      <div class="sub">Welcome, <?php echo esc($me['name']); ?>. You signed in with Lichess. Your next round pairs on Sunday.</div>
      <div class="table">
        <div class="th"><span>#</span><span>Player</span><span>Rating</span><span>Points</span></div>
        <?php foreach ($rows as $r): ?>
        <div class="tr"><span class="rk"><?php echo $r[0]; ?></span><span><?php echo esc($r[1]); ?></span><span><?php echo (int)$r[2]; ?></span><span><?php echo esc($r[3]); ?></span></div>
        <?php endforeach; ?>
      </div>
    </section>
    <footer class="foot"><div class="foot-in"><span>&copy; 2025 Lichess4545</span><span>Powered by Lichess OAuth</span></div></footer>
    </body></html>
    <?php
}

function render_authorize($q) {
    $client = $q['client_id'] ?? 'lichess4545';
    $ru     = $q['redirect_uri'] ?? '';
    $state  = $q['state'] ?? '';
    $scope  = $q['scope'] ?? 'preference:read';
    app_head('Authorize — Lichess');
    ?>
    <div class="liwrap">
      <div class="litop"><div class="litop-in"><?php echo knight_svg(26,'#bababa'); ?> lichess<span style="color:#9a948c;font-weight:600;font-size:14px;">.org</span></div></div>
      <div class="licard">
        <div class="lihead">
          <h1>Authorize application</h1>
          <p><b><?php echo esc($client); ?></b> is requesting access to your Lichess account.</p>
        </div>
        <div class="libody">
          <div class="liscope"><span class="c">&#10003;</span><div><strong>Read preferences</strong><div class="m">View your public profile and site preferences.</div></div></div>
          <div class="liscope"><span class="c">&#10003;</span><div><strong>Read email address</strong><div class="m">Read the email address on your account.</div></div></div>
          <div class="liredir"><div class="l">You will be redirected to</div><div class="u"><?php echo esc($ru); ?></div></div>
        </div>
        <div class="liactions">
          <a class="li-auth" href="/oauth/complete?redirect_uri=<?php echo esc($ru); ?>&amp;state=<?php echo esc($state); ?>">Authorize</a>
          <a class="li-cancel" href="/">Cancel</a>
        </div>
      </div>
      <div class="linote">Only authorize applications you trust with your Lichess account.</div>
    </div>
    </body></html>
    <?php
}
