<?php
ob_start();
// ── Database setup ──
$db_host = 'localhost';
$db_name = 'KrazePlanet';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['ok' => false, 'message' => 'DB error']));
}

// ── Users table ──
$pdo->exec("
    CREATE TABLE IF NOT EXISTS lab60_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('user','admin') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// ── Reports table ──
$pdo->exec("
    CREATE TABLE IF NOT EXISTS lab60_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        author_email VARCHAR(150) NOT NULL,
        author_name VARCHAR(100) NOT NULL,
        report_name VARCHAR(500) NOT NULL,
        network VARCHAR(100) NOT NULL,
        date_range VARCHAR(80) NOT NULL,
        impressions INT NOT NULL DEFAULT 0,
        clicks INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// ── Seed default users + reports (once) ──
$sc = $pdo->query("SELECT COUNT(*) FROM lab60_users WHERE email IN ('alex@adpulse.io','admin@adpulse.io')")->fetchColumn();
if ($sc == 0) {
    $iu = $pdo->prepare("INSERT IGNORE INTO lab60_users (name, email, password, role) VALUES (?,?,?,?)");
    $users = [
        ['Alex Turner',  'alex@adpulse.io',  password_hash('alex@123',  PASSWORD_DEFAULT), 'user'],
        ['Priya Singh',  'priya@adpulse.io', password_hash('priya@123', PASSWORD_DEFAULT), 'user'],
        ['Maria Garcia', 'maria@adpulse.io', password_hash('maria@123', PASSWORD_DEFAULT), 'user'],
        ['Admin',        'admin@adpulse.io', password_hash('admin@123', PASSWORD_DEFAULT), 'admin'],
    ];
    foreach ($users as $u) $iu->execute($u);

    $ir = $pdo->prepare("INSERT INTO lab60_reports (author_email, author_name, report_name, network, date_range, impressions, clicks, created_at) VALUES (?,?,?,?,?,?,?,?)");
    $reports = [
        ['alex@adpulse.io',  'Alex Turner',  'Q4 2024 Network Campaign',           'AdMob',                  'Oct 1 – Dec 31, 2024', 124500, 3240, date('Y-m-d H:i:s', strtotime('-5 days'))],
        ['priya@adpulse.io', 'Priya Singh',  'Holiday Promo — Instagram Audience', 'Meta Audience Network',   'Dec 1 – Dec 31, 2024',  89200, 2180, date('Y-m-d H:i:s', strtotime('-3 days'))],
        ['maria@adpulse.io', 'Maria Garcia', 'Retention Push — January 2025',      'Unity Ads',               'Jan 1 – Jan 31, 2025',  56800, 1420, date('Y-m-d H:i:s', strtotime('-1 day'))],
    ];
    foreach ($reports as $r) $ir->execute($r);
}

// ── AJAX: register ──
if (isset($_GET['action']) && $_GET['action'] === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $body  = json_decode(file_get_contents('php://input'), true);
        $name  = trim($body['name']     ?? '');
        $email = trim($body['email']    ?? '');
        $pass  = trim($body['password'] ?? '');
        if (!$name || !$email || !$pass) { echo json_encode(['ok'=>false,'message'=>'All fields required.']); exit; }
        if (strlen($pass) < 6) { echo json_encode(['ok'=>false,'message'=>'Password must be at least 6 characters.']); exit; }
        $chk = $pdo->prepare("SELECT id FROM lab60_users WHERE email=?");
        $chk->execute([$email]);
        if ($chk->fetch()) { echo json_encode(['ok'=>false,'message'=>'Email already registered.']); exit; }
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO lab60_users (name,email,password) VALUES (?,?,?)")->execute([$name,$email,$hash]);
        echo json_encode(['ok'=>true,'name'=>$name,'email'=>$email,'role'=>'user']);
    } catch (Exception $e) { echo json_encode(['ok'=>false,'message'=>'Server error.']); }
    exit;
}

// ── AJAX: login ──
if (isset($_GET['action']) && $_GET['action'] === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $body  = json_decode(file_get_contents('php://input'), true);
        $email = trim($body['email']    ?? '');
        $pass  = trim($body['password'] ?? '');
        if (!$email || !$pass) { echo json_encode(['ok'=>false,'message'=>'Email and password required.']); exit; }
        $stmt = $pdo->prepare("SELECT id,name,password,role FROM lab60_users WHERE email=?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if ($row && password_verify($pass, $row['password'])) {
            echo json_encode(['ok'=>true,'name'=>$row['name'],'email'=>$email,'role'=>$row['role']]);
        } else {
            echo json_encode(['ok'=>false,'message'=>'Invalid email or password.']);
        }
    } catch (Exception $e) { echo json_encode(['ok'=>false,'message'=>'Server error.']); }
    exit;
}

// ── AJAX: get_reports ──
if (isset($_GET['action']) && $_GET['action'] === 'get_reports' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $rows = $pdo->query("SELECT id, author_name, report_name, network, date_range, impressions, clicks, created_at FROM lab60_reports ORDER BY id DESC");
        echo json_encode(['ok'=>true,'reports'=>$rows->fetchAll()]);
    } catch (Exception $e) { echo json_encode(['ok'=>false,'reports'=>[]]); }
    exit;
}

// ── AJAX: create_report ──
if (isset($_GET['action']) && $_GET['action'] === 'create_report' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $body        = json_decode(file_get_contents('php://input'), true);
        $email       = trim($body['email']       ?? '');
        $author      = trim($body['author']      ?? '');
        $report_name = $body['report_name']      ?? '';  // VULNERABLE: stored raw, no sanitisation
        $network     = trim($body['network']     ?? 'AdMob');
        $date_range  = trim($body['date_range']  ?? 'Last 30 days');
        $impressions = (int)($body['impressions'] ?? 0);
        $clicks      = (int)($body['clicks']      ?? 0);

        if (!$email || !$report_name) { echo json_encode(['ok'=>false,'message'=>'Report name is required.']); exit; }

        $pdo->prepare("INSERT INTO lab60_reports (author_email,author_name,report_name,network,date_range,impressions,clicks) VALUES (?,?,?,?,?,?,?)")
            ->execute([$email, $author, $report_name, $network, $date_range, $impressions, $clicks]);

        echo json_encode(['ok'=>true,'message'=>'Report created.']);
    } catch (Exception $e) { echo json_encode(['ok'=>false,'message'=>'Server error.']); }
    exit;
}

// ── AJAX: get_stats ──
if (isset($_GET['action']) && $_GET['action'] === 'get_stats' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $row = $pdo->query("SELECT COUNT(*) AS total_reports, COALESCE(SUM(impressions),0) AS total_impressions, COALESCE(SUM(clicks),0) AS total_clicks FROM lab60_reports")->fetch();
        $ctr = $row['total_impressions'] > 0 ? round($row['total_clicks'] / $row['total_impressions'] * 100, 2) : 0;
        echo json_encode(['ok'=>true,'stats'=>['reports'=>(int)$row['total_reports'],'impressions'=>(int)$row['total_impressions'],'clicks'=>(int)$row['total_clicks'],'ctr'=>$ctr]]);
    } catch (Exception $e) { echo json_encode(['ok'=>false]); }
    exit;
}

// ── AJAX: update_profile ──
if (isset($_GET['action']) && $_GET['action'] === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $body  = json_decode(file_get_contents('php://input'), true);
        $email = trim($body['email'] ?? '');
        $name  = trim($body['name']  ?? '');
        if (!$email || !$name) { echo json_encode(['ok'=>false,'message'=>'Missing fields.']); exit; }
        $pdo->prepare("UPDATE lab60_users SET name=? WHERE email=?")->execute([$name, $email]);
        echo json_encode(['ok'=>true,'name'=>$name]);
    } catch (Exception $e) { echo json_encode(['ok'=>false,'message'=>'Server error.']); }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdPulse — Network Reports</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --ap-blue:      #1da1f2;
            --ap-blue-d:    #1991da;
            --ap-blue-glow: rgba(29,161,242,0.18);
            --ap-dark:      #0d1117;
            --ap-surface:   #161b22;
            --ap-surface2:  #1c2128;
            --ap-border:    #30363d;
            --ap-text:      #e6edf3;
            --ap-muted:     #6e7681;
            --ap-green:     #3fb950;
            --ap-red:       #f85149;
            --ap-yellow:    #e3b341;
        }

        body {
            background: var(--ap-dark);
            color: var(--ap-text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }

        /* ── App header ── */
        .app-header {
            background: var(--ap-surface);
            border-bottom: 1px solid var(--ap-border);
            padding: 0 2rem; height: 56px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .app-logo { display: flex; align-items: center; gap: 0.55rem; text-decoration: none; }
        .app-logo-icon {
            width: 30px; height: 30px; background: var(--ap-blue);
            border-radius: 6px; display: flex; align-items: center;
            justify-content: center; color: #fff; font-size: 1rem;
        }
        .app-logo-name { font-size: 1.1rem; font-weight: 800; color: var(--ap-text); letter-spacing: -0.02em; }
        .app-logo-name span { color: var(--ap-blue); }
        .app-header-right { font-size: 0.72rem; color: var(--ap-muted); }

        /* ── Page wrap (auth views) ── */
        .page-wrap {
            min-height: calc(100vh - 56px);
            display: flex; align-items: center; justify-content: center;
            padding: 2.5rem 1rem;
            background: radial-gradient(ellipse at 50% 0%, rgba(29,161,242,0.06) 0%, transparent 60%);
        }

        /* ── Card ── */
        .card {
            background: var(--ap-surface); border: 1px solid var(--ap-border);
            border-radius: 12px; box-shadow: 0 24px 60px rgba(0,0,0,0.55);
            width: 100%; max-width: 420px;
        }
        .card-header { padding: 2rem 2rem 0; text-align: center; margin-bottom: 1.75rem; }
        .card-header h2 { font-size: 1.4rem; font-weight: 800; margin-bottom: 0.3rem; }
        .card-header p  { font-size: 0.82rem; color: var(--ap-muted); }
        .card-body { padding: 0 2rem 2rem; }

        /* ── Form elements ── */
        .form-group { margin-bottom: 1rem; }
        .form-label {
            display: block; font-size: 0.78rem; font-weight: 600;
            color: #8b949e; margin-bottom: 0.3rem; letter-spacing: 0.03em;
        }
        .form-control {
            width: 100%; background: var(--ap-dark); border: 1px solid var(--ap-border);
            border-radius: 6px; color: var(--ap-text); padding: 0.6rem 0.85rem;
            font-size: 0.88rem; font-family: inherit; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: var(--ap-blue); box-shadow: 0 0 0 3px var(--ap-blue-glow);
        }
        select.form-control { cursor: pointer; }
        .btn-primary {
            width: 100%; background: var(--ap-blue); color: #fff; border: none;
            border-radius: 6px; padding: 0.72rem; font-size: 0.9rem; font-weight: 700;
            font-family: inherit; cursor: pointer; margin-top: 0.5rem;
            transition: background 0.2s, transform 0.1s;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        }
        .btn-primary:hover  { background: var(--ap-blue-d); }
        .btn-primary:active { transform: scale(0.99); }
        .btn-primary:disabled { opacity: 0.45; cursor: default; }
        .alert-error {
            background: rgba(248,81,73,0.1); border: 1px solid rgba(248,81,73,0.3);
            color: #fca5a5; border-radius: 6px; padding: 0.6rem 0.85rem;
            font-size: 0.8rem; margin-top: 0.75rem; display: none;
        }
        .divider-text {
            text-align: center; font-size: 0.75rem; color: var(--ap-muted); margin: 1.1rem 0 0;
        }
        .divider-text a { color: var(--ap-blue); text-decoration: none; }

        /* ════ DASHBOARD ════ */
        #dashboardView { display: none; }
        .dash-header {
            background: var(--ap-surface); border-bottom: 1px solid var(--ap-border);
            padding: 0 2rem; height: 52px;
            display: flex; align-items: center; gap: 1.25rem;
        }
        .dash-logo { font-weight: 800; font-size: 0.95rem; letter-spacing: -0.02em; flex-shrink: 0; }
        .dash-logo span { color: var(--ap-blue); }
        .dash-nav { display: flex; gap: 0; flex: 1; }
        .dash-nav a {
            color: var(--ap-muted); text-decoration: none;
            font-size: 0.78rem; padding: 0 1rem; height: 52px;
            display: flex; align-items: center;
            border-bottom: 2px solid transparent; transition: all 0.15s;
        }
        .dash-nav a:hover, .dash-nav a.active { color: var(--ap-text); border-bottom-color: var(--ap-blue); }
        .dash-nav a.logout-link { color: var(--ap-red); margin-left: auto; }
        .dash-user {
            display: flex; align-items: center; gap: 0.45rem;
            font-size: 0.78rem; color: var(--ap-muted); flex-shrink: 0;
        }
        .dash-user-avatar {
            width: 26px; height: 26px; border-radius: 50%;
            background: var(--ap-blue); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem; font-weight: 700;
        }
        .admin-badge {
            display: none; background: rgba(29,161,242,0.15);
            border: 1px solid rgba(29,161,242,0.35); color: var(--ap-blue);
            font-size: 0.62rem; font-weight: 700; padding: 0.1rem 0.4rem;
            border-radius: 4px; letter-spacing: 0.05em; text-transform: uppercase;
        }

        .dash-body { padding: 2rem; background: #0a0f14; min-height: calc(100vh - 108px); }

        /* Tab panels */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* Section header */
        .section-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.25rem;
        }
        .section-title { font-size: 1rem; font-weight: 700; }
        .section-sub   { font-size: 0.78rem; color: var(--ap-muted); margin-top: 0.15rem; }
        .btn-new {
            background: var(--ap-blue); color: #fff; border: none; border-radius: 6px;
            padding: 0.5rem 0.9rem; font-size: 0.8rem; font-weight: 700;
            font-family: inherit; cursor: pointer; display: flex; align-items: center;
            gap: 0.4rem; transition: background 0.2s;
        }
        .btn-new:hover { background: var(--ap-blue-d); }

        /* Reports table */
        .table-wrap {
            background: var(--ap-surface); border: 1px solid var(--ap-border);
            border-radius: 10px; overflow: hidden; max-width: 1100px;
        }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
        .data-table thead tr { background: var(--ap-surface2); }
        .data-table th {
            padding: 0.7rem 1rem; text-align: left; font-size: 0.68rem;
            font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
            color: var(--ap-muted); border-bottom: 1px solid var(--ap-border);
        }
        .data-table td {
            padding: 0.75rem 1rem; border-bottom: 1px solid rgba(48,54,61,0.5);
            vertical-align: middle;
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: rgba(29,161,242,0.03); }
        .td-name { font-weight: 600; color: var(--ap-text); max-width: 280px; }
        .td-name code { font-size: 0.75rem; color: var(--ap-yellow); font-family: 'Courier New', monospace; }
        .net-badge {
            display: inline-block; background: rgba(29,161,242,0.1); border: 1px solid rgba(29,161,242,0.25);
            color: #79c0ff; font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.45rem;
            border-radius: 3px; white-space: nowrap;
        }
        .table-empty {
            text-align: center; padding: 3.5rem 1rem; color: var(--ap-muted); font-size: 0.82rem;
        }
        .table-empty i { font-size: 2.2rem; display: block; margin-bottom: 0.75rem; color: var(--ap-border); }

        /* Overview stats */
        .stats-grid {
            display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem;
            max-width: 860px; margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--ap-surface); border: 1px solid var(--ap-border);
            border-radius: 8px; padding: 1.1rem;
        }
        .stat-lbl { font-size: 0.68rem; color: var(--ap-muted); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 0.35rem; }
        .stat-val { font-size: 1.6rem; font-weight: 800; color: var(--ap-blue); }
        .stat-sub { font-size: 0.68rem; color: var(--ap-muted); margin-top: 0.1rem; }

        /* Settings form */
        .settings-card {
            background: var(--ap-surface); border: 1px solid var(--ap-border);
            border-radius: 10px; padding: 1.75rem; max-width: 460px;
        }
        .settings-card h3 { font-size: 0.9rem; font-weight: 700; margin-bottom: 1.25rem; }
        .input-readonly {
            width: 100%; background: rgba(255,255,255,0.03);
            border: 1px solid var(--ap-border); border-radius: 6px;
            padding: 0.6rem 0.85rem; font-size: 0.88rem; color: var(--ap-muted); cursor: not-allowed;
        }
        .settings-note { font-size: 0.7rem; color: var(--ap-muted); margin-top: 0.3rem; }
        .btn-save {
            background: var(--ap-blue); color: #fff; border: none; border-radius: 6px;
            padding: 0.6rem 1.25rem; font-size: 0.85rem; font-weight: 700;
            font-family: inherit; cursor: pointer; margin-top: 0.5rem;
            display: flex; align-items: center; gap: 0.4rem; transition: background 0.2s;
        }
        .btn-save:hover { background: var(--ap-blue-d); }

        /* Accounts info box (for lab context) */
        .accounts-box {
            background: var(--ap-surface2); border: 1px solid var(--ap-border);
            border-radius: 8px; padding: 1rem 1.25rem; margin-top: 1.75rem;
            font-size: 0.78rem; max-width: 460px;
        }
        .accounts-box h4 { font-size: 0.7rem; font-weight: 700; color: var(--ap-muted); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 0.6rem; }
        .account-row { display: flex; gap: 1.5rem; padding: 0.3rem 0; border-bottom: 1px solid rgba(48,54,61,0.4); }
        .account-row:last-child { border-bottom: none; }
        .account-row span { color: var(--ap-muted); font-size: 0.74rem; }
        .account-row strong { color: var(--ap-text); }
        .account-row .role-tag {
            font-size: 0.6rem; padding: 0.06rem 0.35rem; border-radius: 3px; font-weight: 700;
            background: rgba(29,161,242,0.1); color: var(--ap-blue); border: 1px solid rgba(29,161,242,0.25);
        }
        .account-row .role-tag.admin { background: rgba(227,179,65,0.1); color: var(--ap-yellow); border-color: rgba(227,179,65,0.25); }

        /* Modal */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.65);
            display: flex; align-items: center; justify-content: center;
            z-index: 1000; padding: 1rem;
        }
        .modal-overlay.hidden { display: none; }
        .modal-box {
            background: var(--ap-surface); border: 1px solid var(--ap-border);
            border-radius: 12px; padding: 1.75rem; width: 100%; max-width: 500px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.7);
        }
        .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
        .modal-header h3 { font-size: 1rem; font-weight: 700; }
        .modal-close {
            background: none; border: none; color: var(--ap-muted); cursor: pointer;
            font-size: 1.1rem; padding: 0.2rem 0.4rem; border-radius: 4px;
            transition: color 0.15s;
        }
        .modal-close:hover { color: var(--ap-text); }
        .vuln-field-note {
            background: rgba(248,81,73,0.08); border: 1px solid rgba(248,81,73,0.2);
            border-radius: 5px; padding: 0.45rem 0.7rem; margin-top: 0.3rem;
            font-size: 0.69rem; color: #fca5a5;
        }
        .vuln-field-note i { margin-right: 0.3rem; }

        /* Toast */
        .toast {
            position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 2000;
            background: var(--ap-surface); border: 1px solid var(--ap-border);
            border-radius: 8px; padding: 0.8rem 1.1rem;
            display: flex; align-items: center; gap: 0.6rem; font-size: 0.8rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.55);
            transform: translateY(8px); opacity: 0;
            transition: all 0.25s; pointer-events: none;
        }
        .toast.show { transform: translateY(0); opacity: 1; pointer-events: auto; }
        .toast.success { border-left: 3px solid var(--ap-green); color: #7ee787; }
        .toast.error   { border-left: 3px solid var(--ap-red);   color: #fca5a5; }
    </style>
</head>
<body>

    <!-- App header -->
    <div class="app-header">
        <a class="app-logo" href="#">
            <div class="app-logo-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <span class="app-logo-name">Ad<span>Pulse</span></span>
        </a>
        <div class="app-header-right">Mobile Ad Analytics Platform</div>
    </div>

    <!-- ════════════════════ VIEW 1: LOGIN ════════════════════ -->
    <div class="page-wrap" id="loginView">
        <div class="card">
            <div class="card-header">
                <h2>Sign in to AdPulse</h2>
                <p>Access your network reports and analytics</p>
            </div>
            <div class="card-body">
                <form id="loginForm" onsubmit="submitLogin(event)">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" id="liEmail" class="form-control" placeholder="you@company.com" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" id="liPassword" class="form-control" placeholder="Enter your password">
                    </div>
                    <button type="submit" class="btn-primary" id="liBtn">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>
                    <div class="alert-error" id="liError"></div>
                </form>
                <div class="divider-text">
                    Don't have an account? <a href="#" onclick="showRegister(); return false;">Create account</a>
                </div>
                <div class="accounts-box">
                    <h4><i class="bi bi-person-badge"></i> Test Accounts</h4>
                    <div class="account-row">
                        <span>alex@adpulse.io</span>
                        <strong>alex@123</strong>
                        <span class="role-tag">User</span>
                    </div>
                    <div class="account-row">
                        <span>priya@adpulse.io</span>
                        <strong>priya@123</strong>
                        <span class="role-tag">User</span>
                    </div>
                    <div class="account-row">
                        <span>admin@adpulse.io</span>
                        <strong>admin@123</strong>
                        <span class="role-tag admin">Admin</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════ VIEW 2: REGISTER ════════════════════ -->
    <div class="page-wrap" id="registerView" style="display:none;">
        <div class="card">
            <div class="card-header">
                <h2>Create Account</h2>
                <p>Join AdPulse to manage your ad network reports</p>
            </div>
            <div class="card-body">
                <form id="registerForm" onsubmit="submitRegister(event)">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" id="rgName" class="form-control" placeholder="Your full name" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" id="rgEmail" class="form-control" placeholder="you@company.com" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" id="rgPassword" class="form-control" placeholder="Min. 6 characters">
                    </div>
                    <button type="submit" class="btn-primary" id="rgBtn">
                        <i class="bi bi-person-plus-fill"></i> Create Account
                    </button>
                    <div class="alert-error" id="rgError"></div>
                </form>
                <div class="divider-text">
                    Already have an account? <a href="#" onclick="showLogin(); return false;">Sign in</a>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════ VIEW 3: DASHBOARD ════════════════════ -->
    <div id="dashboardView">
        <div class="dash-header">
            <div class="dash-logo">Ad<span>Pulse</span></div>
            <nav class="dash-nav">
                <a href="#" class="active" onclick="switchTab('reports',this);  return false;"><i class="bi bi-file-bar-graph" style="margin-right:0.3rem;"></i>Reports</a>
                <a href="#"               onclick="switchTab('overview',this); return false;"><i class="bi bi-speedometer2"  style="margin-right:0.3rem;"></i>Overview</a>
                <a href="#"               onclick="switchTab('profile',this);  return false;"><i class="bi bi-person-gear"   style="margin-right:0.3rem;"></i>Profile</a>
                <a href="#" class="logout-link" onclick="logout(); return false;"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
            <div class="dash-user">
                <div class="dash-user-avatar" id="dashAvatar">U</div>
                <span id="dashUserName">User</span>
                <span class="admin-badge" id="adminBadge">&#9733; Admin</span>
            </div>
        </div>

        <div class="dash-body">

            <!-- ══ TAB: REPORTS ══ -->
            <div class="tab-panel active" id="tab-reports">

                <div class="section-header" style="max-width:1100px;">
                    <div>
                        <div class="section-title"><i class="bi bi-file-bar-graph" style="color:var(--ap-blue);"></i> Network Reports</div>
                        <div class="section-sub">All network reports — visible to every authenticated user</div>
                    </div>
                    <button class="btn-new" onclick="openReportModal()">
                        <i class="bi bi-plus-lg"></i> New Network Report
                    </button>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Report Name</th>
                                <th>Network</th>
                                <th>Date Range</th>
                                <th>Impressions</th>
                                <th>Clicks</th>
                                <th>Created By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="reportsBody">
                            <tr><td colspan="8" class="table-empty"><i class="bi bi-hourglass-split"></i>Loading reports…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ══ TAB: OVERVIEW ══ -->
            <div class="tab-panel" id="tab-overview">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="bi bi-speedometer2" style="color:var(--ap-blue);"></i> Platform Overview</div>
                        <div class="section-sub">Aggregated stats across all network reports</div>
                    </div>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-lbl"><i class="bi bi-file-bar-graph"></i> Total Reports</div>
                        <div class="stat-val" id="statReports">—</div>
                        <div class="stat-sub">All campaigns</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-lbl"><i class="bi bi-eye"></i> Impressions</div>
                        <div class="stat-val" id="statImpressions">—</div>
                        <div class="stat-sub">Total served</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-lbl"><i class="bi bi-cursor-fill"></i> Clicks</div>
                        <div class="stat-val" id="statClicks">—</div>
                        <div class="stat-sub">Total clicks</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-lbl"><i class="bi bi-percent"></i> Avg CTR</div>
                        <div class="stat-val" id="statCtr">—</div>
                        <div class="stat-sub">Click-through rate</div>
                    </div>
                </div>
            </div>

            <!-- ══ TAB: PROFILE ══ -->
            <div class="tab-panel" id="tab-profile">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="bi bi-person-circle" style="color:var(--ap-blue);"></i> Profile</div>
                        <div class="section-sub">Manage your AdPulse account</div>
                    </div>
                </div>
                <div class="settings-card">
                    <h3>Account Information</h3>
                    <form onsubmit="saveProfile(event)">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" id="profileName" class="form-control" placeholder="Your name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <div class="input-readonly" id="profileEmail">—</div>
                            <div class="settings-note">Email cannot be changed.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <div class="input-readonly" id="profileRole">—</div>
                        </div>
                        <button type="submit" class="btn-save" id="saveProfileBtn">
                            <i class="bi bi-check-lg"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>

        </div><!-- /dash-body -->
    </div><!-- /dashboardView -->

    <!-- ══ MODAL: New Network Report ══ -->
    <div class="modal-overlay hidden" id="reportModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="bi bi-plus-circle" style="color:var(--ap-blue);"></i> New Network Report</h3>
                <button class="modal-close" onclick="closeReportModal()"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="form-group">
                <label class="form-label">Report Name <span style="color:var(--ap-red);">*</span></label>
                <input type="text" id="modalReportName" class="form-control" placeholder='e.g. Q1 2025 Campaign' autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label">Ad Network</label>
                <select id="modalNetwork" class="form-control">
                    <option>AdMob</option>
                    <option>Meta Audience Network</option>
                    <option>Unity Ads</option>
                    <option>AppLovin</option>
                    <option>ironSource</option>
                    <option>Pangle</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Date Range</label>
                <select id="modalDateRange" class="form-control">
                    <option>Last 7 days</option>
                    <option>Last 30 days</option>
                    <option>Last Quarter</option>
                    <option>Jan 1 – Jan 31, 2025</option>
                    <option>Q4 2024</option>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                <div class="form-group">
                    <label class="form-label">Est. Impressions</label>
                    <input type="number" id="modalImpressions" class="form-control" placeholder="50000" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Est. Clicks</label>
                    <input type="number" id="modalClicks" class="form-control" placeholder="1200" min="0">
                </div>
            </div>
            <div class="alert-error" id="modalError"></div>
            <button class="btn-primary" style="margin-top:0.75rem;" onclick="createReport()">
                <i class="bi bi-save"></i> Run &amp; Save Report
            </button>
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
    function saveSession(name, email, role) {
        localStorage.setItem('lab60_session', JSON.stringify({ name, email, role: role || 'user' }));
    }
    function logout() {
        localStorage.removeItem('lab60_session');
        location.reload();
    }

    // ════ VIEWS TOGGLE ════
    function showLogin() {
        document.getElementById('registerView').style.display = 'none';
        document.getElementById('loginView').style.display    = 'flex';
    }
    function showRegister() {
        document.getElementById('loginView').style.display    = 'none';
        document.getElementById('registerView').style.display = 'flex';
    }

    // ════ SHOW DASHBOARD ════
    function showDashboard(name, email, role) {
        currentEmail = email;
        currentName  = name;
        currentRole  = role || 'user';

        document.getElementById('dashUserName').textContent = name;
        document.getElementById('dashAvatar').textContent   = name.charAt(0).toUpperCase();
        document.getElementById('adminBadge').style.display = currentRole === 'admin' ? 'inline-block' : 'none';
        document.getElementById('profileName').value        = name;
        document.getElementById('profileEmail').textContent = email;
        document.getElementById('profileRole').textContent  = currentRole.charAt(0).toUpperCase() + currentRole.slice(1);

        document.getElementById('loginView').style.display     = 'none';
        document.getElementById('registerView').style.display  = 'none';
        document.getElementById('dashboardView').style.display = 'block';

        loadReports();
        loadStats();
    }

    // ════ TABS ════
    function switchTab(tab, el) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.dash-nav a:not(.logout-link)').forEach(a => a.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        if (el) el.classList.add('active');
        if (tab === 'overview') loadStats();
    }

    // ════ LOAD REPORTS ════
    // ╔══════════════════════════════════════════════════════════════════╗
    // ║  VULNERABILITY:  report_name is inserted via innerHTML           ║
    // ║  The DB stores it raw — no htmlspecialchars() in the PHP handler  ║
    // ║  Any HTML/JS in report_name executes in every viewer's browser   ║
    // ║  Real-world: HackerOne #485748  (MoPub, $700 bounty)            ║
    // ╚══════════════════════════════════════════════════════════════════╝
    async function loadReports() {
        const tbody = document.getElementById('reportsBody');
        tbody.innerHTML = '<tr><td colspan="8" class="table-empty"><i class="bi bi-hourglass-split"></i>Loading…</td></tr>';
        try {
            const resp = await fetch('?action=get_reports', {
                method: 'POST', headers: {'Content-Type':'application/json'}, body: '{}'
            });
            const data = await resp.json();
            if (!data.ok || data.reports.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="table-empty"><i class="bi bi-file-bar-graph"></i>No reports yet. Create your first network report.</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            data.reports.forEach((r, i) => {
                const d    = new Date(r.created_at).toLocaleDateString('en-GB', {day:'numeric',month:'short',year:'numeric'});
                const imp  = Number(r.impressions).toLocaleString();
                const clk  = Number(r.clicks).toLocaleString();
                const row  = document.createElement('tr');

                // ⚠ VULNERABLE: r.report_name is injected via innerHTML without escaping
                // Safe alternative would be: document.createTextNode(r.report_name)
                row.innerHTML = `
                    <td style="color:var(--ap-muted);">${i + 1}</td>
                    <td class="td-name">${r.report_name}</td>
                    <td><span class="net-badge">${safeText(r.network)}</span></td>
                    <td style="color:var(--ap-muted);font-size:0.78rem;">${safeText(r.date_range)}</td>
                    <td style="font-weight:600;">${imp}</td>
                    <td style="color:var(--ap-muted);">${clk}</td>
                    <td style="color:var(--ap-muted);">${safeText(r.author_name)}</td>
                    <td style="color:var(--ap-muted);font-size:0.75rem;">${d}</td>
                `;
                tbody.appendChild(row);
            });
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="8" class="table-empty"><i class="bi bi-exclamation-circle"></i>Failed to load reports.</td></tr>';
        }
    }

    // safeText — used for non-vulnerable fields
    function safeText(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ════ MODAL ════
    function openReportModal() {
        document.getElementById('modalReportName').value = '';
        document.getElementById('modalImpressions').value = '';
        document.getElementById('modalClicks').value = '';
        document.getElementById('modalError').style.display = 'none';
        document.getElementById('reportModal').classList.remove('hidden');
        setTimeout(() => document.getElementById('modalReportName').focus(), 50);
    }
    function closeReportModal() { document.getElementById('reportModal').classList.add('hidden'); }

    async function createReport() {
        const name = document.getElementById('modalReportName').value;
        const net  = document.getElementById('modalNetwork').value;
        const dr   = document.getElementById('modalDateRange').value;
        const imp  = parseInt(document.getElementById('modalImpressions').value) || 0;
        const clk  = parseInt(document.getElementById('modalClicks').value) || 0;
        const err  = document.getElementById('modalError');

        if (!name.trim()) { err.textContent = 'Report name is required.'; err.style.display = 'block'; return; }
        err.style.display = 'none';

        try {
            const resp = await fetch('?action=create_report', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ email: currentEmail, author: currentName, report_name: name, network: net, date_range: dr, impressions: imp, clicks: clk })
            });
            const data = await resp.json();
            if (data.ok) {
                closeReportModal();
                showToast('Report saved. XSS payload will fire when anyone views this page.');
                loadReports();
                loadStats();
            } else {
                err.textContent = data.message || 'Failed.'; err.style.display = 'block';
            }
        } catch (e) { err.textContent = 'Network error.'; err.style.display = 'block'; }
    }

    // ════ OVERVIEW STATS ════
    async function loadStats() {
        try {
            const resp = await fetch('?action=get_stats', {
                method: 'POST', headers: {'Content-Type':'application/json'}, body: '{}'
            });
            const data = await resp.json();
            if (data.ok) {
                const s = data.stats;
                document.getElementById('statReports').textContent     = s.reports;
                document.getElementById('statImpressions').textContent = Number(s.impressions).toLocaleString();
                document.getElementById('statClicks').textContent      = Number(s.clicks).toLocaleString();
                document.getElementById('statCtr').textContent         = s.ctr + '%';
            }
        } catch (e) {}
    }

    // ════ PROFILE SAVE ════
    async function saveProfile(e) {
        e.preventDefault();
        const btn  = document.getElementById('saveProfileBtn');
        const name = document.getElementById('profileName').value.trim();
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
                document.getElementById('dashAvatar').textContent   = data.name.charAt(0).toUpperCase();
                const s = JSON.parse(localStorage.getItem('lab60_session') || '{}');
                s.name = data.name;
                localStorage.setItem('lab60_session', JSON.stringify(s));
                showToast('Profile updated.');
            } else { showToast(data.message || 'Update failed.', 'error'); }
        } catch (e) { showToast('Network error.', 'error'); }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Save Changes';
    }

    // ════ LOGIN ════
    async function submitLogin(e) {
        e.preventDefault();
        const btn    = document.getElementById('liBtn');
        const errDiv = document.getElementById('liError');
        errDiv.style.display = 'none';
        const email = document.getElementById('liEmail').value.trim();
        const pass  = document.getElementById('liPassword').value;
        if (!email || !pass) { errDiv.textContent = 'Email and password required.'; errDiv.style.display = 'block'; return; }
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Signing in…';
        try {
            const resp = await fetch('?action=login', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ email, password: pass })
            });
            const data = await resp.json();
            if (data.ok) {
                saveSession(data.name, data.email, data.role);
                showDashboard(data.name, data.email, data.role);
            } else { errDiv.textContent = data.message || 'Invalid credentials.'; errDiv.style.display = 'block'; }
        } catch (err) { errDiv.textContent = 'Network error. Please try again.'; errDiv.style.display = 'block'; }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Sign In';
    }

    // ════ REGISTER ════
    async function submitRegister(e) {
        e.preventDefault();
        const btn    = document.getElementById('rgBtn');
        const errDiv = document.getElementById('rgError');
        errDiv.style.display = 'none';
        const name  = document.getElementById('rgName').value.trim();
        const email = document.getElementById('rgEmail').value.trim();
        const pass  = document.getElementById('rgPassword').value;
        if (!name || !email || !pass) { errDiv.textContent = 'All fields required.'; errDiv.style.display = 'block'; return; }
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Creating…';
        try {
            const resp = await fetch('?action=register', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ name, email, password: pass })
            });
            const data = await resp.json();
            if (data.ok) {
                saveSession(data.name, data.email, data.role);
                showDashboard(data.name, data.email, data.role);
            } else { errDiv.textContent = data.message || 'Registration failed.'; errDiv.style.display = 'block'; }
        } catch (err) { errDiv.textContent = 'Network error.'; errDiv.style.display = 'block'; }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-person-plus-fill"></i> Create Account';
    }

    // ════ INIT: restore session ════
    (function init() {
        const raw = localStorage.getItem('lab60_session');
        if (!raw) return;
        try {
            const s = JSON.parse(raw);
            if (s && s.name && s.email) {
                showDashboard(s.name, s.email, s.role || 'user');
            }
        } catch (e) {}
    })();
    </script>
</body>
</html>
