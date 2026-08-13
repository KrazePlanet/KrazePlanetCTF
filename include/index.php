<?php
// AssetPulse CDN — Resource Importer Module

session_start();

$message = '';
$included_content = '';
$user_input = '';

// Resource fetching engine — supports remote URLs and local CDN assets
function fetch_resource($filename) {
    if (empty($filename)) {
        return "No resource specified.";
    }
    
    // Remote URL import (fetches and processes external assets)
    if (filter_var($filename, FILTER_VALIDATE_URL)) {
        ob_start();
        $result = @include($filename);
        $content = ob_get_clean();
        if ($result !== false) {
            return $content;
        } else {
            return "Failed to fetch remote resource: " . htmlspecialchars($filename);
        }
    }
    
    // Local CDN asset lookup
    $local_path = "includes/" . basename($filename);
    if (file_exists($local_path)) {
        ob_start();
        include($local_path);
        return ob_get_clean();
    } else {
        return "Local asset not found: " . htmlspecialchars($local_path);
    }
}

// Handle resource import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['include_file'])) {
    $user_input = $_POST['filename'] ?? '';
    
    if ($user_input) {
        $included_content = fetch_resource($user_input);
        
        if (strpos($included_content, 'Failed to fetch remote resource') !== false) {
            $message = '<div class="alert alert-danger"><i class="bi bi-x-circle me-1"></i> Failed to import remote resource!</div>';
        } elseif (strpos($included_content, 'Local asset not found') !== false) {
            $message = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i> Local asset not found in CDN library!</div>';
        } else {
            $message = '<div class="alert alert-success"><i class="bi bi-check-circle me-1"></i> Resource imported successfully!</div>';
        }
    }
}

// Create some sample local files for testing
if (!file_exists('includes')) {
    mkdir('includes', 0755, true);
}

// Create sample files
$sample_files = [
    'welcome.txt' => 'Welcome to our application!',
    'about.txt' => 'This is a sample about page.',
    'contact.txt' => 'Contact us at: contact@example.com',
    'config.txt' => 'Database configuration: localhost:3306'
];

foreach ($sample_files as $filename => $content) {
    $file_path = 'includes/' . $filename;
    if (!file_exists($file_path)) {
        file_put_contents($file_path, $content);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AssetPulse — CDN Asset Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f4f8;
            color: #1e293b;
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 260px;
            background: linear-gradient(180deg, #0f172a 0%, #1a2332 100%);
            z-index: 100;
            padding: 0;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-brand .brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #fff;
            flex-shrink: 0;
        }

        .sidebar-brand .brand-text {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.5px;
        }

        .sidebar-brand .brand-text span { color: #a5b4fc; }

        .sidebar-nav { padding: 0.75rem 0; }

        .sidebar-nav .nav-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #475569;
            padding: 1rem 1.5rem 0.5rem;
        }

        .sidebar-nav .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 1.5rem;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.15s;
            border-left: 3px solid transparent;
        }

        .sidebar-nav .nav-item:hover { background: rgba(255,255,255,0.04); color: #e2e8f0; }

        .sidebar-nav .nav-item.active {
            background: rgba(99,102,241,0.1);
            color: #a5b4fc;
            border-left-color: #6366f1;
        }

        .sidebar-nav .nav-item i { font-size: 1.1rem; width: 20px; text-align: center; }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .sidebar-footer .user-info { display: flex; align-items: center; gap: 0.75rem; }

        .sidebar-footer .avatar {
            width: 32px; height: 32px; border-radius: 8px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 0.75rem; font-weight: 700;
        }

        .sidebar-footer .user-name { font-size: 0.8rem; font-weight: 600; color: #e2e8f0; }
        .sidebar-footer .user-role { font-size: 0.7rem; color: #64748b; }

        .main-content { margin-left: 260px; min-height: 100vh; }

        .topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-left { display: flex; align-items: center; gap: 0.75rem; }

        .topbar-left .breadcrumb { margin: 0; background: none; padding: 0; font-size: 0.85rem; }
        .topbar-left .breadcrumb .breadcrumb-item { color: #64748b; }
        .topbar-left .breadcrumb .breadcrumb-item.active { color: #1e293b; font-weight: 600; }
        .topbar-left .breadcrumb .breadcrumb-item + .breadcrumb-item::before { color: #94a3b8; }

        .topbar-right { display: flex; align-items: center; gap: 1rem; }

        .topbar-right .status-badge {
            display: flex; align-items: center; gap: 0.4rem;
            font-size: 0.75rem; font-weight: 600; color: #059669;
            background: #ecfdf5; padding: 0.35rem 0.75rem; border-radius: 20px;
        }

        .topbar-right .status-badge .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: #10b981; animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .topbar-right .btn-icon {
            width: 36px; height: 36px; border-radius: 10px;
            border: 1px solid #e2e8f0; background: #fff;
            display: flex; align-items: center; justify-content: center;
            color: #64748b; cursor: pointer; transition: all 0.15s;
        }

        .topbar-right .btn-icon:hover { background: #f8fafc; border-color: #cbd5e1; }

        .page-content { padding: 1.5rem 2rem 2rem; }

        .page-header { margin-bottom: 1.5rem; }

        .page-header h1 {
            font-size: 1.5rem; font-weight: 700; letter-spacing: -0.5px;
            margin-bottom: 0.25rem;
        }

        .page-header p { color: #64748b; font-size: 0.9rem; margin: 0; }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: #fff; border-radius: 12px; border: 1px solid #e2e8f0;
            padding: 1.25rem; transition: box-shadow 0.15s;
        }

        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

        .stat-card .stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; }

        .stat-card .stat-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
        }

        .stat-card .stat-icon.purple { background: #eef2ff; color: #6366f1; }
        .stat-card .stat-icon.blue { background: #eff6ff; color: #3b82f6; }
        .stat-card .stat-icon.green { background: #ecfdf5; color: #10b981; }
        .stat-card .stat-icon.amber { background: #fffbeb; color: #f59e0b; }

        .stat-card .stat-value { font-size: 1.5rem; font-weight: 700; letter-spacing: -1px; margin-bottom: 0.1rem; }
        .stat-card .stat-label { font-size: 0.78rem; color: #64748b; font-weight: 500; }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }

        .panel { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }

        .panel-header {
            padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between;
        }

        .panel-header h5 { font-size: 0.9rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
        .panel-header h5 i { color: #6366f1; }

        .panel-body { padding: 1.25rem; }

        .form-group { margin-bottom: 1rem; }

        .form-group label {
            font-size: 0.8rem; font-weight: 600; color: #475569;
            margin-bottom: 0.4rem; display: block;
        }

        .form-group label .optional { font-weight: 400; color: #94a3b8; font-size: 0.75rem; }

        .form-control {
            font-family: 'Inter', sans-serif; font-size: 0.85rem;
            padding: 0.6rem 0.85rem; border: 1px solid #e2e8f0;
            border-radius: 8px; background: #fff; transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

        .form-hint { font-size: 0.75rem; color: #94a3b8; margin-top: 0.3rem; }

        .btn-primary {
            background: #6366f1; border: none; color: #fff;
            font-weight: 600; font-size: 0.85rem; padding: 0.6rem 1.25rem;
            border-radius: 8px; transition: all 0.15s;
            display: inline-flex; align-items: center; gap: 0.5rem;
        }

        .btn-primary:hover {
            background: #4f46e5; transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
        }

        .btn-outline {
            background: #fff; border: 1px solid #e2e8f0; color: #475569;
            font-weight: 500; font-size: 0.8rem; padding: 0.4rem 0.75rem;
            border-radius: 6px; transition: all 0.15s; cursor: pointer;
        }

        .btn-outline:hover { border-color: #cbd5e1; background: #f8fafc; }

        .asset-list { list-style: none; padding: 0; margin: 0; }

        .asset-list li {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.6rem 0; border-bottom: 1px solid #f1f5f9;
        }

        .asset-list li:last-child { border-bottom: none; }

        .asset-list .asset-info { display: flex; align-items: center; gap: 0.6rem; }

        .asset-list .asset-icon {
            width: 32px; height: 32px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem; flex-shrink: 0;
        }

        .asset-list .asset-icon.txt { background: #eef2ff; color: #6366f1; }
        .asset-list .asset-icon.cfg { background: #fffbeb; color: #f59e0b; }

        .asset-list .asset-name { font-size: 0.82rem; font-weight: 500; }
        .asset-list .asset-size { font-size: 0.72rem; color: #94a3b8; }

        .asset-list .asset-badge {
            font-size: 0.65rem; font-weight: 600; padding: 0.15rem 0.5rem;
            border-radius: 4px; background: #f1f5f9; color: #64748b;
        }

        .output-console {
            background: #0f172a; border-radius: 10px; overflow: hidden; border: 1px solid #1e293b;
        }

        .output-console .console-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.6rem 1rem; background: #1a2332; border-bottom: 1px solid #1e293b;
        }

        .output-console .console-header .console-title {
            font-size: 0.78rem; font-weight: 600; color: #94a3b8;
            display: flex; align-items: center; gap: 0.4rem;
        }

        .output-console .console-header .console-title .green-dot {
            width: 6px; height: 6px; border-radius: 50%; background: #10b981;
        }

        .output-console .console-body {
            padding: 1rem;
            font-family: 'Cascadia Code', 'Fira Code', 'Consolas', monospace;
            font-size: 0.8rem; color: #e2e8f0;
            min-height: 100px; max-height: 300px; overflow-y: auto;
            line-height: 1.6; white-space: pre-wrap; word-break: break-all;
        }

        .output-console .console-body .prompt-line { color: #10b981; }
        .output-console .console-body .prompt-line .path { color: #6366f1; }
        .output-console .console-body .output-text { color: #e2e8f0; }

        .output-console .console-body .empty-state {
            color: #475569; font-family: 'Inter', sans-serif; font-size: 0.85rem;
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; min-height: 80px; gap: 0.4rem;
        }

        .output-console .console-body .empty-state i { font-size: 1.5rem; color: #334155; }

        .quick-actions {
            display: flex; flex-wrap: wrap; gap: 0.4rem;
            margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #f1f5f9;
        }

        .quick-actions .label {
            font-size: 0.72rem; color: #94a3b8;
            font-weight: 500; margin-right: 0.25rem; align-self: center;
        }

        .alert { font-size: 0.85rem; border-radius: 8px; padding: 0.6rem 1rem; margin-bottom: 1rem; }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }

        @media (max-width: 992px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .two-col { grid-template-columns: 1fr; }
        }

        @media (max-width: 576px) {
            .stats-row { grid-template-columns: 1fr; }
            .page-content { padding: 1rem; }
            .topbar { padding: 0.75rem 1rem; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-boxes"></i></div>
        <div class="brand-text">Asset<span>Pulse</span></div>
    </div>
    <div class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a href="#" class="nav-item"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="#" class="nav-item"><i class="bi bi-cloud-arrow-up"></i> Import Assets</a>
        <a href="#" class="nav-item active"><i class="bi bi-link-45deg"></i> Resource Importer</a>
        <a href="#" class="nav-item"><i class="bi bi-collection"></i> Asset Library</a>
        <div class="nav-label">Management</div>
        <a href="#" class="nav-item"><i class="bi bi-diagram-3"></i> CDN Endpoints</a>
        <a href="#" class="nav-item"><i class="bi bi-shield-check"></i> Access Control</a>
        <a href="#" class="nav-item"><i class="bi bi-bar-chart"></i> Analytics</a>
        <div class="nav-label">Settings</div>
        <a href="#" class="nav-item"><i class="bi bi-gear"></i> Configuration</a>
        <a href="#" class="nav-item"><i class="bi bi-people"></i> Team</a>
    </div>
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="avatar">AK</div>
            <div>
                <div class="user-name">Admin Kraze</div>
                <div class="user-role">Enterprise Plan</div>
            </div>
        </div>
    </div>
</div>

<!-- Main -->
<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#" style="color:#6366f1;text-decoration:none;">AssetPulse</a></li>
                    <li class="breadcrumb-item"><a href="#" style="color:#64748b;text-decoration:none;">CDN</a></li>
                    <li class="breadcrumb-item active">Resource Importer</li>
                </ol>
            </nav>
        </div>
        <div class="topbar-right">
            <div class="status-badge"><span class="dot"></span> All Systems Operational</div>
            <button class="btn-icon"><i class="bi bi-bell"></i></button>
            <button class="btn-icon"><i class="bi bi-question-circle"></i></button>
        </div>
    </div>

    <!-- Page Content -->
    <div class="page-content">
        <div class="page-header">
            <h1>Resource Importer</h1>
            <p>Import external assets and resources from URLs or local storage into your CDN.</p>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-top"><div class="stat-icon purple"><i class="bi bi-files"></i></div></div>
                <div class="stat-value"><?php echo count(array_diff(scandir('includes'), ['.', '..'])); ?></div>
                <div class="stat-label">Total Assets</div>
            </div>
            <div class="stat-card">
                <div class="stat-top"><div class="stat-icon blue"><i class="bi bi-globe"></i></div></div>
                <div class="stat-value">3</div>
                <div class="stat-label">CDN Endpoints</div>
            </div>
            <div class="stat-card">
                <div class="stat-top"><div class="stat-icon green"><i class="bi bi-check-circle"></i></div></div>
                <div class="stat-value"><?php echo $included_content ? '1' : '0'; ?></div>
                <div class="stat-label">Recent Imports</div>
            </div>
            <div class="stat-card">
                <div class="stat-top"><div class="stat-icon amber"><i class="bi bi-cloud-check"></i></div></div>
                <div class="stat-value">99.9%</div>
                <div class="stat-label">Uptime</div>
            </div>
        </div>

        <!-- Two Column -->
        <div class="two-col">
            <!-- Import Form -->
            <div class="panel">
                <div class="panel-header">
                    <h5><i class="bi bi-link-45deg"></i> Import Resource</h5>
                    <span style="font-size:0.75rem;color:#94a3b8;font-weight:500;">POST /api/import</span>
                </div>
                <div class="panel-body">
                    <?php echo $message; ?>
                    <form method="POST">
                        <input type="hidden" name="include_file" value="1">
                        <div class="form-group">
                            <label for="filename">Resource URL or Path <span class="optional">(required)</span></label>
                            <input type="text" class="form-control" id="filename" name="filename"
                                   placeholder="https://example.com/template.html or local-file.txt"
                                   value="<?php echo htmlspecialchars($user_input); ?>">
                            <div class="form-hint">Accepts remote URLs and local asset paths relative to the CDN root.</div>
                        </div>
                        <button type="submit" class="btn-primary"><i class="bi bi-cloud-download"></i> Import Resource</button>
                        <div class="quick-actions">
                            <span class="label">Quick Import:</span>
                            <button type="button" class="btn-outline" onclick="quickImport('welcome.txt')">welcome.txt</button>
                            <button type="button" class="btn-outline" onclick="quickImport('about.txt')">about.txt</button>
                            <button type="button" class="btn-outline" onclick="quickImport('config.txt')">config.txt</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Asset Library -->
            <div class="panel">
                <div class="panel-header">
                    <h5><i class="bi bi-collection"></i> Local Asset Library</h5>
                    <span style="font-size:0.72rem;color:#94a3b8;font-weight:500;"><?php echo count(array_diff(scandir('includes'), ['.', '..'])); ?> assets</span>
                </div>
                <div class="panel-body">
                    <ul class="asset-list">
                        <li>
                            <div class="asset-info">
                                <div class="asset-icon txt"><i class="bi bi-file-text"></i></div>
                                <div>
                                    <div class="asset-name">welcome.txt</div>
                                    <div class="asset-size">23 bytes — last imported</div>
                                </div>
                            </div>
                            <span class="asset-badge">text</span>
                        </li>
                        <li>
                            <div class="asset-info">
                                <div class="asset-icon txt"><i class="bi bi-file-text"></i></div>
                                <div>
                                    <div class="asset-name">about.txt</div>
                                    <div class="asset-size">29 bytes</div>
                                </div>
                            </div>
                            <span class="asset-badge">text</span>
                        </li>
                        <li>
                            <div class="asset-info">
                                <div class="asset-icon txt"><i class="bi bi-file-text"></i></div>
                                <div>
                                    <div class="asset-name">contact.txt</div>
                                    <div class="asset-size">41 bytes</div>
                                </div>
                            </div>
                            <span class="asset-badge">text</span>
                        </li>
                        <li>
                            <div class="asset-info">
                                <div class="asset-icon cfg"><i class="bi bi-gear"></i></div>
                                <div>
                                    <div class="asset-name">config.txt</div>
                                    <div class="asset-size">44 bytes</div>
                                </div>
                            </div>
                            <span class="asset-badge">config</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Output -->
        <?php if ($included_content): ?>
        <div style="margin-top:1.5rem;">
            <div class="panel" style="border-color:#1e293b;">
                <div class="panel-header" style="background:#1a2332;border-bottom-color:#1e293b;">
                    <h5 style="color:#e2e8f0;"><i class="bi bi-terminal" style="color:#10b981;"></i> Import Result</h5>
                    <span style="font-size:0.7rem;color:#64748b;font-weight:500;">200 OK — resource imported</span>
                </div>
                <div class="panel-body" style="padding:0;">
                    <div class="output-console" style="border:none;border-radius:0;">
                        <div class="console-body">
                            <div><span class="prompt-line"><span class="path">assetpulse@cdn</span>:~$</span> import --source <?php echo htmlspecialchars($user_input); ?></div>
                            <div class="output-text"><?php echo htmlspecialchars($included_content); ?></div>
                            <div style="margin-top:0.5rem;"><span class="prompt-line"><span class="path">assetpulse@cdn</span>:~$</span> <span style="color:#10b981;">✓ import complete (<?php echo strlen($included_content); ?> bytes)</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function quickImport(name) {
    document.getElementById('filename').value = name;
}
document.querySelector('form')?.addEventListener('submit', function() {
    const btn = this.querySelector('button[type="submit"]');
    if (btn) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:1rem;height:1rem;" role="status"></span> Importing...';
        btn.disabled = true;
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
