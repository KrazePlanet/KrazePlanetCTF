<?php
// Lab 1: Basic SSTI Attack
// Vulnerability: Server-Side Template Injection without proper sanitization

session_start();

$message = '';
$template_output = '';
$user_input = '';

// Simulate template engine (vulnerable to SSTI)
function render_template($template, $data = []) {
    // This is a simplified template engine for demonstration
    // In real applications, this would be more complex
    
    // Basic template variables
    $template = str_replace('{{name}}', $data['name'] ?? '', $template);
    $template = str_replace('{{email}}', $data['email'] ?? '', $template);
    $template = str_replace('{{message}}', $data['message'] ?? '', $template);
    
    // Vulnerable: Direct evaluation of template expressions
    // This is where SSTI occurs
    if (preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches)) {
        foreach ($matches[1] as $expression) {
            $expression = trim($expression);
            
            // Vulnerable: Direct evaluation of any expression (SSTI)
            try {
                $result = eval("return $expression;");
                $template = str_replace('{{' . $expression . '}}', $result, $template);
            } catch (Exception $e) {
                $template = str_replace('{{' . $expression . '}}', 'ERROR', $template);
            }
        }
    }
    
    return $template;
}

// Handle template rendering
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['render_template'])) {
    $user_input = $_POST['template'] ?? '';
    $name = $_POST['name'] ?? 'User';
    $email = $_POST['email'] ?? 'user@example.com';
    $message = $_POST['message'] ?? 'Hello World';
    
    if ($user_input) {
        $data = [
            'name' => $name,
            'email' => $email,
            'message' => $message
        ];
        
        $template_output = render_template($user_input, $data);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SendStack - Email Campaign Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --primary-light: #dbeafe;
            --accent: #f59e0b;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --bg-body: #f3f4f6;
            --bg-card: #ffffff;
            --sidebar-bg: #ffffff;
            --sidebar-border: #f3f4f6;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 14px rgba(0,0,0,0.08);
            --radius: 10px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: var(--bg-body);
            color: var(--text-dark);
        }

        .app-layout { display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 22px 24px 28px;
            border-bottom: 1px solid var(--sidebar-border);
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }
        .brand-link i { font-size: 22px; }
        .brand-link:hover { color: var(--primary-dark); }

        .sidebar-nav {
            list-style: none;
            padding: 16px 0;
            flex: 1;
        }
        .sidebar-nav li { margin: 2px 0; }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 24px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        .nav-item:hover {
            color: var(--primary);
            background: var(--primary-light);
            border-left-color: var(--primary);
        }
        .nav-item.active {
            color: var(--primary);
            background: var(--primary-light);
            border-left-color: var(--primary);
            font-weight: 600;
        }
        .nav-item i { width: 18px; text-align: center; font-size: 15px; }

        .sidebar-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--sidebar-border);
            font-size: 12px;
            color: var(--text-muted);
        }

        /* MAIN CONTENT */
        .main-area { margin-left: 260px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* TOP HEADER */
        .topbar {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 14px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 64px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .breadcrumbs .crumb { color: var(--text-muted); }
        .breadcrumbs .sep { color: #d1d5db; font-size: 12px; }
        .breadcrumbs .current { color: var(--text-dark); font-weight: 600; }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notif-btn {
            position: relative;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 18px;
            cursor: pointer;
            padding: 4px;
        }
        .notif-btn:hover { color: var(--primary); }
        .notif-dot {
            position: absolute;
            top: 0; right: 0;
            width: 8px; height: 8px;
            background: #ef4444;
            border-radius: 50%;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: default;
        }
        .avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }
        .user-badge .name { font-size: 13px; font-weight: 500; color: var(--text-dark); }

        /* PAGE CONTENT */
        .page-body { flex: 1; padding: 36px 32px 24px; overflow-y: auto; }

        .page-heading { margin-bottom: 32px; }
        .page-heading h1 {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 6px;
        }
        .page-heading p {
            font-size: 14px;
            color: var(--text-muted);
            max-width: 580px;
            line-height: 1.5;
        }

        /* GRID */
        .preview-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
            align-items: start;
        }
        @media (max-width: 1100px) {
            .preview-grid { grid-template-columns: 1fr; }
        }

        /* CARD */
        .card-saas {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .card-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-label i { color: var(--primary); }

        /* FORM */
        .field { margin-bottom: 18px; }
        .field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 5px;
        }
        .field .form-control {
            width: 100%;
            padding: 9px 12px;
            font-size: 14px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--bg-card);
            color: var(--text-dark);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .field .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
            outline: none;
        }
        .field .form-control::placeholder { color: #9ca3af; }
        .field textarea.form-control {
            font-family: 'Monaco','Menlo','Ubuntu Mono',monospace;
            font-size: 13px;
            min-height: 130px;
            resize: vertical;
        }
        .field .hint { font-size: 12px; color: var(--text-muted); margin-top: 4px; }

        .var-hint {
            font-size: 12px;
            padding: 8px 12px;
            background: var(--primary-light);
            border-radius: 6px;
            color: var(--text-muted);
            margin-top: 6px;
        }
        .var-hint code {
            color: var(--primary);
            font-weight: 600;
            background: transparent;
            padding: 0 2px;
        }

        .btn-primary-custom {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 10px 22px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary-custom:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
        }

        /* EMAIL PREVIEW */
        .email-frame {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .email-frame-head {
            background: #fafbfc;
            border-bottom: 1px solid var(--border);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .email-frame-head i { color: var(--primary); font-size: 15px; }
        .email-frame-head span {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .email-frame-body {
            padding: 28px;
            min-height: 320px;
            background: #ffffff;
            font-size: 14px;
            line-height: 1.7;
            color: var(--text-dark);
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .preview-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 320px;
            color: var(--text-muted);
            text-align: center;
        }
        .preview-empty i { font-size: 44px; opacity: 0.25; margin-bottom: 14px; }
        .preview-empty p { font-size: 14px; }

        /* FOOTER */
        .app-footer {
            text-align: center;
            padding: 18px 32px;
            border-top: 1px solid var(--border);
            font-size: 12px;
            color: var(--text-muted);
            background: var(--bg-body);
        }

        /* SCROLLBAR */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    </style>
</head>
<body>
    <div class="app-layout">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <a href="#" class="brand-link">
                    <i class="fas fa-paper-plane"></i>SendStack
                </a>
            </div>
            <ul class="sidebar-nav">
                <li><a href="#" class="nav-item"><i class="fas fa-th-large"></i>Dashboard</a></li>
                <li><a href="#" class="nav-item"><i class="fas fa-envelope"></i>Campaigns</a></li>
                <li><a href="#" class="nav-item active"><i class="fas fa-file-alt"></i>Templates</a></li>
                <li><a href="#" class="nav-item"><i class="fas fa-chart-bar"></i>Analytics</a></li>
                <li><a href="#" class="nav-item"><i class="fas fa-cog"></i>Settings</a></li>
            </ul>
            <div class="sidebar-footer">SendStack v3.1.0</div>
        </aside>

        <!-- MAIN -->
        <div class="main-area">
            <!-- TOPBAR -->
            <header class="topbar">
                <div class="breadcrumbs">
                    <span class="crumb">Templates</span>
                    <span class="sep">/</span>
                    <span class="current">Personalization Preview</span>
                </div>
                <div class="topbar-right">
                    <button class="notif-btn" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notif-dot"></span>
                    </button>
                    <div class="user-badge">
                        <div class="avatar">AR</div>
                        <span class="name">Alex Rivera</span>
                    </div>
                </div>
            </header>

            <!-- PAGE BODY -->
            <div class="page-body">
                <div class="page-heading">
                    <h1>Personalization Preview</h1>
                    <p>Test how your email templates render with dynamic variables. Preview personalization before sending to ensure everything looks right.</p>
                </div>

                <div class="preview-grid">
                    <!-- LEFT: Form -->
                    <div class="card-saas">
                        <div class="card-label">
                            <i class="fas fa-sliders-h"></i> Template Variables
                        </div>

                        <form method="POST">
                            <input type="hidden" name="render_template" value="1">

                            <div class="field">
                                <label for="name">Recipient Name</label>
                                <input type="text" class="form-control" id="name" name="name"
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? 'Alex Rivera'); ?>"
                                       placeholder="e.g. Alex Rivera">
                                <div class="hint">Used for personalized greetings</div>
                            </div>

                            <div class="field">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? 'alex@sendstack.io'); ?>"
                                       placeholder="e.g. alex@sendstack.io">
                                <div class="hint">Shown in the email body</div>
                            </div>

                            <div class="field">
                                <label for="message">Custom Message</label>
                                <input type="text" class="form-control" id="message" name="message"
                                       value="<?php echo htmlspecialchars($_POST['message'] ?? 'Welcome aboard!'); ?>"
                                       placeholder="e.g. Welcome aboard!">
                                <div class="hint">A short custom message to include</div>
                            </div>

                            <div class="field">
                                <label for="template">Email Body</label>
                                <textarea class="form-control" id="template" name="template"
                                    placeholder="Write your email template here..."><?php echo htmlspecialchars($user_input ?: "Hi {{name}},\n\nThanks for joining SendStack! We're thrilled to have you.\n\nYour registered email is: {{email}}\n\n{{message}}\n\nBest,\nThe SendStack Team"); ?></textarea>
                                <div class="var-hint">
                                    <strong>Available:</strong>
                                    <code>{{name}}</code> &nbsp; <code>{{email}}</code> &nbsp; <code>{{message}}</code>
                                </div>
                            </div>

                            <button type="submit" class="btn-primary-custom">
                                <i class="fas fa-play"></i> Generate Preview
                            </button>
                        </form>
                    </div>

                    <!-- RIGHT: Preview -->
                    <div class="email-frame">
                        <div class="email-frame-head">
                            <i class="fas fa-envelope-open-text"></i>
                            <span>Email Preview</span>
                        </div>
                        <div class="email-frame-body">
                            <?php if ($template_output): ?>
                                <?php echo htmlspecialchars($template_output); ?>
                            <?php else: ?>
                                <div class="preview-empty">
                                    <i class="fas fa-eye"></i>
                                    <p>Fill in the template and click <strong>Generate Preview</strong> to see the result.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="app-footer">
                &copy; 2026 SendStack. All rights reserved.
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
