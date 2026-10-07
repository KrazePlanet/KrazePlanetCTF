<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';

$req    = $_SERVER['REQUEST_URI'];
$rel    = '/' . ltrim(substr(parse_url($req, PHP_URL_PATH), strlen(BASE)), '/');
if ($rel !== '/' && str_ends_with($rel, '/')) $rel = rtrim($rel, '/');
$method = $_SERVER['REQUEST_METHOD'];

match (true) {
    /* POST actions first to prevent path shadowing */
    $rel === '/login'     && $method === 'POST' => action_login(),
    $rel === '/register'  && $method === 'POST' => action_register(),
    $rel === '/logout'    && $method === 'POST' => action_logout(),
    $rel === '/transfer'  && $method === 'POST' => action_transfer(),
    $rel === '/topup'     && $method === 'POST' => action_topup(),
    $rel === '/recharge'  && $method === 'POST' => action_recharge(),
    $rel === '/bill'      && $method === 'POST' => action_bill(),
    $rel === '/profile'   && $method === 'POST' => action_profile(),
    $rel === '/install'   && $method === 'POST' => action_install(),

    /* GET pages */
    $rel === '/'          => page_home(),
    $rel === '/transfer'  => page_transfer(),
    $rel === '/topup'     => page_topup(),
    $rel === '/recharge'  => page_recharge(),
    $rel === '/bill'      => page_bill(),
    $rel === '/history'   => page_history(),
    $rel === '/profile'   => page_profile_page(),
    $rel === '/login'     => page_login(),
    $rel === '/register'  => page_register(),
    $rel === '/admin'     => page_admin(),
    $rel === '/install'   => page_install(),
    default               => page_404(),
};

/* ══════════════════════════════════════════════════════════
   PAGE: HOME / WALLET DASHBOARD
══════════════════════════════════════════════════════════ */
function page_home(): void {
    $u = require_login();
    $u = fresh_user();

    // Check flag condition: balance > initial seed (was exploited)
    $flag_show = (int)$u['balance'] > SEED_BAL;

    // Recent transactions
    $st = db()->prepare("
        SELECT t.*, uf.name AS from_name, ut.name AS to_name
        FROM transactions t
        LEFT JOIN users uf ON uf.id = t.from_user
        LEFT JOIN users ut ON ut.id = t.to_user
        WHERE t.from_user=? OR t.to_user=?
        ORDER BY t.id DESC LIMIT 10
    ");
    $st->execute([$u['id'], $u['id']]);
    $txns = $st->fetchAll();

    page_open('Wallet');
    ?>
<div class="main">
  <?php if ($flag_show): ?>
  <div class="flag-box">
    <h3>🚩 Vulnerability Exploited!</h3>
    <p style="color:#6ee7b7;font-size:.875rem;margin-bottom:10px">
      Your wallet balance exceeds the initial ₹500.00 — you successfully exploited the <strong>negative transfer amount</strong> vulnerability.
      By sending a negative amount, the server's arithmetic <code style="background:#052e1c;padding:2px 6px;border-radius:4px">balance − (−X)</code> credited your wallet instead of debiting it.
    </p>
    <div>Your Flag:</div>
    <div class="flag-val"><?= LAB_FLAG ?></div>
  </div>
  <?php endif; ?>

  <!-- Balance Hero Card -->
  <div class="balance-hero">
    <div class="balance-label">SwiftPay Wallet Balance</div>
    <div class="balance-amount"><?= inr((int)$u['balance']) ?></div>
    <div class="balance-upi">📱 <?= h($u['upi_id']) ?></div>
    <div class="balance-chips">
      <span class="balance-chip">✅ KYC Verified</span>
      <span class="balance-chip">🔒 Insured up to ₹5L</span>
      <span class="balance-chip">📈 0.5% cashback active</span>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="quick-actions">
    <a href="<?= url('transfer') ?>" class="qa-btn"><div class="qa-icon">💸</div><div class="qa-label">Send Money</div></a>
    <a href="<?= url('topup') ?>" class="qa-btn"><div class="qa-icon">➕</div><div class="qa-label">Add Money</div></a>
    <a href="<?= url('recharge') ?>" class="qa-btn"><div class="qa-icon">📱</div><div class="qa-label">Mobile Recharge</div></a>
    <a href="<?= url('bill') ?>" class="qa-btn"><div class="qa-icon">🧾</div><div class="qa-label">Pay Bills</div></a>
  </div>

  <!-- Transactions -->
  <div class="card">
    <div style="padding:20px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between">
      <div><div class="page-title" style="font-size:1rem">Recent Transactions</div></div>
      <a href="<?= url('history') ?>" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div style="padding:0 20px">
      <?php if ($txns): ?>
      <?php foreach ($txns as $t):
        $is_credit = ($t['to_user'] == $u['id']);
        $sign = $is_credit ? '+' : '−';
        $cls  = $is_credit ? 'tx-credit' : 'tx-debit';
        $icon_cls = $is_credit ? 'tx-icon-credit' : 'tx-icon-debit';
        $icon = match($t['type']) { 'topup'=>'💳', 'recharge'=>'📱', 'bill'=>'🧾', default=>($is_credit?'⬇️':'⬆️') };
        $counterpart = $is_credit ? ($t['from_name'] ?? 'SwiftPay') : ($t['to_name'] ?? 'SwiftPay');
        $desc = $t['description'] ?: ucfirst($t['type']);
      ?>
      <div class="tx-item">
        <div class="tx-icon <?= $icon_cls ?>"><?= $icon ?></div>
        <div class="tx-info">
          <div class="tx-desc"><?= h($desc) ?></div>
          <div class="tx-meta"><?= h($counterpart) ?> &nbsp;·&nbsp; <?= date('d M, g:i A', strtotime($t['created_at'])) ?> &nbsp;·&nbsp; <span style="font-family:monospace;font-size:.7rem"><?= h($t['ref'] ?? '') ?></span></div>
        </div>
        <div class="tx-amount <?= $cls ?>"><?= $sign ?><?= inr(abs((int)$t['amount'])) ?></div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div style="padding:40px;text-align:center;color:var(--text3)">No transactions yet. Send or receive money to get started.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: SEND MONEY (TRANSFER)
══════════════════════════════════════════════════════════ */
function page_transfer(): void {
    $u = require_login();
    $u = fresh_user();

    // Prefill from ?to=
    $prefill_to = h($_GET['to'] ?? '');

    // Recent contacts
    $st = db()->prepare("
        SELECT DISTINCT u.name, u.upi_id
        FROM transactions t JOIN users u ON u.id = t.to_user
        WHERE t.from_user=? AND t.type='transfer'
        ORDER BY t.id DESC LIMIT 5
    ");
    $st->execute([$u['id']]);
    $contacts = $st->fetchAll();

    page_open('Send Money');
    ?>
<div class="main" style="max-width:620px;margin-left:auto;margin-right:auto">
  <div style="margin-bottom:24px">
    <div class="page-title">💸 Send Money</div>
    <div class="section-sub">Transfer instantly via UPI ID or mobile number</div>
  </div>

  <div class="card card-pad">
    <!-- VULNERABLE TRANSFER FORM -->
    <!-- POST /transfer — amount accepts negative values (intentional bug) -->
    <form method="POST" action="<?= url('transfer') ?>">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="form-group">
        <label class="form-label">To (UPI ID or Mobile)</label>
        <input type="text" name="to_identifier" class="form-control" required
               value="<?= $prefill_to ?>" placeholder="name@swift or 9XXXXXXXXX" autocomplete="off">
      </div>
      <div class="form-group">
        <label class="form-label">Amount (₹)</label>
        <!-- No min="1" enforced in HTML; server also doesn't block negatives (intentional) -->
        <input type="number" name="amount" class="form-control" required
               placeholder="0.00" step="0.01">
      </div>
      <div class="form-group">
        <label class="form-label">Remark (optional)</label>
        <input type="text" name="remark" class="form-control" placeholder="For coffee ☕" maxlength="100">
      </div>
      <div style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:10px;padding:14px;margin-bottom:16px">
        <div style="font-size:.78rem;font-weight:700;color:#4c1d95;margin-bottom:4px">💳 Paying From</div>
        <div style="font-size:.9rem;font-weight:600"><?= h($u['name']) ?></div>
        <div style="font-size:.78rem;color:var(--text3)"><?= h($u['upi_id']) ?> &nbsp;·&nbsp; Available: <?= inr((int)$u['balance']) ?></div>
      </div>
      <button class="btn btn-primary btn-lg" style="width:100%">Send Money →</button>
    </form>

    <?php if ($contacts): ?>
    <hr class="divider">
    <div style="font-size:.78rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px">Recent Contacts</div>
    <div style="display:flex;flex-direction:column;gap:8px">
      <?php foreach ($contacts as $c): ?>
      <a href="<?= url('transfer') ?>?to=<?= urlencode($c['upi_id']) ?>"
         style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:#f9f8ff;border:1px solid var(--border);border-radius:9px;color:var(--text)">
        <div style="width:36px;height:36px;background:var(--bg3);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:800;color:var(--p)">
          <?= strtoupper(substr($c['name'],0,1)) ?>
        </div>
        <div>
          <div style="font-weight:600;font-size:.875rem"><?= h($c['name']) ?></div>
          <div style="font-size:.75rem;color:var(--text3)"><?= h($c['upi_id']) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="card card-pad mt-16" style="background:#fff7ed;border-color:#fed7aa">
    <div style="font-size:.78rem;font-weight:700;color:#92400e;margin-bottom:6px">💡 Lab Hint</div>
    <p style="font-size:.82rem;color:#78350f;line-height:1.6">
      The <code style="background:#fff;padding:2px 6px;border-radius:4px">amount</code> field uses
      <code style="background:#fff;padding:2px 6px;border-radius:4px">FILTER_VALIDATE_FLOAT</code> server-side.
      Try sending a <strong>negative amount</strong> via Burp Suite:<br>
      <code style="background:#fff;padding:3px 8px;border-radius:4px;display:inline-block;margin-top:6px">POST /transfer<br>to_identifier=admin@swiftpay&amount=-5000&_csrf=&lt;token&gt;</code>
    </p>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: ADD MONEY
══════════════════════════════════════════════════════════ */
function page_topup(): void {
    require_login();
    page_open('Add Money');
    ?>
<div class="main" style="max-width:540px;margin-left:auto;margin-right:auto">
  <div class="page-title mb-24" style="margin-bottom:20px">➕ Add Money to Wallet</div>
  <div class="card card-pad">
    <form method="POST" action="<?= url('topup') ?>">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px">
        <?php foreach ([10000,20000,50000,100000] as $amt): ?>
        <button type="submit" name="amount" value="<?= $amt ?>" class="btn btn-secondary" style="font-size:.9rem">
          <?= inr($amt) ?>
        </button>
        <?php endforeach; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Custom Amount (₹)</label>
        <input type="number" name="amount" class="form-control" placeholder="Enter amount" min="100" max="10000000" step="100">
      </div>
      <div class="form-group">
        <label class="form-label">Card Number (Test Mode)</label>
        <input type="text" class="form-control" value="4111 1111 1111 1111" readonly style="color:var(--text3)">
      </div>
      <div class="grid-2">
        <div class="form-group"><label class="form-label">Expiry</label><input type="text" class="form-control" value="12/28" readonly style="color:var(--text3)"></div>
        <div class="form-group"><label class="form-label">CVV</label><input type="text" class="form-control" value="***" readonly style="color:var(--text3)"></div>
      </div>
      <button class="btn btn-primary btn-lg" style="width:100%">Add Money</button>
    </form>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: RECHARGE
══════════════════════════════════════════════════════════ */
function page_recharge(): void {
    require_login();
    $u = fresh_user();
    page_open('Mobile Recharge');
    ?>
<div class="main" style="max-width:540px;margin-left:auto;margin-right:auto">
  <div class="page-title" style="margin-bottom:20px">📱 Mobile Recharge</div>
  <div class="card card-pad">
    <form method="POST" action="<?= url('recharge') ?>">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="form-group">
        <label class="form-label">Mobile Number</label>
        <input type="tel" name="phone" class="form-control" placeholder="10-digit mobile number" maxlength="10" required>
      </div>
      <div class="form-group">
        <label class="form-label">Operator</label>
        <select name="operator" class="form-control">
          <?php foreach (['Jio'=>'Jio','Airtel'=>'Airtel','Vi'=>'Vi (Vodafone Idea)','BSNL'=>'BSNL'] as $v=>$l): ?>
          <option value="<?= $v ?>"><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:16px">
        <?php foreach ([['239','239','1GB/day · 28 days'],['479','479','2GB/day · 56 days'],['699','699','2GB/day · 84 days']] as [$v,$p,$d]): ?>
        <button type="submit" name="amount" value="<?= $v*100 ?>" class="btn btn-secondary" style="flex-direction:column;gap:2px;padding:10px;height:auto">
          <span style="font-weight:800">₹<?= $p ?></span>
          <span style="font-size:.7rem;color:var(--text3)"><?= $d ?></span>
        </button>
        <?php endforeach; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Wallet Balance: <?= inr((int)$u['balance']) ?></label>
      </div>
      <button class="btn btn-primary btn-lg" style="width:100%">Recharge Now</button>
    </form>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: PAY BILLS
══════════════════════════════════════════════════════════ */
function page_bill(): void {
    require_login();
    $u = fresh_user();
    page_open('Pay Bills');
    ?>
<div class="main" style="max-width:540px;margin-left:auto;margin-right:auto">
  <div class="page-title" style="margin-bottom:20px">🧾 Pay Bills</div>
  <div class="card card-pad">
    <form method="POST" action="<?= url('bill') ?>">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="form-group">
        <label class="form-label">Bill Type</label>
        <select name="bill_type" class="form-control">
          <?php foreach (['electricity'=>'Electricity','water'=>'Water / Sewage','gas'=>'Piped Gas','broadband'=>'Broadband','dth'=>'DTH / Cable TV'] as $v=>$l): ?>
          <option value="<?= $v ?>"><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Consumer / Account Number</label>
        <input type="text" name="account_no" class="form-control" placeholder="Enter consumer number" required>
      </div>
      <div class="form-group">
        <label class="form-label">Amount (₹)</label>
        <input type="number" name="amount" class="form-control" placeholder="0.00" min="100" step="100" required>
      </div>
      <div class="form-group">
        <label class="form-label">Wallet Balance: <?= inr((int)$u['balance']) ?></label>
      </div>
      <button class="btn btn-primary btn-lg" style="width:100%">Pay Bill</button>
    </form>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: TRANSACTION HISTORY
══════════════════════════════════════════════════════════ */
function page_history(): void {
    $u = require_login();
    $st = db()->prepare("
        SELECT t.*, uf.name AS from_name, uf.upi_id AS from_upi,
                     ut.name AS to_name, ut.upi_id AS to_upi
        FROM transactions t
        LEFT JOIN users uf ON uf.id = t.from_user
        LEFT JOIN users ut ON ut.id = t.to_user
        WHERE t.from_user=? OR t.to_user=?
        ORDER BY t.id DESC
    ");
    $st->execute([$u['id'], $u['id']]);
    $txns = $st->fetchAll();

    page_open('Transaction History');
    echo '<div class="main">';
    echo '<div style="margin-bottom:20px"><div class="page-title">📋 Transaction History</div><div class="section-sub">'.count($txns).' transactions</div></div>';
    echo '<div class="card"><table class="table"><thead><tr><th>Date</th><th>Description</th><th>Type</th><th>Counterpart</th><th>Ref</th><th>Amount</th></tr></thead><tbody>';
    foreach ($txns as $t) {
        $is_credit = ($t['to_user'] == $u['id']);
        $sign = $is_credit ? '+' : '−';
        $color = $is_credit ? 'var(--green)' : 'var(--red)';
        $counterpart = $is_credit ? ($t['from_name']??'SwiftPay') : ($t['to_name']??'SwiftPay');
        echo "<tr><td style='font-size:.78rem;color:var(--text3)'>".date('d M Y<br>g:i A',strtotime($t['created_at']))."</td>";
        echo "<td style='font-weight:600'>".h($t['description']?:ucfirst($t['type']))."</td>";
        echo "<td><span class='tag tag-info'>".h($t['type'])."</span></td>";
        echo "<td>".h($counterpart)."</td>";
        echo "<td style='font-family:monospace;font-size:.75rem;color:var(--text3)'>".h($t['ref']??'')."</td>";
        echo "<td style='font-weight:700;color:$color'>$sign".inr(abs((int)$t['amount']))."</td></tr>";
    }
    echo '</tbody></table></div></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: PROFILE
══════════════════════════════════════════════════════════ */
function page_profile_page(): void {
    $u = require_login();
    $u = fresh_user();
    page_open('Profile');
    ?>
<div class="main" style="max-width:540px;margin-left:auto;margin-right:auto">
  <div class="page-title" style="margin-bottom:20px">👤 My Profile</div>
  <div class="card card-pad">
    <form method="POST" action="<?= url('profile') ?>">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="form-group"><label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" value="<?= h($u['name']) ?>" required></div>
      <div class="form-group"><label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= h($u['email']) ?>" required></div>
      <div class="form-group"><label class="form-label">Mobile</label>
        <input type="text" class="form-control" value="<?= h($u['phone']) ?>" readonly style="color:var(--text3)"></div>
      <div class="form-group"><label class="form-label">UPI ID</label>
        <input type="text" class="form-control" value="<?= h($u['upi_id']) ?>" readonly style="color:var(--text3)"></div>
      <div class="form-group"><label class="form-label">New Password <span style="font-weight:400;text-transform:none">(leave blank to keep)</span></label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" minlength="6"></div>
      <button class="btn btn-primary" style="width:100%">Save Changes</button>
    </form>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGES: AUTH
══════════════════════════════════════════════════════════ */
function page_login(): void {
    if (user()) redirect('');
    page_open('Sign In');
    ?>
<div class="main" style="max-width:420px;margin-left:auto;margin-right:auto">
  <div style="text-align:center;margin-bottom:28px;padding-top:12px">
    <div style="font-size:3rem;margin-bottom:8px">💜</div>
    <div style="font-size:1.5rem;font-weight:900;color:var(--p)">Welcome to SwiftPay</div>
    <div style="color:var(--text3);font-size:.875rem;margin-top:4px">India Ka Digital Wallet</div>
  </div>
  <div class="card card-pad">
    <form method="POST" action="<?= url('login') ?>">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="form-group">
        <label class="form-label">Mobile Number or Email</label>
        <input type="text" name="identifier" class="form-control" required autofocus placeholder="9XXXXXXXXX or email@example.com">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required placeholder="••••••••">
      </div>
      <button class="btn btn-primary btn-lg" style="width:100%">Sign In</button>
    </form>
    <div style="text-align:center;margin-top:16px;font-size:.85rem;color:var(--text3)">
      New to SwiftPay? <a href="<?= url('register') ?>">Create account</a>
    </div>
    <div style="margin-top:16px;padding:12px;background:#f5f3ff;border-radius:9px;font-size:.75rem;color:var(--text3);line-height:1.6">
      <strong style="color:var(--p)">Demo Accounts:</strong><br>
      Admin: admin@swiftpay.app / admin123<br>
      Priya: priya@example.com / priya123<br>
      Demo: demo@swiftpay.lab / demo123
    </div>
  </div>
</div>
<?php page_close();
}

function page_register(): void {
    if (user()) redirect('');
    page_open('Register');
    ?>
<div class="main" style="max-width:440px;margin-left:auto;margin-right:auto">
  <div class="page-title" style="margin-bottom:20px;text-align:center">Create SwiftPay Account</div>
  <div class="card card-pad">
    <form method="POST" action="<?= url('register') ?>">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="form-group"><label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Mobile Number</label>
        <input type="tel" name="phone" class="form-control" required maxlength="10" placeholder="10-digit mobile"></div>
      <div class="form-group"><label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required minlength="6"></div>
      <button class="btn btn-primary btn-lg" style="width:100%">Create Account</button>
    </form>
    <div style="text-align:center;margin-top:14px;font-size:.85rem;color:var(--text3)">
      Already have an account? <a href="<?= url('login') ?>">Sign In</a>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: ADMIN
══════════════════════════════════════════════════════════ */
function page_admin(): void {
    $u = require_login();
    if (!$u['is_admin']) { flash('error','Access denied.'); redirect(''); }

    $users = db()->query("SELECT id,name,phone,email,upi_id,balance,created_at FROM users ORDER BY id")->fetchAll();
    $total_bal = array_sum(array_column($users,'balance'));
    $tx_count = (int)db()->query("SELECT COUNT(*) FROM transactions")->fetchColumn();

    page_open('Admin — SwiftPay');
    echo '<div class="main">';
    echo '<div class="page-title" style="margin-bottom:20px">⚙️ Admin Panel</div>';
    echo '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">';
    foreach ([['👥',count($users),'Users'],['💰',inr($total_bal),'Total Balances'],['📋',$tx_count,'Transactions']] as [$i,$v,$l])
        echo "<div class='card card-pad' style='text-align:center'><div style='font-size:2rem'>$i</div><div style='font-size:1.4rem;font-weight:800;margin:6px 0'>$v</div><div style='font-size:.8rem;color:var(--text3)'>$l</div></div>";
    echo '</div>';
    echo '<div class="card"><div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;font-weight:700">All Users</div>';
    echo '<table class="table"><thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>UPI ID</th><th>Balance</th><th>Joined</th></tr></thead><tbody>';
    foreach ($users as $u2)
        echo "<tr><td>#{$u2['id']}</td><td style='font-weight:600'>".h($u2['name'])."</td><td>".h($u2['phone'])."</td><td style='font-family:monospace;font-size:.8rem'>".h($u2['upi_id'])."</td><td style='font-weight:700'>".inr((int)$u2['balance'])."</td><td style='font-size:.78rem;color:var(--text3)'>".date('d M Y',strtotime($u2['created_at']))."</td></tr>";
    echo '</tbody></table></div></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: INSTALL / RESET
══════════════════════════════════════════════════════════ */
function page_install(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        install_schema(db(), true);
        flash('success','Database reset successfully.');
        redirect('login');
    }
    page_open('Reset Lab');
    ?>
<div class="main" style="max-width:460px;margin-left:auto;margin-right:auto">
  <div class="card card-pad" style="text-align:center">
    <div style="font-size:2.5rem;margin-bottom:12px">⚗️</div>
    <div class="page-title">Reset Lab Database</div>
    <p style="color:var(--text3);margin:12px 0 20px;font-size:.875rem">Drops and recreates all tables. All orders and user data will be lost.</p>
    <form method="POST">
      <button class="btn btn-danger btn-lg" style="width:100%">Reset Database</button>
    </form>
    <a href="<?= url('login') ?>" class="btn btn-secondary" style="width:100%;margin-top:10px;text-align:center">Cancel</a>
  </div>
</div>
<?php page_close();
}

function page_404(): void {
    http_response_code(404);
    page_open('404');
    echo '<div class="main" style="text-align:center;padding:80px"><div style="font-size:3rem;margin-bottom:12px">🔍</div><div class="page-title">Page Not Found</div><a href="'.url().'" class="btn btn-primary mt-16" style="margin-top:16px">Go Home</a></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   ACTIONS
══════════════════════════════════════════════════════════ */
function action_login(): void {
    verify_csrf();
    $id   = trim($_POST['identifier'] ?? '');
    $pass = $_POST['password'] ?? '';
    $st = db()->prepare("SELECT * FROM users WHERE email=? OR phone=?");
    $st->execute([$id, $id]);
    $u = $st->fetch();
    if (!$u || !password_verify($pass, $u['password_hash']))
        { flash('error','Invalid credentials.'); redirect('login'); }
    session_regenerate_id(true);
    $_SESSION['uid'] = $u['id'];
    flash('success','Welcome back, '.$u['name'].'!');
    redirect('');
}

function action_register(): void {
    verify_csrf();
    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!$name || !$phone || !$email || !$pass || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($phone) !== 10)
        { flash('error','Please fill all fields correctly.'); redirect('register'); }
    // Generate UPI ID from name + phone suffix
    $slug = preg_replace('/[^a-z0-9]/', '', strtolower($name)) . substr($phone,-4);
    $upi  = $slug . '@swift';
    try {
        $st = db()->prepare("INSERT INTO users (name,phone,email,upi_id,password_hash,balance) VALUES (?,?,?,?,?,?)");
        $st->execute([$name,$phone,$email,$upi,password_hash($pass,PASSWORD_DEFAULT),SEED_BAL]);
        $uid = (int)db()->lastInsertId();
        // Welcome top-up transaction
        db()->prepare("INSERT INTO transactions (to_user,amount,type,description,ref) VALUES (?,?,?,?,?)")
            ->execute([$uid, SEED_BAL, 'topup', 'Welcome bonus', 'WELCOME'.rand(100000,999999)]);
        session_regenerate_id(true);
        $_SESSION['uid'] = $uid;
        flash('success','Account created! ₹500 welcome balance added.');
        redirect('');
    } catch (PDOException) { flash('error','Email or phone already registered.'); redirect('register'); }
}

function action_logout(): void {
    verify_csrf();
    session_destroy();
    header('Location: '.url('login')); exit;
}

/*
 * INTENTIONALLY VULNERABLE TRANSFER ENDPOINT
 * POST /transfer  param: amount
 *
 * Bug: FILTER_VALIDATE_FLOAT accepts negative values.
 * Balance check: ($u['balance'] >= $amount_paise) is trivially true when amount is negative.
 * SQL: UPDATE wallets SET balance = balance - (-X) → balance + X  (steals money)
 */
function action_transfer(): void {
    $u = require_login();
    verify_csrf();

    $to_id = trim($_POST['to_identifier'] ?? '');
    $remark = trim($_POST['remark'] ?? '');

    // --- INTENTIONAL BUG: FILTER_VALIDATE_FLOAT accepts negatives ---
    $amount_raw = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
    if ($amount_raw === false) { flash('error','Enter a valid amount.'); redirect('transfer'); }

    // Convert to paise (integer) — negative preserved
    $amount_paise = (int)round($amount_raw * 100);
    if ($amount_paise === 0) { flash('error','Amount cannot be zero.'); redirect('transfer'); }

    // Find recipient
    $rst = db()->prepare("SELECT * FROM users WHERE upi_id=? OR phone=?");
    $rst->execute([$to_id, $to_id]);
    $recipient = $rst->fetch();
    if (!$recipient) { flash('error','UPI ID or mobile not found.'); redirect('transfer'); }
    if ($recipient['id'] === (int)$u['id']) { flash('error','Cannot transfer to yourself.'); redirect('transfer'); }

    // Fresh sender balance
    $sender_st = db()->prepare("SELECT balance FROM users WHERE id=?");
    $sender_st->execute([$u['id']]);
    $sender = $sender_st->fetch();

    // --- INTENTIONAL BUG: balance check passes when amount is negative ---
    // (any real balance >= a negative number)
    if ((int)$sender['balance'] < $amount_paise) {
        flash('error', 'Insufficient balance. Available: ' . inr((int)$sender['balance']));
        redirect('transfer');
    }

    // --- INTENTIONAL BUG: balance - (-X) = balance + X ---
    db()->prepare("UPDATE users SET balance = balance - ? WHERE id=?")->execute([$amount_paise, $u['id']]);
    db()->prepare("UPDATE users SET balance = balance + ? WHERE id=?")->execute([$amount_paise, $recipient['id']]);

    $ref = 'T'.gen_ref();
    $desc = $remark ?: 'Transfer to '.$recipient['name'];
    db()->prepare("INSERT INTO transactions (from_user,to_user,amount,type,description,ref) VALUES (?,?,?,?,?,?)")
        ->execute([$u['id'], $recipient['id'], abs($amount_paise), 'transfer', $desc, $ref]);

    if ($amount_raw < 0) {
        flash('warning', 'Transfer processed. Note: negative amounts affect balances inversely.');
    } else {
        flash('success', inr($amount_paise).' sent to '.$recipient['name'].'. Ref: '.$ref);
    }
    redirect('');
}

function action_topup(): void {
    $u = require_login();
    verify_csrf();
    $amount = max(100, min(10000000, (int)($_POST['amount'] ?? 0)));
    db()->prepare("UPDATE users SET balance = balance + ? WHERE id=?")->execute([$amount, $u['id']]);
    $ref = 'TU'.gen_ref();
    db()->prepare("INSERT INTO transactions (to_user,amount,type,description,ref) VALUES (?,?,?,?,?)")
        ->execute([$u['id'], $amount, 'topup', 'Wallet top-up via card', $ref]);
    flash('success', inr($amount).' added to wallet. Ref: '.$ref);
    redirect('');
}

function action_recharge(): void {
    $u = require_login();
    verify_csrf();
    $amount = (int)($_POST['amount'] ?? 0);
    $phone  = preg_replace('/\D/','', $_POST['phone'] ?? '');
    $op     = htmlspecialchars($_POST['operator'] ?? 'Unknown');
    if ($amount <= 0) { flash('error','Select or enter amount.'); redirect('recharge'); }
    $sender = db()->prepare("SELECT balance FROM users WHERE id=?")->execute([$u['id']]) ? null : null;
    $st = db()->prepare("SELECT balance FROM users WHERE id=?"); $st->execute([$u['id']]); $bal = (int)$st->fetchColumn();
    if ($bal < $amount) { flash('error','Insufficient balance.'); redirect('recharge'); }
    db()->prepare("UPDATE users SET balance = balance - ? WHERE id=?")->execute([$amount, $u['id']]);
    $ref = 'RC'.gen_ref();
    db()->prepare("INSERT INTO transactions (from_user,amount,type,description,ref) VALUES (?,?,?,?,?)")
        ->execute([$u['id'], $amount, 'recharge', "$op recharge for $phone", $ref]);
    flash('success', 'Recharge of '.inr($amount).' for '.$phone.' successful! Ref: '.$ref);
    redirect('');
}

function action_bill(): void {
    $u = require_login();
    verify_csrf();
    $amount  = (int)(floatval($_POST['amount'] ?? 0) * 100);
    $type    = htmlspecialchars($_POST['bill_type'] ?? 'bill');
    $acc_no  = htmlspecialchars($_POST['account_no'] ?? '');
    if ($amount <= 0) { flash('error','Enter a valid amount.'); redirect('bill'); }
    $st = db()->prepare("SELECT balance FROM users WHERE id=?"); $st->execute([$u['id']]); $bal = (int)$st->fetchColumn();
    if ($bal < $amount) { flash('error','Insufficient balance.'); redirect('bill'); }
    db()->prepare("UPDATE users SET balance = balance - ? WHERE id=?")->execute([$amount, $u['id']]);
    $ref = 'BL'.gen_ref();
    db()->prepare("INSERT INTO transactions (from_user,amount,type,description,ref) VALUES (?,?,?,?,?)")
        ->execute([$u['id'], $amount, 'bill', ucfirst($type).' bill for '.$acc_no, $ref]);
    flash('success', ucfirst($type).' bill paid: '.inr($amount).'. Ref: '.$ref);
    redirect('');
}

function action_profile(): void {
    $u = require_login();
    verify_csrf();
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!$name || !$email || !filter_var($email,FILTER_VALIDATE_EMAIL)) { flash('error','Invalid name or email.'); redirect('profile'); }
    try {
        if ($pass && strlen($pass) >= 6)
            db()->prepare("UPDATE users SET name=?,email=?,password_hash=? WHERE id=?")->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$u['id']]);
        else
            db()->prepare("UPDATE users SET name=?,email=? WHERE id=?")->execute([$name,$email,$u['id']]);
        flash('success','Profile updated.');
    } catch (PDOException) { flash('error','Email already in use.'); }
    redirect('profile');
}

function action_install(): void {
    install_schema(db(), true);
    flash('success','Database reset.');
    redirect('login');
}
