<?php
/*
 * MTN MoMAD (Mobile Advertising) business portal.
 * Training lab (lab205). The /app/login endpoint trusts the client-supplied
 * "gateway" flag: when it is true the account for the submitted email is
 * returned without verifying the password, so changing the email and flipping
 * the flag logs an attacker in as any user. Students register their own
 * accounts (attacker + victim) and test the bypass between them.
 */

$db_name = getenv('DB_NAME') ?: 'KrazePlanet';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_host = getenv('DB_HOST') ?: '127.0.0.1';

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 3,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db_name`");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS lab205_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(120) NOT NULL,
            company VARCHAR(120) NOT NULL DEFAULT '',
            phone VARCHAR(40) NOT NULL DEFAULT '',
            role ENUM('Advertiser','Administrator') NOT NULL DEFAULT 'Advertiser',
            balance BIGINT NOT NULL DEFAULT 0,
            campaigns TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    if ((int)$pdo->query("SELECT COUNT(*) FROM lab205_accounts")->fetchColumn() === 0) {
        $seed = $pdo->prepare("INSERT INTO lab205_accounts (email, password, name, company, phone, role, balance, campaigns) VALUES (?,?,?,?,?,?,?,?)");
        $rows = [
            ['admin@momad.mtn.ng', 'S3cur3Adm!n#92', 'MoMAD Admin', 'MTN MoMAD', '+234 803 000 0001', 'Administrator', 0, []],
            ['attacker@mtnbusiness.com', 'attacker@1234', 'Attacker Test', 'Red Team Security', '+234 802 100 2000', 'Advertiser', 50000, [
                ['name'=>'Recon Test Campaign','status'=>'Paused','impressions'=>5200,'spend'=>1800],
            ]],
            ['victim@mtnbusiness.com', 'victim@1234', 'Victim Test', 'Northbridge Retail', '+234 803 555 7788', 'Advertiser', 970000, [
                ['name'=>'Nationwide Flash Sale','status'=>'Active','impressions'=>1680000,'spend'=>1240000],
                ['name'=>'Loyalty SMS Blast','status'=>'Active','impressions'=>540000,'spend'=>410000],
            ]],
            ['attacker@momad.ng', 'Advertiser@2024', 'Tunde Bako', 'BrightReach Media', '+234 802 551 7788', 'Advertiser', 85000, [
                ['name'=>'Lagos Data Bundle Promo','status'=>'Active','impressions'=>142800,'spend'=>61200],
                ['name'=>'BrightReach Brand Awareness','status'=>'Paused','impressions'=>38400,'spend'=>15600],
            ]],
            ['ada.obi@zenithads.ng', 'Z3nith!Ads22', 'Ada Obi', 'Zenith Ads Ltd', '+234 806 442 1930', 'Advertiser', 1240000, [
                ['name'=>'Q4 Retail Blast (Nationwide)','status'=>'Active','impressions'=>2410000,'spend'=>1860000],
                ['name'=>'Festive Airtime Giveaway','status'=>'Active','impressions'=>988000,'spend'=>742000],
                ['name'=>'Zenith Loan Awareness','status'=>'Review','impressions'=>0,'spend'=>0],
            ]],
            ['chidi.eze@payfast.ng', 'PayF@st_640', 'Chidi Eze', 'PayFast Microfinance', '+234 701 118 4522', 'Advertiser', 520000, [
                ['name'=>'Instant Loan SMS Campaign','status'=>'Active','impressions'=>1120000,'spend'=>905000],
            ]],
        ];
        foreach ($rows as $r) {
            $seed->execute([$r[0], password_hash($r[1], PASSWORD_DEFAULT), $r[2], $r[3], $r[4], $r[5], $r[6], json_encode($r[7])]);
        }
    }
} catch (PDOException $e) {
    $pdo = null;
}

function momad_profile(PDO $pdo, array $acc): array {
    $p = [
        'id'        => '/' . $acc['id'],
        'name'      => $acc['name'],
        'email'     => $acc['email'],
        'company'   => $acc['company'],
        'role'      => $acc['role'],
        'phone'     => $acc['phone'],
        'balance'   => (int)$acc['balance'],
        'campaigns' => json_decode($acc['campaigns'] ?: '[]', true) ?: [],
    ];
    if ($acc['role'] === 'Administrator') {
        $list = [];
        $rows = $pdo->query("SELECT id, name, email, company, phone, balance, campaigns FROM lab205_accounts WHERE role <> 'Administrator' ORDER BY id");
        foreach ($rows as $a) {
            $list[] = [
                'id' => '/' . $a['id'], 'name' => $a['name'], 'email' => $a['email'],
                'company' => $a['company'], 'phone' => $a['phone'], 'balance' => (int)$a['balance'],
                'campaigns' => count(json_decode($a['campaigns'] ?: '[]', true) ?: []),
            ];
        }
        $p['accounts'] = $list;
    }
    return $p;
}

$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// -- API: /app/register --
if ($method === 'POST' && preg_match('#/app/register/?$#', $uri)) {
    header('Content-Type: application/json');
    if (!$pdo) { echo json_encode(['error'=>true,'message'=>'Service temporarily unavailable.']); exit; }

    $b        = json_decode(file_get_contents('php://input'), true);
    $email    = strtolower(trim($b['email']    ?? ''));
    $password = (string)($b['password'] ?? '');
    $name     = trim($b['name']    ?? '');
    $company  = trim($b['company'] ?? '');
    $phone    = trim($b['phone']   ?? '');

    if (!$name || !$company)                                  { echo json_encode(['error'=>true,'message'=>'Name and company are required.']); exit; }
    if (!preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email))  { echo json_encode(['error'=>true,'message'=>'Enter a valid email address.']); exit; }
    if (strlen($password) < 8)                                { echo json_encode(['error'=>true,'message'=>'Password must be at least 8 characters.']); exit; }

    $dupe = $pdo->prepare("SELECT 1 FROM lab205_accounts WHERE email = ?");
    $dupe->execute([$email]);
    if ($dupe->fetch()) { echo json_encode(['error'=>true,'message'=>'An account with this email already exists.']); exit; }

    $ins = $pdo->prepare("INSERT INTO lab205_accounts (email, password, name, company, phone, role, balance, campaigns) VALUES (?,?,?,?,?,'Advertiser',?,?)");
    $ins->execute([$email, password_hash($password, PASSWORD_DEFAULT), mb_substr($name,0,120), mb_substr($company,0,120), mb_substr($phone,0,40), 50000, '[]']);

    echo json_encode(['error'=>false,'message'=>'Account created. You can now sign in.','email'=>$email]);
    exit;
}

// -- API: /app/login --
if ($method === 'POST' && preg_match('#/app/login/?$#', $uri)) {
    header('Content-Type: application/json');
    if (!$pdo) { echo json_encode(['error'=>true,'response'=>null,'message'=>'Service temporarily unavailable.']); exit; }

    $body    = json_decode(file_get_contents('php://input'), true);
    $updates = $body['params']['updates'] ?? [];

    $email = ''; $password = ''; $gateway = false;
    foreach ((array)$updates as $u) {
        $param = $u['param'] ?? '';
        if ($param === 'user') {
            $email    = strtolower(trim($u['value']['userEmail'] ?? ''));
            $password = (string)($u['value']['userPassword'] ?? '');
        } elseif ($param === 'gateway') {
            $gateway = (($u['value'] ?? false) === true);
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM lab205_accounts WHERE email = ?");
    $stmt->execute([$email]);
    $acc = $stmt->fetch();

    if (!$acc) {
        echo json_encode(['error' => true, 'response' => null, 'message' => 'Invalid email or password.']);
        exit;
    }

    // Backend trusts the gateway flag: when true, no password check is performed.
    $ok = $gateway === true ? true : password_verify($password, $acc['password']);

    if ($ok) {
        echo json_encode(['error' => false, 'response' => momad_profile($pdo, $acc)]);
    } else {
        echo json_encode(['error' => true, 'response' => null, 'message' => 'Invalid email or password.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MoMAD — MTN Mobile Advertising</title>
<style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{
        --yellow:#ffcb05; --yellow-d:#e0b100; --blue:#004f9f; --blue-d:#003366;
        --ink:#15181f; --bg:#f3f4f6; --card:#ffffff; --line:#e5e7eb; --muted:#6b7280;
        --ok:#16a34a; --okbg:#dcfce7; --warn:#b45309; --warnbg:#fef3c7; --review:#1d4ed8; --reviewbg:#dbeafe;
        --danger:#dc2626;
    }
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:var(--ink);background:var(--bg);min-height:100vh;-webkit-font-smoothing:antialiased;}

    .mtn-logo{display:inline-flex;align-items:center;gap:9px;font-weight:800;}
    .mtn-pill{background:var(--yellow);color:var(--blue);font-weight:900;font-size:.95rem;letter-spacing:.5px;padding:3px 11px;border-radius:999px;border:1.5px solid #111;box-shadow:0 1px 0 #111;}

    /* -- Auth card -- */
    .login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:radial-gradient(1200px 500px at 50% -10%,#fff6cf 0%,var(--bg) 55%);}
    .login-card{background:var(--card);border:1px solid var(--line);border-radius:16px;width:100%;max-width:420px;overflow:hidden;box-shadow:0 18px 50px rgba(0,0,0,.08);}
    .login-top{height:5px;background:linear-gradient(90deg,var(--yellow),var(--yellow-d),var(--blue));}
    .login-body{padding:30px 30px 26px;}
    .login-body .brand-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;}
    .login-body .brand-row .sub{font-size:.68rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);}
    .login-body h1{font-size:1.4rem;font-weight:800;letter-spacing:-.3px;}
    .login-body .tag{color:var(--muted);font-size:.88rem;margin-top:4px;margin-bottom:22px;}
    .field{margin-bottom:15px;}
    .field label{display:block;font-size:.8rem;font-weight:600;margin-bottom:6px;}
    .field input{width:100%;border:1.5px solid var(--line);border-radius:9px;padding:11px 13px;font-size:.94rem;transition:border-color .15s,box-shadow .15s;}
    .field input:focus{outline:none;border-color:var(--blue);box-shadow:0 0 0 3px rgba(0,79,159,.14);}
    .frow{display:flex;gap:12px;}
    .frow .field{flex:1;}
    .btn{width:100%;border:none;border-radius:9px;padding:12px;font-size:.96rem;font-weight:800;cursor:pointer;background:var(--yellow);color:var(--ink);transition:background .15s,transform .08s;margin-top:6px;}
    .btn:hover{background:var(--yellow-d);}
    .btn:active{transform:translateY(1px);}
    .btn:disabled{opacity:.6;cursor:default;}
    .alert{display:none;border-radius:9px;padding:10px 13px;font-size:.84rem;margin-bottom:14px;}
    .alert.show{display:block;}
    .alert.err{background:#fde8e8;border:1px solid #f5c2c2;color:#9b2c2c;}
    .alert.ok{background:var(--okbg);border:1px solid #bbf7d0;color:#15803d;}
    .switch{margin-top:18px;font-size:.85rem;color:var(--muted);text-align:center;}
    .switch a{color:var(--blue);font-weight:700;cursor:pointer;text-decoration:none;}
    .demo{margin-top:18px;border-top:1px dashed var(--line);padding-top:14px;font-size:.78rem;color:var(--muted);}
    .demo b{color:var(--ink);}
    .demo .creds{font-family:ui-monospace,Menlo,Consolas,monospace;background:#f8fafc;border:1px solid var(--line);border-radius:7px;padding:8px 10px;margin-top:8px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:8px;color:var(--ink);}
    .demo .creds:hover{border-color:var(--blue);}
    .login-foot{text-align:center;font-size:.72rem;color:#9aa1ab;padding:14px;border-top:1px solid var(--line);background:#fafbfc;}
    .hidden{display:none;}

    /* -- Dashboard -- */
    #dash{display:none;}
    .topbar{background:var(--ink);color:#fff;height:58px;display:flex;align-items:center;gap:12px;padding:0 24px;}
    .topbar .prod{font-weight:800;letter-spacing:.4px;}
    .topbar .prod span{color:var(--yellow);}
    .topbar .spacer{flex:1;}
    .topbar .acct{display:flex;align-items:center;gap:9px;font-size:.85rem;}
    .topbar .av{width:32px;height:32px;border-radius:50%;background:var(--yellow);color:var(--ink);font-weight:800;display:flex;align-items:center;justify-content:center;}
    .logout{background:transparent;border:1px solid rgba(255,255,255,.25);color:#fff;border-radius:8px;padding:7px 13px;font-size:.8rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
    .logout:hover{border-color:var(--yellow);color:var(--yellow);}

    .wrap{max-width:1040px;margin:0 auto;padding:28px 24px 60px;}
    .hero{background:linear-gradient(120deg,var(--blue),var(--blue-d));color:#fff;border-radius:16px;padding:26px 28px;display:flex;align-items:center;gap:18px;flex-wrap:wrap;}
    .hero .ava{width:62px;height:62px;border-radius:14px;background:var(--yellow);color:var(--ink);font-weight:800;font-size:1.5rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .hero h2{font-size:1.35rem;font-weight:800;}
    .hero .meta{color:#cfe0f2;font-size:.9rem;margin-top:3px;}
    .role-badge{display:inline-block;margin-top:8px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.3);border-radius:999px;padding:3px 11px;font-size:.72rem;font-weight:700;letter-spacing:.03em;}
    .role-badge.admin{background:var(--yellow);color:var(--ink);border-color:var(--yellow);}

    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-top:20px;}
    .stat{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:16px 18px;}
    .stat .k{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:700;}
    .stat .v{font-size:1.5rem;font-weight:800;margin-top:5px;}
    .stat .s{font-size:.76rem;color:var(--muted);margin-top:2px;}

    .section{background:var(--card);border:1px solid var(--line);border-radius:12px;margin-top:20px;overflow:hidden;}
    .section .sh{padding:15px 18px;border-bottom:1px solid var(--line);font-weight:800;font-size:.95rem;display:flex;align-items:center;gap:8px;}
    .section .sh .tag{margin-left:auto;font-size:.68rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;padding:3px 9px;border-radius:999px;background:var(--yellow);color:var(--ink);}
    table{width:100%;border-collapse:collapse;font-size:.85rem;}
    th{text-align:left;padding:11px 18px;font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);background:#fafbfc;border-bottom:1px solid var(--line);}
    td{padding:12px 18px;border-bottom:1px solid var(--line);}
    tr:last-child td{border-bottom:none;}
    .pill{font-size:.7rem;font-weight:700;padding:3px 9px;border-radius:999px;}
    .pill.Active{background:var(--okbg);color:var(--ok);}
    .pill.Paused{background:var(--warnbg);color:var(--warn);}
    .pill.Review{background:var(--reviewbg);color:var(--review);}
    .mono{font-family:ui-monospace,Menlo,Consolas,monospace;}
    .empty{padding:26px 18px;text-align:center;color:var(--muted);font-size:.86rem;}
    .support{display:flex;align-items:center;gap:12px;padding:16px 18px;color:var(--muted);font-size:.86rem;}
    .support a{color:var(--blue);font-weight:600;text-decoration:none;}

    @media(max-width:620px){ th:nth-child(4),td:nth-child(4){display:none;} }
</style>
</head>
<body>

<!-- AUTH -->
<div class="login-wrap" id="login">
    <div class="login-card">
        <div class="login-top"></div>
        <div class="login-body">
            <div class="brand-row">
                <div class="mtn-logo"><span class="mtn-pill">MTN</span></div>
                <div class="sub">MoMAD Portal</div>
            </div>

            <div class="alert" id="authAlert"></div>

            <!-- Sign in -->
            <section id="viewLogin">
                <h1>Sign in to MoMAD</h1>
                <p class="tag">Mobile Advertising for MTN Business partners.</p>
                <form id="loginForm" onsubmit="return doLogin(event)">
                    <div class="field">
                        <label for="email">Business email</label>
                        <input type="email" id="email" placeholder="you@company.com" autocomplete="username">
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" placeholder="Your password" autocomplete="current-password">
                    </div>
                    <button class="btn" id="loginBtn" type="submit">Sign In</button>
                </form>
                <div class="switch">New to MoMAD? <a onclick="showRegister()">Create an account</a></div>
            </section>

            <!-- Sign up -->
            <section id="viewRegister" class="hidden">
                <h1>Create your account</h1>
                <p class="tag">Register as an MTN Business advertiser.</p>
                <form id="registerForm" onsubmit="return doRegister(event)">
                    <div class="frow">
                        <div class="field">
                            <label for="rName">Full name</label>
                            <input type="text" id="rName" placeholder="Jane Doe" autocomplete="name">
                        </div>
                        <div class="field">
                            <label for="rCompany">Company</label>
                            <input type="text" id="rCompany" placeholder="Acme Media" autocomplete="organization">
                        </div>
                    </div>
                    <div class="field">
                        <label for="rEmail">Business email</label>
                        <input type="email" id="rEmail" placeholder="you@company.com" autocomplete="email">
                    </div>
                    <div class="frow">
                        <div class="field">
                            <label for="rPassword">Password</label>
                            <input type="password" id="rPassword" placeholder="Min. 8 characters" autocomplete="new-password">
                        </div>
                        <div class="field">
                            <label for="rPhone">Mobile number</label>
                            <input type="tel" id="rPhone" placeholder="+234 ..." autocomplete="tel">
                        </div>
                    </div>
                    <button class="btn" id="registerBtn" type="submit">Create account</button>
                </form>
                <div class="switch">Already have an account? <a onclick="showLogin()">Sign in</a></div>
            </section>

            <div class="demo">
                <b>Test accounts</b> &mdash; tap to auto-fill the sign-in form, or create your own above.
                <div class="creds" onclick="fillCred('attacker@mtnbusiness.com','attacker@1234')"><span>attacker@mtnbusiness.com&nbsp;/&nbsp;attacker@1234</span><span>&#8681;</span></div>
                <div class="creds" onclick="fillCred('victim@mtnbusiness.com','victim@1234')"><span>victim@mtnbusiness.com&nbsp;/&nbsp;victim@1234</span><span>&#8681;</span></div>
            </div>
        </div>
        <div class="login-foot">&copy; MTN Nigeria &middot; MoMAD Mobile Advertising Platform</div>
    </div>
</div>

<!-- DASHBOARD -->
<div id="dash">
    <div class="topbar">
        <span class="mtn-pill">MTN</span>
        <span class="prod">Mo<span>MAD</span></span>
        <div class="spacer"></div>
        <div class="acct"><div class="av" id="dAv">A</div><span id="dWho">Advertiser</span></div>
        <button class="logout" onclick="logout()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>
            Sign out
        </button>
    </div>

    <div class="wrap">
        <div class="hero">
            <div class="ava" id="dAva">A</div>
            <div style="flex:1;min-width:220px;">
                <h2 id="dName">Account</h2>
                <div class="meta"><span id="dCompany"></span> &middot; <span class="mono" id="dEmail"></span></div>
                <span class="role-badge" id="dRole">Advertiser</span>
            </div>
        </div>

        <div class="stats" id="dStats"></div>

        <div class="section">
            <div class="sh"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-6"/></svg> Campaigns</div>
            <div id="dCampaigns"></div>
        </div>

        <div class="section" id="dAccountsSection" style="display:none;">
            <div class="sh"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/><path d="M16 4a3 3 0 0 1 0 6M21 20c0-2.5-1.3-4.2-3.5-5"/></svg> All Advertiser Accounts <span class="tag">Admin</span></div>
            <div id="dAccounts"></div>
        </div>

        <div class="section" id="dSupportSection" style="display:none;">
            <div class="sh"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Support</div>
            <div class="support">
                <div>Questions about your campaigns or billing? Your MoMAD account manager is
                <a href="#" onclick="return false;">admin@momad.mtn.ng</a>.</div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var login   = document.getElementById('login');
    var dash    = document.getElementById('dash');
    var alertEl = document.getElementById('authAlert');

    var naira = function(n){ return '₦' + Number(n||0).toLocaleString('en-NG'); };
    var num   = function(n){ return Number(n||0).toLocaleString('en-NG'); };
    function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }

    function showAlert(msg, ok){ alertEl.textContent = msg; alertEl.className = 'alert show ' + (ok ? 'ok' : 'err'); }
    function clearAlert(){ alertEl.className = 'alert'; }

    window.fillCred = function(email, pass){
        showLogin();
        document.getElementById('email').value = email;
        document.getElementById('password').value = pass;
    };
    window.showRegister = function(){ clearAlert(); document.getElementById('viewLogin').classList.add('hidden'); document.getElementById('viewRegister').classList.remove('hidden'); };
    window.showLogin    = function(){ clearAlert(); document.getElementById('viewRegister').classList.add('hidden'); document.getElementById('viewLogin').classList.remove('hidden'); };

    window.doRegister = function(e){
        e.preventDefault();
        clearAlert();
        var payload = {
            name:     document.getElementById('rName').value.trim(),
            company:  document.getElementById('rCompany').value.trim(),
            email:    document.getElementById('rEmail').value.trim(),
            password: document.getElementById('rPassword').value,
            phone:    document.getElementById('rPhone').value.trim()
        };
        var btn = document.getElementById('registerBtn');
        btn.disabled = true; btn.textContent = 'Creating...';
        fetch('/app/register', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) })
        .then(function(r){ return r.json(); })
        .then(function(res){
            if (res.error === false) {
                showLogin();
                document.getElementById('email').value = res.email || payload.email;
                document.getElementById('password').value = '';
                showAlert('Account created. You can now sign in.', true);
                ['rName','rCompany','rEmail','rPassword','rPhone'].forEach(function(id){ document.getElementById(id).value=''; });
            } else {
                showAlert(res.message || 'Could not create account.', false);
            }
        })
        .catch(function(){ showAlert('Network error. Please try again.', false); })
        .finally(function(){ btn.disabled=false; btn.textContent='Create account'; });
        return false;
    };

    window.doLogin = function(e){
        e.preventDefault();
        clearAlert();
        var email = document.getElementById('email').value.trim();
        var pass  = document.getElementById('password').value;
        var btn   = document.getElementById('loginBtn');
        btn.disabled = true; btn.textContent = 'Signing in...';

        // MoMAD login payload — the backend reads these params to authenticate.
        var payload = {
            params: {
                updates: [
                    { param: 'user', value: { userEmail: email, userPassword: pass }, op: 'a' },
                    { param: 'gateway', value: false, op: 'a' }
                ]
            }
        };

        fetch('/app/login', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) })
        .then(function(r){ return r.json(); })
        .then(function(res){
            if (res.error === false && res.response) {
                renderDash(res.response);
            } else {
                showAlert(res.message || 'Invalid email or password.', false);
            }
        })
        .catch(function(){ showAlert('Network error. Please try again.', false); })
        .finally(function(){ btn.disabled=false; btn.textContent='Sign In'; });
        return false;
    };

    function renderDash(a){
        var initial = (a.name || 'A').charAt(0).toUpperCase();
        document.getElementById('dAv').textContent  = initial;
        document.getElementById('dAva').textContent = initial;
        document.getElementById('dWho').textContent  = (a.name||'Account').split(' ')[0];
        document.getElementById('dName').textContent = a.name || 'Account';
        document.getElementById('dCompany').textContent = a.company || '';
        document.getElementById('dEmail').textContent   = a.email || '';

        var isAdmin = a.role === 'Administrator';
        var rb = document.getElementById('dRole');
        rb.textContent = a.role || 'Advertiser';
        rb.className = 'role-badge' + (isAdmin ? ' admin' : '');

        var camps = a.campaigns || [];
        var imp = camps.reduce(function(t,c){ return t + (c.impressions||0); }, 0);
        var spend = camps.reduce(function(t,c){ return t + (c.spend||0); }, 0);
        document.getElementById('dStats').innerHTML =
            stat('Wallet balance', naira(a.balance), 'Available credit') +
            stat('Active campaigns', camps.filter(function(c){return c.status==='Active';}).length, 'of ' + camps.length + ' total') +
            stat('Impressions', num(imp), 'Last 30 days') +
            stat('Ad spend', naira(spend), 'Last 30 days');

        document.getElementById('dCampaigns').innerHTML = camps.length ? (
            '<table><thead><tr><th>Campaign</th><th>Status</th><th>Impressions</th><th>Spend</th></tr></thead><tbody>' +
            camps.map(function(c){
                return '<tr><td><b>'+esc(c.name)+'</b></td><td><span class="pill '+esc(c.status)+'">'+esc(c.status)+'</span></td>'+
                       '<td>'+num(c.impressions)+'</td><td>'+naira(c.spend)+'</td></tr>';
            }).join('') + '</tbody></table>'
        ) : '<div class="empty">No campaigns on this account yet.</div>';

        var accSec = document.getElementById('dAccountsSection');
        if (isAdmin && a.accounts) {
            accSec.style.display = '';
            document.getElementById('dAccounts').innerHTML =
                '<table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Company</th><th>Balance</th></tr></thead><tbody>' +
                a.accounts.map(function(u){
                    return '<tr><td class="mono">'+esc(u.id)+'</td><td><b>'+esc(u.name)+'</b></td>'+
                           '<td class="mono">'+esc(u.email)+'</td><td class="mono">'+esc(u.phone)+'</td>'+
                           '<td>'+esc(u.company)+'</td><td>'+naira(u.balance)+'</td></tr>';
                }).join('') + '</tbody></table>';
        } else {
            accSec.style.display = 'none';
        }

        document.getElementById('dSupportSection').style.display = isAdmin ? 'none' : '';

        try { localStorage.setItem('momad_session', JSON.stringify(a)); } catch(e){}
        login.style.display = 'none';
        dash.style.display = 'block';
        window.scrollTo(0,0);
    }

    function stat(k,v,s){
        return '<div class="stat"><div class="k">'+k+'</div><div class="v">'+v+'</div><div class="s">'+s+'</div></div>';
    }

    window.logout = function(){
        try { localStorage.removeItem('momad_session'); } catch(e){}
        dash.style.display = 'none';
        login.style.display = 'flex';
        showLogin();
        document.getElementById('email').value = '';
        document.getElementById('password').value = '';
        window.scrollTo(0,0);
    };

    // Restore a previously signed-in session on reload (client-side session only)
    try {
        var _saved = localStorage.getItem('momad_session');
        if (_saved) renderDash(JSON.parse(_saved));
    } catch(e){}
})();
</script>
</body>
</html>
