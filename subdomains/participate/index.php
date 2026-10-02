<?php
// BugBridge — coordinated vulnerability disclosure platform
// A realistic bug-bounty platform clone used for hands-on web-security practice.
// Students register researcher accounts, some enabling two-factor authentication,
// join programs, submit reports, and invite collaborators onto their reports.
//
// The intended flaw (mirrors HackerOne report #2571981): a program can require
// two-factor authentication before a researcher may SUBMIT a report to it — and
// that check is correctly enforced. But a report's OWNER can invite a second
// researcher as a collaborator, and when that collaborator accepts the invite,
// the acceptance handler never checks whether the invited account has 2FA
// enabled at all. A researcher who never turned on 2FA can still be added as a
// collaborator and read the full sensitive report — defeating the entire point
// of requiring 2FA to protect that data in the first place.

session_start();

$db_host     = getenv('DB_HOST') ?: 'localhost';
$db_username = getenv('DB_USER') ?: 'root';
$db_password = getenv('DB_PASS') ?: '';
$db_name     = getenv('DB_NAME') ?: 'KrazePlanet';

mysqli_report(MYSQLI_REPORT_OFF);
$db = @new mysqli($db_host, $db_username, $db_password);
if ($db->connect_error) { die('Service temporarily unavailable.'); }
$db->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->select_db($db_name);
$db->set_charset('utf8mb4');

$db->query("CREATE TABLE IF NOT EXISTS part_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    twofa_enabled TINYINT(1) NOT NULL DEFAULT 0,
    twofa_secret VARCHAR(32) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS part_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description VARCHAR(500) DEFAULT '',
    requires_2fa TINYINT(1) NOT NULL DEFAULT 0,
    min_bounty INT DEFAULT 100,
    max_bounty INT DEFAULT 5000,
    grad VARCHAR(120) DEFAULT 'linear-gradient(135deg,#0f172a,#1e293b)'
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS part_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    reporter_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    severity ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
    details TEXT NOT NULL,
    poc TEXT DEFAULT '',
    status VARCHAR(30) NOT NULL DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS part_collaborators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uniq_report_user (report_id, user_id)
)") or die('init error');

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function first_name($name) { $p = preg_split('/\s+/', trim($name)); return $p[0] ?: $name; }
function avatar_letter($name) { return strtoupper(mb_substr(trim($name), 0, 1) ?: 'U'); }
$AVATAR_COLORS = ['#0891b2','#7c3aed','#dc2626','#059669','#d97706','#2563eb'];
function avatar_color($seed) { global $AVATAR_COLORS; return $AVATAR_COLORS[crc32($seed) % count($AVATAR_COLORS)]; }
function sev_color($sev) {
    return match($sev) { 'Critical' => '#7f1d1d', 'High' => '#b91c1c', 'Medium' => '#b45309', default => '#3f6212' };
}

// ── Real RFC 6238 TOTP (works with Google Authenticator, Authy, etc.) ───────────
function base32_encode_totp($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($data) as $byte) $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
    $bits = str_pad($bits, (int)(ceil(strlen($bits) / 5) * 5), '0', STR_PAD_RIGHT);
    $out = '';
    foreach (str_split($bits, 5) as $chunk) $out .= $alphabet[bindec($chunk)];
    return $out;
}
function base32_decode_totp($b32) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
    $bits = '';
    foreach (str_split($b32) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos === false) continue;
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $bytes = '';
    foreach (str_split($bits, 8) as $byte) { if (strlen($byte) === 8) $bytes .= chr(bindec($byte)); }
    return $bytes;
}
function totp_new_secret() { return base32_encode_totp(random_bytes(10)); } // 16-char base32 secret
function totp_code_at($secretBase32, $timeStep, $period = 30, $digits = 6) {
    $key = base32_decode_totp($secretBase32);
    $counter = pack('J', (int)$timeStep); // 64-bit big-endian step counter
    $hash = hash_hmac('sha1', $counter, $key, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $truncated = substr($hash, $offset, 4);
    $value = unpack('N', $truncated)[1] & 0x7FFFFFFF;
    $code = $value % (10 ** $digits);
    return str_pad((string)$code, $digits, '0', STR_PAD_LEFT);
}
function totp_verify($secretBase32, $inputCode, $period = 30, $digits = 6, $window = 1) {
    $inputCode = preg_replace('/\s+/', '', (string)$inputCode);
    if (!preg_match('/^\d{6}$/', $inputCode)) return false;
    $currentStep = (int)floor(time() / $period);
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(totp_code_at($secretBase32, $currentStep + $i, $period, $digits), $inputCode)) return true;
    }
    return false;
}
function totp_provisioning_uri($secretBase32, $username, $issuer = 'BugBridge') {
    $label = rawurlencode($issuer . ':' . $username);
    $query = http_build_query(['secret' => $secretBase32, 'issuer' => $issuer, 'algorithm' => 'SHA1', 'digits' => 6, 'period' => 30], '', '&', PHP_QUERY_RFC3986);
    return "otpauth://totp/{$label}?{$query}";
}

// ── Seed programs ────────────────────────────────────────────────────────────────
$seed = [
    ['Nimbus Cloud', 'nimbus-cloud', 'Cloud storage & collaboration suite.', 1, 200, 8000, 'linear-gradient(135deg,#1d4ed8,#0891b2)'],
    ['Ledgerly', 'ledgerly', 'Small-business accounting SaaS.', 1, 300, 10000, 'linear-gradient(135deg,#065f46,#0891b2)'],
    ['Kettlebird Social', 'kettlebird', 'Social networking platform.', 0, 100, 3000, 'linear-gradient(135deg,#7c3aed,#db2777)'],
    ['Northwind Retail', 'northwind-retail', 'E-commerce platform for merchants.', 1, 250, 6000, 'linear-gradient(135deg,#b45309,#7c2d12)'],
    ['Pinboard Notes', 'pinboard-notes', 'Note-taking and task app.', 0, 50, 1500, 'linear-gradient(135deg,#0f172a,#334155)'],
];
$check = $db->query("SELECT COUNT(*) AS c FROM part_programs")->fetch_assoc();
if ((int)$check['c'] === 0) {
    $st = $db->prepare("INSERT INTO part_programs (name, slug, description, requires_2fa, min_bounty, max_bounty, grad) VALUES (?,?,?,?,?,?,?)");
    foreach ($seed as $s) { $st->bind_param('sssiiis', $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6]); $st->execute(); }
}

function current_user($db) {
    if (empty($_SESSION['uid'])) return null;
    $st = $db->prepare("SELECT * FROM part_users WHERE id=?");
    $st->bind_param('i', $_SESSION['uid']); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function find_user_by_username($db, $username) {
    $st = $db->prepare("SELECT * FROM part_users WHERE username=?");
    $st->bind_param('s', $username); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function all_programs($db) {
    $out = []; $r = $db->query("SELECT * FROM part_programs ORDER BY name"); while ($row = $r->fetch_assoc()) $out[] = $row; return $out;
}
function program_by_slug($db, $slug) {
    $st = $db->prepare("SELECT * FROM part_programs WHERE slug=?");
    $st->bind_param('s', $slug); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function program_by_id($db, $id) {
    $st = $db->prepare("SELECT * FROM part_programs WHERE id=?");
    $st->bind_param('i', $id); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function report_by_id($db, $id) {
    $st = $db->prepare("SELECT * FROM part_reports WHERE id=?");
    $st->bind_param('i', $id); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function my_reports($db, $userId) {
    $st = $db->prepare("SELECT r.*, p.name AS program_name, p.slug AS program_slug FROM part_reports r JOIN part_programs p ON p.id=r.program_id WHERE r.reporter_id=? ORDER BY r.id DESC");
    $st->bind_param('i', $userId); $st->execute();
    $out = []; $r = $st->get_result(); while ($row = $r->fetch_assoc()) $out[] = $row; return $out;
}
function collaborator_status($db, $reportId, $userId) {
    $st = $db->prepare("SELECT status FROM part_collaborators WHERE report_id=? AND user_id=?");
    $st->bind_param('ii', $reportId, $userId); $st->execute();
    $row = $st->get_result()->fetch_assoc();
    return $row['status'] ?? null;
}
function report_collaborators($db, $reportId) {
    $st = $db->prepare("SELECT c.*, u.name, u.username, u.twofa_enabled FROM part_collaborators c JOIN part_users u ON u.id=c.user_id WHERE c.report_id=? ORDER BY c.invited_at");
    $st->bind_param('i', $reportId); $st->execute();
    $out = []; $r = $st->get_result(); while ($row = $r->fetch_assoc()) $out[] = $row; return $out;
}
function my_pending_invites($db, $userId) {
    $st = $db->prepare("SELECT c.*, r.title, r.severity, r.program_id, p.name AS program_name, p.requires_2fa, u.name AS reporter_name
        FROM part_collaborators c
        JOIN part_reports r ON r.id = c.report_id
        JOIN part_programs p ON p.id = r.program_id
        JOIN part_users u ON u.id = r.reporter_id
        WHERE c.user_id=? AND c.status='pending' ORDER BY c.invited_at DESC");
    $st->bind_param('i', $userId); $st->execute();
    $out = []; $r = $st->get_result(); while ($row = $r->fetch_assoc()) $out[] = $row; return $out;
}

// ── Access control for a report ─────────────────────────────────────────────────
// THE BUG lives here: an accepted collaborator is granted access purely because
// their invite row says status='accepted' — there is no check that their account
// actually has twofa_enabled, even when the program's requires_2fa flag is set.
function can_view_report($db, $userId, $report) {
    if ((int)$report['reporter_id'] === (int)$userId) return true;
    $status = collaborator_status($db, $report['id'], $userId);
    return $status === 'accepted';
}

$action = $_GET['action'] ?? 'programs';
$me = current_user($db);

// ── Auth ──────────────────────────────────────────────────────────────────────
if ($action === 'logout') { session_destroy(); header('Location: index.php?action=login'); exit; }

$authError = '';
if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name  = trim($_POST['name'] ?? '');
        $username = strtolower(trim($_POST['username'] ?? ''));
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        if ($name === '' || !preg_match('/^[a-z0-9_]{3,20}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
            $authError = 'Enter your name, a username (3-20 lowercase letters/numbers/_), a valid email, and a password of at least 6 characters.';
        } else {
            $st = $db->prepare("SELECT id FROM part_users WHERE email=? OR username=?");
            $st->bind_param('ss', $email, $username); $st->execute();
            if ($st->get_result()->fetch_row()) {
                $authError = 'An account with that email or username already exists.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $st = $db->prepare("INSERT INTO part_users (name, username, email, password_hash) VALUES (?,?,?,?)");
                $st->bind_param('ssss', $name, $username, $email, $hash); $st->execute();
                $_SESSION['uid'] = $db->insert_id;
                header('Location: index.php'); exit;
            }
        }
    }
    render_auth('register', $authError);
    exit;
}
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        $st = $db->prepare("SELECT * FROM part_users WHERE email=?");
        $st->bind_param('s', $email); $st->execute();
        $u = $st->get_result()->fetch_assoc();
        if ($u && password_verify($pass, $u['password_hash'])) {
            $_SESSION['uid'] = $u['id'];
            header('Location: ' . ($_GET['next'] ?? 'index.php')); exit;
        }
        $authError = 'Invalid email or password.';
    }
    render_auth('login', $authError);
    exit;
}

if (!$me) {
    $next = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
    header('Location: index.php?action=login&next=' . $next);
    exit;
}
$myId = (int)$me['id'];

// ── Two-factor authentication — real RFC 6238 TOTP, scannable by any
// authenticator app (Google Authenticator, Authy, 1Password, etc.) ─────────────
if ($action === 'security') {
    $enableError = '';
    if (isset($_GET['bad_code'])) $enableError = 'That code was incorrect or has expired. Make sure your device\'s clock is accurate and try the newest code shown in your app.';
    render_head('Security'); render_topbar($me);
    ?>
    <div class="wrap" style="max-width:560px;">
      <h1>Two-factor authentication</h1>
      <?php if ($me['twofa_enabled']): ?>
        <div class="notice">✅ Two-factor authentication is <strong>enabled</strong> on your account.</div>
        <form method="POST" action="index.php?action=disable-2fa"><button class="btn" type="submit">Disable 2FA</button></form>
      <?php else: ?>
        <div class="warn">⚠ Two-factor authentication is <strong>not enabled</strong>. Some programs require it before you can submit a report.</div>
        <?php
        if (empty($_SESSION['twofa_pending_secret'])) { $_SESSION['twofa_pending_secret'] = totp_new_secret(); }
        $secret = $_SESSION['twofa_pending_secret'];
        $otpauth = totp_provisioning_uri($secret, $me['username'], 'BugBridge');
        ?>
        <?php if ($enableError): ?><div class="warn"><?php echo esc($enableError); ?></div><?php endif; ?>
        <div class="card">
          <p style="font-size:0.85rem;color:#94a3b8;">1. Scan this QR code with Google Authenticator, Authy, or any TOTP app:</p>
          <div id="qrcode" class="qr-box"></div>
          <p style="font-size:0.8rem;color:#64748b;margin-top:0.75rem;">Can't scan? Enter this key manually (time-based, 6 digits, 30s):</p>
          <div class="secret-box"><?php echo esc(chunk_split($secret, 4, ' ')); ?></div>
          <p style="font-size:0.85rem;color:#94a3b8;margin-top:1.25rem;">2. Enter the current 6-digit code from your app:</p>
          <form method="POST" action="index.php?action=enable-2fa">
            <input name="code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="123456" autocomplete="one-time-code" required autofocus>
            <button class="btn btn-primary" type="submit" style="margin-top:0.75rem;">Verify &amp; enable</button>
          </form>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
        new QRCode(document.getElementById("qrcode"), {
          text: <?php echo json_encode($otpauth); ?>,
          width: 200, height: 200,
          colorDark: "#0b1120", colorLight: "#ffffff"
        });
        </script>
      <?php endif; ?>
    </div>
    </body></html>
    <?php
    exit;
}
if ($action === 'enable-2fa' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $secret = $_SESSION['twofa_pending_secret'] ?? '';
    if ($secret !== '' && totp_verify($secret, $code)) {
        $st = $db->prepare("UPDATE part_users SET twofa_enabled=1, twofa_secret=? WHERE id=?");
        $st->bind_param('si', $secret, $myId); $st->execute();
        unset($_SESSION['twofa_pending_secret']);
        header('Location: index.php?action=security'); exit;
    }
    header('Location: index.php?action=security&bad_code=1'); exit;
}
if ($action === 'disable-2fa' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $db->prepare("UPDATE part_users SET twofa_enabled=0, twofa_secret=NULL WHERE id=?");
    $st->bind_param('i', $myId); $st->execute();
    header('Location: index.php?action=security'); exit;
}

// ── Submit a report (correctly enforces the program's 2FA requirement) ──────────
if ($action === 'submit-report') {
    $slug = $_GET['slug'] ?? '';
    $program = program_by_slug($db, $slug);
    if (!$program) { header('Location: index.php?action=programs'); exit; }

    $submitError = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // --- Correct enforcement: this is the ONE place the report says HackerOne
        // gets it right. A program that requires 2FA blocks submission outright. ---
        if ((int)$program['requires_2fa'] === 1 && (int)$me['twofa_enabled'] !== 1) {
            $submitError = 'This program requires two-factor authentication to submit reports. Enable 2FA in Settings → Security to continue.';
        } else {
            $title = trim($_POST['title'] ?? '');
            $sev   = $_POST['severity'] ?? 'Medium';
            $details = trim($_POST['details'] ?? '');
            $poc   = trim($_POST['poc'] ?? '');
            if ($title !== '' && $details !== '') {
                $st = $db->prepare("INSERT INTO part_reports (program_id, reporter_id, title, severity, details, poc) VALUES (?,?,?,?,?,?)");
                $st->bind_param('iissss', $program['id'], $myId, $title, $sev, $details, $poc);
                $st->execute();
                header('Location: index.php?action=report&id=' . $db->insert_id); exit;
            }
            $submitError = 'Title and vulnerability details are required.';
        }
    }
    render_head('Submit report'); render_topbar($me);
    ?>
    <div class="wrap" style="max-width:640px;">
      <a href="index.php?action=program&slug=<?php echo esc($slug); ?>" class="back-link">← <?php echo esc($program['name']); ?></a>
      <h1>Submit a report</h1>
      <?php if ($program['requires_2fa']): ?><div class="badge-2fa">🔒 This program requires 2FA to submit reports</div><?php endif; ?>
      <?php if ($submitError): ?><div class="warn"><?php echo esc($submitError); ?></div><?php endif; ?>
      <form method="POST" action="index.php?action=submit-report&slug=<?php echo esc($slug); ?>">
        <label>Title</label><input name="title" required>
        <label>Severity</label>
        <select name="severity">
          <option>Low</option><option selected>Medium</option><option>High</option><option>Critical</option>
        </select>
        <label>Vulnerability details</label><textarea name="details" rows="6" required placeholder="Describe the vulnerability, impact, and steps to reproduce..."></textarea>
        <label>Proof of concept (optional)</label><textarea name="poc" rows="4" placeholder="Requests, payloads, screenshots description..."></textarea>
        <button class="btn btn-primary" type="submit" style="margin-top:1rem;">Submit report</button>
      </form>
    </div>
    </body></html>
    <?php
    exit;
}

// ── VULNERABLE ENDPOINT — accept a collaborator invite ──────────────────────────
if ($action === 'accept-invite' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['invite_id'] ?? 0);
    $st = $db->prepare("SELECT * FROM part_collaborators WHERE id=? AND user_id=? AND status='pending'");
    $st->bind_param('ii', $id, $myId); $st->execute();
    $invite = $st->get_result()->fetch_assoc();
    if ($invite) {
        // --- THE BUG -----------------------------------------------------------
        // No lookup of the report's program.requires_2fa, and no check of
        // $me['twofa_enabled']. Any pending invite is accepted unconditionally,
        // granting full report access regardless of the program's 2FA policy.
        $st = $db->prepare("UPDATE part_collaborators SET status='accepted', responded_at=NOW() WHERE id=?");
        $st->bind_param('i', $invite['id']); $st->execute();
        // -----------------------------------------------------------------------
    }
    header('Location: index.php?action=report&id=' . ($invite['report_id'] ?? 0)); exit;
}
if ($action === 'decline-invite' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['invite_id'] ?? 0);
    $st = $db->prepare("UPDATE part_collaborators SET status='declined', responded_at=NOW() WHERE id=? AND user_id=? AND status='pending'");
    $st->bind_param('ii', $id, $myId); $st->execute();
    header('Location: index.php?action=invitations'); exit;
}

// ── Add a collaborator (report owner only) ───────────────────────────────────────
if ($action === 'add-collaborator' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportId = (int)($_POST['report_id'] ?? 0);
    $username = strtolower(trim($_POST['username'] ?? ''));
    $report = report_by_id($db, $reportId);
    if ($report && (int)$report['reporter_id'] === $myId) {
        $target = find_user_by_username($db, $username);
        if ($target && (int)$target['id'] !== $myId) {
            $st = $db->prepare("INSERT IGNORE INTO part_collaborators (report_id, user_id) VALUES (?,?)");
            $st->bind_param('ii', $reportId, $target['id']); $st->execute();
        }
    }
    header('Location: index.php?action=report&id=' . $reportId); exit;
}

$programs = all_programs($db);
$myProgramReports = my_reports($db, $myId);
$pendingInvites = my_pending_invites($db, $myId);

// =========================================================================
//  RENDER
// =========================================================================
function render_auth($mode, $error) {
    $isReg = ($mode === 'register');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $isReg ? 'Sign up' : 'Sign in'; ?> — BugBridge</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{margin:0;background:#0b1120;color:#e2e8f0;font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;}
.auth{width:400px;max-width:92vw;}
.logo-row{display:flex;align-items:center;gap:0.6rem;justify-content:center;font-weight:800;font-size:1.35rem;margin-bottom:1.75rem;}
.logo-row .mark{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#059669,#0891b2);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:700;}
.card{background:#111a2e;border:1px solid #1e293b;border-radius:16px;padding:2rem;}
.card h1{font-size:1.25rem;margin:0 0 0.35rem;}
.card p.s{color:#94a3b8;font-size:0.88rem;margin:0 0 1.5rem;}
label{display:block;font-size:0.8rem;color:#94a3b8;margin:0.9rem 0 0.35rem;font-weight:500;}
input,textarea,select{width:100%;background:#0b1120;border:1px solid #263349;color:#e2e8f0;border-radius:8px;padding:0.65rem 0.8rem;font-size:0.92rem;font-family:inherit;}
input:focus,textarea:focus,select:focus{outline:none;border-color:#0891b2;}
.btn-primary{width:100%;margin-top:1.4rem;background:#0891b2;border:none;color:#fff;padding:0.7rem;border-radius:8px;font-size:0.95rem;font-weight:600;cursor:pointer;}
.btn-primary:hover{background:#0e7490;}
.alt{text-align:center;margin-top:1.25rem;font-size:0.86rem;color:#94a3b8;}
.alt a{color:#22d3ee;text-decoration:none;font-weight:600;}
.err{background:#450a0a;border:1px solid #7f1d1d;color:#fca5a5;padding:0.7rem 0.9rem;border-radius:8px;font-size:0.85rem;margin-bottom:1rem;}
</style></head><body>
<div class="auth">
  <div class="logo-row"><span class="mark">BB</span> BugBridge</div>
  <div class="card">
    <h1><?php echo $isReg ? 'Create your account' : 'Welcome back'; ?></h1>
    <p class="s"><?php echo $isReg ? 'Find, report, and fix vulnerabilities together.' : 'Sign in to your researcher account.'; ?></p>
    <?php if ($error): ?><div class="err"><?php echo esc($error); ?></div><?php endif; ?>
    <form method="POST" action="index.php?action=<?php echo $isReg ? 'register' : 'login'; ?><?php echo isset($_GET['next']) ? '&next=' . esc($_GET['next']) : ''; ?>">
      <?php if ($isReg): ?>
        <label>Full name</label><input name="name" required>
        <label>Username</label><input name="username" placeholder="lowercase, no spaces" required>
      <?php endif; ?>
      <label>Email</label><input name="email" type="email" placeholder="you@example.com" required>
      <label>Password</label><input name="password" type="password" placeholder="••••••••" required>
      <button class="btn-primary" type="submit"><?php echo $isReg ? 'Sign up' : 'Sign in'; ?></button>
    </form>
    <div class="alt">
      <?php if ($isReg): ?>Already have an account? <a href="index.php?action=login">Sign in</a>
      <?php else: ?>New here? <a href="index.php?action=register">Create an account</a><?php endif; ?>
    </div>
  </div>
</div>
</body></html>
<?php
}

function render_head($title) {
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($title); ?> — BugBridge</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{margin:0;background:#0b1120;color:#e2e8f0;font-family:'Inter',sans-serif;}
a{color:inherit;}
::-webkit-scrollbar{width:8px;}
::-webkit-scrollbar-thumb{background:#263349;border-radius:4px;}
.topbar{background:#111a2e;border-bottom:1px solid #1e293b;position:sticky;top:0;z-index:10;}
.topbar-in{max-width:1080px;margin:0 auto;padding:0.85rem 1.5rem;display:flex;align-items:center;justify-content:space-between;}
.brand{display:flex;align-items:center;gap:0.55rem;font-weight:800;font-size:1.1rem;text-decoration:none;color:#e2e8f0;}
.brand .mark{width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#059669,#0891b2);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;}
.nav{display:flex;gap:1.3rem;align-items:center;}
.nav a{font-size:0.88rem;color:#94a3b8;text-decoration:none;font-weight:500;}
.nav a.active,.nav a:hover{color:#22d3ee;}
.nav .invite-link{position:relative;}
.nav .badge{position:absolute;top:-8px;right:-14px;background:#dc2626;color:#fff;font-size:0.62rem;font-weight:700;border-radius:10px;padding:0.05rem 0.4rem;}
.acct{display:flex;align-items:center;gap:0.6rem;}
.acct .av{width:28px;height:28px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.78rem;font-weight:700;}
.twofa-pill{font-size:0.68rem;padding:0.15rem 0.5rem;border-radius:10px;font-weight:600;}
.twofa-pill.on{background:#052e1a;color:#4ade80;border:1px solid #166534;}
.twofa-pill.off{background:#3f1d1d;color:#fca5a5;border:1px solid #7f1d1d;}
.btn{background:#1a2438;border:1px solid #263349;color:#e2e8f0;padding:0.4rem 0.9rem;border-radius:8px;font-size:0.84rem;cursor:pointer;font-weight:500;text-decoration:none;display:inline-flex;align-items:center;gap:0.4rem;}
.btn:hover{background:#212d47;}
.btn-primary{background:#0891b2;border:none;color:#fff;}
.btn-primary:hover{background:#0e7490;}
.wrap{max-width:1080px;margin:0 auto;padding:2rem 1.5rem;}
h1{font-size:1.5rem;margin:0 0 1.25rem;}
.sub{color:#94a3b8;font-size:0.9rem;margin-bottom:1.5rem;}
.back-link{color:#94a3b8;text-decoration:none;font-size:0.85rem;display:inline-block;margin-bottom:1rem;}
.back-link:hover{color:#22d3ee;}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.1rem;}
.pcard{background:#111a2e;border:1px solid #1e293b;border-radius:14px;overflow:hidden;text-decoration:none;color:inherit;display:block;}
.pcard:hover{border-color:#0891b2;}
.pcard .thumb{height:80px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.05rem;}
.pcard .body{padding:1rem 1.1rem;}
.pcard .dsc{font-size:0.8rem;color:#94a3b8;margin-top:0.3rem;}
.pcard .meta{display:flex;justify-content:space-between;align-items:center;margin-top:0.9rem;font-size:0.78rem;}
.pcard .bounty{color:#4ade80;font-weight:600;}
.badge-2fa{display:inline-block;background:#1e1033;color:#c4b5fd;border:1px solid #4c1d95;font-size:0.75rem;padding:0.25rem 0.65rem;border-radius:8px;margin-bottom:1rem;}
.notice{background:#052e1a;border:1px solid #166534;color:#86efac;padding:0.9rem 1.1rem;border-radius:10px;font-size:0.85rem;margin-bottom:1.25rem;}
.warn{background:#3f1d1d;border:1px solid #7f1d1d;color:#fca5a5;padding:0.9rem 1.1rem;border-radius:10px;font-size:0.85rem;margin-bottom:1.25rem;}
.empty{color:#64748b;font-size:0.9rem;padding:2.5rem 0;text-align:center;}
label{display:block;font-size:0.82rem;color:#94a3b8;margin:0.9rem 0 0.35rem;font-weight:500;}
input,textarea,select{width:100%;background:#0b1120;border:1px solid #263349;color:#e2e8f0;border-radius:8px;padding:0.65rem 0.8rem;font-size:0.9rem;font-family:inherit;}
input:focus,textarea:focus,select:focus{outline:none;border-color:#0891b2;}
.card{background:#111a2e;border:1px solid #1e293b;border-radius:14px;padding:1.5rem;margin-top:0.5rem;}
.secret-box{font-family:'JetBrains Mono',monospace;background:#0b1120;border:1px dashed #263349;color:#22d3ee;padding:0.75rem 1rem;border-radius:8px;letter-spacing:2px;margin:0.75rem 0;text-align:center;}
.qr-box{background:#fff;border-radius:10px;padding:1rem;display:inline-flex;align-items:center;justify-content:center;}
.sev-badge{display:inline-block;padding:0.2rem 0.65rem;border-radius:20px;font-size:0.76rem;font-weight:700;color:#fff;}
.report-row{background:#111a2e;border:1px solid #1e293b;border-radius:12px;padding:1rem 1.25rem;margin-bottom:0.7rem;display:flex;justify-content:space-between;align-items:center;text-decoration:none;color:inherit;}
.report-row:hover{border-color:#0891b2;}
.report-row .meta{font-size:0.78rem;color:#94a3b8;margin-top:0.2rem;}
.collab-row{display:flex;align-items:center;gap:0.7rem;padding:0.6rem 0;border-bottom:1px solid #1e293b;}
.collab-row .av{width:32px;height:32px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.78rem;font-weight:700;flex-shrink:0;}
.status-pill{font-size:0.72rem;padding:0.15rem 0.55rem;border-radius:10px;font-weight:600;margin-left:auto;}
.status-pill.pending{background:#3f2d0a;color:#fbbf24;border:1px solid #78350f;}
.status-pill.accepted{background:#052e1a;color:#4ade80;border:1px solid #166534;}
.invite-card{background:#111a2e;border:1px solid #1e293b;border-radius:12px;padding:1.1rem 1.3rem;margin-bottom:0.8rem;}
.invite-card .actions{display:flex;gap:0.5rem;margin-top:0.9rem;}
.details-block{background:#0b1120;border:1px solid #1e293b;border-radius:10px;padding:1.1rem;font-size:0.9rem;line-height:1.7;white-space:pre-wrap;margin-top:0.5rem;}
.locked-box{background:#1e1033;border:1px dashed #4c1d95;color:#c4b5fd;border-radius:12px;padding:2rem;text-align:center;font-size:0.9rem;}
</style></head><body>
<?php
}

function render_topbar($me) {
?>
<div class="topbar"><div class="topbar-in">
  <a href="index.php?action=programs" class="brand"><span class="mark">BB</span> BugBridge</a>
  <div class="nav">
    <a href="index.php?action=programs">Programs</a>
    <a href="index.php?action=my-reports">My Reports</a>
    <a href="index.php?action=invitations" class="invite-link">Invitations</a>
    <a href="index.php?action=security">Security</a>
  </div>
  <div class="acct">
    <span class="twofa-pill <?php echo $me['twofa_enabled']?'on':'off'; ?>"><?php echo $me['twofa_enabled'] ? '2FA on' : '2FA off'; ?></span>
    <span class="av" style="background:<?php echo avatar_color($me['username']); ?>;"><?php echo esc(avatar_letter($me['name'])); ?></span>
    <span style="font-size:0.85rem;">@<?php echo esc($me['username']); ?></span>
    <a href="index.php?action=logout" class="btn">Sign out</a>
  </div>
</div></div>
<?php
}

// =========================================================================
//  PAGES
// =========================================================================

if ($action === 'invitations') {
    render_head('Invitations'); render_topbar($me);
    ?>
    <div class="wrap" style="max-width:640px;">
      <h1>Collaboration invitations</h1>
      <?php if (empty($pendingInvites)): ?>
        <div class="empty">No pending invitations.</div>
      <?php else: foreach ($pendingInvites as $inv): ?>
      <div class="invite-card">
        <div style="font-weight:700;"><?php echo esc($inv['title']); ?></div>
        <div class="sub" style="margin:0.3rem 0 0;">Program: <?php echo esc($inv['program_name']); ?> <?php echo $inv['requires_2fa'] ? '<span class="badge-2fa">🔒 2FA required to submit</span>' : ''; ?></div>
        <div style="font-size:0.82rem;color:#94a3b8;">Invited by <strong><?php echo esc($inv['reporter_name']); ?></strong></div>
        <div class="actions">
          <form method="POST" action="index.php?action=accept-invite" style="display:inline;">
            <input type="hidden" name="invite_id" value="<?php echo (int)$inv['id']; ?>">
            <button class="btn btn-primary" type="submit">Accept</button>
          </form>
          <form method="POST" action="index.php?action=decline-invite" style="display:inline;">
            <input type="hidden" name="invite_id" value="<?php echo (int)$inv['id']; ?>">
            <button class="btn" type="submit">Decline</button>
          </form>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
    </body></html>
    <?php
    exit;
}

if ($action === 'my-reports') {
    render_head('My Reports'); render_topbar($me);
    ?>
    <div class="wrap" style="max-width:720px;">
      <h1>My reports</h1>
      <?php if (empty($myProgramReports)): ?>
        <div class="empty">You haven't submitted any reports yet. <a href="index.php?action=programs" style="color:#22d3ee;">Browse programs.</a></div>
      <?php else: foreach ($myProgramReports as $r): ?>
      <a class="report-row" href="index.php?action=report&id=<?php echo (int)$r['id']; ?>">
        <div>
          <div style="font-weight:600;"><?php echo esc($r['title']); ?></div>
          <div class="meta"><?php echo esc($r['program_name']); ?> · <?php echo esc($r['status']); ?></div>
        </div>
        <span class="sev-badge" style="background:<?php echo sev_color($r['severity']); ?>;"><?php echo esc($r['severity']); ?></span>
      </a>
      <?php endforeach; endif; ?>
    </div>
    </body></html>
    <?php
    exit;
}

if ($action === 'report') {
    $id = (int)($_GET['id'] ?? 0);
    $report = report_by_id($db, $id);
    render_head('Report'); render_topbar($me);
    if (!$report) { echo '<div class="wrap"><div class="empty">Report not found.</div></div></body></html>'; exit; }
    $program = program_by_id($db, $report['program_id']);
    $isOwner = ((int)$report['reporter_id'] === $myId);
    $allowed = can_view_report($db, $myId, $report);
    $collabs = report_collaborators($db, $id);
    ?>
    <div class="wrap" style="max-width:720px;">
      <a href="index.php?action=my-reports" class="back-link">← My Reports</a>
      <div style="display:flex;justify-content:space-between;align-items:flex-start;">
        <div>
          <h1 style="margin-bottom:0.3rem;"><?php echo esc($report['title']); ?></h1>
          <div class="sub" style="margin-top:0;">Program: <strong><?php echo esc($program['name']); ?></strong> <?php echo $program['requires_2fa'] ? '<span class="badge-2fa">🔒 2FA required to submit</span>' : ''; ?></div>
        </div>
        <span class="sev-badge" style="background:<?php echo sev_color($report['severity']); ?>;"><?php echo esc($report['severity']); ?></span>
      </div>

      <?php if ($allowed): ?>
        <h2 style="font-size:1rem;margin-top:1.5rem;">Vulnerability details</h2>
        <div class="details-block"><?php echo esc($report['details']); ?></div>
        <?php if (trim($report['poc']) !== ''): ?>
          <h2 style="font-size:1rem;">Proof of concept</h2>
          <div class="details-block"><?php echo esc($report['poc']); ?></div>
        <?php endif; ?>
      <?php else: ?>
        <div class="locked-box" style="margin-top:1.5rem;">🔒 You don't have access to this report's details.</div>
      <?php endif; ?>

      <h2 style="font-size:1rem;margin-top:1.75rem;">Collaborators</h2>
      <?php if (empty($collabs)): ?>
        <div style="color:#64748b;font-size:0.85rem;">No collaborators yet.</div>
      <?php else: foreach ($collabs as $c): ?>
        <div class="collab-row">
          <span class="av" style="background:<?php echo avatar_color($c['username']); ?>;"><?php echo esc(avatar_letter($c['name'])); ?></span>
          <div>@<?php echo esc($c['username']); ?> <span style="color:#64748b;font-size:0.78rem;"><?php echo $c['twofa_enabled'] ? '(2FA on)' : '(2FA off)'; ?></span></div>
          <span class="status-pill <?php echo esc($c['status']); ?>"><?php echo esc(ucfirst($c['status'])); ?></span>
        </div>
      <?php endforeach; endif; ?>

      <?php if ($isOwner): ?>
      <form method="POST" action="index.php?action=add-collaborator" style="margin-top:1.25rem;display:flex;gap:0.5rem;">
        <input type="hidden" name="report_id" value="<?php echo (int)$id; ?>">
        <input name="username" placeholder="Invite by username" required>
        <button class="btn btn-primary" type="submit">Invite collaborator</button>
      </form>
      <?php endif; ?>
    </div>
    </body></html>
    <?php
    exit;
}

if ($action === 'program') {
    $slug = $_GET['slug'] ?? '';
    $program = program_by_slug($db, $slug);
    render_head('Program'); render_topbar($me);
    if (!$program) { echo '<div class="wrap"><div class="empty">Program not found.</div></div></body></html>'; exit; }
    ?>
    <div class="wrap" style="max-width:680px;">
      <a href="index.php?action=programs" class="back-link">← Programs</a>
      <h1><?php echo esc($program['name']); ?></h1>
      <p class="sub" style="margin-top:-0.75rem;"><?php echo esc($program['description']); ?></p>
      <?php if ($program['requires_2fa']): ?><div class="badge-2fa">🔒 This program requires two-factor authentication to submit reports</div><?php endif; ?>
      <div class="card">
        <div style="display:flex;justify-content:space-between;">
          <div><div style="color:#94a3b8;font-size:0.78rem;">Bounty range</div><div style="font-weight:700;color:#4ade80;">$<?php echo number_format($program['min_bounty']); ?> – $<?php echo number_format($program['max_bounty']); ?></div></div>
          <a class="btn btn-primary" href="index.php?action=submit-report&slug=<?php echo esc($slug); ?>">Submit report</a>
        </div>
      </div>
    </div>
    </body></html>
    <?php
    exit;
}

// ── Default: Programs list ───────────────────────────────────────────────────────
render_head('Programs'); render_topbar($me);
?>
<div class="wrap">
  <h1>Programs</h1>
  <div class="sub">Pick a program and submit a vulnerability report.</div>
  <div class="grid">
    <?php foreach ($programs as $p): ?>
    <a class="pcard" href="index.php?action=program&slug=<?php echo esc($p['slug']); ?>">
      <div class="thumb" style="background:<?php echo esc($p['grad']); ?>;"><?php echo esc($p['name']); ?></div>
      <div class="body">
        <div style="font-weight:700;"><?php echo esc($p['name']); ?><?php echo $p['requires_2fa'] ? ' 🔒' : ''; ?></div>
        <div class="dsc"><?php echo esc($p['description']); ?></div>
        <div class="meta"><span class="bounty">$<?php echo number_format($p['min_bounty']); ?>–$<?php echo number_format($p['max_bounty']); ?></span><span style="color:#64748b;"><?php echo $p['requires_2fa'] ? '2FA required' : 'Open'; ?></span></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
</body></html>
