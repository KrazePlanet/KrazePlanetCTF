<?php
session_start();
$flag = "flag{idor_banking_support_ticket_disclosure_705}";

$host = 'localhost';
$db = 'VexiumCTF';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function esc($s) {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function seed705($conn, $flag) {
    // Users
    $conn->query("CREATE TABLE IF NOT EXISTS lab705_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(120) NOT NULL UNIQUE,
        password VARCHAR(120) NOT NULL,
        full_name VARCHAR(120) NOT NULL,
        account_type VARCHAR(30) NOT NULL DEFAULT 'standard'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Bank accounts
    $conn->query("CREATE TABLE IF NOT EXISTS lab705_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        account_number VARCHAR(20) NOT NULL UNIQUE,
        account_name VARCHAR(120) NOT NULL,
        balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        currency VARCHAR(10) NOT NULL DEFAULT 'USD',
        account_type VARCHAR(30) NOT NULL DEFAULT 'checking',
        FOREIGN KEY (user_id) REFERENCES lab705_users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Wire transfers
    $conn->query("CREATE TABLE IF NOT EXISTS lab705_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref_number VARCHAR(30) NOT NULL UNIQUE,
        from_account VARCHAR(20) NOT NULL,
        to_account VARCHAR(20) NOT NULL,
        from_name VARCHAR(120) NOT NULL,
        to_name VARCHAR(120) NOT NULL,
        amount DECIMAL(14,2) NOT NULL,
        currency VARCHAR(10) NOT NULL DEFAULT 'USD',
        description TEXT,
        status VARCHAR(20) NOT NULL DEFAULT 'completed',
        transfer_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Support tickets
    $conn->query("CREATE TABLE IF NOT EXISTS lab705_support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        priority VARCHAR(20) NOT NULL DEFAULT 'normal',
        status VARCHAR(30) NOT NULL DEFAULT 'open',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES lab705_users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Check if already seeded
    $check = $conn->query("SELECT COUNT(*) AS cnt FROM lab705_users");
    $row = $check->fetch_assoc();
    if ($row['cnt'] > 0) return;

    // Seed users (passwords are plain text for lab simplicity)
    $conn->query("INSERT INTO lab705_users (email, password, full_name, account_type) VALUES
        ('alice@securebank.com', 'alice123', 'Alice Johnson', 'standard'),
        ('bob@securebank.com', 'bob123', 'Bob Smith', 'business'),
        ('david@securebank.com', 'david123', 'David Chen', 'premium')");

    // Seed accounts
    $conn->query("INSERT INTO lab705_accounts (user_id, account_number, account_name, balance, currency, account_type) VALUES
        (1, '10003451', 'Alice Johnson - Checking', 45280.75, 'USD', 'checking'),
        (1, '10003452', 'Alice Johnson - Savings', 128500.00, 'USD', 'savings'),
        (2, '10003453', 'Bob Smith - Business Account', 892300.50, 'USD', 'business'),
        (3, '10003454', 'David Chen - Premium Account', 2500000.00, 'USD', 'premium'),
        (3, '10003455', 'David Chen - Investment', 5000000.00, 'USD', 'investment')");

    // Seed wire transfers
    $conn->query("INSERT INTO lab705_transactions (ref_number, from_account, to_account, from_name, to_name, amount, currency, description, status, transfer_date) VALUES
        ('TXN-10001', '10003452', '10003451', 'Alice Johnson', 'Alice Johnson', 5000.00, 'USD', 'Internal transfer - Savings to Checking', 'completed', '2026-05-12 09:30:00'),
        ('TXN-10002', '10003453', '10003451', 'Bob Smith', 'Alice Johnson', 25000.00, 'USD', 'Invoice payment - Web development services', 'completed', '2026-05-14 14:15:00'),
        ('TXN-10003', '10003451', '10003453', 'Alice Johnson', 'Bob Smith', 1500.00, 'USD', 'Refund - overpayment correction', 'completed', '2026-05-18 11:00:00'),
        ('TXN-10004', '10003454', '10003455', 'David Chen', 'David Chen', 100000.00, 'USD', 'Transfer to investment account', 'completed', '2026-05-20 10:00:00'),
        ('TXN-10005', '10003454', '10003453', 'David Chen', 'Bob Smith', 75000.00, 'USD', 'Consulting fees - Q2 2026', 'completed', '2026-05-22 16:45:00'),
        ('TXN-10006', '10003455', '10003454', 'David Chen', 'David Chen', 50000.00, 'USD', 'Dividend distribution', 'completed', '2026-05-25 08:30:00'),
        ('TXN-10007', '10003451', '10003454', 'Alice Johnson', 'David Chen', 3200.00, 'USD', 'Freelance payment - Website maintenance', 'pending', '2026-06-01 13:20:00')");

    // Seed support tickets — id=5 contains the flag
    $flagMessage = 'Our premium account (10003454) has been flagged for suspicious API access patterns. Multiple unauthorized attempts to view account statements and transaction details were detected from IP 203.45.67.89 between 02:00-03:00 UTC. The flag for our security team is: ' . $flag . '. Please escalate to the fraud department immediately and enable additional monitoring on our accounts.';
    $stmtTicket = $conn->prepare("INSERT INTO lab705_support_tickets (user_id, subject, message, priority, status, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtTicket->bind_param("isssss", $uid, $subj, $msg, $prio, $stat, $cdate);

    $uid = 1; $subj = 'Online banking login issue'; $msg = 'I am unable to log in to my online banking account. The page keeps timing out after I enter my credentials. I have tried clearing my cache and using a different browser but the issue persists. Please assist.'; $prio = 'high'; $stat = 'open'; $cdate = '2026-05-10 08:15:00';
    $stmtTicket->execute();

    $uid = 1; $subj = 'Missing transaction in statement'; $msg = 'My account statement for April 2026 seems to be missing a debit card transaction of $47.99 at Target on April 15th. I have checked my email receipt and the charge went through. Kindly investigate and update the statement.'; $prio = 'normal'; $stat = 'in_progress'; $cdate = '2026-05-19 10:30:00';
    $stmtTicket->execute();

    $uid = 2; $subj = 'Wire transfer limit increase request'; $msg = 'I need to increase my daily wire transfer limit from $50,000 to $200,000 temporarily for an upcoming business acquisition. Please let me know what documentation is required and the approval process.'; $prio = 'high'; $stat = 'open'; $cdate = '2026-05-23 15:45:00';
    $stmtTicket->execute();

    $uid = 3; $subj = 'International transfer to Hong Kong'; $msg = 'I need to set up a recurring international wire transfer to a beneficiary in Hong Kong (HSBC account). The monthly amount will be approximately $25,000 HKD. Please advise on fees and processing times.'; $prio = 'normal'; $stat = 'open'; $cdate = '2026-05-27 09:00:00';
    $stmtTicket->execute();

    $uid = 3; $subj = 'URGENT: Security Breach Notification'; $msg = $flagMessage; $prio = 'critical'; $stat = 'open'; $cdate = '2026-06-02 06:30:00';
    $stmtTicket->execute();
    $stmtTicket->close();
}

seed705($conn, $flag);

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT id, email, full_name, account_type FROM lab705_users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($user = $res->fetch_assoc()) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['account_type'] = $user['account_type'];
        header('Location: index.php?action=dashboard');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}

// Require login for all actions except login page
$action = $_GET['action'] ?? 'login';
if (!isset($_SESSION['user_id']) && $action !== 'login') {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$fullName = $_SESSION['full_name'] ?? '';
$accountType = $_SESSION['account_type'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SecureBank — Online Banking</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background: #ecfdf5; color: #064e3b; min-height: 100vh; }
.topbar { background: linear-gradient(135deg, #065f46 0%, #047857 100%); color: #fff; padding: 0 32px; display: flex; align-items: center; justify-content: space-between; height: 64px; box-shadow: 0 2px 12px rgba(5,150,105,0.3); position: sticky; top: 0; z-index: 100; }
.topbar .logo { display: flex; align-items: center; gap: 12px; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
.topbar .logo svg { width: 32px; height: 32px; }
.topbar .user-info { display: flex; align-items: center; gap: 20px; font-size: 14px; }
.topbar .user-info span { opacity: 0.9; }
.topbar .user-info .badge { background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.topbar a { color: #fff; text-decoration: none; font-size: 14px; font-weight: 500; padding: 8px 16px; border-radius: 8px; transition: background 0.2s; }
.topbar a:hover { background: rgba(255,255,255,0.15); }
.container { max-width: 1100px; margin: 0 auto; padding: 32px 24px; }
.card { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(5,150,105,0.08); padding: 28px; margin-bottom: 24px; border: 1px solid #d1fae5; }
.card h2 { font-size: 20px; font-weight: 700; color: #065f46; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
.card h2 .subtitle { font-size: 13px; font-weight: 400; color: #6b7280; margin-left: auto; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.account-card { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #fff; border-radius: 14px; padding: 24px; position: relative; overflow: hidden; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; border: none; }
.account-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(5,150,105,0.25); }
.account-card .acct-type { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.75; margin-bottom: 4px; }
.account-card .acct-name { font-size: 16px; font-weight: 600; margin-bottom: 12px; }
.account-card .acct-number { font-size: 18px; font-weight: 700; letter-spacing: 2px; font-family: 'Courier New', monospace; margin-bottom: 8px; }
.account-card .acct-balance { font-size: 28px; font-weight: 800; }
.account-card .acct-balance small { font-size: 14px; font-weight: 400; opacity: 0.8; }
.account-card .card-bg { position: absolute; top: -30%; right: -20%; width: 200px; height: 200px; border-radius: 50%; background: rgba(255,255,255,0.05); }
.account-card .card-bg2 { position: absolute; bottom: -40%; right: -10%; width: 150px; height: 150px; border-radius: 50%; background: rgba(255,255,255,0.08); }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
table th { text-align: left; padding: 12px 16px; background: #f0fdf4; color: #065f46; font-weight: 600; border-bottom: 2px solid #d1fae5; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
table td { padding: 14px 16px; border-bottom: 1px solid #e7f5e8; }
table tr:hover td { background: #f0fdf4; }
table .amount { font-weight: 600; font-family: 'Courier New', monospace; }
table .amount.credit { color: #059669; }
table .amount.debit { color: #dc2626; }
.status-badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge.open { background: #fef3c7; color: #92400e; }
.status-badge.in_progress { background: #dbeafe; color: #1e40af; }
.status-badge.completed { background: #d1fae5; color: #065f46; }
.status-badge.closed { background: #e5e7eb; color: #374151; }
.priority-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
.priority-badge.normal { background: #e5e7eb; color: #374151; }
.priority-badge.high { background: #fef3c7; color: #92400e; }
.priority-badge.critical { background: #fee2e2; color: #991b1b; }
.ref-link { color: #059669; text-decoration: none; font-weight: 500; font-family: 'Courier New', monospace; font-size: 13px; }
.ref-link:hover { text-decoration: underline; }
.view-link { color: #059669; text-decoration: none; font-weight: 500; font-size: 13px; display: inline-flex; align-items: center; gap: 4px; }
.view-link:hover { text-decoration: underline; }
.empty-state { text-align: center; padding: 48px 24px; color: #6b7280; }
.empty-state svg { width: 64px; height: 64px; opacity: 0.3; margin-bottom: 16px; }
.empty-state p { font-size: 16px; }
.login-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #065f46 0%, #059669 50%, #34d399 100%); }
.login-box { background: #fff; border-radius: 20px; padding: 48px 40px; width: 420px; max-width: 90vw; box-shadow: 0 24px 48px rgba(5,150,105,0.2); }
.login-box .logo-lg { text-align: center; margin-bottom: 8px; }
.login-box .logo-lg svg { width: 48px; height: 48px; }
.login-box h1 { text-align: center; font-size: 24px; font-weight: 800; color: #065f46; margin-bottom: 4px; }
.login-box p.tagline { text-align: center; color: #6b7280; font-size: 14px; margin-bottom: 32px; }
.login-box label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
.login-box input[type="email"], .login-box input[type="password"] { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; font-family: 'Inter', sans-serif; transition: border-color 0.2s; margin-bottom: 16px; outline: none; }
.login-box input[type="email"]:focus, .login-box input[type="password"]:focus { border-color: #059669; }
.login-box button { width: 100%; padding: 14px; background: linear-gradient(135deg, #065f46 0%, #059669 100%); color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
.login-box button:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(5,150,105,0.3); }
.login-box .error { background: #fef2f2; color: #991b1b; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; border: 1px solid #fecaca; }
.login-box .test-accounts { margin-top: 24px; padding-top: 20px; border-top: 1px solid #e7f5e8; }
.login-box .test-accounts p { font-size: 12px; color: #6b7280; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.login-box .test-accounts code { display: block; font-size: 12px; color: #374151; padding: 4px 0; font-family: 'Courier New', monospace; }
.detail-row { display: flex; padding: 12px 0; border-bottom: 1px solid #e7f5e8; }
.detail-row:last-child { border-bottom: none; }
.detail-row .label { font-weight: 600; color: #374151; min-width: 160px; font-size: 14px; }
.detail-row .value { font-size: 14px; color: #064e3b; }
.detail-card { background: #f0fdf4; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #d1fae5; }
.detail-card h3 { font-size: 16px; font-weight: 700; color: #065f46; margin-bottom: 12px; }
.flag-notice { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px solid #f59e0b; border-radius: 12px; padding: 16px 20px; margin-top: 16px; font-weight: 600; color: #92400e; text-align: center; font-size: 15px; }
.back-link { display: inline-flex; align-items: center; gap: 6px; color: #059669; text-decoration: none; font-size: 14px; font-weight: 500; margin-bottom: 20px; }
.back-link:hover { text-decoration: underline; }
@media(max-width:700px) {
    .grid-2 { grid-template-columns: 1fr; }
    .container { padding: 16px 12px; }
    .card { padding: 20px 16px; }
    .topbar { padding: 0 16px; }
    .topbar .logo { font-size: 18px; }
    table { font-size: 12px; }
    table th, table td { padding: 10px 8px; }
    .detail-row { flex-direction: column; gap: 4px; }
    .detail-row .label { min-width: auto; }
    .login-box { padding: 32px 24px; }
}
</style>
</head>
<body>

<?php if ($action !== 'login'): ?>
<nav class="topbar">
    <div class="logo">
        <svg viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="rgba(255,255,255,0.2)"/><path d="M16 6L6 12v2h20v-2L16 6z" fill="#fff" opacity="0.9"/><path d="M8 14v10h4V14H8zm6 0v10h4V14h-4zm6 0v10h4V14h-4z" fill="#fff" opacity="0.7"/></svg>
        SecureBank
    </div>
    <div class="user-info">
        <span><?= esc($fullName) ?></span>
        <span class="badge"><?= esc($accountType) ?></span>
        <a href="index.php?action=dashboard">Dashboard</a>
        <a href="index.php?action=transfers">Transfers</a>
        <a href="index.php?action=support">Support</a>
        <a href="index.php?action=logout">Logout</a>
    </div>
</nav>
<?php endif; ?>

<div class="container">
<?php
switch ($action) {

    case 'login':
?>
<div class="login-page">
    <div class="login-box">
        <div class="logo-lg">
            <svg viewBox="0 0 48 48" fill="none" width="48" height="48"><rect width="48" height="48" rx="12" fill="#059669"/><path d="M24 10L10 18v3h28v-3L24 10z" fill="#fff" opacity="0.95"/><path d="M13 21v14h6V21h-6zm8 0v14h6V21h-6zm8 0v14h6V21h-6z" fill="#fff" opacity="0.8"/></svg>
        </div>
        <h1>SecureBank</h1>
        <p class="tagline">Online Banking Portal v3.1</p>
        <?php if ($error): ?>
            <div class="error"><?= esc($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" placeholder="your@email.com" required>
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" required>
            <button type="submit" name="login">Sign In</button>
        </form>
        <div class="test-accounts">
            <p>Test Accounts (Lab)</p>
            <code>alice@securebank.com / alice123</code>
            <code>bob@securebank.com / bob123</code>
            <code>david@securebank.com / david123</code>
        </div>
    </div>
</div>
<?php
    break;

    case 'dashboard':
        // Fetch user's accounts
        $stmt = $conn->prepare("SELECT id, account_number, account_name, balance, currency, account_type FROM lab705_accounts WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $accounts = $stmt->get_result();

        // Fetch recent transactions for user's accounts
        $userAcctNums = [];
        $acctsRes = $conn->prepare("SELECT account_number FROM lab705_accounts WHERE user_id = ?");
        $acctsRes->bind_param("i", $userId);
        $acctsRes->execute();
        $userAcctRows = $acctsRes->get_result()->fetch_all(MYSQLI_ASSOC);
        $userAcctNums = array_column($userAcctRows, 'account_number');

        $recentTxns = null;
        if (!empty($userAcctNums)) {
            $placeholders = implode(',', array_fill(0, count($userAcctNums), '?'));
            $types = str_repeat('s', count($userAcctNums) * 2);
            $allParams = array_merge($userAcctNums, $userAcctNums);
            $stmt2 = $conn->prepare("SELECT t.ref_number, t.from_account, t.to_account, t.from_name, t.to_name, t.amount, t.currency, t.description, t.status, t.transfer_date
                FROM lab705_transactions t
                WHERE t.from_account IN ($placeholders) OR t.to_account IN ($placeholders)
                ORDER BY t.transfer_date DESC LIMIT 5");
            $bindParams = [$types];
            foreach ($allParams as &$p) { $bindParams[] = &$p; }
            unset($p);
            call_user_func_array([$stmt2, 'bind_param'], $bindParams);
            $stmt2->execute();
            $recentTxns = $stmt2->get_result();
        }
?>
<div style="margin-bottom: 24px;">
    <h1 style="font-size: 28px; font-weight: 800; color: #065f46;">Welcome back, <?= esc(explode(' ', $fullName)[0]) ?>!</h1>
    <p style="color: #6b7280; margin-top: 4px;">Here's your financial overview</p>
</div>

<div class="grid-2">
    <?php while ($acct = $accounts->fetch_assoc()): ?>
        <a href="index.php?action=statement&acct=<?= esc($acct['account_number']) ?>" style="text-decoration: none;">
            <div class="account-card">
                <div class="card-bg"></div>
                <div class="card-bg2"></div>
                <div class="acct-type"><?= esc($acct['account_type']) ?> Account</div>
                <div class="acct-name"><?= esc($acct['account_name']) ?></div>
                <div class="acct-number"><?= esc($acct['account_number']) ?></div>
                <div class="acct-balance"><?= esc(number_format($acct['balance'], 2)) ?> <small><?= esc($acct['currency']) ?></small></div>
            </div>
        </a>
    <?php endwhile; ?>
</div>

<div class="card">
    <h2>Recent Transactions</h2>
    <?php if (!$recentTxns || $recentTxns->num_rows === 0): ?>
        <div class="empty-state"><p>No recent transactions</p></div>
    <?php else: ?>
        <table>
            <thead><tr><th>Reference</th><th>Description</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
                <?php while ($txn = $recentTxns->fetch_assoc()): ?>
                <tr>
                    <td><a href="index.php?action=transfer&ref=<?= esc($txn['ref_number']) ?>" class="ref-link"><?= esc($txn['ref_number']) ?></a></td>
                    <td><?= esc($txn['description']) ?></td>
                    <td class="amount <?= ($txn['from_account'] === $txn['to_account']) ? 'credit' : (in_array($txn['from_account'], $userAcctNums) ? 'debit' : 'credit') ?>">
                        <?php
                        if ($txn['from_account'] === $txn['to_account']) {
                            echo esc(number_format($txn['amount'], 2)) . ' ' . esc($txn['currency']);
                        } elseif (in_array($txn['from_account'], $userAcctNums)) {
                            echo '-' . esc(number_format($txn['amount'], 2)) . ' ' . esc($txn['currency']);
                        } else {
                            echo '+' . esc(number_format($txn['amount'], 2)) . ' ' . esc($txn['currency']);
                        }
                        ?>
                    </td>
                    <td><?= esc(date('M j, Y', strtotime($txn['transfer_date']))) ?></td>
                    <td><span class="status-badge <?= esc($txn['status']) ?>"><?= esc(ucfirst(str_replace('_', ' ', $txn['status']))) ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php
    break;

    case 'statement':
        $acctNum = $_GET['acct'] ?? '';
        if (empty($acctNum)) {
            echo '<div class="card"><p>No account number specified.</p></div>';
            break;
        }

        // IDOR: No check if this account belongs to the logged-in user!
        $stmt = $conn->prepare("SELECT a.*, u.full_name FROM lab705_accounts a JOIN lab705_users u ON a.user_id = u.id WHERE a.account_number = ?");
        $stmt->bind_param("s", $acctNum);
        $stmt->execute();
        $acct = $stmt->get_result()->fetch_assoc();

        if (!$acct) {
            echo '<div class="card"><p>Account not found.</p></div>';
            break;
        }

        // Get transactions for this account
        $stmt2 = $conn->prepare("SELECT * FROM lab705_transactions WHERE from_account = ? OR to_account = ? ORDER BY transfer_date DESC LIMIT 20");
        $stmt2->bind_param("ss", $acctNum, $acctNum);
        $stmt2->execute();
        $txns = $stmt2->get_result();
?>
<a href="index.php?action=dashboard" class="back-link">← Back to Dashboard</a>
<div class="card">
    <h2>Account Statement <span class="subtitle"><?= esc($acct['account_number']) ?></span></h2>
    <div class="detail-card">
        <h3><?= esc($acct['account_name']) ?></h3>
        <div class="detail-row"><span class="label">Account Holder</span><span class="value"><?= esc($acct['full_name']) ?></span></div>
        <div class="detail-row"><span class="label">Account Number</span><span class="value"><?= esc($acct['account_number']) ?></span></div>
        <div class="detail-row"><span class="label">Account Type</span><span class="value"><?= esc(ucfirst($acct['account_type'])) ?></span></div>
        <div class="detail-row"><span class="label">Current Balance</span><span class="value" style="font-weight:700;font-size:18px;">$<?= esc(number_format($acct['balance'], 2)) ?></span></div>
    </div>

    <h3 style="font-size:16px;font-weight:600;margin-bottom:12px;">Transaction History</h3>
    <?php if ($txns->num_rows === 0): ?>
        <div class="empty-state"><p>No transactions found for this account.</p></div>
    <?php else: ?>
        <table>
            <thead><tr><th>Ref #</th><th>Description</th><th>From</th><th>To</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
                <?php while ($txn = $txns->fetch_assoc()): ?>
                <tr>
                    <td><a href="index.php?action=transfer&ref=<?= esc($txn['ref_number']) ?>" class="ref-link"><?= esc($txn['ref_number']) ?></a></td>
                    <td><?= esc($txn['description']) ?></td>
                    <td style="font-size:12px;"><?= esc($txn['from_name']) ?><br><span style="color:#6b7280;"><?= esc($txn['from_account']) ?></span></td>
                    <td style="font-size:12px;"><?= esc($txn['to_name']) ?><br><span style="color:#6b7280;"><?= esc($txn['to_account']) ?></span></td>
                    <td class="amount">$<?= esc(number_format($txn['amount'], 2)) ?></td>
                    <td style="font-size:12px;"><?= esc(date('M j, Y', strtotime($txn['transfer_date']))) ?></td>
                    <td><span class="status-badge <?= esc($txn['status']) ?>"><?= esc(ucfirst(str_replace('_', ' ', $txn['status']))) ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php
    break;

    case 'transfer':
        $ref = $_GET['ref'] ?? '';
        if (empty($ref)) {
            echo '<div class="card"><p>No transaction reference specified.</p></div>';
            break;
        }

        // IDOR: No check if this transaction involves the logged-in user!
        $stmt = $conn->prepare("SELECT * FROM lab705_transactions WHERE ref_number = ?");
        $stmt->bind_param("s", $ref);
        $stmt->execute();
        $txn = $stmt->get_result()->fetch_assoc();

        if (!$txn) {
            echo '<div class="card"><p>Transaction not found.</p></div>';
            break;
        }
?>
<a href="javascript:history.back()" class="back-link">← Back</a>
<div class="card">
    <h2>Wire Transfer Details <span class="subtitle"><?= esc($txn['ref_number']) ?></span></h2>
    <div class="detail-card">
        <h3>Transfer Information</h3>
        <div class="detail-row"><span class="label">Reference Number</span><span class="value" style="font-weight:600;"><?= esc($txn['ref_number']) ?></span></div>
        <div class="detail-row"><span class="label">Status</span><span class="value"><span class="status-badge <?= esc($txn['status']) ?>"><?= esc(ucfirst(str_replace('_', ' ', $txn['status']))) ?></span></span></div>
        <div class="detail-row"><span class="label">Transfer Date</span><span class="value"><?= esc(date('F j, Y \a\t g:i A', strtotime($txn['transfer_date']))) ?></span></div>
        <div class="detail-row"><span class="label">Amount</span><span class="value" style="font-weight:700;font-size:18px;color:#059669;">$<?= esc(number_format($txn['amount'], 2)) ?> <?= esc($txn['currency']) ?></span></div>
    </div>
    <div class="grid-2" style="margin-top:16px;">
        <div class="detail-card">
            <h3>Sender</h3>
            <div class="detail-row"><span class="label">Name</span><span class="value"><?= esc($txn['from_name']) ?></span></div>
            <div class="detail-row"><span class="label">Account</span><span class="value"><?= esc($txn['from_account']) ?></span></div>
        </div>
        <div class="detail-card">
            <h3>Recipient</h3>
            <div class="detail-row"><span class="label">Name</span><span class="value"><?= esc($txn['to_name']) ?></span></div>
            <div class="detail-row"><span class="label">Account</span><span class="value"><?= esc($txn['to_account']) ?></span></div>
        </div>
    </div>
    <div class="detail-card">
        <h3>Description</h3>
        <p style="color:#374151;font-size:14px;"><?= esc($txn['description']) ?></p>
    </div>
</div>
<?php
    break;

    case 'support':
        // Fetch user's own tickets
        $stmt = $conn->prepare("SELECT id, subject, message, priority, status, created_at FROM lab705_support_tickets WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $tickets = $stmt->get_result();
?>
<div style="margin-bottom: 24px;">
    <h1 style="font-size: 28px; font-weight: 800; color: #065f46;">Support Center</h1>
    <p style="color: #6b7280; margin-top: 4px;">View and manage your support tickets</p>
</div>

<div class="card">
    <h2>My Support Tickets</h2>
    <?php if ($tickets->num_rows === 0): ?>
        <div class="empty-state"><p>You have no support tickets.</p></div>
    <?php else: ?>
        <table>
            <thead><tr><th>ID</th><th>Subject</th><th>Priority</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>
                <?php while ($ticket = $tickets->fetch_assoc()): ?>
                <tr>
                    <td><strong>#SUP-<?= esc($ticket['id']) ?></strong></td>
                    <td><?= esc($ticket['subject']) ?></td>
                    <td><span class="priority-badge <?= esc($ticket['priority']) ?>"><?= esc($ticket['priority']) ?></span></td>
                    <td><span class="status-badge <?= esc($ticket['status']) ?>"><?= esc(ucfirst(str_replace('_', ' ', $ticket['status']))) ?></span></td>
                    <td style="font-size:12px;"><?= esc(date('M j, Y', strtotime($ticket['created_at']))) ?></td>
                    <td><a href="index.php?action=ticket&id=<?= esc($ticket['id']) ?>" class="view-link">View →</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php
    break;

    case 'ticket':
        $ticketId = (int)($_GET['id'] ?? 0);
        if ($ticketId <= 0) {
            echo '<div class="card"><p>Invalid ticket ID.</p></div>';
            break;
        }

        // IDOR: No check if this ticket belongs to the logged-in user!
        $stmt = $conn->prepare("SELECT t.*, u.full_name, u.email, u.account_type FROM lab705_support_tickets t JOIN lab705_users u ON t.user_id = u.id WHERE t.id = ?");
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $ticket = $stmt->get_result()->fetch_assoc();

        if (!$ticket) {
            echo '<div class="card"><p>Support ticket not found.</p></div>';
            break;
        }

        // Check if message contains the flag
        $hasFlag = (strpos($ticket['message'], 'flag{') !== false);
?>
<a href="index.php?action=support" class="back-link">← Back to Support Tickets</a>
<div class="card">
    <h2>Ticket #SUP-<?= esc($ticket['id']) ?> <span class="subtitle"><?= esc($ticket['subject']) ?></span></h2>
    <div class="detail-card">
        <div class="detail-row"><span class="label">Submitted by</span><span class="value"><?= esc($ticket['full_name']) ?> (<?= esc($ticket['email']) ?>)</span></div>
        <div class="detail-row"><span class="label">Account Type</span><span class="value"><?= esc(ucfirst($ticket['account_type'])) ?></span></div>
        <div class="detail-row"><span class="label">Priority</span><span class="value"><span class="priority-badge <?= esc($ticket['priority']) ?>"><?= esc($ticket['priority']) ?></span></span></div>
        <div class="detail-row"><span class="label">Status</span><span class="value"><span class="status-badge <?= esc($ticket['status']) ?>"><?= esc(ucfirst(str_replace('_', ' ', $ticket['status']))) ?></span></span></div>
        <div class="detail-row"><span class="label">Date</span><span class="value"><?= esc(date('F j, Y \a\t g:i A', strtotime($ticket['created_at']))) ?></span></div>
    </div>
    <div class="detail-card">
        <h3>Message</h3>
        <p style="color: #374151; font-size: 14px; line-height: 1.7; white-space: pre-wrap;"><?= esc($ticket['message']) ?></p>
    </div>
    <?php if ($hasFlag): ?>
        <div class="flag-notice">🎯 FLAG CAPTURED: <?= esc($flag) ?></div>
    <?php endif; ?>
</div>
<?php
    break;

    case 'transfers':
        // Fetch all transactions involving user's accounts
        $stmt = $conn->prepare("SELECT a.account_number FROM lab705_accounts a WHERE a.user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userAccts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $userAcctNums = array_column($userAccts, 'account_number');

        if (empty($userAcctNums)) {
            echo '<div class="card"><p>No accounts found.</p></div>';
            break;
        }

        $placeholders = implode(',', array_fill(0, count($userAcctNums), '?'));
        $types = str_repeat('s', count($userAcctNums) * 2);
        $allParams = array_merge($userAcctNums, $userAcctNums);
        $stmt2 = $conn->prepare("SELECT * FROM lab705_transactions WHERE from_account IN ($placeholders) OR to_account IN ($placeholders) ORDER BY transfer_date DESC");
        $bindParams = [$types];
        foreach ($allParams as &$p) { $bindParams[] = &$p; }
        unset($p);
        call_user_func_array([$stmt2, 'bind_param'], $bindParams);
        $stmt2->execute();
        $txns = $stmt2->get_result();
?>
<div style="margin-bottom: 24px;">
    <h1 style="font-size: 28px; font-weight: 800; color: #065f46;">Wire Transfers</h1>
    <p style="color: #6b7280; margin-top: 4px;">Complete transaction history</p>
</div>

<div class="card">
    <h2>All Transactions</h2>
    <?php if ($txns->num_rows === 0): ?>
        <div class="empty-state"><p>No transactions found.</p></div>
    <?php else: ?>
        <table>
            <thead><tr><th>Reference</th><th>Description</th><th>Sender</th><th>Recipient</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
                <?php while ($txn = $txns->fetch_assoc()): ?>
                <tr>
                    <td><a href="index.php?action=transfer&ref=<?= esc($txn['ref_number']) ?>" class="ref-link"><?= esc($txn['ref_number']) ?></a></td>
                    <td><?= esc($txn['description']) ?></td>
                    <td style="font-size:12px;"><?= esc($txn['from_name']) ?></td>
                    <td style="font-size:12px;"><?= esc($txn['to_name']) ?></td>
                    <td class="amount">$<?= esc(number_format($txn['amount'], 2)) ?></td>
                    <td style="font-size:12px;"><?= esc(date('M j, Y', strtotime($txn['transfer_date']))) ?></td>
                    <td><span class="status-badge <?= esc($txn['status']) ?>"><?= esc(ucfirst(str_replace('_', ' ', $txn['status']))) ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php
    break;

    default:
        header('Location: index.php?action=dashboard');
        exit;
}
?>
</div>
</body>
</html>
