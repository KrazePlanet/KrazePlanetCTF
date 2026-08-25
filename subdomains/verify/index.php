<?php
// ── Database setup ──
$db_name = "KrazePlanet";
$db_user = "root";
$db_pass = "";
$hosts   = ["krazeplanet", "127.0.0.1", "localhost", "172.19.0.1", "host.docker.internal"];

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

// ── Users table ──
$pdo->exec("
    CREATE TABLE IF NOT EXISTS lab202_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('user','admin') NOT NULL DEFAULT 'user',
        otp CHAR(6) NOT NULL DEFAULT '000000',
        email_verified TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
// Migrate: add role column if upgrading from old schema
try { $pdo->exec("ALTER TABLE lab202_users ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER password"); } catch(Exception $e) {}

// ── Projects table ──
$pdo->exec("
    CREATE TABLE IF NOT EXISTS lab202_projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_email VARCHAR(150) NOT NULL,
        name VARCHAR(150) NOT NULL,
        description TEXT,
        status ENUM('active','in_progress','completed') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// ── Seed default users (once) ──
$seedCount = $pdo->query("SELECT COUNT(*) FROM lab202_users WHERE email IN ('admin@kzlabs.store','ali@gmail.com')")->fetchColumn();
if ($seedCount == 0) {
    $ins = $pdo->prepare("INSERT IGNORE INTO lab202_users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $seeds = [
        ['Ali',        'ali@gmail.com',        'ali@123',        'user'],
        ['Saziya',     'saziya@gmail.com',     'saziya@123',     'user'],
        ['Praveen',    'praveen@gmail.com',     'praveen@123',    'user'],
        ['Aaqib',      'aaqib@gmail.com',       'aaqib@123',      'user'],
        ['Karan',      'karan@gmail.com',       'karan@123',      'user'],
        ['Mahendra',   'mahendra@gmail.com',    'mahendra@123',   'user'],
        ['Rajvardhan', 'rajvardhan@gmail.com',  'rajvardhan@123', 'user'],
        ['Sathish',    'sathish@gmail.com',     'sathish@123',    'user'],
        ['Admin',      'admin@kzlabs.store',    'admin@123',      'admin'],
    ];
    foreach ($seeds as $s) {
        $ins->execute([$s[0], $s[1], password_hash($s[2], PASSWORD_DEFAULT), $s[3]]);
    }
}

// ── Helper: get role from DB ──
function getUserRole($pdo, $email) {
    $s = $pdo->prepare("SELECT role FROM lab202_users WHERE email = ?");
    $s->execute([$email]);
    $r = $s->fetch();
    return $r ? $r['role'] : 'user';
}

// ── AJAX: register ──
if (isset($_GET['action']) && $_GET['action'] === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $body  = json_decode(file_get_contents('php://input'), true);
    $name  = trim($body['name']     ?? '');
    $email = trim($body['email']    ?? '');
    $pass  = trim($body['password'] ?? '');

    if (!$name || !$email || !$pass) { echo json_encode(['ok'=>false,'message'=>'All fields are required.']); exit; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['ok'=>false,'message'=>'Invalid email address.']); exit; }

    $check = $pdo->prepare("SELECT id FROM lab202_users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) { echo json_encode(['ok'=>false,'message'=>'An account with this email already exists.']); exit; }

    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $ins = $pdo->prepare("INSERT INTO lab202_users (name, email, password, otp) VALUES (?, ?, ?, ?)");
    $ins->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $otp]);

    echo json_encode(['ok'=>true, 'email'=>$email, 'otp'=>$otp, 'name'=>$name]);
    exit;
}

// ── AJAX: verify OTP ──
if (isset($_GET['action']) && $_GET['action'] === 'verify' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $body  = json_decode(file_get_contents('php://input'), true);
    $email = trim($body['email'] ?? '');
    $code  = trim($body['otp']   ?? '');

    $stmt = $pdo->prepare("SELECT otp, name, role FROM lab202_users WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    // VULNERABLE: verify field is returned in JSON body — no server-side session is set.
    // Client checks response.verify === true to grant access.
    // Intercept with Burp Suite: flip "verify":false → "verify":true to bypass OTP entirely.
    if ($row && $code === $row['otp']) {
        $pdo->prepare("UPDATE lab202_users SET email_verified = 1 WHERE email = ?")->execute([$email]);
        echo json_encode(['verify'=>true, 'message'=>'Email verified successfully.', 'name'=>$row['name'], 'role'=>$row['role']]);
    } else {
        echo json_encode(['verify'=>false, 'message'=>'Invalid OTP. Please try again.']);
    }
    exit;
}

// ── AJAX: login ──
if (isset($_GET['action']) && $_GET['action'] === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $body  = json_decode(file_get_contents('php://input'), true);
    $email = trim($body['email']    ?? '');
    $pass  = trim($body['password'] ?? '');

    $stmt = $pdo->prepare("SELECT name, password, role FROM lab202_users WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if ($row && password_verify($pass, $row['password'])) {
        echo json_encode(['ok'=>true, 'name'=>$row['name'], 'email'=>$email, 'role'=>$row['role']]);
    } else {
        echo json_encode(['ok'=>false, 'message'=>'Invalid email or password.']);
    }
    exit;
}

// ── AJAX: get_users (admin only) ──
if (isset($_GET['action']) && $_GET['action'] === 'get_users' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $body  = json_decode(file_get_contents('php://input'), true);
    $email = trim($body['email'] ?? '');
    if (getUserRole($pdo, $email) !== 'admin') { echo json_encode(['ok'=>false,'message'=>'Forbidden.']); exit; }
    $rows = $pdo->query("SELECT id, name, email, role, created_at FROM lab202_users ORDER BY id ASC")->fetchAll();
    echo json_encode(['ok'=>true, 'users'=>$rows]);
    exit;
}

// ── AJAX: edit_user (admin only) ──
if (isset($_GET['action']) && $_GET['action'] === 'edit_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $body    = json_decode(file_get_contents('php://input'), true);
    $callerEmail = trim($body['caller'] ?? '');
    $userId  = intval($body['id']       ?? 0);
    $newName = trim($body['name']       ?? '');
    $newEmail= trim($body['email']      ?? '');
    if (getUserRole($pdo, $callerEmail) !== 'admin') { echo json_encode(['ok'=>false,'message'=>'Forbidden.']); exit; }
    if (!$newName || !$newEmail || !$userId) { echo json_encode(['ok'=>false,'message'=>'Missing fields.']); exit; }
    $pdo->prepare("UPDATE lab202_users SET name = ?, email = ? WHERE id = ?")->execute([$newName, $newEmail, $userId]);
    echo json_encode(['ok'=>true]);
    exit;
}

// ── AJAX: delete_user (admin only) ──
if (isset($_GET['action']) && $_GET['action'] === 'delete_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $body    = json_decode(file_get_contents('php://input'), true);
    $callerEmail = trim($body['caller'] ?? '');
    $userId  = intval($body['id']       ?? 0);
    if (getUserRole($pdo, $callerEmail) !== 'admin') { echo json_encode(['ok'=>false,'message'=>'Forbidden.']); exit; }
    $self = $pdo->prepare("SELECT email FROM lab202_users WHERE id = ?");
    $self->execute([$userId]);
    $target = $self->fetch();
    if ($target && $target['email'] === $callerEmail) { echo json_encode(['ok'=>false,'message'=>'Cannot delete your own account.']); exit; }
    $pdo->prepare("DELETE FROM lab202_users WHERE id = ?")->execute([$userId]);
    echo json_encode(['ok'=>true]);
    exit;
}

// ── AJAX: update_profile ──
if (isset($_GET['action']) && $_GET['action'] === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $body    = json_decode(file_get_contents('php://input'), true);
    $email   = trim($body['email'] ?? '');
    $newName = trim($body['name']  ?? '');
    if (!$email || !$newName) { echo json_encode(['ok'=>false,'message'=>'Missing fields.']); exit; }
    $pdo->prepare("UPDATE lab202_users SET name = ? WHERE email = ?")->execute([$newName, $email]);
    echo json_encode(['ok'=>true, 'name'=>$newName]);
    exit;
}

// ── AJAX: get_projects ──
if (isset($_GET['action']) && $_GET['action'] === 'get_projects' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $body  = json_decode(file_get_contents('php://input'), true);
    $email = trim($body['email'] ?? '');
    $rows  = $pdo->prepare("SELECT id, name, description, status, created_at FROM lab202_projects WHERE user_email = ? ORDER BY id DESC");
    $rows->execute([$email]);
    echo json_encode(['ok'=>true, 'projects'=>$rows->fetchAll()]);
    exit;
}

// ── AJAX: create_project ──
if (isset($_GET['action']) && $_GET['action'] === 'create_project' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $body  = json_decode(file_get_contents('php://input'), true);
    $email = trim($body['email']       ?? '');
    $name  = trim($body['name']        ?? '');
    $desc  = trim($body['description'] ?? '');
    if (!$email || !$name) { echo json_encode(['ok'=>false,'message'=>'Project name is required.']); exit; }
    $pdo->prepare("INSERT INTO lab202_projects (user_email, name, description) VALUES (?, ?, ?)")->execute([$email, $name, $desc]);
    echo json_encode(['ok'=>true]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureApp — Create Account</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sa-blue:      #3b82f6;
            --sa-blue-d:    #2563eb;
            --sa-blue-glow: rgba(59,130,246,0.25);
            --sa-dark:      #0f0f10;
            --sa-surface:   #18181b;
            --sa-surface2:  #1f1f23;
            --sa-border:    #2e2e33;
            --sa-text:      #f4f4f5;
            --sa-muted:     #71717a;
            --sa-green:     #22c55e;
            --sa-red:       #ef4444;
            --sa-yellow:    #f5c518;
        }

        body {
            background: var(--sa-dark);
            color: var(--sa-text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }

        /* ── App header ── */
        .app-header {
            background: var(--sa-surface);
            border-bottom: 1px solid var(--sa-border);
            padding: 0 2rem;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .app-logo {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
        }
        .app-logo-icon {
            width: 32px;
            height: 32px;
            background: var(--sa-blue);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #fff;
        }
        .app-logo-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--sa-text);
            letter-spacing: -0.01em;
        }
        .app-logo-name span { color: var(--sa-blue); }
        .app-header-right { font-size: 0.75rem; color: var(--sa-muted); }

        /* ── Page wrap ── */
        .page-wrap {
            min-height: calc(100vh - 56px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem;
            background: radial-gradient(ellipse at 50% 0%, rgba(59,130,246,0.07) 0%, transparent 65%);
        }

        /* ── Cards ── */
        .card {
            background: var(--sa-surface);
            border: 1px solid var(--sa-border);
            border-radius: 12px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.5);
        }

        /* ── Form elements ── */
        .form-group { margin-bottom: 1.1rem; }
        .form-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: #a1a1aa;
            margin-bottom: 0.35rem;
            letter-spacing: 0.03em;
        }
        .form-control {
            width: 100%;
            background: var(--sa-dark);
            border: 1px solid var(--sa-border);
            border-radius: 8px;
            color: var(--sa-text);
            padding: 0.65rem 0.9rem;
            font-size: 0.88rem;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-control:focus {
            border-color: var(--sa-blue);
            box-shadow: 0 0 0 3px var(--sa-blue-glow);
        }
        .btn-primary {
            width: 100%;
            background: var(--sa-blue);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: background 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-primary:hover { background: var(--sa-blue-d); }
        .btn-primary:active { transform: scale(0.99); }
        .btn-primary:disabled { opacity: 0.55; cursor: default; }
        .alert-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
            border-radius: 8px;
            padding: 0.65rem 0.9rem;
            font-size: 0.8rem;
            margin-top: 0.75rem;
            display: none;
        }
        .divider-text {
            text-align: center;
            font-size: 0.75rem;
            color: var(--sa-muted);
            margin: 1.25rem 0 0;
        }
        .divider-text a { color: var(--sa-blue); text-decoration: none; }

        /* ── Step indicator ── */
        .steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 2rem;
        }
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.3rem;
        }
        .step-dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            font-weight: 700;
            border: 2px solid var(--sa-border);
            color: var(--sa-muted);
            background: var(--sa-dark);
            transition: all 0.25s;
        }
        .step-dot.active  { border-color: var(--sa-blue); color: var(--sa-blue); background: var(--sa-blue-glow); }
        .step-dot.done    { border-color: var(--sa-green); background: var(--sa-green); color: #fff; }
        .step-label { font-size: 0.65rem; color: var(--sa-muted); white-space: nowrap; }
        .step-label.active { color: var(--sa-blue); }
        .step-line {
            flex: 1;
            height: 2px;
            background: var(--sa-border);
            margin: 0 0.5rem;
            margin-bottom: 1.1rem;
            transition: background 0.25s;
        }
        .step-line.done { background: var(--sa-green); }

        /* ════ VIEW 1: Register ════ */
        #registerView { width: 100%; }
        #registerView .card { width: 100%; max-width: 420px; }
        .reg-header { padding: 2rem 2rem 0; text-align: center; margin-bottom: 1.75rem; }
        .reg-header h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: 0.3rem; }
        .reg-header p  { font-size: 0.82rem; color: var(--sa-muted); }
        .reg-body { padding: 0 2rem 2rem; }

        /* ════ VIEW 2: OTP ════ */
        #otpView { display: none; width: 100%; }
        #otpView .card { width: 100%; max-width: 480px; }
        .otp-layout {
            display: flex;
            flex-direction: column;
        }

        /* Email inbox pane */
        .email-pane {
            background: var(--sa-surface2);
            border-bottom: 1px solid var(--sa-border);
            border-radius: 12px 12px 0 0;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }
        .email-pane-title {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--sa-muted);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .email-pane-title i { color: var(--sa-yellow); }
        .email-item {
            background: var(--sa-dark);
            border: 1px solid var(--sa-border);
            border-left: 3px solid var(--sa-blue);
            border-radius: 8px;
            padding: 1rem;
            flex: 1;
        }
        .email-item-meta { font-size: 0.7rem; color: var(--sa-muted); margin-bottom: 0.85rem; }
        .email-item-meta strong { color: #a1a1aa; }
        .email-item-subject {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--sa-text);
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--sa-border);
        }
        .email-item-body { font-size: 0.8rem; color: #a1a1aa; line-height: 1.65; }
        .otp-display {
            display: inline-block;
            background: var(--sa-surface);
            border: 1px solid var(--sa-blue);
            border-radius: 8px;
            padding: 0.6rem 1.25rem;
            font-family: 'Courier New', monospace;
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 0.35em;
            color: var(--sa-yellow);
            margin: 0.75rem 0;
        }

        /* OTP entry pane */
        .otp-entry-pane {
            padding: 1.5rem 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .otp-entry-header { margin-bottom: 1.5rem; }
        .otp-entry-header h3 { font-size: 1.15rem; font-weight: 700; margin-bottom: 0.3rem; }
        .otp-entry-header p  { font-size: 0.8rem; color: var(--sa-muted); }
        .otp-entry-header p strong { color: var(--sa-blue); }

        /* 6-digit OTP input boxes */
        .otp-boxes {
            display: flex;
            gap: 0.4rem;
            margin-bottom: 1.25rem;
        }
        .otp-box {
            flex: 1;
            min-width: 0;
            height: 46px;
            background: var(--sa-dark);
            border: 2px solid var(--sa-border);
            border-radius: 8px;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            color: var(--sa-text);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            caret-color: var(--sa-blue);
        }
        .otp-box:focus {
            border-color: var(--sa-blue);
            box-shadow: 0 0 0 3px var(--sa-blue-glow);
        }
        .otp-hint {
            font-size: 0.75rem;
            color: var(--sa-muted);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .otp-hint i { color: var(--sa-yellow); }

        /* ════ VIEW 3: Dashboard ════ */
        #dashboardView { display: none; width: 100%; }
        .dash-header {
            background: var(--sa-surface);
            border-bottom: 1px solid var(--sa-border);
            padding: 0 2rem;
            height: 52px;
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .dash-header-title { color: var(--sa-blue); font-weight: 700; font-size: 0.9rem; }
        .dash-nav { display: flex; gap: 0; flex: 1; }
        .dash-nav a {
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            font-size: 0.78rem;
            padding: 0 1rem;
            height: 52px;
            display: flex;
            align-items: center;
            border-bottom: 2px solid transparent;
            transition: all 0.15s;
        }
        .dash-nav a:hover, .dash-nav a.active { color: #fff; border-bottom-color: var(--sa-blue); }
        .dash-user { display: flex; align-items: center; gap: 0.5rem; font-size: 0.78rem; color: rgba(255,255,255,0.7); }
        .dash-user-dot { width: 8px; height: 8px; background: var(--sa-green); border-radius: 50%; }
        .dash-body { padding: 2rem; background: #0a0a0b; min-height: calc(100vh - 148px); }
        .bypass-alert {
            background: rgba(34,197,94,0.08);
            border: 1px solid rgba(34,197,94,0.25);
            border-left: 4px solid var(--sa-green);
            border-radius: 8px;
            padding: 0.9rem 1.1rem;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.82rem;
            color: #86efac;
        }
        .user-profile-card {
            background: var(--sa-surface);
            border: 1px solid var(--sa-border);
            border-radius: 10px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
            max-width: 480px;
        }
        .user-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--sa-blue), #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }
        .user-info h3 { font-size: 1rem; font-weight: 700; margin-bottom: 0.15rem; }
        .user-info p  { font-size: 0.78rem; color: var(--sa-muted); }
        .user-badge {
            margin-top: 0.4rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: rgba(34,197,94,0.12);
            border: 1px solid rgba(34,197,94,0.25);
            color: #86efac;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
        }
        .dash-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.75rem;
            max-width: 700px;
        }
        .stat-card {
            background: var(--sa-surface);
            border: 1px solid var(--sa-border);
            border-radius: 8px;
            padding: 1.1rem;
        }
        .stat-label { font-size: 0.7rem; color: var(--sa-muted); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 0.4rem; }
        .stat-val   { font-size: 1.6rem; font-weight: 700; color: var(--sa-blue); }
        .stat-sub   { font-size: 0.7rem; color: var(--sa-muted); margin-top: 0.15rem; }

        /* ── Lab info box ── */
        .lab-info-box {
            background: linear-gradient(135deg, #0f172a, #1e1b4b);
            border: 1px solid #334155;
            border-left: 4px solid var(--sa-blue);
            border-radius: 8px;
            padding: 1.1rem 1.25rem;
            margin-top: 2rem;
            max-width: 900px;
            color: #e2e8f0;
        }
        .lab-info-box h4 {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--sa-blue);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .lab-info-box p { font-size: 0.82rem; color: #94a3b8; line-height: 1.65; }
        .lab-info-box code {
            background: rgba(255,255,255,0.08);
            padding: 0.1rem 0.35rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            color: #7dd3fc;
        }
        .lab-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1.25rem;
            margin-top: 0.65rem;
            padding-top: 0.65rem;
            border-top: 1px solid #1e293b;
        }
        .lab-meta-item { font-size: 0.72rem; color: #64748b; }
        .lab-meta-item strong { color: #94a3b8; }

        /* ── Admin badge ── */
        .admin-badge {
            display: inline-flex; align-items: center; gap: 0.3rem;
            background: rgba(245,197,24,0.12); border: 1px solid rgba(245,197,24,0.35);
            color: #f5c518; font-size: 0.68rem; font-weight: 700;
            padding: 0.1rem 0.45rem; border-radius: 4px; letter-spacing: 0.03em;
        }

        /* ── Tab panels ── */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ── Section header ── */
        .section-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.25rem;
        }
        .section-title { font-size: 1rem; font-weight: 700; }
        .section-sub   { font-size: 0.78rem; color: var(--sa-muted); margin-top: 0.15rem; }
        .btn-sm {
            background: var(--sa-blue); color: #fff; border: none; border-radius: 6px;
            padding: 0.45rem 0.9rem; font-size: 0.78rem; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 0.4rem; transition: background 0.2s;
        }
        .btn-sm:hover { background: var(--sa-blue-d); }
        .btn-sm-danger { background: rgba(239,68,68,0.12); color: #ef4444; border: 1px solid rgba(239,68,68,0.25); }
        .btn-sm-danger:hover { background: rgba(239,68,68,0.22); }
        .btn-sm-edit { background: rgba(59,130,246,0.12); color: var(--sa-blue); border: 1px solid rgba(59,130,246,0.25); }
        .btn-sm-edit:hover { background: rgba(59,130,246,0.22); }

        /* ── Data table ── */
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        .data-table th {
            text-align: left; padding: 0.6rem 0.9rem;
            font-size: 0.68rem; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase;
            color: var(--sa-muted); border-bottom: 1px solid var(--sa-border);
        }
        .data-table td {
            padding: 0.75rem 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.04);
            color: rgba(255,255,255,0.85); vertical-align: middle;
        }
        .data-table tr:hover td { background: rgba(255,255,255,0.02); }
        .role-badge {
            display: inline-flex; align-items: center; gap: 0.25rem;
            font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 3px;
        }
        .role-badge.admin { background: rgba(245,197,24,0.12); color: #f5c518; border: 1px solid rgba(245,197,24,0.3); }
        .role-badge.user  { background: rgba(59,130,246,0.10); color: var(--sa-blue); border: 1px solid rgba(59,130,246,0.25); }
        .status-badge {
            display: inline-block; font-size: 0.65rem; font-weight: 700;
            padding: 0.1rem 0.45rem; border-radius: 3px; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .status-badge.active      { background: rgba(34,197,94,0.12); color: #86efac; border: 1px solid rgba(34,197,94,0.25); }
        .status-badge.in_progress { background: rgba(245,197,24,0.10); color: #fde047; border: 1px solid rgba(245,197,24,0.25); }
        .status-badge.completed   { background: rgba(148,163,184,0.10); color: #94a3b8; border: 1px solid rgba(148,163,184,0.2); }
        .table-wrap {
            background: var(--sa-surface); border: 1px solid var(--sa-border);
            border-radius: 10px; overflow: hidden;
        }
        .table-empty {
            text-align: center; padding: 3rem 1rem;
            color: var(--sa-muted); font-size: 0.82rem;
        }
        .table-empty i { font-size: 2rem; display: block; margin-bottom: 0.75rem; color: var(--sa-border); }

        /* ── Settings form ── */
        .settings-card {
            background: var(--sa-surface); border: 1px solid var(--sa-border);
            border-radius: 10px; padding: 1.75rem; max-width: 520px;
        }
        .settings-card h3 { font-size: 0.9rem; font-weight: 700; margin-bottom: 1.25rem; }
        .settings-note { font-size: 0.72rem; color: var(--sa-muted); margin-top: 0.35rem; }
        .input-readonly {
            width: 100%; background: rgba(255,255,255,0.03); border: 2px solid var(--sa-border);
            border-radius: 8px; padding: 0.6rem 0.85rem; font-size: 0.88rem;
            color: var(--sa-muted); cursor: not-allowed;
        }

        /* ── Billing ── */
        .billing-card {
            background: var(--sa-surface); border: 1px solid var(--sa-border);
            border-radius: 10px; padding: 1.75rem; max-width: 520px; margin-bottom: 1.25rem;
        }
        .billing-plan-name { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem; }
        .billing-plan-sub  { font-size: 0.8rem; color: var(--sa-muted); margin-bottom: 1.25rem; }
        .usage-row { margin-bottom: 0.85rem; }
        .usage-label { display: flex; justify-content: space-between; font-size: 0.75rem; color: #a1a1aa; margin-bottom: 0.3rem; }
        .usage-bar { height: 6px; background: rgba(255,255,255,0.07); border-radius: 3px; overflow: hidden; }
        .usage-fill { height: 100%; background: var(--sa-blue); border-radius: 3px; transition: width 0.5s; }
        .billing-upgrade {
            width: 100%; padding: 0.7rem; background: linear-gradient(135deg,#3b82f6,#8b5cf6);
            color: #fff; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600;
            cursor: pointer; transition: opacity 0.2s;
        }
        .billing-upgrade:hover { opacity: 0.88; }

        /* ── Project card grid ── */
        .projects-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(240px,1fr));
            gap: 1rem; margin-top: 1rem;
        }
        .project-card {
            background: var(--sa-surface); border: 1px solid var(--sa-border);
            border-radius: 10px; padding: 1.1rem;
        }
        .project-name { font-size: 0.88rem; font-weight: 700; margin-bottom: 0.3rem; }
        .project-desc { font-size: 0.75rem; color: var(--sa-muted); margin-bottom: 0.75rem; line-height: 1.5; min-height: 2rem; }
        .project-meta { display: flex; align-items: center; justify-content: space-between; }
        .project-date { font-size: 0.68rem; color: #52525b; }

        /* ── Modal ── */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.7);
            display: flex; align-items: center; justify-content: center;
            z-index: 1000; padding: 1rem;
        }
        .modal-overlay.hidden { display: none; }
        .modal-box {
            background: var(--sa-surface); border: 1px solid var(--sa-border);
            border-radius: 12px; padding: 1.75rem; width: 100%; max-width: 420px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.7);
        }
        .modal-title { font-size: 1rem; font-weight: 700; margin-bottom: 1.25rem; }
        .modal-actions { display: flex; gap: 0.6rem; justify-content: flex-end; margin-top: 1.25rem; }
        .btn-cancel {
            background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.7);
            border: 1px solid var(--sa-border); border-radius: 6px;
            padding: 0.45rem 0.9rem; font-size: 0.78rem; cursor: pointer;
        }

        /* ── Toast ── */
        .toast {
            position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 2000;
            background: var(--sa-surface); border: 1px solid var(--sa-border);
            border-radius: 8px; padding: 0.8rem 1.1rem;
            display: flex; align-items: center; gap: 0.6rem;
            font-size: 0.8rem; box-shadow: 0 8px 32px rgba(0,0,0,0.5);
            transform: translateY(8px); opacity: 0;
            transition: all 0.25s; pointer-events: none;
        }
        .toast.show { transform: translateY(0); opacity: 1; pointer-events: auto; }
        .toast.success { border-left: 3px solid var(--sa-green); color: #86efac; }
        .toast.error   { border-left: 3px solid #ef4444; color: #fca5a5; }
    </style>
</head>
<body>

    <!-- App header -->
    <div class="app-header">
        <a class="app-logo" href="#">
            <div class="app-logo-icon"><i class="bi bi-shield-check"></i></div>
            <span class="app-logo-name">Secure<span>App</span></span>
        </a>
        <div class="app-header-right">Enterprise Security Platform</div>
    </div>

    <!-- ════════════════════ VIEW 1: REGISTER ════════════════════ -->
    <div class="page-wrap" id="registerView">
        <div class="card">
            <div class="reg-header">
                <div class="steps" id="regSteps">
                    <div class="step">
                        <div class="step-dot active" id="dot1">1</div>
                        <div class="step-label active">Register</div>
                    </div>
                    <div class="step-line" id="line1"></div>
                    <div class="step">
                        <div class="step-dot" id="dot2">2</div>
                        <div class="step-label" id="label2">Verify Email</div>
                    </div>
                    <div class="step-line" id="line2"></div>
                    <div class="step">
                        <div class="step-dot" id="dot3">3</div>
                        <div class="step-label" id="label3">Dashboard</div>
                    </div>
                </div>
                <h2 id="regCardTitle">Create your account</h2>
                <p id="regCardSub">Join SecureApp — enter your details to get started</p>
            </div>
            <div id="regFormBody" class="reg-body">
                <form id="registerForm" onsubmit="submitRegister(event)">
                    <div class="form-group">
                        <label class="form-label" for="regName">Full Name</label>
                        <input type="text" id="regName" class="form-control" placeholder="John Doe" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="regEmail">Email Address</label>
                        <input type="email" id="regEmail" class="form-control" placeholder="you@example.com" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="regPassword">Password</label>
                        <input type="password" id="regPassword" class="form-control" placeholder="Min. 6 characters">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="regConfirm">Confirm Password</label>
                        <input type="password" id="regConfirm" class="form-control" placeholder="Repeat password">
                    </div>
                    <button type="submit" class="btn-primary" id="regBtn">
                        <i class="bi bi-person-plus-fill"></i> Create Account
                    </button>
                    <div class="alert-error" id="regError"></div>
                </form>
                <div class="divider-text">Already have an account? <a href="#" onclick="showSignIn(); return false;">Sign in</a></div>
            </div>

            <!-- Sign-in body (hidden by default) -->
            <div id="signinBody" class="reg-body" style="display:none;">
                <form id="signinForm" onsubmit="submitSignIn(event)">
                    <div class="form-group">
                        <label class="form-label" for="siEmail">Email Address</label>
                        <input type="email" id="siEmail" class="form-control" placeholder="you@example.com" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="siPassword">Password</label>
                        <input type="password" id="siPassword" class="form-control" placeholder="Enter your password">
                    </div>
                    <button type="submit" class="btn-primary" id="siBtn">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>
                    <div class="alert-error" id="siError"></div>
                </form>
                <div class="divider-text">Don't have an account? <a href="#" onclick="showRegister(); return false;">Register</a></div>
            </div>
        </div>
    </div>

    <!-- ════════════════════ VIEW 2: OTP VERIFY ════════════════════ -->
    <div class="page-wrap" id="otpView">
        <div class="card">
            <div class="otp-layout">
                <!-- Left: Simulated email inbox -->
                <div class="email-pane">
                    <div class="email-pane-title">
                        <i class="bi bi-envelope-fill"></i> Simulated Email Inbox
                    </div>
                    <div class="email-item">
                        <div class="email-item-meta">
                            <strong>From:</strong> noreply@secureapp.io &nbsp;&nbsp;
                            <strong>To:</strong> <span id="emailTo">—</span>
                        </div>
                        <div class="email-item-subject">
                            <i class="bi bi-shield-lock" style="color:var(--sa-blue);"></i>
                            Verify your SecureApp account
                        </div>
                        <div class="email-item-body">
                            Hi <strong id="emailName">there</strong>,<br><br>
                            Thank you for signing up for SecureApp. Please use the verification
                            code below to confirm your email address.<br><br>
                            <strong style="color:#a1a1aa;">Your verification code:</strong><br>
                            <div class="otp-display" id="otpDisplay">------</div>
                            <br>
                            This code expires in <strong>10 minutes</strong>.<br><br>
                            <span style="font-size:0.72rem;color:#52525b;">
                                If you did not create an account, please ignore this email.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right: OTP entry -->
                <div class="otp-entry-pane">
                    <div class="otp-entry-header">
                        <div class="steps" style="margin-bottom:1.25rem;">
                            <div class="step">
                                <div class="step-dot done">
                                    <i class="bi bi-check" style="font-size:0.9rem;"></i>
                                </div>
                                <div class="step-label">Register</div>
                            </div>
                            <div class="step-line done"></div>
                            <div class="step">
                                <div class="step-dot active">2</div>
                                <div class="step-label active">Verify Email</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step">
                                <div class="step-dot">3</div>
                                <div class="step-label">Dashboard</div>
                            </div>
                        </div>
                        <h3><i class="bi bi-envelope-check" style="color:var(--sa-blue);"></i> Check your email</h3>
                        <p>Enter the 6-digit code sent to <strong id="otpEmailHint">your email</strong></p>
                    </div>
                    <form id="otpForm" onsubmit="submitOtp(event)">
                        <div class="otp-boxes">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                        </div>
                        <button type="submit" class="btn-primary" id="otpBtn">
                            <i class="bi bi-shield-check-fill"></i> Verify Code
                        </button>
                        <div class="alert-error" id="otpError"></div>
                    </form>
                    <div class="divider-text" style="margin-top:1rem;">
                        Didn't receive a code? <a href="#" onclick="return false;">Resend</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════ VIEW 3: DASHBOARD ════════════════════ -->
    <div id="dashboardView">
        <div class="dash-header">
            <span class="dash-header-title"><i class="bi bi-grid-fill"></i> Dashboard</span>
            <nav class="dash-nav">
                <a href="#" class="active" onclick="switchTab('overview',this); return false;">Overview</a>
                <a href="#" onclick="switchTab('projects',this); return false;">Projects</a>
                <a href="#" id="teamTabLink" onclick="switchTab('team',this); return false;">Team</a>
                <a href="#" onclick="switchTab('settings',this); return false;">Settings</a>
                <a href="#" onclick="switchTab('billing',this); return false;">Billing</a>
                <a href="#" onclick="logout(); return false;" style="color:#ef4444;"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
            <div class="dash-user">
                <div class="dash-user-dot"></div>
                <span id="dashUserName">User</span>
                <span class="admin-badge" id="adminBadge" style="display:none;"><i class="bi bi-key-fill"></i> Admin</span>
            </div>
        </div>

        <div class="dash-body">

            <!-- ══ TAB: OVERVIEW ══ -->
            <div class="tab-panel active" id="tab-overview">
                <div class="bypass-alert" id="bypassAlert">
                    <i class="bi bi-check-circle-fill" style="font-size:1.2rem;color:var(--sa-green);flex-shrink:0;margin-top:0.1rem;"></i>
                    <div>
                        <strong>OTP Bypass Successful!</strong> — You flipped
                        <code style="background:rgba(255,255,255,0.1);padding:0.1rem 0.3rem;border-radius:3px;font-family:monospace;color:#86efac;">"verify":false</code>
                        →
                        <code style="background:rgba(255,255,255,0.1);padding:0.1rem 0.3rem;border-radius:3px;font-family:monospace;color:#86efac;">"verify":true</code>
                        in the server response. The app granted access without a valid OTP — no server-side session was validated.
                    </div>
                </div>

                <div class="user-profile-card">
                    <div class="user-avatar" id="dashAvatar">U</div>
                    <div class="user-info">
                        <h3 id="dashFullName">Welcome!</h3>
                        <p id="dashEmail">user@example.com</p>
                        <div class="user-badge"><i class="bi bi-check-circle-fill"></i> Email Verified</div>
                    </div>
                </div>

                <div class="dash-stats">
                    <div class="stat-card">
                        <div class="stat-label"><i class="bi bi-folder2-open"></i> Projects</div>
                        <div class="stat-val" id="statProjects">—</div>
                        <div class="stat-sub">Get started →</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label"><i class="bi bi-people"></i> Team Members</div>
                        <div class="stat-val" id="statTeam">—</div>
                        <div class="stat-sub">Your organisation</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label"><i class="bi bi-activity"></i> API Calls</div>
                        <div class="stat-val">—</div>
                        <div class="stat-sub">No activity yet</div>
                    </div>
                </div>
            </div>

            <!-- ══ TAB: PROJECTS ══ -->
            <div class="tab-panel" id="tab-projects">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="bi bi-folder2-open" style="color:var(--sa-blue);"></i> Projects</div>
                        <div class="section-sub">Manage your workspace projects</div>
                    </div>
                    <button class="btn-sm" onclick="openProjectModal()"><i class="bi bi-plus-lg"></i> New Project</button>
                </div>
                <div id="projectsContainer">
                    <div class="table-empty"><i class="bi bi-folder2"></i>Loading projects…</div>
                </div>
            </div>

            <!-- ══ TAB: TEAM ══ -->
            <div class="tab-panel" id="tab-team">
                <div class="section-header">
                    <div>
                        <div class="section-title" id="teamSectionTitle"><i class="bi bi-people" style="color:var(--sa-blue);"></i> Team</div>
                        <div class="section-sub" id="teamSectionSub">Your organisation members</div>
                    </div>
                </div>
                <div id="teamContainer">
                    <div class="table-empty"><i class="bi bi-people"></i>Loading…</div>
                </div>
            </div>

            <!-- ══ TAB: SETTINGS ══ -->
            <div class="tab-panel" id="tab-settings">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="bi bi-gear" style="color:var(--sa-blue);"></i> Account Settings</div>
                        <div class="section-sub">Update your profile information</div>
                    </div>
                </div>
                <div class="settings-card">
                    <h3>Profile Information</h3>
                    <form onsubmit="saveProfile(event)">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" id="settingsName" class="form-control" placeholder="Your full name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <div class="input-readonly" id="settingsEmail">—</div>
                            <div class="settings-note">Email cannot be changed here.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Member Since</label>
                            <div class="input-readonly" id="settingsSince">—</div>
                        </div>
                        <button type="submit" class="btn-primary" id="saveProfileBtn">
                            <i class="bi bi-check-lg"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- ══ TAB: BILLING ══ -->
            <div class="tab-panel" id="tab-billing">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="bi bi-credit-card" style="color:var(--sa-blue);"></i> Billing</div>
                        <div class="section-sub">Manage your subscription and usage</div>
                    </div>
                </div>
                <div class="billing-card">
                    <div class="billing-plan-name">SecureApp Free Plan</div>
                    <div class="billing-plan-sub">You are on the free tier. Upgrade for unlimited access.</div>
                    <div class="usage-row">
                        <div class="usage-label"><span>Storage</span><span>0 / 1 GB</span></div>
                        <div class="usage-bar"><div class="usage-fill" style="width:2%;"></div></div>
                    </div>
                    <div class="usage-row">
                        <div class="usage-label"><span>API Requests</span><span>0 / 10,000 / mo</span></div>
                        <div class="usage-bar"><div class="usage-fill" style="width:0%;"></div></div>
                    </div>
                    <div class="usage-row">
                        <div class="usage-label"><span>Team Members</span><span id="billingTeam">1 / 3</span></div>
                        <div class="usage-bar"><div class="usage-fill" id="billingTeamFill" style="width:33%;"></div></div>
                    </div>
                    <button class="billing-upgrade" style="margin-top:0.5rem;" onclick="showToast('Upgrade flow is not available in this lab demo.','error')">
                        <i class="bi bi-lightning-fill"></i> Upgrade to Pro — $12/mo
                    </button>
                </div>
                <div class="billing-card" style="font-size:0.8rem;color:var(--sa-muted);">
                    <strong style="color:var(--sa-text);display:block;margin-bottom:0.5rem;">Payment Method</strong>
                    No payment method on file. Add one to upgrade.
                </div>
            </div>

        </div><!-- /dash-body -->
    </div><!-- /dashboardView -->

    <!-- ══ MODAL: New Project ══ -->
    <div class="modal-overlay hidden" id="projectModal">
        <div class="modal-box">
            <div class="modal-title"><i class="bi bi-folder-plus" style="color:var(--sa-blue);"></i> New Project</div>
            <div class="form-group">
                <label class="form-label">Project Name</label>
                <input type="text" id="modalProjectName" class="form-control" placeholder="e.g. API Redesign">
            </div>
            <div class="form-group">
                <label class="form-label">Description <span style="color:var(--sa-muted);font-weight:400;">(optional)</span></label>
                <input type="text" id="modalProjectDesc" class="form-control" placeholder="Short description">
            </div>
            <div id="modalProjectError" class="alert-error" style="display:none;"></div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeProjectModal()">Cancel</button>
                <button class="btn-sm" onclick="createProject()"><i class="bi bi-check-lg"></i> Create</button>
            </div>
        </div>
    </div>

    <!-- ══ MODAL: Edit User (Admin) ══ -->
    <div class="modal-overlay hidden" id="editUserModal">
        <div class="modal-box">
            <div class="modal-title"><i class="bi bi-pencil-square" style="color:var(--sa-blue);"></i> Edit User</div>
            <input type="hidden" id="editUserId">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" id="editUserName" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" id="editUserEmail" class="form-control">
            </div>
            <div id="editUserError" class="alert-error" style="display:none;"></div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeEditUserModal()">Cancel</button>
                <button class="btn-sm" onclick="saveEditUser()"><i class="bi bi-check-lg"></i> Save</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"><i class="bi bi-check-circle-fill"></i><span id="toastMsg"></span></div>

    <script>
    let currentEmail = '';
    let currentName  = '';
    let currentRole  = 'user';
    let _toastTimer  = null;

    // ════ TOAST ════
    function showToast(msg, type) {
        const t = document.getElementById('toast');
        const i = t.querySelector('i');
        t.className = 'toast ' + (type || 'success');
        i.className = type === 'error' ? 'bi bi-x-circle-fill' : 'bi bi-check-circle-fill';
        document.getElementById('toastMsg').textContent = msg;
        t.classList.add('show');
        clearTimeout(_toastTimer);
        _toastTimer = setTimeout(() => t.classList.remove('show'), 3000);
    }

    // ════ SESSION ════
    function saveSession(name, email, role, bypassed) {
        localStorage.setItem('lab202_session', JSON.stringify({ name, email, role: role||'user', bypassed: !!bypassed }));
    }
    function logout() {
        localStorage.removeItem('lab202_session');
        location.reload();
    }

    // ════ DASHBOARD INIT ════
    function showDashboard(name, email, role, bypassed) {
        currentEmail = email;
        currentName  = name;
        currentRole  = role || 'user';

        document.getElementById('dashUserName').textContent = name;
        document.getElementById('dashFullName').textContent = name;
        document.getElementById('dashEmail').textContent    = email;
        document.getElementById('dashAvatar').textContent   = name.charAt(0).toUpperCase();
        document.getElementById('bypassAlert').style.display   = bypassed ? 'flex' : 'none';
        document.getElementById('adminBadge').style.display    = currentRole === 'admin' ? 'inline-flex' : 'none';
        document.getElementById('registerView').style.display  = 'none';
        document.getElementById('otpView').style.display       = 'none';
        document.getElementById('dashboardView').style.display = 'block';

        // Settings tab pre-fill
        document.getElementById('settingsName').value          = name;
        document.getElementById('settingsEmail').textContent   = email;
        document.getElementById('settingsSince').textContent   = 'Account active';

        // Team tab label
        if (currentRole === 'admin') {
            document.getElementById('teamSectionTitle').innerHTML = '<i class="bi bi-people" style="color:var(--sa-blue);"></i> User Management';
            document.getElementById('teamSectionSub').textContent = 'Admin Control Panel — edit or remove any account';
        }

        loadOverviewStats();
    }

    // ════ TABS ════
    function switchTab(tab, el) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.dash-nav a').forEach(a => a.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        if (el) el.classList.add('active');

        if (tab === 'projects') loadProjects();
        if (tab === 'team')     loadTeam();
    }

    // ════ OVERVIEW STATS ════
    async function loadOverviewStats() {
        try {
            const pr = await fetch('?action=get_projects', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ email: currentEmail })
            });
            const pd = await pr.json();
            document.getElementById('statProjects').textContent = pd.ok ? pd.projects.length : '0';
        } catch(e) { document.getElementById('statProjects').textContent = '0'; }

        try {
            const tr = await fetch('?action=get_users', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ email: currentEmail })
            });
            const td = await tr.json();
            if (td.ok) {
                document.getElementById('statTeam').textContent = td.users.length;
                const bTeam = document.getElementById('billingTeam');
                if (bTeam) bTeam.textContent = td.users.length + ' / ∞';
            } else {
                document.getElementById('statTeam').textContent = '9';
            }
        } catch(e) { document.getElementById('statTeam').textContent = '9'; }
    }

    // ════ PROJECTS ════
    async function loadProjects() {
        const cont = document.getElementById('projectsContainer');
        cont.innerHTML = '<div class="table-empty"><i class="bi bi-hourglass-split"></i>Loading…</div>';
        try {
            const resp = await fetch('?action=get_projects', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ email: currentEmail })
            });
            const data = await resp.json();
            if (!data.ok || data.projects.length === 0) {
                cont.innerHTML = '<div class="table-empty"><i class="bi bi-folder2"></i>No projects yet. Create your first one!</div>';
                return;
            }
            const statusLabel = { active:'Active', in_progress:'In Progress', completed:'Completed' };
            let html = '<div class="projects-grid">';
            data.projects.forEach(p => {
                const date = new Date(p.created_at).toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'});
                html += `<div class="project-card">
                    <div class="project-name">${esc(p.name)}</div>
                    <div class="project-desc">${p.description ? esc(p.description) : '<em style="color:#52525b;">No description</em>'}</div>
                    <div class="project-meta">
                        <span class="status-badge ${p.status}">${statusLabel[p.status]||p.status}</span>
                        <span class="project-date">${date}</span>
                    </div>
                </div>`;
            });
            html += '</div>';
            cont.innerHTML = html;
        } catch(e) {
            cont.innerHTML = '<div class="table-empty"><i class="bi bi-exclamation-circle"></i>Failed to load projects.</div>';
        }
    }

    function openProjectModal() {
        document.getElementById('modalProjectName').value = '';
        document.getElementById('modalProjectDesc').value = '';
        document.getElementById('modalProjectError').style.display = 'none';
        document.getElementById('projectModal').classList.remove('hidden');
        setTimeout(() => document.getElementById('modalProjectName').focus(), 50);
    }
    function closeProjectModal() { document.getElementById('projectModal').classList.add('hidden'); }

    async function createProject() {
        const name = document.getElementById('modalProjectName').value.trim();
        const desc = document.getElementById('modalProjectDesc').value.trim();
        const err  = document.getElementById('modalProjectError');
        if (!name) { err.textContent='Project name is required.'; err.style.display='block'; return; }
        err.style.display = 'none';
        try {
            const resp = await fetch('?action=create_project', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ email: currentEmail, name, description: desc })
            });
            const data = await resp.json();
            if (data.ok) {
                closeProjectModal();
                showToast('Project created!');
                loadProjects();
                loadOverviewStats();
            } else {
                err.textContent = data.message || 'Failed.'; err.style.display = 'block';
            }
        } catch(e) { err.textContent='Network error.'; err.style.display='block'; }
    }

    // ════ TEAM ════
    async function loadTeam() {
        const cont = document.getElementById('teamContainer');
        cont.innerHTML = '<div class="table-empty"><i class="bi bi-hourglass-split"></i>Loading…</div>';

        if (currentRole === 'admin') {
            // Admin: full user table with edit/delete
            try {
                const resp = await fetch('?action=get_users', {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ email: currentEmail })
                });
                const data = await resp.json();
                if (!data.ok) { cont.innerHTML='<div class="table-empty"><i class="bi bi-shield-x"></i>Access denied.</div>'; return; }
                let html = `<div class="table-wrap"><table class="data-table">
                    <thead><tr>
                        <th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th>
                    </tr></thead><tbody>`;
                data.users.forEach((u, i) => {
                    const date = new Date(u.created_at).toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'});
                    const isSelf = u.email === currentEmail;
                    html += `<tr>
                        <td style="color:var(--sa-muted);">${i+1}</td>
                        <td><strong>${esc(u.name)}</strong></td>
                        <td style="color:var(--sa-muted);">${esc(u.email)}</td>
                        <td><span class="role-badge ${u.role}">${u.role==='admin'?'&#9733; Admin':'User'}</span></td>
                        <td style="color:var(--sa-muted);">${date}</td>
                        <td>
                            <button class="btn-sm btn-sm-edit" style="margin-right:0.4rem;" onclick="openEditUser(${u.id},'${esc(u.name)}','${esc(u.email)}')"><i class="bi bi-pencil"></i> Edit</button>
                            ${isSelf ? '' : `<button class="btn-sm btn-sm-danger" onclick="deleteUser(${u.id},'${esc(u.name)}')"><i class="bi bi-trash"></i> Delete</button>`}
                        </td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
                cont.innerHTML = html;
            } catch(e) {
                cont.innerHTML = '<div class="table-empty"><i class="bi bi-exclamation-circle"></i>Failed to load users.</div>';
            }
        } else {
            // Normal user: static team view
            const members = [
                {name:'Ali',email:'ali@gmail.com',role:'user'},
                {name:'Saziya',email:'s*****@gmail.com',role:'user'},
                {name:'Praveen',email:'p*****@gmail.com',role:'user'},
                {name:'Admin',email:'a*****@kzlabs.store',role:'admin'},
            ];
            let html = `<div class="table-wrap"><table class="data-table">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th></tr></thead><tbody>`;
            members.forEach((m, i) => {
                html += `<tr>
                    <td style="color:var(--sa-muted);">${i+1}</td>
                    <td><strong>${esc(m.name)}</strong></td>
                    <td style="color:var(--sa-muted);">${esc(m.email)}</td>
                    <td><span class="role-badge ${m.role}">${m.role==='admin'?'&#9733; Admin':'User'}</span></td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            cont.innerHTML = html;
        }
    }

    // ════ ADMIN: EDIT USER ════
    function openEditUser(id, name, email) {
        document.getElementById('editUserId').value    = id;
        document.getElementById('editUserName').value  = name;
        document.getElementById('editUserEmail').value = email;
        document.getElementById('editUserError').style.display = 'none';
        document.getElementById('editUserModal').classList.remove('hidden');
        setTimeout(() => document.getElementById('editUserName').focus(), 50);
    }
    function closeEditUserModal() { document.getElementById('editUserModal').classList.add('hidden'); }

    async function saveEditUser() {
        const id    = document.getElementById('editUserId').value;
        const name  = document.getElementById('editUserName').value.trim();
        const email = document.getElementById('editUserEmail').value.trim();
        const err   = document.getElementById('editUserError');
        if (!name || !email) { err.textContent='Name and email required.'; err.style.display='block'; return; }
        err.style.display = 'none';
        try {
            const resp = await fetch('?action=edit_user', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ caller: currentEmail, id, name, email })
            });
            const data = await resp.json();
            if (data.ok) {
                closeEditUserModal();
                showToast('User updated successfully.');
                loadTeam();
            } else {
                err.textContent = data.message || 'Failed.'; err.style.display = 'block';
            }
        } catch(e) { err.textContent='Network error.'; err.style.display='block'; }
    }

    // ════ ADMIN: DELETE USER ════
    async function deleteUser(id, name) {
        if (!confirm('Delete user "' + name + '"? This cannot be undone.')) return;
        try {
            const resp = await fetch('?action=delete_user', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ caller: currentEmail, id })
            });
            const data = await resp.json();
            if (data.ok) { showToast('User deleted.'); loadTeam(); loadOverviewStats(); }
            else          { showToast(data.message || 'Failed to delete.', 'error'); }
        } catch(e) { showToast('Network error.', 'error'); }
    }

    // ════ SETTINGS ════
    async function saveProfile(e) {
        e.preventDefault();
        const btn  = document.getElementById('saveProfileBtn');
        const name = document.getElementById('settingsName').value.trim();
        if (!name) { showToast('Name cannot be empty.', 'error'); return; }
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';
        try {
            const resp = await fetch('?action=update_profile', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ email: currentEmail, name })
            });
            const data = await resp.json();
            if (data.ok) {
                currentName = data.name;
                document.getElementById('dashUserName').textContent = data.name;
                document.getElementById('dashFullName').textContent = data.name;
                document.getElementById('dashAvatar').textContent   = data.name.charAt(0).toUpperCase();
                const s = JSON.parse(localStorage.getItem('lab202_session') || '{}');
                s.name = data.name;
                localStorage.setItem('lab202_session', JSON.stringify(s));
                showToast('Profile updated successfully.');
            } else { showToast(data.message || 'Update failed.', 'error'); }
        } catch(e) { showToast('Network error.', 'error'); }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Save Changes';
    }

    // ════ TOGGLE REGISTER ↔ SIGN-IN ════
    function showSignIn() {
        document.getElementById('regFormBody').style.display  = 'none';
        document.getElementById('signinBody').style.display   = 'block';
        document.getElementById('regCardTitle').textContent   = 'Welcome back';
        document.getElementById('regCardSub').textContent     = 'Sign in to your SecureApp account';
        document.getElementById('regSteps').style.display     = 'none';
    }
    function showRegister() {
        document.getElementById('signinBody').style.display   = 'none';
        document.getElementById('regFormBody').style.display  = 'block';
        document.getElementById('regCardTitle').textContent   = 'Create your account';
        document.getElementById('regCardSub').textContent     = 'Join SecureApp \u2014 enter your details to get started';
        document.getElementById('regSteps').style.display     = 'flex';
    }

    // ════ OTP BOXES ════
    const boxes = document.querySelectorAll('.otp-box');
    boxes.forEach((box, i) => {
        box.addEventListener('input', () => {
            box.value = box.value.replace(/\D/, '');
            if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
        });
        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !box.value && i > 0) boxes[i - 1].focus();
        });
        box.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            [...text].slice(0, 6).forEach((ch, idx) => { if (boxes[idx]) boxes[idx].value = ch; });
            boxes[Math.min(text.length, 5)].focus();
        });
    });
    function getOtpValue() { return [...boxes].map(b => b.value).join(''); }

    // ════ REGISTER ════
    async function submitRegister(e) {
        e.preventDefault();
        const btn    = document.getElementById('regBtn');
        const errDiv = document.getElementById('regError');
        errDiv.style.display = 'none';
        const name     = document.getElementById('regName').value.trim();
        const email    = document.getElementById('regEmail').value.trim();
        const password = document.getElementById('regPassword').value;
        const confirm  = document.getElementById('regConfirm').value;
        if (!name || !email || !password) { errDiv.textContent='All fields are required.'; errDiv.style.display='block'; return; }
        if (password !== confirm)          { errDiv.textContent='Passwords do not match.'; errDiv.style.display='block'; return; }
        if (password.length < 6)           { errDiv.textContent='Password must be at least 6 characters.'; errDiv.style.display='block'; return; }
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Creating account...';
        try {
            const resp = await fetch('?action=register', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ name, email, password })
            });
            const data = await resp.json();
            if (data.ok) {
                currentEmail = data.email; currentName = data.name;
                document.getElementById('emailTo').textContent      = data.email;
                document.getElementById('emailName').textContent    = data.name.split(' ')[0];
                document.getElementById('otpDisplay').textContent   = data.otp;
                document.getElementById('otpEmailHint').textContent = data.email;
                document.getElementById('registerView').style.display = 'none';
                document.getElementById('otpView').style.display      = 'flex';
                setTimeout(() => boxes[0].focus(), 100);
            } else { errDiv.textContent=data.message||'Registration failed.'; errDiv.style.display='block'; }
        } catch(err) { errDiv.textContent='Network error. Please try again.'; errDiv.style.display='block'; }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-person-plus-fill"></i> Create Account';
    }

    // ════ SIGN IN ════
    async function submitSignIn(e) {
        e.preventDefault();
        const btn    = document.getElementById('siBtn');
        const errDiv = document.getElementById('siError');
        errDiv.style.display = 'none';
        const email    = document.getElementById('siEmail').value.trim();
        const password = document.getElementById('siPassword').value;
        if (!email || !password) { errDiv.textContent='Email and password are required.'; errDiv.style.display='block'; return; }
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Signing in...';
        try {
            const resp = await fetch('?action=login', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ email, password })
            });
            const data = await resp.json();
            if (data.ok) {
                saveSession(data.name, data.email, data.role, false);
                showDashboard(data.name, data.email, data.role, false);
            } else { errDiv.textContent=data.message||'Invalid email or password.'; errDiv.style.display='block'; }
        } catch(err) { errDiv.textContent='Network error. Please try again.'; errDiv.style.display='block'; }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Sign In';
    }

    // ════ VERIFY OTP ════
    async function submitOtp(e) {
        e.preventDefault();
        const btn    = document.getElementById('otpBtn');
        const errDiv = document.getElementById('otpError');
        errDiv.style.display = 'none';
        const otp = getOtpValue();
        if (otp.length < 6) { errDiv.textContent='Please enter the full 6-digit code.'; errDiv.style.display='block'; return; }
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Verifying...';
        try {
            const resp = await fetch('?action=verify', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ email: currentEmail, otp })
            });
            const data = await resp.json();
            // VULNERABLE: app trusts the client-received "verify" field.
            // No server-side session is created or checked.
            // Use Burp Suite to intercept this response and flip:
            //   "verify":false  →  "verify":true
            if (data.verify === true) {
                const uname = data.name || currentName;
                const role  = data.role || 'user';
                saveSession(uname, currentEmail, role, true);
                showDashboard(uname, currentEmail, role, true);
            } else {
                errDiv.textContent=data.message||'Invalid OTP. Please try again.'; errDiv.style.display='block';
                boxes.forEach(b => { b.value=''; b.style.borderColor=''; });
                boxes[0].focus();
            }
        } catch(err) { errDiv.textContent='Network error. Please try again.'; errDiv.style.display='block'; }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-shield-check-fill"></i> Verify Code';
    }

    // ════ UTILITY ════
    function esc(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    // ════ INIT: restore session ════
    (function init() {
        const raw = localStorage.getItem('lab202_session');
        if (!raw) return;
        try {
            const s = JSON.parse(raw);
            if (s && s.name && s.email) {
                showDashboard(s.name, s.email, s.role || 'user', s.bypassed || false);
            }
        } catch (e) {}
    })();
    </script>
</body>
</html>
