<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Robust PHPMailer Multi-Path Loader
$phpMailerLoaded = false;
$mailerPaths = [
    __DIR__ . '/PHPMailer',
    __DIR__ . '/../subdomains/PHPMailer',
    __DIR__ . '/../PHPMailer',
    '/opt/lampp/htdocs/subdomains/PHPMailer',
    '/opt/lampp/htdocs/navbar/PHPMailer',
    '/opt/lampp/htdocs/PHPMailer'
];
foreach ($mailerPaths as $mDir) {
    if (file_exists("{$mDir}/Exception.php")) {
        require_once "{$mDir}/Exception.php";
        require_once "{$mDir}/PHPMailer.php";
        require_once "{$mDir}/SMTP.php";
        $phpMailerLoaded = true;
        break;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$msg_sent = false;
$msg_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? 'General Inquiry');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $msg_error = 'Please fill in all required fields (Name, Email, and Message).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg_error = 'Please provide a valid email address.';
    } else {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host        = 'mailpit';
            $mail->SMTPAuth    = false;
            $mail->Port        = 1025;
            $mail->SMTPSecure  = '';
            $mail->SMTPAutoTLS = false;
            $mail->Timeout     = 3;

            $mail->setFrom('noreply@krazeplanet.com', 'KrazePlanet Contact Form');
            $mail->addAddress('support@krazeplanet.com', 'KrazePlanet Support');
            $mail->addReplyTo($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'New Contact Inquiry: [' . htmlspecialchars($subject) . '] from ' . htmlspecialchars($name);

            $mail->Body = '
            <div style="font-family: -apple-system, BlinkMacSystemFont, Roboto, sans-serif; background:#070b14; padding:40px 20px; color:#f8fafc;">
                <div style="max-width:560px; margin:0 auto; background:#0f172a; border:1px solid rgba(255,255,255,0.12); border-radius:16px; padding:36px; box-shadow:0 20px 40px rgba(0,0,0,0.6);">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:20px;">
                        <span style="font-size:24px; font-weight:800; color:#38bdf8; letter-spacing:-0.5px;">KrazePlanet</span>
                        <span style="font-size:12px; font-weight:600; background:rgba(56,189,248,0.15); color:#38bdf8; padding:3px 8px; border-radius:8px; border:1px solid rgba(56,189,248,0.3);">Contact Inquiry</span>
                    </div>
                    <h2 style="font-size:20px; font-weight:700; margin-bottom:16px; color:#ffffff;">New Contact Form Message</h2>
                    
                    <div style="background:#070b14; border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:18px; margin-bottom:20px;">
                        <p style="margin:0 0 10px 0; font-size:14px; color:#94a3b8;"><strong style="color:#ffffff;">Sender Name:</strong> ' . htmlspecialchars($name) . '</p>
                        <p style="margin:0 0 10px 0; font-size:14px; color:#94a3b8;"><strong style="color:#ffffff;">Sender Email:</strong> <a href="mailto:' . htmlspecialchars($email) . '" style="color:#38bdf8; text-decoration:none;">' . htmlspecialchars($email) . '</a></p>
                        <p style="margin:0 0 10px 0; font-size:14px; color:#94a3b8;"><strong style="color:#ffffff;">Subject:</strong> ' . htmlspecialchars($subject) . '</p>
                        <p style="margin:0; font-size:13px; color:#64748b;"><strong style="color:#94a3b8;">Timestamp:</strong> ' . date('Y-m-d H:i:s T') . '</p>
                    </div>

                    <h4 style="font-size:15px; font-weight:700; color:#ffffff; margin-bottom:8px;">Message Content:</h4>
                    <div style="background:#1e293b; border-left:4px solid #38bdf8; border-radius:6px; padding:16px; font-size:14px; line-height:1.6; color:#f1f5f9; white-space:pre-wrap; margin-bottom:24px;">' . htmlspecialchars($message) . '</div>

                    <p style="color:#64748b; font-size:12px; line-height:1.5; margin:0;">
                        You can directly reply to this email to reply back to <strong>' . htmlspecialchars($email) . '</strong>.
                    </p>
                </div>
            </div>';

            $mail->send();
            $msg_sent = true;
        } catch (Exception $e) {
            error_log("Contact Mail Send Error: " . $mail->ErrorInfo);
            $msg_error = 'Failed to send message. Please try again or join our Discord community.';
        }
    }
}
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

    .contact-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 16px;
      padding: 32px;
      backdrop-filter: blur(16px);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
    }

    .form-control, .form-select {
      background-color: #070b14;
      border: 1px solid rgba(255, 255, 255, 0.14);
      color: #ffffff;
      font-size: 14px;
      padding: 11px 14px;
      border-radius: 9px;
      transition: all 0.2s;
    }

    .form-control:focus, .form-select:focus {
      background-color: #070b14;
      border-color: #38bdf8;
      color: #ffffff;
      box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
    }

    .btn-send {
      background: linear-gradient(135deg, #0284c7, #0369a1);
      color: #ffffff;
      font-weight: 700;
      font-size: 15px;
      padding: 12px;
      border-radius: 9px;
      border: none;
      width: 100%;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-send:hover {
      background: linear-gradient(135deg, #0369a1, #075985);
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(2, 132, 199, 0.35);
      color: #ffffff;
    }

    .discord-banner {
      background: linear-gradient(135deg, rgba(88, 101, 242, 0.15), rgba(64, 78, 237, 0.25));
      border: 1px solid rgba(88, 101, 242, 0.4);
      border-radius: 16px;
      padding: 28px;
      transition: all 0.25s ease-in-out;
    }

    .discord-banner:hover {
      border-color: #5865F2;
      box-shadow: 0 10px 30px rgba(88, 101, 242, 0.25);
    }
  </style>
</head>

<body>
  <!-- Standard Navbar -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <div class="container py-5">
    
    <!-- Hero Section -->
    <div class="text-center py-4">
      <h1 class="hero-title">Get in Touch</h1>
      <p class="hero-subtitle">
        Have questions, feedback, or lab suggestions? Reach out directly to our team or join the active KrazePlanet Discord community.
      </p>
    </div>

    <div class="row g-4 justify-content-center">
      
      <!-- Left Column: Official Discord & Info -->
      <div class="col-lg-5">
        <div class="discord-banner mb-4">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: #5865F2; font-size: 1.6rem; color: #fff;">
              <i class="bi bi-discord"></i>
            </div>
            <div>
              <h5 class="fw-bold text-white mb-0">Join Our Discord</h5>
              <div class="text-secondary small">Instant support, lab writeups & discussions</div>
            </div>
          </div>
          <p class="text-light small mb-3 opacity-75">
            Connect directly with researchers, discuss attack techniques, report issues, and collaborate on CTF challenges.
          </p>
          <a href="https://discord.krazeplanet.com" target="_blank" rel="noopener noreferrer" class="btn btn-primary w-100 fw-bold py-2" style="background: #5865F2; border: none; border-radius: 10px;">
            <i class="bi bi-discord me-2"></i> Join Community Discord &rarr;
          </a>
        </div>

        <div class="contact-card">
          <h6 class="fw-bold text-white mb-3">Community Channels</h6>
          
          <div class="d-flex align-items-start gap-3 mb-3">
            <i class="bi bi-envelope-fill text-info fs-5 mt-1"></i>
            <div>
              <div class="text-white small fw-bold">Email Support</div>
              <a href="mailto:contact@krazeplanet.com" class="text-info small text-decoration-none fw-semibold">contact@krazeplanet.com</a>
            </div>
          </div>

          <div class="d-flex align-items-start gap-3 mb-3">
            <i class="bi bi-mortarboard-fill text-success fs-5 mt-1"></i>
            <div>
              <div class="text-white small fw-bold">Academy & Courses</div>
              <a href="https://academy.krazeplanet.com" target="_blank" class="text-info small text-decoration-none">academy.krazeplanet.com</a>
            </div>
          </div>

          <div class="d-flex align-items-start gap-3">
            <i class="bi bi-shield-check text-warning fs-5 mt-1"></i>
            <div>
              <div class="text-white small fw-bold">Lab Bug Bounty</div>
              <div class="text-secondary small">Vulnerability training platform</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Interactive Contact Form -->
      <div class="col-lg-7">
        <div class="contact-card">
          <h4 class="fw-bold text-white mb-1">Send us a Message</h4>
          <p class="text-secondary small mb-4">We usually respond within 24 hours.</p>

          <?php if ($msg_sent): ?>
            <div class="alert alert-success py-3 px-4 border-0 mb-4" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981 !important; color: #a7f3d0; border-radius: 10px;">
              <i class="bi bi-check-circle-fill me-2"></i> Thank you! Your message has been sent successfully. We will get back to you soon.
            </div>
          <?php endif; ?>

          <?php if (!empty($msg_error)): ?>
            <div class="alert alert-danger py-2 px-3 border-0 mb-4" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444 !important; color: #fca5a5; border-radius: 10px;">
              <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($msg_error) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="contact.php">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-light mb-1">Your Name</label>
                <input type="text" name="name" class="form-control" placeholder="John Doe" required autofocus>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-light mb-1">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@domain.com" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold text-light mb-1">Subject</label>
              <input type="text" name="subject" class="form-control" placeholder="Lab Question / Suggestion / General Inquiry" required>
            </div>

            <div class="mb-4">
              <label class="form-label small fw-semibold text-light mb-1">Message</label>
              <textarea name="message" class="form-control" rows="5" placeholder="Type your message here..." required></textarea>
            </div>

            <button type="submit" class="btn-send">
              <i class="bi bi-send-fill"></i>
              <span>Send Message &rarr;</span>
            </button>
          </form>
        </div>
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
