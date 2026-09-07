<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific tables
if ($pdo) {
    // Users table (hackers)
    $users_sql = "CREATE TABLE IF NOT EXISTS `lab_operation_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL UNIQUE,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($users_sql);

    // Reports table
    $reports_sql = "CREATE TABLE IF NOT EXISTS `lab_operation_reports` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `created_by` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($reports_sql);

    // Collaborators table
    $collaborators_sql = "CREATE TABLE IF NOT EXISTS `lab_operation_collaborators` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `report_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `status` ENUM('pending', 'accepted') DEFAULT 'pending',
        `bounty_weight` DECIMAL(3,2) DEFAULT 1.00,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($collaborators_sql);

    // Seed some users
    $users = [
        ['hacker1', 'hacker1@example.com'],
        ['hacker2', 'hacker2@example.com'],
        ['hacker3', 'hacker3@example.com'],
        ['security_researcher', 'researcher@example.com'],
        ['bug_hunter', 'hunter@example.com']
    ];

    foreach ($users as $user) {
        $stmt = $pdo->prepare("SELECT id FROM lab_operation_users WHERE username = ? LIMIT 1");
        $stmt->execute([$user[0]]);
        if (!$stmt->fetch()) {
            $insert = $pdo->prepare("INSERT INTO lab_operation_users (username, email) VALUES (?, ?)");
            $insert->execute($user);
        }
    }

    // Create a test report
    $stmt = $pdo->prepare("SELECT id FROM lab_operation_reports WHERE title = 'Test Vulnerability Report' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO lab_operation_reports (title, description, created_by) VALUES (?, ?, ?)");
        $insert->execute(['Test Vulnerability Report', 'This is a dummy report for testing the collaborator vulnerability', 1]);
    }
}

$action = $_GET['action'] ?? 'dashboard';

// Handle GraphQL mutation endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'graphql') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['operationName']) && $input['operationName'] === 'SaveCollaboratorsMutation') {
        $reportId = $input['variables']['input']['report_id'] ?? 0;
        $collaborators = $input['variables']['input']['collaborators'] ?? [];
        
        $collaboratorNodes = [];
        
        foreach ($collaborators as $collab) {
            $usernameOrEmail = $collab['username_or_email'] ?? '';
            $bountyWeight = $collab['bounty_weight'] ?? 1.0;
            
            // Find user by username or email
            $stmt = $pdo->prepare("SELECT id, username, email FROM lab_operation_users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Check if already a collaborator
                $checkStmt = $pdo->prepare("SELECT id FROM lab_operation_collaborators WHERE report_id = ? AND user_id = ? LIMIT 1");
                $checkStmt->execute([$reportId, $user['id']]);
                
                if (!$checkStmt->fetch()) {
                    // Add as pending collaborator
                    $insertStmt = $pdo->prepare("INSERT INTO lab_operation_collaborators (report_id, user_id, status, bounty_weight) VALUES (?, ?, 'pending', ?)");
                    $insertStmt->execute([$reportId, $user['id'], $bountyWeight]);
                }
                
                // VULNERABLE: Returns email even though status is pending
                $collaboratorNodes[] = [
                    'node' => [
                        'id' => $user['id'],
                        'state' => 'pending',
                        'email' => $user['email'], // VULNERABLE: Email disclosure
                        'bounty_weight' => $bountyWeight,
                        'recipient' => [
                            'id' => $user['id'],
                            'username' => $user['username']
                        ]
                    ]
                ];
            }
        }
        
        $response = [
            'data' => [
                'saveCollaborators' => [
                    'was_successful' => true,
                    'errors' => [
                        'edges' => []
                    ]
                ]
            ],
            'collaborators' => $collaboratorNodes // VULNERABLE: Including collaborators in response
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
}

// Get report collaborators for display
$reportId = 1;
$collabStmt = $pdo->prepare("SELECT c.*, u.username, u.email FROM lab_operation_collaborators c JOIN lab_operation_users u ON c.user_id = u.id WHERE c.report_id = ?");
$collabStmt->execute([$reportId]);
$collaborators = $collabStmt->fetchAll();

// Get all users for the dropdown
$userStmt = $pdo->query("SELECT username FROM lab_operation_users ORDER BY username");
$allUsers = $userStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bug Bounty Platform - Report Collaboration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #0a0a0a;
            color: #fff;
            font-size: 14px;
        }
        
        .navbar {
            background-color: #1a1a1a;
            border-bottom: 1px solid #333;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar .logo {
            font-size: 20px;
            font-weight: bold;
            color: #663399;
        }
        
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .card {
            background: #1a1a1a;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 20px;
            border: 1px solid #333;
        }
        
        .card h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #663399;
        }
        
        .card p {
            color: #888;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #888;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 4px;
            color: #fff;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #663399;
        }
        
        .btn-submit {
            padding: 12px 30px;
            background-color: #663399;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .btn-submit:hover {
            background-color: #552288;
        }
        
        .collaborators-list {
            margin-top: 20px;
        }
        
        .collaborator-item {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .collaborator-info {
            flex: 1;
        }
        
        .collaborator-username {
            font-weight: bold;
            color: #fff;
        }
        
        .collaborator-email {
            color: #888;
            font-size: 12px;
        }
        
        .collaborator-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: #ffa500;
            color: #000;
        }
        
        .status-accepted {
            background-color: #00ff00;
            color: #000;
        }
        
        .api-response {
            background: #2a2a2a;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .test-users {
            margin-top: 20px;
            padding: 20px;
            background-color: #663399;
            border-radius: 8px;
        }
        
        .test-users h4 {
            margin-bottom: 10px;
        }
        
        .test-users table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .test-users td {
            padding: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #fff;
        }
        
        .test-users tr:last-child td {
            border-bottom: none;
        }
        
        .note {
            color: #ffa500;
            font-size: 12px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">Bug Bounty Platform</div>
    </div>

    <div class="container">
        <div class="card">
            <h2>Report #1: Test Vulnerability Report</h2>
            <p>Add collaborators to this report. The vulnerability allows viewing email addresses even for pending invitations.</p>
            
            <div class="form-group">
                <label for="username">Add Collaborator (Username)</label>
                <select id="username">
                    <?php foreach ($allUsers as $user): ?>
                        <option value="<?php echo htmlspecialchars($user); ?>"><?php echo htmlspecialchars($user); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="button" class="btn-submit" onclick="addCollaborator()">Add Collaborator</button>
            
            <div class="collaborators-list">
                <h3>Current Collaborators</h3>
                <?php if (empty($collaborators)): ?>
                    <p style="color: #888; margin-top: 10px;">No collaborators yet</p>
                <?php else: ?>
                    <?php foreach ($collaborators as $collab): ?>
                        <div class="collaborator-item">
                            <div class="collaborator-info">
                                <div class="collaborator-username"><?php echo htmlspecialchars($collab['username']); ?></div>
                                <div class="collaborator-email"><?php echo htmlspecialchars($collab['email']); ?></div>
                            </div>
                            <span class="collaborator-status status-<?php echo $collab['status']; ?>">
                                <?php echo ucfirst($collab['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div id="apiResponse" class="api-response" style="display: none;"></div>
            
            <div class="test-users">
                <h4>Test Users</h4>
                <table>
                    <tr>
                        <td>hacker1</td>
                        <td>hacker2</td>
                        <td>hacker3</td>
                    </tr>
                    <tr>
                        <td>security_researcher</td>
                        <td>bug_hunter</td>
                    </tr>
                </table>
                <p class="note">Note: When you add a collaborator via the API, the response will include their email address even though they are in 'pending' status</p>
            </div>
        </div>
    </div>
    
    <script>
        async function addCollaborator() {
            const username = document.getElementById('username').value;
            const responseDiv = document.getElementById('apiResponse');
            
            responseDiv.style.display = 'block';
            responseDiv.textContent = 'Sending GraphQL mutation...\n';
            
            const mutation = {
                operationName: 'SaveCollaboratorsMutation',
                variables: {
                    input: {
                        report_id: 1,
                        collaborators: [
                            {
                                username_or_email: username,
                                bounty_weight: 1.0
                            }
                        ],
                        product_area: 'collaboration',
                        product_feature: 'save_collaborators'
                    }
                },
                query: `mutation SaveCollaboratorsMutation($input: SaveCollaboratorsMutationInput!) {
                    saveCollaborators(input: $input) {
                        was_successful
                        errors {
                            edges {
                                node {
                                    message
                                }
                            }
                        }
                    }
                }`
            };
            
            try {
                const response = await fetch('index.php?action=graphql', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(mutation)
                });
                
                const data = await response.json();
                responseDiv.textContent += '\n' + JSON.stringify(data, null, 2);
                
                // Reload page to show updated collaborators
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } catch (error) {
                responseDiv.textContent += '\nError: ' + error.message;
            }
        }
    </script>
</body>
</html>
