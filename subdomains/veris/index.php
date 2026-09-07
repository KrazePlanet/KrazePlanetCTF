<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table for users
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_veris_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed some existing users for enumeration testing
    $stmt = $pdo->prepare("SELECT id FROM lab_veris_users WHERE username = 'user1' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_veris_users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        $insert->execute(['user1', 'user1@veris.com', 'password123', 'John Doe']);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_veris_users WHERE username = 'user2' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_veris_users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        $insert->execute(['user2', 'user2@veris.com', 'password123', 'Jane Smith']);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_veris_users WHERE username = 'admin' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_veris_users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        $insert->execute(['admin', 'admin@veris.com', 'admin123', 'Admin User']);
    }
}

$action = $_GET['action'] ?? 'login';
$message = '';
$messageType = '';

// Handle logout
if ($action === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM lab_veris_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // User does not exist - VULNERABLE: specific error message
        $message = 'User not exist';
        $messageType = 'error';
    } elseif ($user['password'] !== $password) {
        // User exists but wrong password - VULNERABLE: different error message
        $message = 'Password does not match';
        $messageType = 'error';
    } else {
        // Successful login
        $_SESSION['veris_user'] = $user;
        $_SESSION['veris_user_id'] = $user['id'];
        header('Location: index.php?action=dashboard');
        exit;
    }
}

// Handle forgot password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'forgot' && isset($_POST['action']) && $_POST['action'] === 'forgot_password') {
    $email = $_POST['email'] ?? '';
    
    $stmt = $pdo->prepare("SELECT id FROM lab_veris_users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Email does not exist - VULNERABLE: specific error message
        $message = 'Password can not be reset';
        $messageType = 'error';
    } else {
        // Email exists - VULNERABLE: different error message
        $message = 'Please check your email';
        $messageType = 'success';
    }
}

// Check authentication for dashboard
$is_authenticated = isset($_SESSION['veris_user']);
$current_user = $_SESSION['veris_user'] ?? null;

// Redirect to login if not authenticated
if (!$is_authenticated && $action === 'dashboard') {
    header('Location: index.php?action=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veris - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            font-size: 14px;
        }
        
        .navbar {
            background-color: #1a1a2e;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar .logo {
            font-size: 20px;
            font-weight: bold;
        }
        
        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background-color: #e74c3c;
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
            background-color: #c0392b;
        }
        
        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #1a1a2e;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #333;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 600;
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
            border-color: #1a1a2e;
        }
        
        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #1a1a2e;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .btn-submit:hover {
            background-color: #16213e;
        }
        
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .message.error {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }
        
        .message.success {
            background-color: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .back-link a {
            color: #1a1a2e;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .dashboard-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            color: #1a1a2e;
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            width: 200px;
            font-weight: 600;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .test-users {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .test-users h4 {
            color: #1a1a2e;
            font-size: 14px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .test-users table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .test-users td {
            padding: 10px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .test-users tr:last-child td {
            border-bottom: none;
        }
    </style>
</head>
<body>
<?php if ($action === 'login'): ?>
<div class="container">
    <div class="auth-card">
        <div class="logo">
            <h1>Veris</h1>
            <p>Secure Authentication Platform</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-submit">Login</button>
        </form>
        
        <div class="back-link">
            <a href="index.php?action=forgot">Forgot Password?</a>
        </div>
        
        <div class="test-users">
            <h4>Test Accounts</h4>
            <table>
                <tr>
                    <td>user1 / password123</td>
                </tr>
                <tr>
                    <td>user2 / password123</td>
                </tr>
                <tr>
                    <td>admin / admin123</td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php elseif ($action === 'forgot'): ?>
<div class="container">
    <div class="auth-card">
        <div class="logo">
            <h1>Veris</h1>
            <p>Forgot Password</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="forgot_password">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            
            <button type="submit" class="btn-submit">Reset Password</button>
        </form>
        
        <div class="back-link">
            <a href="index.php?action=login">Back to Login</a>
        </div>
        
        <div class="test-users">
            <h4>Test Email Addresses</h4>
            <table>
                <tr>
                    <td>user1@veris.com</td>
                </tr>
                <tr>
                    <td>user2@veris.com</td>
                </tr>
                <tr>
                    <td>admin@veris.com</td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php elseif ($action === 'dashboard'): ?>
<div class="navbar">
    <div class="logo">Veris</div>
    <div class="user-info">
        <span><?php echo htmlspecialchars($current_user['username']); ?></span>
        <a href="index.php?action=logout" class="logout-btn">Logout</a>
    </div>
</div>

<div class="dashboard-container">
    <div class="card">
        <h2>Dashboard</h2>
        
        <div class="info-row">
            <div class="info-label">Username:</div>
            <div class="info-value"><?php echo htmlspecialchars($current_user['username']); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div class="info-value"><?php echo htmlspecialchars($current_user['email']); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Full Name:</div>
            <div class="info-value"><?php echo htmlspecialchars($current_user['full_name']); ?></div>
        </div>
    </div>
</div>

<?php endif; ?>
</body>
</html>
