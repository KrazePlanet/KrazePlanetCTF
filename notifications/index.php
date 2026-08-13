<?php
// Lab 1205 — HTML Injection via First/Last Name in Confirmation Email
// Platform: hackerone.com/hackers/pentest-community-application | HackerOne Report #1374017
// Vulnerability: first_and_last_name[first] and first_and_last_name[last] echoed raw
//                in the confirmation email sent to the applicant.
// Email sender: marketing-cms@hackerone.com
// Payloads:
//   First:  "><h1>anything</h1>
//   Last:   "><h1>testiv</h1><u></u><img>

session_start();

$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply') {
    // ⚠ VULNERABLE — first and last name stored raw, no sanitization
    $_SESSION['first']       = $_POST['first']       ?? '';
    $_SESSION['last']        = $_POST['last']        ?? '';
    $_SESSION['email']       = $_POST['email']       ?? '';
    $_SESSION['linkedin']    = $_POST['linkedin']    ?? '';
    $_SESSION['experience']  = $_POST['experience']  ?? '1-2';
    $_SESSION['specialties'] = $_POST['specialties'] ?? [];
    $_SESSION['message']     = $_POST['message']     ?? '';
    $submitted = true;
}

$first      = $_SESSION['first']      ?? '';
$last       = $_SESSION['last']       ?? '';
$email      = htmlspecialchars($_SESSION['email']    ?? 'applicant@example.com', ENT_QUOTES, 'UTF-8');
$experience = htmlspecialchars($_SESSION['experience'] ?? '1-2', ENT_QUOTES, 'UTF-8');
$message    = htmlspecialchars($_SESSION['message']  ?? '', ENT_QUOTES, 'UTF-8');

$firstSafe  = htmlspecialchars($first, ENT_QUOTES, 'UTF-8');
$lastSafe   = htmlspecialchars($last,  ENT_QUOTES, 'UTF-8');
$initial    = strtoupper(substr(strip_tags($first), 0, 1)) ?: 'A';

if (isset($_GET['reset'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$showForm = !$submitted && !isset($_SESSION['first']) || isset($_GET['form']);
if (isset($_GET['form'])) $showForm = true;
if ($submitted) $showForm = false;
if (!$submitted && !isset($_SESSION['first'])) $showForm = true;
if (!$submitted && isset($_SESSION['first']) && $_SESSION['first'] !== '') $showForm = false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pentest Community Application — HackerOne</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',sans-serif;background:#f7f8fa;color:#1a1a2e;min-height:100vh;display:flex;flex-direction:column;}

/* ── Header ───────────────────────────────────────────────────────────────── */
.header{background:#1e2a3a;height:60px;display:flex;align-items:center;padding:0 32px;flex-shrink:0;position:sticky;top:0;z-index:100;box-shadow:0 2px 10px rgba(0,0,0,.25);}
.header-logo{display:flex;align-items:center;gap:9px;text-decoration:none;margin-right:36px;}
.h1-mark{width:32px;height:32px;flex-shrink:0;}
.header-logo-text{color:#fff;font-size:1.05rem;font-weight:800;letter-spacing:-.02em;}
.header-nav{display:flex;gap:2px;flex:1;}
.header-nav a{color:rgba(255,255,255,.7);font-size:.8rem;font-weight:500;text-decoration:none;padding:7px 12px;border-radius:4px;transition:color .15s,background .15s;}
.header-nav a:hover{color:#fff;background:rgba(255,255,255,.08);}
.header-right{display:flex;gap:10px;align-items:center;}
.btn-header-outline{border:1.5px solid rgba(255,255,255,.3);border-radius:6px;padding:6px 14px;color:rgba(255,255,255,.85);font-size:.78rem;font-weight:600;text-decoration:none;transition:border-color .15s,color .15s;}
.btn-header-outline:hover{border-color:#fff;color:#fff;}
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
.main{flex:1;max-width:900px;margin:0 auto;width:100%;padding:36px 16px 48px;}

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

/* ── Email preview ────────────────────────────────────────────────────────── */
.email-outer{background:#fff;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,.08);overflow:hidden;}
.email-outer-header{background:linear-gradient(90deg,#1e2a3a,#0f3460);padding:18px 24px;display:flex;align-items:center;justify-content:space-between;}
.email-outer-title{color:#fff;font-size:.9rem;font-weight:700;display:flex;align-items:center;gap:8px;}
.email-outer-title svg{width:18px;height:18px;opacity:.8;}
.email-outer-sub{color:rgba(255,255,255,.55);font-size:.72rem;margin-top:2px;}
.back-link{display:inline-flex;align-items:center;gap:6px;color:rgba(255,255,255,.75);font-size:.76rem;text-decoration:none;border:1px solid rgba(255,255,255,.25);border-radius:5px;padding:5px 11px;transition:background .15s;}
.back-link:hover{background:rgba(255,255,255,.1);color:#fff;}

.email-panel{background:#f1f3f5;padding:20px;}
.email-chrome{background:#e4e6e8;border-radius:8px 8px 0 0;padding:8px 14px;display:flex;align-items:center;gap:8px;border-bottom:1px solid #d0d3d7;}
.email-dot{width:10px;height:10px;border-radius:50%;}
.email-chrome-bar{flex:1;background:#fff;border-radius:10px;height:18px;display:flex;align-items:center;padding:0 10px;font-size:.65rem;color:#8a9ab8;font-family:monospace;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}
.email-container{background:#fff;border-radius:0 0 8px 8px;border:1px solid #d0d3d7;border-top:none;}
.email-meta-bar{padding:10px 18px;border-bottom:1px solid #eef0f2;font-size:.72rem;}
.email-meta-row{display:flex;gap:6px;margin-bottom:2px;line-height:1.5;}
.email-meta-key{color:#9ca3af;width:34px;flex-shrink:0;font-weight:600;}
.email-meta-val{color:#4b5563;}
.email-body{padding:0;}
.email-inner{max-width:520px;margin:0 auto;padding:28px 24px 32px;}
.email-logo-row{display:flex;align-items:center;gap:8px;margin-bottom:22px;}
.email-logo-mark{width:30px;height:30px;flex-shrink:0;}
.email-logo-text{font-size:.95rem;font-weight:800;color:#1e2a3a;letter-spacing:-.02em;}
.email-headline{font-size:1.15rem;font-weight:800;color:#1e2a3a;margin-bottom:14px;line-height:1.3;}
.email-text{font-size:.82rem;color:#4b5563;line-height:1.75;margin-bottom:12px;}
.email-cta{display:inline-block;background:#25a244;color:#fff;padding:11px 26px;border-radius:7px;text-decoration:none;font-weight:700;font-size:.82rem;margin:8px 0 16px;}
.email-divider{border:none;border-top:1px solid #f3f4f6;margin:16px 0;}
.email-footer-txt{font-size:.67rem;color:#9ca3af;line-height:1.65;}

/* ── Success badge ────────────────────────────────────────────────────────── */
.success-badge{display:inline-flex;align-items:center;gap:7px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:7px;padding:9px 14px;font-size:.78rem;font-weight:600;color:#065f46;margin-bottom:20px;}
.success-badge svg{width:16px;height:16px;flex-shrink:0;}
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

<?php if ($showForm): ?>
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- APPLICATION FORM                                                        -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
  <div style="margin-bottom:24px;">
    <div class="section-tag">Community Application</div>
    <h2 class="section-title">Apply Now</h2>
    <p class="section-sub">Fill in your details below. A confirmation email will be sent to the address you provide. Your name will appear in the email exactly as entered.</p>
  </div>

  <div class="app-card">
    <div class="app-card-header">
      <div class="app-card-header-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#4dd672" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
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
              value="iamr0000t@example.com" required>
            <div class="form-hint">Confirmation email will be sent here.</div>
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
            <input class="form-input" type="text" name="handle" value="iamr0000t" placeholder="@yourhandle">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Specialties</label>
          <div class="specialties-grid">
            <?php foreach(['Web Application','API Security','Mobile (iOS)','Mobile (Android)','Network','Cloud','Crypto / Blockchain','Social Engineering','Red Team'] as $s): ?>
            <label class="specialty-item">
              <input type="checkbox" name="specialties[]" value="<?= htmlspecialchars($s) ?>"> <?= htmlspecialchars($s) ?>
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
        <p class="form-footer-note">By submitting this form you agree to our Terms of Service and Privacy Policy.<br>A confirmation email will be sent from <strong>marketing-cms@hackerone.com</strong>.</p>
      </form>
    </div>
  </div>

<?php else: ?>
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- EMAIL PREVIEW (after submission)                                        -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->

  <div class="success-badge">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
    Application submitted! Confirmation email sent to <?= $email ?>
  </div>

  <div class="email-outer">
    <div class="email-outer-header">
      <div>
        <div class="email-outer-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          Recipient's Inbox — Confirmation Email Preview
        </div>
        <div class="email-outer-sub">This is the email HackerOne sends to the applicant — names are rendered raw</div>
      </div>
      <a href="index.php?form=1" class="back-link">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Submit another payload
      </a>
    </div>

    <div class="email-panel">
      <div class="email-chrome">
        <div class="email-dot" style="background:#ff5f57;"></div>
        <div class="email-dot" style="background:#ffbd2e;margin-left:5px;"></div>
        <div class="email-dot" style="background:#28ca40;margin-left:5px;"></div>
        <div class="email-chrome-bar">marketing-cms@hackerone.com — Your HackerOne Pentest Application</div>
      </div>
      <div class="email-container">
        <div class="email-meta-bar">
          <div class="email-meta-row"><span class="email-meta-key">From:</span><span class="email-meta-val">HackerOne &lt;marketing-cms@hackerone.com&gt;</span></div>
          <div class="email-meta-row"><span class="email-meta-key">To:</span><span class="email-meta-val"><?= $email ?></span></div>
          <div class="email-meta-row"><span class="email-meta-key">Subj:</span><span class="email-meta-val">Your HackerOne Pentest Community Application</span></div>
        </div>
        <div class="email-body">
          <div class="email-inner">
            <div class="email-logo-row">
              <svg class="email-logo-mark" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                <rect width="512" height="512" rx="80" fill="#25a244"/>
                <path d="M96 352V160h64v72h96V160h64v192h-64v-72H160v72H96zm224-192h64v128l64-128h72L448 256l72 96h-72l-64-128v128h-64V160z" fill="#fff"/>
              </svg>
              <span class="email-logo-text">HackerOne</span>
            </div>

            <div class="email-headline">Thank you for applying!</div>

            <div class="email-text">
              Hi
              <?php
              // ⚠ VULNERABLE SINK — $first and $last echoed raw, no htmlspecialchars().
              // Report #1374017: first_and_last_name[first] and first_and_last_name[last]
              // are embedded unsanitized in the confirmation email body.
              //
              // Payload (First):  "><h1>anything</h1>
              // Payload (Last):   "><h1>testiv</h1><u></u><img>
              echo $first . ' ' . $last;
              ?>,
            </div>

            <div class="email-text">
              We've received your application to join the <strong>HackerOne Pentest Community</strong>. Our team will review your submission and reach out within 5–7 business days.
            </div>

            <div class="email-text">
              In the meantime, feel free to continue earning on our platform:
            </div>

            <a href="#" class="email-cta">View Your Dashboard →</a>

            <div class="email-text">
              If you have any questions, please reach out to <a href="#" style="color:#25a244;">support@hackerone.com</a>.
            </div>

            <hr class="email-divider">

            <div class="email-text">
              <strong>Application Summary</strong><br>
              Experience: <?= $experience ?><br>
              Applied: <?= date('F j, Y') ?>
            </div>

            <div class="email-footer-txt">
              © <?= date('Y') ?> HackerOne, Inc. · 369 Pine Street, Suite 201, San Francisco, CA 94104<br>
              <a href="#" style="color:#9ca3af;">Unsubscribe</a> · <a href="#" style="color:#9ca3af;">Privacy Policy</a> · <a href="https://hackerone.com/reports/1374017" target="_blank" style="color:#9ca3af;">HackerOne Report #1374017</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php endif; ?>
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
