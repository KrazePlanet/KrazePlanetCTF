<?php
// ─── Default credentials ───
// admin@cbse.local      : admin123
// admin@kzlabs.in       : admin123
// admin@kzlabs.com      : admin123
// admin@kzlabs.store    : admin123
// hr@kzlabs.in          : hr@123
// hr@kzlabs.com         : hr@123
// hr@kzlabs.store       : hr@123
// (weak/default creds found during recon)

$logged_in = false;
$error = '';

$valid_creds = [
    'admin@cbse.local'  => 'admin123',
    'admin@kzlabs.in'   => 'admin123',
    'admin@kzlabs.com'  => 'admin123',
    'admin@kzlabs.store' => 'admin123',
    'hr@kzlabs.in'      => 'hr@123',
    'hr@kzlabs.com'     => 'hr@123',
    'hr@kzlabs.store'   => 'hr@123',
];

if (isset($_GET['logout'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (isset($valid_creds[$email]) && $valid_creds[$email] === $password) {
        $logged_in = true;
    } else {
        $error = 'Invalid email or password. Please try again.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $logged_in ? 'Student Database — CBSE PARIKSHA SANGAM' : 'CBSE PARIKSHA SANGAM — Login' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{min-height:100vh;font-family:'Inter',sans-serif;font-size:14px;background:#f0f2f5;color:#1a1a1a;}
.govt-bar{background:#1a237e;padding:5px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:4px;}
.govt-bar .left{display:flex;align-items:center;gap:10px;}
.govt-bar .left .flag{width:28px;height:auto;}
.govt-bar .left .satyamev{color:#ffd54f;font-size:.55rem;font-weight:600;text-transform:uppercase;letter-spacing:.4px;line-height:1.2;}
.govt-bar .right{display:flex;align-items:center;gap:12px;}
.govt-bar .right a{color:#90caf9;font-size:.6rem;text-decoration:none;transition:color .15s;}
.govt-bar .right a:hover{color:#fff;}
.govt-bar .right .sep{color:#5c6bc0;font-size:.6rem;}
.header{background:linear-gradient(135deg,#1a237e 0%,#283593 50%,#3949ab 100%);padding:0 16px;color:#fff;}
.header-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;gap:14px;padding:14px 0;}
.header-inner .cbse-logo{width:58px;height:58px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.45rem;color:#1a237e;font-weight:800;text-align:center;line-height:1.2;border:2px solid #ffd54f;padding:2px;}
.header-inner .cbse-logo span{font-size:.5rem;display:block;}
.header-text{flex:1;}
.header-text h1{font-size:1.05rem;font-weight:800;letter-spacing:.3px;line-height:1.25;}
.header-text .sub{font-size:.62rem;color:#bbdefb;font-weight:500;margin-top:1px;}
.header-text .sub span{color:#ffd54f;}
.header-right{text-align:right;font-size:.55rem;color:#bbdefb;line-height:1.4;}
.header-right .hi{font-size:.65rem;color:#fff;font-weight:600;}
.header-right a{color:#90caf9;text-decoration:none;}
.nav{background:#283593;border-top:1px solid #3949ab;padding:0 16px;}
.nav-inner{max-width:1100px;margin:0 auto;display:flex;gap:2px;}
.nav-inner a{padding:9px 16px;font-size:.68rem;font-weight:500;color:#c5cae9;text-decoration:none;transition:all .12s;border-bottom:2px solid transparent;}
.nav-inner a:hover,.nav-inner a.active{background:#3949ab;color:#fff;border-bottom-color:#ffd54f;}
.nav-inner a.active{background:#1a237e;color:#fff;}
.main{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 16px;min-height:calc(100vh - 200px);}
.login-wrap{width:100%;max-width:420px;}
.login-card{background:#fff;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,.08);overflow:hidden;border:1px solid #e0e0e0;}
.login-card .top-strip{height:4px;background:linear-gradient(90deg,#ff9933,#fff,#138808);}
.login-card .card-body{padding:30px 28px 26px;}
.login-card .card-body .lock-icon{width:50px;height:50px;border-radius:50%;background:#e8eaf6;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;}
.login-card .card-body .lock-icon svg{width:22px;height:22px;fill:#1a237e;}
.login-card .card-body h1{font-size:1.1rem;font-weight:700;color:#1a237e;text-align:center;margin-bottom:4px;}
.login-card .card-body .sub{font-size:.72rem;color:#666;text-align:center;margin-bottom:20px;}
.form-group{margin-bottom:14px;}
.form-group label{display:block;font-size:.65rem;font-weight:600;color:#444;margin-bottom:4px;}
.form-group input{width:100%;padding:10px 12px;border:1px solid #d0d0d0;border-radius:6px;font-size:.82rem;color:#1a1a1a;outline:none;transition:border-color .15s;font-family:inherit;}
.form-group input:focus{border-color:#1a237e;box-shadow:0 0 0 3px rgba(26,35,126,.1);}
.btn-login{width:100%;padding:11px;background:#1a237e;color:#fff;border:none;border-radius:6px;font-size:.82rem;font-weight:600;cursor:pointer;font-family:inherit;transition:background .15s;}
.btn-login:hover{background:#283593;}
.help-links{display:flex;justify-content:space-between;margin-top:14px;font-size:.65rem;}
.help-links a{color:#1a237e;text-decoration:none;}
.help-links a:hover{text-decoration:underline;}
.error-box{background:#fff5f5;border:1px solid #fecaca;border-left:3px solid #dc2626;border-radius:6px;padding:10px 12px;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px;}
.error-box svg{width:14px;height:14px;fill:#dc2626;flex-shrink:0;margin-top:1px;}
.error-box span{font-size:.75rem;color:#991b1b;}
.dash-main{padding:24px 16px;max-width:1100px;margin:0 auto;width:100%;}
.dash-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;}
.dash-header h1{font-size:1.15rem;font-weight:700;color:#1a237e;display:flex;align-items:center;gap:7px;}
.dash-header h1 svg{width:18px;height:18px;fill:#1a237e;}
.dash-header .user-badge{display:flex;align-items:center;gap:8px;background:#e8eaf6;padding:6px 12px 6px 8px;border-radius:20px;font-size:.72rem;color:#1a237e;font-weight:500;}
.dash-header .user-badge svg{width:16px;height:16px;fill:#1a237e;}
.student-table-wrap{background:#fff;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;}
.student-table-wrap .st-title{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#f8f9ff;border-bottom:1px solid #e0e0e0;}
.student-table-wrap .st-title h2{font-size:.82rem;font-weight:700;color:#1a237e;display:flex;align-items:center;gap:6px;}
.student-table-wrap .st-title h2 svg{width:15px;height:15px;fill:#1a237e;}
.student-table-wrap .st-title .count{font-size:.65rem;color:#666;background:#e8eaf6;padding:2px 10px;border-radius:12px;}
table.std{width:100%;border-collapse:collapse;font-size:.72rem;}
table.std th{text-align:left;padding:8px 12px;font-size:.6rem;font-weight:700;color:#444;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #e0e0e0;background:#fafafa;}
table.std td{padding:7px 12px;border-bottom:1px solid #f0f0f0;color:#333;}
table.std tr:last-child td{border-bottom:none;}
table.std tr:hover td{background:#f8f9ff;}
.status-badge{display:inline-block;padding:2px 8px;border-radius:12px;font-size:.6rem;font-weight:600;}
.sb-active{background:#e8f5e9;color:#2e7d32;}
.sb-inactive{background:#fce4ec;color:#c62828;}
.logout-btn{display:inline-flex;align-items:center;gap:4px;padding:6px 14px;background:#dc2626;color:#fff;border:none;border-radius:5px;font-size:.72rem;font-weight:600;cursor:pointer;text-decoration:none;transition:background .12s;}
.logout-btn:hover{background:#b91c1c;}
.logout-btn svg{width:12px;height:12px;fill:#fff;}
.footer{background:#1a237e;padding:14px 16px;text-align:center;font-size:.58rem;color:#90caf9;border-top:3px solid #ff9933;}
.footer .links{display:flex;justify-content:center;gap:14px;margin-bottom:4px;flex-wrap:wrap;}
.footer .links a{color:#90caf9;text-decoration:none;}
.footer .links a:hover{text-decoration:underline;}
.footer .visits{font-size:.55rem;color:#7986cb;margin-top:4px;}
</style>
</head>
<body>
<div class="govt-bar">
  <div class="left">
    <svg class="flag" viewBox="0 0 30 20" xmlns="http://www.w3.org/2000/svg">
      <rect width="30" height="6.67" fill="#FF9933"/>
      <rect y="6.67" width="30" height="6.67" fill="#fff"/>
      <rect y="13.33" width="30" height="6.67" fill="#138808"/>
      <circle cx="15" cy="10" r="2.5" fill="#000080"/>
    </svg>
    <span class="satyamev">सत्यमेव जयते<br>Government of India</span>
  </div>
  <div class="right">
    <a href="#">Skip to Main</a><span class="sep">|</span>
    <a href="#">Screen Reader</a><span class="sep">|</span>
    <a href="#">A+ A A-</a><span class="sep">|</span>
    <a href="#">हिन्दी</a>
  </div>
</div>

<div class="header">
  <div class="header-inner">
    <div class="cbse-logo">CBSE<br><span>सी. बी. एस. ई.</span></div>
    <div class="header-text">
      <h1>Central Board of Secondary Education</h1>
      <div class="sub">PARIKSHA SANGAM — <span>Student Database Portal</span></div>
    </div>
    <div class="header-right">
      <?php if ($logged_in): ?>
        <div class="hi">Welcome, Admin</div>
        <div>Last Login: 06 Jun 2026 08:30 AM</div>
      <?php else: ?>
        <div class="hi">Student Database Login</div>
        <div>Version 2.1.4</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="nav">
  <div class="nav-inner">
    <a href="index.php" class="active">Home</a>
    <a href="#">About</a>
    <a href="#">Results</a>
    <a href="#">Student Corner</a>
    <a href="#">Contact</a>
  </div>
</div>

<?php if (!$logged_in): ?>
  <div class="main">
    <div class="login-wrap">
      <div class="login-card">
        <div class="top-strip"></div>
        <div class="card-body">
          <div class="lock-icon">
            <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/></svg>
          </div>
          <h1>Student Database Login</h1>
          <p class="sub">Enter your credentials to access the CBSE student database</p>
          <?php if (!empty($error)): ?>
            <div class="error-box">
              <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
              <span><?= htmlspecialchars($error) ?></span>
            </div>
          <?php endif; ?>
          <form method="post">
            <div class="form-group">
              <label>Email Address</label>
              <input type="text" name="email" placeholder="admin@cbse.local" autocomplete="off">
            </div>
            <div class="form-group">
              <label>Password</label>
              <input type="password" name="password" placeholder="••••••••••" autocomplete="off">
            </div>
            <div class="form-group" style="margin-bottom:18px;">
              <label>reCAPTCHA Verification</label>
              <div style="background:#f9f9f9;border:1px solid #d0d0d0;border-radius:6px;padding:10px 12px;display:flex;align-items:center;gap:8px;">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:#1a237e;flex-shrink:0;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                <span style="font-size:.65rem;color:#666;">I'm not a robot (reCAPTCHA disabled — test mode)</span>
              </div>
            </div>
            <button type="submit" class="btn-login">Sign In</button>
          </form>
          <div class="help-links">
            <a href="#">Forgot Password?</a>
            <a href="#">Register</a>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="dash-main">
    <div class="dash-header">
      <h1><svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9h-4v4h-2v-4H9V9h4V5h2v4h4v2z"/></svg>Student Database</h1>
      <div style="display:flex;align-items:center;gap:10px;">
        <div class="user-badge">
          <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
          <?= htmlspecialchars($email) ?>
        </div>
        <a href="index.php?logout=1" class="logout-btn">
          <svg viewBox="0 0 24 24"><path d="M13 3h-2v10h2V3zm4.83 2.17l-1.42 1.42C17.99 7.86 19 9.81 19 12c0 3.87-3.13 7-7 7s-7-3.13-7-7c0-2.19 1.01-4.14 2.59-5.42L6.17 5.17C4.23 6.82 3 9.26 3 12c0 4.97 4.03 9 9 9s9-4.03 9-9c0-2.74-1.23-5.18-3.17-6.83z"/></svg>
          Logout
        </a>
      </div>
    </div>
    <div class="student-table-wrap">
      <div class="st-title">
        <h2><svg viewBox="0 0 24 24"><path d="M5 13.18v4.12c0 .53.21 1.03.58 1.41L10.59 23l7-7-7-7-5.01 5.18z"/></svg>Registered Students (2025-26 Academic Year)</h2>
        <span class="count">1,247 students</span>
      </div>
      <table class="std">
        <thead>
          <tr>
            <th>Roll No.</th><th>Student Name</th><th>Class</th><th>Section</th><th>Father's Name</th><th>DOB</th><th>Contact</th><th>Email</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>1001</td><td><strong>Aarav Sharma</strong></td><td>XII</td><td>A</td><td>Rajesh Sharma</td><td>15-04-2008</td><td>9876543210</td><td>aarav.s@cbse.local</td><td><span class="status-badge sb-active">Active</span></td></tr>
          <tr><td>1002</td><td><strong>Priya Patel</strong></td><td>XII</td><td>A</td><td>Anil Patel</td><td>22-07-2008</td><td>9876543211</td><td>priya.p@cbse.local</td><td><span class="status-badge sb-active">Active</span></td></tr>
          <tr><td>1003</td><td><strong>Rahul Verma</strong></td><td>XII</td><td>B</td><td>Sunil Verma</td><td>03-01-2009</td><td>9876543212</td><td>rahul.v@cbse.local</td><td><span class="status-badge sb-active">Active</span></td></tr>
          <tr><td>1004</td><td><strong>Ananya Gupta</strong></td><td>XII</td><td>B</td><td>Deepak Gupta</td><td>18-09-2008</td><td>9876543213</td><td>ananya.g@cbse.local</td><td><span class="status-badge sb-active">Active</span></td></tr>
          <tr><td>1005</td><td><strong>Vikram Singh</strong></td><td>XI</td><td>C</td><td>Manoj Singh</td><td>11-11-2009</td><td>9876543214</td><td>vikram.s@cbse.local</td><td><span class="status-badge sb-active">Active</span></td></tr>
          <tr><td>1006</td><td><strong>Neha Joshi</strong></td><td>XI</td><td>C</td><td>Ravi Joshi</td><td>25-02-2009</td><td>9876543215</td><td>neha.j@cbse.local</td><td><span class="status-badge sb-active">Active</span></td></tr>
          <tr><td>1007</td><td><strong>Aditya Kumar</strong></td><td>X</td><td>D</td><td>Sanjay Kumar</td><td>14-06-2010</td><td>9876543216</td><td>aditya.k@cbse.local</td><td><span class="status-badge sb-active">Active</span></td></tr>
          <tr><td>1008</td><td><strong>Shreya Nair</strong></td><td>X</td><td>D</td><td>Suresh Nair</td><td>30-08-2010</td><td>9876543217</td><td>shreya.n@cbse.local</td><td><span class="status-badge sb-active">Active</span></td></tr>
          <tr><td>1009</td><td><strong>Rohit Deshmukh</strong></td><td>IX</td><td>E</td><td>Vijay Deshmukh</td><td>05-12-2011</td><td>9876543218</td><td>rohit.d@cbse.local</td><td><span class="status-badge sb-active">Active</span></td></tr>
          <tr><td>1010</td><td><strong>Kavita Reddy</strong></td><td>IX</td><td>E</td><td>Gopal Reddy</td><td>19-03-2011</td><td>9876543219</td><td>kavita.r@cbse.local</td><td><span class="status-badge sb-active">Active</span></td></tr>
          <tr><td>1011</td><td><strong>Arjun Mehta</strong></td><td>XI</td><td>F</td><td>Prakash Mehta</td><td>07-07-2009</td><td>9876543220</td><td>arjun.m@cbse.local</td><td><span class="status-badge sb-inactive">Inactive</span></td></tr>
          <tr><td>1012</td><td><strong>Divya Iyer</strong></td><td>VIII</td><td>G</td><td>Krishnan Iyer</td><td>21-10-2012</td><td>9876543221</td><td>divya.i@cbse.local</td><td><span class="status-badge sb-active">Active</span></td></tr>
          <tr><td>1013</td><td><strong>Karan Kapoor</strong></td><td>X</td><td>H</td><td>Raj Kapoor</td><td>12-01-2010</td><td>9876543222</td><td>karan.k@cbse.local</td><td><span class="status-badge sb-active">Active</span></td></tr>
          <tr><td>1014</td><td><strong>Simran Kaur</strong></td><td>XII</td><td>I</td><td>Gurpreet Singh</td><td>09-05-2008</td><td>9876543223</td><td>simran.k@cbse.local</td><td><span class="status-badge sb-active">Active</span></td></tr>
          <tr><td>1015</td><td><strong>Varun Malhotra</strong></td><td>IX</td><td>J</td><td>Ashok Malhotra</td><td>28-11-2011</td><td>9876543224</td><td>varun.m@cbse.local</td><td><span class="status-badge sb-inactive">Inactive</span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<div class="footer">
  <div class="links">
    <a href="#">Privacy Policy</a><a href="#">Terms of Use</a><a href="#">Copyright Policy</a>
    <a href="#">Hyperlinking Policy</a><a href="#">Accessibility Statement</a><a href="#">Contact Us</a>
  </div>
  <div>© 2026 Central Board of Secondary Education. All rights reserved.</div>
  <div class="visits">Visitor Count: 4,82,39,107 | Last Updated: 06 Jun 2026</div>
</div>
</body>
</html>