<?php
// ============================================================
// Database Configuration
// ============================================================
$dbname = 'KrazePlanet';
$username = 'root';
$password = '';
$hosts = ['krazeplanet', '127.0.0.1', 'localhost', '172.19.0.1', 'host.docker.internal'];

// Table Names Configuration
$table_users = 'hackerone_users';
$table_reports = 'hackerone_reports';


$pdo = null;
$lastException = null;
foreach ($hosts as $h) {
    try {
        $pdo = new PDO("mysql:host=$h;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8 COLLATE utf8_general_ci");
        $pdo->exec("USE `$dbname`");
        break;
    } catch(PDOException $e) {
        $lastException = $e;
    }
}
if (!$pdo) {
    die("Connection failed: " . ($lastException ? $lastException->getMessage() : "Unknown error"));
}

// Initialize database tables
function initializeDatabase($pdo) {
    global $table_users, $table_reports;
    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$table_users} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            role ENUM('user', 'admin') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Vulnerability reports table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$table_reports} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            program VARCHAR(100) DEFAULT 'Matomo',
            description TEXT NOT NULL,
            impact TEXT NOT NULL,
            severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            status ENUM('open', 'needs_more_info', 'pending_bounty', 'resolved', 'closed') DEFAULT 'open',
            bounty_amount DECIMAL(10,2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Insert default admin triage user if none exists
    $checkAdmin = $pdo->prepare("SELECT COUNT(*) FROM {$table_users} WHERE role = 'admin'");
    $checkAdmin->execute();
    if ($checkAdmin->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO {$table_users} (username, email, password, full_name, role) VALUES (?, ?, ?, 'HackerOne Triage', 'admin')");
        $stmt->execute([
            'admin',
            'triage@hackerone.local',
            password_hash('admin123', PASSWORD_DEFAULT)
        ]);
    }

    // Seed default reports if none exist
    $checkReports = $pdo->query("SELECT COUNT(*) FROM {$table_reports}");
    if ($checkReports->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO {$table_reports} (user_id, title, program, description, impact, severity, status, bounty_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            1,
            'Reflected Cross-Site Scripting (XSS) in Dashboard Search Query',
            'Matomo',
            'The search component within the dashboard outputs the user-provided \'q\' parameter directly without sanitization, allowing execution of script payloads.',
            'An attacker can hijack user sessions and execute unauthorized API calls.',
            'medium',
            'open',
            250.00
        ]);
        $stmt->execute([
            1,
            'SQL Injection (SQLi) in Product Catalog Endpoint',
            'Dell',
            'The product detail catalog queries do not bind input variables, allowing execution of arbitrary SQL commands via the \'id\' parameter.',
            'Unauthorized disclosure of product inventory database records.',
            'high',
            'pending_bounty',
            1000.00
        ]);
        $stmt->execute([
            1,
            'Insecure Direct Object Reference (IDOR) on AstroData API',
            'NASA',
            'The user AstroData profile retrieval endpoints do not validate if the requesting account owns the requested astro-id parameter.',
            'Unauthorized reading of metadata profiles of other researchers.',
            'medium',
            'open',
            0.00
        ]);
        $stmt->execute([
            1,
            'Server-Side Request Forgery (SSRF) in Document Parser Service',
            'DOD',
            'The document conversion portal accepts arbitrary remote URLs and downloads contents from within the internal network segment.',
            'Accessing internal service ports and scanning internal topology.',
            'critical',
            'resolved',
            0.00
        ]);
    }
}
initializeDatabase($pdo);

// Session management
session_start();

// Authentication Helpers
function getCurrentUser($pdo) {
    global $table_users;
    if (!isset($_SESSION['h1_user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM {$table_users} WHERE id = ?");
    $stmt->execute([$_SESSION['h1_user_id']]);
    $u = $stmt->fetch();
    if (!$u) {
        unset($_SESSION['h1_user_id']);
        unset($_SESSION['h1_username']);
        unset($_SESSION['h1_role']);
        return null;
    }
    return $u;
}

function loginUser($pdo, $username, $password) {
    global $table_users;
    $stmt = $pdo->prepare("SELECT * FROM {$table_users} WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['h1_user_id'] = $user['id'];
        $_SESSION['h1_username'] = $user['username'];
        $_SESSION['h1_role'] = $user['role'];
        return true;
    }
    return false;
}

function logoutUser() {
    unset($_SESSION['h1_user_id']);
    unset($_SESSION['h1_username']);
    unset($_SESSION['h1_role']);
    header("Location: index.php");
    exit();
}

// --- Logout ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
}

// --- Register ---
$authError = ''; $authSuccess = '';
if (!isset($_SESSION['h1_user_id']) && isset($_POST['register'])) {
    $u  = trim($_POST['reg_username']  ?? '');
    $e  = trim($_POST['reg_email']     ?? '');
    $p  =      $_POST['reg_password']  ?? '';
    $c  =      $_POST['reg_confirm']   ?? '';
    $fn = trim($_POST['reg_full_name'] ?? '');
    if (empty($u) || empty($e) || empty($p)) {
        $authError = 'Username, email and password are required';
    } elseif ($p !== $c) {
        $authError = 'Passwords do not match';
    } elseif (strlen($p) < 6) {
        $authError = 'Password must be at least 6 characters';
    } else {
        try {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM {$table_users} WHERE username = ? OR email = ?");
            $chk->execute([$u, $e]);
            if ($chk->fetchColumn()) {
                $authError = 'Username or email already exists';
            } else {
                $ins = $pdo->prepare("INSERT INTO {$table_users} (username, email, password, full_name) VALUES (?, ?, ?, ?)");
                $ins->execute([$u, $e, password_hash($p, PASSWORD_DEFAULT), $fn]);
                $authSuccess = 'Account created! You can now login.';
            }
        } catch (PDOException $ex) { $authError = 'Registration failed: ' . $ex->getMessage(); }
    }
}

// --- Login ---
if (!isset($_SESSION['h1_user_id']) && isset($_POST['login'])) {
    if (loginUser($pdo, $_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: index.php'); exit();
    } else {
        $authError = 'Invalid username or password';
    }
}

// --- Auth Gate ---
if (!isset($_SESSION['h1_user_id'])) {
    $authView = $_GET['view'] ?? 'login';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HackerOne - Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #0b0a12;
      color: #f1f0f5;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .auth-box {
      background-color: #131222;
      border: 1px solid #23223f;
      border-radius: 8px;
      padding: 2.5rem;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.4);
    }
    .h1-logo {
      color: #ffffff;
      font-weight: 800;
      font-size: 2.2rem;
      text-align: center;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.65rem;
    }
    .h1-logo span {
      background-color: #000000;
      color: #ffffff;
      padding: 2px 10px;
      border-radius: 4px;
      font-size: 1.8rem;
    }
    .auth-sub {
      text-align: center;
      font-size: 0.85rem;
      color: #8f8ea8;
      margin-bottom: 2rem;
    }
    .form-control {
      background-color: #0e0d16;
      border: 1px solid #2c2b48;
      color: #f1f0f5;
      padding: 0.65rem 0.75rem;
      border-radius: 4px;
    }
    .form-control:focus {
      background-color: #0e0d16;
      border-color: #ff2a74;
      box-shadow: 0 0 0 3px rgba(255,42,116,0.15);
      color: #f1f0f5;
    }
    .form-label {
      font-weight: 500;
      color: #cfcee0;
      font-size: 0.875rem;
    }
    .btn-h1 {
      background: linear-gradient(90deg, #ff2a74 0%, #ff523b 100%);
      border: none;
      color: white;
      font-weight: 600;
      padding: 0.75rem;
      border-radius: 4px;
      width: 100%;
      transition: opacity 0.2s;
    }
    .btn-h1:hover {
      opacity: 0.95;
    }
    .alert-danger {
      background-color: rgba(239,68,68,0.1);
      border: 1px solid rgba(239,68,68,0.4);
      color: #fca5a5;
    }
    .alert-success {
      background-color: rgba(34,197,94,0.1);
      border: 1px solid rgba(34,197,94,0.4);
      color: #86efac;
    }
    .demo-creds {
      background-color: #0e0d16;
      border: 1px solid #23223f;
      border-radius: 4px;
      padding: 0.85rem;
      font-size: 0.8rem;
      color: #8f8ea8;
      margin-top: 1rem;
    }
    .switch-link {
      text-align: center;
      margin-top: 1.25rem;
      font-size: 0.85rem;
    }
    .switch-link a {
      color: #ff2a74;
      text-decoration: none;
    }
  </style>
</head>
<body>
<div class="auth-box">
  <div class="h1-logo"><span>h1</span> hackerone</div>
  <div class="auth-sub">Bug Bounty Platform Console</div>

  <?php if ($authError): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($authError); ?></div>
  <?php endif; ?>
  <?php if ($authSuccess): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($authSuccess); ?></div>
  <?php endif; ?>

  <?php if ($authView === 'register'): ?>
  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Username *</label>
      <input type="text" class="form-control" name="reg_username" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email Address *</label>
      <input type="email" class="form-control" name="reg_email" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Full Name</label>
      <input type="text" class="form-control" name="reg_full_name">
    </div>
    <div class="mb-3">
      <label class="form-label">Password *</label>
      <input type="password" class="form-control" name="reg_password" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Confirm Password *</label>
      <input type="password" class="form-control" name="reg_confirm" required>
    </div>
    <button type="submit" name="register" class="btn btn-h1">Create Hunter Account</button>
    <div class="switch-link"><a href="index.php">Log in to your account</a></div>
  </form>
  <?php else: ?>
  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Email or Username</label>
      <input type="text" class="form-control" name="username" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" class="form-control" name="password" required>
    </div>
    <button type="submit" name="login" class="btn btn-h1">Log In</button>
    <div class="demo-creds text-center">
      <strong>Triage Team Account:</strong> admin / admin123
    </div>
    <div class="switch-link"><a href="index.php?view=register">New to HackerOne? Sign up</a></div>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
<?php exit(); }

// --- Logged In Logic ---
$user = getCurrentUser($pdo);
$error = '';
$success = '';

// Handle report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_report') {
    $title = trim($_POST['title'] ?? '');
    $program = trim($_POST['program'] ?? 'Matomo');
    $description = trim($_POST['description'] ?? '');
    $impact = trim($_POST['impact'] ?? '');
    $severity = $_POST['severity'] ?? 'medium';

    if (empty($title) || empty($description) || empty($impact)) {
        $error = 'Report Title, Description, and Impact details are required.';
    } else {
        try {
            // Vulnerable: Stored unescaped to trigger Stored XSS
            $stmt = $pdo->prepare("
                INSERT INTO {$table_reports} (user_id, title, program, description, impact, severity) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user['id'], $title, $program, $description, $impact, $severity]);
            $success = 'Vulnerability report submitted successfully to triage!';
        } catch (PDOException $e) {
            $error = 'Failed to submit report: ' . $e->getMessage();
        }
    }
}

// Fetch reports based on role access controls
// Admin (Triage Team) sees ALL reports; Standard users see ONLY their own reports
if ($user['role'] === 'admin') {
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, u.full_name 
        FROM {$table_reports} r 
        JOIN {$table_users} u ON r.user_id = u.id 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, u.full_name 
        FROM {$table_reports} r 
        JOIN {$table_users} u ON r.user_id = u.id 
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user['id']]);
}
$reports = $stmt->fetchAll();

// Determine filters/view types
$active_report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : null;
$active_report = null;
if ($active_report_id) {
    // Access control verification: make sure standard users cannot access other reports via URL parameter
    if ($user['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT r.*, u.username FROM {$table_reports} r JOIN {$table_users} u ON r.user_id = u.id WHERE r.id = ?");
        $stmt->execute([$active_report_id]);
    } else {
        $stmt = $pdo->prepare("SELECT r.*, u.username FROM {$table_reports} r JOIN {$table_users} u ON r.user_id = u.id WHERE r.id = ? AND r.user_id = ?");
        $stmt->execute([$active_report_id, $user['id']]);
    }
    $active_report = $stmt->fetch();
}

$view_mode = $_GET['view'] ?? 'dashboard';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HackerOne Portal - Triage Console</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --h1-dark-bg: #0b0a12;
      --h1-panel-bg: #100f1c;
      --h1-card-bg: #171626;
      --h1-border: #23223b;
      --h1-pink: #ff2a74;
      --h1-pink-hover: #e02063;
      --h1-text: #f1f0f5;
      --h1-muted: #8f8ea8;
      
      --severity-low: #22c55e;
      --severity-medium: #eab308;
      --severity-high: #f97316;
      --severity-critical: #ef4444;
    }
    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--h1-dark-bg);
      color: var(--h1-text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    
    /* Layout */
    .h1-layout {
      display: flex;
      flex-grow: 1;
    }
    
    /* Sidebar Left navigation */
    .h1-sidebar {
      width: 60px;
      background-color: #08080f;
      border-right: 1px solid var(--h1-border);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 15px 0;
      flex-shrink: 0;
    }
    .h1-logo-badge {
      width: 32px;
      height: 32px;
      background-color: white;
      color: black;
      font-weight: 800;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.15rem;
      margin-bottom: 25px;
      text-decoration: none;
    }
    .h1-sidebar-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      color: var(--h1-muted);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      margin-bottom: 15px;
      text-decoration: none;
      transition: all 0.2s;
    }
    .h1-sidebar-icon:hover, .h1-sidebar-icon.active {
      color: white;
      background-color: rgba(255,255,255,0.05);
    }
    .h1-sidebar-icon.active {
      color: var(--h1-pink);
    }
    
    /* Topbar Header */
    .h1-topbar {
      height: 60px;
      border-bottom: 1px solid var(--h1-border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 30px;
      background-color: var(--h1-panel-bg);
    }
    .user-profile-badge {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .user-avatar-circle {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background-color: var(--h1-pink);
      color: white;
      font-weight: 700;
      font-size: 0.8rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .btn-submit-report {
      background: linear-gradient(90deg, var(--h1-pink) 0%, #ff523b 100%);
      color: white;
      border: none;
      font-weight: 700;
      padding: 6px 16px;
      border-radius: 4px;
      font-size: 0.85rem;
      text-decoration: none;
      transition: opacity 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .btn-submit-report:hover {
      opacity: 0.9;
      color: white;
    }

    /* Sub-nav filters bar */
    .filter-subnav {
      background-color: var(--h1-panel-bg);
      border-bottom: 1px solid var(--h1-border);
      padding: 10px 30px;
      display: flex;
      align-items: center;
      gap: 15px;
      overflow-x: auto;
    }
    .filter-pill {
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--h1-muted);
      padding: 4px 12px;
      border-radius: 20px;
      text-decoration: none;
      white-space: nowrap;
      transition: all 0.2s;
    }
    .filter-pill:hover, .filter-pill.active {
      color: white;
      background-color: rgba(255,255,255,0.05);
    }
    .filter-pill.active {
      color: white;
      background-color: rgba(255, 42, 116, 0.15);
      border: 1px solid rgba(255, 42, 116, 0.3);
    }
    .filter-pill span {
      background-color: var(--h1-border);
      color: var(--h1-muted);
      border-radius: 10px;
      padding: 1px 6px;
      font-size: 0.7rem;
      margin-left: 5px;
    }

    /* Dashboard main panel split */
    .dashboard-panel {
      display: flex;
      flex-grow: 1;
    }
    .reports-list-pane {
      width: 320px;
      border-right: 1px solid var(--h1-border);
      background-color: var(--h1-panel-bg);
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
    }
    .reports-search-box {
      padding: 15px;
      border-bottom: 1px solid var(--h1-border);
    }
    .report-list-item {
      padding: 16px;
      border-bottom: 1px solid var(--h1-border);
      text-decoration: none;
      color: var(--h1-text);
      display: block;
      transition: background-color 0.2s;
    }
    .report-list-item:hover, .report-list-item.active {
      background-color: rgba(255,255,255,0.02);
    }
    .report-list-item.active {
      border-left: 3px solid var(--h1-pink);
      padding-left: 13px;
    }
    .report-list-title {
      font-size: 0.88rem;
      font-weight: 700;
      line-height: 1.4;
      margin-bottom: 8px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .report-list-meta {
      display: flex;
      justify-content: space-between;
      font-size: 0.75rem;
      color: var(--h1-muted);
    }
    
    /* Severity Badges */
    .severity-dot-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
    }
    .severity-dot-badge::before {
      content: '';
      width: 6px;
      height: 6px;
      border-radius: 50%;
      display: inline-block;
    }
    .sev-low { color: var(--severity-low); }
    .sev-low::before { background-color: var(--severity-low); }
    .sev-medium { color: var(--severity-medium); }
    .sev-medium::before { background-color: var(--severity-medium); }
    .sev-high { color: var(--severity-high); }
    .sev-high::before { background-color: var(--severity-high); }
    .sev-critical { color: var(--severity-critical); }
    .sev-critical::before { background-color: var(--severity-critical); }

    /* Report Details Right Pane */
    .report-details-pane {
      flex-grow: 1;
      background-color: var(--h1-dark-bg);
      overflow-y: auto;
      padding: 30px;
    }
    
    /* Empty state info */
    .empty-reports-details {
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: var(--h1-muted);
    }
    .balloon-icon {
      font-size: 4rem;
      margin-bottom: 15px;
      opacity: 0.6;
    }
    
    /* Report Header details */
    .details-title-row {
      border-bottom: 1px solid var(--h1-border);
      padding-bottom: 20px;
      margin-bottom: 25px;
    }
    .details-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: white;
      margin-bottom: 12px;
    }
    .details-meta-line {
      display: flex;
      gap: 15px;
      font-size: 0.85rem;
      color: var(--h1-muted);
      align-items: center;
    }
    
    /* Markdown POC contents card */
    .poc-block-card {
      background-color: var(--h1-panel-bg);
      border: 1px solid var(--h1-border);
      border-radius: 6px;
      padding: 24px;
      margin-bottom: 20px;
    }
    .poc-header {
      font-size: 0.95rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: white;
      border-bottom: 1px solid var(--h1-border);
      padding-bottom: 10px;
      margin-bottom: 15px;
    }
    .poc-body {
      color: #d1d0db;
      font-size: 0.95rem;
      line-height: 1.6;
      white-space: pre-wrap;
    }

    /* Program Guidelines Submit Form */
    .submit-canvas {
      padding: 30px;
      flex-grow: 1;
      display: flex;
      gap: 30px;
      overflow-y: auto;
    }
    .scope-sidebar {
      width: 220px;
      flex-shrink: 0;
    }
    .scope-link {
      display: block;
      padding: 8px 16px;
      color: var(--h1-muted);
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 600;
      border-radius: 4px;
      transition: all 0.2s;
    }
    .scope-link:hover, .scope-link.active {
      color: #ff2a74;
      background-color: rgba(255, 42, 116, 0.05);
    }
    
    .guidelines-main {
      flex-grow: 1;
      background-color: var(--h1-panel-bg);
      border: 1px solid var(--h1-border);
      border-radius: 6px;
      padding: 30px;
    }
    .guidelines-right {
      width: 280px;
      flex-shrink: 0;
    }
    .guidelines-right-card {
      background-color: var(--h1-panel-bg);
      border: 1px solid var(--h1-border);
      border-radius: 6px;
      padding: 20px;
      margin-bottom: 20px;
    }
    
    .form-control, .form-select {
      background-color: var(--h1-dark-bg);
      border: 1px solid var(--h1-border);
      color: var(--h1-text);
      padding: 0.65rem 0.85rem;
    }
    .form-control:focus, .form-select:focus {
      background-color: var(--h1-dark-bg);
      border-color: var(--h1-pink);
      box-shadow: 0 0 0 3px rgba(255,42,116,0.15);
      color: var(--h1-text);
    }
    .form-label {
      color: white;
      font-weight: 700;
      font-size: 0.875rem;
      margin-bottom: 6px;
    }
    
    /* Footer links */
    .h1-footer {
      border-top: 1px solid var(--h1-border);
      padding: 15px 30px;
      text-align: center;
      font-size: 0.78rem;
      color: var(--h1-muted);
      background-color: var(--h1-panel-bg);
    }
    .h1-footer a {
      color: var(--h1-muted);
      text-decoration: none;
      margin: 0 8px;
    }
    .h1-footer a:hover {
      color: white;
    }
  </style>
</head>
<body>

  <!-- Top Console Header -->
  <header class="h1-topbar">
    <div class="d-flex align-items-center gap-3">
      <a href="index.php?view=dashboard" class="h1-logo-badge">h1</a>
      <span class="fw-bold text-white">hackerone</span>
    </div>
    
    <div class="d-flex align-items-center gap-3">
      <a href="index.php?view=submit" class="btn-submit-report"><i class="bi bi-bug-fill"></i> Submit report</a>
      <div class="user-profile-badge">
        <span class="text-white small fw-bold">@<?php echo htmlspecialchars($user['username']); ?></span>
        <div class="user-avatar-circle"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
      </div>
    </div>
  </header>

  <!-- Main Layout -->
  <div class="h1-layout">
    
    <!-- Left Navigation bar icon options -->
    <nav class="h1-sidebar">
      <a href="index.php?view=dashboard" class="h1-sidebar-icon <?php echo $view_mode === 'dashboard' ? 'active' : ''; ?>"><i class="bi bi-shield-lock-fill"></i></a>
      <a href="index.php?view=guidelines" class="h1-sidebar-icon <?php echo $view_mode === 'guidelines' ? 'active' : ''; ?>"><i class="bi bi-journal-bookmark-fill"></i></a>
      <a href="index.php?action=logout" class="h1-sidebar-icon mt-auto"><i class="bi bi-box-arrow-right"></i></a>
    </nav>

    <!-- Console Main panels -->
    <div class="d-flex flex-column flex-grow-1">

      <!-- ==================== VIEW 1: REPORTS DASHBOARD ==================== -->
      <?php if ($view_mode === 'dashboard'): ?>
        
        <!-- Status filter bar -->
        <div class="filter-subnav">
          <a href="#" class="filter-pill active">Open</a>
          <a href="#" class="filter-pill">Needs more information</a>
          <a href="#" class="filter-pill">Pending bounty</a>
          <a href="#" class="filter-pill">Pending disclosure <span>2</span></a>
          <a href="#" class="filter-pill">Pending retests</a>
          <a href="#" class="filter-pill">All <span><?php echo count($reports); ?></span></a>
          <a href="#" class="filter-pill">Draft <span>2</span></a>
          <a href="#" class="filter-pill">Favorites</a>
        </div>

        <div class="dashboard-panel flex-grow-1">
          <!-- Left listing column -->
          <div class="reports-list-pane">
            <div class="reports-search-box">
              <input type="text" class="form-control form-control-sm" id="reportSearch" placeholder="Search all reports..." onkeyup="filterReports()">
            </div>
            
            <div id="reportsList">
              <?php if (empty($reports)): ?>
                <p class="text-muted small text-center py-4">No reports found.</p>
              <?php else: ?>
                <?php foreach ($reports as $rep): ?>
                  <a href="index.php?view=dashboard&report_id=<?php echo $rep['id']; ?>" 
                     class="report-list-item <?php echo $active_report_id === $rep['id'] ? 'active' : ''; ?>"
                     data-title="<?php echo htmlspecialchars($rep['title']); ?>">
                    <div class="report-list-title"><?php echo htmlspecialchars($rep['title']); ?></div>
                    <div class="report-list-meta">
                      <span class="severity-dot-badge sev-<?php echo $rep['severity']; ?>"><?php echo htmlspecialchars($rep['severity']); ?></span>
                      <span>#<?php echo $rep['id']; ?></span>
                    </div>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <!-- Right detail column -->
          <div class="report-details-pane">
            <?php if (!$active_report): ?>
              <div class="empty-reports-details">
                <i class="bi bi-wind balloon-icon"></i>
                <h5 class="fw-bold text-white mb-2">Nothing to see here yet!</h5>
                <p class="small">Click on a report to dive into the details.</p>
              </div>
            <?php else: ?>
              <div class="details-title-row">
                <h2 class="details-title"><?php echo $active_report['title']; // Vulnerable: Raw output for CTF Stored XSS ?></h2>
                <div class="details-meta-line">
                  <span class="severity-dot-badge sev-<?php echo $active_report['severity']; ?>"><?php echo htmlspecialchars($active_report['severity']); ?> Severity</span>
                  <span>&bull;</span>
                  <span>ID: #<?php echo $active_report['id']; ?></span>
                  <span>&bull;</span>
                  <span>Reported to <strong><?php echo htmlspecialchars($active_report['program']); ?></strong></span>
                  <span>&bull;</span>
                  <span>Submitted by <strong>@<?php echo htmlspecialchars($active_report['username']); ?></strong></span>
                </div>
              </div>

              <div class="poc-block-card">
                <div class="poc-header">Description</div>
                <div class="poc-body"><?php echo $active_report['description']; // Vulnerable: Raw output for CTF Stored XSS ?></div>
              </div>

              <div class="poc-block-card">
                <div class="poc-header">Impact</div>
                <div class="poc-body"><?php echo $active_report['impact']; // Vulnerable: Raw output for CTF Stored XSS ?></div>
              </div>

              <?php if ($user['role'] === 'admin'): ?>
                <div class="poc-block-card" style="border-left: 4px solid var(--h1-pink);">
                  <div class="poc-header" style="color:var(--h1-pink);">Triage Operations Console</div>
                  <div class="d-flex align-items-center gap-3 mt-3">
                    <button class="btn btn-sm btn-outline-light">Request Details</button>
                    <button class="btn btn-sm btn-outline-success">Accept &amp; Triage</button>
                    <button class="btn btn-sm btn-danger btn-sm" style="background-color: var(--h1-pink);">Award Bounty</button>
                  </div>
                </div>
              <?php endif; ?>

            <?php endif; ?>
          </div>
        </div>

      <!-- ==================== VIEW 2: SUBMIT REPORT FORM ==================== -->
      <?php elseif ($view_mode === 'submit'): ?>
        
        <div class="submit-canvas">
          <!-- Scope menu guide -->
          <div class="scope-sidebar">
            <a href="#" class="scope-link active">Program guidelines</a>
            <a href="#" class="scope-link">Scope</a>
            <a href="#" class="scope-link">Hacktivity</a>
            <a href="#" class="scope-link">Thanks</a>
            <a href="#" class="scope-link">Updates</a>
          </div>

          <!-- Main Guidelines & Form -->
          <div class="guidelines-main">
            <h3 class="fw-bold text-white mb-2">Submit Vulnerability Report</h3>
            <p class="text-muted small mb-4">Please provide detailed, reproducible steps. Ensure you test in accordance with out-of-scope rules.</p>
            
            <form method="POST" action="index.php?view=dashboard">
              <input type="hidden" name="action" value="submit_report">
              
              <div class="mb-4">
                <label class="form-label">Title *</label>
                <div class="text-muted small mb-2">A clear and concise title including the type of vulnerability and the impacted asset.</div>
                <input type="text" class="form-control" name="title" placeholder="e.g. Stored XSS in /profile/edit parameter" required>
              </div>

              <div class="mb-4">
                <label class="form-label">Target Program *</label>
                <select class="form-select" name="program">
                  <option value="Matomo" selected>Matomo</option>
                  <option value="Dell">Dell</option>
                  <option value="NASA">NASA</option>
                  <option value="DOD">DOD</option>
                  <option value="Superhuman">Superhuman</option>
                  <option value="BrowserStack">BrowserStack</option>
                  <option value="Rockstar Games">Rockstar Games</option>
                </select>
              </div>

              <div class="mb-4">
                <label class="form-label">Severity *</label>
                <select class="form-select" name="severity">
                  <option value="low">Low (CVSS 0.1 - 3.9)</option>
                  <option value="medium" selected>Medium (CVSS 4.0 - 6.9)</option>
                  <option value="high">High (CVSS 7.0 - 8.9)</option>
                  <option value="critical">Critical (CVSS 9.0 - 10.0)</option>
                </select>
              </div>

              <div class="mb-4">
                <label class="form-label">Description *</label>
                <div class="text-muted small mb-2">What is the vulnerability? In clear steps, how do you reproduce it?</div>
                <textarea class="form-control" name="description" rows="8" placeholder="Detailed reproduction steps and code components..." required></textarea>
              </div>

              <div class="mb-4">
                <label class="form-label">Impact *</label>
                <div class="text-muted small mb-2">What security impact can an attacker achieve?</div>
                <textarea class="form-control" name="impact" rows="4" placeholder="e.g. Account takeover, reading sensitive files..." required></textarea>
              </div>

              <div class="d-flex align-items-center gap-3">
                <button type="submit" class="btn-submit-report"><i class="bi bi-send me-1"></i> Submit Report</button>
                <a href="index.php?view=dashboard" class="btn btn-outline-secondary btn-sm">Cancel</a>
              </div>
            </form>
          </div>

          <!-- Rewards summary side column -->
          <div class="guidelines-right">
            <div class="guidelines-right-card">
              <h5 class="fw-bold text-white mb-3">Rewards Summary</h5>
              <div class="d-flex justify-content-between small mb-2">
                <span>Critical</span>
                <span class="fw-bold text-white">$2,500</span>
              </div>
              <div class="d-flex justify-content-between small mb-2">
                <span>High</span>
                <span class="fw-bold text-white">$1,200</span>
              </div>
              <div class="d-flex justify-content-between small mb-2">
                <span>Medium</span>
                <span class="fw-bold text-white">$500</span>
              </div>
              <div class="d-flex justify-content-between small mb-2">
                <span>Low</span>
                <span class="fw-bold text-white">$333</span>
              </div>
            </div>

            <div class="guidelines-right-card">
              <h5 class="fw-bold text-white mb-2">Matomo Stats</h5>
              <div class="text-muted small mb-2">Response efficiency: <strong class="text-success">99%</strong></div>
              <div class="text-muted small mb-2">Average triage time: <strong class="text-white">12 Hours</strong></div>
              <div class="text-muted small">Total reports: <strong class="text-white">49 Resolved</strong></div>
            </div>
          </div>
        </div>

      <!-- ==================== VIEW 3: PROGRAM DETAILS ==================== -->
      <?php elseif ($view_mode === 'guidelines'): ?>
        <div class="submit-canvas">
          <div class="scope-sidebar">
            <a href="#" class="scope-link active">Program guidelines</a>
            <a href="#" class="scope-link">Scope</a>
            <a href="#" class="scope-link">Hacktivity</a>
            <a href="#" class="scope-link">Thanks</a>
            <a href="#" class="scope-link">Updates</a>
          </div>

          <div class="guidelines-main">
            <h2 class="fw-bold text-white mb-3">Program Introduction</h2>
            <p>No technology is perfect, and Matomo believes that working with skilled security researchers across the globe is crucial in identifying weaknesses in any technology.</p>
            <p>If you believe you have found a security issue in our product or service, we encourage you to notify us. We will work with you to resolve the issue promptly.</p>
            <p class="mb-4">We accept reports that demonstrate real, reproducible security issues with clear impact in a running Matomo environment.</p>

            <h4 class="fw-bold text-white mb-3">Program Highlights</h4>
            <ul class="text-muted" style="line-height: 1.8;">
              <li><strong class="text-white">Closed Scope:</strong> Only accepts reports based on the listed scope.</li>
              <li><strong class="text-white">Fast Payment:</strong> Ensures payment within 1 month of receiving a vulnerability report.</li>
              <li><strong class="text-white">Platform Standards:</strong> Fully compliant with Platform Standards.</li>
              <li><strong class="text-white">Top Response Efficiency:</strong> This program's response efficiency is above 90%.</li>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <!-- Footer Bar -->
      <footer class="h1-footer">
        &copy; HackerOne 2026. 
        <a href="#">Opportunities</a> 
        <a href="#">Security</a> 
        <a href="#">Leaderboard</a> 
        <a href="#">Docs</a> 
        <a href="#">Disclosure Guidelines</a>
      </footer>

    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function filterReports() {
      const query = document.getElementById('reportSearch').value.toLowerCase();
      const items = document.querySelectorAll('.report-list-item');
      items.forEach(item => {
        const title = item.getAttribute('data-title').toLowerCase();
        if (title.includes(query)) {
          item.style.display = 'block';
        } else {
          item.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>
