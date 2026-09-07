<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_rabbitmq_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `role` VARCHAR(50) DEFAULT 'administrator',
        `last_login` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed default guest account
    $stmt = $pdo->prepare("SELECT id FROM lab_rabbitmq_users WHERE username = 'guest' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_rabbitmq_users (username, password, role) VALUES (?, ?, ?)");
        $insert->execute(['guest', 'guest', 'administrator']);
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
    
    $stmt = $pdo->prepare("SELECT * FROM lab_rabbitmq_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['password'] === $password) {
        $_SESSION['rabbitmq_user'] = $user;
        $_SESSION['rabbitmq_user_id'] = $user['id'];
        
        // Update last login
        $update = $pdo->prepare("UPDATE lab_rabbitmq_users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        header('Location: index.php?action=dashboard');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}

// Check authentication
$is_authenticated = isset($_SESSION['rabbitmq_user']);
$current_user = $_SESSION['rabbitmq_user'] ?? null;

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
    <title>RabbitMQ Management Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <style>
        :root {
            --rabbitmq-orange: #ff6600;
            --rabbitmq-orange-dark: #e65c00;
            --rabbitmq-gray: #f5f5f5;
            --rabbitmq-dark: #333;
            --rabbitmq-border: #ddd;
        }
        
        body {
            background-color: var(--rabbitmq-gray);
            color: var(--rabbitmq-dark);
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 14px;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ff6600 0%, #ff8c00 50%, #ffa500 100%);
        }
        
        .login-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--rabbitmq-orange) 0%, var(--rabbitmq-orange-dark) 100%);
            padding: 2rem;
            text-align: center;
        }
        
        .login-header h1 {
            color: white;
            font-size: 1.4rem;
            margin: 0 0 0.5rem 0;
            font-weight: 600;
        }
        
        .login-header small {
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.8rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--rabbitmq-dark);
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }
        
        .form-control {
            border: 1px solid var(--rabbitmq-border);
            border-radius: 4px;
            padding: 0.6rem;
            font-size: 0.9rem;
        }
        
        .form-control:focus {
            border-color: var(--rabbitmq-orange);
            box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.1);
        }
        
        .btn-rabbitmq {
            background-color: var(--rabbitmq-orange);
            border-color: var(--rabbitmq-orange);
            color: white;
            padding: 0.6rem 1.25rem;
            font-weight: 600;
            width: 100%;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .btn-rabbitmq:hover {
            background-color: var(--rabbitmq-orange-dark);
            border-color: var(--rabbitmq-orange-dark);
            color: white;
        }
        
        .login-footer {
            padding: 1rem 2rem;
            background-color: #f8f9fa;
            border-top: 1px solid var(--rabbitmq-border);
            text-align: center;
        }
        
        .login-footer small {
            color: #666;
            font-size: 0.75rem;
        }
        
        /* Dashboard Styles */
        .dashboard-container {
            min-height: 100vh;
            background-color: var(--rabbitmq-gray);
        }
        
        .navbar-rabbitmq {
            background-color: var(--rabbitmq-orange);
            border-bottom: 2px solid var(--rabbitmq-orange-dark);
            padding: 0.5rem 1rem;
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .navbar-brand i {
            margin-right: 0.5rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        
        .nav-link:hover {
            color: white !important;
        }
        
        .nav-link.active {
            color: white !important;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
        
        .main-content {
            padding: 1.5rem;
        }
        
        .dashboard-header {
            margin-bottom: 1.5rem;
        }
        
        .dashboard-header h2 {
            color: var(--rabbitmq-dark);
            font-weight: 600;
            font-size: 1.4rem;
        }
        
        .info-card {
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }
        
        .info-card h4 {
            color: var(--rabbitmq-dark);
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--rabbitmq-orange);
            font-size: 1rem;
        }
        
        .info-row {
            display: flex;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            width: 180px;
            flex-shrink: 0;
            font-size: 0.85rem;
        }
        
        .info-value {
            color: var(--rabbitmq-dark);
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }
        
        .table-rabbitmq {
            font-size: 0.85rem;
        }
        
        .table-rabbitmq th {
            background-color: var(--rabbitmq-orange);
            color: white;
            font-weight: 600;
            border: none;
            font-size: 0.85rem;
        }
        
        .badge-status {
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .status-running {
            background-color: #28a745;
            color: white;
        }
        
        .status-idle {
            background-color: #ffc107;
            color: #333;
        }
        
        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--rabbitmq-orange);
            font-weight: 600;
        }
        
        .system-alert {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
            padding: 0.75rem;
            margin-bottom: 1.25rem;
        }
        
        .system-alert h5 {
            color: #856404;
            margin: 0 0 0.5rem 0;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .system-alert p {
            margin: 0;
            font-size: 0.85rem;
        }
        
        .queue-message {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.5rem;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            margin-bottom: 0.5rem;
        }
        
        .queue-message pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-all;
        }
    </style>
</head>
<body>
<?php if ($action === 'login'): ?>
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h1><i class="bi bi-box-seam"></i> RabbitMQ</h1>
            <small>Management Console</small>
        </div>
        
        <div class="login-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" style="border-radius: 4px; font-size: 0.85rem;">
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
                <button type="submit" class="btn btn-rabbitmq">Login</button>
            </form>
        </div>
        
        <div class="login-footer">
            <small>RabbitMQ 3.8.2 &copy; 2019 Pivotal Software, Inc.</small>
        </div>
    </div>
</div>

<?php elseif ($action === 'dashboard'): ?>
<div class="dashboard-container">
    <nav class="navbar navbar-expand-lg navbar-rabbitmq">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-box-seam"></i> RabbitMQ Management
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Overview</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Connections</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Channels</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Exchanges</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Queues</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Admin</a>
                    </li>
                </ul>
                
                <div class="user-dropdown">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($current_user['username'], 0, 1)); ?>
                    </div>
                    <span class="text-white" style="font-size: 0.85rem;"><?php echo htmlspecialchars($current_user['username']); ?></span>
                    <a href="index.php?action=logout" class="btn btn-sm btn-outline-light" style="font-size: 0.75rem;">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="main-content">
        <div class="dashboard-header">
            <h2><i class="bi bi-speedometer2"></i> RabbitMQ Overview</h2>
            <p class="text-muted" style="font-size: 0.85rem;">staging.dev.unikrn.space - Management Console</p>
        </div>
        
        <div class="system-alert">
            <h5><i class="bi bi-exclamation-triangle"></i> Security Warning</h5>
            <p>This RabbitMQ instance is using default credentials (guest/guest) with administrative access. This is a critical security vulnerability.</p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="info-card">
                    <h4><i class="bi bi-info-circle"></i> RabbitMQ Information</h4>
                    
                    <div class="info-row">
                        <div class="info-label">RabbitMQ Version:</div>
                        <div class="info-value">3.8.2</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Erlang Version:</div>
                        <div class="info-value">22.1.4</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Node:</div>
                        <div class="info-value">rabbit@staging-rmq-01</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Erlang Cookie:</div>
                        <div class="info-value">UOBSKJGQZLXQYGDYJZKX</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Cluster Name:</div>
                        <div class="info-value">staging-rabbitmq@dev.unikrn.space</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Uptime:</div>
                        <div class="info-value">15 days, 4 hours, 32 minutes</div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="info-card">
                    <h4><i class="bi bi-hdd"></i> Memory & Disk</h4>
                    
                    <div class="info-row">
                        <div class="info-label">Memory Used:</div>
                        <div class="info-value">512 MB / 2048 MB</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Disk Free:</div>
                        <div class="info-value">45.2 GB / 50 GB</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">File Descriptors:</div>
                        <div class="info-value">128 / 1024</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Sockets:</div>
                        <div class="info-value">45 / 829</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Erlang Processes:</div>
                        <div class="info-value">234</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Run Queue:</div>
                        <div class="info-value">0</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="info-card">
            <h4><i class="bi bi-list-ul"></i> Queues</h4>
            <table class="table table-rabbitmq">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>State</th>
                        <th>Ready</th>
                        <th>Unacked</th>
                        <th>Total</th>
                        <th>Messages/sec</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>unikrn_sso_auth_queue</td>
                        <td><span class="badge-status status-running">Running</span></td>
                        <td>1,245</td>
                        <td>0</td>
                        <td>1,245</td>
                        <td>12.5</td>
                    </tr>
                    <tr>
                        <td>unikrn_api_requests</td>
                        <td><span class="badge-status status-running">Running</span></td>
                        <td>3,567</td>
                        <td>23</td>
                        <td>3,590</td>
                        <td>45.2</td>
                    </tr>
                    <tr>
                        <td>unikrn_payment_events</td>
                        <td><span class="badge-status status-running">Running</span></td>
                        <td>892</td>
                        <td>5</td>
                        <td>897</td>
                        <td>8.7</td>
                    </tr>
                    <tr>
                        <td>unikrn_user_notifications</td>
                        <td><span class="badge-status status-idle">Idle</span></td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0.0</td>
                    </tr>
                    <tr>
                        <td>unikrn_betting_odds</td>
                        <td><span class="badge-status status-running">Running</span></td>
                        <td>12,345</td>
                        <td>156</td>
                        <td>12,501</td>
                        <td>234.1</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="info-card">
            <h4><i class="bi bi-file-text"></i> Queue Message Samples (SSO Authentication Queue)</h4>
            <div class="queue-message">
                <pre>{"event": "user_login", "user_id": "user_12345", "timestamp": "2019-12-07T14:32:15Z", "sso_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...", "ip_address": "192.168.1.100"}</pre>
            </div>
            <div class="queue-message">
                <pre>{"event": "token_refresh", "user_id": "user_67890", "timestamp": "2019-12-07T14:33:22Z", "new_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...", "expires_in": 3600}</pre>
            </div>
            <div class="queue-message">
                <pre>{"event": "password_reset", "user_id": "user_11111", "timestamp": "2019-12-07T14:34:45Z", "reset_token": "a1b2c3d4e5f6...", "email": "user@unikrn.com"}</pre>
            </div>
        </div>
        
        <div class="info-card">
            <h4><i class="bi bi-file-text"></i> Queue Message Samples (API Requests Queue)</h4>
            <div class="queue-message">
                <pre>{"request": "get_user_profile", "user_id": "user_22222", "api_key": "uk_live_abc123xyz...", "timestamp": "2019-12-07T14:35:10Z"}</pre>
            </div>
            <div class="queue-message">
                <pre>{"request": "place_bet", "user_id": "user_33333", "amount": 150.00, "odds_id": "odds_45678", "api_key": "uk_live_def456uvw...", "timestamp": "2019-12-07T14:36:25Z"}</pre>
            </div>
            <div class="queue-message">
                <pre>{"request": "withdraw_funds", "user_id": "user_44444", "amount": 500.00, "destination": "bank_account_123", "api_key": "uk_live_ghi789rst...", "timestamp": "2019-12-07T14:37:40Z"}</pre>
            </div>
        </div>
        
        <div class="info-card">
            <h4><i class="bi bi-shield-check"></i> Administrative Capabilities</h4>
            <p style="font-size: 0.85rem; color: #666;">As an administrator, you have full control over this RabbitMQ instance:</p>
            <ul style="font-size: 0.85rem; color: #666;">
                <li>Create, modify, and delete queues</li>
                <li>Create, modify, and delete exchanges</li>
                <li>Manage bindings between queues and exchanges</li>
                <li>View and purge messages from all queues</li>
                <li>Manage user permissions and policies</li>
                <li>Configure cluster-wide parameters</li>
            </ul>
        </div>
    </div>
</div>

<?php endif; ?>
</body>
</html>
