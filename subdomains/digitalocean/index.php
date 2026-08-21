<?php
// ============================================================
// Database Configuration
// ============================================================
$dbname = 'KrazePlanet';
$username = 'root';
$password = '';
$hosts = ['127.0.0.1', 'localhost'];

// Table Names Configuration
$table_users = 'digitalocean_users';
$table_tickets = 'digitalocean_tickets';


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
    global $table_users, $table_tickets;
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

    // Support tickets table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$table_tickets} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
            priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Insert default admin user if none exists
    $check = $pdo->prepare("SELECT COUNT(*) FROM {$table_users} WHERE role = 'admin'");
    $check->execute();
    if ($check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO {$table_users} (username, email, password, full_name, role) VALUES (?, ?, ?, 'Administrator', 'admin')");
        $stmt->execute([
            'admin',
            'admin@community.local',
            password_hash('admin123', PASSWORD_DEFAULT)
        ]);
    }
}
initializeDatabase($pdo);

// Session management
session_start();

// Authentication Helpers
function getCurrentUser($pdo) {
    global $table_users;
    if (!isset($_SESSION['desk_user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM {$table_users} WHERE id = ?");
    $stmt->execute([$_SESSION['desk_user_id']]);
    $u = $stmt->fetch();
    if (!$u) {
        unset($_SESSION['desk_user_id']);
        unset($_SESSION['desk_username']);
        unset($_SESSION['desk_role']);
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
        $_SESSION['desk_user_id'] = $user['id'];
        $_SESSION['desk_username'] = $user['username'];
        $_SESSION['desk_role'] = $user['role'];
        return true;
    }
    return false;
}

function logoutUser() {
    unset($_SESSION['desk_user_id']);
    unset($_SESSION['desk_username']);
    unset($_SESSION['desk_role']);
    header("Location: index.php");
    exit();
}

// --- Logout ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
}

// --- Register ---
$authError = ''; $authSuccess = '';
if (!isset($_SESSION['desk_user_id']) && isset($_POST['register'])) {
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
if (!isset($_SESSION['desk_user_id']) && isset($_POST['login'])) {
    if (loginUser($pdo, $_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: index.php'); exit();
    } else {
        $authError = 'Invalid username or password';
    }
}

// --- Auth Gate ---
if (!isset($_SESSION['desk_user_id'])) {
    $authView = $_GET['view'] ?? 'login';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DigitalOcean Support - Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #0c1017;
      color: #e6edf3;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .auth-box {
      background-color: #161b22;
      border: 1px solid #30363d;
      border-radius: 6px;
      padding: 2.5rem;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }
    .do-logo {
      color: #0080ff;
      font-weight: 800;
      font-size: 1.8rem;
      text-align: center;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }
    .do-logo i {
      font-size: 2.1rem;
    }
    .auth-sub {
      text-align: center;
      font-size: 0.85rem;
      color: #8b949e;
      margin-bottom: 2rem;
    }
    .form-control {
      background-color: #0d1117;
      border: 1px solid #30363d;
      color: #c9d1d9;
      padding: 0.65rem 0.75rem;
      border-radius: 4px;
    }
    .form-control:focus {
      background-color: #0d1117;
      border-color: #1f6feb;
      box-shadow: 0 0 0 3px rgba(31,111,235,0.15);
      color: #c9d1d9;
    }
    .form-label {
      font-weight: 500;
      color: #c9d1d9;
      font-size: 0.875rem;
    }
    .btn-do {
      background-color: #0080ff;
      border: none;
      color: white;
      font-weight: 600;
      padding: 0.7rem;
      border-radius: 4px;
      width: 100%;
      transition: background-color 0.2s;
    }
    .btn-do:hover {
      background-color: #0066cc;
    }
    .alert-danger {
      background-color: rgba(248,81,73,0.1);
      border: 1px solid rgba(248,81,73,0.4);
      color: #ff7b72;
    }
    .alert-success {
      background-color: rgba(56,139,253,0.1);
      border: 1px solid rgba(56,139,253,0.4);
      color: #58a6ff;
    }
    .demo-creds {
      background-color: #0d1117;
      border: 1px solid #30363d;
      border-radius: 4px;
      padding: 0.85rem;
      font-size: 0.8rem;
      color: #8b949e;
      margin-top: 1rem;
    }
    .switch-link {
      text-align: center;
      margin-top: 1.25rem;
      font-size: 0.85rem;
    }
    .switch-link a {
      color: #58a6ff;
      text-decoration: none;
    }
  </style>
</head>
<body>
<div class="auth-box">
  <div class="do-logo"><i class="bi bi-cloud-fill"></i> DigitalOcean</div>
  <div class="auth-sub">Support Portal Console</div>

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
    <button type="submit" name="register" class="btn btn-do">Create Account</button>
    <div class="switch-link"><a href="index.php">Already have an account? Log in</a></div>
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
    <button type="submit" name="login" class="btn btn-do">Log In</button>
    <div class="demo-creds text-center">
      <strong>Demo Accounts:</strong> admin / admin123
    </div>
    <div class="switch-link"><a href="index.php?view=register">New to DigitalOcean? Sign up</a></div>
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

// Handle ticket creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';

    if (empty($subject) || empty($description)) {
        $error = 'Subject and description comments are required.';
    } else {
        try {
            // Vulnerable: Stored unescaped to trigger Stored XSS
            $ins = $pdo->prepare("INSERT INTO {$table_tickets} (user_id, subject, description, priority) VALUES (?, ?, ?, ?)");
            $ins->execute([$user['id'], $subject, $description, $priority]);
            $success = 'Support ticket created successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to create ticket: ' . $e->getMessage();
        }
    }
}

// Fetch user tickets
if ($user['role'] === 'admin') {
    $stmt = $pdo->prepare("
        SELECT t.*, u.username, u.full_name 
        FROM {$table_tickets} t 
        JOIN {$table_users} u ON t.user_id = u.id 
        ORDER BY t.created_at DESC
    ");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT t.*, u.username, u.full_name 
        FROM {$table_tickets} t 
        JOIN {$table_users} u ON t.user_id = u.id 
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$user['id']]);
}
$tickets = $stmt->fetchAll();

// Count aggregates
$open_count = 0;
$resolved_count = 0;
foreach ($tickets as $t) {
    if ($t['status'] === 'open' || $t['status'] === 'in_progress') {
        $open_count++;
    } else {
        $resolved_count++;
    }
}

// Active view modes: 'home', 'tickets', 'create'
$view_mode = $_GET['view'] ?? 'home';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DigitalOcean Cloud Console - Support Hub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-dark: #070a13;
      --bg-sidebar: #0f121d;
      --bg-card: #151a2d;
      --border-color: #24293f;
      --text-muted: #8892b0;
      --do-blue: #0080ff;
      --do-blue-light: #e6f0ff;
      --do-teal: #00cccc;
      --do-green: #22c55e;
      --do-red: #ef4444;
    }
    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-dark);
      color: #e6edf3;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    
    /* Layout structure */
    .console-container {
      display: flex;
      flex-grow: 1;
    }
    
    /* Sidebar styling */
    .console-sidebar {
      width: 250px;
      background-color: var(--bg-sidebar);
      border-right: 1px solid var(--border-color);
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      padding: 20px 0;
    }
    .brand-section {
      padding: 0 24px 20px 24px;
      border-bottom: 1px solid var(--border-color);
      margin-bottom: 15px;
    }
    .brand-link {
      color: white;
      font-weight: 700;
      font-size: 1.25rem;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .brand-link i {
      color: var(--do-blue);
      font-size: 1.45rem;
    }
    .sidebar-menu-title {
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--text-muted);
      padding: 10px 24px;
    }
    .sidebar-link {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 24px;
      color: #a0aec0;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      transition: all 0.2s;
    }
    .sidebar-link:hover, .sidebar-link.active {
      color: white;
      background-color: rgba(255,255,255,0.03);
    }
    .sidebar-link.active {
      border-left: 3px solid var(--do-blue);
      background-color: rgba(0, 128, 255, 0.05);
      padding-left: 21px;
    }
    
    /* Top Search Bar */
    .console-topbar {
      height: 60px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 30px;
      background-color: var(--bg-dark);
    }
    .search-input-wrapper {
      position: relative;
      width: 100%;
      max-width: 480px;
    }
    .search-input-wrapper i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
    }
    .search-control {
      background-color: #0d1117;
      border: 1px solid var(--border-color);
      border-radius: 4px;
      color: white;
      padding: 6px 12px 6px 36px;
      font-size: 0.85rem;
      width: 100%;
      outline: none;
    }
    .search-control:focus {
      border-color: var(--do-blue);
    }

    /* Main Console Content Panel */
    .console-content {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }
    .console-body {
      padding: 30px;
      flex-grow: 1;
    }
    
    /* Support Banner Section */
    .support-banner {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }
    .support-banner h1 {
      font-size: 2.2rem;
      font-weight: 700;
      color: white;
    }
    .btn-outline-do {
      border: 1px solid var(--do-blue);
      color: var(--do-blue);
      border-radius: 4px;
      padding: 8px 18px;
      font-size: 0.9rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s;
    }
    .btn-outline-do:hover {
      background-color: rgba(0, 128, 255, 0.05);
      color: white;
    }
    .btn-solid-do {
      background-color: #82cbdc;
      color: #081a24;
      border-radius: 4px;
      padding: 8px 18px;
      font-size: 0.9rem;
      font-weight: 600;
      border: none;
      text-decoration: none;
      transition: all 0.2s;
    }
    .btn-solid-do:hover {
      background-color: #9cdfed;
      color: #081a24;
    }
    
    .resources-title {
      font-size: 1.15rem;
      font-weight: 600;
      color: white;
      margin-bottom: 15px;
    }
    
    /* Cards Layout */
    .resource-card {
      background-color: #101424;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      padding: 24px;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .card-icon-label {
      color: #fb8c00;
      font-weight: 700;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 12px;
    }
    .card-desc {
      color: var(--text-muted);
      font-size: 0.9rem;
      line-height: 1.5;
      margin-bottom: 20px;
    }
    .btn-card-link {
      margin-top: auto;
      border: 1px solid var(--border-color);
      color: #a0aec0;
      font-weight: 600;
      font-size: 0.85rem;
      padding: 6px 16px;
      border-radius: 4px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .btn-card-link:hover {
      color: white;
      border-color: var(--text-muted);
    }
    
    /* Support Plans Cards */
    .plan-card {
      background-color: #101424;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      padding: 20px;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .plan-title {
      font-weight: 700;
      font-size: 1.1rem;
      color: white;
      margin-bottom: 6px;
    }
    .plan-desc {
      font-size: 0.85rem;
      color: var(--text-muted);
      margin-bottom: 15px;
      height: 2.5rem;
    }
    .plan-price {
      font-size: 1.35rem;
      font-weight: 700;
      color: white;
      margin-bottom: 20px;
    }
    .plan-btn {
      background-color: transparent;
      border: 1px solid var(--do-blue);
      color: var(--do-blue);
      padding: 6px;
      border-radius: 4px;
      font-size: 0.85rem;
      font-weight: 600;
      width: 100%;
      transition: all 0.2s;
    }
    .plan-btn:hover {
      background-color: rgba(0,128,255,0.05);
    }
    .plan-btn.active-plan {
      background-color: rgba(255,255,255,0.05);
      border-color: var(--border-color);
      color: var(--text-muted);
      cursor: not-allowed;
    }

    /* Ticket Stat Card */
    .stat-card {
      background-color: #101424;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      padding: 16px 20px;
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .stat-icon-wrapper {
      width: 42px;
      height: 42px;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.35rem;
    }
    .stat-icon-wrapper.open { background-color: rgba(239, 68, 68, 0.1); color: var(--do-red); }
    .stat-icon-wrapper.resolved { background-color: rgba(34, 197, 94, 0.1); color: var(--do-green); }
    .stat-title {
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 2px;
    }
    .stat-number {
      font-size: 1.45rem;
      font-weight: 700;
      color: white;
      line-height: 1;
    }
    
    /* Tickets Board list */
    .tickets-container {
      background-color: #101424;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      padding: 24px;
      margin-top: 24px;
    }
    .tickets-header-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid var(--border-color);
      padding-bottom: 15px;
      margin-bottom: 20px;
    }
    .search-cases-wrapper {
      width: 100%;
      max-width: 400px;
      position: relative;
    }
    .search-cases-wrapper i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
    }
    .search-cases-control {
      background-color: #0c1017;
      border: 1px solid var(--border-color);
      border-radius: 4px;
      color: white;
      padding: 6px 12px 6px 36px;
      font-size: 0.85rem;
      width: 100%;
      outline: none;
    }
    .search-cases-control:focus {
      border-color: var(--do-blue);
    }
    
    .ticket-row-item {
      border: 1px solid var(--border-color);
      border-radius: 4px;
      background-color: #0c1017;
      padding: 16px;
      margin-bottom: 12px;
    }
    .ticket-row-title {
      font-size: 1.05rem;
      font-weight: 700;
      color: white;
      margin-bottom: 6px;
    }
    .ticket-row-meta {
      display: flex;
      gap: 12px;
      align-items: center;
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-bottom: 10px;
    }
    .badge-do {
      font-size: 0.72rem;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 3px;
      text-transform: uppercase;
    }
    .badge-priority-low { background-color: rgba(34,197,94,0.1); color: var(--do-green); }
    .badge-priority-medium { background-color: rgba(251,140,0,0.1); color: #fb8c00; }
    .badge-priority-high { background-color: rgba(239,68,68,0.1); color: var(--do-red); }
    .badge-priority-critical { background-color: var(--do-red); color: white; }
    .badge-status-open { background-color: rgba(34,197,94,0.1); color: var(--do-green); }
    .badge-status-in_progress { background-color: rgba(0,128,255,0.1); color: var(--do-blue); }
    .badge-status-resolved { background-color: rgba(255,255,255,0.05); color: var(--text-muted); }
    .badge-status-closed { background-color: rgba(255,255,255,0.05); color: var(--text-muted); }
    
    .ticket-row-desc {
      color: #c9d1d9;
      font-size: 0.9rem;
      line-height: 1.45;
      margin-bottom: 8px;
      white-space: pre-wrap;
    }
    
    /* Empty State */
    .empty-state-wrapper {
      text-align: center;
      padding: 50px 20px;
    }
    .empty-bag-icon {
      font-size: 3rem;
      color: #ec407a;
      margin-bottom: 15px;
      display: inline-block;
    }
    
    /* Create ticket Form styling */
    .fk-form-label {
      color: #c9d1d9;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 6px;
    }
    .select-do {
      background-color: #0c1017;
      border: 1px solid var(--border-color);
      color: white;
      padding: 6px;
      border-radius: 4px;
      font-size: 0.85rem;
      width: 100%;
    }
    .select-do option {
      background-color: var(--bg-card);
    }
    
    /* User team badge top */
    .user-team-badge {
      display: flex;
      align-items: center;
      gap: 10px;
      background-color: rgba(255,255,255,0.05);
      border: 1px solid var(--border-color);
      padding: 4px 12px;
      border-radius: 4px;
      font-size: 0.85rem;
    }
    .team-avatar {
      width: 24px;
      height: 24px;
      background-color: #ef4444;
      color: white;
      font-weight: 700;
      font-size: 0.75rem;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
  </style>
</head>
<body>

  <div class="console-container">
    
    <!-- Left Navigation Sidebar -->
    <aside class="console-sidebar">
      <div class="brand-section">
        <a href="index.php" class="brand-link">
          <i class="bi bi-cloud-fill"></i>
          <span>DigitalOcean</span>
        </a>
      </div>
      
      <div class="sidebar-menu-title">Support</div>
      <a href="index.php?view=home" class="sidebar-link <?php echo $view_mode === 'home' ? 'active' : ''; ?>">
        <i class="bi bi-info-circle"></i>
        <span>Getting Started</span>
      </a>
      <a href="index.php?view=tickets" class="sidebar-link <?php echo $view_mode === 'tickets' ? 'active' : ''; ?>">
        <i class="bi bi-ticket-detailed"></i>
        <span>Support Center</span>
      </a>
      
      <div class="sidebar-menu-title">Resources</div>
      <a href="../discussions/" class="sidebar-link">
        <i class="bi bi-people"></i>
        <span>Community</span>
      </a>
      <a href="#" class="sidebar-link">
        <i class="bi bi-activity"></i>
        <span>Status</span>
      </a>
      <a href="#" class="sidebar-link">
        <i class="bi bi-braces"></i>
        <span>API</span>
      </a>
      <a href="../articles/" class="sidebar-link">
        <i class="bi bi-journal-text"></i>
        <span>Documentation</span>
      </a>
      
      <div class="sidebar-menu-title" style="margin-top:auto;">Account</div>
      <a href="index.php?action=logout" class="sidebar-link">
        <i class="bi bi-box-arrow-right"></i>
        <span>Log Out</span>
      </a>
    </aside>

    <!-- Main Console Panel -->
    <div class="console-content">
      
      <!-- Top Console Searchbar -->
      <header class="console-topbar">
        <div class="search-input-wrapper">
          <i class="bi bi-search"></i>
          <input type="text" class="search-control" placeholder="Search by resource name or public IP (Ctrl+B)">
        </div>
        
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-primary btn-sm" onclick="location.href='index.php?view=create'"><i class="bi bi-plus-lg me-1"></i>Create</button>
          <div class="user-team-badge">
            <span><?php echo htmlspecialchars($user['username']); ?></span>
            <div class="team-avatar"><?php echo htmlspecialchars(strtoupper(substr($user['username'], 0, 2))); ?></div>
          </div>
        </div>
      </header>

      <!-- Main Body Container -->
      <div class="console-body">

        <?php if ($error): ?>
          <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert alert-success mb-4"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- ==================== VIEW 1: HOME RESOURCES ==================== -->
        <?php if ($view_mode === 'home'): ?>
          <div class="support-banner">
            <div>
              <h1>Support</h1>
              <p class="text-muted mb-0">Troubleshooting resources</p>
            </div>
            <div class="d-flex gap-2">
              <a href="index.php?view=tickets" class="btn-outline-do">View tickets ↗</a>
              <a href="index.php?view=create" class="btn-solid-do">Create a ticket ↗</a>
            </div>
          </div>

          <!-- Troubleshooting 3 Cards Grid -->
          <div class="row g-4 mb-5">
            <div class="col-md-4">
              <div class="resource-card">
                <div class="card-icon-label"><i class="bi bi-lightning-fill"></i> Support Articles</div>
                <p class="card-desc">Visit our docs to search for help across a variety of topics and system protocols.</p>
                <a href="../articles/" class="btn-card-link">Browse articles ↗</a>
              </div>
            </div>
            <div class="col-md-4">
              <div class="resource-card">
                <div class="card-icon-label"><i class="bi bi-lightning-fill"></i> Community</div>
                <p class="card-desc">Engage with DigitalOcean's community of experts and ask technical questions.</p>
                <a href="../discussions/" class="btn-card-link">Visit our community ↗</a>
              </div>
            </div>
            <div class="col-md-4">
              <div class="resource-card">
                <div class="card-icon-label"><i class="bi bi-lightning-fill"></i> Status Page</div>
                <p class="card-desc">Check the current status and operating health of active DigitalOcean services.</p>
                <a href="#" class="btn-card-link">Visit status dashboard ↗</a>
              </div>
            </div>
          </div>

          <!-- Support Plans Grid -->
          <h3 class="resources-title">Support plans</h3>
          <p class="text-muted mb-4">Learn more about our <a href="#" class="text-primary text-decoration-none">plans ↗</a></p>
          
          <div class="row g-3">
            <div class="col-lg-3 col-md-6">
              <div class="plan-card">
                <h5 class="plan-title">Starter</h5>
                <p class="plan-desc">Included for all DigitalOcean accounts.</p>
                <div class="plan-price">$0/month</div>
                <button class="plan-btn active-plan" disabled>CURRENT PLAN</button>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="plan-card">
                <h5 class="plan-title">Developer</h5>
                <p class="plan-desc">For testing ideas and hosting small projects.</p>
                <div class="plan-price">$24/month</div>
                <button class="plan-btn">Select plan</button>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="plan-card">
                <h5 class="plan-title">Standard</h5>
                <p class="plan-desc">For teams with production workloads.</p>
                <div class="plan-price">$99/month</div>
                <button class="plan-btn">Select plan</button>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="plan-card">
                <h5 class="plan-title">Premium</h5>
                <p class="plan-desc">For businesses with mission-critical applications.</p>
                <div class="plan-price">$999/month</div>
                <button class="plan-btn">Select plan</button>
              </div>
            </div>
          </div>

        <!-- ==================== VIEW 2: TICKETS DASHBOARD ==================== -->
        <?php elseif ($view_mode === 'tickets'): ?>
          <div class="support-banner">
            <div>
              <h1>Support Tickets</h1>
              <p class="text-muted mb-0">Track active technical issues and report requests</p>
            </div>
            <div>
              <a href="index.php?view=create" class="btn-solid-do"><i class="bi bi-plus-lg me-1"></i>Create Ticket</a>
            </div>
          </div>

          <!-- Statistics Counters Cards -->
          <div class="row g-4">
            <div class="col-md-6">
              <div class="stat-card">
                <div class="stat-icon-wrapper open"><i class="bi bi-briefcase-fill"></i></div>
                <div>
                  <div class="stat-title">Open Tickets</div>
                  <div class="stat-number"><?php echo $open_count; ?></div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="stat-card">
                <div class="stat-icon-wrapper resolved"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                  <div class="stat-title">Resolved &amp; Closed</div>
                  <div class="stat-number"><?php echo $resolved_count; ?></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tickets Board -->
          <div class="tickets-container">
            <div class="tickets-header-row">
              <h5 class="fw-bold mb-0">Open Tickets (<?php echo $open_count; ?>)</h5>
              <div class="search-cases-wrapper">
                <i class="bi bi-search"></i>
                <input type="text" class="search-cases-control" id="ticketSearch" placeholder="Search by case number or subject" onkeyup="filterTickets()">
              </div>
            </div>

            <div id="ticketsList">
              <?php if (empty($tickets)): ?>
                <div class="empty-state-wrapper">
                  <i class="bi bi-briefcase empty-bag-icon"></i>
                  <h5 class="fw-bold mb-2">No tickets found</h5>
                  <p class="text-muted small">You don't have any support tickets at the moment.</p>
                  <a href="index.php?view=create" class="btn btn-primary btn-sm mt-3">+ Create Ticket</a>
                </div>
              <?php else: ?>
                <?php foreach ($tickets as $t): ?>
                  <div class="ticket-row-item" data-subject="<?php echo htmlspecialchars($t['subject']); ?>">
                    <div class="ticket-row-title"><?php echo $t['subject']; // Vulnerable: Direct unescaped output for CTF Stored XSS ?></div>
                    <div class="ticket-row-meta">
                      <span class="badge-do badge-priority-<?php echo $t['priority']; ?>"><?php echo htmlspecialchars($t['priority']); ?></span>
                      <span class="badge-do badge-status-<?php echo $t['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $t['status'])); ?></span>
                      <span>ID: #<?php echo $t['id']; ?></span>
                      <span>&bull; Submitted by @<?php echo htmlspecialchars($t['username']); ?> &bull; <?php echo date('M j, Y g:i A', strtotime($t['created_at'])); ?></span>
                    </div>
                    <div class="ticket-row-desc"><?php echo $t['description']; // Vulnerable: Direct unescaped output for CTF Stored XSS ?></div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

        <!-- ==================== VIEW 3: CREATE TICKET FORM ==================== -->
        <?php elseif ($view_mode === 'create'): ?>
          <div class="support-banner">
            <div>
              <h1>Create Support Ticket</h1>
              <p class="text-muted mb-0">Open a case directly with DigitalOcean technical operators</p>
            </div>
            <div>
              <a href="index.php?view=tickets" class="btn-outline-do"><i class="bi bi-arrow-left me-1"></i>Back to Support</a>
            </div>
          </div>

          <div class="row justify-content-center">
            <div class="col-lg-8">
              <div class="tickets-container">
                <form method="POST">
                  <input type="hidden" name="action" value="create_ticket">
                  <div class="mb-4">
                    <label class="fk-form-label">Subject *</label>
                    <input type="text" class="form-control" name="subject" placeholder="Brief summary of the technical issue" required>
                  </div>
                  <div class="mb-4">
                    <label class="fk-form-label">Priority</label>
                    <select class="select-do" name="priority">
                      <option value="low">Low (Standard response time)</option>
                      <option value="medium" selected>Medium (Standard system operation)</option>
                      <option value="high">High (Production system performance warning)</option>
                      <option value="critical">Critical (Infrastructure outage or failure)</option>
                    </select>
                  </div>
                  <div class="mb-4">
                    <label class="fk-form-label">Description *</label>
                    <textarea class="form-control" name="description" rows="8" placeholder="Provide detailed steps to reproduce, API endpoints, error logs, or environment context..." required></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Submit Support Case</button>
                </form>
              </div>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function filterTickets() {
      const query = document.getElementById('ticketSearch').value.toLowerCase();
      const items = document.querySelectorAll('.ticket-row-item');
      items.forEach(item => {
        const sub = item.getAttribute('data-subject').toLowerCase();
        if (sub.includes(query)) {
          item.style.display = 'block';
        } else {
          item.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>
