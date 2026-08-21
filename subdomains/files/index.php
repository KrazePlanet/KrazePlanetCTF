<?php
// Simulate user authentication and session
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = uniqid('user_');
    $_SESSION['username'] = 'Demo User';
}

// File viewing functionality
$output = '';
$error = '';
$current_file = '';

if (isset($_GET['file'])) {
    $file = $_GET['file'];
    $current_file = htmlspecialchars($file);
    
    // Basic "security" message to make it look protected
    if (preg_match('/\.\.\//', $file)) {
        $error = "Directory traversal detected! Activity logged.";
    } else {
        ob_start();
        try {
            include($file);
            $output = ob_get_clean();
        } catch (Exception $e) {
            $error = "Error reading file: " . $e->getMessage();
            ob_end_clean();
        }
    }
}

// Sample files for the file browser
$sample_files = [
    'welcome.txt' => 'Welcome Message',
    'news.html' => 'Latest News',
    'document.pdf' => 'Important Document',
    'settings.ini' => 'Configuration Settings'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Path Traversal - Basic</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .user-info {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        nav {
            background-color: #34495e;
            padding: 0.5rem 0;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav li a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            display: block;
            transition: background 0.3s;
        }
        
        nav li a:hover {
            background-color: #2c3e50;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .sidebar {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .content-area {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        h2 {
            color: #3498db;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .file-list {
            list-style: none;
        }
        
        .file-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .file-list li a {
            color: #3498db;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .file-list li a:hover {
            color: #2980b9;
        }
        
        .file-list li a::before {
            content: "📄";
            margin-right: 8px;
        }
        
        .file-form {
            display: flex;
            margin-bottom: 1rem;
        }
        
        .file-input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 1rem;
        }
        
        .submit-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        .submit-btn:hover {
            background: #2980b9;
        }
        
        .output-container {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
            min-height: 200px;
        }
        
        .error {
            color: #e74c3c;
            background: #fadbd8;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .success {
            color: #27ae60;
            background: #d5f4e6;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .file-path {
            font-family: monospace;
            background: #2c3e50;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        footer {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem 0;
            color: #7f8c8d;
            font-size: 0.9rem;
            border-top: 1px solid #eee;
        }
        
        .security-notice {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 10px 15px;
            margin: 15px 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">SecureFile Viewer v2.1</div>
                <div class="user-info">
                    Welcome, <?php echo $_SESSION['username']; ?> 
                    (<?php echo $_SESSION['user_id']; ?>)
                </div>
            </div>
        </div>
    </header>
    
    <nav>
        <div class="container">
            <ul>
                <li><a href="?file=welcome.txt">Dashboard</a></li>
                <li><a href="?file=news.html">Company News</a></li>
                <li><a href="?file=document.pdf">Documents</a></li>
                <li><a href="?file=settings.ini">Settings</a></li>
                <li><a href="#" onclick="alert('Help system loading...')">Help</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="main-content">
            <aside class="sidebar">
                <h2>File Browser</h2>
                <p>Select a file to view:</p>
                <ul class="file-list">
                    <?php foreach($sample_files as $filename => $display_name): ?>
                        <li>
                            <a href="?file=<?php echo $filename; ?>">
                                <?php echo $display_name; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="security-notice">
                    <strong>Security Notice:</strong> All file ACCESS is logged and monitored.
                </div>
            </aside>
            
            <main class="content-area">
                <h1>Enterprise Document Viewer</h1>
                <p>View and analyze documents securely with our enterprise-grade file viewer.</p>
                
                <div class="security-notice">
                    <strong>New:</strong> Enhanced security protocols implemented in v2.1.
                </div>
                
                <h2>File ACCESS</h2>
                <form method="GET" action="" class="file-form">
                    <input type="text" 
                           id="file" 
                           name="file" 
                           class="file-input" 
                           placeholder="Enter filename or path (e.g. documents/report.txt)"
                           value="<?php echo $current_file; ?>">
                    <button type="submit" class="submit-btn">View File</button>
                </form>
                
                <?php if ($error): ?>
                    <div class="error">
                        <strong>Error:</strong> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($current_file && !$error): ?>
                    <div class="success">
                        File loaded successfully. ACCESS logged.
                    </div>
                <?php endif; ?>
                
                <?php if ($current_file): ?>
                    <div class="file-path">
                        Current file: <?php echo $current_file; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($output || $error): ?>
                    <div class="output-container">
                        <h2>File Content:</h2>
                        <?php if ($output): ?>
                            <pre><?php echo htmlspecialchars($output); ?></pre>
                        <?php else: ?>
                            <p>No content to display.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="output-container">
                        <p>No file selected. Use the form above to view a file or select one from the browser.</p>
                        <p>Supported formats: TXT, HTML, PDF, INI, XML, and more.</p>
                    </div>
                <?php endif; ?>
            </main>
        </div>
        
        <footer>
            <p>SecureFile Viewer &copy; 2023 | Enterprise Edition v2.1 | All ACCESS monitored and logged</p>
        </footer>
    </div>
</body>
</html>