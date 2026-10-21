<?php
/*
 * Harbor container registry (U.S. Air Force deployment).
 * Training lab (lab207). Authentication state is decided by the CLIENT from the
 * response of GET /api/v2.0/users/current, which the server always returns as
 * 401. Rewriting that response to 200 in a proxy lets the UI load the admin
 * console and its data.
 */
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Harbor login endpoint — invalid credentials are rejected.
if ($method === 'POST' && preg_match('#/c/login/?$#', $uri)) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Harbor-CSRF-Token: ' . base64_encode(random_bytes(24)));
    echo json_encode(['errors' => [['code' => 'UNAUTHORIZED', 'message' => 'UnAuthorize']]]);
    exit;
}

// Current-user check — the UI trusts this response to decide if you are signed in.
if ($method === 'GET' && preg_match('#/api/v2\.0/users/current/?$#', $uri)) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Harbor-CSRF-Token: ' . base64_encode(random_bytes(24)));
    echo json_encode(['errors' => [['code' => 'UNAUTHORIZED', 'message' => 'UnAuthorize']]]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Harbor</title>
<style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{
        --hb:#4b9ccf; --hb-d:#2f6a93; --hb-dd:#13334a; --ink:#2b2f33; --head:#1b2a33;
        --bg:#eef1f4; --card:#fff; --line:#dbe1e6; --muted:#788a98; --sidebar:#1b2a33; --topbar:#13334a;
        --ok:#318700; --okbg:#dff0d8; --warn:#b85c00; --danger:#c92100;
    }
    body{font-family:'Metropolis','Segoe UI',Roboto,-apple-system,Arial,sans-serif;color:var(--ink);background:var(--bg);min-height:100vh;-webkit-font-smoothing:antialiased;}
    .usg{background:#007a33;color:#fff;font-size:.68rem;text-align:center;padding:3px 10px;letter-spacing:.04em;font-weight:700;}
    .harbor-logo{display:inline-flex;align-items:center;gap:8px;font-weight:700;font-size:1.1rem;letter-spacing:.3px;}
    .harbor-logo svg{width:28px;height:28px;}

    /* Sign-in */
    .signin{min-height:calc(100vh - 22px);display:flex;align-items:center;justify-content:center;padding:24px;
        background:linear-gradient(135deg,#13334a 0%,#2f6a93 55%,#4b9ccf 120%);position:relative;overflow:hidden;}
    .signin .wave{position:absolute;left:0;right:0;bottom:0;height:160px;opacity:.25;}
    .signin-card{background:#fff;border-radius:4px;width:100%;max-width:420px;box-shadow:0 18px 50px rgba(0,0,0,.35);position:relative;z-index:2;overflow:hidden;}
    .signin-head{background:#13334a;color:#fff;padding:26px 30px;}
    .signin-head .sub{font-size:.72rem;color:#9fc4de;letter-spacing:.14em;text-transform:uppercase;margin-top:4px;}
    .signin-body{padding:28px 30px 30px;}
    .signin-body .org{font-size:.72rem;color:var(--muted);font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:18px;}
    .field{margin-bottom:16px;}
    .field label{display:block;font-size:.76rem;font-weight:600;color:var(--head);margin-bottom:6px;}
    .field input{width:100%;border:1px solid var(--line);border-radius:3px;padding:10px 12px;font-size:.92rem;transition:border-color .15s,box-shadow .15s;}
    .field input:focus{outline:none;border-color:var(--hb);box-shadow:0 0 0 3px rgba(75,156,207,.18);}
    .btn{width:100%;border:none;border-radius:3px;padding:11px;font-size:.9rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;cursor:pointer;background:var(--hb-d);color:#fff;transition:background .15s;}
    .btn:hover{background:var(--hb-dd);}
    .btn:disabled{opacity:.6;cursor:default;}
    .alert{display:none;background:#fde5e3;border:1px solid #f3b7b1;color:#9b241a;border-radius:3px;padding:9px 12px;font-size:.82rem;margin-bottom:14px;}
    .alert.show{display:block;}
    .signin-foot{text-align:center;font-size:.68rem;color:#9aa6b0;padding:12px;border-top:1px solid var(--line);}

    /* Console */
    #console{display:none;}
    .topbar{background:var(--topbar);color:#fff;height:56px;display:flex;align-items:center;padding:0 20px;gap:14px;}
    .topbar .spacer{flex:1;}
    .topbar .ctx{font-size:.72rem;color:#9fc4de;border:1px solid rgba(159,196,222,.4);border-radius:3px;padding:2px 9px;letter-spacing:.06em;}
    .topbar .user{font-size:.85rem;display:flex;align-items:center;gap:8px;}
    .topbar .av{width:30px;height:30px;border-radius:50%;background:var(--hb);color:#13334a;font-weight:800;display:flex;align-items:center;justify-content:center;}
    .signout{background:transparent;border:1px solid rgba(255,255,255,.25);color:#fff;border-radius:3px;padding:6px 12px;font-size:.78rem;cursor:pointer;}
    .signout:hover{border-color:var(--hb);color:var(--hb);}
    .shell{display:grid;grid-template-columns:230px 1fr;min-height:calc(100vh - 78px);}
    .side{background:var(--sidebar);color:#c7d2db;padding:14px 0;}
    .side .grp{font-size:.66rem;text-transform:uppercase;letter-spacing:.1em;color:#6b7f8d;padding:14px 20px 6px;}
    .side a{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#c7d2db;text-decoration:none;font-size:.86rem;border-left:3px solid transparent;}
    .side a.active,.side a:hover{background:#0f2635;color:#fff;border-left-color:var(--hb);}
    .main{padding:24px 28px 60px;overflow:auto;}
    .crumb{font-size:.78rem;color:var(--muted);margin-bottom:4px;}
    .ptitle{font-size:1.4rem;font-weight:700;color:var(--head);margin-bottom:18px;}
    .stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:22px;}
    .stat{background:#fff;border:1px solid var(--line);border-radius:4px;padding:16px 18px;}
    .stat .k{font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);font-weight:700;}
    .stat .v{font-size:1.5rem;font-weight:700;color:var(--head);margin-top:5px;}
    .stat .s{font-size:.74rem;color:var(--muted);margin-top:2px;}
    .panel{background:#fff;border:1px solid var(--line);border-radius:4px;margin-bottom:22px;overflow:hidden;}
    .panel .ph{padding:13px 18px;border-bottom:1px solid var(--line);font-weight:700;font-size:.92rem;color:var(--head);display:flex;align-items:center;gap:8px;}
    table{width:100%;border-collapse:collapse;font-size:.84rem;}
    th{text-align:left;padding:10px 18px;font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);background:#f6f8fa;border-bottom:1px solid var(--line);}
    td{padding:11px 18px;border-bottom:1px solid var(--line);}
    tr:last-child td{border-bottom:none;}
    .tag{font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:3px;}
    .tag.pub{background:#e3f1fb;color:#2f6a93;}
    .tag.priv{background:#f0eef7;color:#6b4fa3;}
    .tag.admin{background:#fde8e6;color:#c92100;}
    .tag.user{background:#eef1f4;color:#5a6b78;}
    .mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:.82rem;}

    @media(max-width:820px){ .shell{grid-template-columns:1fr;} .side{display:none;} }
</style>
</head>
<body>

<div class="usg">UNCLASSIFIED // FOR OFFICIAL USE ONLY — U.S. Government Information System</div>

<!-- SIGN IN -->
<div class="signin" id="signin">
    <svg class="wave" viewBox="0 0 1440 160" preserveAspectRatio="none"><path d="M0 80C240 20 480 140 720 100S1200 20 1440 70V160H0Z" fill="#9fc4de"/></svg>
    <div class="signin-card">
        <div class="signin-head">
            <div class="harbor-logo">
                <svg viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="15" fill="#4b9ccf"/><path d="M16 6v14M9 14a7 7 0 0 0 14 0" stroke="#fff" stroke-width="2.2" stroke-linecap="round" fill="none"/><circle cx="16" cy="6" r="2.4" fill="#fff"/></svg>
                Harbor
            </div>
            <div class="sub">Container Registry</div>
        </div>
        <div class="signin-body">
            <div class="org">96th Cyberspace Test Group &middot; af.mil</div>
            <div class="alert" id="loginAlert"></div>
            <form id="loginForm" onsubmit="return doSignin(event)">
                <div class="field"><label for="username">Username</label><input id="username" type="text" placeholder="Username" autocomplete="username"></div>
                <div class="field"><label for="password">Password</label><input id="password" type="password" placeholder="Password" autocomplete="current-password"></div>
                <button class="btn" id="loginBtn" type="submit">Sign In</button>
            </form>
        </div>
        <div class="signin-foot">Harbor 2.5.1 &middot; Authorized use only</div>
    </div>
</div>

<!-- CONSOLE -->
<div id="console">
    <div class="topbar">
        <div class="harbor-logo"><svg viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="15" fill="#4b9ccf"/><path d="M16 6v14M9 14a7 7 0 0 0 14 0" stroke="#fff" stroke-width="2.2" stroke-linecap="round" fill="none"/><circle cx="16" cy="6" r="2.4" fill="#fff"/></svg> Harbor</div>
        <div class="ctx">af.mil &middot; Registry</div>
        <div class="spacer"></div>
        <div class="user"><div class="av">A</div> admin</div>
        <button class="signout" onclick="signout()">Sign Out</button>
    </div>
    <div class="shell">
        <div class="side">
            <a class="active" href="#" onclick="return false;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7"/></svg> Projects</a>
            <a href="#" onclick="return false;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v6H4zM4 14h16v6H4z"/></svg> Logs</a>
            <div class="grp">Administration</div>
            <a href="#" onclick="return false;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/></svg> Users</a>
            <a href="#" onclick="return false;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="6" rx="1"/><rect x="3" y="14" width="18" height="6" rx="1"/></svg> Registries</a>
            <a href="#" onclick="return false;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v6M12 16v6M2 12h6M16 12h6"/></svg> Replications</a>
            <a href="#" onclick="return false;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 1-.1 1.2l2 1.5-2 3.4-2.3-.9a7 7 0 0 1-2 1.2l-.3 2.4H10l-.3-2.4a7 7 0 0 1-2-1.2l-2.3.9-2-3.4 2-1.5A7 7 0 0 1 5.3 12"/></svg> Configuration</a>
        </div>
        <div class="main">
            <div class="crumb">Administration</div>
            <div class="ptitle">Projects</div>

            <div class="stat-row">
                <div class="stat"><div class="k">Projects</div><div class="v">6</div><div class="s">2 public &middot; 4 private</div></div>
                <div class="stat"><div class="k">Repositories</div><div class="v">148</div><div class="s">Container images</div></div>
                <div class="stat"><div class="k">Storage used</div><div class="v">812 GB</div><div class="s">of 2 TB</div></div>
                <div class="stat"><div class="k">Users</div><div class="v">5</div><div class="s">2 admins</div></div>
            </div>

            <div class="panel">
                <div class="ph"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--hb-d)" stroke-width="2"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7"/></svg> Projects</div>
                <table>
                    <thead><tr><th>Project</th><th>Access</th><th>Repositories</th><th>Pulls</th><th>Created</th></tr></thead>
                    <tbody>
                        <tr><td class="mono">afnet/airfield-ops</td><td><span class="tag priv">Private</span></td><td>34</td><td>12,480</td><td>2021-06-14</td></tr>
                        <tr><td class="mono">usaf/logistics-api</td><td><span class="tag priv">Private</span></td><td>27</td><td>9,210</td><td>2021-08-02</td></tr>
                        <tr><td class="mono">base-images/rhel8</td><td><span class="tag pub">Public</span></td><td>18</td><td>44,900</td><td>2020-11-30</td></tr>
                        <tr><td class="mono">intel/geo-pipeline</td><td><span class="tag priv">Private</span></td><td>22</td><td>6,120</td><td>2022-01-19</td></tr>
                        <tr><td class="mono">weather/forecast-svc</td><td><span class="tag pub">Public</span></td><td>15</td><td>18,330</td><td>2022-03-08</td></tr>
                        <tr><td class="mono">maintenance/avionics</td><td><span class="tag priv">Private</span></td><td>32</td><td>7,540</td><td>2022-05-21</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="panel">
                <div class="ph"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--hb-d)" stroke-width="2"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/></svg> Users</div>
                <table>
                    <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Registry Key</th><th>Last Login</th></tr></thead>
                    <tbody>
                        <tr><td class="mono">admin</td><td class="mono">harbor-admin@us.af.mil</td><td><span class="tag admin">System Admin</span></td><td class="mono">cli_7f3a&hellip;9b21</td><td>2022-09-03 18:42</td></tr>
                        <tr><td class="mono">m.reyes</td><td class="mono">m.reyes@us.af.mil</td><td><span class="tag admin">System Admin</span></td><td class="mono">cli_1c08&hellip;ae55</td><td>2022-09-01 07:15</td></tr>
                        <tr><td class="mono">j.okoro</td><td class="mono">j.okoro@us.af.mil</td><td><span class="tag user">Developer</span></td><td class="mono">cli_44b2&hellip;0f9d</td><td>2022-08-29 14:03</td></tr>
                        <tr><td class="mono">svc-ci</td><td class="mono">ci-bot@us.af.mil</td><td><span class="tag user">Robot</span></td><td class="mono">cli_9ad1&hellip;77e2</td><td>2022-09-03 02:00</td></tr>
                        <tr><td class="mono">a.khan</td><td class="mono">a.khan@us.af.mil</td><td><span class="tag user">Guest</span></td><td class="mono">cli_20ff&hellip;31c7</td><td>2022-08-12 11:48</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var signin  = document.getElementById('signin');
    var consoleEl = document.getElementById('console');
    var alertEl = document.getElementById('loginAlert');

    function showAlert(m){ alertEl.textContent = m; alertEl.classList.add('show'); }
    function clearAlert(){ alertEl.classList.remove('show'); }

    function openConsole(){
        try { localStorage.setItem('harbor_session','1'); } catch(e){}
        signin.style.display = 'none';
        consoleEl.style.display = 'block';
        window.scrollTo(0,0);
    }

    window.doSignin = function(ev){
        ev.preventDefault();
        clearAlert();
        var btn = document.getElementById('loginBtn');
        btn.disabled = true; btn.textContent = 'Signing in...';
        // The UI decides whether you are signed in from the current-user response.
        fetch('/api/v2.0/users/current', { method:'GET', headers:{ 'Accept':'application/json' } })
        .then(function(res){
            if (res.ok) { openConsole(); }
            else { showAlert('Invalid user name or password.'); }
        })
        .catch(function(){ showAlert('Unable to reach the registry. Please try again.'); })
        .finally(function(){ btn.disabled = false; btn.textContent = 'Sign In'; });
        return false;
    };

    window.signout = function(){
        try { localStorage.removeItem('harbor_session'); } catch(e){}
        consoleEl.style.display = 'none';
        signin.style.display = 'flex';
        document.getElementById('username').value = '';
        document.getElementById('password').value = '';
        clearAlert();
        window.scrollTo(0,0);
    };

    try { if (localStorage.getItem('harbor_session') === '1') openConsole(); } catch(e){}
})();
</script>
</body>
</html>
