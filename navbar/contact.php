<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Contact details shown on this page
$supportEmail = 'contact@krazeplanet.com';
$phoneDisplay = '+91 8527310670';
$whatsappNum  = '918527310670'; // digits only, for wa.me
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Contact Us - Web Security Training Platform</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="icon" href="/favicon.ico" />

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root {
      --font-primary: 'Inter', system-ui, -apple-system, sans-serif;
      --font-heading: 'Outfit', sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
      --rose: #f43f5e;
    }
    * { box-sizing: border-box; }
    body {
      color: #f8fafc;
      min-height: 100vh;
      font-family: var(--font-primary);
      -webkit-font-smoothing: antialiased;
    }
    h1, h2, h3, h4 { font-family: var(--font-heading); }
    a { text-decoration: none; }

    .contact-wrap {
      max-width: 940px;
      margin: 0 auto;
      padding: 4rem 1.25rem 5rem;
    }

    .contact-hero { text-align: center; margin-bottom: 3rem; }
    .contact-hero h1 {
      font-size: 3rem;
      font-weight: 800;
      color: #ffffff;
      margin-bottom: 1rem;
      letter-spacing: -0.01em;
    }
    .contact-hero p {
      color: #94a3b8;
      font-size: 1.05rem;
      line-height: 1.6;
      max-width: 620px;
      margin: 0 auto;
    }

    .contact-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.5rem;
    }

    .contact-card {
      background: rgba(15, 23, 42, 0.55);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 2rem;
      display: flex;
      flex-direction: column;
      backdrop-filter: blur(8px);
      transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    }
    .contact-card:hover {
      transform: translateY(-3px);
      border-color: rgba(244, 63, 94, 0.4);
      box-shadow: 0 14px 34px rgba(0, 0, 0, 0.4), 0 0 22px rgba(244, 63, 94, 0.12);
    }

    .contact-icon {
      width: 56px;
      height: 56px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(244, 63, 94, 0.12);
      border: 1px solid rgba(244, 63, 94, 0.3);
      color: #fb7185;
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .contact-card h3 {
      font-size: 1.35rem;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 0.6rem;
    }
    .contact-card .card-desc {
      color: #94a3b8;
      font-size: 0.95rem;
      line-height: 1.55;
      margin-bottom: 1.25rem;
    }
    .contact-value {
      font-family: var(--font-mono);
      font-size: 1.05rem;
      font-weight: 600;
      color: #f1f5f9;
      margin-bottom: 1.5rem;
      word-break: break-all;
    }

    .contact-btn {
      margin-top: auto;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      width: 100%;
      padding: 12px 20px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 0.98rem;
      color: #ffffff;
      background: linear-gradient(135deg, #f43f5e, #e11d48);
      border: none;
      box-shadow: 0 6px 18px rgba(244, 63, 94, 0.3);
      transition: all 0.2s ease;
    }
    .contact-btn:hover {
      background: linear-gradient(135deg, #e11d48, #be123c);
      color: #ffffff;
      transform: translateY(-1px);
      box-shadow: 0 8px 24px rgba(244, 63, 94, 0.45);
    }

    @media (max-width: 720px) {
      .contact-grid { grid-template-columns: 1fr; }
      .contact-hero h1 { font-size: 2.2rem; }
      .contact-wrap { padding: 2.5rem 1rem 4rem; }
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/navbar.php'; ?>

  <main class="contact-wrap">
    <div class="contact-hero">
      <h1>Get in Touch</h1>
      <p>Have questions, feedback, or lab suggestions? Reach out directly to our team or join the active KrazePlanet Discord community.</p>
    </div>

    <div class="contact-grid">
      <!-- Email Support -->
      <div class="contact-card">
        <div class="contact-icon"><i class="bi bi-envelope-fill"></i></div>
        <h3>Email Support</h3>
        <p class="card-desc">For course inquiries, syllabus details, and general questions.</p>
        <div class="contact-value"><?= htmlspecialchars($supportEmail) ?></div>
        <a class="contact-btn" href="mailto:<?= htmlspecialchars($supportEmail) ?>">
          <i class="bi bi-envelope"></i> Send Email
        </a>
      </div>

      <!-- Phone & WhatsApp -->
      <div class="contact-card">
        <div class="contact-icon"><i class="bi bi-telephone-fill"></i></div>
        <h3>Phone &amp; WhatsApp</h3>
        <p class="card-desc">Call or message directly for enrollment and batch slot assistance.</p>
        <div class="contact-value"><?= htmlspecialchars($phoneDisplay) ?></div>
        <a class="contact-btn" href="https://wa.me/<?= htmlspecialchars($whatsappNum) ?>" target="_blank" rel="noopener noreferrer">
          <i class="bi bi-whatsapp"></i> Contact on WhatsApp
        </a>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../footer/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa"
    crossorigin="anonymous"></script>
</body>
</html>
