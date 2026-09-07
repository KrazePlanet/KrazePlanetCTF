<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table for users
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_weblate_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed some existing users for enumeration testing
    $stmt = $pdo->prepare("SELECT id FROM lab_weblate_users WHERE username = 'translator' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_weblate_users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        $insert->execute(['translator', 'translator@weblate.org', 'password123', 'Maria Garcia']);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_weblate_users WHERE username = 'developer' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_weblate_users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        $insert->execute(['developer', 'developer@weblate.org', 'password123', 'John Smith']);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_weblate_users WHERE username = 'admin' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_weblate_users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        $insert->execute(['admin', 'admin@weblate.org', 'admin123', 'Admin User']);
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM lab_weblate_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['password'] === $password) {
        $_SESSION['weblate_user'] = $user;
        $_SESSION['weblate_user_id'] = $user['id'];
        header('Location: index.php?action=account');
        exit;
    } else {
        $message = 'Invalid username or password';
        $messageType = 'error';
    }
}

// Check authentication
$is_authenticated = isset($_SESSION['weblate_user']);
$current_user = $_SESSION['weblate_user'] ?? null;

// Redirect to login if not authenticated
if (!$is_authenticated && $action !== 'login') {
    header('Location: index.php?action=login');
    exit;
}

// Handle add email form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'account' && isset($_POST['action']) && $_POST['action'] === 'add_email') {
    $email = $_POST['email'] ?? '';
    
    // Check if email already exists in any user account (VULNERABLE: different responses)
    $stmt = $pdo->prepare("SELECT id FROM lab_weblate_users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        // Email already exists - VULNERABLE: specific error message
        $message = 'This email address is already in use.';
        $messageType = 'error';
    } else {
        // Email does not exist - success
        $message = 'Email address added successfully.';
        $messageType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weblate - Account Settings</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            font-size: 14px;
        }
        
        .navbar {
            background-color: #2c3e50;
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
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
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
            border-color: #3498db;
        }
        
        .btn-submit {
            padding: 12px 30px;
            background-color: #3498db;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .btn-submit:hover {
            background-color: #2980b9;
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
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
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
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .login-header p {
            color: #666;
            font-size: 12px;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .btn-login:hover {
            background-color: #2980b9;
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
        
        .test-emails {
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .test-emails h4 {
            color: #3498db;
            font-size: 14px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .test-emails table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .test-emails td {
            padding: 10px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .test-emails tr:last-child td {
            border-bottom: none;
        }
    </style>
</head>
<body>
<?php if ($action === 'login'): ?>
<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <h1>Weblate</h1>
            <p>Web-based Translation Platform</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
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
        
        <div class="test-emails">
            <h4>Test Accounts</h4>
            <table>
                <tr>
                    <td>translator / password123</td>
                </tr>
                <tr>
                    <td>developer / password123</td>
                </tr>
                <tr>
                    <td>admin / admin123</td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php elseif ($action === 'account'): ?>
<div class="navbar">
    <div class="logo">Weblate</div>
    <div class="user-info">
        <span><?php echo htmlspecialchars($current_user['username']); ?></span>
        <a href="index.php?action=logout" class="logout-btn">Logout</a>
    </div>
</div>

<div class="container">
    <div class="card">
        <h2>Account Settings</h2>
        
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
    
    <div class="card">
        <h2>Add Email Address</h2>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="add_email">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autofocus placeholder="Enter email to add">
            </div>
            
            <button type="submit" class="btn-submit">Add Email</button>
        </form>
        
        <div class="test-emails">
            <h4>Test Email Addresses</h4>
            <table>
                <tr>
                    <td>translator@weblate.org</td>
                </tr>
                <tr>
                    <td>developer@weblate.org</td>
                </tr>
                <tr>
                    <td>admin@weblate.org</td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>
</body>
</html>
