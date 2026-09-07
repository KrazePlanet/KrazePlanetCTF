<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_rundeck_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255),
        `role` VARCHAR(50) DEFAULT 'user',
        `last_login` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed default admin account
    $stmt = $pdo->prepare("SELECT id FROM lab_rundeck_users WHERE username = 'admin' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_rundeck_users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $insert->execute(['admin', 'admin', 'Rundeck Administrator', 'admin']);
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
    
    $stmt = $pdo->prepare("SELECT * FROM lab_rundeck_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['password'] === $password) {
        $_SESSION['rundeck_user'] = $user;
        $_SESSION['rundeck_user_id'] = $user['id'];
        
        // Update last login
        $update = $pdo->prepare("UPDATE lab_rundeck_users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        header('Location: index.php?action=dashboard');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}

// Check authentication
$is_authenticated = isset($_SESSION['rundeck_user']);
$current_user = $_SESSION['rundeck_user'] ?? null;
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
    <title>Rundeck - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        :root {
            --rundeck-dark: #2c3e50;
            --rundeck-darker: #1a252f;
            --rundeck-primary: #3498db;
            --rundeck-primary-hover: #2980b9;
            --rundeck-success: #27ae60;
            --rundeck-warning: #f39c12;
            --rundeck-danger: #e74c3c;
            --rundeck-light: #ecf0f1;
            --rundeck-text: #333;
            --rundeck-text-muted: #7f8c8d;
        }
        
        body {
            background-color: #f5f5f5;
            color: var(--rundeck-text);
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            min-height: 100vh;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .login-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--rundeck-dark) 0%, var(--rundeck-darker) 100%);
            padding: 2rem;
            text-align: center;
        }
        
        .login-header h1 {
            color: white;
            font-size: 1.8rem;
            margin: 0;
            font-weight: 600;
        }
        
        .login-header small {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--rundeck-dark);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 0.75rem;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--rundeck-primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .btn-rundeck {
            background-color: var(--rundeck-primary);
            border-color: var(--rundeck-primary);
            color: white;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            width: 100%;
            border-radius: 4px;
        }
        
        .btn-rundeck:hover {
            background-color: var(--rundeck-primary-hover);
            border-color: var(--rundeck-primary-hover);
            color: white;
        }
        
        .login-footer {
            padding: 1rem 2rem;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
            text-align: center;
        }
        
        .login-footer small {
            color: var(--rundeck-text-muted);
        }
        
        /* Dashboard Styles */
        .dashboard-container {
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        
        .navbar-rundeck {
            background-color: var(--rundeck-dark);
            border-bottom: 3px solid var(--rundeck-primary);
            padding: 0.5rem 1rem;
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 600;
            font-size: 1.3rem;
        }
        
        .navbar-brand i {
            margin-right: 0.5rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            padding: 0.5rem 1rem;
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
            color: var(--rundeck-dark);
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
            color: var(--rundeck-dark);
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--rundeck-primary);
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
            color: var(--rundeck-text-muted);
            width: 180px;
            flex-shrink: 0;
        }
        
        .info-value {
            color: var(--rundeck-text);
            font-family: 'Courier New', monospace;
        }
        
        .error-alert {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .error-alert h5 {
            color: #856404;
            margin: 0 0 0.5rem 0;
            font-weight: 600;
        }
        
        .error-alert pre {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.75rem;
            margin: 0.5rem 0;
            font-size: 0.85rem;
            overflow-x: auto;
        }
        
        .error-alert code {
            color: #d63384;
        }
        
        .version-badge {
            background-color: var(--rundeck-success);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
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
            background-color: var(--rundeck-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>
<?php if ($action === 'login'): ?>
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h1><i class="bi bi-terminal"></i> Rundeck</h1>
            <small>Job Scheduler and Runbook Automation</small>
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
                <button type="submit" class="btn btn-rundeck">Log In</button>
            </form>
        </div>
        
        <div class="login-footer">
            <small>Rundeck Community Edition &copy; 2021</small>
        </div>
    </div>
</div>

<?php elseif ($action === 'dashboard'): ?>
<div class="dashboard-container">
    <nav class="navbar navbar-expand-lg navbar-rundeck">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-terminal"></i> Rundeck
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Nodes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Executions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">System</a>
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
            <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
            <p class="text-muted">Welcome to Rundeck Automation Server</p>
        </div>
        
        <div class="error-alert">
            <h5><i class="bi bi-exclamation-triangle"></i> Server Error (500)</h5>
            <p>An internal server error occurred while processing your request.</p>
            <pre><code>java.lang.NullPointerException
    at com.dtolabs.rundeck.app.api.ApiService.getApiVersion(ApiService.java:245)
    at com.dtolabs.rundeck.app.api.ApiController.handleLogin(ApiController.java:189)
    at sun.reflect.NativeMethodAccessorImpl.invoke0(Native Method)
    at sun.reflect.NativeMethodAccessorImpl.invoke(NativeMethodAccessorImpl.java:62)
    at sun.reflect.DelegatingMethodAccessorImpl.invoke(DelegatingMethodAccessorImpl.java:43)
    at java.lang.reflect.Method.invoke(Method.java:498)
    at org.springframework.web.method.support.InvocableHandlerMethod.doInvoke(InvocableHandlerMethod.java:205)</code></pre>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="info-card">
                    <h4><i class="bi bi-info-circle"></i> System Information</h4>
                    
                    <div class="info-row">
                        <div class="info-label">Rundeck Version:</div>
                        <div class="info-value">3.4.6-20211118</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Build Number:</div>
                        <div class="info-value">3.4.6.GA</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Java Version:</div>
                        <div class="info-value">1.8.0_292</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Server Time:</div>
                        <div class="info-value"><?php echo date('Y-m-d H:i:s T'); ?></div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Server Uptime:</div>
                        <div class="info-value">15 days, 4 hours, 32 minutes</div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="info-card">
                    <h4><i class="bi bi-folder"></i> Physical Path Information</h4>
                    
                    <div class="info-row">
                        <div class="info-label">Rundeck Base:</div>
                        <div class="info-value">/var/lib/rundeck</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Config Directory:</div>
                        <div class="info-value">/etc/rundeck</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Log Directory:</div>
                        <div class="info-value">/var/log/rundeck</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Temp Directory:</div>
                        <div class="info-value">/tmp/rundeck</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Work Directory:</div>
                        <div class="info-value">/var/lib/rundeck/work</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="info-card">
            <h4><i class="bi bi-shield-check"></i> Security Notice</h4>
            <div class="alert alert-warning" style="border-radius: 4px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Default Credentials Detected:</strong> This system is using default credentials (admin/admin). Please change the password immediately to secure your Rundeck instance.
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
</body>
</html>
