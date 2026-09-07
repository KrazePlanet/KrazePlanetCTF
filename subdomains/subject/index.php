<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific tables for reports and organizations
if ($pdo) {
    $org_sql = "CREATE TABLE IF NOT EXISTS `lab_subject_organizations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($org_sql);
    
    $report_sql = "CREATE TABLE IF NOT EXISTS `lab_subject_reports` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `organization_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `url` VARCHAR(255) NOT NULL,
        `state` VARCHAR(50) NOT NULL,
        `substate` VARCHAR(50) NOT NULL,
        `severity_rating` VARCHAR(20) NOT NULL,
        `readable_substate` VARCHAR(50) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `reporter_name` VARCHAR(100) NOT NULL,
        `is_private` TINYINT DEFAULT 0,
        FOREIGN KEY (`organization_id`) REFERENCES `lab_subject_organizations`(`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($report_sql);
    
    // Seed organizations
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lab_subject_organizations");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $orgs = [
            ['HackerOne', 'hackerone'],
            ['TestCorp', 'testcorp'],
            ['SecureApp', 'secureapp'],
            ['BugBounty', 'bugbounty']
        ];
        foreach ($orgs as $org) {
            $stmt = $pdo->prepare("INSERT INTO lab_subject_organizations (name, slug) VALUES (?, ?)");
            $stmt->execute($org);
        }
    }
    
    // Seed reports
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lab_subject_reports");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $reports = [
            [1, 'SQL Injection in login form', '/reports/1', 'triaged', 'triaged', 'critical', 'Triaged', 'security_researcher', 0],
            [1, 'XSS in user profile', '/reports/2', 'resolved', 'resolved', 'high', 'Resolved', 'hacker123', 0],
            [1, 'CSRF in password reset', '/reports/3', 'new', 'new', 'medium', 'New', 'bug_hunter', 1],
            [2, 'IDOR in API endpoint', '/reports/4', 'triaged', 'triaged', 'high', 'Triaged', 'researcher_x', 1],
            [2, 'Path traversal in file upload', '/reports/5', 'resolved', 'resolved', 'critical', 'Resolved', 'security_ninja', 0],
            [3, 'SSRF in webhook handler', '/reports/6', 'new', 'new', 'critical', 'New', 'pentester', 1],
            [3, 'RCE via deserialization', '/reports/7', 'triaged', 'triaged', 'critical', 'Triaged', 'exploit_dev', 1],
            [4, 'Authentication bypass', '/reports/8', 'resolved', 'resolved', 'critical', 'Resolved', 'white_hat', 0],
        ];
        foreach ($reports as $report) {
            $stmt = $pdo->prepare("INSERT INTO lab_subject_reports (organization_id, title, url, state, substate, severity_rating, readable_substate, reporter_name, is_private) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute($report);
        }
    }
}

// Handle /bugs.json endpoint (VULNERABLE: IDOR allows accessing other organizations' reports)
if (strpos($_SERVER['REQUEST_URI'], 'bugs.json') !== false) {
    header('Content-Type: application/json');
    
    $organization_id = $_POST['organization_id'] ?? $_GET['organization_id'] ?? 1;
    $text_query = $_POST['text_query'] ?? $_GET['text_query'] ?? '';
    $limit = $_POST['limit'] ?? $_GET['limit'] ?? 100;
    $page = $_POST['page'] ?? $_GET['page'] ?? 1;
    $view = $_POST['view'] ?? $_GET['view'] ?? 'message';
    $sort_type = $_POST['sort_type'] ?? $_GET['sort_type'] ?? 'latest_activity';
    $sort_direction = $_POST['sort_direction'] ?? $_GET['sort_direction'] ?? 'descending';
    
    // Handle substates array from POST
    $substates = $_POST['substates'] ?? $_GET['substates'] ?? ['new', 'needs-more-info', 'triaged', 'resolved', 'informative', 'not-applicable', 'duplicate', 'retesting', 'pending-program-review', 'spam'];
    if (is_string($substates)) {
        $substates = json_decode($substates, true) ?? [$substates];
    }
    
    // Handle program_states array from POST
    $program_states = $_POST['program_states'] ?? $_GET['program_states'] ?? [];
    if (is_string($program_states)) {
        $program_states = json_decode($program_states, true) ?? [];
    }
    
    // VULNERABLE: No authorization check - any organization_id can be queried
    $whereClause = "WHERE r.organization_id = ?";
    $params = [$organization_id];
    
    // Add substate filtering
    if (!empty($substates) && is_array($substates)) {
        $placeholders = str_repeat('?,', count($substates) - 1) . '?';
        $whereClause .= " AND r.substate IN ($placeholders)";
        $params = array_merge($params, $substates);
    }
    
    // Add text query filtering
    if (!empty($text_query)) {
        $whereClause .= " AND (r.title LIKE ? OR r.id LIKE ?)";
        $search = '%' . $text_query . '%';
        $params[] = $search;
        $params[] = $search;
    }
    
    // Add program_states filtering if provided
    if (!empty($program_states) && is_array($program_states)) {
        $placeholders = str_repeat('?,', count($program_states) - 1) . '?';
        $whereClause .= " AND o.id IN ($placeholders)";
        $params = array_merge($params, $program_states);
    }
    
    // Sort direction
    $orderBy = "ORDER BY r.created_at " . ($sort_direction === 'descending' ? 'DESC' : 'ASC');
    if ($sort_type === 'latest_activity') {
        $orderBy = "ORDER BY r.created_at " . ($sort_direction === 'descending' ? 'DESC' : 'ASC');
    } elseif ($sort_type === 'pg_search_rank') {
        $orderBy = "ORDER BY r.id " . ($sort_direction === 'descending' ? 'DESC' : 'ASC');
    }
    
    $offset = ($page - 1) * $limit;
    
    $stmt = $pdo->prepare("
        SELECT r.id, r.title, r.url, r.state, r.substate, r.severity_rating, r.readable_substate, 
               r.created_at, r.submitted_at, r.reporter_name, o.name as organization_name
        FROM lab_subject_reports r
        JOIN lab_subject_organizations o ON r.organization_id = o.id
        $whereClause
        $orderBy
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'reports' => $reports,
        'total' => count($reports),
        'organization_id' => $organization_id,
        'page' => $page,
        'limit' => $limit
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bug Reports - HackerOne Style</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .header {
            background-color: #ffffff;
            border-bottom: 1px solid #e5e5e5;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 64px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #000;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .logo span {
            color: #ff3b30;
        }
        
        .nav {
            display: flex;
            gap: 32px;
        }
        
        .nav a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .nav a:hover {
            color: #ff3b30;
        }
        
        .header-actions {
            display: flex;
            gap: 16px;
            align-items: center;
        }
        
        .btn-signin {
            padding: 8px 20px;
            background-color: #ff3b30;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-signin:hover {
            background-color: #e6352b;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 24px;
        }
        
        .page-header {
            margin-bottom: 32px;
        }
        
        .page-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #000;
            margin-bottom: 8px;
        }
        
        .page-header p {
            color: #666;
            font-size: 16px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid #e5e5e5;
        }
        
        .card h2 {
            font-size: 20px;
            margin-bottom: 16px;
            color: #000;
            font-weight: 600;
        }
        
        .card p {
            color: #666;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            color: #333;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #ff3b30;
            box-shadow: 0 0 0 3px rgba(255, 59, 48, 0.1);
        }
        
        .btn-submit {
            padding: 12px 32px;
            background-color: #ff3b30;
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-submit:hover {
            background-color: #e6352b;
        }
        
        .reports-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }
        
        .reports-table th {
            background: #f9fafb;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .reports-table td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }
        
        .reports-table tr:hover {
            background-color: #f9fafb;
        }
        
        .severity-critical {
            color: #dc2626;
            font-weight: 600;
            background-color: #fef2f2;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .severity-high {
            color: #ea580c;
            font-weight: 600;
            background-color: #fff7ed;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .severity-medium {
            color: #2563eb;
            font-weight: 600;
            background-color: #eff6ff;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .severity-low {
            color: #16a34a;
            font-weight: 600;
            background-color: #f0fdf4;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .state-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .state-new {
            background-color: #dbeafe;
            color: #1d4ed8;
        }
        
        .state-triaged {
            background-color: #fef3c7;
            color: #b45309;
        }
        
        .state-resolved {
            background-color: #dcfce7;
            color: #15803d;
        }
        
        .json-preview {
            background: #1f2937;
            color: #10b981;
            padding: 24px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin-top: 24px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            border: 1px solid #374151;
        }
        
        .org-info {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin-bottom: 24px;
            border-radius: 6px;
        }
        
        .org-info h4 {
            margin-bottom: 12px;
            color: #1d4ed8;
            font-size: 16px;
            font-weight: 600;
        }
        
        .org-info table {
            width: 100%;
        }
        
        .org-info td {
            padding: 8px 0;
            color: #374151;
            font-size: 14px;
        }
        
        .footer {
            background-color: #1f2937;
            color: #9ca3af;
            padding: 48px 24px;
            margin-top: 64px;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 32px;
        }
        
        .footer-section h5 {
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .footer-section a {
            color: #9ca3af;
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .footer-section a:hover {
            color: #fff;
        }
        
        .footer-bottom {
            max-width: 1200px;
            margin: 32px auto 0;
            padding-top: 32px;
            border-top: 1px solid #374151;
            text-align: center;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="#" class="logo">Hacker<span>One</span></a>
        <div class="nav">
            <a href="#">Programs</a>
            <a href="#">Hackers</a>
            <a href="#">Leaderboard</a>
            <a href="#">Community</a>
            <a href="#">About</a>
        </div>
        <div class="header-actions">
            <a href="#" class="btn-signin">Sign In</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>Bug Reports</h1>
            <p>Search and explore bug reports across organizations</p>
        </div>
        
        <div class="card">
            <h2>Search Reports</h2>
            <p>Use the bugs.json API endpoint to search for reports across different organizations.</p>
            
            <div class="org-info">
                <h4>Available Organizations</h4>
                <table>
                    <tr>
                        <td><strong>ID 1:</strong> HackerOne</td>
                        <td><strong>ID 2:</strong> TestCorp</td>
                        <td><strong>ID 3:</strong> SecureApp</td>
                        <td><strong>ID 4:</strong> BugBounty</td>
                    </tr>
                </table>
            </div>
            
            <div class="form-group">
                <label for="organization_id">Organization ID</label>
                <select id="organization_id">
                    <option value="1">1 - HackerOne</option>
                    <option value="2">2 - TestCorp</option>
                    <option value="3">3 - SecureApp</option>
                    <option value="4">4 - BugBounty</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="text_query">Search Query (optional)</label>
                <input type="text" id="text_query" placeholder="Enter search term or leave empty for all reports">
            </div>
            
            <div class="form-group">
                <label for="limit">Limit</label>
                <input type="number" id="limit" value="100" min="1" max="1000">
            </div>
            
            <button type="button" class="btn-submit" onclick="searchReports()">Search Reports</button>
            
            <div id="results" style="display: none;">
                <h3 style="margin-top: 30px; margin-bottom: 15px; font-size: 20px; font-weight: 600; color: #000;">Results</h3>
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Organization</th>
                            <th>Severity</th>
                            <th>State</th>
                            <th>Reporter</th>
                        </tr>
                    </thead>
                    <tbody id="reportsBody"></tbody>
                </table>
                
                <h4 style="margin-top: 30px; margin-bottom: 10px; font-size: 16px; font-weight: 600; color: #000;">JSON Response</h4>
                <div class="json-preview" id="jsonPreview"></div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h5>Company</h5>
                <a href="#">About</a>
                <a href="#">Careers</a>
                <a href="#">Blog</a>
                <a href="#">Press</a>
            </div>
            <div class="footer-section">
                <h5>Resources</h5>
                <a href="#">Documentation</a>
                <a href="#">API</a>
                <a href="#">Community</a>
                <a href="#">Help Center</a>
            </div>
            <div class="footer-section">
                <h5>Legal</h5>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Security</a>
                <a href="#">Bug Bounty</a>
            </div>
            <div class="footer-section">
                <h5>Connect</h5>
                <a href="#">Twitter</a>
                <a href="#">GitHub</a>
                <a href="#">Discord</a>
                <a href="#">LinkedIn</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 HackerOne. All rights reserved.</p>
        </div>
    </div>
    
    <script>
        function searchReports() {
            const organization_id = document.getElementById('organization_id').value;
            const text_query = document.getElementById('text_query').value;
            const limit = document.getElementById('limit').value;
            
            const formData = new FormData();
            formData.append('organization_id', organization_id);
            formData.append('text_query', text_query);
            formData.append('limit', limit);
            formData.append('page', '1');
            
            fetch('bugs.json', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                displayResults(data);
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        function displayResults(data) {
            const resultsDiv = document.getElementById('results');
            const reportsBody = document.getElementById('reportsBody');
            const jsonPreview = document.getElementById('jsonPreview');
            
            resultsDiv.style.display = 'block';
            
            reportsBody.innerHTML = '';
            
            data.reports.forEach(report => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${report.id}</td>
                    <td><a href="${report.url}" target="_blank">${report.title}</a></td>
                    <td>${report.organization_name}</td>
                    <td class="severity-${report.severity_rating.toLowerCase()}">${report.severity_rating}</td>
                    <td><span class="state-badge state-${report.substate}">${report.readable_substate}</span></td>
                    <td>${report.reporter_name}</td>
                `;
                reportsBody.appendChild(row);
            });
            
            jsonPreview.textContent = JSON.stringify(data, null, 2);
        }
    </script>
</body>
</html>
