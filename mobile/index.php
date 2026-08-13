<?php
ob_start();
// ── Database setup ──
$db_host = 'localhost';
$db_name = 'VexiumCTF';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

// ── Users table ──
$pdo->exec("
    CREATE TABLE IF NOT EXISTS lab203_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(15) UNIQUE NOT NULL,
        otp CHAR(6) NOT NULL DEFAULT '000000',
        wallet_balance DECIMAL(8,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// ── Migration: add wallet_balance if missing ──
try {
    $pdo->exec("ALTER TABLE lab203_users ADD COLUMN IF NOT EXISTS wallet_balance DECIMAL(8,2) NOT NULL DEFAULT 0.00");
} catch (Exception $e) {}

// ── Orders table ──
$pdo->exec("
    CREATE TABLE IF NOT EXISTS lab203_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_phone VARCHAR(15) NOT NULL,
        item_name VARCHAR(150) NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        total DECIMAL(8,2) NOT NULL,
        status ENUM('delivered','processing','cancelled') NOT NULL DEFAULT 'delivered',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// ── Seed default users + orders (once) ──
$sc = $pdo->query("SELECT COUNT(*) FROM lab203_users WHERE phone IN ('+919876543210','+918765432109')")->fetchColumn();
if ($sc == 0) {
    $iu = $pdo->prepare("INSERT IGNORE INTO lab203_users (name, phone, wallet_balance) VALUES (?,?,?)");
    $users = [
        ['Rahul Sharma',  '+919876543210', 150.00],
        ['Priya Mehta',   '+918765432109',  75.50],
        ['Arjun Patel',   '+917654321098', 200.00],
        ['Sneha Gupta',   '+916543210987',  50.00],
        ['Vikram Singh',  '+919988776655', 125.00],
    ];
    foreach ($users as $u) $iu->execute($u);

    $io = $pdo->prepare("INSERT INTO lab203_orders (user_phone, item_name, quantity, total, status, created_at) VALUES (?,?,?,?,?,?)");
    $orders = [
        ['+919876543210', 'Amul Butter 500g',        1, 264.00, 'delivered',  date('Y-m-d H:i:s', strtotime('-3 days'))],
        ['+919876543210', 'Eggs 12 pcs',              1,  74.00, 'delivered',  date('Y-m-d H:i:s', strtotime('-5 days'))],
        ['+919876543210', 'Brown Bread',               2,  86.00, 'processing', date('Y-m-d H:i:s', strtotime('-1 hour'))],
        ['+918765432109', 'Maggi Noodles 12pk',        1, 120.00, 'delivered',  date('Y-m-d H:i:s', strtotime('-2 days'))],
        ['+918765432109', 'Colgate Total 300g',        1,  89.00, 'delivered',  date('Y-m-d H:i:s', strtotime('-7 days'))],
        ['+918765432109', 'Tata Salt 1kg',             2,  44.00, 'delivered',  date('Y-m-d H:i:s', strtotime('-4 days'))],
        ['+917654321098', 'Surf Excel 1kg',            1, 220.00, 'delivered',  date('Y-m-d H:i:s', strtotime('-1 day'))],
        ['+917654321098', 'Parle-G 800g',              1,  60.00, 'delivered',  date('Y-m-d H:i:s', strtotime('-4 days'))],
        ['+917654321098', 'Amul Gold Milk 1L',         3,  63.00, 'processing', date('Y-m-d H:i:s', strtotime('-30 minutes'))],
        ['+916543210987', 'Good Day Butter Cookies',   2,  50.00, 'delivered',  date('Y-m-d H:i:s', strtotime('-6 days'))],
        ['+916543210987', 'Lays Classic 26g',          5, 100.00, 'delivered',  date('Y-m-d H:i:s', strtotime('-3 days'))],
        ['+916543210987', 'Pepsi 2L',                  1,  85.00, 'cancelled',  date('Y-m-d H:i:s', strtotime('-2 days'))],
        ['+919988776655', 'Fortune Sunflower Oil 1L',  1, 130.00, 'delivered',  date('Y-m-d H:i:s', strtotime('-2 days'))],
        ['+919988776655', 'Head & Shoulders 340ml',    1, 310.00, 'delivered',  date('Y-m-d H:i:s', strtotime('-7 days'))],
        ['+919988776655', 'Dettol Soap 3-pack',        1, 120.00, 'processing', date('Y-m-d H:i:s', strtotime('-2 hours'))],
    ];
    foreach ($orders as $o) $io->execute($o);
}

// ── AJAX: send_otp ──
if (isset($_GET['action']) && $_GET['action'] === 'send_otp' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    $body  = json_decode(file_get_contents('php://input'), true);
    $name  = trim($body['name']  ?? '');
    $phone = trim($body['phone'] ?? '');

    if (!$phone) { echo json_encode(['ok'=>false,'message'=>'Phone number is required.']); exit; }
    if (!preg_match('/^\+[0-9]{10,15}$/', $phone)) { echo json_encode(['ok'=>false,'message'=>'Enter a valid 10-digit mobile number.']); exit; }

    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $check = $pdo->prepare("SELECT id, name FROM lab203_users WHERE phone = ?");
    $check->execute([$phone]);
    $existing = $check->fetch();

    if ($existing) {
        $pdo->prepare("UPDATE lab203_users SET otp = ? WHERE phone = ?")->execute([$otp, $phone]);
        $displayName = $existing['name'];
        $isNew = false;
    } else {
        if (!$name) { echo json_encode(['ok'=>false,'message'=>'Name is required for new accounts.']); exit; }
        $pdo->prepare("INSERT INTO lab203_users (name, phone, otp) VALUES (?, ?, ?)")->execute([$name, $phone, $otp]);
        $displayName = $name;
        $isNew = true;
    }

    echo json_encode(['ok'=>true, 'phone'=>$phone, 'otp'=>$otp, 'name'=>$displayName, 'isNew'=>$isNew]);
    exit;
}

// ── AJAX: verify OTP ──
if (isset($_GET['action']) && $_GET['action'] === 'verify' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $body  = json_decode(file_get_contents('php://input'), true);
        $phone = trim($body['phone'] ?? '');
        $code  = trim($body['otp']   ?? '');

        $stmt = $pdo->prepare("SELECT otp, name, wallet_balance FROM lab203_users WHERE phone = ?");
        $stmt->execute([$phone]);
        $row = $stmt->fetch();

        // VULNERABLE: verify field is returned in JSON body — no server-side session is set.
        // Client checks response.verify === true to grant access.
        // Intercept with Burp Suite: flip "verify":false → "verify":true to bypass OTP entirely.
        if ($row && $code === $row['otp']) {
            echo json_encode(['verify'=>true, 'name'=>$row['name'], 'wallet'=>(float)($row['wallet_balance'] ?? 0)]);
        } else {
            echo json_encode(['verify'=>false, 'message'=>'Incorrect OTP. Please try again.']);
        }
    } catch (Exception $e) {
        echo json_encode(['verify'=>false, 'message'=>'Server error. Please try again.']);
    }
    exit;
}

// ── AJAX: get_orders ──
if (isset($_GET['action']) && $_GET['action'] === 'get_orders' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    $body  = json_decode(file_get_contents('php://input'), true);
    $phone = trim($body['phone'] ?? '');
    $rows  = $pdo->prepare("SELECT id, item_name, quantity, total, status, created_at FROM lab203_orders WHERE user_phone = ? ORDER BY id DESC");
    $rows->execute([$phone]);
    echo json_encode(['ok'=>true, 'orders'=>$rows->fetchAll()]);
    exit;
}

// ── AJAX: update_profile ──
if (isset($_GET['action']) && $_GET['action'] === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    $body  = json_decode(file_get_contents('php://input'), true);
    $phone = trim($body['phone'] ?? '');
    $name  = trim($body['name']  ?? '');
    if (!$phone || !$name) { echo json_encode(['ok'=>false,'message'=>'Missing fields.']); exit; }
    $pdo->prepare("UPDATE lab203_users SET name = ? WHERE phone = ?")->execute([$name, $phone]);
    echo json_encode(['ok'=>true, 'name'=>$name]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>blinkit — Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bl-yellow:      #f5c518;
            --bl-yellow-d:    #d4a715;
            --bl-yellow-glow: rgba(245,197,24,0.18);
            --bl-dark:        #0e0e0e;
            --bl-surface:     #171717;
            --bl-surface2:    #1e1e1e;
            --bl-border:      #2c2c2c;
            --bl-text:        #f0f0f0;
            --bl-muted:       #6b7280;
            --bl-green:       #22c55e;
            --bl-red:         #ef4444;
            --bl-blue:        #3b82f6;
        }

        body {
            background: var(--bl-dark);
            color: var(--bl-text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }

        /* ── App header ── */
        .app-header {
            background: var(--bl-surface);
            border-bottom: 1px solid var(--bl-border);
            padding: 0 2rem;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .app-logo { display: flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .app-logo-icon {
            width: 30px; height: 30px;
            background: var(--bl-yellow);
            border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            color: #0e0e0e; font-size: 1.1rem;
        }
        .app-logo-name {
            font-size: 1.15rem; font-weight: 800;
            color: var(--bl-yellow); letter-spacing: -0.03em;
        }
        .app-header-right { font-size: 0.72rem; color: var(--bl-muted); }

        /* ── Page wrap ── */
        .page-wrap {
            min-height: calc(100vh - 56px);
            display: flex; align-items: center; justify-content: center;
            padding: 2.5rem 1rem;
            background: radial-gradient(ellipse at 50% 0%, rgba(245,197,24,0.05) 0%, transparent 60%);
        }

        /* ── Card ── */
        .card {
            background: var(--bl-surface);
            border: 1px solid var(--bl-border);
            border-radius: 14px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.6);
        }

        /* ── Form elements ── */
        .form-group { margin-bottom: 1.1rem; }
        .form-label {
            display: block; font-size: 0.78rem; font-weight: 600;
            color: #a1a1aa; margin-bottom: 0.35rem; letter-spacing: 0.03em;
        }
        .form-control {
            width: 100%; background: var(--bl-dark);
            border: 1px solid var(--bl-border); border-radius: 8px;
            color: var(--bl-text); padding: 0.65rem 0.9rem;
            font-size: 0.88rem; font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s; outline: none;
        }
        .form-control:focus {
            border-color: var(--bl-yellow);
            box-shadow: 0 0 0 3px var(--bl-yellow-glow);
        }
        .phone-input-wrap {
            display: flex; align-items: center;
            background: var(--bl-dark); border: 1px solid var(--bl-border);
            border-radius: 8px; overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .phone-input-wrap:focus-within {
            border-color: var(--bl-yellow);
            box-shadow: 0 0 0 3px var(--bl-yellow-glow);
        }
        .phone-prefix {
            padding: 0 0.75rem; font-size: 0.88rem; font-weight: 600;
            color: var(--bl-muted); border-right: 1px solid var(--bl-border);
            white-space: nowrap; user-select: none; height: 42px;
            display: flex; align-items: center;
        }
        .phone-input {
            flex: 1; background: transparent; border: none; outline: none;
            padding: 0.65rem 0.9rem; color: var(--bl-text);
            font-size: 0.88rem; font-family: inherit;
        }
        .btn-primary {
            width: 100%; background: var(--bl-yellow); color: #0e0e0e;
            border: none; border-radius: 8px; padding: 0.8rem;
            font-size: 0.92rem; font-weight: 700; font-family: inherit;
            cursor: pointer; margin-top: 0.5rem;
            transition: background 0.2s, transform 0.1s;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        }
        .btn-primary:hover  { background: var(--bl-yellow-d); }
        .btn-primary:active { transform: scale(0.99); }
        .btn-primary:disabled { opacity: 0.45; cursor: default; }
        .alert-error {
            background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5; border-radius: 8px; padding: 0.65rem 0.9rem;
            font-size: 0.8rem; margin-top: 0.75rem; display: none;
        }
        .divider-text {
            text-align: center; font-size: 0.75rem; color: var(--bl-muted); margin: 1.25rem 0 0;
        }
        .divider-text a { color: var(--bl-yellow); text-decoration: none; }

        /* ── Step indicator ── */
        .steps {
            display: flex; align-items: center; justify-content: center;
            gap: 0; margin-bottom: 2rem;
        }
        .step { display: flex; flex-direction: column; align-items: center; gap: 0.3rem; }
        .step-dot {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.72rem; font-weight: 700;
            border: 2px solid var(--bl-border); color: var(--bl-muted);
            background: var(--bl-dark); transition: all 0.25s;
        }
        .step-dot.active { border-color: var(--bl-yellow); color: var(--bl-yellow); background: var(--bl-yellow-glow); }
        .step-dot.done   { border-color: var(--bl-green); background: var(--bl-green); color: #fff; }
        .step-label { font-size: 0.65rem; color: var(--bl-muted); white-space: nowrap; }
        .step-label.active { color: var(--bl-yellow); }
        .step-line {
            flex: 1; height: 2px; background: var(--bl-border);
            margin: 0 0.5rem; margin-bottom: 1.1rem; transition: background 0.25s;
        }
        .step-line.done { background: var(--bl-green); }

        /* ════ VIEW 1: Phone entry ════ */
        #phoneView { width: 100%; }
        #phoneView .card { width: 100%; max-width: 400px; }
        .reg-header { padding: 2rem 2rem 0; text-align: center; margin-bottom: 1.75rem; }
        .reg-header h2 { font-size: 1.35rem; font-weight: 800; margin-bottom: 0.3rem; }
        .reg-header p  { font-size: 0.82rem; color: var(--bl-muted); }
        .reg-body { padding: 0 2rem 2rem; }

        /* ════ VIEW 2: OTP Verify ════ */
        #otpView { display: none; width: 100%; }
        .otp-layout {
            display: flex; flex-direction: column;
            align-items: center;
        }
        #otpView .card { width: 100%; max-width: 420px; }

        /* SMS pane */
        .sms-pane {
            background: #111; border-bottom: 1px solid var(--bl-border);
            border-radius: 13px 13px 0 0; padding: 1.25rem 1.5rem;
            width: 100%;
        }
        .sms-pane-title {
            font-size: 0.68rem; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; color: var(--bl-muted); margin-bottom: 1rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .sms-pane-title i { color: var(--bl-yellow); }
        .sms-sender-row {
            display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.6rem;
        }
        .sms-sender-icon {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--bl-yellow); color: #0e0e0e;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 900; flex-shrink: 0;
        }
        .sms-sender-name { font-size: 0.82rem; font-weight: 700; }
        .sms-sender-time { font-size: 0.7rem; color: var(--bl-muted); margin-left: auto; }
        .sms-bubble {
            background: var(--bl-surface2); border: 1px solid var(--bl-border);
            border-radius: 0 10px 10px 10px; padding: 0.85rem 1rem;
            font-size: 0.8rem; color: #c4c4c4; line-height: 1.65; margin-left: 2.75rem;
        }
        .sms-otp-display {
            font-size: 1.75rem; font-weight: 900; color: var(--bl-yellow);
            letter-spacing: 0.3em; font-family: 'Courier New', monospace;
            margin: 0.4rem 0; display: block;
        }
        .sms-to { font-size: 0.68rem; color: #52525b; margin-top: 0.65rem; }

        /* OTP entry pane */
        .otp-entry-pane {
            padding: 1.5rem 1.75rem 1.75rem;
            display: flex; flex-direction: column; align-items: center; width: 100%;
        }
        .otp-entry-header {
            text-align: center; margin-bottom: 1.25rem; width: 100%;
        }
        .otp-entry-header h3 { font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem; }
        .otp-entry-header p  { font-size: 0.78rem; color: var(--bl-muted); }
        .otp-boxes {
            display: flex; gap: 0.5rem; margin-bottom: 1.25rem; width: 100%; justify-content: center;
        }
        .otp-box {
            flex: 1; max-width: 48px; height: 50px;
            background: var(--bl-dark); border: 2px solid var(--bl-border);
            border-radius: 8px; text-align: center; font-size: 1.2rem;
            font-weight: 700; font-family: 'Courier New', monospace;
            color: var(--bl-text); outline: none; transition: border-color 0.2s;
            caret-color: var(--bl-yellow);
        }
        .otp-box:focus { border-color: var(--bl-yellow); box-shadow: 0 0 0 3px var(--bl-yellow-glow); }
        .otp-hint {
            font-size: 0.72rem; color: var(--bl-muted); margin-bottom: 1.1rem;
            display: flex; align-items: center; gap: 0.35rem;
        }
        .otp-hint i { color: var(--bl-yellow); }

        /* ════ VIEW 3: Dashboard ════ */
        #dashboardView { display: none; width: 100%; }
        .dash-header {
            background: var(--bl-surface); border-bottom: 1px solid var(--bl-border);
            padding: 0 2rem; height: 52px;
            display: flex; align-items: center; gap: 1.5rem;
        }
        .dash-header-title { color: var(--bl-yellow); font-weight: 800; font-size: 0.95rem; letter-spacing: -0.02em; }
        .dash-nav { display: flex; gap: 0; flex: 1; }
        .dash-nav a {
            color: rgba(255,255,255,0.5); text-decoration: none;
            font-size: 0.78rem; padding: 0 1rem; height: 52px;
            display: flex; align-items: center;
            border-bottom: 2px solid transparent; transition: all 0.15s;
        }
        .dash-nav a:hover, .dash-nav a.active { color: #fff; border-bottom-color: var(--bl-yellow); }
        .dash-user {
            display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.78rem; color: rgba(255,255,255,0.7);
        }
        .dash-user-dot { width: 8px; height: 8px; background: var(--bl-green); border-radius: 50%; }
        .dash-body { padding: 2rem; background: #0a0a0a; min-height: calc(100vh - 108px); }

        /* Bypass alert */
        .bypass-alert {
            background: rgba(245,197,24,0.07); border: 1px solid rgba(245,197,24,0.25);
            border-left: 4px solid var(--bl-yellow); border-radius: 8px;
            padding: 0.9rem 1.1rem; margin-bottom: 1.75rem;
            display: flex; align-items: flex-start; gap: 0.75rem;
            font-size: 0.82rem; color: #fde68a;
        }

        /* Profile card */
        .user-profile-card {
            background: var(--bl-surface); border: 1px solid var(--bl-border);
            border-radius: 10px; padding: 1.25rem 1.5rem;
            display: flex; align-items: center; gap: 1.1rem;
            margin-bottom: 1.5rem; max-width: 500px;
        }
        .user-avatar {
            width: 52px; height: 52px; border-radius: 50%;
            background: linear-gradient(135deg, var(--bl-yellow), #f59e0b);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem; font-weight: 800; color: #0e0e0e; flex-shrink: 0;
        }
        .user-info h3 { font-size: 1rem; font-weight: 700; margin-bottom: 0.12rem; }
        .user-info p  { font-size: 0.78rem; color: var(--bl-muted); }
        .user-badge {
            margin-top: 0.35rem; display: inline-flex; align-items: center; gap: 0.3rem;
            background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.25);
            color: #86efac; font-size: 0.65rem; font-weight: 700;
            padding: 0.12rem 0.45rem; border-radius: 4px;
        }

        /* Stats */
        .dash-stats {
            display: grid; grid-template-columns: repeat(3,1fr);
            gap: 1rem; margin-bottom: 1.75rem; max-width: 600px;
        }
        .stat-card {
            background: var(--bl-surface); border: 1px solid var(--bl-border);
            border-radius: 8px; padding: 1rem;
        }
        .stat-label { font-size: 0.68rem; color: var(--bl-muted); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 0.35rem; }
        .stat-val   { font-size: 1.5rem; font-weight: 800; color: var(--bl-yellow); }
        .stat-sub   { font-size: 0.68rem; color: var(--bl-muted); margin-top: 0.1rem; }

        /* ── Tab panels ── */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* Section header */
        .section-header {
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;
        }
        .section-title { font-size: 1rem; font-weight: 700; }
        .section-sub   { font-size: 0.78rem; color: var(--bl-muted); margin-top: 0.15rem; }

        /* Order cards */
        .orders-list { display: flex; flex-direction: column; gap: 0.85rem; max-width: 680px; }
        .order-card {
            background: var(--bl-surface); border: 1px solid var(--bl-border);
            border-radius: 10px; padding: 1rem 1.25rem;
            display: flex; align-items: center; gap: 1rem;
        }
        .order-icon {
            width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
            background: rgba(245,197,24,0.1); border: 1px solid rgba(245,197,24,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: var(--bl-yellow);
        }
        .order-info { flex: 1; min-width: 0; }
        .order-name { font-size: 0.88rem; font-weight: 600; margin-bottom: 0.15rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .order-meta { font-size: 0.72rem; color: var(--bl-muted); }
        .order-right { text-align: right; flex-shrink: 0; }
        .order-total { font-size: 0.95rem; font-weight: 700; color: var(--bl-text); }
        .order-date  { font-size: 0.68rem; color: var(--bl-muted); margin-top: 0.15rem; }
        .status-badge {
            display: inline-block; font-size: 0.62rem; font-weight: 700;
            padding: 0.1rem 0.45rem; border-radius: 3px;
            text-transform: uppercase; letter-spacing: 0.05em;
        }
        .status-badge.delivered  { background: rgba(34,197,94,0.1);  color: #86efac; border: 1px solid rgba(34,197,94,0.25); }
        .status-badge.processing { background: rgba(245,197,24,0.1); color: #fde047; border: 1px solid rgba(245,197,24,0.25); }
        .status-badge.cancelled  { background: rgba(239,68,68,0.1);  color: #fca5a5; border: 1px solid rgba(239,68,68,0.25); }
        .orders-empty {
            text-align: center; padding: 3rem 1rem; color: var(--bl-muted); font-size: 0.82rem;
        }
        .orders-empty i { font-size: 2rem; display: block; margin-bottom: 0.75rem; color: var(--bl-border); }

        /* Lab info box */
        .lab-info-box {
            background: linear-gradient(135deg, #111, #1a1400);
            border: 1px solid #2a2000; border-left: 4px solid var(--bl-yellow);
            border-radius: 8px; padding: 1.1rem 1.25rem; margin-top: 2rem;
            max-width: 780px; color: #e2e8f0;
        }
        .lab-info-box h4 {
            font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; color: var(--bl-yellow); margin-bottom: 0.5rem;
            display: flex; align-items: center; gap: 0.4rem;
        }
        .lab-info-box p   { font-size: 0.8rem; color: #94a3b8; line-height: 1.65; }
        .lab-info-box code {
            background: rgba(255,255,255,0.07); padding: 0.1rem 0.35rem;
            border-radius: 3px; font-family: 'Courier New', monospace;
            font-size: 0.74rem; color: var(--bl-yellow);
        }
        .lab-meta-row {
            display: flex; flex-wrap: wrap; gap: 0.75rem 1.25rem;
            margin-top: 0.65rem; padding-top: 0.65rem; border-top: 1px solid #1e1500;
        }
        .lab-meta-item { font-size: 0.7rem; color: #64748b; }
        .lab-meta-item strong { color: #94a3b8; }

        /* Addresses tab */
        .address-list { display: flex; flex-direction: column; gap: 0.85rem; max-width: 520px; }
        .address-card {
            background: var(--bl-surface); border: 1px solid var(--bl-border);
            border-radius: 10px; padding: 1.1rem 1.25rem;
            display: flex; gap: 1rem; align-items: flex-start;
        }
        .address-icon {
            width: 36px; height: 36px; border-radius: 8px; flex-shrink: 0;
            background: rgba(245,197,24,0.1); display: flex; align-items: center;
            justify-content: center; color: var(--bl-yellow); font-size: 1rem;
        }
        .address-type  { font-size: 0.72rem; font-weight: 700; color: var(--bl-yellow); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.2rem; }
        .address-text  { font-size: 0.82rem; color: #a1a1aa; line-height: 1.55; }
        .btn-add-addr  {
            margin-top: 0.75rem; background: transparent;
            border: 1px dashed var(--bl-border); color: var(--bl-muted);
            border-radius: 10px; padding: 0.85rem 1.25rem; font-size: 0.8rem;
            cursor: pointer; width: 100%; max-width: 520px;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            transition: border-color 0.2s, color 0.2s;
        }
        .btn-add-addr:hover { border-color: var(--bl-yellow); color: var(--bl-yellow); }

        /* Wallet tab */
        .wallet-card {
            background: linear-gradient(135deg, #1a1400, #201a00);
            border: 1px solid rgba(245,197,24,0.2); border-radius: 14px;
            padding: 1.75rem; max-width: 400px; margin-bottom: 1.25rem;
        }
        .wallet-label { font-size: 0.72rem; color: #a0916b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.4rem; }
        .wallet-balance { font-size: 2.5rem; font-weight: 900; color: var(--bl-yellow); margin-bottom: 0.25rem; }
        .wallet-sub { font-size: 0.78rem; color: #7a6c40; margin-bottom: 1.25rem; }
        .btn-add-money {
            width: 100%; background: var(--bl-yellow); color: #0e0e0e;
            border: none; border-radius: 8px; padding: 0.7rem;
            font-size: 0.88rem; font-weight: 700; cursor: pointer; transition: background 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        }
        .btn-add-money:hover { background: var(--bl-yellow-d); }
        .txn-card {
            background: var(--bl-surface); border: 1px solid var(--bl-border);
            border-radius: 10px; overflow: hidden; max-width: 400px;
        }
        .txn-header {
            padding: 0.75rem 1.1rem; font-size: 0.72rem; font-weight: 700;
            letter-spacing: 0.07em; text-transform: uppercase;
            color: var(--bl-muted); border-bottom: 1px solid var(--bl-border);
        }
        .txn-row {
            display: flex; align-items: center; padding: 0.8rem 1.1rem;
            border-bottom: 1px solid rgba(255,255,255,0.03); gap: 0.85rem;
        }
        .txn-row:last-child { border-bottom: none; }
        .txn-icon { font-size: 1rem; color: var(--bl-yellow); flex-shrink: 0; }
        .txn-info { flex: 1; }
        .txn-name { font-size: 0.8rem; font-weight: 600; }
        .txn-date { font-size: 0.68rem; color: var(--bl-muted); margin-top: 0.1rem; }
        .txn-amount { font-size: 0.88rem; font-weight: 700; }
        .txn-amount.credit { color: var(--bl-green); }
        .txn-amount.debit  { color: #f87171; }

        /* Settings form */
        .settings-card {
            background: var(--bl-surface); border: 1px solid var(--bl-border);
            border-radius: 10px; padding: 1.75rem; max-width: 480px;
        }
        .settings-card h3 { font-size: 0.9rem; font-weight: 700; margin-bottom: 1.25rem; }
        .settings-note { font-size: 0.7rem; color: var(--bl-muted); margin-top: 0.35rem; }
        .input-readonly {
            width: 100%; background: rgba(255,255,255,0.03);
            border: 1px solid var(--bl-border); border-radius: 8px;
            padding: 0.6rem 0.85rem; font-size: 0.88rem;
            color: var(--bl-muted); cursor: not-allowed;
        }

        /* Toast */
        .toast {
            position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 2000;
            background: var(--bl-surface); border: 1px solid var(--bl-border);
            border-radius: 8px; padding: 0.8rem 1.1rem;
            display: flex; align-items: center; gap: 0.6rem;
            font-size: 0.8rem; box-shadow: 0 8px 32px rgba(0,0,0,0.6);
            transform: translateY(8px); opacity: 0;
            transition: all 0.25s; pointer-events: none;
        }
        .toast.show { transform: translateY(0); opacity: 1; pointer-events: auto; }
        .toast.success { border-left: 3px solid var(--bl-green); color: #86efac; }
        .toast.error   { border-left: 3px solid var(--bl-red); color: #fca5a5; }
    </style>
</head>
<body>

    <!-- App header -->
    <div class="app-header">
        <a class="app-logo" href="#">
            <div class="app-logo-icon"><i class="bi bi-lightning-charge-fill"></i></div>
            <span class="app-logo-name">blinkit</span>
        </a>
        <div class="app-header-right">Groceries in 10 minutes</div>
    </div>

    <!-- ════════════════════ VIEW 1: PHONE ENTRY ════════════════════ -->
    <div class="page-wrap" id="phoneView">
        <div class="card">
            <div class="reg-header">
                <div class="steps">
                    <div class="step">
                        <div class="step-dot active">1</div>
                        <div class="step-label active">Enter Number</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step">
                        <div class="step-dot">2</div>
                        <div class="step-label">Verify OTP</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step">
                        <div class="step-dot">3</div>
                        <div class="step-label">Orders</div>
                    </div>
                </div>
                <h2>Log in or Sign up</h2>
                <p>Enter your mobile number to continue</p>
            </div>
            <div class="reg-body">
                <form id="phoneForm" onsubmit="submitPhone(event)">
                    <div class="form-group">
                        <label class="form-label">Full Name <span style="color:var(--bl-muted);font-weight:400;">(required for new accounts)</span></label>
                        <input type="text" id="regName" class="form-control" placeholder="e.g. Rahul Sharma" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mobile Number</label>
                        <div class="phone-input-wrap">
                            <div class="phone-prefix">🇮🇳 +91</div>
                            <input type="tel" id="regPhone" class="phone-input"
                                placeholder="9876543210" maxlength="10"
                                inputmode="numeric" pattern="[0-9]{10}" autocomplete="off">
                        </div>
                    </div>
                    <button type="submit" class="btn-primary" id="phoneBtn">
                        <i class="bi bi-lightning-charge-fill"></i> Send OTP
                    </button>
                    <div class="alert-error" id="phoneError"></div>
                </form>
                <div class="divider-text">By continuing, you agree to blinkit's Terms of Service</div>
            </div>
        </div>
    </div>

    <!-- ════════════════════ VIEW 2: OTP VERIFY ════════════════════ -->
    <div class="page-wrap" id="otpView">
        <div class="otp-layout">
            <div class="card" style="width:100%;max-width:420px;">
                <!-- Simulated SMS Inbox -->
                <div class="sms-pane">
                    <div class="sms-pane-title">
                        <i class="bi bi-phone-fill"></i> Simulated SMS Inbox
                    </div>
                    <div class="sms-sender-row">
                        <div class="sms-sender-icon">B</div>
                        <div class="sms-sender-name">Blinkit</div>
                        <div class="sms-sender-time">Just now</div>
                    </div>
                    <div class="sms-bubble">
                        Hi <strong id="smsName">there</strong>! Your Blinkit OTP is:<br>
                        <span class="sms-otp-display" id="otpDisplay">------</span>
                        Valid for <strong>10 minutes</strong>. Do not share this OTP with anyone.
                        <br><br>
                        <span style="font-size:0.7rem;color:#52525b;">
                            – Team Blinkit
                        </span>
                    </div>
                    <div class="sms-to">Sent to: <span id="smsPhone">+91 —</span></div>
                </div>

                <!-- OTP Entry -->
                <div class="otp-entry-pane">
                    <div class="otp-entry-header">
                        <div class="steps" style="margin-bottom:1.1rem;">
                            <div class="step">
                                <div class="step-dot done"><i class="bi bi-check" style="font-size:0.85rem;"></i></div>
                                <div class="step-label">Enter Number</div>
                            </div>
                            <div class="step-line done"></div>
                            <div class="step">
                                <div class="step-dot active">2</div>
                                <div class="step-label active">Verify OTP</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step">
                                <div class="step-dot">3</div>
                                <div class="step-label">Orders</div>
                            </div>
                        </div>
                        <h3><i class="bi bi-shield-check" style="color:var(--bl-yellow);"></i> Verify your number</h3>
                        <p>Enter the 6-digit code sent to <strong id="otpPhoneHint">your phone</strong></p>
                    </div>
                    <div class="otp-hint"><i class="bi bi-info-circle-fill"></i> Code expires in 10 minutes</div>
                    <form id="otpForm" onsubmit="submitOtp(event)" style="width:100%;">
                        <div class="otp-boxes">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                        </div>
                        <button type="submit" class="btn-primary" id="otpBtn">
                            <i class="bi bi-shield-check-fill"></i> Verify OTP
                        </button>
                        <div class="alert-error" id="otpError"></div>
                    </form>
                    <div class="divider-text" style="margin-top:1rem;">
                        Didn't receive? <a href="#" onclick="return false;">Resend OTP</a> &nbsp;·&nbsp;
                        <a href="#" onclick="backToPhone(); return false;">Change Number</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════ VIEW 3: DASHBOARD ════════════════════ -->
    <div id="dashboardView">
        <div class="dash-header">
            <span class="dash-header-title"><i class="bi bi-lightning-charge-fill"></i> blinkit</span>
            <nav class="dash-nav">
                <a href="#" class="active" onclick="switchTab('orders',this);   return false;"><i class="bi bi-bag-check" style="margin-right:0.3rem;"></i>Orders</a>
                <a href="#"               onclick="switchTab('addresses',this); return false;"><i class="bi bi-geo-alt"   style="margin-right:0.3rem;"></i>Addresses</a>
                <a href="#"               onclick="switchTab('wallet',this);    return false;"><i class="bi bi-wallet2"   style="margin-right:0.3rem;"></i>Wallet</a>
                <a href="#"               onclick="switchTab('profile',this);   return false;"><i class="bi bi-person"    style="margin-right:0.3rem;"></i>Profile</a>
                <a href="#" onclick="logout(); return false;" style="color:#ef4444;"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
            <div class="dash-user">
                <div class="dash-user-dot"></div>
                <span id="dashUserName">User</span>
            </div>
        </div>

        <div class="dash-body">

            <!-- ══ TAB: ORDERS ══ -->
            <div class="tab-panel active" id="tab-orders">
                <div class="user-profile-card">
                    <div class="user-avatar" id="dashAvatar">U</div>
                    <div class="user-info">
                        <h3 id="dashFullName">Welcome!</h3>
                        <p id="dashPhone">+91 —</p>
                        <div class="user-badge"><i class="bi bi-check-circle-fill"></i> Number Verified</div>
                    </div>
                </div>

                <div class="dash-stats">
                    <div class="stat-card">
                        <div class="stat-label"><i class="bi bi-bag-check"></i> Total Orders</div>
                        <div class="stat-val" id="statOrders">—</div>
                        <div class="stat-sub">All time</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label"><i class="bi bi-wallet2"></i> Blinkit Cash</div>
                        <div class="stat-val" id="statWallet">—</div>
                        <div class="stat-sub">Available balance</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label"><i class="bi bi-clock-history"></i> Last Order</div>
                        <div class="stat-val" id="statLast" style="font-size:0.85rem;">—</div>
                        <div class="stat-sub">Recent activity</div>
                    </div>
                </div>

                <div class="section-header" style="max-width:680px;">
                    <div>
                        <div class="section-title">Recent Orders</div>
                        <div class="section-sub">Your Blinkit order history</div>
                    </div>
                </div>
                <div id="ordersContainer">
                    <div class="orders-empty"><i class="bi bi-bag"></i>Loading orders…</div>
                </div>
            </div>

            <!-- ══ TAB: ADDRESSES ══ -->
            <div class="tab-panel" id="tab-addresses">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="bi bi-geo-alt" style="color:var(--bl-yellow);"></i> Saved Addresses</div>
                        <div class="section-sub">Your delivery locations</div>
                    </div>
                </div>
                <div class="address-list">
                    <div class="address-card">
                        <div class="address-icon"><i class="bi bi-house-fill"></i></div>
                        <div>
                            <div class="address-type">Home</div>
                            <div class="address-text" id="addrHome">Loading…</div>
                        </div>
                    </div>
                    <div class="address-card">
                        <div class="address-icon"><i class="bi bi-briefcase-fill"></i></div>
                        <div>
                            <div class="address-type">Work</div>
                            <div class="address-text" id="addrWork">Loading…</div>
                        </div>
                    </div>
                </div>
                <button class="btn-add-addr" onclick="showToast('Add address is not available in this lab demo.','error')">
                    <i class="bi bi-plus-circle"></i> Add New Address
                </button>
            </div>

            <!-- ══ TAB: WALLET ══ -->
            <div class="tab-panel" id="tab-wallet">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="bi bi-wallet2" style="color:var(--bl-yellow);"></i> Blinkit Cash</div>
                        <div class="section-sub">Your Blinkit wallet balance</div>
                    </div>
                </div>
                <div class="wallet-card">
                    <div class="wallet-label">Available Balance</div>
                    <div class="wallet-balance" id="walletBalance">₹0.00</div>
                    <div class="wallet-sub">Use at checkout for instant payment</div>
                    <button class="btn-add-money" onclick="showToast('Add Money is not available in this lab demo.','error')">
                        <i class="bi bi-plus-circle-fill"></i> Add Money
                    </button>
                </div>
                <div class="txn-card">
                    <div class="txn-header">Recent Transactions</div>
                    <div class="txn-row">
                        <div class="txn-icon"><i class="bi bi-gift-fill"></i></div>
                        <div class="txn-info">
                            <div class="txn-name">Cashback — First Order</div>
                            <div class="txn-date">Credited 15 days ago</div>
                        </div>
                        <div class="txn-amount credit">+₹50</div>
                    </div>
                    <div class="txn-row">
                        <div class="txn-icon"><i class="bi bi-tag-fill"></i></div>
                        <div class="txn-info">
                            <div class="txn-name">Referral Bonus</div>
                            <div class="txn-date">Credited 8 days ago</div>
                        </div>
                        <div class="txn-amount credit" id="walletCreditExtra">+₹0</div>
                    </div>
                    <div class="txn-row">
                        <div class="txn-icon"><i class="bi bi-bag-fill"></i></div>
                        <div class="txn-info">
                            <div class="txn-name">Order Payment</div>
                            <div class="txn-date">3 days ago</div>
                        </div>
                        <div class="txn-amount debit">–₹50</div>
                    </div>
                </div>
            </div>

            <!-- ══ TAB: PROFILE ══ -->
            <div class="tab-panel" id="tab-profile">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="bi bi-person-circle" style="color:var(--bl-yellow);"></i> Profile</div>
                        <div class="section-sub">Manage your account details</div>
                    </div>
                </div>
                <div class="settings-card">
                    <h3>Personal Information</h3>
                    <form onsubmit="saveProfile(event)">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" id="settingsName" class="form-control" placeholder="Your full name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mobile Number</label>
                            <div class="input-readonly" id="settingsPhone">—</div>
                            <div class="settings-note">Phone number cannot be changed.</div>
                        </div>
                        <button type="submit" class="btn-primary" id="saveProfileBtn" style="margin-top:0.75rem;">
                            <i class="bi bi-check-lg"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>

        </div><!-- /dash-body -->
    </div><!-- /dashboardView -->

    <!-- Toast -->
    <div class="toast" id="toast"><i class="bi bi-check-circle-fill"></i><span id="toastMsg"></span></div>

    <script>
    let currentPhone  = '';
    let currentName   = '';
    let currentWallet = 0;
    let _toastTimer   = null;

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
    function saveSession(name, phone, wallet, bypassed) {
        localStorage.setItem('lab203_session', JSON.stringify({ name, phone, wallet: wallet || 0, bypassed: !!bypassed }));
    }
    function logout() {
        localStorage.removeItem('lab203_session');
        location.reload();
    }

    // ════ DASHBOARD INIT ════
    function showDashboard(name, phone, wallet, bypassed) {
        currentPhone  = phone;
        currentName   = name;
        currentWallet = parseFloat(wallet) || 0;

        const fmt = formatPhone(phone);
        document.getElementById('dashUserName').textContent = name;
        document.getElementById('dashFullName').textContent = name;
        document.getElementById('dashPhone').textContent    = fmt;
        document.getElementById('dashAvatar').textContent   = name.charAt(0).toUpperCase();
        document.getElementById('statWallet').textContent   = '₹' + currentWallet.toFixed(2);
        document.getElementById('walletBalance').textContent= '₹' + currentWallet.toFixed(2);

        document.getElementById('settingsName').value        = name;
        document.getElementById('settingsPhone').textContent = fmt;

        // Wallet credit
        const extra = (currentWallet - 50).toFixed(0);
        document.getElementById('walletCreditExtra').textContent = extra > 0 ? '+₹' + extra : '+₹0';

        // Addresses (personalised per user)
        setAddresses(phone);

        document.getElementById('phoneView').style.display     = 'none';
        document.getElementById('otpView').style.display       = 'none';
        document.getElementById('dashboardView').style.display = 'block';

        loadOrders();
    }

    function formatPhone(p) {
        const d = p.replace(/^\+91/, '').replace(/\D/g,'');
        return d.length === 10 ? '+91 ' + d.substring(0,5) + ' ' + d.substring(5) : p;
    }

    function setAddresses(phone) {
        const map = {
            '+919876543210': ['Flat 4B, Sunrise Apartments,\nMG Road, Bengaluru – 560001', 'Block C, 3rd Floor, Prestige Tech Park,\nWhitefield, Bengaluru – 560066'],
            '+918765432109': ['Door 22, Green Valley Layout,\nHSR Layout, Bengaluru – 560102', 'WeWork Galaxy, 43 Residency Rd,\nBengaluru – 560025'],
            '+917654321098': ['S-7, Shivam Heights, Prabhadevi,\nMumbai – 400025', '9th Floor, One BKC Tower,\nBandra Kurla Complex, Mumbai – 400051'],
            '+916543210987': ['3/14 Rajouri Garden Extn,\nNew Delhi – 110027', 'F-12, Cyber City Tower B,\nGurgaon – 122002'],
            '+919988776655': ['Plot 46, Kaveri Nagar,\nHyderabad – 500062', '2nd Floor, DLF Cyber Hub,\nHyderabad – 500081'],
        };
        const addrs = map[phone] || ['Saved address not available', 'Saved address not available'];
        document.getElementById('addrHome').textContent = addrs[0];
        document.getElementById('addrWork').textContent = addrs[1];
    }

    // ════ TABS ════
    function switchTab(tab, el) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.dash-nav a').forEach(a => a.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        if (el) el.classList.add('active');
    }

    // ════ ORDERS ════
    async function loadOrders() {
        const cont = document.getElementById('ordersContainer');
        cont.innerHTML = '<div class="orders-empty"><i class="bi bi-hourglass-split"></i>Loading orders…</div>';
        try {
            const resp = await fetch('?action=get_orders', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ phone: currentPhone })
            });
            const data = await resp.json();

            document.getElementById('statOrders').textContent = data.ok ? data.orders.length : '0';

            if (!data.ok || data.orders.length === 0) {
                cont.innerHTML = '<div class="orders-empty"><i class="bi bi-bag"></i>No orders yet. Start shopping!</div>';
                document.getElementById('statLast').textContent = '—';
                return;
            }

            const icons = ['bi-egg-fried','bi-basket2-fill','bi-box-seam-fill','bi-bag-fill','bi-cart-fill','bi-shop'];
            let html = '<div class="orders-list">';
            data.orders.forEach((o, idx) => {
                const d    = new Date(o.created_at);
                const date = d.toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'});
                const ic   = icons[idx % icons.length];
                html += `<div class="order-card">
                    <div class="order-icon"><i class="bi ${ic}"></i></div>
                    <div class="order-info">
                        <div class="order-name">${esc(o.item_name)}</div>
                        <div class="order-meta">Qty: ${o.quantity} &nbsp;·&nbsp; <span class="status-badge ${o.status}">${o.status.replace('_',' ')}</span></div>
                    </div>
                    <div class="order-right">
                        <div class="order-total">₹${parseFloat(o.total).toFixed(2)}</div>
                        <div class="order-date">${date}</div>
                    </div>
                </div>`;
            });
            html += '</div>';
            cont.innerHTML = html;

            // Last order date
            const last = new Date(data.orders[0].created_at);
            const diff = Math.round((Date.now() - last.getTime()) / 36e5);
            document.getElementById('statLast').textContent = diff < 24 ? diff + 'h ago' : Math.round(diff/24) + 'd ago';
        } catch(e) {
            cont.innerHTML = '<div class="orders-empty"><i class="bi bi-exclamation-circle"></i>Failed to load orders.</div>';
        }
    }

    // ════ PROFILE SAVE ════
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
                body: JSON.stringify({ phone: currentPhone, name })
            });
            const data = await resp.json();
            if (data.ok) {
                currentName = data.name;
                document.getElementById('dashUserName').textContent = data.name;
                document.getElementById('dashFullName').textContent = data.name;
                document.getElementById('dashAvatar').textContent   = data.name.charAt(0).toUpperCase();
                const s = JSON.parse(localStorage.getItem('lab203_session') || '{}');
                s.name = data.name;
                localStorage.setItem('lab203_session', JSON.stringify(s));
                showToast('Profile updated!');
            } else { showToast(data.message || 'Update failed.', 'error'); }
        } catch(e) { showToast('Network error.', 'error'); }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Save Changes';
    }

    // ════ BACK TO PHONE ════
    function backToPhone() {
        document.getElementById('otpView').style.display  = 'none';
        document.getElementById('phoneView').style.display = 'flex';
    }

    // ════ OTP BOXES ════
    const boxes = document.querySelectorAll('.otp-box');
    boxes.forEach((box, i) => {
        box.addEventListener('input', () => {
            box.value = box.value.replace(/\D/, '');
            if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
        });
        box.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !box.value && i > 0) boxes[i - 1].focus();
        });
        box.addEventListener('paste', e => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'');
            [...text].slice(0,6).forEach((ch, idx) => { if (boxes[idx]) boxes[idx].value = ch; });
            boxes[Math.min(text.length, 5)].focus();
        });
    });
    function getOtpValue() { return [...boxes].map(b => b.value).join(''); }

    // ════ SUBMIT PHONE ════
    async function submitPhone(e) {
        e.preventDefault();
        const btn    = document.getElementById('phoneBtn');
        const errDiv = document.getElementById('phoneError');
        errDiv.style.display = 'none';

        const name  = document.getElementById('regName').value.trim();
        const digits = document.getElementById('regPhone').value.trim().replace(/\D/g,'');
        const phone = '+91' + digits;

        if (!digits || digits.length !== 10) {
            errDiv.textContent = 'Enter a valid 10-digit mobile number.';
            errDiv.style.display = 'block'; return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending OTP…';

        try {
            const resp = await fetch('?action=send_otp', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ name, phone })
            });
            const data = await resp.json();

            if (data.ok) {
                currentPhone = data.phone;
                currentName  = data.name;
                document.getElementById('smsName').textContent    = data.name.split(' ')[0];
                document.getElementById('otpDisplay').textContent = data.otp;
                document.getElementById('smsPhone').textContent   = formatPhone(data.phone);
                document.getElementById('otpPhoneHint').textContent = formatPhone(data.phone);
                document.getElementById('phoneView').style.display = 'none';
                document.getElementById('otpView').style.display   = 'flex';
                setTimeout(() => boxes[0].focus(), 100);
            } else {
                errDiv.textContent = data.message || 'Failed to send OTP.';
                errDiv.style.display = 'block';
            }
        } catch(err) {
            errDiv.textContent = 'Network error. Please try again.';
            errDiv.style.display = 'block';
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> Send OTP';
    }

    // ════ VERIFY OTP ════
    async function submitOtp(e) {
        e.preventDefault();
        const btn    = document.getElementById('otpBtn');
        const errDiv = document.getElementById('otpError');
        errDiv.style.display = 'none';

        const otp = getOtpValue();
        if (otp.length < 6) {
            errDiv.textContent = 'Please enter the full 6-digit OTP.';
            errDiv.style.display = 'block'; return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Verifying…';

        try {
            const resp = await fetch('?action=verify', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ phone: currentPhone, otp })
            });
            const data = await resp.json();

            // VULNERABLE: app trusts the client-received "verify" field.
            // No server-side session is created or checked.
            // Use Burp Suite to intercept this response and flip:
            //   "verify":false  →  "verify":true
            if (data.verify === true) {
                saveSession(data.name || currentName, currentPhone, data.wallet || 0, true);
                showDashboard(data.name || currentName, currentPhone, data.wallet || 0, true);
            } else {
                errDiv.textContent = data.message || 'Incorrect OTP. Please try again.';
                errDiv.style.display = 'block';
                boxes.forEach(b => { b.value = ''; });
                boxes[0].focus();
            }
        } catch(err) {
            errDiv.textContent = 'Network error. Please try again.';
            errDiv.style.display = 'block';
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-shield-check-fill"></i> Verify OTP';
    }

    // ════ UTILITY ════
    function esc(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    // ════ INIT: restore session ════
    (function init() {
        const raw = localStorage.getItem('lab203_session');
        if (!raw) return;
        try {
            const s = JSON.parse(raw);
            if (s && s.name && s.phone) {
                showDashboard(s.name, s.phone, s.wallet || 0, s.bypassed || false);
            }
        } catch(e) {}
    })();
    </script>
</body>
</html>
