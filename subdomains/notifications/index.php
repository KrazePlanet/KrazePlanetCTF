<?php
// Lab 1205 — HTML Injection via First/Last Name in Confirmation Email
// Platform: hackerone.com/hackers/pentest-community-application | HackerOne Report #1374017
// Email sending configured via SMTP (codeshackio mail configuration)

session_start();

// NOTE: SMTP delivery removed for this lab. The confirmation email is rendered
// into a simulated "recipient inbox" (Gmail-style panel) on the right instead
// of being emailed. The first/last name is still echoed raw into the message
// body, so a stored HTML-injection payload renders in the recipient's inbox.

/**
 * Build the confirmation email HTML body. $first and $last are echoed RAW —
 * intentionally vulnerable (HTML injection, HackerOne #1374017).
 */
function renderConfirmBody($first, $last, $experience) {
    return '
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.05);font-family:Arial,sans-serif;">
      <div style="background:#1e2a3a;padding:24px;text-align:center;">
        <h2 style="color:#ffffff;margin:0;font-size:22px;letter-spacing:-0.5px;">HackerOne Pentest Community</h2>
      </div>
      <div style="padding:32px 24px;">
        <h2 style="color:#1e2a3a;margin-top:0;font-size:20px;">Thank you for applying!</h2>
        <div style="font-size:15px;color:#4b5563;line-height:1.6;margin-bottom:16px;">
          Hi ' . $first . ' ' . $last . ',
        </div>
        <p style="font-size:15px;color:#4b5563;line-height:1.6;">We have received your application to join the <strong>HackerOne Pentest Community</strong>. Our team will review your submission and reach out within 5–7 business days.</p>
        <p style="font-size:15px;color:#4b5563;line-height:1.6;">In the meantime, feel free to continue earning on our platform.</p>
        <div style="margin:24px 0;">
          <a href="#" style="background:#25a244;color:#ffffff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;display:inline-block;">View Your Dashboard →</a>
        </div>
        <p style="font-size:14px;color:#6b7280;">If you have any questions, please reach out to <a href="#" style="color:#25a244;">support@hackerone.com</a>.</p>
        <hr style="border:none;border-top:1px solid #f3f4f6;margin:24px 0;">
        <div style="font-size:13px;color:#6b7280;">
          <strong>Application Summary</strong><br>
          Experience: ' . htmlspecialchars($experience, ENT_QUOTES, 'UTF-8') . '<br>
          Applied: ' . date('F j, Y') . '
        </div>
      </div>
    </div>';
}

// ── Database (auto-created on first run) ────────────────────────────────────
mysqli_report(MYSQLI_REPORT_OFF);
$db       = null;
$dbError  = '';
$database = 'KrazePlanet_DB';

$conn = @mysqli_connect((getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1')), 'root', '') ?: @mysqli_connect('127.0.0.1', 'root', '');
if ($conn) {
    @mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    @mysqli_select_db($conn, $database);
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab1374017_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(200) DEFAULT '',
        last_name VARCHAR(200) DEFAULT '',
        email VARCHAR(255) DEFAULT '',
        linkedin VARCHAR(500) DEFAULT '',
        handle VARCHAR(100) DEFAULT '',
        experience VARCHAR(20) DEFAULT '',
        specialties VARCHAR(1000) DEFAULT '',
        message TEXT,
        ip VARCHAR(64) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $db = $conn;
} else {
    $dbError = 'DB connection failed: ' . mysqli_connect_error();
}

$submitted = false;
$first = '';
$last = '';
$email = '';
$experience = '3-5';

if (!isset($_SESSION['inbox'])) {
    $_SESSION['inbox'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply') {
    // ⚠ VULNERABLE — first and last name stored raw, no sanitization
    $first       = $_POST['first']       ?? '';
    $last        = $_POST['last']        ?? '';
    $email       = trim($_POST['email']  ?? '');
    $linkedin    = $_POST['linkedin']    ?? '';
    $experience  = $_POST['experience']  ?? '3-5';
    $specialties = $_POST['specialties'] ?? [];
    $message     = $_POST['message']     ?? '';
    $submitted   = true;

    // Persist application (first/last name stored raw — intentionally vulnerable)
    if ($db) {
        $stmt = mysqli_prepare($db, "INSERT INTO lab1374017_applications (first_name, last_name, email, linkedin, handle, experience, specialties, message, ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $handle_esc      = $_POST['handle'] ?? '';
            $linkedin_esc    = $linkedin;
            $specialties_str = is_array($specialties) ? implode(', ', $specialties) : '';
            $ip              = $_SERVER['REMOTE_ADDR'] ?? '';
            mysqli_stmt_bind_param($stmt, 'sssssssss', $first, $last, $email, $linkedin_esc, $handle_esc, $experience, $specialties_str, $message, $ip);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    if ($email) {
        // Deliver the confirmation into the simulated recipient inbox instead of
        // sending over SMTP. First/last name kept raw so the HTML payload renders.
        array_unshift($_SESSION['inbox'], [
            'first'      => $first,
            'last'       => $last,
            'email'      => $email,
            'experience' => $experience,
            'time'       => time(),
        ]);
    }
}

// Show form on GET requests / page refreshes. Only show submission confirmation immediately after POST.
$showForm = !$submitted;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HackerOne — Pentest Community Application</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*:before,*:after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:#f4f6f8;color:#1a1a2e;line-height:1.5;min-height:100vh;display:flex;flex-direction:column;}

/* ── Header ───────────────────────────────────────────────────────────────── */
.header{background:#1e2a3a;padding:0 32px;height:56px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.header-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.h1-mark{width:28px;height:28px;}
.header-logo-text{font-size:.95rem;font-weight:800;color:#fff;letter-spacing:-.02em;}
.header-nav{display:flex;align-items:center;gap:24px;}
.header-nav a{color:rgba(255,255,255,.7);font-size:.8rem;font-weight:500;text-decoration:none;transition:color .15s;}
.header-nav a:hover{color:#fff;}
.header-right{display:flex;align-items:center;gap:12px;}
.btn-header-outline{border:1px solid rgba(255,255,255,.3);border-radius:6px;padding:6px 14px;color:#fff;font-size:.78rem;font-weight:600;text-decoration:none;transition:border-color .15s;}
.btn-header-outline:hover{border-color:rgba(255,255,255,.7);}
.btn-header-green{background:#25a244;border:none;border-radius:6px;padding:7px 16px;color:#fff;font-size:.78rem;font-weight:700;text-decoration:none;transition:background .15s;}
.btn-header-green:hover{background:#1e8a38;}

/* ── Hero ─────────────────────────────────────────────────────────────────── */
.hero{background:linear-gradient(135deg,#1e2a3a 0%,#0f3460 60%,#16213e 100%);padding:64px 32px 56px;text-align:center;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 60% 40%,rgba(37,162,68,.18) 0%,transparent 65%),radial-gradient(ellipse at 20% 80%,rgba(37,162,68,.08) 0%,transparent 50%);pointer-events:none;}
.hero-badge{display:inline-flex;align-items:center;gap:7px;background:rgba(37,162,68,.15);border:1px solid rgba(37,162,68,.35);border-radius:20px;padding:5px 14px;font-size:.72rem;font-weight:700;color:#4dd672;text-transform:uppercase;letter-spacing:.08em;margin-bottom:20px;}
.hero-badge-dot{width:6px;height:6px;border-radius:50%;background:#25a244;animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.4;}}
.hero-title{font-size:2.6rem;font-weight:800;color:#fff;line-height:1.2;margin-bottom:16px;letter-spacing:-.03em;}
.hero-title span{color:#4dd672;}
.hero-sub{font-size:1rem;color:rgba(255,255,255,.7);max-width:560px;margin:0 auto 32px;line-height:1.65;}
.hero-stats{display:flex;justify-content:center;gap:36px;flex-wrap:wrap;}
.hero-stat{text-align:center;}
.hero-stat-num{font-size:1.5rem;font-weight:800;color:#fff;}
.hero-stat-label{font-size:.72rem;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.06em;margin-top:2px;}

/* ── Main layout ──────────────────────────────────────────────────────────── */
.main{flex:1;max-width:1420px;margin:0 auto;width:100%;padding:36px 16px 48px;display:flex;gap:24px;align-items:flex-start;}
.content-col{flex:1;min-width:0;}
@media(max-width:1100px){.main{flex-wrap:wrap;}}

/* ── Gmail-style Inbox (recipient view) ───────────────────────────────────── */
.inbox{width:420px;flex-shrink:0;background:#fff;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,.08);overflow:hidden;display:flex;flex-direction:column;position:sticky;top:24px;}
@media(max-width:1100px){.inbox{width:100%;position:static;}}
.inbox-top{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid #f1f3f4;}
.inbox-glogo{font-size:1.05rem;font-weight:700;display:flex;align-items:center;gap:6px;}
.inbox-glogo .g-m{color:#ea4335;}.inbox-glogo .g-a1{color:#fbbc05;}.inbox-glogo .g-i{color:#34a853;}.inbox-glogo .g-l{color:#4285f4;}
.inbox-search{flex:1;background:#eaf1fb;border-radius:8px;padding:7px 12px;font-size:.74rem;color:#80868b;display:flex;align-items:center;gap:8px;}
.inbox-tabs{display:flex;padding:0 6px;border-bottom:1px solid #f1f3f4;}
.inbox-tab{padding:10px 16px;font-size:.76rem;font-weight:600;color:#5f6368;border-bottom:3px solid transparent;}
.inbox-tab.active{color:#1a73e8;border-bottom-color:#1a73e8;}
.inbox-list{flex:1;overflow-y:auto;max-height:640px;}
.inbox-empty{padding:48px 24px;text-align:center;color:#9aa0a6;font-size:.82rem;}
.inbox-empty svg{width:40px;height:40px;stroke:#dadce0;margin-bottom:12px;}
details.mail{border-bottom:1px solid #f1f3f4;}
summary.mail-row{list-style:none;display:flex;align-items:center;gap:12px;padding:12px 16px;cursor:pointer;transition:box-shadow .12s,background .12s;}
summary.mail-row::-webkit-details-marker{display:none;}
summary.mail-row:hover{box-shadow:inset 1px 0 0 #dadce0,inset -1px 0 0 #dadce0,0 1px 2px rgba(60,64,67,.15);z-index:1;}
details.mail[open] summary.mail-row{background:#f2f6fc;font-weight:600;}
.mail-avatar{width:36px;height:36px;border-radius:50%;background:#25a244;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:700;flex-shrink:0;}
.mail-meta{flex:1;min-width:0;}
.mail-from{font-size:.8rem;color:#202124;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.mail-subject{font-size:.76rem;color:#202124;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.mail-snippet{font-size:.72rem;color:#5f6368;font-weight:400;}
.mail-time{font-size:.68rem;color:#5f6368;flex-shrink:0;}
.mail-body{padding:16px 18px 22px;background:#f8f9fb;border-top:1px solid #f1f3f4;}
.mail-to{font-size:.72rem;color:#5f6368;margin-bottom:12px;}
.mail-to strong{color:#202124;}

/* ── Section heading ──────────────────────────────────────────────────────── */
.section-tag{display:inline-block;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:4px;padding:3px 10px;font-size:.7rem;font-weight:700;color:#065f46;text-transform:uppercase;letter-spacing:.07em;margin-bottom:10px;}
.section-title{font-size:1.6rem;font-weight:800;color:#1e2a3a;margin-bottom:8px;letter-spacing:-.02em;}
.section-sub{font-size:.88rem;color:#6b7280;line-height:1.65;margin-bottom:28px;}

/* ── Application form card ────────────────────────────────────────────────── */
.app-card{background:#fff;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,.08);overflow:hidden;}
.app-card-header{background:linear-gradient(90deg,#1e2a3a,#0f3460);padding:20px 28px;display:flex;align-items:center;gap:12px;}
.app-card-header-icon{width:40px;height:40px;background:rgba(37,162,68,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.app-card-header-icon svg{width:22px;height:22px;}
.app-card-header-title{color:#fff;font-size:1rem;font-weight:700;}
.app-card-header-sub{color:rgba(255,255,255,.6);font-size:.76rem;margin-top:2px;}
.app-card-body{padding:28px;}

/* ── Form ─────────────────────────────────────────────────────────────────── */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
@media(max-width:600px){.form-row{grid-template-columns:1fr;}}
.form-group{margin-bottom:18px;}
.form-label{display:block;font-size:.78rem;font-weight:700;color:#374151;margin-bottom:6px;}
.form-label .req{color:#ef4444;margin-left:2px;}
.form-input{width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:.84rem;color:#1a1a2e;background:#fff;outline:none;font-family:inherit;transition:border-color .15s,box-shadow .15s;}
.form-input:focus{border-color:#25a244;box-shadow:0 0 0 3px rgba(37,162,68,.12);}
.form-hint{font-size:.68rem;color:#9ca3af;margin-top:4px;}
select.form-input{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;}
textarea.form-input{resize:vertical;min-height:90px;line-height:1.55;}
.specialties-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:4px;}
@media(max-width:500px){.specialties-grid{grid-template-columns:repeat(2,1fr);}}
.specialty-item{display:flex;align-items:center;gap:7px;cursor:pointer;font-size:.78rem;color:#374151;}
.specialty-item input[type=checkbox]{width:14px;height:14px;accent-color:#25a244;cursor:pointer;flex-shrink:0;}
.form-divider{border:none;border-top:1px solid #f3f4f6;margin:6px 0 22px;}
.btn-submit{width:100%;background:#25a244;border:none;border-radius:8px;padding:12px;font-size:.9rem;font-weight:700;color:#fff;cursor:pointer;font-family:inherit;transition:background .15s,transform .1s;letter-spacing:.01em;}
.btn-submit:hover{background:#1e8a38;}
.btn-submit:active{transform:scale(.99);}
.form-footer-note{text-align:center;font-size:.7rem;color:#9ca3af;margin-top:10px;line-height:1.6;}
</style>
</head>
<body>

<!-- Header -->
<header class="header">
  <a href="index.php" class="header-logo">
    <svg class="h1-mark" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
      <rect width="512" height="512" rx="80" fill="#25a244"/>
      <path d="M96 352V160h64v72h96V160h64v192h-64v-72H160v72H96zm224-192h64v128l64-128h72L448 256l72 96h-72l-64-128v128h-64V160z" fill="#fff"/>
    </svg>
    <span class="header-logo-text">HackerOne</span>
  </a>
  <nav class="header-nav">
    <a href="#">Platform</a>
    <a href="#">Programs</a>
    <a href="#" style="color:rgba(255,255,255,.95);">Hackers</a>
    <a href="#">Resources</a>
    <a href="#">Pricing</a>
  </nav>
  <div class="header-right">
    <a href="#" class="btn-header-outline">Sign In</a>
    <a href="#" class="btn-header-green">Get Started</a>
  </div>
</header>

<!-- Hero -->
<section class="hero">
  <div class="hero-badge">
    <span class="hero-badge-dot"></span>
    Now Accepting Applications
  </div>
  <h1 class="hero-title">Join the HackerOne<br><span>Pentest Community</span></h1>
  <p class="hero-sub">Apply to become part of the elite group of pentesters delivering structured, high-quality assessments to world-class organizations.</p>
  <div class="hero-stats">
    <div class="hero-stat"><div class="hero-stat-num">3,000+</div><div class="hero-stat-label">Active Pentesters</div></div>
    <div class="hero-stat"><div class="hero-stat-num">$230M+</div><div class="hero-stat-label">Bounties Paid</div></div>
    <div class="hero-stat"><div class="hero-stat-num">40,000+</div><div class="hero-stat-label">Hackers</div></div>
  </div>
</section>

<!-- Main -->
<main class="main">
<div class="content-col">

<?php if ($showForm): ?>
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- APPLICATION FORM                                                        -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
  <div style="margin-bottom:24px;">
    <div class="section-tag">Community Application</div>
    <h2 class="section-title">Apply Now</h2>
    <p class="section-sub">Fill in your details below. A confirmation email will be delivered to the inbox panel on the right. Your name will appear in the email exactly as entered.</p>
    <?php if ($dbError): ?>
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 14px;border-radius:8px;font-size:.8rem;">
      <strong>Database Warning:</strong> <?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?> — submissions will not be stored.
    </div>
    <?php endif; ?>
  </div>

  <div class="app-card">
    <div class="app-card-header">
      <div class="app-card-header-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#4dd672" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 010 7.75"/></svg>
      </div>
      <div>
        <div class="app-card-header-title">Pentest Community Application</div>
        <div class="app-card-header-sub">hackerone.com/hackers/pentest-community-application</div>
      </div>
    </div>
    <div class="app-card-body">
      <form method="POST" action="index.php">
        <input type="hidden" name="action" value="apply">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="first">First Name <span class="req">*</span></label>
            <input class="form-input" type="text" id="first" name="first"
              placeholder='Try: "><h1>Injected</h1>'
              autocomplete="off" required>
            <div class="form-hint">Parameter: <code style="font-size:.68rem;">first_and_last_name[first]</code></div>
          </div>
          <div class="form-group">
            <label class="form-label" for="last">Last Name <span class="req">*</span></label>
            <input class="form-input" type="text" id="last" name="last"
              placeholder='Try: "><img src=x onerror=alert(1)>'
              autocomplete="off" required>
            <div class="form-hint">Parameter: <code style="font-size:.68rem;">first_and_last_name[last]</code></div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="email">Email Address <span class="req">*</span></label>
            <input class="form-input" type="email" id="email" name="email"
              value="" required>
            <div class="form-hint">The confirmation will appear in the recipient inbox panel on the right.</div>
          </div>
          <div class="form-group">
            <label class="form-label" for="linkedin">LinkedIn Profile</label>
            <input class="form-input" type="url" id="linkedin" name="linkedin"
              placeholder="https://linkedin.com/in/username">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="experience">Years of Experience <span class="req">*</span></label>
            <select class="form-input" id="experience" name="experience" required>
              <option value="">Select...</option>
              <option value="lt1">Less than 1 year</option>
              <option value="1-2">1 – 2 years</option>
              <option value="3-5" selected>3 – 5 years</option>
              <option value="5-10">5 – 10 years</option>
              <option value="10+">10+ years</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">HackerOne Handle</label>
            <input class="form-input" type="text" name="handle" value="" placeholder="@yourhandle">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Specialties</label>
          <div class="specialties-grid">
            <?php foreach(['Web Application','API Security','Mobile (iOS)','Mobile (Android)','Network','Cloud','Crypto / Blockchain','Social Engineering','Red Team'] as $s): ?>
            <label class="specialty-item">
              <input type="checkbox" name="specialties[]" value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>"> <?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <hr class="form-divider">

        <div class="form-group">
          <label class="form-label" for="message">Why do you want to join? <span class="req">*</span></label>
          <textarea class="form-input" id="message" name="message" rows="4" required placeholder="Tell us about your experience, notable findings, and why you want to be part of the HackerOne Pentest Community..."></textarea>
        </div>

        <button class="btn-submit" type="submit">Submit Application →</button>
        <p class="form-footer-note">By submitting this form you agree to our Terms of Service and Privacy Policy.<br>A confirmation email will arrive from <strong>noreply@hackerone.com</strong>.</p>
      </form>
    </div>
  </div>

<?php else: ?>
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- APPLICATION SUBMISSION CONFIRMATION                                      -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
  <div style="background:#fff;border-radius:12px;padding:40px 32px;box-shadow:0 2px 16px rgba(0,0,0,.08);text-align:center;max-width:580px;margin:32px auto;">
    <div style="width:64px;height:64px;background:#ecfdf5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#25a244" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
    </div>
    <h2 style="font-size:1.5rem;font-weight:800;color:#1e2a3a;margin-bottom:10px;">Application Submitted!</h2>
    <p style="font-size:.92rem;color:#4b5563;line-height:1.65;margin-bottom:24px;">
      A confirmation email has been delivered to <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>.<br>Check the inbox panel on the right.
    </p>

    <?php if ($dbError): ?>
      <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:8px;font-size:.82rem;margin-bottom:20px;text-align:left;">
        <strong>Database Warning:</strong> <?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?> — application not stored.
      </div>
    <?php endif; ?>

    <a href="index.php" class="btn-submit" style="display:inline-block;text-decoration:none;width:auto;padding:12px 28px;">Submit Another Application →</a>
  </div>

<?php endif; ?>
</div><!-- /.content-col -->

<!-- Recipient Inbox (Gmail-style) -->
<aside class="inbox">
  <div class="inbox-top">
    <div class="inbox-glogo">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ea4335" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
      <span><span class="g-m">M</span><span class="g-a1">a</span><span class="g-i">i</span><span class="g-l">l</span></span>
    </div>
    <div class="inbox-search">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#80868b" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      Search mail
    </div>
  </div>
  <div class="inbox-tabs">
    <div class="inbox-tab active">Primary</div>
    <div class="inbox-tab">Promotions</div>
    <div class="inbox-tab">Social</div>
  </div>
  <div class="inbox-list">
    <?php if (empty($_SESSION['inbox'])): ?>
      <div class="inbox-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        <div>Your inbox is empty.</div>
        <div style="font-size:.72rem;margin-top:4px;">Submit an application to see the confirmation arrive here.</div>
      </div>
    <?php else: foreach ($_SESSION['inbox'] as $i => $msg): ?>
      <details class="mail"<?= $i === 0 ? ' open' : '' ?>>
        <summary class="mail-row">
          <div class="mail-avatar">H</div>
          <div class="mail-meta">
            <div class="mail-from">HackerOne &lt;noreply@hackerone.com&gt;</div>
            <div class="mail-subject">Your HackerOne Pentest Community Application
              <span class="mail-snippet">— Thank you for applying!…</span>
            </div>
          </div>
          <div class="mail-time"><?= date('g:i A', (int)$msg['time']) ?></div>
        </summary>
        <div class="mail-body">
          <div class="mail-to">to <strong><?= htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8') ?></strong></div>
          <?php
            // ⚠ first/last name rendered RAW — HTML injection renders in the recipient's inbox
            echo renderConfirmBody($msg['first'], $msg['last'], $msg['experience']);
          ?>
        </div>
      </details>
    <?php endforeach; endif; ?>
  </div>
</aside>
</main>

<footer style="background:#1e2a3a;padding:16px 32px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;flex-shrink:0;">
  <span style="font-size:.72rem;color:rgba(255,255,255,.4);">© <?= date('Y') ?> HackerOne, Inc. All rights reserved.</span>
  <span style="font-size:.72rem;color:rgba(255,255,255,.35);">
    <a href="https://hackerone.com/reports/1374017" target="_blank" style="color:rgba(255,255,255,.35);text-decoration:none;">HackerOne Report #1374017</a>
    &nbsp;·&nbsp;<a href="#" style="color:rgba(255,255,255,.35);text-decoration:none;">Privacy</a>
    &nbsp;·&nbsp;<a href="#" style="color:rgba(255,255,255,.35);text-decoration:none;">Terms</a>
  </span>
</footer>

</body>
</html>
