<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_broadband_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255),
        `role` VARCHAR(50) DEFAULT 'admin',
        `last_login` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed default admin account
    $stmt = $pdo->prepare("SELECT id FROM lab_broadband_users WHERE username = 'admin' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_broadband_users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $insert->execute(['admin', 'admin', 'System Administrator', 'admin']);
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
    
    $stmt = $pdo->prepare("SELECT * FROM lab_broadband_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['password'] === $password) {
        $_SESSION['broadband_user'] = $user;
        $_SESSION['broadband_user_id'] = $user['id'];
        
        // Update last login
        $update = $pdo->prepare("UPDATE lab_broadband_users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        header('Location: index.php?action=dashboard');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}

// Check authentication
$is_authenticated = isset($_SESSION['broadband_user']);
$current_user = $_SESSION['broadband_user'] ?? null;

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
    <title>MTN Broadband Maps - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f0f0f0;
            min-height: 100vh;
        }
        
        .header {
            background-color: #000;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo {
            color: #ffcc00;
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
        }
        
        .logo span {
            color: #fff;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
        }
        
        .nav-links a {
            color: #fff;
            text-decoration: none;
            font-size: 14px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .login-box {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
        }
        
        .login-box h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #ffcc00;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #ffcc00;
            border: none;
            border-radius: 4px;
            color: #000;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .btn-login:hover {
            background-color: #e6b800;
        }
        
        .error {
            color: #d32f2f;
            background-color: #ffebee;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .footer {
            background-color: #000;
            color: #fff;
            text-align: center;
            padding: 20px;
            font-size: 12px;
        }
        
        /* Dashboard Styles */
        .dashboard-header {
            background-color: #000;
            color: #fff;
            padding: 20px 30px;
        }
        
        .dashboard-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .dashboard-header p {
            color: #999;
            font-size: 14px;
        }
        
        .dashboard-content {
            padding: 30px;
        }
        
        .info-section {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .info-section h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 2px solid #ffcc00;
            padding-bottom: 10px;
        }
        
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-label {
            width: 200px;
            font-weight: bold;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .warning-box h4 {
            color: #856404;
            margin: 0 0 10px 0;
        }
        
        .warning-box p {
            color: #856404;
            margin: 0;
        }
        
        .logout-btn {
            background-color: #ffcc00;
            color: #000;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background-color: #e6b800;
        }
    </style>
</head>
<body>
<?php if ($action === 'login'): ?>
<div class="header">
    <a href="#" class="logo">MTN <span>Broadband</span></a>
    <div class="nav-links">
        <a href="#">Home</a>
        <a href="#">Coverage</a>
        <a href="#">Plans</a>
        <a href="#">Support</a>
    </div>
</div>

<div class="container">
    <div class="login-box">
        <h2>Login to Broadband Maps</h2>
        
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
    </div>
</div>

<div class="footer">
    &copy; 2021 MTN Group. All rights reserved.
</div>

<?php elseif ($action === 'dashboard'): ?>
<div class="header">
    <a href="#" class="logo">MTN <span>Broadband Maps</span></a>
    <div class="nav-links">
        <a href="#">Dashboard</a>
        <a href="#">Coverage Map</a>
        <a href="#">Reports</a>
        <a href="index.php?action=logout" class="logout-btn">Logout</a>
    </div>
</div>

<div class="container">
    <div class="warning-box">
        <h4>⚠️ Security Warning</h4>
        <p>This system is using default credentials (admin/admin). Immediate password change is required.</p>
    </div>
    
    <div style="background: white; border-radius: 5px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h3 style="color: #333; font-size: 18px; margin-bottom: 15px; border-bottom: 2px solid #ffcc00; padding-bottom: 10px;">Broadband Coverage Map</h3>
        <div style="background-color: #e8e8e8; border: 2px dashed #ccc; border-radius: 5px; height: 400px; display: flex; align-items: center; justify-content: center; color: #666; font-size: 16px;">
            <div style="text-align: center;">
                <div style="font-size: 48px; margin-bottom: 10px;">🗺️</div>
                <p>Interactive Coverage Map</p>
                <small>Ghana Broadband Coverage Visualization</small>
            </div>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
        <div style="background: white; border-radius: 5px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center;">
            <h3 style="color: #ffcc00; font-size: 36px; margin: 0;">85%</h3>
            <p style="color: #666; margin: 10px 0 0 0;">National Coverage</p>
        </div>
        <div style="background: white; border-radius: 5px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center;">
            <h3 style="color: #ffcc00; font-size: 36px; margin: 0;">2,450</h3>
            <p style="color: #666; margin: 10px 0 0 0;">Active Sites</p>
        </div>
        <div style="background: white; border-radius: 5px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center;">
            <h3 style="color: #ffcc00; font-size: 36px; margin: 0;">12.5M</h3>
            <p style="color: #666; margin: 10px 0 0 0;">Subscribers</p>
        </div>
        <div style="background: white; border-radius: 5px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center;">
            <h3 style="color: #ffcc00; font-size: 36px; margin: 0;">98.5%</h3>
            <p style="color: #666; margin: 10px 0 0 0;">Uptime</p>
        </div>
    </div>
    
    <div style="background: white; border-radius: 5px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h3 style="color: #333; font-size: 18px; margin-bottom: 15px; border-bottom: 2px solid #ffcc00; padding-bottom: 10px;">Regional Coverage Status</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #000; color: #fff;">
                    <th style="padding: 12px; text-align: left;">Region</th>
                    <th style="padding: 12px; text-align: center;">Coverage</th>
                    <th style="padding: 12px; text-align: center;">Sites</th>
                    <th style="padding: 12px; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">Greater Accra</td>
                    <td style="padding: 12px; text-align: center;">95%</td>
                    <td style="padding: 12px; text-align: center;">850</td>
                    <td style="padding: 12px; text-align: center;"><span style="background-color: #28a745; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">Active</span></td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">Ashanti</td>
                    <td style="padding: 12px; text-align: center;">88%</td>
                    <td style="padding: 12px; text-align: center;">620</td>
                    <td style="padding: 12px; text-align: center;"><span style="background-color: #28a745; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">Active</span></td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">Northern</td>
                    <td style="padding: 12px; text-align: center;">72%</td>
                    <td style="padding: 12px; text-align: center;">380</td>
                    <td style="padding: 12px; text-align: center;"><span style="background-color: #28a745; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">Active</span></td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">Western</td>
                    <td style="padding: 12px; text-align: center;">81%</td>
                    <td style="padding: 12px; text-align: center;">320</td>
                    <td style="padding: 12px; text-align: center;"><span style="background-color: #28a745; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">Active</span></td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">Eastern</td>
                    <td style="padding: 12px; text-align: center;">76%</td>
                    <td style="padding: 12px; text-align: center;">180</td>
                    <td style="padding: 12px; text-align: center;"><span style="background-color: #ffc107; color: #000; padding: 4px 12px; border-radius: 12px; font-size: 12px;">Maintenance</span></td>
                </tr>
                <tr>
                    <td style="padding: 12px;">Volta</td>
                    <td style="padding: 12px; text-align: center;">68%</td>
                    <td style="padding: 12px; text-align: center;">100</td>
                    <td style="padding: 12px; text-align: center;"><span style="background-color: #28a745; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">Active</span></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div style="background: white; border-radius: 5px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h3 style="color: #333; font-size: 18px; margin-bottom: 15px; border-bottom: 2px solid #ffcc00; padding-bottom: 10px;">Administrative Actions</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <button style="padding: 12px; background-color: #ffcc00; border: none; border-radius: 4px; color: #000; font-weight: bold; cursor: pointer;">Add New Site</button>
            <button style="padding: 12px; background-color: #ffcc00; border: none; border-radius: 4px; color: #000; font-weight: bold; cursor: pointer;">Edit Coverage</button>
            <button style="padding: 12px; background-color: #ffcc00; border: none; border-radius: 4px; color: #000; font-weight: bold; cursor: pointer;">Generate Report</button>
            <button style="padding: 12px; background-color: #ffcc00; border: none; border-radius: 4px; color: #000; font-weight: bold; cursor: pointer;">Manage Users</button>
        </div>
    </div>
</div>

<div class="footer">
    &copy; 2021 MTN Group. All rights reserved.
</div>

<?php endif; ?>
</body>
</html>
