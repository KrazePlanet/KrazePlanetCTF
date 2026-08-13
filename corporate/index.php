<?php
// Corporate Portal Application Controller
$page = $_GET['page'] ?? 'pages/about.php';

// Accept alternative parameter names for routing if passed
if (isset($_GET['file'])) {
    $page = $_GET['file'];
} elseif (isset($_GET['view'])) {
    $page = $_GET['view'];
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ApexCorp Solutions | Enterprise Cloud &amp; Security</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="icon" href="/favicon.ico" />

  <style>
    :root {
      --bg-dark: #090d16;
      --card-bg: #151d2a;
      --accent-emerald: #10b981;
      --accent-cyan: #38bdf8;
      --text-primary: #f8fafc;
      --text-muted: #cbd5e1;
    }

    body {
      background: radial-gradient(circle at 50% 0%, #1e293b 0%, #0f172a 65%, #090d16 100%);
      background-attachment: fixed;
      color: var(--text-primary);
      min-height: 100vh;
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      display: flex;
      flex-direction: column;
    }

    .navbar {
      background: rgba(15, 23, 42, 0.92) !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(16px);
      padding: 1rem 0;
    }

    .navbar-brand {
      font-weight: 800;
      font-size: 1.45rem;
      background: linear-gradient(90deg, #10b981, #38bdf8);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      letter-spacing: -0.5px;
    }

    .nav-link {
      color: var(--text-muted) !important;
      font-weight: 600;
      padding: 0.5rem 1.1rem !important;
      border-radius: 0.5rem;
      transition: all 0.25s;
    }

    .nav-link:hover,
    .nav-link.active {
      color: #ffffff !important;
      background: rgba(255, 255, 255, 0.08);
    }

    .hero-banner {
      background: radial-gradient(ellipse at top, rgba(56, 189, 248, 0.12), transparent 70%);
      padding: 3rem 0 2rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .hero-title {
      font-weight: 800;
      font-size: 2.6rem;
      background: linear-gradient(90deg, #ffffff 30%, #38bdf8 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      letter-spacing: -0.5px;
    }

    .hero-subtitle {
      color: var(--text-muted);
      font-size: 1.15rem;
      max-width: 650px;
      margin: 0 auto;
    }

    .btn-portal {
      background: linear-gradient(135deg, #10b981, #059669);
      border: 1px solid rgba(52, 211, 153, 0.4);
      color: white;
      font-weight: 700;
      border-radius: 0.75rem;
      padding: 0.6rem 1.4rem;
      transition: all 0.3s;
      text-decoration: none;
      box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
    }

    .btn-portal:hover {
      background: linear-gradient(135deg, #059669, #047857);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
    }

    footer {
      margin-top: auto;
      padding: 1.75rem 0;
      background: rgba(15, 23, 42, 0.95);
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      color: var(--text-muted);
      font-size: 0.875rem;
    }
  </style>
</head>

<body>
  <!-- Corporate Header -->
  <nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
      <a class="navbar-brand" href="?page=pages/about.php">
        <i class="bi bi-building-gear me-2"></i>ApexCorp Solutions
      </a>
      <button class="navbar-toggler navbar-dark" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link <?php echo (strpos($page, 'about') !== false) ? 'active' : ''; ?>" href="?page=pages/about.php">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo (strpos($page, 'careers') !== false) ? 'active' : ''; ?>" href="?page=pages/careers.php">Careers</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo (strpos($page, 'blog') !== false) ? 'active' : ''; ?>" href="?page=pages/blog.php">Blog</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo (strpos($page, 'contact') !== false) ? 'active' : ''; ?>" href="?page=pages/contact.php">Contact</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo (strpos($page, 'privacy') !== false) ? 'active' : ''; ?>" href="?page=pages/privacy.php">Privacy Policy</a>
          </li>
        </ul>
        <a href="https://discord.gg/Ujg69RM6qd" target="_blank" rel="noopener noreferrer" class="btn btn-portal ms-lg-3">
          <i class="bi bi-discord me-2"></i>Join Community
        </a>
      </div>
    </div>
  </nav>

  <!-- Hero Header -->
  <div class="hero-banner text-center">
    <div class="container">
      <h1 class="hero-title">ApexCorp Enterprise Portal</h1>
      <p class="hero-subtitle">Next-Generation Infrastructure, High-Availability Cloud Services &amp; Developer APIs</p>
    </div>
  </div>

  <!-- Dynamic Content Area (Vulnerable Page Component Includer) -->
  <main class="container my-5">
    <?php
      if (!empty($page)) {
          // Dynamic Page Component Inclusion (Vulnerable to LFI)
          @include($page);
      }
    ?>
  </main>

  <!-- Footer -->
  <footer class="text-center">
    <div class="container">
      <p class="mb-0">&copy; 2026 ApexCorp Solutions. All rights reserved.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-A3rJD856KowSb7dwlZdYKdNhnE+E03B9aA7D0A876B5A1"
    crossorigin="anonymous"></script>
</body>

</html>
