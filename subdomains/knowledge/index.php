<?php
// SQL Injection Lab 124 — DoD Publications Search Page
// HackerOne Report #2312334 (High — U.S. Dept of Defense)

$server   = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$username = "root";
$password = "";
$database = "KrazePlanet_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        die(json_encode(["success" => false, "error" => "DB connection failed"]));
    }
}
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

function initializeLab124Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab124_pubs (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        years      INT NOT NULL,
        title      VARCHAR(200) NOT NULL,
        authors    VARCHAR(200) NOT NULL,
        department VARCHAR(80)  NOT NULL
    )");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab124_secret (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");
    $chkS = mysqli_query($conn, "SELECT * FROM lab124_secret LIMIT 1");
    if (mysqli_num_rows($chkS) == 0) {
        mysqli_query($conn, "INSERT INTO lab124_secret (secret_data) VALUES ('flag{dod_pubs_xor_sqli_2312334}')");
    }
    $chk = mysqli_query($conn, "SELECT COUNT(*) AS c FROM lab124_pubs");
    $r = mysqli_fetch_assoc($chk);
    if ((int)$r['c'] === 0) {
        // Single row ensures clean 1:1 SLEEP delay
        mysqli_query($conn, "INSERT INTO lab124_pubs (years, title, authors, department) VALUES
            (2017, 'Advances in Secure Network Protocol Design for Military Infrastructure', 'Hurlburt', 'Computer Science & Cybersecurity')");
    } else {
        // Ensure author is exactly 'Hurlburt' for report-matching search
        mysqli_query($conn, "UPDATE lab124_pubs SET authors = 'Hurlburt' WHERE authors LIKE 'Hurlburt%'");
    }
}
initializeLab124Database($conn);

// POST search endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Powered-By: DoD-PubsSearch/2.1');

    $years   = (int)($_POST['years'] ?? 0);
    $authors = $_POST['authors'] ?? '';

    // years is safely cast to int
    // authors is a string parameter — wrapped in quotes in SQL
    $sql    = "SELECT id, years, title, authors, department
               FROM lab124_pubs
               WHERE years = $years AND authors = '$authors'";
    $result = mysqli_query($conn, $sql);

    if ($result === false) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => mysqli_error($conn)]);
        exit;
    }

    $rows = [];
    while ($r = mysqli_fetch_assoc($result)) $rows[] = $r;

    echo json_encode([
        "success" => !empty($rows),
        "count"   => count($rows),
        "papers"  => $rows
    ]);
    exit;
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Publications — DoD Research Library</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#e8e8e8;color:#1a1a2e;font-size:13px;min-height:100vh;display:flex;flex-direction:column;}

/* ── Gov banner ───────────────────────────────────────────────────────────── */
.gov-banner{background:#003366;color:rgba(255,255,255,.75);font-size:.65rem;padding:4px 20px;display:flex;align-items:center;gap:6px;}
.gov-banner svg{width:12px;height:12px;fill:rgba(255,255,255,.6);}

/* ── Site header ──────────────────────────────────────────────────────────── */
.site-header{background:#002244;border-bottom:3px solid #c8a84b;padding:0;}
.site-header-inner{max-width:1100px;margin:0 auto;padding:10px 20px;display:flex;align-items:center;gap:14px;}
.site-seal{width:48px;height:48px;background:radial-gradient(circle,#c8a84b,#9a7c2e);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:2px solid rgba(200,168,75,.4);}
.site-seal svg{width:26px;height:26px;fill:#fff;}
.site-title{color:#fff;}
.site-title-main{font-size:1rem;font-weight:800;letter-spacing:.02em;line-height:1.2;}
.site-title-sub{font-size:.65rem;color:rgba(255,255,255,.55);letter-spacing:.06em;text-transform:uppercase;margin-top:1px;}

/* ── Nav ─────────────────────────────────────────────────────────────────── */
.site-nav{background:#001933;border-bottom:1px solid rgba(200,168,75,.2);}
.site-nav-inner{max-width:1100px;margin:0 auto;padding:0 20px;display:flex;}
.site-nav a{color:rgba(255,255,255,.55);padding:8px 14px;font-size:.72rem;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;display:inline-block;letter-spacing:.04em;text-transform:uppercase;}
.site-nav a:hover{color:#fff;}
.site-nav a.active{color:#c8a84b;border-bottom-color:#c8a84b;}

/* ── Content ─────────────────────────────────────────────────────────────── */
.content-wrap{max-width:1100px;margin:0 auto;padding:16px 20px;flex:1;display:grid;grid-template-columns:1fr 270px;gap:16px;}

/* ── Panel ──────────────────────────────────────────────────────────────── */
.panel{background:#fff;border:1px solid #ccc;border-radius:2px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);}
.panel-header{background:#003366;color:#fff;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;}
.panel-title{font-size:.82rem;font-weight:700;letter-spacing:.03em;}
.panel-sub{font-size:.65rem;color:rgba(255,255,255,.55);}

/* ── Search form ──────────────────────────────────────────────────────────── */
.search-form{padding:14px;border-bottom:1px solid #f0f0f0;display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;}
.sf-group{display:flex;flex-direction:column;gap:3px;}
.sf-group label{font-size:.68rem;font-weight:700;color:#444;text-transform:uppercase;letter-spacing:.05em;}
.sf-group select,.sf-group input{border:1px solid #ccc;border-radius:2px;padding:5px 7px;font-size:.8rem;font-family:inherit;outline:none;}
.sf-group select{width:90px;}
.sf-group input{width:220px;}
.sf-btn{background:#4a5e2f;color:#fff;border:none;border-radius:2px;padding:6px 16px;font-size:.78rem;font-weight:700;cursor:pointer;font-family:inherit;}
.sf-btn:hover{background:#3a4d22;}

/* ── Results ─────────────────────────────────────────────────────────────── */
.results-body{padding:0;}
.no-results{padding:24px;text-align:center;font-size:.82rem;color:#888;}
.result-row{padding:10px 14px;border-bottom:1px solid #f0f0f0;font-size:.78rem;}
.result-row:last-child{border-bottom:none;}
.result-title{font-weight:700;color:#003366;font-size:.82rem;margin-bottom:3px;}
.result-meta{color:#666;font-size:.72rem;line-height:1.5;}
.result-meta span{display:inline-block;margin-right:12px;}
.result-meta strong{color:#444;}

/* ── Network info footer ──────────────────────────────────────────────────── */
.net-info{margin:0 14px 14px;padding:7px 0;border-top:1px solid #f0f0f0;font-size:.65rem;color:#888;}

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.sidebar{}
.sidebar-card{background:#fff;border:1px solid #ccc;border-radius:2px;overflow:hidden;margin-bottom:12px;box-shadow:0 1px 3px rgba(0,0,0,.08);}
.sidebar-card-header{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;padding:7px 11px;}
.sidebar-card-body{padding:10px 11px;font-size:.73rem;line-height:1.6;color:#444;}
.info-row{display:flex;gap:6px;margin-bottom:5px;font-size:.72rem;}
.info-lbl{color:#888;min-width:70px;flex-shrink:0;}
.info-val{font-weight:700;color:#1a1a2e;}

/* ── Footer ──────────────────────────────────────────────────────────────── */
footer{background:#001933;color:#6b6b8a;font-size:.65rem;padding:10px 20px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:4px;border-top:2px solid #c8a84b;}
footer a{color:#6b6b8a;text-decoration:none;}.footer a:hover{color:#c8a84b;}
</style>
</head>
<body>

<div class="gov-banner">
  <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
  An official website of the United States Department of Defense
</div>

<header class="site-header">
  <div class="site-header-inner">
    <div class="site-seal">
      <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
    </div>
    <div class="site-title">
      <div class="site-title-main">DoD Research Publications Management System</div>
      <div class="site-title-sub">Defense Technical Information Center (DTIC) · /pubs/ Module</div>
    </div>
  </div>
</header>

<nav class="site-nav">
  <div class="site-nav-inner">
    <a href="#">Home</a>
    <a href="#" class="active">Publications</a>
    <a href="#">Groups</a>
    <a href="#">Admin</a>
    <a href="#">Reports</a>
  </div>
</nav>

<div class="content-wrap">

  <!-- Main panel -->
  <div class="panel">
    <div class="panel-header">
      <span class="panel-title">Search Publications</span>
      <span class="panel-sub">/pubs/index.php</span>
    </div>

    <form class="search-form" id="searchForm" method="POST" action="index.php" onsubmit="event.preventDefault(); doSearch();">
      <div class="sf-group">
        <label for="years">Year</label>
        <select id="years" name="years">
          <option value="2015">2015</option>
          <option value="2016">2016</option>
          <option value="2017" selected>2017</option>
          <option value="2018">2018</option>
          <option value="2019">2019</option>
          <option value="2020">2020</option>
        </select>
      </div>
      <div class="sf-group">
        <label for="authors">Author(s)</label>
        <input type="text" id="authors" name="authors" placeholder="e.g. Hurlburt" autocomplete="off">
      </div>
      <button type="submit" class="sf-btn">Search</button>
    </form>

    <div class="results-body" id="results"></div>

    <div class="net-info" id="netInfo" style="display:none;">
      <span style="color:#555;font-weight:700;">Request:</span> POST /pubs/index.php
    </div>
  </div>

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-card">
      <div class="sidebar-card-header" style="background:#003366;color:#fff;">System Status</div>
      <div class="sidebar-card-body" style="font-size:.7rem;line-height:1.7;">
        <div class="info-row"><span class="info-lbl">Database</span><span class="info-val">MySQL 5.5.62</span></div>
        <div class="info-row"><span class="info-lbl">Status</span><span style="color:#27ae60;font-weight:700;">Operational</span></div>
        <div class="info-row"><span class="info-lbl">WAF</span><span style="color:#27ae60;font-weight:700;">Active</span></div>
        <div class="info-row"><span class="info-lbl">Last scan</span><span class="info-val">2024-01-11</span></div>
      </div>
    </div>

    <div class="sidebar-card">
      <div class="sidebar-card-header" style="background:#4a5e2f;color:#fff;">Recent Activity</div>
      <div class="sidebar-card-body" style="font-size:.7rem;line-height:1.7;">
        <div style="margin-bottom:4px;color:#888;">Jan 11, 2024 · 14:48 UTC</div>
        <div>Security scan completed — 2 findings flagged</div>
        <div style="margin-top:6px;color:#888;">Jan 10, 2024 · 09:15 UTC</div>
        <div>CS-2019 group indexed in search catalog</div>
      </div>
    </div>

    <div class="sidebar-card">
      <div class="sidebar-card-header" style="background:#6b6b6b;color:#fff;">About DTIC</div>
      <div class="sidebar-card-body" style="font-size:.7rem;line-height:1.7;color:#555;">
        Defense Technical Information Center (DTIC) serves the DoD and defense contractor community by providing scientific and technical information.
      </div>
    </div>
  </div>

</div><!-- .content-wrap -->

<footer>
  <span>DoD Research Publications Management System · DTIC · /pubs/ · Restricted Access</span>
  <span>Version 2.1 · Restricted Access · DoD VDP</span>
</footer>

<script>
async function doSearch() {
    var form = document.getElementById('searchForm');
    var results = document.getElementById('results');
    var netInfo = document.getElementById('netInfo');
    var fd = new FormData(form);

    // Search — POST with years and authors
    try {
        var resp = await fetch('index.php', { method: 'POST', body: fd });
        var data = await resp.json();

        if (data.success && data.count > 0) {
            var html = '';
            data.papers.forEach(function(p) {
                html += '<div class="result-row">' +
                    '<div class="result-title">' + esc(p.title) + '</div>' +
                    '<div class="result-meta">' +
                    '<span><strong>Authors:</strong> ' + esc(p.authors) + '</span>' +
                    '<span><strong>Year:</strong> ' + esc(p.years) + '</span>' +
                    '<span><strong>Department:</strong> ' + esc(p.department) + '</span>' +
                    '</div></div>';
            });
            results.innerHTML = html;
        } else {
            results.innerHTML = '<div class="no-results">No publications found matching your search criteria.</div>';
        }
    } catch(e) {
        results.innerHTML = '<div class="no-results">Search error: ' + esc(e.message) + '</div>';
    }
    netInfo.style.display = 'block';
}

function esc(s) {
    var d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
}
</script>

</body>
</html>
