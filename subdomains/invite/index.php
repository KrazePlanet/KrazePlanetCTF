<?php
// SSTI Lab 804 — Unikrn Smarty SSTI via Profile Fields in Invitation Email
// HackerOne Report #164224 (Unikrn)
// Vulnerable parameter: first_name / last_name / nickname (profile edit)
// Trigger: sending a friend invitation — profile name is concatenated into Smarty template
// Detection:  {7*7} → 49
// Version:    {$smarty.version} → 3.1.29
// RCE:        {php}echo $smarty['FLAG'];{/php}
// Flag via:   {$smarty.FLAG}  or  {php}echo $smarty['FLAG'];{/php}

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

function send_unikrn_invite_email($recipient_email, $sender_name, $subject, $body_text) {
    if (!$recipient_email) return false;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'mailpit';
        $mail->SMTPAuth   = false;
                $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
        $mail->Port       = 1025;
        $mail->setFrom('noreply@krazeplanet.com', 'Unikrn');
        $mail->addAddress($recipient_email);
        $mail->isHTML(true);
        $mail->Subject = $subject;

        $htmlMailBody = '
        <!DOCTYPE html>
        <html>
        <head><meta charset="utf-8"></head>
        <body style="font-family:Arial,sans-serif;background:#0d0d17;color:#ffffff;padding:20px;margin:0;">
        <div style="max-width:540px;margin:0 auto;background:#151525;border-radius:12px;border:1px solid #2d2d44;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.5);">
          <div style="background:#6d28d9;padding:24px;text-align:center;">
            <h2 style="color:#ffffff;margin:0;font-size:24px;font-weight:800;letter-spacing:-0.5px;">UNIKRN</h2>
          </div>
          <div style="padding:28px 24px;color:#e2e8f0;font-size:15px;line-height:1.7;">
            ' . nl2br($body_text) . '
          </div>
          <div style="background:#0d0d17;padding:16px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #2d2d44;">
            © 2016 Unikrn Inc. · Seattle, WA
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

// ── Smarty-like config (exposed to template — SSTI exfil target) ────────
$smarty_config = [
    'version'    => '3.1.29',
    'FLAG'       => 'flag{unikrn_smarty_ssti_invite_email_164224}',
    'APP_SECRET' => 'unikrn-app-sk-2016-r4nd0m-9x8z',
    'DB_HOST'    => 'db.unikrn.internal',
    'DB_PASS'    => 'Unikrn_DB_P@ss2016!',
    'API_KEY'    => 'unikrn-api-2016-x9y8z7w6',
    'ENV'        => 'production',
    'SMTP_HOST'  => 'mail.unikrn.com',
];

// ── Simulated Smarty template engine ─────────────────────────────────────
// BUG: profile name fields are concatenated directly into the template string
// before smarty_render() is called, so {php}...{/php} in a name executes PHP
function smarty_render($template, $context = []) {
    // Layer 0: strip {* comments *}
    $template = preg_replace('/\{\*.*?\*\}/s', '', $template);

    // Layer 1: {php}...{/php} — VULNERABLE: arbitrary PHP execution via eval
    $template = preg_replace_callback('/\{php\}(.*?)\{\/php\}/si', function($m) use ($context) {
        extract($context);
        ob_start();
        try {
            eval($m[1]);
        } catch (\Throwable $e) {
            ob_end_clean();
            return '[Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . ']';
        }
        $out = ob_get_clean();
        return $out !== false ? $out : '';
    }, $template);

    // Layer 2: {$smarty.KEY} — Smarty system variables
    $template = preg_replace_callback('/\{\$smarty\.(\w+)\}/i', function($m) use ($context) {
        $key = $m[1];
        if (isset($context['smarty'][$key]))            return (string)$context['smarty'][$key];
        if (isset($context['smarty'][strtoupper($key)])) return (string)$context['smarty'][strtoupper($key)];
        if ($key === 'version')                         return '3.1.29';
        return $m[0];
    }, $template);

    // Layer 3: {$variable} — simple variable substitution
    $template = preg_replace_callback('/\{\$(\w+)\}/', function($m) use ($context) {
        $key = $m[1];
        if (isset($context[$key])) {
            $v = $context[$key];
            return is_array($v) ? json_encode($v, JSON_PRETTY_PRINT) : (string)$v;
        }
        return $m[0];
    }, $template);

    // Layer 4: {expression} — arithmetic / simple expressions ({7*7} → 49)
    $template = preg_replace_callback('/\{([^$\*{\/][^}]*)\}/', function($m) use ($context) {
        $expr = trim($m[1]);
        if ($expr === '' || strpos($expr, 'php') === 0) return $m[0];
        $php_expr = preg_replace("/(?<!['\"\$\\\\])\\b([a-zA-Z_][a-zA-Z0-9_]*)\\b/", '\$$1', $expr);
        extract($context);
        $result = null;
        try {
            $result = @eval("return ($php_expr);");
        } catch (\Throwable $e) {
            return $m[0];
        }
        if ($result === false || $result === null) return $m[0];
        if (is_array($result) || is_object($result)) return json_encode($result);
        return (string)$result;
    }, $template);

    return $template;
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
$db->query("CREATE TABLE IF NOT EXISTS lab804_users (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    first_name       VARCHAR(255) NOT NULL DEFAULT '',
    last_name        VARCHAR(255) NOT NULL DEFAULT '',
    nickname         VARCHAR(255) NOT NULL DEFAULT '',
    email            VARCHAR(255) NOT NULL UNIQUE,
    password         VARCHAR(255) NOT NULL,
    invite_to_email  VARCHAR(255) DEFAULT NULL,
    invite_rendered  TEXT DEFAULT NULL,
    ssti_detected    TINYINT(1) DEFAULT 0,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// ── Logout ─────────────────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// ── Invitation email template ─────────────────────────────────────────────
// VULNERABLE: first_name, last_name, nickname are concatenated directly into
// the Smarty template string. Any Smarty syntax in those fields is executed.
function build_invite_template($u) {
    $fn = $u['first_name'];
    $ln = $u['last_name'];
    $nk = $u['nickname'];
    // BUG: string concatenation instead of safe variable substitution
    return
        "Hey there,\n\n" .
        "Your friend " . $fn . " " . $ln . " (known as " . $nk . " on Unikrn) " .
        "wants you to join the biggest esports wagering platform!\n\n" .
        $nk . " has been wagering on Unikrn since {\$member_since} and is personally inviting you.\n\n" .
        "Create your account and get started:\n" .
        "https://unikrn.com/invite?ref=" . urlencode($nk) . "\n\n" .
        "See you on the leaderboard,\n" .
        "The Unikrn Team\n" .
        "support@unikrn.com\n\n" .
        "{\$site_disclaimer}";
}

// ── State ──────────────────────────────────────────────────────────────────
$reg_errors     = [];
$login_errors   = [];
$profile_errors = [];
$invite_errors  = [];
$profile_ok     = false;
$invite_ok      = false;
$form           = [];
$mode           = 'register';

// ── Load session user ──────────────────────────────────────────────────────
$logged_in_user = null;
if (!empty($_SESSION['user804_id'])) {
    $sid  = (int)$_SESSION['user804_id'];
    $stmt = $db->prepare("SELECT * FROM lab804_users WHERE id = ?");
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
        $mode     = 'register';
        $nickname = trim($_POST['nickname']  ?? '');
        $email    = trim($_POST['email']     ?? '');
        $pw       = trim($_POST['password']  ?? '');

        if ($nickname === '')  $reg_errors[] = 'Nickname is required.';
        if ($email    === '')  $reg_errors[] = 'Email is required.';
        if (strlen($pw) < 6)  $reg_errors[] = 'Password must be at least 6 characters.';

        if (empty($reg_errors)) {
            $chk = $db->prepare("SELECT id FROM lab804_users WHERE email = ?");
            $chk->bind_param('s', $email);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $reg_errors[] = 'Email already registered. <a href="#" onclick="switchTab(\'login\');return false;" style="color:#a78bfa;">Sign in instead</a>.';
            }
            $chk->close();
        }

        if (empty($reg_errors)) {
            $pw_hash = password_hash($pw, PASSWORD_BCRYPT);
            $ins = $db->prepare("INSERT INTO lab804_users (nickname, email, password) VALUES (?,?,?)");
            $ins->bind_param('sss', $nickname, $email, $pw_hash);
            $ins->execute();
            $new_id = $db->insert_id;
            $ins->close();

            $_SESSION['user804_id'] = $new_id;
            header('Location: index.php');
            exit;
        }
        $form = compact('nickname', 'email');
    }

    // ── Login ────────────────────────────────────────────────────────────────
    elseif ($action === 'login') {
        $mode  = 'login';
        $email = trim($_POST['login_email']    ?? '');
        $pw    = trim($_POST['login_password'] ?? '');

        if ($email === '' || $pw === '') {
            $login_errors[] = 'Please enter your email and password.';
        } else {
            $stmt = $db->prepare("SELECT * FROM lab804_users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row && password_verify($pw, $row['password'])) {
                $_SESSION['user804_id'] = $row['id'];
                header('Location: index.php');
                exit;
            } else {
                $login_errors[] = 'Invalid email or password.';
            }
        }
    }

    // ── Save Profile ─────────────────────────────────────────────────────────
    elseif ($action === 'save_profile' && $logged_in_user) {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name']  ?? '');
        $nickname   = trim($_POST['nickname']   ?? '');

        if ($nickname === '') $profile_errors[] = 'Nickname is required.';

        if (empty($profile_errors)) {
            $upd = $db->prepare("UPDATE lab804_users SET first_name=?, last_name=?, nickname=?, updated_at=NOW() WHERE id=?");
            $upd->bind_param('sssi', $first_name, $last_name, $nickname, $logged_in_user['id']);
            $upd->execute();
            $upd->close();

            // Reload from DB
            $stmt = $db->prepare("SELECT * FROM lab804_users WHERE id = ?");
            $stmt->bind_param('i', $logged_in_user['id']);
            $stmt->execute();
            $logged_in_user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $profile_ok = true;
        }
    }

    // ── Send Invite ──────────────────────────────────────────────────────────
    elseif ($action === 'send_invite' && $logged_in_user) {
        $invite_email = trim($_POST['invite_email'] ?? '');

        if ($invite_email === '') {
            $invite_errors[] = "Friend's email is required.";
        } else {
            // Reload fresh user data
            $stmt = $db->prepare("SELECT * FROM lab804_users WHERE id = ?");
            $stmt->bind_param('i', $logged_in_user['id']);
            $stmt->execute();
            $u = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $context = [
                'smarty'           => $smarty_config,
                'config'           => $smarty_config,
                'flag'             => $smarty_config['FLAG'],
                'member_since'     => date('F Y', strtotime($u['created_at'])),
                'site_disclaimer'  => 'Unikrn is licensed and regulated. Gamble responsibly.',
                'site_name'        => 'Unikrn',
            ];

            // VULNERABLE: template is built by string concatenation with raw profile fields
            $body_tpl = build_invite_template($u);
            $invite_rendered = smarty_render($body_tpl, $context);

            // SSTI detection: any { } in profile fields means Smarty syntax was present
            $had_smarty = (
                strpos($u['first_name'] . $u['last_name'] . $u['nickname'], '{') !== false
            );
            $ssti = $had_smarty ? 1 : 0;

            $upd = $db->prepare("UPDATE lab804_users SET invite_to_email=?, invite_rendered=?, ssti_detected=?, updated_at=NOW() WHERE id=?");
            $upd->bind_param('ssii', $invite_email, $invite_rendered, $ssti, $u['id']);
            $upd->execute();
            $upd->close();

            send_unikrn_invite_email($invite_email, $u['nickname'], 'You have been invited to join Unikrn!', $invite_rendered);

            // Reload
            $stmt = $db->prepare("SELECT * FROM lab804_users WHERE id = ?");
            $stmt->bind_param('i', $u['id']);
            $stmt->execute();
            $logged_in_user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $invite_ok = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $logged_in_user ? 'Profile — Unikrn' : 'Unikrn — Esports Wagering' ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;background:#090d1a;color:#e5e7eb;font-size:14px;min-height:100vh;display:flex;flex-direction:column;}

/* ── Nav ──────────────────────────────────────────────────────────────────── */
.nav{background:#06091a;border-bottom:1px solid #1f2937;padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.unikrn-wordmark{font-size:1.2rem;font-weight:900;color:#fff;letter-spacing:.12em;text-transform:uppercase;}
.unikrn-wordmark span{color:#7c3aed;}
.nav-links{display:flex;align-items:center;gap:18px;}
.nav-link{font-size:.8rem;color:rgba(255,255,255,.5);text-decoration:none;font-weight:500;}
.nav-link:hover{color:#a78bfa;}
.nav-btn{background:#7c3aed;color:#fff;border:none;padding:8px 18px;border-radius:5px;font-size:.8rem;font-weight:700;cursor:pointer;text-decoration:none;letter-spacing:.03em;}
.nav-btn:hover{background:#6d28d9;}
.user-menu{display:flex;align-items:center;gap:10px;}
.user-avatar-sm{width:30px;height:30px;background:linear-gradient(135deg,#7c3aed,#a855f7);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.74rem;font-weight:900;color:#fff;flex-shrink:0;}
.user-name-nav{font-size:.8rem;font-weight:600;color:#e5e7eb;}
.logout-btn{font-size:.74rem;color:rgba(255,255,255,.4);text-decoration:none;font-weight:500;padding:5px 10px;border:1px solid rgba(255,255,255,.15);border-radius:4px;transition:all .15s;}
.logout-btn:hover{background:rgba(255,255,255,.08);color:#fff;}

/* ── Browser bar ─────────────────────────────────────────────────────────── */
.browser-bar{background:#0d1117;border-bottom:1px solid #1f2937;padding:6px 14px;display:flex;align-items:center;gap:10px;}
.browser-dots{display:flex;gap:5px;}
.browser-dot{width:10px;height:10px;border-radius:50%;}
.browser-url{background:#161b2a;border-radius:20px;padding:4px 14px;font-family:'Courier New',monospace;font-size:.7rem;color:#6b7280;border:1px solid #1f2937;min-width:320px;}

/* ── Layout ───────────────────────────────────────────────────────────────── */
.page{flex:1;padding:26px 16px 48px;}
.page-inner{max-width:980px;margin:0 auto;}

/* ── Profile grid ─────────────────────────────────────────────────────────── */
.profile-grid{display:grid;grid-template-columns:220px 1fr;gap:20px;align-items:start;}
@media(max-width:720px){.profile-grid{grid-template-columns:1fr;}}

/* ── Sidebar ──────────────────────────────────────────────────────────────── */
.sidebar{background:#111827;border:1px solid #1f2937;border-radius:10px;padding:20px;color:#e5e7eb;overflow:hidden;position:relative;}
.sidebar::before{content:'';position:absolute;top:-40px;right:-40px;width:120px;height:120px;background:radial-gradient(circle,rgba(124,58,237,.25),transparent 70%);pointer-events:none;}
.sidebar-avatar{width:52px;height:52px;background:linear-gradient(135deg,#7c3aed,#a855f7);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:900;color:#fff;margin:0 auto 10px;}
.sidebar-nick{font-size:.88rem;font-weight:700;color:#fff;text-align:center;margin-bottom:2px;word-break:break-all;}
.sidebar-email{font-size:.7rem;color:rgba(255,255,255,.35);text-align:center;margin-bottom:12px;word-break:break-all;}
.level-badge{display:flex;align-items:center;justify-content:center;gap:6px;background:#1f2937;border-radius:5px;padding:5px 10px;margin-bottom:12px;}
.level-badge svg{width:12px;height:12px;fill:#f59e0b;}
.level-badge span{font-size:.72rem;font-weight:700;color:#f59e0b;letter-spacing:.04em;}
.sidebar-divider{height:1px;background:rgba(255,255,255,.06);margin:10px 0;}
.sidebar-row{display:flex;justify-content:space-between;align-items:center;padding:4px 0;}
.sidebar-lbl{font-size:.64rem;font-weight:700;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:.07em;}
.sidebar-val{font-size:.73rem;color:rgba(255,255,255,.6);font-weight:500;}
.sidebar-logout{display:flex;align-items:center;gap:6px;font-size:.74rem;font-weight:600;color:rgba(255,255,255,.35);text-decoration:none;margin-top:12px;padding:7px 0;border-top:1px solid rgba(255,255,255,.06);transition:color .15s;}
.sidebar-logout:hover{color:#ef4444;}
.sidebar-logout svg{width:12px;height:12px;stroke:currentColor;flex-shrink:0;}

/* ── Cards ────────────────────────────────────────────────────────────────── */
.card{background:#111827;border:1px solid #1f2937;border-radius:10px;padding:20px;margin-bottom:16px;}
.card-title{font-size:.92rem;font-weight:800;color:#f9fafb;margin-bottom:3px;display:flex;align-items:center;gap:8px;}
.card-title svg{width:14px;height:14px;fill:currentColor;color:#7c3aed;}
.card-sub{font-size:.74rem;color:#6b7280;margin-bottom:16px;}

/* ── Forms ────────────────────────────────────────────────────────────────── */
.form-group{display:flex;flex-direction:column;gap:4px;margin-bottom:12px;}
.form-label{font-size:.72rem;font-weight:700;color:#9ca3af;letter-spacing:.03em;}
.required{color:#ef4444;margin-left:2px;}
.form-input{background:#0d1117;border:1.5px solid #1f2937;border-radius:6px;padding:9px 12px;font-size:.86rem;color:#f9fafb;outline:none;transition:border-color .15s,box-shadow .15s;font-family:inherit;}
.form-input:focus{border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.15);}
.form-hint{font-size:.67rem;color:#4b5563;}
.form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:10px;}

/* ── Buttons ──────────────────────────────────────────────────────────────── */
.btn-purple{background:#7c3aed;color:#fff;border:none;padding:10px 20px;border-radius:6px;font-size:.84rem;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;letter-spacing:.02em;}
.btn-purple:hover{background:#6d28d9;}
.btn-purple:active{transform:scale(.98);}
.btn-full{width:100%;}
.btn-outline{background:transparent;color:#7c3aed;border:1.5px solid #7c3aed;padding:10px 20px;border-radius:6px;font-size:.84rem;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s;}
.btn-outline:hover{background:rgba(124,58,237,.12);}

/* ── Tabs ─────────────────────────────────────────────────────────────────── */
.tab-bar{display:flex;gap:0;border-bottom:1px solid #1f2937;margin-bottom:16px;}
.tab-btn{flex:1;background:none;border:none;padding:10px;font-size:.8rem;font-weight:700;color:#4b5563;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;font-family:inherit;transition:color .15s;}
.tab-btn.active{color:#a78bfa;border-bottom-color:#7c3aed;}
.tab-btn:hover:not(.active){color:#9ca3af;}
.tab-panel{display:none;}
.tab-panel.active{display:block;}

/* ── Alerts ───────────────────────────────────────────────────────────────── */
.error-box{background:#1f0a0a;border:1px solid #7f1d1d;border-radius:6px;padding:10px 13px;margin-bottom:12px;font-size:.77rem;color:#fca5a5;}
.error-box ul{padding-left:16px;margin-top:3px;}
.success-box{background:#0a1f12;border:1px solid #14532d;border-radius:6px;padding:10px 13px;margin-bottom:12px;font-size:.77rem;color:#86efac;display:flex;align-items:center;gap:7px;}
.success-box svg{width:13px;height:13px;flex-shrink:0;}

/* ── Social / divider ────────────────────────────────────────────────────── */
.divider{display:flex;align-items:center;gap:10px;margin:12px 0;color:#374151;font-size:.74rem;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#1f2937;}
.social-btn{display:flex;align-items:center;justify-content:center;gap:8px;border:1px solid #1f2937;border-radius:6px;padding:9px;font-size:.78rem;font-weight:600;color:#9ca3af;cursor:pointer;background:#0d1117;transition:background .1s;width:100%;margin-bottom:6px;font-family:inherit;}
.social-btn:hover{background:#161b2a;}
.sign-in-link{text-align:center;font-size:.76rem;color:#4b5563;margin-top:10px;}
.sign-in-link a{color:#a78bfa;font-weight:700;text-decoration:none;}
.sign-in-link a:hover{text-decoration:underline;}

/* ── Promo panel ──────────────────────────────────────────────────────────── */
.promo-panel{background:linear-gradient(145deg,#12172b,#1a1040);border:1px solid #2d1f6e;border-radius:10px;padding:24px;color:#fff;position:relative;overflow:hidden;}
.promo-panel::before{content:'';position:absolute;top:-60px;right:-60px;width:200px;height:200px;background:radial-gradient(circle,rgba(124,58,237,.2),transparent 70%);pointer-events:none;}
.promo-panel::after{content:'';position:absolute;bottom:-40px;left:-40px;width:160px;height:160px;background:radial-gradient(circle,rgba(168,85,247,.15),transparent 70%);pointer-events:none;}
.promo-title{font-size:1.15rem;font-weight:900;margin-bottom:6px;line-height:1.35;position:relative;text-transform:uppercase;letter-spacing:.04em;}
.promo-title em{color:#a78bfa;font-style:normal;}
.promo-sub{font-size:.78rem;opacity:.6;margin-bottom:16px;position:relative;line-height:1.65;}
.promo-features{list-style:none;position:relative;}
.promo-features li{display:flex;align-items:flex-start;gap:10px;margin-bottom:12px;font-size:.78rem;}
.promo-dot{width:6px;height:6px;background:#a78bfa;border-radius:50%;flex-shrink:0;margin-top:5px;}
.promo-features strong{display:block;font-weight:700;color:#e5e7eb;margin-bottom:1px;}
.promo-features span{opacity:.55;font-size:.74rem;}

/* ── Guest grid ───────────────────────────────────────────────────────────── */
.guest-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;}
@media(max-width:720px){.guest-grid{grid-template-columns:1fr;}}

/* ── Invite email preview ────────────────────────────────────────────────── */
.email-preview-wrap{animation:slideIn .3s ease;}
@keyframes slideIn{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
.email-preview{background:#111827;border:1px solid #1f2937;border-radius:10px;overflow:hidden;}
.email-preview-hd{background:#0d1117;border-bottom:1px solid #1f2937;padding:12px 16px;display:flex;align-items:center;gap:10px;}
.email-hd-icon{width:28px;height:28px;background:linear-gradient(135deg,#7c3aed,#a855f7);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.email-hd-icon svg{width:13px;height:13px;fill:#fff;}
.email-hd-text{flex:1;}
.email-hd-title{font-size:.76rem;font-weight:700;color:#f9fafb;}
.email-hd-sub{font-size:.66rem;color:#4b5563;}
.ssti-badge{background:#3b0764;color:#d8b4fe;border:1px solid #6d28d9;border-radius:4px;padding:2px 8px;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-left:auto;}
.inbox-card{margin:12px;border:1px solid #1f2937;border-radius:6px;overflow:hidden;}
.inbox-meta{padding:10px 13px;border-bottom:1px solid #1f2937;display:grid;gap:3px;background:#0d1117;}
.inbox-meta-row{display:flex;gap:8px;font-size:.74rem;}
.meta-lbl{color:#374151;min-width:52px;flex-shrink:0;}
.meta-val{color:#e5e7eb;font-weight:500;}
.meta-val.ssti-output{color:#f87171;font-weight:700;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);border-radius:3px;padding:1px 6px;font-size:.7rem;}
.inbox-body{padding:14px;font-size:.8rem;color:#9ca3af;line-height:1.85;white-space:pre-wrap;background:#111827;}

/* ── Template source ──────────────────────────────────────────────────────── */
.template-source{margin:0 12px 12px;}
.tpl-toggle{display:flex;align-items:center;gap:6px;font-size:.69rem;color:#374151;font-weight:700;cursor:pointer;padding:7px 0;border:none;background:none;font-family:inherit;transition:color .15s;}
.tpl-toggle:hover{color:#9ca3af;}
.tpl-toggle svg{width:11px;height:11px;fill:#374151;transition:transform .2s;}
.tpl-toggle.open svg{transform:rotate(90deg);}
.tpl-source-box{display:none;background:#0d1117;border-radius:6px;padding:10px 12px;font-family:'Courier New',monospace;font-size:.69rem;line-height:1.85;border:1px solid #1f2937;}
.tpl-comment{color:#4b5563;}
.tpl-key{color:#82aaff;}
.tpl-expr{color:#c3e88d;background:rgba(195,232,141,.08);border-radius:2px;padding:0 3px;}
.tpl-php{color:#f87171;background:rgba(248,113,113,.08);border-radius:2px;padding:0 3px;}

/* ── Report panel ─────────────────────────────────────────────────────────── */
.report-panel{background:#111827;border:1px solid #1f2937;border-radius:10px;padding:16px 20px;margin-top:16px;}
.report-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(165px,1fr));gap:12px;}
.report-item-label{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#374151;margin-bottom:3px;}
.report-item-val{font-size:.78rem;color:#e5e7eb;font-weight:600;}
.report-item-val a{color:#a78bfa;text-decoration:none;font-weight:700;}
.report-item-val a:hover{text-decoration:underline;}
.severity-badge{background:#1c0a40;color:#c4b5fd;border:1px solid #4c1d95;border-radius:3px;padding:1px 7px;font-size:.72rem;font-weight:700;}

/* ── Profile fields summary ───────────────────────────────────────────────── */
.fields-summary{background:#0d1117;border:1px solid #1f2937;border-radius:6px;padding:10px 13px;margin-bottom:14px;font-size:.76rem;line-height:2;}
.field-row{display:flex;gap:6px;align-items:baseline;}
.field-lbl{color:#4b5563;min-width:90px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;}
.field-val{color:#e5e7eb;font-family:'Courier New',monospace;font-size:.78rem;word-break:break-all;}
.field-val.has-payload{color:#f59e0b;}

/* ── Footer ───────────────────────────────────────────────────────────────── */
footer{background:#06091a;border-top:1px solid #1f2937;color:rgba(255,255,255,.25);padding:16px 24px;font-size:.7rem;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:auto;}
footer a{color:rgba(255,255,255,.35);text-decoration:none;}
footer a:hover{color:#a78bfa;}
</style>
</head>
<body>

<!-- Nav -->
<nav class="nav">
  <a href="index.php" class="nav-logo">
    <span class="unikrn-wordmark">UNI<span>KRN</span></span>
  </a>
  <div class="nav-links">
    <a href="#" class="nav-link">Esports</a>
    <a href="#" class="nav-link">Casino</a>
    <a href="#" class="nav-link">Leaderboard</a>
    <?php if ($logged_in_user): ?>
    <div class="user-menu">
      <div class="user-avatar-sm"><?= esc(mb_strtoupper(mb_substr($logged_in_user['nickname'] ?: $logged_in_user['email'], 0, 1, 'UTF-8'))) ?></div>
      <span class="user-name-nav"><?= esc($logged_in_user['nickname'] ?: $logged_in_user['email']) ?></span>
      <a href="index.php?logout=1" class="logout-btn">Log out</a>
    </div>
    <?php else: ?>
    <a href="index.php" class="nav-btn">Sign up</a>
    <?php endif; ?>
  </div>
</nav>

<?php if ($logged_in_user): ?>
<!-- Browser bar -->
<div class="browser-bar">
  <div class="browser-dots">
    <div class="browser-dot" style="background:#FF5F57;"></div>
    <div class="browser-dot" style="background:#FEBC2E;"></div>
    <div class="browser-dot" style="background:#28C840;"></div>
  </div>
  <div class="browser-url">🔒 unikrn.com/account/profile</div>
</div>
<?php endif; ?>

<div class="page">
<div class="page-inner">

<?php if ($logged_in_user): ?>
  <!-- ── LOGGED IN ─────────────────────────────────────────────────────── -->
  <?php
  $nick  = $logged_in_user['nickname']   ?: '—';
  $fn    = $logged_in_user['first_name'] ?: '';
  $ln    = $logged_in_user['last_name']  ?: '';
  $display_name = trim($fn . ' ' . $ln) ?: $nick;
  $has_payload  = strpos($fn . $ln . $nick, '{') !== false;
  $ssti_fired   = (bool)$logged_in_user['ssti_detected'];
  $invite_shown = !empty($logged_in_user['invite_rendered']);
  ?>
  <div class="profile-grid">

    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-avatar"><?= esc(mb_strtoupper(mb_substr($nick, 0, 1, 'UTF-8'))) ?></div>
      <div class="sidebar-nick"><?= esc($nick) ?></div>
      <div class="sidebar-email"><?= esc($logged_in_user['email']) ?></div>
      <div class="level-badge">
        <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <span>Level 7 Bettor</span>
      </div>
      <div class="sidebar-divider"></div>
      <div class="sidebar-row">
        <span class="sidebar-lbl">Member since</span>
        <span class="sidebar-val"><?= esc(date('M Y', strtotime($logged_in_user['created_at']))) ?></span>
      </div>
      <div class="sidebar-row">
        <span class="sidebar-lbl">Status</span>
        <span class="sidebar-val" style="color:#10b981;">● Active</span>
      </div>
      <div class="sidebar-row">
        <span class="sidebar-lbl">Invites sent</span>
        <span class="sidebar-val"><?= $invite_shown ? '1' : '0' ?></span>
      </div>
      <a href="index.php?logout=1" class="sidebar-logout">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        Log out
      </a>
    </div>

    <!-- Main content -->
    <div class="main-content">

      <!-- Step 1: Profile Settings -->
      <div class="card">
        <div class="card-title">
          <svg viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          Profile Settings
          <span style="font-size:.68rem;font-weight:600;background:#1f2937;color:#6b7280;padding:2px 8px;border-radius:4px;margin-left:auto;">Step 1 — Set your name</span>
        </div>
        <div class="card-sub">Your name fields are used in invitation emails sent to friends</div>

        <?php if (!empty($profile_errors)): ?>
        <div class="error-box"><?= esc($profile_errors[0]) ?></div>
        <?php endif; ?>
        <?php if ($profile_ok): ?>
        <div class="success-box">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Profile saved. Now invite a friend to trigger the template rendering.
        </div>
        <?php endif; ?>

        <form method="POST" action="index.php" autocomplete="off">
          <input type="hidden" name="action" value="save_profile">
          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" class="form-input"
                     value="<?= esc($logged_in_user['first_name']) ?>"
                     placeholder="First name">
            </div>
            <div class="form-group">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" class="form-input"
                     value="<?= esc($logged_in_user['last_name']) ?>"
                     placeholder="Last name">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Nickname<span class="required">*</span></label>
            <input type="text" name="nickname" class="form-input"
                   value="<?= esc($logged_in_user['nickname']) ?>"
                   placeholder="Your gamer tag">
            <span class="form-hint">Shown in invitation emails and on the leaderboard</span>
          </div>
          <button type="submit" class="btn-purple">Save profile</button>
        </form>
      </div>

      <!-- Step 2: Invite a Friend -->
      <div class="card">
        <div class="card-title">
          <svg viewBox="0 0 24 24"><path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
          Invite a Friend
          <span style="font-size:.68rem;font-weight:600;background:#1f2937;color:#6b7280;padding:2px 8px;border-radius:4px;margin-left:auto;">Step 2 — Send invite to trigger rendering</span>
        </div>
        <div class="card-sub">Your current profile name will be used in the invitation email — the template is rendered using Smarty server-side</div>

        <?php if ($has_payload): ?>
        <div class="fields-summary">
          <div class="field-row"><span class="field-lbl">First Name</span><span class="field-val has-payload"><?= esc($fn ?: '(empty)') ?></span></div>
          <div class="field-row"><span class="field-lbl">Last Name</span><span class="field-val has-payload"><?= esc($ln ?: '(empty)') ?></span></div>
          <div class="field-row"><span class="field-lbl">Nickname</span><span class="field-val has-payload"><?= esc($nick) ?></span></div>
          <div style="font-size:.66rem;color:#f59e0b;margin-top:4px;">⚠ Smarty syntax detected in profile fields — will be rendered in email</div>
        </div>
        <?php else: ?>
        <div class="fields-summary">
          <div class="field-row"><span class="field-lbl">First Name</span><span class="field-val"><?= esc($fn ?: '(empty)') ?></span></div>
          <div class="field-row"><span class="field-lbl">Last Name</span><span class="field-val"><?= esc($ln ?: '(empty)') ?></span></div>
          <div class="field-row"><span class="field-lbl">Nickname</span><span class="field-val"><?= esc($nick) ?></span></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($invite_errors)): ?>
        <div class="error-box"><?= esc($invite_errors[0]) ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php" autocomplete="off">
          <input type="hidden" name="action" value="send_invite">
          <div class="form-group">
            <label class="form-label">Friend's Email<span class="required">*</span></label>
            <input type="email" name="invite_email" class="form-input"
                   value="<?= esc($_POST['invite_email'] ?? $logged_in_user['invite_to_email'] ?? '') ?>"
                   placeholder="friend@example.com">
            <span class="form-hint">This triggers rendering of the invitation email template</span>
          </div>
          <button type="submit" class="btn-purple">
            Send Invitation →
          </button>
        </form>
      </div>

      <!-- Step 3: Invitation Email Preview (shown after invite is sent) -->
      <?php if ($invite_shown): ?>
      <div class="email-preview-wrap">
        <div class="email-preview">
          <div class="email-preview-hd">
            <div class="email-hd-icon">
              <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            </div>
            <div class="email-hd-text">
              <div class="email-hd-title">Invitation Email Sent — Smarty Rendered Server-Side</div>
              <div class="email-hd-sub">Template processed before delivery via Smarty 3.1.29</div>
            </div>
            <?php if ($ssti_fired): ?>
            <span class="ssti-badge">SSTI / RCE</span>
            <?php endif; ?>
          </div>

          <div class="inbox-card">
            <div class="inbox-meta">
              <div class="inbox-meta-row">
                <span class="meta-lbl">From</span>
                <span class="meta-val">Unikrn &lt;no-reply@unikrn.com&gt;</span>
              </div>
              <div class="inbox-meta-row">
                <span class="meta-lbl">To</span>
                <span class="meta-val"><?= esc($logged_in_user['invite_to_email']) ?></span>
              </div>
              <div class="inbox-meta-row">
                <span class="meta-lbl">Subject</span>
                <span class="meta-val <?= $ssti_fired ? 'ssti-output' : '' ?>">
                  <?php
                  // Render subject line (vulnerable: nickname in subject)
                  $subj_raw = $logged_in_user['nickname'] . ' has invited you to join Unikrn!';
                  $subj_rendered = smarty_render($subj_raw, [
                      'smarty' => $smarty_config,
                      'config' => $smarty_config,
                      'flag'   => $smarty_config['FLAG'],
                      'member_since' => date('F Y', strtotime($logged_in_user['created_at'])),
                      'site_disclaimer' => 'Gamble responsibly.',
                      'site_name' => 'Unikrn',
                  ]);
                  echo esc($subj_rendered);
                  ?>
                </span>
              </div>
            </div>
            <div class="inbox-body"><?= esc($logged_in_user['invite_rendered']) ?></div>
          </div>

          <div class="template-source">
            <button class="tpl-toggle" id="tplToggle" onclick="toggleTpl()">
              <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
              View raw Smarty template (before rendering)
            </button>
            <div class="tpl-source-box" id="tplSource">
              <div class="tpl-comment">{* Unikrn invitation email template — Smarty 3.1.29 *}</div>
              <div class="tpl-comment" style="margin-top:4px;">{* BUG: name fields are concatenated into template string instead of being passed as safe Smarty variables *}</div>
              <div style="margin-top:6px;color:#6b7280;">Hey there,</div>
              <div style="margin-top:2px;">Your friend <span class="tpl-php">[first_name]</span> <span class="tpl-php">[last_name]</span> (known as <span class="tpl-php">[nickname]</span> on Unikrn)</div>
              <div>wants you to join the biggest esports wagering platform!</div>
              <div style="margin-top:4px;"><span class="tpl-php">[nickname]</span> has been wagering since <span class="tpl-expr">{$member_since}</span> ...</div>
              <div style="margin-top:4px;color:#6b7280;">Where <span class="tpl-php">[field]</span> = raw string concatenation (VULNERABLE)</div>
              <div style="margin-top:4px;color:#6b7280;">Where <span class="tpl-expr">{$var}</span> = safe Smarty variable (not vulnerable)</div>
              <div style="margin-top:6px;color:#f59e0b;">If nickname = <span class="tpl-php">{php}echo $smarty['FLAG'];{/php}</span></div>
              <div>→ template becomes: "... (known as <span class="tpl-php">{php}echo $smarty['FLAG'];{/php}</span> on Unikrn)"</div>
              <div>→ Smarty evaluates PHP block → outputs flag</div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /main-content -->
  </div><!-- /profile-grid -->

<?php else: ?>
  <!-- ── GUEST ──────────────────────────────────────────────────────────── -->
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
          <strong>Please fix:</strong>
          <ul><?php foreach ($reg_errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>
        <form method="POST" action="index.php" autocomplete="off">
          <input type="hidden" name="action" value="register">
          <div class="form-group">
            <label class="form-label">Nickname<span class="required">*</span></label>
            <input type="text" name="nickname" class="form-input"
                   placeholder="Your gamer tag"
                   value="<?= esc($form['nickname'] ?? '') ?>">
            <span class="form-hint">Your public username on Unikrn</span>
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
          <button type="submit" class="btn-purple btn-full">Create account</button>
          <div class="divider">or</div>
          <button type="button" class="social-btn">
            <svg viewBox="0 0 24 24" width="14" height="14"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
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
          <button type="submit" class="btn-purple btn-full">Sign in</button>
        </form>
        <div class="sign-in-link" style="margin-top:10px;">New to Unikrn? <a href="#" onclick="switchTab('register');return false;">Create account</a></div>
      </div>
    </div>

    <!-- Promo panel -->
    <div class="promo-panel">
      <div class="promo-title">Wager on <em>Esports</em>.<br>Win Real Money.</div>
      <div class="promo-sub">Join hundreds of thousands of esports fans betting on CS:GO, Dota 2, League of Legends and more — on the world's first licensed esports wagering platform.</div>
      <ul class="promo-features">
        <li>
          <div class="promo-dot"></div>
          <div>
            <strong>Live esports betting</strong>
            <span>Real-time odds on 30+ esports titles</span>
          </div>
        </li>
        <li>
          <div class="promo-dot"></div>
          <div>
            <strong>UnikoinGold rewards</strong>
            <span>Earn crypto rewards on every wager</span>
          </div>
        </li>
        <li>
          <div class="promo-dot"></div>
          <div>
            <strong>Invite & earn</strong>
            <span>Invite friends via personalised invitation emails — powered by Smarty templating</span>
          </div>
        </li>
      </ul>
    </div>

  </div><!-- /guest-grid -->
<?php endif; ?>

<!-- Report Metadata -->
<div class="report-panel">
  <div style="font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#374151;margin-bottom:10px;">HackerOne Report Metadata</div>
  <div class="report-grid">
    <div><div class="report-item-label">Report</div><div class="report-item-val"><a href="https://hackerone.com/reports/164224" target="_blank" rel="noopener">#164224</a></div></div>
    <div><div class="report-item-label">Researcher</div><div class="report-item-val">yaworsk (Pete Yaworski)</div></div>
    <div><div class="report-item-label">Program</div><div class="report-item-val">Unikrn</div></div>
    <div><div class="report-item-label">Severity</div><div class="report-item-val"><span class="severity-badge">Critical / RCE</span></div></div>
    <div><div class="report-item-label">Weakness</div><div class="report-item-val">Smarty SSTI → RCE</div></div>
    <div><div class="report-item-label">Engine</div><div class="report-item-val">Smarty 3.1.29</div></div>
    <div><div class="report-item-label">Reported</div><div class="report-item-val">Aug 29, 2016</div></div>
    <div><div class="report-item-label">Disclosed</div><div class="report-item-val">Aug 17, 2017</div></div>
  </div>
</div>

</div><!-- /page-inner -->
</div><!-- /page -->

<footer>
  <span>© 2016 Unikrn Inc. All rights reserved — Lab simulation based on <a href="https://hackerone.com/reports/164224" target="_blank" rel="noopener">HackerOne #164224</a></span>
  <span>unikrn.com · Seattle, WA</span>
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
