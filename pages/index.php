<?php
session_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// ── Self-bootstrapping: create includes dir and config files on first run ──
if (!is_dir(__DIR__ . '/includes')) {
    @mkdir(__DIR__ . '/includes', 0755, true);
}

$config_txt = __DIR__ . '/includes/app_config.txt';
if (!file_exists($config_txt)) {
    file_put_contents($config_txt, '; PageForge CMS — Application Configuration
; WARNING: This file contains sensitive credentials.
; In production, this should be outside the web root.
;
; CLASSIFICATION: INTERNAL — DO NOT EXPOSE PUBLICLY

;========================================================================
; Database Configuration
;========================================================================
DB_HOST     = localhost
DB_PORT     = 3306
DB_NAME     = VexiumCTF_DB
DB_USER     = pageforge_admin
DB_PASS     = PgF0rg3_S3cur3_DB!

;========================================================================
; Application Settings
;========================================================================
APP_NAME    = PageForge CMS
APP_VERSION = 2.4.1
APP_ENV     = production
DEBUG_MODE  = true
ALLOW_URL_INCLUDE = true

;========================================================================
; Security Configuration
;========================================================================
AUTH_SALT           = Xk9mN2pQ4rL8sT1vW5yA3bC6dE0fH7j
SESSION_TIMEOUT     = 3600
CSRF_PROTECTION     = false
UPLOAD_MAX_SIZE     = 100M

;========================================================================
; Secret Key (Internal Use Only)
;========================================================================
SECRET_KEY = flag{rfi_cms_config_disclosure_1003}

;========================================================================
; Cache Configuration
;========================================================================
CACHE_DRIVER = file
CACHE_TTL    = 3600
CACHE_PATH   = /tmp/pageforge_cache/
');
}

// ── Database configuration ──────────────────────────────────────────────────
$host = 'localhost';
$db   = 'VexiumCTF';
$user = 'root';
$pass = '';

$message = '';
$message_type = '';

// ── Connect to MySQL ────────────────────────────────────────────────────────
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// ── Create/seed tables ──────────────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS lab1003_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin','editor') DEFAULT 'editor',
    avatar VARCHAR(50) DEFAULT 'initials',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS lab1003_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    content TEXT,
    author_id INT NOT NULL,
    status ENUM('draft','published','archived') DEFAULT 'draft',
    template VARCHAR(50) DEFAULT 'default',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES lab1003_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Seed data ───────────────────────────────────────────────────────────────
$result = $conn->query("SELECT COUNT(*) AS cnt FROM lab1003_users");
$row = $result->fetch_assoc();
if ($row['cnt'] == 0) {
    $conn->query("INSERT INTO lab1003_users (email, password, full_name, role, avatar) VALUES
        ('admin@pageforge.io', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'Sarah Mitchell', 'admin', 'SM'),
        ('editor@pageforge.io', '" . password_hash('editor123', PASSWORD_DEFAULT) . "', 'James Rodriguez', 'editor', 'JR')
    ");
}

$result = $conn->query("SELECT COUNT(*) AS cnt FROM lab1003_pages");
$row = $result->fetch_assoc();
if ($row['cnt'] == 0) {
    $conn->query("INSERT INTO lab1003_pages (title, slug, content, author_id, status, template) VALUES
        ('Homepage', 'homepage', '<h2>Welcome to PageForge CMS</h2><p>This is the homepage content. PageForge is a powerful content management system designed for modern websites.</p><p>Our platform offers flexible page management, media libraries, and a user-friendly interface.</p>', 1, 'published', 'default'),
        ('About Us', 'about-us', '<h2>About Our Company</h2><p>Founded in 2020, we have been delivering exceptional digital experiences to our clients worldwide.</p><p>Our team of 50+ professionals specializes in web development, design, and content strategy.</p>', 1, 'published', 'default'),
        ('Contact', 'contact', '<h2>Get In Touch</h2><p>Have a project in mind? We would love to hear from you.</p><p>Email: contact@pageforge.io<br>Phone: +1 (555) 123-4567<br>Address: 123 Innovation Drive, Suite 400, San Francisco, CA 94105</p>', 2, 'published', 'default')
    ");
}

// ── Helper functions ────────────────────────────────────────────────────────
function esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function get_user($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM lab1003_users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// ── Authentication ──────────────────────────────────────────────────────────
$logged_in = false;
$current_user = null;

if (isset($_SESSION['lab1003_user_id'])) {
    $current_user = get_user($conn, $_SESSION['lab1003_user_id']);
    if ($current_user) {
        $logged_in = true;
    }
}

// ── Handle Logout ───────────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// ── Handle Login ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $stmt = $conn->prepare("SELECT * FROM lab1003_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['lab1003_user_id'] = $user['id'];
        $current_user = $user;
        $logged_in = true;
        $message = 'Login successful. Welcome back, ' . esc($user['full_name']) . '!';
        $message_type = 'success';
    } else {
        $message = 'Invalid email or password. Please try again.';
        $message_type = 'danger';
    }
}

// ── Determine action ────────────────────────────────────────────────────────
$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';

// ── Handle file loading via editor ─────────────────────────────────────
$loaded_content = '';
$load_error = '';

if ($logged_in && isset($_GET['load']) && $action === 'edit') {
    $load_target = $_GET['load'];
    
    if (!empty($load_target)) {
        ob_start();
        $included = @include($load_target);
        $loaded_content = ob_get_clean();
        
        if ($included === false && empty($loaded_content)) {
            $load_error = 'Failed to load content from: ' . esc($load_target);
            $loaded_content = '';
        }
    }
}

// ── If not logged in, show login ────────────────────────────────────────────
if (!$logged_in) {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PageForge CMS — Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
<style>
:root {
    --pf-primary: #6366f1;
    --pf-primary-dark: #4f46e5;
    --pf-primary-light: #818cf8;
    --pf-bg: #0f172a;
    --pf-card-bg: #1e293b;
    --pf-border: #334155;
    --pf-text: #e2e8f0;
    --pf-text-muted: #94a3b8;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    color: var(--pf-text);
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.login-wrapper {
    width: 100%;
    max-width: 420px;
    padding: 20px;
}

.login-header {
    text-align: center;
    margin-bottom: 32px;
}

.logo-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, var(--pf-primary), var(--pf-primary-light));
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
}

.logo-icon svg {
    width: 28px;
    height: 28px;
    fill: #fff;
}

.login-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 4px;
}

.login-header p {
    font-size: 0.875rem;
    color: var(--pf-text-muted);
}

.login-card {
    background: var(--pf-card-bg);
    border: 1px solid var(--pf-border);
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.3);
}

.form-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--pf-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
}

.form-control {
    background: #0f172a;
    border: 1px solid var(--pf-border);
    color: var(--pf-text);
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 0.9rem;
}

.form-control:focus {
    background: #0f172a;
    border-color: var(--pf-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    color: var(--pf-text);
}

.form-control::placeholder {
    color: #475569;
}

.btn-login {
    background: linear-gradient(135deg, var(--pf-primary), var(--pf-primary-dark));
    border: none;
    color: #fff;
    padding: 11px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    width: 100%;
    transition: all 0.2s;
    cursor: pointer;
}

.btn-login:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
}

.login-footer {
    text-align: center;
    margin-top: 20px;
    font-size: 0.8rem;
    color: var(--pf-text-muted);
}

.login-footer a {
    color: var(--pf-primary-light);
    text-decoration: none;
}

.login-footer a:hover {
    text-decoration: underline;
}

.alert {
    border-radius: 10px;
    border: none;
    font-size: 0.85rem;
}
</style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-header">
        <div class="logo-icon">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
        </div>
        <h1>PageForge CMS</h1>
        <p>Sign in to manage your content</p>
    </div>

    <div class="login-card">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> mb-3"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
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

    <div class="login-footer">
    </div>
</div>
</body>
</html>
    <?php
    exit;
}

// ── Page data helpers ───────────────────────────────────────────────────────
function get_pages($conn, $author_id = null) {
    if ($author_id) {
        $stmt = $conn->prepare("SELECT p.*, u.full_name AS author_name FROM lab1003_pages p JOIN lab1003_users u ON p.author_id = u.id WHERE p.author_id = ? ORDER BY p.updated_at DESC");
        $stmt->bind_param("i", $author_id);
    } else {
        $stmt = $conn->prepare("SELECT p.*, u.full_name AS author_name FROM lab1003_pages p JOIN lab1003_users u ON p.author_id = u.id ORDER BY p.updated_at DESC");
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_page($conn, $id) {
    $stmt = $conn->prepare("SELECT p.*, u.full_name AS author_name FROM lab1003_pages p JOIN lab1003_users u ON p.author_id = u.id WHERE p.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$current_page_id = isset($_GET['page']) ? (int)$_GET['page'] : 0;

// ── Render the admin panel ──────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PageForge CMS — Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
<style>
:root {
    --pf-primary: #6366f1;
    --pf-primary-dark: #4f46e5;
    --pf-primary-light: #818cf8;
    --pf-accent: #06b6d4;
    --pf-bg: #0f172a;
    --pf-sidebar: #1e293b;
    --pf-card: #1e293b;
    --pf-border: #334155;
    --pf-text: #e2e8f0;
    --pf-text-muted: #94a3b8;
    --pf-text-dim: #64748b;
    --pf-success: #10b981;
    --pf-warning: #f59e0b;
    --pf-danger: #ef4444;
    --pf-hover: #334155;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    background: var(--pf-bg);
    color: var(--pf-text);
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    min-height: 100vh;
    display: flex;
}

/* ── Sidebar ────────────────────────────────────────────────────────────── */
.sidebar {
    width: 260px;
    background: var(--pf-sidebar);
    border-right: 1px solid var(--pf-border);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    height: 100vh;
    position: sticky;
    top: 0;
}

.sidebar-brand {
    padding: 20px 20px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid var(--pf-border);
}

.sidebar-brand-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--pf-primary), var(--pf-primary-light));
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.sidebar-brand-icon svg {
    width: 18px;
    height: 18px;
    fill: #fff;
}

.sidebar-brand-text {
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    line-height: 1.2;
}

.sidebar-brand-sub {
    font-size: 0.65rem;
    color: var(--pf-text-muted);
    font-weight: 500;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.sidebar-nav {
    flex: 1;
    padding: 12px 10px;
}

.nav-section {
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--pf-text-dim);
    padding: 16px 12px 6px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 8px;
    color: var(--pf-text-muted);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.15s;
    cursor: pointer;
}

.nav-item:hover {
    background: var(--pf-hover);
    color: var(--pf-text);
}

.nav-item.active {
    background: rgba(99, 102, 241, 0.12);
    color: var(--pf-primary-light);
}

.nav-item svg {
    width: 18px;
    height: 18px;
    fill: currentColor;
    flex-shrink: 0;
}

.nav-item .badge-count {
    margin-left: auto;
    background: var(--pf-border);
    color: var(--pf-text-muted);
    font-size: 0.7rem;
    padding: 1px 8px;
    border-radius: 10px;
    font-weight: 600;
}

.nav-item.active .badge-count {
    background: rgba(99, 102, 241, 0.25);
    color: var(--pf-primary-light);
}

.sidebar-footer {
    padding: 14px 16px;
    border-top: 1px solid var(--pf-border);
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.75rem;
    flex-shrink: 0;
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--pf-text);
    line-height: 1.2;
}

.user-role {
    font-size: 0.7rem;
    color: var(--pf-text-dim);
    text-transform: capitalize;
}

.user-logout {
    color: var(--pf-text-dim);
    text-decoration: none;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.15s;
}

.user-logout:hover {
    color: var(--pf-danger);
    background: rgba(239, 68, 68, 0.1);
}

/* ── Main content ──────────────────────────────────────────────────────── */
.main-content {
    flex: 1;
    min-width: 0;
    padding: 24px 32px 32px;
    max-width: calc(100% - 260px);
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
}

.page-header h1 {
    font-size: 1.4rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}

.page-header p {
    font-size: 0.85rem;
    color: var(--pf-text-muted);
    margin: 2px 0 0;
}

.breadcrumb-custom {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: var(--pf-text-dim);
    margin-bottom: 16px;
}

.breadcrumb-custom a {
    color: var(--pf-text-muted);
    text-decoration: none;
}

.breadcrumb-custom a:hover {
    color: var(--pf-primary-light);
}

.breadcrumb-custom .sep {
    color: var(--pf-border);
}

/* ── Stat cards ─────────────────────────────────────────────────────────── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: var(--pf-card);
    border: 1px solid var(--pf-border);
    border-radius: 12px;
    padding: 18px 20px;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
}

.stat-icon svg { width: 20px; height: 20px; fill: #fff; }

.stat-value {
    font-size: 1.6rem;
    font-weight: 700;
    color: #fff;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.78rem;
    color: var(--pf-text-muted);
    margin-top: 2px;
}

/* ── Page list table ────────────────────────────────────────────────────── */
.table-wrap {
    background: var(--pf-card);
    border: 1px solid var(--pf-border);
    border-radius: 12px;
    overflow: hidden;
}

.table-wrap .table-header {
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--pf-border);
}

.table-header h3 {
    font-size: 0.95rem;
    font-weight: 700;
    margin: 0;
}

.page-table {
    width: 100%;
    border-collapse: collapse;
}

.page-table th {
    text-align: left;
    padding: 11px 20px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--pf-text-dim);
    background: rgba(15, 23, 42, 0.4);
    border-bottom: 1px solid var(--pf-border);
}

.page-table td {
    padding: 13px 20px;
    font-size: 0.85rem;
    border-bottom: 1px solid rgba(51, 65, 85, 0.5);
    vertical-align: middle;
}

.page-table tr:last-child td {
    border-bottom: none;
}

.page-table tr:hover td {
    background: rgba(99, 102, 241, 0.03);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
}

.status-badge.published {
    background: rgba(16, 185, 129, 0.12);
    color: var(--pf-success);
}

.status-badge.draft {
    background: rgba(245, 158, 11, 0.12);
    color: var(--pf-warning);
}

.status-badge.archived {
    background: rgba(100, 116, 139, 0.12);
    color: var(--pf-text-dim);
}

.btn-sm-custom {
    padding: 5px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 6px;
    border: 1px solid var(--pf-border);
    background: transparent;
    color: var(--pf-text-muted);
    text-decoration: none;
    transition: all 0.15s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.btn-sm-custom:hover {
    background: var(--pf-hover);
    color: var(--pf-text);
}

.btn-sm-primary {
    background: rgba(99, 102, 241, 0.1);
    border-color: rgba(99, 102, 241, 0.2);
    color: var(--pf-primary-light);
}

.btn-sm-primary:hover {
    background: rgba(99, 102, 241, 0.2);
    color: var(--pf-primary-light);
}

/* ── Page Editor ────────────────────────────────────────────────────────── */
.editor-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 20px;
    align-items: start;
}

.editor-main {
    background: var(--pf-card);
    border: 1px solid var(--pf-border);
    border-radius: 12px;
    overflow: hidden;
}

.editor-toolbar {
    padding: 12px 20px;
    border-bottom: 1px solid var(--pf-border);
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    background: rgba(15, 23, 42, 0.4);
}

.editor-toolbar .toolbar-btn {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 1px solid var(--pf-border);
    background: transparent;
    color: var(--pf-text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
}

.editor-toolbar .toolbar-btn:hover {
    background: var(--pf-hover);
    color: var(--pf-text);
}

.editor-toolbar .toolbar-btn svg { width: 15px; height: 15px; fill: currentColor; }

.editor-toolbar .toolbar-sep {
    width: 1px;
    height: 22px;
    background: var(--pf-border);
    margin: 0 4px;
}

.editor-body {
    padding: 24px 20px;
}

.editor-title-input {
    width: 100%;
    background: transparent;
    border: none;
    border-bottom: 2px solid var(--pf-border);
    color: #fff;
    font-size: 1.3rem;
    font-weight: 700;
    padding: 8px 0 10px;
    outline: none;
    margin-bottom: 16px;
}

.editor-title-input:focus {
    border-bottom-color: var(--pf-primary);
}

.editor-content-area {
    min-height: 260px;
    background: #0f172a;
    border: 1px solid var(--pf-border);
    border-radius: 8px;
    padding: 16px;
    color: var(--pf-text);
    font-size: 0.88rem;
    line-height: 1.7;
    outline: none;
    width: 100%;
    font-family: 'Segoe UI', system-ui, sans-serif;
}

/* ── RFI panel (sidebar) ───────────────────────────────────────────────── */
.rfi-panel {
    background: var(--pf-card);
    border: 1px solid var(--pf-border);
    border-radius: 12px;
    overflow: hidden;
    border-top: 3px solid var(--pf-danger);
}

.rfi-panel-header {
    padding: 14px 18px;
    background: rgba(239, 68, 68, 0.05);
    border-bottom: 1px solid var(--pf-border);
    display: flex;
    align-items: center;
    gap: 10px;
}

.rfi-panel-header svg { width: 18px; height: 18px; fill: var(--pf-danger); flex-shrink: 0; }

.rfi-panel-header h4 {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--pf-text);
    margin: 0;
}

.rfi-panel-body {
    padding: 16px 18px;
}

.rfi-label {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--pf-text-dim);
    margin-bottom: 6px;
}

.rfi-url-row {
    display: flex;
    gap: 0;
    margin-bottom: 8px;
}

.rfi-input {
    flex: 1;
    background: #0f172a;
    border: 1px solid var(--pf-border);
    border-right: none;
    border-radius: 8px 0 0 8px;
    padding: 9px 12px;
    color: var(--pf-text);
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 0.78rem;
    outline: none;
}

.rfi-input:focus {
    border-color: var(--pf-primary);
}

.rfi-btn-load {
    background: var(--pf-danger);
    border: 1px solid var(--pf-danger);
    border-radius: 0 8px 8px 0;
    color: #fff;
    padding: 9px 16px;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}

.rfi-btn-load:hover {
    background: #dc2626;
}

.rfi-result {
    margin-top: 12px;
    background: #0f172a;
    border: 1px solid var(--pf-border);
    border-radius: 8px;
    overflow: hidden;
}

.rfi-result-header {
    padding: 8px 12px;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--pf-text-muted);
    background: rgba(15, 23, 42, 0.5);
    border-bottom: 1px solid var(--pf-border);
    display: flex;
    align-items: center;
    gap: 6px;
}

.rfi-result-header .status-dot {
    width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
}

.rfi-result-body {
    padding: 12px;
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 0.75rem;
    color: #e2e8f0;
    line-height: 1.6;
    max-height: 300px;
    overflow: auto;
    white-space: pre-wrap;
    word-break: break-all;
}

.rfi-error {
    padding: 12px;
    font-size: 0.78rem;
    color: var(--pf-danger);
    background: rgba(239, 68, 68, 0.05);
}

/* ── Responsive ─────────────────────────────────────────────────────────── */
@media (max-width: 1024px) {
    .editor-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 60px;
    }
    .sidebar-brand-text,
    .sidebar-brand-sub,
    .nav-section,
    .nav-item span,
    .nav-item .badge-count,
    .user-info,
    .user-logout {
        display: none;
    }
    .sidebar-brand {
        padding: 16px 12px;
        justify-content: center;
    }
    .sidebar-nav {
        padding: 8px 6px;
    }
    .nav-item {
        justify-content: center;
        padding: 10px;
    }
    .sidebar-footer {
        justify-content: center;
        padding: 10px;
    }
    .main-content {
        padding: 16px;
        max-width: calc(100% - 60px);
    }
}
</style>
</head>
<body>

<!-- ── Sidebar ──────────────────────────────────────────────────────────── -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
        </div>
        <div>
            <div class="sidebar-brand-text">PageForge</div>
            <div class="sidebar-brand-sub">Content Manager</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Main</div>
        <a href="index.php?action=dashboard" class="nav-item <?php echo $action === 'dashboard' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>Dashboard</span>
        </a>
        <a href="index.php?action=pages" class="nav-item <?php echo $action === 'pages' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span>Pages</span>
            <?php 
            $all_pages = get_pages($conn);
            if (count($all_pages) > 0): ?>
            <span class="badge-count"><?php echo count($all_pages); ?></span>
            <?php endif; ?>
        </a>
        <a href="index.php?action=edit&page=1" class="nav-item <?php echo $action === 'edit' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <span>Page Editor</span>
        </a>
        <div class="nav-section">Settings</div>
        <a href="#" class="nav-item">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.32 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            <span>Settings</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-avatar" style="background:linear-gradient(135deg,<?php echo $current_user['role']==='admin'?'#6366f1,#4f46e5':'#10b981,#059669'; ?>);color:#fff;">
            <?php echo esc(substr($current_user['full_name'], 0, 1) . substr(explode(' ', $current_user['full_name'])[1]??'', 0, 1)); ?>
        </div>
        <div class="user-info">
            <div class="user-name"><?php echo esc($current_user['full_name']); ?></div>
            <div class="user-role"><?php echo esc($current_user['role']); ?></div>
        </div>
        <a href="index.php?action=logout" class="user-logout" title="Sign Out">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        </a>
    </div>
</aside>

<!-- ── Main Content ─────────────────────────────────────────────────────── -->
<main class="main-content">

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-3" style="border-radius:10px;border:none;">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($load_error): ?>
    <div class="alert alert-danger" style="border-radius:10px;border:none;font-size:0.85rem;">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $load_error; ?>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- DASHBOARD                                                         -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <?php if ($action === 'dashboard'): 
        $pages = get_pages($conn);
        $pub_count = 0; $draft_count = 0;
        foreach ($pages as $p) {
            if ($p['status'] === 'published') $pub_count++;
            else $draft_count++;
        }
    ?>
    <div class="breadcrumb-custom">
        <a href="index.php?action=dashboard">Dashboard</a>
    </div>

    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p>Welcome back, <?php echo esc($current_user['full_name']); ?>. Here's your content overview.</p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="stat-value"><?php echo count($pages); ?></div>
            <div class="stat-label">Total Pages</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-value"><?php echo $pub_count; ?></div>
            <div class="stat-label">Published</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                <svg viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </div>
            <div class="stat-value"><?php echo $draft_count; ?></div>
            <div class="stat-label">Drafts</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#06b6d4,#0891b2);">
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            </div>
            <div class="stat-value">2</div>
            <div class="stat-label">Authors</div>
        </div>
    </div>

    <div class="table-wrap">
        <div class="table-header">
            <h3>Recent Pages</h3>
            <a href="index.php?action=pages" class="btn-sm-custom btn-sm-primary">
                <i class="bi bi-eye"></i> View All
            </a>
        </div>
        <table class="page-table">
            <thead>
                <tr>
                    <th style="width:40%;">Title</th>
                    <th style="width:15%;">Status</th>
                    <th style="width:15%;">Author</th>
                    <th style="width:20%;">Updated</th>
                    <th style="width:10%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                <tr>
                    <td><strong><?php echo esc($p['title']); ?></strong></td>
                    <td><span class="status-badge <?php echo $p['status']; ?>"><span style="width:6px;height:6px;border-radius:50%;background:currentColor;"></span><?php echo ucfirst($p['status']); ?></span></td>
                    <td style="color:var(--pf-text-muted);"><?php echo esc($p['author_name']); ?></td>
                    <td style="color:var(--pf-text-muted);font-size:0.82rem;"><?php echo date('M j, Y g:i A', strtotime($p['updated_at'])); ?></td>
                    <td><a href="index.php?action=edit&page=<?php echo $p['id']; ?>" class="btn-sm-custom btn-sm-primary"><i class="bi bi-pencil"></i> Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PAGES LIST                                                        -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <?php elseif ($action === 'pages'):
        $pages = get_pages($conn);
    ?>
    <div class="breadcrumb-custom">
        <a href="index.php?action=dashboard">Dashboard</a>
        <span class="sep">/</span>
        <span style="color:var(--pf-text-muted);">Pages</span>
    </div>

    <div class="page-header">
        <div>
            <h1>All Pages</h1>
            <p>Manage your website content</p>
        </div>
        <a href="index.php?action=edit&page=1" class="btn-sm-custom btn-sm-primary" style="padding:8px 18px;">
            <i class="bi bi-plus-lg"></i> Edit Page
        </a>
    </div>

    <div class="table-wrap">
        <table class="page-table">
            <thead>
                <tr>
                    <th style="width:35%;">Title</th>
                    <th style="width:10%;">Status</th>
                    <th style="width:12%;">Template</th>
                    <th style="width:13%;">Author</th>
                    <th style="width:18%;">Updated</th>
                    <th style="width:12%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                <tr>
                    <td>
                        <strong><?php echo esc($p['title']); ?></strong>
                        <div style="font-size:0.72rem;color:var(--pf-text-dim);">/<?php echo esc($p['slug']); ?></div>
                    </td>
                    <td><span class="status-badge <?php echo $p['status']; ?>"><span style="width:6px;height:6px;border-radius:50%;background:currentColor;"></span><?php echo ucfirst($p['status']); ?></span></td>
                    <td style="color:var(--pf-text-muted);font-size:0.8rem;"><?php echo esc($p['template']); ?></td>
                    <td style="color:var(--pf-text-muted);"><?php echo esc($p['author_name']); ?></td>
                    <td style="color:var(--pf-text-muted);font-size:0.82rem;"><?php echo date('M j, Y g:i A', strtotime($p['updated_at'])); ?></td>
                    <td><a href="index.php?action=edit&page=<?php echo $p['id']; ?>" class="btn-sm-custom btn-sm-primary"><i class="bi bi-pencil"></i> Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PAGE EDITOR + RFI                                                  -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <?php elseif ($action === 'edit'):
        $page = get_page($conn, $current_page_id);
        if (!$page) {
            $page = get_page($conn, 1);
            $current_page_id = 1;
        }
        $pages_list = get_pages($conn);
    ?>
    <div class="breadcrumb-custom">
        <a href="index.php?action=dashboard">Dashboard</a>
        <span class="sep">/</span>
        <a href="index.php?action=pages">Pages</a>
        <span class="sep">/</span>
        <span style="color:var(--pf-text-muted);">Edit: <?php echo esc($page['title']); ?></span>
    </div>

    <div class="page-header">
        <div>
            <h1>Edit Page</h1>
            <p>Editing: <strong><?php echo esc($page['title']); ?></strong> — Last modified by <?php echo esc($page['author_name']); ?></p>
        </div>
        <div style="display:flex;gap:8px;">
            <select class="form-select" style="width:auto;background:var(--pf-card);border-color:var(--pf-border);color:var(--pf-text);font-size:0.8rem;padding:6px 30px 6px 12px;border-radius:8px;">
                <option value="1" <?php echo $current_page_id==1?'selected':''; ?>>Page: Homepage</option>
                <option value="2" <?php echo $current_page_id==2?'selected':''; ?>>Page: About Us</option>
                <option value="3" <?php echo $current_page_id==3?'selected':''; ?>>Page: Contact</option>
            </select>
        </div>
    </div>

    <div class="editor-layout">
        <!-- Left: Editor -->
        <div class="editor-main">
            <div class="editor-toolbar">
                <button class="toolbar-btn" title="Bold"><svg viewBox="0 0 24 24"><path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6zM6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/></svg></button>
                <button class="toolbar-btn" title="Italic"><svg viewBox="0 0 24 24"><path d="M19 4h-9M14 20H5M15 4L9 20"/></svg></button>
                <button class="toolbar-btn" title="Underline"><svg viewBox="0 0 24 24"><path d="M6 3v7a6 6 0 006 6 6 6 0 006-6V3M4 21h16"/></svg></button>
                <div class="toolbar-sep"></div>
                <button class="toolbar-btn" title="Heading"><svg viewBox="0 0 24 24"><path d="M6 12h12M6 4v16M18 4v16"/></svg></button>
                <button class="toolbar-btn" title="List"><svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></button>
                <button class="toolbar-btn" title="Link"><svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg></button>
                <div class="toolbar-sep"></div>
                <button class="toolbar-btn" title="Source"><svg viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg></button>
            </div>

            <div class="editor-body">
                <input type="text" class="editor-title-input" value="<?php echo esc($page['title']); ?>" readonly>
                <div class="editor-content-area" contenteditable="false">
                    <?php echo $page['content']; ?>
                </div>
                <div style="margin-top:16px;display:flex;gap:8px;justify-content:flex-end;">
                    <button class="btn-sm-custom" style="padding:8px 20px;">Save Draft</button>
                    <button class="btn-sm-custom btn-sm-primary" style="padding:8px 20px;">Publish</button>
                </div>
            </div>
        </div>

        <!-- Right: RFI Panel -->
        <div>
            <div class="rfi-panel">
                <div class="rfi-panel-header">
                    <svg viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <h4>Load External Content</h4>
                </div>
                <div class="rfi-panel-body">
                    <div class="rfi-label">Source URL</div>
                    <form method="GET" action="index.php" id="rfiForm">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="page" value="<?php echo $current_page_id; ?>">
                        <div class="rfi-url-row">
                            <input type="text" name="load" class="rfi-input" placeholder="https://example.com/content.html" value="<?php echo isset($_GET['load']) ? esc($_GET['load']) : ''; ?>">
                            <button type="submit" class="rfi-btn-load">Load</button>
                        </div>
                    </form>

                    <?php if (!empty($loaded_content)): ?>
                    <div class="rfi-result">
                        <div class="rfi-result-header">
                            <span class="status-dot" style="background:var(--pf-success);"></span>
                            Content Loaded
                        </div>
                        <div class="rfi-result-body"><?php echo $loaded_content; ?></div>
                    </div>
                    <?php elseif ($load_error): ?>
                    <div class="rfi-result">
                        <div class="rfi-result-header">
                            <span class="status-dot" style="background:var(--pf-danger);"></span>
                            Load Failed
                        </div>
                        <div class="rfi-error"><?php echo $load_error; ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Page Info Panel -->
            <div style="background:var(--pf-card);border:1px solid var(--pf-border);border-radius:12px;margin-top:16px;overflow:hidden;">
                <div style="padding:14px 18px;border-bottom:1px solid var(--pf-border);font-size:0.85rem;font-weight:700;">Page Information</div>
                <div style="padding:14px 18px;font-size:0.8rem;">
                    <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid rgba(51,65,85,0.3);">
                        <span style="color:var(--pf-text-dim);">Status</span>
                        <span class="status-badge <?php echo $page['status']; ?>" style="font-size:0.68rem;"><span style="width:5px;height:5px;border-radius:50%;background:currentColor;"></span><?php echo ucfirst($page['status']); ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid rgba(51,65,85,0.3);">
                        <span style="color:var(--pf-text-dim);">Author</span>
                        <span><?php echo esc($page['author_name']); ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid rgba(51,65,85,0.3);">
                        <span style="color:var(--pf-text-dim);">Template</span>
                        <span><?php echo esc($page['template']); ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid rgba(51,65,85,0.3);">
                        <span style="color:var(--pf-text-dim);">Slug</span>
                        <span style="font-family:monospace;font-size:0.75rem;">/<?php echo esc($page['slug']); ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:5px 0;">
                        <span style="color:var(--pf-text-dim);">Created</span>
                        <span style="font-size:0.75rem;"><?php echo date('M j, Y', strtotime($page['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <footer style="margin-top:32px;padding-top:16px;border-top:1px solid var(--pf-border);font-size:0.75rem;color:var(--pf-text-dim);display:flex;justify-content:space-between;">
        <span>PageForge CMS v2.4.1</span>
    </footer>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Page switcher
document.querySelector('select')?.addEventListener('change', function() {
    window.location.href = 'index.php?action=edit&page=' + this.value;
});
</script>
</body>
</html>
