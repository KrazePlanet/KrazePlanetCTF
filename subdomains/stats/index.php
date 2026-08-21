<?php
/**
 * ============================================================================
 * KrazePlanet Cybersecurity — Contact & Security Inquiry Portal (Real-World Lab)
 * 
 * Features for SQLi Testing:
 *   1. Contact Form Submission with HTTP User-Agent Header Logging (INSERT SQLi)
 *   2. Header-based Blind SQLi / Second-Order SQLi
 *   3. Secret Vault & Flag Extraction (kraze_vault)
 *   4. Automated Mock SMTP Confirmation Email via Mailpit
 * ============================================================================
 */

session_start();

// Load PHPMailer
if (file_exists(__DIR__ . '/../codeshackio/vendor/autoload.php')) {
    require_once __DIR__ . '/../codeshackio/vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── Database Configuration ──────────────────────────────────────────────────
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "KrazePlanet_DB";

$conn = @mysqli_connect($db_host, $db_user, $db_pass);
if (!$conn) {
    die("<div style='font-family:sans-serif;padding:30px;background:#f8d7da;color:#721c24;margin:50px auto;max-width:600px;border-radius:8px;'><h3>Database Connection Error</h3><p>Could not connect to MySQL server. Please ensure XAMPP/LAMPP MySQL is running.</p><p><code>" . htmlspecialchars(mysqli_connect_error()) . "</code></p></div>");
}

@mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $db_name");
@mysqli_select_db($conn, $db_name);

// ── Schema Initialization ───────────────────────────────────────────────────
function setup_kraze_schema($conn) {
    // 1. Contact Submissions Table
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS kraze_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        service_inquiry VARCHAR(100) NOT NULL,
        budget VARCHAR(50) NOT NULL,
        timeline VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        user_agent VARCHAR(500) NOT NULL,
        ip_address VARCHAR(50) DEFAULT '127.0.0.1',
        email_sent TINYINT(1) DEFAULT 0,
        status VARCHAR(30) DEFAULT 'Pending Review',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $chk_c = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM kraze_contacts");
    if ($chk_c && ($row = mysqli_fetch_assoc($chk_c)) && $row['c'] == 0) {
        @mysqli_query($conn, "INSERT INTO kraze_contacts (full_name, email, phone, service_inquiry, budget, timeline, message, user_agent, status) VALUES
            ('Vikram Singhania', 'vikram@fintechsecure.in', '+91 9811223344', 'Web Application Penetration Testing', '$5,000 - $15,000', 'Within 2 Weeks', 'We need a comprehensive gray-box pen test for our new payment gateway API before launch.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36', 'Under Analysis'),
            ('Elena Rostova', 'elena@cyberglobal.eu', '+44 20 7946 0912', 'Red Team Operations', '$15,000+', 'Immediately / Urgent', 'Looking for external adversary emulation and physical social engineering assessment.', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Safari/17.2', 'Scheduled')");
    }

    // 2. Secret Vault Table (CTF Flag & Production API Secrets)
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS kraze_vault (
        id INT AUTO_INCREMENT PRIMARY KEY,
        secret_key VARCHAR(100) NOT NULL,
        secret_value VARCHAR(255) NOT NULL,
        confidentiality_level VARCHAR(50) NOT NULL
    )");

    $chk_v = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM kraze_vault");
    if ($chk_v && ($row = mysqli_fetch_assoc($chk_v)) && $row['c'] == 0) {
        @mysqli_query($conn, "INSERT INTO kraze_vault (secret_key, secret_value, confidentiality_level) VALUES
            ('FLAG_KRAZEPLANET_SQLI', 'FLAG{kr4z3pl4n3t_us3r_4g3nt_1ns3rt_sqli_2026}', 'TOP_SECRET'),
            ('KRAZE_CROWDSTRIKE_API_KEY', 'cs_live_sec_994812aa87b1', 'RESTRICTED'),
            ('INTERNAL_SIEM_ENDPOINT_TOKEN', 'siem_auth_bearer_490124fe', 'CONFIDENTIAL')");
    }
}
setup_kraze_schema($conn);

// ── Helper Function: Send Confirmation Email via SMTP ────────────────────────
function send_inquiry_email($recipient_email, $recipient_name, $details) {
    if (empty($recipient_email) || !class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'mailpit';
        $mail->SMTPAuth   = false;
                $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
        $mail->Port       = 1025;
        $mail->Timeout    = 8; // Prevent hanging on network latency

        // Recipients
        $mail->setFrom('noreply@krazeplanet.com', 'KrazePlanet Security');
        $mail->addAddress($recipient_email, $recipient_name);
        
        // Content
        $mail->isHTML(true);
        $subject = 'Inquiry Received — KrazePlanet Cybersecurity';
        $mail->Subject = $subject;

        $htmlBody = '
        <!DOCTYPE html>
        <html>
        <head><meta charset="utf-8"></head>
        <body style="font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;background-color:#070d18;color:#e2e8f0;padding:24px;margin:0;">
          <div style="max-width:600px;margin:0 auto;background:#0d1527;border:1px solid #1e293b;border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.5);">
            
            <div style="background:linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);padding:24px;text-align:center;">
              <h2 style="color:#070d18;margin:0;font-size:22px;font-weight:800;letter-spacing:-0.5px;">KrazePlanet Cybersecurity</h2>
              <p style="color:#070d18;margin:4px 0 0 0;font-size:13px;font-weight:600;">Security Inquiry Confirmation</p>
            </div>

            <div style="padding:28px 24px;">
              <p style="font-size:16px;color:#ffffff;margin-top:0;">Hello <strong>' . htmlspecialchars($recipient_name) . '</strong>,</p>
              <p style="color:#94a3b8;font-size:14px;line-height:1.6;">
                Thank you for contacting KrazePlanet. We have received your security service inquiry. Our red team and penetration testing specialists will review your requirements and reach out to you within 24 hours.
              </p>

              <div style="background:#091122;border:1px solid #1e293b;border-radius:8px;padding:18px;margin:20px 0;">
                <h4 style="color:#00f2fe;margin:0 0 12px 0;font-size:14px;text-transform:uppercase;letter-spacing:0.5px;">Submitted Information Summary</h4>
                <table style="width:100%;font-size:13px;color:#e2e8f0;border-collapse:collapse;">
                  <tr><td style="padding:5px 0;color:#64748b;width:140px;">Full Name:</td><td style="font-weight:600;">' . htmlspecialchars($details['name']) . '</td></tr>
                  <tr><td style="padding:5px 0;color:#64748b;">Email Address:</td><td style="font-weight:600;">' . htmlspecialchars($details['email']) . '</td></tr>
                  <tr><td style="padding:5px 0;color:#64748b;">Phone:</td><td>' . htmlspecialchars($details['phone']) . '</td></tr>
                  <tr><td style="padding:5px 0;color:#64748b;">Service Inquiry:</td><td style="color:#00f2fe;font-weight:600;">' . htmlspecialchars($details['service']) . '</td></tr>
                  <tr><td style="padding:5px 0;color:#64748b;">Project Budget:</td><td>' . htmlspecialchars($details['budget']) . '</td></tr>
                  <tr><td style="padding:5px 0;color:#64748b;">Project Timeline:</td><td>' . htmlspecialchars($details['timeline']) . '</td></tr>
                  <tr><td style="padding:5px 0;color:#64748b;vertical-align:top;">Message:</td><td style="white-space:pre-line;">' . htmlspecialchars($details['message']) . '</td></tr>
                  <tr><td style="padding:5px 0;color:#64748b;">Client User-Agent:</td><td style="font-size:11px;color:#94a3b8;word-break:break-all;">' . htmlspecialchars($details['user_agent']) . '</td></tr>
                </table>
              </div>

              <p style="color:#94a3b8;font-size:13px;line-height:1.5;margin-bottom:0;">
                If you have urgent requirements, you can also reach our security operations center directly at <a href="mailto:contact@krazeplanet.com" style="color:#00f2fe;text-decoration:none;">contact@krazeplanet.com</a> or <strong>+91 8527310670</strong>.
              </p>
            </div>

            <div style="background:#070d18;padding:16px 24px;text-align:center;font-size:12px;color:#64748b;border-top:1px solid #1e293b;">
              © 2026 KrazePlanet Cybersecurity Inc. · All rights reserved.
            </div>

          </div>
        </body>
        </html>';

        $mail->Body = $htmlBody;
        $mail->AltBody = "Hello {$recipient_name},\n\nThank you for reaching out to KrazePlanet. We received your inquiry regarding {$details['service']}.\n\nKrazePlanet Security Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Return false on error without breaking user flow
        return false;
    }
}

// ── Contact Form Processing & Header Logging ────────────────────────────────
$msg_success = '';
$msg_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = ($_POST['phone_country'] ?? '+91 (IN)') . ' ' . ($_POST['phone'] ?? '');
    $service = $_POST['service_inquiry'] ?? 'General Inquiry';
    $budget = $_POST['budget'] ?? 'Not Specified';
    $timeline = $_POST['timeline'] ?? 'Flexible';
    $message = $_POST['message'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown User-Agent';
    $ip_addr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    if (empty(trim($full_name)) || empty(trim($email)) || empty(trim($message))) {
        $msg_error = "Please fill in all required fields (Full Name, Email, and Message).";
    } else {
        /**
         * [VULNERABLE: User-Agent Header & Form Input INSERT SQL Injection]
         * The HTTP User-Agent and Form inputs are directly interpolated into the INSERT statement.
         */
        $sql = "INSERT INTO kraze_contacts (full_name, email, phone, service_inquiry, budget, timeline, message, user_agent, ip_address) 
                VALUES ('$full_name', '$email', '$phone', '$service', '$budget', '$timeline', '$message', '$user_agent', '$ip_addr')";
        
        $res = @mysqli_query($conn, $sql);
        if ($res) {
            // Dispatch SMTP Confirmation Email
            $details = [
                'name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'service' => $service,
                'budget' => $budget,
                'timeline' => $timeline,
                'message' => $message,
                'user_agent' => $user_agent,
                'ip' => $ip_addr
            ];
            $email_status = send_inquiry_email($email, $full_name, $details);

            $msg_success = "Thank you, " . htmlspecialchars($full_name) . "! Your message and security inquiry have been securely transmitted. A confirmation email has been dispatched to <strong>" . htmlspecialchars($email) . "</strong> via our mail server.";
        } else {
            $msg_error = "SQL Database Error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us — KrazePlanet Cybersecurity & Penetration Testing</title>
    
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Google Font Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --kp-bg: #070d18;
            --kp-card-bg: #0d1527;
            --kp-card-border: #1e293b;
            --kp-cyan: #00f2fe;
            --kp-cyan-hover: #00d2de;
            --kp-cyan-dim: rgba(0, 242, 254, 0.12);
            --kp-text: #e2e8f0;
            --kp-muted: #94a3b8;
            --kp-input-bg: #091122;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--kp-bg);
            color: var(--kp-text);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* ── Top Navbar (Matching Screenshot) ────────────────────────────────── */
        .kp-navbar {
            background-color: rgba(7, 13, 24, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #131d33;
            padding: 18px 40px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .kp-nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .kp-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
            font-size: 1.35rem;
            font-weight: 800;
            text-decoration: none;
            letter-spacing: -0.3px;
        }
        .kp-logo-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #070d18;
            font-size: 1.1rem;
        }
        .kp-nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .kp-nav-links a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        .kp-nav-links a:hover {
            color: #ffffff;
        }
        .btn-contact-nav {
            background: linear-gradient(135deg, #00f2fe 0%, #00c6ff 100%);
            color: #070d18 !important;
            font-weight: 700 !important;
            padding: 8px 22px;
            border-radius: 6px;
            transition: all 0.2s !important;
        }
        .btn-contact-nav:hover {
            box-shadow: 0 0 20px rgba(0, 242, 254, 0.4);
            transform: translateY(-1px);
        }

        /* ── Main Layout ─────────────────────────────────────────────────────── */
        .kp-main-container {
            max-width: 1280px;
            margin: 50px auto 80px;
            padding: 0 24px;
        }

        /* ── Left Column: Contact Information ───────────────────────────────── */
        .contact-info-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }
        .contact-info-desc {
            color: #94a3b8;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 36px;
            max-width: 480px;
        }

        .info-cards-wrapper {
            display: flex;
            flex-direction: column;
            gap: 16px;
            max-width: 480px;
        }
        .info-card-item {
            background: var(--kp-card-bg);
            border: 1px solid #19253d;
            border-radius: 12px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: border-color 0.2s;
        }
        .info-card-item:hover {
            border-color: #00f2fe;
        }
        .info-icon-box {
            width: 48px;
            height: 48px;
            background: rgba(0, 242, 254, 0.08);
            border: 1px solid rgba(0, 242, 254, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--kp-cyan);
            font-size: 1.35rem;
            flex-shrink: 0;
        }
        .info-label {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 1rem;
            color: #ffffff;
            font-weight: 600;
        }

        /* ── Right Column: Send Us A Message Form ───────────────────────────── */
        .contact-form-card {
            background: var(--kp-card-bg);
            border: 1px solid #19253d;
            border-radius: 14px;
            padding: 36px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
        }
        .form-header-title {
            font-size: 1.45rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 28px;
        }

        .kp-form-group {
            margin-bottom: 20px;
        }
        .kp-label {
            font-size: 0.88rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .kp-label i {
            color: var(--kp-cyan);
            font-size: 0.95rem;
        }

        .kp-input, .kp-select, .kp-textarea {
            width: 100%;
            background-color: var(--kp-input-bg);
            border: 1px solid #1e293b;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 0.92rem;
            color: #ffffff;
            outline: none;
            transition: all 0.2s;
        }
        .kp-input:focus, .kp-select:focus, .kp-textarea:focus {
            border-color: var(--kp-cyan);
            box-shadow: 0 0 0 3px rgba(0, 242, 254, 0.15);
        }
        .kp-input::placeholder, .kp-textarea::placeholder {
            color: #475569;
        }
        .kp-select option {
            background-color: #0d1527;
            color: #ffffff;
        }

        .phone-input-group {
            display: flex;
            gap: 10px;
        }
        .phone-prefix-select {
            width: 120px;
            flex-shrink: 0;
        }

        .btn-send-message {
            background: linear-gradient(135deg, #00f2fe 0%, #00c6ff 100%);
            color: #070d18;
            font-weight: 800;
            font-size: 1rem;
            padding: 14px;
            border-radius: 8px;
            border: none;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-send-message:hover {
            box-shadow: 0 0 25px rgba(0, 242, 254, 0.45);
            transform: translateY(-2px);
        }

        /* Floating Arrow Up */
        .floating-chat-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 48px;
            height: 48px;
            background: var(--kp-cyan);
            color: #070d18;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 4px 15px rgba(0, 242, 254, 0.35);
            text-decoration: none;
            transition: transform 0.2s;
        }
        .floating-chat-btn:hover {
            transform: scale(1.1);
            color: #070d18;
        }
    </style>
</head>
<body>

    <!-- ── TOP NAVBAR (Matching Screenshot) ────────────────────────────────── -->
    <nav class="kp-navbar">
        <div class="kp-nav-container">
            <a href="index.php" class="kp-brand">
                <div class="kp-logo-icon"><i class="bi bi-shield-shaded"></i></div>
                KrazePlanet
            </a>

            <ul class="kp-nav-links d-none d-md-flex">
                <li><a href="index.php">Home</a></li>
                <li><a href="#">Services <i class="bi bi-chevron-down small"></i></a></li>
                <li><a href="#">Tools <i class="bi bi-chevron-down small"></i></a></li>
                <li><a href="#">About</a></li>
                <li><a href="#" style="font-size:1.1rem;"><i class="bi bi-brightness-high"></i></a></li>
                <li><a href="index.php" class="btn-contact-nav">Contact Us</a></li>
            </ul>
        </div>
    </nav>

    <!-- ── MAIN CONTENT CONTAINER ───────────────────────────────────────────── -->
    <div class="kp-main-container">

        <!-- Flash Message Alerts -->
        <?php if (!empty($msg_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="background:#450a0a;border-color:#b91c1c;color:#fecaca;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $msg_error; ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($msg_success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="background:#064e3b;border-color:#059669;color:#a7f3d0;">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $msg_success; ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row align-items-start gy-5">
            
            <!-- ── LEFT COLUMN: CONTACT INFORMATION ────────────────────────── -->
            <div class="col-lg-5 pe-lg-4">
                <h1 class="contact-info-title">Contact Information</h1>
                <p class="contact-info-desc">
                    Reach out to us through any of these channels, and our team will respond promptly to discuss your security needs.
                </p>

                <div class="info-cards-wrapper">
                    <!-- Email Card -->
                    <div class="info-card-item">
                        <div class="info-icon-box">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div>
                            <div class="info-label">Email</div>
                            <div class="info-value">contact@krazeplanet.com</div>
                        </div>
                    </div>

                    <!-- Phone Card -->
                    <div class="info-card-item">
                        <div class="info-icon-box">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <div>
                            <div class="info-label">Phone</div>
                            <div class="info-value">+91 8527310670</div>
                        </div>
                    </div>

                    <!-- Location Card -->
                    <div class="info-card-item">
                        <div class="info-icon-box">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div>
                            <div class="info-label">Location</div>
                            <div class="info-value">Serving clients worldwide</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── RIGHT COLUMN: SEND US A MESSAGE FORM ─────────────────────── -->
            <div class="col-lg-7 ps-lg-4">
                <div class="contact-form-card">
                    <h2 class="form-header-title">Send us a Message</h2>

                    <form method="POST" action="index.php">
                        <input type="hidden" name="action" value="send_message">

                        <!-- Full Name* -->
                        <div class="kp-form-group">
                            <label class="kp-label"><i class="bi bi-person"></i> Full Name *</label>
                            <input type="text" name="full_name" class="kp-input" placeholder="John Doe" required>
                        </div>

                        <!-- Email* -->
                        <div class="kp-form-group">
                            <label class="kp-label"><i class="bi bi-envelope"></i> Email *</label>
                            <input type="email" name="email" class="kp-input" placeholder="john@example.com" required>
                        </div>

                        <!-- Phone -->
                        <div class="kp-form-group">
                            <label class="kp-label"><i class="bi bi-telephone"></i> Phone</label>
                            <div class="phone-input-group">
                                <select name="phone_country" class="kp-select phone-prefix-select">
                                    <option value="+91 (IN)">+91 (IN)</option>
                                    <option value="+1 (US)">+1 (US)</option>
                                    <option value="+44 (UK)">+44 (UK)</option>
                                    <option value="+971 (UAE)">+971 (UAE)</option>
                                    <option value="+65 (SG)">+65 (SG)</option>
                                    <option value="+49 (DE)">+49 (DE)</option>
                                    <option value="+61 (AU)">+61 (AU)</option>
                                </select>
                                <input type="text" name="phone" class="kp-input" placeholder="Enter phone number">
                            </div>
                        </div>

                        <!-- Service Inquiry* -->
                        <div class="kp-form-group">
                            <label class="kp-label"><i class="bi bi-building"></i> Service Inquiry *</label>
                            <select name="service_inquiry" class="kp-select" required>
                                <option value="" disabled selected>Select a...</option>
                                <option value="Web Application Penetration Testing">Web Application Penetration Testing</option>
                                <option value="Mobile App Security Assessment">Mobile App Security Assessment</option>
                                <option value="Network & Infrastructure PenTest">Network & Infrastructure PenTest</option>
                                <option value="Cloud Security Audit & Review">Cloud Security Audit & Review</option>
                                <option value="Red Team Operations & Emulation">Red Team Operations & Emulation</option>
                                <option value="Source Code Review & Static Analysis">Source Code Review & Static Analysis</option>
                            </select>
                        </div>

                        <!-- Project Budget -->
                        <div class="kp-form-group">
                            <label class="kp-label"><i class="bi bi-currency-dollar"></i> Project Budget</label>
                            <select name="budget" class="kp-select">
                                <option value="" disabled selected>Select...</option>
                                <option value="< $2,000">&lt; $2,000</option>
                                <option value="$2,000 - $5,000">$2,000 - $5,000</option>
                                <option value="$5,000 - $15,000">$5,000 - $15,000</option>
                                <option value="$15,000+">$15,000+</option>
                            </select>
                        </div>

                        <!-- Project Timeline -->
                        <div class="kp-form-group">
                            <label class="kp-label"><i class="bi bi-clock"></i> Project Timeline</label>
                            <select name="timeline" class="kp-select">
                                <option value="" disabled selected>Select...</option>
                                <option value="Immediately / Urgent">Immediately / Urgent</option>
                                <option value="Within 2 Weeks">Within 2 Weeks</option>
                                <option value="Within 1 Month">Within 1 Month</option>
                                <option value="Flexible">Flexible</option>
                            </select>
                        </div>

                        <!-- Message* -->
                        <div class="kp-form-group">
                            <label class="kp-label"><i class="bi bi-chat-left-text"></i> Message *</label>
                            <textarea name="message" class="kp-textarea" rows="4" placeholder="Tell us about your project..." required></textarea>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-send-message">
                            Send Message
                        </button>
                    </form>
                </div>
            </div>

        </div>

    </div>

    <!-- Floating Arrow Up -->
    <a href="#" class="floating-chat-btn" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;">
        <i class="bi bi-arrow-up"></i>
    </a>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
