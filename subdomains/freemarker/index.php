<?php
session_start();

$default_subject = 'Exclusive Invitation for ${user.firstName} — Unlock Your ${user.loyaltyTier} Rewards';
$default_body = '<!DOCTYPE html>
<html>
<head>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b; }
  .email-card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
  .header { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 32px 24px; text-align: center; color: #ffffff; }
  .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
  .content { padding: 32px 28px; line-height: 1.6; font-size: 15px; }
  .user-badge { display: inline-block; background: #eff6ff; color: #2563eb; padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 13px; margin-bottom: 16px; }
  .promo-box { background: #f1f5f9; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 4px; margin: 20px 0; }
  .cta-btn { display: inline-block; background: #2563eb; color: #ffffff !important; padding: 12px 28px; border-radius: 8px; font-weight: 600; text-decoration: none; margin-top: 16px; }
  .footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
</style>
</head>
<body>
  <div class="email-card">
    <div class="header">
      <h1>${company.name}</h1>
      <p style="margin: 6px 0 0 0; opacity: 0.9; font-size: 14px;">${campaignName}</p>
    </div>
    <div class="content">
      <div class="user-badge">${user.loyaltyTier} Member</div>
      <p>Dear <strong>${user.name}</strong>,</p>
      <p>Thank you for being a valued client. We are pleased to inform you that your recent order (<strong>${order.id}</strong>) totaling <strong>${order.total}</strong> has been successfully processed.</p>
      
      <div class="promo-box">
        <strong>Your Personal VIP Promo Code:</strong><br>
        <span style="font-family: monospace; font-size: 18px; color: #1d4ed8; font-weight: bold;">${discountCode}</span>
        <p style="margin: 6px 0 0 0; font-size: 13px; color: #475569;">Enjoy 25% off across all enterprise services during ${currentYear}.</p>
      </div>

      <p>If you have any questions regarding your account status or points balance (${user.points} pts), please reach out to our VIP support desk at <a href="mailto:${company.supportEmail}">${company.supportEmail}</a>.</p>

      <center>
        <a href="${company.website}" class="cta-btn">Access Client Portal &rarr;</a>
      </center>
    </div>
    <div class="footer">
      &copy; ${currentYear} ${company.name}. All rights reserved.<br>
      ${company.address}
    </div>
  </div>
</body>
</html>';

$subject_input = $_POST['subject'] ?? $default_subject;
$body_input = $_POST['body'] ?? $default_body;

$rendered_subject = '';
$rendered_body = '';
$error_msg = '';
$render_time = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['preview'])) {
    $start = microtime(true);
    
    // Evaluate Subject
    $b64_sub = base64_encode($subject_input);
    $cmd_sub = sprintf(
        'env -u LD_LIBRARY_PATH /usr/bin/java -cp %s FreeMarkerEvaluator --base64 %s 2>&1',
        escapeshellarg(__DIR__ . '/freemarker.jar:' . __DIR__),
        escapeshellarg($b64_sub)
    );
    $rendered_subject = shell_exec($cmd_sub);
    
    // Evaluate Body
    $b64_body = base64_encode($body_input);
    $cmd_body = sprintf(
        'env -u LD_LIBRARY_PATH /usr/bin/java -cp %s FreeMarkerEvaluator --base64 %s 2>&1',
        escapeshellarg(__DIR__ . '/freemarker.jar:' . __DIR__),
        escapeshellarg($b64_body)
    );
    $rendered_body = shell_exec($cmd_body);
    
    $render_time = round((microtime(true) - $start) * 1000, 2);
} else {
    // Initial default render
    $b64_sub = base64_encode($default_subject);
    $cmd_sub = sprintf('env -u LD_LIBRARY_PATH /usr/bin/java -cp %s FreeMarkerEvaluator --base64 %s 2>&1', escapeshellarg(__DIR__ . '/freemarker.jar:' . __DIR__), escapeshellarg($b64_sub));
    $rendered_subject = shell_exec($cmd_sub);

    $b64_body = base64_encode($default_body);
    $cmd_body = sprintf('env -u LD_LIBRARY_PATH /usr/bin/java -cp %s FreeMarkerEvaluator --base64 %s 2>&1', escapeshellarg(__DIR__ . '/freemarker.jar:' . __DIR__), escapeshellarg($b64_body));
    $rendered_body = shell_exec($cmd_body);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PulseMail — Enterprise Campaign & Template Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --sidebar-bg: #0f172a;
            --topbar-bg: #ffffff;
            --app-bg: #f8fafc;
            --card-border: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--app-bg);
            color: #1e293b;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar */
        .app-sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: #94a3b8;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid #1e293b;
        }

        .sidebar-brand {
            padding: 20px 24px;
            font-size: 19px;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #1e293b;
        }

        .sidebar-brand i {
            color: #38bdf8;
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
            color: #475569;
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
            background: var(--primary);
            font-weight: 600;
        }

        .sidebar-link i {
            font-size: 17px;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #3b82f6;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }

        /* Main Workspace */
        .app-workspace {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #f1f5f9;
        }

        /* Top Bar */
        .app-topbar {
            height: 64px;
            background: #ffffff;
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
            color: #0f172a;
        }

        .template-status-badge {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 12px;
        }

        /* Split Workspace Area */
        .studio-body {
            flex-grow: 1;
            display: flex;
            overflow: hidden;
            padding: 20px;
            gap: 20px;
        }

        /* Left Editor Panel */
        .editor-panel {
            flex: 1;
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        }

        .panel-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fafafa;
        }

        .panel-header h2 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
            color: #334155;
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
            color: #1e293b;
            background: #ffffff;
            resize: none;
        }

        .code-textarea:focus {
            background: #ffffff;
        }

        /* Right Preview Panel */
        .preview-panel {
            flex: 1;
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        }

        .preview-viewport {
            flex-grow: 1;
            background: #f8fafc;
            overflow-y: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
        }

        .email-meta-header {
            background: #ffffff;
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
            width: 75px;
            color: #64748b;
            font-weight: 600;
        }

        .email-meta-value {
            color: #0f172a;
            font-weight: 500;
        }

        .rendered-container {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 8px;
            padding: 20px;
            flex-grow: 1;
            min-height: 400px;
        }

        .tag-pill {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            font-size: 11px;
            font-family: 'JetBrains Mono', monospace;
            padding: 2px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .tag-pill:hover {
            background: #e2e8f0;
            color: var(--primary);
        }
    </style>
</head>
<body>

    <!-- Left App Sidebar -->
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-envelope-paper-heart-fill"></i> PulseMail
        </div>

        <div class="sidebar-menu">
            <div class="nav-section-title">Campaigns & Marketing</div>
            <a href="campaigns.php" class="sidebar-link">
                <i class="bi bi-send"></i> Campaigns
            </a>
            <a href="index.php" class="sidebar-link active">
                <i class="bi bi-file-earmark-richtext"></i> Template Studio
            </a>
            <a href="audience.php" class="sidebar-link">
                <i class="bi bi-people"></i> Audiences & Lists
            </a>
            <a href="automations.php" class="sidebar-link">
                <i class="bi bi-lightning-charge"></i> Automations
            </a>

            <div class="nav-section-title mt-3">Insights & Configuration</div>
            <a href="analytics.php" class="sidebar-link">
                <i class="bi bi-bar-chart-line"></i> Analytics
            </a>
            <a href="settings.php" class="sidebar-link">
                <i class="bi bi-gear"></i> Platform Settings
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="user-avatar">AM</div>
            <div>
                <div class="text-white fw-bold small">Alex Morgan</div>
                <div class="text-secondary small" style="font-size: 11px;">Acme Enterprise (Admin)</div>
            </div>
        </div>
    </aside>

    <!-- Main Workspace -->
    <main class="app-workspace">
        
        <!-- Top App Navigation -->
        <header class="app-topbar">
            <div class="topbar-title">
                <i class="bi bi-layout-text-window-reverse text-primary fs-5"></i>
                <h1>Campaign Template Designer</h1>
                <span class="template-status-badge">Auto-Synced</span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2" onclick="document.getElementById('studioForm').reset();">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-2" onclick="alert('Test campaign dispatched to alex.morgan@apexcorp.internal');">
                    <i class="bi bi-send-check"></i> Send Test
                </button>
                <button type="submit" form="studioForm" class="btn btn-sm btn-primary d-flex align-items-center gap-2 px-3 fw-semibold">
                    <i class="bi bi-play-circle-fill"></i> Render & Preview
                </button>
            </div>
        </header>

        <!-- Studio Body -->
        <div class="studio-body">
            
            <!-- Left Form & Code Editor -->
            <div class="editor-panel">
                <form id="studioForm" method="POST" action="" style="display: flex; flex-direction: column; height: 100%;">
                    <div class="panel-header">
                        <h2><i class="bi bi-code-slash text-primary"></i> Dynamic Template & Merge Tags</h2>
                        <div class="d-flex align-items-center gap-1">
                            <span class="small text-muted me-1" style="font-size: 11px;">Merge Tags:</span>
                            <span class="tag-pill" onclick="insertTag('${user.name}')">\${user.name}</span>
                            <span class="tag-pill" onclick="insertTag('${order.id}')">\${order.id}</span>
                            <span class="tag-pill" onclick="insertTag('${order.total}')">\${order.total}</span>
                            <span class="tag-pill" onclick="insertTag('${discountCode}')">\${discountCode}</span>
                        </div>
                    </div>

                    <!-- Subject Input Bar -->
                    <div class="p-3 border-bottom bg-light">
                        <label class="form-label small fw-bold text-muted mb-1">Email Subject Line</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-chat-left-text"></i></span>
                            <input type="text" name="subject" id="subjectInput" class="form-control" value="<?= htmlspecialchars($subject_input) ?>" placeholder="Enter email subject with merge tags...">
                        </div>
                    </div>

                    <!-- Template Code Area -->
                    <div class="d-flex flex-column flex-grow-1">
                        <textarea name="body" id="bodyInput" class="code-textarea" placeholder="Write standard HTML with dynamic FreeMarker variables..."><?= htmlspecialchars($body_input) ?></textarea>
                    </div>

                    <div class="p-3 border-top bg-light d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            <i class="bi bi-server me-1"></i> Dynamic Server Engine: <strong>Java Template Processor</strong>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary px-3 fw-semibold">
                            <i class="bi bi-arrow-repeat me-1"></i> Update Live Preview
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Preview Panel -->
            <div class="preview-panel">
                <div class="panel-header">
                    <h2><i class="bi bi-eye text-primary"></i> Client Preview Simulator</h2>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active"><i class="bi bi-display me-1"></i> Desktop</button>
                        <button type="button" class="btn btn-outline-secondary"><i class="bi bi-phone me-1"></i> Mobile</button>
                    </div>
                </div>

                <div class="preview-viewport">
                    
                    <!-- Simulated Email Headers -->
                    <div class="email-meta-header">
                        <div class="email-meta-row">
                            <div class="email-meta-label">From:</div>
                            <div class="email-meta-value">PulseMail VIP Concierge &lt;notifications@pulsemail.io&gt;</div>
                        </div>
                        <div class="email-meta-row">
                            <div class="email-meta-label">To:</div>
                            <div class="email-meta-value">Alex Morgan &lt;alex.morgan@apexcorp.internal&gt;</div>
                        </div>
                        <div class="email-meta-row">
                            <div class="email-meta-label">Subject:</div>
                            <div class="email-meta-value fw-bold text-primary"><?= htmlspecialchars($rendered_subject) ?></div>
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
