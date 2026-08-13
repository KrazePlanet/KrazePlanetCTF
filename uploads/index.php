<?php
// Advanced File Management System with Multiple LFI Vulnerabilities
session_start();

// Initialize application
if (!isset($_SESSION['app_data'])) {
    $_SESSION['app_data'] = [
        'user' => 'admin',
        'role' => 'Administrator',
        'theme' => 'default',
        'last_login' => date('Y-m-d H:i:s')
    ];
}

$output = '';
$error = '';
$success = '';

// Technique 1: File upload with malicious filename inclusion
if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
    $uploaded_file = $_FILES['document']['name'];
    $temp_path = $_FILES['document']['tmp_name'];
    
    // VULNERABLE: Using uploaded filename in include without validation
    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    
    $target_path = $upload_dir . basename($uploaded_file);
    
    if (move_uploaded_file($temp_path, $target_path)) {
        $success = "File uploaded successfully: " . htmlspecialchars($uploaded_file);
        
        // VULNERABLE: Log the upload using include with user input
        $log_entry = date('Y-m-d H:i:s') . " - User {$_SESSION['app_data']['user']} uploaded: $uploaded_file\n";
        file_put_contents("upload_log.txt", $log_entry, FILE_APPEND);
        
        // VULNERABLE: Include the uploaded file if it's a PHP file
        if (strtolower(pathinfo($uploaded_file, PATHINFO_EXTENSION)) === 'php') {
            ob_start();
            include($target_path);
            $output = ob_get_clean();
        }
    } else {
        $error = "File upload failed.";
    }
}

// Technique 2: Configuration file loader with path traversal
if (isset($_GET['load_config'])) {
    $config_file = $_GET['load_config'];
    
    // VULNERABLE: Direct config file inclusion
    $config_path = "config/" . $config_file;
    
    ob_start();
    if (file_exists($config_path)) {
        include($config_path);
    } else {
        // VULNERABLE: Fallback to absolute path
        @include($config_file);
    }
    $config_content = ob_get_clean();
    
    if ($config_content) {
        $output = "<h4>Configuration Loaded:</h4><pre>" . htmlspecialchars($config_content) . "</pre>";
    }
}

// Technique 3: Plugin system with dynamic inclusion
if (isset($_POST['plugin'])) {
    $plugin_name = $_POST['plugin'];
    
    // VULNERABLE: Direct plugin inclusion without validation
    $plugin_file = "plugins/" . $plugin_name . ".php";
    
    ob_start();
    if (file_exists($plugin_file)) {
        include($plugin_file);
        $output = ob_get_clean();
    } else {
        // VULNERABLE: Try different extensions
        $alt_plugin = "plugins/" . $plugin_name;
        @include($alt_plugin);
        $output = ob_get_clean();
        
        if (empty($output)) {
            $error = "Plugin not found: " . htmlspecialchars($plugin_name);
        }
    }
}

// Technique 4: Cache system with file inclusion
if (isset($_GET['cache'])) {
    $cache_key = $_GET['cache'];
    
    // VULNERABLE: Cache file inclusion
    $cache_file = "cache/" . $cache_key;
    
    if (file_exists($cache_file)) {
        ob_start();
        include($cache_file);
        $output = ob_get_clean();
        $success = "Cache loaded: " . htmlspecialchars($cache_key);
    } else {
        $error = "Cache not found: " . htmlspecialchars($cache_key);
    }
}

// Technique 5: Local file wrapper with filter bypass
if (isset($_GET['resource'])) {
    $resource = $_GET['resource'];
    
    // VULNERABLE: Using php://filter for reading files
    if (strpos($resource, 'php://') === 0) {
        ob_start();
        @include($resource);
        $output = ob_get_clean();
    } else {
        // VULNERABLE: Direct file inclusion
        ob_start();
        @include($resource);
        $output = ob_get_clean();
    }
}

// Create sample files and directories
createSampleEnvironment();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload with LFI Vulnerability</title>
    <style>
        :root {
            --primary: #0066cc;
            --secondary: #004499;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --dark: #343a40;
            --light: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: var(--dark);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--warning);
        }
        
        .user-info {
            background: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 300px 1fr;
            min-height: 600px;
        }
        
        .sidebar {
            background: var(--light);
            padding: 2rem 1rem;
            border-right: 1px solid #dee2e6;
        }
        
        .content {
            padding: 2rem;
        }
        
        .module {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .module-header {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
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
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary);
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .output-container {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 1rem;
            margin-top: 1rem;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .nav-tabs {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 1rem;
        }
        
        .nav-tab {
            padding: 1rem 2rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 500;
        }
        
        .nav-tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .sidebar-nav {
            list-style: none;
        }
        
        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-nav a {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--dark);
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .sidebar-nav a:hover {
            background: var(--primary);
            color: white;
        }
        
        .file-list {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .file-item {
            padding: 0.5rem;
            border-bottom: 1px solid #eee;
            font-family: monospace;
        }
        
        .file-item:last-child {
            border-bottom: none;
        }
        
        .payload-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 1rem;
            margin-top: 2rem;
        }
        
        .payload-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 0.5rem;
        }
        
        .payload-item {
            background: white;
            padding: 0.5rem;
            margin: 0.25rem 0;
            border-radius: 3px;
            font-family: monospace;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Advanced File Manager v3.0</div>
            <div class="user-info">
                User: <?php echo $_SESSION['app_data']['user']; ?> 
                | Role: <?php echo $_SESSION['app_data']['role']; ?>
            </div>
        </div>
        
        <div class="main-content">
            <aside class="sidebar">
                <h3 style="margin-bottom: 1rem; color: var(--dark);">Navigation</h3>
                <ul class="sidebar-nav">
                    <li><a href="#" onclick="showTab('upload')">File Upload</a></li>
                    <li><a href="#" onclick="showTab('config')">Configuration</a></li>
                    <li><a href="#" onclick="showTab('plugins')">Plugins</a></li>
                    <li><a href="#" onclick="showTab('cache')">Cache System</a></li>
                    <li><a href="#" onclick="showTab('resources')">Resources</a></li>
                    <li><a href="#" onclick="showTab('payloads')">Payload Guide</a></li>
                </ul>
                
                <div class="file-list">
                    <h4>Sample Files:</h4>
                    <?php
                    $files = [
                        'config/database.php',
                        'config/settings.inc',
                        'plugins/header.php',
                        'plugins/footer.inc',
                        'cache/homepage.cache',
                        'uploads/test.txt',
                        'upload_log.txt',
                        '/etc/passwd'
                    ];
                    
                    foreach ($files as $file) {
                        echo "<div class='file-item'>$file</div>";
                    }
                    ?>
                </div>
            </aside>
            
            <main class="content">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- File Upload Tab -->
                <div id="upload-tab" class="tab-content active">
                    <div class="module">
                        <div class="module-header">File Upload System</div>
                        <p>Upload documents and files to the system. PHP files will be automatically processed.</p>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label class="form-label">Select File:</label>
                                <input type="file" name="document" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Upload File</button>
                        </form>
                    </div>
                </div>
                
                <!-- Configuration Tab -->
                <div id="config-tab" class="tab-content">
                    <div class="module">
                        <div class="module-header">Configuration Loader</div>
                        <p>Load and view configuration files from the config directory.</p>
                        <form method="GET">
                            <input type="hidden" name="load_config" value="1">
                            <div class="form-group">
                                <label class="form-label">Config File:</label>
                                <input type="text" name="load_config" class="form-control" placeholder="database.php" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Load Configuration</button>
                        </form>
                    </div>
                </div>
                
                <!-- Plugins Tab -->
                <div id="plugins-tab" class="tab-content">
                    <div class="module">
                        <div class="module-header">Plugin Manager</div>
                        <p>Load and execute system plugins.</p>
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Plugin Name:</label>
                                <input type="text" name="plugin" class="form-control" placeholder="header" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Load Plugin</button>
                        </form>
                    </div>
                </div>
                
                <!-- Cache Tab -->
                <div id="cache-tab" class="tab-content">
                    <div class="module">
                        <div class="module-header">Cache System</div>
                        <p>View cached files and data.</p>
                        <form method="GET">
                            <div class="form-group">
                                <label class="form-label">Cache Key:</label>
                                <input type="text" name="cache" class="form-control" placeholder="homepage.cache" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Load Cache</button>
                        </form>
                    </div>
                </div>
                
                <!-- Resources Tab -->
                <div id="resources-tab" class="tab-content">
                    <div class="module">
                        <div class="module-header">Resource Loader</div>
                        <p>Load system resources and files using advanced methods.</p>
                        <form method="GET">
                            <div class="form-group">
                                <label class="form-label">Resource Path:</label>
                                <input type="text" name="resource" class="form-control" placeholder="php://filter/convert.base64-encode/resource=index.php" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Load Resource</button>
                        </form>
                    </div>
                </div>
                
                <!-- Payloads Tab -->
                <div id="payloads-tab" class="tab-content">
                    <div class="module">
                        <div class="module-header">LFI Payload Guide</div>
                        <div class="payload-section">
                            <div class="payload-title">File Upload Payloads:</div>
                            <div class="payload-item">malicious.php (upload PHP shell)</div>
                            <div class="payload-item">../../../etc/passwd (path traversal in filename)</div>
                            
                            <div class="payload-title">Configuration Payloads:</div>
                            <div class="payload-item">../../../../etc/passwd</div>
                            <div class="payload-item">/etc/passwd</div>
                            <div class="payload-item">../upload_log.txt</div>
                            
                            <div class="payload-title">Plugin Payloads:</div>
                            <div class="payload-item">../../../../etc/passwd</div>
                            <div class="payload-item">../uploads/malicious.php</div>
                            <div class="payload-item">/etc/hosts</div>
                            
                            <div class="payload-title">Cache Payloads:</div>
                            <div class="payload-item">../../../../etc/passwd</div>
                            <div class="payload-item">../config/database.php</div>
                            
                            <div class="payload-title">Resource Payloads (php://filter):</div>
                            <div class="payload-item">php://filter/convert.base64-encode/resource=index.php</div>
                            <div class="payload-item">php://filter/read=string.rot13/resource=config/database.php</div>
                            <div class="payload-item">php://filter/convert.base64-encode/resource=/etc/passwd</div>
                        </div>
                    </div>
                </div>
                
                <?php if ($output): ?>
                    <div class="module">
                        <div class="module-header">Output</div>
                        <div class="output-container">
                            <?php echo htmlspecialchars($output); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
        }
        
        // Initialize first tab as active
        document.addEventListener('DOMContentLoaded', function() {
            showTab('upload');
        });
    </script>
</body>
</html>

<?php
function createSampleEnvironment() {
    // Create directories
    $dirs = ['config', 'plugins', 'cache', 'uploads'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }
    
    // Create sample config files
    $configs = [
        'database.php' => '<?php 
// Database configuration
$db_host = "localhost";
$db_user = "root"; 
$db_pass = "secret123";
$db_name = "app_db";
?>',
        'settings.inc' => '<?php
// Application settings
$app_name = "File Manager";
$debug_mode = true;
$admin_email = "admin@example.com";
?>'
    ];
    
    foreach ($configs as $file => $content) {
        if (!file_exists("config/$file")) {
            file_put_contents("config/$file", $content);
        }
    }
    
    // Create sample plugins
    $plugins = [
        'header.php' => '<?php
// Header plugin
echo "<h1>Header Plugin Loaded</h1>";
?>',
        'footer.inc' => '<?php
// Footer plugin  
echo "<footer>Footer Plugin Loaded</footer>";
?>'
    ];
    
    foreach ($plugins as $file => $content) {
        if (!file_exists("plugins/$file")) {
            file_put_contents("plugins/$file", $content);
        }
    }
    
    // Create sample cache files
    $caches = [
        'homepage.cache' => '<?php
// Cached homepage data
$cache_data = ["title" => "Homepage", "content" => "Welcome to our site"];
print_r($cache_data);
?>'
    ];
    
    foreach ($caches as $file => $content) {
        if (!file_exists("cache/$file")) {
            file_put_contents("cache/$file", $content);
        }
    }
    
    // Create upload log
    if (!file_exists('upload_log.txt')) {
        file_put_contents('upload_log.txt', "Upload Log:\n");
    }
}
?>