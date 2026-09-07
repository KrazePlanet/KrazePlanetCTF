<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_nagios_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `role` VARCHAR(50) DEFAULT 'admin',
        `last_login` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed default nagiosadmin account
    $stmt = $pdo->prepare("SELECT id FROM lab_nagios_users WHERE username = 'nagiosadmin' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_nagios_users (username, password, role) VALUES (?, ?, ?)");
        $insert->execute(['nagiosadmin', 'nagiosadmin', 'admin']);
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
    
    $stmt = $pdo->prepare("SELECT * FROM lab_nagios_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['password'] === $password) {
        $_SESSION['nagios_user'] = $user;
        $_SESSION['nagios_user_id'] = $user['id'];
        
        // Update last login
        $update = $pdo->prepare("UPDATE lab_nagios_users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        header('Location: index.php?action=dashboard');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}

// Check authentication
$is_authenticated = isset($_SESSION['nagios_user']);
$current_user = $_SESSION['nagios_user'] ?? null;

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
    <title>Nagios Core</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #fff;
            color: #333;
            font-size: 12px;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        }
        
        .login-box {
            background: white;
            border: 2px solid #ccc;
            border-radius: 5px;
            width: 100%;
            max-width: 350px;
            padding: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        
        .login-box h2 {
            color: #cc0000;
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        
        .login-box p {
            color: #666;
            font-size: 11px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            color: #333;
            font-size: 11px;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #999;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #cc0000;
        }
        
        .btn-login {
            width: 100%;
            padding: 10px;
            background-color: #cc0000;
            border: none;
            border-radius: 3px;
            color: white;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .btn-login:hover {
            background-color: #aa0000;
        }
        
        .error {
            color: #cc0000;
            background-color: #ffeeee;
            padding: 10px;
            border: 1px solid #cc0000;
            border-radius: 3px;
            margin-bottom: 15px;
            font-size: 11px;
        }
        
        /* Dashboard Styles */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 200px;
            background-color: #1a1a1a;
            color: white;
            padding: 0;
        }
        
        .sidebar-header {
            background-color: #cc0000;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .sidebar-nav {
            padding: 10px 0;
        }
        
        .sidebar-nav a {
            display: block;
            color: #fff;
            text-decoration: none;
            padding: 8px 15px;
            font-size: 11px;
            border-bottom: 1px solid #333;
        }
        
        .sidebar-nav a:hover {
            background-color: #333;
        }
        
        .sidebar-nav a.active {
            background-color: #cc0000;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .header {
            background-color: #fff;
            border-bottom: 2px solid #cc0000;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #cc0000;
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
            font-size: 11px;
        }
        
        .info-box {
            background: white;
            border: 1px solid #ccc;
            border-radius: 3px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .info-box h3 {
            color: #cc0000;
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 1px solid #cc0000;
            padding-bottom: 5px;
        }
        
        .status-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        .status-table th {
            background-color: #1a1a1a;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        
        .status-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        
        .status-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-ok {
            color: #00aa00;
            font-weight: bold;
        }
        
        .status-warning {
            color: #ffaa00;
            font-weight: bold;
        }
        
        .status-critical {
            color: #cc0000;
            font-weight: bold;
        }
        
        .status-unknown {
            color: #888;
            font-weight: bold;
        }
        
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 3px;
        }
        
        .warning-box h4 {
            color: #856404;
            margin: 0 0 5px 0;
            font-size: 12px;
        }
        
        .warning-box p {
            color: #856404;
            margin: 0;
            font-size: 11px;
        }
        
        .logout-btn {
            background-color: #cc0000;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background-color: #aa0000;
        }
        
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .stat-card {
            background: white;
            border: 1px solid #ccc;
            border-radius: 3px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-card h3 {
            color: #cc0000;
            font-size: 24px;
            margin: 0;
        }
        
        .stat-card p {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 11px;
        }
    </style>
</head>
<body>
<?php if ($action === 'login'): ?>
<div class="login-container">
    <div class="login-box">
        <h2>Nagios Core</h2>
        <p>Monitoring and Infrastructure Management</p>
        
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
        
        <p style="margin-top: 15px; font-size: 10px; color: #999;">
            omon1.fpki.gov | 3.220.248.203
        </p>
    </div>
</div>

<?php elseif ($action === 'dashboard'): ?>
<div class="dashboard-container">
    <div class="sidebar">
        <div class="sidebar-header">Nagios Core</div>
        <div class="sidebar-nav">
            <a href="#" class="active">Dashboard</a>
            <a href="#">Monitoring</a>
            <a href="#">Hosts</a>
            <a href="#">Services</a>
            <a href="#">Host Groups</a>
            <a href="#">Service Groups</a>
            <a href="#">Reports</a>
            <a href="#">Configuration</a>
            <a href="#">System</a>
            <a href="index.php?action=logout" style="color: #ff6666;">Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Nagios Core Dashboard</h1>
            <p>omon1.fpki.gov - U.S. General Services Administration</p>
            <p style="margin-top: 10px;">Welcome, <?php echo htmlspecialchars($current_user['username']); ?> | <a href="index.php?action=logout" class="logout-btn">Logout</a></p>
        </div>
        
        <div class="warning-box">
            <h4>⚠️ Security Warning</h4>
            <p>This Nagios instance is using default credentials (nagiosadmin/nagiosadmin). Immediate password change is required to secure the monitoring system.</p>
        </div>
        
        <div class="stat-cards">
            <div class="stat-card">
                <h3>45</h3>
                <p>Total Hosts</p>
            </div>
            <div class="stat-card">
                <h3>234</h3>
                <p>Total Services</p>
            </div>
            <div class="stat-card">
                <h3 class="status-ok">42</h3>
                <p>Hosts Up</p>
            </div>
            <div class="stat-card">
                <h3 class="status-critical">3</h3>
                <p>Hosts Down</p>
            </div>
        </div>
        
        <div class="info-box">
            <h3>Host Status</h3>
            <table class="status-table">
                <thead>
                    <tr>
                        <th>Host Name</th>
                        <th>Status</th>
                        <th>Last Check</th>
                        <th>Duration</th>
                        <th>Status Information</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>omon1.fpki.gov</td>
                        <td class="status-ok">UP</td>
                        <td>0m 30s ago</td>
                        <td>45d 12h 30m</td>
                        <td>PING OK - Packet loss = 0%, RTA = 0.45ms</td>
                    </tr>
                    <tr>
                        <td>webserver01.fpki.gov</td>
                        <td class="status-ok">UP</td>
                        <td>0m 30s ago</td>
                        <td>30d 8h 15m</td>
                        <td>PING OK - Packet loss = 0%, RTA = 0.52ms</td>
                    </tr>
                    <tr>
                        <td>dbserver01.fpki.gov</td>
                        <td class="status-warning">WARNING</td>
                        <td>0m 30s ago</td>
                        <td>15d 4h 20m</td>
                        <td>PING WARNING - Packet loss = 5%, RTA = 1.20ms</td>
                    </tr>
                    <tr>
                        <td>mailserver.fpki.gov</td>
                        <td class="status-critical">DOWN</td>
                        <td>0m 30s ago</td>
                        <td>2h 15m</td>
                        <td>PING CRITICAL - Packet loss = 100%</td>
                    </tr>
                    <tr>
                        <td>appserver01.fpki.gov</td>
                        <td class="status-ok">UP</td>
                        <td>0m 30s ago</td>
                        <td>25d 18h 45m</td>
                        <td>PING OK - Packet loss = 0%, RTA = 0.38ms</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="info-box">
            <h3>Service Status</h3>
            <table class="status-table">
                <thead>
                    <tr>
                        <th>Host</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Last Check</th>
                        <th>Attempt</th>
                        <th>Status Information</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>omon1.fpki.gov</td>
                        <td>HTTP</td>
                        <td class="status-ok">OK</td>
                        <td>0m 30s ago</td>
                        <td>3/3</td>
                        <td>HTTP OK: HTTP/1.1 200 OK - 0.045 second response time</td>
                    </tr>
                    <tr>
                        <td>omon1.fpki.gov</td>
                        <td>SSH</td>
                        <td class="status-ok">OK</td>
                        <td>0m 30s ago</td>
                        <td>3/3</td>
                        <td>SSH OK - OpenSSH 8.2p1 Ubuntu</td>
                    </tr>
                    <tr>
                        <td>webserver01.fpki.gov</td>
                        <td>HTTP</td>
                        <td class="status-ok">OK</td>
                        <td>0m 30s ago</td>
                        <td>3/3</td>
                        <td>HTTP OK: HTTP/1.1 200 OK - 0.062 second response time</td>
                    </tr>
                    <tr>
                        <td>dbserver01.fpki.gov</td>
                        <td>MySQL</td>
                        <td class="status-warning">WARNING</td>
                        <td>0m 30s ago</td>
                        <td>3/3</td>
                        <td>MySQL WARNING: Slow queries detected (15/sec)</td>
                    </tr>
                    <tr>
                        <td>mailserver.fpki.gov</td>
                        <td>SMTP</td>
                        <td class="status-critical">CRITICAL</td>
                        <td>0m 30s ago</td>
                        <td>3/3</td>
                        <td>SMTP CRITICAL - Unable to connect to port 25</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="info-box">
            <h3>Administrative Capabilities</h3>
            <p style="color: #666; line-height: 1.6; font-size: 11px;">As an administrator, you have full control over the Nagios monitoring system including viewing and modifying host configurations, managing service checks, generating reports, and accessing system settings. Default credentials allow complete administrative access to the monitoring infrastructure.</p>
        </div>
    </div>
</div>

<?php endif; ?>
</body>
</html>
