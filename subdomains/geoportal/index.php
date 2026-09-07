<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_geoportal_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255),
        `role` VARCHAR(50) DEFAULT 'admin',
        `organization` VARCHAR(255),
        `last_login` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed default admin accounts
    $stmt = $pdo->prepare("SELECT id FROM lab_geoportal_users WHERE username = 'admin' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_geoportal_users (username, password, full_name, role, organization) VALUES (?, ?, ?, ?, ?)");
        $insert->execute(['admin', 'admin', 'System Administrator', 'admin', 'Default Organization']);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_geoportal_users WHERE username = 'gptadmin' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_geoportal_users (username, password, full_name, role, organization) VALUES (?, ?, ?, ?, ?)");
        $insert->execute(['gptadmin', 'gptadmin', 'GPT Administrator', 'admin', 'Default Organization']);
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
    
    $stmt = $pdo->prepare("SELECT * FROM lab_geoportal_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['password'] === $password) {
        $_SESSION['geoportal_user'] = $user;
        $_SESSION['geoportal_user_id'] = $user['id'];
        
        // Update last login
        $update = $pdo->prepare("UPDATE lab_geoportal_users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        header('Location: index.php?action=dashboard');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}

// Check authentication
$is_authenticated = isset($_SESSION['geoportal_user']);
$current_user = $_SESSION['geoportal_user'] ?? null;

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
    <title>GeoPortal - Geographic Information System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f8;
            color: #333;
            font-size: 14px;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 50%, #1e3a5f 100%);
        }
        
        .login-box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #1e3a5f;
            font-size: 24px;
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
            border-color: #2c5282;
            box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #1e3a5f;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .btn-login:hover {
            background-color: #2c5282;
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
            background-color: #1e3a5f;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar h1 {
            font-size: 20px;
            margin: 0;
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
            background-color: #f0f4f8;
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
            color: #1e3a5f;
            font-size: 18px;
            margin-bottom: 20px;
            border-bottom: 3px solid #2c5282;
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
        
        .map-placeholder {
            background-color: #e2e8f0;
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #718096;
            font-size: 16px;
        }
        
        .map-placeholder div {
            text-align: center;
        }
        
        .map-placeholder div div {
            the-size: 48px;
            margin-bottom: 15px;
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
            color: #2c5282;
            font-size: 32px;
            margin: 0;
        }
        
        .stat-card p {
            color: #718096;
            margin: 8px 0 0 0;
            font-size: 13px;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            padding: 12px;
            background-color: #2c5282;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .action-btn:hover {
            background-color: #1e3a5f;
        }
        
        .action-btn.danger {
            background-color: #c53030;
        }
        
        .action-btn.danger:hover {
            background-color: #9b2c2c;
        }
    </style>
</head>
<body>
<?php if ($action === 'login'): ?>
<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <h1>🌍 GeoPortal</h1>
            <p>Geographic Information System</p>
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
            U.S. Department of Defense - GeoPortal System
        </p>
    </div>
</div>

<?php elseif ($action === 'dashboard'): ?>
<div class="dashboard-container">
    <div class="top-bar">
        <h1>🌍 GeoPortal - Geographic Information System</h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($current_user['username']); ?> (<?php echo htmlspecialchars($current_user['role']); ?>)</span>
            <a href="index.php?action=logout" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="warning-box">
            <h4>⚠️ Security Warning</h4>
            <p>This GeoPortal system is using default administrator credentials (admin/admin and gptadmin/gptadmin). Immediate password change is required to secure the system.</p>
        </div>
        
        <div class="stat-cards">
            <div class="stat-card">
                <h3>1,247</h3>
                <p>Active Maps</p>
            </div>
            <div class="stat-card">
                <h3>8,532</h3>
                <p>Geospatial Layers</p>
            </div>
            <div class="stat-card">
                <h3>156</h3>
                <p>Registered Users</p>
            </div>
            <div class="stat-card">
                <h3>99.8%</h3>
                <p>System Uptime</p>
            </div>
        </div>
        
        <div class="info-section">
            <h3>Interactive Map</h3>
            <div class="map-placeholder">
                <div>
                    <div>🗺️</div>
                    <p>Interactive Geographic Map</p>
                    <small>Defense Geographic Information System</small>
                </div>
            </div>
        </div>
        
        <div class="info-section">
            <h3>System Information</h3>
            <div class="info-row">
                <div class="info-label">Application:</div>
                <div class="info-value">GeoPortal GIS</div>
            </div>
            <div class="info-row">
                <div class="info-label">Organization:</div>
                <div class="info-value">U.S. Department of Defense</div>
            </div>
            <div class="info-row">
                <div class="info-label">Version:</div>
                <div class="info-value">3.2.1</div>
            </div>
            <div class="info-row">
                <div class="info-label">Server:</div>
                <div class="info-value">Apache/2.4.52</div>
            </div>
            <div class="info-row">
                <div class="info-label">Database:</div>
                <div class="info-value">PostgreSQL 14.2</div>
            </div>
            <div class="info-row">
                <div class="info-label">Map Engine:</div>
                <div class="info-value">GeoServer 2.21</div>
            </div>
        </div>
        
        <div class="info-section">
            <h3>Administrative Capabilities</h3>
            <p style="color: #4a5568; line-height: 1.6; margin-bottom: 20px;">As an administrator, you have full control over the GeoPortal system including:</p>
            <div class="action-buttons">
                <button class="action-btn">Manage Maps</button>
                <button class="action-btn">Edit Layers</button>
                <button class="action-btn">User Management</button>
                <button class="action-btn">Generate Reports</button>
                <button class="action-btn">System Settings</button>
                <button class="action-btn danger">Delete Posts</button>
                <button class="action-btn danger">Edit Website</button>
                <button class="action-btn danger">Export Data</button>
            </div>
        </div>
        
        <div class="info-section">
            <h3>Recent Activity</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background-color: #1e3a5f; color: white;">
                        <th style="padding: 12px; text-align: left;">Timestamp</th>
                        <th style="padding: 12px; text-align: left;">User</th>
                        <th style="padding: 12px; text-align: left;">Action</th>
                        <th style="padding: 12px; text-align: left;">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 12px;">2023-11-23 14:30:00</td>
                        <td style="padding: 12px;">admin</td>
                        <td style="padding: 12px;">Login</td>
                        <td style="padding: 12px;">Successful authentication</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 12px;">2023-11-23 14:25:00</td>
                        <td style="padding: 12px;">gptadmin</td>
                        <td style="padding: 12px;">Map Update</td>
                        <td style="padding: 12px;">Updated military base coordinates</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 12px;">2023-11-23 14:20:00</td>
                        <td style="padding: 12px;">admin</td>
                        <td style="padding: 12px;">Layer Edit</td>
                        <td style="padding: 12px;">Modified infrastructure layer</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px;">2023-11-23 14:15:00</td>
                        <td style="padding: 12px;">gptadmin</td>
                        <td style="padding: 12px;">Post Delete</td>
                        <td style="padding: 12px;">Deleted outdated map post</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>
</body>
</html>
