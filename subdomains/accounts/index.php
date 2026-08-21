<?php
// SSTI Lab 803 — Uber Profile Name Jinja2 SSTI
// HackerOne Report #125980 (Uber — $10,000 bounty)
// Vulnerable parameter: name (POST update_name) — rendered directly in Jinja2-style email template
// Payload: {{ '7'*7 }} → name becomes '7777777' in account update email
// Flag via: {{ config['FLAG'] }} or {{ config.FLAG }}

session_start();
require_once __DIR__ . '/../codeshackio/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_uber_email($recipient_email, $recipient_name, $subject, $body_text) {
    if (!$recipient_email) return false;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'mailpit';
        $mail->SMTPAuth   = false;
                $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
        $mail->Port       = 1025;
        $mail->setFrom('noreply@krazeplanet.com', 'Uber');
        $mail->addAddress($recipient_email, $recipient_name);
        $mail->isHTML(true);
        $mail->Subject = $subject;

        $htmlMailBody = '
        <!DOCTYPE html>
        <html>
        <head><meta charset="utf-8"></head>
        <body style="font-family:Arial,sans-serif;background:#f6f6f6;padding:20px;margin:0;">
        <div style="max-width:540px;margin:0 auto;background:#ffffff;border-radius:8px;border:1px solid #e0e0e0;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
          <div style="background:#000000;padding:20px;text-align:center;">
            <h2 style="color:#ffffff;margin:0;font-size:22px;font-weight:800;letter-spacing:-0.5px;">Uber</h2>
          </div>
          <div style="padding:28px 24px;color:#1a1a1a;font-size:15px;line-height:1.7;">
            ' . nl2br($body_text) . '
          </div>
          <div style="background:#f9f9f9;padding:16px;text-align:center;font-size:12px;color:#888888;border-top:1px solid #eeeeee;">
            © 2016 Uber Technologies, Inc. · San Francisco, CA
          </div>
        </div>
        </body>
        </html>
        ';

        $mail->Body = $htmlMailBody;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ── App config (exposed to template context — SSTI data exfil target) ─────
$config = [
    'SECRET_KEY' => 'uber-flask-sk-2016-r4nd0m-x9z8y7',
    'FLAG'       => 'flag{uber_jinja2_ssti_profile_name_125980}',
    'FLASK_ENV'  => 'production',
    'DB_URI'     => 'postgresql://uber:Ub3r_DB_P@ss@db.internal/rider',
    'REDIS_URL'  => 'redis://cache.uber.internal:6379',
    'API_KEY'    => 'uber-api-2016-x9y8z7w6v5u4',
    'SMTP_HOST'  => 'email.uber.com',
    'APP_NAME'   => 'Uber Rider',
];

// ── Simulated Jinja2 template evaluator ───────────────────────────────────
// BUG: evaluates {{expressions}} with no sandboxing — enables SSTI
function jinja2_render($template, $context = []) {
    return preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function($m) use ($context) {
        $expr = trim($m[1]);
        // Support dot notation: config.FLAG → config['FLAG']
        $expr = preg_replace_callback(
            '/([a-zA-Z_]\w*)\.([a-zA-Z_]\w*)/',
            function($d) { return $d[1] . "['" . $d[2] . "']"; },
            $expr
        );
        // Simulate Python string repetition: 'str' * N → directly repeated string literal
        // This makes {{ '7'*7 }} → '7777777', matching real Jinja2 behaviour
        // We embed the result as a PHP string literal so the identifier-prefix step is not affected
        $expr = preg_replace_callback(
            "/('[^']*'|\"[^\"]*\")\s*\*\s*(\d+)/",
            function($r) {
                $str = substr($r[1], 1, -1); // strip surrounding quotes
                $n   = max(0, (int)$r[2]);
                return var_export(str_repeat($str, $n), true);
            },
            $expr
        );
        // Prefix bare PHP identifiers with $ so eval resolves them as variables
        $php_expr = preg_replace("/(?<!['\"\$\\\\])\\b([a-zA-Z_][a-zA-Z0-9_]*)\\b/", '\$$1', $expr);
        extract($context);
        $result = null;
        try {
            $result = @eval("return ($php_expr);");
        } catch (\Throwable $e) {
            return $m[0];
        }
        if ($result === false || $result === null) return $m[0];
        if (is_array($result) || is_object($result)) return json_encode($result, JSON_PRETTY_PRINT);
        return (string)$result;
    }, $template);
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ── MySQL connection + table bootstrap ────────────────────────────────────
$db = new mysqli('localhost', 'root', '', 'KrazePlanet_DB');
if ($db->connect_error) {
    die('<p style="padding:32px;font-family:sans-serif">DB error: ' . esc($db->connect_error) . '</p>');
}
$db->query("CREATE TABLE IF NOT EXISTS lab803_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    name_rendered TEXT,
    email_body    TEXT,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// ── Logout ─────────────────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// ── Vulnerable email template ──────────────────────────────────────────────
// VULNERABLE: name is concatenated into the template string before rendering.
// BUG: if name = "{{ '7'*7 }}", the template becomes "Hi {{ '7'*7 }}, your account..."
// which jinja2_render then evaluates → "Hi 7777777, your account..."
$raw_body_tpl = "Hi %s,\n\nYour Uber account information has been updated on {{date}}.\n\nUpdated details:\n  Name:  %s\n  Email: {{user['email']}}\n\nIf you did not make this change, please contact support@uber.com immediately\nor visit rider.uber.com/security to secure your account.\n\nThanks,\nThe Uber Team\nsupport@uber.com";

// ── State ──────────────────────────────────────────────────────────────────
$reg_errors    = [];
$login_errors  = [];
$update_errors = [];
$update_ok     = false;
$form          = [];
$mode          = 'register';

// ── Load session user ──────────────────────────────────────────────────────
$logged_in_user = null;
if (!empty($_SESSION['user803_id'])) {
    $sid  = (int)$_SESSION['user803_id'];
    $stmt = $db->prepare("SELECT * FROM lab803_users WHERE id = ?");
    $stmt->bind_param('i', $sid);
    $stmt->execute();
    $logged_in_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ── POST handlers ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'register';

    // ── Register ────────────────────────────────────────────────────────────
    if ($action === 'register') {
        $mode  = 'register';
        $name  = trim($_POST['name']     ?? '');
        $email = trim($_POST['email']    ?? '');
        $pw    = trim($_POST['password'] ?? '');

        if ($name  === '')    $reg_errors[] = 'Name is required.';
        if ($email === '')    $reg_errors[] = 'Email is required.';
        if (strlen($pw) < 6) $reg_errors[] = 'Password must be at least 6 characters.';

        if (empty($reg_errors)) {
            $chk = $db->prepare("SELECT id FROM lab803_users WHERE email = ?");
            $chk->bind_param('s', $email);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $reg_errors[] = 'Email already registered. <a href="#" onclick="switchTab(\'login\');return false;">Sign in instead</a>.';
            }
            $chk->close();
        }

        if (empty($reg_errors)) {
            $context = [
                'config' => $config,
                'user'   => ['name' => $name, 'email' => $email],
                'name'   => $name,
                'date'   => date('F j, Y'),
            ];
            // VULNERABLE: name concatenated directly into template string before rendering
            $name_rendered = jinja2_render($name, $context);
            $body_tpl      = sprintf($raw_body_tpl, $name, $name);
            $email_body    = jinja2_render($body_tpl, $context);
            $pw_hash       = password_hash($pw, PASSWORD_BCRYPT);

            $ins = $db->prepare("INSERT INTO lab803_users (name, email, password, name_rendered, email_body) VALUES (?,?,?,?,?)");
            $ins->bind_param('sssss', $name, $email, $pw_hash, $name_rendered, $email_body);
            $ins->execute();
            $new_id = $db->insert_id;
            $ins->close();

            $_SESSION['user803_id'] = $new_id;
            send_uber_email($email, $name_rendered, 'Account Notification - Uber', $email_body);
            header('Location: index.php');
            exit;
        }
        $form = compact('name', 'email');
    }

    // ── Login ────────────────────────────────────────────────────────────────
    elseif ($action === 'login') {
        $mode  = 'login';
        $email = trim($_POST['login_email']    ?? '');
        $pw    = trim($_POST['login_password'] ?? '');

        if ($email === '' || $pw === '') {
            $login_errors[] = 'Please enter your email and password.';
        } else {
            $stmt = $db->prepare("SELECT * FROM lab803_users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row && password_verify($pw, $row['password'])) {
                $_SESSION['user803_id'] = $row['id'];
                header('Location: index.php');
                exit;
            } else {
                $login_errors[] = 'Invalid email or password.';
            }
        }
    }

    // ── Update Name ──────────────────────────────────────────────────────────
    elseif ($action === 'update_name' && $logged_in_user) {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $update_errors[] = 'Name cannot be empty.';
        } else {
            $context = [
                'config' => $config,
                'user'   => ['name' => $name, 'email' => $logged_in_user['email']],
                'name'   => $name,
                'date'   => date('F j, Y'),
            ];
            // VULNERABLE: name concatenated directly into template string before rendering
            $name_rendered = jinja2_render($name, $context);
            $body_tpl      = sprintf($raw_body_tpl, $name, $name);
            $email_body    = jinja2_render($body_tpl, $context);

            $upd = $db->prepare("UPDATE lab803_users SET name = ?, name_rendered = ?, email_body = ?, updated_at = NOW() WHERE id = ?");
            $upd->bind_param('sssi', $name, $name_rendered, $email_body, $logged_in_user['id']);
            $upd->execute();
            $upd->close();

            send_uber_email($logged_in_user['email'], $name_rendered, 'Uber Account Information Updated', $email_body);

            // Reload from DB
            $stmt = $db->prepare("SELECT * FROM lab803_users WHERE id = ?");
            $stmt->bind_param('i', $logged_in_user['id']);
            $stmt->execute();
            $logged_in_user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $update_ok = true;
        }
    }
}

// SSTI detection: rendered name differs from raw input
$ssti_detected = $logged_in_user
    && isset($logged_in_user['name_rendered'])
    && $logged_in_user['name_rendered'] !== $logged_in_user['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $logged_in_user ? 'Account Settings — Uber' : 'rider.uber.com — Sign In' ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;background:#f5f5f5;color:#2c2c2c;font-size:14px;min-height:100vh;display:flex;flex-direction:column;}

/* ── Nav ──────────────────────────────────────────────────────────────────── */
.nav{background:#000;padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.uber-wordmark{font-size:1.35rem;font-weight:900;color:#fff;letter-spacing:-.02em;}
.nav-links{display:flex;align-items:center;gap:20px;}
.nav-link{font-size:.82rem;color:rgba(255,255,255,.6);text-decoration:none;font-weight:500;}
.nav-link:hover{color:#fff;}
.nav-btn{background:#fff;color:#000;border:none;padding:9px 20px;border-radius:4px;font-size:.82rem;font-weight:700;cursor:pointer;text-decoration:none;letter-spacing:.01em;}
.nav-btn:hover{background:#e8e8e8;}
.user-menu{display:flex;align-items:center;gap:10px;}
.user-avatar-sm{width:32px;height:32px;background:#06C167;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:900;color:#fff;flex-shrink:0;}
.user-name-nav{font-size:.82rem;font-weight:600;color:#fff;}
.logout-btn{font-size:.76rem;color:rgba(255,255,255,.55);text-decoration:none;font-weight:500;padding:5px 11px;border:1px solid rgba(255,255,255,.2);border-radius:4px;transition:all .15s;}
.logout-btn:hover{background:rgba(255,255,255,.1);color:#fff;border-color:rgba(255,255,255,.4);}

/* ── Browser bar ─────────────────────────────────────────────────────────── */
.browser-bar{background:#e8e8e8;padding:6px 14px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #d0d0d0;}
.browser-dots{display:flex;gap:5px;}
.browser-dot{width:11px;height:11px;border-radius:50%;}
.browser-url{background:#fff;border-radius:20px;padding:4px 14px;font-family:'Courier New',monospace;font-size:.7rem;color:#555;border:1px solid #d8d8d8;min-width:320px;}

/* ── Layout ───────────────────────────────────────────────────────────────── */
.page{flex:1;padding:28px 16px 48px;}
.page-inner{max-width:980px;margin:0 auto;}

/* ── Profile grid (logged in) ────────────────────────────────────────────── */
.profile-grid{display:grid;grid-template-columns:230px 1fr;gap:22px;align-items:start;}
@media(max-width:720px){.profile-grid{grid-template-columns:1fr;}}

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.sidebar{background:#1a1a1a;border-radius:10px;padding:22px;color:#fff;}
.sidebar-avatar{width:56px;height:56px;background:#06C167;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:900;color:#fff;margin:0 auto 12px;}
.sidebar-name{font-size:.9rem;font-weight:700;color:#fff;text-align:center;margin-bottom:3px;word-break:break-all;}
.sidebar-email{font-size:.7rem;color:rgba(255,255,255,.45);text-align:center;margin-bottom:14px;word-break:break-all;}
.sidebar-divider{height:1px;background:rgba(255,255,255,.08);margin:10px 0;}
.sidebar-row{display:flex;justify-content:space-between;align-items:center;padding:5px 0;}
.sidebar-lbl{font-size:.65rem;font-weight:700;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.07em;}
.sidebar-val{font-size:.74rem;color:rgba(255,255,255,.7);font-weight:500;}
.sidebar-logout{display:flex;align-items:center;gap:6px;font-size:.76rem;font-weight:600;color:rgba(255,255,255,.4);text-decoration:none;margin-top:14px;padding:8px 0;border-top:1px solid rgba(255,255,255,.07);transition:color .15s;}
.sidebar-logout:hover{color:#ff5555;}
.sidebar-logout svg{width:13px;height:13px;stroke:currentColor;flex-shrink:0;}

/* ── Cards ────────────────────────────────────────────────────────────────── */
.card{background:#fff;border-radius:10px;padding:22px;box-shadow:0 1px 3px rgba(0,0,0,.07),0 0 0 1px rgba(0,0,0,.04);margin-bottom:18px;}
.card-title{font-size:.98rem;font-weight:800;color:#1a1a1a;margin-bottom:3px;}
.card-sub{font-size:.76rem;color:#999;margin-bottom:18px;}

/* ── Forms ────────────────────────────────────────────────────────────────── */
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:13px;}
.form-label{font-size:.74rem;font-weight:700;color:#555;letter-spacing:.02em;}
.required{color:#e53e3e;margin-left:2px;}
.form-input{border:1.5px solid #e0e0e0;border-radius:6px;padding:10px 13px;font-size:.88rem;color:#1a1a1a;outline:none;transition:border-color .15s,box-shadow .15s;background:#fff;font-family:inherit;}
.form-input:focus{border-color:#000;box-shadow:0 0 0 3px rgba(0,0,0,.07);}
.form-hint{font-size:.68rem;color:#bbb;margin-top:2px;}
.form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}

/* ── Buttons ─────────────────────────────────────────────────────────────── */
.btn-black{background:#000;color:#fff;border:none;padding:11px 22px;border-radius:6px;font-size:.88rem;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;letter-spacing:.01em;width:100%;}
.btn-black:hover{background:#222;}
.btn-save{background:#06C167;color:#fff;border:none;padding:10px 22px;border-radius:6px;font-size:.86rem;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;letter-spacing:.01em;}
.btn-save:hover{background:#04a354;}
.btn-save:active{transform:scale(.98);}

/* ── Tabs ─────────────────────────────────────────────────────────────────── */
.tab-bar{display:flex;gap:0;border-bottom:2px solid #ebebeb;margin-bottom:18px;}
.tab-btn{flex:1;background:none;border:none;padding:10px;font-size:.82rem;font-weight:700;color:#bbb;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;font-family:inherit;transition:color .15s;}
.tab-btn.active{color:#000;border-bottom-color:#000;}
.tab-btn:hover:not(.active){color:#555;}
.tab-panel{display:none;}
.tab-panel.active{display:block;}

/* ── Error / Success ─────────────────────────────────────────────────────── */
.error-box{background:#fff5f5;border:1px solid #fed7d7;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:.78rem;color:#c53030;}
.error-box ul{padding-left:16px;margin-top:3px;}
.success-box{background:#f0fff4;border:1px solid #9ae6b4;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:.78rem;color:#276749;display:flex;align-items:center;gap:7px;}
.success-box svg{width:14px;height:14px;fill:#276749;flex-shrink:0;}

/* ── Divider / Social ────────────────────────────────────────────────────── */
.divider{display:flex;align-items:center;gap:10px;margin:14px 0;color:#ccc;font-size:.76rem;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#ebebeb;}
.social-btn{display:flex;align-items:center;justify-content:center;gap:8px;border:1.5px solid #e0e0e0;border-radius:6px;padding:9px;font-size:.8rem;font-weight:600;color:#2c2c2c;cursor:pointer;background:#fff;transition:background .1s;width:100%;margin-bottom:7px;font-family:inherit;}
.social-btn:hover{background:#f8f8f8;}
.sign-in-link{text-align:center;font-size:.78rem;color:#888;margin-top:12px;}
.sign-in-link a{color:#000;font-weight:700;text-decoration:none;}
.sign-in-link a:hover{text-decoration:underline;}

/* ── Promo panel ─────────────────────────────────────────────────────────── */
.promo-panel{background:#1a1a1a;border-radius:10px;padding:26px;color:#fff;position:relative;overflow:hidden;}
.promo-panel::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='200' height='200' viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='170' cy='30' r='90' fill='rgba(6,193,103,.05)'/%3E%3Ccircle cx='10' cy='170' r='65' fill='rgba(6,193,103,.04)'/%3E%3C/svg%3E") no-repeat center;pointer-events:none;}
.promo-title{font-size:1.2rem;font-weight:900;margin-bottom:7px;line-height:1.35;position:relative;}
.promo-sub{font-size:.8rem;opacity:.6;margin-bottom:18px;position:relative;line-height:1.65;}
.promo-features{list-style:none;position:relative;}
.promo-features li{display:flex;align-items:flex-start;gap:10px;margin-bottom:14px;font-size:.8rem;}
.promo-dot{width:8px;height:8px;background:#06C167;border-radius:50%;flex-shrink:0;margin-top:5px;}
.promo-features strong{display:block;font-weight:700;margin-bottom:1px;}
.promo-features span{opacity:.6;font-size:.76rem;}
.promo-note{margin-top:18px;padding-top:14px;border-top:1px solid rgba(255,255,255,.1);font-size:.7rem;color:rgba(255,255,255,.4);position:relative;line-height:1.6;}

/* ── Guest grid ──────────────────────────────────────────────────────────── */
.guest-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:start;}
@media(max-width:720px){.guest-grid{grid-template-columns:1fr;}}

/* ── Email preview ───────────────────────────────────────────────────────── */
.email-preview-wrap{animation:slideIn .3s ease;}
@keyframes slideIn{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
.email-preview{background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.07),0 0 0 1px rgba(0,0,0,.04);overflow:hidden;}
.email-preview-hd{background:#f8f8f8;border-bottom:1px solid #ebebeb;padding:12px 18px;display:flex;align-items:center;gap:10px;}
.email-hd-icon{width:30px;height:30px;background:#000;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.email-hd-icon svg{width:14px;height:14px;fill:#fff;}
.email-hd-text{flex:1;}
.email-hd-title{font-size:.78rem;font-weight:700;color:#1a1a1a;}
.email-hd-sub{font-size:.67rem;color:#999;}
.ssti-badge{background:#fff3cd;color:#856404;border:1px solid #ffc107;border-radius:4px;padding:2px 8px;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-left:auto;}
.inbox-card{margin:14px;border:1px solid #e8e8e8;border-radius:7px;overflow:hidden;}
.inbox-meta{padding:10px 14px;border-bottom:1px solid #f0f0f0;display:grid;gap:3px;}
.inbox-meta-row{display:flex;gap:8px;font-size:.75rem;}
.meta-lbl{color:#bbb;min-width:54px;flex-shrink:0;}
.meta-val{color:#1a1a1a;font-weight:500;}
.meta-val.ssti-output{color:#e53e3e;font-weight:700;background:#fff5f5;border:1px solid #fed7d7;border-radius:3px;padding:1px 6px;}
.inbox-body{padding:14px;font-size:.82rem;color:#444;line-height:1.85;white-space:pre-wrap;background:#fff;}

/* ── Template source ─────────────────────────────────────────────────────── */
.template-source{margin:0 14px 14px;}
.tpl-toggle{display:flex;align-items:center;gap:6px;font-size:.71rem;color:#aaa;font-weight:700;cursor:pointer;padding:7px 0;border:none;background:none;font-family:inherit;transition:color .15s;}
.tpl-toggle:hover{color:#000;}
.tpl-toggle svg{width:12px;height:12px;fill:#aaa;transition:transform .2s;}
.tpl-toggle.open svg{transform:rotate(90deg);}
.tpl-source-box{display:none;background:#1a1a1a;border-radius:6px;padding:12px 14px;font-family:'Courier New',monospace;font-size:.7rem;line-height:1.85;}
.tpl-line{color:#6272a4;}
.tpl-key{color:#82aaff;}
.tpl-expr{color:#06C167;background:rgba(6,193,103,.12);border-radius:2px;padding:0 3px;}

/* ── Report panel ────────────────────────────────────────────────────────── */
.report-panel{background:#fff;border-radius:10px;padding:18px 22px;box-shadow:0 1px 3px rgba(0,0,0,.07),0 0 0 1px rgba(0,0,0,.04);margin-top:18px;}
.report-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:14px;}
.report-item-label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#bbb;margin-bottom:3px;}
.report-item-val{font-size:.8rem;color:#1a1a1a;font-weight:600;}
.report-item-val a{color:#000;text-decoration:none;font-weight:700;}
.report-item-val a:hover{text-decoration:underline;}
.severity-badge{background:#f0fff4;color:#276749;border:1px solid #9ae6b4;border-radius:3px;padding:1px 7px;font-size:.75rem;font-weight:700;}

/* ── Footer ──────────────────────────────────────────────────────────────── */
footer{background:#000;color:rgba(255,255,255,.35);padding:18px 24px;font-size:.72rem;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:auto;}
footer a{color:rgba(255,255,255,.45);text-decoration:none;}
footer a:hover{color:#fff;}
</style>
</head>
<body>

<!-- Nav -->
<nav class="nav">
  <a href="index.php" class="nav-logo">
    <span class="uber-wordmark">Uber</span>
  </a>
  <div class="nav-links">
    <a href="#" class="nav-link">Help</a>
    <a href="#" class="nav-link">Safety</a>
    <?php if ($logged_in_user): ?>
    <div class="user-menu">
      <div class="user-avatar-sm"><?= esc(mb_strtoupper(mb_substr($logged_in_user['name'],0,1,'UTF-8'))) ?></div>
      <span class="user-name-nav"><?= esc($logged_in_user['name']) ?></span>
      <a href="index.php?logout=1" class="logout-btn">Log out</a>
    </div>
    <?php else: ?>
    <a href="index.php" class="nav-btn">Sign up</a>
    <?php endif; ?>
  </div>
</nav>

<?php if ($logged_in_user): ?>
<!-- Browser bar simulation (only when logged in) -->
<div class="browser-bar">
  <div class="browser-dots">
    <div class="browser-dot" style="background:#FF5F57;"></div>
    <div class="browser-dot" style="background:#FEBC2E;"></div>
    <div class="browser-dot" style="background:#28C840;"></div>
  </div>
  <div class="browser-url">🔒 rider.uber.com/profile/settings</div>
</div>
<?php endif; ?>

<div class="page">
<div class="page-inner">

<?php if ($logged_in_user): ?>
  <!-- ── LOGGED IN: Profile settings layout ─────────────────────────────── -->
  <div class="profile-grid">

    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-avatar"><?= esc(mb_strtoupper(mb_substr($logged_in_user['name'],0,1,'UTF-8'))) ?></div>
      <div class="sidebar-name"><?= esc($logged_in_user['name_rendered'] ?: $logged_in_user['name']) ?></div>
      <div class="sidebar-email"><?= esc($logged_in_user['email']) ?></div>
      <div class="sidebar-divider"></div>
      <div class="sidebar-row">
        <span class="sidebar-lbl">Member since</span>
        <span class="sidebar-val"><?= esc(date('M Y', strtotime($logged_in_user['created_at']))) ?></span>
      </div>
      <div class="sidebar-row">
        <span class="sidebar-lbl">Last updated</span>
        <span class="sidebar-val"><?= esc(date('M j, Y', strtotime($logged_in_user['updated_at']))) ?></span>
      </div>
      <div class="sidebar-row">
        <span class="sidebar-lbl">City</span>
        <span class="sidebar-val">San Francisco, CA</span>
      </div>
      <div class="sidebar-divider"></div>
      <a href="index.php?logout=1" class="sidebar-logout">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        Log out
      </a>
    </div>

    <!-- Main content -->
    <div class="main-content">

      <!-- Account Settings card -->
      <div class="card">
        <div class="card-title">Account Settings</div>
        <div class="card-sub">Changes to your name will trigger an account update notification email</div>

        <?php if (!empty($update_errors)): ?>
        <div class="error-box"><?= esc($update_errors[0]) ?></div>
        <?php endif; ?>
        <?php if ($update_ok): ?>
        <div class="success-box">
          <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Account name updated. Check your notification email below.
        </div>
        <?php endif; ?>

        <form method="POST" action="index.php" autocomplete="off">
          <input type="hidden" name="action" value="update_name">
          <div class="form-group">
            <label class="form-label">
              Display Name<span class="required">*</span>
            </label>
            <input type="text" name="name" class="form-input"
                   value="<?= esc($logged_in_user['name']) ?>"
                   placeholder="Your name">
            <span class="form-hint">Used in account notification emails sent by support@uber.com</span>
          </div>
          <div class="form-group">
            <label class="form-label">Email address</label>
            <input type="text" class="form-input" value="<?= esc($logged_in_user['email']) ?>" disabled style="background:#f8f8f8;color:#aaa;cursor:not-allowed;">
          </div>
          <button type="submit" class="btn-save">Save changes</button>
        </form>
      </div>

      <!-- Email notification preview -->
      <?php if ($logged_in_user['email_body']): ?>
      <div class="email-preview-wrap">
        <div class="email-preview">
          <div class="email-preview-hd">
            <div class="email-hd-icon">
              <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            </div>
            <div class="email-hd-text">
              <div class="email-hd-title">Account Update Email Sent</div>
              <div class="email-hd-sub">Template rendered server-side before delivery</div>
            </div>
            <?php if ($ssti_detected): ?>
            <span class="ssti-badge">SSTI Result</span>
            <?php endif; ?>
          </div>

          <div class="inbox-card">
            <div class="inbox-meta">
              <div class="inbox-meta-row">
                <span class="meta-lbl">From</span>
                <span class="meta-val">Uber &lt;support@uber.com&gt;</span>
              </div>
              <div class="inbox-meta-row">
                <span class="meta-lbl">To</span>
                <span class="meta-val"><?= esc($logged_in_user['email']) ?></span>
              </div>
              <div class="inbox-meta-row">
                <span class="meta-lbl">Subject</span>
                <span class="meta-val">Your Uber account information has been updated</span>
              </div>
              <div class="inbox-meta-row">
                <span class="meta-lbl">Name</span>
                <span class="meta-val <?= $ssti_detected ? 'ssti-output' : '' ?>"><?= esc($logged_in_user['name_rendered']) ?></span>
              </div>
            </div>
            <div class="inbox-body"><?= esc($logged_in_user['email_body']) ?></div>
          </div>

          <div class="template-source">
            <button class="tpl-toggle" id="tplToggle" onclick="toggleTpl()">
              <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
              View raw email template (before rendering)
            </button>
            <div class="tpl-source-box" id="tplSource">
              <div class="tpl-line"><span style="color:#6272a4;"># Template string is built by string concatenation — NOT safe variable substitution</span></div>
              <div class="tpl-line" style="margin-top:4px;"><span class="tpl-key">body_template</span> = <span style="color:#c3e88d;">"Hi "</span> + <span class="tpl-expr">name</span> + <span style="color:#c3e88d;">", \nYour Uber account information has been updated..."</span></div>
              <div class="tpl-line" style="margin-top:6px;"><span style="color:#6272a4;"># When name = "{{ '7'*7 }}", the template becomes:</span></div>
              <div class="tpl-line"><span class="tpl-key">body_template</span> = <span class="tpl-expr">"Hi {{ '7'*7 }}, \nYour Uber account..."</span></div>
              <div class="tpl-line" style="margin-top:2px;"><span style="color:#6272a4;"># jinja2_render() evaluates {{ '7'*7 }} → '7777777' (Python string repetition)</span></div>
              <div class="tpl-line" style="margin-top:6px;"><span class="tpl-key">Remaining template vars:</span></div>
              <div class="tpl-line">  date        = <span class="tpl-expr">{{date}}</span></div>
              <div class="tpl-line">  user.email  = <span class="tpl-expr">{{user['email']}}</span></div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /main-content -->
  </div><!-- /profile-grid -->

<?php else: ?>
  <!-- ── GUEST: Register / Sign In ──────────────────────────────────────── -->
  <div class="guest-grid">

    <!-- Auth card -->
    <div class="card">
      <div class="tab-bar">
        <button class="tab-btn<?= $mode==='register' ? ' active' : '' ?>" onclick="switchTab('register')" id="tabRegister">Create account</button>
        <button class="tab-btn<?= $mode==='login' ? ' active' : '' ?>" onclick="switchTab('login')" id="tabLogin">Sign in</button>
      </div>

      <!-- Register panel -->
      <div class="tab-panel<?= $mode==='register' ? ' active' : '' ?>" id="panelRegister">
        <?php if (!empty($reg_errors)): ?>
        <div class="error-box">
          <strong>Please fix the following:</strong>
          <ul><?php foreach ($reg_errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>
        <form method="POST" action="index.php" autocomplete="off">
          <input type="hidden" name="action" value="register">
          <div class="form-group">
            <label class="form-label">Full Name<span class="required">*</span></label>
            <input type="text" name="name" class="form-input"
                   placeholder="Your name"
                   value="<?= esc($form['name'] ?? '') ?>">
            <span class="form-hint">Used in your account notification emails</span>
          </div>
          <div class="form-group">
            <label class="form-label">Email address<span class="required">*</span></label>
            <input type="email" name="email" class="form-input"
                   placeholder="you@example.com"
                   value="<?= esc($form['email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Password<span class="required">*</span></label>
            <input type="password" name="password" class="form-input" placeholder="Min. 6 characters">
          </div>
          <button type="submit" class="btn-black">Create account</button>
          <div class="divider">or continue with</div>
          <button type="button" class="social-btn">
            <svg viewBox="0 0 24 24" width="15" height="15"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Continue with Google
          </button>
        </form>
        <div class="sign-in-link">Already have an account? <a href="#" onclick="switchTab('login');return false;">Sign in</a></div>
      </div>

      <!-- Login panel -->
      <div class="tab-panel<?= $mode==='login' ? ' active' : '' ?>" id="panelLogin">
        <?php if (!empty($login_errors)): ?>
        <div class="error-box"><?php foreach ($login_errors as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?></div>
        <?php endif; ?>
        <form method="POST" action="index.php" autocomplete="off">
          <input type="hidden" name="action" value="login">
          <div class="form-group">
            <label class="form-label">Email address<span class="required">*</span></label>
            <input type="email" name="login_email" class="form-input" placeholder="you@example.com"
                   value="<?= esc($_POST['login_email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Password<span class="required">*</span></label>
            <input type="password" name="login_password" class="form-input" placeholder="Your password">
          </div>
          <button type="submit" class="btn-black">Sign in</button>
        </form>
        <div class="sign-in-link" style="margin-top:12px;">New to Uber? <a href="#" onclick="switchTab('register');return false;">Create account</a></div>
      </div>
    </div>

    <!-- Promo panel -->
    <div class="promo-panel">
      <div class="promo-title">Go anywhere.<br>Get anything.</div>
      <div class="promo-sub">Join millions of riders around the world. Your account is your key to Uber's full ecosystem.</div>
      <ul class="promo-features">
        <li>
          <div class="promo-dot"></div>
          <div>
            <strong>Rides on demand</strong>
            <span>Request a ride in seconds, anywhere in 70+ countries</span>
          </div>
        </li>
        <li>
          <div class="promo-dot"></div>
          <div>
            <strong>Uber Eats delivery</strong>
            <span>Food and groceries delivered to your door</span>
          </div>
        </li>
        <li>
          <div class="promo-dot"></div>
          <div>
            <strong>Personalised notifications</strong>
            <span>Account updates sent by support@uber.com — powered by Flask / Jinja2</span>
          </div>
        </li>
      </ul>
      <div class="promo-note">
        rider.uber.com · Uber Technologies, Inc. · San Francisco, CA
      </div>
    </div>

  </div><!-- /guest-grid -->
<?php endif; ?>

<!-- Report Metadata -->
<div class="report-panel">
  <div style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#ccc;margin-bottom:12px;">HackerOne Report Metadata</div>
  <div class="report-grid">
    <div><div class="report-item-label">Report</div><div class="report-item-val"><a href="https://hackerone.com/reports/125980" target="_blank" rel="noopener">#125980</a></div></div>
    <div><div class="report-item-label">Researcher</div><div class="report-item-val">orange (Orange Tsai)</div></div>
    <div><div class="report-item-label">Program</div><div class="report-item-val">Uber</div></div>
    <div><div class="report-item-label">Severity</div><div class="report-item-val"><span class="severity-badge">Critical</span></div></div>
    <div><div class="report-item-label">Weakness</div><div class="report-item-val">Jinja2 Template Injection</div></div>
    <div><div class="report-item-label">Bounty</div><div class="report-item-val">$10,000</div></div>
    <div><div class="report-item-label">Reported</div><div class="report-item-val">Mar 25, 2016</div></div>
    <div><div class="report-item-label">Disclosed</div><div class="report-item-val">Apr 6, 2016</div></div>
  </div>
</div>

</div><!-- /page-inner -->
</div><!-- /page -->

<footer>
  <span>© 2016 Uber Technologies, Inc. All rights reserved — Lab simulation based on <a href="https://hackerone.com/reports/125980" target="_blank" rel="noopener">HackerOne #125980</a></span>
  <span>rider.uber.com · San Francisco, CA</span>
</footer>

<script>
function switchTab(tab) {
    ['register','login'].forEach(function(t) {
        var btn   = document.getElementById('tab' + t.charAt(0).toUpperCase() + t.slice(1));
        var panel = document.getElementById('panel' + t.charAt(0).toUpperCase() + t.slice(1));
        if (!btn || !panel) return;
        if (t === tab) {
            btn.classList.add('active');
            panel.classList.add('active');
        } else {
            btn.classList.remove('active');
            panel.classList.remove('active');
        }
    });
}
function toggleTpl() {
    var box = document.getElementById('tplSource');
    var btn = document.getElementById('tplToggle');
    if (box.style.display === 'block') {
        box.style.display = 'none';
        btn.classList.remove('open');
    } else {
        box.style.display = 'block';
        btn.classList.add('open');
    }
}
</script>
</body>
</html>
