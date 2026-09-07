<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table for users
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_onboarding_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `phone` VARCHAR(20) NOT NULL UNIQUE,
        `discoverable` TINYINT DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);

    // Seed some existing users for enumeration testing
    $stmt = $pdo->prepare("SELECT id FROM lab_onboarding_users WHERE username = 'crypto_enthusiast' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_onboarding_users (username, email, phone, discoverable) VALUES (?, ?, ?, ?)");
        $insert->execute(['crypto_enthusiast', 'crypto@example.com', '+14155551234', 0]);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_onboarding_users WHERE username = 'bitcoin_trader' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_onboarding_users (username, email, phone, discoverable) VALUES (?, ?, ?, ?)");
        $insert->execute(['bitcoin_trader', 'trader@example.com', '+14155555678', 0]);
    }

    $stmt = $pdo->prepare("SELECT id FROM lab_onboarding_users WHERE username = 'blockchain_dev' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_onboarding_users (username, email, phone, discoverable) VALUES (?, ?, ?, ?)");
        $insert->execute(['blockchain_dev', 'dev@example.com', '+14155559012', 1]);
    }
}

$action = $_GET['action'] ?? 'login';
$response = [];

// Handle login flow initiation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login_flow') {
    $flowToken = bin2hex(random_bytes(16));
    $_SESSION['flow_token'] = $flowToken;
    
    $response = [
        'flow_token' => $flowToken,
        'status' => 'success',
        'subtasks' => [
            [
                'subtask_id' => 'LoginEnterUserIdentifier',
                'enter_text' => [
                    'primary_text' => [
                        'text' => 'To get started, first enter your phone, email, or @username'
                    ],
                    'hint_text' => 'Phone, email, or username'
                ]
            ]
        ]
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle user identifier submission (VULNERABLE: bypasses discoverability settings)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'check_identifier') {
    $input = json_decode(file_get_contents('php://input'), true);
    $flowToken = $input['flow_token'] ?? '';
    $identifier = $input['identifier'] ?? '';
    
    if ($flowToken !== $_SESSION['flow_token']) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid flow token']);
        exit;
    }
    
    // Check if identifier exists (email or phone) - VULNERABLE: ignores discoverable setting
    $stmt = $pdo->prepare("SELECT id, username FROM lab_onboarding_users WHERE email = ? OR phone = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();
    
    if ($user) {
        // VULNERABLE: Returns user_id even if discoverable = 0 (disabled)
        $response = [
            'flow_token' => $flowToken,
            'status' => 'success',
            'subtasks' => [
                [
                    'subtask_id' => 'AccountDuplicationCheck',
                    'check_logged_in_account' => [
                        'user_id' => $user['id'],
                        'username' => $user['username']
                    ]
                ]
            ]
        ];
    } else {
        $response = [
            'flow_token' => $flowToken,
            'status' => 'success',
            'subtasks' => [
                [
                    'subtask_id' => 'AccountDuplicationCheck',
                    'check_logged_in_account' => [
                        'user_id' => null
                    ]
                ]
            ]
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X - Login Flow</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #000;
            color: #fff;
            font-size: 14px;
        }
        
        .container {
            max-width: 600px;
            margin: 100px auto;
            padding: 0 20px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo svg {
            width: 50px;
            height: 50px;
            fill: #fff;
        }
        
        .card {
            background: #16181c;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .card p {
            color: #71767b;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #71767b;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px;
            background: #202327;
            border: 1px solid #38444c;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #1d9bf0;
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: #1d9bf0;
            border: none;
            border-radius: 9999px;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .btn-submit:hover {
            background-color: #1a8cd8;
        }
        
        .btn-test {
            width: 100%;
            padding: 14px;
            background-color: #7856ff;
            border: none;
            border-radius: 9999px;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .btn-test:hover {
            background-color: #6244e0;
        }
        
        .results {
            background: #202327;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .test-info {
            margin-top: 30px;
            padding: 20px;
            background-color: #1d9bf0;
            border-radius: 8px;
        }
        
        .test-info h4 {
            margin-bottom: 10px;
        }
        
        .test-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .test-info td {
            padding: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #fff;
        }
        
        .test-info tr:last-child td {
            border-bottom: none;
        }
        
        .note {
            color: #f91880;
            font-size: 12px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path></svg>
        </div>
        
        <div class="card">
            <h2>Login Flow Test</h2>
            <p>Test the login flow API to discover user accounts by phone/email (bypasses privacy settings)</p>
            
            <div class="form-group">
                <label for="identifier">Phone or Email</label>
                <input type="text" id="identifier" placeholder="+14155551234 or crypto@example.com">
            </div>
            
            <button type="button" class="btn-submit" onclick="testFlow()">Test Login Flow</button>
            <button type="button" class="btn-test" onclick="testAll()">Test All Accounts</button>
            
            <div id="results" class="results" style="display: none;"></div>
        </div>
        
        <div class="test-info">
            <h4>Test Accounts (Discoverability Disabled)</h4>
            <table>
                <tr>
                    <td>crypto_enthusiast</td>
                    <td>crypto@example.com</td>
                    <td>+14155551234</td>
                </tr>
                <tr>
                    <td>bitcoin_trader</td>
                    <td>trader@example.com</td>
                    <td>+14155555678</td>
                </tr>
            </table>
            <p class="note">Note: These users have discoverability disabled in privacy settings, but the API still returns their user_id</p>
        </div>
    </div>
    
    <script>
        let flowToken = '';
        
        async function initFlow() {
            const response = await fetch('index.php?action=login_flow', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            const data = await response.json();
            flowToken = data.flow_token;
            return data;
        }
        
        async function checkIdentifier(identifier) {
            const response = await fetch('index.php?action=check_identifier', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    flow_token: flowToken,
                    identifier: identifier
                })
            });
            return await response.json();
        }
        
        async function testFlow() {
            const identifier = document.getElementById('identifier').value;
            if (!identifier) {
                alert('Please enter a phone or email');
                return;
            }
            
            const resultsDiv = document.getElementById('results');
            resultsDiv.style.display = 'block';
            resultsDiv.textContent = 'Initializing flow...\n';
            
            try {
                const flowData = await initFlow();
                resultsDiv.textContent += JSON.stringify(flowData, null, 2) + '\n\n';
                
                resultsDiv.textContent += 'Checking identifier: ' + identifier + '\n';
                const checkData = await checkIdentifier(identifier);
                resultsDiv.textContent += JSON.stringify(checkData, null, 2);
            } catch (error) {
                resultsDiv.textContent += 'Error: ' + error.message;
            }
        }
        
        async function testAll() {
            const identifiers = [
                '+14155551234',
                'crypto@example.com',
                '+14155555678',
                'trader@example.com',
                '+14155559012',
                'dev@example.com',
                'nonexistent@example.com'
            ];
            
            const resultsDiv = document.getElementById('results');
            resultsDiv.style.display = 'block';
            resultsDiv.textContent = 'Testing all accounts...\n\n';
            
            try {
                const flowData = await initFlow();
                resultsDiv.textContent += 'Flow initialized\n\n';
                
                for (const identifier of identifiers) {
                    resultsDiv.textContent += 'Checking: ' + identifier + '\n';
                    const checkData = await checkIdentifier(identifier);
                    const userId = checkData.subtasks[0].check_logged_in_account.user_id;
                    const username = checkData.subtasks[0].check_logged_in_account.username;
                    resultsDiv.textContent += 'Result: ' + (userId ? 'FOUND - User ID: ' + userId + ', Username: ' + username : 'NOT FOUND') + '\n\n';
                }
            } catch (error) {
                resultsDiv.textContent += 'Error: ' + error.message;
            }
        }
    </script>
</body>
</html>
