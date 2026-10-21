<?php
/*
 * Mattermost Customer Portal.
 * Training lab (lab206). /api/v1/users/verify-email-complete accepts a token
 * that is not bound to the email being verified: any outstanding code verifies
 * any account, so a code sent for one email can verify another.
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
        CREATE TABLE IF NOT EXISTS lab206_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) UNIQUE NOT NULL,
            name VARCHAR(120) NOT NULL DEFAULT '',
            company VARCHAR(120) NOT NULL DEFAULT '',
            password VARCHAR(255) NOT NULL DEFAULT '',
            otp CHAR(6) NOT NULL,
            seeded TINYINT NOT NULL DEFAULT 0,
            verified TINYINT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    // Seed the target: an unverified account whose code the student cannot read.
    if ((int)$pdo->query("SELECT COUNT(*) FROM lab206_accounts WHERE email='victim@vusra.com'")->fetchColumn() === 0) {
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $pdo->prepare("INSERT INTO lab206_accounts (email, name, company, password, otp, seeded, verified) VALUES (?,?,?,?,?,1,0)")
            ->execute(['victim@vusra.com', 'POC victim', 'vic comp', password_hash('v!ctimP@ss'.random_int(1000,9999), PASSWORD_DEFAULT), $otp]);
    }
} catch (PDOException $e) {
    $pdo = null;
}

function mm_profile(array $a): array {
    return [
        'email'   => $a['email'],
        'name'    => $a['name'] ?: 'Portal User',
        'company' => $a['company'] ?: $a['email'],
    ];
}

$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// -- API: register (create account, "email" a verification code) --
if ($method === 'POST' && preg_match('#/api/v1/users/register/?$#', $uri)) {
    header('Content-Type: application/json');
    if (!$pdo) { echo json_encode(['ok'=>false,'message'=>'Service temporarily unavailable.']); exit; }

    $b        = json_decode(file_get_contents('php://input'), true);
    $email    = strtolower(trim($b['email']    ?? ''));
    $name     = trim($b['name']    ?? '');
    $company  = trim($b['company'] ?? '');
    $password = (string)($b['password'] ?? '');

    if (!preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email)) { echo json_encode(['ok'=>false,'message'=>'Enter a valid email address.']); exit; }
    if (strlen($password) < 8)                               { echo json_encode(['ok'=>false,'message'=>'Password must be at least 8 characters.']); exit; }
    if (!$name || !$company)                                 { echo json_encode(['ok'=>false,'message'=>'Name and company are required.']); exit; }

    $row = $pdo->prepare("SELECT seeded, verified FROM lab206_accounts WHERE email = ?");
    $row->execute([$email]);
    $existing = $row->fetch();
    if ($existing && ($existing['verified'] || $existing['seeded'])) {
        echo json_encode(['ok'=>false,'message'=>'An account with this email already exists.']); exit;
    }

    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    if ($existing) {
        $pdo->prepare("UPDATE lab206_accounts SET name=?, company=?, password=?, otp=? WHERE email=?")
            ->execute([mb_substr($name,0,120), mb_substr($company,0,120), password_hash($password, PASSWORD_DEFAULT), $otp, $email]);
    } else {
        $pdo->prepare("INSERT INTO lab206_accounts (email, name, company, password, otp, seeded, verified) VALUES (?,?,?,?,?,0,0)")
            ->execute([$email, mb_substr($name,0,120), mb_substr($company,0,120), password_hash($password, PASSWORD_DEFAULT), $otp]);
    }
    // The code is delivered to the owner's inbox (returned here as the lab has no mail server).
    echo json_encode(['ok'=>true, 'email'=>$email, 'otp'=>$otp]);
    exit;
}

// -- API: verify-email-complete --
if ($method === 'POST' && preg_match('#/api/v1/users/verify-email-complete/?$#', $uri)) {
    header('Content-Type: application/json');
    if (!$pdo) { echo json_encode(['ok'=>false,'message'=>'Service temporarily unavailable.']); exit; }

    $b     = json_decode(file_get_contents('php://input'), true);
    $email = strtolower(trim($b['email'] ?? ''));
    $token = trim((string)($b['token'] ?? ''));

    $t = $pdo->prepare("SELECT email FROM lab206_accounts WHERE email = ?");
    $t->execute([$email]);
    $target = $t->fetch();
    if (!$target) { echo json_encode(['ok'=>false,'message'=>'Verification failed.']); exit; }

    // The token is checked for validity across ALL accounts rather than against
    // the one issued for this email, so any outstanding code verifies it.
    $valid = $pdo->prepare("SELECT 1 FROM lab206_accounts WHERE otp = ? AND verified = 0 LIMIT 1");
    $valid->execute([$token]);

    if ($valid->fetch()) {
        $pdo->prepare("UPDATE lab206_accounts SET verified = 1 WHERE email = ?")->execute([$email]);
        $acc = $pdo->prepare("SELECT * FROM lab206_accounts WHERE email = ?");
        $acc->execute([$email]);
        echo json_encode(['ok'=>true, 'account'=>mm_profile($acc->fetch())]);
    } else {
        echo json_encode(['ok'=>false,'message'=>'The verification code is incorrect or has expired.']);
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mattermost Customer Portal</title>
<style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{
        --mm:#1c58d9; --mm-d:#1546ad; --mm-dd:#0b1f3a; --ink:#3f4350; --head:#1b1d22;
        --bg:#f6f7f9; --card:#ffffff; --line:#e4e6ea; --muted:#8a8f9a; --ok:#14b58a; --okbg:#e6f7f1; --danger:#d24b4e;
    }
    body{font-family:'Open Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:var(--ink);background:var(--bg);min-height:100vh;-webkit-font-smoothing:antialiased;}
    .mm-logo{display:inline-flex;align-items:center;gap:9px;font-weight:800;color:var(--mm-dd);font-size:1.15rem;letter-spacing:-.2px;}
    .mm-logo svg{width:26px;height:26px;}

    .nav{background:#fff;border-bottom:1px solid var(--line);height:64px;display:flex;align-items:center;padding:0 32px;gap:12px;}
    .nav .spacer{flex:1;}
    .nav a.link{color:var(--ink);font-size:.86rem;text-decoration:none;margin-left:22px;font-weight:600;}
    .nav a.link:hover{color:var(--mm);}

    .auth{min-height:calc(100vh - 64px);display:flex;align-items:flex-start;justify-content:center;padding:48px 20px;}
    .auth-inner{width:100%;max-width:980px;display:grid;grid-template-columns:1.1fr .9fr;gap:40px;}
    .pitch h1{font-size:2.4rem;line-height:1.1;font-weight:800;color:var(--head);letter-spacing:-1px;}
    .pitch p.sub{color:var(--muted);font-size:1.02rem;margin-top:14px;line-height:1.6;max-width:420px;}
    .pitch .feats{margin-top:26px;display:flex;flex-direction:column;gap:14px;}
    .pitch .feat{display:flex;gap:12px;align-items:flex-start;}
    .pitch .feat .fi{width:34px;height:34px;border-radius:8px;background:#eaf0fd;color:var(--mm);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .pitch .feat .ft{font-weight:700;color:var(--head);font-size:.92rem;}
    .pitch .feat .fd{font-size:.82rem;color:var(--muted);margin-top:2px;line-height:1.4;}
    .card{background:var(--card);border:1px solid var(--line);border-radius:12px;box-shadow:0 10px 36px rgba(11,31,58,.07);padding:30px 28px;}
    .card h2{font-size:1.3rem;font-weight:800;color:var(--head);}
    .card .cs{color:var(--muted);font-size:.88rem;margin-top:4px;margin-bottom:20px;}
    .field{margin-bottom:14px;}
    .field label{display:block;font-size:.8rem;font-weight:600;margin-bottom:6px;color:var(--head);}
    .field input{width:100%;border:1px solid var(--line);border-radius:7px;padding:11px 12px;font-size:.92rem;transition:border-color .15s,box-shadow .15s;}
    .field input:focus{outline:none;border-color:var(--mm);box-shadow:0 0 0 3px rgba(28,88,217,.12);}
    .frow{display:flex;gap:12px;}
    .frow .field{flex:1;}
    .btn{width:100%;border:none;border-radius:7px;padding:12px;font-size:.94rem;font-weight:700;cursor:pointer;background:var(--mm);color:#fff;transition:background .15s;margin-top:6px;}
    .btn:hover{background:var(--mm-d);}
    .btn:disabled{opacity:.6;cursor:default;}
    .switch{margin-top:16px;font-size:.84rem;color:var(--muted);text-align:center;}
    .switch a{color:var(--mm);font-weight:700;cursor:pointer;text-decoration:none;}
    .alert{display:none;border-radius:7px;padding:10px 12px;font-size:.84rem;margin-bottom:14px;}
    .alert.show{display:block;}
    .alert.err{background:#fdecec;border:1px solid #f4cccc;color:#a2383b;}
    .banner{background:var(--okbg);border:1px solid #bdeadb;color:#0f7a5a;border-radius:7px;padding:11px 13px;font-size:.86rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
    .inbox{margin-top:16px;background:#f3f6fc;border:1px dashed #b9cdf2;border-radius:8px;padding:12px 14px;font-size:.82rem;color:var(--ink);text-align:left;}
    .inbox .ih{font-weight:700;color:var(--mm-dd);margin-bottom:6px;display:flex;align-items:center;gap:6px;}
    .inbox .code{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:1.15rem;font-weight:800;letter-spacing:3px;color:var(--mm);}
    .otp{display:flex;gap:9px;justify-content:space-between;margin:10px 0 2px;}
    .otp input{width:100%;aspect-ratio:1/1.1;text-align:center;font-size:1.3rem;font-weight:700;border:1px solid var(--line);border-radius:8px;}
    .otp input:focus{outline:none;border-color:var(--mm);box-shadow:0 0 0 3px rgba(28,88,217,.12);}
    .hidden{display:none;}

    #console{display:none;}
    .cwrap{max-width:1080px;margin:0 auto;padding:0 32px;}
    .org{font-size:2.3rem;font-weight:300;color:var(--head);padding:34px 0 8px;letter-spacing:-.5px;}
    .cgrid{display:grid;grid-template-columns:220px 1fr;gap:40px;padding-bottom:60px;}
    .side a{display:block;padding:11px 0;font-size:.82rem;font-weight:700;letter-spacing:.04em;color:var(--muted);text-transform:uppercase;text-decoration:none;border-bottom:1px solid var(--line);}
    .side a.active,.side a:hover{color:var(--mm);}
    .main .topinfo{display:grid;grid-template-columns:repeat(3,1fr);gap:28px;padding:10px 0 28px;}
    .main .topinfo .k{font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:6px;}
    .main .topinfo .v{font-size:.9rem;color:var(--head);line-height:1.5;}
    .main .topinfo .v.empty{color:var(--muted);font-style:italic;}
    .subbox{border:1px solid var(--line);border-radius:12px;background:#fff;padding:48px 24px;text-align:center;margin-top:8px;}
    .subbox h3{font-size:1.5rem;font-weight:700;color:var(--head);}
    .subbox p{color:var(--muted);margin-top:8px;font-size:.92rem;}
    .subbox .btn{display:inline-block;width:auto;padding:11px 20px;margin-top:18px;}
    .subbox .ill{margin-top:26px;width:150px;opacity:.9;}
    .mono{font-family:ui-monospace,Menlo,Consolas,monospace;}

    @media(max-width:820px){
        .auth-inner{grid-template-columns:1fr;}.pitch{display:none;}
        .cgrid{grid-template-columns:1fr;gap:20px;}.main .topinfo{grid-template-columns:1fr;gap:16px;}
    }
</style>
</head>
<body>

<div class="nav">
    <div class="mm-logo">
        <svg viewBox="0 0 24 24" fill="none"><path d="M12 2C6.5 2 2 6 2 11c0 2.3 1 4.4 2.7 6-.2 1.3-.8 2.6-1.7 3.6 1.9.2 3.8-.3 5.2-1.3 1.2.4 2.5.7 3.8.7 5.5 0 10-4 10-9S17.5 2 12 2z" fill="#1c58d9"/><circle cx="12" cy="11" r="2.6" fill="#fff"/></svg>
        Mattermost
    </div>
    <div class="spacer"></div>
    <a class="link" href="#" onclick="return false;">Contact Us</a>
    <a class="link" id="navLogout" href="#" style="display:none;" onclick="logout();return false;">Log Out</a>
</div>

<!-- AUTH -->
<div class="auth" id="auth">
    <div class="auth-inner">
        <div class="pitch">
            <h1>High Trust Messaging for the Enterprise</h1>
            <p class="sub">Create your Mattermost Cloud customer account to manage subscriptions, billing, and your workspace.</p>
            <div class="feats">
                <div class="feat"><div class="fi"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-4z"/></svg></div><div><div class="ft">Enterprise-grade security</div><div class="fd">SOC2, ISO 27001 and GDPR compliant.</div></div></div>
                <div class="feat"><div class="fi"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div><div><div class="ft">Unlimited messaging</div><div class="fd">Channels, threads and search without limits.</div></div></div>
                <div class="feat"><div class="fi"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/><path d="M16 4a3 3 0 0 1 0 6"/></svg></div><div><div class="ft">Scales with your team</div><div class="fd">From 10 to 10,000+ users.</div></div></div>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="alert err" id="authAlert"></div>

                <!-- Sign up -->
                <section id="viewRegister">
                    <h2>Create an account</h2>
                    <div class="cs">Start your Mattermost Cloud workspace.</div>
                    <form id="regForm" onsubmit="return doRegister(event)">
                        <div class="frow">
                            <div class="field"><label for="rName">Full name</label><input id="rName" type="text" placeholder="Jane Doe" autocomplete="name"></div>
                            <div class="field"><label for="rCompany">Company</label><input id="rCompany" type="text" placeholder="Acme Inc" autocomplete="organization"></div>
                        </div>
                        <div class="field"><label for="rEmail">Work email</label><input id="rEmail" type="email" placeholder="you@company.com" autocomplete="email"></div>
                        <div class="field"><label for="rPassword">Password</label><input id="rPassword" type="password" placeholder="Min. 8 characters" autocomplete="new-password"></div>
                        <button class="btn" id="regBtn" type="submit">Create Account</button>
                    </form>
                </section>

                <!-- Verify -->
                <section id="viewVerify" class="hidden" style="text-align:center;">
                    <div class="banner"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg> Verification email sent!</div>
                    <h2>Verify your email</h2>
                    <div class="cs">Enter the 6-digit code we emailed to <strong id="vEmailLabel">your inbox</strong>.</div>
                    <input type="hidden" id="vEmail">
                    <div class="otp" id="otp">
                        <input maxlength="1" inputmode="numeric"><input maxlength="1" inputmode="numeric"><input maxlength="1" inputmode="numeric">
                        <input maxlength="1" inputmode="numeric"><input maxlength="1" inputmode="numeric"><input maxlength="1" inputmode="numeric">
                    </div>
                    <button class="btn" id="verBtn" onclick="doVerify()">Verify &amp; Continue</button>
                    <div class="inbox" id="devInbox" style="display:none;"></div>
                    <div class="switch"><a onclick="showRegister()">Back to sign up</a></div>
                </section>
            </div>
        </div>
    </div>
</div>

<!-- CONSOLE -->
<div id="console">
    <div class="cwrap">
        <div class="org" id="cOrg">company</div>
        <div class="cgrid">
            <div class="side">
                <a class="active" href="#" onclick="return false;">Subscriptions</a>
                <a href="#" onclick="return false;">Purchase History</a>
                <a href="#" onclick="return false;">Profile</a>
                <a href="#" onclick="return false;">Password</a>
                <a href="#" onclick="return false;">Company Information</a>
                <a href="#" onclick="return false;">Billing &amp; Payment</a>
            </div>
            <div class="main">
                <div class="topinfo">
                    <div><div class="k">Contact Information</div><div class="v" id="cContact">Name</div></div>
                    <div><div class="k">Billing Address</div><div class="v empty">No billing address added</div></div>
                    <div><div class="k">Payment Method</div><div class="v empty">No payment method added</div></div>
                </div>
                <div class="subbox">
                    <h3>No Subscriptions Yet</h3>
                    <p>There are currently no subscriptions in your account.</p>
                    <button class="btn" onclick="return false;">Purchase a Subscription</button>
                    <svg class="ill" viewBox="0 0 160 90" fill="none"><ellipse cx="80" cy="78" rx="60" ry="8" fill="#eaf0fd"/><rect x="52" y="30" width="56" height="40" rx="6" fill="#cfe0fb"/><circle cx="80" cy="24" r="12" fill="#9bbdf4"/><path d="M62 70c0-10 8-16 18-16s18 6 18 16" fill="#1c58d9"/></svg>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var auth    = document.getElementById('auth');
    var consoleEl = document.getElementById('console');
    var alertEl = document.getElementById('authAlert');
    var current = { email:'', otp:'' };

    function showAlert(m){ alertEl.textContent = m; alertEl.classList.add('show'); }
    function clearAlert(){ alertEl.classList.remove('show'); }
    function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }

    window.showRegister = function(){ clearAlert(); document.getElementById('viewVerify').classList.add('hidden'); document.getElementById('viewRegister').classList.remove('hidden'); };

    window.doRegister = function(ev){
        ev.preventDefault();
        clearAlert();
        var payload = {
            name:     document.getElementById('rName').value.trim(),
            company:  document.getElementById('rCompany').value.trim(),
            email:    document.getElementById('rEmail').value.trim(),
            password: document.getElementById('rPassword').value
        };
        var btn = document.getElementById('regBtn');
        btn.disabled = true; btn.textContent = 'Creating...';
        fetch('/api/v1/users/register', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) })
        .then(function(r){ return r.json(); })
        .then(function(res){
            if (res.ok) {
                current.email = res.email; current.otp = res.otp;
                document.getElementById('vEmail').value = res.email;
                document.getElementById('vEmailLabel').textContent = res.email;
                document.getElementById('viewRegister').classList.add('hidden');
                document.getElementById('viewVerify').classList.remove('hidden');
                var ib = document.getElementById('devInbox');
                ib.style.display = 'block';
                ib.innerHTML = '<div class="ih"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1c58d9" stroke-width="2"><rect x="2.5" y="4.5" width="19" height="15" rx="2"/><path d="M3 6l9 7 9-7"/></svg> New email in your inbox</div>'+
                    'From: Mattermost Cloud &lt;noreply@mattermost.com&gt;<br>To: ' + esc(res.email) + '<br>Subject: Verify your email<br><br>'+
                    'Your verification code is <span class="code">' + esc(res.otp) + '</span>';
                document.querySelectorAll('#otp input').forEach(function(i){ i.value=''; });
                document.querySelector('#otp input').focus();
            } else {
                showAlert(res.message || 'Could not create account.');
            }
        })
        .catch(function(){ showAlert('Network error. Please try again.'); })
        .finally(function(){ btn.disabled=false; btn.textContent='Create Account'; });
        return false;
    };

    window.doVerify = function(){
        clearAlert();
        var token = [].slice.call(document.querySelectorAll('#otp input')).map(function(i){return i.value;}).join('');
        if (token.length !== 6) { showAlert('Enter the 6-digit code.'); return; }
        var btn = document.getElementById('verBtn');
        btn.disabled = true; btn.textContent = 'Verifying...';
        fetch('/api/v1/users/verify-email-complete', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ email: document.getElementById('vEmail').value, token: token })
        })
        .then(function(r){ return r.json(); })
        .then(function(res){
            if (res.ok && res.account) { openConsole(res.account); }
            else { showAlert(res.message || 'The verification code is incorrect or has expired.'); document.querySelectorAll('#otp input').forEach(function(i){i.value='';}); document.querySelector('#otp input').focus(); }
        })
        .catch(function(){ showAlert('Network error. Please try again.'); })
        .finally(function(){ btn.disabled=false; btn.textContent='Verify & Continue'; });
    };

    function openConsole(a){
        try { localStorage.setItem('mm_session', JSON.stringify(a)); } catch(e){}
        document.getElementById('cOrg').textContent = a.company || 'company';
        document.getElementById('cContact').innerHTML = esc(a.name || 'Portal User') + '<br>' + '<span class="mono">' + esc(a.email) + '</span>';
        auth.style.display = 'none';
        consoleEl.style.display = 'block';
        document.getElementById('navLogout').style.display = 'inline';
        window.scrollTo(0,0);
    }

    window.logout = function(){
        try { localStorage.removeItem('mm_session'); } catch(e){}
        consoleEl.style.display = 'none';
        document.getElementById('navLogout').style.display = 'none';
        auth.style.display = 'flex';
        showRegister();
        ['rName','rCompany','rEmail','rPassword'].forEach(function(id){ var el=document.getElementById(id); if(el) el.value=''; });
        window.scrollTo(0,0);
    };

    var boxes = document.querySelectorAll('#otp input');
    boxes.forEach(function(b,i){
        b.addEventListener('input',function(){ b.value=b.value.replace(/\D/,''); if(b.value&&i<boxes.length-1) boxes[i+1].focus(); });
        b.addEventListener('keydown',function(e){ if(e.key==='Backspace'&&!b.value&&i>0) boxes[i-1].focus(); });
        b.addEventListener('paste',function(e){ e.preventDefault(); var d=(e.clipboardData.getData('text')||'').replace(/\D/g,'').slice(0,6); boxes.forEach(function(x,j){x.value=d[j]||'';}); if(d.length) boxes[Math.min(d.length,5)].focus(); });
    });

    // Restore a verified session on reload
    try { var s = localStorage.getItem('mm_session'); if (s) openConsole(JSON.parse(s)); } catch(e){}
})();
</script>
</body>
</html>
