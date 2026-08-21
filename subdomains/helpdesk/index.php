<?php
// HelpDesk Portal — Support Ticket & Attachment Viewer
$tab    = $_GET['tab']   ?? 'dashboard';
$attach = $_GET['attach'] ?? null;
$msg    = '';

// Handle new ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject'])) {
    $msg = 'ticket_submitted';
}

// Handle screenshot upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['screenshot'])) {
    $name = 'ticket_upload_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($_FILES['screenshot']['name']));
    $dest = __DIR__ . '/attachments/' . $name;
    if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $dest)) {
        $msg = 'upload_ok';
    } else {
        $msg = 'upload_fail';
    }
}

// Load attachment content for preview (Vulnerable to LFI)
$attach_content = null;
$attach_name    = null;
if ($attach) {
    $attach_name    = basename($attach);
    $attach_content = @file_get_contents($attach);
}

// Mock ticket dataset
$tickets = [
    ['id'=>'#1042','user'=>'mark.henderson','subject'=>'Login portal 500 error after password reset','priority'=>'High','status'=>'open','date'=>'Aug 4, 2026','attach'=>'attachments/ticket_1042_screenshot.txt'],
    ['id'=>'#1039','user'=>'sarah.chen','subject'=>'API Gateway returning 403 on /reports endpoint','priority'=>'Medium','status'=>'pending','date'=>'Aug 3, 2026','attach'=>'attachments/ticket_1039_error_log.txt'],
    ['id'=>'#1035','user'=>'devops-bot','subject'=>'Deployment config diff — staging vs production mismatch','priority'=>'Low','status'=>'resolved','date'=>'Aug 1, 2026','attach'=>'attachments/ticket_1035_config_diff.txt'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SupportDesk Pro — Enterprise Help Center</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" href="/favicon.ico" />

  <style>
    :root {
      --bg:      #090d16;
      --sidebar: #0e1525;
      --card:    #131d2e;
      --card2:   #182237;
      --border:  rgba(255,255,255,0.08);
      --accent:  #0ea5e9;   /* sky blue */
      --accent2: #38bdf8;
      --success: #22c55e;
      --warn:    #f59e0b;
      --danger:  #ef4444;
      --purple:  #a78bfa;
      --text:    #f1f5f9;
      --muted:   #94a3b8;
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
      padding: .8rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky; top: 0; z-index: 100;
      backdrop-filter: blur(14px);
    }
    .topbar-brand {
      font-weight: 800; font-size: 1.25rem;
      background: linear-gradient(90deg, #0ea5e9, #38bdf8);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .topbar-right { display: flex; align-items: center; gap: 12px; }
    .notif-btn {
      position: relative;
      background: rgba(255,255,255,.06);
      border: 1px solid var(--border);
      border-radius: 10px;
      width: 36px; height: 36px;
      display: flex; align-items: center; justify-content: center;
      color: var(--muted); cursor: pointer; text-decoration: none;
      transition: all .2s;
    }
    .notif-btn:hover { background: rgba(255,255,255,.12); color: #fff; }
    .notif-dot {
      position: absolute; top: 6px; right: 6px;
      width: 7px; height: 7px;
      background: var(--danger); border-radius: 50%;
    }
    .user-chip {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(255,255,255,.06);
      border: 1px solid var(--border); border-radius: 20px;
      padding: 5px 14px; font-size: .84rem; font-weight: 600;
    }
    .avatar-sm {
      width: 26px; height: 26px; border-radius: 50%;
      background: linear-gradient(135deg, #0ea5e9, #38bdf8);
      display: flex; align-items: center; justify-content: center;
      font-size: .65rem; font-weight: 800; color: #fff; flex-shrink: 0;
    }

    /* ── Layout ── */
    .layout { display: flex; flex: 1; }

    /* ── Sidebar ── */
    .sidebar {
      width: 235px; background: var(--sidebar);
      border-right: 1px solid var(--border);
      padding: 1.4rem 1rem; flex-shrink: 0;
    }
    .sec-label {
      font-size: .68rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .09em; color: var(--muted);
      padding: 0 .5rem; margin: 1rem 0 .4rem;
    }
    .side-link {
      display: flex; align-items: center; gap: 10px;
      padding: .55rem .85rem; border-radius: 10px;
      color: var(--muted); text-decoration: none;
      font-size: .88rem; font-weight: 500;
      transition: all .2s; margin-bottom: 2px;
    }
    .side-link:hover, .side-link.active {
      background: rgba(14,165,233,.14); color: var(--accent2);
    }
    .side-badge {
      margin-left: auto; background: rgba(14,165,233,.18);
      color: var(--accent2); border-radius: 10px;
      font-size: .7rem; font-weight: 700; padding: 1px 7px;
    }

    /* ── Main ── */
    .main { flex: 1; padding: 2rem; overflow-y: auto; }

    /* ── Stat Cards ── */
    .stat-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 14px; padding: 1.3rem 1.1rem;
      display: flex; align-items: center; gap: 1rem;
    }
    .stat-icon {
      width: 44px; height: 44px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.25rem; flex-shrink: 0;
    }
    .stat-label { font-size: .75rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
    .stat-num   { font-size: 1.85rem; font-weight: 800; line-height: 1; }

    /* ── Panel ── */
    .panel { background: var(--card); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
    .panel-hd {
      background: var(--card2); border-bottom: 1px solid var(--border);
      padding: .95rem 1.35rem; display: flex; align-items: center; justify-content: space-between;
    }
    .panel-title { font-weight: 700; font-size: .97rem; }
    .panel-bd { padding: 1.35rem; }

    /* ── Ticket Table ── */
    .tkt-table { width: 100%; border-collapse: collapse; }
    .tkt-table th {
      background: var(--card2); color: var(--muted);
      font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
      padding: .7rem 1rem; border-bottom: 1px solid var(--border);
    }
    .tkt-table td {
      padding: .8rem 1rem; border-bottom: 1px solid var(--border);
      font-size: .88rem; vertical-align: middle;
    }
    .tkt-table tr:hover td { background: rgba(255,255,255,.025); }
    .tkt-table tbody tr:last-child td { border-bottom: none; }

    /* ── Priority / Status badges ── */
    .badge-p {
      display: inline-block; font-size: .7rem; font-weight: 700;
      padding: 2px 9px; border-radius: 20px;
    }
    .p-high   { background: rgba(239,68,68,.15); color: var(--danger); border: 1px solid rgba(239,68,68,.3); }
    .p-medium { background: rgba(245,158,11,.15); color: var(--warn);   border: 1px solid rgba(245,158,11,.3); }
    .p-low    { background: rgba(34,197,94,.15);  color: var(--success); border: 1px solid rgba(34,197,94,.3); }
    .s-open   { background: rgba(14,165,233,.15); color: var(--accent2); border: 1px solid rgba(14,165,233,.3); }
    .s-pending{ background: rgba(167,139,250,.15);color: var(--purple);  border: 1px solid rgba(167,139,250,.3); }
    .s-resolved{background: rgba(34,197,94,.1);  color: var(--success); border: 1px solid rgba(34,197,94,.25);}

    /* ── Attachment Viewer ── */
    .attach-viewer {
      background: #05080f;
      border: 1px solid rgba(14,165,233,.2);
      border-radius: 10px;
      font-family: 'JetBrains Mono','Fira Code',monospace;
      font-size: .83rem; color: #7dd3fc;
      padding: 1.2rem 1.4rem;
      white-space: pre-wrap; word-break: break-all;
      max-height: 440px; overflow-y: auto; line-height: 1.75;
    }

    /* ── Buttons ── */
    .btn-sky {
      background: linear-gradient(135deg, #0ea5e9, #0284c7);
      border: 1px solid rgba(14,165,233,.4); color: #fff;
      font-weight: 700; border-radius: 9px; padding: 7px 16px;
      font-size: .83rem; text-decoration: none;
      display: inline-flex; align-items: center; gap: 6px;
      transition: all .2s; cursor: pointer;
    }
    .btn-sky:hover { background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff; transform: translateY(-1px); }
    .btn-ghost {
      background: rgba(255,255,255,.05); border: 1px solid var(--border);
      color: var(--muted); font-weight: 600; border-radius: 9px;
      padding: 6px 13px; font-size: .82rem; text-decoration: none;
      display: inline-flex; align-items: center; gap: 5px; transition: all .2s;
    }
    .btn-ghost:hover { background: rgba(255,255,255,.1); color: #fff; }

    /* ── Upload Zone ── */
    .upload-zone {
      border: 2px dashed rgba(14,165,233,.3); border-radius: 14px;
      padding: 2.4rem; text-align: center;
      background: rgba(14,165,233,.04); cursor: pointer; transition: all .25s;
    }
    .upload-zone:hover { border-color: var(--accent); background: rgba(14,165,233,.09); }

    /* ── FAQ ── */
    .faq-item {
      background: var(--card2); border: 1px solid var(--border);
      border-radius: 12px; padding: 1.2rem 1.4rem; margin-bottom: .75rem;
    }
    .faq-q { font-weight: 700; color: #fff; margin-bottom: .5rem; }
    .faq-a { color: var(--muted); font-size: .9rem; line-height: 1.65; }

    footer { padding: 1.2rem; text-align: center; color: var(--muted); font-size: .8rem; border-top: 1px solid var(--border); }
  </style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
  <div class="topbar-brand"><i class="bi bi-headset me-2"></i>SupportDesk Pro</div>
  <div class="topbar-right">
    <a class="notif-btn" href="#" onclick="return false;" title="Notifications">
      <i class="bi bi-bell"></i>
      <span class="notif-dot"></span>
    </a>
    <div class="user-chip">
      <div class="avatar-sm">SD</div>
      <span>support.admin@corp.internal</span>
    </div>
  </div>
</div>

<div class="layout">

  <!-- Sidebar -->
  <nav class="sidebar">
    <div class="sec-label">Main</div>
    <a class="side-link <?php echo $tab==='dashboard'?'active':''; ?>" href="?tab=dashboard">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="sec-label">Tickets</div>
    <a class="side-link <?php echo $tab==='submit'?'active':''; ?>" href="?tab=submit">
      <i class="bi bi-plus-circle"></i> Submit Ticket
    </a>
    <a class="side-link <?php echo $tab==='history'?'active':''; ?>" href="?tab=history">
      <i class="bi bi-clock-history"></i> Ticket History
      <span class="side-badge">3</span>
    </a>
    <a class="side-link <?php echo $tab==='upload'?'active':''; ?>" href="?tab=upload">
      <i class="bi bi-image"></i> Upload Screenshots
    </a>

    <div class="sec-label">Files</div>
    <a class="side-link <?php echo ($tab==='attachments'||$attach)?'active':''; ?>" href="?tab=attachments">
      <i class="bi bi-paperclip"></i> Attachments
      <span class="side-badge">3</span>
    </a>

    <div class="sec-label">Help</div>
    <a class="side-link <?php echo $tab==='faq'?'active':''; ?>" href="?tab=faq">
      <i class="bi bi-question-circle"></i> FAQ
    </a>
  </nav>

  <!-- Main Content -->
  <main class="main">

    <?php if ($tab === 'dashboard'): ?>
    <!-- DASHBOARD -->
    <h4 class="fw-bold mb-1">Support Dashboard</h4>
    <p class="text-muted mb-4">Real-time overview of open support requests and team workload.</p>

    <div class="row g-3 mb-4">
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(14,165,233,.13)"><i class="bi bi-ticket-perforated" style="color:#38bdf8"></i></div>
          <div><div class="stat-label">Open Tickets</div><div class="stat-num" style="color:#38bdf8">12</div></div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(245,158,11,.12)"><i class="bi bi-hourglass-split" style="color:#f59e0b"></i></div>
          <div><div class="stat-label">Pending</div><div class="stat-num" style="color:#f59e0b">5</div></div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(34,197,94,.12)"><i class="bi bi-check2-circle" style="color:#22c55e"></i></div>
          <div><div class="stat-label">Resolved Today</div><div class="stat-num" style="color:#22c55e">7</div></div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(167,139,250,.12)"><i class="bi bi-paperclip" style="color:#a78bfa"></i></div>
          <div><div class="stat-label">Attachments</div><div class="stat-num" style="color:#a78bfa">3</div></div>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-hd">
        <span class="panel-title"><i class="bi bi-activity me-2 text-muted"></i>Recent Tickets</span>
        <a href="?tab=history" class="btn-ghost">View All</a>
      </div>
      <div>
        <table class="tkt-table">
          <tbody>
            <?php foreach ($tickets as $t): ?>
            <tr>
              <td><code class="text-muted"><?php echo $t['id']; ?></code></td>
              <td><?php echo htmlspecialchars($t['subject']); ?></td>
              <td><span class="badge-p p-<?php echo strtolower($t['priority']); ?>"><?php echo $t['priority']; ?></span></td>
              <td><span class="badge-p s-<?php echo $t['status']; ?>"><?php echo ucfirst($t['status']); ?></span></td>
              <td><a href="?tab=attachments&attach=<?php echo urlencode($t['attach']); ?>" class="btn-ghost"><i class="bi bi-eye"></i> View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php elseif ($tab === 'submit'): ?>
    <!-- SUBMIT TICKET -->
    <h4 class="fw-bold mb-1">Submit Support Ticket</h4>
    <p class="text-muted mb-4">Describe your issue and attach any relevant screenshots or logs.</p>

    <?php if ($msg === 'ticket_submitted'): ?>
      <div class="alert bg-dark border-success text-success mb-4">
        <i class="bi bi-check-circle me-2"></i>Ticket submitted! Our support team will respond within 4 business hours.
      </div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-hd"><span class="panel-title"><i class="bi bi-plus-circle me-2 text-muted"></i>New Ticket</span></div>
      <div class="panel-bd">
        <form method="post" enctype="multipart/form-data">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label text-muted small fw-semibold">Subject / Issue Summary</label>
              <input type="text" name="subject" class="form-control bg-dark border-secondary text-white" placeholder="e.g. Cannot access dashboard after login" required>
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted small fw-semibold">Priority</label>
              <select name="priority" class="form-select bg-dark border-secondary text-white">
                <option>Low</option><option selected>Medium</option><option>High</option><option>Critical</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted small fw-semibold">Category</label>
              <select name="category" class="form-select bg-dark border-secondary text-white">
                <option>Authentication</option><option>API / Integration</option>
                <option>Performance</option><option>Billing</option><option>Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted small fw-semibold">Affected Service</label>
              <input type="text" name="service" class="form-control bg-dark border-secondary text-white" placeholder="e.g. Login Portal, Reporting API">
            </div>
            <div class="col-12">
              <label class="form-label text-muted small fw-semibold">Detailed Description</label>
              <textarea name="description" class="form-control bg-dark border-secondary text-white" rows="5"
                placeholder="Steps to reproduce, expected vs actual behaviour, error messages..."></textarea>
            </div>
            <div class="col-12">
              <label class="form-label text-muted small fw-semibold">Attach Screenshot / Log (optional)</label>
              <input type="file" name="screenshot" class="form-control bg-dark border-secondary text-white" accept=".txt,.png,.jpg,.log">
            </div>
            <div class="col-12">
              <button type="submit" class="btn-sky"><i class="bi bi-send"></i> Submit Ticket</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <?php elseif ($tab === 'history'): ?>
    <!-- TICKET HISTORY -->
    <h4 class="fw-bold mb-1">Ticket History</h4>
    <p class="text-muted mb-4">All support tickets submitted by your organisation.</p>
    <div class="panel">
      <div class="panel-hd">
        <span class="panel-title"><i class="bi bi-clock-history me-2 text-muted"></i>All Tickets</span>
        <a href="?tab=submit" class="btn-sky"><i class="bi bi-plus-lg"></i> New Ticket</a>
      </div>
      <table class="tkt-table">
        <thead>
          <tr><th>ID</th><th>Subject</th><th>Submitted By</th><th>Date</th><th>Priority</th><th>Status</th><th>Attachment</th></tr>
        </thead>
        <tbody>
          <?php foreach ($tickets as $t): ?>
          <tr>
            <td><code><?php echo $t['id']; ?></code></td>
            <td><?php echo htmlspecialchars($t['subject']); ?></td>
            <td class="text-muted small"><?php echo htmlspecialchars($t['user']); ?></td>
            <td class="text-muted small"><?php echo $t['date']; ?></td>
            <td><span class="badge-p p-<?php echo strtolower($t['priority']); ?>"><?php echo $t['priority']; ?></span></td>
            <td><span class="badge-p s-<?php echo $t['status']; ?>"><?php echo ucfirst($t['status']); ?></span></td>
            <td>
              <a href="?tab=attachments&attach=<?php echo urlencode($t['attach']); ?>" class="btn-ghost btn-sm"><i class="bi bi-eye"></i> Preview</a>
              <a href="<?php echo htmlspecialchars($t['attach']); ?>" download class="btn-ghost btn-sm ms-1"><i class="bi bi-download"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php elseif ($tab === 'upload'): ?>
    <!-- UPLOAD SCREENSHOTS -->
    <h4 class="fw-bold mb-1">Upload Screenshots</h4>
    <p class="text-muted mb-4">Attach screenshots or log files to open support tickets.</p>

    <?php if ($msg === 'upload_ok'): ?>
      <div class="alert bg-dark border-success text-success mb-4"><i class="bi bi-check-circle me-2"></i>File uploaded to <code>attachments/</code>.</div>
    <?php elseif ($msg === 'upload_fail'): ?>
      <div class="alert bg-dark border-danger text-danger mb-4"><i class="bi bi-x-circle me-2"></i>Upload failed. Check file type and size.</div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-hd"><span class="panel-title"><i class="bi bi-image me-2 text-muted"></i>Screenshot Upload</span></div>
      <div class="panel-bd">
        <form method="post" enctype="multipart/form-data">
          <div class="upload-zone mb-4" onclick="document.getElementById('ssFile').click()">
            <i class="bi bi-cloud-arrow-up fs-1 mb-2" style="color:#0ea5e9"></i>
            <p class="mb-1 fw-semibold">Click to select a file</p>
            <small class="text-muted">Accepted: .png, .jpg, .txt, .log (max 10MB)</small>
            <input type="file" name="screenshot" id="ssFile" class="d-none" accept=".png,.jpg,.txt,.log">
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label text-muted small fw-semibold">Related Ticket ID</label>
              <input type="text" class="form-control bg-dark border-secondary text-white" placeholder="#1042">
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted small fw-semibold">File Description</label>
              <input type="text" class="form-control bg-dark border-secondary text-white" placeholder="Login error screenshot">
            </div>
          </div>
          <button type="submit" class="btn-sky"><i class="bi bi-cloud-upload"></i> Upload File</button>
        </form>
      </div>
    </div>

    <?php elseif ($tab === 'faq'): ?>
    <!-- FAQ -->
    <h4 class="fw-bold mb-1">Frequently Asked Questions</h4>
    <p class="text-muted mb-4">Common issues and self-service solutions.</p>

    <div class="faq-item">
      <div class="faq-q"><i class="bi bi-question-circle text-info me-2"></i>How do I reset my corporate portal password?</div>
      <div class="faq-a">Navigate to <code>/auth/forgot-password</code> and enter your corporate email. You will receive a reset link valid for 30 minutes.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q"><i class="bi bi-question-circle text-info me-2"></i>What file types can I attach to a ticket?</div>
      <div class="faq-a">Support attachments accept <code>.txt</code>, <code>.log</code>, <code>.png</code>, <code>.jpg</code>, and <code>.pdf</code> up to 10MB. Files are stored to <code>attachments/</code> on the server and can be previewed via the File Attachment Viewer.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q"><i class="bi bi-question-circle text-info me-2"></i>How are ticket attachments previewed?</div>
      <div class="faq-a">The attachment preview component reads files directly from the support storage path using the <code>?attach=</code> URL parameter. Navigate to the Attachments tab and enter the file path to view any stored document.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q"><i class="bi bi-question-circle text-info me-2"></i>What is the SLA for critical tickets?</div>
      <div class="faq-a">Critical tickets receive a first-response within 30 minutes during business hours. P1/High tickets are addressed within 4 hours.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q"><i class="bi bi-question-circle text-info me-2"></i>Can I download my ticket attachments?</div>
      <div class="faq-a">Yes. From the Ticket History or Attachments tab, click the <strong>Download</strong> button next to any attachment to save the file locally.</div>
    </div>

    <?php else: ?>
    <!-- ATTACHMENTS / PREVIEW (Vulnerable Tab) -->
    <h4 class="fw-bold mb-1">Attachment Viewer</h4>
    <p class="text-muted mb-4">Preview support ticket attachments stored on the server.</p>

    <div class="panel mb-4">
      <div class="panel-hd"><span class="panel-title"><i class="bi bi-folder2-open me-2 text-muted"></i>Attachment File Access</span></div>
      <div class="panel-bd">
        <p class="text-muted small mb-3">Enter an attachment path to preview in the viewer:</p>
        <form method="get" class="d-flex gap-2 flex-wrap mb-3">
          <input type="hidden" name="tab" value="attachments">
          <input type="text" name="attach" class="form-control bg-dark border-secondary text-white font-monospace"
            style="max-width:430px"
            placeholder="attachments/ticket_1042_screenshot.txt"
            value="<?php echo htmlspecialchars($attach ?? '', ENT_QUOTES); ?>">
          <button type="submit" class="btn-sky"><i class="bi bi-eye"></i> Preview</button>
        </form>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($tickets as $t): ?>
          <a href="?tab=attachments&attach=<?php echo urlencode($t['attach']); ?>" class="btn-ghost">
            <i class="bi bi-file-text me-1"></i><?php echo basename($t['attach']); ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <?php if ($attach && $attach_content !== null && $attach_content !== false): ?>
    <div class="panel">
      <div class="panel-hd">
        <span class="panel-title"><i class="bi bi-file-earmark-text me-2 text-muted"></i><?php echo htmlspecialchars($attach_name); ?></span>
        <a href="<?php echo htmlspecialchars($attach); ?>" download class="btn-ghost"><i class="bi bi-download"></i> Download</a>
      </div>
      <div class="panel-bd">
        <div class="attach-viewer"><?php echo htmlspecialchars($attach_content); ?></div>
      </div>
    </div>
    <?php elseif ($attach): ?>
    <div class="alert bg-dark border-danger text-danger">
      <i class="bi bi-x-circle me-2"></i>Attachment not found: <code><?php echo htmlspecialchars($attach); ?></code>
    </div>
    <?php endif; ?>

    <?php endif; ?>

  </main>
</div>

<footer>&copy; 2026 SupportDesk Pro — Enterprise IT Help Center</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-A3rJD856KowSb7dwlZdYKdNhnE+E03B9aA7D0A876B5A1"
  crossorigin="anonymous"></script>
<script>
  document.getElementById('ssFile')?.addEventListener('change', function() {
    const p = this.closest('.upload-zone').querySelector('p');
    if (this.files.length) p.textContent = this.files[0].name;
  });
</script>
</body>
</html>