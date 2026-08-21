<?php
session_start();

$default_subject = 'Security Audit Summary — {{project.name}} (Score: {{report.security_score}})';
$default_body = '<!DOCTYPE html>
<html>
<head>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0b132b; padding: 24px; color: #e0e1dd; margin: 0; }
  .report-card { max-width: 650px; margin: 0 auto; background: #1c2541; border-radius: 12px; border: 1px solid #3a506b; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
  .rep-header { background: linear-gradient(135deg, #0d1b2a 0%, #1c2541 100%); color: #ffffff; padding: 28px 32px; border-bottom: 2px solid #48e5c2; display: flex; justify-content: space-between; align-items: center; }
  .rep-logo { font-size: 20px; font-weight: 800; color: #48e5c2; letter-spacing: -0.5px; }
  .rep-body { padding: 32px; }
  .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin: 22px 0; }
  .kpi-card { background: #0d1b2a; border: 1px solid #3a506b; border-radius: 8px; padding: 14px; text-align: center; }
  .kpi-label { font-size: 11px; font-weight: 700; color: #8d99ae; text-transform: uppercase; margin-bottom: 4px; }
  .kpi-val { font-size: 20px; font-weight: 800; color: #48e5c2; }
  .detail-table { width: 100%; border-collapse: collapse; font-size: 13px; margin: 18px 0; }
  .detail-table td { padding: 9px 0; border-bottom: 1px solid #3a506b; }
  .detail-table td:first-child { color: #8d99ae; width: 150px; font-weight: 600; }
  .rep-btn { display: inline-block; background: #48e5c2; color: #0d1b2a !important; padding: 10px 24px; border-radius: 6px; font-weight: 700; text-decoration: none; font-size: 13px; margin-top: 14px; }
  .rep-footer { background: #0d1b2a; padding: 16px 32px; font-size: 11px; color: #8d99ae; border-top: 1px solid #3a506b; text-align: center; }
</style>
</head>
<body>
  <div class="report-card">
    <div class="rep-header">
      <div>
        <div class="rep-logo"><i class="bi bi-shield-check me-2"></i>{{company.name}}</div>
        <div style="font-size: 12px; opacity: 0.8; margin-top: 2px;">Automated Posture & Compliance Report</div>
      </div>
      <div style="text-align: right;">
        <div style="font-size: 15px; font-weight: 700;">{{report.id}}</div>
        <div style="font-size: 11px; opacity: 0.8;">{{report.generated_at}}</div>
      </div>
    </div>

    <div class="rep-body">
      <h3 style="margin: 0 0 6px 0; font-size: 19px; color: #ffffff;">Executive Security Assessment</h3>
      <p style="font-size: 13px; color: #8d99ae; margin: 0 0 16px 0;">Prepared for <strong>{{user.name}}</strong> ({{user.role}} &bull; {{user.department}}).</p>

      <div class="kpi-row">
        <div class="kpi-card">
          <div class="kpi-label">Security Posture</div>
          <div class="kpi-val">{{report.security_score}}</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Critical Findings</div>
          <div class="kpi-val" style="color: #5bc0be;">{{report.critical_vulns}}</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Compliance</div>
          <div class="kpi-val" style="font-size: 14px; margin-top: 4px;">PASS</div>
        </div>
      </div>

      <table class="detail-table">
        <tr>
          <td>Target System</td>
          <td><strong>{{project.name}}</strong> ({{project.id}})</td>
        </tr>
        <tr>
          <td>Build Version</td>
          <td><code>{{project.build_version}}</code></td>
        </tr>
        <tr>
          <td>Environment</td>
          <td>{{project.environment}}</td>
        </tr>
        <tr>
          <td>Compliance Standard</td>
          <td><span style="color: #48e5c2; font-weight: 600;">{{report.compliance_status}}</span></td>
        </tr>
      </table>

      <center>
        <a href="{{company.portal_url}}" class="rep-btn">Download Full PDF Audit Package &rarr;</a>
      </center>
    </div>

    <div class="rep-footer">
      Generated automatically by CloudGuard Jinja2 Compliance Engine &bull; Support: {{company.support_email}}
    </div>
  </div>
</body>
</html>';

$subject_input = $_POST['subject'] ?? $default_subject;
$body_input = $_POST['body'] ?? $default_body;

$rendered_subject = '';
$rendered_body = '';
$render_time = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['preview'])) {
    $start = microtime(true);
    
    // Evaluate Subject with Jinja2
    $b64_sub = base64_encode($subject_input);
    $cmd_sub = sprintf(
        'python3 %s %s 2>&1',
        escapeshellarg(__DIR__ . '/evaluator.py'),
        escapeshellarg($b64_sub)
    );
    $rendered_subject = shell_exec($cmd_sub);

    // Evaluate Body with Jinja2
    $b64_body = base64_encode($body_input);
    $cmd_body = sprintf(
        'python3 %s %s 2>&1',
        escapeshellarg(__DIR__ . '/evaluator.py'),
        escapeshellarg($b64_body)
    );
    $rendered_body = shell_exec($cmd_body);
    
    $render_time = round((microtime(true) - $start) * 1000, 2);
} else {
    $b64_sub = base64_encode($default_subject);
    $rendered_subject = shell_exec(sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64_sub)));

    $b64_body = base64_encode($default_body);
    $rendered_body = shell_exec(sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64_body)));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudGuard — Enterprise Compliance Report & Template Studio (Jinja2)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --sidebar-bg: #090f1d;
            --card-border: #1f2a40;
            --app-bg: #0d1527;
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
            color: #10b981;
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
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
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
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #10b981;
            color: #090f1d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 13px;
        }

        .app-workspace {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #090e1c;
        }

        .app-topbar {
            height: 64px;
            background: #0d1527;
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
            flex: 1;
            background: #111b30;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .panel-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0b1222;
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
            flex-grow: 1;
            border: none;
            outline: none;
            padding: 16px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.6;
            color: #e2e8f0;
            background: #111b30;
            resize: none;
        }

        .preview-panel {
            flex: 1;
            background: #111b30;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .preview-viewport {
            flex-grow: 1;
            background: #090e1c;
            overflow-y: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
        }

        .email-meta-header {
            background: #15223c;
            border: 1px solid var(--card-border);
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 18px;
            font-size: 13px;
        }

        .email-meta-row {
            display: flex;
            margin-bottom: 6px;
        }

        .email-meta-row:last-child {
            margin-bottom: 0;
        }

        .email-meta-label {
            width: 85px;
            color: #94a3b8;
            font-weight: 600;
        }

        .email-meta-value {
            color: #f8fafc;
            font-weight: 500;
        }

        .rendered-container {
            background: #15223c;
            border: 1px solid var(--card-border);
            border-radius: 8px;
            padding: 20px;
            flex-grow: 1;
            min-height: 400px;
        }

        .tag-pill {
            background: #0b1222;
            color: #10b981;
            border: 1px solid #1f2a40;
            font-size: 11px;
            font-family: 'JetBrains Mono', monospace;
            padding: 2px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .tag-pill:hover {
            background: #10b981;
            color: #090f1d;
        }
    </style>
</head>
<body>

    <!-- Left App Sidebar -->
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-shield-lock-fill"></i> CloudGuard
        </div>

        <div class="sidebar-menu">
            <div class="nav-section-title">Audits & Reports</div>
            <a href="index.php" class="sidebar-link active">
                <i class="bi bi-file-earmark-bar-graph"></i> Report Studio
            </a>
            <a href="projects.php" class="sidebar-link">
                <i class="bi bi-folder-check"></i> Scanned Projects
            </a>
            <a href="compliance.php" class="sidebar-link">
                <i class="bi bi-patch-check"></i> Compliance Badges
            </a>
            <a href="findings.php" class="sidebar-link">
                <i class="bi bi-bug"></i> Posture Findings
            </a>

            <div class="nav-section-title mt-3">Configuration</div>
            <a href="settings.php" class="sidebar-link">
                <i class="bi bi-sliders"></i> Engine Settings
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="user-avatar">SC</div>
            <div>
                <div class="text-white fw-bold small">Sophia Chen</div>
                <div class="text-secondary small" style="font-size: 11px;">Director of SecOps</div>
            </div>
        </div>
    </aside>

    <!-- Main Workspace -->
    <main class="app-workspace">
        
        <!-- Top App Navigation -->
        <header class="app-topbar">
            <div class="topbar-title">
                <i class="bi bi-file-earmark-code text-success fs-5"></i>
                <h1>Executive Security Audit Report Designer</h1>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 11px;">Python Jinja2</span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2" onclick="document.getElementById('jinjaForm').reset();">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="btn btn-sm btn-outline-success d-flex align-items-center gap-2" onclick="alert('Audit report bundle generated and archived!');">
                    <i class="bi bi-file-earmark-pdf"></i> Generate PDF Package
                </button>
                <button type="submit" form="jinjaForm" class="btn btn-sm btn-success text-dark d-flex align-items-center gap-2 px-3 fw-bold">
                    <i class="bi bi-play-circle-fill"></i> Render & Preview
                </button>
            </div>
        </header>

        <!-- Studio Body -->
        <div class="studio-body">
            
            <!-- Left Form & Code Editor -->
            <div class="editor-panel">
                <form id="jinjaForm" method="POST" action="" style="display: flex; flex-direction: column; height: 100%;">
                    <div class="panel-header">
                        <h2><i class="bi bi-code-square text-success"></i> Dynamic Jinja2 Source & Merge Tags</h2>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <span class="small text-muted me-1" style="font-size: 11px;">Tokens:</span>
                            <span class="tag-pill" onclick="insertTag('{{user.name}}')">{{user.name}}</span>
                            <span class="tag-pill" onclick="insertTag('{{project.name}}')">{{project.name}}</span>
                            <span class="tag-pill" onclick="insertTag('{{report.security_score}}')">{{report.security_score}}</span>
                        </div>
                    </div>

                    <!-- Subject / Header Bar -->
                    <div class="p-3 border-bottom border-dark bg-black bg-opacity-25">
                        <label class="form-label small fw-bold text-muted mb-1">Report Header & Notification Subject</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-dark text-muted border-secondary"><i class="bi bi-card-text"></i></span>
                            <input type="text" name="subject" id="subjectInput" class="form-control bg-dark text-white border-secondary font-monospace" value="<?= htmlspecialchars($subject_input) ?>" placeholder="Enter report subject with Jinja2 {{...}} tokens...">
                        </div>
                    </div>

                    <!-- Template Code Area -->
                    <div class="d-flex flex-column flex-grow-1">
                        <textarea name="body" id="bodyInput" class="code-textarea" placeholder="Write dynamic Jinja2 template code..."><?= htmlspecialchars($body_input) ?></textarea>
                    </div>

                    <div class="p-3 border-top border-dark bg-black bg-opacity-25 d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            <i class="bi bi-cpu me-1 text-success"></i> Engine: <strong>Python 3.12 (Jinja2 Renderer)</strong>
                        </div>
                        <button type="submit" class="btn btn-sm btn-success text-dark px-3 fw-bold">
                            <i class="bi bi-arrow-repeat me-1"></i> Re-Render Document
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Preview Panel -->
            <div class="preview-panel">
                <div class="panel-header">
                    <h2><i class="bi bi-eye text-success"></i> Audit Document Simulator</h2>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active"><i class="bi bi-file-earmark-richtext me-1"></i> Report View</button>
                        <button type="button" class="btn btn-outline-secondary"><i class="bi bi-envelope me-1"></i> Email View</button>
                    </div>
                </div>

                <div class="preview-viewport">
                    
                    <!-- Simulated Dispatch Headers -->
                    <div class="email-meta-header">
                        <div class="email-meta-row">
                            <div class="email-meta-label">Issuer:</div>
                            <div class="email-meta-value">CloudGuard Automated Compliance Daemon &lt;audit@cloudguard.io&gt;</div>
                        </div>
                        <div class="email-meta-row">
                            <div class="email-meta-label">Recipient:</div>
                            <div class="email-meta-value">Sophia Chen (Director of SecOps)</div>
                        </div>
                        <div class="email-meta-row">
                            <div class="email-meta-label">Subject:</div>
                            <div class="email-meta-value fw-bold text-success"><?= htmlspecialchars($rendered_subject) ?></div>
                        </div>
                    </div>

                    <!-- Rendered HTML Canvas -->
                    <div class="rendered-container">
                        <?= $rendered_body ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        function insertTag(tag) {
            const textarea = document.getElementById('bodyInput');
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
