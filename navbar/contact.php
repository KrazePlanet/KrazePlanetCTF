<?php
require_once __DIR__ . '/../config/sso.php';
startKrazeSession(); // public page: no sign-in needed

// Contact details shown on this page
$supportEmail = 'contact@krazeplanet.com';
$phoneDisplay = '+91 8527310670';
$whatsappNum  = '918527310670';  // digits only, for wa.me
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact - KrazePlanet Labs</title>
  <meta name="description" content="Contact KrazePlanet Labs by email or WhatsApp.">
  <link rel="icon" type="image/png" href="https://krazeplanet.com/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    :root {
      --bg: #070b14;
      --card: #0b1324;
      --line: rgba(255, 255, 255, 0.08);
      --text: #e2e8f0;
      --muted: #94a3b8;
      --accent: #38bdf8;
    }
    body { background: var(--bg); color: #cbd5e1; font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; flex-direction: column; }

    .contact-wrap { max-width: 920px; margin: 0 auto; padding: 4.5rem 1.25rem 5rem; width: 100%; }

    .contact-hero { text-align: center; margin-bottom: 3rem; }
    .contact-hero h1 { font-family: 'Outfit', sans-serif; font-size: 2.75rem; font-weight: 700; color: #fff; letter-spacing: -0.6px; margin: 0 0 .9rem; }
    .contact-hero p { color: var(--muted); font-size: 1.1rem; line-height: 1.6; max-width: 560px; margin: 0 auto; }

    .contact-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; }

    .contact-card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 1.75rem; display: flex; flex-direction: column; }

    .contact-head { display: flex; align-items: center; gap: 14px; margin-bottom: 1rem; }
    .contact-icon { width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
      background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.22); color: var(--accent); font-size: 1.15rem; }
    .contact-card h2 { font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 700; color: #fff; margin: 0; }

    .card-desc { color: var(--muted); font-size: 0.95rem; line-height: 1.55; margin: 0 0 1.25rem; }

    .contact-value { font-size: 1.2rem; font-weight: 600; color: #fff; padding: 12px 14px; background: var(--bg); border: 1px solid var(--line); border-radius: 10px; margin-bottom: 1.25rem; overflow-wrap: anywhere; }

    .contact-actions { margin-top: auto; display: flex; gap: 10px; }
    .contact-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 18px; border-radius: 10px;
      font-weight: 600; font-size: 0.95rem; text-decoration: none; border: 1px solid transparent; transition: background .15s ease, border-color .15s ease, color .15s ease; }
    .contact-btn-primary { background: #0284c7; color: #fff; }
    .contact-btn-primary:hover { background: #0ea5e9; color: #fff; }

    @media (max-width: 720px) {
      .contact-wrap { padding: 2.5rem 1rem 4rem; }
      .contact-hero { margin-bottom: 2rem; }
      .contact-hero h1 { font-size: 2.1rem; }
      .contact-hero p { font-size: 1rem; }
      .contact-grid { grid-template-columns: 1fr; }
      .contact-card { padding: 1.4rem; }
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/navbar.php'; ?>

  <main class="contact-wrap flex-grow-1">
    <div class="contact-hero">
      <h1>Get in Touch</h1>
      <p>Have questions, feedback, or lab suggestions? Reach out directly to our team by email or WhatsApp.</p>
    </div>

    <div class="contact-grid">
      <!-- Email -->
      <div class="contact-card">
        <div class="contact-head">
          <div class="contact-icon"><i class="bi bi-envelope"></i></div>
          <h2>Email</h2>
        </div>
        <p class="card-desc">For lab problems, account help, feedback, and general questions.</p>
        <div class="contact-value"><?= $e($supportEmail) ?></div>
        <div class="contact-actions">
          <a class="contact-btn contact-btn-primary" href="mailto:<?= $e($supportEmail) ?>"><i class="bi bi-envelope"></i> Send Email</a>
        </div>
      </div>

      <!-- WhatsApp (messages only, no calls) -->
      <div class="contact-card">
        <div class="contact-head">
          <div class="contact-icon"><i class="bi bi-whatsapp"></i></div>
          <h2>WhatsApp</h2>
        </div>
        <p class="card-desc">Message us directly for quick questions about the labs.</p>
        <div class="contact-value"><?= $e($phoneDisplay) ?></div>
        <div class="contact-actions">
          <a class="contact-btn contact-btn-primary" href="https://wa.me/<?= $e($whatsappNum) ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"></i> Message on WhatsApp</a>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
