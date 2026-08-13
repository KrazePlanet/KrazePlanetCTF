<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Contact - VexiumCTF</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx"
    crossorigin="anonymous">
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

    .contact-list {
      list-style-type: none;
      padding-left: 0;
    }

    .contact-list li {
      padding: 20px;
      background: rgba(15, 23, 42, 0.5);
      border-radius: 8px;
      margin-bottom: 1rem;
      border: 1px solid #334155;
      transition: all 0.3s ease;
    }

    .contact-list li:hover {
      background: rgba(15, 23, 42, 0.8);
      border-color: var(--accent-green);
      transform: translateY(-2px);
    }

    .contact-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 50px;
      height: 50px;
      background: linear-gradient(135deg, var(--accent-green), var(--accent-blue));
      border-radius: 50%;
      margin-right: 15px;
      font-size: 1.5rem;
      color: #1a202c;
    }

    .contact-platform {
      font-weight: 600;
      color: #e2e8f0;
      margin-bottom: 5px;
    }

    .contact-link {
      color: var(--accent-green);
      text-decoration: none;
      transition: color 0.3s;
      word-break: break-all;
    }

    .contact-link:hover {
      color: var(--accent-blue);
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

    .github-card {
      background: linear-gradient(135deg, rgba(72, 187, 120, 0.1), rgba(66, 153, 225, 0.1));
      border: 1px solid #334155;
      border-radius: 12px;
      padding: 2rem;
      text-align: center;
      transition: all 0.3s ease;
    }

    .github-card:hover {
      border-color: var(--accent-green);
      transform: translateY(-3px);
    }

    .github-icon {
      font-size: 3rem;
      color: var(--accent-green);
      margin-bottom: 1rem;
    }

    footer {
      margin-top: 50px;
      padding: 30px 0;
      text-align: center;
      color: #94a3b8;
      border-top: 1px solid #334155;
    }

    .contact-description {
      color: #cbd5e0;
      font-size: 1.1rem;
      line-height: 1.6;
      margin-bottom: 2rem;
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
      <h1 class="hero-title text-center">Contact Me</h1>
      <p class="hero-subtitle text-center">Have questions, suggestions, or want to collaborate? Reach out through any of these channels.</p>
    </div>
  </div>

  <div class="container mb-5">
    <div class="row">
      <div class="col-lg-8">
        <div class="content-card">
          <h2 class="section-title">Get In Touch</h2>
          <p class="contact-description">
            If you have any questions, suggestions, or want to collaborate on security research, feel free to reach out through the following channels. I'm always interested in discussing web security, bug bounty hunting, and improving this platform.
          </p>

          <ul class="contact-list">
            <li>
              <div class="d-flex align-items-center">
                <div class="contact-icon">
                  <i class="bi bi-github"></i>
                </div>
                <div>
                  <div class="contact-platform">GitHub</div>
                  <a href="https://github.com/rix4uni" target="_blank" class="contact-link">https://github.com/rix4uni</a>
                </div>
              </div>
            </li>
            <li>
              <div class="d-flex align-items-center">
                <div class="contact-icon">
                  <i class="bi bi-twitter"></i>
                </div>
                <div>
                  <div class="contact-platform">Twitter</div>
                  <a href="https://twitter.com/rix4uni" target="_blank" class="contact-link">https://twitter.com/rix4uni</a>
                </div>
              </div>
            </li>
            <li>
              <div class="d-flex align-items-center">
                <div class="contact-icon">
                  <i class="bi bi-linkedin"></i>
                </div>
                <div>
                  <div class="contact-platform">LinkedIn</div>
                  <a href="https://linkedin.com/in/rix4uni" target="_blank" class="contact-link">https://linkedin.com/in/rix4uni</a>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
      
      <div class="col-lg-4">
        <div class="github-card">
          <i class="bi bi-github github-icon"></i>
          <h3>Project Repository</h3>
          <p>You can also open issues or discussions directly in the GitHub project repository:</p>
          <a href="https://github.com/KrazePlanet/VexiumCTF" target="_blank" class="contact-link">https://github.com/KrazePlanet/VexiumCTF</a>
        </div>
        
        <div class="content-card mt-4">
          <h4 class="mb-3" style="color: var(--accent-green);">Response Time</h4>
          <p>I typically respond within 24-48 hours. For urgent matters related to security vulnerabilities, please use GitHub issues with appropriate labels.</p>
          
          <h4 class="mt-4 mb-3" style="color: var(--accent-green);">Contributions Welcome</h4>
          <p>If you'd like to contribute to VexiumCTF by adding new labs, improving existing ones, or enhancing documentation, feel free to submit a pull request on GitHub.</p>
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