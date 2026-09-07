<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table for users
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_endless_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `reset_code` VARCHAR(255),
        `reset_expiry` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed some existing users for enumeration testing
    $stmt = $pdo->prepare("SELECT id FROM lab_endless_users WHERE username = 'codermak' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_endless_users (username, email, password) VALUES (?, ?, ?)");
        $insert->execute(['codermak', 'codermak@endless.com', 'password123']);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_endless_users WHERE username = 'admin' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_endless_users (username, email, password) VALUES (?, ?, ?)");
        $insert->execute(['admin', 'admin@endless.com', 'admin123']);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_endless_users WHERE username = 'developer' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_endless_users (username, email, password) VALUES (?, ?, ?)");
        $insert->execute(['developer', 'developer@endless.com', 'password123']);
    }
}

$action = $_GET['action'] ?? 'reset';
$message = '';
$messageType = '';

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reset' && isset($_POST['action']) && $_POST['action'] === 'code') {
    $username = $_POST['username'] ?? '';
    $code = $_POST['code'] ?? '';
    $json = $_POST['json'] ?? '';
    
    // Check if username exists (VULNERABLE: different responses)
    $stmt = $pdo->prepare("SELECT id FROM lab_endless_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($json === 'yes') {
        header('Content-Type: application/json');
        if ($user) {
            // Username exists - VULNERABLE: specific error message
            echo json_encode(['error' => 'No such username in the request list. Your request may have expired.']);
        } else {
            // Username does not exist - VULNERABLE: different error message
            echo json_encode(['error' => 'No User']);
        }
        exit;
    } else {
        if ($user) {
            $message = 'No such username in the request list. Your request may have expired.';
            $messageType = 'error';
        } else {
            $message = 'No User';
            $messageType = 'error';
        }
    }
}

// Handle initial password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reset' && isset($_POST['action']) && $_POST['action'] === 'request') {
    $username = $_POST['username'] ?? '';
    
    $stmt = $pdo->prepare("SELECT id FROM lab_endless_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate and store reset code
        $resetCode = substr(md5(uniqid(rand(), true)), 0, 8);
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $update = $pdo->prepare("UPDATE lab_endless_users SET reset_code = ?, reset_expiry = ? WHERE id = ?");
        $update->execute([$resetCode, $expiry, $user['id']]);
        
        $message = "Password reset code sent. Your code: $resetCode";
        $messageType = 'success';
    } else {
        $message = 'If a user with that username exists, a reset code will be sent.';
        $messageType = 'info';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DirectAdmin - Password Reset</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f0f0;
            color: #333;
            font-size: 14px;
        }
        
        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .reset-card {
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
            color: #0066cc;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 12px;
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
            border-color: #0066cc;
        }
        
        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #0066cc;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .btn-submit:hover {
            background-color: #0052a3;
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
        
        .message.info {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            color: #1976d2;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .back-link a {
            color: #0066cc;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .test-users {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .test-users h4 {
            color: #0066cc;
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
    <div class="container">
        <div class="reset-card">
            <div class="logo">
                <h1>DirectAdmin</h1>
                <p>Control Panel</p>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="request">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus placeholder="Enter your username">
                </div>
                
                <button type="submit" class="btn-submit">Request Password Reset</button>
            </form>
            
            <div class="back-link">
                <a href="#">Back to Login</a>
            </div>
            
            <div class="test-users">
                <h4>Test Usernames</h4>
                <table>
                    <tr>
                        <td>codermak</td>
                    </tr>
                    <tr>
                        <td>admin</td>
                    </tr>
                    <tr>
                        <td>developer</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
