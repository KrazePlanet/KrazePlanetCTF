<?php
// ============================================================
// SQL Injection Lab 113 - ORDER BY Injection via WordPress Shortcode
// Platform: drivegrab.com | HackerOne Report #273946
// Endpoint: POST /wp-admin/admin-ajax.php (action=frm_forms_preview)
// Vulnerability: [display-frm-data order=...] shortcode param
//   used raw in SQL ORDER BY clause — no authentication required
// Payloads:
//   order=zzz                         → SQL error (confirms injection)
//   order=IF(1=1,id,created_at)       → sorted by id (true branch)
//   order=IF(1=2,id,created_at)       → sorted by created_at (false branch)
//   order=IF(1=1,id,created_at) DESC  → reverse of above
// ============================================================

// --- Database Configuration ---
$server   = "localhost";
$username = "root";
$password = "";
$database = "VexiumCTF_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

// --- Initialize Database ---
function initializeLab113Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab113_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        form_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        value TEXT NOT NULL,
        created_at DATETIME NOT NULL
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab113_secret (
        id INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");

    $check = mysqli_query($conn, "SELECT * FROM lab113_secret LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO lab113_secret (secret_data) VALUES ('flag{grab_formidable_sqli_273946}')");
    }

    // Seed entries — timestamps are REVERSED relative to id so that
    //   ORDER BY id         → first: Ahmad Yusof  (id=1, newest date)
    //   ORDER BY created_at → first: Noraini Abdullah (id=5, oldest date 2017-09-25)
    // This makes IF(1=1,id,created_at) vs IF(1=2,id,created_at) return different first entries.
    $checkEntries = mysqli_query($conn, "SELECT COUNT(*) AS c FROM lab113_entries");
    $row = mysqli_fetch_assoc($checkEntries);
    if ((int)$row['c'] === 0) {
        mysqli_query($conn, "INSERT INTO lab113_entries (form_id, name, value, created_at) VALUES
            (1, 'Ahmad Yusof',          'Name: Ahmad Yusof | Email: ahmad.yusof@gmail.com | Phone: +6011-23456789 | Plate: WXX 1234 | Vehicle: Toyota Vios',       '2017-09-29 11:00:00'),
            (1, 'Siti Rahayu Bte Omar', 'Name: Siti Rahayu | Email: siti.rahayu@outlook.com | Phone: +6012-98765432 | Plate: BXX 5678 | Vehicle: Proton Saga',    '2017-09-28 16:45:00'),
            (1, 'Kumar Selvam',         'Name: Kumar Selvam | Email: kumar.s@gmail.com | Phone: +6016-11223344 | Plate: JXX 9012 | Vehicle: Honda City',           '2017-09-27 09:15:00'),
            (1, 'Tan Wei Ming',         'Name: Tan Wei Ming | Email: tanwm@yahoo.com | Phone: +6018-55667788 | Plate: PXX 3456 | Vehicle: Perodua Myvi',           '2017-09-26 14:20:00'),
            (1, 'Noraini Bte Abdullah', 'Name: Noraini Abdullah | Email: noraini.ab@gmail.com | Phone: +6019-44332211 | Plate: SGX 7890 | Vehicle: Nissan Almera', '2017-09-25 10:30:00'),
            (2, 'Lee Chong Wei',        'Name: Lee Chong Wei | Email: lcw@gmail.com | Phone: +6013-12345678 | Plate: KXX 2233 | Vehicle: Toyota Camry',            '2017-09-30 08:00:00')");
    } else {
        // Migrate existing data: update timestamps to reversed order if stale
        mysqli_query($conn, "UPDATE lab113_entries SET created_at='2017-09-29 11:00:00' WHERE id=1 AND created_at='2017-09-25 10:30:00'");
        mysqli_query($conn, "UPDATE lab113_entries SET created_at='2017-09-28 16:45:00' WHERE id=2 AND created_at='2017-09-26 14:20:00'");
        mysqli_query($conn, "UPDATE lab113_entries SET created_at='2017-09-26 14:20:00' WHERE id=4 AND created_at='2017-09-28 16:45:00'");
        mysqli_query($conn, "UPDATE lab113_entries SET created_at='2017-09-25 10:30:00' WHERE id=5 AND created_at='2017-09-29 11:00:00'");
    }
}
initializeLab113Database($conn);

// --- Shortcode attribute parser ---
function parseShortcodeAttrs($str) {
    $attrs = [];
    preg_match_all('/(\w+)=["\']?([^"\'>\s\]]*)["\']?/', $str, $m, PREG_SET_ORDER);
    foreach ($m as $match) {
        $attrs[$match[1]] = $match[2];
    }
    return $attrs;
}

// --- Process [display-frm-data] shortcode ---
function processShortcode($conn, $tag, $attrStr) {
    if ($tag !== 'display-frm-data') {
        return '<em>[Unknown shortcode: ' . htmlspecialchars($tag) . ']</em>';
    }
    $attrs   = parseShortcodeAttrs($attrStr);
    $formId  = (int)($attrs['id']    ?? 1);
    $limit   = max(1, min(20, (int)($attrs['limit'] ?? 5)));
    $order   = $attrs['order']       ?? 'id';
    // order_by is safe (we don't use it — just for display)

    // ====================================================================
    // ⚠ VULNERABLE QUERY — the 'order' shortcode attribute is injected
    // directly into the SQL ORDER BY clause with NO sanitization.
    //
    // The ORDER BY clause doesn't return data directly — instead you
    // observe WHICH ENTRY appears first to extract one bit per request.
    //
    // Confirm injection:
    //   order=zzz
    //   → SQL error (invalid ORDER BY column name)
    //
    // Boolean-blind extraction via sort order:
    //   order=IF(1=1,id,created_at)         → sorted by id       (condition TRUE)
    //   order=IF(1=2,id,created_at)         → sorted by created_at (condition FALSE)
    //   order=IF(1=1,id,created_at) DESC    → reverse of above
    //
    //   order=IF(MID(secret_data,1,1)='f',id,created_at) from lab113_secret#
    //   → if first char of secret = 'f', sort by id; else by created_at
    //   → compare which entry is first to determine truth of condition
    //
    // Full shortcode in after_html:
    //   [display-frm-data id=1 order_by=id limit=3 order=IF(1=1,id,created_at)]
    // ====================================================================
    $sql    = "SELECT id, name, value, created_at FROM lab113_entries WHERE form_id = $formId ORDER BY $order LIMIT $limit";
    $result = mysqli_query($conn, $sql);

    if ($result === false) {
        $err = mysqli_error($conn);
        return '<div class="sc-error"><span class="sc-error-label">WordPress DB Error:</span> '
             . htmlspecialchars($err)
             . '<br><code>Query: ' . htmlspecialchars($sql) . '</code></div>';
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }

    if (empty($rows)) {
        return '<p class="sc-empty">No entries found for form_id=' . $formId . '</p>';
    }

    $out  = '<div class="sc-table-wrap">';
    $out .= '<div class="sc-table-meta">form_id=' . $formId
          . ' | order=' . htmlspecialchars($order)
          . ' | limit=' . $limit . '</div>';
    $out .= '<table class="sc-table">';
    $out .= '<thead><tr><th>#</th><th>ID</th><th>Name</th><th>Entry Data</th><th>Submitted</th></tr></thead><tbody>';
    foreach ($rows as $i => $r) {
        $out .= '<tr class="' . ($i % 2 == 0 ? 'sc-row-even' : 'sc-row-odd') . '">';
        $out .= '<td class="sc-num">' . ($i + 1) . '</td>';
        $out .= '<td class="sc-id">'  . htmlspecialchars($r['id']) . '</td>';
        $out .= '<td class="sc-name">' . htmlspecialchars($r['name']) . '</td>';
        $out .= '<td class="sc-val">'  . htmlspecialchars($r['value']) . '</td>';
        $out .= '<td class="sc-date">' . htmlspecialchars($r['created_at']) . '</td>';
        $out .= '</tr>';
    }
    $out .= '</tbody></table></div>';
    return $out;
}

// --- Render after_html / before_html — process shortcodes ---
function renderHtml($conn, $html) {
    // Replace [shortcode-name attr=val ...] with processed output
    return preg_replace_callback(
        '/\[([a-z][a-z0-9_-]*)([^\]]*)\]/',
        function($m) use ($conn) {
            return processShortcode($conn, $m[1], $m[2]);
        },
        $html
    );
}

// --- Handle POST (simulates wp-admin/admin-ajax.php) ---
// Real admin-ajax.php returns an HTML/JSON fragment, never a full page.
// Students intercept this POST in Burp Suite and modify the order= attribute.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $rawAction     = $_POST['action']      ?? '';
    $rawAfterHtml  = $_POST['after_html']  ?? '';
    $rawBeforeHtml = $_POST['before_html'] ?? '';

    if ($rawAction === 'frm_forms_preview') {
        header('Content-Type: text/html; charset=utf-8');
        $out = '';
        if ($rawBeforeHtml) {
            $out .= '<div class="preview-before">' . renderHtml($conn, $rawBeforeHtml) . '</div>';
        }
        $out .= <<<FORMHTML
<div class="frm-form-wrap">
  <h3 class="frm-form-title">Driver Partner Registration</h3>
  <p class="frm-form-desc">Fill in your details to register as a Grab driver partner.</p>
  <div class="frm-fields">
    <div class="frm-field"><label>Full Name</label><input type="text" placeholder="e.g. Ahmad Yusof" disabled></div>
    <div class="frm-field"><label>Email Address</label><input type="email" placeholder="e.g. ahmad@gmail.com" disabled></div>
    <div class="frm-field"><label>Phone Number</label><input type="tel" placeholder="+60XX-XXXXXXXX" disabled></div>
    <div class="frm-field"><label>Vehicle Plate</label><input type="text" placeholder="e.g. WXX 1234" disabled></div>
    <div class="frm-field"><label>Vehicle Type</label>
      <select disabled><option>Toyota Vios</option><option>Honda City</option><option>Perodua Myvi</option></select>
    </div>
    <div class="frm-field frm-submit"><button type="button" disabled class="frm-btn">Submit Application</button></div>
  </div>
</div>
FORMHTML;
        if ($rawAfterHtml) {
            $out .= '<div class="preview-after">' . renderHtml($conn, $rawAfterHtml) . '</div>';
        }
        echo $out;
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>drivegrab.com — WordPress admin-ajax.php</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f1f1f1;color:#23282d;font-size:13px;min-height:100vh;display:flex;flex-direction:column;}

/* ── WordPress Admin Bar ─────────────────────────────────────────────────────── */
.wp-adminbar{background:#23282d;height:32px;display:flex;align-items:center;width:100%;position:sticky;top:0;z-index:200;}
.wp-adminbar-inner{display:flex;align-items:center;width:100%;padding:0;}
.wp-logo{width:32px;height:32px;background:#23282d;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.wp-logo svg{width:18px;height:18px;fill:#a0a5aa;}
.wp-logo:hover svg{fill:#00a0d2;}
.wp-site-name{color:#a0a5aa;font-size:.75rem;padding:0 10px;cursor:pointer;line-height:32px;}
.wp-site-name:hover{color:#fff;}
.wp-adminbar-right{margin-left:auto;display:flex;align-items:center;padding-right:8px;gap:0;}
.wp-adminbar-item{color:#a0a5aa;font-size:.72rem;padding:0 10px;line-height:32px;cursor:pointer;white-space:nowrap;}
.wp-adminbar-item:hover{color:#fff;}
.wp-adminbar-sep{width:1px;height:18px;background:#3c444c;}

/* ── Site Header ─────────────────────────────────────────────────────────────── */
.site-header{background:#00b14f;padding:0;border-bottom:3px solid #009140;}
.site-header-inner{max-width:1200px;margin:0 auto;padding:0 20px;display:flex;align-items:center;height:54px;gap:16px;}
.site-logo{display:flex;align-items:center;gap:8px;text-decoration:none;}
.site-logo-grab{font-size:1.4rem;font-weight:900;color:#fff;letter-spacing:-.05em;}
.site-logo-drive{font-size:1.4rem;font-weight:400;color:rgba(255,255,255,.8);letter-spacing:-.03em;}
.site-logo-tag{font-size:.6rem;background:rgba(0,0,0,.2);color:rgba(255,255,255,.9);padding:2px 6px;border-radius:2px;margin-left:4px;font-weight:600;letter-spacing:.05em;}
.site-nav{display:flex;gap:0;margin-left:auto;}
.site-nav a{color:rgba(255,255,255,.8);font-size:.78rem;font-weight:500;text-decoration:none;padding:0 13px;line-height:54px;display:block;border-bottom:3px solid transparent;transition:all .15s;margin-bottom:-3px;}
.site-nav a:hover{color:#fff;border-bottom-color:rgba(255,255,255,.5);}

/* ── WP Admin Content ───────────────────────────────────────────────────────── */
.wp-content{background:#f1f1f1;flex:1;padding:20px 20px 32px;}
.wp-wrap{max-width:960px;margin:0 auto;}
.wp-page-header{margin-bottom:12px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.wp-page-title{font-size:1.3rem;font-weight:400;color:#23282d;line-height:1.4;}
.wp-page-title strong{font-weight:600;}
.wp-nav-tab-wrapper{border-bottom:1px solid #ccc;margin-bottom:16px;}
.wp-nav-tab{display:inline-block;font-size:.78rem;padding:5px 12px;text-decoration:none;color:#555;border:1px solid transparent;border-bottom:none;position:relative;top:1px;cursor:pointer;margin-right:2px;}
.wp-nav-tab-active{background:#f1f1f1;border-color:#ccc;border-bottom-color:#f1f1f1;color:#23282d;font-weight:600;}

/* ── Panel card ──────────────────────────────────────────────────────────────── */
.panel{background:#fff;border:1px solid #ccd0d4;border-radius:3px;margin-bottom:16px;}
.panel-header{background:#f6f7f7;border-bottom:1px solid #ccd0d4;padding:10px 16px;display:flex;align-items:center;justify-content:space-between;}
.panel-title{font-size:.82rem;font-weight:700;color:#23282d;display:flex;align-items:center;gap:6px;}
.panel-title svg{width:14px;height:14px;flex-shrink:0;opacity:.6;}
.panel-badge{font-size:.65rem;font-family:monospace;background:#23282d;color:#a0a5aa;padding:2px 7px;border-radius:2px;}
.panel-badge.green{background:#00b14f;color:#fff;}
.panel-body{padding:16px;}
.btn-preview{background:#0073aa;color:#fff;border:none;border-radius:2px;padding:5px 14px;font-size:.75rem;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;}
.btn-preview:hover{background:#005a87;}
.btn-preview:disabled{opacity:.6;cursor:not-allowed;}

/* ── Preview output ──────────────────────────────────────────────────────────── */
.preview-wrap{min-height:200px;}
.preview-empty{padding:32px;text-align:center;color:#aaa;font-size:.82rem;}
.preview-empty svg{width:28px;height:28px;margin:0 auto 8px;display:block;opacity:.3;}
.preview-before,.preview-after{font-family:'SFMono-Regular',Consolas,monospace;font-size:.75rem;color:#555;padding:4px 8px;background:#fffff0;border-left:3px solid #ddd;margin:8px 0;}
.preview-before:empty,.preview-after:empty{display:none;}

/* ── Static form ─────────────────────────────────────────────────────────────── */
.frm-form-wrap{border:1px solid #e5e5e5;border-radius:3px;padding:14px;margin:10px 0;background:#fafafa;}
.frm-form-title{font-size:.88rem;font-weight:700;color:#23282d;margin-bottom:3px;}
.frm-form-desc{font-size:.72rem;color:#666;margin-bottom:12px;}
.frm-fields{display:grid;grid-template-columns:1fr 1fr;gap:8px;}
.frm-field label{display:block;font-size:.68rem;font-weight:700;color:#555;margin-bottom:3px;}
.frm-field input,.frm-field select{width:100%;padding:5px 8px;border:1px solid #ddd;border-radius:2px;font-size:.75rem;background:#f9f9f9;color:#666;}
.frm-submit{grid-column:1/-1;}
.frm-btn{background:#00b14f;color:#fff;border:none;border-radius:2px;padding:7px 16px;font-size:.78rem;font-weight:700;cursor:not-allowed;opacity:.7;}

/* ── Shortcode output ────────────────────────────────────────────────────────── */
.sc-table-wrap{margin:8px 0;}
.sc-table-meta{font-size:.65rem;font-family:monospace;color:#888;margin-bottom:5px;background:#f9f9f9;padding:3px 7px;border-left:2px solid #00b14f;}
.sc-table{width:100%;border-collapse:collapse;font-size:.72rem;}
.sc-table th{padding:5px 8px;text-align:left;background:#23282d;color:#e0e0e0;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;}
.sc-table td{padding:5px 8px;border-bottom:1px solid #f0f0f0;vertical-align:top;}
.sc-row-even td{background:#fff;}
.sc-row-odd td{background:#f9f9f9;}
.sc-num{color:#aaa;font-family:monospace;width:24px;}
.sc-id{font-family:monospace;color:#0073aa;width:28px;}
.sc-name{font-weight:600;color:#23282d;white-space:nowrap;}
.sc-val{color:#555;max-width:260px;word-break:break-word;font-size:.68rem;}
.sc-date{color:#888;white-space:nowrap;font-family:monospace;font-size:.65rem;}
.sc-error{background:#ffeaea;border:1px solid #f5a;border-radius:2px;padding:8px 10px;font-size:.72rem;font-family:monospace;color:#c00;margin:6px 0;line-height:1.6;}
.sc-error-label{font-weight:700;display:block;margin-bottom:4px;}
.sc-empty{color:#aaa;font-size:.78rem;font-style:italic;padding:8px 0;}

/* ── Footer ──────────────────────────────────────────────────────────────────── */
.footer{background:#23282d;color:rgba(255,255,255,.5);padding:14px 20px;margin-top:auto;font-size:.7rem;}
.footer-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
.footer a{color:rgba(255,255,255,.5);text-decoration:none;}.footer a:hover{color:#00b14f;}
.footer-links{display:flex;gap:14px;}
</style>
</head>
<body>

<!-- WordPress Admin Bar -->
<div class="wp-adminbar">
  <div class="wp-adminbar-inner">
    <div class="wp-logo">
      <svg viewBox="0 0 20 20"><path d="M10 .4C4.698.4.4 4.698.4 10s4.298 9.6 9.6 9.6 9.6-4.298 9.6-9.6S15.302.4 10 .4zm0 18c-4.63 0-8.4-3.77-8.4-8.4S5.37 1.6 10 1.6s8.4 3.77 8.4 8.4-3.77 8.4-8.4 8.4zm-.52-13.46c-.4 0-.65.29-.65.65v.01l1.07 6.19.71.71.71-.71 1.07-6.19v-.01c0-.36-.25-.65-.65-.65H9.48zm.52 9.6a.8.8 0 100 1.6.8.8 0 000-1.6z"/></svg>
    </div>
    <span class="wp-site-name">drivegrab.com</span>
    <div class="wp-adminbar-sep"></div>
    <span class="wp-adminbar-item">Formidable Forms</span>
    <div class="wp-adminbar-right">
      <span class="wp-adminbar-item">Howdy, admin</span>
    </div>
  </div>
</div>

<!-- Site Header -->
<header class="site-header">
  <div class="site-header-inner">
    <a href="index.php" class="site-logo">
      <span class="site-logo-grab">Grab</span>
      <span class="site-logo-drive">Drive</span>
      <span class="site-logo-tag">WP ADMIN</span>
    </a>
    <nav class="site-nav">
      <a href="#">Dashboard</a>
      <a href="#">Forms</a>
      <a href="#">Entries</a>
      <a href="#">Settings</a>
    </nav>
  </div>
</header>

<!-- WP Admin Content -->
<div class="wp-content">
  <div class="wp-wrap">

    <div class="wp-page-header">
      <h1 class="wp-page-title">Formidable <span style="color:#aaa;font-weight:300;margin:0 4px;">|</span> <strong>Forms</strong></h1>
    </div>

    <div class="wp-nav-tab-wrapper">
      <span class="wp-nav-tab">All Forms</span>
      <span class="wp-nav-tab wp-nav-tab-active">Driver Partner Registration</span>
      <span class="wp-nav-tab">Entries</span>
      <span class="wp-nav-tab">Settings</span>
      <span class="wp-nav-tab">Styler</span>
    </div>

    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
          Form Preview &mdash; Formidable Pro
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
          <span class="panel-badge">form_id=1</span>
          <button class="btn-preview" id="btnPreview" onclick="loadPreview()">&#9654; Preview Entries</button>
        </div>
      </div>
      <div class="panel-body">
        <div id="previewArea">
          <div class="preview-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            Send an AJAX preview request to see the form output here.
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<footer class="footer">
  <div class="footer-inner">
    <span>drivegrab.com &nbsp;&middot;&nbsp; WordPress + <strong style="color:rgba(255,255,255,.7);">Formidable Pro</strong> plugin &nbsp;&middot;&nbsp; &copy; <?= date('Y') ?> Grab</span>
    <div class="footer-links">
      <a href="https://hackerone.com/reports/273946" target="_blank">HackerOne #273946</a>
      <a href="#">Privacy</a>
      <a href="#">Terms</a>
    </div>
  </div>
</footer>

<script>
// POST body sent to wp-admin/admin-ajax.php — students intercept this in Burp Suite
// Injection point: the order= attribute inside the [display-frm-data] shortcode
var FRM_AJAX_BODY = 'action=frm_forms_preview&after_html=XXX[display-frm-data id=1 order_by=id limit=3 order=id]YYY';

function loadPreview() {
  var btn = document.getElementById('btnPreview');
  btn.disabled = true;
  btn.textContent = 'Loading…';
  fetch('index.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: FRM_AJAX_BODY
  })
  .then(function(r){ return r.text(); })
  .then(function(html){
    document.getElementById('previewArea').innerHTML = html;
  })
  .catch(function(e){ console.error(e); })
  .finally(function(){
    btn.disabled = false;
    btn.textContent = 'Refresh Preview';
  });
}
</script>

</body>
</html>
