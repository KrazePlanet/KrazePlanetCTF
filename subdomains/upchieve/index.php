<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table for registered users
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_upchieve_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed some existing users for enumeration testing
    $stmt = $pdo->prepare("SELECT id FROM lab_upchieve_users WHERE email = 'student@upchieve.org' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_upchieve_users (email, password, full_name) VALUES (?, ?, ?)");
        $insert->execute(['student@upchieve.org', 'password123', 'Emily Johnson']);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_upchieve_users WHERE email = 'tutor@upchieve.org' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_upchieve_users (email, password, full_name) VALUES (?, ?, ?)");
        $insert->execute(['tutor@upchieve.org', 'password123', 'Michael Chen']);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_upchieve_users WHERE email = 'admin@upchieve.org' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_upchieve_users (email, password, full_name) VALUES (?, ?, ?)");
        $insert->execute(['admin@upchieve.org', 'admin123', 'Sarah Williams']);
    }
}

$message = '';
$messageType = '';

// Handle forgot password form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'forgot_password') {
    $email = $_POST['email'] ?? '';
    
    // Check if email already exists (VULNERABLE: different responses)
    $stmt = $pdo->prepare("SELECT id FROM lab_upchieve_users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        // Email exists - VULNERABLE: success message
        $message = 'If that email address exists in our system, we will send a password reset link to your inbox.';
        $messageType = 'success';
    } else {
        // Email does not exist - VULNERABLE: specific error message
        $message = 'No account with that id found.';
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPchieve - Forgot Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
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
        
        .forgot-password-card {
            background: white;
            border-radius: 12px;
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
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #0066cc;
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: #0066cc;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-submit:hover {
            background-color: #0052a3;
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
        
        .help-text {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 13px;
        }
        
        .help-text p {
            margin-bottom: 10px;
        }
        
        .help-text a {
            color: #0066cc;
            text-decoration: none;
        }
        
        .help-text a:hover {
            text-decoration: underline;
        }
        
        .test-emails {
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .test-emails h4 {
            color: #0066cc;
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
    <div class="container">
        <div class="forgot-password-card">
            <div class="logo">
                <h1>UPchieve</h1>
                <p>Free Online Tutoring</p>
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
                    <input type="email" id="email" name="email" required autofocus placeholder="Enter your email">
                </div>
                
                <button type="submit" class="btn-submit">Send Reset Link</button>
            </form>
            
            <div class="back-link">
                <a href="#">Back to Login</a>
            </div>
            
            <div class="help-text">
                <p>Need help? <a href="#">Contact Support</a></p>
                <p>Don't have an account? <a href="#">Sign Up</a></p>
            </div>
            
            <div class="test-emails">
                <h4>Test Email Addresses</h4>
                <table>
                    <tr>
                        <td>student@upchieve.org</td>
                    </tr>
                    <tr>
                        <td>tutor@upchieve.org</td>
                    </tr>
                    <tr>
                        <td>admin@upchieve.org</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
