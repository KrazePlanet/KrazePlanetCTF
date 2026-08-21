<?php
// ============================================================
// Database Configuration
// ============================================================
$dbname = 'KrazePlanet';
$username = 'root';
$password = '';
$hosts = ['127.0.0.1', 'localhost'];

// Table Names Configuration
$table_users = 'articles_users';
$table_events = 'articles_events';
$table_polls = 'articles_polls';
$table_poll_options = 'articles_poll_options';


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

// Initialize Events & Polls database tables
function initializeDatabase($pdo) {
    global $table_users, $table_events, $table_polls, $table_poll_options;
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

    // Events table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$table_events} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_date VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Polls table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$table_polls} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            question VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Poll Options table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$table_poll_options} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            poll_id INT NOT NULL,
            option_text VARCHAR(255) NOT NULL,
            votes INT DEFAULT 0
        )
    ");

    // Seed default admin user if none exists
    $checkAdmin = $pdo->prepare("SELECT COUNT(*) FROM {$table_users} WHERE role = 'admin'");
    $checkAdmin->execute();
    if ($checkAdmin->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO {$table_users} (username, email, password, full_name, role) VALUES (?, ?, ?, 'Administrator', 'admin')");
        $stmt->execute([
            'admin',
            'admin@community.local',
            password_hash('admin123', PASSWORD_DEFAULT)
        ]);
    }

    // Seed default events if none exist
    $checkEvents = $pdo->query("SELECT COUNT(*) FROM {$table_events}");
    if ($checkEvents->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO {$table_events} (user_id, title, description, event_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            1,
            'CTF Bootcamp 2026 Kickoff',
            'Join our comprehensive hands-on training bootcamp focusing on memory corruption, binary exploitation, and advanced web vulnerabilities.',
            'August 18, 2026 - 18:00 UTC'
        ]);
        $stmt->execute([
            1,
            'Global Cyber Hackathon Meetup',
            'Collaborate with researchers, developers, and designers to build next-generation privacy and security open-source tools.',
            'September 05, 2026 - 15:30 UTC'
        ]);
    }

    // Seed default polls if none exist
    $checkPolls = $pdo->query("SELECT COUNT(*) FROM {$table_polls}");
    if ($checkPolls->fetchColumn() == 0) {
        // Poll 1
        $stmt = $pdo->prepare("INSERT INTO {$table_polls} (user_id, question, description) VALUES (?, ?, ?)");
        $stmt->execute([
            1,
            'Which security domain do you find most interesting?',
            'Cast your vote on the core security subfields you focus on or want to learn.'
        ]);
        $pollId1 = $pdo->lastInsertId();

        $stmtOption = $pdo->prepare("INSERT INTO {$table_poll_options} (poll_id, option_text, votes) VALUES (?, ?, ?)");
        $stmtOption->execute([$pollId1, 'Web Application Security & Pentesting', 42]);
        $stmtOption->execute([$pollId1, 'Binary Exploitation & Kernel Pwn', 28]);
        $stmtOption->execute([$pollId1, 'Reverse Engineering & Malware Analysis', 35]);
        $stmtOption->execute([$pollId1, 'Cryptography & Protocol Analysis', 19]);

        // Poll 2
        $stmt->execute([
            1,
            'Which programming language is best for security scripts?',
            'Select your go-to language for building exploits, scanners, and automation scripts.'
        ]);
        $pollId2 = $pdo->lastInsertId();
        $stmtOption->execute([$pollId2, 'Python', 94]);
        $stmtOption->execute([$pollId2, 'Go (Golang)', 41]);
        $stmtOption->execute([$pollId2, 'Rust', 33]);
        $stmtOption->execute([$pollId2, 'Bash / Shell Scripts', 27]);
    }
}
initializeDatabase($pdo);

// Session management
session_start();

// Authentication Helpers
function getCurrentUser($pdo) {
    global $table_users;
    if (!isset($_SESSION['article_user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM {$table_users} WHERE id = ?");
    $stmt->execute([$_SESSION['article_user_id']]);
    $u = $stmt->fetch();
    if (!$u) {
        unset($_SESSION['article_user_id']);
        unset($_SESSION['article_username']);
        unset($_SESSION['article_role']);
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
        $_SESSION['article_user_id'] = $user['id'];
        $_SESSION['article_username'] = $user['username'];
        $_SESSION['article_role'] = $user['role'];
        return true;
    }
    return false;
}

function logoutUser() {
    unset($_SESSION['article_user_id']);
    unset($_SESSION['article_username']);
    unset($_SESSION['article_role']);
    header("Location: index.php");
    exit();
}

// --- Logout ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
}

// --- Register ---
$authError = ''; $authSuccess = '';
if (!isset($_SESSION['article_user_id']) && isset($_POST['register'])) {
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
if (!isset($_SESSION['article_user_id']) && isset($_POST['login'])) {
    if (loginUser($pdo, $_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: index.php'); exit();
    } else {
        $authError = 'Invalid username or password';
    }
}

// --- Auth Gate ---
if (!isset($_SESSION['article_user_id'])) {
    $authView = $_GET['view'] ?? 'login';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Community Portal - Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --header-bg: #004e5d;
      --header-dark: #003b47;
      --accent-color: #006072;
      --btn-primary: #005666;
      --btn-primary-hover: #003f4c;
    }
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      color: #e2e8f0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
    }
    .auth-box {
      background: rgba(30, 41, 59, 0.85);
      border-radius: 14px;
      border: 1px solid #334155;
      padding: 2.5rem;
      width: 100%;
      max-width: 440px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.35);
    }
    .auth-title {
      font-size: 1.9rem;
      font-weight: 700;
      background: linear-gradient(90deg, #38bdf8, #0ea5e9);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-align: center;
      margin-bottom: 0.2rem;
    }
    .auth-sub {
      text-align: center;
      font-size: 0.85rem;
      color: #94a3b8;
      margin-bottom: 1.5rem;
    }
    .form-control {
      background: rgba(30, 41, 59, 0.7);
      border: 1px solid #334155;
      color: #e2e8f0;
      padding: 0.7rem 1rem;
    }
    .form-control:focus {
      background: rgba(30, 41, 59, 0.9);
      border-color: var(--accent-color);
      box-shadow: 0 0 0 0.2rem rgba(0, 96, 114, 0.25);
      color: #e2e8f0;
    }
    .form-label {
      font-weight: 500;
      color: #cbd5e0;
      font-size: 0.9rem;
    }
    .btn-primary {
      background-color: var(--btn-primary);
      border: none;
      padding: 0.7rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s;
      width: 100%;
    }
    .btn-primary:hover {
      background-color: var(--btn-primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 96, 114, 0.3);
    }
    .alert-danger {
      background: rgba(245, 101, 101, 0.1);
      border: 1px solid #f56565;
      color: #f56565;
    }
    .alert-success {
      background: rgba(72, 187, 120, 0.1);
      border: 1px solid #48bb78;
      color: #48bb78;
    }
    .demo-box {
      background: rgba(15, 23, 42, 0.7);
      border-radius: 8px;
      padding: 0.9rem 1rem;
      margin-top: 1rem;
      border-left: 4px solid var(--accent-color);
      font-size: 0.85rem;
      color: #94a3b8;
    }
    .demo-box strong {
      color: var(--accent-color);
      display: block;
      margin-bottom: 0.25rem;
    }
    .switch-link {
      text-align: center;
      margin-top: 1rem;
      font-size: 0.9rem;
    }
    .switch-link a {
      color: #38bdf8;
      text-decoration: none;
    }
  </style>
</head>
<body>
<div class="auth-box">
  <h1 class="auth-title"><i class="bi bi-calendar-event me-2"></i><?php echo $authView === 'register' ? 'Create Account' : 'Login'; ?></h1>
  <p class="auth-sub">Events & Polls Portal</p>
  <?php if ($authError): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($authError); ?></div>
  <?php endif; ?>
  <?php if ($authSuccess): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($authSuccess); ?></div>
  <?php endif; ?>
  <?php if ($authView === 'register'): ?>
  <form method="POST">
    <div class="row g-2 mb-2">
      <div class="col-6"><label class="form-label">Username *</label>
        <input type="text" class="form-control" name="reg_username" value="<?php echo htmlspecialchars($_POST['reg_username'] ?? ''); ?>" required></div>
      <div class="col-6"><label class="form-label">Email *</label>
        <input type="email" class="form-control" name="reg_email" value="<?php echo htmlspecialchars($_POST['reg_email'] ?? ''); ?>" required></div>
    </div>
    <div class="mb-2"><label class="form-label">Full Name</label>
      <input type="text" class="form-control" name="reg_full_name" value="<?php echo htmlspecialchars($_POST['reg_full_name'] ?? ''); ?>"></div>
    <div class="row g-2 mb-3">
      <div class="col-6"><label class="form-label">Password *</label>
        <input type="password" class="form-control" name="reg_password" required></div>
      <div class="col-6"><label class="form-label">Confirm *</label>
        <input type="password" class="form-control" name="reg_confirm" required></div>
    </div>
    <button type="submit" name="register" class="btn btn-primary">Create Account</button>
  </form>
  <div class="switch-link"><a href="index.php"><i class="bi bi-arrow-left me-1"></i>Back to Login</a></div>
  <?php else: ?>
  <form method="POST">
    <div class="mb-3"><label class="form-label">Username or Email</label>
      <input type="text" class="form-control" name="username" required></div>
    <div class="mb-3"><label class="form-label">Password</label>
      <input type="password" class="form-control" name="password" required></div>
    <button type="submit" name="login" class="btn btn-primary">Login</button>
  </form>
  <div class="demo-box"><strong><i class="bi bi-info-circle me-1"></i>Demo Accounts</strong>admin / admin123 &nbsp;|&nbsp; <em>or register a new account</em></div>
  <div class="switch-link"><a href="index.php?view=register"><i class="bi bi-person-plus me-1"></i>Create New Account</a></div>
  <?php endif; ?>
</div>
</body></html>
<?php exit(); }

// --- Logged In Dashboard logic ---
$user = getCurrentUser($pdo);
$error = '';
$success = '';

// Handle event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_event') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');

    if (empty($title) || empty($description) || empty($event_date)) {
        $error = 'All event fields are required.';
    } else {
        try {
            // Vulnerable: Stored unescaped to trigger Stored XSS
            $stmt = $pdo->prepare("INSERT INTO {$table_events} (user_id, title, description, event_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], $title, $description, $event_date]);
            $success = 'Community Event created successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to create event: ' . $e->getMessage();
        }
    }
}

// Handle poll creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_poll') {
    $question = trim($_POST['question'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $options = $_POST['options'] ?? [];

    $cleanOptions = array_filter(array_map('trim', $options));

    if (empty($question) || empty($description) || count($cleanOptions) < 2) {
        $error = 'Poll question, description, and at least two options are required.';
    } else {
        try {
            // Vulnerable: Stored unescaped to trigger Stored XSS
            $stmt = $pdo->prepare("INSERT INTO {$table_polls} (user_id, question, description) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $question, $description]);
            $pollId = $pdo->lastInsertId();

            $stmtOpt = $pdo->prepare("INSERT INTO {$table_poll_options} (poll_id, option_text) VALUES (?, ?)");
            foreach ($cleanOptions as $opt) {
                $stmtOpt->execute([$pollId, $opt]);
            }
            $success = 'Interactive Poll created successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to create poll: ' . $e->getMessage();
        }
    }
}

// Handle voting on polls
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vote_poll') {
    $option_id = intval($_POST['option_id'] ?? 0);
    if ($option_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE {$table_poll_options} SET votes = votes + 1 WHERE id = ?");
            $stmt->execute([$option_id]);
            $success = 'Thank you! Your vote has been recorded.';
        } catch (PDOException $e) {
            $error = 'Failed to submit vote: ' . $e->getMessage();
        }
    } else {
        $error = 'Invalid vote option selected.';
    }
}

// Get all events
$events = $pdo->query("
    SELECT e.*, u.username, u.full_name 
    FROM {$table_events} e 
    JOIN {$table_users} u ON e.user_id = u.id 
    ORDER BY e.created_at DESC
")->fetchAll();

// Get all polls
$polls = $pdo->query("
    SELECT p.*, u.username, u.full_name 
    FROM {$table_polls} p 
    JOIN {$table_users} u ON p.user_id = u.id 
    ORDER BY p.created_at DESC
")->fetchAll();

// Map options to polls
foreach ($polls as &$poll) {
    $stmtOpt = $pdo->prepare("SELECT * FROM {$table_poll_options} WHERE poll_id = ? ORDER BY id ASC");
    $stmtOpt->execute([$poll['id']]);
    $poll['options'] = $stmtOpt->fetchAll();

    // calculate total votes
    $total = 0;
    foreach ($poll['options'] as $o) {
        $total += $o['votes'];
    }
    $poll['total_votes'] = $total;
}
unset($poll);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Community Hub - Events & Polls</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --header-bg: #004e5d;
      --header-dark: #003b47;
      --banner-bg: #004553;
      --accent-color: #006072;
      --btn-primary: #005666;
      --btn-primary-hover: #003f4c;
      --text-dark: #1e293b;
      --footer-bg: #002832;
      --footer-border: #003e4d;
    }
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8fafc;
      color: #334155;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .portal-navbar {
      background-color: var(--header-bg);
      border-bottom: 3px solid #006b80;
      padding: 0.85rem 0;
    }
    .brand-logo {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      color: #ffffff;
      font-weight: 700;
      font-size: 1.45rem;
      letter-spacing: -0.5px;
      text-decoration: none;
    }
    .brand-icon {
      background: #ffffff;
      color: var(--header-bg);
      width: 38px;
      height: 38px;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 1.25rem;
    }
    .portal-nav .nav-link {
      color: #e0f2fe !important;
      font-weight: 500;
      font-size: 0.95rem;
      padding: 0.5rem 0.9rem !important;
      transition: all 0.2s;
    }
    .portal-nav .nav-link:hover,
    .portal-nav .nav-link.active {
      color: #ffda6a !important;
      font-weight: 600;
    }
    .hero-banner {
      background: linear-gradient(135deg, var(--banner-bg) 0%, var(--header-dark) 100%),
                  url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80"><path d="M40 0L80 23v46L40 92 0 69V23z" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="1"/></svg>');
      padding: 3rem 0;
      color: #ffffff;
    }
    .hero-title {
      font-size: 2.25rem;
      font-weight: 700;
      margin-bottom: 0.25rem;
    }
    .hero-sub {
      font-size: 1rem;
      color: #cbd5e1;
    }
    .form-canvas {
      padding: 3rem 0 4rem 0;
    }
    .card {
      background: #ffffff;
      border-radius: 8px;
      border: 1px solid #cbd5e1;
      color: #334155;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
      margin-bottom: 1.5rem;
    }
    .card-header {
      background-color: #f8fafc;
      border-bottom: 1px solid #cbd5e1;
      font-weight: 700;
      color: var(--header-bg);
      padding: 1rem 1.5rem;
    }
    .form-control {
      border: 1px solid #cbd5e1;
      border-radius: 4px;
      padding: 0.65rem 0.85rem;
      color: #334155;
      background-color: #ffffff;
    }
    .form-control:focus {
      border-color: var(--accent-color);
      box-shadow: 0 0 0 0.15rem rgba(0, 96, 114, 0.15);
    }
    .form-label {
      font-weight: 600;
      font-size: 0.875rem;
      color: #1e293b;
      margin-bottom: 0.35rem;
    }
    .btn-portal-submit {
      background-color: var(--btn-primary);
      color: #ffffff;
      font-weight: 600;
      padding: 0.75rem 2rem;
      border-radius: 4px;
      border: none;
      transition: all 0.2s;
    }
    .btn-portal-submit:hover {
      background-color: var(--btn-primary-hover);
      transform: translateY(-1px);
    }
    .user-pill {
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: 2rem;
      padding: 0.35rem 1rem;
      font-size: 0.875rem;
      color: #e0f2fe;
    }
    .tab-btn {
      font-weight: 600;
      padding: 0.5rem 1.5rem;
      border-radius: 50px;
      border: 1px solid var(--accent-color);
      color: var(--accent-color);
      background-color: transparent;
      transition: all 0.2s;
    }
    .tab-btn.active, .tab-btn:hover {
      background-color: var(--accent-color);
      color: #ffffff;
    }
    
    /* Event Listing Styles */
    .event-item-card {
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      border-left: 4px solid #0d9488;
      border-radius: 6px;
      padding: 1.25rem;
      margin-bottom: 1rem;
    }
    .event-meta {
      font-size: 0.8rem;
      color: #64748b;
      margin-bottom: 0.5rem;
    }
    .event-title {
      font-weight: 700;
      font-size: 1.2rem;
      color: var(--header-bg);
      margin-bottom: 0.5rem;
    }
    .event-date-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      background-color: #e0f2fe;
      color: #0369a1;
      font-weight: 700;
      font-size: 0.8rem;
      padding: 0.25rem 0.65rem;
      border-radius: 4px;
      margin-bottom: 0.75rem;
    }
    .event-desc {
      font-size: 0.95rem;
      color: #334155;
      line-height: 1.5;
    }

    /* Poll Listing Styles */
    .poll-item-card {
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      border-left: 4px solid #0ea5e9;
      border-radius: 6px;
      padding: 1.25rem;
      margin-bottom: 1rem;
    }
    .poll-question {
      font-weight: 700;
      font-size: 1.2rem;
      color: var(--header-bg);
      margin-bottom: 0.35rem;
    }
    .poll-desc {
      font-size: 0.9rem;
      color: #475569;
      margin-bottom: 1rem;
      font-style: italic;
    }
    .poll-option-row {
      margin-bottom: 0.85rem;
    }
    .poll-progress-container {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.85rem;
    }
    .poll-progress-bar {
      flex-grow: 1;
      height: 24px;
      background-color: #cbd5e1;
      border-radius: 4px;
      overflow: hidden;
      position: relative;
      cursor: pointer;
    }
    .poll-progress-fill {
      height: 100%;
      background-color: #0ea5e9;
      transition: width 0.3s;
    }
    .poll-progress-text {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      font-weight: 600;
      color: #1e293b;
      font-size: 0.8rem;
      pointer-events: none;
    }
    .poll-progress-percent {
      font-weight: 700;
      color: #1e293b;
      min-width: 45px;
      text-align: right;
    }
    .poll-meta {
      font-size: 0.75rem;
      color: #64748b;
      margin-top: 0.75rem;
    }
    .alert-danger {
      background: rgba(245, 101, 101, 0.1);
      border: 1px solid #f56565;
      color: #f56565;
    }
    .alert-success {
      background: rgba(72, 187, 120, 0.1);
      border: 1px solid #48bb78;
      color: #48bb78;
    }
    .portal-footer {
      background-color: var(--footer-bg);
      color: #94a3b8;
      border-top: 4px solid var(--footer-border);
      padding: 3.5rem 0 1.5rem 0;
      margin-top: auto;
    }
    .footer-heading {
      color: #ffffff;
      font-weight: 700;
      font-size: 1.1rem;
      margin-bottom: 1.25rem;
    }
    .footer-links-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .footer-links-list li {
      margin-bottom: 0.65rem;
    }
    .footer-links-list a {
      color: #cbd5e1;
      text-decoration: none;
      font-size: 0.9rem;
      transition: all 0.2s;
    }
    .footer-links-list a:hover {
      color: #ffffff;
      padding-left: 4px;
    }
    .social-btn {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background-color: var(--footer-border);
      color: #ffffff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }
    .social-btn:hover {
      background-color: #007791;
      color: #ffffff;
    }
    .footer-bottom-bar {
      border-top: 1px solid var(--footer-border);
      padding-top: 1.5rem;
      margin-top: 2.5rem;
      font-size: 0.85rem;
    }
  </style>
</head>
<body>

  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-md navbar-dark portal-navbar sticky-top text-white">
    <div class="container">
      <a class="brand-logo" href="../index.php">
        <div class="brand-icon">C</div>
        <span>COMMUNITY HUB</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
        data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
        aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav portal-nav ms-auto mb-2 mb-lg-0 align-items-center">
          <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="../index.php#about">About Us</a></li>
          <li class="nav-item"><a class="nav-link" href="../discussions/">Discussions</a></li>
          <li class="nav-item"><a class="nav-link active" href="index.php">Events &amp; Polls</a></li>
          <li class="nav-item"><a class="nav-link" href="../members/">Product Reviews</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php?action=logout">Logout</a></li>
        </ul>
        <div class="ms-md-3 d-flex align-items-center">
          <span class="user-pill">
            <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($user['username']); ?>
          </span>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-banner">
    <div class="container">
      <h1 class="hero-title">Events &amp; Polls Portal</h1>
      <p class="hero-sub">Organize community activities, publish calendar events, and host interactive user polls</p>
    </div>
  </section>

  <!-- Tab Controller row -->
  <div class="container mt-4">
    <div class="d-flex justify-content-center gap-3">
      <button class="tab-btn active" id="btnEventsTab" onclick="switchTab('events')"><i class="bi bi-calendar-event me-2"></i>Community Events</button>
      <button class="tab-btn" id="btnPollsTab" onclick="switchTab('polls')"><i class="bi bi-bar-chart-steps me-2"></i>Interactive Polls</button>
    </div>
  </div>

  <main class="form-canvas">
    <div class="container">
      
      <!-- General Alert Messages -->
      <?php if ($error): ?>
        <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success mb-4"><i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <!-- ==================== TAB 1: EVENTS HUB ==================== -->
      <div id="eventsTabSection">
        <div class="row g-4">
          <!-- Left: Create Event Form -->
          <div class="col-lg-5">
            <div class="card h-100">
              <div class="card-header">
                <i class="bi bi-calendar-plus me-2"></i>Create Community Event
              </div>
              <div class="card-body p-4">
                <form method="POST">
                  <input type="hidden" name="action" value="create_event">
                  <div class="mb-3">
                    <label for="event_title" class="form-label">Event Title *</label>
                    <input type="text" class="form-control" id="event_title" name="title" placeholder="e.g. Annual Tech Conference" required>
                  </div>
                  <div class="mb-3">
                    <label for="event_date" class="form-label">Event Date &amp; Time *</label>
                    <input type="text" class="form-control" id="event_date" name="event_date" placeholder="e.g. October 15, 2026 - 14:00 UTC" required>
                  </div>
                  <div class="mb-3">
                    <label for="event_description" class="form-label">Event Description *</label>
                    <textarea class="form-control" id="event_description" name="description" rows="5" placeholder="Outline the event timeline, speaker details, and access links..." required></textarea>
                  </div>
                  <button type="submit" class="btn btn-portal-submit w-100">
                    Publish Event
                  </button>
                </form>
              </div>
            </div>
          </div>

          <!-- Right: Events Listing -->
          <div class="col-lg-7">
            <div class="card">
              <div class="card-header">
                <i class="bi bi-calendar2-check me-2"></i>Scheduled Events
                <span class="badge ms-2" style="background:rgba(0,96,114,0.15);color:var(--accent-color);"><?php echo count($events); ?></span>
              </div>
              <div class="card-body p-4" style="max-height:560px;overflow-y:auto;">
                <?php if (empty($events)): ?>
                  <p class="text-muted text-center py-4">No events scheduled. Plan the first event!</p>
                <?php else: ?>
                  <?php foreach ($events as $ev): ?>
                    <div class="event-item-card">
                      <div class="event-meta">
                        <i class="bi bi-person-fill me-1"></i>Organized by <?php echo htmlspecialchars($ev['full_name'] ?: $ev['username']); ?> &bull; Posted <?php echo date('M j, Y g:i A', strtotime($ev['created_at'])); ?>
                      </div>
                      <h4 class="event-title"><?php echo $ev['title']; // Vulnerable: Direct unescaped output for CTF Stored XSS ?></h4>
                      <div class="event-date-badge">
                        <i class="bi bi-clock-fill"></i> <?php echo htmlspecialchars($ev['event_date']); ?>
                      </div>
                      <div class="event-desc">
                        <?php echo $ev['description']; // Vulnerable: Direct unescaped output for CTF Stored XSS ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== TAB 2: POLLS HUB ==================== -->
      <div id="pollsTabSection" style="display: none;">
        <div class="row g-4">
          <!-- Left: Create Poll Form -->
          <div class="col-lg-5">
            <div class="card h-100">
              <div class="card-header">
                <i class="bi bi-plus-square-fill me-2"></i>Create Interactive Poll
              </div>
              <div class="card-body p-4">
                <form method="POST">
                  <input type="hidden" name="action" value="create_poll">
                  <div class="mb-3">
                    <label for="poll_question" class="form-label">Poll Question *</label>
                    <input type="text" class="form-control" id="poll_question" name="question" placeholder="e.g. Which security tool is best?" required>
                  </div>
                  <div class="mb-3">
                    <label for="poll_description" class="form-label">Poll Description / Context *</label>
                    <textarea class="form-control" id="poll_description" name="description" rows="3" placeholder="Provide background information on this poll..." required></textarea>
                  </div>
                  
                  <label class="form-label text-muted small fw-bold">Poll Options (At least 2)</label>
                  <div id="pollOptionsInputsContainer">
                    <input type="text" class="form-control mb-2" name="options[]" placeholder="Option 1" required>
                    <input type="text" class="form-control mb-2" name="options[]" placeholder="Option 2" required>
                    <input type="text" class="form-control mb-2" name="options[]" placeholder="Option 3">
                  </div>
                  <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addPollOptionField()"><i class="bi bi-plus-circle me-1"></i>Add Option Field</button>
                  
                  <button type="submit" class="btn btn-portal-submit w-100">
                    Publish Poll
                  </button>
                </form>
              </div>
            </div>
          </div>

          <!-- Right: Polls Listing & Interactive Voting -->
          <div class="col-lg-7">
            <div class="card">
              <div class="card-header">
                <i class="bi bi-bar-chart-fill me-2"></i>Active Polls
                <span class="badge ms-2" style="background:rgba(0,96,114,0.15);color:var(--accent-color);"><?php echo count($polls); ?></span>
              </div>
              <div class="card-body p-4" style="max-height:560px;overflow-y:auto;">
                <?php if (empty($polls)): ?>
                  <p class="text-muted text-center py-4">No active polls found. Create the first poll!</p>
                <?php else: ?>
                  <?php foreach ($polls as $p): ?>
                    <div class="poll-item-card">
                      <h4 class="poll-question"><?php echo $p['question']; // Vulnerable: Direct unescaped output for CTF Stored XSS ?></h4>
                      <div class="poll-desc">
                        <?php echo $p['description']; // Vulnerable: Direct unescaped output for CTF Stored XSS ?>
                      </div>
                      
                      <!-- Options progress list -->
                      <?php foreach ($p['options'] as $o): 
                        $pct = $p['total_votes'] > 0 ? round(($o['votes'] / $p['total_votes']) * 100) : 0;
                      ?>
                        <div class="poll-option-row">
                          <div class="poll-progress-container">
                            <div class="poll-progress-bar" onclick="submitPollVote(<?php echo $o['id']; ?>)">
                              <div class="poll-progress-fill" style="width: <?php echo $pct; ?>%;"></div>
                              <div class="poll-progress-text"><?php echo htmlspecialchars($o['option_text']); ?></div>
                            </div>
                            <div class="poll-progress-percent"><?php echo $pct; ?>%</div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                      
                      <div class="poll-meta d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person-fill"></i> Created by <?php echo htmlspecialchars($p['full_name'] ?: $p['username']); ?></span>
                        <span>Total Votes: <strong><?php echo $p['total_votes']; ?></strong></span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- 4-Column Footer -->
  <footer class="portal-footer">
    <div class="container">
      <div class="row g-4">
        <!-- Col 1: Brand & Contact -->
        <div class="col-lg-3 col-md-6">
          <div class="brand-logo mb-3" href="#">
            <div class="brand-icon">C</div>
            <span>COMMUNITY HUB</span>
          </div>
          <p class="small text-light mb-2"><strong>Community Hub Portal</strong></p>
          <p class="small mb-1">600N Broad Street 5 - 276,</p>
          <p class="small mb-1">Middletown, DE 19709</p>
          <p class="small mb-3">United States</p>
          <p class="small mb-1"><i class="bi bi-envelope me-2"></i>contact@communityhub.org</p>
          <p class="small"><i class="bi bi-telephone me-2"></i>+1 800 555 0199</p>
        </div>

        <!-- Col 2: Quick Links -->
        <div class="col-lg-3 col-md-6">
          <h5 class="footer-heading">Links</h5>
          <ul class="footer-links-list">
            <li><a href="../index.php">Home</a></li>
            <li><a href="../index.php#about">About Us</a></li>
            <li><a href="../discussions/">Discussions</a></li>
            <li><a href="index.php">Events &amp; Polls Hub</a></li>
            <li><a href="../members/">Product Reviews</a></li>
            <li><a href="../index.php#contact">Contact us</a></li>
          </ul>
        </div>

        <!-- Col 3: Topics -->
        <div class="col-lg-3 col-md-6">
          <h5 class="footer-heading">Topics</h5>
          <ul class="footer-links-list">
            <li><a href="#">Technology & Innovation</a></li>
            <li><a href="#">Science & Research</a></li>
            <li><a href="#">Healthcare & Medicine</a></li>
            <li><a href="#">Social & Environmental Policy</a></li>
          </ul>
        </div>

        <!-- Col 4: Follow Us -->
        <div class="col-lg-3 col-md-6">
          <h5 class="footer-heading">Follow Us</h5>
          <div class="d-flex gap-2">
            <a href="#" class="social-btn"><i class="bi bi-facebook"></i></a>
            <a href="#" class="social-btn"><i class="bi bi-twitter"></i></a>
            <a href="#" class="social-btn"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>
      </div>

      <div class="footer-bottom-bar d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
        <p class="mb-0">&copy; 2026 Community Hub. All rights reserved.</p>
        <p class="mb-0 small text-light mt-2 mt-md-0">Empowering global connections through open academic dialog</p>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Tab Switching Controller
    function switchTab(tabName) {
      const eventsSection = document.getElementById('eventsTabSection');
      const pollsSection = document.getElementById('pollsTabSection');
      const btnEvents = document.getElementById('btnEventsTab');
      const btnPolls = document.getElementById('btnPollsTab');

      if (tabName === 'events') {
        eventsSection.style.display = 'block';
        pollsSection.style.display = 'none';
        btnEvents.classList.add('active');
        btnPolls.classList.remove('active');
      } else {
        eventsSection.style.display = 'none';
        pollsSection.style.display = 'block';
        btnEvents.classList.remove('active');
        btnPolls.classList.add('active');
      }
    }

    // Dynamic Options Addition
    let optionCount = 3;
    function addPollOptionField() {
      optionCount++;
      const container = document.getElementById('pollOptionsInputsContainer');
      const input = document.createElement('input');
      input.type = 'text';
      input.className = 'form-control mb-2';
      input.name = 'options[]';
      input.placeholder = 'Option ' + optionCount;
      container.appendChild(input);
    }

    // Interactive Poll Vote Submission
    function submitPollVote(optionId) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '';

      const actInput = document.createElement('input');
      actInput.type = 'hidden';
      actInput.name = 'action';
      actInput.value = 'vote_poll';
      form.appendChild(actInput);

      const optInput = document.createElement('input');
      optInput.type = 'hidden';
      optInput.name = 'option_id';
      optInput.value = optionId;
      form.appendChild(optInput);

      document.body.appendChild(form);
      form.submit();
    }
  </script>
</body>
</html>
