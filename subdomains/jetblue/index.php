<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_tomcat_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `role` VARCHAR(50) DEFAULT 'manager',
        `last_login` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed default tomcat account
    $stmt = $pdo->prepare("SELECT id FROM lab_tomcat_users WHERE username = 'tomcat' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_tomcat_users (username, password, role) VALUES (?, ?, ?)");
        $insert->execute(['tomcat', 'tomcat', 'manager']);
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
    
    $stmt = $pdo->prepare("SELECT * FROM lab_tomcat_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['password'] === $password) {
        $_SESSION['tomcat_user'] = $user;
        $_SESSION['tomcat_user_id'] = $user['id'];
        
        // Update last login
        $update = $pdo->prepare("UPDATE lab_tomcat_users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        header('Location: index.php?action=manager');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}

// Check authentication
$is_authenticated = isset($_SESSION['tomcat_user']);
$current_user = $_SESSION['tomcat_user'] ?? null;

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
    <title>Apache Tomcat/6.0.35 - Manager App</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 0;
        }
        
        .header {
            background-color: #4a5d75;
            color: #fff;
            padding: 10px 20px;
            border-bottom: 3px solid #2c3e50;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .header small {
            font-size: 11px;
            color: #ccc;
        }
        
        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .login-box {
            background-color: #f5f5f5;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
            max-width: 400px;
            margin: 50px auto;
        }
        
        .login-box h2 {
            margin-top: 0;
            color: #4a5d75;
            font-size: 16px;
        }
        
        .form-row {
            margin-bottom: 15px;
        }
        
        .form-row label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .form-row input {
            width: 100%;
            padding: 5px;
            border: 1px solid #999;
            font-size: 12px;
        }
        
        .btn {
            background-color: #4a5d75;
            color: #fff;
            border: none;
            padding: 8px 20px;
            cursor: pointer;
            font-size: 12px;
            border-radius: 3px;
        }
        
        .btn:hover {
            background-color: #3a4d65;
        }
        
        .error {
            color: #c00;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        /* Manager Styles */
        .manager-header {
            background-color: #4a5d75;
            color: #fff;
            padding: 10px 20px;
            margin-bottom: 20px;
        }
        
        .manager-header h2 {
            margin: 0;
            font-size: 16px;
        }
        
        .manager-nav {
            background-color: #e8e8e8;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 20px;
        }
        
        .manager-nav a {
            color: #0066cc;
            text-decoration: none;
            margin-right: 20px;
            font-weight: bold;
        }
        
        .manager-nav a:hover {
            text-decoration: underline;
        }
        
        .manager-nav .logout {
            float: right;
            color: #c00;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h3 {
            background-color: #4a5d75;
            color: #fff;
            padding: 8px 15px;
            margin: 0 0 15px 0;
            font-size: 14px;
        }
        
        .table-container {
            border: 1px solid #ccc;
            background-color: #fff;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        th {
            background-color: #e8e8e8;
            border-bottom: 2px solid #999;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        
        td {
            border-bottom: 1px solid #ddd;
            padding: 8px;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-running {
            color: #090;
            font-weight: bold;
        }
        
        .status-stopped {
            color: #c00;
            font-weight: bold;
        }
        
        .btn-small {
            padding: 3px 10px;
            font-size: 11px;
            margin-right: 5px;
        }
        
        .btn-start {
            background-color: #090;
        }
        
        .btn-stop {
            background-color: #c00;
        }
        
        .btn-reload {
            background-color: #f90;
            color: #000;
        }
        
        .info-box {
            background-color: #f5f5f5;
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-box h4 {
            margin: 0 0 10px 0;
            color: #4a5d75;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            width: 200px;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin-bottom: 20px;
        }
        
        .warning strong {
            color: #856404;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 11px;
            border-top: 1px solid #ccc;
            margin-top: 30px;
        }
    </style>
</head>
<body>
<?php if ($action === 'login'): ?>
<div class="header">
    <h1>Apache Tomcat/6.0.35</h1>
    <small>Manager Application</small>
</div>

<div class="container">
    <div class="login-box">
        <h2>Tomcat Manager Application</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-row">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-row">
                <button type="submit" class="btn">Login</button>
            </div>
        </form>
        
        <p style="margin-top: 20px; font-size: 11px; color: #666;">
            <strong>Note:</strong> Default credentials are often left unchanged in production environments.
        </p>
    </div>
</div>

<div class="footer">
    Apache Tomcat/6.0.35<br>
    <a href="#" style="color: #0066cc;">Documentation</a> | 
    <a href="#" style="color: #0066cc;">FAQ</a> | 
    <a href="#" style="color: #0066cc;">Mailing Lists</a>
</div>

<?php elseif ($action === 'manager'): ?>
<div class="header">
    <h1>Apache Tomcat/6.0.35</h1>
    <small>Manager Application</small>
</div>

<div class="container">
    <div class="manager-header">
        <h2>Tomcat Web Application Manager</h2>
    </div>
    
    <div class="manager-nav">
        <a href="#">Applications</a>
        <a href="#">Server Status</a>
        <a href="#">Server Information</a>
        <a href="#">Deploy</a>
        <a href="#" class="logout" href="index.php?action=logout">Logout</a>
    </div>
    
    <div class="warning">
        <strong>Security Warning:</strong> This Tomcat instance is using default credentials (tomcat:tomcat). 
        This is a critical security vulnerability that allows unauthorized access to the manager interface.
    </div>
    
    <div class="info-box">
        <h4>Server Information</h4>
        <div class="info-row">
            <div class="info-label">Tomcat Version:</div>
            <div class="info-value">Apache Tomcat/6.0.35</div>
        </div>
        <div class="info-row">
            <div class="info-label">JVM Version:</div>
            <div class="info-value">1.6.0_45-b06</div>
        </div>
        <div class="info-row">
            <div class="info-label">OS Name:</div>
            <div class="info-value">Linux</div>
        </div>
        <div class="info-row">
            <div class="info-label">OS Version:</div>
            <div class="info-value">2.6.32-696.el6.x86_64</div>
        </div>
        <div class="info-row">
            <div class="info-label">Architecture:</div>
            <div class="info-value">amd64</div>
        </div>
        <div class="info-row">
            <div class="info-label">Server Time:</div>
            <div class="info-value"><?php echo date('D M d H:i:s T Y'); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">JVM Memory:</div>
            <div class="info-value">Total: 1024 MB, Free: 512 MB, Max: 1024 MB</div>
        </div>
    </div>
    
    <div class="section">
        <h3>Applications</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Path</th>
                        <th>Version</th>
                        <th>Display Name</th>
                        <th>Status</th>
                        <th>Sessions</th>
                        <th>Commands</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>/</td>
                        <td>1.0</td>
                        <td>ROOT</td>
                        <td><span class="status-running">running</span></td>
                        <td>0</td>
                        <td>
                            <button class="btn btn-small btn-stop">Stop</button>
                            <button class="btn btn-small btn-reload">Reload</button>
                            <button class="btn btn-small">Undeploy</button>
                        </td>
                    </tr>
                    <tr>
                        <td>/manager</td>
                        <td>6.0.35</td>
                        <td>Tomcat Manager Application</td>
                        <td><span class="status-running">running</span></td>
                        <td>1</td>
                        <td>
                            <button class="btn btn-small btn-stop">Stop</button>
                            <button class="btn btn-small btn-reload">Reload</button>
                            <button class="btn btn-small">Undeploy</button>
                        </td>
                    </tr>
                    <tr>
                        <td>/docs</td>
                        <td>6.0.35</td>
                        <td>Tomcat Documentation</td>
                        <td><span class="status-running">running</span></td>
                        <td>0</td>
                        <td>
                            <button class="btn btn-small btn-stop">Stop</button>
                            <button class="btn btn-small btn-reload">Reload</button>
                            <button class="btn btn-small">Undeploy</button>
                        </td>
                    </tr>
                    <tr>
                        <td>/examples</td>
                        <td>6.0.35</td>
                        <td>Tomcat Examples</td>
                        <td><span class="status-running">running</span></td>
                        <td>0</td>
                        <td>
                            <button class="btn btn-small btn-stop">Stop</button>
                            <button class="btn btn-small btn-reload">Reload</button>
                            <button class="btn btn-small">Undeploy</button>
                        </td>
                    </tr>
                    <tr>
                        <td>/host-manager</td>
                        <td>6.0.35</td>
                        <td>Tomcat Host Manager Application</td>
                        <td><span class="status-stopped">stopped</span></td>
                        <td>0</td>
                        <td>
                            <button class="btn btn-small btn-start">Start</button>
                            <button class="btn btn-small">Undeploy</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="section">
        <h3>Deploy</h3>
        <div class="info-box">
            <h4>Deploy directory or WAR file located on server</h4>
            <div class="form-row">
                <label>Context Path (optional):</label>
                <input type="text" placeholder="/myapp">
            </div>
            <div class="form-row">
                <label>WAR or Directory URL:</label>
                <input type="text" placeholder="file:/path/to/war">
            </div>
            <button class="btn">Deploy</button>
        </div>
        
        <div class="info-box">
            <h4>Deploy WAR file uploaded to server</h4>
            <div class="form-row">
                <label>Select WAR file to upload:</label>
                <input type="file">
            </div>
            <button class="btn">Deploy</button>
        </div>
    </div>
    
    <div class="section">
        <h3>Server Status</h3>
        <div class="info-box">
            <h4>JVM</h4>
            <div class="info-row">
                <div class="info-label">Free memory:</div>
                <div class="info-value">512.45 MB</div>
            </div>
            <div class="info-row">
                <div class="info-label">Total memory:</div>
                <div class="info-value">1024.00 MB</div>
            </div>
            <div class="info-row">
                <div class="info-label">Max memory:</div>
                <div class="info-value">1024.00 MB</div>
            </div>
        </div>
        
        <div class="info-box">
            <h4>Thread Info</h4>
            <div class="info-row">
                <div class="info-label">Thread count:</div>
                <div class="info-value">25</div>
            </div>
            <div class="info-row">
                <div class="info-label">Peak thread count:</div>
                <div class="info-value">32</div>
            </div>
            <div class="info-row">
                <div class="info-label">Daemon thread count:</div>
                <div class="info-value">23</div>
            </div>
        </div>
    </div>
</div>

<div class="footer">
    Apache Tomcat/6.0.35<br>
    Logged in as: <strong><?php echo htmlspecialchars($current_user['username']); ?></strong> | 
    <a href="index.php?action=logout" style="color: #c00;">Logout</a>
</div>

<?php endif; ?>
</body>
</html>
