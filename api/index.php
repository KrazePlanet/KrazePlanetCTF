<?php
// ============================================================
// SQL Injection Lab 114 - Boolean-blind SQLi via REST API Path Segment
// Platform: id.indrive.com | HackerOne Report #2051931
// Endpoint: GET /api/ten-drives/custom-winners/{campaign}/number_trips/{min}/{max}/phone
// Vulnerability: {max} path segment used raw in SQL WHERE clause (integer, no quotes)
// Payloads:
//   max=0 or 1=1--   → TRUE  → random winner phone returned
//   max=0 or 1=2--   → FALSE → empty response {}
//   max=0 or (SELECT SUBSTRING(secret_data,1,1) FROM lab114_secret LIMIT 1)='f'--
//             → TRUE if char matches → blind extraction char-by-char
// ============================================================

// --- Database Configuration ---
$server   = "localhost";
$username = "root";
$password = "";
$database = "VexiumCTF_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) die(json_encode(["error" => "DB connection failed"]));

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

// --- Initialize Database ---
function initializeLab114Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab114_winners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) NOT NULL,
        number_trips INT NOT NULL,
        campaign VARCHAR(60) NOT NULL,
        city VARCHAR(50) NOT NULL
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab114_secret (
        id INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");

    $check = mysqli_query($conn, "SELECT * FROM lab114_secret LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO lab114_secret (secret_data) VALUES ('flag{indrive_blind_sqli_2051931}')");
    }

    $checkW = mysqli_query($conn, "SELECT * FROM lab114_winners LIMIT 1");
    if (mysqli_num_rows($checkW) == 0) {
        mysqli_query($conn, "INSERT INTO lab114_winners (phone, number_trips, campaign, city) VALUES
            ('+7 701-234-5678', 3,  'ten_drive_kz_second_weeks', 'Almaty'),
            ('+7 702-876-4321', 5,  'ten_drive_kz_second_weeks', 'Nur-Sultan'),
            ('+7 705-112-9900', 8,  'ten_drive_kz_second_weeks', 'Shymkent'),
            ('+7 707-445-3312', 12, 'ten_drive_kz_second_weeks', 'Karaganda'),
            ('+7 708-990-1123', 22, 'ten_drive_kz_second_weeks', 'Aktobe'),
            ('+7 771-667-8834', 29, 'ten_drive_kz_second_weeks', 'Almaty')");
    }
}
initializeLab114Database($conn);

// ============================================================
// API MODE — path-style URL routing via QUERY_STRING
// URL format: index.php?api/ten-drives/custom-winners/{campaign}/number_trips/{min}/{max}/phone
//
// PHP parses $_SERVER['QUERY_STRING'] = "api/ten-drives/.../1/5/phone"
// Segments: [0]=api [1]=ten-drives [2]=custom-winners [3]={campaign}
//           [4]=number_trips [5]={min} [6]={max} [7]=phone
//
// ⚠ VULNERABLE: $segments[6] ({max}) is used raw in SQL WHERE clause
//
// Payloads (intercept in Burp, modify path segment 6):
//   .../number_trips/1/5/phone              → normal (winners with 1–5 trips)
//   .../number_trips/1/0 or 1=1#/phone     → always TRUE  → phone returned
//   .../number_trips/1/0 or 1=2#/phone     → always FALSE → {} empty
//   .../number_trips/1/0 or (SELECT SUBSTRING(secret_data,1,1) FROM lab114_secret LIMIT 1)='f'#/phone
//                                           → phone if char matches (blind extraction)
// ============================================================
$qs = $_SERVER['QUERY_STRING'] ?? '';
if (strpos($qs, 'api/') === 0) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    $segments = explode('/', $qs);
    // Expected: api / ten-drives / custom-winners / {campaign} / number_trips / {min} / {max} / phone
    $min      = isset($segments[5]) ? urldecode($segments[5]) : '1';
    $max      = isset($segments[6]) ? urldecode($segments[6]) : '5';  // ⚠ VULNERABLE — raw in SQL
    $campaign = 'ten_drive_kz_second_weeks';

    // ====================================================================
    // ⚠ VULNERABLE QUERY — {max} path segment interpolated raw into SQL
    //   WHERE number_trips >= $min AND number_trips <= $max
    //
    // Injection point: the 7th path segment (index 6) in the URL:
    //   GET /index.php?api/ten-drives/custom-winners/ten_drive_kz_second_weeks/number_trips/1/[HERE]/phone
    //
    // Payload: 0 or 1=1#  → WHERE ... <= 0 or 1=1 → always TRUE → row returned
    // Payload: 0 or 1=2#  → WHERE ... <= 0 or 1=2 → always FALSE → empty {}
    // ====================================================================
    $sql = "SELECT phone, number_trips, city FROM lab114_winners
            WHERE campaign = '$campaign' AND number_trips >= $min AND number_trips <= $max
            ORDER BY RAND() LIMIT 1";

    $result = mysqli_query($conn, $sql);

    if ($result === false) {
        echo json_encode(["error" => mysqli_error($conn)]);
        exit;
    }

    $row = mysqli_fetch_assoc($result);
    if ($row) {
        echo json_encode([
            "phone"        => $row['phone'],
            "number_trips" => (int)$row['number_trips'],
            "city"         => $row['city'],
            "campaign"     => $campaign
        ]);
    } else {
        echo json_encode((object)[]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>inDrive — 10 Rides To Get a Prize</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0D0D0D;color:#fff;min-height:100vh;display:flex;flex-direction:column;}

/* ── Header ──────────────────────────────────────────────────────────────────── */
.header{background:#0D0D0D;border-bottom:1px solid #1E1E1E;padding:0 20px;}
.header-inner{max-width:1100px;margin:0 auto;height:56px;display:flex;align-items:center;justify-content:space-between;}
.logo{display:flex;align-items:center;gap:2px;text-decoration:none;}
.logo-in{font-size:1.3rem;font-weight:900;color:#C8F73B;letter-spacing:-.04em;}
.logo-drive{font-size:1.3rem;font-weight:900;color:#fff;letter-spacing:-.04em;}
.header-nav{display:flex;gap:0;}
.header-nav a{color:#8A8A8A;font-size:.78rem;text-decoration:none;padding:0 14px;line-height:56px;display:block;transition:color .15s;}
.header-nav a:hover{color:#fff;}
.header-cta{background:#C8F73B;color:#0D0D0D;font-size:.78rem;font-weight:800;padding:8px 18px;border-radius:6px;text-decoration:none;transition:background .15s;}
.header-cta:hover{background:#b8e030;}

/* ── Hero ────────────────────────────────────────────────────────────────────── */
.hero{background:linear-gradient(160deg,#111 0%,#0D0D0D 60%,#131a08 100%);padding:60px 20px 40px;text-align:center;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;top:-80px;right:-80px;width:320px;height:320px;background:radial-gradient(circle,rgba(200,247,59,.08) 0%,transparent 70%);}
.hero::after{content:'';position:absolute;bottom:-60px;left:-60px;width:240px;height:240px;background:radial-gradient(circle,rgba(200,247,59,.05) 0%,transparent 70%);}
.hero-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(200,247,59,.12);border:1px solid rgba(200,247,59,.3);color:#C8F73B;font-size:.7rem;font-weight:700;padding:4px 12px;border-radius:20px;margin-bottom:18px;text-transform:uppercase;letter-spacing:.06em;}
.hero-title{font-size:2.4rem;font-weight:900;color:#fff;line-height:1.1;margin-bottom:8px;letter-spacing:-.03em;}
.hero-title .accent{color:#C8F73B;}
.hero-subtitle{font-size:1rem;color:#8A8A8A;margin-bottom:8px;}
.hero-sub2{font-size:.82rem;color:#5A5A5A;margin-bottom:32px;}
.prize-tags{display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:32px;flex-wrap:wrap;}
.prize-tag{background:#1A1A1A;border:1px solid #2A2A2A;border-radius:8px;padding:10px 18px;text-align:center;}
.prize-tag-num{font-size:1.3rem;font-weight:900;color:#C8F73B;}
.prize-tag-lbl{font-size:.62rem;color:#8A8A8A;text-transform:uppercase;letter-spacing:.06em;}

/* ── Winner Card ─────────────────────────────────────────────────────────────── */
.winner-section{max-width:480px;margin:0 auto;padding:0 20px 40px;position:relative;z-index:1;}
.winner-card{background:#1A1A1A;border:1px solid #2A2A2A;border-radius:12px;overflow:hidden;}
.winner-card-header{padding:14px 18px;border-bottom:1px solid #2A2A2A;display:flex;align-items:center;justify-content:space-between;}
.winner-card-title{font-size:.82rem;font-weight:700;color:#fff;}
.winner-card-sub{font-size:.7rem;color:#5A5A5A;}
.winner-result{padding:20px 18px;min-height:80px;display:flex;align-items:center;justify-content:center;}
.winner-idle{text-align:center;color:#5A5A5A;font-size:.82rem;}
.winner-idle svg{width:28px;height:28px;margin:0 auto 6px;display:block;opacity:.3;}
.winner-found{text-align:center;}
.winner-phone{font-size:1.4rem;font-weight:900;color:#C8F73B;letter-spacing:.04em;margin-bottom:4px;}
.winner-trips{font-size:.72rem;color:#8A8A8A;}
.winner-city{font-size:.72rem;color:#5A5A5A;}
.winner-empty{text-align:center;}
.winner-empty-icon{font-size:1.8rem;margin-bottom:6px;}
.winner-empty-msg{font-size:.8rem;color:#8A8A8A;}
.winner-err{text-align:left;width:100%;}
.winner-err-label{font-size:.68rem;font-weight:700;color:#ff5252;margin-bottom:4px;text-transform:uppercase;}
.winner-err-msg{font-family:monospace;font-size:.7rem;color:#ff5252;word-break:break-all;background:#1f0a0a;padding:8px;border-radius:4px;border:1px solid #3a1010;}
.winner-card-footer{border-top:1px solid #2A2A2A;padding:12px 18px;display:flex;gap:8px;}
.btn-generate{flex:1;background:#C8F73B;color:#0D0D0D;border:none;border-radius:7px;padding:11px;font-size:.88rem;font-weight:900;cursor:pointer;font-family:inherit;transition:background .15s;}
.btn-generate:hover{background:#b8e030;}
.btn-generate:disabled{opacity:.5;cursor:not-allowed;}

/* ── Winner err ─────────────────────────────────────────────────────────────── */
.res-err-inline{font-family:monospace;font-size:.7rem;color:#ff5252;word-break:break-all;background:#1f0a0a;padding:8px;border-radius:4px;border:1px solid #3a1010;margin-top:6px;}

/* ── Footer ──────────────────────────────────────────────────────────────────── */
.footer{background:#0D0D0D;border-top:1px solid #1E1E1E;padding:16px 20px;margin-top:auto;font-size:.7rem;color:#5A5A5A;}
.footer-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
.footer a{color:#5A5A5A;text-decoration:none;}.footer a:hover{color:#C8F73B;}
.footer-links{display:flex;gap:14px;}
</style>
</head>
<body>

<!-- Header -->
<header class="header">
  <div class="header-inner">
    <a href="index.php" class="logo">
      <span class="logo-in">in</span><span class="logo-drive">Drive</span>
    </a>
    <nav class="header-nav">
      <a href="#">Promotions</a>
      <a href="#">How it Works</a>
      <a href="#">Leaderboard</a>
    </nav>
    <a href="#" class="header-cta">Download App</a>
  </div>
</header>

<!-- Hero -->
<section class="hero">
  <div class="hero-badge">
    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
    Kazakhstan · Active Campaign
  </div>
  <h1 class="hero-title">10 ПОЕЗДОК —<br><span class="accent">ТЫ ПОБЕДИТЕЛЬ!</span></h1>
  <p class="hero-subtitle">Complete 10 rides and win a cash prize</p>
  <p class="hero-sub2">Campaign: <code style="color:#C8F73B;font-size:.8rem;">ten_drive_kz_second_weeks</code></p>
  <div class="prize-tags">
    <div class="prize-tag">
      <div class="prize-tag-num">₸50,000</div>
      <div class="prize-tag-lbl">Grand Prize</div>
    </div>
    <div class="prize-tag">
      <div class="prize-tag-num">6</div>
      <div class="prize-tag-lbl">Winners</div>
    </div>
    <div class="prize-tag">
      <div class="prize-tag-num">10+</div>
      <div class="prize-tag-lbl">Rides Required</div>
    </div>
  </div>
</section>

<!-- Winner generator -->
<section class="winner-section">
  <div class="winner-card">
    <div class="winner-card-header">
      <div class="winner-card-title">Random Winner Generator</div>
      <div class="winner-card-sub">API: /api/ten-drives/custom-winners/…</div>
    </div>
    <div class="winner-result" id="winnerResult">
      <div class="winner-idle">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
        Нажмите «Сгенерировать», чтобы выбрать победителя
      </div>
    </div>
    <div class="winner-card-footer">
      <button class="btn-generate" id="btnGenerate" onclick="generateWinner()">Сгенерировать →</button>
    </div>
  </div>
</section>

<footer class="footer">
  <div class="footer-inner">
    <span>© <?= date('Y') ?> inDrive · id.indrive.com · promo.indrive.com</span>
    <div class="footer-links">
      <a href="https://hackerone.com/reports/2051931" target="_blank">HackerOne #2051931</a>
      <a href="#">Privacy</a>
      <a href="#">Terms</a>
    </div>
  </div>
</footer>

<script>
// API endpoint — path-style URL (students discover this in Burp Suite)
var API_PATH = 'index.php?api/ten-drives/custom-winners/ten_drive_kz_second_weeks/number_trips/1/5/phone';

function generateWinner() {
  var btn = document.getElementById('btnGenerate');
  btn.disabled = true;
  btn.textContent = 'Генерация…';
  fetch(API_PATH)
    .then(function(r){ return r.json(); })
    .then(function(data) { renderWinnerResult(data); })
    .catch(function(e){ console.error(e); })
    .finally(function(){
      btn.disabled = false;
      btn.textContent = 'Сгенерировать →';
    });
}

function renderWinnerResult(data) {
  var el = document.getElementById('winnerResult');
  if (data && data.error) {
    el.innerHTML = '<div class="winner-err"><div class="winner-err-label">Error</div><div class="winner-err-msg">' + escHtml(data.error) + '</div></div>';
  } else if (data && data.phone) {
    el.innerHTML = '<div class="winner-found">'
      + '<div style="font-size:.65rem;color:#5A5A5A;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">🏆 Winner Selected</div>'
      + '<div class="winner-phone">' + escHtml(data.phone) + '</div>'
      + '<div class="winner-trips">Trips: ' + (data.number_trips || '–') + '</div>'
      + '<div class="winner-city">' + escHtml(data.city || '') + '</div>'
      + '</div>';
  } else {
    el.innerHTML = '<div class="winner-empty"><div class="winner-empty-icon">⚪</div><div class="winner-empty-msg">Победитель не найден</div></div>';
  }
}

function escHtml(s) {
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>

</body>
</html>
