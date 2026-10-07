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
    $rel==='/appointments/book'  &&$method==='POST' => action_book_appointment(),
    $rel==='/install'            &&$method==='POST' => action_install(),

    $rel==='/'                                          => page_home(),
    $rel==='/reports'                                   => page_reports(),
    $rel==='/reports/view'                              => page_report_view(),
    $rel==='/appointments'                              => page_appointments(),
    $rel==='/profile'                                   => page_profile(),
    $rel==='/doctor/patients'                           => page_doctor_patients(),
    $rel==='/login'                                     => page_login(),
    $rel==='/install'                                   => page_install(),
    default                                             => page_404(),
};

/* ══════════════════════════════════════════════════════════
   PAGE: DASHBOARD
══════════════════════════════════════════════════════════ */
function page_home(): void {
    $u=require_login();
    $my_reports=db()->prepare("SELECT COUNT(*) FROM reports WHERE patient_id=?"); $my_reports->execute([$u['id']]);
    $report_cnt=(int)$my_reports->fetchColumn();
    $appt_cnt=db()->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id=? AND status='scheduled'"); $appt_cnt->execute([$u['id']]);
    $upcoming=(int)$appt_cnt->fetchColumn();
    $recent=db()->prepare("SELECT r.*,u.name AS doctor_name FROM reports r JOIN users u ON u.id=r.ordered_by WHERE r.patient_id=? ORDER BY r.test_date DESC LIMIT 3"); $recent->execute([$u['id']]); $recent=$recent->fetchAll();
    $next_appt=db()->prepare("SELECT a.*,u.name AS doctor_name FROM appointments a JOIN users u ON u.id=a.doctor_id WHERE a.patient_id=? AND a.status='scheduled' ORDER BY a.appointment_date,a.appointment_time LIMIT 1"); $next_appt->execute([$u['id']]); $next_appt=$next_appt->fetch();

    page_open('Dashboard');?>
<div class="wrap">
  <div class="two-col">
    <?php sidenav('home');?>
    <div>
      <div class="page-title">Welcome back, <?=h(explode(' ',$u['name'])[0])?> 👋</div>
      <div class="page-subtitle">Here's a summary of your health records</div>

      <div class="stat-grid">
        <div class="stat-card"><div class="stat-icon">🧪</div><div class="stat-val"><?=$report_cnt?></div><div class="stat-lbl">Lab Reports</div></div>
        <div class="stat-card"><div class="stat-icon">📅</div><div class="stat-val"><?=$upcoming?></div><div class="stat-lbl">Upcoming Appts</div></div>
        <div class="stat-card"><div class="stat-icon">🩸</div><div class="stat-val"><?=h($u['blood_group'])?></div><div class="stat-lbl">Blood Group</div></div>
      </div>

      <?php if($next_appt):?>
      <div class="card card-pad" style="margin-bottom:20px;border-left:4px solid var(--p)">
        <div style="font-weight:700;margin-bottom:8px;font-size:.9rem">📅 Next Appointment</div>
        <div style="font-size:.875rem;color:var(--text2)">
          <strong><?=h($next_appt['reason'])?></strong><br>
          Dr. <?=h($next_appt['doctor_name'])?> · <?=date('d M Y',strtotime($next_appt['appointment_date']))?> at <?=h($next_appt['appointment_time'])?>
        </div>
      </div>
      <?php endif;?>

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
        <div style="font-weight:700;font-size:.95rem">🧪 Recent Reports</div>
        <a href="<?=url('reports')?>" class="btn btn-outline btn-sm">View All</a>
      </div>
      <?php if($recent): foreach($recent as $r):?>
      <div class="report-card">
        <div class="report-header">
          <div>
            <div class="report-type"><?=h($r['test_type'])?></div>
            <div class="report-date">Ordered by Dr. <?=h($r['doctor_name'])?></div>
          </div>
          <div style="display:flex;align-items:center;gap:10px">
            <span class="badge badge-teal"><?=date('d M Y',strtotime($r['test_date']))?></span>
            <a href="<?=url('reports/view?id='.$r['id'])?>" class="btn btn-primary btn-sm">View</a>
          </div>
        </div>
        <div class="report-body"><div class="report-summary"><?=h($r['result_summary'])?></div></div>
      </div>
      <?php endforeach; else:?>
      <div class="card card-pad" style="text-align:center;padding:40px"><div style="font-size:2rem;margin-bottom:8px">🧪</div><div>No reports yet</div></div>
      <?php endif;?>

      <!-- Lab Hint -->
      <div class="card card-pad" style="margin-top:20px;background:#fffbeb;border-color:#fcd34d">
        <div style="font-size:.7rem;font-weight:700;color:#d97706;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">💡 Lab Hint</div>
        <p style="font-size:.8rem;color:#78350f;line-height:1.7">
          When you click <strong>View</strong> on a report, notice the <code style="background:#fef3c7;padding:1px 4px;border-radius:3px">?id=</code> parameter in the URL.<br><br>
          The server fetches the report for that <code style="background:#fef3c7;padding:1px 4px;border-radius:3px">id</code> — does it check that the report belongs to <em>you</em>?<br><br>
          Try changing the <code style="background:#fef3c7;padding:1px 4px;border-radius:3px">id</code> value to another number in Burp Suite or your browser.
        </p>
      </div>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: REPORTS LIST
══════════════════════════════════════════════════════════ */
function page_reports(): void {
    $u=require_login();
    $reports=db()->prepare("SELECT r.*,u.name AS doctor_name FROM reports r JOIN users u ON u.id=r.ordered_by WHERE r.patient_id=? ORDER BY r.test_date DESC"); $reports->execute([$u['id']]); $reports=$reports->fetchAll();

    // Flag check: did this user access someone else's report?
    $exploit=db()->prepare("SELECT r.id,r.test_type,r.patient_id,p.name AS victim_name FROM reports r JOIN users p ON p.id=r.patient_id WHERE r.accessed_by=? AND r.patient_id!=?");
    $exploit->execute([$u['id'],$u['id']]); $exploited=$exploit->fetchAll();

    page_open('Lab Reports');?>
<div class="wrap">
  <div class="two-col">
    <?php sidenav('reports');?>
    <div>
      <?php if($exploited):?>
      <div class="flag-box">
        <h3>🚩 IDOR — Unauthorized Medical Record Access!</h3>
        <p style="color:#6ee7b7;font-size:.875rem;margin-bottom:10px">
          You accessed another patient's confidential lab report by modifying the <code style="background:#052e1c;padding:2px 5px;border-radius:4px">id</code> query parameter in the URL.<br><br>
          The <code style="background:#052e1c;padding:2px 5px;border-radius:4px">GET /reports/view?id=X</code> endpoint fetches the report for whatever <code style="background:#052e1c;padding:2px 5px;border-radius:4px">id</code> is provided — with no check that <code style="background:#052e1c;padding:2px 5px;border-radius:4px">reports.patient_id = $_SESSION['uid']</code>.<br><br>
          <?php foreach($exploited as $e):?>
          You read <strong><?=h($e['victim_name'])?>'s</strong> <?=h($e['test_type'])?> report (ID: <?=$e['id']?>).<br>
          <?php endforeach;?>
        </p>
        <div style="font-size:.78rem;color:#34d399;margin-bottom:6px">Your Flag:</div>
        <div class="flag-val"><?=LAB_FLAG?></div>
        <div style="margin-top:12px;font-size:.75rem;color:#6ee7b7">
          <strong>Fix:</strong> Change the query to <code style="background:#052e1c;padding:2px 4px;border-radius:3px">WHERE id=? AND patient_id=?</code> and bind both the report ID and <code style="background:#052e1c;padding:2px 4px;border-radius:3px">$_SESSION['uid']</code>.
        </div>
      </div>
      <?php endif;?>

      <div class="page-title">🧪 My Lab Reports</div>
      <div class="page-subtitle">Your complete diagnostic history</div>

      <?php if($reports): foreach($reports as $r):?>
      <div class="report-card">
        <div class="report-header">
          <div>
            <div class="report-type"><?=h($r['test_type'])?></div>
            <div class="report-date">Ordered by Dr. <?=h($r['doctor_name'])?> · <?=date('d M Y',strtotime($r['test_date']))?></div>
          </div>
          <div style="display:flex;align-items:center;gap:10px">
            <?php $badges=['ready'=>['teal','Ready'],'reviewed'=>['green','Reviewed'],'pending'=>['amber','Pending']];[$bc,$bl]=$badges[$r['status']]??['teal','Ready'];?>
            <span class="badge badge-<?=$bc?>"><?=$bl?></span>
            <a href="<?=url('reports/view?id='.$r['id'])?>" class="btn btn-primary btn-sm">View Report</a>
          </div>
        </div>
        <div class="report-body"><div class="report-summary"><?=h($r['result_summary'])?></div></div>
      </div>
      <?php endforeach; else:?>
      <div class="card card-pad" style="text-align:center;padding:60px"><div style="font-size:2.5rem;margin-bottom:10px">🧪</div><div style="font-weight:600">No lab reports yet</div><p style="color:var(--text3);margin-top:8px;font-size:.875rem">Your test results will appear here once your doctor orders them.</p></div>
      <?php endif;?>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: REPORT VIEW (VULNERABLE)
══════════════════════════════════════════════════════════ */
function page_report_view(): void {
    $u=require_login();
    $id=(int)($_GET['id']??0);
    if(!$id){flash('error','Invalid report ID.');redirect('reports');}

    /*
     * INTENTIONAL VULNERABILITY: IDOR via GET query parameter
     * The query only filters by report ID — it does NOT check patient_id = session uid.
     * Any authenticated patient can read any other patient's lab report by guessing IDs.
     *
     * Secure fix: WHERE id=? AND patient_id=?
     * binding [$id, $u['id']]
     */
    $st=db()->prepare("SELECT r.*,u.name AS doctor_name,p.name AS patient_name FROM reports r JOIN users u ON u.id=r.ordered_by JOIN users p ON p.id=r.patient_id WHERE r.id=?");
    $st->execute([$id]); $r=$st->fetch();
    if(!$r){flash('error','Report not found.');redirect('reports');}

    // Track unauthorized access for flag reveal
    if((int)$r['patient_id']!==$u['id']){
        // Attacker is reading someone else's report — record it
        db()->prepare("UPDATE reports SET accessed_by=? WHERE id=? AND accessed_by IS NULL")->execute([$u['id'],$id]);
    }

    $is_own=(int)$r['patient_id']===$u['id'];

    page_open(h($r['test_type']));?>
<div class="wrap" style="max-width:800px;margin-left:auto;margin-right:auto">
  <div style="margin-bottom:20px">
    <a href="<?=url('reports')?>" style="font-size:.84rem;color:var(--p)">← Back to Reports</a>
  </div>

  <?php if(!$is_own):?>
  <div style="background:#fff7ed;border:2px solid #fb923c;border-radius:10px;padding:16px 20px;margin-bottom:20px;font-size:.84rem;color:#7c2d12">
    ⚠️ <strong>IDOR Alert:</strong> You are viewing <strong><?=h($r['patient_name'])?>'s</strong> confidential lab report. You were authenticated as a different patient but the server did not verify ownership.
    <a href="<?=url('reports')?>" style="color:var(--p);font-weight:600;margin-left:8px">→ Check flag on Reports page</a>
  </div>
  <?php endif;?>

  <div class="card" style="overflow:hidden">
    <div style="background:linear-gradient(135deg,var(--p),#14b8a6);padding:24px 28px;color:#fff">
      <div style="font-size:1.2rem;font-weight:800"><?=h($r['test_type'])?></div>
      <div style="font-size:.85rem;opacity:.85;margin-top:4px">Patient: <?=h($r['patient_name'])?> · Report ID: #<?=$r['id']?></div>
      <div style="font-size:.8rem;opacity:.75;margin-top:2px">Ordered by Dr. <?=h($r['doctor_name'])?> · <?=date('d M Y',strtotime($r['test_date']))?></div>
    </div>
    <div class="card-pad">
      <div style="font-weight:700;margin-bottom:8px;font-size:.875rem">Summary</div>
      <div style="font-size:.875rem;color:var(--text2);line-height:1.65;margin-bottom:20px;padding:12px 16px;background:var(--bg3);border-radius:8px"><?=h($r['result_summary'])?></div>

      <div style="font-weight:700;margin-bottom:8px;font-size:.875rem">Detailed Results</div>
      <div class="result-pre"><?=h($r['result_detail'])?></div>

      <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;font-size:.78rem;color:var(--text3)">
        <span>Status: <strong><?=ucfirst(h($r['status']))?></strong></span>
        <span>Test Date: <?=date('d M Y',strtotime($r['test_date']))?></span>
      </div>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: APPOINTMENTS
══════════════════════════════════════════════════════════ */
function page_appointments(): void {
    $u=require_login();
    $appts=db()->prepare("SELECT a.*,d.name AS doctor_name FROM appointments a JOIN users d ON d.id=a.doctor_id WHERE a.patient_id=? ORDER BY a.appointment_date,a.appointment_time"); $appts->execute([$u['id']]); $appts=$appts->fetchAll();
    page_open('Appointments');?>
<div class="wrap">
  <div class="two-col">
    <?php sidenav('appointments');?>
    <div>
      <div class="page-title">📅 My Appointments</div>
      <div class="page-subtitle">Upcoming and past consultations</div>

      <div class="card card-pad" style="margin-bottom:20px">
        <div style="font-weight:700;margin-bottom:14px">Book New Appointment</div>
        <form method="POST" action="<?=url('appointments/book')?>">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <div class="form-group"><label class="form-label">Reason for Visit</label><input type="text" name="reason" class="form-control" required placeholder="e.g., Annual health check-up"></div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group"><label class="form-label">Date</label><input type="date" name="date" class="form-control" required min="<?=date('Y-m-d')?>"></div>
            <div class="form-group"><label class="form-label">Time Slot</label>
              <select name="time" class="form-control">
                <?php foreach(['09:00 AM','09:30 AM','10:00 AM','10:30 AM','11:00 AM','11:30 AM','02:00 PM','02:30 PM','03:00 PM','04:00 PM'] as $t):?><option><?=$t?></option><?php endforeach;?>
              </select>
            </div>
          </div>
          <button class="btn btn-primary">Book Appointment</button>
        </form>
      </div>

      <?php if($appts): foreach($appts as $a): $bc=$a['status']==='scheduled'?'teal':($a['status']==='completed'?'green':'red');?>
      <div class="card card-pad" style="margin-bottom:12px;display:flex;align-items:center;justify-content:space-between">
        <div>
          <div style="font-weight:700;font-size:.9rem"><?=h($a['reason'])?></div>
          <div style="font-size:.78rem;color:var(--text3);margin-top:4px">Dr. <?=h($a['doctor_name'])?> · <?=date('d M Y',strtotime($a['appointment_date']))?> at <?=h($a['appointment_time'])?></div>
        </div>
        <span class="badge badge-<?=$bc?>"><?=ucfirst(h($a['status']))?></span>
      </div>
      <?php endforeach; else:?>
      <div class="card card-pad" style="text-align:center;padding:40px"><div style="font-size:2rem;margin-bottom:8px">📅</div><div>No appointments</div></div>
      <?php endif;?>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: PROFILE
══════════════════════════════════════════════════════════ */
function page_profile(): void {
    $u=require_login();
    page_open('My Profile');?>
<div class="wrap">
  <div class="two-col">
    <?php sidenav('profile');?>
    <div>
      <div class="page-title">👤 My Profile</div>
      <div class="page-subtitle">Your personal health information</div>
      <div class="card card-pad">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;font-size:.875rem">
          <?php foreach([['Full Name',$u['name']],['Username','@'.h($u['username'])],['Email',$u['email']],['Phone',$u['phone']],['Blood Group',$u['blood_group']],['Date of Birth',$u['dob']?date('d M Y',strtotime($u['dob'])):'—'],['Allergies',$u['allergies']],['Patient ID','P'.str_pad($u['id'],4,'0',STR_PAD_LEFT)]] as[$l,$v]):?>
          <div style="padding-bottom:14px;border-bottom:1px solid var(--border)">
            <div style="font-size:.7rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px"><?=$l?></div>
            <div style="font-weight:600"><?=h((string)$v)?></div>
          </div>
          <?php endforeach;?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: DOCTOR — ALL PATIENTS
══════════════════════════════════════════════════════════ */
function page_doctor_patients(): void {
    $u=require_login();
    if(!is_doctor()){flash('error','Access denied.');redirect('');}
    $patients=db()->query("SELECT * FROM users WHERE role='patient' ORDER BY name")->fetchAll();
    page_open('All Patients');?>
<div class="wrap">
  <div class="two-col">
    <?php sidenav('patients');?>
    <div>
      <div class="page-title">👨‍⚕️ All Patients</div>
      <div class="page-subtitle">Patient directory</div>
      <div class="card">
        <table class="table">
          <thead><tr><th>Patient</th><th>Blood Group</th><th>Allergies</th><th>Phone</th><th></th></tr></thead>
          <tbody>
          <?php foreach($patients as $p):?>
          <tr>
            <td><div style="font-weight:600"><?=h($p['name'])?></div><div style="font-size:.75rem;color:var(--text3)">P<?=str_pad($p['id'],4,'0',STR_PAD_LEFT)?></div></td>
            <td><span class="badge badge-red"><?=h($p['blood_group'])?></span></td>
            <td style="font-size:.8rem;color:var(--text3)"><?=h($p['allergies'])?></td>
            <td style="font-size:.8rem"><?=h($p['phone'])?></td>
            <td><a href="<?=url('reports/view?id=')?>" class="btn btn-outline btn-sm">Reports</a></td>
          </tr>
          <?php endforeach;?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   AUTH PAGES
══════════════════════════════════════════════════════════ */
function page_login(): void {
    if(user()) redirect('');
    page_open('Patient Login');?>
<div class="wrap" style="max-width:400px;margin-left:auto;margin-right:auto;padding-top:32px">
  <div style="text-align:center;margin-bottom:24px">
    <div style="font-size:2.5rem;margin-bottom:8px">🏥</div>
    <div style="font-size:1.4rem;font-weight:800;color:var(--p)">MediCore Patient Portal</div>
    <div style="font-size:.875rem;color:var(--text3);margin-top:6px">Sign in to access your health records</div>
  </div>
  <div class="card card-pad">
    <form method="POST" action="<?=url('login')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required autofocus></div>
      <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-primary btn-lg" style="width:100%">Sign In</button>
    </form>
    <div style="margin-top:16px;padding:12px;background:var(--bg3);border-radius:8px;font-size:.75rem;color:var(--text2)">
      <strong style="color:var(--p)">Demo Accounts:</strong><br>
      student@medicore.lab / student123 <span style="color:var(--text3)">(your account, ID: 4)</span><br>
      riya@example.com / riya123 <span style="color:var(--text3)">(target, report IDs 1–2)</span><br>
      arjun@example.com / arjun123 <span style="color:var(--text3)">(target, report IDs 3–4)</span><br>
      dr.sharma@medicore.in / doctor123 <span style="color:var(--text3)">(doctor/admin)</span>
    </div>
  </div>
</div>
<?php page_close();
}

function page_install(): void {
    page_open('Reset Lab');?>
<div class="wrap" style="max-width:420px;margin-left:auto;margin-right:auto">
  <div class="card card-pad" style="text-align:center">
    <div style="font-size:2rem;margin-bottom:10px">⚗️</div>
    <div style="font-size:1.1rem;font-weight:700;margin-bottom:8px">Reset Lab Database</div>
    <p style="color:var(--text3);margin:0 0 20px;font-size:.875rem">Drops all tables and re-seeds with fresh data.</p>
    <form method="POST"><button class="btn btn-danger btn-lg" style="width:100%">Reset Database</button></form>
    <a href="<?=url('login')?>" class="btn btn-light" style="width:100%;margin-top:10px">Cancel</a>
  </div>
</div>
<?php page_close();
}

function page_404(): void {
    http_response_code(404); page_open('Not Found');
    echo '<div class="wrap" style="text-align:center;padding:80px"><div style="font-size:2.5rem;margin-bottom:10px">🔍</div><div style="font-size:1.2rem;font-weight:700">Page Not Found</div><a href="'.url().'" class="btn btn-primary" style="margin-top:20px;display:inline-flex">Go Home</a></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   ACTIONS
══════════════════════════════════════════════════════════ */
function action_login(): void {
    verify_csrf();
    $email=trim($_POST['email']??''); $pass=$_POST['password']??'';
    $st=db()->prepare("SELECT * FROM users WHERE email=?"); $st->execute([$email]); $u=$st->fetch();
    if(!$u||!password_verify($pass,$u['password_hash'])){flash('error','Invalid email or password.');redirect('login');}
    session_regenerate_id(true); $_SESSION['uid']=$u['id'];
    flash('success','Welcome back, '.$u['name'].'!'); redirect('');
}

function action_logout(): void {
    verify_csrf(); session_destroy(); header('Location:'.url('login')); exit;
}

function action_book_appointment(): void {
    $u=require_login(); verify_csrf();
    $reason=trim($_POST['reason']??''); $date=$_POST['date']??''; $time=$_POST['time']??'';
    if(!$reason||!$date||!$time){flash('error','Please fill all fields.');redirect('appointments');}
    $doc=db()->query("SELECT id FROM users WHERE role='doctor' LIMIT 1")->fetchColumn();
    db()->prepare("INSERT INTO appointments(patient_id,doctor_id,appointment_date,appointment_time,reason) VALUES(?,?,?,?,?)")->execute([$u['id'],$doc,$date,$time,$reason]);
    flash('success','Appointment booked successfully!'); redirect('appointments');
}

function action_install(): void {
    install_schema(db(),true); flash('success','Database reset successfully.'); redirect('login');
}
