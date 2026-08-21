<?php
session_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// ── Self-bootstrapping ──────────────────────────────────────────────────
if (!is_dir(__DIR__ . '/config')) {
    @mkdir(__DIR__ . '/config', 0755, true);
}
$config_file = __DIR__ . '/config/app_settings.conf';
if (!file_exists($config_file)) {
    file_put_contents($config_file, '# ShopStream — Application Settings
# WARNING: Production configuration file.
# This file contains sensitive credentials.
#
# IMPORTANT: Restrict access to this file.

[shop]
name = ShopStream
version = 3.2.1
env = production
url = https://shopstream.io

[database]
host = localhost
port = 3306
name = KrazePlanet_DB
user = ss_admin
pass = Str3amSh0p#2024

[payment]
gateway = stripe
api_key = sk_live_6H9sN2mK4rL8xT1vW5yA3bC7dE0fJ3p
webhook_secret = whsec_Xk9mN2pQ4rL8sT1vW5yA3bC6dE0fH7j

[import]
allow_url_import = true
max_file_size = 50M
allowed_hosts =

[security]
encryption_key = STR3AM_C1PH3R_K3Y_2024
session_ttl = 1800
rate_limit = 100

[flag]
secret = flag{rfi_ecommerce_import_1004}
');
}

// ── Database ────────────────────────────────────────────────────────────
$host = 'localhost';
$db   = 'KrazePlanet';
$user = 'root';
$pass = '';
$message = '';
$message_type = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->query("CREATE TABLE IF NOT EXISTS lab1004_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin','manager','staff') DEFAULT 'staff',
    avatar VARCHAR(10) DEFAULT 'SS',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS lab1004_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    sku VARCHAR(50) UNIQUE NOT NULL,
    category VARCHAR(100) DEFAULT 'General',
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT DEFAULT 0,
    status ENUM('active','draft','discontinued') DEFAULT 'active',
    image_url VARCHAR(500) DEFAULT '',
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS lab1004_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    items INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Seed data ───────────────────────────────────────────────────────────
$r = $conn->query("SELECT COUNT(*) AS cnt FROM lab1004_users")->fetch_assoc();
if ($r['cnt'] == 0) {
    $conn->query("INSERT INTO lab1004_users (email, password, full_name, role, avatar) VALUES
        ('admin@shopstream.io', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'Marcus Chen', 'admin', 'MC'),
        ('manager@shopstream.io', '" . password_hash('manager123', PASSWORD_DEFAULT) . "', 'Elena Vasquez', 'manager', 'EV')
    ");
}

$r = $conn->query("SELECT COUNT(*) AS cnt FROM lab1004_products")->fetch_assoc();
if ($r['cnt'] == 0) {
    $conn->query("INSERT INTO lab1004_products (name, sku, category, price, stock, status, description) VALUES
        ('Wireless Bluetooth Headphones', 'SS-PRO-001', 'Electronics', 89.99, 245, 'active', 'Premium noise-cancelling wireless headphones with 30-hour battery life.'),
        ('Artisan Coffee Maker', 'SS-HOME-002', 'Home & Kitchen', 149.99, 128, 'active', 'Programmable 12-cup coffee maker with thermal carafe.'),
        ('Yoga Mat Premium', 'SS-FIT-003', 'Sports & Fitness', 39.99, 512, 'active', 'Non-slip eco-friendly yoga mat with carrying strap.'),
        ('Mechanical Keyboard RGB', 'SS-TECH-004', 'Electronics', 129.99, 89, 'active', 'Hot-swappable mechanical keyboard with per-key RGB lighting.'),
        ('Organic Skin Care Set', 'SS-BEAU-005', 'Beauty', 54.99, 367, 'active', 'Complete organic skincare routine with vitamin C serum.'),
        ('Portable Power Bank 20000mAh', 'SS-TECH-006', 'Electronics', 44.99, 678, 'active', 'High-capacity fast-charging power bank with dual USB-C.'),
        ('Stainless Steel Water Bottle', 'SS-HOME-007', 'Home & Kitchen', 24.99, 901, 'active', 'Double-wall insulated 32oz bottle. Keeps drinks cold 24h.'),
        ('Running Shoes Ultralight', 'SS-FIT-008', 'Sports & Fitness', 119.99, 156, 'active', 'Carbon-fiber infused running shoes with responsive cushioning.')
    ");
}

$r = $conn->query("SELECT COUNT(*) AS cnt FROM lab1004_orders")->fetch_assoc();
if ($r['cnt'] == 0) {
    $conn->query("INSERT INTO lab1004_orders (order_number, customer_name, customer_email, total, status, items) VALUES
        ('STR-2024-0001', 'Alice Johnson', 'alice@example.com', 239.97, 'delivered', 3),
        ('STR-2024-0002', 'Bob Martinez', 'bob@example.com', 149.99, 'shipped', 1),
        ('STR-2024-0003', 'Carol Williams', 'carol@example.com', 84.98, 'processing', 2),
        ('STR-2024-0004', 'David Kim', 'david@example.com', 169.98, 'pending', 2),
        ('STR-2024-0005', 'Emma Thompson', 'emma@example.com', 54.99, 'delivered', 1),
        ('STR-2024-0006', 'Frank Garcia', 'frank@example.com', 319.96, 'processing', 4),
        ('STR-2024-0007', 'Grace Liu', 'grace@example.com', 44.99, 'shipped', 1),
        ('STR-2024-0008', 'Henry Patel', 'henry@example.com', 194.98, 'pending', 2)
    ");
}

// ── Helpers ─────────────────────────────────────────────────────────────
function esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function get_user($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM lab1004_users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// ── Auth ────────────────────────────────────────────────────────────────
$logged_in = false;
$current_user = null;

if (isset($_SESSION['lab1004_user_id'])) {
    $current_user = get_user($conn, $_SESSION['lab1004_user_id']);
    if ($current_user) $logged_in = true;
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM lab1004_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['lab1004_user_id'] = $user['id'];
        $current_user = $user;
        $logged_in = true;
        $message = 'Welcome back, ' . esc($user['full_name']) . '!';
        $message_type = 'success';
    } else {
        $message = 'Invalid email or password.';
        $message_type = 'danger';
    }
}

$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';

// ── Import handler (vulnerable include) ─────────────────────────────────
$imported_content = '';
$import_error = '';

if ($logged_in && isset($_GET['url']) && $action === 'import') {
    $url = $_GET['url'];
    if (!empty($url)) {
        ob_start();
        $included = @include($url);
        $imported_content = ob_get_clean();
        if ($included === false && empty($imported_content)) {
            $import_error = 'Could not retrieve data from: ' . esc($url);
            $imported_content = '';
        }
    }
}

// ── Login page ──────────────────────────────────────────────────────────
if (!$logged_in) { ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ShopStream — Management Console</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
<style>
:root {
    --ss-primary: #e07a3a;
    --ss-primary-dark: #c96a2e;
    --ss-primary-light: #f09a5a;
    --ss-accent: #e85d3a;
    --ss-bg: #1a1210;
    --ss-card-bg: #2a1f1a;
    --ss-border: #3d2e26;
    --ss-text: #f0e6dc;
    --ss-text-muted: #a09080;
    --ss-success: #5cb85c;
    --ss-warning: #f0ad4e;
    --ss-danger: #d9534f;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    background: linear-gradient(135deg, #1a1210 0%, #2a1f1a 50%, #1a1210 100%);
    color: var(--ss-text);
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.login-wrap { width:100%; max-width:420px; padding:20px; }
.login-header { text-align:center; margin-bottom:32px; }
.logo-emblem {
    width:60px; height:60px;
    background: linear-gradient(135deg, var(--ss-primary), var(--ss-accent));
    border-radius:50%; display:flex; align-items:center; justify-content:center;
    margin:0 auto 16px; box-shadow:0 8px 24px rgba(224,122,58,0.3);
    font-size:1.6rem; font-weight:800; color:#fff;
}
.login-header h1 { font-size:1.6rem; font-weight:700; color:#fff; margin-bottom:2px; }
.login-header p { font-size:0.85rem; color:var(--ss-text-muted); }
.login-card {
    background:var(--ss-card-bg); border:1px solid var(--ss-border);
    border-radius:16px; padding:32px; box-shadow:0 4px 24px rgba(0,0,0,0.4);
}
.form-label { font-size:0.75rem; font-weight:600; color:var(--ss-text-muted); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px; }
.form-control {
    background:#1a1210; border:1px solid var(--ss-border); color:var(--ss-text);
    padding:10px 14px; border-radius:10px; font-size:0.9rem;
}
.form-control:focus { background:#1a1210; border-color:var(--ss-primary); box-shadow:0 0 0 3px rgba(224,122,58,0.15); color:var(--ss-text); }
.form-control::placeholder { color:#5a4a3a; }
.btn-login {
    background:linear-gradient(135deg,var(--ss-primary),var(--ss-accent)); border:none;
    color:#fff; padding:11px 20px; border-radius:10px; font-weight:600;
    font-size:0.95rem; width:100%; transition:all 0.2s; cursor:pointer;
}
.btn-login:hover { transform:translateY(-1px); box-shadow:0 8px 20px rgba(224,122,58,0.35); }
.alert { border-radius:10px; border:none; font-size:0.85rem; }
</style>
</head>
<body>
<div class="login-wrap">
    <div class="login-header">
        <div class="logo-emblem">S</div>
        <h1>ShopStream</h1>
        <p>Management Console</p>
    </div>
    <div class="login-card">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> mb-3"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="POST" action="index.php">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@shopstream.io" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" name="login" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>
    </div>
</div>
</body>
</html>
<?php exit; }

// ── Data helpers ────────────────────────────────────────────────────────
function get_products($conn, $status = null) {
    $q = "SELECT * FROM lab1004_products";
    $params = []; $types = '';
    if ($status) { $q .= " WHERE status = ?"; $params[] = $status; $types = 's'; }
    $q .= " ORDER BY updated_at DESC";
    $stmt = $conn->prepare($q);
    if ($params) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_orders($conn) {
    return $conn->query("SELECT * FROM lab1004_orders ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

// ── Admin panel ─────────────────────────────────────────────────────────
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ShopStream — Management Console</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
<style>
:root {
    --ss-primary: #e07a3a;
    --ss-primary-dark: #c96a2e;
    --ss-primary-light: #f09a5a;
    --ss-accent: #e85d3a;
    --ss-bg: #1a1210;
    --ss-sidebar: #221814;
    --ss-card: #2a1f1a;
    --ss-border: #3d2e26;
    --ss-text: #f0e6dc;
    --ss-text-muted: #a09080;
    --ss-text-dim: #7a6a5a;
    --ss-success: #5cb85c;
    --ss-warning: #f0ad4e;
    --ss-danger: #d9534f;
    --ss-hover: #3d2e26;
    --ss-amber: #d4a04a;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { background:var(--ss-bg); color:var(--ss-text); font-family:'Segoe UI', system-ui, -apple-system, sans-serif; min-height:100vh; display:flex; }

/* ── Sidebar ────────────────────────────────────────────────────────── */
.sidebar {
    width:250px; background:var(--ss-sidebar); border-right:1px solid var(--ss-border);
    display:flex; flex-direction:column; flex-shrink:0; height:100vh; position:sticky; top:0;
}
.sidebar-brand { padding:20px 18px 14px; display:flex; align-items:center; gap:12px; border-bottom:1px solid var(--ss-border); }
.sidebar-logo {
    width:38px; height:38px; background:linear-gradient(135deg,var(--ss-primary),var(--ss-accent));
    border-radius:10px; display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:1.2rem; color:#fff; flex-shrink:0;
}
.sidebar-brand-text { font-size:1.05rem; font-weight:700; color:#fff; line-height:1.2; }
.sidebar-brand-sub { font-size:0.6rem; color:var(--ss-text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.06em; }
.sidebar-nav { flex:1; padding:12px 10px; }
.nav-section { font-size:0.6rem; font-weight:600; text-transform:uppercase; letter-spacing:0.1em; color:var(--ss-text-dim); padding:16px 12px 6px; }
.nav-item {
    display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px;
    color:var(--ss-text-muted); text-decoration:none; font-size:0.85rem;
    font-weight:500; transition:all 0.15s; cursor:pointer;
}
.nav-item:hover { background:var(--ss-hover); color:var(--ss-text); }
.nav-item.active { background:rgba(224,122,58,0.12); color:var(--ss-primary-light); }
.nav-item svg { width:18px; height:18px; fill:currentColor; flex-shrink:0; }
.nav-item .badge-ct {
    margin-left:auto; background:var(--ss-border); color:var(--ss-text-muted);
    font-size:0.65rem; padding:1px 8px; border-radius:10px; font-weight:600;
}
.nav-item.active .badge-ct { background:rgba(224,122,58,0.2); color:var(--ss-primary-light); }
.sidebar-foot { padding:14px 16px; border-top:1px solid var(--ss-border); display:flex; align-items:center; gap:10px; }
.user-avatar { width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.75rem; flex-shrink:0; }
.user-nm { font-size:0.8rem; font-weight:600; color:var(--ss-text); line-height:1.2; }
.user-rl { font-size:0.68rem; color:var(--ss-text-dim); text-transform:capitalize; }
.user-lo { color:var(--ss-text-dim); text-decoration:none; padding:4px; border-radius:4px; transition:all 0.15s; }
.user-lo:hover { color:var(--ss-danger); background:rgba(217,83,79,0.1); }

/* ── Main ────────────────────────────────────────────────────────────── */
.main-content { flex:1; min-width:0; padding:24px 32px 32px; max-width:calc(100% - 250px); }
.page-hdr { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
.page-hdr h1 { font-size:1.4rem; font-weight:700; color:#fff; margin:0; }
.page-hdr p { font-size:0.82rem; color:var(--ss-text-muted); margin:2px 0 0; }
.breadcrumb-ss { display:flex; align-items:center; gap:6px; font-size:0.78rem; color:var(--ss-text-dim); margin-bottom:16px; }
.breadcrumb-ss a { color:var(--ss-text-muted); text-decoration:none; }
.breadcrumb-ss a:hover { color:var(--ss-primary-light); }
.breadcrumb-ss .sep { color:var(--ss-border); }

/* ── Stats ────────────────────────────────────────────────────────────── */
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px; margin-bottom:24px; }
.stat-cd { background:var(--ss-card); border:1px solid var(--ss-border); border-radius:12px; padding:18px 20px; }
.stat-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; margin-bottom:12px; }
.stat-icon svg { width:18px; height:18px; fill:#fff; }
.stat-val { font-size:1.5rem; font-weight:700; color:#fff; line-height:1.2; }
.stat-lbl { font-size:0.75rem; color:var(--ss-text-muted); margin-top:2px; }

/* ── Tables ──────────────────────────────────────────────────────────── */
.table-wrap { background:var(--ss-card); border:1px solid var(--ss-border); border-radius:12px; overflow:hidden; }
.table-wrap .tbl-hdr { padding:14px 20px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid var(--ss-border); }
.tbl-hdr h3 { font-size:0.92rem; font-weight:700; margin:0; color:#fff; }
.tbl { width:100%; border-collapse:collapse; }
.tbl th {
    text-align:left; padding:10px 20px; font-size:0.68rem; font-weight:600;
    text-transform:uppercase; letter-spacing:0.07em; color:var(--ss-text-dim);
    background:rgba(0,0,0,0.2); border-bottom:1px solid var(--ss-border);
}
.tbl td { padding:12px 20px; font-size:0.83rem; border-bottom:1px solid rgba(61,46,38,0.5); vertical-align:middle; color:var(--ss-text); }
.tbl tr:last-child td { border-bottom:none; }
.tbl tr:hover td { background:rgba(224,122,58,0.03); }

.st-badge { display:inline-flex; align-items:center; gap:5px; font-size:0.7rem; font-weight:600; padding:3px 10px; border-radius:20px; }
.st-badge.active, .st-badge.delivered { background:rgba(92,184,92,0.12); color:var(--ss-success); }
.st-badge.draft { background:rgba(240,173,78,0.12); color:var(--ss-warning); }
.st-badge.processing { background:rgba(212,160,74,0.12); color:var(--ss-amber); }
.st-badge.shipped { background:rgba(99,130,210,0.12); color:#6382d2; }
.st-badge.pending { background:rgba(160,144,128,0.12); color:var(--ss-text-muted); }
.st-badge.cancelled, .st-badge.discontinued { background:rgba(217,83,79,0.12); color:var(--ss-danger); }

.btn-ssm { padding:5px 12px; font-size:0.72rem; font-weight:600; border-radius:6px; border:1px solid var(--ss-border); background:transparent; color:var(--ss-text-muted); text-decoration:none; transition:all 0.15s; display:inline-flex; align-items:center; gap:4px; }
.btn-ssm:hover { background:var(--ss-hover); color:var(--ss-text); }
.btn-ssp { background:rgba(224,122,58,0.1); border-color:rgba(224,122,58,0.2); color:var(--ss-primary-light); }
.btn-ssp:hover { background:rgba(224,122,58,0.2); color:var(--ss-primary-light); }

/* ── Import panel ────────────────────────────────────────────────────── */
.import-panel { background:var(--ss-card); border:1px solid var(--ss-border); border-radius:12px; overflow:hidden; border-top:3px solid var(--ss-danger); }
.import-panel-header { padding:14px 18px; background:rgba(217,83,79,0.05); border-bottom:1px solid var(--ss-border); display:flex; align-items:center; gap:10px; }
.import-panel-header svg { width:18px; height:18px; fill:var(--ss-danger); flex-shrink:0; }
.import-panel-header h4 { font-size:0.85rem; font-weight:700; color:var(--ss-text); margin:0; }
.import-panel-body { padding:16px 18px; }
.import-lbl { font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--ss-text-dim); margin-bottom:6px; }
.import-row { display:flex; gap:0; margin-bottom:8px; }
.import-inp {
    flex:1; background:#1a1210; border:1px solid var(--ss-border); border-right:none;
    border-radius:8px 0 0 8px; padding:9px 12px; color:var(--ss-text);
    font-family:'Consolas','Courier New',monospace; font-size:0.78rem; outline:none;
}
.import-inp:focus { border-color:var(--ss-primary); }
.import-btn {
    background:var(--ss-danger); border:1px solid var(--ss-danger);
    border-radius:0 8px 8px 0; color:#fff; padding:9px 16px;
    font-size:0.75rem; font-weight:700; cursor:pointer; transition:all 0.15s; white-space:nowrap;
}
.import-btn:hover { background:#c9302c; }
.import-result { margin-top:12px; background:#1a1210; border:1px solid var(--ss-border); border-radius:8px; overflow:hidden; }
.import-result-hdr { padding:8px 12px; font-size:0.7rem; font-weight:600; color:var(--ss-text-muted); background:rgba(0,0,0,0.3); border-bottom:1px solid var(--ss-border); display:flex; align-items:center; gap:6px; }
.import-result-body { padding:12px; font-family:'Consolas','Courier New',monospace; font-size:0.75rem; color:#e2e8f0; line-height:1.6; max-height:300px; overflow:auto; white-space:pre-wrap; word-break:break-all; }
.import-err { padding:12px; font-size:0.78rem; color:var(--ss-danger); background:rgba(217,83,79,0.05); }

/* ── Responsive ──────────────────────────────────────────────────────── */
@media (max-width:768px) {
    .sidebar { width:56px; }
    .sidebar-brand-text, .sidebar-brand-sub, .nav-section, .nav-item span,
    .nav-item .badge-ct, .user-nm, .user-rl, .user-lo { display:none; }
    .sidebar-brand { padding:14px 10px; justify-content:center; }
    .sidebar-nav { padding:8px 6px; }
    .nav-item { justify-content:center; padding:10px; }
    .sidebar-foot { justify-content:center; padding:10px; }
    .main-content { padding:16px; max-width:calc(100% - 56px); }
}
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo">S</div>
        <div>
            <div class="sidebar-brand-text">ShopStream</div>
            <div class="sidebar-brand-sub">Management Console</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Main</div>
        <a href="index.php?action=dashboard" class="nav-item <?php echo $action === 'dashboard' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>Dashboard</span>
        </a>
        <a href="index.php?action=products" class="nav-item <?php echo $action === 'products' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <span>Products</span>
            <?php $all_products = get_products($conn); if (count($all_products) > 0): ?>
            <span class="badge-ct"><?php echo count($all_products); ?></span>
            <?php endif; ?>
        </a>
        <a href="index.php?action=orders" class="nav-item <?php echo $action === 'orders' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            <span>Orders</span>
            <?php $all_orders = get_orders($conn); if (count($all_orders) > 0): ?>
            <span class="badge-ct"><?php echo count($all_orders); ?></span>
            <?php endif; ?>
        </a>
        <div class="nav-section">Inventory</div>
        <a href="index.php?action=import" class="nav-item <?php echo $action === 'import' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Bulk Import</span>
        </a>
        <div class="nav-section">System</div>
        <a href="#" class="nav-item">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.32 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            <span>Settings</span>
        </a>
    </nav>
    <div class="sidebar-foot">
        <div class="user-avatar" style="background:linear-gradient(135deg,<?php echo $current_user['role']==='admin'?'#e07a3a':'#5cb85c' ?>);color:#fff;">
            <?php $parts = explode(' ', $current_user['full_name']); echo esc(substr($current_user['full_name'], 0, 1) . substr($parts[1]??'', 0, 1)); ?>
        </div>
        <div>
            <div class="user-nm"><?php echo esc($current_user['full_name']); ?></div>
            <div class="user-rl"><?php echo esc($current_user['role']); ?></div>
        </div>
        <a href="index.php?action=logout" class="user-lo" title="Sign Out">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        </a>
    </div>
</aside>

<!-- Main Content -->
<main class="main-content">

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-3" style="border-radius:10px;border:none;">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($import_error): ?>
<div class="alert alert-danger" style="border-radius:10px;border:none;font-size:0.85rem;">
    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $import_error; ?>
</div>
<?php endif; ?>

<!-- ═════════════════════════════════════════════════════════════════════ -->
<!-- DASHBOARD                                                           -->
<!-- ═════════════════════════════════════════════════════════════════════ -->
<?php if ($action === 'dashboard'):
    $products = get_products($conn);
    $orders = get_orders($conn);
    $active_products = 0; $total_revenue = 0; $total_sales = 0;
    foreach ($products as $p) { if ($p['status'] === 'active') $active_products++; }
    foreach ($orders as $o) { if ($o['status'] !== 'cancelled') { $total_revenue += $o['total']; $total_sales++; } }
    $total_stock = 0; foreach ($products as $p) { $total_stock += $p['stock']; }
?>
<div class="breadcrumb-ss">
    <a href="index.php?action=dashboard">Dashboard</a>
</div>
<div class="page-hdr">
    <div>
        <h1>Dashboard</h1>
        <p>Welcome back, <?php echo esc($current_user['full_name']); ?>. Here's your store overview.</p>
    </div>
</div>
<div class="stats-grid">
    <div class="stat-cd">
        <div class="stat-icon" style="background:linear-gradient(135deg,#e07a3a,#c96a2e);">
            <svg viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <div class="stat-val"><?php echo $active_products; ?></div>
        <div class="stat-lbl">Active Products</div>
    </div>
    <div class="stat-cd">
        <div class="stat-icon" style="background:linear-gradient(135deg,#5cb85c,#449d44);">
            <svg viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="stat-val">$<?php echo number_format($total_revenue, 2); ?></div>
        <div class="stat-lbl">Total Revenue</div>
    </div>
    <div class="stat-cd">
        <div class="stat-icon" style="background:linear-gradient(135deg,#d4a04a,#b8892e);">
            <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div class="stat-val"><?php echo count($orders); ?></div>
        <div class="stat-lbl">Total Orders</div>
    </div>
    <div class="stat-cd">
        <div class="stat-icon" style="background:linear-gradient(135deg,#6382d2,#4a6bbf);">
            <svg viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <div class="stat-val"><?php echo number_format($total_stock); ?></div>
        <div class="stat-lbl">Units in Stock</div>
    </div>
</div>
<div class="table-wrap">
    <div class="tbl-hdr">
        <h3>Recent Orders</h3>
        <a href="index.php?action=orders" class="btn-ssm btn-ssp"><i class="bi bi-eye"></i> View All</a>
    </div>
    <table class="tbl">
        <thead>
            <tr>
                <th style="width:18%;">Order</th>
                <th style="width:22%;">Customer</th>
                <th style="width:14%;">Items</th>
                <th style="width:16%;">Total</th>
                <th style="width:15%;">Status</th>
                <th style="width:15%;">Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($orders, 0, 5) as $o): ?>
            <tr>
                <td><strong style="font-family:monospace;"><?php echo esc($o['order_number']); ?></strong></td>
                <td style="color:var(--ss-text-muted);"><?php echo esc($o['customer_name']); ?></td>
                <td><?php echo $o['items']; ?></td>
                <td><strong>$<?php echo number_format($o['total'], 2); ?></strong></td>
                <td><span class="st-badge <?php echo $o['status']; ?>"><span style="width:6px;height:6px;border-radius:50%;background:currentColor;"></span><?php echo ucfirst($o['status']); ?></span></td>
                <td style="color:var(--ss-text-muted);font-size:0.8rem;"><?php echo date('M j, Y', strtotime($o['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ═════════════════════════════════════════════════════════════════════ -->
<!-- PRODUCTS                                                             -->
<!-- ═════════════════════════════════════════════════════════════════════ -->
<?php elseif ($action === 'products'):
    $products = get_products($conn);
?>
<div class="breadcrumb-ss">
    <a href="index.php?action=dashboard">Dashboard</a>
    <span class="sep">/</span>
    <span style="color:var(--ss-text-muted);">Products</span>
</div>
<div class="page-hdr">
    <div>
        <h1>Products</h1>
        <p>Manage your product catalog</p>
    </div>
    <a href="index.php?action=import" class="btn-ssm btn-ssp" style="padding:8px 16px;">
        <i class="bi bi-upload me-1"></i> Bulk Import
    </a>
</div>
<div class="table-wrap">
    <table class="tbl">
        <thead>
            <tr>
                <th style="width:30%;">Product</th>
                <th style="width:12%;">SKU</th>
                <th style="width:12%;">Category</th>
                <th style="width:10%;">Price</th>
                <th style="width:8%;">Stock</th>
                <th style="width:10%;">Status</th>
                <th style="width:18%;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><strong><?php echo esc($p['name']); ?></strong></td>
                <td style="font-family:monospace;font-size:0.78rem;color:var(--ss-text-muted);"><?php echo esc($p['sku']); ?></td>
                <td style="color:var(--ss-text-muted);"><?php echo esc($p['category']); ?></td>
                <td><strong>$<?php echo number_format($p['price'], 2); ?></strong></td>
                <td style="font-weight:600;color:<?php echo $p['stock'] < 100 ? 'var(--ss-warning)' : 'var(--ss-text)'; ?>"><?php echo $p['stock']; ?></td>
                <td><span class="st-badge <?php echo $p['status']; ?>"><span style="width:6px;height:6px;border-radius:50%;background:currentColor;"></span><?php echo ucfirst($p['status']); ?></span></td>
                <td><a href="#" class="btn-ssm">Edit</a> <a href="#" class="btn-ssm">View</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ═════════════════════════════════════════════════════════════════════ -->
<!-- ORDERS                                                               -->
<!-- ═════════════════════════════════════════════════════════════════════ -->
<?php elseif ($action === 'orders'):
    $orders = get_orders($conn);
?>
<div class="breadcrumb-ss">
    <a href="index.php?action=dashboard">Dashboard</a>
    <span class="sep">/</span>
    <span style="color:var(--ss-text-muted);">Orders</span>
</div>
<div class="page-hdr">
    <div>
        <h1>Orders</h1>
        <p>View and manage customer orders</p>
    </div>
</div>
<div class="table-wrap">
    <table class="tbl">
        <thead>
            <tr>
                <th style="width:16%;">Order #</th>
                <th style="width:18%;">Customer</th>
                <th style="width:20%;">Email</th>
                <th style="width:10%;">Items</th>
                <th style="width:14%;">Total</th>
                <th style="width:12%;">Status</th>
                <th style="width:10%;">Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><strong style="font-family:monospace;"><?php echo esc($o['order_number']); ?></strong></td>
                <td><?php echo esc($o['customer_name']); ?></td>
                <td style="color:var(--ss-text-muted);font-size:0.8rem;"><?php echo esc($o['customer_email']); ?></td>
                <td><?php echo $o['items']; ?></td>
                <td><strong>$<?php echo number_format($o['total'], 2); ?></strong></td>
                <td><span class="st-badge <?php echo $o['status']; ?>"><span style="width:6px;height:6px;border-radius:50%;background:currentColor;"></span><?php echo ucfirst($o['status']); ?></span></td>
                <td style="color:var(--ss-text-muted);font-size:0.8rem;"><?php echo date('M j', strtotime($o['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ═════════════════════════════════════════════════════════════════════ -->
<!-- BULK IMPORT (RFI)                                                    -->
<!-- ═════════════════════════════════════════════════════════════════════ -->
<?php elseif ($action === 'import'): ?>
<div class="breadcrumb-ss">
    <a href="index.php?action=dashboard">Dashboard</a>
    <span class="sep">/</span>
    <span style="color:var(--ss-text-muted);">Bulk Import</span>
</div>
<div class="page-hdr">
    <div>
        <h1>Bulk Product Import</h1>
        <p>Import product data from external sources</p>
    </div>
</div>
<div style="display:grid;grid-template-columns:1fr 400px;gap:20px;align-items:start;">
<div class="table-wrap" style="margin-bottom:0;">
    <div class="tbl-hdr"><h3>Current Products</h3></div>
    <table class="tbl">
        <thead><tr><th style="width:50%;">Product</th><th style="width:25%;">SKU</th><th style="width:25%;">Price</th></tr></thead>
        <tbody>
            <?php $products = get_products($conn); foreach (array_slice($products, 0, 6) as $p): ?>
            <tr>
                <td><?php echo esc($p['name']); ?></td>
                <td style="font-family:monospace;font-size:0.78rem;color:var(--ss-text-muted);"><?php echo esc($p['sku']); ?></td>
                <td>$<?php echo number_format($p['price'], 2); ?></td>
            </tr>
            <?php endforeach; if (count($products) > 6): ?>
            <tr><td colspan="3" style="text-align:center;color:var(--ss-text-dim);font-size:0.78rem;">+<?php echo count($products)-6; ?> more products</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="import-panel">
    <div class="import-panel-header">
        <svg viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <h4>Import from URL</h4>
    </div>
    <div class="import-panel-body">
        <div class="import-lbl">Data Source URL</div>
        <form method="GET" action="index.php" id="importForm">
            <input type="hidden" name="action" value="import">
            <div class="import-row">
                <input type="text" name="url" class="import-inp" placeholder="https://supplier.example.com/feed.xml" value="<?php echo isset($_GET['url']) ? esc($_GET['url']) : ''; ?>">
                <button type="submit" class="import-btn">Import</button>
            </div>
        </form>
        <div style="font-size:0.7rem;color:var(--ss-text-dim);line-height:1.5;margin-top:4px;">
            Supported formats: XML, CSV, JSON. Enter a URL to fetch product data from external suppliers or local import files.
        </div>
        <?php if (!empty($imported_content)): ?>
        <div class="import-result">
            <div class="import-result-hdr">
                <span style="width:7px;height:7px;border-radius:50%;background:var(--ss-success);display:inline-block;"></span>
                Data Retrieved
            </div>
            <div class="import-result-body"><?php echo esc($imported_content); ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
<?php endif; ?>

<footer style="margin-top:32px;padding-top:16px;border-top:1px solid var(--ss-border);font-size:0.72rem;color:var(--ss-text-dim);">
    ShopStream v3.2.1 &mdash; E-Commerce Management Console
</footer>

</main>
</body>
</html>