<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Create lab-specific table for file downloads
if ($pdo) {
    $table_sql = "CREATE TABLE IF NOT EXISTS `lab_products_downloads` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `file_path` VARCHAR(255) NOT NULL,
        `downloaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($table_sql);
}

function checkPath($path){
    if(!contains($path, "data_products")){
        ob_clean();
        http_response_code(403);
        echo "403 Forbidden - Access Denied";
        exit();
    }
}

function startsWith($haystack, $needle) {
    $length = strlen($needle);
    return substr($haystack, 0, $length) === $needle;
}

function contains($haystack, $needle) {
    return strpos($haystack, $needle) !== false;
}

$file = $_GET['filePathDownload'] ?? '';

// Handle file download (VULNERABLE: Weak path validation, bypassable with path traversal)
if (!empty($file)) {
    // Log the download attempt
    $stmt = $pdo->prepare("INSERT INTO lab_products_downloads (file_path) VALUES (?)");
    $stmt->execute([$file]);
    
    $filepath = urldecode($file);
    checkPath($filepath);
    
    // Resolve path (VULNERABLE: realpath resolves ../ sequences after checkPath passes)
    $resolvedPath = realpath($filepath);
    
    if ($resolvedPath && file_exists($resolvedPath) && is_file($resolvedPath)) {
        ob_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"" . basename($filepath) . "\";");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($resolvedPath));
        readfile($resolvedPath);
    } else {
        ob_clean();
        header("Content-Type: text/plain");
        echo "File Not Found: " . htmlspecialchars($filepath);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Products Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Source Sans Pro', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            font-size: 14px;
        }
        
        .top-banner {
            background-color: #c62828;
            color: white;
            padding: 8px 20px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        .header {
            background: linear-gradient(135deg, #0a1628 0%, #1a237e 100%);
            color: white;
            padding: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-section h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
        }
        
        .logo-section p {
            font-size: 11px;
            opacity: 0.8;
            margin: 0;
            letter-spacing: 1px;
        }
        
        .header-nav {
            display: flex;
            gap: 25px;
            font-size: 13px;
        }
        
        .header-nav a {
            color: white;
            text-decoration: none;
            opacity: 0.9;
            transition: opacity 0.2s;
        }
        
        .header-nav a:hover {
            opacity: 1;
        }
        
        .sub-header {
            background-color: #1b3a5c;
            color: white;
            padding: 10px 30px;
            font-size: 13px;
            border-bottom: 3px solid #cfb87c;
        }
        
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .breadcrumb {
            color: #666;
            font-size: 12px;
            margin-bottom: 20px;
        }
        
        .breadcrumb a {
            color: #1a237e;
            text-decoration: none;
        }
        
        .card {
            background: white;
            border-radius: 4px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-top: 4px solid #1a237e;
        }
        
        .card-header {
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #1a237e;
            font-weight: 600;
        }
        
        .card h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
            font-weight: 600;
        }
        
        .card p {
            color: #555;
            margin-bottom: 15px;
            line-height: 1.7;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: #fafafa;
            border: 1px solid #ccc;
            border-radius: 3px;
            color: #333;
            font-size: 14px;
            font-family: 'Courier New', monospace;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #1a237e;
            background: white;
            box-shadow: 0 0 0 2px rgba(26, 35, 126, 0.1);
        }
        
        .btn-submit {
            padding: 12px 35px;
            background-color: #1a237e;
            border: none;
            border-radius: 3px;
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.5px;
        }
        
        .btn-submit:hover {
            background-color: #283593;
        }
        
        .security-notice {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #856404;
        }
        
        .security-notice strong {
            display: block;
            margin-bottom: 5px;
        }
        
        .footer {
            background-color: #0a1628;
            color: white;
            padding: 30px;
            text-align: center;
            font-size: 12px;
            margin-top: 40px;
        }
        
        .footer p {
            opacity: 0.7;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="top-banner">UNCLASSIFIED // FOR OFFICIAL USE ONLY</div>
    
    <div class="header">
        <div class="header-inner">
            <div class="logo-section">
                <div>
                    <h1>DEPARTMENT OF DEFENSE</h1>
                    <p>UNITED STATES OF AMERICA</p>
                </div>
            </div>
            <div class="header-nav">
                <a href="#">Home</a>
                <a href="#">Data Products</a>
                <a href="#">Reports</a>
                <a href="#">Contact</a>
            </div>
        </div>
    </div>
    
    <div class="sub-header">
        Defense Logistics Agency > Data Products > File Download
    </div>

    <div class="container">
        <div class="breadcrumb">
            <a href="#">Home</a> > <a href="#">Data Products</a> > <a href="#">File Download</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Data Products Download Portal</h2>
                <p>Defense Logistics Agency - Secure Data Access System</p>
            </div>
            
            <div class="security-notice">
                <strong>SECURITY NOTICE:</strong>
                This system is for authorized use only. All file downloads are logged and monitored. Access is restricted to data_products directory only.
            </div>
            
            <h3>File Retrieval</h3>
            <p>Enter the file path to retrieve data products from the secure data repository. Only authorized personnel with proper clearance may access data products.</p>
            
            <div class="form-group">
                <label for="filePath">File Path</label>
                <input type="text" id="filePath" placeholder="data_products/filename.pdf" value="">
            </div>
            
            <button type="button" class="btn-submit" onclick="downloadFile()">Download File</button>
        </div>
    </div>
    
    <div class="footer">
        <p>Defense Logistics Agency | U.S. Department of Defense</p>
        <p>This is a Department of Defense computer system. This computer system, including all related equipment, networks, and network devices, is provided only for authorized U.S. Government use.</p>
        <p>&copy; 2024 U.S. Department of Defense. All rights reserved.</p>
    </div>
    
    <script>
        function downloadFile() {
            const filePath = document.getElementById('filePath').value;
            if (!filePath) {
                alert('Please enter a file path');
                return;
            }
            
            window.location.href = 'index.php?filePathDownload=' + encodeURIComponent(filePath);
        }
    </script>
</body>
</html>
