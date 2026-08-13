<?php
// Simulate a content management system with LFI vulnerabilities
session_start();

// Initialize user session
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => rand(1000, 9999),
        'name' => 'John Doe',
        'role' => 'Content Editor'
    ];
}

// Different LFI vulnerability techniques
$content = '';
$error = '';
$page_title = 'CMS Dashboard';

// Technique 1: Direct file inclusion without proper filtering
if (isset($_GET['page'])) {
    $page = $_GET['page'];
    
    // Fake security check to make it look protected
    if (strpos($page, '..') !== false) {
        $error = "Invalid page request. Suspicious activity detected.";
    } else {
        // VULNERABLE: Direct inclusion without proper path validation
        $file_path = "pages/" . $page . ".php";
        if (file_exists($file_path)) {
            ob_start();
            include($file_path);
            $content = ob_get_clean();
        } else {
            // VULNERABLE: Fallback that allows arbitrary file inclusion
            $alt_path = "includes/" . $page;
            ob_start();
            @include($alt_path);
            $content = ob_get_clean();
            if (empty($content)) {
                $error = "Page '$page' not found.";
            }
        }
    }
}

// Technique 2: Template engine vulnerability
if (isset($_POST['template'])) {
    $template = $_POST['template'];
    // VULNERABLE: Template inclusion with user input
    $template_file = "templates/" . $template;
    ob_start();
    @include($template_file);
    $template_content = ob_get_clean();
    if ($template_content) {
        $content = "<h3>Template Preview:</h3>" . $template_content;
    }
}

// Technique 3: Language file inclusion
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    // VULNERABLE: Language file inclusion without validation
    $lang_file = "lang/" . $lang . ".php";
    ob_start();
    @include($lang_file);
    $lang_content = ob_get_clean();
    if ($lang_content) {
        $_SESSION['language'] = $lang;
        $content = "Language changed to " . htmlspecialchars($lang);
    }
}

// Technique 4: Log file viewer (common vulnerable feature)
if (isset($_GET['view_log'])) {
    $log_file = $_GET['view_log'];
    // VULNERABLE: Direct log file reading
    if (file_exists($log_file)) {
        $content = "<h3>Log File: " . htmlspecialchars($log_file) . "</h3>";
        $content .= "<pre>" . htmlspecialchars(file_get_contents($log_file)) . "</pre>";
    } else {
        // VULNERABLE: Alternative log path
        $alt_log = "logs/" . $log_file;
        if (file_exists($alt_log)) {
            $content = "<h3>Log File: " . htmlspecialchars($alt_log) . "</h3>";
            $content .= "<pre>" . htmlspecialchars(file_get_contents($alt_log)) . "</pre>";
        } else {
            $error = "Log file not found: " . htmlspecialchars($log_file);
        }
    }
}

// Create some sample files for the lab
createSampleFiles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Local File Inclusion</title>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --danger: #f72585;
            --dark: #212529;
            --light: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .app-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            box-shadow: 0 0 50px rgba(0,0,0,0.1);
        }
        
        .navbar {
            background: var(--dark);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--success);
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: calc(100vh - 80px);
        }
        
        .sidebar {
            background: var(--light);
            padding: 2rem 1rem;
            border-right: 1px solid #dee2e6;
        }
        
        .sidebar-section {
            margin-bottom: 2rem;
        }
        
        .sidebar-section h3 {
            color: var(--dark);
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .sidebar-links {
            list-style: none;
        }
        
        .sidebar-links li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-links a {
            color: var(--dark);
            text-decoration: none;
            padding: 0.5rem;
            display: block;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .sidebar-links a:hover {
            background: var(--primary);
            color: white;
        }
        
        .content-area {
            padding: 2rem;
            background: white;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card-header {
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background: #ffe6e6;
            border: 1px solid #ffcccc;
            color: #cc0000;
        }
        
        .alert-success {
            background: #e6ffe6;
            border: 1px solid #ccffcc;
            color: #006600;
        }
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            margin: 1rem 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .tab-container {
            margin-top: 2rem;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 1rem;
        }
        
        .tab {
            padding: 1rem 2rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <nav class="navbar">
            <div class="logo">CMS Pro</div>
            <ul class="nav-links">
                <li><a href="?page=home">Dashboard</a></li>
                <li><a href="?page=content">Content</a></li>
                <li><a href="?page=media">Media</a></li>
                <li><a href="?page=users">Users</a></li>
                <li><a href="?page=settings">Settings</a></li>
            </ul>
            <div class="user-menu">
                <span>Welcome, <?php echo $_SESSION['user']['name']; ?></span>
                <span style="background: var(--success); padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.8rem;">
                    <?php echo $_SESSION['user']['role']; ?>
                </span>
            </div>
        </nav>

        <div class="main-content">
            <aside class="sidebar">
                <div class="sidebar-section">
                    <h3>Content Management</h3>
                    <ul class="sidebar-links">
                        <li><a href="?page=posts">All Posts</a></li>
                        <li><a href="?page=add_new">Add New</a></li>
                        <li><a href="?page=categories">Categories</a></li>
                        <li><a href="?page=tags">Tags</a></li>
                    </ul>
                </div>
                
                <div class="sidebar-section">
                    <h3>System Tools</h3>
                    <ul class="sidebar-links">
                        <li><a href="?view_log=system.log">System Logs</a></li>
                        <li><a href="?view_log=error.log">Error Logs</a></li>
                        <li><a href="?view_log=ACCESS.log">ACCESS Logs</a></li>
                        <li><a href="#" onclick="showTemplateSection()">Template Editor</a></li>
                    </ul>
                </div>
                
                <div class="sidebar-section">
                    <h3>Internationalization</h3>
                    <ul class="sidebar-links">
                        <li><a href="?lang=en">English</a></li>
                        <li><a href="?lang=es">Spanish</a></li>
                        <li><a href="?lang=fr">French</a></li>
                        <li><a href="?lang=de">German</a></li>
                    </ul>
                </div>
            </aside>

            <main class="content-area">
                <div class="dashboard-header">
                    <h1><?php echo $page_title; ?></h1>
                    <div class="user-menu">
                        <span>User ID: <?php echo $_SESSION['user']['id']; ?></span>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">1,247</div>
                        <div>Total Posts</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">5,892</div>
                        <div>Comments</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">24</div>
                        <div>Active Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">98.2%</div>
                        <div>Uptime</div>
                    </div>
                </div>

                <div class="tab-container">
                    <div class="tabs">
                        <div class="tab active" onclick="switchTab('content')">Content</div>
                        <div class="tab" onclick="switchTab('templates')">Templates</div>
                        <div class="tab" onclick="switchTab('system')">System Info</div>
                    </div>

                    <div id="content-tab" class="tab-content active">
                        <div class="card">
                            <div class="card-header">Page Content</div>
                            <?php if ($content): ?>
                                <div class="content-display">
                                    <?php echo $content; ?>
                                </div>
                            <?php else: ?>
                                <p>Select a page from the sidebar or use the quick ACCESS below:</p>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-top: 1rem;">
                                    <a href="?page=home" class="btn btn-primary">Home</a>
                                    <a href="?page=about" class="btn btn-primary">About</a>
                                    <a href="?page=contact" class="btn btn-primary">Contact</a>
                                    <a href="?page=profile" class="btn btn-primary">Profile</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="templates-tab" class="tab-content">
                        <div class="card">
                            <div class="card-header">Template Management</div>
                            <p>Edit or preview template files:</p>
                            <form method="POST" class="form-group">
                                <label class="form-label">Template File Name:</label>
                                <input type="text" name="template" class="form-control" placeholder="e.g., header.php, footer.php">
                                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Preview Template</button>
                            </form>
                            <?php if (isset($template_content) && $template_content): ?>
                                <div class="code-block">
                                    <?php echo htmlspecialchars($template_content); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="system-tab" class="tab-content">
                        <div class="card">
                            <div class="card-header">System Information</div>
                            <div class="code-block">
                                <?php
                                // Display some system info (another potential info disclosure)
                                echo "PHP Version: " . phpversion() . "\n";
                                echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
                                echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
                                ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label">View System Log:</label>
                                <input type="text" id="log_file" class="form-control" placeholder="e.g., /var/log/system.log">
                                <button onclick="viewLog()" class="btn btn-primary" style="margin-top: 1rem;">View Log File</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activate selected tab
            event.target.classList.add('active');
        }
        
        function showTemplateSection() {
            switchTab('templates');
        }
        
        function viewLog() {
            const logFile = document.getElementById('log_file').value;
            if (logFile) {
                window.location.href = '?view_log=' + encodeURIComponent(logFile);
            }
        }
        
        // Initialize some sample content
        document.addEventListener('DOMContentLoaded', function() {
            // Simulate loading animation
            setTimeout(() => {
                document.querySelector('.stats-grid').style.opacity = '1';
            }, 300);
        });
    </script>
</body>
</html>

<?php
function createSampleFiles() {
    // Create sample pages directory
    if (!is_dir('pages')) mkdir('pages');
    if (!is_dir('includes')) mkdir('includes');
    if (!is_dir('templates')) mkdir('templates');
    if (!is_dir('lang')) mkdir('lang');
    if (!is_dir('logs')) mkdir('logs');
    
    // Sample page files
    $pages = [
        'home.php' => '<h3>Welcome to CMS Pro</h3><p>This is your dashboard homepage.</p>',
        'about.php' => '<h3>About Us</h3><p>Company information goes here.</p>',
        'contact.php' => '<h3>Contact Information</h3><p>Email: info@example.com</p>'
    ];
    
    foreach ($pages as $filename => $content) {
        if (!file_exists("pages/$filename")) {
            file_put_contents("pages/$filename", "<?php // $content ?>");
        }
    }
    
    // Sample template files
    $templates = [
        'header.php' => '<!-- Site Header -->',
        'footer.php' => '<!-- Site Footer -->'
    ];
    
    foreach ($templates as $filename => $content) {
        if (!file_exists("templates/$filename")) {
            file_put_contents("templates/$filename", $content);
        }
    }
    
    // Sample language files
    $langs = [
        'en.php' => '<?php $lang = "English"; ?>',
        'es.php' => '<?php $lang = "Spanish"; ?>'
    ];
    
    foreach ($langs as $filename => $content) {
        if (!file_exists("lang/$filename")) {
            file_put_contents("lang/$filename", $content);
        }
    }
    
    // Sample log files
    $logs = [
        'system.log' => "2024-01-15: System started\n2024-01-15: User logged in",
        'error.log' => "2024-01-15: No errors reported"
    ];
    
    foreach ($logs as $filename => $content) {
        if (!file_exists("logs/$filename")) {
            file_put_contents("logs/$filename", $content);
        }
    }
}
?>