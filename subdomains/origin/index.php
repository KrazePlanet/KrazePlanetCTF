<?php
session_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// ── Self-bootstrapping ──────────────────────────────────────────────────
if (!is_dir(__DIR__ . '/storage')) {
    @mkdir(__DIR__ . '/storage', 0755, true);
}
$config_file = __DIR__ . '/storage/cdn_origin.conf';
if (!file_exists($config_file)) {
    file_put_contents($config_file, '# StreamFlux — CDN Origin Configuration
# WARNING: This file contains sensitive origin server credentials.
# Restrict access to authorized personnel only.

[origin]
id = sf-origin-01
endpoint = https://origin.streamflux.io
region = us-east-1
protocol = https

[caching]
ttl_default = 3600
ttl_static = 86400
purge_key = SF_PURGE_S3CR3T_K3Y

[storage]
bucket = streamflux-cdn-origin
access_key = AKIA6S9N2MK4RL8XT1VW
secret_key = wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY

[security]
tls_cert = /etc/ssl/streamflux/cert.pem
allowed_origins = *.streamflux.io
rate_limit = 1000

[flag]
secret = flag{rfi_streamflux_cdn_1005}
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

$conn->query("CREATE TABLE IF NOT EXISTS lab1005_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin','analyst') DEFAULT 'analyst',
    avatar VARCHAR(10) DEFAULT 'SF',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS lab1005_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(150) UNIQUE NOT NULL,
    duration INT DEFAULT 0,
    views INT DEFAULT 0,
    category VARCHAR(60) DEFAULT 'General',
    status ENUM('published','unlisted','private') DEFAULT 'published',
    thumbnail_bg VARCHAR(20) DEFAULT '#7c3aed',
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS lab1005_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NOT NULL,
    recorded_at DATE NOT NULL,
    daily_views INT DEFAULT 0,
    watch_time INT DEFAULT 0,
    completions INT DEFAULT 0,
    FOREIGN KEY (video_id) REFERENCES lab1005_videos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS lab1005_cdn_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_url VARCHAR(500) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    response_size INT DEFAULT 0,
    checked_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Seed data ───────────────────────────────────────────────────────────
$r = $conn->query("SELECT COUNT(*) AS cnt FROM lab1005_users")->fetch_assoc();
if ($r['cnt'] == 0) {
    $conn->query("INSERT INTO lab1005_users (email, password, full_name, role, avatar) VALUES
        ('admin@streamflux.io', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'Alex Rivera', 'admin', 'AR'),
        ('analyst@streamflux.io', '" . password_hash('analyst123', PASSWORD_DEFAULT) . "', 'Jordan Chen', 'analyst', 'JC')
    ");
}

$r = $conn->query("SELECT COUNT(*) AS cnt FROM lab1005_videos")->fetch_assoc();
if ($r['cnt'] == 0) {
    $conn->query("INSERT INTO lab1005_videos (title, slug, duration, views, category, status, thumbnail_bg, description) VALUES
        ('Getting Started with StreamFlux v3', 'getting-started-v3', 845, 15420, 'Tutorials', 'published', '#7c3aed', 'Complete walkthrough of the StreamFlux platform version 3. Learn about new features and improvements.'),
        ('Advanced CDN Configuration Guide', 'cdn-config-guide', 1230, 8930, 'Engineering', 'published', '#14b8a6', 'Deep dive into CDN configuration, edge caching strategies, and origin server optimization.'),
        ('StreamFlux API v2 Overview', 'api-v2-overview', 672, 12450, 'Development', 'published', '#f59e0b', 'Introduction to the StreamFlux API version 2 with code examples and best practices.'),
        ('Real-Time Analytics Dashboard Walkthrough', 'analytics-dashboard', 945, 7620, 'Tutorials', 'published', '#ef4444', 'Explore the real-time analytics dashboard and learn how to interpret viewer metrics.'),
        ('Scaling Video Transcoding at StreamFlux', 'scaling-transcoding', 1560, 5430, 'Engineering', 'published', '#8b5cf6', 'How StreamFlux handles large-scale video transcoding across distributed infrastructure.'),
        ('Platform Security Best Practices', 'security-best-practices', 1100, 18930, 'Security', 'published', '#06b6d4', 'Security guidelines for StreamFlux users including access control and data protection.'),
        ('StreamFlux Mobile SDK Integration', 'mobile-sdk-intro', 534, 8210, 'Development', 'published', '#10b981', 'Quick start guide for integrating the StreamFlux mobile SDK into your iOS and Android apps.'),
        ('Content Delivery Network Architecture', 'cdn-architecture', 1420, 4320, 'Engineering', 'unlisted', '#f97316', 'Technical overview of StreamFlux CDN architecture spanning 47 global edge locations.'),
        ('Creator Spotlight: Building on StreamFlux', 'creator-spotlight', 760, 11340, 'Community', 'published', '#ec4899', 'Interview with top creators building innovative video experiences on StreamFlux.'),
        ('Platform Release Notes v3.2.1', 'release-notes-321', 312, 6780, 'Announcements', 'private', '#6366f1', 'Release notes for StreamFlux version 3.2.1 including bug fixes and performance improvements.')
    ");
}

$r = $conn->query("SELECT COUNT(*) AS cnt FROM lab1005_analytics")->fetch_assoc();
if ($r['cnt'] == 0) {
    $vids = $conn->query("SELECT id FROM lab1005_videos")->fetch_all(MYSQLI_ASSOC);
    $stmt = $conn->prepare("INSERT INTO lab1005_analytics (video_id, recorded_at, daily_views, watch_time, completions) VALUES (?, ?, ?, ?, ?)");
    $start = new DateTime('2026-04-01');
    for ($d = 0; $d < 66; $d++) {
        $date = (clone $start)->modify("+$d days")->format('Y-m-d');
        foreach ($vids as $v) {
            $views = rand(20, 450);
            $wt = $views * rand(30, 180);
            $comp = intval($views * (rand(30, 75) / 100));
            $stmt->bind_param("issii", $v['id'], $date, $views, $wt, $comp);
            $stmt->execute();
        }
    }
}

// ── Helpers ─────────────────────────────────────────────────────────────
function esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function get_user($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM lab1005_users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function format_duration($secs) {
    $m = floor($secs / 60);
    $s = $secs % 60;
    return $m . ':' . str_pad($s, 2, '0');
}

function format_views($n) {
    if ($n >= 1000000) return number_format($n / 1000000, 1) . 'M';
    if ($n >= 1000) return number_format($n / 1000, 1) . 'K';
    return (string)$n;
}

// ── Auth ────────────────────────────────────────────────────────────────
$logged_in = false;
$current_user = null;

if (isset($_SESSION['lab1005_user_id'])) {
    $current_user = get_user($conn, $_SESSION['lab1005_user_id']);
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
    $stmt = $conn->prepare("SELECT * FROM lab1005_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['lab1005_user_id'] = $user['id'];
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

// ── CDN Asset Proxy (vulnerable include) ──────────────────────────────
$proxy_content = '';
$proxy_error = '';
$proxy_url = '';

if ($logged_in && $action === 'cdn' && isset($_GET['load'])) {
    $proxy_url = $_GET['load'];
    if (!empty($proxy_url)) {
        ob_start();
        $included = @include($proxy_url);
        $proxy_content = ob_get_clean();
        if ($included === false && empty($proxy_content)) {
            $proxy_error = 'Failed to reach origin: ' . esc($proxy_url);
            $proxy_content = '';
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
<title>StreamFlux — Video Analytics</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
<style>
:root {
    --sf-primary: #7c3aed;
    --sf-primary-light: #a78bfa;
    --sf-primary-dark: #5b21b6;
    --sf-accent: #14b8a6;
    --sf-accent-light: #5eead4;
    --sf-gold: #f59e0b;
    --sf-bg: #0a0612;
    --sf-card: #150d24;
    --sf-card2: #1c1130;
    --sf-border: #2d1d4a;
    --sf-text: #e8e0f0;
    --sf-text-muted: #8b79a8;
    --sf-text-dim: #5c4a78;
    --sf-success: #10b981;
    --sf-warning: #f59e0b;
    --sf-danger: #ef4444;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    background: linear-gradient(135deg, #0a0612 0%, #150d24 50%, #0a0612 100%);
    color: var(--sf-text);
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.login-wrap { width:100%; max-width:400px; padding:20px; }
.login-header { text-align:center; margin-bottom:28px; }
.logo-emblem {
    width:56px; height:56px;
    background: linear-gradient(135deg, var(--sf-primary), var(--sf-accent));
    border-radius:16px; display:flex; align-items:center; justify-content:center;
    margin:0 auto 14px; box-shadow:0 8px 24px rgba(124,58,237,0.3);
    font-size:1.5rem; font-weight:800; color:#fff;
}
.login-header h1 { font-size:1.5rem; font-weight:700; color:#fff; margin-bottom:2px; }
.login-header p { font-size:0.82rem; color:var(--sf-text-muted); }
.login-card {
    background:var(--sf-card); border:1px solid var(--sf-border);
    border-radius:14px; padding:28px; box-shadow:0 4px 24px rgba(0,0,0,0.4);
}
.form-label { font-size:0.72rem; font-weight:600; color:var(--sf-text-muted); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:5px; }
.form-control {
    background:#0a0612; border:1px solid var(--sf-border); color:var(--sf-text);
    padding:10px 14px; border-radius:8px; font-size:0.9rem;
}
.form-control:focus { background:#0a0612; border-color:var(--sf-primary); box-shadow:0 0 0 3px rgba(124,58,237,0.15); color:var(--sf-text); }
.form-control::placeholder { color:#3a2a4a; }
.btn-login {
    background:linear-gradient(135deg,var(--sf-primary),var(--sf-primary-dark)); border:none;
    color:#fff; padding:11px 20px; border-radius:8px; font-weight:600;
    font-size:0.95rem; width:100%; transition:all 0.2s; cursor:pointer;
}
.btn-login:hover { transform:translateY(-1px); box-shadow:0 8px 20px rgba(124,58,237,0.35); }
.alert { border-radius:8px; border:none; font-size:0.85rem; }
</style>
</head>
<body>
<div class="login-wrap">
    <div class="login-header">
        <div class="logo-emblem">S</div>
        <h1>StreamFlux</h1>
        <p>Video Analytics Platform</p>
    </div>
    <div class="login-card">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> mb-3"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="POST" action="index.php">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@streamflux.io" required>
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
function get_videos($conn, $status = null) {
    $q = "SELECT * FROM lab1005_videos";
    $params = []; $types = '';
    if ($status) { $q .= " WHERE status = ?"; $params[] = $status; $types = 's'; }
    $q .= " ORDER BY created_at DESC";
    $stmt = $conn->prepare($q);
    if ($params) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_total_views($conn) {
    $r = $conn->query("SELECT SUM(views) AS t FROM lab1005_videos")->fetch_assoc();
    return $r['t'] ?? 0;
}

function get_total_watch_time($conn) {
    $r = $conn->query("SELECT SUM(watch_time) AS t FROM lab1005_analytics")->fetch_assoc();
    return $r['t'] ?? 0;
}

// ── Dashboard ───────────────────────────────────────────────────────────
function compute_growth($conn, $col = 'daily_views') {
    $q = "SELECT COALESCE(SUM($col),0) FROM lab1005_analytics WHERE recorded_at >= CURDATE() - INTERVAL 7 DAY";
    $now = $conn->query($q)->fetch_row()[0];
    $q = "SELECT COALESCE(SUM($col),0) FROM lab1005_analytics WHERE recorded_at >= CURDATE() - INTERVAL 14 DAY AND recorded_at < CURDATE() - INTERVAL 7 DAY";
    $prev = $conn->query($q)->fetch_row()[0];
    if ($prev > 0) return round(($now - $prev) / $prev * 100, 1);
    return 0;
}

function get_chart_data($conn) {
    $data = [];
    $r = $conn->query("SELECT recorded_at, SUM(daily_views) AS v, SUM(watch_time) AS w FROM lab1005_analytics GROUP BY recorded_at ORDER BY recorded_at ASC LIMIT 30");
    while ($row = $r->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

function get_top_videos($conn, $limit = 5) {
    return $conn->query("SELECT title, views, slug, thumbnail_bg, duration FROM lab1005_videos ORDER BY views DESC LIMIT $limit")->fetch_all(MYSQLI_ASSOC);
}

// ── Admin panel ─────────────────────────────────────────────────────────
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StreamFlux — Video Analytics</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
<style>
:root {
    --sf-primary: #7c3aed;
    --sf-primary-light: #a78bfa;
    --sf-primary-dark: #5b21b6;
    --sf-accent: #14b8a6;
    --sf-accent-light: #5eead4;
    --sf-gold: #f59e0b;
    --sf-bg: #0a0612;
    --sf-card: #150d24;
    --sf-card2: #1c1130;
    --sf-border: #2d1d4a;
    --sf-text: #e8e0f0;
    --sf-text-muted: #8b79a8;
    --sf-text-dim: #5c4a78;
    --sf-success: #10b981;
    --sf-warning: #f59e0b;
    --sf-danger: #ef4444;
    --sf-hover: #2d1d4a;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { background:var(--sf-bg); color:var(--sf-text); font-family:'Segoe UI', system-ui, -apple-system, sans-serif; min-height:100vh; display:flex; }

/* ── Sidebar (compact Discord-style) ──────────────────────────────────── */
.sidebar {
    width:68px; background:var(--sf-card); border-right:1px solid var(--sf-border);
    display:flex; flex-direction:column; align-items:center;
    flex-shrink:0; height:100vh; position:sticky; top:0; padding:12px 0;
}
.sidebar-logo {
    width:42px; height:42px;
    background:linear-gradient(135deg,var(--sf-primary),var(--sf-accent));
    border-radius:12px; display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:1.2rem; color:#fff; margin-bottom:16px; flex-shrink:0;
}
.sidebar-div { width:32px; height:2px; background:var(--sf-border); border-radius:1px; margin:4px 0 8px; }
.sidebar-nav { display:flex; flex-direction:column; align-items:center; gap:2px; flex:1; }
.nav-icon {
    width:42px; height:42px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    color:var(--sf-text-dim); text-decoration:none;
    transition:all 0.15s; position:relative;
}
.nav-icon svg { width:20px; height:20px; fill:currentColor; }
.nav-icon:hover { background:var(--sf-hover); color:var(--sf-text-muted); border-radius:12px; }
.nav-icon.active { background:var(--sf-primary); color:#fff; border-radius:12px; }
.nav-icon.active::before {
    content:''; position:absolute; left:-6px; top:50%; transform:translateY(-50%);
    width:4px; height:20px; background:#fff; border-radius:0 4px 4px 0;
}
.nav-icon .tip {
    position:absolute; left:100%; top:50%; transform:translateY(-50%);
    background:#2d1d4a; color:var(--sf-text); font-size:0.7rem; font-weight:600;
    padding:4px 10px; border-radius:6px; white-space:nowrap; margin-left:10px;
    pointer-events:none; opacity:0; transition:opacity 0.15s; z-index:50;
}
.nav-icon:hover .tip { opacity:1; }
.sidebar-foot { margin-top:auto; display:flex; flex-direction:column; align-items:center; gap:4px; padding-bottom:8px; }
.user-avatar-sm {
    width:36px; height:36px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:0.7rem; color:#fff; cursor:pointer; position:relative;
}
.user-avatar-sm.online::after {
    content:''; width:10px; height:10px; border-radius:50%; background:var(--sf-success);
    position:absolute; bottom:-1px; right:-1px; border:2px solid var(--sf-card);
}

/* ── Main ────────────────────────────────────────────────────────────── */
.main-wrap { flex:1; min-width:0; display:flex; }
.main-content { flex:1; min-width:0; padding:24px 28px 32px; max-width:100%; }
.page-hdr { display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; }
.page-hdr-left h1 { font-size:1.35rem; font-weight:700; color:#fff; margin:0; }
.page-hdr-left p { font-size:0.8rem; color:var(--sf-text-muted); margin:2px 0 0; }
.breadcrumb-sf { display:flex; align-items:center; gap:6px; font-size:0.75rem; color:var(--sf-text-dim); margin-bottom:14px; }
.breadcrumb-sf a { color:var(--sf-text-muted); text-decoration:none; }
.breadcrumb-sf a:hover { color:var(--sf-primary-light); }
.breadcrumb-sf .sep { color:var(--sf-border); }

/* ── Stats ────────────────────────────────────────────────────────────── */
.stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; margin-bottom:24px; }
.stat-bx { background:var(--sf-card); border:1px solid var(--sf-border); border-radius:12px; padding:16px 18px; }
.stat-bx-hdr { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
.stat-bx-hdr .lbl { font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.08em; color:var(--sf-text-dim); }
.stat-bx-hdr .ico { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; }
.stat-bx-hdr .ico svg { width:14px; height:14px; fill:#fff; }
.stat-val { font-size:1.55rem; font-weight:700; color:#fff; line-height:1.2; }
.stat-chg { font-size:0.72rem; font-weight:600; margin-top:3px; display:inline-flex; align-items:center; gap:3px; }
.stat-chg.up { color:var(--sf-success); }
.stat-chg.down { color:var(--sf-danger); }

/* ── Chart ────────────────────────────────────────────────────────────── */
.chart-wrap { background:var(--sf-card); border:1px solid var(--sf-border); border-radius:12px; padding:18px 20px; margin-bottom:24px; }
.chart-hdr { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.chart-hdr h3 { font-size:0.9rem; font-weight:700; color:#fff; margin:0; }
.chart-svg { width:100%; height:140px; }
.chart-svg path { stroke-width:2; fill:none; }
.chart-grid { stroke:var(--sf-border); stroke-width:1; }
.chart-labels { display:flex; justify-content:space-between; margin-top:4px; font-size:0.6rem; color:var(--sf-text-dim); }

/* ── Tables ──────────────────────────────────────────────────────────── */
.tbl-wrap { background:var(--sf-card); border:1px solid var(--sf-border); border-radius:12px; overflow:hidden; margin-bottom:24px; }
.tbl-hdr { padding:14px 18px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid var(--sf-border); }
.tbl-hdr h3 { font-size:0.88rem; font-weight:700; margin:0; color:#fff; }
.tbl { width:100%; border-collapse:collapse; }
.tbl th {
    text-align:left; padding:9px 18px; font-size:0.65rem; font-weight:600;
    text-transform:uppercase; letter-spacing:0.07em; color:var(--sf-text-dim);
    background:rgba(0,0,0,0.3); border-bottom:1px solid var(--sf-border);
}
.tbl td { padding:10px 18px; font-size:0.8rem; border-bottom:1px solid rgba(45,29,74,0.4); vertical-align:middle; color:var(--sf-text); }
.tbl tr:last-child td { border-bottom:none; }
.tbl tr:hover td { background:rgba(124,58,237,0.03); }

.st-badge { display:inline-flex; align-items:center; gap:4px; font-size:0.65rem; font-weight:600; padding:2px 9px; border-radius:20px; }
.st-badge.published { background:rgba(16,185,129,0.12); color:var(--sf-success); }
.st-badge.unlisted { background:rgba(245,158,11,0.12); color:var(--sf-warning); }
.st-badge.private { background:rgba(239,68,68,0.12); color:var(--sf-danger); }

.btn-sf { padding:5px 12px; font-size:0.7rem; font-weight:600; border-radius:6px; border:1px solid var(--sf-border); background:transparent; color:var(--sf-text-muted); text-decoration:none; transition:all 0.15s; display:inline-flex; align-items:center; gap:4px; cursor:pointer; }
.btn-sf:hover { background:var(--sf-hover); color:var(--sf-text); }
.btn-sfp { background:rgba(124,58,237,0.1); border-color:rgba(124,58,237,0.2); color:var(--sf-primary-light); }
.btn-sfp:hover { background:rgba(124,58,237,0.2); color:var(--sf-primary-light); }

/* ── Thumbnail placeholder ───────────────────────────────────────────── */
.thumb { width:48px; height:32px; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:0.55rem; font-weight:700; color:rgba(255,255,255,0.7); flex-shrink:0; }

/* ── CDN panel ───────────────────────────────────────────────────────── */
.cdn-panel { border:1px solid var(--sf-border); border-radius:12px; overflow:hidden; background:var(--sf-card); border-left:3px solid var(--sf-accent); }
.cdn-panel-hdr { padding:14px 18px; background:rgba(20,184,166,0.04); border-bottom:1px solid var(--sf-border); display:flex; align-items:center; gap:10px; }
.cdn-panel-hdr svg { width:18px; height:18px; fill:var(--sf-accent); flex-shrink:0; }
.cdn-panel-hdr h4 { font-size:0.85rem; font-weight:700; color:var(--sf-text); margin:0; }
.cdn-panel-body { padding:16px 18px; }
.cdn-lbl { font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--sf-text-dim); margin-bottom:6px; }
.cdn-row { display:flex; gap:0; margin-bottom:6px; }
.cdn-inp {
    flex:1; background:#0a0612; border:1px solid var(--sf-border); border-right:none;
    border-radius:8px 0 0 8px; padding:9px 12px; color:var(--sf-text);
    font-family:'Consolas','Courier New',monospace; font-size:0.78rem; outline:none;
}
.cdn-inp:focus { border-color:var(--sf-accent); }
.cdn-btn {
    background:var(--sf-accent); border:1px solid var(--sf-accent);
    border-radius:0 8px 8px 0; color:#0a0612; padding:9px 16px;
    font-size:0.75rem; font-weight:700; cursor:pointer; transition:all 0.15s; white-space:nowrap;
}
.cdn-btn:hover { background:var(--sf-accent-light); }
.cdn-result { margin-top:12px; background:#0a0612; border:1px solid var(--sf-border); border-radius:8px; overflow:hidden; }
.cdn-result-hdr { padding:7px 12px; font-size:0.68rem; font-weight:600; color:var(--sf-text-muted); background:rgba(0,0,0,0.3); border-bottom:1px solid var(--sf-border); display:flex; align-items:center; gap:6px; }
.cdn-result-body { padding:12px; font-family:'Consolas','Courier New',monospace; font-size:0.72rem; color:#e2e8f0; line-height:1.6; max-height:320px; overflow:auto; white-space:pre-wrap; word-break:break-all; }
.cdn-err { padding:12px; font-size:0.78rem; color:var(--sf-danger); background:rgba(239,68,68,0.04); }

/* ── Two-column ──────────────────────────────────────────────────────── */
.cols-2 { display:grid; grid-template-columns:1fr 380px; gap:20px; align-items:start; }
@media (max-width:900px) { .cols-2 { grid-template-columns:1fr; } }

.cdn-info-box { background:var(--sf-card); border:1px solid var(--sf-border); border-radius:12px; padding:16px 18px; font-size:0.8rem; line-height:1.7; color:var(--sf-text-muted); }
.cdn-info-box strong { color:var(--sf-text); }
.cdn-info-box .url-example { background:#0a0612; border:1px solid var(--sf-border); border-radius:6px; padding:8px 12px; font-family:monospace; font-size:0.72rem; color:var(--sf-accent-light); margin:8px 0; }

/* ── Settings panel ──────────────────────────────────────────────────── */
.settings-card { background:var(--sf-card); border:1px solid var(--sf-border); border-radius:12px; padding:20px; max-width:600px; }
.settings-card .form-label { font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--sf-text-dim); margin-bottom:4px; }
.settings-card .form-control { background:#0a0612; border:1px solid var(--sf-border); color:var(--sf-text); padding:9px 12px; border-radius:8px; font-size:0.85rem; }
.settings-card .form-control:focus { border-color:var(--sf-primary); box-shadow:0 0 0 3px rgba(124,58,237,0.15); }
.settings-card .form-text { color:var(--sf-text-dim); font-size:0.72rem; }

/* ── Footer ──────────────────────────────────────────────────────────── */
.footer-sf { margin-top:32px; padding-top:14px; border-top:1px solid var(--sf-border); font-size:0.7rem; color:var(--sf-text-dim); }
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-logo">S</div>
    <div class="sidebar-div"></div>
    <nav class="sidebar-nav">
        <a href="index.php?action=dashboard" class="nav-icon <?php echo $action === 'dashboard' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span class="tip">Dashboard</span>
        </a>
        <a href="index.php?action=videos" class="nav-icon <?php echo $action === 'videos' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
            <span class="tip">Videos</span>
        </a>
        <a href="index.php?action=cdn" class="nav-icon <?php echo $action === 'cdn' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
            <span class="tip">CDN</span>
        </a>
        <a href="index.php?action=analytics" class="nav-icon <?php echo $action === 'analytics' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>
            <span class="tip">Analytics</span>
        </a>
        <div class="sidebar-div" style="margin-top:auto;"></div>
        <a href="index.php?action=settings" class="nav-icon <?php echo $action === 'settings' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.32 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            <span class="tip">Settings</span>
        </a>
    </nav>
    <div class="sidebar-foot">
        <div class="user-avatar-sm online" style="background:linear-gradient(135deg,<?php echo $current_user['role']==='admin'?'#7c3aed':'#14b8a6' ?>);">
            <?php echo esc(substr($current_user['full_name'], 0, 1) . substr(explode(' ', $current_user['full_name'])[1]??'', 0, 1)); ?>
        </div>
        <a href="index.php?action=logout" class="nav-icon" title="Sign Out" style="width:36px;height:36px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            <span class="tip">Sign Out</span>
        </a>
    </div>
</aside>

<!-- Main -->
<div class="main-wrap">
<main class="main-content">

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-3" style="border-radius:8px;border:none;"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if ($proxy_error): ?>
<div class="alert alert-danger" style="border-radius:8px;border:none;font-size:0.82rem;"><i class="bi bi-exclamation-triangle me-2"></i><?php echo $proxy_error; ?></div>
<?php endif; ?>

<!-- ═════════════════════════════════════════════════════════════════════ -->
<!-- DASHBOARD                                                           -->
<!-- ═════════════════════════════════════════════════════════════════════ -->
<?php if ($action === 'dashboard'):
    $total_views = get_total_views($conn);
    $total_watch = get_total_watch_time($conn);
    $total_videos = $conn->query("SELECT COUNT(*) FROM lab1005_videos")->fetch_row()[0];
    $pub_videos = $conn->query("SELECT COUNT(*) FROM lab1005_videos WHERE status='published'")->fetch_row()[0];
    $views_growth = compute_growth($conn, 'daily_views');
    $wt_growth = compute_growth($conn, 'watch_time');
    $chart = get_chart_data($conn);
    $top = get_top_videos($conn, 5);
?>
<div class="breadcrumb-sf">
    <a href="index.php?action=dashboard">Dashboard</a>
</div>
<div class="page-hdr">
    <div class="page-hdr-left">
        <h1>Dashboard</h1>
        <p>Welcome back, <?php echo esc($current_user['full_name']); ?>. Here's your platform overview.</p>
    </div>
</div>

<div class="stats">
    <div class="stat-bx">
        <div class="stat-bx-hdr">
            <span class="lbl">Total Views</span>
            <div class="ico" style="background:linear-gradient(135deg,#7c3aed,#5b21b6);"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></div>
        </div>
        <div class="stat-val"><?php echo number_format($total_views); ?></div>
        <span class="stat-chg <?php echo $views_growth >= 0 ? 'up' : 'down'; ?>">
            <?php echo ($views_growth >= 0 ? '+' : '') . $views_growth; ?>%
        </span>
    </div>
    <div class="stat-bx">
        <div class="stat-bx-hdr">
            <span class="lbl">Watch Time (hrs)</span>
            <div class="ico" style="background:linear-gradient(135deg,#14b8a6,#0d9488);"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        </div>
        <div class="stat-val"><?php echo number_format(round($total_watch / 3600)); ?></div>
        <span class="stat-chg <?php echo $wt_growth >= 0 ? 'up' : 'down'; ?>">
            <?php echo ($wt_growth >= 0 ? '+' : '') . $wt_growth; ?>%
        </span>
    </div>
    <div class="stat-bx">
        <div class="stat-bx-hdr">
            <span class="lbl">Total Videos</span>
            <div class="ico" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><svg viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg></div>
        </div>
        <div class="stat-val"><?php echo $total_videos; ?></div>
        <span class="stat-chg up"><?php echo $pub_videos; ?> published</span>
    </div>
    <div class="stat-bx">
        <div class="stat-bx-hdr">
            <span class="lbl">Completion Rate</span>
            <div class="ico" style="background:linear-gradient(135deg,#ec4899,#db2777);"><svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
        </div>
        <?php
            $cr = $conn->query("SELECT COALESCE(SUM(completions),0) AS c, COALESCE(SUM(daily_views),0) AS v FROM lab1005_analytics")->fetch_assoc();
            $rate = $cr['v'] > 0 ? round($cr['c'] / $cr['v'] * 100, 1) : 0;
        ?>
        <div class="stat-val"><?php echo $rate; ?>%</div>
        <span class="stat-chg up">+2.4% this month</span>
    </div>
</div>

<div class="chart-wrap">
    <div class="chart-hdr">
        <h3>Views (Last 30 Days)</h3>
        <span style="font-size:0.72rem;color:var(--sf-text-dim);">Daily aggregate</span>
    </div>
    <svg class="chart-svg" viewBox="0 0 600 140" preserveAspectRatio="none">
        <?php
        $max_v = 1;
        foreach ($chart as $c) { if ($c['v'] > $max_v) $max_v = $c['v']; }
        $points = [];
        foreach ($chart as $i => $c) {
            $x = round($i / (count($chart)-1) * 600);
            $y = round(140 - ($c['v'] / $max_v * 120));
            $points[] = "$x,$y";
        }
        $ps = implode(' ', $points);
        ?>
        <line class="chart-grid" x1="0" y1="140" x2="600" y2="140"/>
        <line class="chart-grid" x1="0" y1="105" x2="600" y2="105"/>
        <line class="chart-grid" x1="0" y1="70" x2="600" y2="70"/>
        <line class="chart-grid" x1="0" y1="35" x2="600" y2="35"/>
        <path d="M0,140 L<?php echo $ps; ?> L600,140 Z" fill="rgba(124,58,237,0.08)"/>
        <path d="M<?php echo $ps; ?>" stroke="var(--sf-primary-light)"/>
        <circle cx="<?php echo $points[count($points)-1]?>" cy="14" r="3" fill="var(--sf-accent)"/>
    </svg>
    <div class="chart-labels">
        <span><?php echo date('M j', strtotime($chart[0]['recorded_at'] ?? 'now')); ?></span>
        <span><?php echo date('M j', strtotime($chart[floor(count($chart)/2)]['recorded_at'] ?? 'now')); ?></span>
        <span><?php echo date('M j', strtotime($chart[count($chart)-1]['recorded_at'] ?? 'now')); ?></span>
    </div>
</div>

<div class="tbl-wrap">
    <div class="tbl-hdr">
        <h3>Top Performing Content</h3>
        <a href="index.php?action=videos" class="btn-sf btn-sfp"><i class="bi bi-eye"></i> View All</a>
    </div>
    <table class="tbl">
        <thead>
            <tr>
                <th style="width:5%;"></th>
                <th style="width:50%;">Title</th>
                <th style="width:12%;">Duration</th>
                <th style="width:15%;">Views</th>
                <th style="width:18%;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($top as $i => $v): ?>
            <tr>
                <td><div class="thumb" style="background:<?php echo esc($v['thumbnail_bg']); ?>"><?php echo $i+1; ?></div></td>
                <td><strong><?php echo esc($v['title']); ?></strong></td>
                <td style="color:var(--sf-text-muted);font-family:monospace;"><?php echo format_duration($v['duration']); ?></td>
                <td><strong><?php echo format_views($v['views']); ?></strong></td>
                <td><a href="#" class="btn-sf">View Details</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ═════════════════════════════════════════════════════════════════════ -->
<!-- VIDEOS                                                              -->
<!-- ═════════════════════════════════════════════════════════════════════ -->
<?php elseif ($action === 'videos'):
    $videos = get_videos($conn);
?>
<div class="breadcrumb-sf">
    <a href="index.php?action=dashboard">Dashboard</a>
    <span class="sep">/</span>
    <span style="color:var(--sf-text-muted);">Videos</span>
</div>
<div class="page-hdr">
    <div class="page-hdr-left">
        <h1>Videos</h1>
        <p>Manage your video content library</p>
    </div>
</div>
<div class="tbl-wrap">
    <table class="tbl">
        <thead>
            <tr>
                <th style="width:4%;"></th>
                <th style="width:38%;">Title</th>
                <th style="width:10%;">Category</th>
                <th style="width:9%;">Duration</th>
                <th style="width:10%;">Views</th>
                <th style="width:9%;">Status</th>
                <th style="width:20%;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($videos as $v): ?>
            <tr>
                <td><div class="thumb" style="background:<?php echo esc($v['thumbnail_bg']); ?>">▶</div></td>
                <td><strong><?php echo esc($v['title']); ?></strong></td>
                <td style="color:var(--sf-text-muted);font-size:0.78rem;"><?php echo esc($v['category']); ?></td>
                <td style="font-family:monospace;color:var(--sf-text-muted);"><?php echo format_duration($v['duration']); ?></td>
                <td><strong><?php echo format_views($v['views']); ?></strong></td>
                <td><span class="st-badge <?php echo $v['status']; ?>"><span style="width:5px;height:5px;border-radius:50%;background:currentColor;"></span><?php echo ucfirst($v['status']); ?></span></td>
                <td><a href="#" class="btn-sf">Edit</a> <a href="#" class="btn-sf">Stats</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ═════════════════════════════════════════════════════════════════════ -->
<!-- CDN (RFI)                                                           -->
<!-- ═════════════════════════════════════════════════════════════════════ -->
<?php elseif ($action === 'cdn'): ?>
<div class="breadcrumb-sf">
    <a href="index.php?action=dashboard">Dashboard</a>
    <span class="sep">/</span>
    <span style="color:var(--sf-text-muted);">CDN Settings</span>
</div>
<div class="page-hdr">
    <div class="page-hdr-left">
        <h1>CDN Configuration</h1>
        <p>Manage content delivery network settings and origin assets</p>
    </div>
</div>

<div class="cols-2">
<div>
<div class="cdn-panel">
    <div class="cdn-panel-hdr">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
        <h4>CDN Origin Asset Proxy</h4>
    </div>
    <div class="cdn-panel-body">
        <div class="cdn-lbl">Asset URL</div>
        <form method="GET" action="index.php" id="cdnForm">
            <input type="hidden" name="action" value="cdn">
            <div class="cdn-row">
                <input type="text" name="load" class="cdn-inp" placeholder="https://origin.streamflux.io/assets/banner.png" value="<?php echo esc($proxy_url); ?>">
                <button type="submit" class="cdn-btn">Fetch</button>
            </div>
        </form>
        <div style="font-size:0.68rem;color:var(--sf-text-dim);line-height:1.5;margin-top:4px;">
            Enter the URL of a remote asset to validate it is accessible from our CDN edge nodes. Supports HTTP, HTTPS, and local origin paths.
        </div>
        <?php if (!empty($proxy_content)): ?>
        <div class="cdn-result">
            <div class="cdn-result-hdr">
                <span style="width:7px;height:7px;border-radius:50%;background:var(--sf-success);display:inline-block;"></span>
                Asset Retrieved — <span style="color:var(--sf-text-dim);font-weight:400;"><?php echo esc($proxy_url); ?></span>
            </div>
            <div class="cdn-result-body"><?php echo esc($proxy_content); ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<div>
<div class="cdn-info-box">
    <strong>About the Origin Proxy</strong><br>
    The CDN asset proxy validates that remote assets are reachable from our edge locations. It fetches the content and displays the raw response for debugging purposes.
    <div class="url-example">Example: https://origin.streamflux.io/assets/header.png</div>
    <strong>Common Issues</strong>
    <ul style="margin:6px 0 0;padding-left:16px;color:var(--sf-text-dim);font-size:0.76rem;">
        <li>Invalid or expired origin URLs</li>
        <li>SSL certificate mismatches</li>
        <li>Asset not found at specified path</li>
        <li>Local origin paths may be unavailable</li>
    </ul>
    <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--sf-border);font-size:0.72rem;">
        <span style="color:var(--sf-accent);">&#9679;</span> System Status: All edges healthy
    </div>
</div>
</div>
</div>

<!-- ═════════════════════════════════════════════════════════════════════ -->
<!-- ANALYTICS                                                           -->
<!-- ═════════════════════════════════════════════════════════════════════ -->
<?php elseif ($action === 'analytics'): ?>
<div class="breadcrumb-sf">
    <a href="index.php?action=dashboard">Dashboard</a>
    <span class="sep">/</span>
    <span style="color:var(--sf-text-muted);">Analytics</span>
</div>
<div class="page-hdr">
    <div class="page-hdr-left">
        <h1>Detailed Analytics</h1>
        <p>Per-video performance metrics</p>
    </div>
</div>
<?php
    $videos = get_videos($conn);
    $selected_id = isset($_GET['video_id']) ? intval($_GET['video_id']) : ($videos[0]['id'] ?? 0);
    $vid_data = $conn->query("SELECT * FROM lab1005_analytics WHERE video_id = $selected_id ORDER BY recorded_at ASC LIMIT 30")->fetch_all(MYSQLI_ASSOC);
    $vid_info = $conn->query("SELECT * FROM lab1005_videos WHERE id = $selected_id")->fetch_assoc();
    $max_a = 1; foreach ($vid_data as $d) { if ($d['daily_views'] > $max_a) $max_a = $d['daily_views']; }
    $ap = []; foreach ($vid_data as $i => $d) {
        $x = round($i / (max(count($vid_data)-1,1)) * 100, 1);
        $y = $max_a > 0 ? round(100 - ($d['daily_views'] / $max_a * 80)) : 100;
        $ap[] = "$x,$y";
    }
?>
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
    <?php foreach ($videos as $v): ?>
    <a href="index.php?action=analytics&video_id=<?php echo $v['id']; ?>" class="btn-sf <?php echo $v['id']==$selected_id?'btn-sfp':''; ?>"><?php echo esc($v['title']); ?></a>
    <?php endforeach; ?>
</div>
<div class="chart-wrap">
    <div class="chart-hdr">
        <h3><?php echo $vid_info ? esc($vid_info['title']) : 'No data'; ?></h3>
        <span style="font-size:0.72rem;color:var(--sf-text-dim);">Daily views</span>
    </div>
    <svg class="chart-svg" viewBox="0 0 600 140" preserveAspectRatio="none">
        <line class="chart-grid" x1="0" y1="140" x2="600" y2="140"/>
        <line class="chart-grid" x1="0" y1="105" x2="600" y2="105"/>
        <line class="chart-grid" x1="0" y1="70" x2="600" y2="70"/>
        <line class="chart-grid" x1="0" y1="35" x2="600" y2="35"/>
        <?php $aps = []; foreach ($vid_data as $i => $d) { $x = round($i / (max(count($vid_data)-1,1)) * 600); $y = $max_a > 0 ? round(140 - ($d['daily_views'] / $max_a * 120)) : 140; $aps[] = "$x,$y"; } $aps_str = implode(' ', $aps); ?>
        <path d="M0,140 L<?php echo $aps_str; ?> L600,140 Z" fill="rgba(20,184,166,0.06)"/>
        <path d="M<?php echo $aps_str; ?>" stroke="var(--sf-accent)"/>
    </svg>
    <div class="chart-labels">
        <span><?php echo count($vid_data) > 0 ? date('M j', strtotime($vid_data[0]['recorded_at'])) : ''; ?></span>
        <span><?php echo count($vid_data) > 0 ? date('M j', strtotime($vid_data[floor(count($vid_data)/2)]['recorded_at'])) : ''; ?></span>
        <span><?php echo count($vid_data) > 0 ? date('M j', strtotime($vid_data[count($vid_data)-1]['recorded_at'])) : ''; ?></span>
    </div>
</div>
<?php if ($vid_info): ?>
<div class="stats" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-bx">
        <span class="lbl" style="font-size:0.68rem;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--sf-text-dim);display:block;margin-bottom:4px;">Total Views</span>
        <span style="font-size:1.3rem;font-weight:700;color:#fff;"><?php echo number_format($vid_info['views']); ?></span>
    </div>
    <div class="stat-bx">
        <span class="lbl" style="font-size:0.68rem;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--sf-text-dim);display:block;margin-bottom:4px;">Duration</span>
        <span style="font-size:1.3rem;font-weight:700;color:#fff;"><?php echo format_duration($vid_info['duration']); ?></span>
    </div>
    <div class="stat-bx">
        <span class="lbl" style="font-size:0.68rem;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--sf-text-dim);display:block;margin-bottom:4px;">Status</span>
        <span style="font-size:1.3rem;font-weight:700;color:#fff;text-transform:capitalize;"><?php echo $vid_info['status']; ?></span>
    </div>
</div>
<?php endif; ?>

<!-- ═════════════════════════════════════════════════════════════════════ -->
<!-- SETTINGS                                                            -->
<!-- ═════════════════════════════════════════════════════════════════════ -->
<?php elseif ($action === 'settings'): ?>
<div class="breadcrumb-sf">
    <a href="index.php?action=dashboard">Dashboard</a>
    <span class="sep">/</span>
    <span style="color:var(--sf-text-muted);">Settings</span>
</div>
<div class="page-hdr">
    <div class="page-hdr-left">
        <h1>Settings</h1>
        <p>Manage your account and platform preferences</p>
    </div>
</div>
<div class="settings-card">
    <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" class="form-control" value="<?php echo esc($current_user['full_name']); ?>" disabled>
        <div class="form-text">Contact your administrator to update profile information.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-control" value="<?php echo esc($current_user['email']); ?>" disabled>
    </div>
    <div class="mb-3">
        <label class="form-label">Role</label>
        <input type="text" class="form-control" value="<?php echo esc(ucfirst($current_user['role'])); ?>" disabled>
    </div>
    <div class="mb-3">
        <label class="form-label">API Access Key</label>
        <input type="text" class="form-control" value="sf_api_<?php echo md5($current_user['email']); ?>" disabled>
        <div class="form-text">Used for StreamFlux API authentication.</div>
    </div>
    <hr style="border-color:var(--sf-border);margin:16px 0;">
    <p style="font-size:0.78rem;color:var(--sf-text-dim);">Platform version 3.2.1 · <a href="#" style="color:var(--sf-primary-light);">Documentation</a> · <a href="#" style="color:var(--sf-primary-light);">API Reference</a></p>
</div>

<?php endif; ?>

<footer class="footer-sf">
    StreamFlux v3.2.1 — Video Analytics Platform &copy; <?php echo date('Y'); ?>
</footer>

</main>
</div>
</body>
</html>