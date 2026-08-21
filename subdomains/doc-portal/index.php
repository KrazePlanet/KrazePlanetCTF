<?php
// Documentation Portal Application Controller
$doc = $_GET['doc'] ?? 'docs/user-guide.php';
$version = $_GET['version'] ?? 'v2.4';
$search = $_GET['q'] ?? '';
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DocuSphere Enterprise Hub | Developer Documentation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="icon" href="/favicon.ico" />

  <style>
    :root {
      --bg-dark: #090d16;
      --sidebar-bg: #111827;
      --card-bg: #1f2937;
      --accent-emerald: #10b981;
      --accent-cyan: #38bdf8;
      --text-main: #f8fafc;
      --text-muted: #cbd5e1;
    }

    body {
      background: #0b0f19;
      color: var(--text-main);
      min-height: 100vh;
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      display: flex;
      flex-direction: column;
    }

    .navbar {
      background: rgba(17, 24, 39, 0.95) !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(16px);
      padding: 0.75rem 0;
    }

    .navbar-brand {
      font-weight: 800;
      font-size: 1.35rem;
      background: linear-gradient(90deg, #38bdf8, #34d399);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .sidebar {
      background: var(--sidebar-bg);
      border-right: 1px solid rgba(255, 255, 255, 0.1);
      min-height: calc(100vh - 65px);
      padding: 1.5rem 1rem;
    }

    .sidebar-heading {
      color: #94a3b8;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-top: 1.5rem;
      margin-bottom: 0.5rem;
      padding-left: 0.5rem;
    }

    .doc-nav-link {
      color: var(--text-muted);
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      padding: 0.55rem 0.85rem;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: all 0.2s ease;
      margin-bottom: 2px;
    }

    .doc-nav-link:hover,
    .doc-nav-link.active {
      color: #ffffff;
      background: rgba(56, 189, 248, 0.15);
      border-left: 3px solid var(--accent-cyan);
    }

    .search-input {
      background: rgba(31, 41, 55, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #ffffff;
      border-radius: 10px;
      padding: 0.5rem 1rem 0.5rem 2.4rem;
      font-size: 0.9rem;
    }

    .search-input:focus {
      background: rgba(31, 41, 55, 0.95);
      border-color: var(--accent-cyan);
      color: #ffffff;
      box-shadow: 0 0 0 0.2rem rgba(56, 189, 248, 0.2);
    }

    .search-wrapper {
      position: relative;
      max-width: 320px;
    }

    .search-wrapper i {
      position: absolute;
      left: 0.85rem;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
    }

    .doc-viewport {
      background: rgba(31, 41, 55, 0.5);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(12px);
      min-height: 500px;
    }

    .btn-portal {
      background: linear-gradient(135deg, #10b981, #059669);
      border: 1px solid rgba(52, 211, 153, 0.4);
      color: white;
      font-weight: 700;
      border-radius: 0.75rem;
      padding: 0.5rem 1.2rem;
      transition: all 0.3s;
      text-decoration: none;
      box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
    }

    .btn-portal:hover {
      background: linear-gradient(135deg, #059669, #047857);
      color: white;
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
    }

    footer {
      margin-top: auto;
      padding: 1.25rem 0;
      background: #090d16;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      color: var(--text-muted);
      font-size: 0.85rem;
    }
  </style>
</head>

<body>
  <!-- Top Navigation Header -->
  <nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid px-4">
      <a class="navbar-brand me-4" href="?doc=docs/user-guide.php&version=<?php echo urlencode($version); ?>">
        <i class="bi bi-journal-text me-2"></i>DocuSphere Portal
      </a>

      <!-- Documentation Search Bar -->
      <form class="d-flex search-wrapper me-auto" action="" method="get">
        <input type="hidden" name="doc" value="<?php echo htmlspecialchars($doc, ENT_QUOTES); ?>">
        <input type="hidden" name="version" value="<?php echo htmlspecialchars($version, ENT_QUOTES); ?>">
        <i class="bi bi-search"></i>
        <input class="form-control search-input" type="search" name="q" placeholder="Search documentation..." value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>">
      </form>

      <div class="d-flex align-items-center gap-3">
        <!-- PDF Download Shortcut -->
        <a href="?doc=<?php echo urlencode($doc); ?>&export=pdf" class="btn btn-outline-light btn-sm rounded-pill px-3" onclick="alert('Compiling documentation PDF package...'); return false;">
          <i class="bi bi-file-earmark-pdf text-danger me-1"></i> Export PDF
        </a>
        <a href="https://discord.krazeplanet.com" target="_blank" rel="noopener noreferrer" class="btn btn-portal btn-sm">
          <i class="bi bi-discord me-1"></i> Join Discord
        </a>
      </div>
    </div>
  </nav>

  <!-- Main Grid Layout -->
  <div class="container-fluid">
    <div class="row">

      <!-- Left Sidebar Navigation & Version Selector -->
      <div class="col-lg-3 col-xl-2 sidebar">
        <!-- Version Selector Dropdown -->
        <div class="mb-3">
          <label class="form-label text-muted small fw-semibold font-monospace">Documentation Version</label>
          <select class="form-select bg-dark text-white border-secondary small" onchange="location = this.value;">
            <option value="?doc=<?php echo urlencode($doc); ?>&version=v2.4" <?php echo ($version === 'v2.4') ? 'selected' : ''; ?>>v2.4 (Latest Release)</option>
            <option value="?doc=<?php echo urlencode($doc); ?>&version=v2.0" <?php echo ($version === 'v2.0') ? 'selected' : ''; ?>>v2.0 (LTS Build)</option>
            <option value="?doc=<?php echo urlencode($doc); ?>&version=v1.8" <?php echo ($version === 'v1.8') ? 'selected' : ''; ?>>v1.8 (Legacy)</option>
          </select>
        </div>

        <div class="sidebar-heading">Core Documentation</div>
        <a class="doc-nav-link <?php echo (strpos($doc, 'user-guide') !== false) ? 'active' : ''; ?>" href="?doc=docs/user-guide.php&version=<?php echo urlencode($version); ?>">
          <i class="bi bi-book text-success"></i> User Guide
        </a>
        <a class="doc-nav-link <?php echo (strpos($doc, 'api-reference') !== false) ? 'active' : ''; ?>" href="?doc=docs/api-reference.php&version=<?php echo urlencode($version); ?>">
          <i class="bi bi-code-slash text-info"></i> API Reference
        </a>
        <a class="doc-nav-link <?php echo (strpos($doc, 'installation') !== false) ? 'active' : ''; ?>" href="?doc=docs/installation.php&version=<?php echo urlencode($version); ?>">
          <i class="bi bi-download text-warning"></i> Installation &amp; Setup
        </a>
        <a class="doc-nav-link <?php echo (strpos($doc, 'architecture') !== false) ? 'active' : ''; ?>" href="?doc=docs/architecture.php&version=<?php echo urlencode($version); ?>">
          <i class="bi bi-diagram-3 text-danger"></i> System Architecture
        </a>

        <div class="sidebar-heading">Tools &amp; PDF Services</div>
        <a class="doc-nav-link" href="?doc=<?php echo urlencode($doc); ?>&export=pdf" onclick="alert('Compiling documentation PDF package...'); return false;">
          <i class="bi bi-file-earmark-pdf text-danger"></i> PDF Compiler
        </a>
      </div>

      <!-- Center Document Viewing Area (Dynamic File Includer) -->
      <div class="col-lg-9 col-xl-10 p-4">
        <?php if (!empty($search)): ?>
          <div class="alert alert-info bg-dark border-info text-info mb-4">
            <i class="bi bi-search me-2"></i>Showing search results for: <strong><?php echo htmlspecialchars($search, ENT_QUOTES); ?></strong> in document tree (<code><?php echo htmlspecialchars($doc, ENT_QUOTES); ?></code>)
          </div>
        <?php endif; ?>

        <!-- Rendered Document Viewport (LFI Execution Gateway) -->
        <div class="doc-viewport">
          <?php
            if (!empty($doc)) {
                // Document viewer loads pages dynamically from the filesystem (Vulnerable to LFI)
                @include($doc);
            }
          ?>
        </div>
      </div>

    </div>
  </div>

  <!-- Footer -->
  <footer class="text-center">
    <div class="container-fluid">
      <p class="mb-0">&copy; 2026 DocuSphere Developer Platform. All rights reserved.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-A3rJD856KowSb7dwlZdYKdNhnE+E03B9aA7D0A876B5A1"
    crossorigin="anonymous"></script>
</body>

</html>
