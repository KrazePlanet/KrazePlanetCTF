<?php
require_once __DIR__.'/lib/bootstrap.php';
require_once __DIR__.'/lib/layout.php';

$req=$_SERVER['REQUEST_URI'];
$rel='/'.ltrim(substr(parse_url($req,PHP_URL_PATH),strlen(BASE)),'/');
if($rel!=='/'&&str_ends_with($rel,'/')) $rel=rtrim($rel,'/');
$method=$_SERVER['REQUEST_METHOD'];

match(true){
    $rel==='/login'          &&$method==='POST' => action_login(),
    $rel==='/register'       &&$method==='POST' => action_register(),
    $rel==='/logout'         &&$method==='POST' => action_logout(),
    $rel==='/claims/submit'  &&$method==='POST' => action_claim_submit(),
    $rel==='/admin/review'   &&$method==='POST' => action_admin_review(),
    $rel==='/install'        &&$method==='POST' => action_install(),

    $rel==='/'                                              => page_home(),
    $rel==='/claims/new'                                    => page_claim_new(),
    (bool)preg_match('#^/claims/(\d+)$#',$rel,$m)          => page_claim_detail((int)$m[1]),
    $rel==='/claims'                                        => page_claims(),
    $rel==='/admin'                                         => page_admin(),
    $rel==='/login'                                         => page_login(),
    $rel==='/register'                                      => page_register(),
    $rel==='/install'                                       => page_install(),
    default                                                 => page_404(),
};

/* ══════════════════════════════════════════════════════════
   PAGE: HOME / DASHBOARD
══════════════════════════════════════════════════════════ */
function page_home(): void {
    $u=require_login();
    $st=db()->prepare("SELECT
        COUNT(*) AS total,
        COALESCE(SUM(CASE WHEN status='approved' THEN amount ELSE 0 END),0) AS approved_total,
        COALESCE(SUM(CASE WHEN status='paid'     THEN amount ELSE 0 END),0) AS paid_total,
        COALESCE(SUM(CASE WHEN status='submitted' OR status='under_review' THEN amount ELSE 0 END),0) AS pending_total
        FROM claims WHERE user_id=?");
    $st->execute([$u['id']]); $stats=$st->fetch();

    $recent=db()->prepare("SELECT * FROM claims WHERE user_id=? ORDER BY id DESC LIMIT 5");
    $recent->execute([$u['id']]); $recent=$recent->fetchAll();

    // Check for duplicate exploit
    $dup_st=db()->prepare("SELECT claim_ref,COUNT(*) AS cnt FROM claims WHERE user_id=? GROUP BY claim_ref HAVING cnt>=2");
    $dup_st->execute([$u['id']]); $dup=$dup_st->fetch();
    $flag_show=$dup&&(int)$dup['cnt']>=2;

    page_open('Dashboard');
    ?>
<div class="app">
  <?php if($flag_show):?>
  <div class="flag-box">
    <h3>🚩 Idempotency Vulnerability Exploited!</h3>
    <p style="color:#6ee7b7;font-size:.875rem;margin-bottom:10px">
      You submitted the same <code style="background:#052e1c;padding:2px 6px;border-radius:4px">claim_ref</code> multiple times.<br>
      The server's <code style="background:#052e1c;padding:2px 6px;border-radius:4px">POST /claims/submit</code> endpoint performs a plain <code style="background:#052e1c;padding:2px 6px;border-radius:4px">INSERT</code> with <strong>no uniqueness check</strong> on <code style="background:#052e1c;padding:2px 6px;border-radius:4px">claim_ref</code>.<br>
      The same expense claim was approved multiple times, multiplying the payout.
    </p>
    <div style="font-size:.78rem;color:#34d399;margin-bottom:6px">Your Flag:</div>
    <div class="flag-val"><?=LAB_FLAG?></div>
    <div style="margin-top:12px;font-size:.75rem;color:#6ee7b7">
      <strong>Fix:</strong> Add <code style="background:#052e1c;padding:2px 4px;border-radius:3px">UNIQUE KEY (user_id, claim_ref)</code> to the claims table, or check for an existing <code style="background:#052e1c;padding:2px 4px;border-radius:3px">claim_ref</code> before inserting.
    </div>
  </div>
  <?php endif;?>

  <div class="page-header">
    <div>
      <div class="page-title">👋 Welcome, <?=h(explode(' ',$u['name'])[0])?></div>
      <div class="page-sub"><?=h($u['department'])?> · Employee ID: <?=h($u['employee_id'])?></div>
    </div>
    <a href="<?=url('claims/new')?>" class="btn btn-primary btn-lg">➕ New Claim</a>
  </div>

  <!-- STATS -->
  <div class="stat-grid">
    <?php
    $ss=[
      ['📋','bg:#eff6ff;color:var(--p)',$stats['total'],'Total Claims'],
      ['⏳','bg:#fef3c7;color:var(--amber)',inr((int)$stats['pending_total']),'Pending Amount'],
      ['✅','bg:#dcfce7;color:var(--green)',inr((int)$stats['approved_total']),'Approved Amount'],
      ['💰','bg:#f0fdf4;color:#15803d',inr((int)$stats['paid_total']),'Paid Out'],
    ];
    foreach($ss as[$icon,$style,$val,$lbl]):?>
    <div class="stat-card">
      <div class="stat-icon" style="<?=$style?>"><?=$icon?></div>
      <div class="stat-val"><?=$val?></div>
      <div class="stat-lbl"><?=$lbl?></div>
    </div>
    <?php endforeach;?>
  </div>

  <!-- RECENT CLAIMS -->
  <?php if($recent):?>
  <div class="card">
    <div class="card-header">
      <span>Recent Claims</span>
      <a href="<?=url('claims')?>" class="btn btn-secondary btn-sm">View All →</a>
    </div>
    <table class="table">
      <thead><tr><th>Ref</th><th>Category</th><th>Amount</th><th>Description</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody><?php foreach($recent as $c) claim_row($c);?></tbody>
    </table>
  </div>
  <?php else:?>
  <div class="card card-pad" style="text-align:center;padding:60px">
    <div style="font-size:3rem;margin-bottom:12px">📋</div>
    <div style="font-weight:700;font-size:1.1rem">No claims yet</div>
    <p style="color:var(--text3);margin:8px 0 20px;font-size:.875rem">Submit your first expense claim to get started.</p>
    <a href="<?=url('claims/new')?>" class="btn btn-primary">➕ Submit a Claim</a>
  </div>
  <?php endif;?>

  <!-- LAB HINT -->
  <div class="card card-pad" style="margin-top:20px;background:#fffbeb;border-color:#fcd34d">
    <div style="font-size:.75rem;font-weight:700;color:#92400e;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px">💡 Lab Hint</div>
    <p style="font-size:.825rem;color:#78350f;line-height:1.7">
      When you submit a claim, a <code style="background:#fff8e6;padding:1px 5px;border-radius:3px">claim_ref</code> UUID is generated in the browser and sent with the form.<br>
      The server does a plain <code style="background:#fff8e6;padding:1px 5px;border-radius:3px">INSERT</code> — no uniqueness check.<br><br>
      <strong>Exploit:</strong> Intercept the <code style="background:#fff8e6;padding:1px 5px;border-radius:3px">POST /claims/submit</code> request in Burp Suite and replay it 2–3 times with the same body.
      Each replay creates a new approved claim for the same expense.
    </p>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: NEW CLAIM FORM
══════════════════════════════════════════════════════════ */
function page_claim_new(): void {
    $u=require_login();
    page_open('New Claim');
    ?>
<div class="app" style="max-width:720px;margin-left:auto;margin-right:auto">
  <!-- Progress Steps -->
  <div class="steps">
    <div class="step">
      <div class="step-dot step-active">1</div>
      <div class="step-label active">Claim Details</div>
    </div>
    <div class="step-line"></div>
    <div class="step">
      <div class="step-dot step-pending">2</div>
      <div class="step-label">Review</div>
    </div>
    <div class="step-line"></div>
    <div class="step">
      <div class="step-dot step-pending">3</div>
      <div class="step-label">Submitted</div>
    </div>
  </div>

  <div class="page-header" style="margin-bottom:20px">
    <div>
      <div class="page-title">New Expense Claim</div>
      <div class="page-sub">Fill in the details below. Claims under ₹15,000 are auto-approved.</div>
    </div>
  </div>

  <div class="card card-pad">
    <form method="POST" action="<?=url('claims/submit')?>" id="claim-form">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <!--
        claim_ref is a UUID generated client-side to uniquely identify this submission.
        Intended as an idempotency key — replaying the same request should be a no-op.
        VULNERABILITY: the server never checks if this claim_ref already exists.
      -->
      <input type="hidden" name="claim_ref" id="claim_ref">

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Expense Category *</label>
          <select name="category" class="form-control" id="category" required>
            <option value="">Select category…</option>
            <?php foreach(['Travel','Accommodation','Meals','Equipment','Office Supplies','Training','Medical'] as $c):?>
            <option><?=$c?></option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Amount (₹) *</label>
          <input type="number" name="amount_rupees" id="amount_rupees" class="form-control" placeholder="0.00" step="0.01" min="1" required>
          <div class="form-hint">Enter amount in rupees (e.g. 4500 for ₹4,500)</div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Description *</label>
        <input type="text" name="description" class="form-control" placeholder="Brief description of the expense" required maxlength="500">
      </div>

      <div class="form-group">
        <label class="form-label">Receipt / Bill Reference</label>
        <input type="text" name="receipt_note" class="form-control" placeholder="Invoice no., order ID, or bill reference (optional)">
        <div class="form-hint">We may ask you to upload the original receipt later.</div>
      </div>

      <!-- Travel fields (shown when Travel selected) -->
      <div id="travel-fields" style="display:none">
        <div style="background:var(--bg3);border:1px solid var(--p3);border-radius:8px;padding:16px;margin-bottom:18px">
          <div style="font-size:.8rem;font-weight:700;color:var(--p);margin-bottom:14px">✈️ Travel Details</div>
          <div class="form-row">
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">From</label>
              <input type="text" name="travel_from" class="form-control" placeholder="City / Airport">
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">To</label>
              <input type="text" name="travel_to" class="form-control" placeholder="City / Airport">
            </div>
          </div>
          <div class="form-group" style="margin-top:14px;margin-bottom:0">
            <label class="form-label">Travel Date</label>
            <input type="date" name="travel_date" class="form-control">
          </div>
        </div>
      </div>

      <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:12px 14px;margin-bottom:20px;font-size:.8rem;color:#78350f">
        <strong>Policy reminder:</strong> Claims above ₹15,000 require manager approval and original receipts. Processing time: 2–3 business days.
      </div>

      <div style="display:flex;gap:12px">
        <button type="submit" class="btn btn-primary btn-lg" style="flex:1">Submit Claim →</button>
        <a href="<?=url()?>" class="btn btn-secondary btn-lg">Cancel</a>
      </div>
    </form>
  </div>
</div>
<script>
// Generate a UUID v4 as idempotency key for this submission
// This is meant to prevent duplicate submissions — but the server doesn't enforce it
function uuidv4(){
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,function(c){
        var r=Math.random()*16|0,v=c=='x'?r:(r&0x3|0x8);
        return v.toString(16);
    });
}
document.getElementById('claim_ref').value=uuidv4();

// Show travel fields
document.getElementById('category').addEventListener('change',function(){
    document.getElementById('travel-fields').style.display=this.value==='Travel'?'block':'none';
});
</script>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: CLAIM DETAIL
══════════════════════════════════════════════════════════ */
function page_claim_detail(int $id): void {
    $u=require_login();
    $st=db()->prepare("SELECT c.*,u.name AS uname,u.department,u.employee_id FROM claims c JOIN users u ON u.id=c.user_id WHERE c.id=?");
    $st->execute([$id]); $c=$st->fetch();
    if(!$c||(!$u['is_admin']&&$c['user_id']!=$u['id'])){page_404();return;}

    // Check if this claim_ref has duplicates
    $dup_st=db()->prepare("SELECT COUNT(*) FROM claims WHERE user_id=? AND claim_ref=?");
    $dup_st->execute([$c['user_id'],$c['claim_ref']]); $dup_count=(int)$dup_st->fetchColumn();

    page_open('Claim #'.$id);
    ?>
<div class="app" style="max-width:720px;margin-left:auto;margin-right:auto">
  <div class="page-header">
    <div>
      <div class="page-title">Claim #<?=$id?></div>
      <div class="page-sub" style="font-family:monospace;font-size:.78rem">Ref: <?=h($c['claim_ref'])?></div>
    </div>
    <span class="tag <?=status_tag($c['status'])?>" style="font-size:.8rem;padding:5px 12px">
      <?=status_icon($c['status'])?> <?=ucfirst($c['status'])?>
    </span>
  </div>

  <?php if($dup_count>1):?>
  <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:16px 20px;margin-bottom:20px;display:flex;gap:10px">
    <div style="font-size:1.2rem">⚠️</div>
    <div>
      <div style="font-weight:700;color:#991b1b;margin-bottom:3px">Duplicate Detected</div>
      <div style="font-size:.82rem;color:#b91c1c">This <code>claim_ref</code> appears <?=$dup_count?> times in the system. The server accepted all submissions because there is no uniqueness enforcement on <code>claim_ref</code>.</div>
    </div>
  </div>
  <?php endif;?>

  <div class="card" style="margin-bottom:16px">
    <div class="card-header">Claim Details</div>
    <div style="padding:4px 20px">
      <div class="claim-field"><div class="claim-label">Category</div><div class="claim-value"><?=category_icon($c['category'])?> <?=h($c['category'])?></div></div>
      <div class="claim-field"><div class="claim-label">Amount</div><div class="claim-value amount-big"><?=inr((int)$c['amount'])?></div></div>
      <div class="claim-field"><div class="claim-label">Description</div><div class="claim-value"><?=h($c['description'])?></div></div>
      <?php if($c['receipt_note']):?>
      <div class="claim-field"><div class="claim-label">Receipt</div><div class="claim-value"><?=h($c['receipt_note'])?></div></div>
      <?php endif;?>
      <?php if($c['travel_from']||$c['travel_to']):?>
      <div class="claim-field"><div class="claim-label">Journey</div><div class="claim-value"><?=h($c['travel_from'])?> → <?=h($c['travel_to'])?><?php if($c['travel_date']):?> · <?=date('d M Y',strtotime($c['travel_date']))?><?php endif;?></div></div>
      <?php endif;?>
    </div>
  </div>

  <div class="card" style="margin-bottom:16px">
    <div class="card-header">Submission Info</div>
    <div style="padding:4px 20px">
      <div class="claim-field"><div class="claim-label">Employee</div><div class="claim-value"><?=h($c['uname'])?> · <?=h($c['department'])?> · ID: <?=h($c['employee_id'])?></div></div>
      <div class="claim-field"><div class="claim-label">Claim Ref</div><div class="claim-value" style="font-family:monospace;font-size:.8rem;word-break:break-all"><?=h($c['claim_ref'])?></div></div>
      <div class="claim-field"><div class="claim-label">Submitted</div><div class="claim-value"><?=date('d M Y, g:i A',strtotime($c['submitted_at']))?></div></div>
      <div class="claim-field"><div class="claim-label">Status</div><div class="claim-value"><span class="tag <?=status_tag($c['status'])?>"><?=status_icon($c['status'])?> <?=ucfirst(str_replace('_',' ',$c['status']))?></span></div></div>
    </div>
  </div>

  <?php if($u['is_admin']&&$c['status']==='submitted'):?>
  <div class="card card-pad" style="background:#f8fafc">
    <div style="font-weight:700;margin-bottom:14px">Finance Team Review</div>
    <form method="POST" action="<?=url('admin/review')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <input type="hidden" name="claim_id" value="<?=$id?>">
      <div style="display:flex;gap:10px">
        <button name="action" value="approve" class="btn btn-success" style="flex:1">✓ Approve</button>
        <button name="action" value="reject" class="btn btn-danger" style="flex:1">✗ Reject</button>
      </div>
    </form>
  </div>
  <?php endif;?>

  <a href="<?=url('claims')?>" class="btn btn-secondary" style="margin-top:16px">← Back to Claims</a>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: CLAIMS LIST
══════════════════════════════════════════════════════════ */
function page_claims(): void {
    $u=require_login();
    $st=db()->prepare("SELECT * FROM claims WHERE user_id=? ORDER BY id DESC");
    $st->execute([$u['id']]); $claims=$st->fetchAll();
    $total_approved=array_sum(array_column(array_filter($claims,fn($c)=>in_array($c['status'],['approved','paid'])),'amount'));

    page_open('My Claims');
    ?>
<div class="app">
  <div class="page-header">
    <div>
      <div class="page-title">📋 My Claims</div>
      <div class="page-sub"><?=count($claims)?> claims · <?=inr($total_approved)?> approved</div>
    </div>
    <a href="<?=url('claims/new')?>" class="btn btn-primary">➕ New Claim</a>
  </div>
  <?php if(!$claims):?>
  <div class="card card-pad" style="text-align:center;padding:60px">
    <div style="font-size:2.5rem;margin-bottom:10px">📋</div>
    <div style="font-weight:600">No claims yet</div>
    <a href="<?=url('claims/new')?>" class="btn btn-primary" style="margin-top:16px">Submit First Claim</a>
  </div>
  <?php else:?>
  <div class="card">
    <table class="table">
      <thead><tr><th>Ref</th><th>Category</th><th>Amount</th><th>Description</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody><?php foreach($claims as $c) claim_row($c);?></tbody>
    </table>
  </div>
  <?php endif;?>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: ADMIN
══════════════════════════════════════════════════════════ */
function page_admin(): void {
    $u=require_login();
    if(!$u['is_admin']){flash('error','Access denied.');redirect('');}

    $filter=$_GET['status']??'all';
    $where=$filter==='all'?'1=1':"c.status='".str_replace("'",'',$filter)."'";
    $claims=db()->query("SELECT c.*,u.name AS uname,u.department FROM claims c JOIN users u ON u.id=c.user_id WHERE $where ORDER BY c.id DESC LIMIT 100")->fetchAll();

    $totals=db()->query("SELECT status,COUNT(*) AS cnt,COALESCE(SUM(amount),0) AS total FROM claims GROUP BY status")->fetchAll();

    page_open('Admin — Finance Review');
    ?>
<div class="app">
  <div class="page-header">
    <div><div class="page-title">⚙️ Finance Admin Panel</div><div class="page-sub">Review and approve employee expense claims</div></div>
  </div>

  <!-- Totals -->
  <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:24px">
    <?php foreach($totals as $t):
      $icons=['submitted'=>'📋','under_review'=>'⏳','approved'=>'✅','rejected'=>'✗','paid'=>'💰'];?>
    <div class="card card-pad" style="padding:14px">
      <div style="font-size:.7rem;font-weight:700;color:var(--text3);text-transform:uppercase;margin-bottom:4px"><?=ucfirst(str_replace('_',' ',$t['status']))?></div>
      <div style="font-size:1.2rem;font-weight:800"><?=$t['cnt']?></div>
      <div style="font-size:.75rem;color:var(--text3)"><?=inr((int)$t['total'])?></div>
    </div>
    <?php endforeach;?>
  </div>

  <!-- Filter tabs -->
  <div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
    <?php foreach(['all'=>'All','submitted'=>'Pending','under_review'=>'In Review','approved'=>'Approved','rejected'=>'Rejected','paid'=>'Paid'] as $v=>$l):?>
    <a href="<?=url('admin')?>?status=<?=$v?>" class="btn <?=$filter===$v?'btn-primary':'btn-secondary'?> btn-sm"><?=$l?></a>
    <?php endforeach;?>
  </div>

  <div class="card">
    <table class="table">
      <thead><tr><th>ID</th><th>Employee</th><th>Category</th><th>Amount</th><th>Description</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody><?php foreach($claims as $c) claim_row($c,true);?></tbody>
    </table>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   AUTH PAGES
══════════════════════════════════════════════════════════ */
function page_login(): void {
    if(user()) redirect('');
    page_open('Sign In');
    ?>
<div class="app" style="max-width:420px;margin-left:auto;margin-right:auto;padding-top:32px">
  <div style="text-align:center;margin-bottom:28px">
    <div style="width:56px;height:56px;background:var(--p);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:900;color:#fff;margin:0 auto 12px">CD</div>
    <div class="page-title">Sign in to ClaimDesk</div>
    <div class="page-sub">Expense Management Portal</div>
  </div>
  <div class="card card-pad">
    <form method="POST" action="<?=url('login')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <div class="form-group"><label class="form-label">Work Email</label><input type="email" name="email" class="form-control" required autofocus placeholder="you@company.com"></div>
      <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-primary btn-lg" style="width:100%">Sign In →</button>
    </form>
    <div style="text-align:center;margin-top:14px;font-size:.84rem;color:var(--text3)">New employee? <a href="<?=url('register')?>">Register here</a></div>
    <div style="margin-top:14px;padding:12px;background:var(--bg);border:1px solid var(--border);border-radius:8px;font-size:.75rem;color:var(--text3)">
      <strong style="color:var(--p)">Demo accounts:</strong><br>
      student@claimdesk.lab / student123 (Engineering)<br>
      ananya@example.com / ananya123 (Sales)<br>
      admin@claimdesk.app / admin123 (Finance Admin)
    </div>
  </div>
</div>
<?php page_close();
}

function page_register(): void {
    if(user()) redirect('');
    page_open('Register');
    ?>
<div class="app" style="max-width:440px;margin-left:auto;margin-right:auto;padding-top:32px">
  <div class="page-title" style="margin-bottom:20px;text-align:center">Create Account</div>
  <div class="card card-pad">
    <form method="POST" action="<?=url('register')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Work Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Employee ID</label><input type="text" name="employee_id" class="form-control" placeholder="EMP001"></div>
        <div class="form-group"><label class="form-label">Department</label>
          <select name="department" class="form-control">
            <?php foreach(['Engineering','Sales','Marketing','Finance','HR','Operations','Design'] as $d):?><option><?=$d?></option><?php endforeach;?>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
      <button class="btn btn-primary btn-lg" style="width:100%">Create Account</button>
    </form>
    <div style="text-align:center;margin-top:14px;font-size:.84rem;color:var(--text3)">Already registered? <a href="<?=url('login')?>">Sign in</a></div>
  </div>
</div>
<?php page_close();
}

function page_install(): void {
    page_open('Reset Lab');
    ?>
<div class="app" style="max-width:440px;margin-left:auto;margin-right:auto">
  <div class="card card-pad" style="text-align:center">
    <div style="font-size:2.5rem;margin-bottom:12px">⚗️</div>
    <div class="page-title">Reset Lab Database</div>
    <p style="color:var(--text3);margin:12px 0 20px;font-size:.875rem">Drops all tables and re-seeds with fresh data.</p>
    <form method="POST"><button class="btn btn-danger btn-lg" style="width:100%">Reset Database</button></form>
    <a href="<?=url('login')?>" class="btn btn-secondary" style="width:100%;margin-top:10px">Cancel</a>
  </div>
</div>
<?php page_close();
}

function page_404(): void {
    http_response_code(404); page_open('404');
    echo '<div class="app" style="text-align:center;padding:80px"><div style="font-size:3rem;margin-bottom:12px">🔍</div><div class="page-title">Page Not Found</div><a href="'.url().'" class="btn btn-primary" style="margin-top:20px;display:inline-flex">Go Home</a></div>';
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

function action_register(): void {
    verify_csrf();
    $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $pass=$_POST['password']??'';
    $dept=$_POST['department']??'Engineering'; $eid=trim($_POST['employee_id']??'');
    if(!$name||!$email||!$pass||!filter_var($email,FILTER_VALIDATE_EMAIL)){flash('error','Fill all required fields.');redirect('register');}
    try {
        db()->prepare("INSERT INTO users(name,email,password_hash,employee_id,department) VALUES(?,?,?,?,?)")
            ->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$eid,$dept]);
        session_regenerate_id(true); $_SESSION['uid']=(int)db()->lastInsertId();
        flash('success','Account created! Welcome to ClaimDesk.'); redirect('');
    } catch(PDOException){flash('error','Email already registered.');redirect('register');}
}

function action_logout(): void {
    verify_csrf(); session_destroy(); header('Location:'.url('login')); exit;
}

/*
 * INTENTIONALLY VULNERABLE CLAIM SUBMISSION
 * POST /claims/submit  params: claim_ref, category, amount_rupees, description, receipt_note, ...
 *
 * Vulnerability: plain INSERT with no idempotency check on claim_ref.
 * The client generates a UUID and embeds it in the hidden field "claim_ref".
 * This is supposed to be a submission token — submitting the same form twice should be a no-op.
 * But the server does a plain INSERT without checking if the claim_ref already exists.
 * Additionally, there is no UNIQUE constraint on claims(user_id, claim_ref) in the schema.
 *
 * Exploit: intercept the POST in Burp Repeater and replay it 2-3 times.
 * Each replay creates a new approved claim for the same expense.
 */
function action_claim_submit(): void {
    $u=require_login(); verify_csrf();

    $claim_ref=trim($_POST['claim_ref']??'');
    $category=$_POST['category']??'';
    $amount_rupees=filter_var($_POST['amount_rupees']??'',FILTER_VALIDATE_FLOAT);
    $description=trim($_POST['description']??'');
    $receipt_note=trim($_POST['receipt_note']??'');
    $travel_from=trim($_POST['travel_from']??'');
    $travel_to=trim($_POST['travel_to']??'');
    $travel_date=($_POST['travel_date']??'')?:null;

    // Basic input validation
    if(!$claim_ref||!$category||$amount_rupees===false||$amount_rupees<=0||!$description){
        flash('error','Please fill all required fields.');redirect('claims/new');
    }
    $valid_cats=['Travel','Accommodation','Meals','Equipment','Office Supplies','Training','Medical'];
    if(!in_array($category,$valid_cats)){flash('error','Invalid category.');redirect('claims/new');}

    $amount_paise=(int)round($amount_rupees*100);

    // INTENTIONAL VULNERABILITY: no check for existing claim_ref.
    // A secure implementation would be:
    //   $check = db()->prepare("SELECT id FROM claims WHERE user_id=? AND claim_ref=?");
    //   $check->execute([$u['id'], $claim_ref]);
    //   if ($check->fetch()) { flash('info', 'Claim already submitted.'); redirect('claims'); }
    //
    // Without this check (and without a UNIQUE constraint), replaying the POST creates
    // a new claim every time — even with the identical claim_ref UUID.

    // Auto-approve claims under ₹15,000 (realistic: small claims auto-approved)
    $status=$amount_paise<=1500000?'approved':'submitted';

    db()->prepare("INSERT INTO claims(user_id,claim_ref,category,amount,description,receipt_note,travel_from,travel_to,travel_date,status) VALUES(?,?,?,?,?,?,?,?,?,?)")
        ->execute([$u['id'],$claim_ref,$category,$amount_paise,$description,$receipt_note,$travel_from,$travel_to,$travel_date,$status]);

    $cid=(int)db()->lastInsertId();

    if($status==='approved')
        flash('success',"✅ Claim #$cid submitted and auto-approved! Amount: ".inr($amount_paise));
    else
        flash('info',"📋 Claim #$cid submitted. Under review — approval within 2-3 business days.");

    redirect("claims/$cid");
}

function action_admin_review(): void {
    $u=require_login(); verify_csrf();
    if(!$u['is_admin']){flash('error','Access denied.');redirect('');}
    $cid=(int)($_POST['claim_id']??0); $action=$_POST['action']??'';
    if(!in_array($action,['approve','reject'])){flash('error','Invalid action.');redirect('admin');}
    $new_status=$action==='approve'?'approved':'rejected';
    db()->prepare("UPDATE claims SET status=?,reviewed_at=NOW() WHERE id=?")->execute([$new_status,$cid]);
    flash('success',"Claim #$cid ".($action==='approve'?'approved':'rejected').'.');
    redirect("claims/$cid");
}

function action_install(): void {
    install_schema(db(),true); flash('success','Database reset.'); redirect('login');
}
