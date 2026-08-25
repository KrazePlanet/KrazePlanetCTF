<?php
session_start();
// Resilient PHPMailer & Composer Loader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../codeshackio/vendor/autoload.php')) {
    require_once __DIR__ . '/../codeshackio/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/PHPMailer/PHPMailer.php')) {
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
} elseif (file_exists('/opt/lampp/htdocs/PHPMailer/PHPMailer.php')) {
    require_once '/opt/lampp/htdocs/PHPMailer/Exception.php';
    require_once '/opt/lampp/htdocs/PHPMailer/PHPMailer.php';
    require_once '/opt/lampp/htdocs/PHPMailer/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── Database (auto-created on first run) ────────────────────────────────────
mysqli_report(MYSQLI_REPORT_OFF);
$db       = null;
$dbError  = '';
$database = 'KrazePlanet_DB';

$conn = @mysqli_connect('127.0.0.1', 'root', '') ?: @mysqli_connect((getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1')), 'root', '');
if ($conn) {
    @mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    @mysqli_select_db($conn, $database);
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab57914_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        nickname TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab57914_invites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_email VARCHAR(255) DEFAULT '',
        to_email VARCHAR(255) DEFAULT '',
        nickname TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    @mysqli_query($conn, "INSERT IGNORE INTO lab57914_users (email, nickname) VALUES ('user@romit.io', 'Romit')");
    $db = $conn;
} else {
    $dbError = 'DB connection failed: ' . mysqli_connect_error();
}

// ── Handle Nickname Save ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_nickname') {
    // ⚠ VULNERABLE — nickname stored raw, no sanitization
    $nickname_raw = $_POST['nickname'] ?? '';
    $_SESSION['nickname'] = $nickname_raw;
    if ($db) {
        $stmt = mysqli_prepare($db, "UPDATE lab57914_users SET nickname = ? WHERE email = 'user@romit.io'");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $nickname_raw);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?saved=1#settings');
    exit;
}

// ── Handle Share Wallet ─────────────────────────────────────────────────────
$share_sent = false;
$share_email = '';
$mailError = '';

// Load persisted nickname (DB is source of truth, falls back to session/default)
$nickname = $_SESSION['nickname'] ?? '';
if ($db) {
    $res = mysqli_query($db, "SELECT nickname FROM lab57914_users WHERE email = 'user@romit.io' LIMIT 1");
    if ($res && ($row = mysqli_fetch_assoc($res)) && $row['nickname'] !== null && $row['nickname'] !== '') {
        $nickname = $row['nickname'];
    }
}
if ($nickname === '') {
    $nickname = 'Romit';
}
$saved = isset($_GET['saved']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'share_wallet') {
    $share_sent = true;
    $share_email = trim($_POST['phone'] ?? '');

    // Persist invitation record (nickname stored raw — intentionally vulnerable)
    if ($db && $share_email) {
        $stmt = mysqli_prepare($db, "INSERT INTO lab57914_invites (from_email, to_email, nickname) VALUES ('user@romit.io', ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $share_email, $nickname);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    if ($share_email) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'mailpit';
            $mail->SMTPAuth   = false;
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
            $mail->Port       = 1025;
            $mail->Timeout    = 3;
            $mail->setFrom('noreply@krazeplanet.com', 'Romit');
            $mail->addAddress($share_email);
            $mail->isHTML(true);
            $mail->Subject = 'You have been invited to Romit!';

            // HTML Email Body — $nickname echoed raw so HTML payloads trigger in recipient email
            $emailBody = '
            <!DOCTYPE html>
            <html>
            <head><meta charset="utf-8"></head>
            <body style="font-family:Arial,sans-serif;background:#f3f4f8;padding:20px;margin:0;">
            <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.05);">
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
            </div>
            </body>
            </html>
            ';

            $mail->Body = $emailBody;
            $mail->send();
        } catch (Exception $e) {
            $mailError = 'Mail Error: ' . $mail->ErrorInfo;
        }
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
.layout{display:flex;flex:1;max-width:1000px;margin:0 auto;width:100%;padding:24px 16px;gap:20px;}

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
    <a href="#" class="active">Settings</a>
  </nav>
  <div class="header-right">
    <span class="hdr-balance">$124.50</span>
    <div class="hdr-avatar">U</div>
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
      <a class="snav-item active" href="#"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Settings</a>
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
      <h1 class="page-title">Account Settings</h1>
      <p class="page-sub">Manage your Romit profile and wallet sharing preferences.</p>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px;">
      <!-- Profile Settings card -->
      <div class="card" id="settings">
        <div class="card-header">
          <div class="card-header-icon" style="background:#f3f0fd;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6c3be4" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
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
            Nickname saved!
          </div>
          <?php endif; ?>
          <?php if ($dbError): ?>
          <div class="alert" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;">
            Database unavailable — nickname will only persist in this session. (<?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?>)
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
            <div class="card-header-sub">Send a wallet invitation by email</div>
          </div>
        </div>
        <div class="card-body">
          <?php if ($share_sent): ?>
            <div class="alert alert-success">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
              Wallet invitation email sent to <strong><?= htmlspecialchars($share_email, ENT_QUOTES, 'UTF-8') ?></strong>.
            </div>
            <?php if ($mailError): ?>
            <div class="alert" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;">
              Email Delivery Warning: <?= htmlspecialchars($mailError, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="alert alert-info">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Your current nickname "<strong><?= htmlspecialchars(substr($nickname, 0, 40), ENT_QUOTES, 'UTF-8') ?></strong>" will appear in the email.
            </div>
          <?php endif; ?>

          <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
            <input type="hidden" name="action" value="share_wallet">
            <div class="form-group">
              <label class="form-label" for="phone">Recipient Email Address</label>
              <input class="form-input" type="email" id="phone" name="phone"
                placeholder="recipient@example.com" autocomplete="off" required>
              <div class="form-hint">The recipient will receive a Romit wallet invitation email via SMTP.</div>
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
