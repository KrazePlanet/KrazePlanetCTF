<?php
require_once __DIR__.'/lib/bootstrap.php';
require_once __DIR__.'/lib/layout.php';

$req=$_SERVER['REQUEST_URI'];
$rel='/'.ltrim(substr(parse_url($req,PHP_URL_PATH),strlen(BASE)),'/');
if($rel!=='/'&&str_ends_with($rel,'/')) $rel=rtrim($rel,'/');
$method=$_SERVER['REQUEST_METHOD'];

match(true){
    $rel==='/login'          &&$method==='POST' => action_login(),
    $rel==='/logout'         &&$method==='POST' => action_logout(),
    $rel==='/account/update' &&$method==='POST' => action_update_account(),
    $rel==='/install'        &&$method==='POST' => action_install(),

    $rel==='/'        => page_home(),
    $rel==='/projects'=> page_projects(),
    $rel==='/billing' => page_billing(),
    $rel==='/settings'=> page_settings(),
    $rel==='/admin'   => page_admin(),
    $rel==='/login'   => page_login(),
    $rel==='/install' => page_install(),
    default           => page_404(),
};

/* ══════════════════════════════════════════════════════════
   PAGE: DASHBOARD
══════════════════════════════════════════════════════════ */
function page_home(): void {
    $u=require_login(); $u=fresh_user();
    $p=PLANS[$u['plan']]??PLANS['free'];
    $proj_count=db()->prepare("SELECT COUNT(*) FROM projects WHERE user_id=?"); $proj_count->execute([$u['id']]); $proj_count=(int)$proj_count->fetchColumn();
    page_open('Dashboard');?>
<div class="wrap">
  <div class="two-col">
    <?php sidenav('home');?>
    <div>
      <?php if($u['admin_self_granted']):?>
      <div class="admin-banner">
        <div style="font-weight:700;color:#92400e;margin-bottom:4px">⚠️ Mass Assignment Exploit Detected</div>
        <div style="font-size:.82rem;color:#78350f">Your account was elevated to admin via a mass assignment vulnerability in the profile update endpoint. <a href="<?=url('admin')?>">Go to Admin Panel →</a></div>
      </div>
      <?php endif;?>

      <div style="font-size:1.2rem;font-weight:800;margin-bottom:20px">Good to see you, <?=h(explode(' ',$u['name'])[0])?> 👋</div>

      <div class="stat-grid">
        <div class="stat-card"><div class="stat-num"><?=$proj_count?></div><div class="stat-lbl">Projects</div></div>
        <div class="stat-card"><div class="stat-num"><?=number_format($u['api_calls_today'])?></div><div class="stat-lbl">API Calls Today</div></div>
        <div class="stat-card"><div class="stat-num"><?=number_format($u['api_calls_month'])?></div><div class="stat-lbl">API Calls This Month</div></div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
        <div class="card card-pad">
          <div style="font-weight:700;margin-bottom:12px;font-size:.9rem">📦 Current Plan</div>
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
            <?=plan_badge($u['plan'])?>
            <div style="font-size:.84rem;color:var(--text3)"><?=$p['projects']>=999?'Unlimited':$p['projects']?> projects · <?=$p['storage']?> storage</div>
          </div>
          <div style="font-size:.78rem;color:var(--text3)">Support: <?=h($p['support'])?></div>
          <?php if($u['plan']==='free'):?>
          <a href="<?=url('billing')?>" class="btn btn-primary btn-sm" style="margin-top:12px;width:100%">⬆️ Upgrade Plan</a>
          <?php endif;?>
        </div>

        <div class="card card-pad" style="background:#fffbeb;border-color:#fde68a">
          <div style="font-size:.7rem;font-weight:700;color:#d97706;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">💡 Lab Hint</div>
          <p style="font-size:.8rem;color:#78350f;line-height:1.7">
            Go to <strong>Settings</strong> and update your profile. Intercept the request in Burp Suite.<br><br>
            Add <code style="background:#fef3c7;padding:1px 4px;border-radius:3px">is_admin=1</code> and <code style="background:#fef3c7;padding:1px 4px;border-radius:3px">plan=enterprise</code> to the POST body.<br><br>
            Does the server accept these fields?
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: PROJECTS
══════════════════════════════════════════════════════════ */
function page_projects(): void {
    $u=require_login(); $u=fresh_user();
    $projects=db()->prepare("SELECT * FROM projects WHERE user_id=? ORDER BY created_at DESC"); $projects->execute([$u['id']]); $projects=$projects->fetchAll();
    $plan_limit=PLANS[$u['plan']]['projects'];
    page_open('Projects');?>
<div class="wrap">
  <div class="two-col">
    <?php sidenav('projects');?>
    <div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
        <div style="font-size:1.1rem;font-weight:800">📁 Projects <span style="font-size:.8rem;font-weight:400;color:var(--text3)">(<?=count($projects)?>/<?=$plan_limit>=999?'∞':$plan_limit?> on <?=ucfirst(h($u['plan']))?> plan)</span></div>
        <?php if(count($projects)<$plan_limit||$plan_limit>=999):?>
        <button class="btn btn-primary btn-sm" onclick="alert('Demo: New project creation simulated.')">+ New Project</button>
        <?php else:?>
        <a href="<?=url('billing')?>" class="btn btn-outline btn-sm">⬆️ Upgrade to add more</a>
        <?php endif;?>
      </div>
      <div class="card">
        <table class="table">
          <thead><tr><th>Project</th><th>Status</th><th>API Calls</th><th>Created</th></tr></thead>
          <tbody>
          <?php foreach($projects as $p):?>
          <tr>
            <td style="font-weight:600"><?=h($p['name'])?></td>
            <td><span style="background:<?=$p['status']==='active'?'#d1fae5':'#f1f5f9'?>;color:<?=$p['status']==='active'?'#065f46':'#64748b'?>;padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700"><?=ucfirst(h($p['status']))?></span></td>
            <td><?=number_format($p['api_calls'])?></td>
            <td style="font-size:.8rem;color:var(--text3)"><?=date('d M Y',strtotime($p['created_at']))?></td>
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
   PAGE: BILLING
══════════════════════════════════════════════════════════ */
function page_billing(): void {
    $u=require_login(); $u=fresh_user();
    page_open('Billing');?>
<div class="wrap">
  <div class="two-col">
    <?php sidenav('billing');?>
    <div>
      <div style="font-size:1.1rem;font-weight:800;margin-bottom:20px">💳 Plans & Billing</div>
      <div class="plan-grid">
        <?php foreach(PLANS as $key=>$p):?>
        <div class="plan-card <?=$u['plan']===$key?'current':''?>">
          <div class="plan-name" style="color:<?=$p['color']?>"><?=$p['label']?></div>
          <div class="plan-price"><?=$key==='free'?'₹0':($key==='pro'?'₹999':'Custom')?>/mo</div>
          <?php foreach(['Projects'=>$p['projects']>=999?'Unlimited':$p['projects'],'API Keys'=>$p['api_keys'],'Storage'=>$p['storage'],'Support'=>$p['support']] as $fl=>$fv):?>
          <div class="plan-feature"><?=$fl?>: <strong><?=$fv?></strong></div>
          <?php endforeach;?>
          <?php if($u['plan']===$key):?>
          <div style="margin-top:14px;font-size:.8rem;font-weight:700;color:var(--p)">✓ Current Plan</div>
          <?php else:?>
          <button class="btn btn-outline" style="width:100%;margin-top:14px;font-size:.8rem" onclick="alert('Payment flow simulated. Or try mass assignment in Settings...')">Select Plan</button>
          <?php endif;?>
        </div>
        <?php endforeach;?>
      </div>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: SETTINGS (profile update form)
══════════════════════════════════════════════════════════ */
function page_settings(): void {
    $u=require_login(); $u=fresh_user();
    page_open('Settings');?>
<div class="wrap">
  <div class="two-col">
    <?php sidenav('settings');?>
    <div>
      <div style="font-size:1.1rem;font-weight:800;margin-bottom:20px">⚙️ Account Settings</div>

      <div class="card card-pad" style="margin-bottom:20px">
        <div style="font-weight:700;margin-bottom:14px">Profile Information</div>
        <!--
          INTENTIONAL VULNERABILITY: this form submits only name, email, timezone.
          But the server iterates ALL POST fields without a whitelist.
          Adding is_admin=1 or plan=enterprise to the POST body will update those columns.
        -->
        <form method="POST" action="<?=url('account/update')?>">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?=h($u['name'])?>" required></div>
          <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?=h($u['email'])?>" required></div>
          <div class="form-group"><label class="form-label">Timezone</label>
            <select name="timezone" class="form-control">
              <?php foreach(['Asia/Kolkata','America/New_York','Europe/London','America/Los_Angeles','Asia/Singapore','Australia/Sydney'] as $tz):?>
              <option <?=$u['timezone']===$tz?'selected':''?>><?=$tz?></option>
              <?php endforeach;?>
            </select>
          </div>
          <button class="btn btn-primary">Save Changes</button>
        </form>
      </div>

      <div class="card card-pad" style="background:#f8fafc">
        <div style="font-weight:700;margin-bottom:10px;font-size:.9rem">Account Info</div>
        <div style="display:flex;flex-direction:column;gap:10px;font-size:.84rem">
          <?php foreach([['Plan',plan_badge($u['plan'])],['Admin',($u['is_admin']?'<span style="color:#d97706;font-weight:700">Yes ⚠️</span>':'No')],['User ID','#'.$u['id']]] as[$l,$v]):?>
          <div style="display:flex;justify-content:space-between;padding-bottom:10px;border-bottom:1px solid var(--border)">
            <span style="color:var(--text3)"><?=$l?></span><span><?=$v?></span>
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
   PAGE: ADMIN PANEL (admin only)
══════════════════════════════════════════════════════════ */
function page_admin(): void {
    $u=require_login(); $u=fresh_user();
    if(!$u['is_admin']){
        http_response_code(403); page_open('Access Denied');
        echo '<div class="wrap"><div class="card card-pad" style="text-align:center;padding:60px"><div style="font-size:2rem;margin-bottom:10px">🔒</div><div style="font-weight:700">Admin Access Required</div><p style="color:var(--text3);margin-top:8px">This panel is restricted to admin accounts.</p></div></div>';
        page_close(); return;
    }
    $users=db()->query("SELECT * FROM users ORDER BY id")->fetchAll();
    $total_api=db()->query("SELECT SUM(api_calls_month) FROM users")->fetchColumn();
    page_open('Admin Panel');?>
<div class="wrap">
  <div class="two-col">
    <?php sidenav('admin');?>
    <div>
      <?php if($u['admin_self_granted']):?>
      <div class="flag-box">
        <h3>🚩 Mass Assignment — Privilege Escalation Successful!</h3>
        <p style="color:#6ee7b7;font-size:.875rem;margin-bottom:12px">
          You elevated your own account to admin by injecting <code style="background:#052e1c;padding:2px 5px;border-radius:4px">is_admin=1</code> (and/or <code style="background:#052e1c;padding:2px 5px;border-radius:4px">plan=enterprise</code>) into the <code style="background:#052e1c;padding:2px 5px;border-radius:4px">POST /account/update</code> request.<br><br>
          The handler iterates all POST fields and builds <code style="background:#052e1c;padding:2px 5px;border-radius:4px">UPDATE users SET field=value</code> without a whitelist of allowed fields. Any column name sent as a POST parameter gets updated directly.
        </p>
        <div style="font-size:.78rem;color:#34d399;margin-bottom:6px">Your Flag:</div>
        <div class="flag-val"><?=LAB_FLAG?></div>
        <div style="margin-top:12px;font-size:.75rem;color:#6ee7b7">
          <strong>Fix:</strong> Use an explicit allowlist: <code style="background:#052e1c;padding:2px 4px;border-radius:3px">$allowed = ['name', 'email', 'timezone'];</code> and only process keys in that list.
        </div>
      </div>
      <?php endif;?>

      <div style="font-size:1.1rem;font-weight:800;margin-bottom:20px">🛡️ Admin Panel — All Accounts</div>

      <div class="stat-grid" style="margin-bottom:24px">
        <div class="stat-card"><div class="stat-num"><?=count($users)?></div><div class="stat-lbl">Total Users</div></div>
        <div class="stat-card"><div class="stat-num"><?=number_format((int)$total_api)?></div><div class="stat-lbl">Total API Calls</div></div>
        <div class="stat-card"><div class="stat-num"><?=count(array_filter($users,fn($u)=>$u['is_admin']))?></div><div class="stat-lbl">Admin Accounts</div></div>
      </div>

      <div class="card">
        <table class="table">
          <thead><tr><th>User</th><th>Plan</th><th>Admin</th><th>API Calls/Month</th><th>Self-Elevated</th></tr></thead>
          <tbody>
          <?php foreach($users as $usr):?>
          <tr>
            <td><div style="font-weight:600"><?=h($usr['name'])?></div><div style="font-size:.75rem;color:var(--text3)"><?=h($usr['email'])?></div></td>
            <td><?=plan_badge($usr['plan'])?></td>
            <td><?=$usr['is_admin']?'<span style="color:#d97706;font-weight:700">✓ Yes</span>':'No'?></td>
            <td><?=number_format($usr['api_calls_month'])?></td>
            <td><?=$usr['admin_self_granted']?'<span style="color:#dc2626;font-weight:700">⚠️ Yes</span>':'—'?></td>
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
    page_open('Sign In');?>
<div class="wrap" style="max-width:400px;margin-left:auto;margin-right:auto;padding-top:32px">
  <div style="text-align:center;margin-bottom:24px">
    <div style="font-size:2rem;margin-bottom:8px">⚡</div>
    <div style="font-size:1.3rem;font-weight:800;color:var(--p)">AccountHub</div>
    <div style="font-size:.875rem;color:var(--text3);margin-top:6px">Sign in to your account</div>
  </div>
  <div class="card card-pad">
    <form method="POST" action="<?=url('login')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required autofocus></div>
      <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-primary btn-lg" style="width:100%">Sign In</button>
    </form>
    <div style="margin-top:14px;padding:12px;background:var(--bg3);border-radius:8px;font-size:.75rem;color:var(--text2)">
      <strong style="color:var(--p)">Demo Accounts:</strong><br>
      student@accounthub.lab / student123 <span style="color:var(--text3)">(free tier — your account)</span><br>
      admin@accounthub.io / admin123 <span style="color:var(--text3)">(admin, enterprise)</span><br>
      pro@accounthub.io / pro123 <span style="color:var(--text3)">(pro plan)</span>
    </div>
  </div>
</div>
<?php page_close();
}

function page_install(): void {
    page_open('Reset Lab');?>
<div class="wrap" style="max-width:400px;margin-left:auto;margin-right:auto">
  <div class="card card-pad" style="text-align:center">
    <div style="font-size:2rem;margin-bottom:10px">⚗️</div>
    <div style="font-weight:700;margin-bottom:8px">Reset Lab Database</div>
    <form method="POST"><button class="btn btn-danger btn-lg" style="width:100%">Reset</button></form>
  </div>
</div>
<?php page_close();
}

function page_404(): void {
    http_response_code(404); page_open('Not Found');
    echo '<div class="wrap" style="text-align:center;padding:80px"><div style="font-size:2.5rem;margin-bottom:10px">🔍</div><div>Page Not Found</div><a href="'.url().'" class="btn btn-primary" style="margin-top:20px;display:inline-flex">Go Home</a></div>';
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

/*
 * INTENTIONALLY VULNERABLE ACCOUNT UPDATE ENDPOINT
 * POST /account/update — accepts name, email, timezone from the form.
 *
 * Vulnerability: the handler iterates ALL POST fields (minus _csrf) and builds
 * a dynamic UPDATE query without a field whitelist. Any column that exists in
 * the users table can be overwritten by including it as a POST parameter.
 *
 * Burp Suite vector:
 *   POST /subdomains/accounthub/account/update
 *   name=Student&email=student@accounthub.lab&timezone=Asia/Kolkata&is_admin=1&plan=enterprise&_csrf=<token>
 *   → user.is_admin = 1, user.plan = 'enterprise', admin_self_granted = 1 (via injected field)
 *
 * Secure fix: define $allowed = ['name', 'email', 'timezone'];
 * and only process fields in that list.
 */
function action_update_account(): void {
    $u=require_login(); verify_csrf();

    $was_admin_before=$u['is_admin'];

    // INTENTIONAL VULNERABILITY: no field whitelist — all POST fields except _csrf
    $sets=[]; $vals=[];
    $forbidden=['id','password_hash','created_at','_csrf'];
    foreach($_POST as $k=>$v){
        if(in_array($k,$forbidden,true)) continue;
        // Basic safety: only alphanumeric+underscore column names to prevent SQL injection
        if(!preg_match('/^[a-z_][a-z0-9_]{0,49}$/i',$k)) continue;
        $sets[]="`$k`=?";
        $vals[]=$v;
    }
    if(!$sets){flash('error','Nothing to update.');redirect('settings');}

    // If is_admin is being set and it wasn't already set, mark self-granted
    if(isset($_POST['is_admin'])&&(int)$_POST['is_admin']===1&&!$was_admin_before){
        $sets[]="`admin_self_granted`=?"; $vals[]=1;
    }

    $vals[]=$u['id'];
    db()->prepare("UPDATE users SET ".implode(',',$sets)." WHERE id=?")->execute($vals);

    flash('success','Account updated successfully.'); redirect('settings');
}

function action_install(): void {
    install_schema(db(),true); flash('success','Database reset.'); redirect('login');
}
