<?php
session_start();

$default_display_name = 'Sophia Chen';
$default_handle = 'sophia_sec';
$default_job_title = 'Principal Security Architect';
$default_org = 'Nexus Cyber Systems';
$default_location = 'Seattle, WA';
$default_website = 'https://sophia-chen.dev';
$default_status = '⚡ Securing cloud infrastructure & optimizing {{user.company}} telemetry';
$default_bio = '<div class="bio-intro">
  <p>👋 Hello! I am <strong>{{user.name}}</strong>, working as <strong>{{user.role}}</strong> at <strong>{{user.company}}</strong> based in {{user.location}}.</p>
  <p>Specializing in cloud workload defense, container escape mitigation, and automated policy enforcement. Currently holding <strong>{{user.reputation}}</strong> community reputation score.</p>
  <div class="bio-skills mt-3">
    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50 me-1">Python</span>
    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50 me-1">Kubernetes</span>
    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50 me-1">Cloud Security</span>
    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50">DevSecOps</span>
  </div>
</div>';

$display_name = $_POST['display_name'] ?? $default_display_name;
$handle = $_POST['handle'] ?? $default_handle;
$job_title = $_POST['job_title'] ?? $default_job_title;
$org = $_POST['org'] ?? $default_org;
$location = $_POST['location'] ?? $default_location;
$website = $_POST['website'] ?? $default_website;
$status_input = $_POST['status_message'] ?? $default_status;
$bio_input = $_POST['bio'] ?? $default_bio;

$rendered_status = '';
$rendered_bio = '';
$render_time = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['preview'])) {
    $start = microtime(true);
    
    // Evaluate Status with Jinja2
    $b64_status = base64_encode($status_input);
    $cmd_status = sprintf(
        'python3 %s %s 2>&1',
        escapeshellarg(__DIR__ . '/evaluator.py'),
        escapeshellarg($b64_status)
    );
    $rendered_status = shell_exec($cmd_status);

    // Evaluate Bio with Jinja2
    $b64_bio = base64_encode($bio_input);
    $cmd_bio = sprintf(
        'python3 %s %s 2>&1',
        escapeshellarg(__DIR__ . '/evaluator.py'),
        escapeshellarg($b64_bio)
    );
    $rendered_bio = shell_exec($cmd_bio);
    
    $render_time = round((microtime(true) - $start) * 1000, 2);
} else {
    $b64_status = base64_encode($default_status);
    $rendered_status = shell_exec(sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64_status)));

    $b64_bio = base64_encode($default_bio);
    $rendered_bio = shell_exec(sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64_bio)));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevSpace Profile Studio — User Customization & Bio SSTI (Jinja2)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #06b6d4;
            --primary-dark: #0891b2;
            --accent: #10b981;
            --sidebar-bg: #0b1120;
            --card-border: #1e293b;
            --app-bg: #070b14;
            --surface-bg: #0f172a;
            --surface-highlight: #1e293b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--app-bg);
            color: #f1f5f9;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .app-sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: #94a3b8;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid var(--card-border);
        }

        .sidebar-brand {
            padding: 20px 24px;
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--card-border);
        }

        .sidebar-brand i {
            color: var(--primary);
            font-size: 22px;
        }

        .sidebar-menu {
            padding: 20px 14px;
            flex-grow: 1;
            overflow-y: auto;
        }

        .nav-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            padding: 10px 12px 6px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
            transition: all 0.15s;
        }

        .sidebar-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar-link.active {
            color: #ffffff;
            background: rgba(6, 182, 212, 0.15);
            border: 1px solid rgba(6, 182, 212, 0.3);
            font-weight: 600;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
            box-shadow: 0 0 12px rgba(6, 182, 212, 0.35);
        }

        .app-workspace {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #070b14;
        }

        .app-topbar {
            height: 64px;
            background: #0b1120;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            flex-shrink: 0;
        }

        .topbar-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-title h1 {
            font-size: 17px;
            font-weight: 700;
            margin: 0;
            color: #ffffff;
        }

        .studio-body {
            flex-grow: 1;
            display: flex;
            overflow: hidden;
            padding: 20px;
            gap: 20px;
        }

        .editor-panel {
            flex: 1.1;
            background: var(--surface-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.35);
        }

        .preview-panel {
            flex: 1;
            background: var(--surface-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.35);
        }

        .panel-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #090e1c;
            flex-shrink: 0;
        }

        .panel-header h2 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .code-textarea {
            width: 100%;
            border: 1px solid var(--card-border);
            border-radius: 8px;
            outline: none;
            padding: 14px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.6;
            color: #e2e8f0;
            background: #080d1a;
            resize: vertical;
            min-height: 160px;
            transition: border-color 0.2s;
        }

        .code-textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.2);
        }

        .preview-viewport {
            flex-grow: 1;
            background: #060912;
            overflow-y: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Profile Card Styling in Live View */
        .profile-hero-card {
            background: #0d1527;
            border: 1px solid #1e293b;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }

        .profile-banner {
            height: 110px;
            background: linear-gradient(135deg, #0e7490 0%, #1e1b4b 50%, #042f2e 100%);
            position: relative;
        }

        .profile-badge-overlay {
            position: absolute;
            top: 12px;
            right: 16px;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 11px;
            font-weight: 600;
            color: #38bdf8;
        }

        .profile-card-content {
            padding: 0 24px 24px;
            position: relative;
        }

        .profile-avatar-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: -45px;
            margin-bottom: 14px;
        }

        .profile-main-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
            border: 4px solid #0d1527;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(0,0,0,0.5);
        }

        .profile-status-pill {
            background: #111e38;
            border: 1px solid #223554;
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 13px;
            color: #cbd5e1;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            max-width: 100%;
            word-break: break-word;
        }

        .profile-bio-box {
            background: #090e1c;
            border: 1px solid #1c273d;
            border-radius: 10px;
            padding: 16px;
            font-size: 13.5px;
            line-height: 1.65;
            color: #e2e8f0;
            word-break: break-word;
        }

        .tag-pill {
            background: #0d1527;
            color: var(--primary);
            border: 1px solid #1e293b;
            font-size: 11px;
            font-family: 'JetBrains Mono', monospace;
            padding: 2px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .tag-pill:hover {
            background: var(--primary);
            color: #070b14;
        }

        .stat-badge {
            background: #0a101f;
            border: 1px solid #1e2a40;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
        }

        .stat-num {
            font-size: 16px;
            font-weight: 800;
            color: #38bdf8;
        }

        .stat-lbl {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- Left App Sidebar -->
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-person-badge-fill"></i> DevSpace
        </div>

        <div class="sidebar-menu">
            <div class="nav-section-title">Personalization</div>
            <a href="index.php" class="sidebar-link active">
                <i class="bi bi-palette2"></i> Profile & Bio Studio
            </a>
            <a href="profile.php" class="sidebar-link">
                <i class="bi bi-person-circle"></i> Public Profile View
            </a>
            <a href="projects.php" class="sidebar-link">
                <i class="bi bi-code-slash"></i> Public Repositories
            </a>
            <a href="compliance.php" class="sidebar-link">
                <i class="bi bi-award"></i> Badges & Credentials
            </a>

            <div class="nav-section-title mt-3">Account</div>
            <a href="settings.php" class="sidebar-link">
                <i class="bi bi-gear-wide-connected"></i> Account Security
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="user-avatar">SC</div>
            <div style="overflow: hidden;">
                <div class="text-white fw-bold small text-truncate">Sophia Chen</div>
                <div class="text-secondary small text-truncate" style="font-size: 11px;">@sophia_sec</div>
            </div>
        </div>
    </aside>

    <!-- Main Workspace -->
    <main class="app-workspace">
        
        <!-- Top App Navigation -->
        <header class="app-topbar">
            <div class="topbar-title">
                <i class="bi bi-person-gear text-info fs-5"></i>
                <h1>Account Settings & Public Profile Customizer</h1>
                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25" style="font-size: 11px;">Python Jinja2</span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="profile.php" target="_blank" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-up-right"></i> Open Public URL
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2" onclick="document.getElementById('profileForm').reset();">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="submit" form="profileForm" class="btn btn-sm btn-info text-dark d-flex align-items-center gap-2 px-3 fw-bold">
                    <i class="bi bi-check-circle-fill"></i> Save & Compile Profile
                </button>
            </div>
        </header>

        <!-- Studio Body -->
        <div class="studio-body">
            
            <!-- Left Form: Profile Settings -->
            <div class="editor-panel">
                <div class="panel-header">
                    <h2><i class="bi bi-sliders2 text-info"></i> Profile Personalization Settings</h2>
                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <span class="small text-muted me-1" style="font-size: 11px;">Template Tags:</span>
                        <span class="tag-pill" onclick="insertBioTag('{{user.name}}')">{{user.name}}</span>
                        <span class="tag-pill" onclick="insertBioTag('{{user.role}}')">{{user.role}}</span>
                        <span class="tag-pill" onclick="insertBioTag('{{user.company}}')">{{user.company}}</span>
                        <span class="tag-pill" onclick="insertBioTag('{{user.reputation}}')">{{user.reputation}}</span>
                    </div>
                </div>

                <form id="profileForm" method="POST" action="" class="p-4 d-flex flex-column gap-3">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">Display Name</label>
                            <input type="text" name="display_name" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($display_name) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">Username / Handle</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark text-muted border-secondary">@</span>
                                <input type="text" name="handle" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($handle) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">Role / Job Title</label>
                            <input type="text" name="job_title" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($job_title) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">Organization / Company</label>
                            <input type="text" name="org" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($org) ?>">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">Location</label>
                            <input type="text" name="location" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($location) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">Personal Website / Portfolio</label>
                            <input type="text" name="website" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($website) ?>">
                        </div>
                    </div>

                    <!-- Custom Status Message (Dynamic Jinja2 Template Input) -->
                    <div class="border border-info border-opacity-25 rounded-3 p-3 bg-info bg-opacity-10">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-info mb-0">
                                <i class="bi bi-chat-heart-fill me-1"></i> Custom Status Greeting Banner
                            </label>
                            <span class="badge bg-dark text-info border border-info border-opacity-25 font-monospace" style="font-size: 10px;">Jinja2 Evaluated</span>
                        </div>
                        <div class="small text-muted mb-2" style="font-size: 11px;">
                            Displayed right underneath your avatar on public cards. Supports dynamic Jinja2 expressions like <code>{{user.skills}}</code>.
                        </div>
                        <input type="text" name="status_message" id="statusInput" class="form-control bg-dark text-white border-secondary font-monospace" value="<?= htmlspecialchars($status_input) ?>" placeholder="e.g. 🚀 Building next-gen tools with {{user.role}}">
                    </div>

                    <!-- About Me / Dynamic Profile Bio (Dynamic Jinja2 Template Input) -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-muted mb-0">
                                <i class="bi bi-file-earmark-person me-1"></i> Public Bio / About Me (Dynamic Markup & Template)
                            </label>
                            <span class="badge bg-dark text-muted border border-secondary font-monospace" style="font-size: 10px;">Jinja2 Rendered</span>
                        </div>
                        <div class="small text-muted mb-2" style="font-size: 11px;">
                            HTML & Jinja2 template formatting supported. Personalize your bio with profile context variables.
                        </div>
                        <textarea name="bio" id="bioInput" class="code-textarea" placeholder="Write dynamic profile bio..."><?= htmlspecialchars($bio_input) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-25">
                        <div class="small text-muted">
                            <i class="bi bi-cpu me-1 text-info"></i> Backend: <strong>Python 3.12 (Jinja2 Renderer)</strong>
                        </div>
                        <button type="submit" class="btn btn-sm btn-info text-dark px-3 fw-bold">
                            <i class="bi bi-save2 me-1"></i> Update Public Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Panel: Live Public Profile Preview -->
            <div class="preview-panel">
                <div class="panel-header">
                    <h2><i class="bi bi-eye text-info"></i> Live Public Profile Card Preview</h2>
                    <?php if ($render_time !== null): ?>
                        <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 font-monospace" style="font-size: 11px;">
                            <i class="bi bi-lightning-charge-fill me-1"></i> Compiled in <?= $render_time ?> ms
                        </span>
                    <?php endif; ?>
                </div>

                <div class="preview-viewport">
                    
                    <!-- The Rendered Profile Card -->
                    <div class="profile-hero-card">
                        <div class="profile-banner">
                            <div class="profile-badge-overlay">
                                <i class="bi bi-patch-check-fill text-info me-1"></i> Verified Pro Member
                            </div>
                        </div>

                        <div class="profile-card-content">
                            <div class="profile-avatar-row">
                                <div class="profile-main-avatar">
                                    SC
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-info px-3" disabled>
                                        <i class="bi bi-person-plus me-1"></i> Follow
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <h3 class="fw-bold mb-0 text-white fs-5"><?= htmlspecialchars($display_name) ?></h3>
                                <div class="text-secondary small font-monospace">@<?= htmlspecialchars($handle) ?> &bull; <?= htmlspecialchars($job_title) ?> at <?= htmlspecialchars($org) ?></div>
                            </div>

                            <!-- Rendered Status Message -->
                            <div class="profile-status-pill">
                                <span><?= $rendered_status ?></span>
                            </div>

                            <!-- Stat Grid -->
                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <div class="stat-badge">
                                        <div class="stat-num">1,420</div>
                                        <div class="stat-lbl">Followers</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-badge">
                                        <div class="stat-num">8,950</div>
                                        <div class="stat-lbl">Reputation</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-badge">
                                        <div class="stat-num">98.4%</div>
                                        <div class="stat-lbl">Acceptance</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rendered Bio / About Me -->
                            <div class="profile-bio-box">
                                <div class="small text-uppercase fw-bold text-muted mb-2" style="font-size: 11px; letter-spacing: 0.5px;">
                                    <i class="bi bi-person-lines-fill me-1"></i> About Sophia
                                </div>
                                <div class="rendered-bio-body">
                                    <?= $rendered_bio ?>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-4 mt-3 text-secondary small">
                                <div><i class="bi bi-geo-alt me-1 text-muted"></i> <?= htmlspecialchars($location) ?></div>
                                <div><i class="bi bi-link-45deg me-1 text-muted"></i> <a href="#" class="text-info text-decoration-none"><?= htmlspecialchars($website) ?></a></div>
                                <div><i class="bi bi-calendar3 me-1 text-muted"></i> Joined October 2023</div>
                            </div>
                        </div>
                    </div>

                    <!-- Testing / Educational Context Callout -->
                    <div class="card bg-dark bg-opacity-50 border-secondary border-opacity-50 p-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-lightbulb text-warning"></i>
                            <span class="small fw-bold text-white">How SSTI occurs here:</span>
                        </div>
                        <p class="small text-muted mb-2">
                            When users customize their <strong>Status Greeting</strong> or <strong>Public Bio</strong>, some platforms inadvertently compile the string through a server-side template engine (Jinja2) rather than treating it as static text.
                        </p>
                        <div class="small font-monospace text-info">
                            Try testing: <code>{{7*7}}</code> or <code>{{user.skills}}</code> or Python introspection payloads!
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <script>
        function insertBioTag(tag) {
            const textarea = document.getElementById('bioInput');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            textarea.value = text.substring(0, start) + tag + text.substring(end);
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = start + tag.length;
        }
    </script>
</body>
</html>
