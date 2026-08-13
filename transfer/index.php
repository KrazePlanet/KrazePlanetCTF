<?php
// Lab 1203 — Stored HTML Injection via Nickname in Wallet-Share Email
// Platform: romit.io (Enter) | HackerOne Report #57914
// Vulnerability: Nickname field stored unsanitized; injected raw into wallet-share email body
// Sink: echo $nickname (no htmlspecialchars) inside email template
// Payloads:
//   "> <a href="https://evil.com">Claim your reward</a> <!--
//   "><img src=x onerror="alert(document.domain)">
//   <!--

session_start();

// ── Handle Nickname Save ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_nickname') {
    // ⚠ VULNERABLE — nickname stored raw, no sanitization
    $_SESSION['nickname'] = $_POST['nickname'] ?? '';
    header('Location: ' . $_SERVER['PHP_SELF'] . '?saved=1#settings');
    exit;
}

// ── Handle Share Wallet ─────────────────────────────────────────────────────
$share_sent = false;
$share_phone = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'share_wallet') {
    $share_sent = true;
    $share_phone = $_POST['phone'] ?? '';
}

// ── Nickname (stored, unsanitized) ──────────────────────────────────────────
$nickname = $_SESSION['nickname'] ?? 'User';
$saved    = isset($_GET['saved']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Romit — Send Money Instantly</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
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
.layout{display:flex;flex:1;max-width:1100px;margin:0 auto;width:100%;padding:24px 16px;gap:20px;}

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

/* ── Email preview panel ──────────────────────────────────────────────────── */
.email-panel{background:#f8f9fb;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;}
.email-chrome{background:#e8ecf0;padding:8px 14px;display:flex;align-items:center;gap:8px;border-bottom:1px solid #d1d9e0;}
.email-chrome-dot{width:10px;height:10px;border-radius:50%;}
.email-chrome-bar{flex:1;background:#fff;border-radius:10px;height:18px;display:flex;align-items:center;padding:0 10px;font-size:.65rem;color:#8a9ab8;font-family:monospace;}
.email-meta{padding:12px 18px;border-bottom:1px solid #e2e8f0;background:#fff;}
.email-meta-row{display:flex;gap:6px;margin-bottom:3px;font-size:.72rem;}
.email-meta-label{color:#b0bdd0;font-weight:600;width:36px;flex-shrink:0;}
.email-meta-val{color:#5a6a8a;}
.email-body{background:#fff;padding:0;}
.email-body-inner{max-width:480px;margin:0 auto;padding:24px 24px 28px;}
.email-logo{display:flex;align-items:center;gap:7px;margin-bottom:20px;}
.email-logo-mark{width:28px;height:28px;background:#6c3be4;border-radius:7px;display:flex;align-items:center;justify-content:center;}
.email-logo-mark svg{width:16px;height:16px;}
.email-logo-text{font-size:.9rem;font-weight:800;color:#6c3be4;letter-spacing:-.02em;}
.email-subject{font-size:.95rem;font-weight:700;color:#1a1a2e;margin-bottom:14px;}
.email-content{font-size:.82rem;color:#3a4a6a;line-height:1.7;}
.email-cta{display:inline-block;margin:16px 0;background:#6c3be4;color:#fff;padding:10px 22px;border-radius:7px;text-decoration:none;font-weight:700;font-size:.82rem;}
.email-footer-txt{font-size:.68rem;color:#b0bdd0;margin-top:16px;padding-top:14px;border-top:1px solid #f0f0f0;line-height:1.6;}

/* ── Two column layout for main ───────────────────────────────────────────── */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
@media(max-width:900px){.two-col{grid-template-columns:1fr;}.sidebar{display:none;}}

/* ── Step indicator ───────────────────────────────────────────────────────── */
.steps{display:flex;gap:0;margin-bottom:20px;}
.step{display:flex;align-items:center;gap:8px;font-size:.74rem;font-weight:600;color:#b0bdd0;}
.step-num{width:22px;height:22px;border-radius:50%;border:2px solid #dce3ef;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;flex-shrink:0;}
.step.done .step-num{background:#6c3be4;border-color:#6c3be4;color:#fff;}
.step.done{color:#6c3be4;}
.step.current .step-num{background:#6c3be4;border-color:#6c3be4;color:#fff;}
.step.current{color:#1a1a2e;}
.step-sep{width:32px;height:2px;background:#dce3ef;margin:0 4px;flex-shrink:0;}
.step.done + .step-sep{background:#6c3be4;}
</style>
</head>
<body>

<!-- Header -->
<header class="header">
  <a href="#" class="header-logo">
    <div class="logo-mark">
      <svg viewBox="0 0 24 24" fill="none" stroke="#6c3be4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
      </svg>
    </div>
    <span class="logo-text">Romit</span>
  </a>
  <nav class="header-nav">
    <a href="#">Dashboard</a>
    <a href="#">Send</a>
    <a href="#">Request</a>
    <a href="#" class="active">Settings</a>
  </nav>
  <div class="header-right">
    <span class="hdr-balance">$124.50</span>
    <div class="hdr-avatar">
      <?= strtoupper(substr(strip_tags($nickname), 0, 1)) ?: 'U' ?>
    </div>
  </div>
</header>

<!-- Layout -->
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
    <nav class="sidebar-nav">
      <a href="#" class="snav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>
      <a href="#" class="snav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7-7 7 7"/></svg>
        Send Money
      </a>
      <a href="#" class="snav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7 7 7-7"/></svg>
        Request Money
      </a>
      <a href="#" class="snav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
        Settings
      </a>
    </nav>
    <div class="sidebar-tx">
      <div class="tx-title">Recent</div>
      <div class="tx-item">
        <div class="tx-avatar" style="background:#f59e0b;">J</div>
        <div class="tx-info">
          <div class="tx-name">Jamie L.</div>
          <div class="tx-date">Today, 10:12am</div>
        </div>
        <div class="tx-amount" style="color:#10b981;">+$25.00</div>
      </div>
      <div class="tx-item">
        <div class="tx-avatar" style="background:#6c3be4;">S</div>
        <div class="tx-info">
          <div class="tx-name">Sara K.</div>
          <div class="tx-date">Yesterday</div>
        </div>
        <div class="tx-amount" style="color:#ef4444;">-$12.00</div>
      </div>
      <div class="tx-item">
        <div class="tx-avatar" style="background:#10b981;">M</div>
        <div class="tx-info">
          <div class="tx-name">Mike T.</div>
          <div class="tx-date">May 18</div>
        </div>
        <div class="tx-amount" style="color:#10b981;">+$50.00</div>
      </div>
    </div>
  </aside>

  <!-- Main content -->
  <main class="main">

    <div>
      <div class="page-title">Account Settings</div>
      <div class="page-sub">Manage your Romit profile and wallet sharing preferences.</div>
    </div>

    <!-- Steps -->
    <div class="steps">
      <div class="step <?= $saved ? 'done' : 'current' ?>">
        <div class="step-num">1</div>
        Set Nickname
      </div>
      <div class="step-sep"></div>
      <div class="step <?= ($share_sent) ? 'done' : ($saved ? 'current' : '') ?>">
        <div class="step-num">2</div>
        Share Wallet
      </div>
      <div class="step-sep"></div>
      <div class="step <?= $share_sent ? 'current' : '' ?>">
        <div class="step-num">3</div>
        Email Preview
      </div>
    </div>

    <div class="two-col">

      <!-- Left column: Settings + Share -->
      <div style="display:flex;flex-direction:column;gap:16px;">

        <!-- Settings card -->
        <div class="card" id="settings">
          <div class="card-header">
            <div class="card-header-icon" style="background:#f3f0fd;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6c3be4" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            </div>
            <div>
              <div class="card-header-title">Profile Settings</div>
              <div class="card-header-sub">Your display name shown to recipients</div>
            </div>
          </div>
          <div class="card-body">
            <?php if ($saved): ?>
            <div class="alert alert-success">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
              Nickname saved successfully.
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
              <input type="hidden" name="action" value="save_nickname">
              <div class="form-group">
                <label class="form-label" for="nickname">Nickname</label>
                <input class="form-input" type="text" id="nickname" name="nickname"
                  value="<?= htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8') ?>"
                  placeholder='e.g. "> <a href="evil.com">Click me</a> <!--'
                  autocomplete="off">
                <div class="form-hint">Shown to users when you share your Romit wallet.</div>
              </div>
              <div class="form-group">
                <label class="form-label" for="email_addr">Email</label>
                <input class="form-input" type="email" id="email_addr" name="email_addr"
                  value="user@romit.io" disabled>
              </div>
              <div style="display:flex;gap:8px;">
                <button class="btn btn-primary" type="submit">Save Changes</button>
                <button class="btn btn-outline" type="reset">Reset</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Share Wallet card -->
        <div class="card">
          <div class="card-header">
            <div class="card-header-icon" style="background:#ecfdf5;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.66A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
            </div>
            <div>
              <div class="card-header-title">Share Your Wallet</div>
              <div class="card-header-sub">Send a wallet invitation by phone number</div>
            </div>
          </div>
          <div class="card-body">
            <?php if ($share_sent): ?>
            <div class="alert alert-success">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
              Invitation sent to <?= htmlspecialchars($share_phone, ENT_QUOTES, 'UTF-8') ?> — see email preview →
            </div>
            <?php else: ?>
            <div class="alert alert-info">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Your current nickname "<strong><?= htmlspecialchars(substr($nickname, 0, 40), ENT_QUOTES, 'UTF-8') ?></strong>" will appear in the email.
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>#preview">
              <input type="hidden" name="action" value="share_wallet">
              <div class="form-group">
                <label class="form-label" for="phone">Recipient Phone Number</label>
                <input class="form-input" type="tel" id="phone" name="phone"
                  placeholder="+1 (555) 000-0000" autocomplete="off">
                <div class="form-hint">The recipient will receive a Romit wallet invitation email.</div>
              </div>
              <button class="btn btn-success" type="submit">Send Wallet Invitation</button>
            </form>
          </div>
        </div>

      </div><!-- /left col -->

      <!-- Right column: Email Preview -->
      <div id="preview">
        <div class="card">
          <div class="card-header">
            <div class="card-header-icon" style="background:#fff7ed;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div>
              <div class="card-header-title">Recipient Email Preview</div>
              <div class="card-header-sub">This is what the recipient receives in their inbox</div>
            </div>
          </div>
          <div class="card-body" style="padding:0;">
            <div class="email-panel">
              <!-- Browser chrome -->
              <div class="email-chrome">
                <div class="email-chrome-dot" style="background:#ff5f57;"></div>
                <div class="email-chrome-dot" style="background:#ffbd2e;margin-left:5px;"></div>
                <div class="email-chrome-dot" style="background:#28ca40;margin-left:5px;"></div>
                <div class="email-chrome-bar">no-reply@romit.io — Wallet Invitation</div>
              </div>
              <!-- Email metadata -->
              <div class="email-meta">
                <div class="email-meta-row">
                  <span class="email-meta-label">From:</span>
                  <span class="email-meta-val">no-reply@romit.io</span>
                </div>
                <div class="email-meta-row">
                  <span class="email-meta-label">To:</span>
                  <span class="email-meta-val"><?= $share_sent ? htmlspecialchars($share_phone, ENT_QUOTES, 'UTF-8') : 'recipient@example.com' ?></span>
                </div>
                <div class="email-meta-row">
                  <span class="email-meta-label">Subj:</span>
                  <span class="email-meta-val">You've been invited to Romit!</span>
                </div>
              </div>
              <!-- Email body — VULNERABLE: nickname echoed raw, no htmlspecialchars -->
              <div class="email-body">
                <div class="email-body-inner">
                  <div class="email-logo">
                    <div class="email-logo-mark">
                      <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    <span class="email-logo-text">Romit</span>
                  </div>
                  <div class="email-subject">You've been invited to Romit!</div>
                  <div class="email-content">
                    <p>Hi there,</p>
                    <br>
                    <p>A Romit wallet has been shared with you. The user:
                    <?php
                    // ⚠ VULNERABLE SINK — $nickname echoed raw, no htmlspecialchars().
                    // Attacker sets Nickname to: "> <a href="evil.com">Claim reward</a> <!--
                    // The injected HTML renders directly in this email preview.
                    echo $nickname;
                    ?>
                    has shared their wallet with you and invited you to join Romit.</p>
                    <br>
                    <p>Click below to accept the wallet invitation and get started:</p>
                    <a href="#" class="email-cta">Accept Wallet Invitation</a>
                    <p>If you didn't expect this invitation, you can safely ignore this email.</p>
                  </div>
                  <div class="email-footer-txt">
                    © 2015 Romit, Inc. · 535 Mission St, San Francisco, CA<br>
                    <a href="#" style="color:#b0bdd0;">Unsubscribe</a> · <a href="#" style="color:#b0bdd0;">Privacy Policy</a>
                  </div>
                </div>
              </div><!-- /email-body -->
            </div><!-- /email-panel -->
          </div>
        </div>
      </div><!-- /right col -->

    </div><!-- /two-col -->
  </main>
</div>

<footer style="text-align:center;padding:16px;font-size:.7rem;color:#b0bdd0;border-top:1px solid #e8ecf0;background:#fff;margin-top:8px;">
  © 2015 Romit, Inc. · <a href="https://hackerone.com/reports/57914" target="_blank" style="color:#b0bdd0;">HackerOne Report #57914</a> · <a href="#" style="color:#b0bdd0;">Privacy</a>
</footer>

</body>
</html>
