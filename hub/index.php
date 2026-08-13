<?php
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Serve the HTML interface for GET requests
if ($method === 'GET') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ControlHub — Enterprise Administration</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{min-height:100vh;background:#0f1114;color:#e4e7eb;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;font-size:14px;display:flex;flex-direction:column;}
.top-bar{background:#1c1f26;border-bottom:1px solid #2d323c;padding:0 24px;height:52px;display:flex;align-items:center;gap:12px;}
.top-bar .logo{display:flex;align-items:center;gap:8px;font-weight:700;font-size:.95rem;color:#f0f4f8;}
.top-bar .logo svg{width:20px;height:20px;fill:#10b981;}
.top-bar .logo span{color:#10b981;font-weight:400;font-size:.6rem;text-transform:uppercase;letter-spacing:.8px;border:1px solid #2d323c;padding:1px 7px;border-radius:3px;}
.top-bar .spacer{flex:1;}
.top-bar .top-status{font-size:.7rem;color:#6b7280;display:flex;align-items:center;gap:5px;}
.top-bar .top-status .dot{width:6px;height:6px;border-radius:50%;background:#10b981;display:inline-block;}
.main{flex:1;display:flex;align-items:center;justify-content:center;padding:24px;}
.login-card{background:#1c1f26;border:1px solid #2d323c;border-radius:10px;width:100%;max-width:400px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.5);}
.login-card .top-accent{height:3px;background:linear-gradient(90deg,#059669,#10b981,#34d399);}
.login-card .card-body{padding:32px 28px 28px;}
.login-card .card-body .brand-icon{width:44px;height:44px;border-radius:10px;background:linear-gradient(135deg,#064e3b,#059669);display:flex;align-items:center;justify-content:center;margin-bottom:18px;}
.login-card .card-body .brand-icon svg{width:20px;height:20px;fill:#fff;}
.login-card .card-body h1{font-size:1.2rem;font-weight:700;color:#f0f4f8;margin-bottom:3px;}
.login-card .card-body .sub{font-size:.78rem;color:#6b7280;margin-bottom:22px;}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:.7rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.6px;margin-bottom:5px;}
.form-group .input-wrap{display:flex;align-items:center;border:1px solid #2d323c;border-radius:6px;background:#0f1114;transition:border-color .15s;}
.form-group .input-wrap:focus-within{border-color:#10b981;}
.form-group .input-wrap .icon{padding:0 0 0 11px;color:#6b7280;display:flex;align-items:center;}
.form-group .input-wrap .icon svg{width:15px;height:15px;fill:#6b7280;}
.form-group .input-wrap input{flex:1;padding:10px 11px;border:none;outline:none;background:transparent;color:#f0f4f8;font-size:.88rem;font-family:inherit;}
.form-group .input-wrap input::placeholder{color:#4b5563;}
.btn-submit{width:100%;padding:11px;background:#059669;color:#fff;border:none;border-radius:6px;font-size:.88rem;font-weight:600;cursor:pointer;transition:background .15s;font-family:inherit;}
.btn-submit:hover{background:#10b981;}
.btn-submit:disabled{opacity:.5;cursor:not-allowed;}
.error-alert{background:#1c0f0f;border:1px solid #4a1c1c;border-left:3px solid #ef4444;border-radius:6px;padding:11px 13px;margin-bottom:18px;display:none;align-items:flex-start;gap:9px;}
.error-alert.show{display:flex;}
.error-alert svg{width:16px;height:16px;fill:#ef4444;flex-shrink:0;margin-top:1px;}
.error-alert .ea-body{flex:1;}
.error-alert .ea-body .ea-title{font-size:.65rem;font-weight:700;color:#ef4444;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;}
.error-alert .ea-body .ea-text{font-size:.78rem;color:#fca5a5;word-break:break-word;}
.footer{background:#1c1f26;border-top:1px solid #2d323c;padding:14px 24px;text-align:center;font-size:.68rem;color:#4b5563;}
.loader{display:none;justify-content:center;margin:12px 0;}
.loader.show{display:flex;}
.loader span{width:8px;height:8px;border-radius:50%;background:#10b981;margin:0 4px;animation:blink 1.4s infinite both;}
.loader span:nth-child(2){animation-delay:.2s;}
.loader span:nth-child(3){animation-delay:.4s;}
@keyframes blink{0%,80%,100%{opacity:0;}40%{opacity:1;}}

/* CONSOLE */
.console-wrap{display:none;width:100%;max-width:1024px;margin:0 auto;}
.console-wrap.show{display:block;}
.console-header{display:flex;align-items:center;gap:10px;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #2d323c;}
.console-header .ch-icon{width:38px;height:38px;border-radius:8px;background:linear-gradient(135deg,#064e3b,#059669);display:flex;align-items:center;justify-content:center;}
.console-header .ch-icon svg{width:18px;height:18px;fill:#fff;}
.console-header .ch-info{flex:1;}
.console-header .ch-info h2{font-size:1.15rem;font-weight:700;color:#f0f4f8;}
.console-header .ch-info .ch-sub{font-size:.72rem;color:#6b7280;}
.console-header .ch-badge{background:#064e3b;color:#6ee7b7;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:4px 10px;border-radius:4px;border:1px solid #059669;}
.dash-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin-bottom:22px;}
.stat-card{background:#1c1f26;border:1px solid #2d323c;border-radius:8px;padding:16px 18px;transition:border-color .15s;}
.stat-card:hover{border-color:#3b4452;}
.stat-card .sc-title{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;margin-bottom:4px;}
.stat-card .sc-value{font-size:1.6rem;font-weight:700;color:#f0f4f8;line-height:1.2;}
.stat-card .sc-sub{font-size:.72rem;color:#6b7280;margin-top:2px;}
.stat-card .sc-sub.green{color:#6ee7b7;}
.stat-card .sc-sub.yellow{color:#fcd34d;}
.data-section{background:#1c1f26;border:1px solid #2d323c;border-radius:8px;overflow:hidden;margin-bottom:18px;}
.data-section .ds-title{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;padding:12px 16px;border-bottom:1px solid #2d323c;background:#161920;}
table.data-tbl{width:100%;border-collapse:collapse;font-size:.78rem;}
table.data-tbl th{padding:9px 16px;text-align:left;font-weight:600;color:#6b7280;background:#161920;border-bottom:1px solid #2d323c;font-size:.65rem;text-transform:uppercase;letter-spacing:.4px;}
table.data-tbl td{padding:9px 16px;border-bottom:1px solid #252a35;color:#d1d5db;}
table.data-tbl tr:last-child td{border-bottom:none;}
table.data-tbl tr:hover td{background:#21252f;}
.badge-status{display:inline-block;padding:2px 8px;border-radius:3px;font-size:.62rem;font-weight:700;text-transform:uppercase;}
.badge-active{background:#064e3b;color:#6ee7b7;}
.badge-inactive{background:#3b1c1c;color:#fca5a5;}
.badge-warn{background:#3b2f0f;color:#fcd34d;}
@media(max-width:600px){.login-card .card-body{padding:24px 20px 20px;}.console-header{flex-wrap:wrap;}}
</style>
</head>
<body>

<div class="top-bar">
    <div class="logo">
        <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        ControlHub <span>ENTERPRISE</span>
    </div>
    <div class="spacer"></div>
    <div class="top-status"><span class="dot"></span> System Online</div>
</div>

<div class="main">

<!-- Login -->
<div class="login-card" id="loginCard">
    <div class="top-accent"></div>
    <div class="card-body">
        <div class="brand-icon">
            <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
        </div>
        <h1>Administrator Login</h1>
        <div class="sub">Enter your credentials to access the enterprise management console.</div>

        <div class="error-alert" id="errorAlert">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            <div class="ea-body">
                <div class="ea-title">Authentication Failed</div>
                <div class="ea-text" id="errorText">Invalid username or password.</div>
            </div>
        </div>

        <form id="loginForm" autocomplete="off">
            <div class="form-group">
                <label>Username</label>
                <div class="input-wrap">
                    <span class="icon"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></span>
                    <input type="text" name="username" id="username" placeholder="Username" value="admin">
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <span class="icon"><svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/></svg></span>
                    <input type="password" name="password" id="password" placeholder="Password">
                </div>
            </div>
            <div class="loader" id="loader">
                <span></span><span></span><span></span>
            </div>
            <button type="submit" class="btn-submit" id="loginBtn">Sign In</button>
        </form>
    </div>
</div>

<!-- Admin Console -->
<div class="console-wrap" id="consoleWrap">
    <div class="console-header">
        <div class="ch-icon">
            <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
        </div>
        <div class="ch-info">
            <h2>Enterprise Management Console</h2>
            <div class="ch-sub">Welcome, administrator — you are signed in with full privileges.</div>
        </div>
        <div class="ch-badge">Admin Session</div>
    </div>

    <div class="dash-grid">
        <div class="stat-card">
            <div class="sc-title">Active Users</div>
            <div class="sc-value">1,284</div>
            <div class="sc-sub green">▲ 12% this month</div>
        </div>
        <div class="stat-card">
            <div class="sc-title">Servers</div>
            <div class="sc-value">47</div>
            <div class="sc-sub green">All online</div>
        </div>
        <div class="stat-card">
            <div class="sc-title">Pending Reports</div>
            <div class="sc-value">23</div>
            <div class="sc-sub yellow">Requires review</div>
        </div>
        <div class="stat-card">
            <div class="sc-title">System Uptime</div>
            <div class="sc-value">99.97%</div>
            <div class="sc-sub green">Last 30 days</div>
        </div>
    </div>

    <div class="data-section">
        <div class="ds-title">User Management</div>
        <table class="data-tbl">
            <thead>
                <tr><th>ID</th><th>Username</th><th>Role</th><th>Email</th><th>Status</th><th>Last Login</th></tr>
            </thead>
            <tbody>
                <tr><td>001</td><td>admin</td><td>Super Admin</td><td>admin@controlhub.local</td><td><span class="badge-status badge-active">Active</span></td><td>2026-06-06 08:14</td></tr>
                <tr><td>002</td><td>jdoe</td><td>Operator</td><td>jdoe@controlhub.local</td><td><span class="badge-status badge-active">Active</span></td><td>2026-06-05 22:30</td></tr>
                <tr><td>003</td><td>asmith</td><td>Analyst</td><td>asmith@controlhub.local</td><td><span class="badge-status badge-active">Active</span></td><td>2026-06-04 16:45</td></tr>
                <tr><td>004</td><td>mwilson</td><td>Viewer</td><td>mwilson@controlhub.local</td><td><span class="badge-status badge-inactive">Inactive</span></td><td>2026-05-28 09:12</td></tr>
                <tr><td>005</td><td>khart</td><td>Operator</td><td>khart@controlhub.local</td><td><span class="badge-status badge-active">Active</span></td><td>2026-06-06 07:01</td></tr>
            </tbody>
        </table>
    </div>

    <div class="data-section">
        <div class="ds-title">Server Status</div>
        <table class="data-tbl">
            <thead>
                <tr><th>Server</th><th>IP Address</th><th>Service</th><th>Status</th><th>Uptime</th><th>Load</th></tr>
            </thead>
            <tbody>
                <tr><td>WEB-01</td><td>10.0.1.10</td><td>Nginx 1.24</td><td><span class="badge-status badge-active">Online</span></td><td>87d 14h</td><td>0.42</td></tr>
                <tr><td>WEB-02</td><td>10.0.1.11</td><td>Nginx 1.24</td><td><span class="badge-status badge-active">Online</span></td><td>87d 14h</td><td>0.38</td></tr>
                <tr><td>DB-PRI</td><td>10.0.2.5</td><td>PostgreSQL 16</td><td><span class="badge-status badge-active">Online</span></td><td>120d 3h</td><td>1.12</td></tr>
                <tr><td>DB-STBY</td><td>10.0.2.6</td><td>PostgreSQL 16</td><td><span class="badge-status badge-warn">Standby</span></td><td>120d 3h</td><td>0.08</td></tr>
                <tr><td>APP-CACHE</td><td>10.0.3.2</td><td>Redis 7.2</td><td><span class="badge-status badge-active">Online</span></td><td>45d 9h</td><td>0.91</td></tr>
            </tbody>
        </table>
    </div>
</div>

</div>

<div class="footer">&copy; <?=date('Y')?> ControlHub — Enterprise Administration Platform. All rights reserved.</div>

<script>
(function(){
    const loginCard = document.getElementById('loginCard');
    const consoleWrap = document.getElementById('consoleWrap');
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const loader = document.getElementById('loader');
    const errorAlert = document.getElementById('errorAlert');
    const errorText = document.getElementById('errorText');

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        errorAlert.classList.remove('show');
        loginBtn.disabled = true;
        loader.classList.add('show');

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        fetch('index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: username, password: password })
        })
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
            loader.classList.remove('show');
            loginBtn.disabled = false;

            if (data.status === '1' && data.user_type === 'admin') {
                loginCard.style.display = 'none';
                consoleWrap.classList.add('show');
            } else {
                errorText.textContent = 'Authentication failed. Invalid username or password.';
                errorAlert.classList.add('show');
            }
        })
        .catch(function() {
            loader.classList.remove('show');
            loginBtn.disabled = false;
            errorText.textContent = 'Connection error. Please try again.';
            errorAlert.classList.add('show');
        });
    });
})();
</script>
</body>
</html>
    <?php
    exit;
}

// POST handler — always deny
$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

// Always return status 0 — failed authentication
echo json_encode([
    'status'    => '0',
    'user_type' => ''
]);
exit;
