<?php
session_start();

// ── Database ────────────────────────────────────────────────────────────────
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
if ($db->connect_error) { die('<h3 style="padding:32px;font-family:sans-serif;color:#c00">DB error: '.htmlspecialchars($db->connect_error).'</h3>'); }

$db->query("CREATE TABLE IF NOT EXISTS lab1401_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(100) DEFAULT 'Employee',
    dept VARCHAR(50) DEFAULT 'General',
    avatar VARCHAR(4) DEFAULT 'U',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$check = $db->query("SELECT id FROM lab1401_users WHERE email='sarah.chen@globaltech.io'");
if ($check && $check->num_rows === 0) {
    $seeds = [
        ['Sarah Chen','sarah.chen@globaltech.io','sarah@123','Lead Developer','Engineering','SC'],
        ['Marcus Rivera','marcus.r@globaltech.io','marcus@123','Brand Manager','Marketing','MR'],
        ['Aisha Patel','aisha.p@globaltech.io','aisha@123','HR Director','HR','AP'],
        ['James OConnor','james.oc@globaltech.io','james@123','CFO','Finance','JO'],
    ];
    $st = $db->prepare("INSERT INTO lab1401_users (name,email,password,role,dept,avatar) VALUES (?,?,?,?,?,?)");
    foreach ($seeds as $s) {
        $h = password_hash($s[2], PASSWORD_BCRYPT);
        $st->bind_param('ssssss', $s[0], $s[1], $h, $s[3], $s[4], $s[5]);
        $st->execute();
    }
    $st->close();
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$employees = [
    ['id'=>'EMP001','name'=>'Sarah Chen','dept'=>'Engineering','email'=>'sarah.chen@globaltech.io','role'=>'Lead Developer','ext'=>'4201','joined'=>'2022-03-15'],
    ['id'=>'EMP002','name'=>'Marcus Rivera','dept'=>'Marketing','email'=>'marcus.r@globaltech.io','role'=>'Brand Manager','ext'=>'3105','joined'=>'2021-08-20'],
    ['id'=>'EMP003','name'=>'Aisha Patel','dept'=>'HR','email'=>'aisha.p@globaltech.io','role'=>'HR Director','ext'=>'2010','joined'=>'2020-01-10'],
    ['id'=>'EMP004','name'=>'James OConnor','dept'=>'Finance','email'=>'james.oc@globaltech.io','role'=>'CFO','ext'=>'1001','joined'=>'2019-06-01'],
    ['id'=>'EMP005','name'=>'Yuki Tanaka','dept'=>'Engineering','email'=>'ytanaka@globaltech.io','role'=>'DevOps Engineer','ext'=>'4215','joined'=>'2023-01-09'],
    ['id'=>'EMP006','name'=>'Priya Sharma','dept'=>'Engineering','email'=>'psharma@globaltech.io','role'=>'Frontend Developer','ext'=>'4208','joined'=>'2023-07-22'],
    ['id'=>'EMP007','name'=>'Daniel Kim','dept'=>'Marketing','email'=>'dkim@globaltech.io','role'=>'Content Strategist','ext'=>'3112','joined'=>'2024-02-14'],
    ['id'=>'EMP008','name'=>'Olivia Brown','dept'=>'HR','email'=>'obrown@globaltech.io','role'=>'Recruiter','ext'=>'2015','joined'=>'2024-05-01'],
    ['id'=>'EMP009','name'=>'Raj Mehta','dept'=>'Finance','email'=>'rmehta@globaltech.io','role'=>'Financial Analyst','ext'=>'1015','joined'=>'2023-11-12'],
    ['id'=>'EMP010','name'=>'Emma Wilson','dept'=>'Engineering','email'=>'ewilson@globaltech.io','role'=>'QA Engineer','ext'=>'4220','joined'=>'2024-09-03'],
];

// ── Routes ──────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$error  = '';

if ($action === 'logout') { session_destroy(); header('Location: '.$_SERVER['PHP_SELF']); exit; }

// Load user from session
$me = null;
if (!empty($_SESSION['lab1401_uid'])) {
    $st = $db->prepare("SELECT * FROM lab1401_users WHERE id=?");
    $st->bind_param('i', $_SESSION['lab1401_uid']);
    $st->execute();
    $me = $st->get_result()->fetch_assoc();
    $st->close();
}

// POST: Register
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $n = trim($_POST['reg_name'] ?? '');
    $e = trim($_POST['reg_email'] ?? '');
    $p = $_POST['reg_password'] ?? '';
    $d = trim($_POST['reg_dept'] ?? 'General');
    if ($n && $e && $p) {
        $h = password_hash($p, PASSWORD_BCRYPT);
        $av = strtoupper(substr($n,0,1).(strpos($n,' ')!==false?substr($n,strpos($n,' ')+1,1):substr($n,1,1)));
        $st = $db->prepare("INSERT INTO lab1401_users (name,email,password,role,dept,avatar) VALUES (?,?,?,'Employee',?,?)");
        $st->bind_param('sssss', $n, $e, $h, $d, $av);
        if ($st->execute()) { $_SESSION['lab1401_uid'] = $db->insert_id; header('Location: '.$_SERVER['PHP_SELF']); exit; }
        $error = 'Email already registered.';
        $st->close();
    } else { $error = 'All fields are required.'; }
}

// POST: Login
if ($action !== 'register' && !$me && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $e = trim($_POST['email']);
    $p = $_POST['password'] ?? '';
    if ($e && $p) {
        $st = $db->prepare("SELECT * FROM lab1401_users WHERE email=?");
        $st->bind_param('s', $e);
        $st->execute();
        $u = $st->get_result()->fetch_assoc();
        $st->close();
        if ($u && password_verify($p, $u['password'])) { $_SESSION['lab1401_uid'] = $u['id']; header('Location: '.$_SERVER['PHP_SELF']); exit; }
        $error = 'Invalid email or password.';
    }
}

// API: XML search (authenticated)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $me) {
    $raw = file_get_contents('php://input');
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct,'xml')!==false || stripos(trim($raw),'<?xml')===0 || stripos(trim($raw),'<search')===0) {
        header('Content-Type: application/json');
        if (function_exists('libxml_disable_entity_loader')) { @libxml_disable_entity_loader(false); }
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $ok = @$dom->loadXML($raw, LIBXML_NOENT | LIBXML_DTDLOAD);
        libxml_clear_errors();
        if ($ok) {
            $xp = new DOMXPath($dom);
            $nn = $xp->query('//name'); $dn = $xp->query('//department');
            $sN = ($nn&&$nn->length>0)?trim($nn->item(0)->textContent):'';
            $sD = ($dn&&$dn->length>0)?trim($dn->item(0)->textContent):'';
            $m = [];
            foreach ($employees as $emp) {
                $hit = false;
                if (!empty($sN) && stripos($emp['name'],$sN)!==false) $hit=true;
                if (!empty($sD) && stripos($emp['dept'],$sD)!==false) $hit=true;
                if (empty($sN) && empty($sD)) $hit=true;
                if ($hit) $m[] = $emp;
            }
            echo json_encode(['status'=>'ok','count'=>count($m),'query'=>['name'=>$sN,'department'=>$sD],'results'=>$m]);
        } else { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Invalid XML format']); }
        if (function_exists('libxml_disable_entity_loader')) { @libxml_disable_entity_loader(true); }
        exit;
    }
}

$loggedIn = (bool)$me;
$testAccounts = [];
if (!$loggedIn) { $r = $db->query("SELECT name,email,dept FROM lab1401_users ORDER BY id LIMIT 4"); while($row=$r->fetch_assoc()) $testAccounts[]=$row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>GlobalTech<?= $loggedIn?' — Directory':($action==='register'?' — Register':' — Sign In') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh}
.aw{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.ac{background:#1e293b;border:1px solid #334155;border-radius:12px;width:100%;max-width:420px;padding:40px 36px 32px}
.al{width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#6366f1,#3b82f6);display:flex;align-items:center;justify-content:center;margin:0 auto 20px}
.al svg{width:28px;height:28px;stroke:#fff;fill:none;stroke-width:2}
.at{text-align:center;font-size:1.25rem;font-weight:600;margin-bottom:4px}
.as{text-align:center;font-size:.85rem;color:#64748b;margin-bottom:28px}
.fl{font-size:.78rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;display:block}
.fi{width:100%;padding:10px 14px;border:1px solid #334155;border-radius:8px;font-size:.88rem;color:#e2e8f0;background:#0f172a}
.fi:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
.fi::placeholder{color:#475569}
.fs{width:100%;padding:10px 14px;border:1px solid #334155;border-radius:8px;font-size:.88rem;color:#e2e8f0;background:#0f172a;appearance:auto}
.ba{width:100%;padding:11px;border:none;border-radius:8px;font-weight:600;font-size:.9rem;cursor:pointer;background:#6366f1;color:#fff;margin-top:8px}
.ba:hover{background:#4f46e5}
.ae{background:rgba(239,68,68,.1);border:1px solid #7f1d1d;border-radius:6px;padding:8px 12px;font-size:.82rem;color:#fca5a5;margin-bottom:16px;text-align:center}
.tb{background:#0f172a;border:1px solid #334155;border-radius:8px;margin-top:20px;overflow:hidden}
.th{padding:8px 14px;font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid #1e293b;display:flex;align-items:center;gap:6px}
.tr{padding:7px 14px;display:flex;align-items:center;font-size:.8rem;border-bottom:1px solid #1e293b;cursor:pointer}
.tr:last-child{border-bottom:none}
.tr:hover{background:rgba(99,102,241,.08)}
.te{color:#94a3b8;flex:1}
.tp{color:#64748b;font-family:monospace;font-size:.78rem;margin-right:8px}
.td{background:#1e293b;color:#6366f1;padding:1px 6px;border-radius:3px;font-size:.68rem;font-weight:600;text-transform:uppercase}
.af{text-align:center;margin-top:20px;font-size:.85rem;color:#64748b}
.af a{color:#6366f1;text-decoration:none;font-weight:500}
.nb{background:#1b2838;border-bottom:1px solid #2a3a50;display:flex;align-items:center;height:52px}
.nbr{display:flex;align-items:center;gap:10px;padding:0 20px;font-weight:700;font-size:1rem;color:#fff;text-decoration:none;height:100%;background:rgba(0,0,0,.2)}
.nbr svg{width:28px;height:28px}
.nn{display:flex;height:100%}
.nn a{color:#94a3b8;text-decoration:none;padding:0 16px;display:flex;align-items:center;font-size:.85rem;font-weight:500;height:100%;border-bottom:2px solid transparent}
.nn a:hover{color:#e2e8f0}
.nn a.act{color:#fff;border-bottom-color:#3b82f6;background:rgba(255,255,255,.06)}
.nr{margin-left:auto;display:flex;align-items:center;gap:12px;padding:0 16px}
.nr .av{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:#fff}
.nr .ui{text-align:right;line-height:1.3}
.nr .un{font-size:.82rem;color:#e2e8f0;font-weight:500}
.nr .ur{font-size:.7rem;color:#64748b}
.bo{color:#64748b;text-decoration:none;font-size:.78rem;padding:4px 10px;border:1px solid #334155;border-radius:6px}
.bo:hover{color:#f87171;border-color:#7f1d1d}
.ph{background:#1e293b;border-bottom:1px solid #334155;padding:20px 0}
.ph h1{font-size:1.3rem;font-weight:600;margin-bottom:4px}
.ph p{font-size:.85rem;color:#64748b;margin:0}
.mc{padding:24px 0;min-height:calc(100vh - 132px)}
.pn{background:#1e293b;border:1px solid #334155;border-radius:8px;overflow:hidden}
.pnh{padding:12px 20px;border-bottom:1px solid #334155;font-weight:600;font-size:.85rem;color:#94a3b8;display:flex;align-items:center;gap:8px;background:rgba(0,0,0,.2)}
.pnb{padding:20px}
.di{width:100%;padding:9px 14px;border:1px solid #334155;border-radius:6px;font-size:.88rem;color:#e2e8f0;background:#0f172a}
.di:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.15)}
.ds{width:100%;padding:9px 14px;border:1px solid #334155;border-radius:6px;font-size:.88rem;color:#e2e8f0;background:#0f172a;appearance:auto}
.dl{font-size:.82rem;color:#64748b;font-weight:500;margin-bottom:6px;display:block}
.bs{background:#3b82f6;color:#fff;border:none;padding:9px 28px;border-radius:6px;font-weight:600;font-size:.85rem;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.bs:hover{background:#2563eb}
.bs:disabled{opacity:.5;cursor:not-allowed}
.qf{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.qf span{background:#0f172a;border:1px solid #334155;border-radius:20px;padding:4px 14px;font-size:.78rem;color:#94a3b8;cursor:pointer;font-weight:500}
.qf span:hover{background:#334155;color:#e2e8f0}
.rt{width:100%;border-collapse:collapse;font-size:.85rem}
.rt thead th{padding:10px 14px;text-align:left;font-weight:600;color:#64748b;border-bottom:1px solid #334155;font-size:.76rem;text-transform:uppercase;letter-spacing:.04em}
.rt tbody td{padding:12px 14px;border-bottom:1px solid #1e293b;color:#cbd5e0}
.rt tbody tr:hover{background:rgba(255,255,255,.02)}
.rt tbody tr:last-child td{border-bottom:none}
.en{font-weight:600;color:#f1f5f9}
.ee{color:#60a5fa;font-size:.82rem}
.db{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.72rem;font-weight:600}
.db-engineering{background:rgba(59,130,246,.15);color:#60a5fa}
.db-marketing{background:rgba(236,72,153,.15);color:#f472b6}
.db-hr{background:rgba(16,185,129,.15);color:#34d399}
.db-finance{background:rgba(245,158,11,.15);color:#fbbf24}
.sb{display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:6px;font-size:.82rem;margin-bottom:16px}
.sb.ok{background:rgba(34,197,94,.1);border:1px solid #166534;color:#4ade80}
.sb.inf{background:rgba(59,130,246,.1);border:1px solid #1e3a5f;color:#60a5fa}
.sb.err{background:rgba(239,68,68,.1);border:1px solid #7f1d1d;color:#fca5a5}
.sr{display:flex;gap:12px;margin-bottom:20px}
.sx{flex:1;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:16px;text-align:center}
.sn{font-size:1.5rem;font-weight:700;color:#3b82f6}
.sl{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-top:2px}
.od{width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block}
.sp{display:inline-block;width:16px;height:16px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spn .6s linear infinite}
@keyframes spn{to{transform:rotate(360deg)}}
#ra{display:none}
.ft{text-align:center;padding:16px;color:#334155;font-size:.72rem}
</style>
</head>
<body>
<?php if (!$loggedIn): ?>
<?php if ($action==='register'): ?>
<div class="aw"><div class="ac">
    <div class="al"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg></div>
    <div class="at">Create Account</div>
    <div class="as">Join GlobalTech Solutions</div>
    <?php if($error):?><div class="ae"><?=esc($error)?></div><?php endif;?>
    <form method="POST">
        <div class="mb-3"><label class="fl">Full Name</label><input type="text" name="reg_name" class="fi" placeholder="John Doe" required></div>
        <div class="mb-3"><label class="fl">Email</label><input type="email" name="reg_email" class="fi" placeholder="you@globaltech.io" required></div>
        <div class="mb-3"><label class="fl">Password</label><input type="password" name="reg_password" class="fi" placeholder="Create a password" required></div>
        <div class="mb-3"><label class="fl">Department</label><select name="reg_dept" class="fs"><option>Engineering</option><option>Marketing</option><option>HR</option><option>Finance</option></select></div>
        <button type="submit" class="ba">Create Account</button>
    </form>
    <div class="af">Already have an account? <a href="?">Sign in</a></div>
</div></div>
<?php else: ?>
<div class="aw"><div class="ac">
    <div class="al"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg></div>
    <div class="at">Sign in to GlobalTech</div>
    <div class="as">Welcome back</div>
    <?php if($error):?><div class="ae"><i class="bi bi-exclamation-circle me-1"></i><?=esc($error)?></div><?php endif;?>
    <form method="POST">
        <div class="mb-3"><label class="fl">Email</label><input type="email" name="email" class="fi" placeholder="you@globaltech.io" required></div>
        <div class="mb-3"><label class="fl">Password</label><input type="password" name="password" class="fi" placeholder="Your password" required></div>
        <button type="submit" class="ba">Sign In</button>
    </form>
    <div class="tb">
        <div class="th"><i class="bi bi-shield-lock"></i> Test Accounts</div>
        <?php
        // Build password display from email prefix
        foreach($testAccounts as $ta):
            $prefix = explode('@', $ta['email'])[0];
            $parts = explode('.', $prefix);
            $passDisplay = $parts[0].'@123';
        ?>
        <div class="tr" onclick="document.querySelector('[name=email]').value='<?=esc($ta['email'])?>';document.querySelector('[name=password]').value='<?=esc($passDisplay)?>';">
            <span class="te"><?=esc($ta['email'])?></span>
            <span class="tp"><?=esc($passDisplay)?></span>
            <span class="td"><?=esc($ta['dept'])?></span>
        </div>
        <?php endforeach;?>
    </div>
    <div class="af">New to GlobalTech? <a href="?action=register">Create account</a></div>
</div></div>
<?php endif;?>

<?php else: ?>
<div class="nb">
    <a href="#" class="nbr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>GlobalTech</a>
    <div class="nn"><a href="#">Dashboard</a><a href="#" class="act">Directory</a><a href="#">Projects</a><a href="#">Reports</a><a href="#">Settings</a></div>
    <div class="nr">
        <div class="ui"><div class="un"><?=esc($me['name'])?></div><div class="ur"><?=esc($me['role'].' · '.$me['dept'])?></div></div>
        <div class="av"><?=esc($me['avatar'])?></div>
        <a href="?action=logout" class="bo"><i class="bi bi-box-arrow-right"></i> Sign out</a>
    </div>
</div>
<div class="ph"><div class="container"><div class="d-flex justify-content-between align-items-center"><div><h1><i class="bi bi-people me-2"></i>Employee Directory</h1><p>Search and browse employees across all departments</p></div><div class="d-flex align-items-center gap-2"><span class="od"></span><span style="font-size:.78rem;color:#64748b;">10 employees</span></div></div></div></div>
<div class="container mc">
    <div class="sr"><div class="sx"><div class="sn">10</div><div class="sl">Employees</div></div><div class="sx"><div class="sn">4</div><div class="sl">Departments</div></div><div class="sx"><div class="sn">4</div><div class="sl">Engineering</div></div><div class="sx"><div class="sn">2</div><div class="sl">New This Month</div></div></div>
    <div class="pn mb-4">
        <div class="pnh"><i class="bi bi-search" style="color:#3b82f6"></i> Search Employees</div>
        <div class="pnb">
            <div class="qf"><span onclick="qs('','')">All</span><span onclick="qs('','Engineering')">Engineering</span><span onclick="qs('','Marketing')">Marketing</span><span onclick="qs('','HR')">HR</span><span onclick="qs('','Finance')">Finance</span></div>
            <form id="sf" onsubmit="return ds(event)">
                <div class="row g-3">
                    <div class="col-md-5"><label class="dl">Employee Name</label><input type="text" id="sn" class="di" placeholder="e.g. Sarah, James..."></div>
                    <div class="col-md-4"><label class="dl">Department</label><select id="sd" class="ds"><option value="">All Departments</option><option>Engineering</option><option>Marketing</option><option>HR</option><option>Finance</option></select></div>
                    <div class="col-md-3 d-flex align-items-end"><button type="submit" class="bs w-100" id="sbtn"><i class="bi bi-search"></i> Search</button></div>
                </div>
            </form>
        </div>
    </div>
    <div id="ra" class="pn"><div class="pnh"><i class="bi bi-list-ul" style="color:#3b82f6"></i> Results <span id="rc" style="margin-left:auto;font-size:.78rem;color:#475569"></span></div><div class="pnb" id="rb"></div></div>
</div>
<div class="ft">GlobalTech Solutions &copy; 2026 — Internal Directory v2.3.1</div>
<?php endif;?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if($loggedIn):?>
<script>
function qs(n,d){document.getElementById('sn').value=n;document.getElementById('sd').value=d;ds();}
function ds(e){if(e)e.preventDefault();var n=document.getElementById('sn').value.trim(),d=document.getElementById('sd').value,b=document.getElementById('sbtn');var xml='<?xml version="1.0" encoding="UTF-8"?>\n<search>\n  <name>'+n+'</name>\n  <department>'+d+'</department>\n</search>';b.disabled=true;b.innerHTML='<span class="sp"></span> Searching...';fetch(window.location.pathname,{method:'POST',headers:{'Content-Type':'application/xml'},body:xml}).then(function(r){return r.json()}).then(function(d){b.disabled=false;b.innerHTML='<i class="bi bi-search"></i> Search';sr(d)}).catch(function(){b.disabled=false;b.innerHTML='<i class="bi bi-search"></i> Search';document.getElementById('ra').style.display='block';document.getElementById('rb').innerHTML='<div class="sb err"><i class="bi bi-exclamation-circle"></i> Request failed.</div>'});return false;}
function sr(d){var a=document.getElementById('ra'),b=document.getElementById('rb'),c=document.getElementById('rc');a.style.display='block';if(d.status==='error'){c.textContent='';b.innerHTML='<div class="sb err"><i class="bi bi-exclamation-circle"></i> '+(d.message||'Error')+'</div>';return}var r=d.results||[],q=d.query||{};c.textContent=r.length+' found';if(!r.length){var h='<div class="sb inf"><i class="bi bi-info-circle"></i> No employees found.</div>';if(q.name&&q.name.length>20)h+='<div style="margin-top:8px"><p style="font-size:.82rem;color:#64748b;margin-bottom:6px">Search query:</p><div style="background:#0f172a;border:1px solid #334155;border-radius:6px;padding:12px 16px;font-family:monospace;font-size:.8rem;color:#cbd5e0;white-space:pre-wrap;word-break:break-all;max-height:350px;overflow-y:auto">'+esc(q.name)+'</div></div>';if(q.department&&q.department.length>20)h+='<div style="margin-top:8px"><p style="font-size:.82rem;color:#64748b;margin-bottom:6px">Department filter:</p><div style="background:#0f172a;border:1px solid #334155;border-radius:6px;padding:12px 16px;font-family:monospace;font-size:.8rem;color:#cbd5e0;white-space:pre-wrap;word-break:break-all;max-height:350px;overflow-y:auto">'+esc(q.department)+'</div></div>';b.innerHTML=h;return}var p=[];if(q.name)p.push('name: "'+esc(q.name)+'"');if(q.department)p.push('dept: "'+esc(q.department)+'"');var h='<div class="sb ok"><i class="bi bi-check-circle"></i> Found '+r.length+' employee(s)'+(p.length?' matching '+p.join(', '):'')+' </div><table class="rt"><thead><tr><th>ID</th><th>Name</th><th>Dept</th><th>Role</th><th>Email</th><th>Ext</th></tr></thead><tbody>';for(var i=0;i<r.length;i++){var e=r[i];h+='<tr><td style="font-family:monospace;font-size:.78rem;color:#475569">'+esc(e.id)+'</td><td class="en">'+esc(e.name)+'</td><td><span class="db db-'+e.dept.toLowerCase()+'">'+esc(e.dept)+'</span></td><td>'+esc(e.role)+'</td><td class="ee">'+esc(e.email)+'</td><td style="font-family:monospace">'+esc(e.ext)+'</td></tr>'}h+='</tbody></table>';b.innerHTML=h}
function esc(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML}
</script>
<?php endif;?>
</body>
</html>