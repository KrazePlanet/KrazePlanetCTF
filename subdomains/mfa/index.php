<?php
// Lab 1311 — CSRF Basics: Disable 2FA Without Token
// Platform: "VaultX" — fictional crypto exchange / Web3 wallet
// Vulnerability: POST /index.php?action=disable2fa has NO CSRF token.
//   Any cross-origin form can silently disable the victim's two-factor authentication,
//   leaving the account protected by password alone.
// Difficulty: Easy (Training) | Pure black-box — no hints in UI

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => $isHttps ? 'None' : 'Lax',
    ]);
    session_start();
}

mysqli_report(MYSQLI_REPORT_OFF);

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'KrazePlanet_DB';

$db = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($db->connect_error) {
    foreach (['localhost', '127.0.0.1', 'krazeplanet'] as $fallback_host) {
        $db = @new mysqli($fallback_host, $db_user, $db_pass, $db_name);
        if (!$db->connect_error) break;
    }
}

if (!$db || $db->connect_error) {
    die('<h3 style="padding:32px;font-family:sans-serif;color:#c00">DB connection error: ' . htmlspecialchars($db ? $db->connect_error : 'Could not connect to database') . '</h3>');
}
$db->set_charset('utf8mb4');

$baseUri = $_SERVER['SCRIPT_NAME'] ?? '/subdomains/mfa/index.php';

$loginUrl    = $baseUri;
$registerUrl = $baseUri . '?action=register';
$dashUrl     = $baseUri . '?action=dashboard';
$securityUrl = $baseUri . '?action=security';
$disable2Url = $baseUri . '?action=disable2fa';
$logoutUrl   = $baseUri . '?logout=1';
$attackUrl   = $baseUri . '?attack=1';

define('VICTIM_EMAIL','victim@vaultx.io');

// ── Tables ────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS mfa_users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(100) NOT NULL UNIQUE,
    email        VARCHAR(255) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    totp_enabled TINYINT(1)   DEFAULT 1,
    csrf_pwnd    TINYINT(1)   DEFAULT 0,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
)");

// ── Seed accounts ─────────────────────────────────────────────────────────────
$check = $db->query("SELECT id FROM mfa_users WHERE email='lucas@vaultx.io'");
if ($check && $check->num_rows === 0) {
    $h1 = password_hash('lucas@123',  PASSWORD_BCRYPT);
    $h2 = password_hash('mia@123',    PASSWORD_BCRYPT);
    $h3 = password_hash('oliver@123', PASSWORD_BCRYPT);
    $db->query("INSERT INTO mfa_users (username, email, password, totp_enabled) VALUES
        ('lucas_storm', 'lucas@vaultx.io',  '$h1', 1),
        ('mia_chen',    'mia@vaultx.io',    '$h2', 1),
        ('oliver_b',    'oliver@vaultx.io',  '$h3', 1)");
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ── Route detection ───────────────────────────────────────────────────────────
$action      = $_GET['action'] ?? '';
$isLogout    = isset($_GET['logout']);
$isAttack    = isset($_GET['attack']);
$isRegister  = ($action === 'register');
$isDashboard = ($action === 'dashboard');
$isSecurity  = ($action === 'security');
$isDisable   = ($action === 'disable2fa');
$error       = '';

// ── Logout ────────────────────────────────────────────────────────────────────
if ($isLogout) {
    session_destroy();
    header('Location: ' . $loginUrl);
    exit;
}

// ── Load session user ─────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['mfa_uid'])) {
    $st = $db->prepare("SELECT * FROM mfa_users WHERE id = ?");
    $st->bind_param('i', $_SESSION['mfa_uid']);
    $st->execute();
    $currentUser = $st->get_result()->fetch_assoc();
    $st->close();
}

// ── POST: Register ────────────────────────────────────────────────────────────
if ($isRegister && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($uname && $email && $pass) {
        $hashed = password_hash($pass, PASSWORD_BCRYPT);
        $st = $db->prepare("INSERT INTO mfa_users (username, email, password, totp_enabled) VALUES (?,?,?,1)");
        $st->bind_param('sss', $uname, $email, $hashed);
        if ($st->execute()) {
            $_SESSION['mfa_uid'] = $db->insert_id;
            header('Location: ' . $dashUrl);
            exit;
        }
        $error = 'Username or email already taken.';
        $st->close();
    } else {
        $error = 'All fields are required.';
    }
}

// ── POST: Login ───────────────────────────────────────────────────────────────
if (!$isRegister && !$isDisable && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $st = $db->prepare("SELECT * FROM mfa_users WHERE email = ?");
        $st->bind_param('s', $email);
        $st->execute();
        $user = $st->get_result()->fetch_assoc();
        $st->close();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['mfa_uid'] = $user['id'];
            header('Location: ' . $dashUrl);
            exit;
        }
        $error = 'Invalid email or password.';
    } else {
        $error = 'Please enter your credentials.';
    }
}

// ── POST: Disable 2FA — VULNERABLE (no CSRF token check) ─────────────────────
if ($isDisable && $_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser) {
    // ⚠ No CSRF token check — that is the vulnerability.
    $uid = (int)$currentUser['id'];
    $st = $db->prepare("UPDATE mfa_users SET totp_enabled=0 WHERE id=?");
    $st->bind_param('i', $uid);
    if ($st->execute()) {
        $st->close();
        header('Location: ' . $securityUrl . '&disabled=1');
        exit;
    }
    $error = 'Failed to disable 2FA. Please try again.';
    $st->close();
}

// ── Redirect logged-in user from login ────────────────────────────────────────
if ($currentUser && !$isDashboard && !$isSecurity && !$isDisable && !$isAttack && !$action) {
    header('Location: ' . $dashUrl);
    exit;
}

// ── Guard: protected pages require login ──────────────────────────────────────
if (!$currentUser && !$isAttack && !$isRegister && $action) {
    header('Location: ' . $loginUrl);
    exit;
}

// ── Reload user after disable ─────────────────────────────────────────────────
if ($isSecurity && $currentUser) {
    $st = $db->prepare("SELECT * FROM mfa_users WHERE id = ?");
    $st->bind_param('i', $currentUser['id']);
    $st->execute();
    $currentUser = $st->get_result()->fetch_assoc();
    $st->close();
}

// ── Static portfolio data ─────────────────────────────────────────────────────
$portfolio = [
    ['coin'=>'BTC', 'name'=>'Bitcoin',  'amount'=>'0.4821',  'usd'=>'28,450.23', 'change'=>'+3.2%', 'up'=>true,  'color'=>'#F59E0B'],
    ['coin'=>'ETH', 'name'=>'Ethereum', 'amount'=>'4.2000',  'usd'=>'14,820.00', 'change'=>'+1.8%', 'up'=>true,  'color'=>'#6366F1'],
    ['coin'=>'SOL', 'name'=>'Solana',   'amount'=>'24.500',  'usd'=>'3,724.50',  'change'=>'-0.9%', 'up'=>false, 'color'=>'#8B5CF6'],
    ['coin'=>'USDT','name'=>'Tether',   'amount'=>'2,340.00','usd'=>'2,340.00',  'change'=>'0.0%',  'up'=>true,  'color'=>'#10B981'],
];
$activity = [
    ['date'=>'May 27','type'=>'Received','detail'=>'0.0500 BTC from external wallet', 'sign'=>'+','color'=>'#10B981'],
    ['date'=>'May 25','type'=>'Sent',    'detail'=>'200.00 USDT to 0x4f...a32c',     'sign'=>'−','color'=>'#EF4444'],
    ['date'=>'May 22','type'=>'Received','detail'=>'1.2000 ETH from Coinbase',        'sign'=>'+','color'=>'#10B981'],
    ['date'=>'May 20','type'=>'Received','detail'=>'500.00 USDT from Binance',        'sign'=>'+','color'=>'#10B981'],
    ['date'=>'May 18','type'=>'Sent',    'detail'=>'0.0200 BTC to 1A3x...9kPm',      'sign'=>'−','color'=>'#EF4444'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>VaultX<?php
    if ($isAttack)    echo ' — Security Alert';
    elseif ($isSecurity)  echo ' — Security';
    elseif ($isDashboard) echo ' — Portfolio';
    elseif ($isRegister)  echo ' — Create Account';
    else                  echo ' — Sign In';
?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;background:#0A0A0F;color:#E2E8F0;min-height:100vh;}

/* ── NAV ───────────────────────────────────────────────────────────────────── */
.vx-nav{background:#111118;border-bottom:1px solid #1E1E2E;height:58px;display:flex;align-items:center;padding:0 24px;gap:8px;position:sticky;top:0;z-index:100;}
.vx-logo{font-size:1.15rem;font-weight:900;color:#F59E0B;display:flex;align-items:center;gap:9px;text-decoration:none;letter-spacing:-.01em;margin-right:12px;}
.vx-logo-icon{width:32px;height:32px;background:linear-gradient(135deg,#D97706,#F59E0B);border-radius:8px;display:flex;align-items:center;justify-content:center;}
.vx-logo-icon svg{width:17px;height:17px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;}
.vx-nav-link{color:#64748B;text-decoration:none;font-size:.83rem;font-weight:500;padding:6px 12px;border-radius:6px;transition:background .15s,color .15s;}
.vx-nav-link:hover{background:#1A1A28;color:#FCD34D;}
.vx-nav-link.active{background:#1A1A28;color:#F59E0B;font-weight:600;}
.vx-nav-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.vx-nav-user{font-size:.8rem;color:#64748B;}
.vx-nav-user span{color:#E2E8F0;font-weight:600;}
.vx-nav-logout{font-size:.78rem;color:#475569;text-decoration:none;padding:4px 10px;border-radius:5px;border:1px solid #1E1E2E;}
.vx-nav-logout:hover{color:#F87171;border-color:#F87171;background:rgba(248,113,113,.07);}

/* ── AUTH PAGES ────────────────────────────────────────────────────────────── */
.vx-auth-bg{min-height:100vh;background:#0A0A0F;display:flex;align-items:center;justify-content:center;padding:24px;}
.vx-auth-card{background:#111118;border-radius:16px;border:1px solid #1E1E2E;width:100%;max-width:400px;padding:36px;}
.vx-auth-logo{text-align:center;margin-bottom:28px;}
.vx-auth-logo-icon{width:56px;height:56px;background:linear-gradient(135deg,#D97706,#F59E0B);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;box-shadow:0 0 24px rgba(245,158,11,.3);}
.vx-auth-logo-icon svg{width:28px;height:28px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;}
.vx-auth-title{font-size:1.15rem;font-weight:800;color:#F8FAFC;text-align:center;margin-bottom:4px;}
.vx-auth-sub{font-size:.82rem;color:#475569;text-align:center;margin-bottom:26px;}
.vx-field{margin-bottom:16px;}
.vx-field label{display:block;font-size:.72rem;font-weight:600;color:#64748B;margin-bottom:6px;text-transform:uppercase;letter-spacing:.06em;}
.vx-field input{width:100%;padding:10px 13px;background:#0A0A0F;border:1px solid #1E1E2E;border-radius:8px;font-size:.875rem;color:#E2E8F0;outline:none;transition:border-color .15s,box-shadow .15s;}
.vx-field input:focus{border-color:#F59E0B;box-shadow:0 0 0 3px rgba(245,158,11,.12);}
.vx-field input::placeholder{color:#334155;}
.vx-field input:disabled{opacity:.5;cursor:not-allowed;}
.vx-btn{background:linear-gradient(135deg,#D97706,#F59E0B);color:#0A0A0F;border:none;padding:11px 20px;border-radius:8px;font-size:.875rem;font-weight:800;cursor:pointer;transition:opacity .15s;letter-spacing:.01em;}
.vx-btn:hover{opacity:.88;}
.vx-btn-full{width:100%;}
.vx-btn-danger{background:linear-gradient(135deg,#DC2626,#EF4444);color:#fff;}
.vx-btn-danger:hover{opacity:.88;}
.vx-auth-footer{text-align:center;margin-top:20px;font-size:.8rem;color:#475569;}
.vx-auth-footer a{color:#F59E0B;text-decoration:none;font-weight:500;}
.vx-error{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:#FCA5A5;padding:10px 14px;border-radius:8px;font-size:.8rem;margin-bottom:14px;}

/* ── DASHBOARD ─────────────────────────────────────────────────────────────── */
.vx-wrap{max-width:900px;margin:28px auto;padding:0 16px;}
.vx-page-title{font-size:1.05rem;font-weight:800;color:#F8FAFC;margin-bottom:20px;letter-spacing:-.01em;}
.vx-total-card{background:linear-gradient(135deg,#1a1208,#111118);border:1px solid #2a2010;border-radius:14px;padding:24px;margin-bottom:18px;position:relative;overflow:hidden;}
.vx-total-card::after{content:'';position:absolute;top:-20px;right:-20px;width:120px;height:120px;border-radius:50%;background:radial-gradient(circle,rgba(245,158,11,.08),transparent);}
.vx-total-lbl{font-size:.72rem;font-weight:600;color:#92400E;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;}
.vx-total-val{font-size:2.2rem;font-weight:900;color:#F8FAFC;letter-spacing:-.03em;margin-bottom:4px;}
.vx-total-sub{font-size:.8rem;color:#92400E;display:flex;align-items:center;gap:6px;}
.vx-total-chip{background:rgba(16,185,129,.15);color:#34D399;font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:12px;}
.vx-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;}
.vx-coin-card{background:#111118;border:1px solid #1E1E2E;border-radius:12px;padding:16px;}
.vx-coin-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
.vx-coin-name{display:flex;align-items:center;gap:8px;}
.vx-coin-dot{width:10px;height:10px;border-radius:50%;}
.vx-coin-symbol{font-size:.78rem;font-weight:700;color:#94A3B8;}
.vx-coin-change-pos{font-size:.72rem;font-weight:600;color:#10B981;background:rgba(16,185,129,.1);padding:2px 7px;border-radius:10px;}
.vx-coin-change-neg{font-size:.72rem;font-weight:600;color:#EF4444;background:rgba(239,68,68,.1);padding:2px 7px;border-radius:10px;}
.vx-coin-amount{font-size:1rem;font-weight:800;color:#F8FAFC;margin-bottom:2px;}
.vx-coin-usd{font-size:.78rem;color:#64748B;}
.vx-card{background:#111118;border-radius:12px;border:1px solid #1E1E2E;overflow:hidden;margin-bottom:16px;}
.vx-card-hdr{padding:13px 20px;background:#16161F;border-bottom:1px solid #1E1E2E;display:flex;align-items:center;justify-content:space-between;}
.vx-card-hdr-title{font-size:.75rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.06em;}
.vx-activity-row{padding:12px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #0F0F18;}
.vx-activity-row:last-child{border-bottom:none;}
.vx-activity-left{display:flex;align-items:center;gap:10px;}
.vx-activity-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0;}
.vx-activity-detail{font-size:.8rem;color:#94A3B8;}
.vx-activity-date{font-size:.7rem;color:#475569;margin-top:2px;}
.vx-activity-amt{font-size:.82rem;font-weight:700;}

/* ── SECURITY PAGE ─────────────────────────────────────────────────────────── */
.vx-security-hdr{background:#111118;border:1px solid #1E1E2E;border-radius:12px;padding:18px 20px;margin-bottom:16px;display:flex;align-items:center;gap:12px;}
.vx-security-icon{width:40px;height:40px;background:rgba(245,158,11,.1);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}
.vx-security-title{font-size:.95rem;font-weight:700;color:#F8FAFC;margin-bottom:3px;}
.vx-security-sub{font-size:.78rem;color:#64748B;}
.vx-2fa-card{background:#111118;border-radius:12px;overflow:hidden;margin-bottom:16px;}
.vx-2fa-hdr{padding:13px 20px;background:#16161F;border-bottom:1px solid #1E1E2E;font-size:.75rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.06em;}
.vx-2fa-body{padding:20px;}
.vx-2fa-status{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:#0A0A0F;border:1px solid #1E1E2E;border-radius:8px;margin-bottom:16px;}
.vx-2fa-status-left{display:flex;align-items:center;gap:10px;}
.vx-2fa-status-icon{font-size:1.1rem;}
.vx-2fa-status-label{font-size:.875rem;font-weight:600;color:#E2E8F0;}
.vx-2fa-status-sub{font-size:.75rem;color:#64748B;margin-top:2px;}
.vx-2fa-badge-on{background:rgba(16,185,129,.15);color:#34D399;border:1px solid rgba(16,185,129,.2);font-size:.72rem;font-weight:700;padding:4px 10px;border-radius:20px;}
.vx-2fa-badge-off{background:rgba(239,68,68,.1);color:#F87171;border:1px solid rgba(239,68,68,.2);font-size:.72rem;font-weight:700;padding:4px 10px;border-radius:20px;}
.vx-2fa-desc{font-size:.8rem;color:#64748B;line-height:1.6;margin-bottom:16px;}

.vx-flag-title{font-size:.75rem;font-weight:700;color:#F87171;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;}
.vx-flag-label{font-size:.78rem;color:#64748B;margin-bottom:6px;}
.vx-flag-val{font-family:'Courier New',Courier,monospace;font-size:.9rem;font-weight:700;color:#F8FAFC;background:#0A0A0F;border:1px solid #1E1E2E;padding:10px 14px;border-radius:6px;word-break:break-all;}
.vx-other-card{background:#111118;border-radius:12px;border:1px solid #1E1E2E;overflow:hidden;}
.vx-other-hdr{padding:13px 20px;background:#16161F;border-bottom:1px solid #1E1E2E;font-size:.75rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.06em;}
.vx-other-row{padding:14px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #0F0F18;}
.vx-other-row:last-child{border-bottom:none;}
.vx-other-label{font-size:.85rem;font-weight:500;color:#94A3B8;}
.vx-other-val{font-size:.78rem;color:#64748B;}
.vx-status-dot-on{display:inline-flex;align-items:center;gap:5px;font-size:.78rem;font-weight:600;color:#34D399;}
.vx-status-dot-off{display:inline-flex;align-items:center;gap:5px;font-size:.78rem;font-weight:600;color:#F87171;}

/* ── ATTACK PAGE ───────────────────────────────────────────────────────────── */
.vx-atk-bg{min-height:100vh;background:#0A0A0F;display:flex;align-items:center;justify-content:center;padding:24px;}
.vx-atk-email{background:#111118;border-radius:14px;border:1px solid #1E1E2E;width:100%;max-width:480px;overflow:hidden;box-shadow:0 0 40px rgba(239,68,68,.08);}
.vx-atk-hdr{background:linear-gradient(135deg,#7F1D1D,#991B1B);padding:20px 24px;display:flex;align-items:flex-start;gap:12px;}
.vx-atk-hdr-icon{width:36px;height:36px;background:rgba(255,255,255,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;margin-top:2px;}
.vx-atk-hdr-content{flex:1;}
.vx-atk-brand{font-size:.72rem;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px;}
.vx-atk-hdr-title{font-size:1.05rem;font-weight:800;color:#fff;line-height:1.25;}
.vx-atk-body{padding:26px 24px;}
.vx-atk-alert-box{background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.15);border-radius:8px;padding:14px 16px;margin-bottom:20px;}
.vx-atk-alert-row{display:flex;justify-content:space-between;font-size:.8rem;padding:3px 0;}
.vx-atk-alert-lbl{color:#94A3B8;}
.vx-atk-alert-val{font-weight:600;color:#F8FAFC;}
.vx-atk-alert-val.danger{color:#F87171;}
.vx-atk-text{font-size:.875rem;color:#64748B;line-height:1.65;margin-bottom:20px;}
.vx-atk-btn{background:linear-gradient(135deg,#D97706,#F59E0B);color:#0A0A0F;border:none;width:100%;padding:13px;border-radius:8px;font-size:.95rem;font-weight:800;cursor:pointer;transition:opacity .15s;letter-spacing:.01em;}
.vx-atk-btn:hover{opacity:.88;}
.vx-atk-btn:disabled{opacity:.5;cursor:not-allowed;}
.vx-atk-footer{padding:14px 24px;border-top:1px solid #1A1A28;font-size:.68rem;color:#334155;text-align:center;line-height:1.6;}
</style>
</head>
<body>

<?php if ($isAttack): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     ATTACK PAGE — Fake VaultX withdrawal alert email
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="vx-atk-bg">

  <!-- ⚠ CSRF form: POSTs to /index.php?action=disable2fa — no CSRF token.
       Victim's 2FA is silently disabled, leaving account password-only. -->
  <form id="csrfForm"
        action="/index.php?action=disable2fa"
        method="POST"
        style="display:none;">
  </form>

  <div class="vx-atk-email">
    <div class="vx-atk-hdr">
      <div class="vx-atk-hdr-icon">🚨</div>
      <div class="vx-atk-hdr-content">
        <div class="vx-atk-brand">VaultX Security Alert</div>
        <div class="vx-atk-hdr-title">Suspicious Withdrawal Attempt Blocked</div>
      </div>
    </div>
    <div class="vx-atk-body">
      <div class="vx-atk-alert-box">
        <div class="vx-atk-alert-row">
          <span class="vx-atk-alert-lbl">Amount</span>
          <span class="vx-atk-alert-val danger">$4,800.00 USDT</span>
        </div>
        <div class="vx-atk-alert-row">
          <span class="vx-atk-alert-lbl">Destination</span>
          <span class="vx-atk-alert-val danger">TRx9k...4mQp (Unknown)</span>
        </div>
        <div class="vx-atk-alert-row">
          <span class="vx-atk-alert-lbl">Device</span>
          <span class="vx-atk-alert-val">Unknown — Linux / Firefox</span>
        </div>
        <div class="vx-atk-alert-row">
          <span class="vx-atk-alert-lbl">Location</span>
          <span class="vx-atk-alert-val">Saint Petersburg, Russia</span>
        </div>
        <div class="vx-atk-alert-row">
          <span class="vx-atk-alert-lbl">Time</span>
          <span class="vx-atk-alert-val">Today, 03:42 AM UTC</span>
        </div>
        <div class="vx-atk-alert-row">
          <span class="vx-atk-alert-lbl">Status</span>
          <span class="vx-atk-alert-val danger">BLOCKED — Action Required</span>
        </div>
      </div>
      <div class="vx-atk-text">
        We detected and blocked a suspicious withdrawal from your VaultX wallet.
        To protect your funds, please re-verify your account security settings
        immediately. Failure to verify within 24 hours may result in temporary
        account suspension.
      </div>
      <button class="vx-atk-btn" id="secureBtn" onclick="fireCSRF()">
        🔒 Secure My Account →
      </button>
    </div>
    <div class="vx-atk-footer">
      This is an automated security notice from VaultX.<br>
      VaultX · <a href="#" style="color:#334155;">Unsubscribe</a> · <a href="#" style="color:#334155;">Privacy Policy</a> · <a href="#" style="color:#334155;">Security Center</a>
    </div>
  </div>
</div>
<script>
function fireCSRF() {
    var btn = document.getElementById('secureBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Verifying account…';
    document.getElementById('csrfForm').submit();
}
setTimeout(function() { document.getElementById('csrfForm').submit(); }, 1500);
</script>

<?php elseif ($isSecurity && $currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     SECURITY SETTINGS PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<nav class="vx-nav">
  <a href="/index.php?action=dashboard" class="vx-logo">
    <div class="vx-logo-icon"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
    VaultX
  </a>
  <a href="/index.php?action=dashboard" class="vx-nav-link">Portfolio</a>
  <a href="/index.php?action=security"  class="vx-nav-link active">Security</a>
  <div class="vx-nav-right">
    <span class="vx-nav-user">Signed in as <span><?= esc($currentUser['username']) ?></span></span>
    <a href="/index.php?logout=1" class="vx-nav-logout">Sign Out</a>
  </div>
</nav>

<div class="vx-wrap" style="max-width:620px;">
  <div class="vx-security-hdr">
    <div class="vx-security-icon">🔐</div>
    <div>
      <div class="vx-security-title">Account Security</div>
      <div class="vx-security-sub">Manage your authentication and security preferences</div>
    </div>
  </div>

  <?php if (isset($_GET['disabled'])): ?>
  <div id="successMsg" style="background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:12px 16px;font-size:.875rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;color:#FCA5A5;transition:opacity .4s ease;">
    <svg style="width:18px;height:18px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="#F87171" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
    Two-factor authentication has been disabled for your account.
  </div>
  <script>setTimeout(function(){var m=document.getElementById('successMsg');m.style.opacity='0';setTimeout(function(){m.remove()},400)},3000);</script>
  <?php endif; ?>

  <div class="vx-2fa-card">
    <div class="vx-2fa-hdr">Two-Factor Authentication (TOTP)</div>
    <div class="vx-2fa-body">
      <div class="vx-2fa-status">
        <div class="vx-2fa-status-left">
          <span class="vx-2fa-status-icon"><?= $currentUser['totp_enabled'] ? '🔒' : '🔓' ?></span>
          <div>
            <div class="vx-2fa-status-label">Authenticator App</div>
            <div class="vx-2fa-status-sub">Time-based one-time passwords (TOTP)</div>
          </div>
        </div>
        <?php if ($currentUser['totp_enabled']): ?>
          <span class="vx-2fa-badge-on">Enabled</span>
        <?php else: ?>
          <span class="vx-2fa-badge-off">Disabled</span>
        <?php endif; ?>
      </div>
      <div class="vx-2fa-desc">
        Two-factor authentication adds an extra layer of security to your account.
        When enabled, you'll need both your password and a one-time code from your
        authenticator app to sign in or authorize withdrawals.
      </div>
      <?php if ($currentUser['totp_enabled']): ?>
      <!-- ⚠ VULNERABLE: no csrf_token hidden field -->
      <form method="POST" action="/index.php?action=disable2fa">
        <button type="submit" class="vx-btn vx-btn-danger" style="padding:9px 18px;font-size:.82rem;">
          Disable Two-Factor Authentication
        </button>
      </form>
      <?php else: ?>
      <button class="vx-btn" style="padding:9px 18px;font-size:.82rem;opacity:.6;cursor:not-allowed;" disabled>
        Enable Two-Factor Authentication
      </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="vx-other-card">
    <div class="vx-other-hdr">Other Security Settings</div>
    <div class="vx-other-row">
      <span class="vx-other-label">Email Notifications</span>
      <span class="vx-status-dot-on"><span style="width:6px;height:6px;border-radius:50%;background:#34D399;display:inline-block;"></span>Active</span>
    </div>
    <div class="vx-other-row">
      <span class="vx-other-label">Withdrawal Whitelist</span>
      <span class="vx-status-dot-on"><span style="width:6px;height:6px;border-radius:50%;background:#34D399;display:inline-block;"></span>3 addresses</span>
    </div>
    <div class="vx-other-row">
      <span class="vx-other-label">Login History</span>
      <span class="vx-other-val">Last seen: Today, 07:14 AM</span>
    </div>
    <div class="vx-other-row">
      <span class="vx-other-label">API Keys</span>
      <span class="vx-status-dot-off"><span style="width:6px;height:6px;border-radius:50%;background:#F87171;display:inline-block;"></span>None active</span>
    </div>
  </div>
</div>

<?php elseif ($isDashboard && $currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     DASHBOARD / PORTFOLIO
     ══════════════════════════════════════════════════════════════════════════ -->
<nav class="vx-nav">
  <a href="/index.php?action=dashboard" class="vx-logo">
    <div class="vx-logo-icon"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
    VaultX
  </a>
  <a href="/index.php?action=dashboard" class="vx-nav-link active">Portfolio</a>
  <a href="/index.php?action=security"  class="vx-nav-link">Security</a>
  <div class="vx-nav-right">
    <span class="vx-nav-user">Signed in as <span><?= esc($currentUser['username']) ?></span></span>
    <a href="/index.php?logout=1" class="vx-nav-logout">Sign Out</a>
  </div>
</nav>

<div class="vx-wrap">
  <div class="vx-total-card">
    <div class="vx-total-lbl">Total Portfolio Value</div>
    <div class="vx-total-val">$49,334.73</div>
    <div class="vx-total-sub">
      <span class="vx-total-chip">+2.4% today</span>
      Updated just now
    </div>
  </div>

  <div class="vx-grid">
    <?php foreach ($portfolio as $c): ?>
    <div class="vx-coin-card">
      <div class="vx-coin-top">
        <div class="vx-coin-name">
          <div class="vx-coin-dot" style="background:<?= $c['color'] ?>;"></div>
          <span class="vx-coin-symbol"><?= esc($c['coin']) ?> · <?= esc($c['name']) ?></span>
        </div>
        <?php if ($c['up']): ?>
          <span class="vx-coin-change-pos"><?= esc($c['change']) ?></span>
        <?php else: ?>
          <span class="vx-coin-change-neg"><?= esc($c['change']) ?></span>
        <?php endif; ?>
      </div>
      <div class="vx-coin-amount"><?= esc($c['amount']) ?> <?= esc($c['coin']) ?></div>
      <div class="vx-coin-usd">≈ $<?= esc($c['usd']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="vx-card">
    <div class="vx-card-hdr">
      <span class="vx-card-hdr-title">Recent Activity</span>
      <span style="font-size:.72rem;color:#334155;">Last 30 days</span>
    </div>
    <?php foreach ($activity as $a): ?>
    <div class="vx-activity-row">
      <div class="vx-activity-left">
        <div class="vx-activity-icon" style="background:<?= $a['up'] ?? ($a['sign']==='+') ?>rgba(<?= $a['sign']==='+' ? '16,185,129' : '239,68,68' ?>,.1);">
          <?= $a['sign'] === '+' ? '↓' : '↑' ?>
        </div>
        <div>
          <div class="vx-activity-detail"><?= esc($a['detail']) ?></div>
          <div class="vx-activity-date"><?= esc($a['date']) ?></div>
        </div>
      </div>
      <span class="vx-activity-amt" style="color:<?= $a['color'] ?>;"><?= esc($a['sign']) ?><?= explode(' ', $a['detail'])[0] ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php elseif ($isRegister): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     REGISTER PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="vx-auth-bg">
  <div class="vx-auth-card">
    <div class="vx-auth-logo">
      <div class="vx-auth-logo-icon"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
    </div>
    <div class="vx-auth-title">Create Your Wallet</div>
    <div class="vx-auth-sub">Start trading on VaultX — secure, fast, decentralized</div>

    <?php if ($error): ?><div class="vx-error"><?= esc($error) ?></div><?php endif; ?>

    <form method="POST" action="/index.php?action=register">
      <div class="vx-field">
        <label>Username</label>
        <input type="text" name="username" placeholder="your_handle" required autocomplete="username">
      </div>
      <div class="vx-field">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com" required autocomplete="email">
      </div>
      <div class="vx-field" style="margin-bottom:22px;">
        <label>Password</label>
        <input type="password" name="password" placeholder="Create a strong password" required autocomplete="new-password">
      </div>
      <button type="submit" class="vx-btn vx-btn-full">Create Account</button>
    </form>

    <div class="vx-auth-footer">
      Already have an account? <a href="/index.php">Sign in</a>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     LOGIN PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="vx-auth-bg">
  <div class="vx-auth-card">
    <div class="vx-auth-logo">
      <div class="vx-auth-logo-icon"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
    </div>
    <div class="vx-auth-title">Sign in to VaultX</div>
    <div class="vx-auth-sub">Access your crypto portfolio</div>

    <?php if ($error): ?><div class="vx-error"><?= esc($error) ?></div><?php endif; ?>

    <form method="POST" action="/index.php">
      <div class="vx-field">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com" required autocomplete="email">
      </div>
      <div class="vx-field" style="margin-bottom:22px;">
        <label>Password</label>
        <input type="password" name="password" placeholder="Your password" required autocomplete="current-password">
      </div>
      <button type="submit" class="vx-btn vx-btn-full">Sign In</button>
    </form>

    <div style="background:#111118;border:1px solid #1E1E2E;border-radius:8px;padding:10px 12px;margin-top:14px;font-size:11px;">
      <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748B;margin-bottom:8px;">📋 Test Accounts</div>
      <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #1A1A28;"><span style="color:#94A3B8;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">lucas@vaultx.io</span><span style="font-family:monospace;font-weight:700;color:#E2E8F0;font-size:11px;white-space:nowrap;">lucas@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#1A1208;color:#F59E0B;white-space:nowrap;">User</span></div>
      <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #1A1A28;"><span style="color:#94A3B8;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">mia@vaultx.io</span><span style="font-family:monospace;font-weight:700;color:#E2E8F0;font-size:11px;white-space:nowrap;">mia@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#1A1208;color:#F59E0B;white-space:nowrap;">User</span></div>
      <div style="display:flex;align-items:center;gap:7px;padding:3px 0;"><span style="color:#94A3B8;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">oliver@vaultx.io</span><span style="font-family:monospace;font-weight:700;color:#E2E8F0;font-size:11px;white-space:nowrap;">oliver@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#1A1208;color:#F59E0B;white-space:nowrap;">User</span></div>
    </div>

    <div class="vx-auth-footer">
      New to VaultX? <a href="/index.php?action=register">Create an account</a>
    </div>
  </div>
</div>

<?php endif; ?>
</body>
</html>
