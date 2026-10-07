<?php
require_once __DIR__.'/lib/bootstrap.php';
require_once __DIR__.'/lib/layout.php';

$req=$_SERVER['REQUEST_URI'];
$rel='/'.ltrim(substr(parse_url($req,PHP_URL_PATH),strlen(BASE)),'/');
if($rel!=='/'&&str_ends_with($rel,'/')) $rel=rtrim($rel,'/');
$method=$_SERVER['REQUEST_METHOD'];

match(true){
    $rel==='/login'              &&$method==='POST' => action_login(),
    $rel==='/logout'             &&$method==='POST' => action_logout(),
    $rel==='/leaves/apply'       &&$method==='POST' => action_apply_leave(),
    $rel==='/hr/payroll/export'  &&$method==='POST' => action_payroll_export(),
    $rel==='/install'            &&$method==='POST' => action_install(),

    $rel==='/'                  => page_home(),
    $rel==='/payslips'          => page_payslips(),
    $rel==='/leaves'            => page_leaves(),
    $rel==='/directory'         => page_directory(),
    $rel==='/hr/dashboard'      => page_hr_dashboard(),
    $rel==='/hr/payroll/export' => page_hr_export(),
    $rel==='/manager/leaves'    => page_manager_leaves(),
    $rel==='/login'             => page_login(),
    $rel==='/install'           => page_install(),
    default                     => page_404(),
};

/* ══════════════════════════════════════════════════════════
   PAGE: DASHBOARD
══════════════════════════════════════════════════════════ */
function page_home(): void {
    $u=require_login();
    $ps=db()->prepare("SELECT * FROM payslips WHERE user_id=? ORDER BY id DESC LIMIT 1"); $ps->execute([$u['id']]); $ps=$ps->fetch();
    $leaves_taken=db()->prepare("SELECT COALESCE(SUM(days),0) FROM leaves WHERE user_id=? AND status='approved' AND YEAR(from_date)=YEAR(CURDATE())"); $leaves_taken->execute([$u['id']]); $leaves_taken=(int)$leaves_taken->fetchColumn();
    page_open('Dashboard');?>
<div class="shell">
  <?php sidebar('home');?>
  <main class="main">
    <div class="page-header">
      <div class="page-title">Good morning, <?=h(explode(' ',$u['name'])[0])?> 👋</div>
      <div class="page-subtitle"><?=date('l, d F Y')?></div>
    </div>

    <div class="stat-grid">
      <div class="stat-card"><div class="stat-num"><?=inr($u['salary_inr'])?></div><div class="stat-lbl">Monthly CTC</div></div>
      <div class="stat-card"><div class="stat-num"><?=$u['leave_balance']?></div><div class="stat-lbl">Leave Balance</div></div>
      <div class="stat-card"><div class="stat-num"><?=$leaves_taken?></div><div class="stat-lbl">Leaves Taken (YTD)</div></div>
      <div class="stat-card"><div class="stat-num"><?=$ps?inr($ps['net_pay']):'—'?></div><div class="stat-lbl">Last Net Pay</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
      <div class="card card-pad">
        <div style="font-weight:700;margin-bottom:14px;font-size:.9rem">👤 My Details</div>
        <?php foreach([['Employee ID',$u['employee_id']],['Department',$u['department']],['Designation',$u['designation']],['Role',ucfirst($u['role'])],['Joining Date',date('d M Y',strtotime($u['joining_date']))]] as[$l,$v]):?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--bg3);font-size:.84rem">
          <span style="color:var(--text3)"><?=$l?></span><span style="font-weight:600"><?=h($v)?></span>
        </div>
        <?php endforeach;?>
      </div>
      <div class="card card-pad">
        <div style="font-weight:700;margin-bottom:14px;font-size:.9rem">💡 Lab Hint</div>
        <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:14px;font-size:.8rem;color:#78350f;line-height:1.7">
          Notice the <strong>HR Admin</strong> section in the sidebar — those links are hidden for your role (Employee).<br><br>
          But hidden UI ≠ server-side protection.<br><br>
          Try sending a direct <code style="background:#fef3c7;padding:1px 4px;border-radius:3px">POST</code> request to <code style="background:#fef3c7;padding:1px 4px;border-radius:3px">/hr/payroll/export</code> in Burp Suite and see what happens.
        </div>
      </div>
    </div>
  </main>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: MY PAYSLIPS
══════════════════════════════════════════════════════════ */
function page_payslips(): void {
    $u=require_login();
    $payslips=db()->prepare("SELECT * FROM payslips WHERE user_id=? ORDER BY id DESC"); $payslips->execute([$u['id']]); $payslips=$payslips->fetchAll();
    page_open('My Payslips');?>
<div class="shell">
  <?php sidebar('payslips');?>
  <main class="main">
    <div class="page-header"><div class="page-title">💰 My Payslips</div><div class="page-subtitle">Your salary breakdown history</div></div>
    <?php foreach($payslips as $ps):?>
    <div class="card card-pad" style="margin-bottom:20px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
        <div style="font-weight:700"><?=h($ps['month'])?></div>
        <span class="badge badge-green">Paid</span>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
        <div>
          <div class="payslip-section">Earnings</div>
          <?php foreach([['Basic Salary',$ps['basic']],['HRA',$ps['hra']],['Conveyance',$ps['conveyance']],['Special Allowance',$ps['special']]] as[$l,$v]):?>
          <div class="payslip-row"><span style="color:var(--text3)"><?=$l?></span><span><?=inr($v)?></span></div>
          <?php endforeach;?>
          <div class="payslip-row" style="font-weight:700"><span>Gross Pay</span><span><?=inr($ps['basic']+$ps['hra']+$ps['conveyance']+$ps['special'])?></span></div>
        </div>
        <div>
          <div class="payslip-section">Deductions</div>
          <?php foreach([['Provident Fund',$ps['pf_deduction']],['TDS',$ps['tds_deduction']]] as[$l,$v]):?>
          <div class="payslip-row"><span style="color:var(--text3)"><?=$l?></span><span style="color:var(--red)"><?=inr($v)?></span></div>
          <?php endforeach;?>
          <div class="payslip-row" style="font-weight:700;font-size:1rem;color:var(--p)"><span>Net Pay</span><span><?=inr($ps['net_pay'])?></span></div>
        </div>
      </div>
    </div>
    <?php endforeach;?>
  </main>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: LEAVES
══════════════════════════════════════════════════════════ */
function page_leaves(): void {
    $u=require_login();
    $leaves=db()->prepare("SELECT * FROM leaves WHERE user_id=? ORDER BY from_date DESC"); $leaves->execute([$u['id']]); $leaves=$leaves->fetchAll();
    page_open('Leave Management');?>
<div class="shell">
  <?php sidebar('leaves');?>
  <main class="main">
    <div class="page-header"><div class="page-title">📋 Leave Management</div></div>
    <div class="card card-pad" style="margin-bottom:24px">
      <div style="font-weight:700;margin-bottom:14px">Apply for Leave</div>
      <form method="POST" action="<?=url('leaves/apply')?>">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px">
          <div class="form-group"><label class="form-label">Leave Type</label>
            <select name="type" class="form-control"><?php foreach(['casual'=>'Casual','sick'=>'Sick','earned'=>'Earned','unpaid'=>'Unpaid'] as$v=>$l):?><option value="<?=$v?>"><?=$l?></option><?php endforeach;?></select></div>
          <div class="form-group"><label class="form-label">From</label><input type="date" name="from_date" class="form-control" required></div>
          <div class="form-group"><label class="form-label">To</label><input type="date" name="to_date" class="form-control" required></div>
        </div>
        <div class="form-group"><label class="form-label">Reason</label><input type="text" name="reason" class="form-control" required></div>
        <button class="btn btn-primary btn-sm">Apply Leave</button>
      </form>
    </div>
    <div class="card">
      <table class="table">
        <thead><tr><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Reason</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach($leaves as $l): $bc=$l['status']==='approved'?'green':($l['status']==='rejected'?'red':'amber');?>
        <tr>
          <td><?=ucfirst(h($l['leave_type']))?></td>
          <td><?=date('d M Y',strtotime($l['from_date']))?></td>
          <td><?=date('d M Y',strtotime($l['to_date']))?></td>
          <td><?=$l['days']?> day<?=$l['days']>1?'s':''?></td>
          <td style="font-size:.8rem;color:var(--text3)"><?=h($l['reason'])?></td>
          <td><span class="badge badge-<?=$bc?>"><?=ucfirst(h($l['status']))?></span></td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: EMPLOYEE DIRECTORY
══════════════════════════════════════════════════════════ */
function page_directory(): void {
    $u=require_login();
    // Regular employees see name, dept, designation — NOT salary
    $employees=db()->query("SELECT id,name,employee_id,department,designation,role FROM users ORDER BY department,name")->fetchAll();
    page_open('Employee Directory');?>
<div class="shell">
  <?php sidebar('directory');?>
  <main class="main">
    <div class="page-header"><div class="page-title">👥 Employee Directory</div><div class="page-subtitle">All employees · <?=count($employees)?> members</div></div>
    <div class="card">
      <table class="table">
        <thead><tr><th>Employee</th><th>ID</th><th>Department</th><th>Designation</th><th>Role</th></tr></thead>
        <tbody>
        <?php foreach($employees as $e):?>
        <tr>
          <td style="font-weight:600"><?=h($e['name'])?></td>
          <td style="font-size:.8rem;color:var(--text3)"><?=h($e['employee_id'])?></td>
          <td><?=h($e['department'])?></td>
          <td style="font-size:.84rem"><?=h($e['designation'])?></td>
          <td><span class="badge badge-<?=$e['role']==='hr'?'amber':($e['role']==='manager'?'blue':'gray')?>"><?=ucfirst(h($e['role']))?></span></td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
    </div>
    <div style="margin-top:12px;font-size:.78rem;color:var(--text3);padding:10px 14px;background:var(--bg3);border-radius:8px">
      ℹ️ Salary information is confidential and only accessible to HR personnel.
    </div>
  </main>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: HR DASHBOARD (HR only — properly guarded)
══════════════════════════════════════════════════════════ */
function page_hr_dashboard(): void {
    $u=require_login();
    if(!is_hr()){http_response_code(403); page_open('Access Denied');
        echo '<div class="shell"><main class="main"><div class="card card-pad" style="text-align:center;padding:60px"><div style="font-size:2rem;margin-bottom:10px">🔒</div><div style="font-weight:700;font-size:1.1rem">Access Denied</div><p style="color:var(--text3);margin-top:8px">This page is restricted to HR personnel.</p></div></main></div>';
        page_close(); return;
    }
    $total=db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_payroll=db()->query("SELECT SUM(salary_inr) FROM users")->fetchColumn();
    $pending_leaves=db()->query("SELECT COUNT(*) FROM leaves WHERE status='pending'")->fetchColumn();
    page_open('HR Dashboard');?>
<div class="shell">
  <?php sidebar('hr');?>
  <main class="main">
    <div class="page-header"><div class="page-title">📈 HR Dashboard</div><div class="page-subtitle">Overview · HR restricted</div></div>
    <div class="stat-grid">
      <div class="stat-card"><div class="stat-num"><?=$total?></div><div class="stat-lbl">Total Employees</div></div>
      <div class="stat-card"><div class="stat-num"><?=inr((int)$total_payroll)?></div><div class="stat-lbl">Monthly Payroll</div></div>
      <div class="stat-card"><div class="stat-num"><?=$pending_leaves?></div><div class="stat-lbl">Pending Leaves</div></div>
    </div>
  </main>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: PAYROLL EXPORT (GET — shows form for HR, hint for others)
══════════════════════════════════════════════════════════ */
function page_hr_export(): void {
    $u=require_login();
    page_open('Payroll Export');?>
<div class="shell">
  <?php sidebar(is_hr()?'hr-export':'');?>
  <main class="main">
    <div class="page-header"><div class="page-title">📤 Payroll Export</div></div>
    <?php if(is_hr()):?>
    <div class="card card-pad">
      <p style="font-size:.875rem;color:var(--text2);margin-bottom:18px">Export the full salary report for all employees. This data is strictly confidential.</p>
      <form method="POST" action="<?=url('hr/payroll/export')?>">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <button class="btn btn-primary">Export Salary Report</button>
      </form>
    </div>
    <?php else:?>
    <div class="card card-pad" style="text-align:center;padding:60px">
      <div style="font-size:2.5rem;margin-bottom:10px">🔒</div>
      <div style="font-weight:700;font-size:1.1rem">HR Restricted</div>
      <p style="color:var(--text3);margin-top:8px">Only HR personnel can export payroll data.</p>
    </div>
    <?php endif;?>
  </main>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: MANAGER LEAVE APPROVALS
══════════════════════════════════════════════════════════ */
function page_manager_leaves(): void {
    $u=require_login();
    if(!is_manager()){flash('error','Access denied.');redirect('');}
    $pending=db()->query("SELECT l.*,u.name,u.employee_id,u.department FROM leaves l JOIN users u ON u.id=l.user_id WHERE l.status='pending' ORDER BY l.from_date")->fetchAll();
    page_open('Leave Approvals');?>
<div class="shell">
  <?php sidebar('mgr-leaves');?>
  <main class="main">
    <div class="page-header"><div class="page-title">✅ Pending Leave Approvals</div></div>
    <?php if($pending): foreach($pending as $l):?>
    <div class="card card-pad" style="margin-bottom:14px;display:flex;align-items:center;justify-content:space-between">
      <div>
        <div style="font-weight:700"><?=h($l['name'])?> <span style="font-size:.78rem;color:var(--text3)">(<?=h($l['employee_id'])?>)</span></div>
        <div style="font-size:.8rem;color:var(--text3);margin-top:2px"><?=ucfirst(h($l['leave_type']))?> · <?=date('d M Y',strtotime($l['from_date']))?> – <?=date('d M Y',strtotime($l['to_date']))?> · <?=$l['days']?> day<?=$l['days']>1?'s':''?></div>
        <div style="font-size:.8rem;margin-top:2px"><?=h($l['reason'])?></div>
      </div>
      <span class="badge badge-amber">Pending</span>
    </div>
    <?php endforeach; else:?>
    <div class="card card-pad" style="text-align:center;padding:40px"><div style="font-size:2rem;margin-bottom:8px">✅</div><div>No pending approvals</div></div>
    <?php endif;?>
  </main>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   AUTH PAGES
══════════════════════════════════════════════════════════ */
function page_login(): void {
    if(user()) redirect('');
    page_open('Employee Login');?>
<div style="display:flex;min-height:calc(100vh - 56px);align-items:center;justify-content:center;padding:20px">
  <div style="width:100%;max-width:400px">
    <div style="text-align:center;margin-bottom:24px">
      <div style="font-size:2.5rem;margin-bottom:8px">💼</div>
      <div style="font-size:1.3rem;font-weight:800;color:var(--p)">StaffHub Employee Portal</div>
      <div style="font-size:.875rem;color:var(--text3);margin-top:6px">Sign in with your company credentials</div>
    </div>
    <div class="card card-pad">
      <form method="POST" action="<?=url('login')?>">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <div class="form-group"><label class="form-label">Work Email</label><input type="email" name="email" class="form-control" required autofocus></div>
        <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <button class="btn btn-primary btn-lg" style="width:100%">Sign In</button>
      </form>
      <div style="margin-top:14px;padding:12px;background:var(--bg3);border-radius:7px;font-size:.75rem;color:var(--text2)">
        <strong style="color:var(--p)">Demo Accounts:</strong><br>
        student@staffhub.lab / student123 <span style="color:var(--text3)">(intern — your account)</span><br>
        hr.admin@staffhub.in / hr123 <span style="color:var(--text3)">(HR — can export payroll)</span><br>
        manager.priya@staffhub.in / priya123 <span style="color:var(--text3)">(Engineering Manager)</span>
      </div>
    </div>
  </div>
</div>
<?php page_close();
}

function page_install(): void {
    page_open('Reset Lab');?>
<div style="display:flex;min-height:calc(100vh - 56px);align-items:center;justify-content:center">
  <div class="card card-pad" style="text-align:center;max-width:400px">
    <div style="font-size:2rem;margin-bottom:10px">⚗️</div>
    <div style="font-weight:700;margin-bottom:8px">Reset Lab Database</div>
    <p style="color:var(--text3);margin:0 0 20px;font-size:.875rem">Drops all tables and re-seeds with fresh data.</p>
    <form method="POST"><button class="btn btn-danger btn-lg" style="width:100%">Reset Database</button></form>
  </div>
</div>
<?php page_close();
}

function page_404(): void {
    http_response_code(404); page_open('Not Found');
    echo '<div style="text-align:center;padding:80px"><div style="font-size:2.5rem;margin-bottom:10px">🔍</div><div style="font-size:1.2rem;font-weight:700">Page Not Found</div><a href="'.url().'" class="btn btn-primary" style="margin-top:20px;display:inline-flex">Go Home</a></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   ACTIONS
══════════════════════════════════════════════════════════ */
function action_login(): void {
    verify_csrf();
    $email=trim($_POST['email']??''); $pass=$_POST['password']??'';
    $st=db()->prepare("SELECT * FROM users WHERE email=?"); $st->execute([$email]); $u=$st->fetch();
    if(!$u||!password_verify($pass,$u['password_hash'])){flash('error','Invalid credentials.');redirect('login');}
    session_regenerate_id(true); $_SESSION['uid']=$u['id'];
    flash('success','Welcome back, '.$u['name'].'!'); redirect('');
}

function action_logout(): void {
    verify_csrf(); session_destroy(); header('Location:'.url('login')); exit;
}

function action_apply_leave(): void {
    $u=require_login(); verify_csrf();
    $type=$_POST['type']??''; $from=$_POST['from_date']??''; $to=$_POST['to_date']??''; $reason=trim($_POST['reason']??'');
    if(!$from||!$to||!$reason){flash('error','Fill all fields.');redirect('leaves');}
    $days=max(1,(int)((strtotime($to)-strtotime($from))/86400)+1);
    db()->prepare("INSERT INTO leaves(user_id,leave_type,from_date,to_date,days,reason) VALUES(?,?,?,?,?,?)")->execute([$u['id'],$type,$from,$to,$days,$reason]);
    flash('success','Leave application submitted!'); redirect('leaves');
}

/*
 * INTENTIONALLY VULNERABLE PAYROLL EXPORT ENDPOINT
 * POST /hr/payroll/export — only checks require_login(), NOT is_hr().
 *
 * The sidebar hides this endpoint from non-HR users (security by obscurity),
 * but the server-side handler only verifies the user is authenticated.
 * Any logged-in employee can directly POST to this endpoint and receive
 * the full salary table with all employees' compensation data.
 *
 * Burp Suite vector:
 *   (while logged in as student/intern)
 *   POST /subdomains/staffhub/hr/payroll/export
 *   _csrf=<valid-token>
 *   → Full salary table rendered + flag revealed
 *
 * Secure fix: add `if (!is_hr()) { http_response_code(403); die('Forbidden'); }`
 * immediately after require_login().
 */
function action_payroll_export(): void {
    $u=require_login(); verify_csrf();

    // INTENTIONAL VULNERABILITY: missing role check
    // Should be: if (!is_hr()) { http_response_code(403); die('Forbidden'); }

    // Log this access (used for flag detection)
    db()->prepare("INSERT INTO audit_log(user_id,action,detail) VALUES(?,?,?)")
        ->execute([$u['id'],'payroll_export','Accessed by '.h($u['name']).' (role: '.h($u['role']).')']);

    $employees=db()->query("SELECT name,employee_id,department,designation,role,salary_inr FROM users ORDER BY department,name")->fetchAll();
    $total_payroll=array_sum(array_column($employees,'salary_inr'));

    $was_unauthorized=$u['role']==='employee';

    page_open('Payroll Export');?>
<div class="shell">
  <?php sidebar();?>
  <main class="main">
    <?php if($was_unauthorized):?>
    <div class="flag-box">
      <h3>🚩 Missing Function-Level Access Control!</h3>
      <p style="color:#6ee7b7;font-size:.875rem;margin-bottom:12px">
        You accessed a <strong>restricted HR endpoint</strong> without the HR role.<br><br>
        The <code style="background:#052e1c;padding:2px 5px;border-radius:4px">POST /hr/payroll/export</code> endpoint checks only that you are <em>authenticated</em> (<code style="background:#052e1c;padding:2px 5px;border-radius:4px">require_login()</code>) — it does NOT verify that your role is <code style="background:#052e1c;padding:2px 5px;border-radius:4px">'hr'</code>.<br><br>
        The export link is hidden in the nav for non-HR users, but that UI restriction is purely cosmetic. The endpoint itself is wide open to any logged-in employee.
      </p>
      <div style="font-size:.78rem;color:#34d399;margin-bottom:6px">Your Flag:</div>
      <div class="flag-val"><?=LAB_FLAG?></div>
      <div style="margin-top:12px;font-size:.75rem;color:#6ee7b7">
        <strong>Fix:</strong> Add <code style="background:#052e1c;padding:2px 4px;border-radius:3px">if (!is_hr()) { http_response_code(403); die('Forbidden'); }</code> at the top of <code style="background:#052e1c;padding:2px 4px;border-radius:3px">action_payroll_export()</code>.
      </div>
    </div>
    <?php endif;?>

    <div class="page-header"><div class="page-title">📤 Salary Export — All Employees</div><div class="page-subtitle">Total monthly payroll: <?=inr($total_payroll)?></div></div>
    <div class="card">
      <table class="table">
        <thead><tr><th>Employee</th><th>ID</th><th>Department</th><th>Designation</th><th>Role</th><th style="text-align:right">Salary (₹/month)</th></tr></thead>
        <tbody>
        <?php foreach($employees as $e):?>
        <tr>
          <td style="font-weight:600"><?=h($e['name'])?></td>
          <td style="font-size:.8rem;color:var(--text3)"><?=h($e['employee_id'])?></td>
          <td><?=h($e['department'])?></td>
          <td style="font-size:.84rem"><?=h($e['designation'])?></td>
          <td><span class="badge badge-<?=$e['role']==='hr'?'amber':($e['role']==='manager'?'blue':'gray')?>"><?=ucfirst(h($e['role']))?></span></td>
          <td style="text-align:right;font-weight:700;color:var(--p)"><?=inr($e['salary_inr'])?></td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php page_close();
}

function action_install(): void {
    install_schema(db(),true); flash('success','Database reset.'); redirect('login');
}
