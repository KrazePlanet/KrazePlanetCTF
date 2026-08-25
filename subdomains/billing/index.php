<?php
// Lab 701 — IDOR: Insecure Order Invoice Disclosure
// Platform: "SwiftCart" — fictional e-commerce / D2C store
// Vulnerability: GET /index.php?action=order&id=X has NO ownership check.
//   Any authenticated user can view ANY customer's invoice by changing the id.
// Difficulty: Easy (Training) | Pure black-box — no hints in UI

session_start();

define('LAB_FLAG', 'flag{idor_invoice_disclosure_swiftcart_701}');

// ── Database ──────────────────────────────────────────────────────────────────
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$db_hosts = ['krazeplanet', '127.0.0.1', 'localhost', '172.19.0.1', 'host.docker.internal'];
$db = null;
foreach ($db_hosts as $h) {
    $db = @new mysqli($h, 'root', '');
    if (!$db->connect_error) {
        $db->query("CREATE DATABASE IF NOT EXISTS `KrazePlanet` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db->select_db('KrazePlanet');
        break;
    }
}
if (!$db || $db->connect_error) { die('DB connection failed: ' . ($db ? $db->connect_error : 'Unable to connect to database')); }
if ($db->connect_error) { die('DB connection failed'); }

// ── Tables ─────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS lab701_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    csrf_pwnd TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die($db->error);

$db->query("CREATE TABLE IF NOT EXISTS lab701_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_num VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    order_date DATE NOT NULL,
    status ENUM('Delivered','Shipped','Processing') DEFAULT 'Delivered',
    total DECIMAL(10,2) NOT NULL,
    shipping_address TEXT,
    payment_method VARCHAR(60),
    tracking_num VARCHAR(30),
    special_notes TEXT,
    FOREIGN KEY (user_id) REFERENCES lab701_users(id)
)") or die($db->error);

$db->query("CREATE TABLE IF NOT EXISTS lab701_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    product_sku VARCHAR(20) NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES lab701_orders(id)
)") or die($db->error);

// ── Seed accounts + orders ──────────────────────────────────────────────────────
$check = $db->query("SELECT id FROM lab701_users WHERE email='victim@swiftcart.com'");
if ($check && $check->num_rows === 0) {
    $h1 = password_hash('victim123', PASSWORD_BCRYPT);
    $h2 = password_hash('attacker123', PASSWORD_BCRYPT);
    $h3 = password_hash('admin123', PASSWORD_BCRYPT);

    $db->query("INSERT INTO lab701_users (name, email, password) VALUES
        ('Emma Wilson', 'victim@swiftcart.com', '$h1'),
        ('Alex Chen', 'attacker@swiftcart.com', '$h2'),
        ('SwiftCart Admin', 'admin@swiftcart.com', '$h3')") or die($db->error);

    $db->query("INSERT INTO lab701_orders (order_num, user_id, order_date, status, total, shipping_address, payment_method, tracking_num, special_notes) VALUES
        ('SC-1001', 1, '2026-05-10', 'Delivered', 204.98, 'Emma Wilson\n142 Maple Street\nAustin, TX 78701', 'Visa ending in 4242', 'TRK839102347', NULL),
        ('SC-1002', 1, '2026-05-18', 'Delivered', 74.98, 'Emma Wilson\n142 Maple Street\nAustin, TX 78701', 'Visa ending in 4242', 'TRK928471039', NULL),
        ('SC-1003', 1, '2026-05-25', 'Shipped', 89.99, 'Emma Wilson\n142 Maple Street\nAustin, TX 78701', 'Mastercard ending in 8891', 'TRK110293847', NULL),
        ('SC-1004', 2, '2026-05-28', 'Processing', 59.99, 'Alex Chen\n88 Tech Drive\nSan Francisco, CA 94105', 'Amex ending in 3002', 'TRK pending', NULL),
        ('SC-1005', 3, '2026-06-01', 'Delivered', 0.00, 'SwiftCart HQ\nInternal Mailroom\nSeattle, WA 98109', 'Corporate Account', 'INTERNAL', 'flag{idor_invoice_disclosure_swiftcart_701}')") or die($db->error);

    $db->query("INSERT INTO lab701_order_items (order_id, product_name, product_sku, qty, price) VALUES
        (1, 'Wireless Headphones', 'WH-2048', 1, 129.99),
        (1, 'Running Shoes', 'RS-7751', 1, 74.99),
        (2, 'Bluetooth Speaker', 'BS-3310', 1, 49.99),
        (2, 'Insulated Water Bottle', 'WB-9920', 1, 24.99),
        (3, 'Smart Watch Series 5', 'SW-5501', 1, 89.99),
        (4, 'Gaming Mouse Pro', 'GM-8802', 1, 59.99),
        (5, 'Internal Security Audit Package', 'SEC-AUDIT', 1, 0.00)") or die($db->error);
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$action    = $_GET['action'] ?? '';
$isLogout  = isset($_GET['logout']);
$isRegister= ($action === 'register');
$isOrders  = ($action === 'orders');
$isOrder   = ($action === 'order');
$isAccount = ($action === 'account');
$error     = '';

// ── Logout ────────────────────────────────────────────────────────────────────
if ($isLogout) {
    session_destroy();
    header('Location: /index.php');
    exit;
}

// ── POST: Register ────────────────────────────────────────────────────────────
if ($isRegister && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($name && $email && strlen($pass) >= 4) {
        $h = password_hash($pass, PASSWORD_BCRYPT);
        $st = $db->prepare('INSERT INTO lab701_users (name, email, password) VALUES (?,?,?)');
        $st->bind_param('sss', $name, $email, $h);
        $st->execute();
        $newUserId = $db->insert_id;
        $st->close();

        // Seed generic orders so the student sees invoices and discovers the id parameter
        $ship = $db->real_escape_string($name . "\nUnknown Address\nCity, ST 00000");
        $on1 = 'SC-' . (5000 + (int)$newUserId);
        $on2 = 'SC-' . (5001 + (int)$newUserId);
        $tr1 = 'TRK' . $newUserId . '001';
        $tr2 = 'TRK' . $newUserId . '002';
        $db->query("INSERT INTO lab701_orders (order_num, user_id, order_date, status, total, shipping_address, payment_method, tracking_num, special_notes) VALUES
            ('$on1', $newUserId, CURDATE(), 'Delivered', 69.98, '$ship', 'Visa ending in 1111', '$tr1', NULL),
            ('$on2', $newUserId, CURDATE(), 'Shipped', 24.99, '$ship', 'Visa ending in 1111', '$tr2', NULL)") or die($db->error);

        $ord1 = $db->insert_id;
        $ord2 = $ord1 + 1;
        $db->query("INSERT INTO lab701_order_items (order_id, product_name, product_sku, qty, price) VALUES
            ($ord1, 'Wireless Earbuds', 'WE-1001', 1, 49.99),
            ($ord1, 'USB-C Cable 2-Pack', 'UC-2002', 1, 19.99),
            ($ord2, 'Phone Stand', 'PS-3303', 1, 24.99)") or die($db->error);
    }
    header('Location: /index.php');
    exit;
}

// ── POST: Login ─────────────────────────────────────────────────────────────
if (!$isRegister && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $st = $db->prepare('SELECT * FROM lab701_users WHERE email = ?');
        $st->bind_param('s', $email);
        $st->execute();
        $res = $st->get_result();
        $user = $res->fetch_assoc();
        $st->close();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['lab701_user'] = $user['id'];
            header('Location: /index.php?action=orders');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// ── Current user ──────────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['lab701_user'])) {
    $st = $db->prepare('SELECT * FROM lab701_users WHERE id = ?');
    $st->bind_param('i', $_SESSION['lab701_user']);
    $st->execute();
    $res = $st->get_result();
    $currentUser = $res->fetch_assoc();
    $st->close();
}

// Redirect logged-in users from login page
if ($currentUser && !$action && !$isLogout) {
    header('Location: /index.php?action=orders');
    exit;
}

// ── Fetch order detail ──────────────────────────────────────────────────────
// VULNERABLE: no ownership check on user_id — that is the bug.
$orderDetail = null;
$orderItems = [];
if ($isOrder && isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];
    $st = $db->prepare('SELECT o.*, u.name AS customer_name, u.email AS customer_email FROM lab701_orders o JOIN lab701_users u ON o.user_id = u.id WHERE o.id = ?');
    $st->bind_param('i', $orderId);
    $st->execute();
    $res = $st->get_result();
    $orderDetail = $res->fetch_assoc();
    $st->close();

    if ($orderDetail) {
        $st2 = $db->prepare('SELECT * FROM lab701_order_items WHERE order_id = ?');
        $st2->bind_param('i', $orderId);
        $st2->execute();
        $res2 = $st2->get_result();
        while ($row = $res2->fetch_assoc()) $orderItems[] = $row;
        $st2->close();
    }
}

// ── Fetch user's orders ─────────────────────────────────────────────────────
$userOrders = [];
if ($currentUser && $isOrders) {
    $st = $db->prepare('SELECT * FROM lab701_orders WHERE user_id = ? ORDER BY order_date DESC');
    $st->bind_param('i', $currentUser['id']);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) $userOrders[] = $row;
    $st->close();
}

// ── Account data ────────────────────────────────────────────────────────────
$accountData = null;
if ($currentUser && $isAccount) {
    $st = $db->prepare('SELECT * FROM lab701_users WHERE id = ?');
    $st->bind_param('i', $currentUser['id']);
    $st->execute();
    $res = $st->get_result();
    $accountData = $res->fetch_assoc();
    $st->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SwiftCart<?php
    if ($isOrders)  echo ' — My Orders';
    elseif ($isOrder) echo ' — Order Invoice';
    elseif ($isAccount) echo ' — Account';
    elseif ($isRegister) echo ' — Sign Up';
    else echo ' — Sign In';
?></title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:#F8FAFC;color:#1E293B;line-height:1.5;min-height:100vh;}
a{color:#0D9488;text-decoration:none;}a:hover{text-decoration:underline;}

.sc-nav{background:#fff;border-bottom:1px solid #E2E8F0;padding:0 24px;display:flex;align-items:center;height:56px;position:sticky;top:0;z-index:10;}
.sc-nav-logo{font-weight:800;font-size:1.15rem;color:#0F766E;display:flex;align-items:center;gap:6px;}
.sc-nav-logo svg{width:22px;height:22px;fill:#0D9488;}
.sc-nav-links{margin-left:32px;display:flex;gap:24px;flex:1;}
.sc-nav-link{font-size:.88rem;font-weight:500;color:#64748B;padding:18px 0;border-bottom:2px solid transparent;transition:.15s;}
.sc-nav-link:hover{color:#0F766E;text-decoration:none;}
.sc-nav-link.active{color:#0F766E;border-bottom-color:#0D9488;}
.sc-nav-right{margin-left:auto;display:flex;align-items:center;gap:16px;font-size:.85rem;color:#475569;}
.sc-nav-user{font-weight:500;color:#1E293B;}
.sc-nav-out{font-size:.82rem;color:#64748B;}
.sc-nav-out:hover{color:#0F766E;}

.sc-wrap{max-width:920px;margin:0 auto;padding:32px 24px;}
.sc-wrap-narrow{max-width:460px;margin:0 auto;padding:48px 24px;}

.sc-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden;}
.sc-card-pad{padding:28px 32px;}

.sc-auth-logo{text-align:center;margin-bottom:28px;}
.sc-auth-logo svg{width:40px;height:40px;fill:#0D9488;margin-bottom:10px;}
.sc-auth-title{font-size:1.35rem;font-weight:800;color:#0F172A;margin-bottom:4px;}
.sc-auth-sub{font-size:.85rem;color:#64748B;}
.sc-form-label{display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:5px;}
.sc-form-input{width:100%;padding:10px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.9rem;background:#fff;color:#1E293B;transition:.15s;}
.sc-form-input:focus{outline:none;border-color:#0D9488;box-shadow:0 0 0 3px rgba(13,148,136,.1);}
.sc-btn{width:100%;padding:11px 16px;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;transition:.15s;}
.sc-btn-primary{background:#0D9488;color:#fff;}
.sc-btn-primary:hover{background:#0F766E;}
.sc-btn-outline{background:#fff;color:#0D9488;border:1px solid #0D9488;}
.sc-btn-outline:hover{background:#F0FDFA;}
.sc-error{background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:.85rem;margin-bottom:16px;}

.sc-page-hdr{margin-bottom:24px;}
.sc-page-title{font-size:1.4rem;font-weight:700;color:#0F172A;margin-bottom:4px;}
.sc-page-sub{font-size:.85rem;color:#64748B;}

.sc-table{width:100%;border-collapse:collapse;}
.sc-table th{font-size:.75rem;font-weight:600;color:#64748B;text-transform:uppercase;letter-spacing:.04em;padding:12px 16px;text-align:left;border-bottom:1px solid #E2E8F0;background:#F8FAFC;}
.sc-table td{padding:14px 16px;font-size:.88rem;border-bottom:1px solid #F1F5F9;vertical-align:middle;}
.sc-table tr:last-child td{border-bottom:none;}
.sc-table tr:hover td{background:#F8FAFC;}
.sc-ord-num{font-weight:700;color:#0F172A;font-size:.9rem;}
.sc-ord-date{font-size:.8rem;color:#64748B;}
.sc-badge{display:inline-block;font-size:.7rem;font-weight:600;padding:3px 10px;border-radius:20px;}
.sc-badge-delivered{background:#ECFDF5;color:#047857;}
.sc-badge-shipped{background:#EFF6FF;color:#1D4ED8;}
.sc-badge-processing{background:#FEF3C7;color:#B45309;}
.sc-total{font-weight:700;color:#0F172A;font-family:monospace;font-size:.9rem;}
.sc-view-btn{font-size:.8rem;font-weight:600;color:#0D9488;background:#F0FDFA;border:1px solid #99F6E4;padding:5px 12px;border-radius:6px;cursor:pointer;transition:.15s;text-decoration:none;display:inline-block;}
.sc-view-btn:hover{background:#CCFBF1;}

.sc-invoice-hdr{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px;}
.sc-invoice-num{font-size:1.5rem;font-weight:800;color:#0F172A;margin-bottom:4px;}
.sc-invoice-date{font-size:.85rem;color:#64748B;}
.sc-invoice-status{margin-top:4px;}
.sc-invoice-logo{font-weight:800;font-size:1.1rem;color:#0F766E;display:flex;align-items:center;gap:6px;justify-content:flex-end;}
.sc-invoice-logo svg{width:20px;height:20px;fill:#0D9488;}
.sc-invoice-meta{font-size:.8rem;color:#64748B;margin-top:4px;}

.sc-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;}
@media(max-width:640px){.sc-grid{grid-template-columns:1fr;}}
.sc-box{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:18px 20px;}
.sc-box-title{font-size:.72rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;}
.sc-box-text{font-size:.88rem;color:#1E293B;line-height:1.6;white-space:pre-line;}
.sc-box-email{font-size:.8rem;color:#64748B;margin-top:4px;}

.sc-items-table{width:100%;border-collapse:collapse;margin-top:8px;}
.sc-items-table th{font-size:.72rem;font-weight:600;color:#64748B;text-transform:uppercase;letter-spacing:.04em;padding:10px 12px;text-align:left;border-bottom:1px solid #E2E8F0;}
.sc-items-table td{padding:12px;font-size:.85rem;border-bottom:1px solid #F1F5F9;}
.sc-items-table tr:last-child td{border-bottom:none;}
.sc-sku{font-size:.72rem;color:#94A3B8;font-family:monospace;}
.sc-qty{text-align:center;}
.sc-price{text-align:right;font-weight:600;color:#0F172A;font-family:monospace;}

.sc-total-row{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;background:#F0FDFA;border:1px solid #CCFBF1;border-radius:10px;margin-top:16px;}
.sc-total-label{font-size:.85rem;font-weight:600;color:#0F766E;}
.sc-total-val{font-size:1.15rem;font-weight:800;color:#0F172A;font-family:monospace;}

.sc-flag{background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:20px 24px;margin-bottom:24px;}
.sc-flag-title{font-size:.75rem;font-weight:700;color:#DC2626;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;}
.sc-flag-text{font-size:.85rem;color:#7F1D1D;margin-bottom:10px;}
.sc-flag-val{font-family:'Courier New',Courier,monospace;font-size:.95rem;font-weight:700;color:#991B1B;background:#FEF2F2;border:1px dashed #FCA5A5;padding:10px 14px;border-radius:6px;word-break:break-all;}

.sc-account-row{display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid #F1F5F9;}
.sc-account-row:last-child{border-bottom:none;}
.sc-account-label{font-size:.82rem;font-weight:600;color:#64748B;}
.sc-account-val{font-size:.9rem;color:#1E293B;font-weight:500;}

.sc-empty{text-align:center;padding:48px 24px;}
.sc-empty-title{font-size:1.1rem;font-weight:700;color:#0F172A;margin-bottom:8px;}
.sc-empty-text{font-size:.85rem;color:#64748B;}
</style>
</head>
<body>

<?php if (!$currentUser): ?>
<div class="sc-wrap-narrow">
  <div class="sc-auth-logo">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
      <line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
    <div class="sc-auth-title">SwiftCart</div>
    <div class="sc-auth-sub"><?= $isRegister ? 'Create your account' : 'Sign in to your account' ?></div>
  </div>

  <div class="sc-card">
    <div class="sc-card-pad">
      <?php if ($error): ?>
      <div class="sc-error"><?= esc($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="<?= $isRegister ? '/index.php?action=register' : '/index.php' ?>">
        <?php if ($isRegister): ?>
        <div style="margin-bottom:14px;">
          <label class="sc-form-label">Full Name</label>
          <input type="text" name="name" class="sc-form-input" placeholder="Jane Doe" required>
        </div>
        <?php endif; ?>
        <div style="margin-bottom:14px;">
          <label class="sc-form-label">Email address</label>
          <input type="email" name="email" class="sc-form-input" placeholder="you@example.com" required>
        </div>
        <div style="margin-bottom:20px;">
          <label class="sc-form-label">Password</label>
          <input type="password" name="password" class="sc-form-input" placeholder="••••••••" required>
        </div>
        <button type="submit" class="sc-btn sc-btn-primary"><?= $isRegister ? 'Create Account' : 'Sign In' ?></button>
      </form>

      <div style="text-align:center;margin-top:18px;font-size:.85rem;color:#64748B;">
        <?= $isRegister ? 'Already have an account? <a href="/index.php">Sign in</a>' : 'New here? <a href="/index.php?action=register">Create an account</a>' ?>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<nav class="sc-nav">
  <a href="/index.php?action=orders" class="sc-nav-logo">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
    SwiftCart
  </a>
  <div class="sc-nav-links">
    <a href="/index.php?action=orders" class="sc-nav-link <?= $isOrders || $isOrder ? 'active' : '' ?>">My Orders</a>
    <a href="/index.php?action=account" class="sc-nav-link <?= $isAccount ? 'active' : '' ?>">Account</a>
  </div>
  <div class="sc-nav-right">
    <span class="sc-nav-user"><?= esc($currentUser['name']) ?></span>
    <a href="/index.php?logout=1" class="sc-nav-out">Sign Out</a>
  </div>
</nav>

<?php if ($isOrders): ?>
<div class="sc-wrap">
  <div class="sc-page-hdr">
    <div class="sc-page-title">My Orders</div>
    <div class="sc-page-sub">View your purchase history and download invoices.</div>
  </div>

  <div class="sc-card">
    <table class="sc-table">
      <thead>
        <tr>
          <th>Order</th>
          <th>Date</th>
          <th>Status</th>
          <th>Total</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($userOrders as $o):
          $badgeClass = $o['status'] === 'Delivered' ? 'sc-badge-delivered' : ($o['status'] === 'Shipped' ? 'sc-badge-shipped' : 'sc-badge-processing');
        ?>
        <tr>
          <td>
            <div class="sc-ord-num"><?= esc($o['order_num']) ?></div>
            <div class="sc-ord-date"><?= esc($o['order_date']) ?></div>
          </td>
          <td style="color:#475569;"><?= date('M j, Y', strtotime($o['order_date'])) ?></td>
          <td><span class="sc-badge <?= $badgeClass ?>"><?= esc($o['status']) ?></span></td>
          <td class="sc-total">$<?= number_format((float)$o['total'], 2) ?></td>
          <td style="text-align:right;">
            <a href="/index.php?action=order&id=<?= (int)$o['id'] ?>" class="sc-view-btn">View Invoice</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($userOrders)): ?>
        <tr><td colspan="5" style="text-align:center;color:#94A3B8;padding:32px;">No orders yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif ($isOrder): ?>
<div class="sc-wrap">
  <?php if ($orderDetail): ?>

  <div style="margin-bottom:16px;">
    <a href="/index.php?action=orders" style="font-size:.85rem;color:#64748B;">&larr; Back to My Orders</a>
  </div>

  <?php if ($orderDetail['special_notes']): ?>
  <div class="sc-flag">
    <div class="sc-flag-title">Sensitive Internal Order Exposed</div>
    <div class="sc-flag-text">This order invoice was accessed without proper authorization checks. The IDOR vulnerability allowed viewing another user's (or admin's) private order details.</div>
    <div class="sc-flag-val"><?= esc($orderDetail['special_notes']) ?></div>
  </div>
  <?php endif; ?>

  <div class="sc-card">
    <div class="sc-card-pad">
      <div class="sc-invoice-hdr">
        <div>
          <div class="sc-invoice-num"><?= esc($orderDetail['order_num']) ?></div>
          <div class="sc-invoice-date">Placed on <?= date('F j, Y', strtotime($orderDetail['order_date'])) ?></div>
          <div style="margin-top:4px;">
            <?php
              $badgeClass = $orderDetail['status'] === 'Delivered' ? 'sc-badge-delivered' : ($orderDetail['status'] === 'Shipped' ? 'sc-badge-shipped' : 'sc-badge-processing');
            ?>
            <span class="sc-badge <?= $badgeClass ?>"><?= esc($orderDetail['status']) ?></span>
          </div>
        </div>
        <div style="text-align:right;">
          <div class="sc-invoice-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            SwiftCart
          </div>
          <div class="sc-invoice-meta">support@swiftcart.com</div>
        </div>
      </div>

      <div class="sc-grid">
        <div class="sc-box">
          <div class="sc-box-title">Ship To</div>
          <div class="sc-box-text"><?= nl2br(esc($orderDetail['shipping_address'])) ?></div>
          <div class="sc-box-email"><?= esc($orderDetail['customer_email']) ?></div>
        </div>
        <div class="sc-box">
          <div class="sc-box-title">Payment &amp; Tracking</div>
          <div class="sc-box-text">
<strong>Payment:</strong> <?= esc($orderDetail['payment_method']) ?>
<strong>Tracking:</strong> <?= esc($orderDetail['tracking_num']) ?>
          </div>
        </div>
      </div>

      <div style="font-size:.72rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;">Order Items</div>
      <table class="sc-items-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>SKU</th>
            <th style="text-align:center;">Qty</th>
            <th style="text-align:right;">Price</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orderItems as $item): ?>
          <tr>
            <td style="font-weight:500;"><?= esc($item['product_name']) ?></td>
            <td class="sc-sku"><?= esc($item['product_sku']) ?></td>
            <td class="sc-qty"><?= (int)$item['qty'] ?></td>
            <td class="sc-price">$<?= number_format((float)$item['price'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="sc-total-row">
        <span class="sc-total-label">Order Total</span>
        <span class="sc-total-val">$<?= number_format((float)$orderDetail['total'], 2) ?></span>
      </div>

    </div>
  </div>

  <?php else: ?>
  <div class="sc-empty">
    <div class="sc-empty-title">Order not found</div>
    <div class="sc-empty-text">The order you are looking for does not exist.</div>
    <div style="margin-top:16px;"><a href="/index.php?action=orders" class="sc-view-btn">Back to My Orders</a></div>
  </div>
  <?php endif; ?>
</div>

<?php elseif ($isAccount): ?>
<div class="sc-wrap">
  <div class="sc-page-hdr">
    <div class="sc-page-title">Account</div>
    <div class="sc-page-sub">Manage your profile and preferences.</div>
  </div>

  <div class="sc-card">
    <div class="sc-card-pad">
      <div style="font-size:.72rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.04em;margin-bottom:14px;">Profile Information</div>
      <div class="sc-account-row">
        <span class="sc-account-label">Full Name</span>
        <span class="sc-account-val"><?= esc($accountData['name']) ?></span>
      </div>
      <div class="sc-account-row">
        <span class="sc-account-label">Email</span>
        <span class="sc-account-val"><?= esc($accountData['email']) ?></span>
      </div>
      <div class="sc-account-row">
        <span class="sc-account-label">Member Since</span>
        <span class="sc-account-val"><?= date('F j, Y', strtotime($accountData['created_at'])) ?></span>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>
<?php endif; ?>

</body>
</html>
