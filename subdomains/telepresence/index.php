<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_telepresence_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `role` VARCHAR(50) DEFAULT 'admin',
        `device_name` VARCHAR(255),
        `last_login` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed default admin account
    $stmt = $pdo->prepare("SELECT id FROM lab_telepresence_users WHERE username = 'admin' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_telepresence_users (username, password, role, device_name) VALUES (?, ?, ?, ?)");
        $insert->execute(['admin', 'admin', 'admin', 'Cisco TelePresence SX80']);
    }
}

$action = $_GET['action'] ?? 'login';

// Handle logout
if ($action === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM lab_telepresence_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['password'] === $password) {
        $_SESSION['telepresence_user'] = $user;
        $_SESSION['telepresence_user_id'] = $user['id'];
        
        // Update last login
        $update = $pdo->prepare("UPDATE lab_telepresence_users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        header('Location: index.php?action=dashboard');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}

// Check authentication
$is_authenticated = isset($_SESSION['telepresence_user']);
$current_user = $_SESSION['telepresence_user'] ?? null;

// Redirect to login if not authenticated
if (!$is_authenticated && $action !== 'login') {
    header('Location: index.php?action=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cisco TelePresence SX80</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            font-size: 14px;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #005073 0%, #0089ba 50%, #005073 100%);
        }
        
        .login-box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .cisco-logo {
            color: #00bceb;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .login-header h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .login-header p {
            color: #666;
            font-size: 12px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #333;
            font-size: 13px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #00bceb;
            box-shadow: 0 0 0 3px rgba(0, 188, 235, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #005073;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .btn-login:hover {
            background-color: #003d5c;
        }
        
        .error {
            color: #c53030;
            background-color: #fed7d7;
            padding: 12px;
            border: 1px solid #c53030;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        /* Dashboard Styles */
        .dashboard-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .top-bar {
            background-color: #005073;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar .logo {
            color: #00bceb;
            font-size: 20px;
            font-weight: bold;
        }
        
        .top-bar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background-color: #c53030;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background-color: #9b2c2c;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            background-color: #f5f5f5;
        }
        
        .warning-box {
            background-color: #fffaf0;
            border: 1px solid #ed8936;
            border-left: 4px solid #ed8936;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        
        .warning-box h4 {
            color: #c05621;
            margin: 0 0 8px 0;
            font-size: 14px;
        }
        
        .warning-box p {
            color: #c05621;
            margin: 0;
            font-size: 13px;
        }
        
        .info-section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .info-section h3 {
            color: #005073;
            font-size: 18px;
            margin-bottom: 20px;
            border-bottom: 3px solid #00bceb;
            padding-bottom: 10px;
        }
        
        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            width: 200px;
            font-weight: 600;
            color: #4a5568;
        }
        
        .info-value {
            color: #2d3748;
        }
        
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #00bceb;
            font-size: 32px;
            margin: 0;
        }
        
        .stat-card p {
            color: #718096;
            margin: 8px 0 0 0;
            font-size: 13px;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-online {
            background-color: #48bb78;
        }
        
        .status-offline {
            background-color: #f56565;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            padding: 12px;
            background-color: #005073;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .action-btn:hover {
            background-color: #003d5c;
        }
        
        .action-btn.danger {
            background-color: #c53030;
        }
        
        .action-btn.danger:hover {
            background-color: #9b2c2c;
        }
        
        .rce-warning {
            background-color: #fed7d7;
            border: 2px solid #c53030;
            border-left: 4px solid #c53030;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        
        .rce-warning h4 {
            color: #c53030;
            margin: 0 0 10px 0;
            font-size: 15px;
        }
        
        .rce-warning p {
            color: #742a2a;
            margin: 0;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .rce-warning code {
            background-color: #feb2b2;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
<?php if ($action === 'login'): ?>
<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <div class="cisco-logo">CISCO</div>
            <h2>TelePresence SX80</h2>
            <p>Video Conferencing System</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
        
        <p style="margin-top: 20px; font-size: 11px; color: #999; text-align: center;">
            Cisco TelePresence SX80 Codec | Default Admin Access
        </p>
    </div>
</div>

<?php elseif ($action === 'dashboard'): ?>
<div class="dashboard-container">
    <div class="top-bar">
        <div class="logo">CISCO TelePresence SX80</div>
        <div class="user-info">
            <span><?php echo htmlspecialchars($current_user['username']); ?> (Admin)</span>
            <a href="index.php?action=logout" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="warning-box">
            <h4>⚠️ Security Warning</h4>
            <p>This Cisco TelePresence SX80 device is using default administrator credentials (admin/admin). Immediate password change is required to secure the device and prevent unauthorized access.</p>
        </div>
        
        <div class="rce-warning">
            <h4>🚨 Remote Code Execution Risk</h4>
            <p>As an administrator, you can add startup scripts via <code>/web/scripts</code> which could be exploited for remote code execution. This device is used for trainings, briefings, demonstration rooms, and auditoriums - compromise could allow data interception and use as a persistent backdoor.</p>
        </div>
        
        <div class="stat-cards">
            <div class="stat-card">
                <h3><span class="status-indicator status-online"></span>Online</h3>
                <p>Device Status</p>
            </div>
            <div class="stat-card">
                <h3>SX80</h3>
                <p>Model</p>
            </div>
            <div class="stat-card">
                <h3>TC 9.15</h3>
                <p>Firmware Version</p>
            </div>
            <div class="stat-card">
                <h3>45d</h3>
                <p>Uptime</p>
            </div>
        </div>
        
        <div class="info-section">
            <h3>Device Information</h3>
            <div class="info-row">
                <div class="info-label">Product:</div>
                <div class="info-value">Cisco TelePresence SX80 Codec</div>
            </div>
            <div class="info-row">
                <div class="info-label">Serial Number:</div>
                <div class="info-value">FCH1234ABCD</div>
            </div>
            <div class="info-row">
                <div class="info-label">MAC Address:</div>
                <div class="info-value">00:1B:4F:12:34:56</div>
            </div>
            <div class="info-row">
                <div class="info-label">IP Address:</div>
                <div class="info-value">192.168.1.100</div>
            </div>
            <div class="info-row">
                <div class="info-label">Software:</div>
                <div class="info-value">TC9.15.3.1 00004f</div>
            </div>
            <div class="info-row">
                <div class="info-label">Last Used:</div>
                <div class="info-value">2017 (Inactive)</div>
            </div>
        </div>
        
        <div class="info-section">
            <h3>Connection Status</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background-color: #005073; color: white;">
                        <th style="padding: 12px; text-align: left;">Interface</th>
                        <th style="padding: 12px; text-align: left;">Status</th>
                        <th style="padding: 12px; text-align: left;">IP Address</th>
                        <th style="padding: 12px; text-align: left;">Speed</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 12px;">Ethernet 1</td>
                        <td style="padding: 12px;"><span class="status-indicator status-online"></span>Connected</td>
                        <td style="padding: 12px;">192.168.1.100</td>
                        <td style="padding: 12px;">1 Gbps</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 12px;">HDMI 1</td>
                        <td style="padding: 12px;"><span class="status-indicator status-offline"></span>Disconnected</td>
                        <td style="padding: 12px;">-</td>
                        <td style="padding: 12px;">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px;">HDMI 2</td>
                        <td style="padding: 12px;"><span class="status-indicator status-offline"></span>Disconnected</td>
                        <td style="padding: 12px;">-</td>
                        <td style="padding: 12px;">-</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="info-section">
            <h3>Administrative Capabilities</h3>
            <p style="color: #4a5568; line-height: 1.6; margin-bottom: 20px;">As an administrator, you have full control over the TelePresence device including:</p>
            <div class="action-buttons">
                <button class="action-btn">Device Configuration</button>
                <button class="action-btn">Network Settings</button>
                <button class="action-btn">Audio/Video Controls</button>
                <button class="action-btn">Directory Management</button>
                <button class="action-btn">System Diagnostics</button>
                <button class="action-btn danger">Manage Scripts</button>
                <button class="action-btn danger">Startup Scripts</button>
                <button class="action-btn danger">Firmware Update</button>
            </div>
        </div>
        
        <div class="info-section">
            <h3>Usage Context</h3>
            <p style="color: #4a5568; line-height: 1.6;">This Cisco TelePresence SX80 device is primarily used for trainings, briefings, demonstration rooms, and auditoriums within the organization. Due to its location and usage pattern, unauthorized access could allow an attacker to intercept sensitive communications during high-level meetings and briefings, or use the device as a persistent backdoor for network access.</p>
        </div>
    </div>
</div>

<?php endif; ?>
</body>
</html>
