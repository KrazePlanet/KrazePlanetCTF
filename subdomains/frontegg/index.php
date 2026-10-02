<?php
// Frontegg-style admin console — user management & domain restrictions
// A realistic B2B admin console clone used for hands-on web-security practice.
// Students register an organization, block a domain under Security > Domain
// Restrictions, then try to invite a user from that domain under Users.
//
// The intended flaw (mirrors HackerOne #2033005, Frontegg): the domain-restriction
// check lowercases the submitted domain with a plain ASCII-only lowercaser before
// comparing it against the blocklist. Turkish "İ" (U+0130, LATIN CAPITAL LETTER I
// WITH DOT ABOVE) and other Unicode look-alikes are multi-byte UTF-8 sequences that
// an ASCII-only lowercase pass leaves completely untouched, so "yopmaİl.com" never
// lowercases down to "yopmail.com" and the exact-match blocklist check silently
// fails to catch it — while the mailbox/delivery layer DOES fold the look-alike
// back to its plain-ASCII form, so the invite is both accepted AND actually
// delivered to the "blocked" domain's inbox.

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

$db->query("CREATE TABLE IF NOT EXISTS frontegg_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    org_name VARCHAR(150) NOT NULL DEFAULT 'My Organization',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS frontegg_blocked_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    domain VARCHAR(190) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_owner_domain (owner_id, domain)
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS frontegg_invites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    email_raw VARCHAR(190) NOT NULL,
    role VARCHAR(60) NOT NULL DEFAULT 'Member',
    status VARCHAR(30) NOT NULL DEFAULT 'Pending approval',
    activation_token VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS frontegg_mailbox (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_canonical VARCHAR(190) NOT NULL,
    to_raw VARCHAR(190) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    invite_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function first_name($name) { $p = preg_split('/\s+/', trim($name)); return $p[0] ?: $name; }
function avatar_letters($email) { return strtoupper(mb_substr(trim($email), 0, 2)); }
$AVATAR_COLORS = ['#f59e0b','#10b981','#6366f1','#ec4899','#06b6d4','#84cc16'];
function avatar_color($email) { global $AVATAR_COLORS; return $AVATAR_COLORS[crc32($email) % count($AVATAR_COLORS)]; }

// ── The vulnerable check vs. the honest canonicalizer ───────────────────────────
// Domain restriction check uses PHP's plain strtolower(): ASCII-only, leaves any
// multi-byte UTF-8 character (İ, Cyrillic look-alikes, etc.) completely untouched.
function naive_lower_domain($domain) {
    return strtolower($domain); // <-- THE BUG: not Unicode-aware
}
// Mail delivery / inbox routing uses a real confusable-aware canonicalizer, so the
// message still lands in the "blocked" domain's inbox even though the check above
// didn't recognize it.
function canonical_domain($domain) {
    $map = [
        "\xC4\xB0" => 'i', // İ U+0130 LATIN CAPITAL LETTER I WITH DOT ABOVE
        "\xC4\xB1" => 'i', // ı U+0131 LATIN SMALL LETTER DOTLESS I
        "\xD0\xB0" => 'a', // а U+0430 CYRILLIC SMALL LETTER A
        "\xD0\xB5" => 'e', // е U+0435 CYRILLIC SMALL LETTER IE
        "\xD0\xBE" => 'o', // о U+043E CYRILLIC SMALL LETTER O
        "\xD1\x80" => 'p', // р U+0440 CYRILLIC SMALL LETTER ER
        "\xD1\x81" => 'c', // с U+0441 CYRILLIC SMALL LETTER ES
    ];
    return strtolower(strtr($domain, $map));
}
function canonical_email($email) {
    if (strpos($email, '@') === false) return strtolower($email);
    [$local, $domain] = explode('@', $email, 2);
    return strtolower($local) . '@' . canonical_domain($domain);
}
function email_domain($email) {
    $parts = explode('@', $email, 2);
    return $parts[1] ?? '';
}

function current_user($db) {
    if (empty($_SESSION['uid'])) return null;
    $st = $db->prepare("SELECT * FROM frontegg_users WHERE id=?");
    $st->bind_param('i', $_SESSION['uid']); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function blocked_domains($db, $ownerId) {
    $st = $db->prepare("SELECT * FROM frontegg_blocked_domains WHERE owner_id=? ORDER BY domain");
    $st->bind_param('i', $ownerId); $st->execute();
    $out = []; $r = $st->get_result(); while ($row = $r->fetch_assoc()) $out[] = $row; return $out;
}
function is_domain_blocked_naive($db, $ownerId, $domain) {
    $lowered = naive_lower_domain($domain); // the vulnerable comparison
    // A byte-exact (BINARY) comparison is required here: MySQL's default
    // utf8mb4_unicode_ci collation treats "İ" as equivalent to "i" and would
    // silently "fix" the very bug this lab is built to demonstrate.
    $st = $db->prepare("SELECT 1 FROM frontegg_blocked_domains WHERE owner_id=? AND BINARY domain = BINARY ?");
    $st->bind_param('is', $ownerId, $lowered); $st->execute();
    return (bool)$st->get_result()->fetch_row();
}
function org_invites($db, $ownerId) {
    $st = $db->prepare("SELECT * FROM frontegg_invites WHERE owner_id=? ORDER BY id DESC");
    $st->bind_param('i', $ownerId); $st->execute();
    $out = []; $r = $st->get_result(); while ($row = $r->fetch_assoc()) $out[] = $row; return $out;
}

$action = $_GET['action'] ?? 'users';
$me = current_user($db);

// ── Auth ──────────────────────────────────────────────────────────────────────
if ($action === 'logout') { session_destroy(); header('Location: index.php?action=login'); exit; }

$authError = '';
if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name  = trim($_POST['name'] ?? '');
        $org   = trim($_POST['org_name'] ?? '') ?: "{$name}'s Organization";
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
            $authError = 'Enter your name, a valid email, and a password of at least 6 characters.';
        } else {
            $st = $db->prepare("SELECT id FROM frontegg_users WHERE email=?");
            $st->bind_param('s', $email); $st->execute();
            if ($st->get_result()->fetch_row()) {
                $authError = 'An account with that email already exists.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $st = $db->prepare("INSERT INTO frontegg_users (name, email, password_hash, org_name) VALUES (?,?,?,?)");
                $st->bind_param('ssss', $name, $email, $hash, $org); $st->execute();
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
        $st = $db->prepare("SELECT * FROM frontegg_users WHERE email=?");
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

// ── Domain restriction management ───────────────────────────────────────────────
if ($action === 'add-blocked-domain' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $domain = strtolower(trim($_POST['domain'] ?? ''));
    $domain = preg_replace('/^https?:\/\//', '', $domain);
    if ($domain !== '') {
        $st = $db->prepare("INSERT IGNORE INTO frontegg_blocked_domains (owner_id, domain) VALUES (?,?)");
        $st->bind_param('is', $myId, $domain); $st->execute();
    }
    header('Location: index.php?action=security'); exit;
}
if ($action === 'remove-blocked-domain') {
    $id = (int)($_GET['id'] ?? 0);
    $st = $db->prepare("DELETE FROM frontegg_blocked_domains WHERE id=? AND owner_id=?");
    $st->bind_param('ii', $id, $myId); $st->execute();
    header('Location: index.php?action=security'); exit;
}

// ── VULNERABLE ENDPOINT — invite users ───────────────────────────────────────────
if ($action === 'invite-users') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'method not allowed']); exit; }

    $body = json_decode(file_get_contents('php://input'), true);
    $emails = is_array($body) ? ($body['emails'] ?? []) : [];
    $role   = is_array($body) ? ($body['role'] ?? 'Member') : 'Member';
    if (!is_array($emails) || count($emails) === 0) { http_response_code(400); echo json_encode(['error' => 'No email addresses supplied.']); exit; }
    if (count($emails) > 5) { http_response_code(422); echo json_encode(['error' => 'You can invite up to 5 users at a time.']); exit; }

    $results = [];
    foreach ($emails as $email) {
        $email = trim($email);
        if (!str_contains($email, '@')) { $results[] = ['email' => $email, 'ok' => false, 'error' => 'Invalid email address.']; continue; }
        $domain = email_domain($email);

        // --- THE BUG -------------------------------------------------------------
        // Blocklist lookup lowercases with strtolower() — ASCII only. A domain
        // containing "İ" (or another multi-byte look-alike) is left as-is, so it
        // will not match the blocked (ASCII, lowercase) entry even though a human
        // reading it, and the mail system delivering to it, treat them as the same.
        if (is_domain_blocked_naive($db, $myId, $domain)) {
            $results[] = ['email' => $email, 'ok' => false, 'error' => "Sorry, you can't invite users with this domain due to your organization's restrictions."];
            continue;
        }
        // ---------------------------------------------------------------------

        $token = bin2hex(random_bytes(16));
        $st = $db->prepare("INSERT INTO frontegg_invites (owner_id, email_raw, role, activation_token) VALUES (?,?,?,?)");
        $st->bind_param('isss', $myId, $email, $role, $token); $st->execute();
        $inviteId = $db->insert_id;

        // Mail delivery routes by the CANONICAL (confusable-folded) address, so it
        // really does land in the blocked domain's inbox.
        $toCanonical = canonical_email($email);
        $subject = 'Welcome to your Frontegg account!';
        $st = $db->prepare("INSERT INTO frontegg_mailbox (to_canonical, to_raw, subject, invite_id) VALUES (?,?,?,?)");
        $st->bind_param('sssi', $toCanonical, $email, $subject, $inviteId); $st->execute();

        $results[] = ['email' => $email, 'ok' => true, 'invite_id' => $inviteId];
    }

    $anyOk = count(array_filter($results, fn($r) => $r['ok'])) > 0;
    echo json_encode(['results' => $results, 'any_accepted' => $anyOk]);
    exit;
}

// ── Fake mailbox (Yopmail-style) ─────────────────────────────────────────────────
if ($action === 'inbox') {
    $queryEmail = trim($_GET['email'] ?? '');
    $canonical = $queryEmail !== '' ? canonical_email($queryEmail) : '';
    $mails = [];
    if ($canonical !== '') {
        $st = $db->prepare("SELECT * FROM frontegg_mailbox WHERE to_canonical=? ORDER BY id DESC");
        $st->bind_param('s', $canonical); $st->execute();
        $r = $st->get_result(); while ($row = $r->fetch_assoc()) $mails[] = $row;
    }
    render_head('Inbox');
    ?>
    <div class="mailapp">
      <div class="mail-topbar">
        <div class="brand-mini">✉ Webmail</div>
        <form method="GET" class="mail-search">
          <input type="hidden" name="action" value="inbox">
          <input type="text" name="email" placeholder="Enter an email address to view its inbox…" value="<?php echo esc($queryEmail); ?>">
          <button type="submit">Check inbox</button>
        </form>
        <a href="index.php" class="btn">← Back to admin console</a>
      </div>
      <div class="mail-body">
        <div class="mail-list">
          <?php if ($queryEmail === ''): ?>
            <div class="empty">Type an address above to view its inbox.</div>
          <?php elseif (empty($mails)): ?>
            <div class="empty">No mail for <strong><?php echo esc($queryEmail); ?></strong>.</div>
          <?php else: foreach ($mails as $m): ?>
            <a class="mail-row <?php echo (isset($_GET['mid']) && (int)$_GET['mid']===(int)$m['id'])?'sel':''; ?>" href="index.php?action=inbox&email=<?php echo urlencode($queryEmail); ?>&mid=<?php echo (int)$m['id']; ?>">
              <div class="mr-from">Frontegg</div>
              <div class="mr-subj"><?php echo esc($m['subject']); ?></div>
              <div class="mr-time"><?php echo esc(date('M j, g:i A', strtotime($m['created_at']))); ?></div>
            </a>
          <?php endforeach; endif; ?>
        </div>
        <div class="mail-preview">
          <?php
          $selected = null;
          if (isset($_GET['mid'])) {
              foreach ($mails as $m) if ((int)$m['id'] === (int)$_GET['mid']) { $selected = $m; break; }
          } elseif (!empty($mails)) { $selected = $mails[0]; }
          if ($selected):
              $st = $db->prepare("SELECT * FROM frontegg_invites WHERE id=?");
              $st->bind_param('i', $selected['invite_id']); $st->execute();
              $invite = $st->get_result()->fetch_assoc();
          ?>
          <div class="mail-hd">
            <div style="font-weight:700;font-size:1.1rem;"><?php echo esc($selected['subject']); ?></div>
            <div class="mail-meta">Frontegg &lt;hello@frontegg.com&gt; · <?php echo esc(date('l, F j, Y g:i A', strtotime($selected['created_at']))); ?></div>
          </div>
          <div class="mail-content">
            <div class="logo-banner">Logo</div>
            <h2>Welcome <?php echo esc($selected['to_raw']); ?></h2>
            <p>We are delighted to have you onboard with us!</p>
            <p>Tap the button below to activate your account on the platform.</p>
            <a class="activate-btn" href="index.php?action=activate&token=<?php echo esc($invite['activation_token'] ?? ''); ?>">Activate</a>
            <p style="margin-top:1.5rem;font-size:0.82rem;color:#64748b;">If that doesn't work, copy and paste the following link into your browser:<br>
            <span style="word-break:break-all;">http://<?php echo esc($_SERVER['HTTP_HOST'] ?? 'localhost'); ?><?php echo esc($_SERVER['SCRIPT_NAME'] ?? '/index.php'); ?>?action=activate&amp;token=<?php echo esc($invite['activation_token'] ?? ''); ?></span></p>
            <p style="margin-top:1.5rem;">Cheers,<br>The team</p>
          </div>
          <?php else: ?>
          <div class="empty">Select a message to preview it.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    </body></html>
    <?php
    exit;
}

if ($action === 'activate') {
    $token = $_GET['token'] ?? '';
    $st = $db->prepare("SELECT * FROM frontegg_invites WHERE activation_token=?");
    $st->bind_param('s', $token); $st->execute();
    $invite = $st->get_result()->fetch_assoc();
    if ($invite) {
        $st = $db->prepare("UPDATE frontegg_invites SET status='Active' WHERE id=?");
        $st->bind_param('i', $invite['id']); $st->execute();
    }
    render_head('Account activated');
    ?>
    <div style="max-width:480px;margin:4rem auto;text-align:center;">
      <?php if ($invite): ?>
        <h1>✅ Account activated</h1>
        <p style="color:#64748b;"><?php echo esc($invite['email_raw']); ?> has joined <?php echo esc($me['org_name']); ?> as <strong><?php echo esc($invite['role']); ?></strong>.</p>
      <?php else: ?>
        <h1>Invalid activation link</h1>
      <?php endif; ?>
      <a href="index.php?action=users" class="btn btn-primary" style="margin-top:1rem;display:inline-flex;">Go to Users</a>
    </div>
    </body></html>
    <?php
    exit;
}

// =========================================================================
//  RENDER
// =========================================================================
function render_auth($mode, $error) {
    $isReg = ($mode === 'register');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $isReg ? 'Sign up' : 'Sign in'; ?> — Frontegg</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{margin:0;background:#f4f5f9;color:#1e2230;font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;}
.auth{width:400px;max-width:92vw;}
.logo-row{display:flex;align-items:center;gap:0.6rem;justify-content:center;font-weight:800;font-size:1.3rem;margin-bottom:1.75rem;color:#1e2230;}
.logo-row .mark{width:34px;height:34px;border-radius:9px;background:#4f46e5;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:700;}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:2rem;box-shadow:0 2px 14px rgba(30,30,60,0.06);}
.card h1{font-size:1.25rem;margin:0 0 0.35rem;}
.card p.s{color:#6b7280;font-size:0.88rem;margin:0 0 1.5rem;}
label{display:block;font-size:0.8rem;color:#374151;margin:0.9rem 0 0.35rem;font-weight:500;}
input{width:100%;background:#f9fafb;border:1px solid #d1d5db;color:#1e2230;border-radius:8px;padding:0.65rem 0.8rem;font-size:0.92rem;}
input:focus{outline:none;border-color:#4f46e5;}
.btn-primary{width:100%;margin-top:1.4rem;background:#4f46e5;border:none;color:#fff;padding:0.7rem;border-radius:8px;font-size:0.95rem;font-weight:600;cursor:pointer;}
.btn-primary:hover{background:#4338ca;}
.alt{text-align:center;margin-top:1.25rem;font-size:0.86rem;color:#6b7280;}
.alt a{color:#4f46e5;text-decoration:none;font-weight:600;}
.err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:0.7rem 0.9rem;border-radius:8px;font-size:0.85rem;margin-bottom:1rem;}
</style></head><body>
<div class="auth">
  <div class="logo-row"><span class="mark">Fé</span> Frontegg</div>
  <div class="card">
    <h1><?php echo $isReg ? 'Create your organization' : 'Welcome back'; ?></h1>
    <p class="s"><?php echo $isReg ? 'Set up user management for your product.' : 'Sign in to your admin console.'; ?></p>
    <?php if ($error): ?><div class="err"><?php echo esc($error); ?></div><?php endif; ?>
    <form method="POST" action="index.php?action=<?php echo $isReg ? 'register' : 'login'; ?><?php echo isset($_GET['next']) ? '&next=' . esc($_GET['next']) : ''; ?>">
      <?php if ($isReg): ?>
        <label>Your name</label><input name="name" required>
        <label>Organization name</label><input name="org_name" placeholder="Acme Inc.">
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
<title><?php echo esc($title); ?> — Frontegg</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{margin:0;background:#f4f5f9;color:#1e2230;font-family:'Inter',sans-serif;}
a{color:inherit;}
::-webkit-scrollbar{width:8px;}
::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:4px;}
.app{display:flex;min-height:100vh;}
.sidebar{width:220px;flex-shrink:0;background:#fff;border-right:1px solid #e5e7eb;padding:1.25rem 0.9rem;}
.sb-brand{display:flex;align-items:center;gap:0.5rem;font-weight:800;margin-bottom:1.5rem;padding:0 0.3rem;}
.sb-brand .mark{width:26px;height:26px;border-radius:7px;background:#4f46e5;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.78rem;font-weight:700;}
.sb-org{font-size:0.78rem;color:#6b7280;padding:0 0.3rem 1rem;border-bottom:1px solid #f1f2f6;margin-bottom:0.75rem;}
.nav-item{display:flex;align-items:center;gap:0.6rem;padding:0.5rem 0.7rem;border-radius:8px;font-size:0.88rem;color:#4b5563;text-decoration:none;margin-bottom:0.15rem;}
.nav-item:hover{background:#f4f5f9;}
.nav-item.active{background:#eef0ff;color:#4338ca;font-weight:600;}
.nav-section{font-size:0.7rem;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;margin:1rem 0.7rem 0.3rem;}
.main{flex:1;min-width:0;}
.topbar{background:#fff;border-bottom:1px solid #e5e7eb;padding:0.85rem 1.75rem;display:flex;align-items:center;justify-content:space-between;}
.acct{display:flex;align-items:center;gap:0.6rem;}
.acct .av{width:28px;height:28px;border-radius:50%;background:#4f46e5;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.78rem;font-weight:700;}
.btn{background:#fff;border:1px solid #d1d5db;color:#1e2230;padding:0.4rem 0.9rem;border-radius:8px;font-size:0.84rem;cursor:pointer;font-weight:500;text-decoration:none;display:inline-flex;align-items:center;gap:0.4rem;}
.btn:hover{background:#f4f5f9;}
.btn-primary{background:#4f46e5;border:none;color:#fff;}
.btn-primary:hover{background:#4338ca;}
.wrap{max-width:1080px;margin:0 auto;padding:2rem 1.75rem;}
h1{font-size:1.4rem;margin:0 0 1.25rem;}
.searchbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;gap:1rem;}
.searchbar input{background:#fff;border:1px solid #d1d5db;border-radius:8px;padding:0.55rem 0.8rem;font-size:0.85rem;width:280px;}
table.users{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;}
table.users thead{background:#f4f5f9;}
table.users th{text-align:left;font-size:0.78rem;color:#6b7280;padding:0.75rem 1.1rem;font-weight:600;}
table.users td{padding:0.85rem 1.1rem;border-top:1px solid #f1f2f6;font-size:0.88rem;vertical-align:middle;}
.u-cell{display:flex;align-items:center;gap:0.65rem;}
.u-cell .av{width:34px;height:34px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.78rem;font-weight:700;flex-shrink:0;}
.u-cell .em{font-weight:600;}
.u-cell .em2{color:#6b7280;font-size:0.78rem;}
.pill{display:inline-block;padding:0.2rem 0.65rem;border-radius:20px;font-size:0.76rem;font-weight:600;}
.pill.role{background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe;}
.pill.pending{background:#fdf6e3;color:#92752a;border:1px solid #f3e2b3;}
.pill.active{background:#ecfdf5;color:#0f766e;border:1px solid #a7f3d0;}
.empty{color:#6b7280;font-size:0.9rem;padding:2.5rem 0;text-align:center;}
/* Security page */
.dlist{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-top:1rem;}
.drow{display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1.1rem;border-top:1px solid #f1f2f6;font-size:0.88rem;}
.drow:first-child{border-top:none;}
.drow .rm{color:#b91c1c;font-size:0.8rem;text-decoration:none;}
.add-domain{display:flex;gap:0.5rem;margin-top:1rem;}
.add-domain input{flex:1;background:#fff;border:1px solid #d1d5db;border-radius:8px;padding:0.6rem 0.8rem;font-size:0.88rem;}
.hint-box{background:#eef2ff;border:1px solid #c7d2fe;color:#3730a3;padding:0.9rem 1.1rem;border-radius:10px;font-size:0.84rem;margin-bottom:1.25rem;}
/* Invite modal */
.overlay{position:fixed;inset:0;background:rgba(15,15,25,0.45);display:none;align-items:center;justify-content:center;z-index:50;}
.overlay.open{display:flex;}
.modal{background:#fff;border-radius:14px;width:520px;max-width:92vw;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.25);}
.modal-hd{background:#f4f5f9;padding:1.1rem 1.4rem;display:flex;justify-content:space-between;align-items:center;}
.modal-hd h2{margin:0;font-size:1.15rem;}
.modal-hd .x{cursor:pointer;color:#6b7280;font-size:1.2rem;}
.modal-body{padding:1.4rem;}
.field-label{font-size:0.82rem;font-weight:600;margin-bottom:0.5rem;color:#374151;}
.field-label.err-label{color:#dc4c3f;}
.chipbox{border:1.5px solid #d1d5db;border-radius:10px;padding:0.6rem 0.7rem;display:flex;flex-wrap:wrap;gap:0.4rem;align-items:center;position:relative;}
.chipbox.err-box{border-color:#dc4c3f;}
.role-select{position:absolute;right:0.6rem;top:0.5rem;font-size:0.85rem;border:none;background:transparent;color:#1e2230;font-weight:500;}
.chip{background:#f4f5f9;border-radius:20px;padding:0.3rem 0.5rem 0.3rem 0.8rem;font-size:0.85rem;display:flex;align-items:center;gap:0.4rem;}
.chip.err-chip{background:#fdf1ef;color:#dc4c3f;font-weight:600;}
.chip .x{cursor:pointer;color:inherit;font-weight:700;}
.chip-input{border:none;outline:none;flex:1;min-width:160px;font-size:0.9rem;padding:0.3rem;}
.field-note{font-size:0.78rem;color:#9ca3af;margin-top:0.4rem;display:flex;justify-content:space-between;}
.field-error{color:#dc4c3f;font-size:0.82rem;margin-top:0.5rem;}
.modal-foot{padding:1rem 1.4rem;border-top:1px solid #f1f2f6;display:flex;justify-content:flex-end;gap:0.6rem;}
/* Webmail */
.mailapp{max-width:1080px;margin:0 auto;padding:1.5rem;}
.mail-topbar{display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;}
.brand-mini{font-weight:800;font-size:1.05rem;color:#4f46e5;}
.mail-search{display:flex;gap:0.5rem;flex:1;}
.mail-search input{flex:1;background:#fff;border:1px solid #d1d5db;border-radius:8px;padding:0.55rem 0.8rem;font-size:0.85rem;}
.mail-search button{background:#4f46e5;border:none;color:#fff;border-radius:8px;padding:0.55rem 1rem;font-size:0.85rem;font-weight:600;cursor:pointer;}
.mail-body{display:grid;grid-template-columns:320px 1fr;gap:1.25rem;align-items:start;}
.mail-list{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;}
.mail-row{display:block;padding:0.8rem 1rem;border-top:1px solid #f1f2f6;text-decoration:none;color:#1e2230;}
.mail-row:first-child{border-top:none;}
.mail-row:hover{background:#f9fafb;}
.mail-row.sel{background:#eef2ff;}
.mr-from{font-weight:700;font-size:0.85rem;}
.mr-subj{font-size:0.82rem;color:#4b5563;margin-top:0.1rem;}
.mr-time{font-size:0.72rem;color:#9ca3af;margin-top:0.2rem;}
.mail-preview{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:1.5rem;min-height:300px;}
.mail-hd{border-bottom:1px solid #f1f2f6;padding-bottom:1rem;margin-bottom:1.25rem;}
.mail-meta{font-size:0.8rem;color:#6b7280;margin-top:0.3rem;}
.logo-banner{background:#f4f5f9;border:1px solid #e5e7eb;border-radius:8px;text-align:center;padding:2rem;color:#9ca3af;font-weight:600;margin-bottom:1.5rem;}
.mail-content h2{font-size:1.2rem;}
.activate-btn{display:inline-block;background:#4f46e5;color:#fff !important;text-decoration:none;padding:0.75rem 1.75rem;border-radius:8px;font-weight:600;margin:1rem 0;}
.activate-btn:hover{background:#4338ca;}
</style></head><body>
<?php
}

function render_sidebar($active, $me) {
?>
<div class="sidebar">
  <div class="sb-brand"><span class="mark">Fé</span> Frontegg</div>
  <div class="sb-org"><?php echo esc($me['org_name']); ?></div>
  <a href="index.php?action=users" class="nav-item <?php echo $active==='users'?'active':''; ?>">👤 Users</a>
  <div class="nav-section">Settings</div>
  <a href="index.php?action=security" class="nav-item <?php echo $active==='security'?'active':''; ?>">🔒 Security</a>
  <div class="nav-section">Tools</div>
  <a href="index.php?action=inbox" class="nav-item <?php echo $active==='inbox'?'active':''; ?>" target="_blank">✉ Webmail</a>
</div>
<?php
}

function render_topbar($me) {
?>
<div class="topbar">
  <div style="font-weight:600;color:#6b7280;font-size:0.85rem;"><?php echo esc($me['org_name']); ?></div>
  <div class="acct">
    <span class="av"><?php echo esc(avatar_letters($me['name'])); ?></span>
    <span style="font-size:0.85rem;"><?php echo esc(first_name($me['name'])); ?></span>
    <a href="index.php?action=logout" class="btn">Sign out</a>
  </div>
</div>
<?php
}

// =========================================================================
//  PAGES
// =========================================================================

if ($action === 'security') {
    $domains = blocked_domains($db, $myId);
    render_head('Security'); ?>
    <div class="app"><?php render_sidebar('security', $me); ?>
    <div class="main"><?php render_topbar($me); ?>
      <div class="wrap" style="max-width:640px;">
        <h1>Domain Restrictions</h1>
        <div class="hint-box">Mode: <strong>Deny Only</strong> — users may sign up or be invited from any domain <em>except</em> the ones listed below.</div>
        <form method="POST" action="index.php?action=add-blocked-domain" class="add-domain">
          <input name="domain" placeholder="e.g. yopmail.com" required>
          <button class="btn btn-primary" type="submit">Add</button>
        </form>
        <div class="dlist">
          <?php if (empty($domains)): ?>
            <div class="drow" style="color:#9ca3af;">No domains blocked yet.</div>
          <?php else: foreach ($domains as $d): ?>
            <div class="drow">
              <span><?php echo esc($d['domain']); ?></span>
              <a class="rm" href="index.php?action=remove-blocked-domain&id=<?php echo (int)$d['id']; ?>">Remove</a>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div></div>
    </body></html>
    <?php
    exit;
}

if ($action === 'users') {
    $invites = org_invites($db, $myId);
    render_head('Users'); ?>
    <div class="app"><?php render_sidebar('users', $me); ?>
    <div class="main"><?php render_topbar($me); ?>
      <div class="wrap">
        <h1>Users</h1>
        <div class="searchbar">
          <input type="text" placeholder="Search for any text...">
          <button class="btn btn-primary" onclick="openInvite()">Invite User</button>
        </div>
        <table class="users">
          <thead><tr><th>User</th><th>Roles</th><th>Joined</th><th>Last Seen</th></tr></thead>
          <tbody>
            <?php if (empty($invites)): ?>
            <tr><td colspan="4" class="empty">No users yet. Click "Invite User" to add one.</td></tr>
            <?php else: foreach ($invites as $inv): ?>
            <tr>
              <td>
                <div class="u-cell">
                  <span class="av" style="background:<?php echo avatar_color($inv['email_raw']); ?>;"><?php echo esc(avatar_letters($inv['email_raw'])); ?></span>
                  <div><div class="em"><?php echo esc($inv['email_raw']); ?></div><div class="em2"><?php echo esc($inv['email_raw']); ?></div></div>
                </div>
              </td>
              <td><span class="pill role"><?php echo esc($inv['role']); ?></span></td>
              <td><span class="pill <?php echo $inv['status']==='Active'?'active':'pending'; ?>"><?php echo esc($inv['status']); ?></span></td>
              <td>-</td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div></div>

    <div class="overlay" id="inviteOverlay">
      <div class="modal">
        <div class="modal-hd"><h2>Invite User</h2><span class="x" onclick="closeInvite()">&times;</span></div>
        <div class="modal-body">
          <div class="field-label" id="fieldLabel">Email & Role</div>
          <div class="chipbox" id="chipBox">
            <select class="role-select" id="roleSelect">
              <option value="Member">Member</option>
              <option value="Admin">Admin</option>
              <option value="Backoffice Viewer">Backoffice Viewer</option>
              <option value="Backoffice Admin">Backoffice Admin</option>
            </select>
            <input class="chip-input" id="chipInput" placeholder='Type email address and press "Enter"'>
          </div>
          <div class="field-note"><span>You can invite up to 5 users at a time.</span><span id="chipCount">0/5</span></div>
          <div class="field-error" id="fieldError" style="display:none;"></div>
        </div>
        <div class="modal-foot">
          <button class="btn" onclick="closeInvite()">Cancel</button>
          <button class="btn btn-primary" onclick="sendInvites()">Invite</button>
        </div>
      </div>
    </div>
    <script>
    let chips = [];
    function openInvite(){ document.getElementById('inviteOverlay').classList.add('open'); }
    function closeInvite(){ document.getElementById('inviteOverlay').classList.remove('open'); }
    function renderChips(){
      const box = document.getElementById('chipBox');
      box.querySelectorAll('.chip').forEach(c=>c.remove());
      const input = document.getElementById('chipInput');
      chips.forEach(c => {
        const el = document.createElement('span');
        el.className = 'chip' + (c.err ? ' err-chip' : '');
        el.innerHTML = c.email.replace(/</g,'&lt;') + ' <span class="x" onclick="removeChip(\''+c.email.replace(/'/g,"\\'")+'\')">×</span>';
        box.insertBefore(el, input);
      });
      document.getElementById('chipCount').textContent = chips.length + '/5';
      const anyErr = chips.some(c=>c.err);
      document.getElementById('fieldLabel').className = 'field-label' + (anyErr ? ' err-label' : '');
      box.className = 'chipbox' + (anyErr ? ' err-box' : '');
    }
    function removeChip(email){ chips = chips.filter(c=>c.email!==email); renderChips(); }
    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('chipInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') {
          e.preventDefault();
          const val = e.target.value.trim();
          if (val && chips.length < 5 && !chips.some(c=>c.email===val)) {
            chips.push({email: val, err:false});
            e.target.value = '';
            renderChips();
          }
        }
      });
    });
    function sendInvites(){
      if (chips.length === 0) return;
      const role = document.getElementById('roleSelect').value;
      fetch('?action=invite-users', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ emails: chips.map(c=>c.email), role: role }) })
        .then(r=>r.json())
        .then(d => {
          const err = document.getElementById('fieldError');
          if (d.error) { err.style.display='block'; err.textContent = d.error; return; }
          const failed = (d.results||[]).filter(r=>!r.ok);
          chips = chips.map(c => { const f = failed.find(x=>x.email===c.email); return {email:c.email, err: !!f}; });
          renderChips();
          if (failed.length) {
            err.style.display='block';
            err.textContent = failed[0].error;
          } else {
            location.reload();
          }
        });
    }
    </script>
    </body></html>
    <?php
    exit;
}

// default fallback
header('Location: index.php?action=users'); exit;
