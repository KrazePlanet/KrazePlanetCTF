<?php
// MediCore Hospital EMR Portal — Report Viewer Controller
$section = $_GET['section'] ?? 'dashboard';
$report  = $_GET['report']  ?? null;   // Vulnerable LFI parameter

// Load report file content from local storage (Vulnerable to LFI)
$report_content = null;
$report_name    = null;
if ($report) {
    $report_name    = basename($report);
    $report_content = @file_get_contents($report);
}

// Patient record index
$patient = [
    'id'     => 'P-1001',
    'name'   => 'James R. Mitchell',
    'dob'    => '1978-03-14',
    'blood'  => 'A+',
    'ward'   => 'General Medicine — Room 204',
    'doctor' => 'Dr. Priya Nambiar, MD',
    'status' => 'Admitted',
];

$records = [
    'medical'  => ['Medical Report',    'records/patients/P1001/medical_report.txt',  'bi-file-medical',    '#0891b2'],
    'labs'     => ['Lab Results',       'records/patients/P1001/lab_results.txt',     'bi-eyedropper',      '#7c3aed'],
    'rx'       => ['Prescription',      'records/patients/P1001/prescription.txt',    'bi-capsule',         '#059669'],
    'imaging'  => ['Chest X-Ray Report','records/imaging/P1001_chest_xray.txt',       'bi-image',           '#d97706'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MediCore EMR — Patient Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="icon" href="/favicon.ico" />

  <style>
    :root {
      --bg:      #f0f4f8;
      --white:   #ffffff;
      --navy:    #0c2340;
      --teal:    #0e7490;
      --teal2:   #0891b2;
      --teal3:   #e0f2fe;
      --border:  #cbd5e1;
      --muted:   #64748b;
      --danger:  #dc2626;
      --warn:    #d97706;
      --success: #059669;
      --text:    #0f172a;
    }
    * { box-sizing: border-box; }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Sans', system-ui, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── Top Banner ── */
    .hospital-banner {
      background: var(--navy);
      color: #fff;
      padding: 0 2rem;
      display: flex;
      align-items: stretch;
      justify-content: space-between;
      min-height: 58px;
    }
    .banner-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 700;
      font-size: 1.1rem;
      color: #fff;
      text-decoration: none;
      padding: .75rem 0;
    }
    .brand-cross {
      width: 32px; height: 32px;
      background: #ef4444;
      border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem;
      flex-shrink: 0;
    }
    .banner-tagline {
      font-size: .72rem;
      color: rgba(255,255,255,.55);
      font-weight: 400;
      letter-spacing: .03em;
    }
    .banner-right {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      font-size: .82rem;
      color: rgba(255,255,255,.7);
    }
    .session-chip {
      display: flex; align-items: center; gap: 6px;
      background: rgba(255,255,255,.1);
      border: 1px solid rgba(255,255,255,.18);
      border-radius: 20px;
      padding: 5px 14px;
      color: #fff; font-weight: 600; font-size: .82rem;
    }

    /* ── Navigation Bar ── */
    .emr-nav {
      background: var(--white);
      border-bottom: 2px solid var(--teal2);
      padding: 0 2rem;
      display: flex;
      gap: 0;
    }
    .emr-nav-link {
      display: flex; align-items: center; gap: 7px;
      padding: .85rem 1.25rem;
      font-size: .88rem; font-weight: 600;
      color: var(--muted); text-decoration: none;
      border-bottom: 3px solid transparent;
      margin-bottom: -2px;
      transition: all .2s;
    }
    .emr-nav-link:hover { color: var(--teal); }
    .emr-nav-link.active {
      color: var(--teal);
      border-bottom-color: var(--teal2);
    }

    /* ── Main Layout ── */
    .emr-body {
      display: flex;
      flex: 1;
      max-width: 1280px;
      margin: 0 auto;
      width: 100%;
      padding: 1.5rem;
      gap: 1.5rem;
      align-items: flex-start;
    }

    /* ── Patient Card (left panel) ── */
    .patient-panel {
      width: 260px;
      flex-shrink: 0;
    }
    .patient-card {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .patient-header {
      background: linear-gradient(135deg, #0c2340, #0e7490);
      padding: 1.25rem;
      color: #fff;
      text-align: center;
    }
    .patient-avatar {
      width: 60px; height: 60px;
      border-radius: 50%;
      background: rgba(255,255,255,.15);
      border: 3px solid rgba(255,255,255,.4);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.6rem; margin: 0 auto 10px;
      color: rgba(255,255,255,.9);
    }
    .patient-name { font-weight: 700; font-size: 1rem; margin-bottom: 3px; }
    .patient-id { font-size: .75rem; color: rgba(255,255,255,.65); font-family: 'DM Mono', monospace; }
    .patient-body { padding: 1rem; }
    .info-row {
      display: flex; justify-content: space-between; align-items: flex-start;
      padding: .5rem 0; border-bottom: 1px solid #f1f5f9;
      font-size: .82rem;
    }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: var(--muted); font-weight: 600; font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; }
    .info-value { color: var(--text); font-weight: 600; text-align: right; }
    .status-badge {
      display: inline-block;
      background: rgba(5,150,105,.12);
      color: var(--success);
      border: 1px solid rgba(5,150,105,.3);
      font-size: .72rem; font-weight: 700;
      padding: 2px 8px; border-radius: 20px;
    }

    /* ── Quick Access Record Links ── */
    .record-nav { margin-top: 1rem; }
    .record-link {
      display: flex; align-items: center; gap: 10px;
      padding: .6rem .85rem;
      border-radius: 10px;
      font-size: .85rem; font-weight: 600;
      color: var(--muted); text-decoration: none;
      transition: all .2s; margin-bottom: 3px;
      border: 1px solid transparent;
    }
    .record-link:hover, .record-link.active {
      background: var(--teal3);
      color: var(--teal);
      border-color: rgba(14,116,144,.2);
    }
    .record-link i { font-size: 1rem; width: 20px; text-align: center; }

    /* ── Main Content Area ── */
    .main-area { flex: 1; min-width: 0; }

    /* ── Section Header ── */
    .section-bar {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1rem 1.4rem;
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 1.25rem;
      box-shadow: 0 1px 4px rgba(0,0,0,.04);
    }
    .section-title { font-weight: 700; font-size: 1.1rem; color: var(--text); }
    .section-sub { font-size: .8rem; color: var(--muted); margin-top: 2px; }

    /* ── Record Cards (dashboard grid) ── */
    .record-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px,1fr)); gap: 1rem; margin-bottom: 1.25rem; }
    .rec-card {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 1.25rem;
      box-shadow: 0 1px 4px rgba(0,0,0,.05);
      transition: all .2s;
      text-decoration: none; color: inherit; display: flex; flex-direction: column; gap: .75rem;
    }
    .rec-card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(14,116,144,.14); border-color: var(--teal2); }
    .rec-card-icon {
      width: 44px; height: 44px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem;
    }
    .rec-card-title { font-weight: 700; font-size: .95rem; color: var(--text); }
    .rec-card-meta  { font-size: .78rem; color: var(--muted); }
    .btn-view-rec {
      display: inline-flex; align-items: center; gap: 5px;
      background: var(--teal3);
      border: 1px solid rgba(14,116,144,.25);
      color: var(--teal); font-weight: 700; font-size: .78rem;
      padding: 6px 12px; border-radius: 8px; margin-top: auto;
      transition: all .2s; text-decoration: none;
    }
    .btn-view-rec:hover { background: var(--teal2); color: #fff; }

    /* ── Report Viewer ── */
    .report-viewer-wrap {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,.07);
    }
    .viewer-topbar {
      background: linear-gradient(135deg, #0c2340, #0e7490);
      padding: 1rem 1.4rem;
      display: flex; align-items: center; justify-content: space-between;
    }
    .viewer-title { color: #fff; font-weight: 700; font-size: .95rem; }
    .viewer-filepath { color: rgba(255,255,255,.6); font-size: .75rem; font-family: 'DM Mono', monospace; margin-top: 2px; }
    .viewer-badge {
      background: rgba(255,255,255,.15);
      border: 1px solid rgba(255,255,255,.25);
      color: rgba(255,255,255,.9); font-size: .72rem; font-weight: 700;
      padding: 3px 10px; border-radius: 20px;
    }
    .viewer-content {
      background: #f8fafc;
      font-family: 'DM Mono', monospace;
      font-size: .84rem; color: #334155;
      padding: 1.5rem;
      white-space: pre-wrap; word-break: break-all;
      max-height: 520px; overflow-y: auto;
      line-height: 1.75; border-top: 1px solid var(--border);
    }
    .viewer-toolbar {
      background: #f1f5f9;
      border-top: 1px solid var(--border);
      padding: .75rem 1.4rem;
      display: flex; gap: .5rem; align-items: center;
    }
    .btn-dl-report {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--teal2); color: #fff; font-weight: 700;
      font-size: .82rem; padding: 7px 16px; border-radius: 8px;
      text-decoration: none; transition: all .2s;
    }
    .btn-dl-report:hover { background: var(--teal); color: #fff; }
    .btn-back {
      display: inline-flex; align-items: center; gap: 5px;
      background: var(--white); border: 1px solid var(--border);
      color: var(--muted); font-weight: 600; font-size: .82rem;
      padding: 7px 14px; border-radius: 8px; text-decoration: none; transition: all .2s;
    }
    .btn-back:hover { background: var(--bg); color: var(--text); }

    /* ── Custom path input ── */
    .path-input-card {
      background: var(--white);
      border: 1px solid var(--border);
      border-left: 4px solid var(--teal2);
      border-radius: 12px; padding: 1.1rem 1.3rem;
      margin-bottom: 1.25rem;
      box-shadow: 0 1px 4px rgba(0,0,0,.04);
    }
    .path-input-label { font-size: .8rem; color: var(--muted); font-weight: 600; margin-bottom: .5rem; }

    /* ── Alert bar ── */
    .emr-alert {
      background: #fef9c3; border: 1px solid #fde047;
      border-left: 4px solid #f59e0b;
      border-radius: 10px; padding: .85rem 1.1rem;
      font-size: .85rem; color: #78350f;
      margin-bottom: 1rem;
    }

    footer {
      text-align: center; padding: 1rem;
      border-top: 1px solid var(--border);
      background: var(--white);
      color: var(--muted); font-size: .78rem;
    }
  </style>
</head>
<body>

<!-- Hospital Banner -->
<div class="hospital-banner">
  <a class="banner-brand" href="?section=dashboard">
    <div class="brand-cross"><i class="bi bi-plus-lg"></i></div>
    <div>
      <div>MediCore Regional Hospital</div>
      <div class="banner-tagline">Electronic Medical Records Portal</div>
    </div>
  </a>
  <div class="banner-right">
    <span><i class="bi bi-wifi me-1"></i> System Online</span>
    <div class="session-chip">
      <i class="bi bi-person-circle"></i>
      dr.nambiar &nbsp;|&nbsp; Internal Medicine
    </div>
  </div>
</div>

<!-- Navigation Bar -->
<nav class="emr-nav">
  <a class="emr-nav-link <?php echo $section==='dashboard'?'active':''; ?>" href="?section=dashboard">
    <i class="bi bi-grid-1x2"></i> Patient Dashboard
  </a>
  <a class="emr-nav-link <?php echo $section==='reports'?'active':''; ?>" href="?section=reports">
    <i class="bi bi-file-medical"></i> Medical Reports
  </a>
  <a class="emr-nav-link <?php echo $section==='labs'?'active':''; ?>" href="?section=labs">
    <i class="bi bi-eyedropper"></i> Lab Results
  </a>
  <a class="emr-nav-link <?php echo $section==='rx'?'active':''; ?>" href="?section=rx">
    <i class="bi bi-capsule"></i> Prescriptions
  </a>
  <a class="emr-nav-link <?php echo $section==='imaging'?'active':''; ?>" href="?section=imaging">
    <i class="bi bi-image"></i> Imaging Reports
  </a>
</nav>

<!-- Body -->
<div class="emr-body">

  <!-- Patient Panel (Left) -->
  <div class="patient-panel">
    <div class="patient-card">
      <div class="patient-header">
        <div class="patient-avatar"><i class="bi bi-person"></i></div>
        <div class="patient-name"><?php echo htmlspecialchars($patient['name']); ?></div>
        <div class="patient-id"><?php echo $patient['id']; ?></div>
      </div>
      <div class="patient-body">
        <div class="info-row">
          <span class="info-label">DOB</span>
          <span class="info-value"><?php echo $patient['dob']; ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Blood Type</span>
          <span class="info-value"><?php echo $patient['blood']; ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Ward</span>
          <span class="info-value" style="font-size:.78rem"><?php echo $patient['ward']; ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Physician</span>
          <span class="info-value" style="font-size:.78rem"><?php echo $patient['doctor']; ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Status</span>
          <span class="status-badge"><?php echo $patient['status']; ?></span>
        </div>
      </div>
    </div>

    <div class="record-nav mt-3">
      <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);padding:.4rem .85rem;margin-bottom:4px;">Quick Records</div>
      <?php foreach ($records as $key => [$label, $path, $icon, $color]): ?>
      <a class="record-link" href="?section=<?php echo $key; ?>&report=<?php echo urlencode($path); ?>">
        <i class="bi <?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i>
        <?php echo $label; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Main Content Area -->
  <div class="main-area">

    <!-- Report path input (visible feature hinting at the vulnerable parameter) -->
    <div class="path-input-card">
      <div class="path-input-label"><i class="bi bi-folder2-open me-1"></i>Report File Path — Direct Viewer Access</div>
      <form method="get" class="d-flex gap-2 flex-wrap">
        <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
        <input type="text" name="report"
          class="form-control form-control-sm border-secondary font-monospace"
          style="max-width:420px;background:#f8fafc;"
          placeholder="records/patients/P1001/medical_report.txt"
          value="<?php echo htmlspecialchars($report ?? '', ENT_QUOTES); ?>">
        <button type="submit" class="btn btn-sm" style="background:var(--teal2);color:#fff;font-weight:700;border-radius:8px;">
          <i class="bi bi-eye me-1"></i>Load Report
        </button>
      </form>
    </div>

    <?php if ($report && $report_content !== null && $report_content !== false): ?>
    <!-- REPORT VIEWER -->
    <div class="report-viewer-wrap">
      <div class="viewer-topbar">
        <div>
          <div class="viewer-title"><i class="bi bi-file-earmark-medical me-2"></i><?php echo htmlspecialchars($report_name); ?></div>
          <div class="viewer-filepath"><?php echo htmlspecialchars($report); ?></div>
        </div>
        <span class="viewer-badge">EMR DOCUMENT</span>
      </div>
      <div class="viewer-content"><?php echo htmlspecialchars($report_content); ?></div>
      <div class="viewer-toolbar">
        <a href="<?php echo htmlspecialchars($report); ?>" download class="btn-dl-report"><i class="bi bi-download"></i> Download</a>
        <a href="?section=<?php echo htmlspecialchars($section); ?>" class="btn-back"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </div>

    <?php elseif ($report): ?>
    <div class="emr-alert">
      <i class="bi bi-exclamation-triangle me-2"></i><strong>Access Denied / File Not Found:</strong>
      The requested record path <code><?php echo htmlspecialchars($report); ?></code> could not be loaded.
    </div>

    <?php else: ?>
    <!-- SECTION LANDING / DASHBOARD -->
    <div class="section-bar">
      <div>
        <div class="section-title">
          <?php
            $titles = ['dashboard'=>'Patient Overview','reports'=>'Medical Reports','labs'=>'Laboratory Results','rx'=>'Prescriptions','imaging'=>'Imaging Reports'];
            echo $titles[$section] ?? 'Patient Overview';
          ?>
        </div>
        <div class="section-sub">MediCore EMR — <?php echo $patient['id']; ?> — <?php echo htmlspecialchars($patient['name']); ?></div>
      </div>
      <span class="viewer-badge" style="background:var(--teal3);color:var(--teal);border:1px solid rgba(14,116,144,.25);">August 4, 2026</span>
    </div>

    <div class="emr-alert">
      <i class="bi bi-info-circle me-2"></i>
      <strong>EMR System Notice:</strong> Medical documents are served directly from the file storage path.
      Use the path viewer above or click any record card to view documents.
    </div>

    <div class="record-grid">
      <?php foreach ($records as $key => [$label, $path, $icon, $color]): ?>
      <a class="rec-card" href="?section=<?php echo $key; ?>&report=<?php echo urlencode($path); ?>">
        <div class="rec-card-icon" style="background:<?php echo $color; ?>22;color:<?php echo $color; ?>">
          <i class="bi <?php echo $icon; ?>"></i>
        </div>
        <div>
          <div class="rec-card-title"><?php echo $label; ?></div>
          <div class="rec-card-meta font-monospace"><?php echo $path; ?></div>
        </div>
        <span class="btn-view-rec"><i class="bi bi-eye"></i> View Document</span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</div>

<footer>&copy; 2026 MediCore Regional Hospital — Electronic Medical Records System &nbsp;|&nbsp; HIPAA Compliant</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-A3rJD856KowSb7dwlZdYKdNhnE+E03B9aA7D0A876B5A1"
  crossorigin="anonymous"></script>
</body>
</html>
