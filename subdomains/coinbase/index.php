<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table for registered users
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_coinbase_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed some existing users for enumeration testing
    $stmt = $pdo->prepare("SELECT id FROM lab_coinbase_users WHERE email = 'bitcoiner@gmail.com' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_coinbase_users (email, password, full_name) VALUES (?, ?, ?)");
        $insert->execute(['bitcoiner@gmail.com', 'password123', 'Satoshi Nakamoto']);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_coinbase_users WHERE email = 'trader@gmail.com' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_coinbase_users (email, password, full_name) VALUES (?, ?, ?)");
        $insert->execute(['trader@gmail.com', 'password123', 'John Trader']);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_coinbase_users WHERE email = 'investor@gmail.com' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_coinbase_users (email, password, full_name) VALUES (?, ?, ?)");
        $insert->execute(['investor@gmail.com', 'password123', 'Sarah Investor']);
    }
}

$message = '';
$messageType = '';
$invitedEmails = [];

// Handle invite form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'invite') {
    $emails = $_POST['emails'] ?? '';
    $emailArray = array_filter(array_map('trim', explode("\n", $emails)));
    
    foreach ($emailArray as $email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }
        
        // Check if email already exists (VULNERABLE: different responses)
        $stmt = $pdo->prepare("SELECT id FROM lab_coinbase_users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            // Email exists - VULNERABLE: show that user is already registered
            $invitedEmails[] = ['email' => $email, 'status' => 'registered'];
        } else {
            // Email does not exist - show invite sent
            $invitedEmails[] = ['email' => $email, 'status' => 'invited'];
        }
    }
    
    $message = 'Processed ' . count($invitedEmails) . ' email addresses.';
    $messageType = 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coinbase - Invite Friends</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            font-size: 14px;
        }
        
        .navbar {
            background-color: #0066cc;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar .logo {
            font-size: 24px;
            font-weight: bold;
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
            color: #0066cc;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .card p {
            color: #666;
            margin-bottom: 20px;
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
        
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            min-height: 150px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: #0066cc;
        }
        
        .btn-submit {
            padding: 12px 30px;
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
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .results-table th {
            background-color: #f5f7fa;
            color: #333;
            font-weight: 600;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #0066cc;
        }
        
        .results-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .status-registered {
            color: #f39c12;
            font-weight: 600;
        }
        
        .status-invited {
            color: #27ae60;
            font-weight: 600;
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
    <div class="navbar">
        <div class="logo">Coinbase</div>
    </div>

    <div class="container">
        <div class="card">
            <h2>Invite Friends</h2>
            <p>Import your contacts and invite friends to join Coinbase. We'll check which of your contacts already have Coinbase accounts.</p>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="invite">
                
                <div class="form-group">
                    <label for="emails">Email Addresses (one per line)</label>
                    <textarea id="emails" name="emails" required placeholder="Enter email addresses, one per line&#10;friend1@gmail.com&#10;friend2@gmail.com"></textarea>
                </div>
                
                <button type="submit" class="btn-submit">Import Contacts & Invite</button>
            </form>
            
            <?php if (!empty($invitedEmails)): ?>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Email Address</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invitedEmails as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['email']); ?></td>
                                <td>
                                    <?php if ($item['status'] === 'registered'): ?>
                                        <span class="status-registered">Already a Coinbase user</span>
                                    <?php else: ?>
                                        <span class="status-invited">Invite sent</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div class="test-emails">
                <h4>Test Email Addresses (Already Registered)</h4>
                <table>
                    <tr>
                        <td>bitcoiner@gmail.com</td>
                    </tr>
                    <tr>
                        <td>trader@gmail.com</td>
                    </tr>
                    <tr>
                        <td>investor@gmail.com</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
