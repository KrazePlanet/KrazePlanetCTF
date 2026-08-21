<?php
$server = $_SERVER['SERVER_NAME'] ?? 'localhost';
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';

// ─── View ───
$view = $_GET['view'] ?? 'login';

// ─── Forgot Password (reflected XSS via username) ───
$fp_submitted = ($view === 'forgot' && isset($_GET['username']));
$fp_username  = $_GET['username'] ?? '';
$fp_email     = $_GET['email'] ?? '';

// ─── Login Handler ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    header('Location: index.php?error=' . urlencode('Invalid credentials. Please try again.'));
    exit;
}

// ─── LFI via error parameter ───
$error_msg = '';
$lfi_content = '';
$lfi_path = '';

if (isset($_GET['error'])) {
    $error_msg = $_GET['error'];
    $lfi_path = $error_msg;
    
    // Attempt to read the supplied path
    $content = @file_get_contents($lfi_path);
    if ($content !== false) {
        $lfi_content = $content;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Portal — Site Administration</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{min-height:100vh;background:#0b0e14;color:#c9d1d9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;font-size:14px;display:flex;flex-direction:column;}
.top-bar{background:#111820;border-bottom:1px solid #283040;padding:0 24px;height:52px;display:flex;align-items:center;gap:12px;}
.top-bar .logo{display:flex;align-items:center;gap:8px;font-weight:700;font-size:.95rem;color:#e6edf3;}
.top-bar .logo svg{width:22px;height:22px;fill:#58a6ff;}
.top-bar .logo span{color:#58a6ff;font-weight:400;font-size:.65rem;text-transform:uppercase;letter-spacing:.8px;border:1px solid #283040;padding:1px 7px;border-radius:3px;}
.top-bar .spacer{flex:1;}
.top-bar .status{font-size:.7rem;color:#8b949e;display:flex;align-items:center;gap:5px;}
.top-bar .status .dot{width:6px;height:6px;border-radius:50%;background:#22c55e;display:inline-block;}
.main{flex:1;display:flex;align-items:center;justify-content:center;padding:24px;}
.login-card{background:#111820;border:1px solid #283040;border-radius:10px;width:100%;max-width:400px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.4);}
.login-card .top-accent{height:3px;background:linear-gradient(90deg,#1d4ed8,#3b82f6,#60a5fa);}
.login-card .card-body{padding:32px 28px 28px;}
.login-card .card-body .lock-icon{width:44px;height:44px;border-radius:10px;background:linear-gradient(135deg,#1e3a5f,#1d4ed8);display:flex;align-items:center;justify-content:center;margin-bottom:18px;}
.login-card .card-body .lock-icon svg{width:20px;height:20px;fill:#fff;}
.login-card .card-body h1{font-size:1.2rem;font-weight:700;color:#e6edf3;margin-bottom:3px;}
.login-card .card-body .sub{font-size:.78rem;color:#8b949e;margin-bottom:22px;}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:.7rem;font-weight:600;color:#8b949e;text-transform:uppercase;letter-spacing:.6px;margin-bottom:5px;}
.form-group .input-wrap{display:flex;align-items:center;border:1px solid #283040;border-radius:6px;background:#0b0e14;transition:border-color .15s;}
.form-group .input-wrap:focus-within{border-color:#3b82f6;}
.form-group .input-wrap .icon{padding:0 0 0 11px;color:#8b949e;display:flex;align-items:center;}
.form-group .input-wrap .icon svg{width:15px;height:15px;fill:#8b949e;}
.form-group .input-wrap input{flex:1;padding:10px 11px;border:none;outline:none;background:transparent;color:#e6edf3;font-size:.88rem;font-family:inherit;}
.form-group .input-wrap input::placeholder{color:#5d6879;}
.btn-submit{width:100%;padding:11px;background:#1d4ed8;color:#fff;border:none;border-radius:6px;font-size:.88rem;font-weight:600;cursor:pointer;transition:background .15s;font-family:inherit;}
.btn-submit:hover{background:#2563eb;}
.error-alert{background:#1a1111;border:1px solid #4a1c1c;border-left:3px solid #ef4444;border-radius:6px;padding:11px 13px;margin-bottom:18px;display:flex;align-items:flex-start;gap:9px;}
.error-alert svg{width:16px;height:16px;fill:#ef4444;flex-shrink:0;margin-top:1px;}
.error-alert .ea-body{flex:1;}
.error-alert .ea-body .ea-title{font-size:.65rem;font-weight:700;color:#ef4444;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;}
.error-alert .ea-body .ea-text{font-size:.78rem;color:#fca5a5;word-break:break-word;}
.lfi-output{margin-top:16px;}
.lfi-output .lfi-header{font-size:.65rem;font-weight:700;color:#8b949e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;}
.lfi-output .lfi-box{background:#0b0e14;border:1px solid #283040;border-radius:6px;padding:12px 14px;font-family:'Courier New',monospace;font-size:.75rem;line-height:1.5;color:#d4d4d4;white-space:pre-wrap;word-break:break-all;max-height:320px;overflow-y:auto;}
.footer{background:#111820;border-top:1px solid #283040;padding:14px 24px;text-align:center;font-size:.68rem;color:#5d6879;}
@media(max-width:480px){.login-card .card-body{padding:24px 20px 20px;}}
.forgot-link{display:block;text-align:right;font-size:.72rem;color:#8b949e;text-decoration:none;margin-top:-8px;margin-bottom:16px;transition:color .12s;}
.forgot-link:hover{color:#58a6ff;}
.back-link{display:inline-flex;align-items:center;gap:5px;font-size:.72rem;color:#8b949e;text-decoration:none;margin-bottom:18px;transition:color .12s;}
.back-link:hover{color:#58a6ff;}
.back-link svg{width:13px;height:13px;fill:#8b949e;}
.reset-notice{background:#0f1e10;border:1px solid #1a4d1e;border-left:3px solid #22c55e;border-radius:6px;padding:11px 13px;margin-bottom:16px;display:flex;align-items:flex-start;gap:9px;}
.reset-notice svg{width:16px;height:16px;fill:#22c55e;flex-shrink:0;margin-top:1px;}
.reset-notice .rn-body .rn-title{font-size:.65rem;font-weight:700;color:#22c55e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;}
.reset-notice .rn-body .rn-text{font-size:.78rem;color:#86efac;}
</style>
</head>
<body>

<div class="top-bar">
    <div class="logo">
        <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
        Admin Portal <span>CONTROL PANEL</span>
    </div>
    <div class="spacer"></div>
    <div class="status"><span class="dot"></span> All systems operational</div>
</div>

<?php if ($view === 'login'): ?>
<div class="main">
    <div class="login-card">
        <div class="top-accent"></div>
        <div class="card-body">
            <div class="lock-icon">
                <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/></svg>
            </div>
            <h1>Sign In</h1>
            <div class="sub">Enter your credentials to access the administration panel.</div>

            <?php if ($error_msg): ?>
            <div class="error-alert">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                <div class="ea-body">
                    <div class="ea-title">Authentication Error</div>
                    <div class="ea-text"><?=htmlspecialchars($error_msg)?></div>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrap">
                        <span class="icon"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></span>
                        <input type="text" name="username" placeholder="Username" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <span class="icon"><svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/></svg></span>
                        <input type="password" name="password" placeholder="Password">
                    </div>
                </div>
                <button type="submit" name="login" class="btn-submit">Sign In</button>
                <a href="index.php?view=forgot" class="forgot-link" style="text-align:center;margin-top:12px;display:block;">Forgot password?</a>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($view === 'forgot'): ?>
<div class="main">
    <div class="login-card">
        <div class="top-accent"></div>
        <div class="card-body">
            <a href="index.php" class="back-link"><svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg> Back to Sign In</a>
            <div class="lock-icon">
                <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            </div>
            <h1>Reset Password</h1>
            <div class="sub">Enter your username and email to receive a password reset link.</div>

            <?php if ($fp_submitted): ?>
            <div class="reset-notice">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                <div class="rn-body">
                    <div class="rn-title">Reset Link Sent</div>
                    <div class="rn-text">If an account with username <strong><?=/* reflected */$fp_username?></strong> exists, a password reset link has been sent to the registered email address.</div>
                </div>
            </div>
            <?php endif; ?>

            <form method="GET" action="index.php">
                <input type="hidden" name="view" value="forgot">
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrap">
                        <span class="icon"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></span>
                        <input type="text" name="username" placeholder="Your username" value="<?=htmlspecialchars($fp_username)?>" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-wrap">
                        <span class="icon"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></span>
                        <input type="email" name="email" placeholder="your@email.com" value="<?=htmlspecialchars($fp_email)?>">
                    </div>
                </div>
                <button type="submit" class="btn-submit">Send Reset Link</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="footer">&copy; <?=date('Y')?> Site Administration. All rights reserved.</div>

</body>
</html>
