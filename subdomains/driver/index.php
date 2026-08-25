<?php
// Lab 702 — IDOR: Uber Driver Portal — Trip & Earnings Disclosure
// Platform: "Uber" ride-share driver portal (simulated)
// Vulnerability:
//   1) GET /index.php?action=trip&id=X has NO ownership check on driver_id.
//   2) GET /index.php?action=earnings_detail&driver_uuid=DRV-XXXX has NO ownership check.
// Real World: Anand Prakash | Uber partners.uber.com | $6,500 | HackerOne #150095
// Difficulty: Medium | Pure black-box — no hints in UI

session_start();

define('LAB_FLAG', 'flag{idor_uber_driver_earnings_disclosure_150095}');

// ── Database ──────────────────────────────────────────────────────────────────
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
if ($db->connect_error) { die('DB connection failed'); }

// ── Tables ─────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS lab702_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    driver_uuid VARCHAR(20) NOT NULL UNIQUE,
    vehicle VARCHAR(80),
    rating DECIMAL(2,1) DEFAULT 4.8,
    city VARCHAR(60),
    role ENUM('Driver','Admin') DEFAULT 'Driver',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die($db->error);

$db->query("CREATE TABLE IF NOT EXISTS lab702_trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    pickup VARCHAR(120) NOT NULL,
    dropoff VARCHAR(120) NOT NULL,
    fare DECIMAL(8,2) NOT NULL,
    tip DECIMAL(8,2) DEFAULT 0.00,
    trip_date DATE NOT NULL,
    status ENUM('Completed','Cancelled','In Progress') DEFAULT 'Completed',
    passenger_name VARCHAR(80),
    FOREIGN KEY (driver_id) REFERENCES lab702_users(id)
)") or die($db->error);

$db->query("CREATE TABLE IF NOT EXISTS lab702_earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    week_start DATE NOT NULL,
    week_end DATE NOT NULL,
    gross DECIMAL(10,2) NOT NULL,
    net DECIMAL(10,2) NOT NULL,
    trips_count INT NOT NULL DEFAULT 0,
    bonus DECIMAL(10,2) DEFAULT 0.00,
    payment_method VARCHAR(60),
    special_notes TEXT,
    FOREIGN KEY (driver_id) REFERENCES lab702_users(id)
)") or die($db->error);

// ── Seed data (idempotent) ───────────────────────────────────────────────────
function seed702($db) {
    $check = $db->query("SELECT COUNT(*) AS c FROM lab702_users");
    if ($check && $check->fetch_assoc()['c'] > 0) return;

    $pass1 = password_hash('victim123', PASSWORD_BCRYPT);
    $pass2 = password_hash('attacker123', PASSWORD_BCRYPT);
    $pass3 = password_hash('admin123', PASSWORD_BCRYPT);

    $db->query("INSERT INTO lab702_users (id, name, email, password, driver_uuid, vehicle, rating, city, role) VALUES
        (1, 'James Miller', 'james.miller@driver.com', '$pass1', 'DRV-A1B2-C3D4', 'Toyota Camry 2021', 4.9, 'San Francisco', 'Driver'),
        (2, 'Maria Garcia', 'attacker@driver.com', '$pass2', 'DRV-9E8F-7A6B', 'Honda Civic 2020', 4.7, 'Los Angeles', 'Driver'),
        (3, 'Uber Fleet Admin', 'fleet@uber.com', '$pass3', 'DRV-FLAG-9999', 'Tesla Model 3 2023', 5.0, 'Austin', 'Admin')
    ") or die($db->error);

    $db->query("INSERT INTO lab702_trips (driver_id, pickup, dropoff, fare, tip, trip_date, status, passenger_name) VALUES
        (1, 'SFO Terminal 2', 'Downtown SF - Market St', 42.50, 8.00, '2024-06-03', 'Completed', 'Robert Chen'),
        (1, 'Union Square', 'Fisherman\'s Wharf', 18.75, 5.00, '2024-06-03', 'Completed', 'Lisa Park'),
        (1, 'Mission District', 'SOMA - 4th St', 12.40, 3.00, '2024-06-04', 'Completed', 'Ahmed Hassan'),
        (1, 'Oakland Airport', 'Berkeley Campus', 35.20, 6.50, '2024-06-04', 'Completed', 'Sarah Johnson'),
        (1, 'Palo Alto Caltrain', 'Stanford University', 22.10, 4.00, '2024-06-05', 'Completed', 'David Lee'),
        (1, 'SJC Airport', 'San Jose Downtown', 28.90, 0.00, '2024-06-05', 'Completed', 'Nina Patel'),
        (1, 'Marina District', 'Golden Gate Park', 14.60, 2.00, '2024-06-06', 'Completed', 'Tom Wilson'),
        (1, 'Embarcadero', 'Sunset District', 24.30, 5.50, '2024-06-06', 'Completed', 'Amy Zhang')
    ") or die($db->error);

    $db->query("INSERT INTO lab702_trips (driver_id, pickup, dropoff, fare, tip, trip_date, status, passenger_name) VALUES
        (2, 'LAX Terminal 4', 'Hollywood Blvd', 38.50, 7.00, '2024-06-03', 'Completed', 'Carlos Mendez'),
        (2, 'Santa Monica Pier', 'Beverly Hills', 22.75, 4.50, '2024-06-04', 'Completed', 'Jennifer Wu'),
        (2, 'DTLA - 7th St', 'Pasadena Old Town', 31.20, 6.00, '2024-06-05', 'Completed', 'Kevin Brooks')
    ") or die($db->error);

    $db->query("INSERT INTO lab702_trips (driver_id, pickup, dropoff, fare, tip, trip_date, status, passenger_name) VALUES
        (3, 'Austin Bergstrom', 'UT Campus', 25.00, 10.00, '2024-06-01', 'Completed', 'System Test')
    ") or die($db->error);

    $db->query("INSERT INTO lab702_earnings (driver_id, week_start, week_end, gross, net, trips_count, bonus, payment_method, special_notes) VALUES
        (1, '2024-06-03', '2024-06-09', 472.50, 378.00, 8, 25.00, 'Direct Deposit •••• 4521', NULL),
        (1, '2024-05-27', '2024-06-02', 398.20, 318.56, 6, 0.00, 'Direct Deposit •••• 4521', NULL)
    ") or die($db->error);

    $db->query("INSERT INTO lab702_earnings (driver_id, week_start, week_end, gross, net, trips_count, bonus, payment_method, special_notes) VALUES
        (2, '2024-06-03', '2024-06-09', 231.95, 185.56, 3, 0.00, 'Direct Deposit •••• 8823', NULL)
    ") or die($db->error);

    $db->query("INSERT INTO lab702_earnings (driver_id, week_start, week_end, gross, net, trips_count, bonus, payment_method, special_notes) VALUES
        (3, '2024-06-03', '2024-06-09', 1250.00, 1000.00, 1, 500.00, 'Direct Deposit •••• 9999', 'CONFIDENTIAL — Internal security audit data. ')
    ") or die($db->error);
}
seed702($db);

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$action     = $_GET['action'] ?? '';
$isLogout   = isset($_GET['logout']);
$isRegister = ($action === 'register');
$isTrips    = ($action === 'trips');
$isTrip     = ($action === 'trip');
$isEarnings = ($action === 'earnings');
$isEarningsDetail = ($action === 'earnings_detail');
$error      = '';

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
    $vehicle = trim($_POST['vehicle'] ?? 'Toyota Prius');
    $city = trim($_POST['city'] ?? 'San Francisco');
    if ($name && $email && strlen($pass) >= 4) {
        $h = password_hash($pass, PASSWORD_BCRYPT);
        $uuid = 'DRV-' . strtoupper(bin2hex(random_bytes(4)));
        $st = $db->prepare('INSERT INTO lab702_users (name, email, password, driver_uuid, vehicle, city) VALUES (?,?,?,?,?,?)');
        $st->bind_param('ssssss', $name, $email, $h, $uuid, $vehicle, $city);
        $st->execute();
        $newUserId = $db->insert_id;
        $st->close();

        $db->query("INSERT INTO lab702_trips (driver_id, pickup, dropoff, fare, tip, trip_date, status, passenger_name) VALUES
            ($newUserId, 'Airport Terminal 1', 'Downtown Hotel', 35.50, 5.00, CURDATE(), 'Completed', 'Alex Johnson'),
            ($newUserId, 'Convention Center', 'Uptown Apartments', 18.25, 3.00, CURDATE(), 'Completed', 'Sam Taylor'),
            ($newUserId, 'Sports Arena', 'Residential District', 22.75, 4.00, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Completed', 'Jordan Lee')
        ") or die($db->error);

        $db->query("INSERT INTO lab702_earnings (driver_id, week_start, week_end, gross, net, trips_count, bonus, payment_method, special_notes) VALUES
            ($newUserId, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 76.50, 61.20, 3, 0.00, 'Visa ending in 1111', NULL)
        ") or die($db->error);
    }
    header('Location: /index.php');
    exit;
}

// ── POST: Login ─────────────────────────────────────────────────────────────
if (!$isRegister && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $st = $db->prepare('SELECT * FROM lab702_users WHERE email = ?');
        $st->bind_param('s', $email);
        $st->execute();
        $res = $st->get_result();
        $user = $res->fetch_assoc();
        $st->close();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['lab702_user'] = $user['id'];
            header('Location: /index.php?action=dashboard');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// ── Current user ──────────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['lab702_user'])) {
    $st = $db->prepare('SELECT * FROM lab702_users WHERE id = ?');
    $st->bind_param('i', $_SESSION['lab702_user']);
    $st->execute();
    $res = $st->get_result();
    $currentUser = $res->fetch_assoc();
    $st->close();
}

if ($currentUser && !$action && !$isLogout) {
    header('Location: /index.php?action=dashboard');
    exit;
}

// ── VULNERABLE: Trip detail (no ownership check) ────────────────────────────
$tripDetail = null;
if ($isTrip && isset($_GET['id'])) {
    $tripId = (int)$_GET['id'];
    $st = $db->prepare('SELECT t.*, u.name AS driver_name, u.driver_uuid, u.vehicle FROM lab702_trips t JOIN lab702_users u ON t.driver_id = u.id WHERE t.id = ?');
    $st->bind_param('i', $tripId);
    $st->execute();
    $res = $st->get_result();
    $tripDetail = $res->fetch_assoc();
    $st->close();
}

// ── VULNERABLE: Earnings detail (no ownership check on UUID) ────────────────
$earningsDetail = null;
$flagRevealed = false;
if ($isEarningsDetail && isset($_GET['driver_uuid'])) {
    $uuid = $_GET['driver_uuid'];
    $st = $db->prepare('SELECT e.*, u.name AS driver_name, u.role FROM lab702_earnings e JOIN lab702_users u ON e.driver_id = u.id WHERE u.driver_uuid = ?');
    $st->bind_param('s', $uuid);
    $st->execute();
    $res = $st->get_result();
    $earningsDetail = $res->fetch_assoc();
    $st->close();
    if ($earningsDetail && $earningsDetail['role'] === 'Admin') {
        $flagRevealed = true;
    }
}

// ── Fetch list data ───────────────────────────────────────────────────────────
$myTrips = [];
$myEarnings = [];
if ($currentUser) {
    $st = $db->prepare('SELECT * FROM lab702_trips WHERE driver_id = ? ORDER BY trip_date DESC, id DESC');
    $st->bind_param('i', $currentUser['id']);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) $myTrips[] = $row;
    $st->close();

    $st = $db->prepare('SELECT * FROM lab702_earnings WHERE driver_id = ? ORDER BY week_start DESC');
    $st->bind_param('i', $currentUser['id']);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) $myEarnings[] = $row;
    $st->close();
}

// ── Dashboard aggregates ──────────────────────────────────────────────────────
$totalEarnings = 0;
$totalTrips = 0;
$weekEarnings = 0;
if ($currentUser) {
    $res = $db->query("SELECT SUM(net) AS total, COUNT(*) AS cnt FROM lab702_earnings WHERE driver_id = {$currentUser['id']}");
    if ($res) { $r = $res->fetch_assoc(); $totalEarnings = $r['total'] ?? 0; $totalTrips = $r['cnt'] ?? 0; }
    $res = $db->query("SELECT net FROM lab702_earnings WHERE driver_id = {$currentUser['id']} ORDER BY week_start DESC LIMIT 1");
    if ($res) { $r = $res->fetch_assoc(); $weekEarnings = $r['net'] ?? 0; }
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Uber Driver Portal — 702</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#000;color:#fff;min-height:100vh}
a{color:#06C167;text-decoration:none}
a:hover{text-decoration:underline}
.container{display:flex;min-height:100vh}
.sidebar{width:240px;background:#111;border-right:1px solid #222;padding:24px 0;flex-shrink:0}
.sidebar-brand{padding:0 24px 24px;font-weight:700;font-size:1.25rem;display:flex;align-items:center;gap:8px}
.sidebar-brand svg{width:28px;height:28px;fill:#06C167}
.sidebar-nav a{display:block;padding:12px 24px;color:#aaa;font-size:.9rem;font-weight:500;transition:.15s}
.sidebar-nav a:hover,.sidebar-nav a.active{color:#fff;background:#1a1a1a}
.sidebar-nav a.active{border-left:3px solid #06C167}
.sidebar-footer{padding:24px;font-size:.75rem;color:#666;border-top:1px solid #222;margin-top:auto}
.main{flex:1;padding:32px 40px;overflow:auto}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px}
.topbar h1{font-size:1.5rem;font-weight:600}
.user-pill{background:#1a1a1a;border:1px solid #333;padding:8px 16px;border-radius:20px;font-size:.85rem}
.card{background:#111;border:1px solid #222;border-radius:12px;padding:24px;margin-bottom:24px}
.card-title{font-size:1.05rem;font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.card-title svg{width:20px;height:20px;stroke:#06C167;fill:none;stroke-width:2}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:#111;border:1px solid #222;border-radius:12px;padding:20px}
.stat-label{font-size:.75rem;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
.stat-value{font-size:1.5rem;font-weight:700;color:#fff}
.stat-sub{font-size:.8rem;color:#06C167;margin-top:4px}
table{width:100%;border-collapse:collapse;font-size:.88rem}
th{text-align:left;padding:12px 16px;color:#888;font-weight:500;font-size:.8rem;text-transform:uppercase;letter-spacing:.3px;border-bottom:1px solid #222}
td{padding:14px 16px;border-bottom:1px solid #1a1a1a}
tr:hover td{background:#0f0f0f}
.badge{display:inline-block;padding:3px 10px;border-radius:4px;font-size:.75rem;font-weight:600}
.badge-green{background:#06C16720;color:#06C167;border:1px solid #06C16740}
.badge-red{background:#dc262620;color:#ef4444;border:1px solid #dc262640}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:6px;font-size:.85rem;font-weight:600;border:none;cursor:pointer;transition:.15s}
.btn-primary{background:#06C167;color:#000}
.btn-primary:hover{background:#04a155;text-decoration:none}
.btn-ghost{background:transparent;color:#aaa;border:1px solid #333}
.btn-ghost:hover{color:#fff;border-color:#555}
.trip-route{display:flex;align-items:center;gap:16px;margin:20px 0;padding:16px;background:#0a0a0a;border-radius:8px}
.route-dot{width:12px;height:12px;border-radius:50%;background:#06C167}
.route-line{flex:1;height:2px;background:#333;position:relative}
.route-line::after{content:'';position:absolute;right:0;top:-4px;width:0;height:0;border-left:8px solid #333;border-top:5px solid transparent;border-bottom:5px solid transparent}
.route-label{font-size:.8rem;color:#888}
.route-value{font-weight:600}
.earnings-row{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid #1a1a1a}
.earnings-row:last-child{border-bottom:none}
.earnings-label{color:#888;font-size:.9rem}
.earnings-value{font-weight:600}
.earnings-total{font-size:1.2rem;font-weight:700;color:#06C167}
.auth-wrap{display:flex;align-items:center;justify-content:center;min-height:100vh;background:#000}
.auth-card{width:400px;background:#111;border:1px solid #222;border-radius:16px;padding:40px}
.auth-card h2{font-size:1.5rem;font-weight:700;margin-bottom:8px}
.auth-card p{color:#888;margin-bottom:24px;font-size:.9rem}
.auth-input{width:100%;padding:12px 14px;background:#0a0a0a;border:1px solid #222;border-radius:8px;color:#fff;font-size:.9rem;margin-bottom:12px}
.auth-input:focus{outline:none;border-color:#06C167}
.auth-btn{width:100%;padding:12px;background:#06C167;color:#000;border:none;border-radius:8px;font-weight:700;font-size:.9rem;cursor:pointer;margin-top:4px}
.auth-btn:hover{background:#04a155}
.auth-switch{text-align:center;margin-top:16px;font-size:.85rem;color:#888}
.error-msg{background:#dc262620;color:#ef4444;border:1px solid #dc262640;padding:10px 14px;border-radius:6px;font-size:.85rem;margin-bottom:16px}
.flag-banner{background:#06C16715;border:1px solid #06C16740;color:#06C167;padding:16px 20px;border-radius:8px;margin-top:20px;font-weight:600}
.stars{color:#fbbf24;letter-spacing:2px}
@media(max-width:768px){.sidebar{display:none}.main{padding:20px}}
</style>
</head>
<body>

<?php if (!$currentUser): ?>
<!-- ── Auth Pages ─────────────────────────────────────────────────────────── -->
<div class="auth-wrap">
  <div class="auth-card">
    <?php if ($isRegister): ?>
      <h2>Become a Driver</h2>
      <p>Start earning with Uber today.</p>
      <?php if ($error): ?><div class="error-msg"><?=esc($error)?></div><?php endif; ?>
      <form method="POST" action="/index.php?action=register">
        <input class="auth-input" name="name" placeholder="Full name" required>
        <input class="auth-input" name="email" type="email" placeholder="Email address" required>
        <input class="auth-input" name="password" type="password" placeholder="Password (min 4 chars)" minlength="4" required>
        <input class="auth-input" name="vehicle" placeholder="Vehicle (e.g. Toyota Prius)" value="Toyota Prius">
        <input class="auth-input" name="city" placeholder="City" value="San Francisco">
        <button class="auth-btn" type="submit">Sign up to drive</button>
      </form>
      <div class="auth-switch">Already have an account? <a href="/index.php">Sign in</a></div>
    <?php else: ?>
      <h2>Welcome back, Driver</h2>
      <p>Sign in to your partner account.</p>
      <?php if ($error): ?><div class="error-msg"><?=esc($error)?></div><?php endif; ?>
      <form method="POST" action="/index.php">
        <input class="auth-input" name="email" type="email" placeholder="Email address" required>
        <input class="auth-input" name="password" type="password" placeholder="Password" required>
        <button class="auth-btn" type="submit">Sign in</button>
      </form>
      <div class="auth-switch">New to Uber? <a href="/index.php?action=register">Sign up to drive</a></div>
      <div style="margin-top:24px;padding-top:16px;border-top:1px solid #222;font-size:.75rem;color:#555">
        <strong>Demo accounts:</strong><br>
        james.miller@driver.com / victim123<br>
        attacker@driver.com / attacker123
      </div>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<!-- ── Logged-in Layout ─────────────────────────────────────────────────────── -->
<div class="container">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
      Uber Driver
    </div>
    <nav class="sidebar-nav">
      <a href="/index.php?action=dashboard" class="<?=$action==='dashboard'||!$action?'active':''?>">Dashboard</a>
      <a href="/index.php?action=trips" class="<?=$isTrips||$isTrip?'active':''?>">Trip History</a>
      <a href="/index.php?action=earnings" class="<?=$isEarnings||$isEarningsDetail?'active':''?>">Earnings</a>
      <a href="/index.php?logout=1">Sign Out</a>
    </nav>
    <div class="sidebar-footer">
      &copy; Uber Technologies Inc.<br>
      Partner Portal v3.2.1
    </div>
  </aside>

  <main class="main">
    <div class="topbar">
      <h1><?php
        if ($isTrips || $isTrip) echo 'Trip History';
        elseif ($isEarnings || $isEarningsDetail) echo 'Earnings';
        else echo 'Dashboard';
      ?></h1>
      <div class="user-pill"><?=esc($currentUser['name'])?> &middot; <?=esc($currentUser['driver_uuid'])?></div>
    </div>

<?php if (!$action || $action === 'dashboard'): ?>
    <!-- ── DASHBOARD ─────────────────────────────────────────────────────────── -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-label">Rating</div>
        <div class="stat-value"><?=esc($currentUser['rating'])?></div>
        <div class="stat-sub stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">This Week</div>
        <div class="stat-value">$<?=esc(number_format($weekEarnings,2))?></div>
        <div class="stat-sub">Net earnings</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Trips</div>
        <div class="stat-value"><?=esc($totalTrips)?></div>
        <div class="stat-sub">All time</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Lifetime Earnings</div>
        <div class="stat-value">$<?=esc(number_format($totalEarnings,2))?></div>
        <div class="stat-sub">Net total</div>
      </div>
    </div>

    <div class="card">
      <div class="card-title">
        <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/><path d="M7 10h2v7H7zm4-3h2v10h-2zm4 6h2v4h-2z"/></svg>
        Recent Earnings
      </div>
      <?php if ($myEarnings): ?>
      <table>
        <tr><th>Week</th><th>Trips</th><th>Gross</th><th>Net</th><th>Bonus</th><th>Status</th><th></th></tr>
        <?php foreach ($myEarnings as $e): ?>
        <tr>
          <td><?=esc($e['week_start'])?> &ndash; <?=esc($e['week_end'])?></td>
          <td><?=esc($e['trips_count'])?></td>
          <td>$<?=esc(number_format($e['gross'],2))?></td>
          <td style="color:#06C167;font-weight:600">$<?=esc(number_format($e['net'],2))?></td>
          <td><?=$e['bonus']>0?'+$'.esc(number_format($e['bonus'],2)):'—'?></td>
          <td><span class="badge badge-green">Paid</span></td>
          <td><a class="btn btn-ghost" style="padding:4px 10px;font-size:.75rem" href="/index.php?action=earnings_detail&driver_uuid=<?=esc($currentUser['driver_uuid'])?>">View</a></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?>
        <p style="color:#888">No earnings data yet.</p>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-title">
        <svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
        Recent Trips
      </div>
      <?php if ($myTrips): ?>
      <table>
        <tr><th>Date</th><th>Pickup</th><th>Dropoff</th><th>Fare</th><th>Status</th><th></th></tr>
        <?php foreach (array_slice($myTrips,0,5) as $t): ?>
        <tr>
          <td><?=esc($t['trip_date'])?></td>
          <td><?=esc($t['pickup'])?></td>
          <td><?=esc($t['dropoff'])?></td>
          <td style="color:#06C167;font-weight:600">$<?=esc(number_format($t['fare'],2))?></td>
          <td><span class="badge badge-green"><?=esc($t['status'])?></span></td>
          <td><a class="btn btn-ghost" style="padding:4px 10px;font-size:.75rem" href="/index.php?action=trip&id=<?=esc($t['id'])?>">Details</a></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php if (count($myTrips)>5): ?>
        <div style="margin-top:12px"><a href="/index.php?action=trips" class="btn btn-ghost">View all trips &rarr;</a></div>
      <?php endif; ?>
      <?php else: ?>
        <p style="color:#888">No trips yet.</p>
      <?php endif; ?>
    </div>

<?php elseif ($isTrips): ?>
    <!-- ── TRIPS LIST ────────────────────────────────────────────────────────── -->
    <div class="card">
      <div class="card-title">
        <svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
        All Trips
      </div>
      <?php if ($myTrips): ?>
      <table>
        <tr><th>Trip #</th><th>Date</th><th>Pickup</th><th>Dropoff</th><th>Fare</th><th>Tip</th><th>Status</th><th></th></tr>
        <?php foreach ($myTrips as $t): ?>
        <tr>
          <td>#<?=esc($t['id'])?></td>
          <td><?=esc($t['trip_date'])?></td>
          <td><?=esc($t['pickup'])?></td>
          <td><?=esc($t['dropoff'])?></td>
          <td style="color:#06C167;font-weight:600">$<?=esc(number_format($t['fare'],2))?></td>
          <td>$<?=esc(number_format($t['tip'],2))?></td>
          <td><span class="badge badge-green"><?=esc($t['status'])?></span></td>
          <td><a class="btn btn-primary" style="padding:4px 12px;font-size:.75rem" href="/index.php?action=trip&id=<?=esc($t['id'])?>">View Details</a></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?>
        <p style="color:#888">No trips yet.</p>
      <?php endif; ?>
    </div>

<?php elseif ($isTrip && $tripDetail): ?>
    <!-- ── TRIP DETAIL (VULNERABLE: no ownership check) ────────────────────── -->
    <div class="card">
      <div class="card-title">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 2v4m0 12v4M2 12h4m12 0h4"/></svg>
        Trip #<?=esc($tripDetail['id'])?>
      </div>
      <div class="trip-route">
        <div>
          <div class="route-label">PICKUP</div>
          <div class="route-value"><?=esc($tripDetail['pickup'])?></div>
        </div>
        <div style="flex:1;display:flex;align-items:center;gap:8px;padding:0 16px">
          <div class="route-dot"></div>
          <div class="route-line"></div>
          <div class="route-dot" style="background:#ef4444"></div>
        </div>
        <div style="text-align:right">
          <div class="route-label">DROPOFF</div>
          <div class="route-value"><?=esc($tripDetail['dropoff'])?></div>
        </div>
      </div>
      <div class="stats-grid" style="margin-top:16px">
        <div class="stat-card">
          <div class="stat-label">Date</div>
          <div class="stat-value" style="font-size:1.1rem"><?=esc($tripDetail['trip_date'])?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Status</div>
          <div class="stat-value" style="font-size:1.1rem"><span class="badge badge-green"><?=esc($tripDetail['status'])?></span></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Fare</div>
          <div class="stat-value" style="font-size:1.1rem;color:#06C167">$<?=esc(number_format($tripDetail['fare'],2))?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Tip</div>
          <div class="stat-value" style="font-size:1.1rem">$<?=esc(number_format($tripDetail['tip'],2))?></div>
        </div>
      </div>
      <div style="margin-top:20px;padding:16px;background:#0a0a0a;border-radius:8px">
        <div style="display:flex;justify-content:space-between;margin-bottom:8px">
          <span style="color:#888">Passenger</span>
          <span style="font-weight:600"><?=esc($tripDetail['passenger_name'])?></span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:8px">
          <span style="color:#888">Driver</span>
          <span style="font-weight:600"><?=esc($tripDetail['driver_name'])?></span>
        </div>
        <div style="display:flex;justify-content:space-between">
          <span style="color:#888">Vehicle</span>
          <span style="font-weight:600"><?=esc($tripDetail['vehicle'])?></span>
        </div>
      </div>
    </div>
    <a href="/index.php?action=trips" class="btn btn-ghost">&larr; Back to trips</a>

<?php elseif ($isTrip): ?>
    <div class="card"><p style="color:#888">Trip not found.</p></div>

<?php elseif ($isEarnings): ?>
    <!-- EARNINGS LIST -->
    <div class="card">
      <div class="card-title">
        <svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
        Earnings History
      </div>
      <?php if ($myEarnings): ?>
      <table>
        <tr><th>Week</th><th>Trips</th><th>Gross</th><th>Net</th><th>Bonus</th><th>Payment</th><th></th></tr>
        <?php foreach ($myEarnings as $e): ?>
        <tr>
          <td><?=esc($e['week_start'])?> &ndash; <?=esc($e['week_end'])?></td>
          <td><?=esc($e['trips_count'])?></td>
          <td>$<?=esc(number_format($e['gross'],2))?></td>
          <td style="color:#06C167;font-weight:600">$<?=esc(number_format($e['net'],2))?></td>
          <td><?=$e['bonus']>0?'+$'.esc(number_format($e['bonus'],2)):'—'?></td>
          <td><?=esc($e['payment_method'])?></td>
          <td><a class="btn btn-primary" style="padding:4px 12px;font-size:.75rem" href="/index.php?action=earnings_detail&driver_uuid=<?=esc($currentUser['driver_uuid'])?>">View Statement</a></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?>
        <p style="color:#888">No earnings data yet.</p>
      <?php endif; ?>
    </div>

<?php elseif ($isEarningsDetail && $earningsDetail): ?>
    <!-- EARNINGS DETAIL (VULNERABLE: no ownership check on UUID) -->
    <div class="card">
      <div class="card-title">
        <svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
        Weekly Statement &middot; <?=esc($earningsDetail['week_start'])?> to <?=esc($earningsDetail['week_end'])?>
      </div>
      <div style="margin-bottom:16px;padding:12px 16px;background:#0a0a0a;border-radius:8px">
        <div style="font-size:.8rem;color:#888">DRIVER</div>
        <div style="font-weight:700;font-size:1.1rem"><?=esc($earningsDetail['driver_name'])?></div>
        <div style="font-size:.8rem;color:#666">UUID: <?=esc($earningsDetail['role']==='Admin'?'DRV-FLAG-9999':($earningsDetail['driver_id']==$currentUser['id']?$currentUser['driver_uuid']:'[hidden]'))?></div>
      </div>
      <div class="earnings-row">
        <span class="earnings-label">Trip Earnings</span>
        <span class="earnings-value">$<?=esc(number_format($earningsDetail['gross'] - $earningsDetail['bonus'],2))?></span>
      </div>
      <div class="earnings-row">
        <span class="earnings-label">Weekly Bonus</span>
        <span class="earnings-value">+$<?=esc(number_format($earningsDetail['bonus'],2))?></span>
      </div>
      <div class="earnings-row">
        <span class="earnings-label">Platform Fee</span>
        <span class="earnings-value" style="color:#ef4444">-$<?=esc(number_format($earningsDetail['gross'] - $earningsDetail['net'],2))?></span>
      </div>
      <div class="earnings-row" style="border-top:2px solid #333;margin-top:8px;padding-top:12px">
        <span class="earnings-total">Total Payout</span>
        <span class="earnings-total">$<?=esc(number_format($earningsDetail['net'],2))?></span>
      </div>
      <div style="margin-top:16px;padding:12px 16px;background:#0a0a0a;border-radius:8px">
        <div style="display:flex;justify-content:space-between">
          <span style="color:#888">Trips Completed</span>
          <span style="font-weight:600"><?=esc($earningsDetail['trips_count'])?></span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:8px">
          <span style="color:#888">Payment Method</span>
          <span style="font-weight:600"><?=esc($earningsDetail['payment_method'])?></span>
        </div>
      </div>
      <?php if ($earningsDetail['special_notes']): ?>
      <div style="margin-top:16px;padding:12px 16px;background:#0f0f0f;border-radius:8px;border-left:3px solid #06C167">
        <div style="font-size:.75rem;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Notes</div>
        <div style="font-size:.9rem"><?=nl2br(esc($earningsDetail['special_notes']))?></div>
      </div>
      <?php endif; ?>
      <?php if ($flagRevealed): ?>
      <div class="flag-banner"><?=LAB_FLAG?></div>
      <?php endif; ?>
    </div>
    <a href="/index.php?action=earnings" class="btn btn-ghost">&larr; Back to earnings</a>

<?php elseif ($isEarningsDetail): ?>
    <div class="card"><p style="color:#888">Earnings statement not found.</p></div>

<?php endif; ?>
  </main>
</div>
<?php endif; ?>
</body>
</html>
