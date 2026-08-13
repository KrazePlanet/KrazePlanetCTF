<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>About - VexiumCTF</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

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
      border-bottom: 1px solid #2d3748;
      margin-bottom: 2rem;
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

    .content-card {
      background: rgba(30, 41, 59, 0.7);
      border-radius: 12px;
      border: 1px solid #334155;
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .feature-list {
      list-style-type: none;
      padding-left: 0;
    }

    .feature-list li {
      padding: 12px 0;
      border-bottom: 1px solid #334155;
      display: flex;
      align-items: flex-start;
    }

    .feature-list li:last-child {
      border-bottom: none;
    }

    .feature-icon {
      color: var(--accent-green);
      margin-right: 15px;
      font-size: 1.2rem;
      margin-top: 2px;
      flex-shrink: 0;
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

    .github-link {
      display: inline-flex;
      align-items: center;
      background: rgba(30, 41, 59, 0.7);
      border: 1px solid #334155;
      color: #e2e8f0;
      padding: 0.75rem 1.5rem;
      border-radius: 8px;
      text-decoration: none;
      transition: all 0.3s;
      font-weight: 500;
    }

    .github-link:hover {
      background: rgba(30, 41, 59, 0.9);
      border-color: var(--accent-green);
      color: var(--accent-green);
      transform: translateY(-2px);
    }

    .github-icon {
      margin-right: 10px;
      font-size: 1.2rem;
    }

    .stats-card {
      background: rgba(30, 41, 59, 0.7);
      border-radius: 10px;
      padding: 1.5rem;
      border: 1px solid #334155;
      text-align: center;
      transition: transform 0.3s;
      height: 100%;
    }

    .stats-card:hover {
      transform: translateY(-3px);
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

    footer {
      margin-top: 50px;
      padding: 30px 0;
      text-align: center;
      color: #94a3b8;
      border-top: 1px solid #334155;
    }

    a {
      color: var(--accent-green);
      text-decoration: none;
      transition: color 0.3s;
    }

    a:hover {
      color: var(--accent-blue);
    }

    .mission-statement {
      background: linear-gradient(135deg, rgba(72, 187, 120, 0.1), rgba(66, 153, 225, 0.1));
      border-left: 4px solid var(--accent-green);
      padding: 1.5rem;
      border-radius: 0 8px 8px 0;
      margin: 2rem 0;
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-md navbar-dark sticky-top" style="background-color: var(--primary-dark);">
    <div class="container-fluid">
      <a class="navbar-brand" href="/">
        <i class="bi bi-shield-shaded me-2"></i>VexiumCTF
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
        data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link active" href="about.php">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="contact.php">Contact Us</a>
          </li>
        </ul>
        <form class="d-flex" role="search">
          <input class="form-control search-box me-2" type="search" placeholder="Search labs..." aria-label="Search">
          <button class="btn btn-outline-success" type="submit">Search</button>
        </form>
      </div>
    </div>
  </nav>

  <div class="hero-section">
    <div class="container">
      <h1 class="hero-title text-center">About VexiumCTF</h1>
      <p class="hero-subtitle text-center">Your hands-on platform for mastering web application security through practical, real-world vulnerability labs.</p>
    </div>
  </div>

  <div class="container mb-5">
    <div class="row">
      <div class="col-lg-8">
        <div class="content-card">
          <h2 class="section-title">Our Mission</h2>
          <div class="mission-statement">
            <p>Welcome to <strong>VexiumCTF</strong> – your go-to resource for learning web application penetration testing in a safe and practical environment.</p>
          </div>
          
          <p>I'm passionate about cybersecurity, especially web application security. The goal of <strong>VexiumCTF</strong> is to provide hands-on labs where security enthusiasts, students, and professionals can learn, practice, and improve their skills in web penetration testing in a controlled environment.</p>

          <h3 class="mt-4 mb-3" style="color: var(--accent-green);">What We Offer</h3>
          <p>This project offers intentionally vulnerable web applications designed to help you understand common web vulnerabilities such as:</p>
          
          <ul class="feature-list">
            <li>
              <i class="bi bi-bug-fill feature-icon"></i>
              <div>
                <strong>Cross-Site Scripting (XSS)</strong> - Learn how attackers inject malicious scripts into web pages viewed by other users.
              </div>
            </li>
            <li>
              <i class="bi bi-database-fill feature-icon"></i>
              <div>
                <strong>SQL Injection (SQLi)</strong> - Understand how to exploit database vulnerabilities through unsanitized input fields.
              </div>
            </li>
            <li>
              <i class="bi bi-terminal-fill feature-icon"></i>
              <div>
                <strong>Remote Code Execution (RCE)</strong> - Practice exploiting vulnerabilities that allow attackers to execute arbitrary commands.
              </div>
            </li>
            <li>
              <i class="bi bi-shield-exclamation feature-icon"></i>
              <div>
                <strong>Insecure Direct Object References (IDOR)</strong> - Learn how to ACCESS unauthorized data by manipulating object references.
              </div>
            </li>
            <li>
              <i class="bi bi-arrow-left-right feature-icon"></i>
              <div>
                <strong>Server-Side Request Forgery (SSRF)</strong> - Understand how attackers can make the server send requests to internal resources.
              </div>
            </li>
          </ul>

          <p>The labs are structured to guide you through each vulnerability, explain how they work, and demonstrate how attackers exploit them.</p>

          <div class="d-flex mt-4">
            <a href="https://github.com/KrazePlanet/VexiumCTF" target="_blank" class="github-link">
              <i class="bi bi-github github-icon"></i>
              View Project on GitHub
            </a>
          </div>

          <p class="mt-4">Feel free to contribute or suggest improvements – together we can build a stronger and more secure web!</p>
        </div>
      </div>
      
      <div class="col-lg-4">
        <div class="content-card">
          <h3 class="mb-4" style="color: var(--accent-green);">Platform Stats</h3>
          
          <div class="row">
            <div class="col-6 mb-4">
              <div class="stats-card">
                <div class="stats-number">217+</div>
                <div class="stats-label">Labs</div>
              </div>
            </div>
            <div class="col-6 mb-4">
              <div class="stats-card">
                <div class="stats-number">22</div>
                <div class="stats-label">Categories</div>
              </div>
            </div>
            <div class="col-6 mb-4">
              <div class="stats-card">
                <div class="stats-number">3</div>
                <div class="stats-label">Levels</div>
              </div>
            </div>
            <div class="col-6 mb-4">
              <div class="stats-card">
                <div class="stats-number">100%</div>
                <div class="stats-label">Practical</div>
              </div>
            </div>
          </div>
          
          <h4 class="mt-4 mb-3" style="color: var(--accent-green);">Learning Path</h4>
          <p>Our labs are designed with a progressive learning curve:</p>
          <ul class="feature-list">
            <li>
              <i class="bi bi-1-circle feature-icon"></i>
              <div><strong>Beginner</strong> - Basic vulnerability concepts</div>
            </li>
            <li>
              <i class="bi bi-2-circle feature-icon"></i>
              <div><strong>Intermediate</strong> - Advanced exploitation techniques</div>
            </li>
            <li>
              <i class="bi bi-3-circle feature-icon"></i>
              <div><strong>Advanced</strong> - Real-world scenario simulations</div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <footer>
    <div class="container">
      <p>© 2026 KrazePlanet. All rights reserved.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa"
    crossorigin="anonymous"></script>
</body>
</html>