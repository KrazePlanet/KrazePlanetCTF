<?php
session_start();
// Resilient PHPMailer & Composer Loader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../codeshackio/vendor/autoload.php')) {
    require_once __DIR__ . '/../codeshackio/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/PHPMailer/PHPMailer.php')) {
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
} elseif (file_exists('/opt/lampp/htdocs/PHPMailer/PHPMailer.php')) {
    require_once '/opt/lampp/htdocs/PHPMailer/Exception.php';
    require_once '/opt/lampp/htdocs/PHPMailer/PHPMailer.php';
    require_once '/opt/lampp/htdocs/PHPMailer/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../config/mail.php';

$message = '';
$template_output = '';
$user_input = '';
$mailSent = false;
$mailError = '';

// Simulate template engine (vulnerable to SSTI)
function render_template($template, $data = []) {
    // Basic template variables
    $template = str_replace('{{name}}', $data['name'] ?? '', $template);
    $template = str_replace('{{email}}', $data['email'] ?? '', $template);
    $template = str_replace('{{message}}', $data['message'] ?? '', $template);
    
    // Vulnerable: Direct evaluation of template expressions (SSTI)
    if (preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches)) {
        foreach ($matches[1] as $expression) {
            $expression = trim($expression);
            try {
                $result = eval("return $expression;");
                $template = str_replace('{{' . $expression . '}}', $result, $template);
            } catch (Throwable $e) {
                $template = str_replace('{{' . $expression . '}}', 'ERROR', $template);
            }
        }
    }
    
    return $template;
}

// Handle template rendering and email sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['render_template'])) {
    $user_input = $_POST['template'] ?? '';
    $name = $_POST['name'] ?? 'User';
    $email = trim($_POST['email'] ?? '');
    $message = $_POST['message'] ?? 'Hello World';
    
    if ($user_input) {
        $data = [
            'name' => $name,
            'email' => $email,
            'message' => $message
        ];
        
        $template_output = render_template($user_input, $data);

        if ($email) {
            $mail = new PHPMailer(true);
            try {
                configureKrazeMailer($mail, 'noreply@krazeplanet.com', 'SendStack');
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = 'Your SendStack Personalized Campaign Message';

                $emailBody = '
                <!DOCTYPE html>
                <html>
                <head><meta charset="utf-8"></head>
                <body style="font-family:Arial,sans-serif;background:#f3f4f6;padding:20px;margin:0;">
                <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:10px;border:1px solid #e5e7eb;overflow:hidden;box-shadow:0 4px 14px rgba(0,0,0,0.08);">
                  <div style="background:#2563eb;padding:20px;text-align:center;">
                    <h2 style="color:#ffffff;margin:0;font-size:20px;">SendStack</h2>
                  </div>
                  <div style="padding:28px 24px;color:#1f2937;font-size:14px;line-height:1.7;">
                    ' . nl2br($template_output) . '
                  </div>
                </div>
                </body>
                </html>
                ';

                $mail->Body = $emailBody;
                $mail->send();
                $mailSent = true;
            } catch (Exception $e) {
                $mailError = 'Mail Error: ' . $mail->ErrorInfo;
            }
        }
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
        }
        .sidebar-brand {
            padding: 24px 24px 20px;
            border-bottom: 1px solid var(--sidebar-border);
        }
        .brand-link {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-nav { list-style: none; padding: 16px 12px; }
        .sidebar-nav li { margin-bottom: 4px; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.15s;
        }
        .nav-item:hover { background: var(--bg-body); color: var(--text-dark); }
        .nav-item.active { background: var(--primary-light); color: var(--primary); font-weight: 600; }
        .sidebar-footer {
            padding: 16px 24px;
            font-size: 12px;
            color: var(--text-muted);
            border-top: 1px solid var(--sidebar-border);
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        /* MAIN AREA */
        .main-area {
            flex: 1;
            margin-left: 260px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* TOPBAR */
        .topbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;

        }
        .breadcrumbs { font-size: 13px; color: var(--text-muted); }
        .breadcrumbs .crumb { color: var(--text-muted); }
        .breadcrumbs .sep { margin: 0 8px; }
        .breadcrumbs .current { color: var(--text-dark); font-weight: 600; }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .notif-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 16px;
            cursor: pointer;
            position: relative;
        }
        .notif-dot {
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            position: absolute;
            top: 0;
            right: 0;
        }
        .user-badge { display: flex; align-items: center; gap: 10px; }
        .user-badge .avatar {
            width: 32px;
            height: 32px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }
        .user-badge .name { font-size: 14px; font-weight: 600; }

        /* PAGE BODY */
        .page-body { padding: 32px; flex: 1; }
        .page-heading { margin-bottom: 28px; }
        .page-heading h1 { font-size: 24px; font-weight: 800; color: var(--text-dark); margin-bottom: 6px; }
        .page-heading p { font-size: 14px; color: var(--text-muted); max-width: 600px; }

        /* LAYOUT GRID */
        .preview-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 992px) { .preview-grid { grid-template-columns: 1fr; } }

        /* CARDS */
        .card-saas {
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            padding: 28px;
        }
        .card-label {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* FORMS */
        .field { margin-bottom: 18px; }
        .field label { font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 6px; display: block; }
        .form-control {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.15s;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }
        .hint { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
        textarea.form-control { min-height: 140px; font-family: monospace; font-size: 13px; }
        .var-hint {
            background: var(--primary-light);
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            margin-top: 8px;
            color: var(--primary-dark);
        }
        .var-hint code { background: white; padding: 2px 6px; border-radius: 4px; color: var(--primary-dark); }

        .btn-primary-custom {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary-custom:hover { background: var(--primary-dark); }

        /* EMAIL FRAME */
        .email-frame {
            background: #ffffff;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .email-frame-head {
            background: #f9fafb;
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
        }

        /* FOOTER */
        .app-footer {
            text-align: center;
            padding: 18px 32px;
            border-top: 1px solid var(--border);
            font-size: 12px;
            color: var(--text-muted);
            background: var(--bg-body);
        }
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
                    <span class="current">Personalization Campaign</span>
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
                    <h1>Personalization Campaign</h1>
                    <p>Send personalized email templates with dynamic variables directly to recipients via SMTP.</p>
                </div>

                <div class="preview-grid">
                    <!-- LEFT: Form -->
                    <div class="card-saas">
                        <div class="card-label">
                            <i class="fas fa-sliders-h"></i> Template Variables & Content
                        </div>

                        <form method="POST">
                            <input type="hidden" name="render_template" value="1">

                            <div class="field">
                                <label for="name">Recipient Name</label>
                                <input type="text" class="form-control" id="name" name="name"
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? 'Alex Rivera'); ?>"
                                       placeholder="e.g. Alex Rivera" required>
                                <div class="hint">Used for personalized greetings</div>
                            </div>

                            <div class="field">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? 'alex@sendstack.io'); ?>"
                                       placeholder="e.g. alex@sendstack.io" required>
                                <div class="hint">Email will be sent to this recipient via SMTP</div>
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
                                    placeholder="Write your email template here..."><?php echo htmlspecialchars($user_input ?: "Hi {{name}},

Thanks for joining SendStack! We're thrilled to have you.

Your registered email is: {{email}}

{{message}}

Best,
The SendStack Team"); ?></textarea>
                                <div class="var-hint">
                                    <strong>Available:</strong>
                                    <code>{{name}}</code> &nbsp; <code>{{email}}</code> &nbsp; <code>{{message}}</code>
                                </div>
                            </div>

                            <button type="submit" class="btn-primary-custom">
                                <i class="fas fa-paper-plane"></i> Send Email Campaign
                            </button>
                        </form>
                    </div>

                    <!-- RIGHT: Status Result -->
                    <div class="email-frame">
                        <div class="email-frame-head">
                            <i class="fas fa-paper-plane"></i>
                            <span>Email Delivery Status</span>
                        </div>
                        <div class="email-frame-body">
                            <?php if ($mailSent): ?>
                                <div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:16px;border-radius:8px;margin-bottom:16px;">
                                    <h5 style="margin-bottom:6px;font-weight:700;"><i class="fas fa-check-circle"></i> Email Sent Successfully!</h5>
                                    <p style="margin:0;font-size:13px;">Campaign email sent via SMTP to <strong><?php echo htmlspecialchars($email); ?></strong>.</p>
                                </div>
                                <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;background:#f9fafb;">
                                    <strong style="display:block;margin-bottom:8px;font-size:12px;color:#6b7280;text-transform:uppercase;">Evaluated Email Content Delivered:</strong>
                                    <div style="font-family:monospace;white-space:pre-wrap;font-size:13px;color:#1f2937;"><?php echo htmlspecialchars($template_output); ?></div>
                                </div>
                            <?php elseif ($mailError): ?>
                                <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:16px;border-radius:8px;">
                                    <h5 style="margin-bottom:6px;font-weight:700;"><i class="fas fa-exclamation-triangle"></i> Delivery Warning</h5>
                                    <p style="margin:0;font-size:13px;"><?php echo htmlspecialchars($mailError); ?></p>
                                </div>
                            <?php else: ?>
                                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:280px;color:#6b7280;text-align:center;">
                                    <i class="fas fa-paper-plane" style="font-size:44px;opacity:0.25;margin-bottom:14px;"></i>
                                    <p>Fill in the campaign details and click <strong>Send Email Campaign</strong> to send the email via SMTP.</p>
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
