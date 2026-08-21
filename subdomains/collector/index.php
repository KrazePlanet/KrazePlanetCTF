<?php
// ============================================================
// SQL Injection Lab 110 - Time-based Blind SQLi via User-Agent
// Platform: labs.data.gov | HackerOne Report #297478
// Endpoint: GET /dashboard/datagov/csv_to_json
// Vulnerability: User-Agent header used raw in SQL query
// Technique: XOR-based payload with arithmetic inside sleep()
//   'XOR(if(now()=sysdate(),sleep(5*5),0))OR'
//   sleep(5*5)=25s, sleep(5*5*0)=0s, sleep(6*6-30)=6s
// ============================================================

// --- Database Configuration ---
$server   = "localhost";
$username = "root";
$password = "";
$database = "KrazePlanet_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

// --- Initialize Database ---
function initializeLab110Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab110_access_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_agent VARCHAR(500) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        endpoint VARCHAR(100) NOT NULL,
        accessed_at DATETIME NOT NULL
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab110_secret (
        id INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");

    $check = mysqli_query($conn, "SELECT * FROM lab110_secret LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO lab110_secret (secret_data) VALUES ('flag{datagov_ua_sqli_297478}')");
    }

    // Seed some realistic access log entries
    $check2 = mysqli_query($conn, "SELECT * FROM lab110_access_log LIMIT 1");
    if (mysqli_num_rows($check2) == 0) {
        mysqli_query($conn, "INSERT INTO lab110_access_log (user_agent, ip, endpoint, accessed_at) VALUES
            ('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36', '198.51.100.42', '/dashboard/datagov/csv_to_json', DATE_SUB(NOW(), INTERVAL 12 MINUTE)),
            ('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15', '203.0.113.18', '/dashboard/datagov/csv_to_json', DATE_SUB(NOW(), INTERVAL 8 MINUTE)),
            ('python-requests/2.31.0', '203.0.113.99', '/dashboard/datagov/csv_to_json', DATE_SUB(NOW(), INTERVAL 5 MINUTE)),
            ('Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0', '192.0.2.77', '/dashboard/datagov/csv_to_json', DATE_SUB(NOW(), INTERVAL 2 MINUTE))");
    }
}
initializeLab110Database($conn);

// ===================================================================
// VULNERABLE: User-Agent header read on every GET request and
// used directly in SQL without sanitization.
//
// XOR-based payload (appended to a real Chrome UA string):
//   'XOR(if(now()=sysdate(),sleep(5*5),0))OR'
//
// Full payload examples:
//   Mozilla/5.0 ...Chrome/55.0.2883.87'XOR(if(now()=sysdate(),sleep(5*5),0))OR'
//     → server sleeps 25 seconds (5×5)
//
//   Mozilla/5.0 ...Chrome/55.0.2883.87'XOR(if(now()=sysdate(),sleep(5*5*0),0))OR'
//     → immediate response (5×5×0 = 0)
//
//   Mozilla/5.0 ...Chrome/55.0.2883.87'XOR(if(now()=sysdate(),sleep(6*6-30),0))OR'
//     → server sleeps 6 seconds (6×6−30 = 6)
//
// MySQL evaluates the arithmetic expression inside sleep() — this
// lets you confirm blind extraction logic purely via timing.
// ===================================================================
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$clientIp  = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// ⚠ VULNERABLE QUERY — $userAgent echoed raw into SQL string
$sql = "SELECT * FROM lab110_access_log WHERE user_agent = '$userAgent'";
mysqli_query($conn, $sql);

// Log this visit (safe INSERT using prepared statement — only the SELECT is vulnerable)
$stmt = mysqli_prepare($conn, "INSERT INTO lab110_access_log (user_agent, ip, endpoint, accessed_at) VALUES (?, ?, '/dashboard/datagov/csv_to_json', NOW())");
mysqli_stmt_bind_param($stmt, "ss", $userAgent, $clientIp);
mysqli_stmt_execute($stmt);

// --- Handle CSV conversion (GET param) ---
$csvUrl      = trim($_GET['url'] ?? '');
$convertDone = false;
$jsonOutput  = '';
$csvError    = '';

if ($csvUrl !== '') {
    $convertDone = true;
    // Static sample JSON — realistic but not actually fetching remote URLs
    $jsonOutput = json_encode([
        ["state" => "Alabama",    "code" => "AL", "population" => 5024279,  "area_sq_mi" => 52420],
        ["state" => "Alaska",     "code" => "AK", "population" => 733391,   "area_sq_mi" => 663268],
        ["state" => "Arizona",    "code" => "AZ", "population" => 7151502,  "area_sq_mi" => 113990],
        ["state" => "Arkansas",   "code" => "AR", "population" => 3011524,  "area_sq_mi" => 53179],
        ["state" => "California", "code" => "CA", "population" => 39538223, "area_sq_mi" => 163696],
    ], JSON_PRETTY_PRINT);
}

// --- Fetch recent access log for display ---
$logRows = [];
$logRes  = mysqli_query($conn, "SELECT id, user_agent, ip, endpoint, accessed_at FROM lab110_access_log ORDER BY id DESC LIMIT 6");
while ($row = mysqli_fetch_assoc($logRes)) {
    $logRows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CSV to JSON Converter — labs.data.gov</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Source Sans Pro',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f1f1f1;color:#212121;font-size:15px;min-height:100vh;display:flex;flex-direction:column;}

/* ── Gov Banner ──────────────────────────────────────────────────────────────── */
.gov-banner{background:#fff;border-bottom:1px solid #dfe1e2;padding:4px 0;}
.gov-banner-inner{max-width:1200px;margin:0 auto;padding:0 16px;display:flex;align-items:center;gap:7px;font-size:.75rem;color:#555;}
.gov-banner-inner svg{flex-shrink:0;}
.gov-banner-inner strong{color:#212121;}

/* ── Top Header ──────────────────────────────────────────────────────────────── */
.topbar{background:#1a4480;padding:0;}
.topbar-inner{max-width:1200px;margin:0 auto;padding:0 16px;display:flex;align-items:center;height:56px;gap:20px;}
.site-logo{display:flex;align-items:center;gap:8px;text-decoration:none;}
.logo-data{font-size:1.4rem;font-weight:800;color:#fff;letter-spacing:-.02em;}
.logo-gov{font-size:1.4rem;font-weight:300;color:rgba(255,255,255,.7);letter-spacing:-.02em;}
.logo-labs{background:#e52207;color:#fff;font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;padding:2px 6px;border-radius:2px;margin-left:2px;}
.topbar-nav{display:flex;gap:0;margin-left:auto;}
.topbar-nav a{color:rgba(255,255,255,.8);font-size:.78rem;font-weight:500;text-decoration:none;padding:0 14px;line-height:56px;display:block;border-bottom:3px solid transparent;transition:color .15s,border-color .15s;}
.topbar-nav a:hover{color:#fff;border-bottom-color:rgba(255,255,255,.4);}
.topbar-nav a.active{color:#fff;border-bottom-color:#fff;}

/* ── Sub-header ──────────────────────────────────────────────────────────────── */
.subheader{background:#162e51;border-bottom:4px solid #e52207;}
.subheader-inner{max-width:1200px;margin:0 auto;padding:20px 16px;}
.subheader-title{font-size:1.5rem;font-weight:700;color:#fff;}
.subheader-path{font-size:.72rem;color:rgba(255,255,255,.6);margin-top:3px;font-family:monospace;}
.subheader-desc{font-size:.85rem;color:rgba(255,255,255,.75);margin-top:6px;max-width:600px;line-height:1.5;}

/* ── Page body ───────────────────────────────────────────────────────────────── */
.page-body{flex:1;max-width:1200px;margin:0 auto;padding:28px 16px;width:100%;display:grid;grid-template-columns:1fr 300px;gap:24px;}
@media(max-width:820px){.page-body{grid-template-columns:1fr;}}

/* ── Card ────────────────────────────────────────────────────────────────────── */
.card{background:#fff;border:1px solid #dfe1e2;border-radius:4px;margin-bottom:20px;}
.card-header{padding:14px 18px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;gap:8px;}
.card-header-title{font-size:.9rem;font-weight:700;color:#1a4480;}
.card-header-title svg{width:16px;height:16px;flex-shrink:0;}
.card-body{padding:18px;}

/* ── Converter form ──────────────────────────────────────────────────────────── */
.form-label{display:block;font-size:.8rem;font-weight:700;color:#3d4551;margin-bottom:5px;}
.form-hint{font-size:.7rem;color:#71767a;margin-top:3px;}
.url-row{display:flex;gap:8px;margin-bottom:6px;}
.form-input{flex:1;padding:8px 12px;border:1px solid #adadad;border-radius:3px;font-size:.85rem;color:#212121;font-family:inherit;outline:none;transition:border-color .15s;}
.form-input:focus{border-color:#0071bc;box-shadow:0 0 0 2px rgba(0,113,188,.2);}
.btn-primary{background:#0071bc;color:#fff;border:none;border-radius:3px;padding:8px 18px;font-size:.82rem;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;transition:background .15s;}
.btn-primary:hover{background:#205493;}
.sample-links{display:flex;gap:12px;flex-wrap:wrap;margin-top:10px;}
.sample-link{font-size:.72rem;color:#0071bc;text-decoration:none;}
.sample-link:hover{text-decoration:underline;}
.sample-label{font-size:.72rem;color:#71767a;}

/* ── JSON output ─────────────────────────────────────────────────────────────── */
.json-output{background:#1e2a3a;border-radius:3px;padding:14px;font-family:'SFMono-Regular',Consolas,monospace;font-size:.72rem;color:#e8e8e8;line-height:1.7;overflow-x:auto;max-height:300px;overflow-y:auto;margin-top:14px;}
.json-key{color:#79c0ff;}.json-str{color:#a5d6a7;}.json-num{color:#ffd700;}.json-result-header{font-size:.72rem;font-weight:700;color:#71767a;margin-bottom:4px;}

/* ── Access log table ────────────────────────────────────────────────────────── */
.log-table{width:100%;border-collapse:collapse;font-size:.72rem;}
.log-table th{padding:6px 10px;text-align:left;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#71767a;border-bottom:2px solid #dfe1e2;white-space:nowrap;}
.log-table td{padding:7px 10px;border-bottom:1px solid #f0f0f0;vertical-align:top;color:#3d4551;}
.log-table tr:last-child td{border-bottom:none;}
.log-table tr:hover td{background:#f8fbff;}
.ua-cell{max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-family:monospace;font-size:.68rem;color:#212121;}
.ip-cell{font-family:monospace;font-size:.68rem;color:#71767a;white-space:nowrap;}
.time-cell{white-space:nowrap;color:#71767a;}
.log-self{background:#fff8e1;}
.log-self td{color:#b45309 !important;}

/* ── Sidebar ─────────────────────────────────────────────────────────────────── */
.sidebar-card{background:#fff;border:1px solid #dfe1e2;border-radius:4px;margin-bottom:16px;}
.sidebar-card-header{padding:10px 14px;border-bottom:1px solid #f0f0f0;font-size:.78rem;font-weight:700;color:#1a4480;}
.sidebar-card-body{padding:12px 14px;}
.info-list li{font-size:.75rem;color:#3d4551;line-height:1.8;list-style:none;padding-left:0;}
.info-list li::before{content:"•";color:#0071bc;margin-right:6px;}
.tag-list{display:flex;flex-wrap:wrap;gap:5px;margin-top:4px;}
.tag{background:#e8f1f9;color:#1a4480;font-size:.65rem;font-weight:600;padding:2px 8px;border-radius:10px;}
.meta-row{display:flex;justify-content:space-between;align-items:center;padding:4px 0;border-bottom:1px solid #f7f7f7;font-size:.72rem;}
.meta-row:last-child{border-bottom:none;}
.meta-label{color:#71767a;}
.meta-value{color:#212121;font-weight:600;font-family:monospace;font-size:.68rem;}

/* ── Breadcrumb ──────────────────────────────────────────────────────────────── */
.breadcrumb{font-size:.72rem;color:#71767a;display:flex;align-items:center;gap:5px;margin-bottom:16px;}
.breadcrumb a{color:#0071bc;text-decoration:none;}.breadcrumb a:hover{text-decoration:underline;}
.breadcrumb-sep{color:#bbb;}

/* ── Footer ──────────────────────────────────────────────────────────────────── */
.footer{background:#1a4480;color:rgba(255,255,255,.75);padding:20px 16px;margin-top:auto;}
.footer-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;font-size:.72rem;}
.footer a{color:rgba(255,255,255,.75);text-decoration:none;}.footer a:hover{color:#fff;text-decoration:underline;}
.footer-links{display:flex;gap:16px;}
</style>
</head>
<body>

<!-- Gov Banner -->
<div class="gov-banner">
  <div class="gov-banner-inner">
    <svg width="16" height="16" viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="20" fill="#003366"/><path d="M20 8l2.4 7.4H30l-6.2 4.5 2.4 7.4L20 23l-6.2 4.3 2.4-7.4L10 15.4h7.6z" fill="#fff"/></svg>
    <span>An official website of the <strong>United States government</strong></span>
  </div>
</div>

<!-- Top Header -->
<header class="topbar">
  <div class="topbar-inner">
    <a href="index.php" class="site-logo">
      <span class="logo-data">data</span><span class="logo-gov">.gov</span>
      <span class="logo-labs">labs</span>
    </a>
    <nav class="topbar-nav">
      <a href="#">Home</a>
      <a href="#">Datasets</a>
      <a href="#">Topics</a>
      <a href="index.php" class="active">Labs</a>
      <a href="#">About</a>
    </nav>
  </div>
</header>

<!-- Sub-header -->
<div class="subheader">
  <div class="subheader-inner">
    <div class="subheader-title">CSV to JSON Converter</div>
    <div class="subheader-path">GET /dashboard/datagov/csv_to_json</div>
    <div class="subheader-desc">Convert CSV datasets to JSON format for API integration and developer use. Provide a public CSV URL or paste your data directly.</div>
  </div>
</div>

<!-- Page Body -->
<div class="page-body">
  <main>
    <div class="breadcrumb">
      <a href="#">data.gov</a><span class="breadcrumb-sep">/</span>
      <a href="#">Labs</a><span class="breadcrumb-sep">/</span>
      <span>CSV to JSON</span>
    </div>

    <!-- Converter Tool -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          CSV to JSON Converter
        </div>
      </div>
      <div class="card-body">
        <form method="GET" action="index.php">
          <label class="form-label" for="csv-url">CSV Dataset URL</label>
          <div class="url-row">
            <input class="form-input" type="text" id="csv-url" name="url"
              value="<?= htmlspecialchars($csvUrl) ?>"
              placeholder="https://data.gov/dataset/example.csv">
            <button class="btn-primary" type="submit">Convert</button>
          </div>
          <div class="form-hint">Enter a publicly accessible CSV URL. The file will be fetched and converted to JSON.</div>
          <div class="sample-links">
            <span class="sample-label">Try a sample:</span>
            <a class="sample-link" href="index.php?url=https://data.gov/samples/states.csv">states.csv</a>
            <a class="sample-link" href="index.php?url=https://data.gov/samples/agencies.csv">agencies.csv</a>
            <a class="sample-link" href="index.php?url=https://data.gov/samples/spending.csv">spending.csv</a>
          </div>
        </form>

        <?php if ($convertDone): ?>
        <div style="margin-top:18px;">
          <div class="json-result-header">JSON Output — <?= htmlspecialchars($csvUrl) ?></div>
          <div class="json-output"><pre><?= htmlspecialchars($jsonOutput) ?></pre></div>
          <div style="display:flex;gap:10px;margin-top:10px;">
            <button class="btn-primary" style="font-size:.72rem;padding:5px 12px;" onclick="navigator.clipboard.writeText(document.querySelector('.json-output pre').textContent)">Copy JSON</button>
            <button class="btn-primary" style="font-size:.72rem;padding:5px 12px;background:#205493;">Download .json</button>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Access Log -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 17H5a2 2 0 00-2 2 2 2 0 002 2h14a2 2 0 002-2 2 2 0 00-2-2h-4m-4 0V3m0 14l-3-3m3 3l3-3"/></svg>
          Recent Access Log
        </div>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="log-table">
          <thead>
            <tr>
              <th>#</th>
              <th>User-Agent</th>
              <th>IP</th>
              <th>Accessed</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logRows as $i => $row): ?>
            <tr <?= $i === 0 ? 'class="log-self"' : '' ?>>
              <td style="color:#71767a;"><?= htmlspecialchars($row['id']) ?></td>
              <td class="ua-cell" title="<?= htmlspecialchars($row['user_agent']) ?>"><?= htmlspecialchars($row['user_agent']) ?></td>
              <td class="ip-cell"><?= htmlspecialchars($row['ip']) ?></td>
              <td class="time-cell"><?= htmlspecialchars($row['accessed_at']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- Sidebar -->
  <aside>
    <div class="sidebar-card">
      <div class="sidebar-card-header">About This Tool</div>
      <div class="sidebar-card-body">
        <ul class="info-list">
          <li>Accepts public CSV URLs</li>
          <li>Returns RFC 4627 compliant JSON</li>
          <li>Supports UTF-8 encoded files</li>
          <li>Max file size: 50 MB</li>
          <li>All requests are logged for analytics</li>
        </ul>
      </div>
    </div>

    <div class="sidebar-card">
      <div class="sidebar-card-header">Endpoint Details</div>
      <div class="sidebar-card-body">
        <div class="meta-row"><span class="meta-label">Method</span><span class="meta-value">GET</span></div>
        <div class="meta-row"><span class="meta-label">Path</span><span class="meta-value">/dashboard/datagov/csv_to_json</span></div>
        <div class="meta-row"><span class="meta-label">Host</span><span class="meta-value">labs.data.gov</span></div>
        <div class="meta-row"><span class="meta-label">Auth</span><span class="meta-value">None required</span></div>
        <div class="meta-row"><span class="meta-label">Logged fields</span><span class="meta-value">UA, IP, path, time</span></div>
      </div>
    </div>

    <div class="sidebar-card">
      <div class="sidebar-card-header">API Usage</div>
      <div class="sidebar-card-body">
        <div style="font-size:.72rem;color:#3d4551;margin-bottom:6px;">Query parameters:</div>
        <div class="meta-row"><span class="meta-label">url</span><span class="meta-value">CSV file URL</span></div>
        <div style="font-size:.72rem;color:#71767a;margin-top:8px;">All request headers (including <code style="background:#f1f1f1;padding:1px 4px;border-radius:2px;font-size:.68rem;">User-Agent</code>) are recorded in the access log.</div>
      </div>
    </div>

    <div class="sidebar-card">
      <div class="sidebar-card-header">Related Tools</div>
      <div class="sidebar-card-body">
        <ul class="info-list">
          <li><a href="#" style="color:#0071bc;font-size:.72rem;">JSON Schema Validator</a></li>
          <li><a href="#" style="color:#0071bc;font-size:.72rem;">API Catalog Search</a></li>
          <li><a href="#" style="color:#0071bc;font-size:.72rem;">Dataset Preview</a></li>
          <li><a href="#" style="color:#0071bc;font-size:.72em;">Metadata Extractor</a></li>
        </ul>
      </div>
    </div>
  </aside>
</div>

<footer class="footer">
  <div class="footer-inner">
    <span>© <?= date('Y') ?> Data.gov · A project of the U.S. General Services Administration</span>
    <div class="footer-links">
      <a href="https://hackerone.com/reports/297478" target="_blank">HackerOne Report #297478</a>
      <a href="#">Accessibility</a>
      <a href="#">Privacy</a>
      <a href="#">FOIA</a>
    </div>
  </div>
</footer>

</body>
</html>
