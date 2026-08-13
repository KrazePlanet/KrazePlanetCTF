<?php
// SQL Injection Lab 123 — DoD Research Publications Management System
// HackerOne Report #491191 (High — U.S. Dept of Defense)

$server   = "localhost";
$username = "root";
$password = "";
$database = "VexiumCTF_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) {
    if (isset($_GET['pub_group_id'])) {
        header('Content-Type: application/json');
        die(json_encode(["success" => false, "error" => "DB connection failed"]));
    }
}
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

function initializeLab123Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab123_papers (
        paper_id    INT AUTO_INCREMENT PRIMARY KEY,
        pub_group_id VARCHAR(30) NOT NULL,
        title        VARCHAR(200) NOT NULL,
        authors      VARCHAR(200) NOT NULL,
        department   VARCHAR(80)  NOT NULL,
        pub_year     INT NOT NULL DEFAULT 2019,
        UNIQUE KEY uk_grp (pub_group_id)
    )");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab123_secret (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");
    $chkS = mysqli_query($conn, "SELECT * FROM lab123_secret LIMIT 1");
    if (mysqli_num_rows($chkS) == 0) {
        mysqli_query($conn, "INSERT INTO lab123_secret (secret_data) VALUES ('flag{dod_move_papers_sqli_491191}')");
    }
    $chkP = mysqli_query($conn, "SELECT COUNT(*) AS c FROM lab123_papers");
    $r = mysqli_fetch_assoc($chkP);
    if ((int)$r['c'] === 0) {
        // Single row with UNIQUE key on pub_group_id ensures SLEEP executes exactly once
        mysqli_query($conn, "INSERT INTO lab123_papers (pub_group_id, title, authors, department, pub_year) VALUES
            ('CS-2019', 'Advances in Secure Network Protocol Design for Military Infrastructure', 'Dr. R. Harmon, Lt. Col. M. Vasquez', 'Computer Science & Cybersecurity', 2019)");
    }
}
initializeLab123Database($conn);

// Simulated WAF — blocks bare SLEEP(N); nested subquery bypasses it
function wafCheck($input) {
    $patterns = [
        "/(?<!\()SLEEP\s*\(\s*\d/i",        // SLEEP(N) not preceded by ( → BLOCKED
        "/(?<!\()BENCHMARK\s*\(\s*\d/i",    // BENCHMARK(N,...) not preceded by (
        "/UNION\s+SELECT/i",                 // UNION SELECT
        "/' OR '1'='1/i",                    // classic ' OR '1'='1
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $input)) return true;
    }
    return false;
}

// ============================================================
// API Route — GET with pub_group_id → HTML/JSON paper listing
// Simulates: GET /pubs/move_papers.php?pub_group_id=CS-2019
// ============================================================
if (isset($_GET['pub_group_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Powered-By: DoD-PubsMgmt/2.1');

    $pub_group_id = $_GET['pub_group_id'];

    if (wafCheck($pub_group_id)) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "error"   => "Request blocked by security filter",
            "code"    => "WAF_VIOLATION"
        ]);
        exit;
    }

    // pub_group_id is a string parameter — wrapped in quotes in SQL
    $sql    = "SELECT paper_id, pub_group_id, title, authors, department, pub_year
               FROM lab123_papers
               WHERE pub_group_id = '$pub_group_id'";
    $result = mysqli_query($conn, $sql);

    if ($result === false) {
        http_response_code(500);
        echo json_encode([
            "success"  => false,
            "error"    => "Internal Server Error",
            "sqlstate" => mysqli_sqlstate($conn),
            "message"  => mysqli_error($conn)
        ]);
        exit;
    }

    $row = mysqli_fetch_assoc($result);
    if ($row) {
        echo json_encode(["success" => true, "paper" => $row]);
    } else {
        echo json_encode(["success" => false, "error" => "No papers found for group: " . htmlspecialchars($pub_group_id)]);
    }
    exit;
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Publications Management — DoD Research Library</title>
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

/* ── Paper groups table ───────────────────────────────────────────────────── */
.panel{background:#fff;border:1px solid #ccc;border-radius:2px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);}
.panel-header{background:#003366;color:#fff;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;}
.panel-title{font-size:.82rem;font-weight:700;letter-spacing:.03em;}
.panel-sub{font-size:.65rem;color:rgba(255,255,255,.55);}
.panel-body{padding:0;}

/* ── Table ────────────────────────────────────────────────────────────────── */
.pub-table{width:100%;border-collapse:collapse;}
.pub-table th{background:#f0f0f4;color:#003366;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:7px 12px;text-align:left;border-bottom:2px solid #dde;}
.pub-table td{padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:.78rem;vertical-align:middle;}
.pub-table tr:last-child td{border-bottom:none;}
.pub-table tr:hover td{background:#f8f8fc;}
.grp-id{font-family:monospace;font-weight:700;color:#003366;background:#eef2ff;padding:2px 6px;border-radius:2px;font-size:.75rem;}
.btn-move{background:#4a5e2f;color:#fff;border:none;border-radius:2px;padding:5px 12px;font-size:.72rem;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:4px;transition:background .15s;white-space:nowrap;}
.btn-move:hover{background:#3a4d22;}
.btn-move svg{width:12px;height:12px;fill:none;stroke:#fff;stroke-width:2.5;}
.status-cell{font-size:.72rem;}
.status-ok{color:#27ae60;font-weight:700;}
.status-pending{color:#e67e22;font-weight:700;}

/* ── Result box ──────────────────────────────────────────────────────────── */
.result-box{margin:12px 14px;border-radius:2px;padding:8px 12px;font-size:.75rem;display:none;}
.result-ok{background:#d4edda;border:1px solid #c3e6cb;color:#155724;}
.result-err{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;}
.result-waf{background:#fff3cd;border:1px solid #ffc107;color:#856404;}

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

<!-- Gov banner -->
<div class="gov-banner">
  <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
  An official website of the United States Department of Defense
</div>

<!-- Site header -->
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

<!-- Nav -->
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
  <div>
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Publication Groups — Move Papers</span>
        <span class="panel-sub">/pubs/move_papers.php</span>
      </div>

      <!-- Table -->
      <table class="pub-table">
        <thead>
          <tr>
            <th>Group ID</th>
            <th>Description</th>
            <th>Papers</th>
            <th>Department</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><span class="grp-id">CS-2019</span></td>
            <td>Computer Science 2019 Batch</td>
            <td>24</td>
            <td>Computer Science &amp; Cybersecurity</td>
            <td><span class="status-ok">Active</span></td>
            <td>
              <button class="btn-move" onclick="movePapers('CS-2019')">
                <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                Move Papers
              </button>
            </td>
          </tr>
          <tr>
            <td><span class="grp-id">CYBER-2018</span></td>
            <td>Cybersecurity Research Publications</td>
            <td>18</td>
            <td>Defense Cybersecurity Division</td>
            <td><span class="status-ok">Active</span></td>
            <td>
              <button class="btn-move" onclick="movePapers('CYBER-2018')">
                <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                Move Papers
              </button>
            </td>
          </tr>
          <tr>
            <td><span class="grp-id">MATH-2020</span></td>
            <td>Applied Mathematics Research</td>
            <td>9</td>
            <td>Mathematical Sciences</td>
            <td><span class="status-pending">Pending Review</span></td>
            <td>
              <button class="btn-move" onclick="movePapers('MATH-2020')">
                <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                Move Papers
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Result area -->
      <div class="result-box" id="resultBox"></div>

      <!-- Network info footer inside panel -->
      <div style="margin:0 14px 14px;padding:7px 0;border-top:1px solid #f0f0f0;font-size:.65rem;color:#888;">
        <span style="color:#555;font-weight:700;">Request URL:</span> /pubs/move_papers.php?pub_group_id=<span style="font-family:monospace;color:#003366;font-weight:700;">CS-2019</span>
      </div>
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
        <div class="info-row"><span class="info-lbl">Last scan</span><span class="info-val">2020-02-05</span></div>
      </div>
    </div>

    <div class="sidebar-card">
      <div class="sidebar-card-header" style="background:#4a5e2f;color:#fff;">Recent Activity</div>
      <div class="sidebar-card-body" style="font-size:.7rem;line-height:1.7;">
        <div style="margin-bottom:4px;color:#888;">Feb 5, 2020 · 10:00 UTC</div>
        <div>sp1d3rs moved CS-2019 papers to archive</div>
        <div style="margin-top:6px;color:#888;">Feb 4, 2020 · 16:30 UTC</div>
        <div>CYBER-2018 group published to DTIC</div>
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
  <span>
    Version 2.1 · Restricted Access · DoD VDP
  </span>
</footer>

<script>
async function movePapers(groupId) {
    var box = document.getElementById('resultBox');
    box.style.display = 'none';
    box.className = 'result-box';

    // Move papers — GET request with pub_group_id
    try {
        var resp = await fetch('index.php?pub_group_id=' + encodeURIComponent(groupId));
        var data = await resp.json();

        if (data.success) {
            var p = data.paper;
            box.className = 'result-box result-ok';
            box.innerHTML = '&#10003; Move operation queued for group <strong>' + esc(p.pub_group_id) + '</strong> — ' +
                '"' + esc(p.title) + '" · ' + esc(p.authors) + ' (' + esc(p.pub_year) + ')';
        } else if (resp.status === 403) {
            box.className = 'result-box result-waf';
            box.innerHTML = '&#9888; <strong>WAF blocked the request:</strong> ' + esc(data.code || 'WAF_VIOLATION');
        } else {
            box.className = 'result-box result-err';
            box.innerHTML = '&#10007; ' + esc(data.error || 'Unknown error');
        }
    } catch(e) {
        box.className = 'result-box result-err';
        box.innerHTML = '&#10007; Network error: ' + esc(e.message);
    }
    box.style.display = 'block';
}

function esc(s) {
    var d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
}
</script>

</body>
</html>
