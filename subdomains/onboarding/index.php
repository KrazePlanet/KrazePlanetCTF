<?php
// SSTI Lab 802 — Glovo Welcome Email SSTI
// HackerOne Report #1104349 (High — Glovo)
// Vulnerable parameter: first_name (POST) — rendered directly in Twig-style email template
// Payload: {{7*7}} → subject becomes "49, welcome to Glovo!"
// Flag via: {{config['FLAG']}} or {{config.FLAG}}

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

// ── App config (exposed to template context — SSTI data exfil target) ─────
$config = [
    'APP_NAME'    => 'Glovo',
    'ENV'         => 'production',
    'SECRET_KEY'  => 'glv-prod-sk-9f3a2d1e8c7b4520',
    'FLAG'        => 'flag{glovo_ssti_welcome_email_1104349}',
    'DB_HOST'     => 'postgres-prod.internal.glovo.com',
    'DB_PORT'     => '5432',
    'DB_USER'     => 'glovo_app',
    'DB_PASS'     => 'Gl0v0_DB_S3cr3t!',
    'SMTP_HOST'   => 'email.glovo.com',
    'REDIS_HOST'  => 'redis-cluster.internal',
    'API_KEY'     => 'glv-api-k3y-x7y8z9w1v2u3',
    'JWT_SECRET'  => 'jwts-glv-prod-r4t5y6u7i8o9',
];

// ── Simulated Twig/Jinja2 template evaluator ──────────────────────────────
// BUG: evaluates {{expressions}} with no sandboxing — enables SSTI
function twig_render($template, $context = []) {
    return preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function($m) use ($context) {
        $expr = trim($m[1]);
        // Support dot notation: config.FLAG → config['FLAG']
        $expr = preg_replace_callback(
            '/([a-zA-Z_]\w*)\.([a-zA-Z_]\w*)/',
            function($d) { return $d[1] . "['" . $d[2] . "']"; },
            $expr
        );
        // Prefix bare PHP identifiers with $ so eval resolves them as variables.
        // Skip identifiers already preceded by $, or that are inside quotes (string keys).
        $php_expr = preg_replace("/(?<!['\"\$\\\\])\\b([a-zA-Z_][a-zA-Z0-9_]*)\\b/", '\$$1', $expr);
        extract($context);
        $result = null;
        try {
            $result = @eval("return ($php_expr);");
        } catch (\Throwable $e) {
            return $m[0];
        }
        // @eval returns false on parse error; null means no return value
        if ($result === false || $result === null) return $m[0];
        if (is_array($result) || is_object($result)) return json_encode($result, JSON_PRETTY_PRINT);
        return (string)$result;
    }, $template);
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ── MySQL connection + table bootstrap ────────────────────────────────────
$db_hosts = ['krazeplanet', '127.0.0.1', 'localhost', '172.19.0.1', 'host.docker.internal'];
$db = null;
foreach ($db_hosts as $h) {
    $db = @new mysqli($h, 'root', '');
    if (!$db->connect_error) {
        $db->query("CREATE DATABASE IF NOT EXISTS `KrazePlanet_DB` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db->select_db('KrazePlanet_DB');
        break;
    }
}
if (!$db || $db->connect_error) { die('DB connection failed: ' . ($db ? $db->connect_error : 'Unable to connect to database')); }
if ($db->connect_error) {
    die('<p style="padding:32px;font-family:sans-serif">DB error: ' . esc($db->connect_error) . '</p>');
}
$db->query("CREATE TABLE IF NOT EXISTS lab802_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    first_name    VARCHAR(255) NOT NULL,
    last_name     VARCHAR(255) DEFAULT '',
    email         VARCHAR(255) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    city          VARCHAR(100) DEFAULT 'Bishkek',
    email_subject TEXT,
    email_body    TEXT,
    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// ── Logout ─────────────────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// ── Vulnerable email templates ─────────────────────────────────────────────
// VULNERABLE: template strings are built by concatenating user input before rendering.
// BUG: first_name is embedded directly into the template string, enabling SSTI.
$raw_subject_tpl = '%s, welcome to Glovo!'; // %s = first_name concatenated at runtime
$raw_body_tpl    = "Hi %s,\n\nThank you for joining Glovo in {{city}}! Your account has been successfully created with email {{user['email']}}.\n\nStart ordering from the best restaurants and shops near you.\n\nYour profile details:\n  Name: %s {{last_name}}\n  City: {{city}}\n  Member since: 2021\n\nHappy ordering,\nThe Glovo Team";

// ── State ──────────────────────────────────────────────────────────────────
$errors      = [];
$reg_errors  = [];
$login_errors = [];
$form        = [];
$mode        = 'register'; // active tab when not logged in

// ── Load session user ──────────────────────────────────────────────────────
$logged_in_user = null;
if (!empty($_SESSION['user802_id'])) {
    $sid  = (int)$_SESSION['user802_id'];
    $stmt = $db->prepare("SELECT * FROM lab802_users WHERE id = ?");
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
        $mode       = 'register';
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name']  ?? '');
        $email      = trim($_POST['email']      ?? '');
        $password   = trim($_POST['password']   ?? '');
        $city       = trim($_POST['city']       ?? 'Bishkek');

        if ($first_name === '') $reg_errors[] = 'First name is required.';
        if ($email === '')      $reg_errors[] = 'Email is required.';
        if (strlen($password) < 6) $reg_errors[] = 'Password must be at least 6 characters.';

        if (empty($reg_errors)) {
            // Check duplicate email
            $chk = $db->prepare("SELECT id FROM lab802_users WHERE email = ?");
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
                'config'     => $config,
                'user'       => ['first_name'=>$first_name,'last_name'=>$last_name,'email'=>$email,'city'=>$city],
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'city'       => $city,
            ];

            // VULNERABLE: first_name concatenated directly into template string before rendering
            $subject_tpl    = sprintf($raw_subject_tpl, $first_name);
            $body_tpl       = sprintf($raw_body_tpl, $first_name, $first_name);
            $email_subject  = twig_render($subject_tpl, $context);
            $email_body     = twig_render($body_tpl, $context);
            $pw_hash        = password_hash($password, PASSWORD_BCRYPT);

            $ins = $db->prepare("INSERT INTO lab802_users (first_name,last_name,email,password,city,email_subject,email_body) VALUES (?,?,?,?,?,?,?)");
            $ins->bind_param('sssssss', $first_name, $last_name, $email, $pw_hash, $city, $email_subject, $email_body);
            $ins->execute();
            $new_id = $db->insert_id;
            $ins->close();

            $_SESSION['user802_id'] = $new_id;

            // Send Welcome Email via SMTP
            if ($email) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'mailpit';
                    $mail->SMTPAuth   = false;
                                        $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
                    $mail->Port       = 1025;
                    $mail->setFrom('noreply@krazeplanet.com', 'Glovo');
                    $mail->addAddress($email, $first_name . ' ' . $last_name);
                    $mail->isHTML(true);
                    $mail->Subject = $email_subject;

                    $htmlMailBody = '
                    <!DOCTYPE html>
                    <html>
                    <head><meta charset="utf-8"></head>
                    <body style="font-family:Arial,sans-serif;background:#f4f6f8;padding:20px;margin:0;">
                    <div style="max-width:540px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #e0e0e0;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                      <div style="background:#ffc244;padding:20px;text-align:center;">
                        <h2 style="color:#000000;margin:0;font-size:22px;font-weight:800;">Glovo</h2>
                      </div>
                      <div style="padding:28px 24px;color:#2c3e50;font-size:15px;line-height:1.7;">
                        ' . nl2br($email_body) . '
                      </div>
                      <div style="background:#fafafa;padding:16px;text-align:center;font-size:12px;color:#999999;border-top:1px solid #eeeeee;">
                        © Glovoapp23, S.L. · Madrid, Spain
                      </div>
                    </div>
                    </body>
                    </html>
                    ';

                    $mail->Body = $htmlMailBody;
                    $mail->send();
                    $_SESSION['mail_sent'] = true;
                } catch (Exception $e) {
                    $_SESSION['mail_error'] = 'Mail Error: ' . $mail->ErrorInfo;
                }
            }

            header('Location: index.php');
            exit;
        }
        $form = compact('first_name','last_name','email','city');
    }

    // ── Login ────────────────────────────────────────────────────────────────
    elseif ($action === 'login') {
        $mode     = 'login';
        $email    = trim($_POST['login_email']    ?? '');
        $password = trim($_POST['login_password'] ?? '');

        if ($email === '' || $password === '') {
            $login_errors[] = 'Please enter your email and password.';
        } else {
            $stmt = $db->prepare("SELECT * FROM lab802_users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row && password_verify($password, $row['password'])) {
                $_SESSION['user802_id'] = $row['id'];
                header('Location: index.php');
                exit;
            } else {
                $login_errors[] = 'Invalid email or password.';
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
<title><?= $logged_in_user ? 'Glovo — My Account' : 'Glovo — Create Account' ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;background:#f5f5f5;color:#2c2c2c;font-size:14px;min-height:100vh;display:flex;flex-direction:column;}

/* ── Top nav ──────────────────────────────────────────────────────────────── */
.nav{background:#fff;border-bottom:1px solid #e8e8e8;padding:0 24px;height:56px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.nav-logo{display:flex;align-items:center;gap:8px;text-decoration:none;}
.glovo-icon{width:34px;height:34px;background:#FFC40C;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.glovo-icon svg{width:20px;height:20px;fill:#fff;}
.glovo-wordmark{font-size:1.25rem;font-weight:800;color:#2c2c2c;letter-spacing:-.02em;}
.nav-links{display:flex;align-items:center;gap:20px;}
.nav-link{font-size:.82rem;color:#666;text-decoration:none;font-weight:500;}
.nav-link:hover{color:#2c2c2c;}
.nav-btn{background:#FFC40C;color:#2c2c2c;border:none;padding:8px 18px;border-radius:50px;font-size:.82rem;font-weight:700;cursor:pointer;text-decoration:none;}
.nav-btn:hover{background:#e6b000;}

/* ── Layout ───────────────────────────────────────────────────────────────── */
.page{flex:1;display:flex;flex-direction:column;align-items:center;padding:32px 16px 48px;}
.page-grid{width:100%;max-width:960px;display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;}
@media(max-width:700px){.page-grid{grid-template-columns:1fr;}}

/* ── Cards ────────────────────────────────────────────────────────────────── */
.card{background:#fff;border-radius:12px;padding:28px;box-shadow:0 1px 4px rgba(0,0,0,.08),0 0 0 1px rgba(0,0,0,.04);}
.card-title{font-size:1.1rem;font-weight:800;color:#2c2c2c;margin-bottom:4px;}
.card-sub{font-size:.8rem;color:#888;margin-bottom:20px;}

/* ── Form styles ──────────────────────────────────────────────────────────── */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;}
.form-row.full{grid-template-columns:1fr;}
.form-group{display:flex;flex-direction:column;gap:5px;}
.form-label{font-size:.75rem;font-weight:700;color:#555;letter-spacing:.02em;}
.form-label .required{color:#e53e3e;margin-left:2px;}
.form-input{border:1.5px solid #e0e0e0;border-radius:8px;padding:10px 13px;font-size:.88rem;color:#2c2c2c;outline:none;transition:border-color .15s,box-shadow .15s;background:#fff;font-family:inherit;}
.form-input:focus{border-color:#FFC40C;box-shadow:0 0 0 3px rgba(255,196,12,.15);}
.form-input.vuln-field{border-color:#ffb347;background:#fffdf4;}
.form-input.vuln-field:focus{border-color:#FFC40C;box-shadow:0 0 0 3px rgba(255,196,12,.2);}
select.form-input{cursor:pointer;}

.vuln-badge{display:inline-flex;align-items:center;gap:4px;font-size:.62rem;font-weight:700;background:#fff3cd;color:#856404;border:1px solid #ffc107;border-radius:3px;padding:1px 6px;margin-left:6px;text-transform:uppercase;letter-spacing:.05em;}

.form-hint{font-size:.7rem;color:#aaa;margin-top:2px;}

.submit-btn{width:100%;background:#FFC40C;color:#2c2c2c;border:none;padding:13px;border-radius:50px;font-size:.95rem;font-weight:800;cursor:pointer;margin-top:8px;transition:background .15s,transform .1s;font-family:inherit;letter-spacing:.01em;}
.submit-btn:hover{background:#e6b000;transform:translateY(-1px);}
.submit-btn:active{transform:translateY(0);}

.error-box{background:#fff5f5;border:1px solid #fed7d7;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:.8rem;color:#c53030;}
.error-box ul{padding-left:16px;margin-top:4px;}

.divider{display:flex;align-items:center;gap:10px;margin:16px 0;color:#ccc;font-size:.78rem;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#ebebeb;}

.social-btn{display:flex;align-items:center;justify-content:center;gap:8px;border:1.5px solid #e0e0e0;border-radius:50px;padding:10px;font-size:.82rem;font-weight:600;color:#2c2c2c;cursor:pointer;background:#fff;transition:background .1s;width:100%;margin-bottom:8px;font-family:inherit;}
.social-btn:hover{background:#f8f8f8;}
.sign-in-link{text-align:center;font-size:.8rem;color:#888;margin-top:14px;}
.sign-in-link a{color:#FFC40C;font-weight:700;text-decoration:none;}
.sign-in-link a:hover{text-decoration:underline;}

/* ── Right promo panel ────────────────────────────────────────────────────── */
.promo-panel{background:linear-gradient(135deg,#FFC40C 0%,#FF8C00 100%);border-radius:12px;padding:28px;color:#fff;position:relative;overflow:hidden;}
.promo-panel::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='200' height='200' viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='160' cy='40' r='80' fill='rgba(255,255,255,.08)'/%3E%3Ccircle cx='20' cy='160' r='60' fill='rgba(255,255,255,.06)'/%3E%3C/svg%3E") no-repeat center;pointer-events:none;}
.promo-title{font-size:1.35rem;font-weight:900;margin-bottom:8px;line-height:1.3;position:relative;}
.promo-sub{font-size:.85rem;opacity:.9;margin-bottom:20px;position:relative;line-height:1.6;}
.promo-features{list-style:none;position:relative;}
.promo-features li{display:flex;align-items:flex-start;gap:10px;margin-bottom:12px;font-size:.82rem;}
.promo-features li svg{width:18px;height:18px;fill:#fff;flex-shrink:0;margin-top:1px;}
.promo-features strong{display:block;font-weight:700;margin-bottom:1px;}
.promo-features span{opacity:.85;font-size:.78rem;}

.promo-cities{margin-top:20px;padding-top:16px;border-top:1px solid rgba(255,255,255,.25);position:relative;}
.promo-cities-title{font-size:.72rem;font-weight:700;opacity:.7;text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px;}
.city-tags{display:flex;flex-wrap:wrap;gap:6px;}
.city-tag{background:rgba(255,255,255,.2);border-radius:50px;padding:4px 11px;font-size:.72rem;font-weight:600;}

/* ── Email Preview ────────────────────────────────────────────────────────── */
.email-preview-wrap{grid-column:1/-1;animation:slideIn .3s ease;}
@keyframes slideIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
.email-preview{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.08),0 0 0 1px rgba(0,0,0,.04);overflow:hidden;}
.email-preview-hd{background:#f8f8f8;border-bottom:1px solid #ebebeb;padding:14px 20px;display:flex;align-items:center;gap:10px;}
.email-hd-icon{width:32px;height:32px;background:#FFC40C;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.email-hd-icon svg{width:16px;height:16px;fill:#fff;}
.email-hd-text{flex:1;}
.email-hd-title{font-size:.8rem;font-weight:700;color:#2c2c2c;}
.email-hd-sub{font-size:.7rem;color:#888;}
.ssti-badge{background:#fff3cd;color:#856404;border:1px solid #ffc107;border-radius:4px;padding:2px 8px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-left:auto;}

.inbox-card{margin:16px;border:1px solid #e8e8e8;border-radius:8px;overflow:hidden;}
.inbox-meta{padding:12px 16px;border-bottom:1px solid #f0f0f0;display:grid;gap:4px;}
.inbox-meta-row{display:flex;gap:8px;font-size:.78rem;}
.meta-lbl{color:#aaa;min-width:48px;flex-shrink:0;}
.meta-val{color:#2c2c2c;font-weight:500;}
.meta-val.ssti-output{color:#e53e3e;font-weight:700;background:#fff5f5;border:1px solid #fed7d7;border-radius:3px;padding:1px 6px;}
.inbox-body{padding:16px;font-size:.85rem;color:#444;line-height:1.8;white-space:pre-wrap;background:#fff;}

.template-source{margin:0 16px 16px;}
.tpl-toggle{display:flex;align-items:center;gap:6px;font-size:.75rem;color:#FFC40C;font-weight:700;cursor:pointer;padding:8px 0;border:none;background:none;font-family:inherit;}
.tpl-toggle svg{width:14px;height:14px;fill:#FFC40C;transition:transform .2s;}
.tpl-toggle.open svg{transform:rotate(90deg);}
.tpl-source-box{display:none;background:#1e1e2e;border-radius:6px;padding:12px 14px;font-family:'Courier New',monospace;font-size:.72rem;line-height:1.8;}
.tpl-line{color:#6272a4;}
.tpl-key{color:#82aaff;}
.tpl-expr{color:#FF6A00;background:rgba(255,106,0,.12);border-radius:2px;padding:0 3px;}
.tpl-normal{color:#cdd6f4;}

/* ── Report panel ─────────────────────────────────────────────────────────── */
.report-panel{grid-column:1/-1;background:#fff;border-radius:12px;padding:20px 24px;box-shadow:0 1px 4px rgba(0,0,0,.08),0 0 0 1px rgba(0,0,0,.04);}
.report-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;}
.report-item{}
.report-item-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#aaa;margin-bottom:4px;}
.report-item-val{font-size:.82rem;color:#2c2c2c;font-weight:600;}
.report-item-val a{color:#FFC40C;text-decoration:none;font-weight:700;}
.severity-badge{background:#fff3cd;color:#856404;border:1px solid #ffc107;border-radius:4px;padding:1px 8px;font-size:.78rem;font-weight:700;}
.severity-badge.high{background:#fff5f5;color:#c53030;border-color:#feb2b2;}

/* ── Tabs ─────────────────────────────────────────────────────────────────── */
.tab-bar{display:flex;gap:0;border-bottom:2px solid #ebebeb;margin-bottom:20px;}
.tab-btn{flex:1;background:none;border:none;padding:10px;font-size:.82rem;font-weight:700;color:#aaa;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;font-family:inherit;transition:color .15s;}
.tab-btn.active{color:#2c2c2c;border-bottom-color:#FFC40C;}
.tab-btn:hover:not(.active){color:#555;}
.tab-panel{display:none;}
.tab-panel.active{display:block;}

/* ── User menu (nav) ──────────────────────────────────────────────────────── */
.user-menu{display:flex;align-items:center;gap:10px;}
.user-avatar{width:32px;height:32px;background:#FFC40C;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:800;color:#2c2c2c;flex-shrink:0;}
.user-name{font-size:.82rem;font-weight:700;color:#2c2c2c;}
.logout-btn{font-size:.78rem;color:#aaa;text-decoration:none;font-weight:500;padding:6px 12px;border:1px solid #e0e0e0;border-radius:50px;transition:background .1s,color .1s;}
.logout-btn:hover{background:#fff5f5;color:#c53030;border-color:#fed7d7;}

/* ── Account dashboard ────────────────────────────────────────────────────── */
.account-card{background:#fff;border-radius:12px;padding:28px;box-shadow:0 1px 4px rgba(0,0,0,.08),0 0 0 1px rgba(0,0,0,.04);}
.account-hd{display:flex;align-items:center;gap:14px;margin-bottom:20px;}
.account-avatar{width:52px;height:52px;background:linear-gradient(135deg,#FFC40C,#FF8C00);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:900;color:#fff;flex-shrink:0;}
.account-name{font-size:1.05rem;font-weight:800;color:#2c2c2c;}
.account-since{font-size:.72rem;color:#aaa;margin-top:2px;}
.account-fields{display:grid;gap:10px;}
.account-field{display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:#f8f8f8;border-radius:8px;}
.account-field-lbl{font-size:.72rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.05em;}
.account-field-val{font-size:.82rem;font-weight:600;color:#2c2c2c;}
.account-divider{height:1px;background:#ebebeb;margin:16px 0;}

/* ── Footer ───────────────────────────────────────────────────────────────── */
footer{background:#2c2c2c;color:#aaa;padding:20px 24px;font-size:.75rem;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:auto;}
footer a{color:#FFC40C;text-decoration:none;}
footer a:hover{text-decoration:underline;}
</style>
</head>
<body>

<!-- Nav -->
<nav class="nav">
  <a href="index.php" class="nav-logo">
    <div class="glovo-icon">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>
    </div>
    <span class="glovo-wordmark">Glovo</span>
  </a>
  <div class="nav-links">
    <a href="#" class="nav-link">Restaurants</a>
    <a href="#" class="nav-link">Groceries</a>
    <a href="#" class="nav-link">Pharmacy</a>
    <?php if ($logged_in_user): ?>
    <div class="user-menu">
      <div class="user-avatar"><?= esc(mb_strtoupper(mb_substr($logged_in_user['first_name'],0,1))) ?></div>
      <span class="user-name"><?= esc($logged_in_user['first_name']) ?></span>
      <a href="index.php?logout=1" class="logout-btn">Log out</a>
    </div>
    <?php else: ?>
    <a href="index.php" class="nav-btn">Sign up</a>
    <?php endif; ?>
  </div>
</nav>

<div class="page">
  <div class="page-grid">

  <?php if ($logged_in_user): ?>
    <!-- ── LOGGED IN: Account summary card ──────────────────────────────── -->
    <div class="account-card">
      <div class="account-hd">
        <div class="account-avatar"><?= esc(mb_strtoupper(mb_substr($logged_in_user['first_name'],0,1))) ?></div>
        <div>
          <div class="account-name"><?= esc($logged_in_user['first_name'] . ' ' . $logged_in_user['last_name']) ?></div>
          <div class="account-since">Member since <?= esc(date('M j, Y', strtotime($logged_in_user['registered_at']))) ?></div>
        </div>
      </div>
      <div class="account-fields">
        <div class="account-field">
          <span class="account-field-lbl">Email</span>
          <span class="account-field-val"><?= esc($logged_in_user['email']) ?></span>
        </div>
        <div class="account-field">
          <span class="account-field-lbl">City</span>
          <span class="account-field-val"><?= esc($logged_in_user['city']) ?></span>
        </div>
        <div class="account-field">
          <span class="account-field-lbl">First Name (raw)</span>
          <span class="account-field-val" style="color:#e53e3e;font-family:'Courier New',monospace;"><?= esc($logged_in_user['first_name']) ?></span>
        </div>
      </div>
      <div class="account-divider"></div>
      <a href="index.php?logout=1" style="display:inline-flex;align-items:center;gap:6px;font-size:.8rem;font-weight:700;color:#c53030;text-decoration:none;">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#c53030" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        Log out
      </a>
    </div>

  <?php else: ?>
    <!-- ── GUEST: Register / Sign In tabs ───────────────────────────────── -->
    <div class="card">
      <div class="tab-bar">
        <button class="tab-btn<?= $mode==='register' ? ' active' : '' ?>" onclick="switchTab('register')" id="tabRegister">Create account</button>
        <button class="tab-btn<?= $mode==='login' ? ' active' : '' ?>" onclick="switchTab('login')" id="tabLogin">Sign in</button>
      </div>

      <!-- Register panel -->
      <div class="tab-panel<?= $mode==='register' ? ' active' : '' ?>" id="panelRegister">
        <?php if (!empty($reg_errors)): ?>
        <div class="error-box" style="margin-bottom:14px;">
          <strong>Please fix the following:</strong>
          <ul><?php foreach ($reg_errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>
        <form method="POST" action="index.php" autocomplete="off">
          <input type="hidden" name="action" value="register">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">
                First Name<span class="required">*</span>
              </label>
              <input type="text" name="first_name" class="form-input vuln-field"
                     placeholder="First name"
                     value="<?= esc($form['first_name'] ?? '') ?>">
              <span class="form-hint">Used in your welcome email subject</span>
            </div>
            <div class="form-group">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" class="form-input"
                     placeholder="Last name"
                     value="<?= esc($form['last_name'] ?? '') ?>">
            </div>
          </div>
          <div class="form-row full">
            <div class="form-group">
              <label class="form-label">Email address<span class="required">*</span></label>
              <input type="email" name="email" class="form-input"
                     placeholder="you@example.com"
                     value="<?= esc($form['email'] ?? '') ?>">
            </div>
          </div>
          <div class="form-row full">
            <div class="form-group">
              <label class="form-label">Password<span class="required">*</span></label>
              <input type="password" name="password" class="form-input" placeholder="Min. 6 characters">
            </div>
          </div>
          <div class="form-row full">
            <div class="form-group">
              <label class="form-label">City</label>
              <select name="city" class="form-input">
                <?php
                $cities = ['Bishkek','Almaty','Tashkent','Tbilisi','Yerevan','Baku','Madrid','Barcelona','Rome','Milan','Warsaw','Kraków'];
                $sel = $form['city'] ?? 'Bishkek';
                foreach ($cities as $c): ?>
                <option value="<?= esc($c) ?>"<?= $c===$sel?' selected':'' ?>><?= esc($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <button type="submit" class="submit-btn">Create account</button>
          <div class="divider">or continue with</div>
          <button type="button" class="social-btn">
            <svg viewBox="0 0 24 24" width="16" height="16"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Continue with Google
          </button>
          <button type="button" class="social-btn">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            Continue with Facebook
          </button>
        </form>
        <div class="sign-in-link">Already have an account? <a href="#" onclick="switchTab('login');return false;">Sign in</a></div>
      </div>

      <!-- Login panel -->
      <div class="tab-panel<?= $mode==='login' ? ' active' : '' ?>" id="panelLogin">
        <?php if (!empty($login_errors)): ?>
        <div class="error-box" style="margin-bottom:14px;">
          <?php foreach ($login_errors as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="POST" action="index.php" autocomplete="off">
          <input type="hidden" name="action" value="login">
          <div class="form-row full">
            <div class="form-group">
              <label class="form-label">Email address<span class="required">*</span></label>
              <input type="email" name="login_email" class="form-input" placeholder="you@example.com"
                     value="<?= esc($_POST['login_email'] ?? '') ?>" autofocus>
            </div>
          </div>
          <div class="form-row full">
            <div class="form-group">
              <label class="form-label">Password<span class="required">*</span></label>
              <input type="password" name="login_password" class="form-input" placeholder="Your password">
            </div>
          </div>
          <button type="submit" class="submit-btn" style="margin-top:4px;">Sign in</button>
        </form>
        <div class="sign-in-link" style="margin-top:14px;">New to Glovo? <a href="#" onclick="switchTab('register');return false;">Create account</a></div>
      </div>
    </div>
  <?php endif; ?>

    <!-- Promo Panel -->
    <div class="promo-panel">
      <div class="promo-title">Delivered to your door in minutes</div>
      <div class="promo-sub">Join millions of customers ordering food, groceries, and more across 25+ countries.</div>
      <ul class="promo-features">
        <li>
          <svg viewBox="0 0 24 24"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zm-1 11H5V10h14v8z"/></svg>
          <div>
            <strong>Fast delivery</strong>
            <span>Average 30-min delivery from order to door</span>
          </div>
        </li>
        <li>
          <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
          <div>
            <strong>Available in your city</strong>
            <span>Bishkek, Almaty, Madrid, Rome and more</span>
          </div>
        </li>
        <li>
          <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
          <div>
            <strong>Personalised experience</strong>
            <span>Welcome email with your name — powered by Jinja2</span>
          </div>
        </li>
      </ul>
      <div class="promo-cities">
        <div class="promo-cities-title">Active in</div>
        <div class="city-tags">
          <span class="city-tag">Bishkek</span>
          <span class="city-tag">Almaty</span>
          <span class="city-tag">Madrid</span>
          <span class="city-tag">Rome</span>
          <span class="city-tag">Warsaw</span>
          <span class="city-tag">Tbilisi</span>
        </div>
      </div>
    </div>

    <!-- Report Metadata Row -->
    <div class="report-panel">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#aaa;margin-bottom:12px;">HackerOne Report Metadata</div>
      <div class="report-grid">
        <div class="report-item"><div class="report-item-label">Report</div><div class="report-item-val"><a href="https://hackerone.com/reports/1104349" target="_blank" rel="noopener">#1104349</a></div></div>
        <div class="report-item"><div class="report-item-label">Researcher</div><div class="report-item-val">battle_angel</div></div>
        <div class="report-item"><div class="report-item-label">Program</div><div class="report-item-val">Glovo</div></div>
        <div class="report-item"><div class="report-item-label">Severity</div><div class="report-item-val"><span class="severity-badge high">High</span></div></div>
        <div class="report-item"><div class="report-item-label">Weakness</div><div class="report-item-val">Code Injection (SSTI)</div></div>
        <div class="report-item"><div class="report-item-label">Reported</div><div class="report-item-val">Feb 16, 2021</div></div>
        <div class="report-item"><div class="report-item-label">Resolved</div><div class="report-item-val">May 4, 2021</div></div>
        <div class="report-item"><div class="report-item-label">Disclosed</div><div class="report-item-val">Jul 11, 2022</div></div>
      </div>
    </div>

    <?php if ($logged_in_user): ?>
    <!-- Email Preview (persistent — loaded from DB) -->
    <div class="email-preview-wrap">
      <div class="email-preview">
        <div class="email-preview-hd">
          <div class="email-hd-icon">
            <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
          </div>
          <div class="email-hd-text">
            <div class="email-hd-title">Welcome Email Sent</div>
            <div class="email-hd-sub">Template rendered server-side before delivery</div>
          </div>
          <span class="ssti-badge">SSTI Result</span>
        </div>

        <div class="inbox-card">
          <div class="inbox-meta">
            <div class="inbox-meta-row">
              <span class="meta-lbl">From</span>
              <span class="meta-val">Glovo &lt;noreply@glovo.com&gt;</span>
            </div>
            <div class="inbox-meta-row">
              <span class="meta-lbl">To</span>
              <span class="meta-val"><?= esc($logged_in_user['email']) ?></span>
            </div>
            <div class="inbox-meta-row">
              <span class="meta-lbl">Subject</span>
              <span class="meta-val ssti-output"><?= esc($logged_in_user['email_subject']) ?></span>
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
            <div class="tpl-line" style="margin-top:4px;"><span class="tpl-key">subject_template</span> = <span class="tpl-expr">first_name</span> + <span style="color:#c3e88d;">", welcome to Glovo!"</span></div>
            <div class="tpl-line" style="margin-top:2px;"><span class="tpl-key">body_template</span>    = <span style="color:#c3e88d;">"Hi "</span> + <span class="tpl-expr">first_name</span> + <span style="color:#c3e88d;">", ..."</span></div>
            <div class="tpl-line" style="margin-top:6px;"><span style="color:#6272a4;"># When first_name = "{{7*7}}", the template becomes:</span></div>
            <div class="tpl-line"><span class="tpl-key">subject_template</span> = <span class="tpl-expr">"{{7*7}}, welcome to Glovo!"</span></div>
            <div class="tpl-line" style="margin-top:2px;"><span style="color:#6272a4;"># twig_render() then evaluates {{7*7}} → 49</span></div>
            <div class="tpl-line" style="margin-top:4px;"><span class="tpl-key">Remaining template vars:</span></div>
            <div class="tpl-line">  city   = <span class="tpl-expr">{{city}}</span></div>
            <div class="tpl-line">  email  = <span class="tpl-expr">{{user['email']}}</span></div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<footer>
  <span>© 2021 Glovoapp23, S.L. All rights reserved — Lab simulation based on <a href="https://hackerone.com/reports/1104349" target="_blank" rel="noopener">HackerOne #1104349</a></span>
  <span>Glovo · Madrid, Spain</span>
</footer>

<script>
function switchTab(tab) {
    var tabs = ['register', 'login'];
    tabs.forEach(function(t) {
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
