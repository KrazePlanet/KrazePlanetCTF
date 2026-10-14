<?php
// settings.php - Email and password live on the central KrazePlanet account
// page (shared by every lab site); this page points there.
require_once __DIR__ . '/../config/sso.php';
startKrazeSession();

if (!krazeLoggedIn()) {
    header('Location: ' . krazeLoginUrl($_SERVER['REQUEST_URI']));
    exit;
}

$username = $_SESSION['username'];
$email = $_SESSION['user_email'];
$accountPageUrl = krazeSsoAccountUrl();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Account Settings - KrazePlanet</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="icon" href="favicon.ico" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-dark: #070b14;
      --accent-green: #10b981;
      --font-primary: 'Inter', sans-serif;
      --font-heading: 'Outfit', sans-serif;
    }
    body {
      background: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #070b14 70%);
      background-attachment: fixed;
      color: #f1f5f9;
      min-height: 100vh;
      font-family: var(--font-primary);
      display: flex;
      flex-direction: column;
    }
    .navbar {
      background: rgba(7, 11, 20, 0.85) !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(12px);
      padding: 0.85rem 0;
    }
    .navbar-nav .nav-link {
      color: #94a3b8 !important;
      font-weight: 500;
      padding: 0.5rem 1rem !important;
      border-radius: 0.5rem;
      transition: all 0.3s;
    }
    .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active {
      color: #f8fafc !important;
      background: rgba(255, 255, 255, 0.05);
    }
    .settings-card {
      background: rgba(15, 23, 42, 0.75);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(16px);
      max-width: 650px;
      width: 100%;
    }
    .form-control {
      background: rgba(7, 11, 20, 0.6) !important;
      border: 1px solid rgba(255, 255, 255, 0.1) !important;
      color: #f1f5f9 !important;
      border-radius: 12px;
      padding: 0.7rem 1rem;
    }
    .form-control:focus {
      border-color: #10b981 !important;
      box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.15) !important;
    }
    .btn-cta {
      background: linear-gradient(135deg, #10b981, #059669);
      border: none;
      color: white;
      font-weight: 700;
      border-radius: 12px;
      padding: 0.65rem 1.5rem;
      transition: all 0.3s;
    }
    .btn-cta:hover {
      background: linear-gradient(135deg, #059669, #047857);
      transform: translateY(-2px);
      color: white;
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/navbar.php'; ?>

  <main class="container d-flex align-items-center justify-content-center flex-grow-1 py-5">
    <div class="settings-card">
      <h3 class="fw-bold text-light mb-4" style="font-family: 'Outfit';">
        <i class="bi bi-gear text-success me-2"></i>Account Settings
      </h3>

      <div class="mb-3">
        <label class="form-label text-muted small fw-semibold">Username</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($username); ?>" disabled style="opacity: 0.7;">
      </div>
      <div class="mb-4">
        <label class="form-label text-muted small fw-semibold">Email Address</label>
        <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" disabled style="opacity: 0.7;">
      </div>
      <p class="text-muted small mb-3">
        One KrazePlanet account works on every KrazePlanet lab site. Change your email or password on your account page.
      </p>
      <a class="btn btn-cta" href="<?php echo htmlspecialchars($accountPageUrl); ?>" target="_blank" rel="noopener">Open account page &rarr;</a>
    </div>
  </main>

  <?php include __DIR__ . '/../footer/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
