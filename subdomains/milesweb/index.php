<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MilesWeb India: Fast, Secure & Reliable Web Hosting Built for Indian Websites</title>
  <link rel="icon" href="https://www.milesweb.in/favicon.ico" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <style>
    :root {
      --mw-blue: #0066ff;
      --mw-blue-hover: #0052cc;
      --mw-dark-blue: #0a1b3d;
      --mw-green: #10b981;
      --mw-bg-light: #f8fafc;
      --mw-text-main: #0f172a;
      --mw-text-muted: #64748b;
      --mw-border: #e2e8f0;
      --mw-orange: #ff5722;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
      background-color: #ffffff;
      color: var(--mw-text-main);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      overflow-x: hidden;
    }

    /* Top Announcement Bar */
    .top-announcement-bar {
      background: linear-gradient(90deg, #dcfce7 0%, #fef08a 50%, #dcfce7 100%);
      color: #065f46;
      font-size: 0.85rem;
      font-weight: 700;
      padding: 0.5rem 1rem;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.6rem;
      border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .top-announcement-bar a {
      color: #047857;
      text-decoration: underline;
      font-weight: 800;
    }
    .countdown-timer {
      background: #047857;
      color: #ffffff;
      padding: 0.15rem 0.5rem;
      border-radius: 4px;
      font-family: monospace;
      font-size: 0.8rem;
    }

    /* Navbar */
    .navbar-milesweb {
      background-color: #ffffff;
      border-bottom: 1px solid var(--mw-border);
      padding: 0.9rem 2.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    }
    .brand-logo-wrap {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      text-decoration: none;
    }
    .brand-title {
      font-size: 1.6rem;
      font-weight: 800;
      color: #0a2540;
      letter-spacing: -0.5px;
      line-height: 1;
    }
    .brand-tagline {
      font-size: 0.65rem;
      color: var(--mw-text-muted);
      font-weight: 600;
      display: block;
      margin-top: 2px;
    }
    .flag-icon {
      font-size: 1.2rem;
      margin-left: 4px;
    }

    .nav-menu-links {
      display: flex;
      align-items: center;
      gap: 1.6rem;
      list-style: none;
    }
    .nav-menu-links a {
      color: #334155;
      text-decoration: none;
      font-size: 0.92rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.3rem;
      transition: color 0.2s;
    }
    .nav-menu-links a:hover {
      color: var(--mw-blue);
    }

    .btn-account-outline {
      border: 1px solid var(--mw-border);
      background: #ffffff;
      color: #334155;
      font-weight: 700;
      font-size: 0.88rem;
      padding: 0.5rem 1.2rem;
      border-radius: 6px;
      text-decoration: none;
      transition: all 0.2s;
    }
    .btn-account-outline:hover {
      border-color: var(--mw-blue);
      color: var(--mw-blue);
      box-shadow: 0 2px 8px rgba(0, 102, 255, 0.15);
    }

    /* Hero Section */
    .hero-milesweb {
      background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
      padding: 4rem 2.5rem 3rem 2.5rem;
    }
    .hero-container {
      max-width: 1250px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 3.5rem;
      align-items: center;
    }
    .hero-badge-pill {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: rgba(16, 185, 129, 0.1);
      color: #047857;
      font-size: 0.88rem;
      font-weight: 700;
      padding: 0.35rem 0.9rem;
      border-radius: 20px;
      margin-bottom: 1.2rem;
    }
    .hero-badge-pill strong {
      color: var(--mw-green);
    }
    .hero-headline {
      font-size: 3.2rem;
      font-weight: 800;
      color: var(--mw-text-main);
      line-height: 1.15;
      margin-bottom: 1rem;
      letter-spacing: -1px;
    }
    .hero-subtext {
      font-size: 1.1rem;
      color: var(--mw-text-muted);
      margin-bottom: 1.8rem;
    }
    .hero-check-list {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      margin-bottom: 2rem;
      list-style: none;
    }
    .hero-check-list li {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      font-size: 0.98rem;
      font-weight: 600;
      color: #334155;
    }
    .hero-check-list i {
      color: var(--mw-green);
      font-size: 1.1rem;
    }
    .price-callout-wrap {
      display: flex;
      align-items: baseline;
      gap: 0.4rem;
      margin-bottom: 1.8rem;
    }
    .price-symbol { font-size: 1.8rem; font-weight: 800; color: var(--mw-text-main); }
    .price-number { font-size: 3.5rem; font-weight: 900; color: var(--mw-text-main); line-height: 1; }
    .price-period { font-size: 1.1rem; font-weight: 600; color: var(--mw-text-muted); }

    .btn-start-now {
      background: var(--mw-blue);
      color: #ffffff;
      font-size: 1.1rem;
      font-weight: 700;
      padding: 0.9rem 2.5rem;
      border-radius: 8px;
      border: none;
      text-decoration: none;
      display: inline-block;
      box-shadow: 0 6px 20px rgba(0, 102, 255, 0.35);
      transition: all 0.2s ease;
      cursor: pointer;
    }
    .btn-start-now:hover {
      background: var(--mw-blue-hover);
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(0, 102, 255, 0.45);
    }
    .hero-guarantee-text {
      font-size: 0.85rem;
      color: var(--mw-text-muted);
      margin-top: 1rem;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    /* Right Hero Card Graphic */
    .hero-graphic-card {
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      border-radius: 16px;
      padding: 2.5rem;
      position: relative;
      box-shadow: 0 12px 35px rgba(0, 102, 255, 0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }
    .sale-badge-box {
      position: absolute;
      top: 1.5rem;
      right: 1.5rem;
      background: #ffffff;
      border: 2px dashed var(--mw-blue);
      padding: 0.6rem 1.2rem;
      border-radius: 8px;
      font-weight: 900;
      color: #1e40af;
      font-size: 1.5rem;
    }
    .hero-img-illustration {
      max-width: 280px;
      border-radius: 50%;
      margin: 1.5rem 0;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .trust-pill-badge {
      background: #ffffff;
      border: 1px solid var(--mw-border);
      padding: 0.6rem 1.4rem;
      border-radius: 30px;
      font-weight: 700;
      font-size: 0.9rem;
      color: var(--mw-text-main);
      display: flex;
      align-items: center;
      gap: 0.5rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
      margin-bottom: 1.5rem;
    }
    .datacenter-flags-row {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      font-size: 0.85rem;
      color: var(--mw-text-muted);
      font-weight: 600;
    }

    /* Ratings Bar Row */
    .ratings-bar-milesweb {
      background: #ffffff;
      border-y: 1px solid var(--mw-border);
      padding: 1.2rem 2.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 3rem;
      flex-wrap: wrap;
      border-top: 1px solid var(--mw-border);
      border-bottom: 1px solid var(--mw-border);
    }
    .rating-item-box {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      font-weight: 700;
      font-size: 0.92rem;
      color: #334155;
    }
    .stars-yellow { color: #f59e0b; }

    /* Hosting Plans Pricing Section */
    .plans-section-milesweb {
      padding: 5rem 2.5rem;
      background-color: #ffffff;
    }
    .plans-container {
      max-width: 1250px;
      margin: 0 auto;
    }
    .plans-header-text {
      text-align: center;
      margin-bottom: 3.5rem;
    }
    .plans-main-title {
      font-size: 2.4rem;
      font-weight: 800;
      color: var(--mw-text-main);
      margin-bottom: 0.8rem;
    }
    .plans-sub-title {
      color: var(--mw-text-muted);
      font-size: 1.05rem;
    }

    .plans-cards-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.5rem;
      align-items: stretch;
    }

    .plan-card-mw {
      background: #ffffff;
      border: 1px solid var(--mw-border);
      border-radius: 16px;
      padding: 2.2rem 1.8rem;
      display: flex;
      flex-direction: column;
      position: relative;
      transition: all 0.25s ease;
    }
    .plan-card-mw:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.08);
      border-color: #cbd5e1;
    }
    .plan-card-mw.popular-plan {
      border: 2px solid var(--mw-blue);
      box-shadow: 0 12px 30px rgba(0, 102, 255, 0.15);
    }

    .popular-top-badge {
      position: absolute;
      top: -14px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--mw-blue);
      color: #ffffff;
      font-weight: 800;
      font-size: 0.72rem;
      padding: 0.35rem 1.2rem;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .free-domain-tag-above {
      font-size: 0.75rem;
      font-weight: 800;
      color: var(--mw-blue);
      text-transform: uppercase;
      margin-bottom: 0.4rem;
    }
    .plan-name-title {
      font-size: 1.4rem;
      font-weight: 800;
      color: var(--mw-text-main);
      margin-bottom: 0.3rem;
    }
    .plan-desc-text {
      font-size: 0.84rem;
      color: var(--mw-text-muted);
      margin-bottom: 1.2rem;
      min-height: 38px;
    }

    .discount-pill-badge {
      background: #fff7ed;
      color: var(--mw-orange);
      font-size: 0.75rem;
      font-weight: 800;
      padding: 0.2rem 0.5rem;
      border-radius: 4px;
      display: inline-block;
      margin-bottom: 0.4rem;
    }
    .original-price-strike {
      text-decoration: line-through;
      color: #94a3b8;
      font-size: 0.9rem;
      margin-left: 0.4rem;
    }
    .plan-price-row {
      display: flex;
      align-items: baseline;
      gap: 0.2rem;
      margin-bottom: 0.3rem;
    }
    .plan-price-symbol { font-size: 1.4rem; font-weight: 800; }
    .plan-price-val { font-size: 2.6rem; font-weight: 900; line-height: 1; }
    .plan-price-period { font-size: 0.9rem; color: var(--mw-text-muted); font-weight: 600; }

    .billing-total-note {
      font-size: 0.78rem;
      color: var(--mw-text-muted);
      margin-bottom: 1rem;
    }
    .deal-badge-row {
      display: flex;
      gap: 0.4rem;
      margin-bottom: 1.5rem;
    }
    .deal-pill {
      background: #eff6ff;
      color: var(--mw-blue);
      font-size: 0.75rem;
      font-weight: 700;
      padding: 0.2rem 0.5rem;
      border-radius: 4px;
    }

    .btn-choose-plan {
      width: 100%;
      padding: 0.75rem;
      border-radius: 8px;
      font-weight: 700;
      font-size: 0.95rem;
      text-align: center;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.2s;
      margin-bottom: 2rem;
      display: block;
    }
    .btn-choose-plan.outline {
      border: 1px solid var(--mw-blue);
      color: var(--mw-blue);
      background: #ffffff;
    }
    .btn-choose-plan.outline:hover {
      background: #eff6ff;
    }
    .btn-choose-plan.solid {
      background: var(--mw-blue);
      color: #ffffff;
      border: none;
      box-shadow: 0 4px 14px rgba(0, 102, 255, 0.3);
    }
    .btn-choose-plan.solid:hover {
      background: var(--mw-blue-hover);
    }

    .plan-features-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 0.85rem;
      font-size: 0.88rem;
      color: #334155;

    }
    .plan-features-list li {
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }
    .plan-features-list i {
      color: var(--mw-green);
      font-size: 1rem;
    }
    .pill-free-forever {
      background: #dcfce7;
      color: #15803d;
      font-size: 0.7rem;
      font-weight: 700;
      padding: 0.1rem 0.4rem;
      border-radius: 4px;
      margin-left: auto;
    }

    /* Floating Chat Widget */
    .floating-chat-btn {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      background: var(--mw-blue);
      color: #ffffff;
      width: 56px;
      height: 56px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      box-shadow: 0 8px 25px rgba(0, 102, 255, 0.4);
      cursor: pointer;
      z-index: 10000;
      transition: transform 0.2s;
    }
    .floating-chat-btn:hover {
      transform: scale(1.08);
    }

    /* Footer */
    footer.milesweb-footer {
      background-color: var(--mw-dark-blue);
      color: #94a3b8;
      padding: 4rem 2.5rem 2rem 2.5rem;
      font-size: 0.88rem;
      margin-top: auto;
    }
    .footer-container {
      max-width: 1250px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 2.5rem;
      margin-bottom: 3rem;
    }
    .footer-col h4 {
      color: #ffffff;
      font-size: 1rem;
      font-weight: 700;
      margin-bottom: 1.2rem;
    }
    .footer-col ul {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 0.7rem;
    }
    .footer-col a {
      color: #94a3b8;
      text-decoration: none;
      transition: color 0.2s;
    }
    .footer-col a:hover {
      color: #ffffff;
    }
    .footer-bottom-bar {
      max-width: 1250px;
      margin: 0 auto;
      border-top: 1px solid rgba(255,255,255,0.1);
      padding-top: 1.8rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.82rem;
    }

    @media (max-width: 992px) {
      .hero-container { grid-template-columns: 1fr; }
      .plans-cards-grid { grid-template-columns: repeat(2, 1fr); }
      .footer-container { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
      .plans-cards-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <!-- Top Announcement Bar -->
  <div class="top-announcement-bar">
    <span>🇮🇳 Freedom Sale Is LIVE: <strong>80% Off Hosting</strong> + Free Domain</span>
    <span>- Ends in <span class="countdown-timer" id="timer">12h 58m 51s</span></span>
    <a href="#plansSection">View Plans &rarr;</a>
  </div>

  <!-- Navbar -->
  <nav class="navbar-milesweb">
    <a href="/milesweb/" class="brand-logo-wrap">
      <div>
        <span class="brand-title">MilesWeb<span class="flag-icon">🇮🇳</span></span>
        <span class="brand-tagline">Your Hosting, Our Responsibility</span>
      </div>
    </a>

    <ul class="nav-menu-links d-none d-lg-flex">
      <li><a href="#plansSection">Hosting <i class="bi bi-chevron-down small"></i></a></li>
      <li><a href="#">WordPress <i class="bi bi-chevron-down small"></i></a></li>
      <li><a href="#">VPS &amp; Dedicated <i class="bi bi-chevron-down small"></i></a></li>
      <li><a href="#">Domain &amp; Email <i class="bi bi-chevron-down small"></i></a></li>
      <li><a href="#">Pricing</a></li>
      <li><a href="#">Support <i class="bi bi-chevron-down small"></i></a></li>
      <li><a href="#">About Us <i class="bi bi-chevron-down small"></i></a></li>
    </ul>

    <a href="#" class="btn-account-outline">My Account</a>
  </nav>

  <!-- Hero Section -->
  <section class="hero-milesweb">
    <div class="hero-container">
      
      <!-- Left Hero Column -->
      <div>
        <div id="welcome" class="welcome-banner mb-3" style="display: none; background: #e0f2fe; color: #0369a1; padding: 0.6rem 1rem; border-radius: 6px; font-weight: 600; font-size: 0.95rem; border: 1px solid #bae6fd;">
          Welcome to MilesWeb Hosting!
        </div>
        <div class="hero-badge-pill">
          Get 80% Off Hosting with <strong>Free Domain</strong>
        </div>
        <h1 class="hero-headline">Launch your website in minutes</h1>
        <p class="hero-subtext">Fast, secure &amp; reliable hosting built for Indian websites.</p>

        <ul class="hero-check-list">
          <li><i class="bi bi-check-circle-fill"></i> Up to 20x faster website performance</li>
          <li><i class="bi bi-check-circle-fill"></i> Free SSL, email &amp; daily backups</li>
          <li><i class="bi bi-check-circle-fill"></i> Create your website faster with AI</li>
        </ul>

        <div class="price-callout-wrap">
          <span class="price-symbol">&#8377;</span>
          <span class="price-number">69</span>
          <span class="price-period">/mo</span>
        </div>

        <a href="#plansSection" class="btn-start-now">Start Now</a>

        <div class="hero-guarantee-text">
          <i class="bi bi-shield-check text-success fs-5"></i>
          <span>30-day money-back guarantee &bull; Cancel anytime</span>
        </div>
      </div>

      <!-- Right Hero Graphic -->
      <div class="hero-graphic-card">
        <div class="sale-badge-box">
          SALE<br><span style="font-size: 2.2rem; color: var(--mw-blue);">80%</span><br><span style="font-size: 1rem;">OFF</span>
        </div>
        <img src="https://www.milesweb.in/assets/img/mw/web-hosting.png" class="hero-img-illustration" alt="Hosting Customer">
        
        <div class="trust-pill-badge">
          <i class="bi bi-trophy-fill text-warning fs-5"></i>
          <span>1 million + Websites Trust Us</span>
        </div>

        <div class="datacenter-flags-row">
          <span>Global data center:</span>
          <span>🇮🇳 India</span>
          <span>🇺🇸 US</span>
          <span>🇬🇧 UK</span>
          <span>🇦🇺 AU</span>
          <span>🇸🇬 SG</span>
          <span>🇨🇦 CA</span>
        </div>
      </div>

    </div>
  </section>

  <!-- Ratings Bar -->
  <div class="ratings-bar-milesweb">
    <div class="rating-item-box">
      <span>Google</span>
      <span class="fw-bold">4.6/5</span>
      <span class="stars-yellow">★★★★★</span>
    </div>
    <div class="rating-item-box">
      <i class="bi bi-check2-circle text-primary fs-5"></i>
      <span>Trusted by 1M+ websites</span>
    </div>
    <div class="rating-item-box">
      <i class="bi bi-flag-fill text-danger"></i>
      <span>Indian company since 2012</span>
    </div>
  </div>

  <!-- Hosting Plans Section (Image 2) -->
  <section class="plans-section-milesweb" id="plansSection">
    <div class="plans-container">
      <div class="plans-header-text">
        <h2 class="plans-main-title">Choose the Perfect Web Hosting Plan</h2>
        <p class="plans-sub-title">High-performance hosting tailored for blogs, businesses, and e-commerce websites.</p>
      </div>

      <div class="plans-cards-grid">
        
        <!-- Plan 1: Starter -->
        <div class="plan-card-mw">
          <div class="free-domain-tag-above">FREE DOMAIN</div>
          <h3 class="plan-name-title">Starter</h3>
          <p class="plan-desc-text">Great for first-time users.</p>

          <div>
            <span class="discount-pill-badge">83% OFF</span>
            <span class="original-price-strike">&#8377;399</span>
          </div>

          <div class="plan-price-row">
            <span class="plan-price-symbol">&#8377;</span>
            <span class="plan-price-val">69</span>
            <span class="plan-price-period">/mo</span>
          </div>

          <p class="billing-total-note">For 36 months, you pay &#8377;2,484.</p>

          <a href="#" class="btn-choose-plan outline">Choose Plan</a>

          <ul class="plan-features-list">
            <li><i class="bi bi-check-lg"></i> <strong>1 website</strong></li>
            <li><i class="bi bi-check-lg"></i> <strong>Free domain for 1 year</strong></li>
            <li><i class="bi bi-check-lg"></i> 10 GB NVMe storage</li>
            <li><i class="bi bi-check-lg"></i> 1 email account <span class="pill-free-forever">Free forever</span></li>
            <li><i class="bi bi-check-lg"></i> Daily backups</li>
            <li><i class="bi bi-check-lg"></i> Free SSL for your website</li>
            <li><i class="bi bi-check-lg"></i> WordPress ready</li>
            <li><i class="bi bi-check-lg"></i> AI website builder</li>
          </ul>
        </div>

        <!-- Plan 2: Premium (MOST POPULAR) -->
        <div class="plan-card-mw popular-plan">
          <div class="popular-top-badge">MOST POPULAR</div>

          <h3 class="plan-name-title">Premium</h3>
          <p class="plan-desc-text">Best for blogs &amp; startup websites.</p>

          <div>
            <span class="discount-pill-badge">80% OFF</span>
            <span class="original-price-strike">&#8377;499</span>
          </div>

          <div class="plan-price-row">
            <span class="plan-price-symbol">&#8377;</span>
            <span class="plan-price-val">99</span>
            <span class="plan-price-period">/mo</span>
          </div>

          <p class="billing-total-note">For 36 months, you pay &#8377;3,564.</p>

          <div class="deal-badge-row">
            <span class="deal-pill">+2 mo free</span>
            <span class="deal-pill" style="background:#fff7ed; color:var(--mw-orange);">Limited-Time Deal</span>
          </div>

          <a href="#" class="btn-choose-plan solid">Choose Plan</a>

          <ul class="plan-features-list">
            <li><i class="bi bi-check-lg"></i> <strong>25 websites</strong></li>
            <li><i class="bi bi-check-lg"></i> <strong>Free domain for 1 year</strong></li>
            <li><i class="bi bi-check-lg"></i> 50 GB NVMe storage</li>
            <li><i class="bi bi-check-lg"></i> 50 email accounts <span class="pill-free-forever">Free forever</span></li>
            <li><i class="bi bi-check-lg"></i> Daily backups</li>
            <li><i class="bi bi-check-lg"></i> Free SSL for every website</li>
            <li><i class="bi bi-check-lg"></i> WordPress ready</li>
            <li><i class="bi bi-check-lg"></i> AI website builder</li>
          </ul>
        </div>

        <!-- Plan 3: Business -->
        <div class="plan-card-mw">
          <h3 class="plan-name-title">Business</h3>
          <p class="plan-desc-text">Node.js ready hosting.</p>

          <div>
            <span class="discount-pill-badge">79% OFF</span>
            <span class="original-price-strike">&#8377;699</span>
          </div>

          <div class="plan-price-row">
            <span class="plan-price-symbol">&#8377;</span>
            <span class="plan-price-val">149</span>
            <span class="plan-price-period">/mo</span>
          </div>

          <p class="billing-total-note">For 36 months, you pay &#8377;5,364.</p>

          <div class="deal-badge-row">
            <span class="deal-pill">+2 mo free</span>
          </div>

          <a href="#" class="btn-choose-plan outline">Choose Plan</a>

          <ul class="plan-features-list">
            <li><i class="bi bi-check-lg"></i> <strong>50 websites</strong></li>
            <li><i class="bi bi-check-lg"></i> <strong>Free domain for 1 year</strong></li>
            <li><i class="bi bi-check-lg"></i> 100 GB NVMe storage</li>
            <li><i class="bi bi-check-lg"></i> 150 email accounts <span class="pill-free-forever">Free forever</span></li>
            <li><i class="bi bi-check-lg"></i> Daily &amp; On-Demand Backups</li>
            <li><i class="bi bi-check-lg"></i> Free SSL for every website</li>
            <li><i class="bi bi-check-lg"></i> WordPress ready</li>
            <li><i class="bi bi-check-lg"></i> AI website builder</li>
          </ul>
        </div>

        <!-- Plan 4: Cloud Startup -->
        <div class="plan-card-mw">
          <h3 class="plan-name-title">Cloud Startup</h3>
          <p class="plan-desc-text">20x more power with cloud hosting.</p>

          <div>
            <span class="discount-pill-badge">67% OFF</span>
            <span class="original-price-strike">&#8377;1,499</span>
          </div>

          <div class="plan-price-row">
            <span class="plan-price-symbol">&#8377;</span>
            <span class="plan-price-val">499</span>
            <span class="plan-price-period">/mo</span>
          </div>

          <p class="billing-total-note">For 36 months, you pay &#8377;17,964.</p>

          <div class="deal-badge-row">
            <span class="deal-pill">+2 mo free</span>
          </div>

          <a href="#" class="btn-choose-plan outline">Choose Plan</a>

          <ul class="plan-features-list">
            <li><i class="bi bi-check-lg"></i> <strong>100 websites</strong></li>
            <li><i class="bi bi-check-lg"></i> <strong>Free domain for 1 year</strong></li>
            <li><i class="bi bi-check-lg"></i> 150 GB NVMe storage</li>
            <li><i class="bi bi-check-lg"></i> 200 email accounts <span class="pill-free-forever">Free forever</span></li>
            <li><i class="bi bi-check-lg"></i> Daily &amp; On-Demand Backups</li>
            <li><i class="bi bi-check-lg"></i> Free SSL for every website</li>
            <li><i class="bi bi-check-lg"></i> WordPress ready</li>
            <li><i class="bi bi-check-lg"></i> AI website builder</li>
          </ul>
        </div>

      </div>
    </div>
  </section>

  <!-- Floating Chat Button -->
  <div class="floating-chat-btn">
    <i class="bi bi-chat-fill"></i>
  </div>

  <!-- Footer -->
  <footer class="milesweb-footer">
    <div class="footer-container">
      <div class="footer-col">
        <h4>Hosting Solutions</h4>
        <ul>
          <li><a href="#">Web Hosting India</a></li>
          <li><a href="#">cPanel Hosting</a></li>
          <li><a href="#">WordPress Hosting</a></li>
          <li><a href="#">VPS Hosting</a></li>
          <li><a href="#">Dedicated Servers</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Domains &amp; Email</h4>
        <ul>
          <li><a href="#">Domain Name Registration</a></li>
          <li><a href="#">Transfer Domain</a></li>
          <li><a href="#">Business Email</a></li>
          <li><a href="#">Google Workspace</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Company</h4>
        <ul>
          <li><a href="#">About MilesWeb</a></li>
          <li><a href="#">Data Center Locations</a></li>
          <li><a href="#">Customer Reviews</a></li>
          <li><a href="#">Careers</a></li>
          <li><a href="#">Affiliate Program</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Support &amp; Contact</h4>
        <ul>
          <li><a href="#">24/7 Support Desk</a></li>
          <li><a href="#">Knowledge Base</a></li>
          <li><a href="#">Blog</a></li>
          <li><a href="#">Contact Us</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom-bar">
      <span>&copy; 2026 MilesWeb.in. All Rights Reserved. Built for Web Vulnerability &amp; CTF Lab Testing.</span>
      <div class="d-flex gap-3">
        <a href="#" style="color:#94a3b8; text-decoration:none;">Privacy Policy</a>
        <a href="#" style="color:#94a3b8; text-decoration:none;">Terms of Service</a>
      </div>
    </div>
  </footer>

  <script>
    // Simple Countdown Timer simulation
    let hours = 12, minutes = 58, seconds = 51;
    setInterval(() => {
      seconds--;
      if (seconds < 0) { seconds = 59; minutes--; }
      if (minutes < 0) { minutes = 59; hours--; }
      if (hours < 0) { hours = 23; }
      const timerEl = document.getElementById('timer');
      if (timerEl) {
        timerEl.innerText = `${hours}h ${minutes}m ${seconds}s`;
      }
    }, 1000);
  </script>

  <script>
    function checkHashXSS() {
      if (window.location.hash) {
        var hashData = window.location.hash.substring(1);
        if (hashData) {
          var welcomeEl = document.getElementById('welcome');
          if (welcomeEl) {
            welcomeEl.style.display = 'block';
            // Vulnerable innerHTML Sink
            welcomeEl.innerHTML = 'Welcome ' + decodeURIComponent(hashData);

            // Execute scripts if injected via hash
            var scripts = welcomeEl.getElementsByTagName('script');
            for (var i = 0; i < scripts.length; i++) {
              eval(scripts[i].innerText || scripts[i].textContent);
            }
          }
        }
      }
    }
    window.addEventListener('DOMContentLoaded', checkHashXSS);
    window.addEventListener('hashchange', checkHashXSS);
  </script>
</body>
</html>
