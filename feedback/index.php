<?php
$fname = '';
$lname = '';

if(isset($_GET["fname"]) || isset($_GET["lname"])){
  $fname = str_replace('script', '', $_GET['fname']);
  $lname = str_replace('script', '', $_GET['lname']);
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reflected XSS - Script Tag Filter Evasion</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
  integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="icon" href="favicon.ico" />
  
  <style>
    :root {
      --primary-dark: #1a1f36;
      --primary-light: #2d3748;
      --accent-green: #48bb78;
      --accent-blue: #4299e1;
      --accent-orange: #ed8936;
      --accent-red: #f56565;
    }
    
    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      color: #e2e8f0;
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      display: flex;
      flex-direction: column;
    }

    .hero-section {
      flex: 1;
    }

    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      color: var(--accent-green) !important;
    }

    .nav-link {
      font-weight: 500;
      transition: color 0.3s;
    }

    .nav-link:hover {
      color: var(--accent-green) !important;
    }

    .hero-section {
      background: linear-gradient(rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.9)),
                  url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="%231e293b"/><path d="M0 0L100 100M100 0L0 100" stroke="%23374151" stroke-width="1"/></svg>');
      padding: 3rem 0;
    }

    .hero-title {
      font-size: 2.5rem;
      font-weight: 700;
      background: linear-gradient(90deg, #48bb78, #4299e1);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 1rem;
    }

    .hero-subtitle {
      font-size: 1.2rem;
      color: #cbd5e0;
      max-width: 600px;
      margin: 0 auto;
    }

    .section-title {
      margin-top: 40px;
      margin-bottom: 25px;
      font-weight: 700;
      font-size: 1.8rem;
      position: relative;
      padding-bottom: 10px;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 60px;
      height: 4px;
      background: linear-gradient(90deg, var(--accent-green), var(--accent-blue));
      border-radius: 2px;
    }

    .card {
      background: rgba(30, 41, 59, 0.7);
      border-radius: 12px;
      border: 1px solid #334155;
      transition: all 0.3s ease;
      overflow: hidden;
      height: 100%;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      border-color: var(--accent-green);
    }

    .card-img-top {
      border-radius: 12px 12px 0 0;
      height: 250px;
      object-fit: cover;
      border-bottom: 1px solid #334155;
    }

    .card-body {
      padding: 1.5rem;
    }

    .card-title {
      font-weight: 600;
      color: #e2e8f0;
      margin-bottom: 0.5rem;
    }

    .card-subtitle {
      color: var(--accent-green);
      font-weight: 500;
      font-size: 1.1rem;
    }

    .lab-count {
      display: inline-block;
      background: linear-gradient(90deg, var(--accent-green), var(--accent-blue));
      color: #1a202c;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .search-box {
      background: rgba(30, 41, 59, 0.7);
      border: 1px solid #334155;
      color: #e2e8f0;
    }

    .search-box:focus {
      background: rgba(30, 41, 59, 0.9);
      border-color: var(--accent-green);
      box-shadow: 0 0 0 0.2rem rgba(72, 187, 120, 0.25);
      color: #e2e8f0;
    }

    .btn-outline-success {
      border-color: var(--accent-green);
      color: var(--accent-green);
    }

    .btn-outline-success:hover {
      background-color: var(--accent-green);
      border-color: var(--accent-green);
      color: #1a202c;
    }

    .stats-card {
      background: rgba(30, 41, 59, 0.7);
      border-radius: 12px;
      border: 1px solid #334155;
      padding: 1.5rem;
      transition: all 0.3s ease;
      overflow: hidden;
      height: 100%;
    }

    .stats-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      border-color: var(--accent-green);
    }

    .stats-number {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      background: linear-gradient(90deg, #48bb78, #4299e1);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .stats-label {
      color: #94a3b8;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .coming-soon {
      position: relative;
      overflow: hidden;
    }

    .coming-soon::after {
      content: 'Coming Soon';
      position: absolute;
      top: 10px;
      right: -30px;
      background: var(--accent-orange);
      color: #1a202c;
      padding: 5px 30px;
      font-size: 0.75rem;
      font-weight: 600;
      transform: rotate(45deg);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }


    a {
      text-decoration: none;
      color: inherit;
    }

    a:hover {
      color: inherit;
    }

    .navbar {
      background: linear-gradient(90deg, #0f172a 0%, #1e1b4b 100%) !important;
      border-bottom: 1px solid #334155;
      padding: 0.75rem 0;
    }

    .navbar-brand {
      font-weight: 700;
      font-size: 1.4rem;
      background: linear-gradient(90deg, #ef4444, #f97316);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .navbar-brand i {
      background: linear-gradient(90deg, #ef4444, #f97316);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      font-size: 1.5rem;
    }

    .navbar-nav {
      margin: 0 auto;
      gap: 1.5rem;
    }

    .navbar-nav .nav-link {
      color: #94a3b8 !important;
      font-weight: 500;
      padding: 0.5rem 1rem !important;
      border-radius: 0.5rem;
      transition: all 0.3s;
    }

    .navbar-nav .nav-link:hover,
    .navbar-nav .nav-link.active {
      color: #e2e8f0 !important;
      background: rgba(255, 255, 255, 0.05);
    }

    .btn-cta {
      background: linear-gradient(90deg, #22c55e, #16a34a);
      border: none;
      color: white;
      font-weight: 600;
      border-radius: 0.75rem;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
    }

    .btn-cta:hover {
      background: linear-gradient(90deg, #16a34a, #15803d);
      transform: translateY(-2px);
      color: white;
      box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-md navbar-dark sticky-top">
    <div class="container">
      <a class="navbar-brand" href="/">
        <i class="bi bi-fire"></i>KrazePlanetLabs
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
        data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" href="/">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="/labs">Labs</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="about">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="contact">Contact Us</a>
          </li>
        </ul>
        <a href="https://discord.gg/Ujg69RM6qd" target="_blank" rel="noopener noreferrer" class="btn btn-cta ms-auto">
          <i class="bi bi-discord me-2"></i>Join Discord
        </a>
      </div>
    </div>
  </nav>

    <div class="hero-section">
    <div class="container text-center">
      <h1 class="hero-title">GET Parameter Reflection</h1>
      <p class="hero-subtitle">Test for reflected XSS vulnerabilities through GET parameters</p>

      <!-- Lab Section -->
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-8 col-lg-6">
            <div class="card" style="background: rgba(30, 41, 59, 0.9); border: 1px solid #334155; margin-top: 2rem;">
              <div class="card-body p-4">
                <form action="" method="get">
                  <div class="mb-3">
                    <label for="fname" class="form-label" style="color: #e2e8f0; font-weight: 500;">First Name</label>
                    <input class="form-control search-box" type="text" id="fname" placeholder="Enter first name" name="fname" value="<?php echo $fname; ?>">
                  </div>
                  <div class="mb-3">
                    <label for="lname" class="form-label" style="color: #e2e8f0; font-weight: 500;">Last Name</label>
                    <input class="form-control search-box" type="text" id="lname" placeholder="Enter last name" name="lname" value="<?php echo $lname; ?>">
                  </div>
                  <div class="d-grid">
                    <button type="submit" class="btn btn-outline-success">
                      <i class="bi bi-send me-2"></i>Submit
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
      
    </div>
  </div>

  <footer style="text-align: center; padding: 1rem 0; color: #94a3b8; font-size: 0.875rem;">
    <p class="mb-0">© 2026 KrazePlanet. All rights reserved.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa"
    crossorigin="anonymous"></script>
</body>
</html>