<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>About - Web Security Training Platform</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="icon" href="favicon.ico" />
  
  <!-- Google Fonts: Inter, Outfit, JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  
  <style>
    :root {
      --bg-dark: #070b14;
      --bg-card: rgba(15, 23, 42, 0.75);
      --border-card: rgba(255, 255, 255, 0.08);
      --accent-green: #10b981;
      --accent-green-glow: rgba(16, 185, 129, 0.3);
      --accent-blue: #38bdf8;
      --accent-orange: #f59e0b;
      --accent-red: #f43f5e;
    }

    * {
      box-sizing: border-box;
    }

    body {
      background-color: var(--bg-dark);
      background-image: 
        radial-gradient(at 0% 0%, rgba(56, 189, 248, 0.08) 0px, transparent 50%),
        radial-gradient(at 100% 0%, rgba(16, 185, 129, 0.08) 0px, transparent 50%),
        radial-gradient(at 50% 100%, rgba(15, 23, 42, 0.5) 0px, transparent 50%);
      background-attachment: fixed;
      color: #f8fafc;
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    h1, h2, h3, h4, h5, h6 {
      font-family: 'Outfit', sans-serif;
    }

    .hero-title {
      font-size: 2.6rem;
      font-weight: 800;
      background: linear-gradient(135deg, #ffffff 30%, #38bdf8 70%, #34d399 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 0.8rem;
      letter-spacing: -0.5px;
    }

    .hero-subtitle {
      color: #94a3b8;
      font-size: 1.1rem;
      max-width: 650px;
      margin: 0 auto 1.5rem auto;
      line-height: 1.6;
    }

    .feature-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 14px;
      padding: 26px;
      backdrop-filter: blur(16px);
      transition: all 0.25s ease-in-out;
      height: 100%;
    }

    .feature-card:hover {
      border-color: rgba(56, 189, 248, 0.3);
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
    }

    .feature-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      margin-bottom: 16px;
    }

    .stat-badge {
      background: rgba(15, 23, 42, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 12px;
      padding: 20px;
      text-align: center;
    }

    .stat-number {
      font-size: 2.2rem;
      font-weight: 800;
      font-family: 'Outfit', sans-serif;
      background: linear-gradient(135deg, #38bdf8, #34d399);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .btn-cta {
      background: linear-gradient(135deg, #10b981, #059669);
      border: none;
      color: white;
      font-weight: 600;
      border-radius: 0.75rem;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      padding: 10px 24px;
      box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
    }

    .btn-cta:hover {
      background: linear-gradient(135deg, #059669, #047857);
      transform: translateY(-2px);
      color: white;
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
    }
  </style>
</head>

<body>
  <!-- Standard Navbar -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <div class="container py-5">
    
    <!-- Hero Section -->
    <div class="text-center py-4">
      <h1 class="hero-title">About KrazePlanet</h1>
      <p class="hero-subtitle">
        The hands-on cyber security training platform built on real-world bug bounty reports, penetration testing scenarios, and vulnerability research.
      </p>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-5">
      <div class="col-6 col-md-3">
        <div class="stat-badge">
          <div class="stat-number">260+</div>
          <div class="text-secondary small fw-medium">Active Labs</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-badge">
          <div class="stat-number">100%</div>
          <div class="text-secondary small fw-medium">Real-World Scenarios</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-badge">
          <div class="stat-number">0ms</div>
          <div class="text-secondary small fw-medium">Instant Lab Setup</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-badge">
          <div class="stat-number">24/7</div>
          <div class="text-secondary small fw-medium">Discord Community</div>
        </div>
      </div>
    </div>

    <!-- Core Mission & Pillars -->
    <h3 class="fw-bold mb-4" style="color: #ffffff;">Why Practice on KrazePlanet?</h3>
    <div class="row g-4 mb-5">
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
            <i class="bi bi-shield-lock-fill"></i>
          </div>
          <h5 class="fw-bold text-white mb-2">Authentic Vulnerability Reports</h5>
          <p class="text-secondary small mb-0" style="line-height: 1.6;">
            Every challenge reproduces actual public disclosure reports from HackerOne, Bugcrowd, and CVE advisories to bridge theory and live exploitation.
          </p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="bi bi-lightning-charge-fill"></i>
          </div>
          <h5 class="fw-bold text-white mb-2">Live Multi-Layer Interactions</h5>
          <p class="text-secondary small mb-0" style="line-height: 1.6;">
            From race conditions and rate limiting to IDORs, SQLi, and prototype pollution—train with live HTTP requests, Burp Suite, and automation scripts.
          </p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <h5 class="fw-bold text-white mb-2">Progressive Skill Tracks</h5>
          <p class="text-secondary small mb-0" style="line-height: 1.6;">
            Designed for beginners, security analysts, and seasoned bug bounty hunters looking to master modern web application security testing.
          </p>
        </div>
      </div>
    </div>

    <!-- Call to Action Banner -->
    <div class="p-4 p-md-5 rounded-4 text-center" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.9)); border: 1px solid rgba(56, 189, 248, 0.2);">
      <h3 class="fw-bold text-white mb-2">Ready to Start Hunting?</h3>
      <p class="text-secondary small mb-4">Choose from over 260 interactive vulnerability labs and elevate your testing methodology today.</p>
      <div class="d-flex justify-content-center gap-3">
        <a href="index.php" class="btn-cta">
          <i class="bi bi-play-circle me-2"></i> Explore All Labs
        </a>
        <a href="https://discord.krazeplanet.com" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light px-4 py-2" style="border-radius: 12px; font-weight: 600;">
          <i class="bi bi-discord me-1"></i> Join Discord
        </a>
      </div>
    </div>

  </div>

  <!-- Standard Footer -->
  <?php include __DIR__ . '/../footer/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa"
    crossorigin="anonymous"></script>
</body>
</html>
