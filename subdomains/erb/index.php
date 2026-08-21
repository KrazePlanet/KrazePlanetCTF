<?php
session_start();

$default_subject = 'Invoice <%= invoice.number %> for <%= user.name %> (<%= account.plan %>)';
$default_body = '<!DOCTYPE html>
<html>
<head>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f1f5f9; padding: 24px; color: #0f172a; margin: 0; }
  .invoice-card { max-width: 650px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 10px 25px rgba(0,0,0,0.06); overflow: hidden; }
  .inv-header { background: #0f172a; color: #ffffff; padding: 28px 32px; display: flex; justify-content: space-between; align-items: center; }
  .inv-logo { font-size: 20px; font-weight: 800; letter-spacing: -0.5px; color: #38bdf8; }
  .inv-body { padding: 32px; }
  .grid-2 { display: flex; justify-content: space-between; margin-bottom: 24px; }
  .section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 4px; }
  .section-val { font-size: 14px; font-weight: 600; color: #1e293b; }
  .table-box { width: 100%; border-collapse: collapse; margin: 20px 0; }
  .table-box th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 10px 12px; text-align: left; font-size: 12px; color: #475569; }
  .table-box td { border-bottom: 1px solid #f1f5f9; padding: 12px; font-size: 13px; }
  .total-row { background: #f8fafc; font-weight: bold; font-size: 15px; }
  .status-badge { display: inline-block; background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }
  .inv-footer { background: #f8fafc; padding: 20px 32px; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; text-align: center; }
</style>
</head>
<body>
  <div class="invoice-card">
    <div class="inv-header">
      <div>
        <div class="inv-logo"><%= app_name %></div>
        <div style="font-size: 12px; opacity: 0.8; margin-top: 2px;">Cloud Billing & Infrastructure Receipt</div>
      </div>
      <div style="text-align: right;">
        <div style="font-size: 18px; font-weight: 700;"><%= invoice.number %></div>
        <div style="font-size: 12px; opacity: 0.8;">Generated: <%= server_time %></div>
      </div>
    </div>

    <div class="inv-body">
      <div class="grid-2">
        <div>
          <div class="section-label">Billed To</div>
          <div class="section-val"><%= user.name %></div>
          <div style="font-size: 13px; color: #64748b;"><%= user.email %></div>
          <div style="font-size: 13px; color: #64748b;"><%= user.role %> &bull; <%= user.tier %></div>
        </div>
        <div style="text-align: right;">
          <div class="section-label">Account & Status</div>
          <div class="section-val"><%= account.id %></div>
          <div style="font-size: 13px; color: #64748b;"><%= account.plan %></div>
          <div class="status-badge" style="margin-top: 6px;"><%= invoice.status %></div>
        </div>
      </div>

      <table class="table-box">
        <thead>
          <tr>
            <th>Description</th>
            <th>Billing Period</th>
            <th style="text-align: right;">Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Dedicated Ruby App Cluster (Nodes x4)</td>
            <td>August 2026</td>
            <td style="text-align: right;">$850.00</td>
          </tr>
          <tr>
            <td>Managed Redis & Enterprise Cache</td>
            <td>August 2026</td>
            <td style="text-align: right;">$250.00</td>
          </tr>
          <tr>
            <td>Automated Continuous Deployment Pipeline</td>
            <td>August 2026</td>
            <td style="text-align: right;">$150.00</td>
          </tr>
          <tr class="total-row">
            <td colspan="2">Total Amount Due (<%= invoice.due_date %>)</td>
            <td style="text-align: right; color: #0284c7;"><%= invoice.amount %></td>
          </tr>
        </tbody>
      </table>

      <p style="font-size: 13px; color: #64748b; margin-top: 20px;">
        Payment will be automatically processed via primary corporate card on file. For inquiries, reach out to <a href="mailto:billing@rubycloud.internal">billing@rubycloud.internal</a>.
      </p>
    </div>

    <div class="inv-footer">
      Thank you for building on DocuCraft &amp; RubyCloud Infrastructure.<br>
      DocuCraft SaaS Inc. &bull; 100 Montgomery St, Suite 1400, San Francisco, CA
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
    
    // Evaluate Subject with Ruby ERB
    $b64_sub = base64_encode($subject_input);
    $cmd_sub = sprintf(
        'env -u LD_LIBRARY_PATH ruby %s %s 2>&1',
        escapeshellarg(__DIR__ . '/evaluator.rb'),
        escapeshellarg($b64_sub)
    );
    $rendered_subject = shell_exec($cmd_sub);

    // Evaluate Body with Ruby ERB
    $b64_body = base64_encode($body_input);
    $cmd_body = sprintf(
        'env -u LD_LIBRARY_PATH ruby %s %s 2>&1',
        escapeshellarg(__DIR__ . '/evaluator.rb'),
        escapeshellarg($b64_body)
    );
    $rendered_body = shell_exec($cmd_body);
    
    $render_time = round((microtime(true) - $start) * 1000, 2);
} else {
    $b64_sub = base64_encode($default_subject);
    $rendered_subject = shell_exec(sprintf('env -u LD_LIBRARY_PATH ruby %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.rb'), escapeshellarg($b64_sub)));

    $b64_body = base64_encode($default_body);
    $rendered_body = shell_exec(sprintf('env -u LD_LIBRARY_PATH ruby %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.rb'), escapeshellarg($b64_body)));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocuCraft — Cloud Invoice & Template Engine (Ruby ERB)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #e11d48;
            --primary-dark: #be123c;
            --sidebar-bg: #111827;
            --card-border: #e5e7eb;
            --app-bg: #f3f4f6;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--app-bg);
            color: #1f2937;
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
            color: #9ca3af;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid #1f2937;
        }

        .sidebar-brand {
            padding: 20px 24px;
            font-size: 19px;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #1f2937;
        }

        .sidebar-brand i {
            color: #fb7185;
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
            color: #6b7280;
            padding: 10px 12px 6px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: #9ca3af;
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
            border-top: 1px solid #1f2937;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e11d48;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }

        /* Workspace */
        .app-workspace {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #f9fafb;
        }

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
            color: #111827;
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
            color: #374151;
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
            color: #111827;
            background: #ffffff;
            resize: none;
        }

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
            background: #f3f4f6;
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
            color: #6b7280;
            font-weight: 600;
        }

        .email-meta-value {
            color: #111827;
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
            background: #f3f4f6;
            color: #4b5563;
            border: 1px solid #e5e7eb;
            font-size: 11px;
            font-family: 'JetBrains Mono', monospace;
            padding: 2px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .tag-pill:hover {
            background: #e5e7eb;
            color: var(--primary);
        }
    </style>
</head>
<body>

    <!-- Left App Sidebar -->
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-file-earmark-code-fill"></i> DocuCraft
        </div>

        <div class="sidebar-menu">
            <div class="nav-section-title">Documents & Templates</div>
            <a href="index.php" class="sidebar-link active">
                <i class="bi bi-receipt-cutoff"></i> Invoice Studio
            </a>
            <a href="invoices.php" class="sidebar-link">
                <i class="bi bi-journal-text"></i> Invoices & Bills
            </a>
            <a href="customers.php" class="sidebar-link">
                <i class="bi bi-buildings"></i> Organizations
            </a>
            <a href="servers.php" class="sidebar-link">
                <i class="bi bi-hdd-stack"></i> Infrastructure
            </a>

            <div class="nav-section-title mt-3">Configuration</div>
            <a href="settings.php" class="sidebar-link">
                <i class="bi bi-sliders"></i> Engine Settings
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="user-avatar">MV</div>
            <div>
                <div class="text-white fw-bold small">Marcus Vance</div>
                <div class="text-secondary small" style="font-size: 11px;">DevOps Lead (Admin)</div>
            </div>
        </div>
    </aside>

    <!-- Main Workspace -->
    <main class="app-workspace">
        
        <!-- Top App Navigation -->
        <header class="app-topbar">
            <div class="topbar-title">
                <i class="bi bi-filetype-rb text-danger fs-5"></i>
                <h1>Invoice & Receipt Template Designer</h1>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 11px;">Ruby ERB Engine</span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2" onclick="document.getElementById('erbForm').reset();">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-2" onclick="alert('Sample PDF exported successfully!');">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <button type="submit" form="erbForm" class="btn btn-sm btn-danger d-flex align-items-center gap-2 px-3 fw-semibold">
                    <i class="bi bi-play-circle-fill"></i> Render & Preview
                </button>
            </div>
        </header>

        <!-- Studio Body -->
        <div class="studio-body">
            
            <!-- Left Form & Code Editor -->
            <div class="editor-panel">
                <form id="erbForm" method="POST" action="" style="display: flex; flex-direction: column; height: 100%;">
                    <div class="panel-header">
                        <h2><i class="bi bi-code-slash text-danger"></i> ERB Template Source & Bindings</h2>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <span class="small text-muted me-1" style="font-size: 11px;">Tags:</span>
                            <span class="tag-pill" onclick="insertTag('<%= user.name %>')">&lt;%= user.name %&gt;</span>
                            <span class="tag-pill" onclick="insertTag('<%= invoice.number %>')">&lt;%= invoice.number %&gt;</span>
                            <span class="tag-pill" onclick="insertTag('<%= invoice.amount %>')">&lt;%= invoice.amount %&gt;</span>
                        </div>
                    </div>

                    <!-- Subject / Title Bar -->
                    <div class="p-3 border-bottom bg-light">
                        <label class="form-label small fw-bold text-muted mb-1">Receipt Email Subject / Header</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-card-heading"></i></span>
                            <input type="text" name="subject" id="subjectInput" class="form-control font-monospace" value="<?= htmlspecialchars($subject_input) ?>" placeholder="Enter invoice subject with ERB tags...">
                        </div>
                    </div>

                    <!-- Template Code Area -->
                    <div class="d-flex flex-column flex-grow-1">
                        <textarea name="body" id="bodyInput" class="code-textarea" placeholder="Write HTML with dynamic ERB <%= ... %> tags..."><?= htmlspecialchars($body_input) ?></textarea>
                    </div>

                    <div class="p-3 border-top bg-light d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            <i class="bi bi-gem me-1 text-danger"></i> Backend Renderer: <strong>Ruby 3.2 ERB Processor</strong>
                        </div>
                        <button type="submit" class="btn btn-sm btn-danger px-3 fw-semibold">
                            <i class="bi bi-arrow-repeat me-1"></i> Re-Render Document
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Preview Panel -->
            <div class="preview-panel">
                <div class="panel-header">
                    <h2><i class="bi bi-eye text-danger"></i> Document Live Simulator</h2>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active"><i class="bi bi-file-earmark-text me-1"></i> HTML View</button>
                        <button type="button" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print View</button>
                    </div>
                </div>

                <div class="preview-viewport">
                    
                    <!-- Simulated Dispatch Headers -->
                    <div class="email-meta-header">
                        <div class="email-meta-row">
                            <div class="email-meta-label">Sender:</div>
                            <div class="email-meta-value">DocuCraft Automated Billing System &lt;billing@rubycloud.internal&gt;</div>
                        </div>
                        <div class="email-meta-row">
                            <div class="email-meta-label">Client:</div>
                            <div class="email-meta-value">Marcus Vance &lt;marcus.vance@rubycorp.internal&gt;</div>
                        </div>
                        <div class="email-meta-row">
                            <div class="email-meta-label">Subject:</div>
                            <div class="email-meta-value fw-bold text-danger"><?= htmlspecialchars($rendered_subject) ?></div>
                        </div>
                    </div>

                    <!-- Rendered HTML Document Canvas -->
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
