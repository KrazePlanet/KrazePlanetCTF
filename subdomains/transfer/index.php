<?php
session_start();

if (empty($_SESSION['romit_user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

// Load logged-in user
$current_user = null;
$stmt_u = $conn->prepare("SELECT id, email, nickname FROM lab57914_users WHERE id = ?");
$stmt_u->bind_param('i', $_SESSION['romit_user_id']);
$stmt_u->execute();
$res_u = $stmt_u->get_result();
$current_user = $res_u->fetch_assoc();
$stmt_u->close();
if (!$current_user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

/**
 * Build the invitation email HTML body. $nickname is echoed RAW — intentionally
 * vulnerable (stored HTML injection, HackerOne #57914).
 */
function renderInviteBody($nickname) {
    return '
    <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.05);font-family:Arial,sans-serif;">
      <div style="background:#6c3be4;padding:20px;text-align:center;">
        <h2 style="color:#ffffff;margin:0;font-size:20px;">Romit</h2>
      </div>
      <div style="padding:28px 24px;">
        <h3 style="color:#1a1a2e;margin-top:0;font-size:18px;">You have been invited to Romit!</h3>
        <p style="font-size:14px;color:#4a5568;line-height:1.6;">Hi there,</p>
        <p style="font-size:14px;color:#4a5568;line-height:1.6;">A Romit wallet has been shared with you. The user: <strong>' . $nickname . '</strong> has shared their wallet with you and invited you to join Romit.</p>
        <p style="font-size:14px;color:#4a5568;line-height:1.6;">Click below to accept the wallet invitation and get started:</p>
        <div style="margin:24px 0;">
          <a href="#" style="background:#6c3be4;color:#ffffff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;display:inline-block;">Accept Wallet Invitation</a>
        </div>
        <p style="font-size:13px;color:#a0aec0;">If you did not expect this invitation, you can safely ignore this email.</p>
        <hr style="border:none;border-top:1px solid #edf2f7;margin:24px 0;">
        <div style="font-size:12px;color:#a0aec0;text-align:center;">
          © 2015 Romit, Inc. · 535 Mission St, San Francisco, CA
        </div>
      </div>
    </div>';
}

// ── DB is already connected via config/database.php ─────────────────────────
$db      = $conn;
$dbError = $conn ? '' : 'DB connection failed.';

// ── Handle Share Wallet ─────────────────────────────────────────────────────
$share_result = null; // true = sent, false = failed, null = not attempted
$share_email  = '';

// Nickname from DB (source of truth)
$nickname = $current_user['nickname'] !== null && $current_user['nickname'] !== ''
    ? $current_user['nickname'] : 'Romit';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'share_wallet') {
    $share_email = trim($_POST['phone'] ?? '');

    if ($share_email) {
        // Persist invitation record (nickname stored raw — intentionally vulnerable)
        if ($db) {
            $stmt = mysqli_prepare($db, "INSERT INTO lab57914_invites (from_email, to_email, nickname) VALUES (?, ?, ?)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sss', $current_user['email'], $share_email, $nickname);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        // Send real invitation email — nickname is embedded RAW (the vulnerability)
        $share_result = sendWalletInvite($share_email, $nickname);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Romit — Send Money Instantly</title>
<style>
*,*:before,*:after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f3f4f8;color:#1a1a2e;min-height:100vh;display:flex;flex-direction:column;}

/* ── Header ───────────────────────────────────────────────────────────────── */
.header{background:#6c3be4;height:56px;display:flex;align-items:center;padding:0 28px;box-shadow:0 2px 10px rgba(108,59,228,.35);flex-shrink:0;}
.header-logo{display:flex;align-items:center;gap:9px;text-decoration:none;margin-right:32px;}
.logo-mark{width:32px;height:32px;background:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;}
.logo-mark svg{width:20px;height:20px;}
.logo-text{color:#fff;font-size:1.1rem;font-weight:800;letter-spacing:-.02em;}
.header-nav{display:flex;gap:2px;flex:1;}
.header-nav a{color:rgba(255,255,255,.75);font-size:.78rem;font-weight:500;text-decoration:none;padding:7px 12px;border-radius:4px;transition:background .15s,color .15s;}
.header-nav a:hover,.header-nav a.active{background:rgba(255,255,255,.15);color:#fff;}
.header-right{display:flex;gap:8px;align-items:center;}
.hdr-avatar{width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,.25);border:2px solid rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.72rem;font-weight:700;cursor:pointer;}
.hdr-balance{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:20px;padding:4px 12px;color:#fff;font-size:.75rem;font-weight:600;}

/* ── Layout ───────────────────────────────────────────────────────────────── */
.layout{display:flex;flex:1;max-width:1440px;margin:0 auto;width:100%;padding:24px 16px;gap:20px;align-items:flex-start;}
@media(max-width:1100px){.layout{flex-wrap:wrap;}}

/* ── Sidebar ──────────────────────────────────────────────────────────────── */
.sidebar{width:220px;flex-shrink:0;display:flex;flex-direction:column;gap:12px;}
.wallet-card{background:linear-gradient(135deg,#6c3be4 0%,#9b6ff5 100%);border-radius:14px;padding:20px;color:#fff;box-shadow:0 4px 16px rgba(108,59,228,.3);}
.wallet-label{font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;opacity:.75;margin-bottom:6px;}
.wallet-amount{font-size:1.8rem;font-weight:800;letter-spacing:-.02em;margin-bottom:2px;}
.wallet-sub{font-size:.7rem;opacity:.65;}
.wallet-actions{display:flex;gap:6px;margin-top:14px;}
.wact-btn{flex:1;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);border-radius:6px;padding:7px 4px;text-align:center;font-size:.68rem;font-weight:700;color:#fff;cursor:pointer;transition:background .15s;}
.wact-btn:hover{background:rgba(255,255,255,.3);}
.sidebar-nav{background:#fff;border-radius:10px;padding:6px;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.snav-item{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:7px;font-size:.78rem;font-weight:500;color:#5a6a8a;cursor:pointer;transition:background .12s,color .12s;text-decoration:none;}
.snav-item:hover{background:#f3f0fd;color:#6c3be4;}
.snav-item.active{background:#f3f0fd;color:#6c3be4;font-weight:600;}
.snav-item svg{width:16px;height:16px;flex-shrink:0;}
.sidebar-tx{background:#fff;border-radius:10px;padding:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.tx-title{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#b0bdd0;margin-bottom:10px;}
.tx-item{display:flex;align-items:center;gap:9px;padding:6px 0;border-bottom:1px solid #f3f4f8;}
.tx-item:last-child{border-bottom:none;}
.tx-avatar{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:#fff;flex-shrink:0;}
.tx-info{flex:1;min-width:0;}
.tx-name{font-size:.74rem;font-weight:600;color:#1a1a2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.tx-date{font-size:.62rem;color:#b0bdd0;}
.tx-amount{font-size:.76rem;font-weight:700;}

/* ── Main ─────────────────────────────────────────────────────────────────── */
.main{flex:1;display:flex;flex-direction:column;gap:16px;}

/* ── Page title ───────────────────────────────────────────────────────────── */
.page-title{font-size:1.1rem;font-weight:700;color:#1a1a2e;}
.page-sub{font-size:.78rem;color:#8a9ab8;margin-top:2px;}

/* ── Card ─────────────────────────────────────────────────────────────────── */
.card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.07);overflow:hidden;}
.card-header{padding:16px 20px;border-bottom:1px solid #f3f4f8;display:flex;align-items:center;gap:10px;}
.card-header-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;}
.card-header-title{font-size:.88rem;font-weight:700;color:#1a1a2e;}
.card-header-sub{font-size:.72rem;color:#8a9ab8;margin-top:1px;}
.card-body{padding:20px;}

/* ── Form elements ────────────────────────────────────────────────────────── */
.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:.76rem;font-weight:600;color:#3a4a6a;margin-bottom:6px;}
.form-input{width:100%;padding:9px 12px;border:1.5px solid #dce3ef;border-radius:7px;font-size:.84rem;color:#1a1a2e;background:#fff;outline:none;font-family:inherit;transition:border-color .15s,box-shadow .15s;}
.form-input:focus{border-color:#6c3be4;box-shadow:0 0 0 3px rgba(108,59,228,.12);}
.form-hint{font-size:.68rem;color:#b0bdd0;margin-top:4px;}
.form-row{display:flex;gap:10px;}
.form-row .form-group{flex:1;}
.btn{border:none;border-radius:7px;padding:9px 20px;font-size:.82rem;font-weight:700;cursor:pointer;font-family:inherit;transition:opacity .15s,transform .1s;}
.btn:hover{opacity:.88;}
.btn:active{transform:scale(.98);}
.btn-primary{background:#6c3be4;color:#fff;}
.btn-outline{background:#fff;border:1.5px solid #dce3ef;color:#5a6a8a;}
.btn-outline:hover{border-color:#6c3be4;color:#6c3be4;opacity:1;}
.btn-success{background:#10b981;color:#fff;}

/* ── Alert ────────────────────────────────────────────────────────────────── */
.alert{padding:10px 14px;border-radius:7px;font-size:.78rem;font-weight:500;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.alert-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;}
.alert-info{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;}

/* ── Gmail-style Inbox (recipient view) ───────────────────────────────────── */
.inbox{width:400px;flex-shrink:0;background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.07);overflow:hidden;align-self:stretch;display:flex;flex-direction:column;}
@media(max-width:1100px){.inbox{width:100%;}}
.inbox-top{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid #f1f3f4;}
.inbox-glogo{font-size:1.05rem;font-weight:700;color:#5f6368;display:flex;align-items:center;gap:6px;}
.inbox-glogo .g-m{color:#ea4335;}.inbox-glogo .g-a1{color:#fbbc05;}.inbox-glogo .g-i{color:#34a853;}.inbox-glogo .g-l{color:#4285f4;}.inbox-glogo .g-a2{color:#34a853;}.inbox-glogo .g-i2{color:#ea4335;}.inbox-glogo .g-l2{color:#fbbc05;}
.inbox-search{flex:1;background:#eaf1fb;border-radius:8px;padding:7px 12px;font-size:.74rem;color:#80868b;display:flex;align-items:center;gap:8px;}
.inbox-tabs{display:flex;padding:0 6px;border-bottom:1px solid #f1f3f4;}
.inbox-tab{padding:10px 16px;font-size:.76rem;font-weight:600;color:#5f6368;border-bottom:3px solid transparent;}
.inbox-tab.active{color:#1a73e8;border-bottom-color:#1a73e8;}
.inbox-list{flex:1;overflow-y:auto;}
.inbox-empty{padding:48px 24px;text-align:center;color:#9aa0a6;font-size:.82rem;}
.inbox-empty svg{width:40px;height:40px;stroke:#dadce0;margin-bottom:12px;}
details.mail{border-bottom:1px solid #f1f3f4;}
summary.mail-row{list-style:none;display:flex;align-items:center;gap:12px;padding:12px 16px;cursor:pointer;transition:box-shadow .12s,background .12s;}
summary.mail-row::-webkit-details-marker{display:none;}
summary.mail-row:hover{box-shadow:inset 1px 0 0 #dadce0,inset -1px 0 0 #dadce0,0 1px 2px rgba(60,64,67,.15);z-index:1;}
details.mail[open] summary.mail-row{background:#f2f6fc;font-weight:600;}
.mail-avatar{width:36px;height:36px;border-radius:50%;background:#6c3be4;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:700;flex-shrink:0;}
.mail-meta{flex:1;min-width:0;}
.mail-from{font-size:.8rem;color:#202124;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.mail-subject{font-size:.76rem;color:#202124;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.mail-snippet{font-size:.72rem;color:#5f6368;}
.mail-time{font-size:.68rem;color:#5f6368;flex-shrink:0;}
.mail-body{padding:16px 18px 22px;background:#f8f9fb;border-top:1px solid #f1f3f4;}
.mail-to{font-size:.72rem;color:#5f6368;margin-bottom:12px;}
.mail-to strong{color:#202124;}
</style>
</head>
<body>

<!-- Header -->
<header class="header">
  <a href="index.php" class="header-logo">
    <div class="logo-mark">
      <svg viewBox="0 0 24 24" fill="none" stroke="#6c3be4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    </div>
    <span class="logo-text">Romit</span>
  </a>
  <nav class="header-nav">
    <a href="#">Dashboard</a>
    <a href="#">Send</a>
    <a href="#">Request</a>
    <a href="profile.php">Profile</a>
    <a href="index.php" class="active">Settings</a>
  </nav>
  <div class="header-right">
    <span class="hdr-balance">$124.50</span>
    <div class="hdr-avatar" title="<?= htmlspecialchars($current_user['email'], ENT_QUOTES) ?>">
      <?= htmlspecialchars(strtoupper(substr($current_user['email'], 0, 1)), ENT_QUOTES) ?>
    </div>
    <a href="logout.php" style="color:rgba(255,255,255,.75);font-size:.75rem;font-weight:600;text-decoration:none;margin-left:4px;padding:5px 10px;border-radius:4px;transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.15)'" onmouseout="this.style.background=''">Log out</a>
  </div>
</header>

<div class="layout">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="wallet-card">
      <div class="wallet-label">Wallet Balance</div>
      <div class="wallet-amount">$124.50</div>
      <div class="wallet-sub">Available to send</div>
      <div class="wallet-actions">
        <div class="wact-btn">Send</div>
        <div class="wact-btn">Request</div>
        <div class="wact-btn">Add</div>
      </div>
    </div>

    <div class="sidebar-nav">
      <a class="snav-item" href="#"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
      <a class="snav-item" href="#"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>Send Money</a>
      <a class="snav-item" href="#"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>Request Money</a>
      <a class="snav-item" href="profile.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profile</a>
      <a class="snav-item active" href="index.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>Settings</a>
    </div>

    <div class="sidebar-tx">
      <div class="tx-title">Recent</div>
      <div class="tx-item">
        <div class="tx-avatar" style="background:#f59e0b;">J</div>
        <div class="tx-info"><div class="tx-name">Jamie L.</div><div class="tx-date">Today, 10:12am</div></div>
        <div class="tx-amount" style="color:#10b981;">+$25.00</div>
      </div>
      <div class="tx-item">
        <div class="tx-avatar" style="background:#8b5cf6;">S</div>
        <div class="tx-info"><div class="tx-name">Sara K.</div><div class="tx-date">Yesterday</div></div>
        <div class="tx-amount" style="color:#ef4444;">-$12.00</div>
      </div>
      <div class="tx-item">
        <div class="tx-avatar" style="background:#10b981;">M</div>
        <div class="tx-info"><div class="tx-name">Mike T.</div><div class="tx-date">May 10</div></div>
        <div class="tx-amount" style="color:#10b981;">+$50.00</div>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <main class="main">
    <div>
      <h1 class="page-title">Settings</h1>
      <p class="page-sub">Share your Romit wallet with friends and family.</p>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px;">
      <!-- Share Wallet card -->
      <div class="card">
        <div class="card-header">
          <div class="card-header-icon" style="background:#ecfdf5;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.66A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
          </div>
          <div>
            <div class="card-header-title">Share Your Wallet</div>
            <div class="card-header-sub">Send a wallet invitation by email</div>
          </div>
        </div>
        <div class="card-body">
          <?php if ($share_result === true): ?>
            <div class="alert alert-success">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
              Invitation sent to <strong><?= htmlspecialchars($share_email, ENT_QUOTES, 'UTF-8') ?></strong>.
            </div>
          <?php elseif ($share_result === false): ?>
            <div class="alert" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Failed to send invitation. Please try again.
            </div>
          <?php endif; ?>

          <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
            <input type="hidden" name="action" value="share_wallet">
            <div class="form-group">
              <label class="form-label" for="phone">Recipient Email Address</label>
              <input class="form-input" type="email" id="phone" name="phone"
                placeholder="recipient@example.com" autocomplete="off" required>
              <div class="form-hint">An invitation email will be sent to the recipient.</div>
            </div>
            <button class="btn btn-success" type="submit">Send Wallet Invitation</button>
          </form>
        </div>
      </div>
    </div>
  </main>

</div>

<footer style="text-align:center;padding:16px;font-size:.7rem;color:#b0bdd0;border-top:1px solid #e8ecf0;background:#fff;margin-top:16px;">
  © 2015 Romit, Inc. · <a href="https://hackerone.com/reports/57914" target="_blank" style="color:#b0bdd0;">HackerOne Report #57914</a> · <a href="#" style="color:#b0bdd0;">Privacy</a>
</footer>

</body>
</html>
