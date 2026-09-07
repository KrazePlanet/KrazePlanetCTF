<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_remedy_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255),
        `email` VARCHAR(255),
        `role` VARCHAR(50) DEFAULT 'user',
        `department` VARCHAR(255),
        `last_login` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed default admin account
    $stmt = $pdo->prepare("SELECT id FROM lab_remedy_users WHERE username = 'Admin' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_remedy_users (username, password, full_name, email, role, department) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->execute(['Admin', 'RSSO#Admin#', 'System Administrator', 'admin@mtncameroon.net', 'Administrator', 'IT Security']);
    }
    
    // Seed additional users for information disclosure
    $stmt = $pdo->prepare("SELECT id FROM lab_remedy_users WHERE username = 'john.doe' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_remedy_users (username, password, full_name, email, role, department) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->execute(['john.doe', 'password123', 'John Doe', 'john.doe@mtncameroon.net', 'User', 'Finance']);
    }
    
    $stmt = $pdo->prepare("SELECT id FROM lab_remedy_users WHERE username = 'jane.smith' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_remedy_users (username, password, full_name, email, role, department) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->execute(['jane.smith', 'password123', 'Jane Smith', 'jane.smith@mtncameroon.net', 'User', 'Human Resources']);
    }
    
    $stmt = $pdo->prepare("SELECT id FROM lab_remedy_users WHERE username = 'michael.brown' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_remedy_users (username, password, full_name, email, role, department) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->execute(['michael.brown', 'password123', 'Michael Brown', 'michael.brown@mtncameroon.net', 'Manager', 'Operations']);
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
    
    $stmt = $pdo->prepare("SELECT * FROM lab_remedy_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['password'] === $password) {
        $_SESSION['remedy_user'] = $user;
        $_SESSION['remedy_user_id'] = $user['id'];
        
        // Update last login
        $update = $pdo->prepare("UPDATE lab_remedy_users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        header('Location: index.php?action=admin');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}

// Check authentication
$is_authenticated = isset($_SESSION['remedy_user']);
$current_user = $_SESSION['remedy_user'] ?? null;
$is_admin = $current_user && $current_user['role'] === 'Administrator';

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
    <title>Remedy Single Sign-On - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <style>
        :root {
            --remedy-primary: #0066cc;
            --remedy-primary-dark: #004c99;
            --remedy-secondary: #6c757d;
            --remedy-success: #28a745;
            --remedy-warning: #ffc107;
            --remedy-danger: #dc3545;
            --remedy-dark: #343a40;
            --remedy-light: #f8f9fa;
            --remedy-border: #dee2e6;
        }
        
        body {
            background-color: #f4f6f9;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a365d 0%, #2c5282 50%, #2b6cb0 100%);
        }
        
        .login-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--remedy-primary) 0%, var(--remedy-primary-dark) 100%);
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .login-header h1 {
            color: white;
            font-size: 1.5rem;
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
            color: var(--remedy-dark);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-control {
            border: 1px solid var(--remedy-border);
            border-radius: 4px;
            padding: 0.75rem;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: var(--remedy-primary);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }
        
        .btn-remedy {
            background-color: var(--remedy-primary);
            border-color: var(--remedy-primary);
            color: white;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            width: 100%;
            border-radius: 4px;
            font-size: 0.95rem;
        }
        
        .btn-remedy:hover {
            background-color: var(--remedy-primary-dark);
            border-color: var(--remedy-primary-dark);
            color: white;
        }
        
        .login-footer {
            padding: 1rem 2rem;
            background-color: #f8f9fa;
            border-top: 1px solid var(--remedy-border);
            text-align: center;
        }
        
        .login-footer small {
            color: var(--remedy-secondary);
            font-size: 0.8rem;
        }
        
        /* Admin Dashboard Styles */
        .admin-container {
            min-height: 100vh;
            background-color: #f4f6f9;
        }
        
        .navbar-remedy {
            background-color: var(--remedy-dark);
            border-bottom: 3px solid var(--remedy-primary);
            padding: 0.5rem 1rem;
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 600;
            font-size: 1.1rem;
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
            color: var(--remedy-dark);
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
            color: var(--remedy-dark);
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--remedy-primary);
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
            color: var(--remedy-secondary);
            width: 220px;
            flex-shrink: 0;
            font-size: 0.9rem;
        }
        
        .info-value {
            color: var(--remedy-dark);
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .table-remedy {
            font-size: 0.9rem;
        }
        
        .table-remedy th {
            background-color: var(--remedy-primary);
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
            background-color: var(--remedy-danger);
            color: white;
        }
        
        .badge-manager {
            background-color: var(--remedy-warning);
            color: var(--remedy-dark);
        }
        
        .badge-user {
            background-color: var(--remedy-success);
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
            background-color: var(--remedy-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
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
        
        .config-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .config-item:last-child {
            border-bottom: none;
        }
        
        .config-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-enabled {
            background-color: var(--remedy-success);
            color: white;
        }
        
        .status-disabled {
            background-color: var(--remedy-secondary);
            color: white;
        }
    </style>
</head>
<body>
<?php if ($action === 'login'): ?>
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h1><i class="bi bi-shield-lock"></i> Remedy SSO</h1>
            <small>Single Sign-On Administration Console</small>
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
                <button type="submit" class="btn btn-remedy">Sign In</button>
            </form>
        </div>
        
        <div class="login-footer">
            <small>&copy; 2021 BMC Software, Inc. Remedy Single Sign-On</small>
        </div>
    </div>
</div>

<?php elseif ($action === 'admin'): ?>
<div class="admin-container">
    <nav class="navbar navbar-expand-lg navbar-remedy">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-shield-lock"></i> Remedy SSO Admin
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
                        <a class="nav-link" href="#">Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Configuration</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Audit Logs</a>
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
            <h2><i class="bi bi-speedometer2"></i> SSO Administration Dashboard</h2>
            <p class="text-muted">Remedy Single Sign-On - MTN Cameroon</p>
        </div>
        
        <div class="system-alert">
            <h5><i class="bi bi-exclamation-triangle"></i> Security Warning</h5>
            <p>This system is using default Administrator credentials (Admin / RSSO#Admin#). Immediate password change is required to secure the SSO system.</p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="info-card">
                    <h4><i class="bi bi-info-circle"></i> System Information</h4>
                    
                    <div class="info-row">
                        <div class="info-label">Application:</div>
                        <div class="info-value">Remedy Single Sign-On</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Version:</div>
                        <div class="info-value">9.1.3.002</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Build:</div>
                        <div class="info-value">2021-08-15 14:22:00</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Server:</div>
                        <div class="info-value">Apache Tomcat/9.0.50</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Java Version:</div>
                        <div class="info-value">OpenJDK 11.0.11</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">OS:</div>
                        <div class="info-value">Linux (Ubuntu 20.04 LTS)</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Database:</div>
                        <div class="info-value">Oracle Database 19c</div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="info-card">
                    <h4><i class="bi bi-gear"></i> SSO Configuration</h4>
                    
                    <div class="config-item">
                        <div class="info-label">SSO Domain:</div>
                        <div class="info-value">remedysso.mtncameroon.net</div>
                    </div>
                    
                    <div class="config-item">
                        <div class="info-label">Authentication Protocol:</div>
                        <div class="info-value">SAML 2.0 / OAuth 2.0</div>
                    </div>
                    
                    <div class="config-item">
                        <div class="info-label">Session Timeout:</div>
                        <div class="info-value">30 minutes</div>
                    </div>
                    
                    <div class="config-item">
                        <div class="info-label">Multi-Factor Auth:</div>
                        <div class="info-value"><span class="config-status status-disabled">Disabled</span></div>
                    </div>
                    
                    <div class="config-item">
                        <div class="info-label">Password Policy:</div>
                        <div class="info-value">8 chars, 1 uppercase, 1 number</div>
                    </div>
                    
                    <div class="config-item">
                        <div class="info-label">Account Lockout:</div>
                        <div class="info-value"><span class="config-status status-enabled">Enabled (5 attempts)</span></div>
                    </div>
                    
                    <div class="config-item">
                        <div class="info-label">LDAP Integration:</div>
                        <div class="info-value"><span class="config-status status-enabled">Enabled</span></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="info-card">
            <h4><i class="bi bi-people"></i> Organization Users</h4>
            <table class="table table-remedy">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM lab_remedy_users ORDER BY id");
                    while ($row = $stmt->fetch()):
                        $roleClass = 'badge-user';
                        if ($row['role'] === 'Administrator') $roleClass = 'badge-admin';
                        elseif ($row['role'] === 'Manager') $roleClass = 'badge-manager';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                        <td><span class="badge-role <?php echo $roleClass; ?>"><?php echo htmlspecialchars($row['role']); ?></span></td>
                        <td><?php echo $row['last_login'] ? $row['last_login'] : 'Never'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="info-card">
            <h4><i class="bi bi-grid"></i> Connected Applications</h4>
            <table class="table table-remedy">
                <thead>
                    <tr>
                        <th>Application Name</th>
                        <th>URL</th>
                        <th>Protocol</th>
                        <th>Status</th>
                        <th>Users</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>MTN Cameroon Portal</td>
                        <td>portal.mtncameroon.net</td>
                        <td>SAML 2.0</td>
                        <td><span class="config-status status-enabled">Active</span></td>
                        <td>1,245</td>
                    </tr>
                    <tr>
                        <td>HR Management System</td>
                        <td>hr.mtncameroon.net</td>
                        <td>OAuth 2.0</td>
                        <td><span class="config-status status-enabled">Active</span></td>
                        <td>342</td>
                    </tr>
                    <tr>
                        <td>Finance Dashboard</td>
                        <td>finance.mtncameroon.net</td>
                        <td>SAML 2.0</td>
                        <td><span class="config-status status-enabled">Active</span></td>
                        <td>89</td>
                    </tr>
                    <tr>
                        <td>IT Service Desk</td>
                        <td>itsm.mtncameroon.net</td>
                        <td>OAuth 2.0</td>
                        <td><span class="config-status status-enabled">Active</span></td>
                        <td>567</td>
                    </tr>
                    <tr>
                        <td>Customer CRM</td>
                        <td>crm.mtncameroon.net</td>
                        <td>SAML 2.0</td>
                        <td><span class="config-status status-disabled">Inactive</span></td>
                        <td>0</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="info-card">
            <h4><i class="bi bi-shield-check"></i> Security Settings</h4>
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center">
                        <h3 style="color: var(--remedy-primary); font-weight: 600;">4</h3>
                        <small class="text-muted">Connected Apps</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <h3 style="color: var(--remedy-success); font-weight: 600;">2,243</h3>
                        <small class="text-muted">Total Users</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <h3 style="color: var(--remedy-warning); font-weight: 600;">1</h3>
                        <small class="text-muted">Security Alerts</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
</body>
</html>
