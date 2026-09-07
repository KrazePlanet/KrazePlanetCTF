<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_kinetic_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255),
        `email` VARCHAR(255),
        `role` VARCHAR(50) DEFAULT 'user',
        `last_login` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed default admin account
    $stmt = $pdo->prepare("SELECT id FROM lab_kinetic_users WHERE username = 'admin' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_kinetic_users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
        $insert->execute(['admin', 'admin', 'System Administrator', 'admin@kinetic.local', 'admin']);
    }
    
    // Seed additional users for database disclosure
    $stmt = $pdo->prepare("SELECT id FROM lab_kinetic_users WHERE username = 'john.smith' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_kinetic_users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
        $insert->execute(['john.smith', 'password123', 'John Smith', 'john.smith@defense.gov', 'analyst']);
    }
    
    $stmt = $pdo->prepare("SELECT id FROM lab_kinetic_users WHERE username = 'sarah.jones' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_kinetic_users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
        $insert->execute(['sarah.jones', 'password123', 'Sarah Jones', 'sarah.jones@defense.gov', 'manager']);
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
    
    $stmt = $pdo->prepare("SELECT * FROM lab_kinetic_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['password'] === $password) {
        $_SESSION['kinetic_user'] = $user;
        $_SESSION['kinetic_user_id'] = $user['id'];
        
        // Update last login
        $update = $pdo->prepare("UPDATE lab_kinetic_users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        header('Location: index.php?action=dashboard');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}

// Check authentication
$is_authenticated = isset($_SESSION['kinetic_user']);
$current_user = $_SESSION['kinetic_user'] ?? null;
$is_admin = $current_user && $current_user['role'] === 'admin';

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
    <title>Kinetic Core System Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        :root {
            --kinetic-primary: #0056b3;
            --kinetic-primary-dark: #004494;
            --kinetic-secondary: #6c757d;
            --kinetic-success: #28a745;
            --kinetic-warning: #ffc107;
            --kinetic-danger: #dc3545;
            --kinetic-dark: #343a40;
            --kinetic-light: #f8f9fa;
            --kinetic-border: #dee2e6;
        }
        
        body {
            background-color: #f0f2f5;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }
        
        .login-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--kinetic-primary) 0%, var(--kinetic-primary-dark) 100%);
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .login-header h1 {
            color: white;
            font-size: 1.6rem;
            margin: 0 0 0.5rem 0;
            font-weight: 600;
        }
        
        .login-header small {
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.85rem;
        }
        
        .login-body {
            padding: 2.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--kinetic-dark);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-control {
            border: 1px solid var(--kinetic-border);
            border-radius: 4px;
            padding: 0.75rem;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: var(--kinetic-primary);
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
        }
        
        .btn-kinetic {
            background-color: var(--kinetic-primary);
            border-color: var(--kinetic-primary);
            color: white;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            width: 100%;
            border-radius: 4px;
            font-size: 0.95rem;
        }
        
        .btn-kinetic:hover {
            background-color: var(--kinetic-primary-dark);
            border-color: var(--kinetic-primary-dark);
            color: white;
        }
        
        .login-footer {
            padding: 1rem 2rem;
            background-color: #f8f9fa;
            border-top: 1px solid var(--kinetic-border);
            text-align: center;
        }
        
        .login-footer small {
            color: var(--kinetic-secondary);
            font-size: 0.8rem;
        }
        
        /* Dashboard Styles */
        .dashboard-container {
            min-height: 100vh;
            background-color: #f0f2f5;
        }
        
        .navbar-kinetic {
            background-color: var(--kinetic-dark);
            border-bottom: 3px solid var(--kinetic-primary);
            padding: 0.5rem 1rem;
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .navbar-brand i {
            margin-right: 0.5rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .nav-link:hover {
            color: white !important;
        }
        
        .nav-link.active {
            color: white !important;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        
        .main-content {
            padding: 2rem;
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .dashboard-header h2 {
            color: var(--kinetic-dark);
            font-weight: 600;
        }
        
        .info-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .info-card h4 {
            color: var(--kinetic-dark);
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--kinetic-primary);
            font-size: 1.1rem;
        }
        
        .info-row {
            display: flex;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--kinetic-secondary);
            width: 200px;
            flex-shrink: 0;
            font-size: 0.9rem;
        }
        
        .info-value {
            color: var(--kinetic-dark);
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .log-container {
            background-color: #1a1a1a;
            border-radius: 4px;
            padding: 1rem;
            max-height: 300px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
        }
        
        .log-entry {
            color: #00ff00;
            margin-bottom: 0.25rem;
        }
        
        .log-entry.error {
            color: #ff6b6b;
        }
        
        .log-entry.warning {
            color: #ffc107;
        }
        
        .log-entry.info {
            color: #4dabf7;
        }
        
        .table-kinetic {
            font-size: 0.9rem;
        }
        
        .table-kinetic th {
            background-color: var(--kinetic-primary);
            color: white;
            font-weight: 600;
            border: none;
        }
        
        .badge-role {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-admin {
            background-color: var(--kinetic-danger);
            color: white;
        }
        
        .badge-manager {
            background-color: var(--kinetic-warning);
            color: var(--kinetic-dark);
        }
        
        .badge-analyst {
            background-color: var(--kinetic-success);
            color: white;
        }
        
        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--kinetic-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .version-badge {
            background-color: var(--kinetic-success);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .system-alert {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .system-alert h5 {
            color: #856404;
            margin: 0 0 0.5rem 0;
            font-weight: 600;
        }
    </style>
</head>
<body>
<?php if ($action === 'login'): ?>
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h1><i class="bi bi-gear"></i> Kinetic Core</h1>
            <small>System Console v2.1.0-SNAPSHOT</small>
        </div>
        
        <div class="login-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" style="border-radius: 4px;">
                    <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-kinetic">Sign In</button>
            </form>
        </div>
        
        <div class="login-footer">
            <small>&copy; 2023 Kinetic Data. All rights reserved.</small>
        </div>
    </div>
</div>

<?php elseif ($action === 'dashboard'): ?>
<div class="dashboard-container">
    <nav class="navbar navbar-expand-lg navbar-kinetic">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-gear"></i> Kinetic Core Console
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">System Logs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Configuration</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Activity</a>
                    </li>
                </ul>
                
                <div class="user-dropdown">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($current_user['username'], 0, 1)); ?>
                    </div>
                    <span class="text-white"><?php echo htmlspecialchars($current_user['username']); ?></span>
                    <a href="index.php?action=logout" class="btn btn-sm btn-outline-light">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="main-content">
        <div class="dashboard-header">
            <h2><i class="bi bi-speedometer2"></i> System Dashboard</h2>
            <p class="text-muted">Kinetic Core System Console - Administration Panel</p>
        </div>
        
        <div class="system-alert">
            <h5><i class="bi bi-exclamation-triangle"></i> Security Warning</h5>
            <p>This system is using default credentials (admin/admin). Immediate password change is required to secure the system.</p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="info-card">
                    <h4><i class="bi bi-info-circle"></i> System Information</h4>
                    
                    <div class="info-row">
                        <div class="info-label">Application:</div>
                        <div class="info-value">Kinetic Core System Console</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Version:</div>
                        <div class="info-value">2.1.0-SNAPSHOT</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Build Date:</div>
                        <div class="info-value">2023-03-15 14:32:00</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Server:</div>
                        <div class="info-value">Apache Tomcat/9.0.71</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Java Version:</div>
                        <div class="info-value">OpenJDK 11.0.18</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">OS:</div>
                        <div class="info-value">Linux (Ubuntu 20.04 LTS)</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Database:</div>
                        <div class="info-value">MySQL 8.0.32</div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="info-card">
                    <h4><i class="bi bi-server"></i> Server Configuration</h4>
                    
                    <div class="info-row">
                        <div class="info-label">Server Host:</div>
                        <div class="info-value">kinetic-srv-01.defense.gov</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">IP Address:</div>
                        <div class="info-value">192.168.1.100</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Port:</div>
                        <div class="info-value">8443 (HTTPS)</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Deploy Path:</div>
                        <div class="info-value">/opt/kinetic/tomcat/webapps/kinetic</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Config Path:</div>
                        <div class="info-value">/etc/kinetic/config</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Log Path:</div>
                        <div class="info-value">/var/log/kinetic</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Temp Path:</div>
                        <div class="info-value">/tmp/kinetic</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="info-card">
            <h4><i class="bi bi-people"></i> Database Users</h4>
            <table class="table table-kinetic">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM lab_kinetic_users ORDER BY id");
                    while ($row = $stmt->fetch()):
                        $roleClass = 'badge-analyst';
                        if ($row['role'] === 'admin') $roleClass = 'badge-admin';
                        elseif ($row['role'] === 'manager') $roleClass = 'badge-manager';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><span class="badge-role <?php echo $roleClass; ?>"><?php echo ucfirst($row['role']); ?></span></td>
                        <td><?php echo $row['last_login'] ? $row['last_login'] : 'Never'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="info-card">
            <h4><i class="bi bi-terminal"></i> Server Logs</h4>
            <div class="log-container">
                <div class="log-entry info">[2023-04-07 14:32:15] INFO - Application started successfully</div>
                <div class="log-entry info">[2023-04-07 14:32:16] INFO - Loading configuration from /etc/kinetic/config/application.yml</div>
                <div class="log-entry info">[2023-04-07 14:32:17] INFO - Database connection established to MySQL 8.0.32</div>
                <div class="log-entry warning">[2023-04-07 14:32:18] WARN - Default credentials detected: admin/admin</div>
                <div class="log-entry info">[2023-04-07 14:32:19] INFO - User 'admin' logged in from 192.168.1.50</div>
                <div class="log-entry error">[2023-04-07 14:35:22] ERROR - Failed authentication attempt for user 'test' from 10.0.0.1</div>
                <div class="log-entry info">[2023-04-07 14:40:00] INFO - System health check passed</div>
                <div class="log-entry info">[2023-04-07 14:45:12] INFO - User 'john.smith' logged in from 192.168.1.75</div>
                <div class="log-entry warning">[2023-04-07 14:50:33] WARN - High memory usage detected: 85%</div>
                <div class="log-entry info">[2023-04-07 15:00:00] INFO - Scheduled task 'cleanup_logs' executed</div>
                <div class="log-entry info">[2023-04-07 15:10:45] INFO - User 'sarah.jones' logged in from 192.168.1.80</div>
                <div class="log-entry error">[2023-04-07 15:15:22] ERROR - Database connection timeout</div>
                <div class="log-entry info">[2023-04-07 15:16:00] INFO - Database connection re-established</div>
            </div>
        </div>
        
        <div class="info-card">
            <h4><i class="bi bi-activity"></i> System Activity</h4>
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 style="color: var(--kinetic-primary); font-weight: 600;">3</h3>
                        <small class="text-muted">Active Users</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 style="color: var(--kinetic-success); font-weight: 600;">12</h3>
                        <small class="text-muted">Tasks Today</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 style="color: var(--kinetic-warning); font-weight: 600;">2</h3>
                        <small class="text-muted">Warnings</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 style="color: var(--kinetic-danger); font-weight: 600;">1</h3>
                        <small class="text-muted">Errors</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
</body>
</html>
