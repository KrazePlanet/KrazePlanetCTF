<?php
// ApexMart E-Commerce Platform — Document & Template Rendering System
$tab  = $_GET['tab']  ?? 'orders';
$file = $_GET['file'] ?? null;   // Vulnerable LFI parameter

// Render requested file content from local server storage (Vulnerable to LFI)
$file_content = null;
$file_name    = null;
if ($file) {
    $file_name    = basename($file);
    $file_content = @file_get_contents($file);
}

// Sample e-commerce data
$orders = [
    [
        'id'       => 'ORD-99201',
        'customer' => 'Robert Vance',
        'email'    => 'robert.vance@email.com',
        'date'     => 'Aug 4, 2026',
        'total'    => '$402.49',
        'status'   => 'Shipped',
        'invoice'  => 'invoices/INV-2026-8801.txt',
        'label'    => 'shipping/LABEL-SHIP-4402.txt',
    ],
    [
        'id'       => 'ORD-99202',
        'customer' => 'Elena Rostova',
        'email'    => 'elena.r@email.com',
        'date'     => 'Aug 4, 2026',
        'total'    => '$189.50',
        'status'   => 'Processing',
        'invoice'  => 'invoices/INV-2026-8801.txt',
        'label'    => 'shipping/LABEL-SHIP-4402.txt',
    ],
];

$templates = [
    'Order Confirmation' => 'templates/email_order_confirmation.txt',
    'Product User Manual' => 'manuals/PROD-MANUAL-X100.txt',
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ApexMart E-Commerce Admin & Merchant Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="icon" href="/favicon.ico" />

  <style>
    :root {
      --bg-body:      #f4f6f8;
      --bg-card:      #ffffff;
      --border-color: #e2e8f0;
      --primary-orange:#ff6b00;
      --primary-hover: #e05e00;
      --coral-light:  #fff4ed;
      --text-dark:    #0f172a;
      --text-muted:   #64748b;
      --accent-blue:  #2563eb;
      --accent-green: #16a34a;
    }

    body {
      background-color: var(--bg-body);
      color: var(--text-dark);
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── Top Navbar ── */
    .store-navbar {
      background-color: #1a1d20;
      padding: 0.8rem 2rem;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .store-brand {
      font-weight: 800;
      font-size: 1.25rem;
      color: #fff;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .store-brand-badge {
      background: var(--primary-orange);
      color: #fff;
      font-size: 0.75rem;
      padding: 2px 8px;
      border-radius: 6px;
      font-weight: 700;
    }

    /* ── Navigation Strip ── */
    .nav-strip {
      background: #fff;
      border-bottom: 1px solid var(--border-color);
      padding: 0 2rem;
      display: flex;
      gap: 1rem;
    }
    .nav-strip-item {
      padding: 0.85rem 1rem;
      font-weight: 600;
      font-size: 0.9rem;
      color: var(--text-muted);
      text-decoration: none;
      border-bottom: 3px solid transparent;
      transition: all 0.2s;
    }
    .nav-strip-item:hover, .nav-strip-item.active {
      color: var(--primary-orange);
      border-bottom-color: var(--primary-orange);
    }

    /* ── Main Container ── */
    .main-container {
      flex: 1;
      padding: 2rem;
      max-width: 1280px;
      margin: 0 auto;
      width: 100%;
    }

    /* ── Cards ── */
    .content-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
      margin-bottom: 1.5rem;
      overflow: hidden;
    }
    .card-header-custom {
      padding: 1rem 1.5rem;
      background: #fafbfc;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    /* ── File Renderer Box ── */
    .file-input-box {
      background: var(--coral-light);
      border: 1px solid #ffd8c2;
      border-left: 4px solid var(--primary-orange);
      border-radius: 8px;
      padding: 1rem 1.25rem;
      margin-bottom: 1.5rem;
    }

    .file-viewer-display {
      background: #0f172a;
      color: #38bdf8;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.85rem;
      padding: 1.25rem;
      white-space: pre-wrap;
      word-break: break-all;
      max-height: 480px;
      overflow-y: auto;
      line-height: 1.7;
    }

    /* ── Table Styling ── */
    .custom-table {
      width: 100%;
      border-collapse: collapse;
    }
    .custom-table th {
      background: #f8fafc;
      padding: 0.75rem 1.25rem;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--text-muted);
      border-bottom: 1px solid var(--border-color);
    }
    .custom-table td {
      padding: 1rem 1.25rem;
      border-bottom: 1px solid var(--border-color);
      font-size: 0.88rem;
    }

    .status-pill {
      font-size: 0.75rem;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 20px;
    }
    .status-shipped { background: #dcfce7; color: var(--accent-green); }
    .status-proc    { background: #dbeafe; color: var(--accent-blue); }

    .btn-orange {
      background: var(--primary-orange);
      color: #fff;
      font-weight: 700;
      border-radius: 8px;
      padding: 6px 14px;
      font-size: 0.82rem;
      border: none;
      text-decoration: none;
      transition: background 0.2s;
    }
    .btn-orange:hover {
      background: var(--primary-hover);
      color: #fff;
    }

    footer {
      background: #fff;
      border-top: 1px solid var(--border-color);
      padding: 1.25rem;
      text-align: center;
      color: var(--text-muted);
      font-size: 0.82rem;
    }
  </style>
</head>
<body>

<!-- Store Navbar -->
<header class="store-navbar">
  <a class="store-brand" href="?tab=orders">
    <i class="bi bi-bag-check-fill text-warning fs-4"></i>
    ApexMart Merchant Portal
    <span class="store-brand-badge">ADMIN v5.8</span>
  </a>
  <div class="d-flex align-items-center gap-3 font-monospace small">
    <span class="text-secondary"><i class="bi bi-shop me-1"></i> Store: ApexMart Global</span>
    <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-20">Live Sync</span>
  </div>
</header>

<!-- Nav Strip -->
<nav class="nav-strip">
  <a class="nav-strip-item <?php echo $tab==='orders'?'active':''; ?>" href="?tab=orders">
    <i class="bi bi-cart3 me-1"></i> Orders & Shipments
  </a>
  <a class="nav-strip-item <?php echo $tab==='templates'?'active':''; ?>" href="?tab=templates">
    <i class="bi bi-file-earmark-code me-1"></i> Email & Document Templates
  </a>
  <a class="nav-strip-item <?php echo ($tab==='viewer'||$file)?'active':''; ?>" href="?tab=viewer">
    <i class="bi bi-eye me-1"></i> Document Renderer
  </a>
</nav>

<!-- Main Body -->
<main class="main-container">

  <!-- Document Renderer Input Bar -->
  <div class="file-input-box">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <span class="fw-bold text-dark"><i class="bi bi-file-earmark-arrow-down me-2 text-danger"></i>File & Template Rendering Engine</span>
      <span class="badge bg-warning text-dark font-monospace">Server Access</span>
    </div>
    <form method="get" class="d-flex gap-2 flex-wrap">
      <input type="hidden" name="tab" value="viewer">
      <input type="text" name="file"
        class="form-control form-control-sm font-monospace border-secondary"
        style="max-width:440px;"
        placeholder="invoices/INV-2026-8801.txt"
        value="<?php echo htmlspecialchars($file ?? '', ENT_QUOTES); ?>">
      <button type="submit" class="btn-orange">
        <i class="bi bi-play-fill me-1"></i>Render Document
      </button>
    </form>
  </div>

  <?php if ($file && $file_content !== null && $file_content !== false): ?>
  <!-- FILE VIEWER DISPLAY -->
  <div class="content-card">
    <div class="card-header-custom">
      <div>
        <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2 text-primary"></i><?php echo htmlspecialchars($file_name); ?></h6>
        <small class="text-muted font-monospace"><?php echo htmlspecialchars($file); ?></small>
      </div>
      <a href="<?php echo htmlspecialchars($file); ?>" download class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>Download Raw</a>
    </div>
    <div class="file-viewer-display"><?php echo htmlspecialchars($file_content); ?></div>
  </div>

  <?php elseif ($file): ?>
  <div class="alert alert-danger bg-dark text-danger border-danger">
    <i class="bi bi-exclamation-triangle me-2"></i>Document file not found: <code><?php echo htmlspecialchars($file); ?></code>
  </div>

  <?php elseif ($tab === 'templates'): ?>
  <!-- TEMPLATES TAB -->
  <div class="content-card">
    <div class="card-header-custom">
      <h6 class="mb-0 fw-bold"><i class="bi bi-file-code me-2"></i>E-Commerce System Templates</h6>
    </div>
    <table class="custom-table">
      <thead>
        <tr>
          <th>Template Name</th>
          <th>Resource File Path</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($templates as $name => $path): ?>
        <tr>
          <td><strong><?php echo htmlspecialchars($name); ?></strong></td>
          <td><code class="text-primary"><?php echo htmlspecialchars($path); ?></code></td>
          <td>
            <a href="?tab=viewer&file=<?php echo urlencode($path); ?>" class="btn-orange">
              <i class="bi bi-eye me-1"></i>Render Template
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php else: ?>
  <!-- ORDERS TAB -->
  <div class="content-card">
    <div class="card-header-custom">
      <h6 class="mb-0 fw-bold"><i class="bi bi-receipt me-2"></i>Recent Orders & Invoices</h6>
    </div>
    <table class="custom-table">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Customer</th>
          <th>Date</th>
          <th>Total</th>
          <th>Status</th>
          <th>Invoice & Shipping Labels</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td><code class="fw-bold"><?php echo $o['id']; ?></code></td>
          <td>
            <strong><?php echo htmlspecialchars($o['customer']); ?></strong><br>
            <small class="text-muted"><?php echo htmlspecialchars($o['email']); ?></small>
          </td>
          <td><?php echo $o['date']; ?></td>
          <td class="fw-bold text-success"><?php echo $o['total']; ?></td>
          <td><span class="status-pill status-<?php echo strtolower($o['status']); ?>"><?php echo $o['status']; ?></span></td>
          <td>
            <a href="?tab=viewer&file=<?php echo urlencode($o['invoice']); ?>" class="btn btn-sm btn-outline-dark me-1">
              <i class="bi bi-file-earmark-text me-1"></i>Invoice
            </a>
            <a href="?tab=viewer&file=<?php echo urlencode($o['label']); ?>" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-truck me-1"></i>Shipping Label
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</main>

<footer>
  &copy; 2026 ApexMart Global E-Commerce Solutions Inc. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-A3rJD856KowSb7dwlZdYKdNhnE+E03B9aA7D0A876B5A1"
  crossorigin="anonymous"></script>
</body>
</html>
