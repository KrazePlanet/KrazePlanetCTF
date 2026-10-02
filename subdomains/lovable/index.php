<?php
// Lovable — AI app & website builder (training replica)
// A multi-account replica of Lovable used for hands-on web-security practice.
// Students register their own accounts, build projects, and share them.
//
// The intended flaw (mirrors HackerOne #3591764 / Lovable VDP): the "View access"
// (read-only) invite role — and the "Viewer"/"Admin" member roles — are Pro-only,
// but the gate exists ONLY on the client (greyed-out options + a non-functional
// checkout). The server endpoints that persist those roles never verify the
// account's plan, so a free-plan user who replays the request keeps the Pro role.

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

$db->query("CREATE TABLE IF NOT EXISTS lovable_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    plan ENUM('free','pro','business') NOT NULL DEFAULT 'free',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS lovable_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    template_name VARCHAR(150) DEFAULT NULL,
    grad VARCHAR(120) DEFAULT 'linear-gradient(135deg,#1f2937,#111827)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS lovable_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner','admin','editor','viewer') NOT NULL DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_proj_user (project_id, user_id)
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS lovable_magic_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL UNIQUE,
    code VARCHAR(64) NOT NULL,
    access_level ENUM('disabled','write','read') NOT NULL DEFAULT 'write',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS lovable_stars (
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    PRIMARY KEY (user_id, project_id)
)") or die('init error');

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function base_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path   = $_SERVER['SCRIPT_NAME'] ?? '/index.php'; // e.g. /subdomains/lovable/index.php
    return "{$scheme}://{$host}{$path}";
}

// ── Auth helpers ─────────────────────────────────────────────────────────────────
function current_user($db) {
    if (empty($_SESSION['uid'])) return null;
    $st = $db->prepare("SELECT * FROM lovable_users WHERE id=?");
    $st->bind_param('i', $_SESSION['uid']);
    $st->execute();
    return $st->get_result()->fetch_assoc();
}
function get_project_by_id($db, $id) {
    $st = $db->prepare("SELECT * FROM lovable_projects WHERE id=?");
    $st->bind_param('i', $id);
    $st->execute();
    return $st->get_result()->fetch_assoc();
}
function role_in_project($db, $userId, $projectId) {
    $st = $db->prepare("SELECT role FROM lovable_members WHERE user_id=? AND project_id=?");
    $st->bind_param('ii', $userId, $projectId);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    return $r['role'] ?? null;
}
function magic_code($db, $projectId) {
    $st = $db->prepare("SELECT * FROM lovable_magic_codes WHERE project_id=?");
    $st->bind_param('i', $projectId);
    $st->execute();
    return $st->get_result()->fetch_assoc();
}
function ensure_magic_code($db, $projectId) {
    $mc = magic_code($db, $projectId);
    if ($mc) return $mc;
    $code = bin2hex(random_bytes(9));
    $st = $db->prepare("INSERT INTO lovable_magic_codes (project_id, code, access_level) VALUES (?,?, 'write')");
    $st->bind_param('is', $projectId, $code);
    $st->execute();
    return magic_code($db, $projectId);
}
function project_members($db, $projectId) {
    $st = $db->prepare("SELECT m.role, u.id AS uid, u.name, u.email FROM lovable_members m
        JOIN lovable_users u ON u.id = m.user_id WHERE m.project_id=?
        ORDER BY FIELD(m.role,'owner','admin','editor','viewer'), m.created_at ASC");
    $st->bind_param('i', $projectId);
    $st->execute();
    $out = [];
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) $out[] = $row;
    return $out;
}
function is_starred($db, $userId, $projectId) {
    $st = $db->prepare("SELECT 1 FROM lovable_stars WHERE user_id=? AND project_id=?");
    $st->bind_param('ii', $userId, $projectId);
    $st->execute();
    return (bool)$st->get_result()->fetch_row();
}
function time_ago($ts) {
    if (!$ts) return 'just now';
    $diff = time() - strtotime($ts);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return floor($diff/86400) . 'd ago';
}
function first_name($name) {
    $parts = preg_split('/\s+/', trim($name));
    return $parts[0] ?: $name;
}
function avatar_letter($name) {
    return strtoupper(mb_substr(trim($name), 0, 1) ?: 'U');
}

$TEMPLATES = [
    ['name' => 'Lovable slides', 'desc' => 'Code-powered presentation builder', 'grad' => 'linear-gradient(135deg,#ec4899,#f97316)'],
    ['name' => 'Ecommerce Store Website Template', 'desc' => 'Premium design for webstore', 'grad' => 'linear-gradient(135deg,#0ea5e9,#6366f1)'],
    ['name' => 'Discover events near you', 'desc' => 'Browse popular events by category', 'grad' => 'linear-gradient(135deg,#22c55e,#0ea5e9)'],
    ['name' => 'Article Title — Hero Content Showcase', 'desc' => 'Nexus blog / magazine template', 'grad' => 'linear-gradient(135deg,#64748b,#1e293b)'],
    ['name' => 'AI Film Production Without Limits', 'desc' => 'Cinematic AI showcase reel', 'grad' => 'linear-gradient(135deg,#111827,#7c3aed)'],
    ['name' => 'SaaS Landing Page', 'desc' => 'Convert visitors into signups', 'grad' => 'linear-gradient(135deg,#f43f5e,#8b5cf6)'],
];

$action = $_GET['action'] ?? 'home';
$me = current_user($db);

// ── Auth: logout ─────────────────────────────────────────────────────────────────
if ($action === 'logout') { session_destroy(); header('Location: index.php?action=login'); exit; }

// ── Auth: register ───────────────────────────────────────────────────────────────
$authError = '';
if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name  = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
            $authError = 'Enter a name, a valid email, and a password of at least 6 characters.';
        } else {
            $st = $db->prepare("SELECT id FROM lovable_users WHERE email=?");
            $st->bind_param('s', $email); $st->execute();
            if ($st->get_result()->fetch_row()) {
                $authError = 'An account with that email already exists.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $st = $db->prepare("INSERT INTO lovable_users (name, email, password_hash) VALUES (?,?,?)");
                $st->bind_param('sss', $name, $email, $hash); $st->execute();
                $_SESSION['uid'] = $db->insert_id;
                header('Location: index.php'); exit;
            }
        }
    }
    render_auth('register', $authError);
    exit;
}

// ── Auth: login ──────────────────────────────────────────────────────────────────
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        $st = $db->prepare("SELECT * FROM lovable_users WHERE email=?");
        $st->bind_param('s', $email); $st->execute();
        $u = $st->get_result()->fetch_assoc();
        if ($u && password_verify($pass, $u['password_hash'])) {
            $_SESSION['uid'] = $u['id'];
            $dest = $_GET['next'] ?? 'index.php';
            header('Location: ' . $dest); exit;
        }
        $authError = 'Invalid email or password.';
    }
    render_auth('login', $authError);
    exit;
}

// ── Everything below requires login ──────────────────────────────────────────────
if (!$me) {
    $next = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
    header('Location: index.php?action=login&next=' . $next);
    exit;
}
$myId = (int)$me['id'];
$plan = $me['plan'];

// ── VULNERABLE ENDPOINT — invite-link access level ───────────────────────────────
// Mirrors: POST /projects/{project_id}/magic-codes  {"access_level":"read"}
if ($action === 'magic-codes') {
    header('Content-Type: application/json');
    $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    $proj = get_project_by_id($db, $projectId);
    if (!$proj) { http_response_code(404); echo json_encode(['error' => 'project not found']); exit; }

    $role = role_in_project($db, $myId, $projectId);
    // Authorization DOES check that you can edit the project (owner/admin/editor)...
    if (!in_array($role, ['owner','admin','editor'], true)) {
        http_response_code(403); echo json_encode(['error' => 'forbidden']); exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $mc = ensure_magic_code($db, $projectId);
        echo json_encode(['project_id' => (string)$projectId, 'access_level' => $mc['access_level'], 'code' => $mc['code']]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        $accessLevel = is_array($body) ? ($body['access_level'] ?? '') : '';

        // --- THE BUG -----------------------------------------------------------
        // Only the value's shape is validated. There is NO check that the account
        // holds a Pro plan before honoring "read" (Pro-only View access), even
        // though the UI greys it out and the checkout can't complete on free.
        if (!in_array($accessLevel, ['disabled','write','read'], true)) {
            http_response_code(422); echo json_encode(['error' => 'invalid access_level']); exit;
        }
        // -----------------------------------------------------------------------

        $mc = ensure_magic_code($db, $projectId);
        $st = $db->prepare("UPDATE lovable_magic_codes SET access_level=? WHERE project_id=?");
        $st->bind_param('si', $accessLevel, $projectId);
        $st->execute();

        echo json_encode([
            'id'             => 'mc_' . bin2hex(random_bytes(8)),
            'project_id'     => (string)$projectId,
            'created_at'     => gmdate('D, d M Y H:i:s') . ' GMT',
            'expiry_seconds' => 604800,
            'access_level'   => $accessLevel,
            'code'           => $mc['code'],
        ]);
        exit;
    }
    http_response_code(405); echo json_encode(['error' => 'method not allowed']); exit;
}

// ── VULNERABLE ENDPOINT — change an existing member's role ────────────────────────
// The role dropdown ("Admin/Viewer" are Pro) is only disabled client-side.
if ($action === 'set-role') {
    header('Content-Type: application/json');
    $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    $targetId  = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $proj = get_project_by_id($db, $projectId);
    if (!$proj) { http_response_code(404); echo json_encode(['error'=>'not found']); exit; }
    if ((int)$proj['owner_id'] !== $myId && role_in_project($db, $myId, $projectId) !== 'admin') {
        http_response_code(403); echo json_encode(['error'=>'forbidden']); exit;
    }
    $body = json_decode(file_get_contents('php://input'), true);
    $role = is_array($body) ? ($body['role'] ?? '') : '';
    if ($role === 'remove') {
        $st = $db->prepare("DELETE FROM lovable_members WHERE project_id=? AND user_id=? AND role<>'owner'");
        $st->bind_param('ii', $projectId, $targetId); $st->execute();
        echo json_encode(['ok'=>true, 'removed'=>true]); exit;
    }
    // No plan check here either (same underlying flaw).
    if (!in_array($role, ['admin','editor','viewer'], true)) {
        http_response_code(422); echo json_encode(['error'=>'invalid role']); exit;
    }
    $st = $db->prepare("UPDATE lovable_members SET role=? WHERE project_id=? AND user_id=? AND role<>'owner'");
    $st->bind_param('sii', $role, $projectId, $targetId); $st->execute();
    echo json_encode(['ok'=>true, 'role'=>$role]); exit;
}

// ── Join a project via invite link ───────────────────────────────────────────────
if ($action === 'join') {
    $code = $_GET['code'] ?? '';
    $st = $db->prepare("SELECT * FROM lovable_magic_codes WHERE code=?");
    $st->bind_param('s', $code); $st->execute();
    $mc = $st->get_result()->fetch_assoc();
    if ($mc) {
        $projectId = (int)$mc['project_id'];
        $proj = get_project_by_id($db, $projectId);
        if ($proj) {
            if ((int)$proj['owner_id'] === $myId) { header('Location: index.php?action=project&id=' . $projectId); exit; }
            if ($mc['access_level'] !== 'disabled') {
                $role = ($mc['access_level'] === 'read') ? 'viewer' : 'editor';
                $st = $db->prepare("INSERT INTO lovable_members (project_id, user_id, role) VALUES (?,?,?)
                    ON DUPLICATE KEY UPDATE role=VALUES(role)");
                $st->bind_param('iis', $projectId, $myId, $role); $st->execute();
                $_SESSION['joined_notice'] = ['project' => $proj['name'], 'role' => $role];
                header('Location: index.php?action=project&id=' . $projectId); exit;
            }
        }
    }
    $_SESSION['join_error'] = 'This invite link is invalid or has been disabled.';
    header('Location: index.php'); exit;
}

// ── Add a person by email ────────────────────────────────────────────────────────
if ($action === 'add-member' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = (int)($_POST['project_id'] ?? 0);
    $email = strtolower(trim($_POST['email'] ?? ''));
    $proj = get_project_by_id($db, $projectId);
    if ($proj && ((int)$proj['owner_id'] === $myId || role_in_project($db,$myId,$projectId)==='admin')) {
        $st = $db->prepare("SELECT id FROM lovable_users WHERE email=?");
        $st->bind_param('s', $email); $st->execute();
        $u = $st->get_result()->fetch_assoc();
        if ($u && (int)$u['id'] !== $myId) {
            $st = $db->prepare("INSERT INTO lovable_members (project_id, user_id, role) VALUES (?,?, 'editor')
                ON DUPLICATE KEY UPDATE role=role");
            $st->bind_param('ii', $projectId, $u['id']); $st->execute();
        }
    }
    header('Location: index.php?action=project&id=' . $projectId); exit;
}

// ── Create project from template (Remix) ─────────────────────────────────────────
if ($action === 'remix' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '') ?: 'Untitled Project';
    $template = trim($_POST['template'] ?? '');
    $grad = 'linear-gradient(135deg,#1f2937,#111827)';
    foreach ($TEMPLATES as $t) if ($t['name'] === $template) { $grad = $t['grad']; break; }
    $st = $db->prepare("INSERT INTO lovable_projects (owner_id, name, template_name, grad) VALUES (?,?,?,?)");
    $st->bind_param('isss', $myId, $name, $template, $grad); $st->execute();
    $pid = $db->insert_id;
    $st = $db->prepare("INSERT INTO lovable_members (project_id, user_id, role) VALUES (?,?, 'owner')");
    $st->bind_param('ii', $pid, $myId); $st->execute();
    ensure_magic_code($db, $pid);
    header('Location: index.php?action=project&id=' . $pid); exit;
}

// ── Toggle star ──────────────────────────────────────────────────────────────────
if ($action === 'toggle-star' && isset($_GET['id'])) {
    $pid = (int)$_GET['id'];
    if (is_starred($db, $myId, $pid)) {
        $st = $db->prepare("DELETE FROM lovable_stars WHERE user_id=? AND project_id=?");
    } else {
        $st = $db->prepare("INSERT IGNORE INTO lovable_stars (user_id, project_id) VALUES (?,?)");
    }
    $st->bind_param('ii', $myId, $pid); $st->execute();
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?action=projects')); exit;
}

// ── Fake billing / upgrade attempt ───────────────────────────────────────────────
if ($action === 'upgrade-attempt' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: index.php?action=billing&declined=1'); exit;
}

// ── Project lists for the current user ───────────────────────────────────────────
function owned_projects($db, $userId) {
    $st = $db->prepare("SELECT * FROM lovable_projects WHERE owner_id=? ORDER BY created_at DESC");
    $st->bind_param('i', $userId); $st->execute();
    $o = []; $r = $st->get_result(); while ($row = $r->fetch_assoc()) $o[] = $row; return $o;
}
function shared_projects($db, $userId) {
    $st = $db->prepare("SELECT p.*, m.role AS my_role FROM lovable_projects p
        JOIN lovable_members m ON m.project_id = p.id
        WHERE m.user_id=? AND m.role<>'owner' ORDER BY p.created_at DESC");
    $st->bind_param('i', $userId); $st->execute();
    $o = []; $r = $st->get_result(); while ($row = $r->fetch_assoc()) $o[] = $row; return $o;
}
function accessible_projects($db, $userId) {
    $st = $db->prepare("SELECT p.*, m.role AS my_role FROM lovable_projects p
        JOIN lovable_members m ON m.project_id = p.id
        WHERE m.user_id=? ORDER BY p.created_at DESC");
    $st->bind_param('i', $userId); $st->execute();
    $o = []; $r = $st->get_result(); while ($row = $r->fetch_assoc()) $o[] = $row; return $o;
}

$myOwned  = owned_projects($db, $myId);
$myShared = shared_projects($db, $myId);
$myAll    = accessible_projects($db, $myId);

// =========================================================================
//  RENDER FUNCTIONS
// =========================================================================
function render_auth($mode, $error) {
    $isReg = ($mode === 'register');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $isReg ? 'Sign up' : 'Log in'; ?> — Lovable</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{margin:0;background:#0d0d10;color:#e5e5ea;font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;}
.auth{width:380px;max-width:92vw;}
.logo-row{display:flex;align-items:center;gap:0.6rem;justify-content:center;font-weight:800;font-size:1.35rem;margin-bottom:1.75rem;}
.logo-row .logo{width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#f97316,#ec4899,#8b5cf6);}
.card{background:#141418;border:1px solid #26262c;border-radius:16px;padding:2rem;}
.card h1{font-size:1.3rem;margin:0 0 0.35rem;}
.card p.s{color:#8a8a93;font-size:0.88rem;margin:0 0 1.5rem;}
label{display:block;font-size:0.8rem;color:#a1a1aa;margin:0.9rem 0 0.35rem;}
input{width:100%;background:#0d0d10;border:1px solid #2a2a30;color:#e5e5ea;border-radius:9px;padding:0.7rem 0.8rem;font-size:0.92rem;}
input:focus{outline:none;border-color:#7c3aed;}
.btn-primary{width:100%;margin-top:1.4rem;background:linear-gradient(135deg,#7c3aed,#a855f7);border:none;color:#fff;padding:0.75rem;border-radius:9px;font-size:0.95rem;font-weight:600;cursor:pointer;}
.btn-primary:hover{filter:brightness(1.08);}
.alt{text-align:center;margin-top:1.25rem;font-size:0.86rem;color:#8a8a93;}
.alt a{color:#a78bfa;text-decoration:none;}
.err{background:#2e0505;border:1px solid #dc262655;color:#fca5a5;padding:0.7rem 0.9rem;border-radius:9px;font-size:0.85rem;margin-bottom:1rem;}
</style></head><body>
<div class="auth">
  <div class="logo-row"><span class="logo"></span> Lovable</div>
  <div class="card">
    <h1><?php echo $isReg ? 'Create your account' : 'Welcome back'; ?></h1>
    <p class="s"><?php echo $isReg ? 'Start building apps and websites with AI.' : 'Log in to continue building.'; ?></p>
    <?php if ($error): ?><div class="err"><?php echo esc($error); ?></div><?php endif; ?>
    <form method="POST" action="index.php?action=<?php echo $isReg ? 'register' : 'login'; ?><?php echo isset($_GET['next']) ? '&next=' . esc($_GET['next']) : ''; ?>">
      <?php if ($isReg): ?>
        <label>Full name</label><input name="name" placeholder="Jane Doe" required>
      <?php endif; ?>
      <label>Email</label><input name="email" type="email" placeholder="you@example.com" required>
      <label>Password</label><input name="password" type="password" placeholder="••••••••" required>
      <button class="btn-primary" type="submit"><?php echo $isReg ? 'Sign up' : 'Log in'; ?></button>
    </form>
    <div class="alt">
      <?php if ($isReg): ?>
        Already have an account? <a href="index.php?action=login">Log in</a>
      <?php else: ?>
        New to Lovable? <a href="index.php?action=register">Create an account</a>
      <?php endif; ?>
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
<title><?php echo esc($title); ?> — Lovable</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{margin:0;background:#0d0d10;color:#e5e5ea;font-family:'Inter',sans-serif;}
a{color:inherit;}
::-webkit-scrollbar{width:8px;height:8px;}
::-webkit-scrollbar-thumb{background:#2a2a30;border-radius:4px;}
.app{display:flex;min-height:100vh;}
.sidebar{width:230px;flex-shrink:0;border-right:1px solid #202024;padding:1rem 0.75rem;display:flex;flex-direction:column;}
.sb-brand{display:flex;align-items:center;gap:0.5rem;padding:0.4rem 0.4rem 1rem;font-weight:800;}
.sb-brand .logo{width:24px;height:24px;border-radius:7px;background:linear-gradient(135deg,#f97316,#ec4899,#8b5cf6);display:inline-block;}
.workspace{display:flex;align-items:center;justify-content:space-between;background:#18181c;border:1px solid #2a2a30;border-radius:8px;padding:0.5rem 0.6rem;margin-bottom:0.75rem;font-size:0.82rem;}
.workspace .wname{display:flex;align-items:center;gap:0.5rem;overflow:hidden;}
.workspace .wname span.t{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.workspace .wavatar{width:22px;height:22px;border-radius:6px;background:linear-gradient(135deg,#ec4899,#f97316);display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;flex-shrink:0;}
.nav-item{display:flex;align-items:center;gap:0.6rem;padding:0.45rem 0.6rem;border-radius:8px;font-size:0.86rem;color:#c7c7cf;text-decoration:none;margin-bottom:0.1rem;}
.nav-item:hover{background:#18181c;}
.nav-item.active{background:#232329;color:#fff;}
.nav-item .ic{width:16px;text-align:center;opacity:0.85;}
.nav-section{font-size:0.72rem;color:#6b6b74;text-transform:uppercase;letter-spacing:0.5px;margin:0.9rem 0.6rem 0.3rem;}
.sb-sub{padding-left:1.7rem;}
.sb-bottom{margin-top:auto;}
.referral{background:#18181c;border:1px solid #2a2a30;border-radius:10px;padding:0.75rem;font-size:0.78rem;margin-top:0.5rem;}
.referral .r-title{font-weight:600;margin-bottom:0.15rem;}
.referral .r-sub{color:#8a8a93;font-size:0.72rem;}
.main{flex:1;display:flex;flex-direction:column;min-width:0;}
.topbar{display:flex;align-items:center;justify-content:space-between;padding:0.85rem 1.5rem;border-bottom:1px solid #202024;}
.badge-plan{font-size:0.7rem;padding:0.2rem 0.6rem;border-radius:10px;background:#27272e;color:#a1a1aa;border:1px solid #333;margin-left:0.6rem;}
.acct{display:flex;align-items:center;gap:0.55rem;}
.acct .av{width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#8b5cf6,#ec4899);display:inline-flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;}
.btn{background:#232329;border:1px solid #333;color:#e5e5ea;padding:0.45rem 1rem;border-radius:8px;font-size:0.85rem;cursor:pointer;font-weight:500;text-decoration:none;display:inline-flex;align-items:center;gap:0.4rem;}
.btn:hover{background:#2c2c33;}
.btn-primary{background:linear-gradient(135deg,#7c3aed,#a855f7);border:none;color:#fff;}
.btn-primary:hover{filter:brightness(1.1);}
.btn-pro{background:linear-gradient(135deg,#f59e0b,#f97316);border:none;color:#1a1a1a;font-weight:700;}
.wrap{max-width:1100px;margin:0 auto;padding:2rem 1.5rem;width:100%;}
h1{font-size:1.5rem;margin:0 0 0.25rem;}
.sub{color:#8a8a93;font-size:0.9rem;margin-bottom:1.5rem;}
.searchbar{display:flex;gap:0.6rem;margin-bottom:1.25rem;}
.searchbar input{flex:1;background:#18181c;border:1px solid #2a2a30;color:#e5e5ea;border-radius:8px;padding:0.55rem 0.8rem;font-size:0.85rem;}
.searchbar select{background:#18181c;border:1px solid #2a2a30;color:#c7c7cf;border-radius:8px;padding:0.55rem 0.7rem;font-size:0.82rem;}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:1rem;}
.pcard{background:#18181c;border:1px solid #2a2a30;border-radius:12px;overflow:hidden;transition:border-color .15s;position:relative;}
.pcard:hover{border-color:#7c3aed88;}
.pcard a.cover{text-decoration:none;color:inherit;display:block;}
.pcard .thumb{height:110px;display:flex;align-items:center;justify-content:center;color:#e5e5ea99;font-size:0.75rem;font-weight:600;text-align:center;padding:0.5rem;}
.pcard .meta{padding:0.75rem 0.9rem;display:flex;align-items:center;justify-content:space-between;}
.pcard .meta .name{font-weight:600;font-size:0.9rem;}
.pcard .meta .when{font-size:0.75rem;color:#8a8a93;margin-top:0.15rem;}
.pcard .tag{font-size:0.65rem;color:#a78bfa;background:#7c3aed22;border-radius:6px;padding:0.05rem 0.4rem;margin-left:0.4rem;}
.star-btn{background:none;border:none;color:#5a5a63;cursor:pointer;font-size:0.95rem;text-decoration:none;}
.star-btn.on{color:#facc15;}
.dashed{border:1.5px dashed #33333c;border-radius:12px;display:flex;align-items:center;justify-content:center;min-height:150px;color:#6b6b74;text-decoration:none;font-size:0.85rem;flex-direction:column;gap:0.4rem;}
.dashed:hover{border-color:#7c3aed88;color:#c7c7cf;}
.empty{color:#6b6b74;font-size:0.88rem;padding:2rem 0;text-align:center;}
.notice{background:#052e1a;border:1px solid #16a34a55;color:#86efac;padding:0.8rem 1.1rem;border-radius:10px;margin-bottom:1.25rem;font-size:0.85rem;}
.warn{background:#2e0505;border:1px solid #dc262655;color:#fca5a5;padding:0.8rem 1.1rem;border-radius:10px;margin-bottom:1.25rem;font-size:0.85rem;}
.prompt-box{background:#18181c;border:1px solid #2a2a30;border-radius:16px;padding:1.25rem;margin-bottom:2rem;}
.prompt-box textarea{width:100%;background:transparent;border:none;color:#e5e5ea;font-size:0.95rem;resize:none;outline:none;font-family:inherit;min-height:60px;}
.chips{display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.75rem;}
.chip{background:#232329;border:1px solid #333;border-radius:20px;padding:0.35rem 0.8rem;font-size:0.78rem;color:#c7c7cf;cursor:pointer;}
.chip:hover{background:#2c2c33;}
.tcard{background:#18181c;border:1px solid #2a2a30;border-radius:12px;overflow:hidden;}
.tcard .thumb{height:130px;display:flex;align-items:center;justify-content:center;color:#ffffffcc;font-weight:700;text-align:center;padding:0.75rem;font-size:0.85rem;}
.tcard .body{padding:0.85rem 0.9rem;}
.tcard .body .n{font-weight:600;font-size:0.9rem;}
.tcard .body .d{color:#8a8a93;font-size:0.78rem;margin-top:0.15rem;}
.tcard .body .use{margin-top:0.7rem;width:100%;}
.editor{display:flex;flex:1;}
.side{width:280px;border-right:1px solid #202024;padding:1.25rem 1rem;font-size:0.85rem;color:#9a9aa2;line-height:1.7;overflow-y:auto;}
.side b{color:#e5e5ea;}
.canvas{flex:1;padding:1.5rem;overflow-y:auto;}
.previewbox{border:1px solid #2a2a30;border-radius:12px;background:#fff;color:#111;min-height:360px;padding:2rem;overflow:hidden;}
.overlay{position:fixed;inset:0;background:rgba(0,0,0,0.55);display:none;align-items:flex-start;justify-content:flex-end;z-index:50;}
.overlay.center{align-items:center;justify-content:center;}
.overlay.open{display:flex;}
.modal{background:#18181c;border:1px solid #2a2a30;border-radius:14px 0 0 14px;width:440px;max-width:100%;height:100%;padding:1.5rem;overflow-y:auto;}
.modal.small{border-radius:14px;height:auto;max-height:90vh;width:400px;}
.modal h2{font-size:1.05rem;margin:0 0 1rem;display:flex;justify-content:space-between;align-items:center;}
.modal h2 span.x{cursor:pointer;color:#8a8a93;font-weight:400;}
.modal .x{font-size:1.35rem;}
.add-row{display:flex;gap:0.5rem;margin-bottom:0.5rem;}
.add-row input{flex:1;background:#0d0d10;border:1px solid #2f2f37;color:#e5e5ea;border-radius:9px;padding:0.6rem 0.75rem;font-size:0.86rem;}
.add-row input:focus{outline:none;border-color:#7c3aed;}
.add-btn{background:linear-gradient(135deg,#7c3aed,#a855f7);border:none;color:#fff;border-radius:9px;padding:0 1.1rem;font-size:0.85rem;font-weight:600;cursor:pointer;}
.add-btn:hover{filter:brightness(1.08);}
.sec-label{margin-top:1.4rem;margin-bottom:0.3rem;font-size:0.82rem;color:#8a8a93;font-weight:500;}
.acc-row{display:flex;align-items:center;justify-content:space-between;padding:0.55rem 0;gap:0.5rem;}
.who{font-size:0.88rem;display:flex;align-items:center;gap:0.7rem;min-width:0;}
.who-txt{display:flex;flex-direction:column;min-width:0;}
.who-txt .nm{font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.who-txt small{color:#8a8a93;font-size:0.74rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.who .mav{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#22c55e,#0ea5e9);display:inline-flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;flex-shrink:0;}
.who .mav.ghost{background:#26262e;border:1px solid #33333c;font-size:0.85rem;}
.owner-tag{font-size:0.82rem;color:#8a8a93;padding-right:0.35rem;}
/* custom dropdown */
.dd{position:relative;flex-shrink:0;}
.dd-trigger{display:inline-flex;align-items:center;gap:0.35rem;background:transparent;border:none;color:#c7c7cf;font-size:0.84rem;cursor:pointer;padding:0.3rem 0.4rem;border-radius:7px;font-family:inherit;}
.dd-trigger:hover{background:#232329;}
.dd-trigger .cv{color:#7a7a83;font-size:0.9rem;line-height:1;}
.dd-menu{position:absolute;top:calc(100% + 6px);right:0;width:290px;background:#1c1c22;border:1px solid #33333c;border-radius:12px;padding:0.35rem;box-shadow:0 12px 34px rgba(0,0,0,.55);z-index:60;display:none;}
.dd.open .dd-menu{display:block;}
.dd-opt{display:flex;align-items:flex-start;gap:0.7rem;padding:0.6rem 0.65rem;border-radius:9px;cursor:pointer;}
.dd-opt:hover{background:#26262e;}
.dd-ic{width:20px;text-align:center;color:#b9b9c2;font-size:0.92rem;flex-shrink:0;margin-top:0.05rem;}
.dd-body{min-width:0;}
.dd-t{font-size:0.86rem;color:#f0f0f4;font-weight:500;display:flex;align-items:center;}
.dd-d{font-size:0.75rem;color:#8a8a93;margin-top:0.1rem;}
.dd-opt.danger .dd-t{color:#f87171;}
.dd-opt.danger .dd-ic{color:#f87171;}
.dd-opt.pro-locked{opacity:0.65;}
.linkbox{display:flex;gap:0.5rem;margin-top:0.5rem;}
.linkbox input{flex:1;background:#0d0d10;border:1px solid #2f2f37;color:#8a8a93;border-radius:9px;padding:0.6rem 0.7rem;font-size:0.78rem;}
.copy-btn{background:#232329;border:1px solid #33333c;color:#c7c7cf;border-radius:9px;padding:0 0.85rem;font-size:1rem;cursor:pointer;}
.copy-btn:hover{background:#2c2c33;}
#lockedMsg{display:none;color:#fbbf24;font-size:0.78rem;margin-top:0.6rem;}
#lockedMsg a{color:#fbbf24;}
.pro-tag{background:#7c3aed33;color:#c4b5fd;font-size:0.64rem;padding:0.08rem 0.4rem;border-radius:6px;margin-left:0.4rem;font-weight:600;display:inline-flex;align-items:center;gap:0.15rem;}
.pro-tag::before{content:'◆';font-size:0.55rem;}
.pricing{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;margin-top:1.5rem;}
.plan{background:#18181c;border:1px solid #2a2a30;border-radius:14px;padding:1.5rem;}
.plan.highlight{border-color:#7c3aed88;background:linear-gradient(180deg,#1c1826,#18181c);}
.plan .pname{font-weight:700;font-size:1.05rem;}
.plan .pprice{font-size:1.8rem;font-weight:800;margin:0.5rem 0;}
.plan .pprice span{font-size:0.85rem;color:#8a8a93;font-weight:500;}
.plan ul{padding-left:1.1rem;font-size:0.82rem;color:#c7c7cf;line-height:1.9;margin:1rem 0;}
.declined{background:#2e0505;border:1px solid #dc262655;color:#fca5a5;padding:0.9rem 1.1rem;border-radius:10px;margin-bottom:1.25rem;font-size:0.85rem;}
input[type=checkbox].toggle{width:36px;height:20px;-webkit-appearance:none;appearance:none;background:#33333c;border-radius:20px;position:relative;cursor:pointer;outline:none;}
input[type=checkbox].toggle:checked{background:#7c3aed;}
input[type=checkbox].toggle::before{content:'';position:absolute;top:2px;left:2px;width:16px;height:16px;background:#fff;border-radius:50%;transition:.15s;}
input[type=checkbox].toggle:checked::before{left:18px;}
.toast{position:fixed;bottom:1.25rem;right:1.25rem;background:#27272e;border:1px solid #3a3a44;color:#e5e5ea;padding:0.7rem 1rem;border-radius:10px;font-size:0.82rem;display:none;box-shadow:0 4px 16px rgba(0,0,0,.4);}
.viewpill{font-size:0.7rem;color:#c4b5fd;background:#7c3aed22;border:1px solid #7c3aed44;border-radius:8px;padding:0.2rem 0.6rem;}
</style></head><body>
<?php
}

function render_sidebar($active, $recents, $me) {
    $ws = esc(first_name($me['name'])) . "'s Lovable";
?>
<div class="app">
<div class="sidebar">
    <div class="sb-brand"><span class="logo"></span> Lovable</div>
    <div class="workspace">
        <div class="wname"><span class="wavatar"><?php echo esc(avatar_letter($me['name'])); ?></span> <span class="t"><?php echo $ws; ?></span></div>
        <span style="color:#6b6b74;">⌄</span>
    </div>
    <a href="index.php?action=home" class="nav-item <?php echo $active==='home'?'active':''; ?>"><span class="ic">⌂</span> Home</a>
    <a href="index.php?action=search" class="nav-item <?php echo $active==='search'?'active':''; ?>"><span class="ic">⌕</span> Search</a>
    <a href="index.php?action=resources" class="nav-item <?php echo $active==='resources'?'active':''; ?>"><span class="ic">◎</span> Resources</a>
    <div class="nav-section">Projects</div>
    <a href="index.php?action=projects" class="nav-item <?php echo $active==='projects'?'active':''; ?>"><span class="ic">▦</span> All projects</a>
    <a href="index.php?action=templates" class="nav-item sb-sub <?php echo $active==='templates'?'active':''; ?>"><span class="ic">+</span> New folder</a>
    <a href="index.php?action=starred" class="nav-item <?php echo $active==='starred'?'active':''; ?>"><span class="ic">☆</span> Starred</a>
    <a href="index.php?action=created" class="nav-item <?php echo $active==='created'?'active':''; ?>"><span class="ic">◐</span> Created by me</a>
    <a href="index.php?action=shared" class="nav-item <?php echo $active==='shared'?'active':''; ?>"><span class="ic">⇄</span> Shared with me</a>
    <div class="nav-section">Recents</div>
    <?php foreach (array_slice($recents, 0, 4) as $p): ?>
        <a href="index.php?action=project&id=<?php echo (int)$p['id']; ?>" class="nav-item" style="font-size:0.8rem;color:#8a8a93;">
            <span class="ic">▢</span> <?php echo esc(mb_strimwidth($p['name'], 0, 20, '…')); ?>
        </a>
    <?php endforeach; ?>
    <div class="sb-bottom">
        <div class="referral"><div class="r-title">Share Lovable</div><div class="r-sub">100 credits per paid referral</div></div>
    </div>
</div>
<?php
}

function render_acct($me) {
?>
<div class="acct">
  <a href="index.php?action=billing" class="btn">⚡ Upgrade</a>
  <span class="av"><?php echo esc(avatar_letter($me['name'])); ?></span>
  <span style="font-size:0.85rem;"><?php echo esc(first_name($me['name'])); ?></span>
  <a href="index.php?action=logout" class="btn" style="padding:0.35rem 0.7rem;">Log out</a>
</div>
<?php
}

function project_card($db, $p, $myId, $showRole = false) {
    $starred = is_starred($db, $myId, (int)$p['id']);
    $starIcon = $starred ? '★' : '☆';
    $starCls  = $starred ? 'on' : '';
    ?>
    <div class="pcard">
        <a class="cover" href="index.php?action=project&id=<?php echo (int)$p['id']; ?>">
            <div class="thumb" style="background:<?php echo esc($p['grad']); ?>;"><?php echo esc($p['name']); ?></div>
        </a>
        <div class="meta">
            <div>
                <div class="name">
                    <a href="index.php?action=project&id=<?php echo (int)$p['id']; ?>" style="text-decoration:none;color:inherit;"><?php echo esc($p['name']); ?></a>
                    <?php if ($showRole && !empty($p['my_role'])): ?><span class="tag"><?php echo esc(ucfirst($p['my_role'])); ?></span><?php endif; ?>
                </div>
                <div class="when">Edited <?php echo esc(time_ago($p['created_at'])); ?></div>
            </div>
            <a href="index.php?action=toggle-star&id=<?php echo (int)$p['id']; ?>" class="star-btn <?php echo $starCls; ?>" title="Star"><?php echo $starIcon; ?></a>
        </div>
    </div>
    <?php
}

// =========================================================================
//  PAGES
// =========================================================================

if ($action === 'project') {
    $projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $proj = get_project_by_id($db, $projectId);
    $myRole = $proj ? role_in_project($db, $myId, $projectId) : null;
    if (!$proj || !$myRole) {
        render_head('Not found'); render_sidebar('projects', $myAll, $me);
        echo '<div class="main"><div class="topbar"><div><strong>Project</strong></div>'; render_acct($me); echo '</div><div class="wrap"><div class="empty">You don\'t have access to this project.</div></div></div></body></html>';
        exit;
    }
    $canEdit = in_array($myRole, ['owner','admin','editor'], true);
    $isOwner = ($myRole === 'owner');
    $mc = ensure_magic_code($db, $projectId);
    $members = project_members($db, $projectId);
    $inviteLink = base_url() . "?action=join&code={$mc['code']}";

    render_head($proj['name']);
    ?>
    <div class="app"><div class="main" style="width:100%;">
      <div class="topbar">
        <div style="display:flex;align-items:center;gap:0.75rem;">
          <a href="index.php?action=projects" style="color:#8a8a93;text-decoration:none;font-size:0.9rem;">Projects /</a>
          <strong><?php echo esc($proj['name']); ?></strong>
          <?php if (!$canEdit): ?><span class="viewpill">View only</span><?php endif; ?>
        </div>
        <div style="display:flex;gap:0.5rem;align-items:center;">
          <?php if ($canEdit): ?><button class="btn" onclick="openShare()">Share</button><button class="btn btn-primary">Publish</button><?php endif; ?>
          <?php render_acct($me); ?>
        </div>
      </div>
      <div class="editor">
        <div class="side">
          <p><b>Content &amp; Functionality:</b></p>
          <p>Customize the content, add a backend, implement full search, add comments, set up a newsletter.</p>
          <p style="margin-top:1.5rem;"><b>Which direction interests you most?</b></p>
        </div>
        <div class="canvas">
          <div class="previewbox" style="background:<?php echo esc($proj['grad']); ?>;color:#fff;">
            <div style="font-weight:800;letter-spacing:1px;opacity:0.85;">nexus</div>
            <div style="margin-top:2rem;font-size:1.5rem;font-weight:700;max-width:65%;"><?php echo esc($proj['name']); ?></div>
            <div style="margin-top:0.75rem;opacity:0.85;max-width:65%;font-size:0.9rem;">Featured article description — this is where your main content excerpt would appear to give readers a preview of the full article.</div>
            <div style="margin-top:2rem;display:inline-block;border:1px solid #ffffff55;border-radius:8px;padding:0.5rem 1rem;font-size:0.85rem;">READ MORE →</div>
          </div>
        </div>
      </div>
    </div></div>

    <?php if ($canEdit):
        $accessLabels = ['disabled'=>'Disabled','write'=>'Edit access','read'=>'View access'];
        $roleLabels   = ['admin'=>'Admin','editor'=>'Editor','viewer'=>'Viewer'];
    ?>
    <div class="overlay" id="overlay" onclick="if(event.target===this)closeShare()">
      <div class="modal">
        <h2>Share project <span class="x" onclick="closeShare()">&times;</span></h2>
        <form class="add-row" method="POST" action="index.php?action=add-member">
          <input type="hidden" name="project_id" value="<?php echo (int)$projectId; ?>">
          <input name="email" type="email" placeholder="Add people">
          <button class="add-btn" type="submit">Add</button>
        </form>

        <div class="sec-label">Project access</div>
        <?php foreach ($members as $mem):
            $isRowOwner = ($mem['role'] === 'owner');
            $isYou = ((int)$mem['uid'] === $myId);
        ?>
        <div class="acc-row">
          <div class="who">
            <span class="mav"><?php echo esc(avatar_letter($mem['name'])); ?></span>
            <div class="who-txt"><span class="nm"><?php echo esc($mem['name']); ?><?php echo $isYou ? ' (you)' : ''; ?></span><small><?php echo esc($mem['email']); ?></small></div>
          </div>
          <?php if ($isRowOwner): ?>
            <div class="owner-tag">Owner</div>
          <?php else: ?>
            <div class="dd" data-kind="role" data-uid="<?php echo (int)$mem['uid']; ?>">
              <button type="button" class="dd-trigger" onclick="toggleDD(this)"><span class="dd-cur"><?php echo esc($roleLabels[$mem['role']] ?? 'Editor'); ?></span> <span class="cv">⌄</span></button>
              <div class="dd-menu">
                <div class="dd-opt<?php echo $plan!=='pro'?' pro-locked':''; ?>" onclick="pickRole(this,'admin')">
                  <span class="dd-ic">◆</span>
                  <div class="dd-body"><div class="dd-t">Admin <span class="pro-tag">Pro</span></div><div class="dd-d">Full access to manage the project</div></div>
                </div>
                <div class="dd-opt" onclick="pickRole(this,'editor')">
                  <span class="dd-ic">✎</span>
                  <div class="dd-body"><div class="dd-t">Editor</div><div class="dd-d">Can edit the project</div></div>
                </div>
                <div class="dd-opt<?php echo $plan!=='pro'?' pro-locked':''; ?>" onclick="pickRole(this,'viewer')">
                  <span class="dd-ic">◉</span>
                  <div class="dd-body"><div class="dd-t">Viewer <span class="pro-tag">Pro</span></div><div class="dd-d">Can view the project only</div></div>
                </div>
                <div class="dd-opt danger" onclick="pickRole(this,'remove')">
                  <span class="dd-ic">✕</span>
                  <div class="dd-body"><div class="dd-t">Remove</div><div class="dd-d">Revoke this member's access</div></div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="acc-row" style="border-bottom:none;">
          <div class="who">
            <span class="mav ghost">🔗</span>
            <div class="who-txt"><span class="nm">Invite link</span></div>
          </div>
          <div class="dd" data-kind="access" id="accessDD">
            <button type="button" class="dd-trigger" onclick="toggleDD(this)"><span class="dd-cur"><?php echo esc($accessLabels[$mc['access_level']] ?? 'Edit access'); ?></span> <span class="cv">⌄</span></button>
            <div class="dd-menu">
              <div class="dd-opt" onclick="pickAccess(this,'write')">
                <span class="dd-ic">✎</span>
                <div class="dd-body"><div class="dd-t">Edit access</div><div class="dd-d">Anyone with link becomes editor</div></div>
              </div>
              <div class="dd-opt<?php echo $plan!=='pro'?' pro-locked':''; ?>" onclick="pickAccess(this,'read')">
                <span class="dd-ic">◉</span>
                <div class="dd-body"><div class="dd-t">View access <span class="pro-tag">Pro</span></div><div class="dd-d">Anyone with link becomes viewer</div></div>
              </div>
              <div class="dd-opt" onclick="pickAccess(this,'disabled')">
                <span class="dd-ic">⊘</span>
                <div class="dd-body"><div class="dd-t">Disabled</div><div class="dd-d">Invite link disabled</div></div>
              </div>
            </div>
          </div>
        </div>

        <div class="linkbox">
          <input id="inviteLink" readonly value="<?php echo esc($inviteLink); ?>">
          <button class="copy-btn" onclick="copyLink()" title="Copy link">⧉</button>
        </div>
        <div id="lockedMsg" style="display:none;">🔒 View access requires a Pro plan. <a href="index.php?action=billing">Upgrade</a> to enable this.</div>
      </div>
    </div>
    <div class="toast" id="toast"></div>
    <script>
    const PLAN = <?php echo json_encode($plan); ?>;
    const PROJECT_ID = <?php echo (int)$projectId; ?>;
    function openShare(){ document.getElementById('overlay').classList.add('open'); }
    function closeShare(){ document.getElementById('overlay').classList.remove('open'); closeAllDD(); }
    function showToast(m){ const t=document.getElementById('toast'); t.textContent=m; t.style.display='block'; setTimeout(()=>t.style.display='none',2500); }
    function copyLink(){ const i=document.getElementById('inviteLink'); i.select(); navigator.clipboard.writeText(i.value); showToast('Invite link copied'); }
    function closeAllDD(){ document.querySelectorAll('.dd.open').forEach(d=>d.classList.remove('open')); }
    function toggleDD(btn){ const dd=btn.closest('.dd'); const was=dd.classList.contains('open'); closeAllDD(); if(!was) dd.classList.add('open'); }
    document.addEventListener('click', e=>{ if(!e.target.closest('.dd')) closeAllDD(); });

    function pickAccess(opt, value){
      closeAllDD();
      if (value === 'read' && PLAN !== 'pro') {
        showToast('Upgrade to Pro to enable View access.');
        document.getElementById('lockedMsg').style.display='block';
        return;
      }
      document.getElementById('lockedMsg').style.display='none';
      fetch('?action=magic-codes&project_id='+PROJECT_ID,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({access_level:value})})
        .then(r=>r.json()).then(()=>location.reload());
    }
    function pickRole(opt, role){
      closeAllDD();
      const dd = opt.closest('.dd'); const userId = dd.getAttribute('data-uid');
      if ((role==='viewer'||role==='admin') && PLAN !== 'pro') { showToast('Upgrade to Pro to assign that role.'); return; }
      fetch('?action=set-role&project_id='+PROJECT_ID+'&user_id='+userId,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({role:role})})
        .then(r=>r.json()).then(()=>location.reload());
    }
    </script>
    <?php endif; ?>
    </body></html>
    <?php
    exit;
}

// PLAN_GATE_JS_MARK: on the free plan, the Pro options are disabled in the UI.
function PLAN_GATE_JS_MARK($plan) { return $plan === 'pro' ? '' : 'disabled'; }

if ($action === 'billing') {
    render_head('Billing'); render_sidebar('billing', $myAll, $me);
    ?>
    <div class="main">
      <div class="topbar"><div><strong>Billing &amp; Plans</strong></div><?php render_acct($me); ?></div>
      <div class="wrap">
        <?php if (isset($_GET['declined'])): ?>
        <div class="declined">⚠ Payment could not be completed right now. Your card was <strong>not</strong> charged and your plan remains <code>free</code>. Please try again later.</div>
        <?php endif; ?>
        <h1>Choose a plan</h1>
        <div class="sub">Upgrade to unlock Pro-only sharing controls like read-only "View access" links and Viewer/Admin roles.</div>
        <div class="pricing">
          <div class="plan"><div class="pname">Free</div><div class="pprice">$0<span>/mo</span></div>
            <ul><li>5 daily messages</li><li>Public projects</li><li>Edit-access invite links only</li></ul>
            <button class="btn" style="width:100%;" disabled>Current plan</button></div>
          <div class="plan highlight"><div class="pname">Pro</div><div class="pprice">$25<span>/mo</span></div>
            <ul><li>Unlimited messages</li><li>Private projects</li><li><strong>View-access (read-only) invite links</strong></li><li>Viewer &amp; Admin roles</li><li>Custom domains</li></ul>
            <form method="POST" action="index.php?action=upgrade-attempt"><button class="btn btn-pro" style="width:100%;" type="submit">Upgrade to Pro</button></form></div>
          <div class="plan"><div class="pname">Business</div><div class="pprice">$50<span>/mo</span></div>
            <ul><li>Everything in Pro</li><li>SSO &amp; team roles</li><li>Priority support</li></ul>
            <button class="btn" style="width:100%;" disabled>Contact sales</button></div>
        </div>
      </div>
    </div></body></html>
    <?php
    exit;
}

if ($action === 'templates') {
    render_head('Templates'); render_sidebar('templates', $myAll, $me);
    ?>
    <div class="main">
      <div class="topbar"><div><strong>Templates</strong></div><?php render_acct($me); ?></div>
      <div class="wrap">
        <h1>Start from a template</h1>
        <div class="sub">Pick a template, remix it, and it lands in your Projects list.</div>
        <div class="grid">
          <?php foreach ($TEMPLATES as $t): ?>
          <div class="tcard">
            <div class="thumb" style="background:<?php echo esc($t['grad']); ?>;"><?php echo esc($t['name']); ?></div>
            <div class="body"><div class="n"><?php echo esc($t['name']); ?></div><div class="d"><?php echo esc($t['desc']); ?></div>
              <button class="btn btn-primary use" onclick="openRemix('<?php echo esc($t['name']); ?>')">Use template</button></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="overlay center" id="remixOverlay"><div class="modal small">
      <h2>Remix project <span class="x" onclick="closeRemix()">&times;</span></h2>
      <p style="color:#8a8a93;font-size:0.85rem;">By remixing a project, you will create a copy that you own.</p>
      <form method="POST" action="index.php?action=remix">
        <input type="hidden" name="template" id="templateField">
        <label style="font-size:0.8rem;color:#8a8a93;">Project name</label>
        <input name="name" id="nameField" required style="width:100%;background:#0d0d10;border:1px solid #2a2a30;color:#e5e5ea;border-radius:8px;padding:0.55rem 0.7rem;font-size:0.9rem;margin-top:0.3rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:1rem;">
          <label style="font-size:0.85rem;">Include project history</label><input type="checkbox" class="toggle">
        </div>
        <div style="display:flex;gap:0.5rem;justify-content:flex-end;margin-top:1.25rem;">
          <button type="button" class="btn" onclick="closeRemix()">Cancel</button>
          <button type="submit" class="btn btn-primary">Remix</button>
        </div>
      </form>
    </div></div>
    <script>
    function openRemix(n){document.getElementById('templateField').value=n;document.getElementById('nameField').value=n;document.getElementById('remixOverlay').classList.add('open');}
    function closeRemix(){document.getElementById('remixOverlay').classList.remove('open');}
    </script>
    </body></html>
    <?php
    exit;
}

if ($action === 'resources') {
    render_head('Resources'); render_sidebar('resources', $myAll, $me);
    ?>
    <div class="main"><div class="topbar"><div><strong>Resources</strong></div><?php render_acct($me); ?></div>
      <div class="wrap"><h1>Resources</h1><div class="sub">Guides and community links.</div>
        <div class="grid">
          <div class="tcard"><div class="body"><div class="n">Docs</div><div class="d">Learn how projects, templates and sharing work.</div></div></div>
          <div class="tcard"><div class="body"><div class="n">Community</div><div class="d">Get help and share what you built.</div></div></div>
          <div class="tcard"><div class="body"><div class="n">Changelog</div><div class="d">See what shipped recently.</div></div></div>
        </div>
      </div></div></body></html>
    <?php
    exit;
}

if ($action === 'search') {
    $q = trim($_GET['q'] ?? '');
    $results = $q === '' ? [] : array_values(array_filter($myAll, fn($p) => stripos($p['name'], $q) !== false));
    render_head('Search'); render_sidebar('search', $myAll, $me);
    ?>
    <div class="main"><div class="topbar"><div><strong>Search</strong></div><?php render_acct($me); ?></div>
      <div class="wrap">
        <form class="searchbar"><input type="hidden" name="action" value="search"><input type="text" name="q" placeholder="Search your projects..." value="<?php echo esc($q); ?>" autofocus></form>
        <?php if ($q === ''): ?><div class="empty">Start typing to search your projects.</div>
        <?php elseif (empty($results)): ?><div class="empty">No projects match "<?php echo esc($q); ?>".</div>
        <?php else: ?><div class="grid"><?php foreach ($results as $p) project_card($db, $p, $myId, true); ?></div><?php endif; ?>
      </div></div></body></html>
    <?php
    exit;
}

if (in_array($action, ['starred','created','shared'], true)) {
    $titleMap = ['starred'=>'Starred','created'=>'Created by me','shared'=>'Shared with me'];
    render_head($titleMap[$action]); render_sidebar($action, $myAll, $me);
    ?>
    <div class="main"><div class="topbar"><div><strong><?php echo esc($titleMap[$action]); ?></strong></div><?php render_acct($me); ?></div>
      <div class="wrap"><h1><?php echo esc($titleMap[$action]); ?></h1>
      <?php
      if ($action === 'starred') {
          $list = array_values(array_filter($myAll, fn($p) => is_starred($db, $myId, (int)$p['id'])));
          if (empty($list)) echo '<div class="empty">No starred projects yet. Click the ☆ on any project card to pin it here.</div>';
          else { echo '<div class="grid">'; foreach ($list as $p) project_card($db, $p, $myId, true); echo '</div>'; }
      } elseif ($action === 'created') {
          if (empty($myOwned)) echo '<div class="empty">You haven\'t created any projects yet.</div>';
          else { echo '<div class="grid">'; foreach ($myOwned as $p) project_card($db, $p, $myId); echo '</div>'; }
      } else {
          if (empty($myShared)) echo '<div class="empty">No projects have been shared with you yet.</div>';
          else { echo '<div class="grid">'; foreach ($myShared as $p) project_card($db, $p, $myId, true); echo '</div>'; }
      }
      ?>
      </div></div></body></html>
    <?php
    exit;
}

if ($action === 'projects') {
    render_head('Projects'); render_sidebar('projects', $myAll, $me);
    ?>
    <div class="main"><div class="topbar"><div><strong>Projects</strong> <span style="color:#6b6b74;">···</span></div><?php render_acct($me); ?></div>
      <div class="wrap">
        <form class="searchbar"><input type="hidden" name="action" value="search"><input type="text" name="q" placeholder="Search projects...">
          <select disabled><option>Last edited</option></select><select disabled><option>Any visibility</option></select><select disabled><option>Any status</option></select></form>
        <div class="grid">
          <a href="index.php?action=templates" class="dashed"><div style="font-size:1.6rem;">+</div><div>Create new project</div></a>
          <?php foreach ($myAll as $p) project_card($db, $p, $myId, !empty($p['my_role']) && $p['my_role']!=='owner'); ?>
        </div>
      </div></div></body></html>
    <?php
    exit;
}

// ── Default: Home ────────────────────────────────────────────────────────────────
render_head('Home'); render_sidebar('home', $myAll, $me);
$joined = $_SESSION['joined_notice'] ?? null; unset($_SESSION['joined_notice']);
$joinErr = $_SESSION['join_error'] ?? null; unset($_SESSION['join_error']);
?>
<div class="main">
  <div class="topbar"><div><strong>Home</strong></div><?php render_acct($me); ?></div>
  <div class="wrap">
    <?php if ($joined): ?><div class="notice">You joined <strong><?php echo esc($joined['project']); ?></strong> as <strong><?php echo esc(ucfirst($joined['role'])); ?></strong>.</div><?php endif; ?>
    <?php if ($joinErr): ?><div class="warn"><?php echo esc($joinErr); ?></div><?php endif; ?>
    <h1>Build something Lovable</h1>
    <div class="sub">Create apps and websites by chatting with AI.</div>
    <div class="prompt-box">
      <textarea placeholder="Ask Lovable to create a landing page for my..."></textarea>
      <div class="chips">
        <span class="chip" onclick="location.href='index.php?action=templates'">Landing page</span>
        <span class="chip" onclick="location.href='index.php?action=templates'">Dashboard</span>
        <span class="chip" onclick="location.href='index.php?action=templates'">Blog</span>
        <span class="chip" onclick="location.href='index.php?action=templates'">Ecommerce store</span>
        <span class="chip" onclick="location.href='index.php?action=templates'">Portfolio</span>
      </div>
    </div>
    <h1 style="font-size:1.15rem;">Recent projects</h1>
    <div class="grid">
      <a href="index.php?action=templates" class="dashed"><div style="font-size:1.6rem;">+</div><div>Create new project</div></a>
      <?php foreach (array_slice($myAll, 0, 7) as $p) project_card($db, $p, $myId, !empty($p['my_role']) && $p['my_role']!=='owner'); ?>
    </div>
  </div>
</div>
</body></html>
<?php
// End
?>
