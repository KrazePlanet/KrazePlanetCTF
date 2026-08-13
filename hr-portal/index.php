<?php
// HR Portal — File Preview Controller
// Loads and displays employee documents from local storage (Vulnerable to LFI)
$file   = $_GET['file']   ?? null;
$tab    = $_GET['tab']    ?? 'dashboard';
$upload_msg = '';

// Handle resume upload (stores to uploads/ directory)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resume'])) {
    $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($_FILES['resume']['name']));
    $dest = __DIR__ . '/uploads/' . $name;
    if (move_uploaded_file($_FILES['resume']['tmp_name'], $dest)) {
        $upload_msg = 'success';
    } else {
        $upload_msg = 'error';
    }
}

// Collect uploaded resumes
$resumes = array_filter(glob(__DIR__ . '/uploads/*'), 'is_file');
// Collect offer letters
$letters = array_filter(glob(__DIR__ . '/letters/*'), 'is_file');

// Read file for preview (Vulnerable to LFI)
$preview_content = null;
$preview_name    = null;
if ($file) {
    $preview_name    = basename($file);
    $preview_content = @file_get_contents($file);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ACME Corp — HR Employee Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" href="/favicon.ico" />

  <style>
    :root {
      --bg:        #0b0f1a;
      --sidebar:   #101624;
      --card:      #141c2b;
      --card2:     #1a2236;
      --border:    rgba(255,255,255,0.09);
      --accent:    #6366f1;   /* indigo — distinct from other labs */
      --accent2:   #818cf8;
      --success:   #10b981;
      --danger:    #f43f5e;
      --warn:      #f59e0b;
      --text:      #f1f5f9;
      --muted:     #94a3b8;
    }
    * { box-sizing: border-box; }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', system-ui, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── Top Bar ── */
    .topbar {
      background: var(--sidebar);
      border-bottom: 1px solid var(--border);
      padding: .75rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
      backdrop-filter: blur(12px);
    }
    .topbar-brand {
      font-weight: 800;
      font-size: 1.2rem;
      background: linear-gradient(90deg, #6366f1, #818cf8);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .user-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255,255,255,.06);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 5px 14px;
      font-size: .85rem;
      font-weight: 600;
    }
    .avatar {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: linear-gradient(135deg, #6366f1, #818cf8);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: .72rem;
      font-weight: 800;
      color: #fff;
    }

    /* ── Layout ── */
    .layout { display: flex; flex: 1; }

    /* ── Sidebar ── */
    .sidebar {
      width: 230px;
      background: var(--sidebar);
      border-right: 1px solid var(--border);
      padding: 1.5rem 1rem;
      flex-shrink: 0;
    }
    .nav-section-label {
      font-size: .68rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .1em;
      color: var(--muted);
      padding: 0 .5rem;
      margin-top: 1rem;
      margin-bottom: .4rem;
    }
    .side-link {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: .55rem .85rem;
      border-radius: 9px;
      color: var(--muted);
      text-decoration: none;
      font-size: .9rem;
      font-weight: 500;
      transition: all .2s;
      margin-bottom: 2px;
    }
    .side-link:hover, .side-link.active {
      background: rgba(99,102,241,.14);
      color: var(--accent2);
    }
    .side-link .badge-count {
      margin-left: auto;
      background: rgba(99,102,241,.2);
      color: var(--accent2);
      border-radius: 10px;
      font-size: .7rem;
      font-weight: 700;
      padding: 1px 7px;
    }

    /* ── Main Content ── */
    .main-content { flex: 1; padding: 2rem; overflow-y: auto; }

    /* ── Stat Cards ── */
    .stat-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 1.4rem 1.2rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .stat-icon {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      flex-shrink: 0;
    }
    .stat-label { font-size: .78rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
    .stat-value { font-size: 1.9rem; font-weight: 800; line-height: 1; margin-top: 2px; }

    /* ── Table ── */
    .portal-table { width: 100%; border-collapse: collapse; }
    .portal-table th {
      background: var(--card2);
      color: var(--muted);
      font-size: .75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .07em;
      padding: .75rem 1rem;
      border-bottom: 1px solid var(--border);
    }
    .portal-table td {
      padding: .85rem 1rem;
      border-bottom: 1px solid var(--border);
      font-size: .9rem;
      vertical-align: middle;
    }
    .portal-table tr:hover td { background: rgba(255,255,255,.03); }
    .portal-table tbody tr:last-child td { border-bottom: none; }

    /* ── Panel Card ── */
    .panel {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
    }
    .panel-header {
      background: var(--card2);
      border-bottom: 1px solid var(--border);
      padding: 1rem 1.4rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .panel-title { font-weight: 700; font-size: 1rem; }
    .panel-body { padding: 1.4rem; }

    /* ── File Preview Viewer ── */
    .file-viewer {
      background: #050810;
      border: 1px solid var(--border);
      border-radius: 10px;
      font-family: 'JetBrains Mono', 'Fira Code', monospace;
      font-size: .84rem;
      color: #a5f3fc;
      padding: 1.2rem 1.4rem;
      white-space: pre-wrap;
      word-break: break-all;
      max-height: 420px;
      overflow-y: auto;
      line-height: 1.7;
    }

    /* ── Buttons ── */
    .btn-indigo {
      background: linear-gradient(135deg, #6366f1, #4f46e5);
      border: 1px solid rgba(99,102,241,.4);
      color: #fff;
      font-weight: 700;
      border-radius: 9px;
      padding: 7px 16px;
      font-size: .84rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all .2s;
      cursor: pointer;
    }
    .btn-indigo:hover { background: linear-gradient(135deg, #4f46e5, #4338ca); color: #fff; transform: translateY(-1px); }

    .btn-ghost {
      background: rgba(255,255,255,.06);
      border: 1px solid var(--border);
      color: var(--muted);
      font-weight: 600;
      border-radius: 9px;
      padding: 6px 14px;
      font-size: .83rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all .2s;
    }
    .btn-ghost:hover { background: rgba(255,255,255,.11); color: #fff; }

    /* ── Upload Drop Zone ── */
    .upload-zone {
      border: 2px dashed rgba(99,102,241,.35);
      border-radius: 14px;
      padding: 2.5rem;
      text-align: center;
      transition: all .25s;
      background: rgba(99,102,241,.04);
      cursor: pointer;
    }
    .upload-zone:hover { border-color: var(--accent); background: rgba(99,102,241,.09); }

    /* ── Status Badges ── */
    .badge-status {
      display: inline-block;
      font-size: .72rem;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 20px;
    }
    .badge-review  { background: rgba(245,158,11,.15); color: var(--warn); border: 1px solid rgba(245,158,11,.3); }
    .badge-offered { background: rgba(16,185,129,.15); color: var(--success); border: 1px solid rgba(16,185,129,.3); }
    .badge-new     { background: rgba(99,102,241,.15); color: var(--accent2); border: 1px solid rgba(99,102,241,.3); }

    footer { padding: 1.2rem; text-align: center; color: var(--muted); font-size: .8rem; border-top: 1px solid var(--border); }
  </style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
  <div class="topbar-brand"><i class="bi bi-buildings me-2"></i>ACME Corp — HR Portal</div>
  <div class="user-badge">
    <div class="avatar">HR</div>
    <span>hr.admin@acmecorp.internal</span>
    <i class="bi bi-chevron-down text-muted small"></i>
  </div>
</div>

<!-- Layout -->
<div class="layout">

  <!-- Sidebar -->
  <nav class="sidebar">
    <div class="nav-section-label">Overview</div>
    <a class="side-link <?php echo ($tab==='dashboard') ? 'active' : ''; ?>" href="?tab=dashboard">
      <i class="bi bi-grid-1x2"></i> Dashboard
    </a>

    <div class="nav-section-label">Recruitment</div>
    <a class="side-link <?php echo ($tab==='applications') ? 'active' : ''; ?>" href="?tab=applications">
      <i class="bi bi-person-lines-fill"></i> Applications
      <span class="badge-count">2</span>
    </a>
    <a class="side-link <?php echo ($tab==='upload') ? 'active' : ''; ?>" href="?tab=upload">
      <i class="bi bi-upload"></i> Upload Resume
    </a>

    <div class="nav-section-label">Documents</div>
    <a class="side-link <?php echo ($tab==='letters') ? 'active' : ''; ?>" href="?tab=letters">
      <i class="bi bi-envelope-paper"></i> Offer Letters
      <span class="badge-count">2</span>
    </a>
    <a class="side-link <?php echo ($tab==='preview' || $file) ? 'active' : ''; ?>" href="?tab=preview">
      <i class="bi bi-eye"></i> File Preview
    </a>
  </nav>

  <!-- Main Content -->
  <main class="main-content">

    <?php if ($tab === 'dashboard'): ?>
    <!-- DASHBOARD -->
    <h4 class="fw-bold mb-1">Welcome back, HR Admin</h4>
    <p class="text-muted mb-4">Here's a summary of current recruitment activity.</p>

    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(99,102,241,.15)">
            <i class="bi bi-people" style="color:#818cf8"></i>
          </div>
          <div>
            <div class="stat-label">Total Applicants</div>
            <div class="stat-value" style="color:#818cf8">24</div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(245,158,11,.12)">
            <i class="bi bi-hourglass-split" style="color:#f59e0b"></i>
          </div>
          <div>
            <div class="stat-label">Under Review</div>
            <div class="stat-value" style="color:#f59e0b">8</div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(16,185,129,.12)">
            <i class="bi bi-check-circle" style="color:#10b981"></i>
          </div>
          <div>
            <div class="stat-label">Offers Sent</div>
            <div class="stat-value" style="color:#10b981">2</div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(244,63,94,.12)">
            <i class="bi bi-x-circle" style="color:#f43f5e"></i>
          </div>
          <div>
            <div class="stat-label">Rejected</div>
            <div class="stat-value" style="color:#f43f5e">6</div>
          </div>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <span class="panel-title"><i class="bi bi-clock-history me-2 text-muted"></i>Recent Activity</span>
      </div>
      <div class="panel-body p-0">
        <table class="portal-table">
          <tbody>
            <tr><td><i class="bi bi-upload text-indigo me-2" style="color:#818cf8"></i>New resume uploaded — <strong>Priya Sharma</strong></td><td class="text-muted small">2 hours ago</td></tr>
            <tr><td><i class="bi bi-envelope-check text-success me-2"></i>Offer letter dispatched — <strong>John Davidson</strong></td><td class="text-muted small">Yesterday</td></tr>
            <tr><td><i class="bi bi-person-check me-2" style="color:#818cf8"></i>Application moved to review — <strong>Alex Turner</strong></td><td class="text-muted small">2 days ago</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <?php elseif ($tab === 'applications'): ?>
    <!-- APPLICATIONS -->
    <h4 class="fw-bold mb-1">Candidate Applications</h4>
    <p class="text-muted mb-4">Review submitted resumes and update applicant status.</p>
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title"><i class="bi bi-person-lines-fill me-2 text-muted"></i>All Applications</span>
        <a href="?tab=upload" class="btn-indigo"><i class="bi bi-plus-lg"></i> New Application</a>
      </div>
      <div class="panel-body p-0">
        <table class="portal-table">
          <thead>
            <tr>
              <th>Candidate</th>
              <th>Position</th>
              <th>Submitted</th>
              <th>Status</th>
              <th>Resume</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>John Davidson</strong><br><small class="text-muted">john.davidson@email.com</small></td>
              <td>Senior Software Engineer</td>
              <td class="text-muted small">Aug 2, 2026</td>
              <td><span class="badge-status badge-offered">Offer Sent</span></td>
              <td><a href="?tab=preview&file=uploads/resume_john_davidson.txt" class="btn-ghost"><i class="bi bi-eye"></i> Preview</a></td>
            </tr>
            <tr>
              <td><strong>Priya Sharma</strong><br><small class="text-muted">priya.sharma@email.com</small></td>
              <td>Lead Frontend Engineer</td>
              <td class="text-muted small">Aug 4, 2026</td>
              <td><span class="badge-status badge-review">Under Review</span></td>
              <td><a href="?tab=preview&file=uploads/resume_priya_sharma.txt" class="btn-ghost"><i class="bi bi-eye"></i> Preview</a></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <?php elseif ($tab === 'upload'): ?>
    <!-- UPLOAD RESUME -->
    <h4 class="fw-bold mb-1">Upload Candidate Resume</h4>
    <p class="text-muted mb-4">Upload resumes to local storage for HR review and tracking.</p>

    <?php if ($upload_msg === 'success'): ?>
      <div class="alert alert-success bg-dark border-success text-success mb-4">
        <i class="bi bi-check-circle me-2"></i>Resume uploaded successfully to <code>uploads/</code>.
      </div>
    <?php elseif ($upload_msg === 'error'): ?>
      <div class="alert alert-danger bg-dark border-danger text-danger mb-4">
        <i class="bi bi-x-circle me-2"></i>Upload failed. Please try again.
      </div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-header"><span class="panel-title"><i class="bi bi-upload me-2 text-muted"></i>Resume Upload</span></div>
      <div class="panel-body">
        <form method="post" enctype="multipart/form-data">
          <div class="upload-zone mb-4" onclick="document.getElementById('resumeFile').click()">
            <i class="bi bi-cloud-upload fs-1 mb-2" style="color:#6366f1"></i>
            <p class="mb-1 fw-semibold">Click to select a file</p>
            <small class="text-muted">Accepted: .txt, .pdf, .doc, .docx (max 5MB)</small>
            <input type="file" name="resume" id="resumeFile" class="d-none" accept=".txt,.pdf,.doc,.docx">
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label text-muted small fw-semibold">Candidate Full Name</label>
              <input type="text" class="form-control bg-dark border-secondary text-white" placeholder="John Davidson" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted small fw-semibold">Applied Position</label>
              <input type="text" class="form-control bg-dark border-secondary text-white" placeholder="Senior Software Engineer">
            </div>
          </div>
          <button type="submit" class="btn-indigo"><i class="bi bi-cloud-upload"></i> Upload Resume</button>
        </form>
      </div>
    </div>

    <?php elseif ($tab === 'letters'): ?>
    <!-- OFFER LETTERS -->
    <h4 class="fw-bold mb-1">Offer Letters</h4>
    <p class="text-muted mb-4">View and download all dispatched offer letters.</p>
    <div class="panel">
      <div class="panel-header"><span class="panel-title"><i class="bi bi-envelope-paper me-2 text-muted"></i>Dispatched Letters</span></div>
      <div class="panel-body p-0">
        <table class="portal-table">
          <thead>
            <tr><th>Candidate</th><th>Position</th><th>Issue Date</th><th>Preview</th><th>Download</th></tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>John Davidson</strong></td>
              <td>Senior Software Engineer</td>
              <td class="text-muted small">Aug 4, 2026</td>
              <td><a href="?tab=preview&file=letters/offer_john_davidson.txt" class="btn-ghost"><i class="bi bi-eye"></i> Preview</a></td>
              <td><a href="letters/offer_john_davidson.txt" download class="btn-ghost"><i class="bi bi-download"></i> Download</a></td>
            </tr>
            <tr>
              <td><strong>Priya Sharma</strong></td>
              <td>Lead Frontend Engineer</td>
              <td class="text-muted small">Aug 4, 2026</td>
              <td><a href="?tab=preview&file=letters/offer_priya_sharma.txt" class="btn-ghost"><i class="bi bi-eye"></i> Preview</a></td>
              <td><a href="letters/offer_priya_sharma.txt" download class="btn-ghost"><i class="bi bi-download"></i> Download</a></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <?php else: ?>
    <!-- FILE PREVIEW (Vulnerable Tab) -->
    <h4 class="fw-bold mb-1">Document Viewer</h4>
    <p class="text-muted mb-4">Preview files stored in the HR document management system.</p>

    <div class="panel mb-4">
      <div class="panel-header"><span class="panel-title"><i class="bi bi-folder2-open me-2 text-muted"></i>Quick File Access</span></div>
      <div class="panel-body">
        <p class="text-muted small mb-3">Specify a local file path to preview its contents in the viewer:</p>
        <form method="get" class="d-flex gap-2 flex-wrap">
          <input type="hidden" name="tab" value="preview">
          <input type="text" name="file" class="form-control bg-dark border-secondary text-white font-monospace"
            style="max-width:420px"
            placeholder="uploads/resume_john_davidson.txt"
            value="<?php echo htmlspecialchars($file ?? '', ENT_QUOTES); ?>">
          <button type="submit" class="btn-indigo"><i class="bi bi-eye"></i> Preview File</button>
        </form>
        <div class="mt-3 d-flex flex-wrap gap-2">
          <a href="?tab=preview&file=uploads/resume_john_davidson.txt" class="btn-ghost"><i class="bi bi-file-text me-1"></i>resume_john_davidson.txt</a>
          <a href="?tab=preview&file=uploads/resume_priya_sharma.txt" class="btn-ghost"><i class="bi bi-file-text me-1"></i>resume_priya_sharma.txt</a>
          <a href="?tab=preview&file=letters/offer_john_davidson.txt" class="btn-ghost"><i class="bi bi-envelope-paper me-1"></i>offer_john_davidson.txt</a>
          <a href="?tab=preview&file=letters/offer_priya_sharma.txt" class="btn-ghost"><i class="bi bi-envelope-paper me-1"></i>offer_priya_sharma.txt</a>
        </div>
      </div>
    </div>

    <?php if ($file && $preview_content !== null && $preview_content !== false): ?>
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title"><i class="bi bi-file-earmark-text me-2 text-muted"></i><?php echo htmlspecialchars($preview_name); ?></span>
        <a href="<?php echo htmlspecialchars($file); ?>" download class="btn-ghost"><i class="bi bi-download"></i> Download</a>
      </div>
      <div class="panel-body">
        <div class="file-viewer"><?php echo htmlspecialchars($preview_content); ?></div>
      </div>
    </div>
    <?php elseif ($file): ?>
    <div class="alert bg-dark border-danger text-danger">
      <i class="bi bi-x-circle me-2"></i>File not found or access denied: <code><?php echo htmlspecialchars($file); ?></code>
    </div>
    <?php endif; ?>

    <?php endif; ?>

  </main>
</div>

<footer>&copy; 2026 ACME Corporation — HR Information Systems</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-A3rJD856KowSb7dwlZdYKdNhnE+E03B9aA7D0A876B5A1"
  crossorigin="anonymous"></script>
<script>
  // Show selected filename in upload zone
  document.getElementById('resumeFile')?.addEventListener('change', function () {
    const label = this.closest('.upload-zone').querySelector('p');
    if (this.files.length) label.textContent = this.files[0].name;
  });
</script>
</body>
</html>
