<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_trellix_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255),
        `role` VARCHAR(50) DEFAULT 'analyst',
        `last_login` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed default admin account
    $stmt = $pdo->prepare("SELECT id FROM lab_trellix_users WHERE username = 'admin' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_trellix_users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $insert->execute(['admin', 'admin', 'System Administrator', 'Administrator']);
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
    
    $stmt = $pdo->prepare("SELECT * FROM lab_trellix_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['password'] === $password) {
        $_SESSION['trellix_user'] = $user;
        $_SESSION['trellix_user_id'] = $user['id'];
        
        // Update last login
        $update = $pdo->prepare("UPDATE lab_trellix_users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        header('Location: index.php?action=dashboard');
        exit;
    } else {
        $error = "Invalid credentials";
    }
}

// Check authentication
$is_authenticated = isset($_SESSION['trellix_user']);
$current_user = $_SESSION['trellix_user'] ?? null;
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
    <title>Trellix Insights - Security Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        :root {
            --trellix-dark: #0a1628;
            --trellix-darker: #070b14;
            --trellix-nav: #1a2332;
            --trellix-card: #1e2a3a;
            --trellix-border: #2d3e52;
            --trellix-accent: #00b4d8;
            --trellix-accent-hover: #0096b4;
            --trellix-text: #e8eaf0;
            --trellix-text-muted: #8b9cb5;
        }
        
        body {
            background-color: var(--trellix-darker);
            color: var(--trellix-text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--trellix-darker) 0%, var(--trellix-dark) 100%);
        }
        
        .login-card {
            background: var(--trellix-card);
            border: 1px solid var(--trellix-border);
            border-radius: 12px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo i {
            font-size: 3rem;
            color: var(--trellix-accent);
        }
        
        .login-logo h2 {
            color: var(--trellix-text);
            margin-top: 0.5rem;
            font-weight: 600;
        }
        
        .form-control {
            background-color: var(--trellix-darker);
            border: 1px solid var(--trellix-border);
            color: var(--trellix-text);
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus {
            background-color: var(--trellix-darker);
            border-color: var(--trellix-accent);
            color: var(--trellix-text);
            box-shadow: 0 0 0 0.2rem rgba(0, 180, 216, 0.25);
        }
        
        .form-control::placeholder {
            color: var(--trellix-text-muted);
        }
        
        .btn-trellix {
            background-color: var(--trellix-accent);
            border-color: var(--trellix-accent);
            color: #fff;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }
        
        .btn-trellix:hover {
            background-color: var(--trellix-accent-hover);
            border-color: var(--trellix-accent-hover);
            color: #fff;
        }
        
        /* Dashboard Styles */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 260px;
            background-color: var(--trellix-nav);
            border-right: 1px solid var(--trellix-border);
            padding: 1.5rem 0;
            flex-shrink: 0;
        }
        
        .sidebar-brand {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid var(--trellix-border);
            margin-bottom: 1rem;
        }
        
        .sidebar-brand h4 {
            color: var(--trellix-accent);
            margin: 0;
            font-weight: 600;
        }
        
        .sidebar-brand small {
            color: var(--trellix-text-muted);
        }
        
        .nav-section {
            padding: 0.5rem 0;
        }
        
        .nav-section-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--trellix-text-muted);
            font-weight: 600;
        }
        
        .nav-item {
            padding: 0.5rem 1.5rem;
            color: var(--trellix-text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
        }
        
        .nav-item:hover {
            background-color: rgba(0, 180, 216, 0.1);
            color: var(--trellix-accent);
        }
        
        .nav-item.active {
            background-color: rgba(0, 180, 216, 0.15);
            color: var(--trellix-accent);
            border-left: 3px solid var(--trellix-accent);
        }
        
        .nav-item i {
            font-size: 1.1rem;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--trellix-border);
        }
        
        .top-bar h3 {
            margin: 0;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--trellix-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-section {
            background-color: var(--trellix-card);
            border: 1px solid var(--trellix-border);
            border-radius: 8px;
            padding: 1.5rem;
        }
        
        .dashboard-section h4 {
            color: var(--trellix-accent);
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .card-item {
            background-color: var(--trellix-darker);
            border: 1px solid var(--trellix-border);
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-item:last-child {
            margin-bottom: 0;
        }
        
        .card-item-title {
            font-weight: 500;
            color: var(--trellix-text);
        }
        
        .card-item-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm-trellix {
            background-color: var(--trellix-accent);
            border: none;
            color: #fff;
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            border-radius: 4px;
        }
        
        .btn-sm-trellix:hover {
            background-color: var(--trellix-accent-hover);
        }
        
        .btn-sm-secondary {
            background-color: var(--trellix-nav);
            border: 1px solid var(--trellix-border);
            color: var(--trellix-text);
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            border-radius: 4px;
        }
        
        .btn-sm-secondary:hover {
            background-color: var(--trellix-border);
        }
        
        .recent-actions {
            background-color: var(--trellix-card);
            border: 1px solid var(--trellix-border);
            border-radius: 8px;
            padding: 1.5rem;
        }
        
        .recent-actions h4 {
            color: var(--trellix-accent);
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .action-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--trellix-border);
        }
        
        .action-item:last-child {
            border-bottom: none;
        }
        
        .action-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: rgba(0, 180, 216, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--trellix-accent);
            flex-shrink: 0;
        }
        
        .action-content {
            flex: 1;
        }
        
        .action-title {
            font-weight: 500;
            color: var(--trellix-text);
        }
        
        .action-meta {
            font-size: 0.85rem;
            color: var(--trellix-text-muted);
            margin-top: 0.25rem;
        }
        
        .alert-trellix {
            background-color: rgba(244, 67, 54, 0.15);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #ef5350;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .badge-admin {
            background-color: var(--trellix-accent);
            color: #fff;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
        }
        
        .badge-analyst {
            background-color: var(--trellix-nav);
            color: var(--trellix-text-muted);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
<?php if ($action === 'login'): ?>
<div class="login-container">
    <div class="login-card">
        <div class="login-logo">
            <i class="bi bi-shield-lock"></i>
            <h2>Trellix Insights</h2>
            <small class="text-muted">Security Dashboard</small>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label text-muted">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label text-muted">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn btn-trellix w-100">Sign In</button>
        </form>
        
        <div class="text-center mt-4">
            <small class="text-muted">Trellix Security Platform</small>
        </div>
    </div>
</div>

<?php elseif ($action === 'dashboard' || $action === 'admin'): ?>
<div class="dashboard-container">
    <div class="sidebar">
        <div class="sidebar-brand">
            <h4><i class="bi bi-shield-check"></i> Trellix</h4>
            <small>Insights Platform</small>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Administration</div>
            <a href="index.php?action=dashboard" class="nav-item <?php echo $action === 'dashboard' ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <?php if ($is_admin): ?>
            <a href="index.php?action=admin" class="nav-item <?php echo $action === 'admin' ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i> Admin Panel
            </a>
            <?php endif; ?>
            <a href="#" class="nav-item">
                <i class="bi bi-file-text"></i> Log entries
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Intelligence</div>
            <a href="#" class="nav-item">
                <i class="bi bi-people"></i> Actors and TTPs
            </a>
            <a href="#" class="nav-item">
                <i class="bi bi-shield"></i> Countermeasures
            </a>
            <a href="#" class="nav-item">
                <i class="bi bi-folder"></i> Campaign Categories
            </a>
            <a href="#" class="nav-item">
                <i class="bi bi-graph-up"></i> Campaigns
            </a>
            <a href="#" class="nav-item">
                <i class="bi bi-sliders"></i> Configure
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Threatactors</div>
            <a href="#" class="nav-item">
                <i class="bi bi-person-exclamation"></i> Actors
            </a>
            <a href="#" class="nav-item">
                <i class="bi bi-tools"></i> Tool types
            </a>
            <a href="#" class="nav-item">
                <i class="bi bi-box-seam"></i> Tools
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h3><?php echo $action === 'admin' ? 'Administration Panel' : 'Dashboard'; ?></h3>
                <small class="text-muted">Welcome back, <?php echo htmlspecialchars($current_user['full_name'] ?? $current_user['username']); ?></small>
            </div>
            <div class="user-info">
                <span class="badge <?php echo $is_admin ? 'badge-admin' : 'badge-analyst'; ?>">
                    <?php echo htmlspecialchars($current_user['role']); ?>
                </span>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($current_user['username'], 0, 1)); ?>
                </div>
                <a href="index.php?action=logout" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
        
        <?php if ($action === 'dashboard'): ?>
        <div class="dashboard-grid">
            <div class="dashboard-section">
                <h4><i class="bi bi-gear"></i> Administration</h4>
                <div class="card-item">
                    <span class="card-item-title">Log entries</span>
                    <div class="card-item-actions">
                        <button class="btn-sm-trellix">View</button>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-section">
                <h4><i class="bi bi-lightning"></i> Intelligence</h4>
                <div class="card-item">
                    <span class="card-item-title">Actors and TTPs</span>
                    <div class="card-item-actions">
                        <button class="btn-sm-secondary">View</button>
                        <button class="btn-sm-trellix">Add</button>
                    </div>
                </div>
                <div class="card-item">
                    <span class="card-item-title">Approved: Countermeasures</span>
                    <div class="card-item-actions">
                        <button class="btn-sm-secondary">View</button>
                        <button class="btn-sm-trellix">Add</button>
                    </div>
                </div>
                <div class="card-item">
                    <span class="card-item-title">Campaign Categories</span>
                    <div class="card-item-actions">
                        <button class="btn-sm-secondary">View</button>
                        <button class="btn-sm-trellix">Add</button>
                    </div>
                </div>
                <div class="card-item">
                    <span class="card-item-title">Campaigns</span>
                    <div class="card-item-actions">
                        <button class="btn-sm-secondary">View</button>
                        <button class="btn-sm-trellix">Add</button>
                    </div>
                </div>
                <div class="card-item">
                    <span class="card-item-title">Configure: Behaviour Type</span>
                    <div class="card-item-actions">
                        <button class="btn-sm-secondary">Change</button>
                    </div>
                </div>
                <div class="card-item">
                    <span class="card-item-title">Configure: Metadata</span>
                    <div class="card-item-actions">
                        <button class="btn-sm-secondary">Change</button>
                    </div>
                </div>
                <div class="card-item">
                    <span class="card-item-title">Configure: Weapon Category</span>
                    <div class="card-item-actions">
                        <button class="btn-sm-secondary">Change</button>
                    </div>
                </div>
                <div class="card-item">
                    <span class="card-item-title">CounterSteps</span>
                    <div class="card-item-actions">
                        <button class="btn-sm-secondary">View</button>
                        <button class="btn-sm-trellix">Add</button>
                    </div>
                </div>
                <div class="card-item">
                    <span class="card-item-title">Draft: Countermeasures</span>
                    <div class="card-item-actions">
                        <button class="btn-sm-secondary">View</button>
                        <button class="btn-sm-trellix">Add</button>
                    </div>
                </div>
                <div class="card-item">
                    <span class="card-item-title">Sightings</span>
                    <div class="card-item-actions">
                        <button class="btn-sm-secondary">View</button>
                        <button class="btn-sm-trellix">Add</button>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-section">
                <h4><i class="bi bi-person-exclamation"></i> Threatactors</h4>
                <div class="card-item">
                    <span class="card-item-title">Actors</span>
                    <div class="card-item-actions">
                        <button class="btn-sm-trellix">Add</button>
                        <button class="btn-sm-secondary">Change</button>
                    </div>
                </div>
                <div class="card-item">
                    <span class="card-item-title">Tool types</span>
                    <div class="card-item-actions">
                        <button class="btn-sm-trellix">Add</button>
                        <button class="btn-sm-secondary">Change</button>
                    </div>
                </div>
                <div class="card-item">
                    <span class="card-item-title">Tools</span>
                    <div class="card-item-actions">
                        <button class="btn-sm-trellix">Add</button>
                        <button class="btn-sm-secondary">Change</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="recent-actions">
            <h4><i class="bi bi-clock-history"></i> Recent actions</h4>
            
            <div class="action-item">
                <div class="action-icon">
                    <i class="bi bi-toggle-on"></i>
                </div>
                <div class="action-content">
                    <div class="action-title">Changed Active/Passive status</div>
                    <div class="action-meta">
                        <i class="bi bi-clock"></i> 2 hours ago &bull; ID: ACT-2024-001
                    </div>
                </div>
            </div>
            
            <div class="action-item">
                <div class="action-icon">
                    <i class="bi bi-plus-circle"></i>
                </div>
                <div class="action-content">
                    <div class="action-title">Added new counter measure</div>
                    <div class="action-meta">
                        <i class="bi bi-clock"></i> 5 hours ago &bull; ID: CM-2024-089
                    </div>
                </div>
            </div>
            
            <div class="action-item">
                <div class="action-icon">
                    <i class="bi bi-pencil"></i>
                </div>
                <div class="action-content">
                    <div class="action-title">Changed Behavior Type, Values and Attack:Tactic</div>
                    <div class="action-meta">
                        <i class="bi bi-clock"></i> 8 hours ago &bull; ID: CFG-2024-012
                    </div>
                </div>
            </div>
            
            <div class="action-item">
                <div class="action-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div class="action-content">
                    <div class="action-title">Approved counter measure</div>
                    <div class="action-meta">
                        <i class="bi bi-clock"></i> 1 day ago &bull; ID: CM-2024-088
                    </div>
                </div>
            </div>
        </div>
        
        <?php elseif ($action === 'admin'): ?>
        <?php if (!$is_admin): ?>
            <div class="alert-trellix">
                <i class="bi bi-exclamation-triangle"></i> Access Denied: Administrator privileges required
            </div>
        <?php else: ?>
        <div class="dashboard-section mb-4">
            <h4><i class="bi bi-people"></i> User Management</h4>
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM lab_trellix_users ORDER BY id");
                        while ($user = $stmt->fetch()):
                        ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                            <td>
                                <span class="badge <?php echo $user['role'] === 'Administrator' ? 'badge-admin' : 'badge-analyst'; ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></td>
                            <td>
                                <button class="btn-sm-secondary">Edit</button>
                                <?php if ($user['username'] !== 'admin'): ?>
                                <button class="btn-sm-trellix" style="background-color: #ef5350;">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="dashboard-section">
            <h4><i class="bi bi-gear"></i> System Settings</h4>
            <div class="card-item">
                <span class="card-item-title">Default Credentials Warning</span>
                <div class="card-item-actions">
                    <span class="badge bg-danger">ENABLED</span>
                </div>
            </div>
            <div class="card-item">
                <span class="card-item-title">Session Timeout</span>
                <div class="card-item-actions">
                    <span class="badge bg-secondary">30 minutes</span>
                </div>
            </div>
            <div class="card-item">
                <span class="card-item-title">Audit Logging</span>
                <div class="card-item-actions">
                    <span class="badge bg-success">ENABLED</span>
                </div>
            </div>
        </div>
        
        <div class="alert-trellix mt-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Security Notice:</strong> This system is currently using default credentials. Please change the admin password immediately.
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>
</body>
</html>
