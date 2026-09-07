<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table for registered users
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_exchange_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed some existing users for enumeration testing
    $stmt = $pdo->prepare("SELECT id FROM lab_exchange_users WHERE email = 'john@example.com' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_exchange_users (email, password, full_name) VALUES (?, ?, ?)");
        $insert->execute(['john@example.com', 'password123', 'John Doe']);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_exchange_users WHERE email = 'sarah@test.com' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_exchange_users (email, password, full_name) VALUES (?, ?, ?)");
        $insert->execute(['sarah@test.com', 'password123', 'Sarah Smith']);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_exchange_users WHERE email = 'admin@exchange.com' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_exchange_users (email, password, full_name) VALUES (?, ?, ?)");
        $insert->execute(['admin@exchange.com', 'admin123', 'Admin User']);
    }
}

$message = '';
$messageType = '';

// Handle signup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    $email = $_POST['email'] ?? '';
    
    // Check if email already exists (VULNERABLE: different responses)
    $stmt = $pdo->prepare("SELECT id FROM lab_exchange_users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        // Email already registered - VULNERABLE: specific error message
        $message = 'Email is invalid.';
        $messageType = 'error';
    } else {
        // Email not registered - accept it
        $message = 'Success! A verification link has been sent to your email address.';
        $messageType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GO Exchange - Sign Up</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .signup-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            padding: 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
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
            padding: 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-signup {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-signup:hover {
            transform: translateY(-2px);
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
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
        
        .info-box {
            background-color: #f0f4f8;
            border: 1px solid #d1d9e6;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .info-box h4 {
            color: #667eea;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .info-box p {
            color: #4a5568;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .info-box ul {
            color: #4a5568;
            font-size: 13px;
            margin-left: 20px;
            line-height: 1.8;
        }
        
        .info-box code {
            background-color: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            color: #667eea;
        }
        
        .vulnerability-warning {
            background-color: #fff5f5;
            border: 2px solid #fc8181;
            border-left: 4px solid #fc8181;
            padding: 20px;
            margin-top: 20px;
            border-radius: 8px;
        }
        
        .vulnerability-warning h4 {
            color: #c53030;
            font-size: 15px;
            margin-bottom: 10px;
        }
        
        .vulnerability-warning p {
            color: #742a2a;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .test-emails {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .test-emails h4 {
            color: #667eea;
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
    <div class="signup-container">
        <div class="logo">
            <h1>GO Exchange</h1>
            <p>Cryptocurrency Trading Platform</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="signup">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autofocus placeholder="Enter your email">
            </div>
            
            <button type="submit" class="btn-signup">Sign Up</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="#">Log In</a>
        </div>
        
        <div class="test-emails">
            <h4>Test Email Addresses</h4>
            <table>
                <tr>
                    <td>john@example.com</td>
                </tr>
                <tr>
                    <td>sarah@test.com</td>
                </tr>
                <tr>
                    <td>admin@exchange.com</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
